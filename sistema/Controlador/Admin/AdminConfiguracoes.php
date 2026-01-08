<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\ConfiguracaoModelo;
use sistema\Modelo\PacoteModelo;
use sistema\Nucleo\Helpers;

class AdminConfiguracoes extends AdminControlador
{
    public function editar(): void
    {
        $config = (new ConfiguracaoModelo())->buscaPorId(1);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $config = (new ConfiguracaoModelo())->buscaPorId(1) ?: new ConfiguracaoModelo();

                $config->whatsapp = Helpers::limparNumero($dados['whatsapp']);
                $config->posts_por_pagina = (int) $dados['posts_por_pagina'];
                $config->ordenacao_posts = $dados['ordenacao_posts'];
                $config->gateway_pagamento = $dados['gateway_pagamento'] ?? 'ASAAS';

                if ($config->salvar()) {
                    $this->mensagem->sucesso('Configurações atualizadas com sucesso!')->flash();
                    Helpers::redirecionar('admin/configuracoes/editar');
                } else {
                    $this->mensagem->erro($config->erro())->flash();
                    Helpers::redirecionar('admin/configuracoes/editar');
                }
            }
        }

        echo $this->template->renderizar('configuracoes/formulario.html', [
            'config' => $config
        ]);
    }

    private function validarDados(array $dados): bool
    {
        if (empty($dados['whatsapp'])) {
            $this->mensagem->alerta('Informe um número de WhatsApp para suporte!')->flash();
            return false;
        }

        if (empty($dados['posts_por_pagina']) || $dados['posts_por_pagina'] < 1) {
            $this->mensagem->alerta('A quantidade de posts por página deve ser no mínimo 1!')->flash();
            return false;
        }

        if ($dados['gateway_pagamento'] === 'ASAAS') {
            $pacotes = (new PacoteModelo())->busca("status = 1")->resultado(true);
            if ($pacotes) {
                foreach ($pacotes as $pacote) {
                    $valorFinal = (float)$pacote->valor + (float)$pacote->taxa;
                    if ($valorFinal < 5.00) {
                        $this->mensagem->erro("Erro: Para usar o ASAAS, todos os pacotes ativos devem custar no mínimo R$ 5,00. O pacote '{$pacote->titulo}' custa R$ " . number_format($valorFinal, 2, ',', '.') . ".")->flash();
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
