-- Base de dados para gestão de infraestruturas em Polos de Desenvolvimento Industrial de Angola
-- Compatível com MySQL/MariaDB para WAMP Server

CREATE DATABASE IF NOT EXISTS pdi_angola CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pdi_angola;

-- Tabela de províncias
CREATE TABLE provincias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    capital VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de municípios
CREATE TABLE municipios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    provincia_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_municipio_provincia (nome, provincia_id)
);

-- Tabela de comunas
CREATE TABLE comunas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    municipio_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (municipio_id) REFERENCES municipios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_comuna_municipio (nome, municipio_id)
);

-- Tabela de sectores (para categorização das infraestruturas)
CREATE TABLE sectores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de polos de desenvolvimento industrial
CREATE TABLE polos_desenvolvimento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    provincia_id INT NOT NULL,
    municipio_id INT NOT NULL,
    comuna_id INT,
    endereco TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    area_total DECIMAL(10, 2), -- em hectares
    descricao TEXT,
    status ENUM('ativo', 'em_construcao', 'planejado', 'inativo') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provincia_id) REFERENCES provincias(id),
    FOREIGN KEY (municipio_id) REFERENCES municipios(id),
    FOREIGN KEY (comuna_id) REFERENCES comunas(id)
);

-- Tabela de níveis de acesso
CREATE TABLE niveis_acesso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    permissoes JSON, -- Armazena as permissões em formato JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL, -- Hash da senha
    nivel_acesso_id INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    ultimo_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nivel_acesso_id) REFERENCES niveis_acesso(id)
);

-- Tabela principal de infraestruturas
CREATE TABLE infraestruturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Localização
    polo_id INT NOT NULL,
    provincia_id INT NOT NULL,
    municipio_id INT NOT NULL,
    comuna_id INT,
    bairro_local VARCHAR(200),
    rua_numero VARCHAR(200),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    -- Documentação
    proprietario_terreno ENUM('publico', 'privado') DEFAULT 'publico',
    ref_registo_terreno VARCHAR(100),
    data_registo_terreno DATE,
    ref_contrato_empreitada VARCHAR(100),
    data_contrato_empreitada DATE,
    ref_contrato_concessao VARCHAR(100),
    data_contrato_concessao DATE,
    ref_registo_patrimonio VARCHAR(100),
    data_registo_patrimonio DATE,
    programa VARCHAR(200),
    
    -- Dimensões e confrontações
    area DECIMAL(10, 2),
    confrontacao_norte TEXT,
    confrontacao_sul TEXT,
    confrontacao_este TEXT,
    confrontacao_oeste TEXT,
    
    -- Estado do espaço
    pre_existencias TEXT,
    ocupacao_indevida TEXT,
    condicoes_solos TEXT,
    condicao_vegetacao TEXT,
    vedacoes TEXT,
    
    -- Infraestruturas públicas
    rede_hidraulica ENUM('sim', 'nao', 'parcial') DEFAULT 'nao',
    rede_esgotos_domesticos ENUM('sim', 'nao', 'parcial') DEFAULT 'nao',
    rede_esgotos_pluviais ENUM('sim', 'nao', 'parcial') DEFAULT 'nao',
    rede_eletrica ENUM('sim', 'nao', 'parcial') DEFAULT 'nao',
    vias_acesso ENUM('sim', 'nao', 'parcial') DEFAULT 'nao',
    
    -- Contactos
    nome_representante VARCHAR(100),
    email_representante VARCHAR(100),
    telefone_representante VARCHAR(20),
    admin_municipal VARCHAR(100),
    proprietario VARCHAR(100),
    gestor VARCHAR(100),
    empreiteiro VARCHAR(100),
    fiscal VARCHAR(100),
    
    -- Informações complementares
    ano_construcao YEAR,
    aquisicao VARCHAR(200),
    tipologia VARCHAR(100),
    sector_id INT,
    estado_atual ENUM('excelente', 'bom', 'regular', 'mau', 'ruina') DEFAULT 'bom',
    capacidade_instalada VARCHAR(200),
    produtos TEXT,
    constrangimentos TEXT,
    observacoes TEXT,
    
    -- Metadados
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (polo_id) REFERENCES polos_desenvolvimento(id),
    FOREIGN KEY (provincia_id) REFERENCES provincias(id),
    FOREIGN KEY (municipio_id) REFERENCES municipios(id),
    FOREIGN KEY (comuna_id) REFERENCES comunas(id),
    FOREIGN KEY (sector_id) REFERENCES sectores(id),
    FOREIGN KEY (created_by) REFERENCES usuarios(id),
    FOREIGN KEY (updated_by) REFERENCES usuarios(id)
);

-- Tabela para envolvente exterior
CREATE TABLE envolvente_exterior (
    id INT AUTO_INCREMENT PRIMARY KEY,
    infraestrutura_id INT NOT NULL,
    espaco_parqueamento_viaturas DECIMAL(10, 2),
    espaco_parqueamento_contentor DECIMAL(10, 2),
    disponibilidade_agua_autonoma DECIMAL(10, 2),
    estacao_tratamento_agua DECIMAL(10, 2),
    depositos_bombas_combustivel DECIMAL(10, 2),
    grupos_electrigeneos_kva DECIMAL(10, 2),
    telheiros_cobertos DECIMAL(10, 2),
    local_oficinas_automoveis DECIMAL(10, 2),
    locais_descarga_cais INT,
    balancas_pesagem_viaturas INT,
    pavimento_alcatroado DECIMAL(10, 2),
    pavimento_terra_batida DECIMAL(10, 2),
    espaco_livre_ajardinado DECIMAL(10, 2),
    local_controlo_acesso ENUM('sim', 'nao') DEFAULT 'nao',
    iluminacao_exterior ENUM('sim', 'nao') DEFAULT 'nao',
    sinaletica_transito ENUM('sim', 'nao') DEFAULT 'nao',
    sinaletica_higiene_seguranca ENUM('sim', 'nao') DEFAULT 'nao',
    publicidade ENUM('sim', 'nao') DEFAULT 'nao',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (infraestrutura_id) REFERENCES infraestruturas(id) ON DELETE CASCADE
);

-- Tabela para edificios
CREATE TABLE edificios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    infraestrutura_id INT NOT NULL,
    escritorios DECIMAL(10, 2),
    cozinha_refeitorio DECIMAL(10, 2),
    bar_cafetaria DECIMAL(10, 2),
    instalacoes_sanitarias DECIMAL(10, 2),
    dormitorios_quartos DECIMAL(10, 2),
    bancadas_venda DECIMAL(10, 2),
    espacos_fechados_venda DECIMAL(10, 2),
    armazenagem_secos DECIMAL(10, 2),
    armazenagem_refrigerada DECIMAL(10, 2),
    armazenagem_congelacao DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (infraestrutura_id) REFERENCES infraestruturas(id) ON DELETE CASCADE
);

-- Tabela para equipamentos
CREATE TABLE equipamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    infraestrutura_id INT NOT NULL,
    gruas_manobra_contentores ENUM('sim', 'nao') DEFAULT 'nao',
    empilhadores ENUM('sim', 'nao') DEFAULT 'nao',
    linhas_lavagem_calibragem ENUM('sim', 'nao') DEFAULT 'nao',
    geradores_gelo ENUM('sim', 'nao') DEFAULT 'nao',
    equipamento_cozinha ENUM('sim', 'nao') DEFAULT 'nao',
    palamenta_cozinha ENUM('sim', 'nao') DEFAULT 'nao',
    equipamento_bar_cafetaria ENUM('sim', 'nao') DEFAULT 'nao',
    palamenta_bar_cafetaria ENUM('sim', 'nao') DEFAULT 'nao',
    mobiliario_escritorio ENUM('sim', 'nao') DEFAULT 'nao',
    mobiliario_refeitorio ENUM('sim', 'nao') DEFAULT 'nao',
    mobiliario_bar_cafetaria ENUM('sim', 'nao') DEFAULT 'nao',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (infraestrutura_id) REFERENCES infraestruturas(id) ON DELETE CASCADE
);

-- Tabela para log de atividades
CREATE TABLE log_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(50) NOT NULL, -- CREATE, READ, UPDATE, DELETE
    tabela VARCHAR(50) NOT NULL,
    registro_id INT,
    dados_anteriores JSON,
    dados_novos JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Inserir dados iniciais

-- Níveis de acesso
INSERT INTO niveis_acesso (nome, descricao, permissoes) VALUES
('Administrador', 'Acesso total ao sistema', '{"create": true, "read": true, "update": true, "delete": true, "manage_users": true}'),
('Editor', 'Pode criar, editar e visualizar infraestruturas', '{"create": true, "read": true, "update": true, "delete": false, "manage_users": false}'),
('Visualizador', 'Apenas visualização de dados', '{"create": false, "read": true, "update": false, "delete": false, "manage_users": false}');

-- Sectores
INSERT INTO sectores (nome, descricao) VALUES
('Alimentar e Bebidas', 'Indústrias de processamento de alimentos e bebidas'),
('Têxtil e Vestuário', 'Indústrias têxteis e de confecção'),
('Metalúrgica', 'Indústrias de transformação de metais'),
('Química e Petroquímica', 'Indústrias químicas e petroquímicas'),
('Construção Civil', 'Materiais de construção e cimento'),
('Madeira e Mobiliário', 'Indústrias de madeira e móveis'),
('Papel e Celulose', 'Indústrias de papel e produtos derivados'),
('Plásticos e Borracha', 'Indústrias de plásticos e borracha'),
('Farmacêutica', 'Indústrias farmacêuticas e de cosméticos'),
('Tecnologia', 'Indústrias de tecnologia e eletrônicos'),
('Logística', 'Serviços de armazenagem e distribuição'),
('Outros', 'Outros sectores industriais');

-- Usuário administrador padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, nivel_acesso_id) VALUES
('Administrador', 'admin@pdi.gov.ao', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

