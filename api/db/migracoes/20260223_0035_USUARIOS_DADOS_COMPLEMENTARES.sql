ALTER TABLE usuarios
  ADD COLUMN data_nascimento DATE NULL AFTER perfil_id,
  ADD COLUMN celular VARCHAR(20) NULL AFTER data_nascimento,
  ADD COLUMN cep VARCHAR(20) NULL AFTER celular,
  ADD COLUMN cidade VARCHAR(100) NULL AFTER cep,
  ADD COLUMN bairro VARCHAR(100) NULL AFTER cidade,
  ADD COLUMN numero VARCHAR(20) NULL AFTER bairro,
  ADD COLUMN complemento VARCHAR(255) NULL AFTER numero;