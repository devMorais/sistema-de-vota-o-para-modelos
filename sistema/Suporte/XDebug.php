<?php

namespace sistema\Suporte;

/**
 * Classe de helper para debug rápido.
 * Fornece métodos estáticos para inspecionar variáveis na tela.
 */
class XDebug
{
    /**
     * Função que executa debug (print_r).
     * Pode ser passado vários argumentos para o debug.
     *
     * @param mixed ...$vars Variáveis para inspecionar.
     * @return void
     */
    public static function x(mixed ...$vars): void
    {
        // Chama o helper interno com a cor amarela, 'print_r', sem 'die'
        self::debugInternal('#ffff9f', 'print_r', false, ...$vars);
    }

    /**
     * Função que executa debug (print_r) e encerra (die).
     * Pode ser passado vários argumentos para o debug.
     *
     * @param mixed ...$vars Variáveis para inspecionar.
     * @return void
     */
    public static function xd(mixed ...$vars): void
    {
        // Chama o helper interno com a cor azul, 'print_r', com 'die'
        self::debugInternal('#BBCCDD', 'print_r', true, ...$vars);
    }

    /**
     * Função que executa debug (var_dump).
     * Pode ser passado vários argumentos para o debug.
     *
     * @param mixed ...$vars Variáveis para inspecionar.
     * @return void
     */
    public static function varDump(mixed ...$vars): void
    {
        // Chama o helper interno com a cor amarela, 'var_dump', sem 'die'
        self::debugInternal('#ffff9f', 'var_dump', false, ...$vars);
    }

    /**
     * Função que executa debug (var_dump) e encerra (die).
     * Pode ser passado vários argumentos para o debug.
     *
     * @param mixed ...$vars Variáveis para inspecionar.
     * @return void
     */
    public static function varDumpDie(mixed ...$vars): void
    {
        // Chama o helper interno com a cor azul, 'var_dump', com 'die'
        self::debugInternal('#BBCCDD', 'var_dump', true, ...$vars);
    }

    /**
     * Método interno que renderiza a saída de debug.
     *
     * @param string $color Cor de fundo (hex).
     * @param callable|string $dumper Função de dump a ser usada (ex: 'print_r', 'var_dump').
     * @param bool $dieAfter Se deve encerrar o script após a exibição.
     * @param mixed ...$vars Variáveis para exibir.
     */
    private static function debugInternal(string $color, callable|string $dumper, bool $dieAfter, mixed ...$vars): void
    {
        // Pega o backtrace.
        // [0] = frame de debugInternal()
        // [1] = frame de x(), xd(), varDump() ou varDumpDie() <- Este é o que queremos
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $locationInfo = 'Arquivo: Unknown ---> Linha: Unknown<br><p>'; // Valor padrão
        if (isset($backtrace[1])) {
            $file = $backtrace[1]['file'] ?? 'Unknown';
            $line = $backtrace[1]['line'] ?? 'Unknown';
            // Escapa o caminho do arquivo para evitar quebra de HTML
            $file = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
            $locationInfo = "Arquivo: {$file} ---> Linha: {$line}<br><p>";
        }

        // Garante que a cor é segura para o atributo style
        $safeColor = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');

        // Define um z-index alto para tentar sobrepor outros elementos
        echo "<div style='border: 1px solid black; padding: 10px; background-color: {$safeColor}; margin: 10px; z-index: 99999; position: relative; font-family: monospace;'>";
        echo $locationInfo;

        if (empty($vars)) {
            echo "<b><u>Nenhum argumento passado.</u></b><br>";
        } else {
            foreach ($vars as $idx => $arg) {
                echo "<b><u>ARG[{$idx}]</u></b><br><pre>";

                // Captura a saída do dumper para poder escapá-la
                ob_start();
                $dumper($arg);
                $output = ob_get_clean();

                // Escapa a saída (ex: se a variável for uma string HTML)
                echo htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
                echo "</pre>";
            }
        }

        echo "</div><br><br>";

        // Garante que o buffer de saída seja enviado ao navegador
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();

        if ($dieAfter) {
            die;
        }
    }
}
