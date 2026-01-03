<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;

/**
 * Classe PedidoModelo
 * Responsável por registrar as vendas e transações do Asaas
 */
class PedidoModelo extends Modelo
{
    public function __construct()
    {
        // Nome da tabela de pedidos
        parent::__construct('pedidos');
    }

    /**
     * Calcula o faturamento bruto e taxas de uma candidata específica
     * Utiliza a arquitetura do Modelo base para manter a segurança
     * * @param int $postId
     * @return object|null
     */
    public function financeiroPorPost(int $postId): ?object
    {
        $colunas = "SUM(valor_total) as bruto, SUM(valor_taxa) as taxas, SUM(valor_subtotal) as lucro";
        $termos = "post_id = :id AND status = 'PAGO'";
        $parametros = "id={$postId}";

        return $this->busca($termos, $parametros, $colunas)->resultado();
    }

    /**
     * Busca o Post (Candidata) relacionado a este pedido
     */
    public function post(): ?PostModelo
    {
        if ($this->post_id) {
            return (new PostModelo())->buscaPorId($this->post_id);
        }
        return null;
    }

    /**
     * Busca o Pacote original relacionado (se houver)
     */
    public function pacote(): ?PacoteModelo
    {
        if ($this->pacote_id) {
            return (new PacoteModelo())->buscaPorId($this->pacote_id);
        }
        return null;
    }
}
