-- ============================================================
-- SECTI - SCHEMA COMPLETO (Todas as Migrações Consolidadas)
-- Execute este arquivo UMA VEZ para criar todas as tabelas
-- ============================================================

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

DROP TABLE IF EXISTS licitacoes_documentos;

CREATE TABLE licitacoes_documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  nome VARCHAR(255) NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  caminho_arquivo VARCHAR(500) NOT NULL,
  tamanho_arquivo BIGINT NULL,
  criado_por INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB;

ALTER TABLE licitacoes 
ADD COLUMN criado_por INT NULL AFTER status,
ADD COLUMN atualizado_por INT NULL AFTER criado_por,
ADD CONSTRAINT fk_licitacoes_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_licitacoes_atualizado_por FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
ADD INDEX idx_criado_por (criado_por),
ADD INDEX idx_atualizado_por (atualizado_por);

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

CREATE TABLE IF NOT EXISTS itens_estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(50) NOT NULL UNIQUE,
  descricao VARCHAR(255) NOT NULL,
  unidade VARCHAR(30) NOT NULL,
  estoque_atual INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL DEFAULT 0,
  local_guarda VARCHAR(100),
  categoria_id INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_itens_categoria
    FOREIGN KEY (categoria_id)
    REFERENCES categorias(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entradas_estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  documento VARCHAR(100),
  fornecedor_id INT NULL,
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
  INDEX idx_entradas_item (item_id),
  INDEX idx_entradas_data (data_entrada)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bens_patrimoniais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_patrimonial VARCHAR(60) NOT NULL UNIQUE,
  descricao VARCHAR(255) NOT NULL,
  categoria_id INT NULL,
  marca_modelo VARCHAR(120),
  estado VARCHAR(30) NOT NULL DEFAULT 'ativo',
  data_aquisicao DATETIME NULL,
  valor DECIMAL(12,2) NULL,
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

ALTER TABLE usuarios
  ADD COLUMN perfil_id INT NULL AFTER nome;

SET @has_up := (SELECT COUNT(*) FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = 'usuario_perfis');
SET @sql := IF(@has_up > 0,
    'UPDATE usuarios u
       LEFT JOIN (
         SELECT usuario_id, MIN(perfil_id) AS perfil_id
           FROM usuario_perfis
          GROUP BY usuario_id
       ) up ON up.usuario_id = u.id
       SET u.perfil_id = up.perfil_id;',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE usuarios
  ADD CONSTRAINT fk_usuarios_perfil
    FOREIGN KEY (perfil_id) REFERENCES perfis(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

CREATE INDEX idx_usuarios_perfil ON usuarios (perfil_id);

SET @has_up2 := (SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = 'usuario_perfis');
SET @sql2 := IF(@has_up2 > 0, 'DROP TABLE usuario_perfis;', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

INSERT IGNORE INTO setores (nome) VALUES
  ('Gabinete'),
  ('Administrativo'),
  ('Compras'),
  ('Financeiro'),
  ('TI'),
  ('Almoxarifado'),
  ('Manutenção');

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala de Servidores'
FROM setores s WHERE s.nome = 'TI';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Estações de Trabalho'
FROM setores s WHERE s.nome = 'TI';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Estoque Principal'
FROM setores s WHERE s.nome = 'Almoxarifado';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Depósito Auxiliar'
FROM setores s WHERE s.nome = 'Almoxarifado';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala 01'
FROM setores s WHERE s.nome = 'Administrativo';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala 02'
FROM setores s WHERE s.nome = 'Administrativo';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Gabinete do Secretário'
FROM setores s WHERE s.nome = 'Gabinete';

ALTER TABLE bens_patrimoniais
  ADD COLUMN tipo_eletronico VARCHAR(100) NULL AFTER marca_modelo;

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

SET @col := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'itens_estoque'
    AND COLUMN_NAME = 'produto_base'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE itens_estoque ADD COLUMN produto_base VARCHAR(120) NULL AFTER codigo',
  'SELECT ''produto_base já existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'itens_estoque'
    AND COLUMN_NAME = 'valor_unitario'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE itens_estoque ADD COLUMN valor_unitario DECIMAL(12,2) NULL AFTER unidade',
  'SELECT ''valor_unitario já existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'itens_estoque'
    AND COLUMN_NAME = 'criado_por_usuario_id'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE itens_estoque ADD COLUMN criado_por_usuario_id INT NULL AFTER categoria_id',
  'SELECT ''criado_por_usuario_id já existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'itens_estoque'
    AND CONSTRAINT_NAME = 'fk_itens_criado_por'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql := IF(@fk = 0,
  'ALTER TABLE itens_estoque ADD CONSTRAINT fk_itens_criado_por FOREIGN KEY (criado_por_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT ''FK fk_itens_criado_por já existe''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE entradas_estoque
  ADD COLUMN usuario_operacao_id INT NULL AFTER fornecedor_id;

ALTER TABLE entradas_estoque
  ADD CONSTRAINT fk_entrada_usuario
  FOREIGN KEY (usuario_operacao_id)
  REFERENCES usuarios(id)
  ON DELETE SET NULL ON UPDATE CASCADE;

CREATE INDEX idx_entradas_estoque_data ON entradas_estoque(data_entrada);

ALTER TABLE bens_patrimoniais
  ADD COLUMN tombamento_existente VARCHAR(60) NULL AFTER id_patrimonial;

ALTER TABLE bens_patrimoniais
  ADD COLUMN observacao TEXT NULL AFTER valor,
  ADD COLUMN imagem_path VARCHAR(255) NULL AFTER observacao;

CREATE TABLE IF NOT EXISTS licitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(100) UNIQUE NOT NULL,
  modalidade ENUM('CONCORRENCIA', 'TOMADA_DE_PRECOS', 'CONVITE', 'LEILAO', 'PREGAO', 'DIARIO_OFICIAL') NOT NULL,
  objeto TEXT NOT NULL,
  secretaria_id INT NOT NULL,
  data_abertura DATE NOT NULL,
  valor_estimado DECIMAL(15,2) NOT NULL,
  status ENUM('planejamento', 'publicacao', 'julgamento', 'homologacao', 'adjudicacao', 'encerrada') DEFAULT 'planejamento',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (secretaria_id) REFERENCES setores(id) ON DELETE RESTRICT,
  INDEX idx_numero (numero),
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lhs_cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  carga_horaria INT NOT NULL DEFAULT 0,
  ementa TEXT NULL,
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lhs_cursos_nome (nome),
  INDEX idx_lhs_cursos_ativo (ativo)
) ENGINE=InnoDB;

INSERT IGNORE INTO lhs_cursos (nome, carga_horaria, ementa, ativo) VALUES
  ('Informática Básica', 40, 'Introdução ao uso do computador, sistemas operacionais, navegação na internet e ferramentas de produtividade.', TRUE),
  ('Excel para Iniciantes', 20, 'Fundamentos do Microsoft Excel: criação de planilhas, fórmulas básicas e formatação.', TRUE),
  ('Internet e Redes Sociais', 16, 'Navegação segura na internet, uso consciente de redes sociais e proteção de dados pessoais.', TRUE);

CREATE TABLE IF NOT EXISTS lhs_materiais_didaticos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  nome_arquivo VARCHAR(255) NOT NULL,
  path VARCHAR(500) NOT NULL,
  uploaded_por INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_materiais_curso
    FOREIGN KEY (curso_id) REFERENCES lhs_cursos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lhs_materiais_usuario
    FOREIGN KEY (uploaded_por) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_lhs_materiais_curso (curso_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS licitacoes_fases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  fase VARCHAR(100) NOT NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NULL,
  prazo_dias INT NULL,
  responsavel_id INT NULL,
  observacoes TEXT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS licitacoes_documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  nome VARCHAR(255) NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  caminho_arquivo VARCHAR(500) NOT NULL,
  tamanho_arquivo BIGINT NULL,
  criado_por INT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_licitacao_id (licitacao_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tramitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  acao VARCHAR(255) NOT NULL,
  parecer TEXT NULL,
  usuario_id INT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_licitacao_id (licitacao_id),
  INDEX idx_usuario_id (usuario_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alertas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licitacao_id INT NOT NULL,
  tipo VARCHAR(100) NOT NULL,
  descricao TEXT NOT NULL,
  data_vencimento DATE NULL,
  status ENUM('ativo', 'resolvido', 'expirado') DEFAULT 'ativo',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
  INDEX idx_licitacao_id (licitacao_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lhs_alunos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(200) NOT NULL,
  cpf VARCHAR(14) NOT NULL UNIQUE,
  telefone VARCHAR(20) NULL,
  email VARCHAR(255) NULL,
  endereco VARCHAR(500) NULL,
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lhs_alunos_nome (nome),
  INDEX idx_lhs_alunos_cpf (cpf),
  INDEX idx_lhs_alunos_ativo (ativo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lhs_turmas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  professor_id INT NULL,
  nome VARCHAR(100) NOT NULL,
  horario_inicio TIME NOT NULL,
  horario_fim TIME NOT NULL,
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

CREATE TABLE IF NOT EXISTS lhs_aulas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  data_aula DATE NOT NULL,
  observacao TEXT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_aulas_turma
    FOREIGN KEY (turma_id) REFERENCES lhs_turmas(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT unq_lhs_aula_turma_data
    UNIQUE (turma_id, data_aula),
  INDEX idx_lhs_aulas_turma (turma_id),
  INDEX idx_lhs_aulas_data (data_aula)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lhs_presencas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aula_id INT NOT NULL,
  aluno_id INT NOT NULL,
  presente BOOLEAN NOT NULL DEFAULT FALSE,
  conteudo_ministrado TEXT NULL,
  registrado_por INT NULL,
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

CREATE TABLE IF NOT EXISTS lhs_inscricoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  nome VARCHAR(200) NOT NULL,
  cpf VARCHAR(14) NOT NULL,
  telefone VARCHAR(20) NULL,
  email VARCHAR(255) NULL,
  endereco VARCHAR(500) NULL,
  status ENUM('pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
  motivo_rejeicao VARCHAR(500) NULL,
  aluno_id INT NULL,
  turma_id INT NULL,
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

INSERT IGNORE INTO lhs_alunos (nome, cpf, telefone, email, endereco, ativo) VALUES
  ('Maria Silva Santos', '123.456.789-01', '(83) 99999-1111', 'maria.silva@email.com', 'Rua das Flores, 123 - Centro', TRUE),
  ('João Pedro Oliveira', '234.567.890-12', '(83) 99999-2222', 'joao.oliveira@email.com', 'Av. Principal, 456 - Bairro Novo', TRUE),
  ('Ana Carolina Costa', '345.678.901-23', '(83) 99999-3333', 'ana.costa@email.com', 'Rua da Paz, 789 - Alto Branco', TRUE),
  ('Pedro Henrique Lima', '456.789.012-34', '(83) 99999-4444', 'pedro.lima@email.com', 'Travessa do Sol, 321 - Liberdade', TRUE),
  ('Fernanda Souza Alves', '567.890.123-45', '(83) 99999-5555', 'fernanda.alves@email.com', 'Rua Nova, 654 - Prata', TRUE);

INSERT INTO lhs_turmas (curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, status)
SELECT 
  c.id,
  NULL,
  'Turma 2026.1 - Manhã',
  '08:00:00',
  '12:00:00',
  '2026-02-01',
  '2026-03-15',
  'aberta'
FROM lhs_cursos c WHERE c.nome = 'Letramento Digital'
  AND NOT EXISTS (SELECT 1 FROM lhs_turmas t WHERE t.nome = 'Turma 2026.1 - Manhã' AND t.curso_id = c.id);

INSERT INTO lhs_turmas (curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, status)
SELECT 
  c.id,
  NULL,
  'Turma 2026.1 - Tarde',
  '14:00:00',
  '18:00:00',
  '2026-02-10',
  '2026-04-10',
  'aberta'
FROM lhs_cursos c WHERE c.nome = 'Excel para Iniciantes'
  AND NOT EXISTS (SELECT 1 FROM lhs_turmas t WHERE t.nome = 'Turma 2026.1 - Tarde' AND t.curso_id = c.id);

INSERT INTO lhs_turmas (curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, status)
SELECT 
  c.id,
  NULL,
  'Turma 2026.1 - Noite',
  '19:00:00',
  '21:00:00',
  '2026-02-15',
  '2026-03-30',
  'aberta'
FROM lhs_cursos c WHERE c.nome = 'Internet e Redes Sociais'
  AND NOT EXISTS (SELECT 1 FROM lhs_turmas t WHERE t.nome = 'Turma 2026.1 - Noite' AND t.curso_id = c.id);

INSERT IGNORE INTO lhs_turma_alunos (turma_id, aluno_id, status)
SELECT t.id, a.id, 'matriculado'
FROM lhs_turmas t
CROSS JOIN lhs_alunos a
WHERE t.nome = 'Turma 2026.1 - Manhã' 
  AND a.nome IN ('Maria Silva Santos', 'João Pedro Oliveira');

INSERT IGNORE INTO lhs_turma_alunos (turma_id, aluno_id, status)
SELECT t.id, a.id, 'matriculado'
FROM lhs_turmas t
CROSS JOIN lhs_alunos a
WHERE t.nome = 'Turma 2026.1 - Tarde' 
  AND a.nome IN ('Ana Carolina Costa', 'Pedro Henrique Lima');

INSERT IGNORE INTO lhs_inscricoes (curso_id, nome, cpf, telefone, email, endereco, status)
SELECT 
  c.id,
  'Carlos Eduardo Ferreira',
  '678.901.234-56',
  '(83) 99999-6666',
  'carlos.ferreira@email.com',
  'Rua dos Pinheiros, 111 - Jardim',
  'pendente'
FROM lhs_cursos c WHERE c.nome = 'Letramento Digital';

INSERT IGNORE INTO lhs_inscricoes (curso_id, nome, cpf, telefone, email, endereco, status)
SELECT 
  c.id,
  'Juliana Martins Rocha',
  '789.012.345-67',
  '(83) 99999-7777',
  'juliana.rocha@email.com',
  'Av. das Américas, 222 - Centro',
  'pendente'
FROM lhs_cursos c WHERE c.nome = 'Excel para Iniciantes';

INSERT IGNORE INTO lhs_inscricoes (curso_id, nome, cpf, telefone, email, endereco, status)
SELECT 
  c.id,
  'Roberto Nascimento Silva',
  '890.123.456-78',
  '(83) 99999-8888',
  'roberto.silva@email.com',
  'Rua do Comércio, 333 - Bela Vista',
  'pendente'
FROM lhs_cursos c WHERE c.nome = 'Internet e Redes Sociais';

INSERT IGNORE INTO lhs_inscricoes (curso_id, nome, cpf, telefone, email, endereco, status)
SELECT 
  c.id,
  'Patrícia Gomes Andrade',
  '901.234.567-89',
  '(83) 99999-9999',
  'patricia.andrade@email.com',
  'Travessa Central, 444 - Mirante',
  'pendente'
FROM lhs_cursos c WHERE c.nome = 'Letramento Digital';

CREATE TABLE IF NOT EXISTS fases (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  slug VARCHAR(120) UNIQUE,
  ordem INT DEFAULT 0,
  descricao TEXT,
  ativo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE bens_patrimoniais
  ADD COLUMN current_fase_id INT NULL;

CREATE TABLE IF NOT EXISTS tramitacoes_bens (
  id INT PRIMARY KEY AUTO_INCREMENT,
  bem_id INT NOT NULL,
  from_fase_id INT NULL,
  to_fase_id INT NOT NULL,
  usuario_operacao_id INT NULL,
  comentario TEXT,
  anexo VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (bem_id),
  INDEX (to_fase_id),
  INDEX (usuario_operacao_id),
  CONSTRAINT fk_tram_bem FOREIGN KEY (bem_id) REFERENCES bens_patrimoniais(id) ON DELETE CASCADE
);

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'licitacoes'
    AND COLUMN_NAME = 'current_fase_id'
);

SET @sql := IF(@col_exists = 0,
  'ALTER TABLE licitacoes ADD COLUMN current_fase_id INT NULL',
  'SELECT \"current_fase_id já existe\"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS tramitacoes_licitacoes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  licitacao_id INT NOT NULL,
  from_fase_id INT NULL,
  to_fase_id INT NOT NULL,
  usuario_operacao_id INT NULL,
  comentario TEXT,
  anexo VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (licitacao_id),
  INDEX (to_fase_id),
  INDEX (usuario_operacao_id),
  CONSTRAINT fk_tram_licitacao FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contratos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(50) UNIQUE NOT NULL,
    ano_contrato YEAR DEFAULT (YEAR(NOW())),
    licitacao_id INT,
    fornecedor_id INT NOT NULL,
    objeto LONGTEXT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    valor_contratado DECIMAL(15, 2) NOT NULL,
    valor_executado DECIMAL(15, 2) DEFAULT 0.00,
    valor_saldo DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('planejamento', 'andamento', 'concluido', 'rescindido', 'suspenso') DEFAULT 'planejamento',
    observacoes LONGTEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    atualizado_por INT,
    INDEX idx_numero (numero),
    INDEX idx_status (status),
    INDEX idx_fornecedor (fornecedor_id),
    INDEX idx_licitacao (licitacao_id),
    INDEX idx_data_inicio (data_inicio),
    INDEX idx_data_fim (data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contratos_aditivos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    numero_aditivo VARCHAR(50) NOT NULL,
    tipo ENUM('prorrogacao', 'reajuste', 'reducao', 'ampliacao') NOT NULL,
    data_inicio DATE,
    data_fim DATE,
    valor_adicional DECIMAL(15, 2),
    percentual_reajuste DECIMAL(5, 2),
    status ENUM('planejado', 'aprovado', 'executado', 'cancelado') DEFAULT 'planejado',
    descricao LONGTEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    UNIQUE KEY uk_contrato_aditivo (contrato_id, numero_aditivo),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contratos_apostilamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    numero_apostilamento VARCHAR(50) NOT NULL,
    descricao LONGTEXT NOT NULL,
    data_apostilamento DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    INDEX idx_data (data_apostilamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contratos_fiscais (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_nomeacao DATE NOT NULL,
    data_termino DATE,
    portaria_numero VARCHAR(50),
    data_publicacao_portaria DATE,
    responsabilidades LONGTEXT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    UNIQUE KEY uk_contrato_usuario (contrato_id, usuario_id, data_nomeacao),
    INDEX idx_contrato (contrato_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_ativo (ativo),
    INDEX idx_data_nomeacao (data_nomeacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contratos_gestores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_nomeacao DATE NOT NULL,
    data_termino DATE,
    portaria_numero VARCHAR(50),
    data_publicacao_portaria DATE,
    responsabilidades LONGTEXT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    UNIQUE KEY uk_contrato_usuario (contrato_id, usuario_id, data_nomeacao),
    INDEX idx_contrato (contrato_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_ativo (ativo),
    INDEX idx_data_nomeacao (data_nomeacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empenhos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(50) UNIQUE NOT NULL,
    ano_empenho YEAR DEFAULT (YEAR(NOW())),
    contrato_id INT,
    licitacao_id INT,
    valor_empenhado DECIMAL(15, 2) NOT NULL,
    valor_liquidado DECIMAL(15, 2) DEFAULT 0.00,
    valor_pago DECIMAL(15, 2) DEFAULT 0.00,
    saldo DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('empenho', 'liquidado', 'pago', 'cancelado') DEFAULT 'empenho',
    descricao LONGTEXT,
    data_empenho DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    atualizado_por INT,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE SET NULL,
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE SET NULL,
    INDEX idx_numero (numero),
    INDEX idx_status (status),
    INDEX idx_contrato (contrato_id),
    INDEX idx_licitacao (licitacao_id),
    INDEX idx_data_empenho (data_empenho)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movimentacoes_empenho (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empenho_id INT NOT NULL,
    tipo ENUM('liquidacao', 'pagamento', 'cancelamento', 'anulacao', 'reforco') NOT NULL,
    valor DECIMAL(15, 2) NOT NULL,
    data_movimentacao DATE NOT NULL,
    numero_documento VARCHAR(50),
    descricao LONGTEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    FOREIGN KEY (empenho_id) REFERENCES empenhos(id) ON DELETE CASCADE,
    INDEX idx_empenho (empenho_id),
    INDEX idx_tipo (tipo),
    INDEX idx_data (data_movimentacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alertas_portaria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contrato_id INT NOT NULL,
    tipo ENUM('fiscal', 'gestor') NOT NULL,
    usuario_id INT,
    data_vencimento DATE NOT NULL,
    status ENUM('pendente', 'notificado', 'renovado', 'expirado') DEFAULT 'pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_contrato (contrato_id),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_data_vencimento (data_vencimento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispensas_inexigibilidades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(50) UNIQUE NOT NULL,
    ano INT DEFAULT (YEAR(NOW())),
    tipo ENUM('dispensa', 'inexigibilidade') NOT NULL,
    fornecedor_id INT NOT NULL,
    objeto LONGTEXT NOT NULL,
    valor DECIMAL(15, 2) NOT NULL,
    justificativa_legal LONGTEXT NOT NULL,
    justificativa_tecnica LONGTEXT NOT NULL,
    data_solicitacao DATE NOT NULL,
    data_aprovacao DATE,
    data_conclusao DATE,
    usuario_solicitante INT,
    usuario_aprovador INT,
    status ENUM('planejamento', 'analise_juridica', 'aprovacao', 'executando', 'encerrada', 'cancelada') DEFAULT 'planejamento',
    observacoes LONGTEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_numero (numero),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_fornecedor (fornecedor_id),
    INDEX idx_data_solicitacao (data_solicitacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispensa_documentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dispensa_id INT NOT NULL,
    documento_path VARCHAR(255) NOT NULL,
    documento_nome VARCHAR(255) NOT NULL,
    documento_tamanho INT,
    tipo ENUM('justificativa', 'parecer', 'aprovacao', 'outro') NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT,
    FOREIGN KEY (dispensa_id) REFERENCES dispensas_inexigibilidades(id) ON DELETE CASCADE,
    INDEX idx_dispensa (dispensa_id),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispensa_tramitacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dispensa_id INT NOT NULL,
    etapa VARCHAR(100) NOT NULL,
    acao VARCHAR(255) NOT NULL,
    parecer LONGTEXT,
    status_resultado ENUM('aprovado', 'reprovado', 'pendente_ajuste') NOT NULL,
    usuario_id INT,
    data_tramitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispensa_id) REFERENCES dispensas_inexigibilidades(id) ON DELETE CASCADE,
    INDEX idx_dispensa (dispensa_id),
    INDEX idx_etapa (etapa),
    INDEX idx_data (data_tramitacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contratos_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    tamanho_arquivo INT,
    criado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    INDEX idx_contrato_id (contrato_id),
    INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE lhs_aulas
ADD COLUMN conteudo_ministrado TEXT NULL AFTER observacao,
ADD COLUMN registrado_por INT NULL AFTER conteudo_ministrado;

ALTER TABLE lhs_aulas
ADD CONSTRAINT fk_lhs_aulas_registrado_por
  FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE lhs_presencas
DROP COLUMN conteudo_ministrado;

ALTER TABLE lhs_inscricoes
ADD COLUMN numero_protocolo VARCHAR(20) NOT NULL UNIQUE AFTER id,
ADD COLUMN turma_preferencia_id INT NULL AFTER curso_id,
ADD COLUMN aprovado_por INT NULL AFTER atualizado_em;

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

ALTER TABLE lhs_cursos
ADD COLUMN descricao TEXT NULL AFTER ementa,
ADD COLUMN icone VARCHAR(50) NULL AFTER descricao,
ADD COLUMN nivel VARCHAR(30) NOT NULL DEFAULT 'iniciante' AFTER icone,
ADD COLUMN pre_requisitos TEXT NULL AFTER nivel,
ADD COLUMN publico_alvo TEXT NULL AFTER pre_requisitos;

ALTER TABLE lhs_turmas
ADD COLUMN max_vagas INT NOT NULL DEFAULT 30 AFTER data_fim,
ADD COLUMN local_aula VARCHAR(200) NULL AFTER max_vagas,
ADD COLUMN dias_semana VARCHAR(100) NULL AFTER local_aula;

ALTER TABLE lhs_inscricoes
ADD COLUMN data_nascimento DATE NULL AFTER endereco,
ADD COLUMN escolaridade VARCHAR(50) NULL AFTER data_nascimento,
ADD COLUMN como_soube VARCHAR(100) NULL AFTER escolaridade,
ADD COLUMN turma_preferencia_horario VARCHAR(20) NULL AFTER como_soube,
ADD COLUMN necessidades_especiais TEXT NULL AFTER turma_preferencia_horario;

CREATE TABLE IF NOT EXISTS lhs_depoimentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT NULL,
  nome VARCHAR(200) NOT NULL,
  curso_nome VARCHAR(150) NOT NULL,
  texto TEXT NOT NULL,
  nota INT NOT NULL DEFAULT 5,
  aprovado BOOLEAN NOT NULL DEFAULT FALSE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_depoimentos_aluno
    FOREIGN KEY (aluno_id) REFERENCES lhs_alunos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_lhs_depoimentos_aprovado (aprovado)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lhs_faq (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pergunta VARCHAR(500) NOT NULL,
  resposta TEXT NOT NULL,
  ordem INT NOT NULL DEFAULT 0,
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lhs_faq_ativo_ordem (ativo, ordem)
) ENGINE=InnoDB;

UPDATE lhs_cursos SET
  descricao = 'Aprenda a usar o computador do zero! Desde ligar a máquina até navegar na internet com segurança.',
  icone = 'Monitor',
  nivel = 'iniciante',
  pre_requisitos = 'Nenhum',
  publico_alvo = 'Pessoas que nunca tiveram contato com computadores ou que possuem pouca experiência'
WHERE nome = 'Letramento Digital';

UPDATE lhs_cursos SET
  descricao = 'Domine as planilhas eletrônicas e organize sua vida financeira e profissional com o Excel.',
  icone = 'Table',
  nivel = 'iniciante',
  pre_requisitos = 'Conhecimento básico de informática',
  publico_alvo = 'Jovens e adultos que desejam aprender a usar planilhas para o trabalho ou vida pessoal'
WHERE nome = 'Excel para Iniciantes';

UPDATE lhs_cursos SET
  descricao = 'Navegue com segurança, aprenda a identificar golpes e utilize as redes sociais de forma consciente.',
  icone = 'Globe',
  nivel = 'iniciante',
  pre_requisitos = 'Conhecimento básico de informática',
  publico_alvo = 'Pessoas de todas as idades interessadas em usar a internet de forma segura e produtiva'
WHERE nome = 'Internet e Redes Sociais';

UPDATE lhs_turmas SET max_vagas = 25, local_aula = 'Lan House Social - Sala Principal', dias_semana = 'Segunda a Sexta';

INSERT INTO lhs_faq (pergunta, resposta, ordem, ativo) VALUES
('Os cursos são gratuitos?', 'Sim! Todos os cursos oferecidos pelo Lan House Social são 100% gratuitos. O projeto é uma iniciativa da SECTI para promover a inclusão digital na comunidade.', 1, TRUE),
('Preciso ter computador em casa?', 'Não. Todas as aulas são realizadas nos computadores do Lan House Social. Você só precisa comparecer às aulas no horário da sua turma.', 2, TRUE),
('Qual a idade mínima para se inscrever?', 'Os cursos são voltados para pessoas a partir de 14 anos. Menores de 18 anos precisam de autorização do responsável.', 3, TRUE),
('Como faço para acompanhar minha inscrição?', 'Após realizar sua inscrição, você receberá um número de protocolo. Use-o na página de consulta para verificar o status da sua inscrição a qualquer momento.', 4, TRUE),
('Recebo certificado ao final do curso?', 'Sim! Ao concluir o curso com frequência mínima de 75%, você recebe um certificado digital que pode ser validado online.', 5, TRUE),
('Posso me inscrever em mais de um curso?', 'Sim, você pode se inscrever em quantos cursos desejar, desde que os horários das turmas não sejam conflitantes.', 6, TRUE),
('Como são as aulas?', 'As aulas são presenciais, com teoria e muita prática. Cada aluno tem acesso a um computador individual durante as aulas, com acompanhamento do professor.', 7, TRUE),
('O que acontece se eu faltar?', 'É importante manter frequência mínima de 75% para obter o certificado. Caso precise faltar, avise o professor com antecedência.', 8, TRUE);

INSERT INTO lhs_depoimentos (nome, curso_nome, texto, nota, aprovado) VALUES
('Maria S.', 'Letramento Digital', 'Nunca tinha mexido em um computador antes. Hoje consigo fazer pesquisas, usar e-mail e até ajudo meus netos com as tarefas da escola. Mudou minha vida!', 5, TRUE),
('João P.', 'Excel para Iniciantes', 'Consegui uma promoção no trabalho depois que aprendi Excel. O curso é muito bem explicado, mesmo para quem não sabe nada.', 5, TRUE),
('Ana C.', 'Internet e Redes Sociais', 'Aprendi a identificar golpes na internet e agora me sinto muito mais segura navegando e usando o WhatsApp.', 5, TRUE);

SET @c1 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='lhs_inscricoes' AND COLUMN_NAME='numero_protocolo');
SET @s1 := IF(@c1=0, 'ALTER TABLE lhs_inscricoes ADD COLUMN numero_protocolo VARCHAR(30) NOT NULL AFTER id',
                   'SELECT "numero_protocolo já existe"');
PREPARE stmt1 FROM @s1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SET @c2 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='lhs_inscricoes' AND COLUMN_NAME='turma_preferencia_id');
SET @s2 := IF(@c2=0, 'ALTER TABLE lhs_inscricoes ADD COLUMN turma_preferencia_id INT NULL AFTER curso_id',
                   'SELECT "turma_preferencia_id já existe"');
PREPARE stmt2 FROM @s2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

SET @c3 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='lhs_inscricoes' AND COLUMN_NAME='aprovado_por');
SET @s3 := IF(@c3=0, 'ALTER TABLE lhs_inscricoes ADD COLUMN aprovado_por INT NULL AFTER atualizado_em',
                   'SELECT "aprovado_por já existe"');
PREPARE stmt3 FROM @s3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

START TRANSACTION;

ALTER TABLE lhs_cursos
  ADD UNIQUE KEY uq_lhs_cursos_nome (nome);

ALTER TABLE lhs_turmas
  ADD UNIQUE KEY uq_lhs_turmas_unica (curso_id, dias_semana, horario_inicio, horario_fim);

INSERT INTO lhs_cursos (nome, carga_horaria, ementa, descricao, nivel, icone, pre_requisitos, publico_alvo, ativo)
VALUES
('Manutenção de Computadores', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Rede de Computadores', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Jogos Digitais', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Design Gráfico', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Letramento Digital', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Letramento Avançado', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Banco de Dados', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Web Design', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Python', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Eletrônica Básica', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Canva/CapCut', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Java', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Fotografia', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('IA', 24, '', '', 'iniciante', NULL, NULL, NULL, 1),
('Segurança da Informação', 24, '', '', 'iniciante', NULL, NULL, NULL, 1)
ON DUPLICATE KEY UPDATE
  carga_horaria = VALUES(carga_horaria),
  nivel         = VALUES(nivel),
  ativo         = VALUES(ativo);

SET @LOCAL_AULA = 'Lan House Social - SECTI';
SET @MAX_VAGAS  = 30;

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Manutenção de Computadores - Seg 13:00-15:00', '13:00:00', '15:00:00', '2026-03-02', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Segunda-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Manutenção de Computadores'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Rede de Computadores - Seg 15:00-17:00', '15:00:00', '17:00:00', '2026-03-02', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Segunda-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Rede de Computadores'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'IA - Seg 08:00-10:00', '08:00:00', '10:00:00', '2026-03-02', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Segunda-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='IA'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'IA - Seg 10:00-12:00', '10:00:00', '12:00:00', '2026-03-02', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Segunda-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='IA'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Jogos Digitais - Ter 08:00-10:00', '08:00:00', '10:00:00', '2026-03-03', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Terça-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Jogos Digitais'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Design Gráfico - Ter 13:00-15:00', '13:00:00', '15:00:00', '2026-03-03', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Terça-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Design Gráfico'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Letramento Digital - Ter 10:00-12:00', '10:00:00', '12:00:00', '2026-03-03', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Terça-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Letramento Digital'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Fotografia - Ter 15:00-17:00', '15:00:00', '17:00:00', '2026-03-03', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Terça-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Fotografia'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Letramento Digital - Qua 08:00-10:00', '08:00:00', '10:00:00', '2026-03-04', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Quarta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Letramento Digital'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Banco de Dados - Qua 10:00-12:00', '10:00:00', '12:00:00', '2026-03-04', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Quarta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Banco de Dados'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Canva/CapCut - Qua 13:00-15:00', '13:00:00', '15:00:00', '2026-03-04', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Quarta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Canva/CapCut'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Segurança da Informação - Qua 15:00-17:00', '15:00:00', '17:00:00', '2026-03-04', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Quarta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Segurança da Informação'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Python - Qui 08:00-10:00', '08:00:00', '10:00:00', '2026-03-05', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Quinta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Python'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Web Design - Qui 13:00-15:00', '13:00:00', '15:00:00', '2026-03-05', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Quinta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Web Design'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Letramento Avançado - Qui 15:00-17:00', '15:00:00', '17:00:00', '2026-03-05', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Quinta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Letramento Avançado'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Eletrônica Básica - Sex 08:00-10:00', '08:00:00', '10:00:00', '2026-03-06', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Sexta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Eletrônica Básica'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Python - Sex 13:00-15:00', '13:00:00', '15:00:00', '2026-03-06', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Sexta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Python'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

INSERT INTO lhs_turmas
(curso_id, professor_id, nome, horario_inicio, horario_fim, data_inicio, data_fim, max_vagas, local_aula, dias_semana, status)
SELECT c.id, NULL, 'Java - Sex 15:00-17:00', '15:00:00', '17:00:00', '2026-03-06', NULL, @MAX_VAGAS, @LOCAL_AULA, 'Sexta-feira', 'aberta'
FROM lhs_cursos c WHERE c.nome='Java'
ON DUPLICATE KEY UPDATE
  nome=VALUES(nome), data_inicio=VALUES(data_inicio), data_fim=VALUES(data_fim),
  max_vagas=VALUES(max_vagas), local_aula=VALUES(local_aula), status=VALUES(status);

COMMIT;

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

START TRANSACTION;

INSERT INTO perfis (id, nome)
SELECT 2, 'SUPERADMIN'
WHERE NOT EXISTS (
    SELECT 1 FROM perfis WHERE nome = 'SUPERADMIN'
);

INSERT INTO usuarios (matricula, email, senha_hash, nome, perfil_id)
SELECT
    'ADMIN001',
    'admin_fernanda@local.com',
    '$2y$10$/nuSy/f5nxn2LwABn7/b3.8Igaa/.D.1Zp6e/TfywDW74gF8bSJQO',
    'Admin Fernanda',
    2
WHERE NOT EXISTS (
    SELECT 1 FROM usuarios WHERE email = 'admin_fernanda@local.com'
);

UPDATE usuarios
SET perfil_id = 2
WHERE email = 'admin_fernanda@local.com';

COMMIT;

ALTER TABLE usuarios
  ADD COLUMN data_nascimento DATE NULL AFTER perfil_id,
  ADD COLUMN celular VARCHAR(20) NULL AFTER data_nascimento,
  ADD COLUMN cep VARCHAR(20) NULL AFTER celular,
  ADD COLUMN cidade VARCHAR(100) NULL AFTER cep,
  ADD COLUMN bairro VARCHAR(100) NULL AFTER cidade,
  ADD COLUMN numero VARCHAR(20) NULL AFTER bairro,
  ADD COLUMN complemento VARCHAR(255) NULL AFTER numero;