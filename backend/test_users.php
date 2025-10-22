<?php
/**
 * Teste do sistema de gestão de utilizadores
 */

require_once 'UserManager.php';

echo "🧪 Testando Sistema de Gestão de Utilizadores\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $userManager = new UserManager();
    
    // Teste 1: Obter níveis de acesso
    echo "1. Testando níveis de acesso...\n";
    $levelsResult = $userManager->getAccessLevels();
    if ($levelsResult['success']) {
        echo "   ✅ Níveis de acesso carregados: " . count($levelsResult['levels']) . " níveis\n";
        foreach ($levelsResult['levels'] as $level) {
            echo "      - {$level['nome']}\n";
        }
    } else {
        echo "   ❌ Erro: " . $levelsResult['error'] . "\n";
    }
    echo "\n";
    
    // Teste 2: Listar utilizadores (simulando admin)
    echo "2. Testando listagem de utilizadores...\n";
    $usersResult = $userManager->getUsers(1); // Simulando admin com ID 1
    if ($usersResult['success']) {
        echo "   ✅ Utilizadores carregados: " . count($usersResult['users']) . " utilizadores\n";
        foreach ($usersResult['users'] as $user) {
            echo "      - {$user['nome']} ({$user['email']}) - {$user['nivel_nome']}\n";
        }
    } else {
        echo "   ❌ Erro: " . $usersResult['error'] . "\n";
    }
    echo "\n";
    
    // Teste 3: Criar utilizador de teste (se não existir)
    echo "3. Testando criação de utilizador...\n";
    $testUserData = [
        'nome' => 'Utilizador Teste',
        'email' => 'teste@pdi.gov.ao',
        'senha' => 'teste123',
        'nivel_acesso_id' => 2, // Assumindo que existe um nível não-admin
        'ativo' => 1
    ];
    
    $createResult = $userManager->createUser($testUserData, 1);
    if ($createResult['success']) {
        echo "   ✅ Utilizador de teste criado com sucesso\n";
        echo "   📊 ID: " . $createResult['user_id'] . "\n";
    } else {
        echo "   ⚠️  Erro ao criar utilizador de teste: " . $createResult['error'] . "\n";
        echo "   ℹ️  Pode ser que o utilizador já exista ou não seja administrador\n";
    }
    echo "\n";
    
    echo "🎉 Teste do sistema de utilizadores concluído!\n";
    echo "📝 Para usar a interface web, acesse:\n";
    echo "   http://localhost/pdi_angola/backend/admin_users.php\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "📝 Verifique se a base de dados está configurada corretamente.\n";
}
