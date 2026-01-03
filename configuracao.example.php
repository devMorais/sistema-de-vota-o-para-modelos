<?php

use sistema\Nucleo\Helpers;
use sistema\Suporte\XDebug;

date_default_timezone_set('America/Sao_Paulo');

define('SITE_NOME', 'Votação');
define('SITE_DESCRICAO', 'Modelos - Sistema de votação online');

define('URL_PRODUCAO', 'https://seudominio.com.br/');
define('URL_DESENVOLVIMENTO', 'https://votar.test/');
define('SERVIDORES_LOCAIS', ['localhost', '127.0.0.1', 'votar.test']);
define('URL_INICIAL', 'votar');
define('STATUS_ATIVO', 1);
define('STATUS_INATIVO', 0);

if (Helpers::localhost()) {
    // ========================================
    // LOCALHOST - DESENVOLVIMENTO
    // ========================================
    define('DB_HOST', 'localhost');
    define('DB_PORTA', '3306');
    define('DB_NOME', 'votar');
    define('DB_USUARIO', 'root');
    define('DB_SENHA', 'root');

    define('URL_SITE', '/');
    define('URL_ADMIN', '/admin/');

    // Asaas (Sandbox)
    define('ASAAS_KEY', 'SUA_CHAVE_ASAAS_SANDBOX');
    define('ASAAS_URL', 'https://sandbox.asaas.com/api/v3');
    define('ASAAS_SSL', false);

    // InfinitePay
    define('INFINITEPAY_HANDLE', 'SUA_INFINITE_TAG');
    define('INFINITEPAY_URL', 'https://api.infinitepay.io');
    define('INFINITEPAY_WEBHOOK_URL', 'https://votar.test/webhook/infinitepay');
    define('INFINITEPAY_REDIRECT_URL', null);
    define('INFINITEPAY_SSL', false);
} else {
    // ========================================
    // PRODUÇÃO
    // ========================================
    define('DB_HOST', 'localhost');
    define('DB_PORTA', '3306');
    define('DB_NOME', 'SEU_BANCO');
    define('DB_USUARIO', 'SEU_USUARIO');
    define('DB_SENHA', 'SUA_SENHA');

    define('URL_SITE', '/');
    define('URL_ADMIN', '/admin/');

    // Asaas (Produção)
    define('ASAAS_KEY', 'SUA_CHAVE_ASAAS_PRODUCAO');
    define('ASAAS_URL', 'https://api.asaas.com/api/v3'); // API de produção
    define('ASAAS_SSL', true);

    // InfinitePay (Produção)
    define('INFINITEPAY_HANDLE', 'SUA_INFINITE_TAG');
    define('INFINITEPAY_URL', 'https://api.infinitepay.io');
    define('INFINITEPAY_WEBHOOK_URL', 'https://seudominio.com.br/webhook/infinitepay');
    define('INFINITEPAY_REDIRECT_URL', null);
    define('INFINITEPAY_SSL', true);
}

// ========================================
// EMAIL (Opcional)
// ========================================
define('EMAIL_HOST', 'smtp.hostinger.com');
define('EMAIL_PORTA', '465');
define('EMAIL_USUARIO', '');
define('EMAIL_SENHA', '');
define('EMAIL_REMETENTE', ['email' => EMAIL_USUARIO, 'nome' => SITE_NOME]);
