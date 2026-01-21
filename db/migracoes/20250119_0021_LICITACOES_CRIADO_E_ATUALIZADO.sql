ALTER TABLE licitacoes 
ADD COLUMN criado_por INT NULL AFTER status,
ADD COLUMN atualizado_por INT NULL AFTER criado_por,
ADD CONSTRAINT fk_licitacoes_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_licitacoes_atualizado_por FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
ADD INDEX idx_criado_por (criado_por),
ADD INDEX idx_atualizado_por (atualizado_por);

-- DOWN
-- ALTER TABLE licitacoes 
-- DROP FOREIGN KEY fk_licitacoes_atualizado_por,
-- DROP FOREIGN KEY fk_licitacoes_criado_por,
-- DROP COLUMN atualizado_por,
-- DROP COLUMN criado_por;