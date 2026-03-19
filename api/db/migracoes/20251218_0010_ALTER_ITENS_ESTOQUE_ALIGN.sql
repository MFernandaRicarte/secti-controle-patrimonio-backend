-- 20251218_0010_ALTER_ITENS_ESTOQUE_ALIGN.sql
-- Ajuste idempotente: adiciona colunas e FK apenas se ainda não existirem

-- produto_base
SET @col := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'itens_estoque'
    AND COLUMN_NAME = 'produto_base'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE itens_estoque ADD COLUMN produto_base VARCHAR(120) NULL AFTER codigo',
  'SELECT ''produto_base já existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- valor_unitario
SET @col := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'itens_estoque'
    AND COLUMN_NAME = 'valor_unitario'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE itens_estoque ADD COLUMN valor_unitario DECIMAL(12,2) NULL AFTER unidade',
  'SELECT ''valor_unitario já existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- criado_por_usuario_id
SET @col := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'itens_estoque'
    AND COLUMN_NAME = 'criado_por_usuario_id'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE itens_estoque ADD COLUMN criado_por_usuario_id INT NULL AFTER categoria_id',
  'SELECT ''criado_por_usuario_id já existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- FK fk_itens_criado_por
SET @fk := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'itens_estoque'
    AND CONSTRAINT_NAME = 'fk_itens_criado_por'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE itens_estoque ADD CONSTRAINT fk_itens_criado_por FOREIGN KEY (criado_por_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT ''FK fk_itens_criado_por já existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;