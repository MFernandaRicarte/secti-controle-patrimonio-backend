-- UP: ajustes em bens_patrimoniais para a tela de listagem

ALTER TABLE bens_patrimoniais
  ADD COLUMN tipo_eletronico VARCHAR(100) NULL AFTER marca_modelo;

