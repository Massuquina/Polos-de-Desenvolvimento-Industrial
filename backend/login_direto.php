<?php
/**
 * Script para login direto - PDI Angola
 * Este script força o login sem verificações complexas
 */

// Iniciar sessão
session_start();

// Configurar cabeçalhos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'pdi_angola';
$username = 'root';
$password = '';

// Conectar ao banco de dados
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obter o email do parâmetro GET
    $email = isset($_GET['email']) ? $_GET['email'] : 'admin@pdi.gov.ao';
    
    // Verificar se o usuário existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Armazenar informações do usuário na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_level'] = $user['nivel_acesso_id'];
        $_SESSION['authenticated'] = true;
        
        echo json_encode([
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['nome'],
                'access_level' => $user['nivel_acesso_id']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Usuário não encontrado'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro: ' . $e->getMessage()
    ]);
}