<?php
// Endpoint dedicado para obter token CSRF
// Arquivo: csrf_token.php

// Garantir carregamento da implementação CSRF
require_once __DIR__ . '/csrf.php';

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurar cabeçalhos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gerar um token CSRF consistente com a classe CSRF
$token = CSRF::generateToken();

// Retornar o token em formato JSON
echo json_encode(['token' => $token]);