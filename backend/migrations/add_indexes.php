<?php
/**
 * Migração para adicionar índices de performance
 * Não usar em produção sem revisão. Executar apenas em ambiente de desenvolvimento.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

// Bloquear execução em produção
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint não disponível em produção'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

function indexExists($db, $table, $indexName) {
    $sql = "SELECT COUNT(*) AS cnt 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = ? 
              AND INDEX_NAME = ?";
    $row = $db->selectOne($sql, [$table, $indexName]);
    return isset($row['cnt']) ? ((int)$row['cnt'] > 0) : false;
}

try {
    $db = new Database();

    $indexes = [
        // Tabela infraestruturas
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_localizacao', 'cols' => 'provincia_id, municipio_id, polo_id'],
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_sector', 'cols' => 'sector_id'],
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_estado', 'cols' => 'estado_atual'],
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_coordenadas', 'cols' => 'latitude, longitude'],
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_created_at', 'cols' => 'created_at'],
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_created_by', 'cols' => 'created_by'],
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_ano_construcao', 'cols' => 'ano_construcao'],

        // Tabela polos_desenvolvimento
        ['table' => 'polos_desenvolvimento', 'name' => 'idx_polos_localizacao', 'cols' => 'provincia_id, municipio_id'],
        ['table' => 'polos_desenvolvimento', 'name' => 'idx_polos_status', 'cols' => 'status'],
        ['table' => 'polos_desenvolvimento', 'name' => 'idx_polos_coordenadas', 'cols' => 'latitude, longitude'],

        // Tabela usuarios
        ['table' => 'usuarios', 'name' => 'idx_usuarios_nivel_acesso', 'cols' => 'nivel_acesso_id'],
        ['table' => 'usuarios', 'name' => 'idx_usuarios_ativo', 'cols' => 'ativo'],
        ['table' => 'usuarios', 'name' => 'idx_usuarios_ultimo_login', 'cols' => 'ultimo_login'],

        // Municipios e comunas
        ['table' => 'municipios', 'name' => 'idx_municipios_provincia', 'cols' => 'provincia_id'],
        ['table' => 'comunas', 'name' => 'idx_comunas_municipio', 'cols' => 'municipio_id'],

        // Log de atividades
        ['table' => 'log_atividades', 'name' => 'idx_log_usuario_data', 'cols' => 'usuario_id, created_at'],
        ['table' => 'log_atividades', 'name' => 'idx_log_acao', 'cols' => 'acao'],
        ['table' => 'log_atividades', 'name' => 'idx_log_tabela', 'cols' => 'tabela'],
        ['table' => 'log_atividades', 'name' => 'idx_log_created_at', 'cols' => 'created_at'],
        ['table' => 'log_atividades', 'name' => 'idx_log_tabela_registro', 'cols' => 'tabela, registro_id'],

        // Tabelas relacionadas às infraestruturas
        ['table' => 'envolvente_exterior', 'name' => 'idx_envolvente_infraestrutura', 'cols' => 'infraestrutura_id'],
        ['table' => 'edificios', 'name' => 'idx_edificios_infraestrutura', 'cols' => 'infraestrutura_id'],
        ['table' => 'equipamentos', 'name' => 'idx_equipamentos_infraestrutura', 'cols' => 'infraestrutura_id'],

        // Índices para relatórios e estatísticas
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_relatorio_localizacao_sector', 'cols' => 'provincia_id, sector_id, estado_atual'],
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_relatorio_polo_sector', 'cols' => 'polo_id, sector_id, created_at'],
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_area', 'cols' => 'area'],
        ['table' => 'infraestruturas', 'name' => 'idx_infraestruturas_proprietario', 'cols' => 'proprietario_terreno'],
    ];

    $created = [];
    $skipped = [];

    foreach ($indexes as $idx) {
        if (!indexExists($db, $idx['table'], $idx['name'])) {
            $sql = "CREATE INDEX `{$idx['name']}` ON `{$idx['table']}` ({$idx['cols']})";
            $db->update($sql);
            $created[] = $idx['name'];
        } else {
            $skipped[] = $idx['name'];
        }
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Índices aplicados (apenas os que faltavam).',
        'created' => $created,
        'skipped' => $skipped
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

?>