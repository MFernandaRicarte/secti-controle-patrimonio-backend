START TRANSACTION;

INSERT INTO perfis (id, nome)
SELECT 2, 'SUPERADMIN'
WHERE NOT EXISTS (
    SELECT 1 FROM perfis WHERE nome = 'SUPERADMIN'
);

INSERT INTO usuarios (matricula, email, senha_hash, nome, perfil_id)
SELECT
    'ADMIN001',
    'admin_fernanda@local.com',
    '$2y$10$/nuSy/f5nxn2LwABn7/b3.8Igaa/.D.1Zp6e/TfywDW74gF8bSJQO',
    'Admin Fernanda',
    2
WHERE NOT EXISTS (
    SELECT 1 FROM usuarios WHERE email = 'admin_fernanda@local.com'
);

UPDATE usuarios
SET perfil_id = 2
WHERE email = 'admin_fernanda@local.com';

COMMIT;