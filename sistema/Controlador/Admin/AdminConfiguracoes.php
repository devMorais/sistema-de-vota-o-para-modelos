<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\ConfiguracaoModelo;
use sistema\Nucleo\Helpers;

/**
 * Classe AdminConfiguracoes
 * Gerencia as configurações globais do site
 * @author Fernando Aguiar
 */
class AdminConfiguracoes extends AdminControlador
{

    /**
     * Edita as configurações globais
     * @return void
     */
    public function editar(): void
    {

        $config = (new ConfiguracaoModelo())->buscaPorId(1);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $config = (new ConfiguracaoModelo())->buscaPorId(1);

                if (!$config) {
                    $config = new ConfiguracaoModelo();
                }

                $config->whatsapp = Helpers::limparNumero($dados['whatsapp']);
                $config->posts_por_pagina = (int) $dados['posts_por_pagina'];
                $config->ordenacao_posts = $dados['ordenacao_posts'];
                $config->gateway_pagamento = $dados['gateway_pagamento'] ?? 'ASAAS'; // NOVO

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

    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
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

        // Valida gateway
        if (!in_array($dados['gateway_pagamento'], ['ASAAS', 'INFINITEPAY'])) {
            $this->mensagem->alerta('Gateway de pagamento inválido!')->flash();
            return false;
        }

        return true;
    }
}
