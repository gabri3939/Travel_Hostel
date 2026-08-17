-- ============================================================
-- Travel Hostel — Banco de Dados Completo
-- Inclui tabelas base + taxonomia (categorias, slug, índices)
-- Execute este script do zero para montar o banco completo.
-- ============================================================

DROP DATABASE IF EXISTS travel_hostel;
CREATE DATABASE IF NOT EXISTS travel_hostel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE travel_hostel;

-- ----------------------------------------------------------
-- TABELA: usuarios
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nome             VARCHAR(50)  NOT NULL,
    email            VARCHAR(100) NOT NULL UNIQUE,
    cpf              VARCHAR(14),
    telefone         VARCHAR(15),
    data_nascimento  DATE,
    senha            VARCHAR(255) NOT NULL,
    avatar           VARCHAR(255),
    nivel            ENUM('usuario', 'anfitriao', 'admin') DEFAULT 'usuario',
    avaliacao        DECIMAL(2,1) DEFAULT 0.0,
    total_avaliacoes INT DEFAULT 0,
    data_cadastro    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------
-- TABELA: categorias  (TAXONOMIA)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(60)  NOT NULL UNIQUE,
    slug      VARCHAR(60)  NOT NULL UNIQUE,
    descricao VARCHAR(255),
    icone     VARCHAR(50) DEFAULT 'fa-bed'
);

INSERT INTO categorias (nome, slug, descricao, icone) VALUES
('Praia',        'praia',        'Hostels à beira-mar ou com acesso fácil a praias',       'fa-umbrella-beach'),
('Natureza',     'natureza',     'Ecoturismo, trilhas e imersão na natureza',               'fa-tree'),
('Urbano',       'urbano',       'Hostels em centros urbanos próximos a transporte',        'fa-city'),
('Cultural',     'cultural',     'Arte, gastronomia e experiências culturais',              'fa-landmark'),
('Surf',         'surf',         'Localização estratégica para praticantes de surf',        'fa-water'),
('Econômico',    'economico',    'Melhor custo-benefício para mochileiros',                 'fa-tag'),
('Boutique',     'boutique',     'Design diferenciado e experiência personalizada',         'fa-star');

-- ----------------------------------------------------------
-- TABELA: hostels  (com colunas de taxonomia)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS hostels (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nome             VARCHAR(100) NOT NULL,
    slug             VARCHAR(120) NOT NULL UNIQUE,
    cidade           VARCHAR(100) NOT NULL,
    estado           VARCHAR(50),
    pais             VARCHAR(100) NOT NULL DEFAULT 'Brasil',
    descricao        TEXT,
    preco_diaria     DECIMAL(10, 2) NOT NULL,
    avaliacao        DECIMAL(2, 1) DEFAULT 0.0,
    total_avaliacoes INT DEFAULT 0,
    comodidades      VARCHAR(255),
    camas            INT DEFAULT 0,
    tipo             VARCHAR(50) DEFAULT 'Dormitorio',
    destaque         TINYINT(1) DEFAULT 0,
    imagem_url       VARCHAR(255),
    categoria_id     INT NULL,
    palavras_chave   VARCHAR(255),
    data_cadastro    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hostel_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- ----------------------------------------------------------
-- ÍNDICES DE BUSCA (TAXONOMIA)
-- ----------------------------------------------------------
CREATE INDEX idx_hostels_cidade            ON hostels (cidade);
CREATE INDEX idx_hostels_estado            ON hostels (estado);
CREATE INDEX idx_hostels_categoria         ON hostels (categoria_id);
CREATE INDEX idx_hostels_destaque          ON hostels (destaque);
CREATE INDEX idx_hostels_avaliacao         ON hostels (avaliacao DESC);
CREATE INDEX idx_hostels_preco             ON hostels (preco_diaria);
CREATE INDEX idx_hostels_categoria_aval    ON hostels (categoria_id, avaliacao DESC);
ALTER TABLE hostels ADD FULLTEXT INDEX ft_hostels_busca (nome, cidade, descricao, palavras_chave);

-- ----------------------------------------------------------
-- DADOS: hostels de exemplo (com slug, categoria e palavras-chave)
-- ----------------------------------------------------------
INSERT INTO hostels (nome, slug, cidade, estado, pais, descricao, preco_diaria, avaliacao, total_avaliacoes, comodidades, camas, tipo, destaque, imagem_url, categoria_id, palavras_chave) VALUES
('Morato Hostel Center',
 'morato-hostel-center',
 'Francisco Morato', 'SP', 'Brasil',
 'Hostel simples e aconchegante no centro de Francisco Morato.',
 15.00, 4.5, 120, 'WiFi,Cozinha,Lavanderia', 40, 'Dormitorio', 1,
 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='economico'),
 'hostel francisco morato, hospedagem barata sp, hostel centro'),

('Rocha Vibes Hostel',
 'rocha-vibes-hostel',
 'Franco da Rocha', 'SP', 'Brasil',
 'Ambiente tranquilo com área verde e espaço para descanso.',
 18.00, 4.6, 98, 'WiFi,Jardim,Cozinha,Lavanderia', 35, 'Dormitorio', 1,
 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='natureza'),
 'hostel franco da rocha, hostel jardim sp, hostel natureza'),

('Caieiras Eco Hostel',
 'caieiras-eco-hostel',
 'Caieiras', 'SP', 'Brasil',
 'Hostel ecológico cercado pela natureza e trilhas.',
 20.00, 4.7, 150, 'WiFi,Trilhas,Cozinha,Area Verde', 50, 'Dormitorio', 1,
 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='natureza'),
 'hostel caieiras, ecoturismo sp, hostel trilhas ecologico'),

('São Paulo Downtown',
 'sao-paulo-downtown',
 'São Paulo', 'SP', 'Brasil',
 'Hostel moderno no centro de São Paulo perto de tudo.',
 25.00, 4.8, 320, 'WiFi,Bar,Cozinha,Metro Proximo', 100, 'Dormitorio', 1,
 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='urbano'),
 'hostel sao paulo centro, hostel sp metro, hospedagem sao paulo'),

('Rio Beach Hostel',
 'rio-beach-hostel',
 'Rio de Janeiro', 'RJ', 'Brasil',
 'Hostel na praia com vista incrível e clima animado.',
 30.00, 4.9, 500, 'WiFi,Praia,Bar,Piscina', 120, 'Dormitorio', 1,
 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='praia'),
 'hostel rio de janeiro praia, hostel copacabana, hostel rj barato'),

('Curitiba Green Hostel',
 'curitiba-green-hostel',
 'Curitiba', 'PR', 'Brasil',
 'Hostel sustentável com ambiente calmo e organizado.',
 22.00, 4.6, 210, 'WiFi,Jardim,Cozinha,Bike', 60, 'Dormitorio', 1,
 'https://images.unsplash.com/photo-1560969184-10fe8719e047?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='natureza'),
 'hostel curitiba, hostel sustentavel parana, hospedagem curitiba'),

('Salvador Bahia Hostel',
 'salvador-bahia-hostel',
 'Salvador', 'BA', 'Brasil',
 'Hostel cultural com música e comida típica.',
 28.00, 4.7, 275, 'WiFi,Cafe,Cultura,Cozinha', 80, 'Dormitorio', 0,
 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='cultural'),
 'hostel salvador bahia, hostel pelourinho, hospedagem bahia cultural'),

('Floripa Surf Hostel',
 'floripa-surf-hostel',
 'Florianópolis', 'SC', 'Brasil',
 'Perfeito para surfistas com acesso à praia.',
 27.00, 4.8, 310, 'WiFi,Praia,Surf,Bar', 90, 'Dormitorio', 0,
 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='surf'),
 'hostel florianopolis surf, hostel floripa praia, hospedagem sc surf'),

('Belo Horizonte Hostel',
 'belo-horizonte-hostel',
 'Belo Horizonte', 'MG', 'Brasil',
 'Hostel confortável com clima mineiro acolhedor.',
 21.00, 4.5, 180, 'WiFi,Cozinha,Cafe,Sala', 70, 'Dormitorio', 0,
 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='urbano'),
 'hostel belo horizonte, hostel bh minas gerais, hospedagem bh barata'),

('Brasília Modern Hostel',
 'brasilia-modern-hostel',
 'Brasília', 'DF', 'Brasil',
 'Hostel moderno próximo aos pontos turísticos.',
 26.00, 4.6, 200, 'WiFi,Cozinha,Tour,Sala', 75, 'Dormitorio', 0,
 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=600&q=80',
 (SELECT id FROM categorias WHERE slug='urbano'),
 'hostel brasilia, hospedagem df turismo, hostel plano piloto');
