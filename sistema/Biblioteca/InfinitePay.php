<?php

namespace sistema\Biblioteca;

use sistema\Nucleo\Helpers;
use sistema\Modelo\LogPagamentoInfinitepayModelo;

/**
 * Classe de integração com a API InfinitePay
 * 
 * Responsável por gerenciar pagamentos, gerar links de checkout
 * e verificar status de transações através da API InfinitePay
 * 
 * @author Fernando Aguiar
 */
class InfinitePay
{
    private string $handle;
    private string $url;
    private LogPagamentoInfinitepayModelo $log;
    private ?int $pedidoId;
    private ?string $webhookUrl;
    private ?string $redirectUrl;

    /**
     * Construtor da classe
     * 
     * @param int|null $pedidoId ID do pedido para vincular aos logs
     */
    public function __construct(?int $pedidoId = null)
    {
        $this->handle      = INFINITEPAY_HANDLE;
        $this->url         = INFINITEPAY_URL;
        $this->webhookUrl  = INFINITEPAY_WEBHOOK_URL;
        $this->redirectUrl = INFINITEPAY_REDIRECT_URL;
        $this->log         = new LogPagamentoInfinitepayModelo();
        $this->pedidoId    = $pedidoId;
    }

    /**
     * Verifica o status de um pagamento na InfinitePay
     * 
     * @param string $orderNsu NSU do pedido
     * @param string|null $transactionNsu NSU da transação (opcional)
     * @param string|null $slug Slug da fatura (opcional)
     * @return array Retorna array com status do pagamento
     */
    public function verificarPagamento(string $orderNsu, ?string $transactionNsu = null, ?string $slug = null): array
    {
        $payload = [
            'handle' => $this->handle,
            'order_nsu' => $orderNsu
        ];

        if ($transactionNsu) $payload['transaction_nsu'] = $transactionNsu;
        if ($slug) $payload['slug'] = $slug;

        $resposta = $this->request('/invoices/public/checkout/payment_check', $payload, 'verificar_pagamento');

        if (!isset($resposta->success)) {
            return ['erro' => true, 'paid' => false, 'mensagem' => $resposta->message ?? 'Erro na checagem'];
        }

        return [
            'erro' => false,
            'paid' => $resposta->paid ?? false,
            'capture_method' => $resposta->capture_method ?? 'card',
            'dados' => $resposta
        ];
    }

    /**
     * Gera um link de pagamento na InfinitePay
     * 
     * @param array $itens Lista de itens do pedido
     * @param string|null $orderNsu NSU do pedido
     * @param array|null $dadosCliente Dados do cliente (nome, cpf, email, telefone)
     * @param string|null $redirectUrlCustom URL de redirecionamento customizada
     * @return array Retorna array com link de pagamento ou erro
     */
    public function gerarLinkPagamento(
        array $itens,
        ?string $orderNsu = null,
        ?array $dadosCliente = null,
        ?string $redirectUrlCustom = null
    ): array {
        $redirectUrl = $redirectUrlCustom ?? $this->redirectUrl;

        $payload = [
            'handle' => $this->handle,
            'items' => $this->formatarItens($itens),
            'order_nsu' => $orderNsu,
            'webhook_url' => $this->webhookUrl,
            'redirect_url' => $redirectUrl,
            'customer' => $dadosCliente ? $this->formatarDadosCliente($dadosCliente) : null
        ];

        $resposta = $this->request('/invoices/public/checkout/links', array_filter($payload), 'criar_link');

        if (!isset($resposta->url)) {
            return ['erro' => true, 'mensagem' => $resposta->message ?? 'Erro ao gerar link'];
        }

        return [
            'erro' => false,
            'link' => $resposta->url,
            'order_nsu' => $orderNsu,
            'slug' => $resposta->slug ?? null
        ];
    }

    /**
     * Executa requisição HTTP para a API InfinitePay
     * 
     * @param string $endpoint Endpoint da API
     * @param array $dados Dados a serem enviados
     * @param string $etapa Etapa do processo para log
     * @return object Resposta da API como objeto
     */
    private function request(string $endpoint, array $dados, string $etapa): object
    {
        $tempoInicio = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url . $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($dados),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => INFINITEPAY_SSL ?? true
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        $tempoResposta = round((microtime(true) - $tempoInicio) * 1000, 2); // em milissegundos

        $respostaObj = json_decode($res);

        // Define status baseado no código HTTP e presença de erros
        $statusLog = ($httpCode >= 200 && $httpCode < 300 && !$curlError) ? 'SUCESSO' : 'ERRO';

        // Registra log da requisição
        $this->log->registrar(
            $this->pedidoId,
            $etapa,
            $statusLog,
            $curlError ?: null, // Mensagem será extraída do response no modelo
            $dados,
            $res, // Passa como string JSON
            null, // Código de erro será extraído do response no modelo
            null, // Slug será extraído do response no modelo
            null, // Link será extraído do response no modelo
            $dados['transaction_nsu'] ?? null,
            $dados['order_nsu'] ?? null,
            $this->url . $endpoint,
            $httpCode
        );

        // Atualiza tempo de resposta após salvar o log
        if ($this->log->id) {
            $this->log->tempo_resposta = $tempoResposta;
            $this->log->salvar();
        }

        if ($curlError) {
            return (object)['error' => 'curl_error', 'message' => $curlError];
        }

        return $respostaObj ?? (object)['error' => 'json_error', 'message' => 'Resposta inválida'];
    }

    /**
     * Formata itens para o padrão da API InfinitePay
     * 
     * @param array $itens Lista de itens
     * @return array Itens formatados
     */
    private function formatarItens(array $itens): array
    {
        return array_map(fn($i) => [
            'quantity' => $i['quantidade'] ?? 1,
            'price' => (int)round(($i['valor'] ?? 0) * 100), // Converte para centavos
            'description' => $i['descricao'] ?? 'Voto'
        ], $itens);
    }

    /**
     * Formata dados do cliente para o padrão da API InfinitePay
     * 
     * @param array $d Dados do cliente
     * @return array Dados formatados
     */
    private function formatarDadosCliente(array $d): array
    {
        return [
            'name' => $d['nome'],
            'cpf' => Helpers::limparNumero($d['cpf']),
            'email' => $d['email'] ?? null,
            'phone_number' => Helpers::limparNumero($d['telefone'] ?? '')
        ];
    }
}
