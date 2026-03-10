-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO 0022: SEEDS DE TESTE
-- Dados mínimos para popular e testar as funcionalidades
-- ============================================================

-- ===== ALUNOS DE EXEMPLO =====
INSERT IGNORE INTO lhs_alunos (nome, cpf, telefone, email, endereco, ativo) VALUES
  ('Maria Silva Santos', '123.456.789-01', '(83) 99999-1111', 'maria.silva@email.com', 'Rua das Flores, 123 - Centro', TRUE),
  ('João Pedro Oliveira', '234.567.890-12', '(83) 99999-2222', 'joao.oliveira@email.com', 'Av. Principal, 456 - Bairro Novo', TRUE),
  ('Ana Carolina Costa', '345.678.901-23', '(83) 99999-3333', 'ana.costa@email.com', 'Rua da Paz, 789 - Alto Branco', TRUE),
  ('Pedro Henrique Lima', '456.789.012-34', '(83) 99999-4444', 'pedro.lima@email.com', 'Travessa do Sol, 321 - Liberdade', TRUE),
  ('Fernanda Souza Alves', '567.890.123-45', '(83) 99999-5555', 'fernanda.alves@email.com', 'Rua Nova, 654 - Prata', TRUE);

-- ===== TURMAS DE EXEMPLO =====
-- Turma de Letramento Digital
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

-- Turma de Excel para Iniciantes
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

-- Turma de Internet e Redes Sociais
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

-- ===== MATRÍCULAS DE EXEMPLO =====
-- Matricular alguns alunos nas turmas existentes
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

-- ===== INSCRIÇÕES DE EXEMPLO (PENDENTES) =====
-- Simular inscrições públicas aguardando aprovação
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
