<?php
/**
 * Script para criar utilizador administrador
 * Execute apenas uma vez para configurar o sistema
 */

require_once 'config.php';
require_once 'database.php';

echo "🔧 Criando Utilizador Administrador - PDI Angola\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $db = new Database();
    
    // Verificar se já existe administrador
    $existingAdmin = $db->selectOne(
        "SELECT u.id FROM usuarios u 
         JOIN niveis_acesso na ON u.nivel_acesso_id = na.id 
         WHERE na.nome = 'Administrador' AND u.ativo = 1"
    );
    
    if ($existingAdmin) {
        echo "⚠️  Já existe um administrador ativo no sistema.\n";
        echo "📝 ID do administrador: " . $existingAdmin['id'] . "\n\n";
        
        echo "🔍 Deseja criar um novo administrador mesmo assim? (s/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) !== 's') {
            echo "❌ Operação cancelada.\n";
            exit;
        }
    }
    
    // Obter dados do administrador
    echo "📝 Dados do Administrador:\n";
    echo "Nome: ";
    $handle = fopen("php://stdin", "r");
    $nome = trim(fgets($handle));
    fclose($handle);
    
    if (empty($nome)) {
        $nome = "Administrador do Sistema";
    }
    
    echo "Email: ";
    $handle = fopen("php://stdin", "r");
    $email = trim(fgets($handle));
    fclose($handle);
    
    if (empty($email)) {
        $email = "admin@pdi.gov.ao";
    }
    
    echo "Senha: ";
    $handle = fopen("php://stdin", "r");
    $senha = trim(fgets($handle));
    fclose($handle);
    
    if (empty($senha)) {
        $senha = "admin123";
        echo "⚠️  Usando senha padrão: admin123\n";
    }
    
    // Verificar se email já existe
    $existingUser = $db->selectOne("SELECT id FROM usuarios WHERE email = ?", [$email]);
    if ($existingUser) {
        echo "❌ Erro: Email já está em uso por outro utilizador.\n";
        exit;
    }
    
    // Obter ID do nível de administrador
    $adminLevel = $db->selectOne("SELECT id FROM niveis_acesso WHERE nome = 'Administrador'");
    if (!$adminLevel) {
        echo "❌ Erro: Nível de acesso 'Administrador' não encontrado.\n";
        echo "📝 Execute primeiro o script de instalação das melhorias.\n";
        exit;
    }
    
    // Hash da senha
    $hashedPassword = password_hash($senha, PASSWORD_DEFAULT);
    
    // Criar utilizador
    $sql = "INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo, created_at) 
            VALUES (?, ?, ?, ?, 1, NOW())";
    
    $userId = $db->insert($sql, [
        $nome,
        $email,
        $hashedPassword,
        $adminLevel['id']
    ]);
    
    if ($userId) {
        echo "\n✅ Administrador criado com sucesso!\n";
        echo "📊 Detalhes:\n";
        echo "   ID: {$userId}\n";
        echo "   Nome: {$nome}\n";
        echo "   Email: {$email}\n";
        echo "   Nível: Administrador\n";
        echo "   Status: Ativo\n\n";
        
        echo "🔗 Acesse a interface de gestão:\n";
        echo "   http://localhost/pdi_angola/backend/admin_users.php\n\n";
        
        echo "🔑 Credenciais de acesso:\n";
        echo "   Email: {$email}\n";
        echo "   Senha: {$senha}\n\n";
        
        echo "⚠️  IMPORTANTE: Altere a senha após o primeiro login!\n";
        
    } else {
        echo "❌ Erro ao criar administrador.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📝 Verifique se a base de dados está configurada corretamente.\n";
}
