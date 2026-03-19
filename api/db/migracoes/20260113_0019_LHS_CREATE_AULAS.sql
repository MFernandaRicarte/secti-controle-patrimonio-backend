-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO 0019: AULAS
-- Registro de aulas de cada turma
-- ============================================================

CREATE TABLE IF NOT EXISTS lhs_aulas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  data_aula DATE NOT NULL,
  observacao TEXT NULL COMMENT 'Observações gerais da aula',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_aulas_turma
    FOREIGN KEY (turma_id) REFERENCES lhs_turmas(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT unq_lhs_aula_turma_data
    UNIQUE (turma_id, data_aula),
  INDEX idx_lhs_aulas_turma (turma_id),
  INDEX idx_lhs_aulas_data (data_aula)
) ENGINE=InnoDB;
