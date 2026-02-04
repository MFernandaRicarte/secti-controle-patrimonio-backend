-- Migração: Criar tabelas de Empenhos (Rastreamento Orçamentário)
-- Data: 2026-01-29
-- Descrição: Tabelas para controle de empenhos e movimentações orçamentárias

CREATE TABLE IF NOT EXISTS empenhos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(50) UNIQUE NOT NULL,
    ano_empenho YEAR DEFAULT (YEAR(NOW())),
    contrato_id INT,
    licitacao_id INT,
    valor_empenhado DECIMAL(15, 2) NOT NULL,
    valor_liquidado DECIMAL(15, 2) DEFAULT 0.00,
    valor_pago DECIMAL(15, 2) DEFAULT 0.00,
    saldo DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('empenho', 'liquidado', 'pago', 'cancelado') DEFAULT 'empenho',
    descricao LONGTEXT,
    data_empenho DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    atualizado_por INT,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE SET NULL,
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE SET NULL,
    INDEX idx_numero (numero),
    INDEX idx_status (status),
    INDEX idx_contrato (contrato_id),
    INDEX idx_licitacao (licitacao_id),
    INDEX idx_data_empenho (data_empenho)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movimentacoes_empenho (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empenho_id INT NOT NULL,
    tipo ENUM('liquidacao', 'pagamento', 'cancelamento', 'anulacao', 'reforco') NOT NULL,
    valor DECIMAL(15, 2) NOT NULL,
    data_movimentacao DATE NOT NULL,
    numero_documento VARCHAR(50),
    descricao LONGTEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    FOREIGN KEY (empenho_id) REFERENCES empenhos(id) ON DELETE CASCADE,
    INDEX idx_empenho (empenho_id),
    INDEX idx_tipo (tipo),
    INDEX idx_data (data_movimentacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alertas_portaria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    tipo ENUM('fiscal', 'gestor') NOT NULL,
    usuario_id INT,
    data_vencimento DATE NOT NULL,
    status ENUM('pendente', 'notificado', 'renovado', 'expirado') DEFAULT 'pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_contrato (contrato_id),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_data_vencimento (data_vencimento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
