-- Migração: Criar tabelas para Dispensa e Inexigibilidade
-- Data: 2026-01-29

-- UP
CREATE TABLE IF NOT EXISTS dispensas_inexigibilidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  numero VARCHAR(100) UNIQUE NOT NULL,
  ano YEAR NOT NULL,
  
  tipo ENUM('dispensa', 'inexigibilidade') NOT NULL,
  
  fornecedor_id INT NOT NULL,
  
  objeto TEXT NOT NULL,
  descricao TEXT,
  
  valor DECIMAL(15,2) NOT NULL,
  
  justificativa_legal TEXT NOT NULL COMMENT 'Artigo/Lei que ampara',
  justificativa_tecnica TEXT NOT NULL COMMENT 'Explicação técnica ou operacional',
  
  status ENUM('planejamento', 'analise_juridica', 'aprovacao', 'executando', 'encerrada', 'cancelada') 
    DEFAULT 'planejamento',
  
  data_solicitacao DATE NOT NULL,
  data_aprovacao DATE,
  data_conclusao DATE,
  
  usuario_solicitante INT,
  usuario_aprovador INT,
  
  observacoes TEXT,
  
  criado_por INT,
  atualizado_por INT,
  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (fornecedor_id)
    REFERENCES fornecedores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  
  FOREIGN KEY (usuario_solicitante)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  FOREIGN KEY (usuario_aprovador)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  FOREIGN KEY (criado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  FOREIGN KEY (atualizado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  INDEX idx_numero (numero),
  INDEX idx_tipo (tipo),
  INDEX idx_status (status),
  INDEX idx_fornecedor (fornecedor_id),
  INDEX idx_data_solicitacao (data_solicitacao)
) ENGINE=InnoDB;

-- Documentos de Dispensa/Inexigibilidade
CREATE TABLE IF NOT EXISTS dispensa_documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  dispensa_id INT NOT NULL,
  
  nome VARCHAR(255) NOT NULL,
  tipo VARCHAR(50) NOT NULL COMMENT 'justificativa, parecer, aprovacao, etc',
  
  caminho_arquivo VARCHAR(500) NOT NULL,
  tamanho_arquivo BIGINT,
  
  criado_por INT,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (dispensa_id)
    REFERENCES dispensas_inexigibilidades(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  FOREIGN KEY (criado_por)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  
  INDEX idx_dispensa_id (dispensa_id)
) ENGINE=InnoDB;

-- Pareceres e Despachos de Dispensa
CREATE TABLE IF NOT EXISTS dispensa_tramitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  dispensa_id INT NOT NULL,
  
  etapa VARCHAR(100) NOT NULL COMMENT 'solicitacao, juridico, gerencial, aprovacao, etc',
  
  acao VARCHAR(255) NOT NULL,
  parecer TEXT,
  
  status_resultado ENUM('aprovado', 'reprovado', 'pendente_ajuste') NOT NULL,
  
  usuario_id INT NOT NULL,
  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (dispensa_id)
    REFERENCES dispensas_inexigibilidades(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  
  INDEX idx_dispensa_id (dispensa_id),
  INDEX idx_usuario_id (usuario_id)
) ENGINE=InnoDB;

-- DOWN
-- DROP TABLE IF EXISTS dispensa_tramitacoes;
-- DROP TABLE IF EXISTS dispensa_documentos;
-- DROP TABLE IF EXISTS dispensas_inexigibilidades;
