<?php

namespace sistema\Controlador\Pagamento;

use sistema\Nucleo\Controlador;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PedidoModelo;
use sistema\Modelo\PostModelo;
use sistema\Biblioteca\Asaas;
use sistema\Nucleo\Conexao;

class PagamentoAsaasControlador extends Controlador
{
    public function __construct()
    {
        parent::__construct('templates/site/views');
    }

    /**
     * Processa pagamento com Asaas
     */
    public function processar(PedidoModelo $pedido, array $dados): array
    {
        $asaas = new Asaas($pedido->id);
        $formaPagamento = $dados['forma_pagamento'] ?? 'PIX';

        try {
            if ($formaPagamento === 'CREDIT_CARD') {
                $resultado = $this->processarCartao($asaas, $pedido, $dados);
            } else {
                $resultado = $this->processarPix($asaas, $pedido, $dados);
            }

            if ($resultado['erro']) {
                return $resultado;
            }

            // Atualiza pedido com dados do Asaas
            $pedido->asaas_id = $resultado['id_transacao'];
            $pedido->gateway_usado = 'ASAAS';
            $pedido->metodo_pagamento = $formaPagamento === 'CREDIT_CARD' ? 'CARTAO' : 'PIX';

            if ($formaPagamento === 'PIX') {
                $pedido->pix_qrcode = $resultado['payload'];
                $pedido->pix_img = $resultado['encodedImage'];
            }

            // Verifica status
            if (isset($resultado['status'])) {
                if ($resultado['status'] === 'CONFIRMED' || $resultado['status'] === 'RECEIVED') {
                    $pedido->status = 'PAGO';
                    $pedido->pago_em = date('Y-m-d H:i:s');

                    // Adiciona votos e receita
                    $post = new PostModelo();
                    $post->id = $pedido->post_id;
                    $post->adicionarVotos($pedido->total_votos);
                    $post->adicionarReceita((float)$pedido->valor_total);
                } else {
                    $pedido->status = 'AGUARDANDO';
                }
            }

            return ['erro' => false];
        } catch (\Exception $e) {
            error_log('Erro Asaas: ' . $e->getMessage());
            return ['erro' => true, 'mensagem' => 'Erro ao processar pagamento Asaas'];
        }
    }

    /**
     * Processa pagamento PIX
     */
    private function processarPix(Asaas $asaas, PedidoModelo $pedido, array $dados): array
    {
        return $asaas->gerarPixVenda(
            [
                'nome' => $pedido->cliente_nome,
                'cpf' => $pedido->cliente_cpf,
                'email' => $pedido->cliente_email,
                'telefone' => $pedido->cliente_telefone
            ],
            (float) $pedido->valor_total,
            "Votos para " . ($dados['post_titulo'] ?? 'Votação'),
            (string) $pedido->id
        );
    }

    /**
     * Processa pagamento com Cartão
     */
    private function processarCartao(Asaas $asaas, PedidoModelo $pedido, array $dados): array
    {
        if (
            empty($dados['cartao_nome']) || empty($dados['cartao_numero']) ||
            empty($dados['cartao_mes']) || empty($dados['cartao_ano']) ||
            empty($dados['cartao_cvv'])
        ) {
            return ['erro' => true, 'mensagem' => 'Dados do cartão incompletos.'];
        }

        return $asaas->gerarCobrancaCartao(
            [
                'nome' => $pedido->cliente_nome,
                'cpf' => $pedido->cliente_cpf,
                'email' => $pedido->cliente_email,
                'telefone' => $pedido->cliente_telefone,
                'cep' => $dados['cep'] ?? '01310100'
            ],
            [
                'nome' => $dados['cartao_nome'],
                'numero' => $dados['cartao_numero'],
                'mes' => $dados['cartao_mes'],
                'ano' => $dados['cartao_ano'],
                'cvv' => $dados['cartao_cvv']
            ],
            (float) $pedido->valor_total,
            "Votos para " . ($dados['post_titulo'] ?? 'Votação'),
            (string) $pedido->id
        );
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

        $asaas = new Asaas($pedido->id);
        $cobranca = $asaas->consultarCobranca($pedido->asaas_id);

        if (isset($cobranca->status) && ($cobranca->status == 'RECEIVED' || $cobranca->status == 'CONFIRMED')) {
            $this->confirmarPagamento($pedido);
        } else {
            Helpers::json('aguardando', 'Aguardando pagamento...');
        }
    }

    /**
     * Confirma pagamento e atualiza banco
     */
    private function confirmarPagamento(PedidoModelo $pedido): void
    {
        $pdo = Conexao::getInstancia();
        $pdo->beginTransaction();

        try {
            // Atualiza pedido
            $stmtPedido = $pdo->prepare("UPDATE pedidos SET status = 'PAGO', pago_em = NOW() WHERE id = :id");
            $stmtPedido->bindValue(':id', $pedido->id);
            $stmtPedido->execute();

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
     * Webhook do Asaas
     */
    public function webhook(): void
    {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);

        // Valida evento
        if (!isset($dados['event']) || ($dados['event'] != 'PAYMENT_CONFIRMED' && $dados['event'] != 'PAYMENT_RECEIVED')) {
            http_response_code(200);
            echo json_encode(['status' => 'evento ignorado']);
            return;
        }

        $pagamento = $dados['payment'];
        $idTransacaoAsaas = $pagamento['id'];

        // Busca pedido
        $pedidoModelo = new PedidoModelo();
        $pedidos = $pedidoModelo->busca("asaas_id = :id AND gateway_usado = 'ASAAS'", "id={$idTransacaoAsaas}")->resultado(true);

        if (!$pedidos) {
            http_response_code(404);
            echo json_encode(['error' => 'Pedido não encontrado']);
            return;
        }

        $pedido = $pedidos[0];

        // Se já foi pago
        if ($pedido->status === 'PAGO') {
            http_response_code(200);
            echo json_encode(['status' => 'já processado']);
            return;
        }

        // Processa pagamento
        $pdo = Conexao::getInstancia();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("UPDATE pedidos SET status = 'PAGO', pago_em = NOW() WHERE id = :id");
            $stmt->bindValue(':id', $pedido->id);
            $stmt->execute();

            $post = new PostModelo();
            $post->id = $pedido->post_id;
            $post->adicionarVotos($pedido->total_votos);
            $post->adicionarReceita((float)$pedido->valor_total);

            $pdo->commit();
            http_response_code(200);
            echo json_encode(['status' => 'sucesso']);
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Erro webhook Asaas: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao processar']);
        }
    }

    /**
     * Renderiza página de pagamento PIX
     */
    public function renderizarPagamentoPix(PedidoModelo $pedido, string $whatsapp): void
    {
        echo $this->template->renderizar('pagamento.html', [
            'titulo' => 'Pagamento PIX',
            'pedido' => $pedido,
            'copiaCola' => $pedido->pix_qrcode,
            'imagemQrcode' => $pedido->pix_img,
            'tipoPagamento' => 'PIX',
            'whatsapp' => $whatsapp,
            'gateway' => 'ASAAS'
        ]);
    }

    /**
     * Renderiza página de pagamento Cartão
     */
    public function renderizarPagamentoCartao(PedidoModelo $pedido, string $whatsapp): void
    {
        echo $this->template->renderizar('pagamento.html', [
            'titulo' => 'Processando Pagamento',
            'pedido' => $pedido,
            'tipoPagamento' => 'CARTAO',
            'whatsapp' => $whatsapp,
            'gateway' => 'ASAAS'
        ]);
    }
}
