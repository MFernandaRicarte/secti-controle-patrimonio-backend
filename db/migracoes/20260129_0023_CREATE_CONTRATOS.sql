-- Migração: Criar tabelas de Contratos (Lei 14.133/2021)
-- Data: 2026-01-29
-- Descrição: Tabelas para gerenciamento de contratos e seus aditivos

CREATE TABLE IF NOT EXISTS contratos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(50) UNIQUE NOT NULL,
    ano_contrato YEAR DEFAULT (YEAR(NOW())),
    licitacao_id INT,
    fornecedor_id INT NOT NULL,
    objeto LONGTEXT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    valor_contratado DECIMAL(15, 2) NOT NULL,
    valor_executado DECIMAL(15, 2) DEFAULT 0.00,
    valor_saldo DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('planejamento', 'andamento', 'concluido', 'rescindido', 'suspenso') DEFAULT 'planejamento',
    observacoes LONGTEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    atualizado_por INT,
    INDEX idx_numero (numero),
    INDEX idx_status (status),
    INDEX idx_fornecedor (fornecedor_id),
    INDEX idx_licitacao (licitacao_id),
    INDEX idx_data_inicio (data_inicio),
    INDEX idx_data_fim (data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contratos_aditivos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    numero_aditivo VARCHAR(50) NOT NULL,
    tipo ENUM('prorrogacao', 'reajuste', 'reducao', 'ampliacao') NOT NULL,
    data_inicio DATE,
    data_fim DATE,
    valor_adicional DECIMAL(15, 2),
    percentual_reajuste DECIMAL(5, 2),
    status ENUM('planejado', 'aprovado', 'executado', 'cancelado') DEFAULT 'planejado',
    descricao LONGTEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    UNIQUE KEY uk_contrato_aditivo (contrato_id, numero_aditivo),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contratos_apostilamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    numero_apostilamento VARCHAR(50) NOT NULL,
    descricao LONGTEXT NOT NULL,
    data_apostilamento DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    INDEX idx_data (data_apostilamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contratos_fiscais (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_nomeacao DATE NOT NULL,
    data_termino DATE,
    portaria_numero VARCHAR(50),
    data_publicacao_portaria DATE,
    responsabilidades LONGTEXT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    UNIQUE KEY uk_contrato_usuario (contrato_id, usuario_id, data_nomeacao),
    INDEX idx_contrato (contrato_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_ativo (ativo),
    INDEX idx_data_nomeacao (data_nomeacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contratos_gestores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_nomeacao DATE NOT NULL,
    data_termino DATE,
    portaria_numero VARCHAR(50),
    data_publicacao_portaria DATE,
    responsabilidades LONGTEXT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    UNIQUE KEY uk_contrato_usuario (contrato_id, usuario_id, data_nomeacao),
    INDEX idx_contrato (contrato_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_ativo (ativo),
    INDEX idx_data_nomeacao (data_nomeacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
