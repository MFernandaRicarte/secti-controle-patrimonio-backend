-- UP

INSERT IGNORE INTO perfis (nome) VALUES ('ADMIN_LANHOUSE');

CREATE TABLE IF NOT EXISTS lhs_professor_turmas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  professor_id INT NOT NULL,
  turma_id INT NOT NULL,
  atribuido_por INT NULL,
  atribuido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_professor_turma (professor_id, turma_id),
  CONSTRAINT fk_lhs_pt_professor FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_pt_turma FOREIGN KEY (turma_id) REFERENCES lhs_turmas(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_pt_atribuido FOREIGN KEY (atribuido_por) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lhs_professor_turmas (professor_id, turma_id)
SELECT professor_id, id FROM lhs_turmas WHERE professor_id IS NOT NULL;

-- DOWN
-- DROP TABLE IF EXISTS lhs_professor_turmas;
-- DELETE FROM perfis WHERE nome = 'ADMIN_LANHOUSE';
