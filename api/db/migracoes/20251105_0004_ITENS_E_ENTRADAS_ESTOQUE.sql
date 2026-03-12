-- UP
CREATE TABLE IF NOT EXISTS itens_estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(50) NOT NULL UNIQUE,
  descricao VARCHAR(255) NOT NULL,
  unidade VARCHAR(30) NOT NULL,
  estoque_atual INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL DEFAULT 0,
  local_guarda VARCHAR(100),
  categoria_id INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_itens_categoria
    FOREIGN KEY (categoria_id)
    REFERENCES categorias(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entradas_estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  documento VARCHAR(100),
  fornecedor_id INT NULL,
  quantidade INT NOT NULL,
  custo_unitario DECIMAL(12,2),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_entradas_item
    FOREIGN KEY (item_id)
    REFERENCES itens_estoque(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_entradas_fornecedor
    FOREIGN KEY (fornecedor_id)
    REFERENCES fornecedores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_entradas_item (item_id),
  INDEX idx_entradas_data (data_entrada)
) ENGINE=InnoDB;

-- DOWN
-- DROP TABLE entradas_estoque;
-- DROP TABLE itens_estoque;