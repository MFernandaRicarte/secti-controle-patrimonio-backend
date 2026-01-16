-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO 0014: CURSOS
-- Tabela de cursos oferecidos
-- ============================================================

CREATE TABLE IF NOT EXISTS lhs_cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  carga_horaria INT NOT NULL DEFAULT 0 COMMENT 'Carga horária em horas',
  ementa TEXT NULL COMMENT 'Ementa do curso',
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lhs_cursos_nome (nome),
  INDEX idx_lhs_cursos_ativo (ativo)
) ENGINE=InnoDB;

-- Cursos de exemplo
INSERT IGNORE INTO lhs_cursos (nome, carga_horaria, ementa, ativo) VALUES
  ('Informática Básica', 40, 'Introdução ao uso do computador, sistemas operacionais, navegação na internet e ferramentas de produtividade.', TRUE),
  ('Excel para Iniciantes', 20, 'Fundamentos do Microsoft Excel: criação de planilhas, fórmulas básicas e formatação.', TRUE),
  ('Internet e Redes Sociais', 16, 'Navegação segura na internet, uso consciente de redes sociais e proteção de dados pessoais.', TRUE);
