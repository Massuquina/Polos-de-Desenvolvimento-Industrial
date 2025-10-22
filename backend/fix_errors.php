<?php
/**
 * Script de correção rápida para PDI Angola
 * Resolve problemas comuns de configuração
 */

echo "🔧 Corrigindo problemas do PDI Angola...\n\n";

// 1. Criar arquivo .env se não existir
echo "1. Verificando arquivo .env...\n";
$envFile = __DIR__ . '/.env';
$exampleFile = __DIR__ . '/env.example';

if (!file_exists($envFile)) {
    if (file_exists($exampleFile)) {
        if (copy($exampleFile, $envFile)) {
            echo "   ✅ Arquivo .env criado a partir do exemplo\n";
        } else {
            echo "   ❌ Erro ao criar arquivo .env\n";
        }
    } else {
        echo "   ⚠️  Arquivo env.example não encontrado\n";
    }
} else {
    echo "   ✅ Arquivo .env já existe\n";
}

// 2. Criar diretórios necessários
echo "\n2. Criando diretórios necessários...\n";
$directories = [
    __DIR__ . '/cache',
    __DIR__ . '/logs',
    __DIR__ . '/backups',
    __DIR__ . '/temp'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   ✅ Diretório criado: " . basename($dir) . "\n";
        } else {
            echo "   ❌ Erro ao criar diretório: " . basename($dir) . "\n";
        }
    } else {
        echo "   ✅ Diretório existe: " . basename($dir) . "\n";
    }
}

// 3. Testar EnvLoader
echo "\n3. Testando EnvLoader...\n";
try {
    require_once 'EnvLoader.php';
    EnvLoader::load();
    
    $testValue = EnvLoader::get('DB_HOST', 'NOT_FOUND');
    if ($testValue !== 'NOT_FOUND') {
        echo "   ✅ EnvLoader funcionando corretamente\n";
    } else {
        echo "   ⚠️  EnvLoader carregou mas sem variáveis\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro no EnvLoader: " . $e->getMessage() . "\n";
}

// 4. Verificar permissões
echo "\n4. Verificando permissões...\n";
$writableDirs = ['cache', 'logs', 'backups'];
foreach ($writableDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "   ✅ {$dir} é gravável\n";
        } else {
            echo "   ⚠️  {$dir} não é gravável\n";
        }
    }
}

// 5. Testar BackupManager
echo "\n5. Testando BackupManager...\n";
try {
    require_once 'BackupManager.php';
    $backup = new BackupManager();
    echo "   ✅ BackupManager funcionando\n";
} catch (Exception $e) {
    echo "   ❌ Erro no BackupManager: " . $e->getMessage() . "\n";
}

// 6. Executar teste completo
echo "\n6. Executando teste completo...\n";
echo "   📝 Execute: php test_simple.php\n";

echo "\n🎉 Correção concluída!\n";
echo "📝 Se ainda houver problemas, verifique:\n";
echo "   - Permissões dos diretórios\n";
echo "   - Configuração do arquivo .env\n";
echo "   - Logs de erro do PHP\n";
