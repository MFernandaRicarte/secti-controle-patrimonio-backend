-- UP
-- Esta migração cria as tabelas de OS do ReciclaTech.
-- Ela tenta detectar automaticamente o nome da tabela pai de solicitações,
-- porque seu erro indica que "rct_solicitacoes" não está sendo encontrado.

DROP PROCEDURE IF EXISTS sp_rct_create_os;

DELIMITER $$

CREATE PROCEDURE sp_rct_create_os()
BEGIN
  DECLARE v_parent VARCHAR(64);
  DECLARE v_itens  VARCHAR(64);

  -- tenta localizar a tabela de solicitações (pais)
  SELECT t.table_name INTO v_parent
  FROM information_schema.tables t
  WHERE t.table_schema = DATABASE()
    AND t.table_name IN (
      'rct_solicitacoes',
      'rct_reciclatech_solicitacoes',
      'reciclatech_solicitacoes'
    )
  LIMIT 1;

  -- tenta localizar a tabela de itens de solicitação
  SELECT t.table_name INTO v_itens
  FROM information_schema.tables t
  WHERE t.table_schema = DATABASE()
    AND t.table_name IN (
      'rct_solicitacao_itens',
      'rct_solicitacoes_itens',
      'rct_reciclatech_solicitacao_itens',
      'reciclatech_solicitacao_itens'
    )
  LIMIT 1;

  IF v_parent IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'RECICLATECH_CREATE_OS: tabela de solicitacoes nao encontrada. Verifique a migracao 0036 (RECICLATECH_SOLICITACOES).';
  END IF;

  IF v_itens IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'RECICLATECH_CREATE_OS: tabela de itens da solicitacao nao encontrada. Verifique a migracao 0036 (RECICLATECH_SOLICITACOES).';
  END IF;

  -- cria rct_os com FK para a tabela pai detectada
  SET @sql1 = CONCAT(
    'CREATE TABLE IF NOT EXISTS rct_os (',
    '  id INT AUTO_INCREMENT PRIMARY KEY,',
    '  solicitacao_id INT NOT NULL,',
    '  status VARCHAR(20) NOT NULL DEFAULT ''ABERTA'',',
    '  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,',
    '  atualizado_em TIMESTAMP NULL DEFAULT NULL,',
    '  criado_por INT NULL,',
    '  atualizado_por INT NULL,',
    '  INDEX idx_rct_os_solicitacao (solicitacao_id),',
    '  INDEX idx_rct_os_status (status),',
    '  CONSTRAINT fk_rct_os_solicitacao FOREIGN KEY (solicitacao_id) REFERENCES ', v_parent, '(id) ON DELETE CASCADE ON UPDATE CASCADE,',
    '  CONSTRAINT fk_rct_os_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,',
    '  CONSTRAINT fk_rct_os_atualizado_por FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE',
    ') ENGINE=InnoDB;'
  );

  PREPARE stmt1 FROM @sql1;
  EXECUTE stmt1;
  DEALLOCATE PREPARE stmt1;

  -- cria rct_os_itens (FK para rct_os)
  SET @sql2 = CONCAT(
    'CREATE TABLE IF NOT EXISTS rct_os_itens (',
    '  id INT AUTO_INCREMENT PRIMARY KEY,',
    '  os_id INT NOT NULL,',
    '  tipo VARCHAR(80) NOT NULL,',
    '  quantidade INT NOT NULL DEFAULT 1,',
    '  descricao VARCHAR(255) NULL,',
    '  marca VARCHAR(120) NULL,',
    '  modelo VARCHAR(120) NULL,',
    '  situacao_tecnica VARCHAR(30) NULL,',
    '  observacao_tecnica TEXT NULL,',
    '  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,',
    '  INDEX idx_rct_os_itens_os (os_id),',
    '  CONSTRAINT fk_rct_os_itens_os FOREIGN KEY (os_id) REFERENCES rct_os(id) ON DELETE CASCADE ON UPDATE CASCADE',
    ') ENGINE=InnoDB;'
  );

  PREPARE stmt2 FROM @sql2;
  EXECUTE stmt2;
  DEALLOCATE PREPARE stmt2;

END$$

DELIMITER ;

CALL sp_rct_create_os();
DROP PROCEDURE IF EXISTS sp_rct_create_os;

-- DOWN (rollback)
-- DROP TABLE IF EXISTS rct_os_itens;
-- DROP TABLE IF EXISTS rct_os;