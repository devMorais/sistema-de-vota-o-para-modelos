<?php

namespace sistema\Controlador;

use sistema\Nucleo\Controlador;
use sistema\Nucleo\Helpers;
use sistema\Nucleo\Sessao;
use sistema\Modelo\UsuarioModelo;
use sistema\Suporte\Email;

class UsuarioControlador extends Controlador
{
    private static ?UsuarioModelo $usuarioLogado = null;

    public function __construct()
    {
        parent::__construct('templates/usuario/views');
    }

    /**
     * Busca usuário pela sessão (COM CACHE DE MEMÓRIA)
     * @return UsuarioModelo|null
     */
    public static function usuario(): ?UsuarioModelo
    {
        if (self::$usuarioLogado) {
            return self::$usuarioLogado;
        }

        $sessao = new Sessao();
        if (!$sessao->checar('usuarioId')) {
            return null;
        }

        self::$usuarioLogado = (new UsuarioModelo())->buscaPorId($sessao->usuarioId);

        return self::$usuarioLogado;
    }

    /**
     * Cadastro de usuários
     * @return void
     */
    public function cadastro(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if (empty($dados['nome'])) {
                Helpers::json('erro', 'Informe seu nome!');
            } elseif (empty($dados['email'])) {
                Helpers::json('erro', 'Informe seu e-mail!');
            } else {
                $usuario = new UsuarioModelo();

                $usuario->nome = $dados['nome'];
                $usuario->email = $dados['email'];
                $usuario->token = Helpers::gerarToken();

                if ($usuario->salvar()) {

                    try {
                        $email = new Email();

                        $view = $this->template->renderizar('emails/confirmar-cadastro.html', [
                            'usuario' => $usuario,
                        ]);

                        $email->criar(
                            'Confirmação de Cadastro - ' . SITE_NOME,
                            $view,
                            $dados['email'],
                            $dados['nome']
                        );

                        $email->enviar(EMAIL_REMETENTE['email'], EMAIL_REMETENTE['nome']);

                        $this->mensagem->sucesso('Cadastrado realizado com sucesso')->flash();
                        Helpers::json('redirecionar', Helpers::url());
                    } catch (\PHPMailer\PHPMailer\Exception $ex) {
                        $id = $usuario->ultimoId();
                        $usuario = (new UsuarioModelo())->buscaPorId($id);
                        if ($usuario) {
                            $usuario->deletar();
                        }

                        Helpers::json('erro', 'Erro ao enviar e-mail. Tente novamente mais tarde! ' . $ex->getMessage());
                    }
                } else {
                    Helpers::json('erro', 'Erro de sistema: ' . $usuario->mensagem());
                }
            }
        }

        echo $this->template->renderizar('cadastro.html', [
            'titulo' => 'Cadastre-se'
        ]);
    }

    /**
     * Confirmar e-mail e ativar conta
     * @param string $token
     * @return void
     */
    public function confirmarEmail(string $token): void
    {

        $usuario = (new UsuarioModelo())->buscaPorToken($token);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!$usuario) {
            $this->mensagem->erro('Erro: Token inválido!')->flash();
            Helpers::redirecionar();
        } else {
            if (isset($dados)) {
                if ($this->validarDadosCadastro($dados)) {

                    $usuario->senha = Helpers::gerarSenha($dados['senha']);
                    $usuario->status = 1;
                    $usuario->token = null; // Limpa o token para não usar de novo
                    $usuario->cadastrado_em = date('Y-m-d H:i:s');

                    if ($usuario->salvar()) {
                        // Logar o usuário automaticamente após confirmar (opcional)
                        // (new Sessao())->criar('usuarioId', $usuario->id);

                        $this->mensagem->sucesso('Cadastrado confirmado com sucesso!')->flash();
                        Helpers::json('redirecionar', Helpers::url());
                    }
                }
            }
        }
        echo $this->template->renderizar('ativar.html', [
            'usuario' => $usuario
        ]);
    }

    /**
     * Validar campos do cadastro
     * @param array $dados
     * @return bool
     */
    public function validarDadosCadastro(array $dados): bool
    {
        if (empty($dados['senha'])) {
            Helpers::json('erro', 'Informe uma senha!');
            return false;
        }
        if ($dados['senha'] != $dados['senha2']) {
            Helpers::json('erro', 'As senhas estão diferentes!');
            return false;
        }
        if (!empty($dados['senha'])) {
            if (!Helpers::validarSenha($dados['senha'])) {
                Helpers::json('erro', 'A senha deve ter entre 6 e 50 caracteres!');
                return false;
            }
        }

        return true;
    }

    /**
     * Login
     * @return void
     */
    public function login(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if (in_array('', $dados)) {
                Helpers::json('erro', 'Informe seu login e senha!');
            } else {
                $usuario = new UsuarioModelo();

                if ($usuario->login($dados, 1)) {
                    $this->mensagem->sucesso('Seja bem vindo(a) ao seu painel de controle!')->flash();
                    Helpers::json('redirecionar', Helpers::url('saas'));
                } else {
                    Helpers::json('erro', strip_tags($usuario->mensagem()));
                }
            }
        }
    }
}
