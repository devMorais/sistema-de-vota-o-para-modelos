<?php

use Pecee\SimpleRouter\SimpleRouter;
use sistema\Nucleo\Helpers;

try {
    //namespace dos controladores
    SimpleRouter::setDefaultNamespace('sistema\Controlador');

    SimpleRouter::get(URL_SITE, 'SiteControlador@landing');
    SimpleRouter::get(URL_SITE . 'index.php', 'SiteControlador@landing');
    SimpleRouter::get(URL_SITE . 'votar', 'SiteControlador@index');
    SimpleRouter::get(URL_SITE . 'votar/page/{pagina}', 'SiteControlador@index');

    // Demais rotas do site
    SimpleRouter::get(URL_SITE . 'sobre-nos', 'SiteControlador@sobre');
    SimpleRouter::get(URL_SITE . 'post/{categoria}/{slug}', 'SiteControlador@post');
    SimpleRouter::get(URL_SITE . 'categoria/{slug}/{pagina?}', 'SiteControlador@categoria');

    // Rotas de Ação (POST)
    SimpleRouter::post(URL_SITE . 'buscar', 'SiteControlador@buscar');
    SimpleRouter::post(URL_SITE . 'checkout', 'SiteControlador@checkout');

    // Pagamento e Webhooks
    SimpleRouter::post(URL_SITE . 'pagamento/processar', 'SiteControlador@pagamentoProcessar');
    SimpleRouter::post(URL_SITE . 'pagamento/verificar', 'SiteControlador@pagamentoVerificar');
    SimpleRouter::get(URL_SITE . 'pagamento/{id}', 'SiteControlador@pagamento');
    SimpleRouter::post(URL_SITE . 'webhook/asaas', 'SiteControlador@webhook');

    // Utilitários
    SimpleRouter::get(URL_SITE . '404', 'SiteControlador@erro404');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'contato', 'SiteControlador@contato');


    // =========================================================================
    // ROTAS DE USUÁRIO
    // =========================================================================
    SimpleRouter::match(['get', 'post'], URL_SITE . 'cadastro', 'UsuarioControlador@cadastro');
    SimpleRouter::post(URL_SITE . 'login', 'UsuarioControlador@login');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'usuario/confirmar/email/{token}', 'UsuarioControlador@confirmarEmail');


    // =========================================================================
    // ROTAS SAAS
    // =========================================================================
    SimpleRouter::get(URL_SITE . 'saas', 'SaasControlador@index');
    SimpleRouter::get(URL_SITE . 'saas/sair', 'SaasControlador@sair');


    // =========================================================================
    // ROTAS ADMIN
    // =========================================================================
    SimpleRouter::group(['namespace' => 'Admin'], function () {

        //ADMIN LOGIN
        SimpleRouter::get(URL_ADMIN, 'AdminLogin@index');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'login', 'AdminLogin@login');

        //DASHBOARD
        SimpleRouter::get(URL_ADMIN . 'dashboard', 'AdminDashboard@dashboard');
        SimpleRouter::get(URL_ADMIN . 'sair', 'AdminDashboard@sair');

        //ADMIN LANDINGPAGE
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'landing/editar', 'AdminLanding@editar');

        // ADMIN CONFIGURAÇÕES
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'configuracoes/editar', 'AdminConfiguracoes@editar');

        //ADMIN USUARIOS
        SimpleRouter::get(URL_ADMIN . 'usuarios/listar', 'AdminUsuarios@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'usuarios/cadastrar', 'AdminUsuarios@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'usuarios/editar/{id}', 'AdminUsuarios@editar');
        SimpleRouter::get(URL_ADMIN . 'usuarios/deletar/{id}', 'AdminUsuarios@deletar');
        SimpleRouter::post(URL_ADMIN . 'usuarios/datatable', 'AdminUsuarios@datatable');

        //ADMIN POSTS
        SimpleRouter::get(URL_ADMIN . 'posts/listar', 'AdminPosts@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'posts/cadastrar', 'AdminPosts@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'posts/editar/{id}', 'AdminPosts@editar');
        SimpleRouter::get(URL_ADMIN . 'posts/deletar/{id}', 'AdminPosts@deletar');
        SimpleRouter::post(URL_ADMIN . 'posts/datatable', 'AdminPosts@datatable');

        //ADMIN CATEGORIAS
        SimpleRouter::get(URL_ADMIN . 'categorias/listar', 'AdminCategorias@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'categorias/cadastrar', 'AdminCategorias@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'categorias/editar/{id}', 'AdminCategorias@editar');
        SimpleRouter::get(URL_ADMIN . 'categorias/deletar/{id}', 'AdminCategorias@deletar');

        //ADMIN INGRESSOS (PACOTES)
        SimpleRouter::get(URL_ADMIN . 'ingressos/listar', 'AdminPacotes@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'ingressos/cadastrar', 'AdminPacotes@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'ingressos/editar/{id}', 'AdminPacotes@editar');
        SimpleRouter::get(URL_ADMIN . 'ingressos/deletar/{id}', 'AdminPacotes@deletar');
        SimpleRouter::post(URL_ADMIN . 'ingressos/datatable', 'AdminPacotes@datatable');

        // ADMIN PEDIDOS (PAGAMENTOS)
        SimpleRouter::get(URL_ADMIN . 'pedidos/listar', 'AdminPedidos@listar');
        SimpleRouter::post(URL_ADMIN . 'pedidos/datatable', 'AdminPedidos@datatable');
        SimpleRouter::get(URL_ADMIN . 'pedidos/ver/{id}', 'AdminPedidos@ver');
    });

    SimpleRouter::start();
} catch (Pecee\SimpleRouter\Exceptions\NotFoundHttpException $ex) {
    if (Helpers::localhost()) {
        echo $ex->getMessage();
    } else {
        Helpers::redirecionar('404');
    }
}
