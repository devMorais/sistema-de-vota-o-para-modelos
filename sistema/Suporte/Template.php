<?php

namespace sistema\Suporte;

use sistema\Nucleo\Helpers;
use sistema\Controlador\UsuarioControlador;

/**
 * Classe Template
 */
class Template
{
    private \Twig\Environment $twig;

    public function __construct(string $diretorio)
    {
        $loader = new \Twig\Loader\FilesystemLoader($diretorio);
        $this->twig = new \Twig\Environment($loader, [
            'debug' => true,
            'auto_reload' => true,
        ]);

        $this->helpers();
    }

    /**
     * Metodo responsavel por realizar a renderização das views
     * @param string $view
     * @param array $dados
     * @return string
     */
    public function renderizar(string $view, array $dados)
    {
        try {
            return $this->twig->render($view, $dados);
        } catch (\Twig\Error\LoaderError | \Twig\Error\SyntaxError | \Twig\Error\RuntimeError $ex) {
            echo 'Erro no Template: ' . $ex->getMessage();
            die();
        }
    }

    /**
     * Metodo responsavel por chamar funções da classe Helpers
     * @return void
     */
    private function helpers(): void
    {
        // 1. URL
        $this->twig->addFunction(
            new \Twig\TwigFunction('url', function (?string $url = null) {
                return Helpers::url($url);
            })
        );

        // 2. Saudação
        $this->twig->addFunction(
            new \Twig\TwigFunction('saudacao', function () {
                return Helpers::saudacao();
            })
        );

        // 3. Resumir Texto
        $this->twig->addFunction(
            new \Twig\TwigFunction('resumirTexto', function (string $texto, int $limite) {
                return Helpers::resumirTexto($texto, $limite);
            })
        );

        // 4. Flash Message
        $this->twig->addFunction(
            new \Twig\TwigFunction('flash', function () {
                return Helpers::flash();
            })
        );

        // 5. Usuário (Atenção: verifique se isso não está causando a lentidão de 5s)
        $this->twig->addFunction(
            new \Twig\TwigFunction('usuario', function () {
                return UsuarioControlador::usuario();
            })
        );

        // 6. Contar Tempo
        $this->twig->addFunction(
            new \Twig\TwigFunction('contarTempo', function (string $data) {
                return Helpers::contarTempo($data);
            })
        );

        // 7. Formatar Número
        $this->twig->addFunction(
            new \Twig\TwigFunction('formatarNumero', function (?int $numero = null) {
                return Helpers::formatarNumero($numero);
            })
        );

        // 8. Formatar Valor
        $this->twig->addFunction(
            new \Twig\TwigFunction('formatarValor', function (?float $valor = null) {
                return Helpers::formatarValor($valor);
            })
        );

        // 9. Tempo de Carregamento (CORRIGIDO)
        $this->twig->addFunction(
            new \Twig\TwigFunction('tempoCarregamento', function () {
                $inicio = $_SERVER["REQUEST_TIME_FLOAT"] ?? microtime(true);
                $tempoTotal = microtime(true) - $inicio;
                return number_format($tempoTotal, 4);
            })
        );
    }
}
