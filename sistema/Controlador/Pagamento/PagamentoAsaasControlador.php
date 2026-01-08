<?php

namespace sistema\Controlador\Pagamento;

use sistema\Nucleo\Controlador;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PedidoModelo;
use sistema\Biblioteca\Asaas;

class PagamentoAsaasControlador extends Controlador
{
    public function __construct()
    {
        parent::__construct('templates/site/views');
    }

    public function exibirTelaPagamento(PedidoModelo $pedido, string $whatsapp): void
    {
        echo $this->template->renderizar('pagamento/asaas.html', [
            'titulo' => $pedido->status == 'PAGO' ? 'Voto Confirmado' : 'Pagamento Asaas',
            'pedido' => $pedido,
            'whatsapp' => $whatsapp,
            'tipoPagamento' => $pedido->metodo_pagamento,
            'copiaCola' => $pedido->pix_qrcode,
            'imagemQrcode' => $pedido->pix_img
        ]);
    }

    public function processar(PedidoModelo $pedido, array $dados): array
    {
        $asaas = new Asaas($pedido->id);
        $forma = $dados['forma_pagamento'] ?? 'PIX';

        try {
            $res = ($forma === 'CREDIT_CARD')
                ? $asaas->gerarCobrancaCartao(
                    ['nome' => $pedido->cliente_nome, 'cpf' => $pedido->cliente_cpf, 'email' => $pedido->cliente_email, 'telefone' => $pedido->cliente_telefone, 'cep' => $dados['cep'] ?? '01310100'],
                    ['nome' => $dados['cartao_nome'], 'numero' => $dados['cartao_numero'], 'mes' => $dados['cartao_mes'], 'ano' => $dados['cartao_ano'], 'cvv' => $dados['cartao_cvv']],
                    (float) $pedido->valor_total,
                    "Votos",
                    (string) $pedido->id
                )
                : $asaas->gerarPixVenda(
                    ['nome' => $pedido->cliente_nome, 'cpf' => $pedido->cliente_cpf, 'email' => $pedido->cliente_email, 'telefone' => $pedido->cliente_telefone],
                    (float) $pedido->valor_total,
                    "Votos",
                    (string) $pedido->id
                );

            if ($res['erro']) return $res;

            $pedido->asaas_id = $res['id_transacao'];
            $pedido->gateway_usado = 'ASAAS';
            $pedido->metodo_pagamento = ($forma === 'CREDIT_CARD') ? 'CARTAO' : 'PIX';

            if ($forma === 'PIX') {
                $pedido->pix_qrcode = $res['payload'];
                $pedido->pix_img = $res['encodedImage'];
            }

            $pedido->salvar();

            return ['erro' => false];
        } catch (\Exception $e) {
            return ['erro' => true, 'mensagem' => $e->getMessage()];
        }
    }

    public function webhook(): void
    {
        $dados = json_decode(file_get_contents('php://input'), true);

        if (!isset($dados['event']) || !in_array($dados['event'], ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'])) {
            return;
        }

        $pedido = (new PedidoModelo())->busca("asaas_id = :id", "id={$dados['payment']['id']}")->resultado();

        if ($pedido && $pedido->status !== 'PAGO') {
            $pedido->confirmarPagamento();
            http_response_code(200);
        }
    }

    public function verificarStatusPagamento(\sistema\Modelo\PedidoModelo $pedido): void
    {
        $asaas = new Asaas($pedido->id);
        $dadosAsaas = $asaas->consultarCobranca($pedido->asaas_id);

        if (isset($dadosAsaas->status) && in_array($dadosAsaas->status, ['RECEIVED', 'CONFIRMED'])) {
            $pedido->confirmarPagamento();
        }
    }
}
