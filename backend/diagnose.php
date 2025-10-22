<?php
/**
 * Diagnóstico completo do PDI Angola
 * Verifica todos os componentes e dependências
 */

echo "🔍 Diagnóstico PDI Angola v1.2.0\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Verificar arquivos essenciais
echo "1. Verificando arquivos essenciais...\n";
$essentialFiles = [
    'config.php' => 'Configurações principais',
    'database.php' => 'Classe Database',
    'EnvLoader.php' => 'Carregador de ambiente',
    'SimpleLogger.php' => 'Logger simples'
];

foreach ($essentialFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ {$file} - {$description}\n";
    } else {
        echo "   ❌ {$file} - {$description} (FALTANDO)\n";
    }
}

// 2. Verificar se constantes estão definidas
echo "\n2. Verificando constantes...\n";
try {
    require_once __DIR__ . '/config.php';
    
    $constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'BACKUP_PATH'];
    foreach ($constants as $constant) {
        if (defined($constant)) {
            echo "   ✅ {$constant} = " . constant($constant) . "\n";
        } else {
            echo "   ❌ {$constant} não definida\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Erro ao carregar config.php: " . $e->getMessage() . "\n";
}

// 3. Verificar se Database pode ser carregada
echo "\n3. Verificando classe Database...\n";
try {
    require_once __DIR__ . '/database.php';
    if (class_exists('Database')) {
        echo "   ✅ Classe Database carregada\n";
        
        // Tentar criar instância
        $db = new Database();
        echo "   ✅ Instância Database criada\n";
    } else {
        echo "   ❌ Classe Database não encontrada\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro com Database: " . $e->getMessage() . "\n";
}

// 4. Verificar diretórios
echo "\n4. Verificando diretórios...\n";
$directories = [
    'cache' => 'Cache',
    'logs' => 'Logs',
    'backups' => 'Backups'
];

foreach ($directories as $dir => $description) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $writable = is_writable($path) ? 'gravável' : 'não gravável';
        echo "   ✅ {$dir}/ - {$description} ({$writable})\n";
    } else {
        echo "   ❌ {$dir}/ - {$description} (não existe)\n";
        
        // Tentar criar
        if (mkdir($path, 0755, true)) {
            echo "      ✅ Diretório criado\n";
        } else {
            echo "      ❌ Falha ao criar diretório\n";
        }
    }
}

// 5. Verificar EnvLoader
echo "\n5. Verificando EnvLoader...\n";
try {
    require_once __DIR__ . '/EnvLoader.php';
    EnvLoader::load();
    $testValue = EnvLoader::get('DB_HOST', 'NOT_FOUND');
    echo "   ✅ EnvLoader: DB_HOST = {$testValue}\n";
} catch (Exception $e) {
    echo "   ❌ EnvLoader: " . $e->getMessage() . "\n";
}

// 6. Verificar SimpleLogger
echo "\n6. Verificando SimpleLogger...\n";
try {
    require_once __DIR__ . '/SimpleLogger.php';
    $logger = new SimpleLogger();
    $logger->info('Teste de diagnóstico');
    echo "   ✅ SimpleLogger funcionando\n";
} catch (Exception $e) {
    echo "   ❌ SimpleLogger: " . $e->getMessage() . "\n";
}

// 7. Teste final - BackupManager
echo "\n7. Teste final - BackupManager...\n";
try {
    require_once __DIR__ . '/BackupManager.php';
    $backup = new BackupManager();
    echo "   ✅ BackupManager criado com sucesso\n";
    
    // Testar método simples
    $backups = $backup->listBackups();
    echo "   ✅ listBackups() funcionando: " . count($backups) . " backups\n";
} catch (Exception $e) {
    echo "   ❌ BackupManager: " . $e->getMessage() . "\n";
    echo "   📝 Stack trace:\n";
    echo "      " . str_replace("\n", "\n      ", $e->getTraceAsString()) . "\n";
}

echo "\n🎯 Diagnóstico concluído!\n";
echo "📝 Se houver erros, execute: php fix_errors.php\n";
