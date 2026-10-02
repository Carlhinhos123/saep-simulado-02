CREATE DATABASE IF NOT EXISTS industria_db;
USE industria_db;

-- 1. TABELA DE USUÁRIOS
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cargo VARCHAR(50) NOT NULL
);

-- 2. TABELA DE INSUMOS
CREATE TABLE IF NOT EXISTS insumos (
    id_insumo INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    unidade_medida VARCHAR(10) NOT NULL, -- Ex: Un, Kg, m, L
    preco_custo DECIMAL(10,2) NOT NULL CHECK (preco_custo >= 0),
    qtd_atual INT NOT NULL DEFAULT 0 CHECK (qtd_atual >= 0),
    qtd_minima INT NOT NULL DEFAULT 0 CHECK (qtd_minima >= 0)
);

-- 3. TABELA DE MOVIMENTAÇÕES DE ESTOQUE
CREATE TABLE IF NOT EXISTS movimentacoes (
    id_movimentacao INT AUTO_INCREMENT PRIMARY KEY,
    id_insumo INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo ENUM('ENTRADA', 'SAIDA') NOT NULL,
    quantidade INT NOT NULL CHECK (quantidade > 0),
    data_movimentacao DATETIME NOT NULL,
    FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- ============================================================
-- POPULAÇÃO INICIAL (MÍNIMO DE 3 REGISTROS POR TABELA)
-- ============================================================

-- Inserção de Usuários
INSERT INTO usuarios (nome, email, senha, cargo) VALUES
('Carlos', 'carlos.almoxarife@metalurgica.com', '123', 'Almoxarife'),
('Ana Paula', 'ana.gerente@metalurgica.com', '6767', 'Gerente de Produção'),
('Roberto', 'roberto.operador@metalurgica.com', '333', 'Operador de Estoque');

-- Inserção de Insumos
INSERT INTO insumos (nome, categoria, unidade_medida, preco_custo, qtd_atual, qtd_minima) VALUES
('Chapa de Aço Carbono 2mm 1x2m', 'Chapas', 'Un', 185.50, 45, 10),
('Parafuso Sextavado M8x30mm', 'Fixadores', 'Un', 0.45, 1200, 500),
('Tinta Esmalte Sintético Cinza 18L', 'Pintura', 'L', 210.00, 8, 10),
('Placa Conectora Eletroeletrônica V2', 'Eletrônicos', 'Un', 42.00, 15, 20);

-- Inserção de Movimentações
INSERT INTO movimentacoes (id_insumo, id_usuario, tipo, quantidade, data_movimentacao) VALUES
(1, 1, 'ENTRADA', 50, '2026-09-20 08:30:00'),
(2, 3, 'ENTRADA', 2000, '2026-09-21 09:15:00'),
(3, 1, 'SAIDA', 5, '2026-09-25 14:00:00'),
(4, 2, 'SAIDA', 10, '2026-09-28 11:45:00');