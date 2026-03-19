CREATE TABLE IF NOT EXISTS transferencias_bens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bem_id INT NOT NULL,

  setor_origem_id INT NULL,
  sala_origem_id INT NULL,
  setor_destino_id INT NOT NULL,
  sala_destino_id INT NOT NULL,

  responsavel_origem_id INT NULL,
  responsavel_destino_id INT NULL,

  usuario_operacao_id INT NOT NULL,
  observacao TEXT NULL,

  data_transferencia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_tb_bem FOREIGN KEY (bem_id)
    REFERENCES bens_patrimoniais(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  CONSTRAINT fk_tb_setor_origem FOREIGN KEY (setor_origem_id)
    REFERENCES setores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT fk_tb_sala_origem FOREIGN KEY (sala_origem_id)
    REFERENCES salas(id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT fk_tb_setor_destino FOREIGN KEY (setor_destino_id)
    REFERENCES setores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  CONSTRAINT fk_tb_sala_destino FOREIGN KEY (sala_destino_id)
    REFERENCES salas(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  CONSTRAINT fk_tb_resp_origem FOREIGN KEY (responsavel_origem_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT fk_tb_resp_destino FOREIGN KEY (responsavel_destino_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT fk_tb_usuario_operacao FOREIGN KEY (usuario_operacao_id)
    REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  INDEX idx_tb_data (data_transferencia),
  INDEX idx_tb_bem (bem_id),
  INDEX idx_tb_setor_origem (setor_origem_id),
  INDEX idx_tb_setor_destino (setor_destino_id)
) ENGINE=InnoDB;
