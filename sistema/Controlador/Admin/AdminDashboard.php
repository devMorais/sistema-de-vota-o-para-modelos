<?php

namespace sistema\Controlador\Admin;

use sistema\Nucleo\Sessao;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PostModelo;
use sistema\Modelo\UsuarioModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Modelo\PedidoModelo;

/**
 * Classe AdminDashboard
 *
 * @author Fernando Aguiar
 */
class AdminDashboard extends AdminControlador
{

    /**
     * Home do admin
     * @return void
     */
    public function dashboard(): void
    {
        $postsModelo = new PostModelo();
        $usuarios = new UsuarioModelo();
        $categoriasModelo = new CategoriaModelo();
        $pedidoModelo = new PedidoModelo();

        $categoriasParaRanking = $categoriasModelo->busca("status = 1")->resultado(true);

        $rankingPorCategoria = [];

        if ($categoriasParaRanking) {
            foreach ($categoriasParaRanking as $cat) {
                $candidatas = $postsModelo->busca("categoria_id = {$cat->id} AND status = 1")
                    ->ordem('votos DESC, id DESC')
                    ->limite(5)
                    ->resultado(true);

                if ($candidatas) {
                    foreach ($candidatas as $post) {
                        $financeiro = $pedidoModelo->financeiroPorPost($post->id);
                        $post->valor_bruto = $financeiro->bruto ?? 0;
                        $post->valor_taxas = $financeiro->taxas ?? 0;
                        $post->valor_lucro = $financeiro->lucro ?? 0;
                    }
                }

                $rankingPorCategoria[] = [
                    'titulo' => $cat->titulo,
                    'candidatas' => $candidatas
                ];
            }
        }

        echo $this->template->renderizar('dashboard.html', [
            'rankingPorCategoria' => $rankingPorCategoria,
            'posts' => [
                'posts' => $postsModelo->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $postsModelo->busca(null, 'COUNT(id)', 'id')->total(),
                'ativo' => $postsModelo->busca('status = :s', 's=1 COUNT(status)', 'status')->total(),
                'inativo' => $postsModelo->busca('status = :s', 's=0 COUNT(status)', 'status')->total()
            ],

            'categorias' => [
                'total' => $categoriasModelo->busca()->total(),
                'categoriasAtiva' => $categoriasModelo->busca('status = 1')->total(),
                'categoriasInativa' => $categoriasModelo->busca('status = 0')->total(),
            ],

            'usuarios' => [
                'logins' => $usuarios->busca()->ordem('ultimo_login DESC')->limite(5)->resultado(true),
                'usuarios' => $usuarios->busca('level != 3')->total(),
                'usuariosAtivo' => $usuarios->busca('status = 1 AND level != 3')->total(),
                'usuariosInativo' => $usuarios->busca('status = 0 AND level != 3')->total(),
                'admin' => $usuarios->busca('level = 3')->total(),
                'adminAtivo' => $usuarios->busca('status = 1 AND level = 3')->total(),
                'adminInativo' => $usuarios->busca('status = 0 AND level = 3')->total()
            ],
        ]);
    }

    /**
     * Faz logout do usuário
     * @return void
     */
    public function sair(): void
    {
        $sessao = new Sessao();
        $sessao->limpar('usuarioId');

        $this->mensagem->informa('Você saiu do painel de controle!')->flash();
        Helpers::redirecionar('admin/login');
    }
}
