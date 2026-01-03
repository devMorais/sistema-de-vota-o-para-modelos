<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;

class LogPagamentoModelo extends Modelo
{
    public function __construct()
    {
        parent::__construct('logs_pagamento');
    }

    public function registrar(
        ?int $pedidoId,
        string $tipoPagamento,
        string $etapa,
        string $status,
        ?string $mensagem = null,
        ?array $requestData = null,
        $responseData = null,
        ?string $codigoErro = null,
        ?string $asaasId = null,
        ?string $endpoint = null,
        ?int $httpCode = null
    ): bool {
        $requestSanitizado = $this->sanitizarDados($requestData);

        $responseJson = null;
        if ($responseData !== null) {
            if (is_object($responseData) || is_array($responseData)) {
                $responseJson = json_encode($responseData);
            } else {
                $responseJson = $responseData;
            }
        }

        $this->pedido_id = $pedidoId;
        $this->tipo_pagamento = $tipoPagamento;
        $this->etapa = $etapa;
        $this->status = $status;
        $this->mensagem = $mensagem;
        $this->codigo_erro = $codigoErro;
        $this->request_data = $requestSanitizado ? json_encode($requestSanitizado) : null;
        $this->response_data = $responseJson;
        $this->asaas_id = $asaasId;
        $this->endpoint = $endpoint;
        $this->http_code = $httpCode;
        $this->tempo_resposta = null;
        $this->ip_usuario = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return $this->salvar();
    }

    private function sanitizarDados(?array $dados): ?array
    {
        if (!$dados) {
            return null;
        }

        $sanitizado = $dados;

        if (isset($sanitizado['creditCard']['number'])) {
            $numero = $sanitizado['creditCard']['number'];
            $sanitizado['creditCard']['number'] = substr($numero, 0, 4) . '****' . substr($numero, -4);
        }

        if (isset($sanitizado['creditCard']['ccv'])) {
            $sanitizado['creditCard']['ccv'] = '***';
        }

        if (isset($sanitizado['creditCardToken'])) {
            $sanitizado['creditCardToken'] = substr($sanitizado['creditCardToken'], 0, 10) . '...';
        }

        return $sanitizado;
    }

    public function buscarPorPedido(int $pedidoId): ?array
    {
        return $this->busca("pedido_id = :id", "id={$pedidoId}")
            ->ordem('cadastrado_em DESC')
            ->resultado(true);
    }

    public function buscarErros(int $limite = 100): ?array
    {
        return $this->busca("status = 'ERRO'")
            ->ordem('cadastrado_em DESC')
            ->limite($limite)
            ->resultado(true);
    }

    public function buscarSucessoPorPedido(int $pedidoId): ?array
    {
        return $this->busca("pedido_id = :id AND status = 'SUCESSO'", "id={$pedidoId}")
            ->ordem('cadastrado_em DESC')
            ->resultado(true);
    }
}
