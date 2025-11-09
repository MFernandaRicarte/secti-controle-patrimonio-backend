ALTER TABLE usuarios
  ADD COLUMN matricula VARCHAR(60) NULL UNIQUE AFTER id;

UPDATE usuarios
   SET matricula = CONCAT('M', LPAD(id, 6, '0'))
 WHERE matricula IS NULL;

ALTER TABLE usuarios
  MODIFY COLUMN matricula VARCHAR(60) NOT NULL UNIQUE;

DROP TRIGGER IF EXISTS trg_usuarios_no_matricula_update;
DELIMITER $$
CREATE TRIGGER trg_usuarios_no_matricula_update
BEFORE UPDATE ON usuarios
FOR EACH ROW
BEGIN
  IF NEW.matricula <> OLD.matricula THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'A matrícula não pode ser alterada';
  END IF;
END$$
DELIMITER ;

-- DOWN (referência)
-- DELIMITER $$
-- DROP TRIGGER IF EXISTS trg_usuarios_no_matricula_update$$
-- DELIMITER ;
-- ALTER TABLE usuarios DROP COLUMN matricula;
