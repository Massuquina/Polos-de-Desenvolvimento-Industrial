<?php
/**
 * Script para testar se o usuário administrador existe na base de dados
 */

require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    
    // Verificar se a tabela usuarios existe
    $tables = $db->select("SHOW TABLES LIKE 'usuarios'");
    if (empty($tables)) {
        echo "Erro: Tabela 'usuarios' não existe. Execute o script database_schema.sql primeiro.\n";
        exit(1);
    }
    
    // Verificar se o usuário admin existe
    $admin = $db->selectOne("SELECT * FROM usuarios WHERE email = ?", ['admin@pdi.gov.ao']);
    
    if ($admin) {
        echo "Usuário administrador encontrado:\n";
        echo "ID: " . $admin['id'] . "\n";
        echo "Nome: " . $admin['nome'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Ativo: " . ($admin['ativo'] ? 'Sim' : 'Não') . "\n";
        echo "Hash da senha: " . substr($admin['senha'], 0, 20) . "...\n";
        
        // Testar se a senha 'admin123' funciona
        if (password_verify('admin123', $admin['senha'])) {
            echo "✓ Senha 'admin123' está correta\n";
        } else {
            echo "✗ Senha 'admin123' não confere\n";
            // Gerar novo hash para admin123
            $newHash = password_hash('admin123', PASSWORD_DEFAULT);
            echo "Novo hash sugerido: $newHash\n";
        }
    } else {
        echo "Usuário administrador não encontrado.\n";
        echo "Criando usuário administrador...\n";
        
        // Criar usuário admin
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $result = $db->insert(
            "INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo) VALUES (?, ?, ?, ?, ?)",
            ['Administrador', 'admin@pdi.gov.ao', $hashedPassword, 1, 1]
        );
        
        if ($result) {
            echo "✓ Usuário administrador criado com sucesso\n";
            echo "Email: admin@pdi.gov.ao\n";
            echo "Senha: admin123\n";
        } else {
            echo "✗ Erro ao criar usuário administrador\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>