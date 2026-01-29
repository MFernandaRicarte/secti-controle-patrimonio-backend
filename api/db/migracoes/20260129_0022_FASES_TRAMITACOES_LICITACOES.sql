-- Migração: adiciona current_fase_id em licitacoes e cria tramitacoes_licitacoes
ALTER TABLE licitacoes
  ADD COLUMN IF NOT EXISTS current_fase_id INT NULL;

CREATE TABLE IF NOT EXISTS tramitacoes_licitacoes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  licitacao_id INT NOT NULL,
  from_fase_id INT NULL,
  to_fase_id INT NOT NULL,
  usuario_operacao_id INT NULL,
  comentario TEXT,
  anexo VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (licitacao_id),
  INDEX (to_fase_id),
  INDEX (usuario_operacao_id),
  CONSTRAINT fk_tram_licitacao FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE
);
