<?php

namespace sistema\Controlador\Pagamento;

use sistema\Nucleo\Controlador;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PedidoModelo;
use sistema\Biblioteca\InfinitePay;

class PagamentoInfinitepayControlador extends Controlador
{
    public function __construct()
    {
        parent::__construct('templates/site/views');
    }

    public function exibirTelaPagamento(PedidoModelo $pedido, string $whatsapp): void
    {
        $infinitePay = new InfinitePay($pedido->id);

        if ($pedido->status === 'AGUARDANDO') {
            $res = $infinitePay->verificarPagamento($pedido->infinitepay_order_nsu);

            if (!$res['erro'] && $res['paid']) {
                $pedido->confirmarPagamento();
                $pedido = (new PedidoModelo())->buscaPorId($pedido->id);
            }
        }

        if ($pedido->status === 'AGUARDANDO' && empty($_GET['transaction_nsu'])) {
            header('Location: ' . $pedido->infinitepay_link);
            exit;
        }

        echo $this->template->renderizar('pagamento/infinitepay.html', [
            'titulo' => $pedido->status == 'PAGO' ? 'Voto Confirmado' : 'Processando Pagamento',
            'pedido' => $pedido,
            'whatsapp' => $whatsapp
        ]);
    }


    public function processar(PedidoModelo $pedido, array $dados): array
    {
        $infinitePay = new InfinitePay($pedido->id);
        $resultado = $infinitePay->gerarLinkPagamento(
            [['quantidade' => 1, 'valor' => (float) $pedido->valor_total, 'descricao' => $pedido->total_votos . ' votos']],
            'pedido-' . $pedido->id,
            [
                'nome'     => $pedido->cliente_nome,
                'cpf'      => $pedido->cliente_cpf,
                'email'    => $pedido->cliente_email,
                'telefone' => $pedido->cliente_telefone
            ],
            null,
            Helpers::url('pedido/pagamento/' . $pedido->id)
        );

        if ($resultado['erro']) return $resultado;

        $pedido->infinitepay_link = $resultado['link'];
        $pedido->infinitepay_order_nsu = $resultado['order_nsu'];
        $pedido->gateway_usado = 'INFINITEPAY';

        $pedido->metodo_pagamento = 'CARTAO';

        return ['erro' => false];
    }

    public function webhook(): void
    {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);

        if (!$dados || !isset($dados['order_nsu'])) {
            http_response_code(400);
            exit;
        }

        $pedido = (new PedidoModelo())->busca("infinitepay_order_nsu = :nsu", "nsu={$dados['order_nsu']}")->resultado();

        if ($pedido && $pedido->status !== 'PAGO') {
            $pedido->confirmarPagamento();
            http_response_code(200);
            echo json_encode(['status' => 'sucesso']);
        }
    }

    private function confirmarNoBancoInterno($pedido, $dadosPagamento = null)
    {
        $metodo = ($dadosPagamento['capture_method'] ?? '') === 'pix' ? 'PIX' : 'CARTAO';
        return $pedido->confirmarPagamento($metodo);
    }
}
