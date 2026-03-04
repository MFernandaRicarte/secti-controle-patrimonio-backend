ALTER TABLE reciclatech_solicitacoes
  ADD COLUMN atualizado_por INT NULL;

ALTER TABLE reciclatech_solicitacoes
  ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE reciclatech_solicitacoes
  ADD CONSTRAINT fk_reciclatech_solicitacoes_atualizado_por
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- DOWN (rollback)
-- ALTER TABLE reciclatech_solicitacoes DROP FOREIGN KEY fk_reciclatech_solicitacoes_atualizado_por;
-- ALTER TABLE reciclatech_solicitacoes DROP COLUMN atualizado_por;
-- ALTER TABLE reciclatech_solicitacoes DROP COLUMN atualizado_em;