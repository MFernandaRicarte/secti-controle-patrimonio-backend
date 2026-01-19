-- ============================================================
-- SECTI - SCHEMA COMPLETO (Todas as Migrações Consolidadas)
-- Execute este arquivo UMA VEZ para criar todas as tabelas
-- ============================================================

-- ===== 0001: TABELAS INICIAIS =====
CREATE TABLE IF NOT EXISTS perfis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula VARCHAR(60) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  perfil_id INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_perfil
    FOREIGN KEY (perfil_id) REFERENCES perfis(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===== 0002: SETOR E SALA =====
CREATE TABLE IF NOT EXISTS setores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS salas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  setor_id INT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_salas_setor
    FOREIGN KEY (setor_id) REFERENCES setores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT unq_salas_setor_nome
    UNIQUE (setor_id, nome)
) ENGINE=InnoDB;

-- ===== 0003: FORNECEDOR E CATEGORIA =====
CREATE TABLE IF NOT EXISTS fornecedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  cnpj VARCHAR(18) UNIQUE,
  email VARCHAR(255),
  telefone VARCHAR(30),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fornecedores_nome (nome)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===== 0004: ITENS E ENTRADAS ESTOQUE =====
CREATE TABLE IF NOT EXISTS itens_estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(50) NOT NULL UNIQUE,
  produto_base VARCHAR(120) NULL,
  descricao VARCHAR(255) NOT NULL,
  unidade VARCHAR(30) NOT NULL,
  valor_unitario DECIMAL(12,2) NULL,
  estoque_atual INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL DEFAULT 0,
  local_guarda VARCHAR(100),
  categoria_id INT NULL,
  criado_por_usuario_id INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_itens_categoria
    FOREIGN KEY (categoria_id)
    REFERENCES categorias(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_itens_criado_por
    FOREIGN KEY (criado_por_usuario_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entradas_estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  documento VARCHAR(100),
  fornecedor_id INT NULL,
  usuario_operacao_id INT NULL,
  quantidade INT NOT NULL,
  custo_unitario DECIMAL(12,2),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_entradas_item
    FOREIGN KEY (item_id)
    REFERENCES itens_estoque(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_entradas_fornecedor
    FOREIGN KEY (fornecedor_id)
    REFERENCES fornecedores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_entrada_usuario
    FOREIGN KEY (usuario_operacao_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_entradas_item (item_id),
  INDEX idx_entradas_data (data_entrada),
  INDEX idx_entradas_estoque_data (data_entrada)
) ENGINE=InnoDB;

-- ===== 0005: BENS PATRIMONIAIS =====
CREATE TABLE IF NOT EXISTS bens_patrimoniais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_patrimonial VARCHAR(60) NOT NULL UNIQUE,
  tombamento_existente VARCHAR(60) NULL,
  descricao VARCHAR(255) NOT NULL,
  categoria_id INT NULL,
  marca_modelo VARCHAR(120),
  tipo_eletronico VARCHAR(100) NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'ativo',
  data_aquisicao DATETIME NULL,
  valor DECIMAL(12,2) NULL,
  observacao TEXT NULL,
  imagem_path VARCHAR(255) NULL,
  setor_id INT NULL,
  sala_id INT NULL,
  responsavel_usuario_id INT NULL,
  qr_code_hash VARCHAR(120),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bem_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_bem_setor
    FOREIGN KEY (setor_id) REFERENCES setores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_bem_sala
    FOREIGN KEY (sala_id) REFERENCES salas(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_bem_responsavel
    FOREIGN KEY (responsavel_usuario_id) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entradas_bem (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bem_id INT NOT NULL,
  data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  documento VARCHAR(100),
  fornecedor_id INT NULL,
  valor DECIMAL(12,2) NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_entrada_bem_bem
    FOREIGN KEY (bem_id) REFERENCES bens_patrimoniais(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_entrada_bem_fornecedor
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_entrada_bem_bem (bem_id),
  INDEX idx_entrada_bem_data (data_entrada)
) ENGINE=InnoDB;

-- ===== 0009: TRANSFERÊNCIAS DE BENS =====
CREATE TABLE IF NOT EXISTS transferencias_bens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bem_id INT NOT NULL,
  setor_origem_id INT NULL,
  sala_origem_id INT NULL,
  setor_destino_id INT NOT NULL,
  sala_destino_id INT NOT NULL,
  responsavel_origem_id INT NULL,
  responsavel_destino_id INT NULL,
  usuario_operacao_id INT NOT NULL,
  observacao TEXT NULL,
  data_transferencia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tb_bem FOREIGN KEY (bem_id)
    REFERENCES bens_patrimoniais(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tb_setor_origem FOREIGN KEY (setor_origem_id)
    REFERENCES setores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tb_sala_origem FOREIGN KEY (sala_origem_id)
    REFERENCES salas(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tb_setor_destino FOREIGN KEY (setor_destino_id)
    REFERENCES setores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tb_sala_destino FOREIGN KEY (sala_destino_id)
    REFERENCES salas(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tb_resp_origem FOREIGN KEY (responsavel_origem_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tb_resp_destino FOREIGN KEY (responsavel_destino_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_tb_usuario_operacao FOREIGN KEY (usuario_operacao_id)
    REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_tb_data (data_transferencia),
  INDEX idx_tb_bem (bem_id),
  INDEX idx_tb_setor_origem (setor_origem_id),
  INDEX idx_tb_setor_destino (setor_destino_id)
) ENGINE=InnoDB;

-- ===== SEEDS: DADOS INICIAIS =====

-- Perfil Administrador
INSERT IGNORE INTO perfis (nome) VALUES ('Administrador');

-- Setores principais
INSERT IGNORE INTO setores (nome) VALUES
  ('Gabinete'),
  ('Administrativo'),
  ('Compras'),
  ('Financeiro'),
  ('TI'),
  ('Almoxarifado'),
  ('Manutenção');

-- Salas por setor
INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala de Servidores' FROM setores s WHERE s.nome = 'TI';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Estações de Trabalho' FROM setores s WHERE s.nome = 'TI';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Estoque Principal' FROM setores s WHERE s.nome = 'Almoxarifado';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Depósito Auxiliar' FROM setores s WHERE s.nome = 'Almoxarifado';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala 01' FROM setores s WHERE s.nome = 'Administrativo';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala 02' FROM setores s WHERE s.nome = 'Administrativo';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Gabinete do Secretário' FROM setores s WHERE s.nome = 'Gabinete';

-- Usuário administrador padrão (Senha: 123456)
INSERT IGNORE INTO usuarios (matricula, email, nome, senha_hash, perfil_id)
VALUES (
  'admin001',
  'admin@secti.gov.br',
  'Administrador',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  (SELECT id FROM perfis WHERE nome = 'Administrador')
);

-- ============================================================
-- LAN HOUSE SOCIAL - MÓDULO DE CONTROLE ACADÊMICO
-- ============================================================

-- ===== LHS 0014: CURSOS =====
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

-- ===== LHS 0015: MATERIAIS DIDÁTICOS =====
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

-- ===== LHS 0016: ALUNOS =====
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

-- ===== LHS 0017: TURMAS =====
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

-- ===== LHS 0018: MATRÍCULA DE ALUNOS EM TURMAS =====
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

-- ===== LHS 0019: AULAS =====
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

-- ===== LHS 0020: PRESENÇAS =====
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

-- ===== LHS 0021: INSCRIÇÕES (PRÉ-MATRÍCULA PÚBLICA) =====
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

-- ===== LHS SEEDS: CURSOS DE EXEMPLO =====
INSERT IGNORE INTO lhs_cursos (nome, carga_horaria, ementa, ativo) VALUES
  ('Letramento Digital', 12, 'Introdução ao uso do computador, sistemas operacionais, navegação na internet e ferramentas de produtividade.', TRUE),
  ('Excel para Iniciantes', 20, 'Fundamentos do Microsoft Excel: criação de planilhas, fórmulas básicas e formatação.', TRUE),
  ('Internet e Redes Sociais', 16, 'Navegação segura na internet, uso consciente de redes sociais e proteção de dados pessoais.', TRUE);
