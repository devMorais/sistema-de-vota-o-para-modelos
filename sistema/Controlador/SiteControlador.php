<?php

namespace sistema\Controlador;

use sistema\Nucleo\Controlador;
use sistema\Modelo\PostModelo;
use sistema\Nucleo\Helpers;
use sistema\Modelo\CategoriaModelo;
use sistema\Biblioteca\Paginar;
use sistema\Suporte\Email;
use sistema\Modelo\ConfiguracaoModelo;
use sistema\Modelo\LandingPageModelo;
use sistema\Modelo\PacoteModelo;

class SiteControlador extends Controlador
{
    private $config;

    public function __construct()
    {
        parent::__construct('templates/site/views');
        $this->config = (new ConfiguracaoModelo())->buscaPorId(1);
    }

    public function landing(): void
    {
        $landingModelo = new LandingPageModelo();
        $landing = $landingModelo->buscaPorId(1);

        if (!$landing) {
            $landing = (object) [
                'texto_topo' => 'CONCURSO OFICIAL',
                'titulo_principal' => 'Vote Pela Sua Miss',
                'subtitulo' => 'A beleza, a elegância e a simpatia estão em jogo.',
                'texto_botao' => 'VER CANDIDATAS',
                'url_botao' => 'votar',
                'imagem_fundo' => null,
                'status' => 1
            ];
        }

        $urlImagemFundo = $landing->imagem_fundo
            ? Helpers::url('uploads/imagens/thumbs/' . $landing->imagem_fundo)
            : null;

        echo $this->template->renderizar('landing.html', [
            'landing' => $landing,
            'urlImagemFundo' => $urlImagemFundo
        ]);
    }

    public function index(?int $pagina = null): void
    {
        $limite = $this->config->posts_por_pagina ?? 24;
        $ordem = $this->config->ordenacao_posts ?? 'titulo ASC';

        $pagina = $pagina ?? 1;
        $postModelo = new PostModelo();

        $total = $postModelo->busca("status = :s", "s=1")->total();

        $paginar = new Paginar(Helpers::url('page'), $pagina, $limite, 3, $total);

        $postsParaCards = $postModelo->busca("status = 1")
            ->ordem($ordem)
            ->limite($paginar->limite())
            ->offset($paginar->offset())
            ->resultado(true);

        echo $this->template->renderizar('index.html', [
            'posts' => $postsParaCards,
            'paginacao' => $paginar->renderizar(),
            'paginacaoInfo' => $paginar->info(),
            'categorias' => $this->categorias(),
            'config' => $this->config
        ]);
    }

    public function buscar(): void
    {
        $busca = filter_input(INPUT_POST, 'busca', FILTER_DEFAULT);

        if (isset($busca)) {
            $termo = "%{$busca}%";
            $query = "status = 1 AND (titulo LIKE '{$termo}' OR categoria_id IN (SELECT id FROM categorias WHERE titulo LIKE '{$termo}'))";

            $posts = (new PostModelo())->busca($query)->limite(5)->resultado(true);

            if ($posts) {
                echo "<div class='list-group'>";
                foreach ($posts as $post) {
                    $imagemUrl = $post->capa ? Helpers::url('uploads/imagens/thumbs/' . $post->capa) : 'https://placehold.co/50';
                    $link = Helpers::url('post/') . $post->categoria()->slug . '/' . $post->slug;

                    echo "
                    <a href='{$link}' class='list-group-item list-group-item-action d-flex align-items-center gap-3 bg-dark text-light border-secondary'>
                        <div style='width: 40px; height: 40px; min-width: 40px;'>
                            <img src='{$imagemUrl}' alt='{$post->titulo}' class='rounded-circle' style='width: 100%; height: 100%; object-fit: cover;'>
                        </div>
                        <div class='flex-grow-1'>
                            <h6 class='mb-0 text-white' style='font-size: 14px;'>{$post->titulo}</h6>
                            <small class='text-primary' style='font-size: 11px;'>{$post->categoria()->titulo}</small>
                        </div>
                    </a>";
                }
                echo "</div>";
            }
        }
    }

    public function post(string $categoria, string $slug): void
    {
        $post = (new PostModelo())->buscaPorSlug($slug);

        if (!$post) {
            Helpers::redirecionar('404');
        }

        $post->salvarVisitas();

        $pacotes = (new PacoteModelo())->busca('status = :s', 's=1')->resultado(true);

        echo $this->template->renderizar('post.html', [
            'post' => $post,
            'categorias' => $this->categorias(),
            'pacotes' => $pacotes
        ]);
    }

    public function categorias(): ?array
    {
        return (new CategoriaModelo())->busca("status = 1")->resultado(true);
    }

    public function categoria(string $slug, ?int $pagina = null): void
    {
        $categoria = (new CategoriaModelo())->buscaPorSlug($slug);
        if (!$categoria) {
            Helpers::redirecionar('404');
        }
        $categoria->salvarVisitas();

        $posts = (new PostModelo());
        $total = $posts->busca('categoria_id = :c AND status = :s', "c={$categoria->id}&s=1 COUNT(id)", 'id')->total();

        $paginar = new Paginar(Helpers::url('categoria/' . $slug), ($pagina ?? 1), 10, 3, $total);

        echo $this->template->renderizar('categoria.html', [
            'posts' => $posts->busca("categoria_id = {$categoria->id} AND status = 1")->limite($paginar->limite())->offset($paginar->offset())->resultado(true),
            'paginacao' => $paginar->renderizar(),
            'paginacaoInfo' => $paginar->info(),
            'categorias' => $this->categorias(),
            'categoriaAtual' => $categoria
        ]);
    }

    public function sobre(): void
    {
        echo $this->template->renderizar('sobre.html', [
            'titulo' => 'Sobre nós',
            'categorias' => $this->categorias(),
        ]);
    }

    public function erro404(): void
    {
        echo $this->template->renderizar('404.html', [
            'titulo' => 'Página não encontrada',
            'categorias' => $this->categorias(),
        ]);
    }

    public function checkout(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!isset($dados['pacotes']) || !isset($dados['post_id'])) {
            Helpers::redirecionar();
            return;
        }

        $postBusca = (new PostModelo())->busca("id = :id", "id={$dados['post_id']}")->resultado(true);
        if (!$postBusca) {
            Helpers::redirecionar('404');
            return;
        }
        $post = $postBusca[0];
        $pacotesDb = (new PacoteModelo())->busca("status = 1")->resultado(true);

        $tabelaPrecos = [];
        if ($pacotesDb) {
            foreach ($pacotesDb as $pct) {
                $tabelaPrecos[$pct->quantidade] = [
                    'id'    => $pct->id,
                    'valor' => $pct->valor,
                    'taxa'  => $pct->taxa
                ];
            }
        }

        $subtotal = 0;
        $taxaUnicaAplicada = 0;
        $totalVotos = 0;
        $itensCarrinho = false;

        $pacoteIdParaSalvar = null;
        $tiposDiferentesSelecionados = 0;

        foreach ($dados['pacotes'] as $tipo => $quantidade) {
            $quantidade = intval($quantidade);

            if ($quantidade > 0 && isset($tabelaPrecos[$tipo])) {
                $precoUnitario = $tabelaPrecos[$tipo]['valor'];
                $taxaUnitaria  = $tabelaPrecos[$tipo]['taxa'];

                $pacoteIdParaSalvar = $tabelaPrecos[$tipo]['id'];
                $tiposDiferentesSelecionados++;

                $subtotal += $precoUnitario * $quantidade;

                if ($taxaUnitaria > $taxaUnicaAplicada) {
                    $taxaUnicaAplicada = $taxaUnitaria;
                }

                $totalVotos += $tipo * $quantidade;
                $itensCarrinho = true;
            }
        }

        if (!$itensCarrinho) {
            Helpers::redirecionar('post/' . $post->categoria()->slug . '/' . $post->slug);
            return;
        }

        if ($tiposDiferentesSelecionados > 1) {
            $pacoteIdParaSalvar = null;
        }

        $totalGeral = $subtotal + $taxaUnicaAplicada;

        echo $this->template->renderizar('checkout.html', [
            'titulo'        => 'Checkout - ' . $post->titulo,
            'post'          => $post,
            'totalVotos'    => $totalVotos,
            'subtotal'      => number_format($subtotal, 2, ',', '.'),
            'totalTaxas'    => number_format($taxaUnicaAplicada, 2, ',', '.'),
            'totalGeral'    => number_format($totalGeral, 2, ',', '.'),
            'subtotalFloat' => $subtotal,
            'taxaFloat'     => $taxaUnicaAplicada,
            'totalFloat'    => $totalGeral,
            'pacoteId'      => $pacoteIdParaSalvar,
            'config'        => $this->config
        ]);
    }

    public function contato(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

        if (isset($dados)) {
            if (in_array('', $dados)) {
                Helpers::json('erro', 'Preencha todos os campos!');
            } elseif (!Helpers::validarEmail($dados['email'])) {
                Helpers::json('erro', 'E-mail inválido!');
            } else {
                try {
                    $email = new Email();
                    $view = $this->template->renderizar('emails/contato.html', [
                        'dados' => $dados,
                    ]);

                    $email->criar(
                        'Contato via Site - ' . SITE_NOME,
                        $view,
                        EMAIL_REMETENTE['email'],
                        EMAIL_REMETENTE['nome'],
                        $dados['email'],
                        $dados['nome']
                    );

                    $anexos = $_FILES['anexos'];
                    foreach ($anexos['tmp_name'] as $indice => $anexo) {
                        if (!$anexo == UPLOAD_ERR_OK) {
                            $email->anexar($anexo, $anexos['name'][$indice]);
                        }
                    }
                    $email->enviar(EMAIL_REMETENTE['email'], EMAIL_REMETENTE['nome']);

                    Helpers::json('successo', 'E-mail enviado com sucesso!');
                    Helpers::json('redirecionar', Helpers::url());
                } catch (\PHPMailer\PHPMailer\Exception $ex) {
                    Helpers::json('erro', 'Erro ao enviar e-mail. Tente novamente mais tarde! ' . $ex->getMessage());
                }
            }
        }

        echo $this->template->renderizar('contato.html', [
            'categorias' => $this->categorias(),
        ]);
    }
}
 