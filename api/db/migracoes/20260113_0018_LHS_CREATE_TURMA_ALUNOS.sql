-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO 0018: TURMA_ALUNOS
-- Matrícula de alunos em turmas (relação N:N)
-- ============================================================

CREATE TABLE IF NOT EXISTS lhs_turma_alunos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  aluno_id INT NOT NULL,
  status ENUM('matriculado', 'aprovado', 'reprovado', 'evadido') NOT NULL DEFAULT 'matriculado',
  data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_turma_alunos_turma
    FOREIGN KEY (turma_id) REFERENCES lhs_turmas(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_turma_alunos_aluno
    FOREIGN KEY (aluno_id) REFERENCES lhs_alunos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT unq_lhs_turma_aluno
    UNIQUE (turma_id, aluno_id),
  INDEX idx_lhs_turma_alunos_turma (turma_id),
  INDEX idx_lhs_turma_alunos_aluno (aluno_id),
  INDEX idx_lhs_turma_alunos_status (status)
) ENGINE=InnoDB;
