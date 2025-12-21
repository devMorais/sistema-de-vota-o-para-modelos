<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;

/**
 * Classe PacoteModelo
 * Responsável por gerenciar os pacotes de votos disponíveis
 */
class PacoteModelo extends Modelo
{

    public function __construct()
    {
        parent::__construct('pacotes_votos');
    }

    /**
     * Retorna o valor total do pacote (Preço + Taxa)
     * MUDAMOS O NOME DE total() PARA valorTotal()
     * @return float
     */
    public function valorTotal(): float
    {
        $valor = $this->valor ?? 0;
        $taxa = $this->taxa ?? 0;

        return (float) ($valor + $taxa);
    }
}
