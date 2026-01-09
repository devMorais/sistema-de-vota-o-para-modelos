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
        $transactionNsu = $_GET['transaction_nsu'] ?? null;
        $slug = $_GET['slug'] ?? $pedido->infinitepay_slug;
        $receiptUrl = $_GET['receipt_url'] ?? null;

        if ($pedido->status === 'AGUARDANDO') {

            if ($transactionNsu || $slug) {

                if ($transactionNsu && !$pedido->infinitepay_transaction_nsu) {
                    $pedido->infinitepay_transaction_nsu = $transactionNsu;
                }
                if ($slug && !$pedido->infinitepay_slug) {
                    $pedido->infinitepay_slug = $slug;
                }
                if ($receiptUrl && !$pedido->infinitepay_receipt_url) {
                    $pedido->infinitepay_receipt_url = $receiptUrl;
                }

                if ($transactionNsu || $slug || $receiptUrl) {
                    $pedido->salvar();
                }

                $res = $infinitePay->verificarPagamento(
                    $pedido->infinitepay_order_nsu,
                    $transactionNsu,
                    $slug
                );

                if (!$res['erro'] && $res['paid']) {
                    $metodo = ($res['capture_method'] ?? '') === 'pix' ? 'PIX' : 'CARTAO';
                    $pedido->metodo_pagamento = $metodo;
                    $pedido->confirmarPagamento();
                    $pedido = (new PedidoModelo())->buscaPorId($pedido->id);
                }
            } else {
                header('Location: ' . $pedido->infinitepay_link);
                exit;
            }
        }

        echo $this->template->renderizar('pagamento/infinitepay.html', [
            'titulo' => $pedido->status == 'PAGO' ? 'Voto Confirmado' : 'Processando Pagamento',
            'pedido' => $pedido,
            'whatsapp' => $whatsapp
        ]);
    }

    public function verificarStatusPagamento(PedidoModelo $pedido): void
    {
        if ($pedido->status === 'PAGO') {
            return;
        }

        $infinitePay = new InfinitePay($pedido->id);

        $res = $infinitePay->verificarPagamento(
            $pedido->infinitepay_order_nsu,
            $pedido->infinitepay_transaction_nsu,
            $pedido->infinitepay_slug
        );

        if (!$res['erro'] && $res['paid']) {
            $metodo = ($res['capture_method'] ?? '') === 'pix' ? 'PIX' : 'CARTAO';
            $pedido->metodo_pagamento = $metodo;
            $pedido->confirmarPagamento();
        }
    }

    public function processar(PedidoModelo $pedido, array $dados): array
    {
        $infinitePay = new InfinitePay($pedido->id);
        $urlRetorno = Helpers::url('pedido/pagamento/' . $pedido->id);

        $resultado = $infinitePay->gerarLinkPagamento(
            [['quantidade' => 1, 'valor' => (float) $pedido->valor_total, 'descricao' => $pedido->total_votos . ' votos']],
            'pedido-' . $pedido->id,
            [
                'nome'     => $pedido->cliente_nome,
                'cpf'      => $pedido->cliente_cpf,
                'email'    => $pedido->cliente_email,
                'telefone' => $pedido->cliente_telefone
            ],
            $urlRetorno
        );

        if ($resultado['erro']) return $resultado;

        $pedido->infinitepay_link = $resultado['link'];
        $pedido->infinitepay_order_nsu = $resultado['order_nsu'];
        $pedido->infinitepay_slug = $resultado['slug'] ?? null;
        $pedido->gateway_usado = 'INFINITEPAY';

        if (!$pedido->salvar()) {
            return ['erro' => true, 'mensagem' => 'Erro ao salvar dados do pedido'];
        }

        return ['erro' => false];
    }

    public function webhook(): void
    {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);

        if (!$dados || !isset($dados['order_nsu'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'order_nsu não fornecido']);
            exit;
        }

        $pedido = (new PedidoModelo())->busca("infinitepay_order_nsu = :nsu", "nsu={$dados['order_nsu']}")->resultado();

        if (!$pedido) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
            exit;
        }

        if ($pedido->status !== 'PAGO') {
            if (isset($dados['transaction_nsu'])) {
                $pedido->infinitepay_transaction_nsu = $dados['transaction_nsu'];
            }
            if (isset($dados['invoice_slug'])) {
                $pedido->infinitepay_slug = $dados['invoice_slug'];
            }
            if (isset($dados['receipt_url'])) {
                $pedido->infinitepay_receipt_url = $dados['receipt_url'];
            }

            $metodo = ($dados['capture_method'] ?? '') === 'pix' ? 'PIX' : 'CARTAO';
            $pedido->metodo_pagamento = $metodo;

            $pedido->confirmarPagamento();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => null]);
        } else {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Já processado']);
        }
    }
}
