<?php
/**
 * Utilitário de diagnóstico: mostra o corpo recebido e a decodificação JSON.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$raw = file_get_contents('php://input');
$decoded = null;
$jsonError = null;

if ($raw !== false && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $jsonError = json_last_error_msg();
    }
}

echo json_encode([
    'method' => $method,
    'content_type' => $contentType,
    'raw_length' => strlen($raw ?: ''),
    'raw' => $raw,
    'decoded' => $decoded,
    'json_error' => $jsonError,
], JSON_UNESCAPED_UNICODE);
?>