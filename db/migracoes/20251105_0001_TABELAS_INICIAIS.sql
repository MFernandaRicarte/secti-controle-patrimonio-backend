-- UP
CREATE TABLE IF NOT EXISTS perfis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuario_perfis (
  usuario_id INT NOT NULL,
  perfil_id INT NOT NULL,
  PRIMARY KEY (usuario_id, perfil_id),
  CONSTRAINT fk_usuario_perfis_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_usuario_perfis_perfil  FOREIGN KEY (perfil_id)  REFERENCES perfis(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- DOWN (rollback)
-- DROP TABLE usuario_perfis;
-- DROP TABLE usuarios;
-- DROP TABLE perfis;
