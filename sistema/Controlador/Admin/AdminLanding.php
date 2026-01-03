<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\LandingPageModelo;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use sistema\Nucleo\Helpers;

/**
 * Classe AdminLanding
 *
 * @author Fernando Aguiar
 */
class AdminLanding extends AdminControlador
{

    private ?string $imagem_fundo = null;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Edita a Landing Page
     * @return void
     */
    public function editar(): void
    {
        $landing = (new LandingPageModelo())->buscaPorId(1);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $landing = (new LandingPageModelo())->buscaPorId(1);

                if (!$landing) {
                    $landing = new LandingPageModelo();
                }

                $landing->texto_topo = $dados['texto_topo'];
                $landing->titulo_principal = $dados['titulo_principal'];
                $landing->subtitulo = $dados['subtitulo'];
                $landing->texto_botao = $dados['texto_botao'];
                $landing->url_botao = $dados['url_botao'] ?? URL_INICIAL;
                $landing->status = $dados['status'] ?? STATUS_ATIVO;
                $landing->atualizado_em = date('Y-m-d H:i:s');

                if (!empty($_FILES['imagem_fundo']["name"])) {
                    if ($landing->imagem_fundo && file_exists("uploads/imagens/thumbs/{$landing->imagem_fundo}")) {
                        unlink("uploads/imagens/thumbs/{$landing->imagem_fundo}");
                    }

                    $landing->imagem_fundo = $this->imagem_fundo ?? null;
                }

                if ($landing->salvar()) {
                    $this->mensagem->sucesso('Landing Page atualizada com sucesso')->flash();
                    Helpers::redirecionar('admin/landing/editar');
                } else {
                    $this->mensagem->erro($landing->erro())->flash();
                    Helpers::redirecionar('admin/landing/editar');
                }
            }
        }

        if (!$landing) {
            $landing = new LandingPageModelo();
        }

        echo $this->template->renderizar('landing/formulario.html', [
            'landing' => $landing
        ]);
    }

    /**
     * Valida os dados e processa a imagem convertendo para WEBP
     * @param array $dados
     * @return bool
     */
    public function validarDados(array $dados): bool
    {
        if (empty($dados['titulo_principal'])) {
            $this->mensagem->alerta('Escreva um título para a Landing Page!')->flash();
            return false;
        }

        if (!empty($_FILES['imagem_fundo']) && $_FILES['imagem_fundo']['error'] === UPLOAD_ERR_OK) {

            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($_FILES['imagem_fundo']['tmp_name']);

                $nomeArquivo = 'landing-bg-' . uniqid() . '.webp';
                $caminhoDiretorio = 'uploads/imagens/thumbs/';

                if (!is_dir($caminhoDiretorio)) {
                    mkdir($caminhoDiretorio, 0755, true);
                }

                $image->toWebp(85)->save($caminhoDiretorio . $nomeArquivo);

                $this->imagem_fundo = $nomeArquivo;
            } catch (\Exception $e) {
                $this->mensagem->erro('Erro ao processar imagem: ' . $e->getMessage())->flash();
                return false;
            }
        }

        return true;
    }
}
