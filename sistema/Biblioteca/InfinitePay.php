<?php

namespace sistema\Biblioteca;

use sistema\Nucleo\Helpers;
use sistema\Modelo\LogPagamentoInfinitepayModelo;
use sistema\Suporte\XDebug;

class InfinitePay
{
    private $handle;
    private $url;
    private $log;
    private $pedidoId;
    private $webhookUrl;
    private $redirectUrl;

    public function __construct(?int $pedidoId = null)
    {
        $this->handle       = INFINITEPAY_HANDLE;
        $this->url          = INFINITEPAY_URL;
        $this->webhookUrl   = INFINITEPAY_WEBHOOK_URL;
        $this->redirectUrl  = INFINITEPAY_REDIRECT_URL;
        $this->log          = new LogPagamentoInfinitepayModelo();
        $this->pedidoId     = $pedidoId;
    }

    /**
     * Gera link de pagamento (checkout) na InfinitePay
     *
     * @param array $itens Lista de itens do pedido
     * @param string $orderNsu Identificador único do pedido (opcional)
     * @param array|null $dadosCliente Dados do cliente (opcional)
     * @param array|null $enderecoEntrega Endereço de entrega (opcional)
     * @param string|null $redirectUrl URL de redirecionamento customizada (opcional)
     * @return array
     */
    public function gerarLinkPagamento(
        array $itens,
        ?string $orderNsu = null,
        ?array $dadosCliente = null,
        ?array $enderecoEntrega = null,
        ?string $redirectUrl = null
    ): array {
        // Validação básica
        if (empty($itens)) {
            $this->log->registrar(
                $this->pedidoId,
                'criar_link',
                'ERRO',
                'Lista de itens vazia',
                ['itens' => $itens],
                null,
                'invalid_items'
            );
            return ['erro' => true, 'mensagem' => 'É necessário informar ao menos 1 item'];
        }

        // Monta payload
        $payload = [
            'handle' => $this->handle,
            'items' => $this->formatarItens($itens)
        ];

        // Order NSU (opcional)
        if ($orderNsu) {
            $payload['order_nsu'] = $orderNsu;
        }

        // Webhook URL (opcional)
        if ($this->webhookUrl) {
            $payload['webhook_url'] = $this->webhookUrl;
        }

        // Redirect URL (opcional)
        $urlRedirect = $redirectUrl ?? $this->redirectUrl;
        if ($urlRedirect) {
            $payload['redirect_url'] = $urlRedirect;
        }

        // Dados do cliente (opcional)
        if ($dadosCliente) {
            $payload['customer'] = $this->formatarDadosCliente($dadosCliente);
        }

        // Endereço de entrega (opcional)
        if ($enderecoEntrega) {
            $payload['address'] = $this->formatarEndereco($enderecoEntrega);
        }

        // Faz requisição
        $resposta = $this->post('/invoices/public/checkout/links', $payload);

        XDebug::xd("Resposta da API InfinitePay", $resposta);

        // Verifica erro
        if (!isset($resposta->url)) {
            $mensagem = $resposta->message ?? 'Erro ao gerar link de pagamento';
            $codigoErro = $resposta->error ?? null;

            $this->log->registrar(
                $this->pedidoId,
                'criar_link',
                'ERRO',
                $mensagem,
                $payload,
                $resposta,
                $codigoErro,
                null,
                null,
                null,
                null,
                $this->url . '/invoices/public/checkout/links'
            );

            return ['erro' => true, 'mensagem' => $mensagem];
        }

        // Sucesso
        $this->log->registrar(
            $this->pedidoId,
            'criar_link',
            'SUCESSO',
            'Link de pagamento gerado com sucesso',
            $payload,
            $resposta,
            null,
            null,
            $resposta->url,
            null,
            $orderNsu,
            $this->url . '/invoices/public/checkout/links',
            200
        );

        return [
            'erro' => false,
            'link' => $resposta->url,
            'slug' => null,
            'order_nsu' => $orderNsu
        ];
    }

    /**
     * Verifica status de pagamento
     *
     * @param string $orderNsu Order NSU do pedido
     * @param string|null $transactionNsu Transaction NSU (opcional)
     * @param string|null $slug Slug da fatura (opcional)
     * @return array
     */
    public function verificarPagamento(
        string $orderNsu,
        ?string $transactionNsu = null,
        ?string $slug = null
    ): array {
        $payload = [
            'handle' => $this->handle,
            'order_nsu' => $orderNsu
        ];

        if ($transactionNsu) {
            $payload['transaction_nsu'] = $transactionNsu;
        }

        if ($slug) {
            $payload['slug'] = $slug;
        }

        $resposta = $this->post('/invoices/public/checkout/payment_check', $payload);

        // Verifica erro
        if (!isset($resposta->success)) {
            $mensagem = $resposta->message ?? 'Erro ao verificar pagamento';

            $this->log->registrar(
                $this->pedidoId,
                'verificar_pagamento',
                'ERRO',
                $mensagem,
                $payload,
                $resposta,
                null,
                $slug,
                null,
                $transactionNsu,
                $orderNsu,
                $this->url . '/invoices/public/checkout/payment_check'
            );

            return ['erro' => true, 'mensagem' => $mensagem];
        }

        // Sucesso
        $this->log->registrar(
            $this->pedidoId,
            'verificar_pagamento',
            'SUCESSO',
            'Status verificado com sucesso',
            $payload,
            $resposta,
            null,
            $slug,
            null,
            $transactionNsu,
            $orderNsu,
            $this->url . '/invoices/public/checkout/payment_check',
            200
        );

        return [
            'erro' => false,
            'success' => $resposta->success ?? false,
            'paid' => $resposta->paid ?? false,
            'amount' => $resposta->amount ?? 0,
            'paid_amount' => $resposta->paid_amount ?? 0,
            'installments' => $resposta->installments ?? 1,
            'capture_method' => $resposta->capture_method ?? null
        ];
    }

    /**
     * Formata itens para o formato esperado pela API
     *
     * @param array $itens
     * @return array
     */
    private function formatarItens(array $itens): array
    {
        $itensFormatados = [];

        foreach ($itens as $item) {
            $itensFormatados[] = [
                'quantity' => $item['quantidade'] ?? 1,
                'price' => $this->converterParaCentavos($item['valor'] ?? 0),
                'description' => $item['descricao'] ?? 'Produto'
            ];
        }

        return $itensFormatados;
    }

    /**
     * Formata dados do cliente
     *
     * @param array $dados
     * @return array
     */
    private function formatarDadosCliente(array $dados): array
    {
        $cliente = [];

        if (!empty($dados['nome'])) {
            $cliente['name'] = $dados['nome'];
        }

        if (!empty($dados['cpf'])) {
            $cliente['cpf'] = Helpers::limparNumero($dados['cpf']);
        }

        if (!empty($dados['email'])) {
            $cliente['email'] = $dados['email'];
        }

        if (!empty($dados['telefone'])) {
            $cliente['phone_number'] = Helpers::limparNumero($dados['telefone']);
        }

        return $cliente;
    }

    /**
     * Formata endereço de entrega
     *
     * @param array $endereco
     * @return array
     */
    private function formatarEndereco(array $endereco): array
    {
        return [
            'cep' => Helpers::limparNumero($endereco['cep'] ?? ''),
            'street' => $endereco['rua'] ?? '',
            'neighborhood' => $endereco['bairro'] ?? '',
            'number' => $endereco['numero'] ?? 'S/N',
            'complement' => $endereco['complemento'] ?? ''
        ];
    }

    /**
     * Converte valor em reais para centavos
     *
     * @param float $valor
     * @return int
     */
    private function converterParaCentavos(float $valor): int
    {
        return (int) round($valor * 100);
    }

    /**
     * Faz requisição POST para API
     *
     * @param string $endpoint
     * @param array $dados
     * @return object
     */
    private function post(string $endpoint, array $dados): object
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, INFINITEPAY_SSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, INFINITEPAY_SSL ? 2 : 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: SistemaVotos'
        ]);

        $resultado = curl_exec($ch);

        if (curl_errno($ch)) {
            $erro = curl_error($ch);
            curl_close($ch);
            error_log('Erro cURL InfinitePay: ' . $erro);
            return (object)['error' => 'connection_error', 'message' => 'Erro de conexão com InfinitePay'];
        }

        curl_close($ch);
        return json_decode($resultado);
    }

    /**
     * Faz requisição GET para API
     *
     * @param string $endpoint
     * @return object
     */
    private function get(string $endpoint): object
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, INFINITEPAY_SSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, INFINITEPAY_SSL ? 2 : 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: SistemaVotos'
        ]);

        $resultado = curl_exec($ch);

        if (curl_errno($ch)) {
            $erro = curl_error($ch);
            curl_close($ch);
            error_log('Erro cURL GET InfinitePay: ' . $erro);
            return (object)['error' => 'connection_error', 'message' => 'Erro de conexão com InfinitePay'];
        }

        curl_close($ch);
        return json_decode($resultado);
    }

    /**
     * Define o ID do pedido
     *
     * @param int $pedidoId
     * @return void
     */
    public function setPedidoId(int $pedidoId): void
    {
        $this->pedidoId = $pedidoId;
    }
}
