-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO 0015: MATERIAIS DIDÁTICOS
-- Arquivos de apoio vinculados aos cursos
-- ============================================================

CREATE TABLE IF NOT EXISTS lhs_materiais_didaticos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  nome_arquivo VARCHAR(255) NOT NULL,
  path VARCHAR(500) NOT NULL COMMENT 'Caminho do arquivo no servidor',
  uploaded_por INT NULL COMMENT 'ID do usuário que fez upload',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_materiais_curso
    FOREIGN KEY (curso_id) REFERENCES lhs_cursos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_materiais_usuario
    FOREIGN KEY (uploaded_por) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_lhs_materiais_curso (curso_id)
) ENGINE=InnoDB;
