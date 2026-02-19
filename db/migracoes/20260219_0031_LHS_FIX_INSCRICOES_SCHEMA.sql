-- numero_protocolo
SET @c1 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='lhs_inscricoes' AND COLUMN_NAME='numero_protocolo');
SET @s1 := IF(@c1=0, 'ALTER TABLE lhs_inscricoes ADD COLUMN numero_protocolo VARCHAR(30) NOT NULL AFTER id',
                   'SELECT "numero_protocolo já existe"');
PREPARE stmt1 FROM @s1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

-- turma_preferencia_id
SET @c2 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='lhs_inscricoes' AND COLUMN_NAME='turma_preferencia_id');
SET @s2 := IF(@c2=0, 'ALTER TABLE lhs_inscricoes ADD COLUMN turma_preferencia_id INT NULL AFTER curso_id',
                   'SELECT "turma_preferencia_id já existe"');
PREPARE stmt2 FROM @s2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- aprovado_por
SET @c3 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='lhs_inscricoes' AND COLUMN_NAME='aprovado_por');
SET @s3 := IF(@c3=0, 'ALTER TABLE lhs_inscricoes ADD COLUMN aprovado_por INT NULL AFTER atualizado_em',
                   'SELECT "aprovado_por já existe"');
PREPARE stmt3 FROM @s3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;