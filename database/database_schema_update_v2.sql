-- Atualização do esquema da base de dados para PDI Angola v2.0

USE pdi_angola;

-- Tabela de Lotes
CREATE TABLE lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    polo_id INT NOT NULL,
    nome_lote VARCHAR(100) NOT NULL, -- Ex: Lote A1, Lote 12
    area_m2 DECIMAL(10, 2), -- Área do lote em metros quadrados
    status ENUM(
        'disponivel', 
        'ocupado', 
        'reservado', 
        'em_desenvolvimento'
    ) DEFAULT 'disponivel',
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (polo_id) REFERENCES polos_desenvolvimento(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lote_polo (polo_id, nome_lote)
);

-- Tabela de Empresas
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lote_id INT, -- Lote onde a empresa está instalada (pode ser NULL se não estiver em um lote específico)
    polo_id INT NOT NULL, -- Polo onde a empresa está localizada
    nome_empresa VARCHAR(255) NOT NULL,
    nif VARCHAR(20) UNIQUE, -- Número de Identificação Fiscal
    sector_id INT, -- Sector de atividade da empresa
    atividade_principal TEXT,
    num_funcionarios INT,
    contacto_principal VARCHAR(100),
    email_principal VARCHAR(100),
    telefone_principal VARCHAR(20),
    website VARCHAR(255),
    data_instalacao DATE,
    status ENUM(
        'ativa', 
        'inativa', 
        'em_implementacao', 
        'em_expansao'
    ) DEFAULT 'ativa',
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lote_id) REFERENCES lotes(id) ON DELETE SET NULL,
    FOREIGN KEY (polo_id) REFERENCES polos_desenvolvimento(id) ON DELETE CASCADE,
    FOREIGN KEY (sector_id) REFERENCES sectores(id) ON DELETE SET NULL
);

-- Adicionar colunas para gestão de lotes e empresas na tabela polos_desenvolvimento
ALTER TABLE polos_desenvolvimento
ADD COLUMN total_lotes INT DEFAULT 0, -- Número total de lotes no polo
ADD COLUMN lotes_ocupados INT DEFAULT 0, -- Número de lotes ocupados por empresas
ADD COLUMN total_empresas INT DEFAULT 0; -- Número total de empresas instaladas no polo

-- Trigger para atualizar total_lotes e lotes_ocupados em polos_desenvolvimento ao inserir um lote
DELIMITER //
CREATE TRIGGER after_lote_insert
AFTER INSERT ON lotes
FOR EACH ROW
BEGIN
    UPDATE polos_desenvolvimento
    SET 
        total_lotes = total_lotes + 1,
        lotes_ocupados = lotes_ocupados + CASE WHEN NEW.status = 'ocupado' THEN 1 ELSE 0 END
    WHERE id = NEW.polo_id;
END;
//
DELIMITER ;

-- Trigger para atualizar total_lotes e lotes_ocupados em polos_desenvolvimento ao atualizar um lote
DELIMITER //
CREATE TRIGGER after_lote_update
AFTER UPDATE ON lotes
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        UPDATE polos_desenvolvimento
        SET 
            lotes_ocupados = lotes_ocupados 
                             + CASE WHEN NEW.status = 'ocupado' THEN 1 ELSE 0 END 
                             - CASE WHEN OLD.status = 'ocupado' THEN 1 ELSE 0 END
        WHERE id = NEW.polo_id;
    END IF;
END;
//
DELIMITER ;

-- Trigger para atualizar total_lotes e lotes_ocupados em polos_desenvolvimento ao deletar um lote
DELIMITER //
CREATE TRIGGER after_lote_delete
AFTER DELETE ON lotes
FOR EACH ROW
BEGIN
    UPDATE polos_desenvolvimento
    SET 
        total_lotes = total_lotes - 1,
        lotes_ocupados = lotes_ocupados - CASE WHEN OLD.status = 'ocupado' THEN 1 ELSE 0 END
    WHERE id = OLD.polo_id;
END;
//
DELIMITER ;

-- Trigger para atualizar total_empresas em polos_desenvolvimento ao inserir uma empresa
DELIMITER //
CREATE TRIGGER after_empresa_insert
AFTER INSERT ON empresas
FOR EACH ROW
BEGIN
    UPDATE polos_desenvolvimento
    SET total_empresas = total_empresas + 1
    WHERE id = NEW.polo_id;
END;
//
DELIMITER ;

-- Trigger para atualizar total_empresas em polos_desenvolvimento ao deletar uma empresa
DELIMITER //
CREATE TRIGGER after_empresa_delete
AFTER DELETE ON empresas
FOR EACH ROW
BEGIN
    UPDATE polos_desenvolvimento
    SET total_empresas = total_empresas - 1
    WHERE id = OLD.polo_id;
END;
//
DELIMITER ;

-- Adicionar permissões para lotes e empresas nos níveis de acesso
UPDATE niveis_acesso SET permissoes = JSON_SET(permissoes,
    '$.manage_lotes', TRUE,
    '$.manage_empresas', TRUE
) WHERE nome = 'Administrador';

UPDATE niveis_acesso SET permissoes = JSON_SET(permissoes,
    '$.manage_lotes', TRUE,
    '$.manage_empresas', TRUE
) WHERE nome = 'Editor';

UPDATE niveis_acesso SET permissoes = JSON_SET(permissoes,
    '$.manage_lotes', FALSE,
    '$.manage_empresas', FALSE
) WHERE nome = 'Visualizador';

-- Adicionar alguns lotes e empresas de exemplo
INSERT INTO lotes (polo_id, nome_lote, area_m2, status, descricao) VALUES
(1, 'Lote A1', 5000.00, 'ocupado', 'Lote principal no Polo de Viana, ocupado pela Empresa A.'),
(1, 'Lote A2', 3500.00, 'disponivel', 'Lote disponível para novas instalações no Polo de Viana.'),
(1, 'Lote B1', 7000.00, 'reservado', 'Lote reservado para expansão da Empresa B.'),
(2, 'Lote C1', 4000.00, 'ocupado', 'Lote no PDIC, ocupado pela Empresa C.'),
(2, 'Lote C2', 2000.00, 'disponivel', 'Lote menor disponível no PDIC.'),
(3, 'Lote D1', 6000.00, 'em_desenvolvimento', 'Lote em Benguela, com infraestrutura em desenvolvimento.');

INSERT INTO empresas (lote_id, polo_id, nome_empresa, nif, sector_id, atividade_principal, num_funcionarios, contacto_principal, email_principal, telefone_principal, website, data_instalacao, status, observacoes) VALUES
(1, 1, 'Empresa A - Indústria Alimentar', '123456789LA', 1, 'Produção de lacticínios', 150, 'João Silva', 'joao.silva@empresaA.com', '+244912345678', 'www.empresaA.com', '2020-01-15', 'ativa', 'Empresa líder no setor de lacticínios.'),
(NULL, 1, 'Empresa B - Logística Angola', '987654321LA', 11, 'Serviços de logística e armazenagem', 80, 'Maria Santos', 'maria.santos@empresaB.com', '+244923456789', 'www.empresaB.com', '2019-05-20', 'ativa', 'Grande centro de distribuição no Polo de Viana.'),
(4, 2, 'Empresa C - Metalúrgica Cabinda', '112233445LA', 3, 'Fabricação de estruturas metálicas', 200, 'Pedro Costa', 'pedro.costa@empresaC.com', '+244934567890', 'www.empresaC.com', '2021-03-10', 'ativa', 'Especializada em projetos de grande porte.'),
(NULL, 3, 'Empresa D - Construções Benguela', '556677889LA', 5, 'Construção civil e obras públicas', 120, 'Ana Pereira', 'ana.pereira@empresaD.com', '+244945678901', 'www.empresaD.com', '2022-07-01', 'em_implementacao', 'Nova empresa em fase de instalação.');

-- Atualizar os totais nos polos após a inserção dos dados de exemplo
UPDATE polos_desenvolvimento pd
SET 
    total_lotes = (SELECT COUNT(*) FROM lotes WHERE polo_id = pd.id),
    lotes_ocupados = (SELECT COUNT(*) FROM lotes WHERE polo_id = pd.id AND status = 'ocupado'),
    total_empresas = (SELECT COUNT(*) FROM empresas WHERE polo_id = pd.id);


-- Adicionar coluna para perímetro (GeoJSON) dos polos
ALTER TABLE polos_desenvolvimento
    ADD COLUMN perimeter_geojson TEXT NULL COMMENT 'GeoJSON do perímetro do polo (Polygon/MultiPolygon)';

