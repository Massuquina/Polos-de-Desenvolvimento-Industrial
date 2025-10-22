-- Script para inserir dados iniciais das províncias, municípios e polos de Angola

USE pdi_angola;

-- Inserir províncias
INSERT INTO provincias (nome, capital) VALUES
('Bengo', 'Caxito'),
('Benguela', 'Benguela'),
('Bié', 'Cuíto'),
('Cabinda', 'Cabinda'),
('Cuando-Cubango', 'Menongue'),
('Cuanza Norte', 'Nadalatando'),
('Cuanza Sul', 'Sumbe'),
('Cunene', 'Ondijiva'),
('Huambo', 'Huambo'),
('Huíla', 'Lubango'),
('Luanda', 'Luanda'),
('Lunda Norte', 'Dundo'),
('Lunda Sul', 'Saurimo'),
('Malanje', 'Malanje'),
('Moxico', 'Luena'),
('Namibe', 'Moçâmedes'),
('Uíge', 'Uíge'),
('Zaire', 'Mabanza Congo');

-- Inserir municípios por província
-- Bengo
INSERT INTO municipios (nome, provincia_id) VALUES
('Ambriz', 1), ('Bula Atumba', 1), ('Dande', 1), ('Dembos', 1), ('Nambuangongo', 1), ('Pango Aluquém', 1);

-- Benguela
INSERT INTO municipios (nome, provincia_id) VALUES
('Balombo', 2), ('Baía Farta', 2), ('Benguela', 2), ('Bocoio', 2), ('Caimbambo', 2), ('Catumbela', 2), ('Chongorói', 2), ('Cubal', 2), ('Ganda', 2), ('Lobito', 2);

-- Bié
INSERT INTO municipios (nome, provincia_id) VALUES
('Andulo', 3), ('Camacupa', 3), ('Catabola', 3), ('Chinguar', 3), ('Chitembo', 3), ('Cuemba', 3), ('Cunhinga', 3), ('Cuíto', 3), ('Nharea', 3);

-- Cabinda
INSERT INTO municipios (nome, provincia_id) VALUES
('Belize', 4), ('Buco-Zau', 4), ('Cabinda', 4), ('Cacongo', 4);

-- Cuando-Cubango
INSERT INTO municipios (nome, provincia_id) VALUES
('Calai', 5), ('Cuangar', 5), ('Cuchi', 5), ('Cuito Cuanavale', 5), ('Dirico', 5), ('Mavinga', 5), ('Menongue', 5), ('Nancova', 5), ('Rivungo', 5);

-- Cuanza Norte
INSERT INTO municipios (nome, provincia_id) VALUES
('Ambaca', 6), ('Banga', 6), ('Bolongongo', 6), ('Cambambe', 6), ('Cazengo', 6), ('Golungo Alto', 6), ('Gonguembo', 6), ('Lucala', 6), ('Quiculungo', 6), ('Samba Caju', 6);

-- Cuanza Sul
INSERT INTO municipios (nome, provincia_id) VALUES
('Amboim', 7), ('Cassongue', 7), ('Cela', 7), ('Conda', 7), ('Ebo', 7), ('Libolo', 7), ('Mussende', 7), ('Porto Amboim', 7), ('Quibala', 7), ('Quilenda', 7), ('Seles', 7), ('Sumbe', 7);

-- Cunene
INSERT INTO municipios (nome, provincia_id) VALUES
('Cahama', 8), ('Cuanhama', 8), ('Curoca', 8), ('Cuvelai', 8), ('Namacunde', 8), ('Ombadja', 8);

-- Huambo
INSERT INTO municipios (nome, provincia_id) VALUES
('Bailundo', 9), ('Cachiungo', 9), ('Caála', 9), ('Ecunha', 9), ('Huambo', 9), ('Londuimbali', 9), ('Longonjo', 9), ('Mungo', 9), ('Chicala-Choloanga', 9), ('Chinjenje', 9), ('Ucuma', 9);

-- Huíla
INSERT INTO municipios (nome, provincia_id) VALUES
('Caconda', 10), ('Cacula', 10), ('Caluquembe', 10), ('Chiange', 10), ('Chibia', 10), ('Chicomba', 10), ('Chipindo', 10), ('Cuvango', 10), ('Humpata', 10), ('Jamba', 10), ('Lubango', 10), ('Matala', 10), ('Quilengues', 10), ('Quipungo', 10);

-- Luanda
INSERT INTO municipios (nome, provincia_id) VALUES
('Belas', 11), ('Cacuaco', 11), ('Cazenga', 11), ('Ícolo e Bengo', 11), ('Luanda', 11), ('Quilamba Quiaxi', 11), ('Quissama', 11), ('Talatona', 11), ('Viana', 11);

-- Lunda Norte
INSERT INTO municipios (nome, provincia_id) VALUES
('Cambulo', 12), ('Capenda-Camulemba', 12), ('Caungula', 12), ('Chitato', 12), ('Cuango', 12), ('Cuílo', 12), ('Lóvua', 12), ('Lubalo', 12), ('Lucapa', 12), ('Xá-Muteba', 12);

-- Lunda Sul
INSERT INTO municipios (nome, provincia_id) VALUES
('Cacolo', 13), ('Dala', 13), ('Muconda', 13), ('Saurimo', 13);

-- Malanje
INSERT INTO municipios (nome, provincia_id) VALUES
('Cacuso', 14), ('Calandula', 14), ('Cambundi-Catembo', 14), ('Cangandala', 14), ('Caombo', 14), ('Cuaba Nzoji', 14), ('Cunda-Dia-Baze', 14), ('Luquembo', 14), ('Malanje', 14), ('Marimba', 14), ('Massango', 14), ('Mucari', 14), ('Quela', 14), ('Quirima', 14);

-- Moxico
INSERT INTO municipios (nome, provincia_id) VALUES
('Alto Zambeze', 15), ('Bundas', 15), ('Camanongue', 15), ('Léua', 15), ('Luau', 15), ('Luacano', 15), ('Luchazes', 15), ('Cameia', 15), ('Moxico', 15);

-- Namibe
INSERT INTO municipios (nome, provincia_id) VALUES
('Bibala', 16), ('Camucuio', 16), ('Moçâmedes', 16), ('Tômbua', 16), ('Virei', 16);

-- Uíge
INSERT INTO municipios (nome, provincia_id) VALUES
('Alto Cauale', 17), ('Ambuíla', 17), ('Bembe', 17), ('Buengas', 17), ('Bungo', 17), ('Damba', 17), ('Milunga', 17), ('Mucaba', 17), ('Negage', 17), ('Puri', 17), ('Quimbele', 17), ('Quitexe', 17), ('Sanza Pombo', 17), ('Songo', 17), ('Uíge', 17), ('Zombo', 17);

-- Zaire
INSERT INTO municipios (nome, provincia_id) VALUES
('Cuimba', 18), ('Mabanza Congo', 18), ('Nóqui', 18), ('Nezeto', 18), ('Soio', 18), ('Tomboco', 18);

-- Inserir Polos de Desenvolvimento Industrial
INSERT INTO polos_desenvolvimento (nome, provincia_id, municipio_id, endereco, latitude, longitude, area_total, descricao, status) VALUES
('Polo Industrial de Viana', 11, 
 (SELECT id FROM municipios WHERE nome = 'Viana' AND provincia_id = 11), 
 'Estrada de Catete, Km 25, Viana', 
 -8.9344, 13.4067, 2350.00, 
 'Zona de desenvolvimento de projectos industriais que ocupa uma área total de 2.350 hectares', 
 'ativo'),

('Polo de Desenvolvimento Industrial da Catumbela', 2, 
 (SELECT id FROM municipios WHERE nome = 'Catumbela' AND provincia_id = 2), 
 'Estrada Nacional 100, junto a Ponte 4 de Abril', 
 NULL, NULL, NULL, 
 'Polo criado por decreto aprovado pelo Conselho de Ministros em 1999', 
 'ativo'),

('Polo de Desenvolvimento Industrial de Lucala', 6, 
 (SELECT id FROM municipios WHERE nome = 'Lucala' AND provincia_id = 6), 
 'Município de Lucala, Cuanza Norte', 
 -9.2610, 15.2399, 853.00, 
 'Polo situado a cerca de 200 km de Luanda, área de 853 hectares', 
 'em_construcao'),

('Polo de Desenvolvimento Industrial da Caála', 9, 
 (SELECT id FROM municipios WHERE nome = 'Caála' AND provincia_id = 9), 
 'Província do Huambo', 
 NULL, NULL, NULL, 
 'Polo localizado na província do Huambo com vasta área disponível', 
 'ativo'),

('Polo Industrial do Fútila', 4, 
 (SELECT id FROM municipios WHERE nome = 'Cabinda' AND provincia_id = 4), 
 'Província de Cabinda', 
 NULL, NULL, NULL, 
 'Polo de desenvolvimento industrial na província de Cabinda', 
 'planejado');


INSERT INTO polos_desenvolvimento (
  nome, provincia_id, municipio_id, endereco, latitude, longitude, area_total, status, descricao
)
VALUES (
  '22 Logs',
  11,
  (SELECT id FROM municipios WHERE nome = 'Viana' AND provincia_id = 11),
  'Estrada de Catete, Km 22, Viana',
  -8.9148, 13.4279,
  220.0,
  'ativo',
  'Centro logístico de distribuição e armazenagem com foco em bens de consumo e materiais industriais, próximo às principais vias de transporte.'
);



INSERT INTO polos_desenvolvimento (
  nome, provincia_id, municipio_id, endereco, latitude, longitude, area_total, status, descricao
)
VALUES (
  'IDIIA',
  11,
  (SELECT id FROM municipios WHERE nome = 'Viana' AND provincia_id = 11),
  'Largo 17 de Setembro 3, Luanda',
  -8.8029911423697, 13.243508179395976,
  220.0,
  'ativo',
  'Ministério da Indústria e Comercio.'
);

UPDATE polos_desenvolvimento
SET perimeter_geojson = '{
  "type": "Polygon",
  "coordinates": [
    [
      [13.4220, -8.9180],
      [13.4320, -8.9180],
      [13.4320, -8.9080],
      [13.4220, -8.9080],
      [13.4220, -8.9180]
    ]
  ]
}'
WHERE id = 7;