<?php
/**
 * Script para forçar a criação do usuário administrador
 * Este script insere diretamente o usuário no banco de dados
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
    
    // Verificar se o usuário admin existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@pdi.gov.ao']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Atualizar a senha do usuário existente
        $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, ativo = 1 WHERE id = ?");
        $stmt->execute([$senha_hash, $admin['id']]);
        
        echo json_encode([
            'success' => true,
            'action' => 'update',
            'message' => 'Senha do administrador atualizada com sucesso',
            'user_id' => $admin['id']
        ]);
    } else {
        // Inserir o usuário admin diretamente
        $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        // Verificar se a tabela niveis_acesso existe e tem o nível 1
        $stmt = $pdo->prepare("SELECT id FROM niveis_acesso WHERE id = 1");
        $stmt->execute();
        $nivel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$nivel) {
            // Criar o nível de acesso administrador se não existir
            $stmt = $pdo->prepare("INSERT INTO niveis_acesso (id, nome, permissoes) VALUES (1, 'Administrador', ?)");
            $permissoes = json_encode([
                'admin' => true,
                'gerenciar_usuarios' => true,
                'gerenciar_infraestruturas' => true,
                'gerenciar_empresas' => true,
                'gerenciar_lotes' => true
            ]);
            $stmt->execute([$permissoes]);
        }
        
        // Inserir o usuário administrador
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Administrador', 'admin@pdi.gov.ao', $senha_hash, 1, 1]);
        
        echo json_encode([
            'success' => true,
            'action' => 'insert',
            'message' => 'Usuário administrador criado com sucesso',
            'user_id' => $pdo->lastInsertId()
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar/atualizar usuário: ' . $e->getMessage()
    ]);
}