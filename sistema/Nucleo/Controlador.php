<?php

namespace sistema\Nucleo;

use sistema\Suporte\Template;
use sistema\Nucleo\Mensagem; // Adicionei este 'use' que estava faltando
use sistema\Modelo\UsuarioModelo; // Adicionado para o type-hint
use sistema\Controlador\UsuarioControlador; // Adicionado para buscar o usuário

/**
 * Classe Controlador, responsável por instanciar templates e mensagens para uso global
 *
 * @author Fernando Aguiar
 */
class Controlador
{
    protected Template $template;
    protected Mensagem $mensagem;
    protected ?UsuarioModelo $usuario;

    /**
     * Construtor responsável por definir o diretório pai das views e criar a instancia do engine template e mensagens.
     * @param string $diretorio
     */
    public function __construct(string $diretorio)
    {
        $this->template = new Template($diretorio);
        $this->mensagem = new Mensagem();
        $this->usuario = UsuarioControlador::usuario();
    }
}
