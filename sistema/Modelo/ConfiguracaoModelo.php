<?php

namespace sistema\Modelo;

use sistema\Nucleo\Modelo;

/**
 * Classe ConfiguracaoModelo
 * Responsável por gerenciar as configurações globais do sistema
 */
class ConfiguracaoModelo extends Modelo
{
    public function __construct()
    {
        // Nome da tabela que você criou no banco de dados
        parent::__construct('configuracoes');
    }
}
