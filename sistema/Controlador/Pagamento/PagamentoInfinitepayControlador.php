<?php

namespace sistema\Controlador\Pagamento;

use sistema\Nucleo\Controlador;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PedidoModelo;
use sistema\Modelo\PostModelo;
use sistema\Biblioteca\InfinitePay;
use sistema\Nucleo\Conexao;

class PagamentoInfinitepayControlador extends Controlador
{
    public function __construct()
    {
        parent::__construct('templates/site/views');
    }

    /**
     * Processa pagamento com InfinitePay
     */
    public function processar(PedidoModelo $pedido, array $dados): array
    {
        $infinitePay = new InfinitePay($pedido->id);

        try {
            // Monta itens para o checkout
            $itens = [
                [
                    'quantidade' => 1,
                    'valor' => (float) $pedido->valor_total, // ← A classe InfinitePay converte
                    'descricao' => $pedido->total_votos . ' votos - ' . strip_tags($dados['post_titulo'] ?? 'Votação')
                ]
            ];

            // Dados do cliente - sempre envia pelo menos o nome e CPF
            $dadosCliente = [
                'nome' => $pedido->cliente_nome,
                'cpf' => $pedido->cliente_cpf
            ];

            // Adiciona email e telefone se disponíveis
            if (!empty($pedido->cliente_email)) {
                $dadosCliente['email'] = $pedido->cliente_email;
            }
            if (!empty($pedido->cliente_telefone)) {
                $dadosCliente['telefone'] = $pedido->cliente_telefone;
            }

            // Gera link de pagamento com URL de retorno personalizada
            $redirectUrl = Helpers::url('pedido/pagamento/' . $pedido->id);
            $resultado = $infinitePay->gerarLinkPagamento(
                $itens,
                'pedido-' . $pedido->id,
                $dadosCliente,
                null, // sem endereço
                $redirectUrl
            );

            if ($resultado['erro']) {
                return $resultado;
            }

            // Atualiza pedido com dados do InfinitePay
            $pedido->infinitepay_link = $resultado['link'];
            $pedido->infinitepay_slug = $resultado['slug'];
            $pedido->infinitepay_order_nsu = $resultado['order_nsu'];
            $pedido->gateway_usado = 'INFINITEPAY';
            $pedido->status = 'AGUARDANDO';

            return ['erro' => false];
        } catch (\Exception $e) {
            error_log('Erro InfinitePay: ' . $e->getMessage());
            return ['erro' => true, 'mensagem' => 'Erro ao processar pagamento InfinitePay'];
        }
    }

    /**
     * Verifica status do pagamento
     */
    public function verificar(PedidoModelo $pedido): void
    {
        if ($pedido->status == 'PAGO') {
            Helpers::json('pago', 'Pagamento já confirmado');
            return;
        }

        // Tenta pegar o NSU e o SLUG da URL (retorno do checkout) ou do banco
        $transactionNsu = $_GET['transaction_nsu'] ?? $pedido->infinitepay_transaction_nsu;
        $slug = $_GET['slug'] ?? $pedido->infinitepay_slug;

        $infinitePay = new InfinitePay($pedido->id);

        // Passa os dados capturados para a verificação
        $resultado = $infinitePay->verificarPagamento(
            $pedido->infinitepay_order_nsu,
            $transactionNsu,
            $slug
        );

        if ($resultado['erro'] || !$resultado['paid']) {
            Helpers::json('aguardando', 'Aguardando confirmação do pagamento...');
            return;
        }

        // Pagamento confirmado - agora salva o NSU que veio da URL no banco
        if (empty($pedido->infinitepay_transaction_nsu)) {
            $pedido->infinitepay_transaction_nsu = $transactionNsu;
        }

        $this->confirmarPagamento($pedido, $resultado);
    }

    /**
     * Confirma pagamento e atualiza banco
     */
    private function confirmarPagamento(PedidoModelo $pedido, array $dadosPagamento = []): void
    {
        $pdo = Conexao::getInstancia();
        $pdo->beginTransaction();

        try {
            // Atualiza pedido
            $stmt = $pdo->prepare("
                UPDATE pedidos SET 
                    status = 'PAGO', 
                    pago_em = NOW(),
                    metodo_pagamento = :metodo
                WHERE id = :id
            ");

            $stmt->execute([
                ':metodo' => $dadosPagamento['capture_method'] === 'pix' ? 'PIX' : 'CARTAO',
                ':id' => $pedido->id
            ]);

            // Adiciona votos
            $post = new PostModelo();
            $post->id = $pedido->post_id;

            if (!$post->adicionarVotos($pedido->total_votos)) {
                throw new \Exception("Erro ao somar votos");
            }

            if (!$post->adicionarReceita((float)$pedido->valor_total)) {
                throw new \Exception("Erro ao somar receita");
            }

            $pdo->commit();
            Helpers::json('pago', 'Pagamento confirmado!');
        } catch (\Exception $e) {
            $pdo->rollBack();
            Helpers::json('erro', 'Erro: ' . $e->getMessage());
        }
    }

    /**
     * Webhook do InfinitePay
     */
    public function webhook(): void
    {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);

        // Valida se recebeu dados
        if (!$dados || !isset($dados['invoice_slug']) || !isset($dados['order_nsu'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos']);
            return;
        }

        // Busca pedido pelo order_nsu
        $pedidoModelo = new PedidoModelo();
        $pedidos = $pedidoModelo->busca(
            "infinitepay_order_nsu = :nsu AND gateway_usado = 'INFINITEPAY'",
            "nsu={$dados['order_nsu']}"
        )->resultado(true);

        if (!$pedidos) {
            http_response_code(404);
            echo json_encode(['error' => 'Pedido não encontrado']);
            return;
        }

        $pedido = $pedidos[0];

        // Se já foi pago, retorna sucesso
        if ($pedido->status === 'PAGO') {
            http_response_code(200);
            echo json_encode(['status' => 'já processado']);
            return;
        }

        // Processa pagamento
        $pdo = Conexao::getInstancia();
        $pdo->beginTransaction();

        try {
            // Atualiza pedido
            $stmt = $pdo->prepare("
                UPDATE pedidos SET 
                    status = 'PAGO', 
                    pago_em = NOW(),
                    infinitepay_transaction_nsu = :transaction_nsu,
                    infinitepay_receipt_url = :receipt_url,
                    metodo_pagamento = :metodo
                WHERE id = :id
            ");

            $stmt->execute([
                ':transaction_nsu' => $dados['transaction_nsu'] ?? null,
                ':receipt_url' => $dados['receipt_url'] ?? null,
                ':metodo' => $dados['capture_method'] === 'pix' ? 'PIX' : 'CARTAO',
                ':id' => $pedido->id
            ]);

            // Adiciona votos e receita
            $post = new PostModelo();
            $post->id = $pedido->post_id;

            if (!$post->adicionarVotos($pedido->total_votos)) {
                throw new \Exception("Erro ao somar votos");
            }

            if (!$post->adicionarReceita((float)$pedido->valor_total)) {
                throw new \Exception("Erro ao somar receita");
            }

            $pdo->commit();

            http_response_code(200);
            echo json_encode(['status' => 'sucesso']);
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Erro no webhook InfinitePay: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao processar']);
        }
    }

    /**
     * Redireciona para o link de pagamento InfinitePay
     */
    public function redirecionarPagamento(PedidoModelo $pedido): void
    {
        if (!empty($pedido->infinitepay_link)) {
            header('Location: ' . $pedido->infinitepay_link);
            exit;
        }
    }

    /**
     * Renderiza página de sucesso (caso usuário volte da InfinitePay)
     */
    public function renderizarSucesso(PedidoModelo $pedido, string $whatsapp): void
    {
        echo $this->template->renderizar('pagamento.html', [
            'titulo' => 'Processando Pagamento',
            'pedido' => $pedido,
            'tipoPagamento' => 'INFINITEPAY',
            'whatsapp' => $whatsapp,
            'gateway' => 'INFINITEPAY',
            'mensagem' => 'Aguardando confirmação do pagamento...'
        ]);
    }
}
