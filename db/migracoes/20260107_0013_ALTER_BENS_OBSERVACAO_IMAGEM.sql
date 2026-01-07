-- UP
ALTER TABLE bens_patrimoniais
  ADD COLUMN observacao TEXT NULL AFTER valor,
  ADD COLUMN imagem_path VARCHAR(255) NULL AFTER observacao;

-- DOWN
ALTER TABLE bens_patrimoniais
  DROP COLUMN imagem_path,
  DROP COLUMN observacao;
