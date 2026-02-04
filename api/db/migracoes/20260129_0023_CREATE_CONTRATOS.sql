-- Migração: Criar tabelas de contratos e relacionamentos
-- Data: 2026-01-29

-- UP
CREATE TABLE IF NOT EXISTS contratos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  numero VARCHAR(100) UNIQUE NOT NULL,
  ano_contrato YEAR NOT NULL,
  
  licitacao_id INT NULL,
  fornecedor_id INT NOT NULL,
  
  objeto TEXT NOT NULL,
  
  data_inicio DATE NOT NULL,
  data_fim DATE NOT NULL,
  
  valor_contratado DECIMAL(15,2) NOT NULL,
  valor_executado DECIMAL(15,2) DEFAULT 0,
  valor_saldo DECIMAL(15,2) DEFAULT 0,
  
  status ENUM('planejamento', 'andamento', 'concluido', 'rescindido', 'suspenso') 
    DEFAULT 'planejamento',
  
  observacoes TEXT NULL,
  
  criado_por INT NULL,
  atualizado_por INT NULL,
  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (licitacao_id)
    REFERENCES licitacoes(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  FOREIGN KEY (fornecedor_id)
    REFERENCES fornecedores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  
  FOREIGN KEY (criado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  FOREIGN KEY (atualizado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  INDEX idx_numero (numero),
  INDEX idx_status (status),
  INDEX idx_data_fim (data_fim),
  INDEX idx_fornecedor (fornecedor_id),
  INDEX idx_licitacao (licitacao_id)
) ENGINE=InnoDB;

-- Aditivos de Contrato
CREATE TABLE IF NOT EXISTS contratos_aditivos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  contrato_id INT NOT NULL,
  numero_aditivo VARCHAR(50) NOT NULL,
  
  tipo ENUM('prorrogacao', 'reajuste', 'reducao', 'ampliacao') NOT NULL,
  
  descricao TEXT,
  
  data_inicio DATE,
  data_fim DATE,
  
  valor_adicional DECIMAL(15,2) DEFAULT 0,
  novo_valor_total DECIMAL(15,2),
  
  percentual_reajuste DECIMAL(5,2) DEFAULT 0,
  
  justificativa TEXT,
  
  status ENUM('planejamento', 'aprovado', 'executado', 'cancelado') 
    DEFAULT 'planejamento',
  
  criado_por INT,
  atualizado_por INT,
  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT uk_aditivo_contrato
    UNIQUE (contrato_id, numero_aditivo),
  
  FOREIGN KEY (contrato_id)
    REFERENCES contratos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  FOREIGN KEY (criado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  FOREIGN KEY (atualizado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  INDEX idx_contrato_id (contrato_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- Apostilamentos (alterações administrativas)
CREATE TABLE IF NOT EXISTS contratos_apostilamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  contrato_id INT NOT NULL,
  numero_apostilamento VARCHAR(50),
  
  descricao TEXT NOT NULL,
  
  data_apostilamento DATE NOT NULL,
  
  criado_por INT,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (contrato_id)
    REFERENCES contratos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  FOREIGN KEY (criado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  INDEX idx_contrato_id (contrato_id)
) ENGINE=InnoDB;

-- Fiscais de Contrato (Lei 14.133/2021)
CREATE TABLE IF NOT EXISTS contratos_fiscais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  contrato_id INT NOT NULL,
  usuario_id INT NOT NULL,
  
  data_nomeacao DATE NOT NULL,
  data_termino DATE,
  
  portaria_numero VARCHAR(50),
  data_publicacao_portaria DATE,
  
  responsabilidades TEXT,
  
  ativo BOOLEAN DEFAULT TRUE,
  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT uk_fiscal_contrato
    UNIQUE (contrato_id, usuario_id, data_nomeacao),
  
  FOREIGN KEY (contrato_id)
    REFERENCES contratos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  
  INDEX idx_contrato_id (contrato_id),
  INDEX idx_usuario_id (usuario_id),
  INDEX idx_ativo (ativo),
  INDEX idx_data_termino (data_termino)
) ENGINE=InnoDB;

-- Gestores de Contrato (Lei 14.133/2021)
CREATE TABLE IF NOT EXISTS contratos_gestores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  contrato_id INT NOT NULL,
  usuario_id INT NOT NULL,
  
  data_nomeacao DATE NOT NULL,
  data_termino DATE,
  
  portaria_numero VARCHAR(50),
  data_publicacao_portaria DATE,
  
  responsabilidades TEXT,
  
  ativo BOOLEAN DEFAULT TRUE,
  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT uk_gestor_contrato
    UNIQUE (contrato_id, usuario_id, data_nomeacao),
  
  FOREIGN KEY (contrato_id)
    REFERENCES contratos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  
  INDEX idx_contrato_id (contrato_id),
  INDEX idx_usuario_id (usuario_id),
  INDEX idx_ativo (ativo),
  INDEX idx_data_termino (data_termino)
) ENGINE=InnoDB;

-- DOWN
-- DROP TABLE IF EXISTS contratos_gestores;
-- DROP TABLE IF EXISTS contratos_fiscais;
-- DROP TABLE IF EXISTS contratos_apostilamentos;
-- DROP TABLE IF EXISTS contratos_aditivos;
-- DROP TABLE IF EXISTS contratos;
