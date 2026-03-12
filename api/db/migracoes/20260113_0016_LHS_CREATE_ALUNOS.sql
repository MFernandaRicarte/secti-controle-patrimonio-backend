-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO 0016: ALUNOS
-- Cadastro de alunos da comunidade
-- ============================================================

CREATE TABLE IF NOT EXISTS lhs_alunos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(200) NOT NULL,
  cpf VARCHAR(14) NOT NULL UNIQUE COMMENT 'CPF com máscara (xxx.xxx.xxx-xx)',
  telefone VARCHAR(20) NULL,
  email VARCHAR(255) NULL,
  endereco VARCHAR(500) NULL,
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lhs_alunos_nome (nome),
  INDEX idx_lhs_alunos_cpf (cpf),
  INDEX idx_lhs_alunos_ativo (ativo)
) ENGINE=InnoDB;
