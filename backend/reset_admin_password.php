<?php
/**
 * Utilitário de desenvolvimento: reseta a senha do admin para 'admin123'.
 * APENAS PARA DESENVOLVIMENTO - Bloqueado em produção
 */

require_once __DIR__ . '/config.php';

// Bloquear acesso em produção
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    http_response_code(404);
    echo json_encode(['error' => 'Recurso não encontrado'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new Database();
    $auth = new Auth();

    $admin = $db->selectOne("SELECT id FROM usuarios WHERE email = ?", ['admin@pdi.gov.ao']);

    if ($admin && isset($admin['id'])) {
        $updated = $auth->updatePassword($admin['id'], 'admin123');
        echo json_encode([
            'ok' => true,
            'action' => 'reset',
            'user_id' => $admin['id'],
            'message' => 'Senha do admin redefinida para admin123'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Se não existir, criar o usuário admin
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $created = $db->insert(
        "INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo) VALUES (?, ?, ?, ?, ?)",
        ['Administrador', 'admin@pdi.gov.ao', $hashedPassword, 1, 1]
    );

    echo json_encode([
        'ok' => (bool)$created,
        'action' => 'create',
        'message' => $created ? 'Usuário admin criado com senha admin123' : 'Falha ao criar usuário admin'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>