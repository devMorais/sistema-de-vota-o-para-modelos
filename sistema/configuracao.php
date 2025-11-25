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
define('URL_PRODUCAO', 'https://escrever.devmorais.com.br/');
define('URL_DESENVOLVIMENTO', 'https://votar.test/');
define('SERVIDORES_LOCAIS', ['localhost',  '127.0.0.1', 'votar.test']);

if (Helpers::localhost()) {
    //dados de acesso ao banco de dados em localhost
    define('DB_HOST', 'localhost');
    define('DB_PORTA', '3306');
    define('DB_NOME', 'votar');
    define('DB_USUARIO', 'root');
    define('DB_SENHA', 'root');

    define('URL_SITE', '/');
    define('URL_ADMIN', '/admin/');
    define('ASAAS_KEY', '$aact_hmlg_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OmQ3YzMzODg5LTVjMTgtNDBkZS1iNTE0LTQzY2VjMmNhYzNkYjo6JGFhY2hfYjg2ODhhN2YtZDlkYi00OWFiLWEwMjktOWM3NWJkMjRmNDRj');
    define('ASAAS_URL', 'https://sandbox.asaas.com/api/v3');
} else {
    //dados de acesso ao banco de dados na hospedagem
    define('DB_HOST', 'localhost');
    define('DB_PORTA', '3306');
    define('DB_NOME', '');
    define('DB_USUARIO', '');
    define('DB_SENHA', '');
    define('URL_SITE', '/');
    define('URL_ADMIN', '/admin/');
    // define('ASAAS_KEY', '$aact_prod_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OjE0NWJmOTIxLTkwYTItNDk5ZS1iN2Q0LTJkZGFjMjY4YTYzNDo6JGFhY2hfZjA3YmRiNjgtOTc4NC00NzkwLWIzZTctYzE2ODY0ZjlmMDA0');
    // define('ASAAS_URL', 'https://api.asaas.com/v3');
    define('ASAAS_KEY', '$aact_hmlg_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OmQ3YzMzODg5LTVjMTgtNDBkZS1iNTE0LTQzY2VjMmNhYzNkYjo6JGFhY2hfYjg2ODhhN2YtZDlkYi00OWFiLWEwMjktOWM3NWJkMjRmNDRj');
    define('ASAAS_URL', 'https://sandbox.asaas.com/api/v3');
}

//autenticação do servidor de emails
define('EMAIL_HOST', 'smtp.hostinger.com');
define('EMAIL_PORTA', '465');
define('EMAIL_USUARIO', '');
define('EMAIL_SENHA', '');
define('EMAIL_REMETENTE', ['email' => EMAIL_USUARIO, 'nome' => SITE_NOME]);
