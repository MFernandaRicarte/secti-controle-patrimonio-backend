-- UP
CREATE TABLE IF NOT EXISTS bens_patrimoniais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_patrimonial VARCHAR(60) NOT NULL UNIQUE,
  descricao VARCHAR(255) NOT NULL,
  categoria_id INT NULL,
  marca_modelo VARCHAR(120),
  estado VARCHAR(30) NOT NULL DEFAULT 'ativo',
  data_aquisicao DATETIME NULL,
  valor DECIMAL(12,2) NULL,
  setor_id INT NULL,
  sala_id INT NULL,
  responsavel_usuario_id INT NULL,
  qr_code_hash VARCHAR(120),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bem_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_bem_setor
    FOREIGN KEY (setor_id) REFERENCES setores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_bem_sala
    FOREIGN KEY (sala_id) REFERENCES salas(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_bem_responsavel
    FOREIGN KEY (responsavel_usuario_id) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entradas_bem (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bem_id INT NOT NULL,
  data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  documento VARCHAR(100),
  fornecedor_id INT NULL,
  valor DECIMAL(12,2) NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_entrada_bem_bem
    FOREIGN KEY (bem_id) REFERENCES bens_patrimoniais(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_entrada_bem_fornecedor
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_entrada_bem_bem (bem_id),
  INDEX idx_entrada_bem_data (data_entrada)
) ENGINE=InnoDB;

-- DOWN
-- DROP TABLE entradas_bem;
-- DROP TABLE bens_patrimoniais;
