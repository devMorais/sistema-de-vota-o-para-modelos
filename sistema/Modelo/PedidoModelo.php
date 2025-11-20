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
     * Busca o Post (Candidata) relacionado a este pedido
     * @return PostModelo|null
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
     * @return PacoteModelo|null
     */
    public function pacote(): ?PacoteModelo
    {
        if ($this->pacote_id) {
            return (new PacoteModelo())->buscaPorId($this->pacote_id);
        }
        return null;
    }
}
