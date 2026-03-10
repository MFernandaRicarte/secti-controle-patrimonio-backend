-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO 0017: TURMAS
-- Turmas vinculadas a cursos com professor responsável
-- ============================================================

CREATE TABLE IF NOT EXISTS lhs_turmas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  professor_id INT NULL COMMENT 'ID do usuário (professor) responsável',
  nome VARCHAR(100) NOT NULL COMMENT 'Ex: Turma 2026.1 - Manhã',
  horario_inicio TIME NOT NULL COMMENT 'Horário de início das aulas',
  horario_fim TIME NOT NULL COMMENT 'Horário de término das aulas',
  data_inicio DATE NOT NULL,
  data_fim DATE NULL,
  status ENUM('aberta', 'em_andamento', 'concluida', 'cancelada') NOT NULL DEFAULT 'aberta',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_turmas_curso
    FOREIGN KEY (curso_id) REFERENCES lhs_cursos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_turmas_professor
    FOREIGN KEY (professor_id) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_lhs_turmas_curso (curso_id),
  INDEX idx_lhs_turmas_professor (professor_id),
  INDEX idx_lhs_turmas_status (status),
  INDEX idx_lhs_turmas_data_inicio (data_inicio)
) ENGINE=InnoDB;
