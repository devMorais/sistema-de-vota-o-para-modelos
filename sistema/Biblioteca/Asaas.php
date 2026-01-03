<?php

namespace sistema\Biblioteca;

use sistema\Nucleo\Helpers;
use sistema\Modelo\LogPagamentoModelo;

class Asaas
{
    private $apiKey;
    private $url;
    private $log;
    private $pedidoId;

    public function __construct(?int $pedidoId = null)
    {
        $this->apiKey       = ASAAS_KEY;
        $this->url          = ASAAS_URL;
        $this->log          = new LogPagamentoModelo();
        $this->pedidoId     = $pedidoId;
    }

    public function gerarPixVenda(array $dadosCliente, float $valor, string $descricao, string $referenciaExterna)
    {
        $clienteId = $this->obterIdCliente(
            $dadosCliente['nome'],
            $dadosCliente['cpf'],
            $dadosCliente['email'],
            $dadosCliente['telefone'] ?? null
        );

        if (!$clienteId) {
            return ['erro' => true, 'mensagem' => 'Erro ao cadastrar cliente no Asaas. Verifique os dados (CPF/Email).'];
        }

        $dadosCobranca = [
            'customer' => $clienteId,
            'billingType' => 'PIX',
            'value' => $valor,
            'dueDate' => date('Y-m-d', strtotime('+2 days')),
            'description' => $descricao,
            'externalReference' => $referenciaExterna
        ];

        $cobranca = $this->post('/payments', $dadosCobranca);

        if (!isset($cobranca->id)) {
            $msg = $cobranca->errors[0]->description ?? 'Erro desconhecido ao criar cobrança.';
            $codigoErro = $cobranca->errors[0]->code ?? null;

            $this->log->registrar(
                $this->pedidoId,
                'PIX',
                'criar_cobranca',
                'ERRO',
                $msg,
                $dadosCobranca,
                $cobranca,
                $codigoErro,
                null,
                $this->url . '/payments'
            );

            return ['erro' => true, 'mensagem' => $msg];
        }

        $qrCode = $this->get("/payments/{$cobranca->id}/pixQrCode");

        if (!isset($qrCode->payload)) {
            $this->log->registrar(
                $this->pedidoId,
                'PIX',
                'gerar_pix',
                'ERRO',
                'Erro ao gerar QR Code PIX',
                null,
                $qrCode,
                null,
                $cobranca->id,
                $this->url . "/payments/{$cobranca->id}/pixQrCode"
            );
            return ['erro' => true, 'mensagem' => 'Erro ao gerar QR Code PIX'];
        }

        $this->log->registrar(
            $this->pedidoId,
            'PIX',
            'gerar_pix',
            'SUCESSO',
            'PIX gerado com sucesso',
            null,
            ['id' => $cobranca->id, 'status' => $cobranca->status],
            null,
            $cobranca->id,
            $this->url . "/payments/{$cobranca->id}/pixQrCode",
            200
        );

        return [
            'erro' => false,
            'id_transacao' => $cobranca->id,
            'status' => $cobranca->status,
            'payload' => $qrCode->payload,
            'encodedImage' => $qrCode->encodedImage
        ];
    }

    public function gerarCobrancaCartao(array $dadosCliente, array $dadosCartao, float $valor, string $descricao, string $referenciaExterna)
    {
        if (empty($dadosCliente['email']) || empty($dadosCliente['telefone'])) {
            $this->log->registrar(
                $this->pedidoId,
                'CARTAO',
                'validacao',
                'ERRO',
                'Email e telefone são obrigatórios para pagamento com cartão',
                ['email_presente' => !empty($dadosCliente['email']), 'telefone_presente' => !empty($dadosCliente['telefone'])],
                null,
                'invalid_data'
            );
            return ['erro' => true, 'mensagem' => 'Email e telefone são obrigatórios para pagamento com cartão.'];
        }

        $clienteId = $this->obterIdCliente(
            $dadosCliente['nome'],
            $dadosCliente['cpf'],
            $dadosCliente['email'],
            $dadosCliente['telefone'],
            $dadosCliente['cep']
        );

        if (!$clienteId) {
            return ['erro' => true, 'mensagem' => 'Erro ao cadastrar cliente no Asaas. Verifique CPF/Email.'];
        }

        $resultadoToken = $this->tokenizarCartao($dadosCartao, $dadosCliente, $clienteId);

        if ($resultadoToken['erro']) {
            return $resultadoToken;
        }

        return $this->pagarComTokenCartao(
            $clienteId,
            $valor,
            $descricao,
            $referenciaExterna,
            $resultadoToken['token']
        );
    }

    private function obterIdCliente($nome, $cpf, $email, $telefone = null, $cep = null)
    {
        $cpfLimpo = Helpers::limparNumero($cpf);
        $busca = $this->get("/customers?cpfCnpj={$cpfLimpo}");

        $tipoPagamento = !empty($cep) ? 'CARTAO' : 'PIX';

        if (!isset($busca->data)) {
            $this->log->registrar(
                $this->pedidoId,
                $tipoPagamento,
                'criar_cliente',
                'ERRO',
                'Erro ao buscar cliente no Asaas',
                ['cpf' => $cpfLimpo],
                $busca,
                null,
                null,
                $this->url . "/customers?cpfCnpj={$cpfLimpo}"
            );
            return null;
        }

        if (isset($busca->data) && count($busca->data) > 0) {
            $this->log->registrar(
                $this->pedidoId,
                $tipoPagamento,
                'criar_cliente',
                'SUCESSO',
                'Cliente encontrado no Asaas',
                null,
                null,
                null,
                $busca->data[0]->id,
                $this->url . "/customers?cpfCnpj={$cpfLimpo}",
                200
            );
            return $busca->data[0]->id;
        }

        $novoCliente = [
            'name' => $nome,
            'cpfCnpj' => $cpfLimpo,
            'email' => $email,
            'mobilePhone' => $telefone
        ];

        $criacao = $this->post('/customers', $novoCliente);

        if (!isset($criacao->id)) {
            $codigoErro = $criacao->errors[0]->code ?? null;
            $mensagem = $criacao->errors[0]->description ?? 'Erro ao criar cliente';

            $this->log->registrar(
                $this->pedidoId,
                $tipoPagamento,
                'criar_cliente',
                'ERRO',
                $mensagem,
                $novoCliente,
                $criacao,
                $codigoErro,
                null,
                $this->url . '/customers'
            );
            return null;
        }

        $this->log->registrar(
            $this->pedidoId,
            $tipoPagamento,
            'criar_cliente',
            'SUCESSO',
            'Cliente criado com sucesso',
            null,
            null,
            null,
            $criacao->id,
            $this->url . '/customers',
            200
        );

        return $criacao->id;
    }

    private function post($endpoint, $dados)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, ASAAS_SSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ASAAS_SSL ? 2 : 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
            'User-Agent: SistemaVotos'
        ]);

        $resultado = curl_exec($ch);

        if (curl_errno($ch)) {
            $erro = curl_error($ch);
            curl_close($ch);
            error_log('Erro cURL: ' . $erro);
            return (object)['errors' => [['code' => 'connection_error', 'description' => 'Erro de conexão com Asaas']]];
        }

        curl_close($ch);
        return json_decode($resultado);
    }

    private function get($endpoint)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, ASAAS_SSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ASAAS_SSL ? 2 : 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
            'User-Agent: SistemaVotos'
        ]);

        $resultado = curl_exec($ch);

        if (curl_errno($ch)) {
            $erro = curl_error($ch);
            curl_close($ch);
            error_log('Erro cURL GET: ' . $erro);
            return (object)['errors' => [['code' => 'connection_error', 'description' => 'Erro de conexão com Asaas']]];
        }

        curl_close($ch);
        return json_decode($resultado);
    }

    public function consultarCobranca($idTransacao)
    {
        return $this->get("/payments/{$idTransacao}");
    }

    public function setPedidoId(int $pedidoId): void
    {
        $this->pedidoId = $pedidoId;
    }

    public function tokenizarCartao(array $dadosCartao, array $dadosCliente, string $clienteId)
    {
        $endpoint = '/creditCard/tokenizeCreditCard';

        $dados = [
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
            ],
            'remoteIp' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];

        $resultado = $this->post($endpoint, $dados);

        if (!isset($resultado->creditCardToken)) {
            $msg = isset($resultado->errors) && count($resultado->errors) > 0
                ? $resultado->errors[0]->description
                : 'Erro ao tokenizar cartão';
            $codigoErro = $resultado->errors[0]->code ?? null;

            $this->log->registrar(
                $this->pedidoId,
                'CARTAO',
                'tokenizar_cartao',
                'ERRO',
                $msg,
                $dados,
                $resultado,
                $codigoErro,
                $clienteId,
                $this->url . $endpoint
            );

            return ['erro' => true, 'mensagem' => $msg];
        }

        $this->log->registrar(
            $this->pedidoId,
            'CARTAO',
            'tokenizar_cartao',
            'SUCESSO',
            'Cartão tokenizado com sucesso',
            null,
            null,
            null,
            $clienteId,
            $this->url . $endpoint,
            200
        );

        return [
            'erro' => false,
            'token' => $resultado->creditCardToken
        ];
    }

    public function pagarComTokenCartao(string $clienteId, float $valor, string $descricao, string $referenciaExterna, string $tokenCartao)
    {
        $dadosCobranca = [
            'customer' => $clienteId,
            'billingType' => 'CREDIT_CARD',
            'value' => $valor,
            'dueDate' => date('Y-m-d'),
            'description' => $descricao,
            'externalReference' => $referenciaExterna
        ];

        $cobranca = $this->post('/payments', $dadosCobranca);

        if (!isset($cobranca->id)) {
            $msg = isset($cobranca->errors) && count($cobranca->errors) > 0
                ? $cobranca->errors[0]->description
                : 'Erro ao criar cobrança';
            $codigoErro = $cobranca->errors[0]->code ?? null;

            $this->log->registrar(
                $this->pedidoId,
                'CARTAO',
                'criar_cobranca',
                'ERRO',
                $msg,
                $dadosCobranca,
                $cobranca,
                $codigoErro,
                null,
                $this->url . '/payments'
            );

            return ['erro' => true, 'mensagem' => $msg];
        }

        $dadosPagamento = [
            'creditCardToken' => $tokenCartao
        ];

        $pagamento = $this->post("/payments/{$cobranca->id}/payWithCreditCard", $dadosPagamento);

        if (!isset($pagamento->id)) {
            $msg = isset($pagamento->errors) && count($pagamento->errors) > 0
                ? $pagamento->errors[0]->description
                : 'Erro ao processar pagamento';
            $codigoErro = $pagamento->errors[0]->code ?? null;

            $this->log->registrar(
                $this->pedidoId,
                'CARTAO',
                'processar_pagamento',
                'ERRO',
                $msg,
                $dadosPagamento,
                $pagamento,
                $codigoErro,
                $cobranca->id,
                $this->url . "/payments/{$cobranca->id}/payWithCreditCard"
            );

            return ['erro' => true, 'mensagem' => $msg];
        }

        $this->log->registrar(
            $this->pedidoId,
            'CARTAO',
            'processar_pagamento',
            'SUCESSO',
            'Pagamento processado com sucesso',
            null,
            ['id' => $pagamento->id, 'status' => $pagamento->status],
            null,
            $pagamento->id,
            $this->url . "/payments/{$cobranca->id}/payWithCreditCard",
            200
        );

        return [
            'erro' => false,
            'id_transacao' => $pagamento->id,
            'status' => $pagamento->status
        ];
    }
}
