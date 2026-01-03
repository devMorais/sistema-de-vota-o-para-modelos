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
     * @param mixed $responseData Resposta da API InfinitePay
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

        $responseJson = null;
        if ($responseData !== null) {
            if (is_object($responseData) || is_array($responseData)) {
                $responseJson = json_encode($responseData);
            } else {
                $responseJson = $responseData;
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
     *
     * @param array|null $dados
     * @return array|null
     */
    private function sanitizarDados(?array $dados): ?array
    {
        if (!$dados) {
            return null;
        }

        $sanitizado = $dados;

        // Sanitizar dados de cartão (se houver)
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

        // Sanitizar informações pessoais sensíveis (opcional)
        if (isset($sanitizado['customer']['email'])) {
            $email = $sanitizado['customer']['email'];
            $partes = explode('@', $email);
            if (count($partes) === 2) {
                $sanitizado['customer']['email'] = substr($partes[0], 0, 3) . '***@' . $partes[1];
            }
        }

        if (isset($sanitizado['customer']['phone_number'])) {
            $telefone = $sanitizado['customer']['phone_number'];
            $sanitizado['customer']['phone_number'] = substr($telefone, 0, 5) . '****' . substr($telefone, -2);
        }

        return $sanitizado;
    }

    /**
     * Busca todos os logs de um pedido específico
     *
     * @param int $pedidoId
     * @return array|null
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
     * @param int $limite
     * @return array|null
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
     * @param int $pedidoId
     * @return array|null
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
     * @param string $slug
     * @return array|null
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
     * @param string $transactionNsu
     * @return array|null
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
     * @param string $orderNsu
     * @return array|null
     */
    public function buscarPorOrderNsu(string $orderNsu): ?array
    {
        return $this->busca("order_nsu = :nsu", "nsu={$orderNsu}")
            ->ordem('cadastrado_em DESC')
            ->resultado(true);
    }

    /**
     * Busca logs por etapa
     *
     * @param string $etapa
     * @param int $limite
     * @return array|null
     */
    public function buscarPorEtapa(string $etapa, int $limite = 50): ?array
    {
        return $this->busca("etapa = :etapa", "etapa={$etapa}")
            ->ordem('cadastrado_em DESC')
            ->limite($limite)
            ->resultado(true);
    }

    /**
     * Busca último log de um pedido
     *
     * @param int $pedidoId
     * @return object|null
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
     * Estatísticas de logs por status
     *
     * @return array
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
