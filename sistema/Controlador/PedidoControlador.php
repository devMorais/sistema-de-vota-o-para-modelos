<?php

namespace sistema\Controlador;

use sistema\Nucleo\Controlador;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PedidoModelo;
use sistema\Modelo\PostModelo;
use sistema\Modelo\ConfiguracaoModelo;
use sistema\Controlador\Pagamento\PagamentoAsaasControlador;
use sistema\Controlador\Pagamento\PagamentoInfinitepayControlador;

class PedidoControlador extends Controlador
{
    private $config;

    public function __construct()
    {
        parent::__construct('templates/site/views');
        $this->config = (new ConfiguracaoModelo())->buscaPorId(1);
    }

    public function processar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!$this->validarDados($dados)) {
            $this->renderizarCheckoutComErro($dados);
            return;
        }

        $this->executarFluxoPagamento($dados);
    }

    public function pagamento(int $idPedido): void
    {
        $pedido = (new PedidoModelo())->buscaPorId($idPedido);

        if (!$pedido) {
            $this->mensagem->alerta('Pedido não encontrado.')->flash();
            Helpers::redirecionar();
            return;
        }

        $whatsapp = preg_replace('/[^0-9]/', '', $this->config->whatsapp ?? '');

        if ($pedido->gateway_usado === 'INFINITEPAY') {
            (new PagamentoInfinitepayControlador())->exibirTelaPagamento($pedido, $whatsapp);
        } else {
            (new PagamentoAsaasControlador())->exibirTelaPagamento($pedido, $whatsapp);
        }
    }

    public function verificar(): void
    {
        $idPedido = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $pedido = (new PedidoModelo())->buscaPorId($idPedido);

        if ($pedido && $pedido->status !== 'PAGO') {
            if ($pedido->gateway_usado === 'INFINITEPAY') {
                $controladorIP = new PagamentoInfinitepayControlador();
                $controladorIP->verificarStatusPagamento($pedido);
            } else {
                $controladorAsaas = new PagamentoAsaasControlador();
                $controladorAsaas->verificarStatusPagamento($pedido);
            }
            $pedido = (new PedidoModelo())->buscaPorId($idPedido);
        }

        echo json_encode(['pago' => ($pedido && $pedido->status === 'PAGO')]);
    }

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
        return $pedido->salvar() ? $pedido : null;
    }

    private function validarDados(?array $dados): bool
    {
        if (!$dados || empty($dados['nome']) || empty($dados['cpf'])) return false;
        if ((float)($dados['valor_total'] ?? 0) <= 0) return false;
        return true;
    }

    private function renderizarCheckoutComErro(array $dados): void
    {
        $post = (new PostModelo())->buscaPorId($dados['post_id'] ?? 0);
        $gateway = $this->config->gateway_pagamento ?? 'ASAAS';
        $template = ($gateway === 'INFINITEPAY') ? 'checkout/checkout-infinitepay.html' : 'checkout/checkout-asaas.html';

        echo $this->template->renderizar($template, [
            'post'       => $post,
            'totalVotos' => $dados['total_votos'] ?? 0,
            'form'       => $dados,
            'config'     => $this->config
        ]);
    }

    private function executarFluxoPagamento(array $dados): void
    {
        $pedido = $this->criarPedido($dados);

        if (!$pedido) {
            $this->mensagem->erro('Erro ao criar pedido. Tente novamente.')->flash();
            Helpers::redirecionar();
            return;
        }

        $controlador = $this->instanciarControladorPagamento();
        $resultado = $controlador->processar($pedido, $dados);

        if ($resultado['erro']) {
            $pedido->status = 'ERRO';
            $pedido->salvar();

            $this->mensagem->erro($resultado['mensagem'])->flash();
            Helpers::redirecionar();
            return;
        }

        $pedido->salvar();
        Helpers::redirecionar('pedido/pagamento/' . $pedido->id);
    }

    private function instanciarControladorPagamento()
    {
        $gateway = $this->config->gateway_pagamento ?? 'ASAAS';

        return ($gateway === 'INFINITEPAY')
            ? new PagamentoInfinitepayControlador()
            : new PagamentoAsaasControlador();
    }
}
