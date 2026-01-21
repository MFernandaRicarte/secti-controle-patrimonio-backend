-- Tabela de Licitações

CREATE TABLE licitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,

  numero VARCHAR(100) UNIQUE NOT NULL,

  modalidade ENUM(
    'CONCORRENCIA',
    'TOMADA_DE_PRECOS',
    'CONVITE',
    'LEILAO',
    'PREGAO',
    'DIARIO_OFICIAL'
  ) NOT NULL,

  objeto TEXT NOT NULL,

  secretaria_id INT NOT NULL,

  data_abertura DATE NOT NULL,

  valor_estimado DECIMAL(15,2) NOT NULL,

  status ENUM(
    'planejamento',
    'publicacao',
    'julgamento',
    'homologacao',
    'adjudicacao',
    'encerrada'
  ) DEFAULT 'planejamento',

  criado_por INT NULL,
  atualizado_por INT NULL,

  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (secretaria_id)
    REFERENCES setores(id)
    ON DELETE RESTRICT,

  FOREIGN KEY (criado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL,

  FOREIGN KEY (atualizado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL,

  INDEX idx_numero (numero),
  INDEX idx_status (status),
  INDEX idx_criado_por (criado_por),
  INDEX idx_atualizado_por (atualizado_por)
) ENGINE=InnoDB;

-- Licitações fases

CREATE TABLE licitacoes_fases (
  id INT AUTO_INCREMENT PRIMARY KEY,

  licitacao_id INT NOT NULL,

  fase VARCHAR(100) NOT NULL,

  data_inicio DATE NOT NULL,
  data_fim DATE NULL,

  prazo_dias INT NULL,

  responsavel_id INT NULL,

  observacoes TEXT NULL,

  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (licitacao_id)
    REFERENCES licitacoes(id)
    ON DELETE CASCADE,

  FOREIGN KEY (responsavel_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL,

  INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB;


-- Licitacoes documentos

CREATE TABLE licitacoes_documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,

  licitacao_id INT NOT NULL,

  nome VARCHAR(255) NOT NULL,

  tipo VARCHAR(50) NOT NULL,

  caminho_arquivo VARCHAR(500) NOT NULL,

  tamanho_arquivo BIGINT NULL,

  criado_por INT NULL,

  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (licitacao_id)
    REFERENCES licitacoes(id)
    ON DELETE CASCADE,

  FOREIGN KEY (criado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL,

  INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB;


-- Licitações tramitações

CREATE TABLE tramitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,

  licitacao_id INT NOT NULL,

  acao VARCHAR(255) NOT NULL,

  parecer TEXT NULL,

  usuario_id INT NOT NULL,

  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (licitacao_id)
    REFERENCES licitacoes(id)
    ON DELETE CASCADE,

  FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE CASCADE,

  INDEX idx_licitacao_id (licitacao_id),
  INDEX idx_usuario_id (usuario_id)
) ENGINE=InnoDB;

-- Licitações alertas

CREATE TABLE alertas (
  id INT AUTO_INCREMENT PRIMARY KEY,

  licitacao_id INT NOT NULL,

  tipo VARCHAR(100) NOT NULL,

  descricao TEXT NOT NULL,

  data_vencimento DATE NULL,

  status ENUM(
    'ativo',
    'resolvido',
    'expirado'
  ) DEFAULT 'ativo',

  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (licitacao_id)
    REFERENCES licitacoes(id)
    ON DELETE CASCADE,

  INDEX idx_licitacao_id (licitacao_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

