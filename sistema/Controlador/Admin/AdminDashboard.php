<?php

namespace sistema\Controlador\Admin;

use sistema\Nucleo\Sessao;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PostModelo;
use sistema\Modelo\UsuarioModelo;
use sistema\Modelo\CategoriaModelo;

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

        // 1. Busca todas as categorias ativas para o Ranking
        $categoriasParaRanking = $categoriasModelo->busca("status = 1")->resultado(true);

        $rankingPorCategoria = [];

        // 2. Para cada categoria, busca o TOP 5 candidatas
        if ($categoriasParaRanking) {
            foreach ($categoriasParaRanking as $cat) {
                $candidatas = $postsModelo->busca("categoria_id = {$cat->id} AND status = 1")
                    ->ordem('votos DESC, id DESC') // Desempate por ID se votos forem iguais
                    ->limite(5)
                    ->resultado(true);

                // 3. Calcula o Líquido (Receita - Taxas) para cada candidata
                if ($candidatas) {
                    foreach ($candidatas as $post) {
                        // Query manual para somar as taxas dos pedidos PAGOS deste post
                        // Assumindo que pedidos.pacote_id liga com pacotes_votos.id
                        $queryTaxas = \sistema\Nucleo\Conexao::getInstancia()->query(
                            "SELECT SUM(pv.taxa) as total_taxas 
                             FROM pedidos p 
                             JOIN pacotes_votos pv ON p.pacote_id = pv.id 
                             WHERE p.post_id = {$post->id} AND p.status = 'PAGO'"
                        );
                        $resultadoTaxa = $queryTaxas->fetch();
                        $totalTaxas = $resultadoTaxa->total_taxas ?? 0;

                        // Adiciona propriedade dinâmica ao objeto post para usar na view
                        $post->receita_liquida = $post->receita - $totalTaxas;
                        $post->total_taxas = $totalTaxas;
                    }
                }

                $rankingPorCategoria[] = [
                    'titulo' => $cat->titulo,
                    'candidatas' => $candidatas
                ];
            }
        }

        echo $this->template->renderizar('dashboard.html', [
            // Passamos o novo array estruturado por categoria
            'rankingPorCategoria' => $rankingPorCategoria,

            // Dados dos POSTS (Candidatas)
            'posts' => [
                'posts' => $postsModelo->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $postsModelo->busca(null, 'COUNT(id)', 'id')->total(),
                'ativo' => $postsModelo->busca('status = :s', 's=1 COUNT(status)', 'status')->total(),
                'inativo' => $postsModelo->busca('status = :s', 's=0 COUNT(status)', 'status')->total()
            ],

            // AQUI ESTAVA FALTANDO: Dados das CATEGORIAS para os cards do topo
            'categorias' => [
                'total' => $categoriasModelo->busca()->total(),
                'categoriasAtiva' => $categoriasModelo->busca('status = 1')->total(),
                'categoriasInativa' => $categoriasModelo->busca('status = 0')->total(),
            ],

            // Dados dos USUÁRIOS (Admin)
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
