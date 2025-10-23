<?php
// Endpoint dedicado para obter token CSRF
// Arquivo: csrf_token.php

// Iniciar sessão
session_start();

// Configurar cabeçalhos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gerar um token CSRF válido
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;
$_SESSION['csrf_token_time'] = time();

// Retornar o token em formato JSON
echo json_encode(['token' => $token]);