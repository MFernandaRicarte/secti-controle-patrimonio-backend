-- Migration para criar tabela de documentos de contratos

CREATE TABLE IF NOT EXISTS contratos_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    tamanho_arquivo INT,
    criado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    INDEX idx_contrato_id (contrato_id),
    INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
