-- 20260219_0033_LHS_UPSERT_CURSOS_TURMAS.sql
-- Seed/Upsert seguro para produção: NÃO apaga inscrições.
-- Garante cursos e turmas do Lan House Social conforme grade/horários.
-- Requer MariaDB/MySQL com suporte a ON DUPLICATE KEY UPDATE.

START TRANSACTION;

-- =========================================================
-- 1) Garantir unicidade para permitir UPSERT
-- =========================================================

-- Cursos: nome único
ALTER TABLE lhs_cursos
  ADD UNIQUE KEY uq_lhs_cursos_nome (nome);

-- Turmas: uma turma é única por curso + dia + horários
ALTER TABLE lhs_turmas
  ADD UNIQUE KEY uq_lhs_turmas_unica (curso_id, dias_semana, horario_inicio, horario_fim);

-- =========================================================
-- 2) UPSERT de cursos (todos iniciante, ativo, 24h)
-- =========================================================

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

-- =========================================================
-- 3) UPSERT de turmas (conforme imagem)
-- =========================================================

-- Ajuste se quiser:
SET @LOCAL_AULA = 'Lan House Social - SECTI';
SET @MAX_VAGAS  = 30;

-- Datas base apenas para exibição. Pode ser alterado depois.
-- (Seg) 2026-03-02, (Ter) 2026-03-03, (Qua) 2026-03-04, (Qui) 2026-03-05, (Sex) 2026-03-06

-- -------------------------
-- SEGUNDA-FEIRA
-- -------------------------

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

-- -------------------------
-- TERÇA-FEIRA
-- -------------------------

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

-- -------------------------
-- QUARTA-FEIRA
-- -------------------------

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

-- -------------------------
-- QUINTA-FEIRA
-- -------------------------

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

-- -------------------------
-- SEXTA-FEIRA
-- -------------------------

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