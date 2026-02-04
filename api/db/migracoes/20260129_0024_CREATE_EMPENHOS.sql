-- Migração: Criar tabelas de empenhos
-- Data: 2026-01-29

-- UP
CREATE TABLE IF NOT EXISTS empenhos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  numero VARCHAR(100) UNIQUE NOT NULL,
  ano_empenho YEAR NOT NULL,
  
  contrato_id INT,
  licitacao_id INT,
  
  valor_empenhado DECIMAL(15,2) NOT NULL,
  valor_liquidado DECIMAL(15,2) DEFAULT 0,
  valor_pago DECIMAL(15,2) DEFAULT 0,
  saldo DECIMAL(15,2) NOT NULL,
  
  descricao TEXT,
  
  data_empenho DATE NOT NULL,
  
  status ENUM('empenho', 'liquidado', 'pago', 'cancelado') DEFAULT 'empenho',
  
  criado_por INT,
  atualizado_por INT,
  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (contrato_id)
    REFERENCES contratos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  FOREIGN KEY (licitacao_id)
    REFERENCES licitacoes(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  FOREIGN KEY (criado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  FOREIGN KEY (atualizado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  INDEX idx_numero (numero),
  INDEX idx_contrato (contrato_id),
  INDEX idx_status (status),
  INDEX idx_data_empenho (data_empenho),
  INDEX idx_ano_empenho (ano_empenho)
) ENGINE=InnoDB;

-- Movimentações de Empenho
CREATE TABLE IF NOT EXISTS movimentacoes_empenho (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  empenho_id INT NOT NULL,
  
  tipo ENUM('liquidacao', 'pagamento', 'cancelamento', 'anulacao', 'reforco') 
    NOT NULL,
  
  valor DECIMAL(15,2) NOT NULL,
  
  data_movimentacao DATE NOT NULL,
  
  numero_documento VARCHAR(100),
  descricao TEXT,
  
  criado_por INT,
  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (empenho_id)
    REFERENCES empenhos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  FOREIGN KEY (criado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  INDEX idx_empenho_id (empenho_id),
  INDEX idx_tipo (tipo),
  INDEX idx_data_movimentacao (data_movimentacao)
) ENGINE=InnoDB;

-- Alertas de Vencimento de Portaria (Fiscais e Gestores)
CREATE TABLE IF NOT EXISTS alertas_portaria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  contrato_id INT NOT NULL,
  
  tipo ENUM('fiscal', 'gestor') NOT NULL,
  usuario_id INT NOT NULL,
  
  data_vencimento DATE NOT NULL,
  
  dias_antes_vencimento INT DEFAULT 30,
  
  status ENUM('pendente', 'notificado', 'renovado', 'expirado') 
    DEFAULT 'pendente',
  
  data_notificacao DATETIME,
  data_renovacao DATE,
  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (contrato_id)
    REFERENCES contratos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  INDEX idx_contrato_id (contrato_id),
  INDEX idx_status (status),
  INDEX idx_data_vencimento (data_vencimento)
) ENGINE=InnoDB;

-- DOWN
-- DROP TABLE IF EXISTS alertas_portaria;
-- DROP TABLE IF EXISTS movimentacoes_empenho;
-- DROP TABLE IF EXISTS empenhos;
