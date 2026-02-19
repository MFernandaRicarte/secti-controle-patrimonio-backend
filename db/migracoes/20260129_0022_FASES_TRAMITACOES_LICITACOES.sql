-- MariaDB compatível: adiciona coluna current_fase_id somente se não existir
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'licitacoes'
    AND COLUMN_NAME = 'current_fase_id'
);

SET @sql := IF(@col_exists = 0,
  'ALTER TABLE licitacoes ADD COLUMN current_fase_id INT NULL',
  'SELECT \"current_fase_id já existe\"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
