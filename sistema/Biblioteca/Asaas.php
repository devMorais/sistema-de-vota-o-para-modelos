<?php

namespace sistema\Biblioteca;

use sistema\Nucleo\Helpers;

class Asaas
{
    private $apiKey;
    private $url;

    public function __construct()
    {
        $this->apiKey = ASAAS_KEY;
        $this->url    = ASAAS_URL;
    }

    /**
     * Função Principal: Faz todo o processo de gerar o PIX
     */
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
            return ['erro' => true, 'mensagem' => $msg];
        }

        $qrCode = $this->get("/payments/{$cobranca->id}/pixQrCode");

        return [
            'erro' => false,
            'id_transacao' => $cobranca->id,
            'status' => $cobranca->status,
            'payload' => $qrCode->payload ?? null,
            'encodedImage' => $qrCode->encodedImage ?? null
        ];
    }

    /**
     * Verifica se o cliente já existe pelo CPF, se não, cria um novo.
     */
    private function obterIdCliente($nome, $cpf, $email, $telefone = null)
    {
        $cpfLimpo = Helpers::limparNumero($cpf);
        $busca = $this->get("/customers?cpfCnpj={$cpfLimpo}");

        if (isset($busca->data) && count($busca->data) > 0) {
            return $busca->data[0]->id;
        }

        $novoCliente = [
            'name' => $nome,
            'cpfCnpj' => $cpfLimpo,
            'email' => $email,
            'mobilePhone' => $telefone
        ];

        $criacao = $this->post('/customers', $novoCliente);

        return $criacao->id ?? null;
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
        curl_close($ch);

        return json_decode($resultado);
    }

    /**
     * Consulta o status de uma cobrança no Asaas
     * @param string $idTransacao Ex: pay_123456
     */
    public function consultarCobranca($idTransacao)
    {
        return $this->get("/payments/{$idTransacao}");
    }
}
