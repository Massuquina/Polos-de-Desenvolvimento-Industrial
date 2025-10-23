<?php
/**
 * Bypass de autenticação para resolver problemas de login
 * Este arquivo é incluído pela API para permitir login direto
 */

function bypass_auth($email, $password) {
    global $db;
    
    // Conectar ao banco de dados
    $pdo = $db->getConnection();
    
    // Verificar se o usuário existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $password == $user['senha']) {
        // Iniciar sessão
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Armazenar informações do usuário na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_level'] = $user['nivel_acesso_id'];
        $_SESSION['authenticated'] = true;
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['nome'],
                'access_level' => $user['nivel_acesso_id']
            ]
        ];
    }
    
    return ['success' => false, 'error' => 'Credenciais inválidas'];
}