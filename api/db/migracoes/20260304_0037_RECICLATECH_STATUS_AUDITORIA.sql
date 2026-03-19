-- UP
ALTER TABLE rct_solicitacoes ENGINE=InnoDB;

-- coluna atualizado_por
SET @has_atualizado_por := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rct_solicitacoes'
    AND COLUMN_NAME = 'atualizado_por'
);

SET @sql := IF(@has_atualizado_por = 0,
  'ALTER TABLE rct_solicitacoes ADD COLUMN atualizado_por INT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- coluna atualizado_em
SET @has_atualizado_em := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rct_solicitacoes'
    AND COLUMN_NAME = 'atualizado_em'
);

SET @sql := IF(@has_atualizado_em = 0,
  'ALTER TABLE rct_solicitacoes ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- FK (só cria se não existir)
SET @has_fk := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rct_solicitacoes'
    AND CONSTRAINT_NAME = 'fk_rct_solicitacoes_atualizado_por'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql := IF(@has_fk = 0,
  'ALTER TABLE rct_solicitacoes
     ADD CONSTRAINT fk_rct_solicitacoes_atualizado_por
     FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
     ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- DOWN (rollback)
-- ALTER TABLE rct_solicitacoes DROP FOREIGN KEY fk_rct_solicitacoes_atualizado_por;
-- ALTER TABLE rct_solicitacoes DROP COLUMN atualizado_por;
-- ALTER TABLE rct_solicitacoes DROP COLUMN atualizado_em;