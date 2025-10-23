<?php
/**
 * Script para corrigir problemas de login
 * Este script verifica e corrige as credenciais do administrador
 */

require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new Database();
    
    // Verificar se o usuário admin existe
    $admin = $db->selectOne("SELECT id, email, senha FROM usuarios WHERE email = ?", ['admin@pdi.gov.ao']);
    
    if ($admin) {
        // Gerar nova senha hash para admin123
        $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        // Atualizar a senha e garantir que a conta esteja ativa
        $db->update(
            "UPDATE usuarios SET senha = ?, ativo = 1 WHERE id = ?", 
            [$senha_hash, $admin['id']]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Senha do administrador redefinida com sucesso',
            'user_id' => $admin['id'],
            'email' => $admin['email']
        ]);
    } else {
        // Criar o usuário admin se não existir
        $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $id = $db->insert(
            "INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo) VALUES (?, ?, ?, ?, ?)",
            ['Administrador', 'admin@pdi.gov.ao', $senha_hash, 1, 1]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuário administrador criado com sucesso',
            'user_id' => $id,
            'email' => 'admin@pdi.gov.ao'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao corrigir credenciais: ' . $e->getMessage()
    ]);
}