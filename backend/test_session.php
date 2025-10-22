<?php
/**
 * Teste de sessão para verificar se as configurações estão corretas
 * APENAS PARA DESENVOLVIMENTO - Bloqueado em produção
 */

require_once 'config.php';

// Bloquear acesso em produção
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    http_response_code(404);
    echo json_encode(['error' => 'Recurso não encontrado'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Configurar cookies de sessão antes de iniciar a sessão
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', 'Off');
ini_set('session.cookie_httponly', 'On');

// Iniciar sessão
session_start();

// Verificar se a sessão já existe
if (isset($_SESSION['test_count'])) {
    $_SESSION['test_count']++;
} else {
    $_SESSION['test_count'] = 1;
}

// Retornar informações sobre a sessão
header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'test_count' => $_SESSION['test_count'],
    'session_info' => [
        'cookie_params' => session_get_cookie_params(),
        'session_name' => session_name(),
        'session_status' => session_status()
    ],
    'php_info' => [
        'session.cookie_samesite' => ini_get('session.cookie_samesite'),
        'session.cookie_secure' => ini_get('session.cookie_secure'),
        'session.cookie_httponly' => ini_get('session.cookie_httponly')
    ]
]);
?>