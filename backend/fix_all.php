<?php
/**
 * Correção completa do PDI Angola
 * Resolve todos os problemas conhecidos
 */

echo "🔧 Correção Completa PDI Angola v1.2.0\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Criar arquivo .env se não existir
echo "1. Configurando arquivo .env...\n";
$envFile = __DIR__ . '/.env';
$exampleFile = __DIR__ . '/env.example';

if (!file_exists($envFile)) {
    if (file_exists($exampleFile)) {
        if (copy($exampleFile, $envFile)) {
            echo "   ✅ Arquivo .env criado\n";
        } else {
            echo "   ❌ Erro ao criar .env\n";
        }
    } else {
        echo "   ⚠️  Arquivo env.example não encontrado\n";
    }
} else {
    echo "   ✅ Arquivo .env já existe\n";
}

// 2. Criar todos os diretórios necessários
echo "\n2. Criando diretórios...\n";
$directories = [
    'cache' => 'Cache do sistema',
    'logs' => 'Logs de aplicação',
    'backups' => 'Backups automáticos',
    'temp' => 'Arquivos temporários'
];

foreach ($directories as $dir => $description) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            echo "   ✅ {$dir}/ - {$description}\n";
        } else {
            echo "   ❌ {$dir}/ - {$description} (falha)\n";
        }
    } else {
        $writable = is_writable($path) ? 'gravável' : 'não gravável';
        echo "   ✅ {$dir}/ - {$description} ({$writable})\n";
    }
}

// 3. Testar carregamento de classes
echo "\n3. Testando carregamento de classes...\n";

// Teste EnvLoader
try {
    require_once __DIR__ . '/EnvLoader.php';
    EnvLoader::load();
    echo "   ✅ EnvLoader\n";
} catch (Exception $e) {
    echo "   ❌ EnvLoader: " . $e->getMessage() . "\n";
}

// Teste Database
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/database.php';
    $db = new Database();
    echo "   ✅ Database\n";
} catch (Exception $e) {
    echo "   ❌ Database: " . $e->getMessage() . "\n";
}

// Teste SimpleLogger
try {
    require_once __DIR__ . '/SimpleLogger.php';
    $logger = new SimpleLogger();
    echo "   ✅ SimpleLogger\n";
} catch (Exception $e) {
    echo "   ❌ SimpleLogger: " . $e->getMessage() . "\n";
}

// Teste BackupManager
try {
    require_once __DIR__ . '/BackupManager.php';
    $backup = new BackupManager();
    echo "   ✅ BackupManager\n";
} catch (Exception $e) {
    echo "   ❌ BackupManager: " . $e->getMessage() . "\n";
}

// Teste AuditLogger
try {
    require_once __DIR__ . '/AuditLogger.php';
    $audit = new AuditLogger();
    echo "   ✅ AuditLogger\n";
} catch (Exception $e) {
    echo "   ❌ AuditLogger: " . $e->getMessage() . "\n";
}

// 4. Teste funcional do BackupManager
echo "\n4. Teste funcional do BackupManager...\n";
try {
    $backup = new BackupManager();
    
    // Testar listagem de backups
    $backups = $backup->listBackups();
    echo "   ✅ listBackups(): " . count($backups) . " backups\n";
    
    // Testar backup de configuração
    $configBackup = $backup->createConfigBackup();
    if ($configBackup) {
        echo "   ✅ createConfigBackup(): {$configBackup}\n";
    } else {
        echo "   ⚠️  createConfigBackup(): falhou\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Teste funcional: " . $e->getMessage() . "\n";
}

// 5. Verificar permissões
echo "\n5. Verificando permissões...\n";
$writableDirs = ['cache', 'logs', 'backups'];
$allWritable = true;

foreach ($writableDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "   ✅ {$dir}/ é gravável\n";
        } else {
            echo "   ❌ {$dir}/ não é gravável\n";
            $allWritable = false;
        }
    }
}

if ($allWritable) {
    echo "   🎉 Todas as permissões estão corretas\n";
} else {
    echo "   ⚠️  Algumas permissões precisam ser ajustadas\n";
}

echo "\n🎯 Correção completa finalizada!\n";
echo "📝 Para testar: php diagnose.php\n";
echo "📝 Para teste simples: php test_simple.php\n";
