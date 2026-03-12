CREATE TABLE IF NOT EXISTS modelo_contrato (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL DEFAULT 'Modelo de Contrato',
  conteudo TEXT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insere um modelo padrão
INSERT INTO modelo_contrato (titulo, conteudo) VALUES (
  'Modelo de Contrato Padrão',
  'CONTRATO DE TRABALHO\n\nEu, {nome}, portador(a) da matrícula {matricula}, residente à {numero} {complemento}, {bairro}, {cidade} - {cep}, telefone {celular}, email {email}, nascido(a) em {data_nascimento}, venho por meio deste firmar contrato de trabalho com a SECTI.\n\nData: {criado_em}\n\nAssinatura: ____________________________'
);