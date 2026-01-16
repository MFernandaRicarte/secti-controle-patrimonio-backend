-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO 0020: PRESENÇAS
-- Registro de presença com conteúdo ministrado
-- ============================================================

CREATE TABLE IF NOT EXISTS lhs_presencas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aula_id INT NOT NULL,
  aluno_id INT NOT NULL,
  presente BOOLEAN NOT NULL DEFAULT FALSE,
  conteudo_ministrado TEXT NULL COMMENT 'Conteúdo ministrado nesta aula',
  registrado_por INT NULL COMMENT 'ID do usuário (professor) que registrou',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_presencas_aula
    FOREIGN KEY (aula_id) REFERENCES lhs_aulas(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_presencas_aluno
    FOREIGN KEY (aluno_id) REFERENCES lhs_alunos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_presencas_registrado_por
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT unq_lhs_presenca_aula_aluno
    UNIQUE (aula_id, aluno_id),
  INDEX idx_lhs_presencas_aula (aula_id),
  INDEX idx_lhs_presencas_aluno (aluno_id),
  INDEX idx_lhs_presencas_presente (presente)
) ENGINE=InnoDB;
