-- UP
SET @has_ip := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rct_solicitacoes'
    AND COLUMN_NAME = 'ip_origem'
);

SET @sql := IF(@has_ip = 0,
  'ALTER TABLE rct_solicitacoes ADD COLUMN ip_origem VARCHAR(45) NULL AFTER criado_por',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rct_solicitacoes'
    AND INDEX_NAME = 'idx_rct_solicitacoes_ip'
);

SET @sql := IF(@has_idx = 0,
  'ALTER TABLE rct_solicitacoes ADD INDEX idx_rct_solicitacoes_ip (ip_origem, criado_em)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_cat := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rct_solicitacao_itens'
    AND COLUMN_NAME = 'categoria_id'
);

SET @sql := IF(@has_cat = 0,
  'ALTER TABLE rct_solicitacao_itens ADD COLUMN categoria_id INT NULL AFTER descricao',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_fk := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rct_solicitacao_itens'
    AND CONSTRAINT_NAME = 'fk_rct_item_categoria'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql := IF(@has_fk = 0,
  'ALTER TABLE rct_solicitacao_itens
     ADD CONSTRAINT fk_rct_item_categoria
     FOREIGN KEY (categoria_id) REFERENCES rct_categorias(id)
     ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- DOWN (rollback)
-- ALTER TABLE rct_solicitacao_itens DROP FOREIGN KEY fk_rct_item_categoria;
-- ALTER TABLE rct_solicitacao_itens DROP COLUMN categoria_id;
-- ALTER TABLE rct_solicitacoes DROP INDEX idx_rct_solicitacoes_ip;
-- ALTER TABLE rct_solicitacoes DROP COLUMN ip_origem;
