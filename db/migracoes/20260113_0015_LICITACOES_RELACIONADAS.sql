-- UP
CREATE TABLE IF NOT EXISTS licitacoes_fases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  fase VARCHAR(100) NOT NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NULL,
  prazo_dias INT NULL,
  responsavel_id INT NULL,
  observacoes TEXT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS licitacoes_documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  nome VARCHAR(255) NOT NULL,
  tipo VARCHAR(50) NOT NULL, -- 'TR', 'edital', 'ata', 'parecer', etc.
  caminho_arquivo VARCHAR(500) NOT NULL,
  tamanho_arquivo BIGINT NULL,
  criado_por INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tramitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  acao VARCHAR(255) NOT NULL,
  parecer TEXT NULL,
  usuario_id INT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_licitacao_id (licitacao_id),
  INDEX idx_usuario_id (usuario_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alertas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  tipo VARCHAR(100) NOT NULL, -- 'prazo_fase', 'vencimento', etc.
  descricao TEXT NOT NULL,
  data_vencimento DATE NULL,
  status ENUM('ativo', 'resolvido', 'expirado') DEFAULT 'ativo',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  INDEX idx_licitacao_id (licitacao_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- DOWN
-- DROP TABLE IF EXISTS alertas;
-- DROP TABLE IF EXISTS tramitacoes;
-- DROP TABLE IF EXISTS licitacoes_documentos;
-- DROP TABLE IF EXISTS licitacoes_fases;