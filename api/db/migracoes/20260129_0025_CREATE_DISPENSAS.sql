-- Migração: Criar tabelas de Dispensas e Inexigibilidades
-- Data: 2026-01-29
-- Descrição: Tabelas para gestão de processos de dispensa e inexigibilidade (Art. 24, Lei 8.666/93)

CREATE TABLE IF NOT EXISTS dispensas_inexigibilidades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(50) UNIQUE NOT NULL,
    ano INT DEFAULT (YEAR(NOW())),
    tipo ENUM('dispensa', 'inexigibilidade') NOT NULL,
    fornecedor_id INT NOT NULL,
    objeto LONGTEXT NOT NULL,
    valor DECIMAL(15, 2) NOT NULL,
    justificativa_legal LONGTEXT NOT NULL,
    justificativa_tecnica LONGTEXT NOT NULL,
    data_solicitacao DATE NOT NULL,
    data_aprovacao DATE,
    data_conclusao DATE,
    usuario_solicitante INT,
    usuario_aprovador INT,
    status ENUM('planejamento', 'analise_juridica', 'aprovacao', 'executando', 'encerrada', 'cancelada') DEFAULT 'planejamento',
    observacoes LONGTEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_numero (numero),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_fornecedor (fornecedor_id),
    INDEX idx_data_solicitacao (data_solicitacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispensa_documentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dispensa_id INT NOT NULL,
    documento_path VARCHAR(255) NOT NULL,
    documento_nome VARCHAR(255) NOT NULL,
    documento_tamanho INT,
    tipo ENUM('justificativa', 'parecer', 'aprovacao', 'outro') NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    FOREIGN KEY (dispensa_id) REFERENCES dispensas_inexigibilidades(id) ON DELETE CASCADE,
    INDEX idx_dispensa (dispensa_id),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispensa_tramitacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dispensa_id INT NOT NULL,
    etapa VARCHAR(100) NOT NULL,
    acao VARCHAR(255) NOT NULL,
    parecer LONGTEXT,
    status_resultado ENUM('aprovado', 'reprovado', 'pendente_ajuste') NOT NULL,
    usuario_id INT,
    data_tramitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispensa_id) REFERENCES dispensas_inexigibilidades(id) ON DELETE CASCADE,
    INDEX idx_dispensa (dispensa_id),
    INDEX idx_etapa (etapa),
    INDEX idx_data (data_tramitacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
