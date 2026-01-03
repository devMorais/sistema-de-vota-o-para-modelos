-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 03/01/2026 às 17:40
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `landing_page`
--
ALTER TABLE `landing_page`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
