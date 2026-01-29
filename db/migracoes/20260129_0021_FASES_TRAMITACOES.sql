-- Migração: cria tabelas de fases e tramitações e adiciona campo current_fase_id em bens_patrimoniais
CREATE TABLE IF NOT EXISTS fases (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  slug VARCHAR(120) UNIQUE,
  ordem INT DEFAULT 0,
  descricao TEXT,
  ativo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- adiciona coluna current_fase_id em bens_patrimoniais
ALTER TABLE bens_patrimoniais
  ADD COLUMN current_fase_id INT NULL;

-- tabela de tramitações
CREATE TABLE IF NOT EXISTS tramitacoes_bens (
  id INT PRIMARY KEY AUTO_INCREMENT,
  bem_id INT NOT NULL,
  from_fase_id INT NULL,
  to_fase_id INT NOT NULL,
  usuario_operacao_id INT NULL,
  comentario TEXT,
  anexo VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (bem_id),
  INDEX (to_fase_id),
  INDEX (usuario_operacao_id),
  CONSTRAINT fk_tram_bem FOREIGN KEY (bem_id) REFERENCES bens_patrimoniais(id) ON DELETE CASCADE
);

-- Observação: chaves estrangeiras opcionais para fases/usuários podem ser adicionadas conforme esquema existente.
