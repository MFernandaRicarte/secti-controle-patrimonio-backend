ALTER TABLE lhs_aulas
ADD COLUMN conteudo_ministrado TEXT NULL AFTER observacao,
ADD COLUMN registrado_por INT NULL AFTER conteudo_ministrado;

ALTER TABLE lhs_aulas
ADD CONSTRAINT fk_lhs_aulas_registrado_por
  FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE lhs_presencas
DROP COLUMN conteudo_ministrado;

CREATE TABLE IF NOT EXISTS lhs_inscricoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero_protocolo VARCHAR(20) NOT NULL UNIQUE,
  curso_id INT NOT NULL,
  turma_preferencia_id INT NULL,
  nome VARCHAR(200) NOT NULL,
  cpf VARCHAR(14) NOT NULL,
  telefone VARCHAR(20) NULL,
  email VARCHAR(255) NULL,
  endereco VARCHAR(500) NULL,
  status ENUM('pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
  motivo_rejeicao TEXT NULL,
  aprovado_por INT NULL,
  aluno_id INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_inscricoes_curso
    FOREIGN KEY (curso_id) REFERENCES lhs_cursos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_inscricoes_turma
    FOREIGN KEY (turma_preferencia_id) REFERENCES lhs_turmas(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_inscricoes_aprovado_por
    FOREIGN KEY (aprovado_por) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_inscricoes_aluno
    FOREIGN KEY (aluno_id) REFERENCES lhs_alunos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_lhs_inscricoes_protocolo (numero_protocolo),
  INDEX idx_lhs_inscricoes_status (status),
  INDEX idx_lhs_inscricoes_cpf (cpf),
  INDEX idx_lhs_inscricoes_curso (curso_id)
) ENGINE=InnoDB;

INSERT IGNORE INTO perfis (nome) VALUES ('Professor');
INSERT IGNORE INTO perfis (nome) VALUES ('SUPERADMIN');

CREATE TABLE IF NOT EXISTS lhs_certificados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT NOT NULL,
  turma_id INT NOT NULL,
  codigo_validacao VARCHAR(50) NOT NULL UNIQUE,
  frequencia_final DECIMAL(5,2) NOT NULL,
  emitido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  emitido_por INT NULL,
  CONSTRAINT fk_lhs_certificados_aluno
    FOREIGN KEY (aluno_id) REFERENCES lhs_alunos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_certificados_turma
    FOREIGN KEY (turma_id) REFERENCES lhs_turmas(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_certificados_emitido_por
    FOREIGN KEY (emitido_por) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_lhs_certificados_codigo (codigo_validacao),
  INDEX idx_lhs_certificados_aluno (aluno_id),
  INDEX idx_lhs_certificados_turma (turma_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lhs_notificacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(50) NOT NULL,
  turma_id INT NULL,
  aluno_id INT NULL,
  data_referencia DATE NULL,
  mensagem TEXT NOT NULL,
  lida BOOLEAN NOT NULL DEFAULT FALSE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_notificacoes_turma
    FOREIGN KEY (turma_id) REFERENCES lhs_turmas(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_notificacoes_aluno
    FOREIGN KEY (aluno_id) REFERENCES lhs_alunos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_lhs_notificacoes_tipo (tipo),
  INDEX idx_lhs_notificacoes_lida (lida),
  INDEX idx_lhs_notificacoes_criado (criado_em)
) ENGINE=InnoDB;
