<?php
/**
 * Script para corrigir o endpoint de CSRF
 */

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'pdi_angola';
$username = 'root';
$password = '';

// Iniciar sessão
session_start();

// Gerar um token CSRF válido
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;
$_SESSION['csrf_token_time'] = time();

// Configurar cabeçalhos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Criar um endpoint dedicado para CSRF
$csrf_endpoint = <<<EOT
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
\$token = bin2hex(random_bytes(32));
\$_SESSION['csrf_token'] = \$token;
\$_SESSION['csrf_token_time'] = time();

// Retornar o token em formato JSON
echo json_encode(['token' => \$token]);
EOT;

// Salvar o endpoint dedicado
file_put_contents(__DIR__ . '/csrf_token.php', $csrf_endpoint);

// Modificar o script csrf_fix.js para usar o endpoint dedicado
$js_file = __DIR__ . '/../frontend/js/csrf_fix.js';
if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    
    // Atualizar a URL do endpoint
    $js_content = str_replace(
        "'/pdi_angola/backend/api.php/csrf/token'",
        "'/pdi_angola/backend/csrf_token.php'",
        $js_content
    );
    
    // Salvar as alterações
    file_put_contents($js_file, $js_content);
}

// Retornar o token em formato JSON
echo json_encode(['token' => $token, 'message' => 'Endpoint CSRF corrigido com sucesso']);