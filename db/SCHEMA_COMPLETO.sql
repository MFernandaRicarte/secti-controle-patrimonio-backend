-- ============================================================
-- SECTI - SCHEMA COMPLETO (Todas as Migrações Consolidadas)
-- Execute este arquivo UMA VEZ para criar todas as tabelas
-- ============================================================

-- ===== 0001: TABELAS INICIAIS =====
CREATE TABLE IF NOT EXISTS perfis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula VARCHAR(60) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  perfil_id INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_perfil
    FOREIGN KEY (perfil_id) REFERENCES perfis(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===== 0002: SETOR E SALA =====
CREATE TABLE IF NOT EXISTS setores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS salas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  setor_id INT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_salas_setor
    FOREIGN KEY (setor_id) REFERENCES setores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT unq_salas_setor_nome
    UNIQUE (setor_id, nome)
) ENGINE=InnoDB;

-- ===== 0003: FORNECEDOR E CATEGORIA =====
CREATE TABLE IF NOT EXISTS fornecedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  cnpj VARCHAR(18) UNIQUE,
  email VARCHAR(255),
  telefone VARCHAR(30),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fornecedores_nome (nome)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===== 0004: ITENS E ENTRADAS ESTOQUE =====
CREATE TABLE IF NOT EXISTS itens_estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(50) NOT NULL UNIQUE,
  produto_base VARCHAR(120) NULL,
  descricao VARCHAR(255) NOT NULL,
  unidade VARCHAR(30) NOT NULL,
  valor_unitario DECIMAL(12,2) NULL,
  estoque_atual INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL DEFAULT 0,
  local_guarda VARCHAR(100),
  categoria_id INT NULL,
  criado_por_usuario_id INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_itens_categoria
    FOREIGN KEY (categoria_id)
    REFERENCES categorias(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_itens_criado_por
    FOREIGN KEY (criado_por_usuario_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entradas_estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  documento VARCHAR(100),
  fornecedor_id INT NULL,
  usuario_operacao_id INT NULL,
  quantidade INT NOT NULL,
  custo_unitario DECIMAL(12,2),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_entradas_item
    FOREIGN KEY (item_id)
    REFERENCES itens_estoque(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_entradas_fornecedor
    FOREIGN KEY (fornecedor_id)
    REFERENCES fornecedores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_entrada_usuario
    FOREIGN KEY (usuario_operacao_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_entradas_item (item_id),
  INDEX idx_entradas_data (data_entrada),
  INDEX idx_entradas_estoque_data (data_entrada)
) ENGINE=InnoDB;

-- ===== 0005: BENS PATRIMONIAIS =====
CREATE TABLE IF NOT EXISTS bens_patrimoniais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_patrimonial VARCHAR(60) NOT NULL UNIQUE,
  tombamento_existente VARCHAR(60) NULL,
  descricao VARCHAR(255) NOT NULL,
  categoria_id INT NULL,
  marca_modelo VARCHAR(120),
  tipo_eletronico VARCHAR(100) NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'ativo',
  data_aquisicao DATETIME NULL,
  valor DECIMAL(12,2) NULL,
  observacao TEXT NULL,
  imagem_path VARCHAR(255) NULL,
  setor_id INT NULL,
  sala_id INT NULL,
  responsavel_usuario_id INT NULL,
  qr_code_hash VARCHAR(120),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bem_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_bem_setor
    FOREIGN KEY (setor_id) REFERENCES setores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_bem_sala
    FOREIGN KEY (sala_id) REFERENCES salas(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_bem_responsavel
    FOREIGN KEY (responsavel_usuario_id) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entradas_bem (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bem_id INT NOT NULL,
  data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  documento VARCHAR(100),
  fornecedor_id INT NULL,
  valor DECIMAL(12,2) NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_entrada_bem_bem
    FOREIGN KEY (bem_id) REFERENCES bens_patrimoniais(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_entrada_bem_fornecedor
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_entrada_bem_bem (bem_id),
  INDEX idx_entrada_bem_data (data_entrada)
) ENGINE=InnoDB;

-- ===== 0009: TRANSFERÊNCIAS DE BENS =====
CREATE TABLE IF NOT EXISTS transferencias_bens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bem_id INT NOT NULL,
  setor_origem_id INT NULL,
  sala_origem_id INT NULL,
  setor_destino_id INT NOT NULL,
  sala_destino_id INT NOT NULL,
  responsavel_origem_id INT NULL,
  responsavel_destino_id INT NULL,
  usuario_operacao_id INT NOT NULL,
  observacao TEXT NULL,
  data_transferencia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tb_bem FOREIGN KEY (bem_id)
    REFERENCES bens_patrimoniais(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tb_setor_origem FOREIGN KEY (setor_origem_id)
    REFERENCES setores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tb_sala_origem FOREIGN KEY (sala_origem_id)
    REFERENCES salas(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tb_setor_destino FOREIGN KEY (setor_destino_id)
    REFERENCES setores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tb_sala_destino FOREIGN KEY (sala_destino_id)
    REFERENCES salas(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tb_resp_origem FOREIGN KEY (responsavel_origem_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tb_resp_destino FOREIGN KEY (responsavel_destino_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tb_usuario_operacao FOREIGN KEY (usuario_operacao_id)
    REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_tb_data (data_transferencia),
  INDEX idx_tb_bem (bem_id),
  INDEX idx_tb_setor_origem (setor_origem_id),
  INDEX idx_tb_setor_destino (setor_destino_id)
) ENGINE=InnoDB;

-- ===== SEEDS: DADOS INICIAIS =====

-- Perfil Administrador
INSERT IGNORE INTO perfis (nome) VALUES ('Administrador');

-- Setores principais
INSERT IGNORE INTO setores (nome) VALUES
  ('Gabinete'),
  ('Administrativo'),
  ('Compras'),
  ('Financeiro'),
  ('TI'),
  ('Almoxarifado'),
  ('Manutenção');

-- Salas por setor
INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala de Servidores' FROM setores s WHERE s.nome = 'TI';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Estações de Trabalho' FROM setores s WHERE s.nome = 'TI';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Estoque Principal' FROM setores s WHERE s.nome = 'Almoxarifado';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Depósito Auxiliar' FROM setores s WHERE s.nome = 'Almoxarifado';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala 01' FROM setores s WHERE s.nome = 'Administrativo';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala 02' FROM setores s WHERE s.nome = 'Administrativo';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Gabinete do Secretário' FROM setores s WHERE s.nome = 'Gabinete';

-- Usuário administrador padrão (Senha: 123456)
INSERT IGNORE INTO usuarios (matricula, email, nome, senha_hash, perfil_id)
VALUES (
  'admin001',
  'admin@secti.gov.br',
  'Administrador',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  (SELECT id FROM perfis WHERE nome = 'Administrador')
);
