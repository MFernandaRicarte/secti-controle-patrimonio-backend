-- UP
ALTER TABLE bens_patrimoniais
  ADD COLUMN tombamento_existente VARCHAR(60) NULL AFTER id_patrimonial;

-- DOWN
ALTER TABLE bens_patrimoniais
  DROP COLUMN tombamento_existente;