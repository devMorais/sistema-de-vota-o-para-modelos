<?php

namespace sistema\Biblioteca;

use sistema\Nucleo\Helpers;
use sistema\Modelo\LogPagamentoModelo;

class Asaas
{
    private string $apiKey;
    private string $url;
    private LogPagamentoModelo $log;
    private ?int $pedidoId;

    public function __construct(?int $pedidoId = null)
    {
        $this->apiKey = ASAAS_KEY;
        $this->url = ASAAS_URL;
        $this->log = new LogPagamentoModelo();
        $this->pedidoId = $pedidoId;
    }

    public function gerarPixVenda(array $dadosCliente, float $valor, string $descricao, string $referenciaExterna): array
    {
        $clienteId = $this->obterIdCliente($dadosCliente, 'PIX');
        if (!$clienteId) return ['erro' => true, 'mensagem' => 'Erro ao processar cliente.'];

        $payload = [
            'customer' => $clienteId,
            'billingType' => 'PIX',
            'value' => $valor,
            'dueDate' => date('Y-m-d', strtotime('+2 days')),
            'description' => $descricao,
            'externalReference' => $referenciaExterna
        ];

        $cobranca = $this->request('/payments', $payload, 'PIX', 'criar_cobranca');
        if (!isset($cobranca->id)) return ['erro' => true, 'mensagem' => $cobranca->errors[0]->description ?? 'Erro'];

        $qrCode = $this->request("/payments/{$cobranca->id}/pixQrCode", [], 'PIX', 'gerar_pix', 'GET');

        return [
            'erro' => false,
            'id_transacao' => $cobranca->id,
            'status' => $cobranca->status,
            'payload' => $qrCode->payload ?? '',
            'encodedImage' => $qrCode->encodedImage ?? ''
        ];
    }

    public function gerarCobrancaCartao(array $dadosCliente, array $dadosCartao, float $valor, string $descricao, string $referenciaExterna): array
    {
        $clienteId = $this->obterIdCliente($dadosCliente, 'CARTAO');
        if (!$clienteId) return ['erro' => true, 'mensagem' => 'Erro ao processar cliente.'];

        $payloadToken = [
            'customer' => $clienteId,
            'creditCard' => [
                'holderName' => $dadosCartao['nome'],
                'number' => Helpers::limparNumero($dadosCartao['numero']),
                'expiryMonth' => $dadosCartao['mes'],
                'expiryYear' => $dadosCartao['ano'],
                'ccv' => $dadosCartao['cvv']
            ],
            'creditCardHolderInfo' => [
                'name' => $dadosCliente['nome'],
                'email' => $dadosCliente['email'],
                'cpfCnpj' => Helpers::limparNumero($dadosCliente['cpf']),
                'postalCode' => Helpers::limparNumero($dadosCliente['cep'] ?? '01310100'),
                'addressNumber' => '0',
                'phone' => Helpers::limparNumero($dadosCliente['telefone'])
            ]
        ];

        $resToken = $this->request('/creditCard/tokenizeCreditCard', $payloadToken, 'CARTAO', 'tokenizar_cartao');
        if (!isset($resToken->creditCardToken)) {
            return ['erro' => true, 'mensagem' => $resToken->errors[0]->description ?? 'Cartão recusado.'];
        }

        $payloadPgto = [
            'customer' => $clienteId,
            'billingType' => 'CREDIT_CARD',
            'value' => $valor,
            'dueDate' => date('Y-m-d'),
            'description' => $descricao,
            'externalReference' => $referenciaExterna,
            'creditCardToken' => $resToken->creditCardToken
        ];

        $cobranca = $this->request('/payments', $payloadPgto, 'CARTAO', 'criar_cobranca');

        if (!isset($cobranca->id)) {
            return ['erro' => true, 'mensagem' => $cobranca->errors[0]->description ?? 'Erro no processamento.'];
        }

        return [
            'erro' => false,
            'id_transacao' => $cobranca->id,
            'status' => $cobranca->status
        ];
    }

    public function consultarCobranca(string $idCobrancaAsaas): object
    {
        return $this->request("/payments/{$idCobrancaAsaas}", [], 'PIX', 'consultar_status', 'GET');
    }

    private function request(string $endpoint, array $dados, string $tipoPgto, string $etapa, string $method = 'POST'): object
    {
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $this->url . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'access_token: ' . $this->apiKey,
                'User-Agent: SistemaVotacao'
            ],
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => true
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($dados);
        }

        curl_setopt_array($ch, $options);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        $respostaObj = json_decode($res);

        $statusLog = ($httpCode >= 200 && $httpCode < 300) ? 'SUCESSO' : 'ERRO';
        $msgLog = $curlError ?: ($respostaObj->errors[0]->description ?? null);

        $this->log->registrar(
            $this->pedidoId,
            $tipoPgto,
            $etapa,
            $statusLog,
            $msgLog,
            $dados,
            $res,
            $respostaObj->errors[0]->code ?? null,
            $respostaObj->id ?? null,
            $this->url . $endpoint,
            $httpCode
        );

        if ($curlError) return (object)['errors' => [['description' => $curlError]]];
        return $respostaObj ?? (object)['errors' => [['description' => 'Resposta Inválida']]];
    }

    private function obterIdCliente(array $d, string $tipo): ?string
    {
        $cpf = Helpers::limparNumero($d['cpf']);

        $busca = $this->request("/customers?cpfCnpj={$cpf}", [], $tipo, 'buscar_cliente', 'GET');

        if (!empty($busca->data)) return $busca->data[0]->id;

        $novo = $this->request('/customers', [
            'name' => $d['nome'],
            'cpfCnpj' => $cpf,
            'email' => $d['email'] ?? null,
            'mobilePhone' => Helpers::limparNumero($d['telefone'] ?? '')
        ], $tipo, 'criar_cliente');

        return $novo->id ?? null;
    }
}
