-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 22/12/2025 às 03:41
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
(1, 1, 'miss-universo-minas-gerais-2026', '<p>Miss Universo Minas Gerais 2026</p>', '<p>O maior concurso de beleza do estado, reunindo representantes de todas as regiões em busca da coroa soberana.</p>', 1, 1, '2025-12-21 22:54:07', NULL, '2025-12-21 22:54:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int NOT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `posts_por_pagina` int DEFAULT '24',
  `ordenacao_posts` varchar(50) DEFAULT 'titulo ASC',
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `whatsapp`, `posts_por_pagina`, `ordenacao_posts`, `atualizado_em`) VALUES
(1, '61983411859', 24, 'titulo ASC', '2025-12-22 00:11:46');

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
(1, 'GRANDE FINAL EM BREVE', 'Vote Pela Sua Miss', 'Junte-se a nós nesta jornada de elegância e propósito. Seu voto é decisivo para escolher a representante oficial de Minas Gerais', 'CONHECER CANDIDATAS', 'votar', 'landing-bg-6948a3bdc49bf.webp', 1, '2025-12-21 22:52:54');

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
(1, 'Pacote (10 Votos)', 10, 20.00, 3.50, 1);

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
  `pix_qrcode` text,
  `pix_img` text,
  `status` varchar(20) DEFAULT 'AGUARDANDO',
  `cadastrado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pago_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `pedidos`
--

INSERT INTO `pedidos` (`id`, `post_id`, `pacote_id`, `total_votos`, `valor_subtotal`, `valor_taxa`, `valor_total`, `cliente_nome`, `cliente_cpf`, `cliente_email`, `cliente_telefone`, `asaas_id`, `pix_qrcode`, `pix_img`, `status`, `cadastrado_em`, `pago_em`) VALUES
(1, 1, 1, 10, 20.00, 3.50, 23.50, 'Fernando teste', '03595350111', NULL, NULL, 'pay_0w2ri5oufxfam39f', '00020101021226820014br.gov.bcb.pix2560pix-h.asaas.com/qr/cobv/0fed7e68-e85f-4994-8454-593ec43ce4d75204000053039865802BR592355225041 FERNANDO AGUIA6010Planaltina61087375420362070503***63046FF9', 'iVBORw0KGgoAAAANSUhEUgAAAcIAAAHCAQAAAABUY/ToAAADDElEQVR4Xu2VS47cQAxDvfP9b5Rjeec0H6Vqd4CggtlEDVB2u/Th4yxKQY77h/Hr+LPzrxFyFyF3EXIXIXcRchf/jbwO4ryvk+LU6Uxf+j6WMuRk0sJTk8VIRI0ds2UZcjhZXal9026YZorfkw85ntSJpLQGO8U65BeR1n6kj/LtGXI6eXe3FwACvdp8qEsZcjRZymv/LM+Qf3kGkCt05/R11/zKbzXeEXIseXkPrDr4/5WMO3dDPZtgFHIyiXTdNAoyWyxbGqUNOZhk6FHhAniNghfdxiEnk+hJnQkpFSyM/pkvacixpAk0Bvr2VXWjFqFNQ84lya2QB8Cj9sx/AAuqkGNJqRXNceMWvVNZlBtoyLEkWZ3IZXJZ98jrT4ScT16Wo7Wweoty3VnIySRhQrgc8MBLcqQo2j/kYNJDy61aHRnZ7GNTQo4mqzCh2icd8xoZxyfkZLLFasmlJFI1wZ7YXo2Qw0mxflFC2Ek8tb7qSBZyNKlxXf0af9TCMFXLaMi5pK/Za1BE8T4bxDHkeFKARxgcdkCumQ+P/YScTaqrwy7Nl4mVjw5lyLEkt8vd16Q0brEmNmyN0JBjSXK9Gp6d24KvlGwHv8cmhJxJ0hBnuZJjXT/cY1FIQk4mkdPW1F9p3aTDU7mQkGNJidRqBaUNP0GhyEIOJw2ug7eY5dEWIaeTlIglV30seqWYcIScTboGkIPg7unErJbkvRwhB5MswUmz5FKvhudW2CLkYPIqqFMfEqI+ZNO7YKOQk0n1qzZjjPpmH9gDOdQs5HSSCge0fEXXseqahpxMMnn1fcekdHT1ZcUymKQXcjIJciFU7t9pC3R8lo3ykGPJAg1f4IIJXICqg2XIyWQHrCZC2wkfJ/ibCzmYfNyx7l1wEXaBsqJdQk4muVwuH2nr7FlSO5CjDzmYfCn6yktUIc1VK1EdaUN+DcnQAK91hB09DfkFJKf0y8Cq257k1Qk5mXRLjxhU8F26ai/0IeeSB/Hag1LLg7I6ZDhYHXI0+aMIuYuQuwi5i5C7CLmLLyN/A4dB2FR1oNZdAAAAAElFTkSuQmCC', 'PAGO', '2025-12-21 23:18:48', '2025-12-21 23:22:32'),
(2, 1, 1, 80, 160.00, 3.50, 163.50, 'Fernando', '86961906028', NULL, NULL, 'pay_uds0ce2y7p9dnmm5', '00020101021226820014br.gov.bcb.pix2560pix-h.asaas.com/qr/cobv/033f0798-bb14-492e-8c3c-4a8778cc65cc5204000053039865802BR592355225041 FERNANDO AGUIA6010Planaltina61087375420362070503***63040776', 'iVBORw0KGgoAAAANSUhEUgAAAcIAAAHCAQAAAABUY/ToAAADEUlEQVR4Xu2TS47kMAxDs8v9bzTH8q6m+Cg56QYaGfRmVABVTqwPH2th53j9Mv4c3zv/GiGfIuRThHyKkE8R8in+G7kO4lR6votzKVV+ani+s9puypBzyZuQgJZBYczuliFnk90l00G7QeEpfnc+5Hhy+QOuGvy4UsYhP4hU1howR5WSh/wM8uWuf5tAr4a7dm59yLlkKTf78297hvzhN4C8hY+ajaf8duOKkGNJztenrKG/5K6XHq1uyyjkZBJxEa10exusMsUi5HSyqJ6xAIyCF93KkJPJQjykMtQsTNlZH3I4eQjVKgfDu7Fd6YYcTNacc1YwKrAdyx4LqpCDSRgUmnDi2HUK3o42CjmWrCayug1gVXaOsP4g5GASRHUplm7G6fetd92LkKNJpNJc0B33xNa6Ca5CjiVV+3OWXv3FHAyerR1tFHIsqXBTssLb44THHhyfkJNJ6479RvKVUGUrGiFHk6Tsy2cNUTZd620v1CHHkrQb4QJUXR3q3ZMk5GRSmZ92ULMOXXuDviAh55Nrn75yB3JV3kSWRcjRJCinXAi9Onf0alwdypBzya+MqrPuxeGLUReiJKAhx5Lqbvwt4qw58vJgWkiVIeeSCqkK4nMWgSXfs123nzohx5JQEoqQQqNKpcWQX5uHnExKRbcSCPrfwP0PISeTru0gIahMYLZHW4ScTlp9lgAjb+VAiknNQk4mxSH1FVC/+JpB2FaLCDmWbIUw6ZZsbhs+5SpDWYWcSzKBZgQrK/RuULott5CTyW7UsBHNt5tvAjOjIceSrvWW7qB4AZdfWVtlMORkEiXjy0ishEAytKVXyNGkNABa/Zy2QFeWZaM85FiyQAGSMlTuw2dYUzdCziY7JJJCL/Hq2McJ/uZCDiavM0ZIbcIuUFa0S8jJJIdbSFkAtwcpDspDjieX5DplXKjLxGOpb7YhP4M8fBmwkIeWdYQdPQ35AaQ12lrSLTzJqxNyMumWlIcQqeBpiLdbeaEPOZeU/K1ofecsLJXhYHXI0eSvIuRThHyKkE8R8ilCPsWHkX8B1OVoxID9QrMAAAAASUVORK5CYII=', 'AGUARDANDO', '2025-12-22 00:32:16', NULL);

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
(1, 1, 1, 'beatriz-alvarenga-6948a61280d2d.webp', 'beatriz-alvarenga', '<p>Beatriz Alvarenga</p>', '<p>Beatriz tem 19 anos, é estudante de Arquitetura e apaixonada por projetos sociais voltados para a educação infantil.</p>', 1, 9, 10, 23.50, '2025-12-21 22:59:46', NULL, '2025-12-22 00:39:49');

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
(1, 3, 'Administrador', 'missecominasgerais@gmail.com', '$2y$10$ngdGZJBu5faoMzxfdvTzG.ppuDZ0DeE1MXye8FX2bHywpBbNFQT4q', 1, '2025-12-21 19:04:28', '2025-11-23 02:28:38', '2025-11-26 19:57:27');

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
  ADD KEY `idx_status` (`status`);

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
-- AUTO_INCREMENT de tabela `pacotes_votos`
--
ALTER TABLE `pacotes_votos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
