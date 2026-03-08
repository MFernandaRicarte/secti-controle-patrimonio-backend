-- UP

CREATE TABLE IF NOT EXISTS rct_os_equipamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  os_id INT NOT NULL,
  tipo VARCHAR(100) NOT NULL,
  descricao VARCHAR(255) NULL,
  numero_item INT NOT NULL,
  destino_padrao VARCHAR(80) NULL,
  destino_outro VARCHAR(255) NULL,
  destino_final VARCHAR(255) NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_rct_os_equipamentos_os
    FOREIGN KEY (os_id) REFERENCES rct_os(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_rct_os_equipamentos_os_id ON rct_os_equipamentos (os_id);
CREATE INDEX idx_rct_os_equipamentos_destino_padrao ON rct_os_equipamentos (destino_padrao);

-- DOWN (rollback)
-- DROP TABLE rct_os_equipamentos;