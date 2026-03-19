-- SEED INICIAL DE SETORES E SALAS

-- SETORES PRINCIPAIS
INSERT IGNORE INTO setores (nome) VALUES
  ('Gabinete'),
  ('Administrativo'),
  ('Compras'),
  ('Financeiro'),
  ('TI'),
  ('Almoxarifado'),
  ('Manutenção');

-- SALAS POR SETOR
-- TI
INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala de Servidores'
FROM setores s WHERE s.nome = 'TI';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Estações de Trabalho'
FROM setores s WHERE s.nome = 'TI';

-- Almoxarifado
INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Estoque Principal'
FROM setores s WHERE s.nome = 'Almoxarifado';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Depósito Auxiliar'
FROM setores s WHERE s.nome = 'Almoxarifado';

-- Administrativo
INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala 01'
FROM setores s WHERE s.nome = 'Administrativo';

INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Sala 02'
FROM setores s WHERE s.nome = 'Administrativo';

-- Gabinete
INSERT IGNORE INTO salas (setor_id, nome)
SELECT s.id, 'Gabinete do Secretário'
FROM setores s WHERE s.nome = 'Gabinete';
