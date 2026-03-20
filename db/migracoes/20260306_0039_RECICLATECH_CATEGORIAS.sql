-- UP
CREATE TABLE IF NOT EXISTS rct_categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  descricao VARCHAR(255) NULL,
  icone VARCHAR(60) NULL COMMENT 'nome do ícone (ex: lucide icon name)',
  exemplos VARCHAR(500) NULL COMMENT 'exemplos de itens aceitos, separados por vírgula',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  ordem INT NOT NULL DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO rct_categorias (nome, descricao, icone, exemplos, ativo, ordem) VALUES
  ('Computadores e Notebooks', 'Desktops, notebooks, all-in-ones e seus componentes', 'monitor', 'Desktop, Notebook, All-in-one, Placa-mãe, Processador, Memória RAM', 1, 1),
  ('Celulares e Tablets', 'Smartphones, tablets e leitores digitais', 'smartphone', 'Smartphone, Tablet, Leitor digital, Smartwatch', 1, 2),
  ('Impressoras e Periféricos', 'Impressoras, scanners, teclados, mouses e cabos', 'printer', 'Impressora, Scanner, Teclado, Mouse, Webcam, Fone de ouvido, Cabos', 1, 3),
  ('Monitores e TVs', 'Monitores, televisores e projetores', 'tv', 'Monitor LCD, Monitor LED, TV, Projetor', 1, 4),
  ('Equipamentos de Rede', 'Roteadores, switches, modems e access points', 'wifi', 'Roteador, Switch, Modem, Access Point, Repetidor', 1, 5),
  ('Baterias e Fontes', 'Baterias, carregadores e fontes de alimentação', 'battery-charging', 'Bateria de notebook, Carregador, Fonte ATX, Nobreak, Estabilizador', 1, 6),
  ('Peças e Componentes', 'Placas, HDs, SSDs e outros componentes avulsos', 'cpu', 'HD, SSD, Placa de vídeo, Placa de rede, Cooler, Gabinete', 1, 7),
  ('Outros Eletrônicos', 'Outros equipamentos eletrônicos não listados acima', 'package', 'Caixa de som, Câmera digital, Calculadora, Outros', 1, 8);

-- DOWN (rollback)
-- DROP TABLE IF EXISTS rct_categorias;
