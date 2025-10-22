<?php
/**
 * Utilitário: exibe o estado do ambiente e configurações chave.
 * APENAS PARA DESENVOLVIMENTO - Bloqueado em produção
 */

require_once __DIR__ . '/config.php';

// Bloquear acesso em produção
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    http_response_code(404);
    echo json_encode(['error' => 'Recurso não encontrado'], JSON_UNESCAPED_UNICODE);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$env = defined('ENVIRONMENT') ? ENVIRONMENT : 'undefined';
$displayErrors = ini_get('display_errors');
$errorReporting = error_reporting();

echo json_encode([
    'environment' => $env,
    'display_errors' => $displayErrors,
    'error_reporting' => $errorReporting,
    'cors' => [
        'allow_origin' => isset($_SERVER['HTTP_ORIGIN']) && !empty($_SERVER['HTTP_ORIGIN'])
            ? $_SERVER['HTTP_ORIGIN']
            : 'http://localhost/pdi_angola/frontend',
        'allow_credentials' => true,
        'allow_methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'allow_headers' => 'Content-Type, Authorization, X-Requested-With'
    ]
], JSON_UNESCAPED_UNICODE);

?>