-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 21/12/2025 às 23:52
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
(1, 1, 'missecominasgerais', '<p><span style=\"font-family:Comic Sans MS,cursive\">Missecominasgerais</span></p>', '<p>Este é realizado em minas gerais.</p>', 1, 40, '2025-11-26 22:58:27', '2025-12-15 23:45:25', '2025-12-15 23:54:20'),
(3, 1, 'miss-teen-minas-gerais', '<p>MISS TEEN MINAS GERAIS</p>', '<p>Miss Teen Minas Gerais é muito mais do que um concurso de beleza. É um projeto voltado para adolescentes que desejam usar sua voz, sua imagem e sua representatividade para causar impacto real na sociedade.</p><p>O concurso busca uma candidata consciente do seu papel social, com voz ativa, empatia e compromisso com causas que transformam vidas. Aqui, a faixa e a coroa vão além da estética: tornam-se ferramentas de visibilidade, amplificação e responsabilidade social.</p><p>A Miss Teen Minas Gerais é incentivada a desenvolver, apoiar e dar continuidade a projetos sociais, utilizando sua posição para inspirar outras jovens, levantar debates importantes e promover ações que façam a diferença em sua comunidade e em todo o estado.</p>', 1, 6, '2025-12-16 02:32:50', '2025-12-15 23:53:53', '2025-12-15 23:54:18');

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
(1, 'CONCURSO OFICIAL 2026', 'Vote Pela Sua Miss', 'A beleza, a elegância e a simpatia estão em jogo. Sua voz ajudará a coroar a nova estrela. Participe desta escolha histórica.', 'Ver canditadas', 'votar', 'landing-bg-6948881c7e4f5.webp', 1, '2025-12-21 20:51:58');

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
(1, '5 Votos', 5, 2.00, 3.50, 1);

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
(1, 1, 1, 5, 2.00, 3.50, 5.50, 'Teste Agora Morais', '04273991063', 'teste@gmail.com', '61983411859', 'pay_ksqlzwe6a7yy17p4', '00020101021226820014br.gov.bcb.pix2560pix-h.asaas.com/qr/cobv/21ce5ef6-4e3a-4c96-97c6-a30207d894bb5204000053039865802BR592355225041 FERNANDO AGUIA6010Planaltina61087375420362070503***6304A2A5', 'iVBORw0KGgoAAAANSUhEUgAAAcIAAAHCAQAAAABUY/ToAAADEElEQVR4Xu2TQW7kQAwDffP/f7TP8s1rFqW2s8DCQS7RANTM2BLFYg7d2c4f1p/tX+W7FfKtQr5VyLcK+VYh3+rXyGOj9qvRb9uPXbuDN1qt9uUMOZlsozWSrmGXAkbIMzLkbHKpjVtwRCdWVjlDjif1Ob/i25ek598IOZ48JfVKmKtG2UN+BnlaXawJ/DLzYC5nyNFkOcW+fFZmyP98BpCrtFpnXZcCYgl3hRxLcr6PUy6/J2WB+h/cQSFHk7g0y+KOwtexvQ85nwS+VY5fj0KdbBo15GRSqPHuWlsszLWuGxNyPokLHa9/naVtQcoKOZksD42KwTiJ0JQjmEKOJUHLesle8hVW/pKth5xMHjp9JagVr+mwj8H9coacTeKvU18Tyk157i7kbBKTBK+9tObZLkn2hRxMarakpXRBFQPPq7NtCTmWdOsEOCyVoTxiSIDHFHI06T1PvstiQrzzEEJOJlkKs2qLg6SwtOosAkLOJS9RjvawZnaing+P0ZBjSe8KMYRfmbwbLE/I4aTsrECVU7cAcl2HdSNCjibtA6FnpWH5Hd4KY8ix5Kl7IFqk4crod12I/gtCQ44lrfIFVZAnNj5664WFHE1KgNY5n4Q8jt9aXYKVFHI02advt6OK1u2oT/VnyNEkpiZ2jTR8n6BQyyEnk1JsrYgy8zpVFdNqyOFkgf3is+lO1K/ugC5GpYUcS0qwhwvhW1FWrUp0NGLI4aQxICYYHzpfrWUgEFfIsWRz9mnBnp8bmSTvMvO3Qs4lVTYWY8wW5SlB5bU3IeeSbJfdK80lP+fahpxNnroKOmr17eXoK8prSLSQk0kzXsmoTpgEfDxWjPqQY8kF1l0Q1kUKUGlEhpxMdsmEcR2+XBw9TcXaHHIsedxnXFvjOn0/yuL/c8Mh55LqfPICOurOoO1UW0JOJi9HH3mZquTpK1GKvCE/hmRpgK99lBO9DfkJJAtZ/YCFIZO+lJCTSUuHsQqiO9VKcZqz8IecS+oGyFyijE4R3pFOwh1yNPmjCvlWId8q5FuFfKuQb/Vh5F/niIikicIiMwAAAABJRU5ErkJggg==', 'PAGO', '2025-11-26 23:10:04', '2025-11-26 23:10:39'),
(2, 1, 1, 10, 4.00, 3.50, 7.50, 'Teste2 teste', '86418300068', 'teste2@gmail.com', '61983411859', 'pay_yvh2j4l8zj33jqgs', '00020101021226820014br.gov.bcb.pix2560pix-h.asaas.com/qr/cobv/396cf974-0f53-4081-b134-f9805d704de85204000053039865802BR592355225041 FERNANDO AGUIA6010Planaltina61087375420362070503***630463BC', 'iVBORw0KGgoAAAANSUhEUgAAAcIAAAHCAQAAAABUY/ToAAADGElEQVR4Xu2TQZLbMBADddP/f5Rn6eYYDZD2blWi1F4yrsJYljgYNHQgdTx+WL+O78q/Vsm7KnlXJe+q5F2VvKv/Rl4HdUrQ+rxYPptTwqmnH2/OknNJM9INsSDCmGavqf0lB5NLZXXIjmCaKXnvfMnxpH6P3csqeS01LPlJ5EPSGglzpZW95GeQD6un5huxH9HEy1lyNBkn7N9/O7PkH34DyF2XRe81/+Rt4VUlx5KXzwGYLXvX9aHDqbesoJLDSRpHKOQ94i02ESVnk5EWpC2POSh4aA5EydnkEqOuNFywjJ0eQ8m5JIg0c/aaQnJGINSSg8kFcZESt6zXDkymwZJzScPae83kdYyx+CPnDSUHk+skxLlIfOR5nReUnE4uSphxrS9/2qH0IHcJJceSl+eXtxm3E4zvXkM0dSXHknCoB0ZBWoDB8zDDa0pOJuU4OARg9MLIMK8w4yu85FgSAx6vsXwlCPI8+SUnk2JlXVAITkd6d6zkLzmWVC+HN/5cB8CfMXZiFYoJtORYUkt1PGSI3wrW6HlLydnks/dubzKnAHIfh30iSo4mM8y+Y/CWv7XYotCWnEuiaO8zyc2SnjkQyyO05FySkaUn8XbDrybxy1NyMhlUWw3iPZdLoBZfD4qQkoNJ6O30EK8DfSj0y7rkdFI+VNgMlfYFzIhpyblkvBGVIj7MzlgRJYeTa7fxYONn2v91PHiUnE3abfUhJ7Ctqyfcl19Rciwpmbtna6jHdutrlyERJQeTD/RFayW3jLj3aMXSlxxMSpQcFrd7BiQTvqJLDiY91KWMhMgtnYlVFusVJceS9tup9fKy9YniMKy50JKDSXlskCX/RODjtmO0LjmWDCirDdr1FClAUYksOZlcdeGzU96VdnEm+PtWcjS593h/x6LpEKG2I3DJuaRWutuqC58zY3UCa/wlB5NPR86BJ0ZNq33F8Sz5IaTWOFG57KOc6GnJDyC99dtvFkbnwxFRSk4mLekZ96tRRjoOwYorOZc8qKfu3ZZRNi5arUiwu+Ro8kdV8q5K3lXJuyp5VyXv6sPI3xRkGCMGADCZAAAAAElFTkSuQmCC', 'PAGO', '2025-11-26 23:14:13', '2025-11-26 23:18:39'),
(3, 1, 1, 20, 8.00, 3.50, 11.50, 'Fernando', '86961906028', NULL, NULL, 'pay_ag1ijk12cxsy9f1z', '00020101021226820014br.gov.bcb.pix2560pix-h.asaas.com/qr/cobv/b6f7d416-36ee-44cc-92c5-27aecfcd94a65204000053039865802BR592355225041 FERNANDO AGUIA6010Planaltina61087375420362070503***630428A4', 'iVBORw0KGgoAAAANSUhEUgAAAcIAAAHCAQAAAABUY/ToAAADB0lEQVR4Xu2TQY7dMAxDs8v9b9RjZfcbPkpOpkCRwWxGAaj+2BbFxy7s2T4/rD/bv8p3K+RThXyqkE8V8qlCPtWvkcdG7RKO/djPRYf6NnpvN2fIuWQzJXo/170w8ujLGXI2WSqmhm2uzdqdDzmebM/aAfvo6JDvIT+qPgpzVatxyHeQIK02Y79kFr8RO0OOJst5PP9bmSH/828AeZUmknXXfJW3hKtCjiXl33zLeG63rr/z1q6nEnI0qamGwrF6prvvAM0Q0EKOJjHsIphz6CBQ8NtUaMixpFqrkljrtNia1kkVcizpy5XqW7a3uhbqIdBoDzmY3ORgisHu+pgJMeVpyMGkhOI8vCe1XxGVBhpyLIlmpTN80qhSZL7akNNJJGdoTpT2piqyTiHnkyLMKYIYAqA/IJLwhJxNwulHgrPcongzgx5yMonTmzms+HRW55lwckKOJj0qSR22O0EQUR0fci5p1kNDrchXvdb6L0SEnEtqTDVDQAvul0ZgyMGkp9qvaSvsDZYc8g2khjSCtVjxAcN6ESFHk9jPrtz1KOre8dvUCm3IuaTuvv+gJTO2yFPwaqU9IeeS0AxE7DrvTVuxvT9VyMkkPgif1yNQAvN6BMykhJxL0l8e8+CinVivo9JDTiaL5XMIw9P1FawR05BjyWqEcfWiCYJZGR0RcjrZt78mwnTQxtfPgy3kbFKwmoOncA5ReBbsJuSpdM1DjiUtW/KOvSS7FcorcETIySQEv+121KPwImcjhkNOJ21aP9sZ9juQpBkvJuRcsgqPQrwSBdYqhwJDDibLKZm2vK2Yd5IEXCEHk/Jo1XB9uyPwVWTF6BxyLLlA2Q8WhVCkAJVGZMjJZJe0Qxe9Lv9SdHA+XMjB5LrjdqiBOOpllEVNJYQcTHK50nTVeA0DlNUJjg45nDwdBnTT7l3yOKs1XCFfQYpiaICffZQTPQ35FrK3ZmE2ZXAuJeRk0pITOojTp5UV2nEh55IbJTOiM+pHq5OTcIccTf6oQj5VyKcK+VQhnyrkU72M/Av4q2jEmXlIRgAAAABJRU5ErkJggg==', 'PAGO', '2025-12-02 00:14:22', '2025-12-02 00:15:32');

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
(1, 1, 1, 'miss-gabriela-6927866ca06a4.webp', 'adriana', 'Adriana', '<p>Gabriela</p>', 1, 23, 40, 30.00, '2025-11-26 22:59:56', '2025-12-01 23:40:52', '2025-12-03 04:23:43'),
(2, 1, 1, 'miss-daniela-69278cf611b91.webp', 'daniela', 'Daniela', '<p><span style=\"font-family:Courier New,Courier,monospace\">Miss è naturall de brasilia contando a hisotoria dela</span></p>', 1, 18, 0, 0.00, '2025-11-26 23:27:50', '2025-12-01 23:40:21', '2025-12-04 16:38:18');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
