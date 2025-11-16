<?php

namespace sistema\Controlador\Admin;

use sistema\Nucleo\Controlador;
use sistema\Nucleo\Helpers;
use sistema\Controlador\UsuarioControlador;
use sistema\Modelo\UsuarioModelo;
use sistema\Nucleo\Sessao;

/**
 * Classe AdminControlador
 *
 * @author Fernando Aguiar
 */
class AdminControlador extends Controlador
{
    protected ?UsuarioModelo $usuario;

    public function __construct()
    {
        parent::__construct('templates/admin/views');

        $this->usuario = UsuarioControlador::usuario();

        if (!$this->usuario or $this->usuario->level != 3) {
            $this->mensagem->erro('Faça login para acessar o painel de controle!')->flash();

            $sessao = new Sessao();
            $sessao->limpar('usuarioId');

            Helpers::redirecionar('admin/login');
        }
    }
}
