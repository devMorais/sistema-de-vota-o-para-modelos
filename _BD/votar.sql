-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 03/01/2026 às 17:36
-- Versão do servidor: 9.5.0
-- Versão do PHP: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `votar`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `texto` text,
  `status` int NOT NULL DEFAULT '0',
  `visitas` int NOT NULL DEFAULT '0',
  `cadastrado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL,
  `ultima_visita_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `usuario_id`, `slug`, `titulo`, `texto`, `status`, `visitas`, `cadastrado_em`, `atualizado_em`, `ultima_visita_em`) VALUES
(1, 1, 'miss-2026', '<p>Miss 2026</p>', '<p>Miss 2026</p>', 1, 2, '2025-12-23 18:50:52', NULL, '2025-12-27 17:50:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int NOT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `posts_por_pagina` int DEFAULT '24',
  `ordenacao_posts` varchar(50) DEFAULT 'titulo ASC',
  `gateway_pagamento` enum('ASAAS','INFINITEPAY') NOT NULL DEFAULT 'ASAAS' COMMENT 'Gateway de pagamento ativo',
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `whatsapp`, `posts_por_pagina`, `ordenacao_posts`, `gateway_pagamento`, `atualizado_em`) VALUES
(1, '61983411859', 24, 'titulo ASC', 'INFINITEPAY', '2025-12-22 00:11:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `landing_page`
--

CREATE TABLE `landing_page` (
  `id` int NOT NULL,
  `texto_topo` varchar(255) DEFAULT NULL,
  `titulo_principal` varchar(255) DEFAULT NULL,
  `subtitulo` text,
  `texto_botao` varchar(100) DEFAULT NULL,
  `url_botao` varchar(255) DEFAULT NULL,
  `imagem_fundo` varchar(255) DEFAULT NULL,
  `status` int DEFAULT '1',
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `landing_page`
--

INSERT INTO `landing_page` (`id`, `texto_topo`, `titulo_principal`, `subtitulo`, `texto_botao`, `url_botao`, `imagem_fundo`, `status`, `atualizado_em`) VALUES
(1, 'GRANDE FINAL EM BREVE', 'Vote Pela Sua Miss', 'Junte-se a nós nesta jornada de elegância e propósito. Seu voto é decisivo para escolher a representante oficial de Minas Gerais', 'CONHECER CANDIDATAS', 'votar', 'landing-bg-694b5118df4c3.webp', 1, '2025-12-23 23:34:03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_pagamento`
--

CREATE TABLE `logs_pagamento` (
  `id` int NOT NULL,
  `pedido_id` int DEFAULT NULL COMMENT 'ID do pedido relacionado',
  `tipo_pagamento` enum('PIX','CARTAO','BOLETO') NOT NULL COMMENT 'Tipo de pagamento',
  `etapa` varchar(50) NOT NULL COMMENT 'Etapa do processo',
  `status` enum('SUCESSO','ERRO','AVISO') NOT NULL COMMENT 'Status da operação',
  `mensagem` text COMMENT 'Mensagem principal do log',
  `codigo_erro` varchar(50) DEFAULT NULL COMMENT 'Código do erro retornado pelo Asaas',
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Dados enviados para o Asaas',
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Resposta completa do Asaas',
  `asaas_id` varchar(100) DEFAULT NULL COMMENT 'ID da transação no Asaas',
  `endpoint` varchar(255) DEFAULT NULL COMMENT 'URL do endpoint chamado',
  `http_code` int DEFAULT NULL COMMENT 'Código HTTP da resposta',
  `tempo_resposta` int DEFAULT NULL COMMENT 'Tempo de resposta em ms',
  `ip_usuario` varchar(45) DEFAULT NULL COMMENT 'IP do usuário',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'User Agent do navegador',
  `cadastrado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Despejando dados para a tabela `logs_pagamento`
--

INSERT INTO `logs_pagamento` (`id`, `pedido_id`, `tipo_pagamento`, `etapa`, `status`, `mensagem`, `codigo_erro`, `request_data`, `response_data`, `asaas_id`, `endpoint`, `http_code`, `tempo_resposta`, `ip_usuario`, `user_agent`, `cadastrado_em`) VALUES
(1, 1, 'PIX', 'gerar_pix', 'SUCESSO', NULL, NULL, NULL, '{\"id\": \"pay_dsmd6e11g9n95qsv\", \"status\": \"PENDING\"}', 'pay_dsmd6e11g9n95qsv', 'https://sandbox.asaas.com/api/v3/payments/pay_dsmd6e11g9n95qsv/pixQrCode', 200, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 18:53:56'),
(2, 2, 'CARTAO', 'processar_pagamento', 'SUCESSO', NULL, NULL, NULL, '{\"id\": \"pay_p2gxyd45z66xu8h9\", \"status\": \"CONFIRMED\"}', 'pay_p2gxyd45z66xu8h9', 'https://sandbox.asaas.com/api/v3/payments/pay_p2gxyd45z66xu8h9/payWithCreditCard', 200, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 18:59:24'),
(3, 3, 'PIX', 'gerar_pix', 'SUCESSO', NULL, NULL, NULL, '{\"id\": \"pay_0x45wh4qs2xt8265\", \"status\": \"PENDING\"}', 'pay_0x45wh4qs2xt8265', 'https://sandbox.asaas.com/api/v3/payments/pay_0x45wh4qs2xt8265/pixQrCode', 200, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 19:19:25'),
(4, 4, 'CARTAO', 'processar_pagamento', 'SUCESSO', NULL, NULL, NULL, '{\"id\": \"pay_rgr7nrqs8veaj618\", \"status\": \"CONFIRMED\"}', 'pay_rgr7nrqs8veaj618', 'https://sandbox.asaas.com/api/v3/payments/pay_rgr7nrqs8veaj618/payWithCreditCard', 200, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 19:23:26'),
(5, 5, 'CARTAO', 'validacao', 'ERRO', NULL, 'card_declined', NULL, NULL, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 19:27:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_pagamento_infinitepay`
--

CREATE TABLE `logs_pagamento_infinitepay` (
  `id` int NOT NULL,
  `pedido_id` int DEFAULT NULL COMMENT 'ID do pedido relacionado',
  `etapa` varchar(50) NOT NULL COMMENT 'Etapa do processo (criar_link, verificar_pagamento, webhook)',
  `status` enum('SUCESSO','ERRO','AVISO') NOT NULL COMMENT 'Status da operação',
  `mensagem` text COMMENT 'Mensagem principal do log',
  `codigo_erro` varchar(50) DEFAULT NULL COMMENT 'Código do erro retornado',
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Dados enviados para InfinitePay',
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Resposta completa da InfinitePay',
  `infinitepay_slug` varchar(100) DEFAULT NULL COMMENT 'Slug da fatura na InfinitePay',
  `infinitepay_link` varchar(500) DEFAULT NULL COMMENT 'Link de pagamento gerado',
  `transaction_nsu` varchar(100) DEFAULT NULL COMMENT 'NSU da transação',
  `order_nsu` varchar(100) DEFAULT NULL COMMENT 'NSU do pedido',
  `endpoint` varchar(255) DEFAULT NULL COMMENT 'URL do endpoint chamado',
  `http_code` int DEFAULT NULL COMMENT 'Código HTTP da resposta',
  `tempo_resposta` int DEFAULT NULL COMMENT 'Tempo de resposta em ms',
  `ip_usuario` varchar(45) DEFAULT NULL COMMENT 'IP do usuário',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'User Agent do navegador',
  `cadastrado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Despejando dados para a tabela `logs_pagamento_infinitepay`
--

INSERT INTO `logs_pagamento_infinitepay` (`id`, `pedido_id`, `etapa`, `status`, `mensagem`, `codigo_erro`, `request_data`, `response_data`, `infinitepay_slug`, `infinitepay_link`, `transaction_nsu`, `order_nsu`, `endpoint`, `http_code`, `tempo_resposta`, `ip_usuario`, `user_agent`, `cadastrado_em`) VALUES
(1, 10, 'criar_link', 'ERRO', NULL, NULL, '{\"handle\":\"fe_guiar92\",\"items\":[{\"quantity\":1,\"price\":100,\"description\":\"1 votos - <p>Claudia<\\/p>\"}],\"order_nsu\":\"pedido-10\",\"webhook_url\":\"\\/webhook\\/infinitepay\",\"redirect_url\":\"\\/pagamento\\/sucesso\"}', '{\"success\":false,\"message\":\"Invalid checkout link params\",\"errors\":{\"redirect_url\":[\"is not a valid URI\"],\"webhook_url\":[\"is not a valid URI\"]}}', NULL, NULL, NULL, NULL, 'https://api.infinitepay.io/invoices/public/checkout/links', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 10:14:21'),
(2, 11, 'criar_link', 'ERRO', NULL, NULL, '{\"handle\":\"fe_guiar92\",\"items\":[{\"quantity\":1,\"price\":100,\"description\":\"1 votos - <p>Claudia<\\/p>\"}],\"order_nsu\":\"pedido-11\",\"webhook_url\":\"https:\\/\\/votar.test\\/webhook\\/infinitepay\",\"redirect_url\":\"https:\\/\\/votar.test\\/pedido\\/pagamento\\/\"}', '{\"url\":\"https:\\/\\/checkout.infinitepay.io\\/fe_guiar92?lenc=G9QAYByHsWN6ZMnBV09dVDo50oMc7rcTBRa0wDJr6uZF3mW_sEn6s704oATaFkUaY8Chj56t-jo5AEg5Cr-CIOAIIw20wtxkWBqegScYtXuBUNdHi7a_KipWJSB6f9c7QMM-5o2ZiYt1OUcAbRNS1cOIjxztI-H20upVzBdZv9HY0L7Jfv3502vLjjdw_LZD3I4qXupadCV6RiIqyzSFMjggo7gI4iK2ecVn.v1.8ba81e9296718bd4\"}', NULL, NULL, NULL, NULL, 'https://api.infinitepay.io/invoices/public/checkout/links', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 10:24:56'),
(3, 12, 'criar_link', 'SUCESSO', NULL, NULL, NULL, '{\"url\":\"https:\\/\\/checkout.infinitepay.io\\/fe_guiar92?lenc=G9QAYByFTVl59rRykGvJZd9MTjnSQJI4kzw4fAf71M2LvMt-YZP0Z3txQAm0LYo0xoBzmDjGp3VyAJByFH4FQcARRhpohbnJsDQ8A08wav8Coa6PFm1_VVSsSkC0_q53gIZ9zBtTFRfrco4A2iakqocRHznaR8LjpelVzBdZ32hsaN9kv_786XVlxxu4ftshbkcVL3UtuhI9IxGVZZpCGRyQUVwEcRHbvOIz.v1.b812b96da562be7a\"}', NULL, 'https://checkout.infinitepay.io/fe_guiar92?lenc=G9QAYByFTVl59rRykGvJZd9MTjnSQJI4kzw4fAf71M2LvMt-YZP0Z3txQAm0LYo0xoBzmDjGp3VyAJByFH4FQcARRhpohbnJsDQ8A08wav8Coa6PFm1_VVSsSkC0_q53gIZ9zBtTFRfrco4A2iakqocRHznaR8LjpelVzBdZ32hsaN9kv_786XVlxxu4ftshbkcVL3UtuhI9IxGVZZpCGRyQUVwEcRHbvOIz.v1.b812b96da562be7a', NULL, 'pedido-12', 'https://api.infinitepay.io/invoices/public/checkout/links', 200, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 10:29:00'),
(4, 13, 'criar_link', 'SUCESSO', NULL, NULL, NULL, '{\"url\":\"https:\\/\\/checkout.infinitepay.io\\/fe_guiar92?lenc=GwQBACwO7OYdi7BF7ajBUbECLzRCks6e4TFENXXzImvbtoz-G2NhF1DgY1mUpRMknRzpgQ96g0tbLfSS6pagJjJdFuYz13IHAK_08PTbQ7eCa0sCiYOEEw20tKDcdKUJohic5S8tMzU9W5wHsFnntpyg9wTzKqNly3a6wx2Q_UDwfIlothYyAutIXw2AoEqcclrXzqYBGaTv33UicYh7_hVYU8i5Zsuznz8p6wDyxPvJ_6IqgHLCmgouyk8uqjvPIAu5raRYYeiiJNpiRs4ODDuwzUiuAQ.v1.36ec3e195607c83c\"}', NULL, 'https://checkout.infinitepay.io/fe_guiar92?lenc=GwQBACwO7OYdi7BF7ajBUbECLzRCks6e4TFENXXzImvbtoz-G2NhF1DgY1mUpRMknRzpgQ96g0tbLfSS6pagJjJdFuYz13IHAK_08PTbQ7eCa0sCiYOEEw20tKDcdKUJohic5S8tMzU9W5wHsFnntpyg9wTzKqNly3a6wx2Q_UDwfIlothYyAutIXw2AoEqcclrXzqYBGaTv33UicYh7_hVYU8i5Zsuznz8p6wDyxPvJ_6IqgHLCmgouyk8uqjvPIAu5raRYYeiiJNpiRs4ODDuwzUiuAQ.v1.36ec3e195607c83c', NULL, 'pedido-13', 'https://api.infinitepay.io/invoices/public/checkout/links', 200, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 10:34:02'),
(5, 14, 'criar_link', 'SUCESSO', NULL, NULL, NULL, '{\"url\":\"https:\\/\\/checkout.infinitepay.io\\/fe_guiar92?lenc=G_wAoJwJdqyVoXn2HZaohnfzf643srVV8gj-IhL3BjTwvEiQE75uCWoi02Flc3UtdwDwSg9Pvz38bcG1JYHEQcCRBtyy9GolY4wVp_yXMcxpTRS_29es_9Cs4L2-vJpweWWG9RfpIM8PZGsvRU7o5BQn7mL0qxfAVo3fOmnLTC9AG-vdudHDh9vwX8B6onVC8151cgBtlfzxL6JqEM2K1hFMUZJMYSDXrMmZW1lBnscAiTT3yISZF2ahhYU5Ag.v1.4689dcf0650256a5\"}', NULL, 'https://checkout.infinitepay.io/fe_guiar92?lenc=G_wAoJwJdqyVoXn2HZaohnfzf643srVV8gj-IhL3BjTwvEiQE75uCWoi02Flc3UtdwDwSg9Pvz38bcG1JYHEQcCRBtyy9GolY4wVp_yXMcxpTRS_29es_9Cs4L2-vJpweWWG9RfpIM8PZGsvRU7o5BQn7mL0qxfAVo3fOmnLTC9AG-vdudHDh9vwX8B6onVC8151cgBtlfzxL6JqEM2K1hFMUZJMYSDXrMmZW1lBnscAiTT3yISZF2ahhYU5Ag.v1.4689dcf0650256a5', NULL, 'pedido-14', 'https://api.infinitepay.io/invoices/public/checkout/links', 200, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 12:07:21'),
(6, 15, 'criar_link', 'SUCESSO', NULL, NULL, NULL, '{\"url\":\"https:\\/\\/checkout.infinitepay.io\\/fe_guiar92?lenc=G_4AwCwSzDNMp9pJsuCcPlKiGg5N3bzInrJfWUbfGBO7gAKfAyThuiWoiUzUZFnYnNkOmAej3Q32d4PfXRJIHAQcaQJZjOnVSscYK075zxGMps_j3-lr1sxtOQH3-PIqg8JEttMXaCzNiwXnS0SztRDSUYu-Ghi0RGFopHUtucANpHdtY4eHLvg3QJ6ca7a8J40cWJ54P_9DVImhnCAZgaOKxFHz-CzIQky3kmKFoYtQ74jtwLAD28BIrgE.v1.a2b045a65446f70c\"}', NULL, 'https://checkout.infinitepay.io/fe_guiar92?lenc=G_4AwCwSzDNMp9pJsuCcPlKiGg5N3bzInrJfWUbfGBO7gAKfAyThuiWoiUzUZFnYnNkOmAej3Q32d4PfXRJIHAQcaQJZjOnVSscYK075zxGMps_j3-lr1sxtOQH3-PIqg8JEttMXaCzNiwXnS0SztRDSUYu-Ghi0RGFopHUtucANpHdtY4eHLvg3QJ6ca7a8J40cWJ54P_9DVImhnCAZgaOKxFHz-CzIQky3kmKFoYtQ74jtwLAD28BIrgE.v1.a2b045a65446f70c', NULL, 'pedido-15', 'https://api.infinitepay.io/invoices/public/checkout/links', 200, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 12:21:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacotes_votos`
--

CREATE TABLE `pacotes_votos` (
  `id` int NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `quantidade` int NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `taxa` decimal(10,2) NOT NULL,
  `status` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `pacotes_votos`
--

INSERT INTO `pacotes_votos` (`id`, `titulo`, `quantidade`, `valor`, `taxa`, `status`) VALUES
(1, '1 Voto', 1, 1.00, 0.00, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int NOT NULL,
  `post_id` int NOT NULL,
  `pacote_id` int DEFAULT NULL,
  `total_votos` int NOT NULL,
  `valor_subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_taxa` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_total` decimal(10,2) NOT NULL,
  `cliente_nome` varchar(255) NOT NULL,
  `cliente_cpf` varchar(20) NOT NULL,
  `cliente_email` varchar(255) DEFAULT NULL,
  `cliente_telefone` varchar(20) DEFAULT NULL,
  `asaas_id` varchar(100) DEFAULT NULL,
  `gateway_usado` enum('ASAAS','INFINITEPAY') DEFAULT 'ASAAS' COMMENT 'Gateway utilizado neste pedido',
  `infinitepay_slug` varchar(100) DEFAULT NULL COMMENT 'Slug da fatura na InfinitePay',
  `infinitepay_link` varchar(500) DEFAULT NULL COMMENT 'Link de pagamento InfinitePay',
  `infinitepay_order_nsu` varchar(100) DEFAULT NULL COMMENT 'Order NSU da InfinitePay',
  `infinitepay_transaction_nsu` varchar(100) DEFAULT NULL COMMENT 'Transaction NSU da InfinitePay',
  `infinitepay_receipt_url` varchar(500) DEFAULT NULL COMMENT 'URL do comprovante InfinitePay',
  `metodo_pagamento` enum('PIX','CARTAO','CREDIT_CARD') DEFAULT NULL COMMENT 'Método de pagamento utilizado',
  `pix_qrcode` text,
  `pix_img` text,
  `status` varchar(20) DEFAULT 'AGUARDANDO',
  `cadastrado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pago_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `pedidos`
--

INSERT INTO `pedidos` (`id`, `post_id`, `pacote_id`, `total_votos`, `valor_subtotal`, `valor_taxa`, `valor_total`, `cliente_nome`, `cliente_cpf`, `cliente_email`, `cliente_telefone`, `asaas_id`, `gateway_usado`, `infinitepay_slug`, `infinitepay_link`, `infinitepay_order_nsu`, `infinitepay_transaction_nsu`, `infinitepay_receipt_url`, `metodo_pagamento`, `pix_qrcode`, `pix_img`, `status`, `cadastrado_em`, `pago_em`) VALUES
(1, 1, 1, 20, 20.00, 3.50, 23.50, 'Fernando Aguiar da Costa Morais', '03595350111', '', '', 'pay_dsmd6e11g9n95qsv', 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, '00020101021226820014br.gov.bcb.pix2560pix-h.asaas.com/qr/cobv/9247d508-bcae-4f87-8404-072ad8fa25b75204000053039865802BR592355225041 FERNANDO AGUIA6010Planaltina61087375420362070503***6304B131', 'iVBORw0KGgoAAAANSUhEUgAAAcIAAAHCAQAAAABUY/ToAAADDklEQVR4Xu2UTc7bMBBDvcv9b9RjeZeGj5RsFChcfJtOAE78I3H4mMUoOd4/rF/Hn8q/VsmnKvlUJZ+q5FOVfKr/Rp4H9XqfLzYv3p/twR7dr+0sOZnUKrqWfhOx4l50iYy/5GAy6jID25yXtTtfcjwJ+1p7cCVlSbvkF5Fa7JYwV7ayl/wO8r3Uj74Z+yUn8HKWHE3GeT5/dmbJv3wGkLc69fP2rLmTt4WrSo4lmS9Tjs0Lz/zUrcs/cDtKjiY3YGJPnMaKleqIkl9AGnPP43cOkLvQHIiSo0l8FkzmRkyqW16pSo4l4/EVRBZRt4xAqCXHk/KYCZ7bHZcj2JWcTVJO0GLlyJXgnQhacjApK69FY5cvKZivbcnh5Kcl/DLyuVHeX5El55K76QxiWDtQNPkvnwTvSo4lpR6cgfAECCDIYYtBLzmZ1AbdgAgwMiyTDU5Oyclk2ocCwq+gRWjnKISSk8nlidmWk6nz8l7P+BRQcjTpoud2BNsJjUvRJYeTcqAsp55rF0MSS04ntWfaatHU2vNPC3GfiJKjSRHa3ZyJWf7ER2Fbci6Jsv+FRQnf/915WuEbSk4m38zYdNho+D16NG62JSeTMrsvdM8ckNQI6UkpOZY83RZqx145h1g+WevbSg4mfaVhQiX1Dsphc8nJ5CE7N37OAju5d8aKKDmcXKgXy3DLY0kIr5LDyY8g8PTsiVhWtUzcToOQknNJXEhSV/uSlMP/t3NBS44lWcH4KNgtI26OwjoLNpecTMp9XFPWWogdnAfOgST71Co5lhRhh120Vppfe49PiSXHkm86nrPWy8voVygnARKt5GRSHp7htHKSqGTnGyyUnEsuMFaapybP8GlK20LJ2eQqCHX0ejN2pbFxrBWq5Fhyz1geAjT9KwXqchguOZfUiueC7HNmrE7QuuR4Un63Nu+S58yRiEJ2yW8gRdE0wGUf5UR3S34Die8eYNebM+KIKCUnk5aMAPBUI1m3VuJKziUPKrozZONiq1XOiLNKDiZ/VCWfquRTlXyqkk9V8qm+jPwN6Q5oxPBhcjAAAAAASUVORK5CYII=', 'PAGO', '2025-12-23 18:53:56', '2025-12-23 18:54:19'),
(2, 1, 1, 280, 280.00, 3.50, 283.50, 'TESTE APROVADO', '03595350111', 'farguiarn3@gmail.com.br', '61983411859', 'pay_p2gxyd45z66xu8h9', 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PAGO', '2025-12-23 18:59:24', '2025-12-23 18:59:26'),
(3, 1, 1, 40, 40.00, 3.50, 43.50, 'Fernando Aguiar da Costa Morais', '03595350111', '', '', 'pay_0x45wh4qs2xt8265', 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, '00020101021226820014br.gov.bcb.pix2560pix-h.asaas.com/qr/cobv/417e200d-a357-4e82-b2b0-865375c087095204000053039865802BR592355225041 FERNANDO AGUIA6010Planaltina61087375420362070503***6304A210', 'iVBORw0KGgoAAAANSUhEUgAAAcIAAAHCAQAAAABUY/ToAAADFUlEQVR4Xu2VQY7cQAwDffP/f5Rn+eYMi1K3EyBwkEs0AGWPuyWxuAcJ2OP+x/hx/F752wj5FiHfIuRbhHyLkG/x38jrIM7P7dT9vE71Ls7rU/+cPrYy5GCyha7RLgtjmKjbypCzyV1l5NAW1+Hakw85ntSJpLQG+yog5DeRurUGzFHp9gw5nbyr6kYT6F2msZUhR5OlvN6f5RnyD88AcgWroLJmza/8VmFHyLEk8/WUpWq9Zy4rUNnWE3I0KdhIyyvQta2qtgg5nFSXJgK1OWv0rjdNNeRokrw+PkDOzdLG0ZqQo0lVpPeU0ZqRhwvU0KIJOZlULl5toMYp0ixVmYccTUokiJovl15hpccXRxuFHEtS64o4k+ge96dpyOEk8FF9bQX5pnSsvQg5mhSL9HnbeI1e360LOZhEdWjOV/MiwOA52tFGIeeSoHqFdFYe5mVm3LqQo0lJVNRhE9BNYIQVhZCTSUGHtqBOJLawIbm+9sIg5FhSYVrNtQCroi/Psg45npRaFbVLT0VngziGnE5+Mr181NBhHnKtw9qIkLPJyoS1shPrb8WqkIacS2ruhDeBRfA2KO1v7Yf+UsjJpLRKLqi1FtZWitw/NiLkXFL9Kut2QVAChuslkJP+WMjJZKeXOGeaePms/eh7yNkk+cHr/tPoAVaLbsi55B5+efB9jN3mZRFyOkm3SgV7LzjqanMdIYeT+rd6LLxulrImZGVdYMjBJC2GLpkY1Ccqq22uC5WQk8kqg3WvrVyWyIi9Qo4m0VggxshNsA9YY3x4PUKOJkv3uUhFS3pUHCvHSdqQY8mbDlQZIPHoDcnQln5DjialkQC6friJkY7PstE95FiyQM3dF8MELkBVOWtdQo4lOzTkJfTY5abRP2x/2aGQA8k1Y3WkUFI+/pREiTUhJ5O6mWnAMIDLqrsCHHI0+VEYoNyo6fLqGqqQ30Fq6oXf2gxyJYQd3Q35BSTP1vsOgyf3qoScTLpE32oslkdlLEHbhZxLHoRIihLqzluW5WB1yNHkP0XItwj5FiHfIuRbhHyLLyN/AoeYKQTuKQjgAAAAAElFTkSuQmCC', 'PAGO', '2025-12-23 19:19:25', '2025-12-23 19:19:39'),
(4, 1, 1, 40, 40.00, 3.50, 43.50, 'Fernando Aguiar da Costa Morais', '03595350111', 'farguiarn3@gmail.com.br', '61983411859', 'pay_rgr7nrqs8veaj618', 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PAGO', '2025-12-23 19:23:26', '2025-12-23 19:23:28'),
(5, 1, 1, 220, 220.00, 3.50, 223.50, 'Fernando Aguiar da Costa Morais', '03595350111', 'farguiarn3@gmail.com.br', '61983411859', NULL, 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ERRO', '2025-12-23 19:27:12', NULL),
(6, 1, 1, 1, 1.00, 0.00, 1.00, 'Fernando', '03595350111', NULL, NULL, NULL, 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'AGUARDANDO', '2026-01-03 09:54:48', NULL),
(7, 1, 1, 1, 1.00, 0.00, 1.00, 'Fernando', '03595350111', NULL, NULL, NULL, 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'AGUARDANDO', '2026-01-03 09:56:47', NULL),
(8, 1, 1, 1, 1.00, 0.00, 1.00, 'Fernando', '03595350111', NULL, NULL, NULL, 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'AGUARDANDO', '2026-01-03 09:57:29', NULL),
(9, 1, 1, 1, 1.00, 0.00, 1.00, 'Fernando', '03595350111', NULL, NULL, NULL, 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'AGUARDANDO', '2026-01-03 10:10:29', NULL),
(10, 1, 1, 1, 1.00, 0.00, 1.00, 'Fernando', '03595350111', NULL, NULL, NULL, 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ERRO', '2026-01-03 10:14:20', NULL),
(11, 1, 1, 1, 1.00, 0.00, 1.00, 'Fernando Aguiar da Costa Morais', '03595350111', NULL, NULL, NULL, 'ASAAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ERRO', '2026-01-03 10:24:56', NULL),
(12, 1, 1, 1, 1.00, 0.00, 1.00, 'Fernando Aguiar da Costa Morais', '03595350111', NULL, NULL, NULL, 'INFINITEPAY', NULL, 'https://checkout.infinitepay.io/fe_guiar92?lenc=G9QAYByFTVl59rRykGvJZd9MTjnSQJI4kzw4fAf71M2LvMt-YZP0Z3txQAm0LYo0xoBzmDjGp3VyAJByFH4FQcARRhpohbnJsDQ8A08wav8Coa6PFm1_VVSsSkC0_q53gIZ9zBtTFRfrco4A2iakqocRHznaR8LjpelVzBdZ32hsaN9kv_786XVlxxu4ftshbkcVL3UtuhI9IxGVZZpCGRyQUVwEcRHbvOIz.v1.b812b96da562be7a', 'pedido-12', NULL, NULL, NULL, NULL, NULL, 'AGUARDANDO', '2026-01-03 10:29:00', NULL),
(13, 1, 1, 2, 2.00, 0.00, 2.00, 'Fernando Aguiar da Costa Morais', '03595350111', NULL, NULL, NULL, 'INFINITEPAY', NULL, 'https://checkout.infinitepay.io/fe_guiar92?lenc=GwQBACwO7OYdi7BF7ajBUbECLzRCks6e4TFENXXzImvbtoz-G2NhF1DgY1mUpRMknRzpgQ96g0tbLfSS6pagJjJdFuYz13IHAK_08PTbQ7eCa0sCiYOEEw20tKDcdKUJohic5S8tMzU9W5wHsFnntpyg9wTzKqNly3a6wx2Q_UDwfIlothYyAutIXw2AoEqcclrXzqYBGaTv33UicYh7_hVYU8i5Zsuznz8p6wDyxPvJ_6IqgHLCmgouyk8uqjvPIAu5raRYYeiiJNpiRs4ODDuwzUiuAQ.v1.36ec3e195607c83c', 'pedido-13', NULL, NULL, NULL, NULL, NULL, 'AGUARDANDO', '2026-01-03 10:34:01', NULL),
(14, 1, 1, 1, 1.00, 0.00, 1.00, 'Fernando Aguiar da Costa Morais', '03595350111', NULL, NULL, NULL, 'INFINITEPAY', NULL, 'https://checkout.infinitepay.io/fe_guiar92?lenc=G_wAoJwJdqyVoXn2HZaohnfzf643srVV8gj-IhL3BjTwvEiQE75uCWoi02Flc3UtdwDwSg9Pvz38bcG1JYHEQcCRBtyy9GolY4wVp_yXMcxpTRS_29es_9Cs4L2-vJpweWWG9RfpIM8PZGsvRU7o5BQn7mL0qxfAVo3fOmnLTC9AG-vdudHDh9vwX8B6onVC8151cgBtlfzxL6JqEM2K1hFMUZJMYSDXrMmZW1lBnscAiTT3yISZF2ahhYU5Ag.v1.4689dcf0650256a5', 'pedido-14', NULL, NULL, NULL, NULL, NULL, 'AGUARDANDO', '2026-01-03 12:07:21', NULL),
(15, 1, 1, 1, 1.00, 0.00, 1.00, 'Fernando Aguiar da Costa Morais', '03595350111', NULL, NULL, NULL, 'INFINITEPAY', NULL, 'https://checkout.infinitepay.io/fe_guiar92?lenc=G_4AwCwSzDNMp9pJsuCcPlKiGg5N3bzInrJfWUbfGBO7gAKfAyThuiWoiUzUZFnYnNkOmAej3Q32d4PfXRJIHAQcaQJZjOnVSscYK075zxGMps_j3-lr1sxtOQH3-PIqg8JEttMXaCzNiwXnS0SztRDSUYu-Ghi0RGFopHUtucANpHdtY4eHLvg3QJ6ca7a8J40cWJ54P_9DVImhnCAZgaOKxFHz-CzIQky3kmKFoYtQ74jtwLAD28BIrgE.v1.a2b045a65446f70c', 'pedido-15', NULL, NULL, NULL, NULL, NULL, 'AGUARDANDO', '2026-01-03 12:21:24', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `posts`
--

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `categoria_id` int NOT NULL,
  `capa` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `texto` longtext,
  `status` int NOT NULL DEFAULT '0',
  `visitas` int NOT NULL DEFAULT '0',
  `votos` int NOT NULL DEFAULT '0',
  `receita` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cadastrado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL,
  `ultima_visita_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `posts`
--

INSERT INTO `posts` (`id`, `usuario_id`, `categoria_id`, `capa`, `slug`, `titulo`, `texto`, `status`, `visitas`, `votos`, `receita`, `cadastrado_em`, `atualizado_em`, `ultima_visita_em`) VALUES
(1, 1, 1, 'claudia-694b0edd19276.webp', 'claudia', '<p>Claudia</p>', '<p>Claudia</p>', 1, 22, 380, 394.00, '2025-12-23 18:51:27', NULL, '2026-01-03 12:20:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `level` int NOT NULL DEFAULT '1',
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `ultimo_login` datetime DEFAULT NULL,
  `cadastrado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `level`, `nome`, `email`, `senha`, `status`, `ultimo_login`, `cadastrado_em`, `atualizado_em`) VALUES
(1, 3, 'Administrador', 'missecominasgerais@gmail.com', '$2y$10$ngdGZJBu5faoMzxfdvTzG.ppuDZ0DeE1MXye8FX2bHywpBbNFQT4q', 1, '2026-01-03 00:16:27', '2025-11-23 02:28:38', '2025-11-26 19:57:27');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`) USING BTREE;

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `landing_page`
--
ALTER TABLE `landing_page`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `logs_pagamento`
--
ALTER TABLE `logs_pagamento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedido` (`pedido_id`),
  ADD KEY `idx_tipo_status` (`tipo_pagamento`,`status`),
  ADD KEY `idx_etapa` (`etapa`),
  ADD KEY `idx_data` (`cadastrado_em`),
  ADD KEY `idx_asaas` (`asaas_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tipo_etapa` (`tipo_pagamento`,`etapa`),
  ADD KEY `idx_erro` (`status`,`codigo_erro`);

--
-- Índices de tabela `logs_pagamento_infinitepay`
--
ALTER TABLE `logs_pagamento_infinitepay`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedido` (`pedido_id`),
  ADD KEY `idx_etapa` (`etapa`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_data` (`cadastrado_em`),
  ADD KEY `idx_slug` (`infinitepay_slug`),
  ADD KEY `idx_transaction_nsu` (`transaction_nsu`),
  ADD KEY `idx_order_nsu` (`order_nsu`);

--
-- Índices de tabela `pacotes_votos`
--
ALTER TABLE `pacotes_votos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post` (`post_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_gateway` (`gateway_usado`),
  ADD KEY `idx_infinitepay_slug` (`infinitepay_slug`),
  ADD KEY `idx_infinitepay_order_nsu` (`infinitepay_order_nsu`),
  ADD KEY `idx_infinitepay_transaction_nsu` (`infinitepay_transaction_nsu`);

--
-- Índices de tabela `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `categoria` (`categoria_id`),
  ADD KEY `idx_visitas` (`visitas`),
  ADD KEY `id` (`id`),
  ADD KEY `idx_slug` (`slug`) USING BTREE,
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `landing_page`
--
ALTER TABLE `landing_page`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `logs_pagamento`
--
ALTER TABLE `logs_pagamento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_pagamento_infinitepay`
--
ALTER TABLE `logs_pagamento_infinitepay`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pacotes_votos`
--
ALTER TABLE `pacotes_votos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `logs_pagamento`
--
ALTER TABLE `logs_pagamento`
  ADD CONSTRAINT `fk_logs_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `logs_pagamento_infinitepay`
--
ALTER TABLE `logs_pagamento_infinitepay`
  ADD CONSTRAINT `fk_logs_infinitepay_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
