<?php
/**
 * Migration utilitária: adiciona colunas de auditoria ausentes em tabelas usadas pela API.
 * APENAS PARA DESENVOLVIMENTO - Bloqueado em produção
 */

require_once __DIR__ . '/../config.php';

// Bloquear acesso em produção
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    http_response_code(404);
    echo json_encode(['error' => 'Recurso não encontrado'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../database.php';

header('Content-Type: application/json; charset=utf-8');

function columnExists($db, $table, $column) {
    $sql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $row = $db->selectOne($sql, [$table, $column]);
    return isset($row['cnt']) ? ((int)$row['cnt'] > 0) : false;
}

function ensureColumn($db, $table, $column, $definition) {
    if (!columnExists($db, $table, $column)) {
        $db->update("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        return true;
    }
    return false;
}

try {
    $db = new Database();

    $changes = [];

    // Tabela: lotes
    $changes['lotes'] = [
        'created_by' => ensureColumn($db, 'lotes', 'created_by', 'INT NULL'),
        'updated_by' => ensureColumn($db, 'lotes', 'updated_by', 'INT NULL'),
        'created_at' => ensureColumn($db, 'lotes', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'),
        'updated_at' => ensureColumn($db, 'lotes', 'updated_at', 'DATETIME NULL DEFAULT NULL')
    ];

    // Tabela: empresas
    $changes['empresas'] = [
        'created_by' => ensureColumn($db, 'empresas', 'created_by', 'INT NULL'),
        'updated_by' => ensureColumn($db, 'empresas', 'updated_by', 'INT NULL'),
        'created_at' => ensureColumn($db, 'empresas', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'),
        'updated_at' => ensureColumn($db, 'empresas', 'updated_at', 'DATETIME NULL DEFAULT NULL')
    ];

    echo json_encode([
        'ok' => true,
        'message' => 'Migração aplicada (colunas ausentes adicionadas, se necessário).',
        'changes' => $changes
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>