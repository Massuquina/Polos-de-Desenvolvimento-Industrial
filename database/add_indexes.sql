-- Script para adicionar índices necessários para otimização de performance
-- Base de dados PDI Angola
-- Execute este script após a criação da base de dados

USE pdi_angola;

-- Índices para tabela infraestruturas (tabela principal com muitas consultas)
-- Índice composto para consultas por localização
CREATE INDEX idx_infraestruturas_localizacao ON infraestruturas (provincia_id, municipio_id, polo_id);

-- Índice para consultas por setor
CREATE INDEX idx_infraestruturas_sector ON infraestruturas (sector_id);

-- Índice para consultas por estado atual
CREATE INDEX idx_infraestruturas_estado ON infraestruturas (estado_atual);

-- Índice para consultas por coordenadas geográficas (para mapas)
CREATE INDEX idx_infraestruturas_coordenadas ON infraestruturas (latitude, longitude);

-- Índice para consultas por data de criação (relatórios e ordenação)
CREATE INDEX idx_infraestruturas_created_at ON infraestruturas (created_at);

-- Índice para consultas por usuário criador
CREATE INDEX idx_infraestruturas_created_by ON infraestruturas (created_by);

-- Índice para consultas por ano de construção
CREATE INDEX idx_infraestruturas_ano_construcao ON infraestruturas (ano_construcao);

-- Índices para tabela polos_desenvolvimento
-- Índice para consultas por localização
CREATE INDEX idx_polos_localizacao ON polos_desenvolvimento (provincia_id, municipio_id);

-- Índice para consultas por status
CREATE INDEX idx_polos_status ON polos_desenvolvimento (status);

-- Índice para consultas por coordenadas
CREATE INDEX idx_polos_coordenadas ON polos_desenvolvimento (latitude, longitude);

-- Índices para tabela usuarios
-- Índice para consultas por nível de acesso
CREATE INDEX idx_usuarios_nivel_acesso ON usuarios (nivel_acesso_id);

-- Índice para consultas por status ativo
CREATE INDEX idx_usuarios_ativo ON usuarios (ativo);

-- Índice para consultas por último login
CREATE INDEX idx_usuarios_ultimo_login ON usuarios (ultimo_login);

-- Índices para tabela municipios
-- Índice para consultas por província
CREATE INDEX idx_municipios_provincia ON municipios (provincia_id);

-- Índices para tabela comunas
-- Índice para consultas por município
CREATE INDEX idx_comunas_municipio ON comunas (municipio_id);

-- Índices para tabela log_atividades (para auditoria e relatórios)
-- Índice composto para consultas por usuário e data
CREATE INDEX idx_log_usuario_data ON log_atividades (usuario_id, created_at);

-- Índice para consultas por ação
CREATE INDEX idx_log_acao ON log_atividades (acao);

-- Índice para consultas por tabela
CREATE INDEX idx_log_tabela ON log_atividades (tabela);

-- Índice para consultas por data de criação
CREATE INDEX idx_log_created_at ON log_atividades (created_at);

-- Índice composto para consultas por tabela e registro
CREATE INDEX idx_log_tabela_registro ON log_atividades (tabela, registro_id);

-- Índices para tabelas relacionadas às infraestruturas
-- Índice para envolvente_exterior
CREATE INDEX idx_envolvente_infraestrutura ON envolvente_exterior (infraestrutura_id);

-- Índice para edificios
CREATE INDEX idx_edificios_infraestrutura ON edificios (infraestrutura_id);

-- Índice para equipamentos
CREATE INDEX idx_equipamentos_infraestrutura ON equipamentos (infraestrutura_id);

-- Índices para otimização de relatórios e estatísticas
-- Índice composto para relatórios por localização e setor
CREATE INDEX idx_infraestruturas_relatorio_localizacao_sector ON infraestruturas (provincia_id, sector_id, estado_atual);

-- Índice composto para relatórios por polo e setor
CREATE INDEX idx_infraestruturas_relatorio_polo_sector ON infraestruturas (polo_id, sector_id, created_at);

-- Índice para consultas de área total
CREATE INDEX idx_infraestruturas_area ON infraestruturas (area);

-- Índice para consultas por proprietário do terreno
CREATE INDEX idx_infraestruturas_proprietario ON infraestruturas (proprietario_terreno);

-- Verificar se os índices foram criados com sucesso
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE
FROM 
    INFORMATION_SCHEMA.STATISTICS 
WHERE 
    TABLE_SCHEMA = 'pdi_angola' 
    AND INDEX_NAME NOT IN ('PRIMARY')
ORDER BY 
    TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;