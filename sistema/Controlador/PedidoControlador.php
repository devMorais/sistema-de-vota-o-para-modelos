<?php

namespace sistema\Controlador;

use sistema\Nucleo\Controlador;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PedidoModelo;
use sistema\Modelo\PostModelo;
use sistema\Modelo\ConfiguracaoModelo;
use sistema\Controlador\Pagamento\PagamentoAsaasControlador;
use sistema\Controlador\Pagamento\PagamentoInfinitepayControlador;
use sistema\Suporte\XDebug;

class PedidoControlador extends Controlador
{
    private $config;

    public function __construct()
    {
        parent::__construct('templates/site/views');
        $this->config = (new ConfiguracaoModelo())->buscaPorId(1);
    }

    /**
     * Processa pagamento - cria pedido e delega para gateway
     */
    public function processar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!$this->validarDados($dados)) {
            $post = (new PostModelo())->buscaPorId($dados['post_id'] ?? 0);
            if (!$post) {
                Helpers::redirecionar();
                return;
            }

            $gateway = $this->config->gateway_pagamento ?? 'ASAAS';
            $template = ($gateway === 'INFINITEPAY') ? 'checkout/checkout-infinitepay.html' : 'checkout/checkout-asaas.html';

            echo $this->template->renderizar($template, [
                'titulo'        => 'Checkout - ' . $post->titulo,
                'post'          => $post,
                'totalVotos'    => $dados['total_votos'] ?? 0,
                'pacoteId'      => $dados['pacote_id'] ?? null,
                'subtotalFloat' => $dados['valor_subtotal'] ?? 0,
                'taxaFloat'     => $dados['valor_taxa'] ?? 0,
                'totalFloat'    => $dados['valor_total'] ?? 0,
                'subtotal'      => number_format((float)($dados['valor_subtotal'] ?? 0), 2, ',', '.'),
                'totalTaxas'    => number_format((float)($dados['valor_taxa'] ?? 0), 2, ',', '.'),
                'totalGeral'    => number_format((float)($dados['valor_total'] ?? 0), 2, ',', '.'),
                'form'          => $dados,
                'config'        => $this->config
            ]);
            return;
        }

        // Cria pedido
        $pedido = $this->criarPedido($dados);

        if (!$pedido) {
            $this->mensagem->erro('Erro ao criar pedido. Tente novamente.')->flash();
            Helpers::redirecionar();
            return;
        }

        // Delega para gateway configurado
        $gateway = $this->config->gateway_pagamento ?? 'ASAAS';
        try {
            if ($gateway === 'INFINITEPAY') {
                $controlador = new PagamentoInfinitepayControlador();
            } else {
                $controlador = new PagamentoAsaasControlador();
            }

            $resultado = $controlador->processar($pedido, $dados);

            if ($resultado['erro']) {
                $pedido->status = 'ERRO';
                $pedido->salvar();

                $this->mensagem->erro('Erro: ' . $resultado['mensagem'])->flash();
                $post = (new PostModelo())->buscaPorId($dados['post_id']);
                if ($post) {
                    Helpers::redirecionar('post/' . $post->categoria()->slug . '/' . $post->slug);
                } else {
                    Helpers::redirecionar();
                }
                return;
            }

            $pedido->salvar();
            Helpers::redirecionar('pedido/pagamento/' . $pedido->id);
        } catch (\Exception $e) {
            error_log('Erro ao processar pedido: ' . $e->getMessage());

            $pedido->status = 'ERRO';
            $pedido->salvar();
            $this->mensagem->erro('Erro ao processar pagamento. Tente novamente.')->flash();
            Helpers::redirecionar();
        }
    }

    /**
     * Exibe página de pagamento com ajuste para evitar loop na InfinitePay
     */
    public function pagamento(int $idPedido): void
    {
        // --- INÍCIO DO LOG DE DEBUG FÍSICO ---
        $arquivoLog = dirname(__DIR__, 2) . '/debug_infinitepay.txt';
        $timestamp = date('Y-m-d H:i:s');
        $uri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        $params = $_SERVER['QUERY_STRING'] ?? 'Nenhum';

        $logEntrada = "[$timestamp] ACESSO: Pedido #$idPedido | URI: $uri | GET: $params\n";
        file_put_contents($arquivoLog, $logEntrada, FILE_APPEND);
        // --- FIM DO LOG DE DEBUG ---

        // Busca o pedido atualizado diretamente do banco
        $pedido = (new PedidoModelo())->buscaPorId($idPedido);

        if (!$pedido) {
            $this->mensagem->alerta('Pedido não encontrado.')->flash();
            Helpers::redirecionar();
            return;
        }

        $whatsapp = preg_replace('/[^0-9]/', '', $this->config->whatsapp ?? '');

        // 1. SE JÁ ESTIVER PAGO NO BANCO: Mostra sucesso imediatamente 
        if ($pedido->status === 'PAGO') {
            $controlador = new PagamentoInfinitepayControlador();
            $controlador->renderizarSucesso($pedido, $whatsapp);
            return;
        }

        // 2. SE HOUVER ERRO NO PEDIDO: Mostra a tela de erro 
        if ($pedido->status === 'ERRO') {
            echo $this->template->renderizar('pagamento.html', [
                'titulo' => 'Erro no Pagamento',
                'pedido' => $pedido,
                'whatsapp' => $whatsapp
            ]);
            return;
        }

        // 3. LÓGICA ESPECÍFICA: INFINITEPAY
        // 3. LÓGICA ESPECÍFICA: INFINITEPAY
        if ($pedido->gateway_usado === 'INFINITEPAY') {
            $controlador = new PagamentoInfinitepayControlador();

            // ✅ SE VOLTOU DO CHECKOUT (tem parâmetros GET), verifica AGORA
            if (!empty($_GET['transaction_nsu']) || !empty($_GET['slug']) || !empty($_GET['transaction_id'])) {

                // Atualiza transaction_nsu se veio na URL
                if (!empty($_GET['transaction_nsu']) && empty($pedido->infinitepay_transaction_nsu)) {
                    $pedido->infinitepay_transaction_nsu = $_GET['transaction_nsu'];
                    $pedido->salvar();
                }

                // ✅ FORÇA verificação IMEDIATA
                $infinitePay = new \sistema\Biblioteca\InfinitePay($pedido->id);
                $resultado = $infinitePay->verificarPagamento($pedido->infinitepay_order_nsu);

                // Se já pagou, confirma AGORA
                if (!empty($resultado['paid']) && $resultado['paid'] === true) {
                    $controlador->confirmarPagamentoPublico($pedido, $resultado);

                    // Recarrega pedido atualizado
                    $pedido = (new PedidoModelo())->buscaPorId($pedido->id);

                    // Mostra tela de sucesso
                    $controlador->renderizarSucesso($pedido, $whatsapp);
                    return;
                }

                // Se não pagou ainda, mostra tela de processamento
                $controlador->renderizarSucesso($pedido, $whatsapp);
                return;
            }

            // Se for acesso limpo (sem volta de checkout) e não pagou, redireciona
            if (!empty($pedido->infinitepay_link)) {
                $controlador->redirecionarPagamento($pedido);
                return;
            }
        }

        // 4. LÓGICA ESPECÍFICA: ASAAS (PIX) 
        if (!empty($pedido->pix_qrcode)) {
            $controlador = new PagamentoAsaasControlador();
            $controlador->renderizarPagamentoPix($pedido, $whatsapp);
            return;
        }

        // 5. LÓGICA ESPECÍFICA: ASAAS (CARTÃO) 
        $controlador = new PagamentoAsaasControlador();
        $controlador->renderizarPagamentoCartao($pedido, $whatsapp);
    }

    /**
     * AJAX: Verifica status do pagamento em tempo real para automação
     */
    public function verificar(): void
    {
        $idPedido = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$idPedido) {
            Helpers::json('erro', 'ID inválido');
            return;
        }

        $pedido = (new PedidoModelo())->buscaPorId($idPedido);

        if (!$pedido) {
            Helpers::json('erro', 'Pedido não encontrado');
            return;
        }

        // 1. Se já está PAGO no banco, avisa o JS imediatamente
        if ($pedido->status === 'PAGO') {
            echo json_encode(['pago' => true, 'status' => 'PAGO']);
            return;
        }

        // 2. Se for InfinitePay, consulta a API em tempo real para acelerar a confirmação
        if ($pedido->gateway_usado === 'INFINITEPAY') {
            $controladorIP = new PagamentoInfinitepayControlador();
            $statusReal = $controladorIP->consultarStatusAPI($pedido);

            if ($statusReal === 'PAGO') {
                echo json_encode(['pago' => true, 'status' => 'PAGO']);
                return;
            }
        }

        // 3. Caso contrário, continua AGUARDANDO
        echo json_encode(['aguardando' => true, 'status' => 'AGUARDANDO']);
    }

    /**
     * Retorna mensagem de erro do pedido
     */
    public function erro(): void
    {
        $pedidoId = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);

        if (!$pedidoId) {
            echo json_encode(['mensagem' => 'ID inválido']);
            return;
        }

        $pdo = \sistema\Nucleo\Conexao::getInstancia();
        $stmt = $pdo->prepare("
            SELECT mensagem 
            FROM logs_pagamento 
            WHERE pedido_id = :pedido_id 
            AND status = 'ERRO' 
            ORDER BY cadastrado_em DESC 
            LIMIT 1
        ");

        $stmt->execute([':pedido_id' => $pedidoId]);
        $log = $stmt->fetch(\PDO::FETCH_OBJ);

        if ($log && $log->mensagem) {
            echo json_encode(['mensagem' => $log->mensagem]);
        } else {
            echo json_encode(['mensagem' => 'Não foi possível processar seu pagamento. Tente novamente.']);
        }
    }

    /**
     * Cria pedido no banco
     */
    private function criarPedido(array $dados): ?PedidoModelo
    {
        $pedido = new PedidoModelo();
        $pedido->post_id = $dados['post_id'];
        $pedido->pacote_id = !empty($dados['pacote_id']) ? $dados['pacote_id'] : null;
        $pedido->valor_subtotal = $dados['valor_subtotal'];
        $pedido->valor_taxa = $dados['valor_taxa'];
        $pedido->valor_total = $dados['valor_total'];
        $pedido->total_votos = $dados['total_votos'];
        $pedido->cliente_nome = $dados['nome'];
        $pedido->cliente_cpf = preg_replace('/[^0-9]/', '', $dados['cpf']);
        $pedido->cliente_email = $dados['email'] ?? null;
        $pedido->cliente_telefone = isset($dados['telefone']) ? preg_replace('/[^0-9]/', '', $dados['telefone']) : null;

        if ($pedido->salvar()) {
            return $pedido;
        }

        return null;
    }

    /**
     * Valida dados do pedido
     */
    private function validarDados(?array $dados): bool
    {
        if (!$dados || !isset($dados['post_id']) || !isset($dados['cpf'])) {
            $this->mensagem->alerta('Dados incompletos. Tente novamente.')->flash();
            return false;
        }

        if (empty($dados['nome'])) {
            $this->mensagem->erro('Por favor, preencha todos os campos obrigatórios.')->flash();
            return false;
        }

        $cpfLimpo = preg_replace('/[^0-9]/', '', $dados['cpf']);
        if (strlen($cpfLimpo) !== 11) {
            $this->mensagem->erro('O CPF informado é inválido.')->flash();
            return false;
        }

        // Validação específica para Asaas com cartão
        $gateway = $this->config->gateway_pagamento ?? 'ASAAS';
        $formaPagamento = $dados['forma_pagamento'] ?? 'PIX';

        if ($gateway === 'ASAAS' && $formaPagamento === 'CREDIT_CARD') {
            if (empty($dados['email']) || !Helpers::validarEmail($dados['email'])) {
                $this->mensagem->erro('E-mail válido é obrigatório para pagamento com cartão.')->flash();
                return false;
            }

            if (empty($dados['telefone'])) {
                $this->mensagem->erro('Telefone é obrigatório para pagamento com cartão.')->flash();
                return false;
            }

            $telefoneLimpo = preg_replace('/[^0-9]/', '', $dados['telefone']);
            if (strlen($telefoneLimpo) < 10) {
                $this->mensagem->erro('Telefone inválido.')->flash();
                return false;
            }

            if (empty($dados['cep'])) {
                $this->mensagem->erro('CEP é obrigatório para pagamento com cartão.')->flash();
                return false;
            }

            $cepLimpo = preg_replace('/[^0-9]/', '', $dados['cep']);
            if (strlen($cepLimpo) !== 8) {
                $this->mensagem->erro('CEP inválido. Deve conter 8 dígitos.')->flash();
                return false;
            }
        }

        if (!isset($dados['valor_total']) || (float)$dados['valor_total'] <= 0 || (int)$dados['total_votos'] <= 0) {
            $this->mensagem->erro('Erro nos valores do pedido. Tente novamente.')->flash();
            return false;
        }

        return true;
    }
}
