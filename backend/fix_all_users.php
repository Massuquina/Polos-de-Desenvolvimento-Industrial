<?php
/**
 * Script para corrigir todas as credenciais de usuários
 * Este script atualiza diretamente as senhas no banco de dados
 */

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'pdi_angola';
$username = 'root';
$password = '';

header('Content-Type: application/json; charset=utf-8');

try {
    // Conectar ao banco de dados diretamente
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar o nome da tabela de usuários (pode ser 'users' ou 'usuarios')
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $user_table = in_array('usuarios', $tables) ? 'usuarios' : (in_array('users', $tables) ? 'users' : null);
    
    if (!$user_table) {
        throw new Exception("Tabela de usuários não encontrada");
    }
    
    // Verificar o nome da coluna de senha (pode ser 'password' ou 'senha')
    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM {$user_table}");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    $password_column = in_array('senha', $columns) ? 'senha' : (in_array('password', $columns) ? 'password' : null);
    $email_column = in_array('email', $columns) ? 'email' : null;
    $name_column = in_array('nome', $columns) ? 'nome' : (in_array('name', $columns) ? 'name' : null);
    $active_column = in_array('ativo', $columns) ? 'ativo' : (in_array('is_active', $columns) ? 'is_active' : null);
    
    if (!$password_column || !$email_column || !$name_column || !$active_column) {
        throw new Exception("Colunas necessárias não encontradas na tabela {$user_table}");
    }
    
    // Lista de usuários para atualizar
    $users = [
        [
            "email" => "admin@pdi.gov.ao",
            "name" => "Administrador Principal",
            "password" => "admin123"
        ],
        [
            "email" => "gestor@pdi.gov.ao",
            "name" => "Gestor de Projetos",
            "password" => "gestor123"
        ],
        [
            "email" => "tecnico@pdi.gov.ao",
            "name" => "Técnico de Infraestrutura",
            "password" => "tecnico123"
        ],
        [
            "email" => "visualizador@pdi.gov.ao",
            "name" => "Visualizador de Dados",
            "password" => "visualizador123"
        ]
    ];
    
    $results = [];
    
    // Atualizar cada usuário
    foreach ($users as $user) {
        // Verificar se o usuário existe
        $stmt = $pdo->prepare("SELECT id FROM {$user_table} WHERE {$email_column} = ?");
        $stmt->execute([$user["email"]]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Gerar hash da senha usando algoritmo compatível
        $password_hash = password_hash($user["password"], PASSWORD_DEFAULT);
        
        if ($existing_user) {
            // Atualizar usuário existente
            $stmt = $pdo->prepare("UPDATE {$user_table} SET {$password_column} = ?, {$active_column} = 1 WHERE id = ?");
            $stmt->execute([$password_hash, $existing_user['id']]);
            
            $results[] = [
                'success' => true,
                'action' => 'update',
                'message' => "Senha do usuário {$user["email"]} atualizada com sucesso",
                'user_id' => $existing_user['id']
            ];
        } else {
            // Criar novo usuário se não existir
            $stmt = $pdo->prepare("INSERT INTO {$user_table} ({$email_column}, {$name_column}, {$password_column}, {$active_column}) VALUES (?, ?, ?, 1)");
            $stmt->execute([$user["email"], $user["name"], $password_hash]);
            
            $results[] = [
                'success' => true,
                'action' => 'create',
                'message' => "Usuário {$user["email"]} criado com sucesso",
                'user_id' => $pdo->lastInsertId()
            ];
        }
    }
    
    // Exibir resultados
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro: ' . $e->getMessage()
    ]);
}