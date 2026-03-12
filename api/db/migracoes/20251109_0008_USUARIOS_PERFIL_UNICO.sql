-- UP
-- 1) adiciona a coluna perfil_id em usuarios (inicialmente opcional)
ALTER TABLE usuarios
  ADD COLUMN perfil_id INT NULL AFTER nome;

-- 2) se existir a tabela de junção, migra o primeiro perfil de cada usuário
-- (caso haja mais de um, fica o menor perfil_id — ajustável conforme regra de negócio)
SET @has_up := (SELECT COUNT(*) FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = 'usuario_perfis');
-- só executa a migração se a tabela existir
SET @sql := IF(@has_up > 0,
    'UPDATE usuarios u
       LEFT JOIN (
         SELECT usuario_id, MIN(perfil_id) AS perfil_id
           FROM usuario_perfis
          GROUP BY usuario_id
       ) up ON up.usuario_id = u.id
       SET u.perfil_id = up.perfil_id;',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) cria a FK para perfis (se o perfil for apagado, zera o campo)
ALTER TABLE usuarios
  ADD CONSTRAINT fk_usuarios_perfil
    FOREIGN KEY (perfil_id) REFERENCES perfis(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 4) índice para consultas
CREATE INDEX idx_usuarios_perfil ON usuarios (perfil_id);

-- 5) remove a tabela de junção, se existir
SET @has_up2 := (SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = 'usuario_perfis');
SET @sql2 := IF(@has_up2 > 0, 'DROP TABLE usuario_perfis;', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- DOWN (referência, se algum dia quiser voltar)
-- CREATE TABLE usuario_perfis (
--   usuario_id INT NOT NULL,
--   perfil_id  INT NOT NULL,
--   PRIMARY KEY (usuario_id, perfil_id),
--   CONSTRAINT fk_up_user  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
--   CONSTRAINT fk_up_perfil FOREIGN KEY (perfil_id)  REFERENCES perfis(id)  ON DELETE CASCADE ON UPDATE CASCADE
-- );
-- UPDATE usuario_perfis up
-- JOIN usuarios u ON u.perfil_id = up.perfil_id AND up.usuario_id = u.id
-- SET up.usuario_id = u.id; -- repovoa
-- ALTER TABLE usuarios DROP FOREIGN KEY fk_usuarios_perfil;
-- ALTER TABLE usuarios DROP INDEX idx_usuarios_perfil;
-- ALTER TABLE usuarios DROP COLUMN perfil_id;
