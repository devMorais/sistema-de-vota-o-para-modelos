<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;
use sistema\Nucleo\Conexao;

class LogPagamentoInfinitepayModelo extends Modelo
{
    public function __construct()
    {
        parent::__construct('logs_pagamento_infinitepay');
    }

    /**
     * Registra um log de operação com InfinitePay
     *
     * @param int|null $pedidoId ID do pedido
     * @param string $etapa Etapa do processo (criar_link, verificar_pagamento, webhook)
     * @param string $status Status da operação (SUCESSO, ERRO, AVISO)
     * @param string|null $mensagem Mensagem descritiva
     * @param array|null $requestData Dados enviados para InfinitePay
     * @param mixed $responseData Resposta da API InfinitePay (string JSON, object ou array)
     * @param string|null $codigoErro Código do erro retornado
     * @param string|null $infinitepaySlug Slug da fatura na InfinitePay
     * @param string|null $infinitepayLink Link de pagamento gerado
     * @param string|null $transactionNsu NSU da transação
     * @param string|null $orderNsu NSU do pedido
     * @param string|null $endpoint URL do endpoint chamado
     * @param int|null $httpCode Código HTTP da resposta
     * @return bool
     */
    public function registrar(
        ?int $pedidoId,
        string $etapa,
        string $status,
        ?string $mensagem = null,
        ?array $requestData = null,
        $responseData = null,
        ?string $codigoErro = null,
        ?string $infinitepaySlug = null,
        ?string $infinitepayLink = null,
        ?string $transactionNsu = null,
        ?string $orderNsu = null,
        ?string $endpoint = null,
        ?int $httpCode = null
    ): bool {
        $requestSanitizado = $this->sanitizarDados($requestData);

        // Processa response_data e extrai objeto para análise
        $responseJson = null;
        $responseObj = null;

        if ($responseData !== null) {
            if (is_string($responseData)) {
                $responseJson = $responseData;
                $responseObj = json_decode($responseData);
            } elseif (is_object($responseData) || is_array($responseData)) {
                $responseJson = json_encode($responseData);
                $responseObj = is_array($responseData) ? (object)$responseData : $responseData;
            }
        }

        // Extrai informações adicionais do response se não foram passadas explicitamente
        if ($responseObj) {
            // Código de erro: tenta várias propriedades possíveis
            if (!$codigoErro) {
                $codigoErro = $responseObj->error ?? $responseObj->code ?? null;
            }

            // Mensagem: pega da resposta se não foi passada
            if (!$mensagem) {
                $mensagem = $responseObj->message ?? null;
            }

            // Slug: tenta invoice_slug ou slug
            if (!$infinitepaySlug) {
                $infinitepaySlug = $responseObj->slug ?? $responseObj->invoice_slug ?? null;
            }

            // Link/URL de pagamento
            if (!$infinitepayLink) {
                $infinitepayLink = $responseObj->url ?? null;
            }
        }

        $this->pedido_id = $pedidoId;
        $this->etapa = $etapa;
        $this->status = $status;
        $this->mensagem = $mensagem;
        $this->codigo_erro = $codigoErro;
        $this->request_data = $requestSanitizado ? json_encode($requestSanitizado) : null;
        $this->response_data = $responseJson;
        $this->infinitepay_slug = $infinitepaySlug;
        $this->infinitepay_link = $infinitepayLink;
        $this->transaction_nsu = $transactionNsu;
        $this->order_nsu = $orderNsu;
        $this->endpoint = $endpoint;
        $this->http_code = $httpCode;
        $this->tempo_resposta = null;
        $this->ip_usuario = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return $this->salvar();
    }

    /**
     * Sanitiza dados sensíveis antes de salvar no log
     * Remove informações como números de cartão, CVV e parcialmente emails/telefones
     *
     * @param array|null $dados Dados a serem sanitizados
     * @return array|null Dados sanitizados ou null
     */
    private function sanitizarDados(?array $dados): ?array
    {
        if (!$dados) {
            return null;
        }

        $sanitizado = $dados;

        // Sanitizar dados de cartão de crédito
        if (isset($sanitizado['customer']['creditCard']['number'])) {
            $numero = $sanitizado['customer']['creditCard']['number'];
            $sanitizado['customer']['creditCard']['number'] = substr($numero, 0, 4) . '****' . substr($numero, -4);
        }

        if (isset($sanitizado['customer']['creditCard']['cvv'])) {
            $sanitizado['customer']['creditCard']['cvv'] = '***';
        }

        if (isset($sanitizado['customer']['creditCard']['ccv'])) {
            $sanitizado['customer']['creditCard']['ccv'] = '***';
        }

        // Sanitizar email parcialmente (mantém início e domínio)
        if (isset($sanitizado['customer']['email'])) {
            $email = $sanitizado['customer']['email'];
            $partes = explode('@', $email);
            if (count($partes) === 2) {
                $sanitizado['customer']['email'] = substr($partes[0], 0, 3) . '***@' . $partes[1];
            }
        }

        // Sanitizar telefone parcialmente
        if (isset($sanitizado['customer']['phone_number'])) {
            $telefone = $sanitizado['customer']['phone_number'];
            $sanitizado['customer']['phone_number'] = substr($telefone, 0, 5) . '****' . substr($telefone, -2);
        }

        return $sanitizado;
    }

    /**
     * Busca todos os logs de um pedido específico
     *
     * @param int $pedidoId ID do pedido
     * @return array|null Lista de logs ou null
     */
    public function buscarPorPedido(int $pedidoId): ?array
    {
        return $this->busca("pedido_id = :id", "id={$pedidoId}")
            ->ordem('cadastrado_em DESC')
            ->resultado(true);
    }

    /**
     * Busca logs de erro
     *
     * @param int $limite Quantidade máxima de registros
     * @return array|null Lista de logs de erro ou null
     */
    public function buscarErros(int $limite = 100): ?array
    {
        return $this->busca("status = 'ERRO'")
            ->ordem('cadastrado_em DESC')
            ->limite($limite)
            ->resultado(true);
    }

    /**
     * Busca logs de sucesso de um pedido específico
     *
     * @param int $pedidoId ID do pedido
     * @return array|null Lista de logs de sucesso ou null
     */
    public function buscarSucessoPorPedido(int $pedidoId): ?array
    {
        return $this->busca("pedido_id = :id AND status = 'SUCESSO'", "id={$pedidoId}")
            ->ordem('cadastrado_em DESC')
            ->resultado(true);
    }

    /**
     * Busca logs por slug da InfinitePay
     *
     * @param string $slug Slug da fatura
     * @return array|null Lista de logs ou null
     */
    public function buscarPorSlug(string $slug): ?array
    {
        return $this->busca("infinitepay_slug = :slug", "slug={$slug}")
            ->ordem('cadastrado_em DESC')
            ->resultado(true);
    }

    /**
     * Busca logs por transaction NSU
     *
     * @param string $transactionNsu NSU da transação
     * @return array|null Lista de logs ou null
     */
    public function buscarPorTransactionNsu(string $transactionNsu): ?array
    {
        return $this->busca("transaction_nsu = :nsu", "nsu={$transactionNsu}")
            ->ordem('cadastrado_em DESC')
            ->resultado(true);
    }

    /**
     * Busca logs por order NSU
     *
     * @param string $orderNsu NSU do pedido
     * @return array|null Lista de logs ou null
     */
    public function buscarPorOrderNsu(string $orderNsu): ?array
    {
        return $this->busca("order_nsu = :nsu", "nsu={$orderNsu}")
            ->ordem('cadastrado_em DESC')
            ->resultado(true);
    }

    /**
     * Busca logs por etapa específica
     *
     * @param string $etapa Nome da etapa (criar_link, verificar_pagamento, webhook)
     * @param int $limite Quantidade máxima de registros
     * @return array|null Lista de logs ou null
     */
    public function buscarPorEtapa(string $etapa, int $limite = 50): ?array
    {
        return $this->busca("etapa = :etapa", "etapa={$etapa}")
            ->ordem('cadastrado_em DESC')
            ->limite($limite)
            ->resultado(true);
    }

    /**
     * Busca o último log de um pedido
     *
     * @param int $pedidoId ID do pedido
     * @return object|null Último log ou null
     */
    public function buscarUltimoPorPedido(int $pedidoId): ?object
    {
        $resultado = $this->busca("pedido_id = :id", "id={$pedidoId}")
            ->ordem('cadastrado_em DESC')
            ->limite(1)
            ->resultado(true);

        return $resultado ? $resultado[0] : null;
    }

    /**
     * Retorna estatísticas de logs agrupadas por status e data
     *
     * @return array Estatísticas de logs
     */
    public function estatisticasPorStatus(): array
    {
        $sql = "SELECT 
                    status,
                    COUNT(*) as total,
                    DATE(cadastrado_em) as data
                FROM {$this->tabela}
                GROUP BY status, DATE(cadastrado_em)
                ORDER BY data DESC, status";

        $stmt = Conexao::getInstancia()->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
