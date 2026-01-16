-- ============================================================
-- LAN HOUSE SOCIAL - SCHEMA DE CONTROLE ACADÊMICO
-- Execute este arquivo para criar as tabelas do módulo
-- ============================================================

-- ===== CURSOS =====
-- Cursos oferecidos pelo Lan House Social
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

-- ===== MATERIAIS DIDÁTICOS =====
-- Arquivos de apoio vinculados aos cursos
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

-- ===== ALUNOS =====
-- Cadastro de alunos da comunidade
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

-- ===== TURMAS =====
-- Turmas vinculadas a cursos com professor responsável
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

-- ===== MATRÍCULA DE ALUNOS EM TURMAS =====
-- Relação N:N entre turmas e alunos
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

-- ===== AULAS =====
-- Registro de aulas de cada turma
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

-- ===== PRESENÇAS =====
-- Registro de presença com conteúdo ministrado
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

-- ============================================================
-- DADOS INICIAIS (SEEDS)
-- ============================================================

-- Cursos de exemplo
INSERT IGNORE INTO lhs_cursos (nome, carga_horaria, ementa, ativo) VALUES
  ('Letramento Digital', 12, 'Introdução ao uso do computador, sistemas operacionais, navegação na internet e ferramentas de produtividade.', TRUE),
  ('Excel para Iniciantes', 20, 'Fundamentos do Microsoft Excel: criação de planilhas, fórmulas básicas e formatação.', TRUE),
  ('Internet e Redes Sociais', 16, 'Navegação segura na internet, uso consciente de redes sociais e proteção de dados pessoais.', TRUE);
