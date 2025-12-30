ALTER TABLE entradas_estoque
  ADD COLUMN usuario_operacao_id INT NULL AFTER fornecedor_id;

ALTER TABLE entradas_estoque
  ADD CONSTRAINT fk_entrada_usuario
  FOREIGN KEY (usuario_operacao_id)
  REFERENCES usuarios(id)
  ON DELETE SET NULL ON UPDATE CASCADE;

CREATE INDEX idx_entradas_estoque_data ON entradas_estoque(data_entrada);
