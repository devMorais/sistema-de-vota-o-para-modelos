<?php

use sistema\Nucleo\Helpers;
use sistema\Suporte\XDebug;

//Arquivo de configuração do sistema
//define o fuso horario
date_default_timezone_set('America/Sao_Paulo');

//informações do sistema
define('SITE_NOME', 'Votação');
define('SITE_DESCRICAO', 'Modelos - Sistema de votação online');

//urls do sistema
define('URL_PRODUCAO', 'Inserir URL PRODUÇÂO');
define('URL_DESENVOLVIMENTO', 'Inserir URL Desenvolvimento');
define('SERVIDORES_LOCAIS', ['localhost',  '127.0.0.1', 'votar.test']);

if (Helpers::localhost()) {
    //dados de acesso ao banco de dados em localhost
    define('DB_HOST', 'localhost');
    define('DB_PORTA', '3306');
    define('DB_NOME', '');
    define('DB_USUARIO', '');
    define('DB_SENHA', '');

    define('URL_SITE', '/');
    define('URL_ADMIN', '/admin/');
    define('ASAAS_KEY', '');
    define('ASAAS_URL', '');
} else {
    //dados de acesso ao banco de dados na hospedagem
    define('DB_HOST', 'localhost');
    define('DB_PORTA', '3306');
    define('DB_NOME', '');
    define('DB_USUARIO', '');
    define('DB_SENHA', '');
    define('URL_SITE', '/');
    define('URL_ADMIN', '/admin/');
    define('ASAAS_KEY', '');
    define('ASAAS_URL', '');
}

//autenticação do servidor de emails
define('EMAIL_HOST', 'smtp.hostinger.com');
define('EMAIL_PORTA', '465');
define('EMAIL_USUARIO', '');
define('EMAIL_SENHA', '');
define('EMAIL_REMETENTE', ['email' => EMAIL_USUARIO, 'nome' => SITE_NOME]);
