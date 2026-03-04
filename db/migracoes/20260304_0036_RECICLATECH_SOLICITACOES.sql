-- UP
CREATE TABLE IF NOT EXISTS reciclatech_solicitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  protocolo VARCHAR(20) NOT NULL UNIQUE,

  nome VARCHAR(150) NOT NULL,
  telefone VARCHAR(50) NULL,
  email VARCHAR(150) NULL,

  endereco VARCHAR(255) NOT NULL,
  referencia VARCHAR(255) NULL,
  observacoes TEXT NULL,

  status VARCHAR(30) NOT NULL DEFAULT 'ABERTA',

  criado_por INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_reciclatech_solicitacoes_criado_por
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reciclatech_solicitacao_itens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  solicitacao_id INT NOT NULL,

  tipo VARCHAR(150) NOT NULL,
  quantidade INT NOT NULL,
  descricao VARCHAR(255) NULL,

  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_reciclatech_itens_solicitacao
    FOREIGN KEY (solicitacao_id) REFERENCES reciclatech_solicitacoes(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- DOWN (rollback)
-- DROP TABLE reciclatech_solicitacao_itens;
-- DROP TABLE reciclatech_solicitacoes;