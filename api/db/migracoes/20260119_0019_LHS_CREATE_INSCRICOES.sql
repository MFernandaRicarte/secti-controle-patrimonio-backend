-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO 0019: INSCRIÇÕES
-- Tabela para armazenar interesse em cursos (pré-matrícula)
-- ============================================================

CREATE TABLE IF NOT EXISTS lhs_inscricoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  nome VARCHAR(200) NOT NULL,
  cpf VARCHAR(14) NOT NULL COMMENT 'CPF com máscara (xxx.xxx.xxx-xx)',
  telefone VARCHAR(20) NULL,
  email VARCHAR(255) NULL,
  endereco VARCHAR(500) NULL,
  status ENUM('pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
  motivo_rejeicao VARCHAR(500) NULL COMMENT 'Motivo caso seja rejeitado',
  aluno_id INT NULL COMMENT 'ID do aluno criado após aprovação',
  turma_id INT NULL COMMENT 'ID da turma em que foi matriculado',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_lhs_inscricoes_curso
    FOREIGN KEY (curso_id) REFERENCES lhs_cursos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_inscricoes_aluno
    FOREIGN KEY (aluno_id) REFERENCES lhs_alunos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_inscricoes_turma
    FOREIGN KEY (turma_id) REFERENCES lhs_turmas(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
    
  INDEX idx_lhs_inscricoes_curso (curso_id),
  INDEX idx_lhs_inscricoes_status (status),
  INDEX idx_lhs_inscricoes_cpf (cpf),
  INDEX idx_lhs_inscricoes_criado_em (criado_em)
) ENGINE=InnoDB;
