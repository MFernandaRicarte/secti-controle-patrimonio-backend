-- UP
CREATE TABLE IF NOT EXISTS licitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(100) UNIQUE NOT NULL,
  modalidade ENUM('CONCORRENCIA', 'TOMADA_DE_PRECOS', 'CONVITE', 'LEILAO', 'PREGAO', 'DIARIO_OFICIAL') NOT NULL,
  objeto TEXT NOT NULL,
  secretaria_id INT NOT NULL,
  data_abertura DATE NOT NULL,
  valor_estimado DECIMAL(15,2) NOT NULL,
  status ENUM('planejamento', 'publicacao', 'julgamento', 'homologacao', 'adjudicacao', 'encerrada') DEFAULT 'planejamento',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (secretaria_id) REFERENCES setores(id) ON DELETE RESTRICT,
  INDEX idx_numero (numero),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- DOWN
-- DROP TABLE IF EXISTS licitacoes;