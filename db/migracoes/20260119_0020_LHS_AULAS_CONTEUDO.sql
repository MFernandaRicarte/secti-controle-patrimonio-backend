-- ============================================================
-- LAN HOUSE SOCIAL - MIGRAÇÃO: AJUSTE AULAS CONTEUDO
-- Move conteudo_ministrado para a tabela de aulas
-- ============================================================

ALTER TABLE lhs_aulas 
ADD COLUMN IF NOT EXISTS conteudo_ministrado TEXT NULL COMMENT 'Conteúdo ministrado nesta aula' AFTER observacao;

ALTER TABLE lhs_aulas 
ADD COLUMN IF NOT EXISTS registrado_por INT NULL COMMENT 'ID do usuário (professor) que registrou' AFTER conteudo_ministrado;

ALTER TABLE lhs_aulas
ADD CONSTRAINT fk_lhs_aulas_registrado_por
FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE lhs_presencas
DROP COLUMN IF EXISTS conteudo_ministrado;
