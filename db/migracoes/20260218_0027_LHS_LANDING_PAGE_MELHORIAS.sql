ALTER TABLE lhs_cursos
ADD COLUMN descricao TEXT NULL AFTER ementa,
ADD COLUMN icone VARCHAR(50) NULL AFTER descricao,
ADD COLUMN nivel VARCHAR(30) NOT NULL DEFAULT 'iniciante' AFTER icone,
ADD COLUMN pre_requisitos TEXT NULL AFTER nivel,
ADD COLUMN publico_alvo TEXT NULL AFTER pre_requisitos;

ALTER TABLE lhs_turmas
ADD COLUMN max_vagas INT NOT NULL DEFAULT 30 AFTER data_fim,
ADD COLUMN local_aula VARCHAR(200) NULL AFTER max_vagas,
ADD COLUMN dias_semana VARCHAR(100) NULL AFTER local_aula;

ALTER TABLE lhs_inscricoes
ADD COLUMN data_nascimento DATE NULL AFTER endereco,
ADD COLUMN escolaridade VARCHAR(50) NULL AFTER data_nascimento,
ADD COLUMN como_soube VARCHAR(100) NULL AFTER escolaridade,
ADD COLUMN turma_preferencia_horario VARCHAR(20) NULL AFTER como_soube,
ADD COLUMN necessidades_especiais TEXT NULL AFTER turma_preferencia_horario;

CREATE TABLE IF NOT EXISTS lhs_depoimentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT NULL,
  nome VARCHAR(200) NOT NULL,
  curso_nome VARCHAR(150) NOT NULL,
  texto TEXT NOT NULL,
  nota INT NOT NULL DEFAULT 5,
  aprovado BOOLEAN NOT NULL DEFAULT FALSE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lhs_depoimentos_aluno
    FOREIGN KEY (aluno_id) REFERENCES lhs_alunos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_lhs_depoimentos_aprovado (aprovado)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lhs_faq (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pergunta VARCHAR(500) NOT NULL,
  resposta TEXT NOT NULL,
  ordem INT NOT NULL DEFAULT 0,
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lhs_faq_ativo_ordem (ativo, ordem)
) ENGINE=InnoDB;

UPDATE lhs_cursos SET
  descricao = 'Aprenda a usar o computador do zero! Desde ligar a máquina até navegar na internet com segurança.',
  icone = 'Monitor',
  nivel = 'iniciante',
  pre_requisitos = 'Nenhum',
  publico_alvo = 'Pessoas que nunca tiveram contato com computadores ou que possuem pouca experiência'
WHERE nome = 'Letramento Digital';

UPDATE lhs_cursos SET
  descricao = 'Domine as planilhas eletrônicas e organize sua vida financeira e profissional com o Excel.',
  icone = 'Table',
  nivel = 'iniciante',
  pre_requisitos = 'Conhecimento básico de informática',
  publico_alvo = 'Jovens e adultos que desejam aprender a usar planilhas para o trabalho ou vida pessoal'
WHERE nome = 'Excel para Iniciantes';

UPDATE lhs_cursos SET
  descricao = 'Navegue com segurança, aprenda a identificar golpes e utilize as redes sociais de forma consciente.',
  icone = 'Globe',
  nivel = 'iniciante',
  pre_requisitos = 'Conhecimento básico de informática',
  publico_alvo = 'Pessoas de todas as idades interessadas em usar a internet de forma segura e produtiva'
WHERE nome = 'Internet e Redes Sociais';

UPDATE lhs_turmas SET max_vagas = 25, local_aula = 'Lan House Social - Sala Principal', dias_semana = 'Segunda a Sexta';

INSERT INTO lhs_faq (pergunta, resposta, ordem, ativo) VALUES
('Os cursos são gratuitos?', 'Sim! Todos os cursos oferecidos pelo Lan House Social são 100% gratuitos. O projeto é uma iniciativa da SECTI para promover a inclusão digital na comunidade.', 1, TRUE),
('Preciso ter computador em casa?', 'Não. Todas as aulas são realizadas nos computadores do Lan House Social. Você só precisa comparecer às aulas no horário da sua turma.', 2, TRUE),
('Qual a idade mínima para se inscrever?', 'Os cursos são voltados para pessoas a partir de 14 anos. Menores de 18 anos precisam de autorização do responsável.', 3, TRUE),
('Como faço para acompanhar minha inscrição?', 'Após realizar sua inscrição, você receberá um número de protocolo. Use-o na página de consulta para verificar o status da sua inscrição a qualquer momento.', 4, TRUE),
('Recebo certificado ao final do curso?', 'Sim! Ao concluir o curso com frequência mínima de 75%, você recebe um certificado digital que pode ser validado online.', 5, TRUE),
('Posso me inscrever em mais de um curso?', 'Sim, você pode se inscrever em quantos cursos desejar, desde que os horários das turmas não sejam conflitantes.', 6, TRUE),
('Como são as aulas?', 'As aulas são presenciais, com teoria e muita prática. Cada aluno tem acesso a um computador individual durante as aulas, com acompanhamento do professor.', 7, TRUE),
('O que acontece se eu faltar?', 'É importante manter frequência mínima de 75% para obter o certificado. Caso precise faltar, avise o professor com antecedência.', 8, TRUE);

INSERT INTO lhs_depoimentos (nome, curso_nome, texto, nota, aprovado) VALUES
('Maria S.', 'Letramento Digital', 'Nunca tinha mexido em um computador antes. Hoje consigo fazer pesquisas, usar e-mail e até ajudo meus netos com as tarefas da escola. Mudou minha vida!', 5, TRUE),
('João P.', 'Excel para Iniciantes', 'Consegui uma promoção no trabalho depois que aprendi Excel. O curso é muito bem explicado, mesmo para quem não sabe nada.', 5, TRUE),
('Ana C.', 'Internet e Redes Sociais', 'Aprendi a identificar golpes na internet e agora me sinto muito mais segura navegando e usando o WhatsApp.', 5, TRUE);
