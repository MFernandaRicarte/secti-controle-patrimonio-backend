DROP TABLE IF EXISTS licitacoes_documentos;

CREATE TABLE licitacoes_documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  nome VARCHAR(255) NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  caminho_arquivo VARCHAR(500) NOT NULL,
  tamanho_arquivo BIGINT NULL,
  criado_por INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB;