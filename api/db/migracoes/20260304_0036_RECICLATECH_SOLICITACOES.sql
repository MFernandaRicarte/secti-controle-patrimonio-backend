-- UP
CREATE TABLE IF NOT EXISTS rct_solicitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  protocolo VARCHAR(30) NOT NULL UNIQUE,
  nome VARCHAR(255) NOT NULL,
  telefone VARCHAR(30) NOT NULL,
  email VARCHAR(255) NULL,
  endereco VARCHAR(255) NOT NULL,
  referencia VARCHAR(255) NULL,
  observacoes TEXT NULL,
  status ENUM('ABERTA','TRIAGEM','AGENDADA','COLETADA','CANCELADA') NOT NULL DEFAULT 'ABERTA',
  criado_por INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rct_solicitacoes_criado_por
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rct_solicitacao_itens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  solicitacao_id INT NOT NULL,
  tipo VARCHAR(100) NOT NULL,
  quantidade INT NOT NULL DEFAULT 1,
  descricao VARCHAR(255) NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rct_solicitacao_itens_solicitacao
    FOREIGN KEY (solicitacao_id) REFERENCES rct_solicitacoes(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- DOWN (rollback)
-- DROP TABLE rct_solicitacao_itens;
-- DROP TABLE rct_solicitacoes;