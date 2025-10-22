<?php
/**
 * Teste simples para verificar se as classes estão carregando
 */

// Suprimir warnings para teste
error_reporting(E_ERROR | E_PARSE);

echo "🧪 Teste Simples - PDI Angola v1.2.0\n";
echo str_repeat("=", 40) . "\n\n";

// Teste 1: EnvLoader
echo "1. Testando EnvLoader...\n";
try {
    require_once 'EnvLoader.php';
    EnvLoader::load();
    $testValue = EnvLoader::get('DB_HOST', 'NOT_FOUND');
    echo "   ✅ EnvLoader: DB_HOST = {$testValue}\n";
} catch (Exception $e) {
    echo "   ❌ EnvLoader: " . $e->getMessage() . "\n";
}

// Teste 2: SimpleLogger
echo "\n2. Testando SimpleLogger...\n";
try {
    require_once 'SimpleLogger.php';
    $logger = new SimpleLogger();
    $logger->info('Teste do SimpleLogger');
    echo "   ✅ SimpleLogger funcionando\n";
} catch (Exception $e) {
    echo "   ❌ SimpleLogger: " . $e->getMessage() . "\n";
}

// Teste 3: Database
echo "\n3. Testando Database...\n";
try {
    require_once 'config.php';
    require_once 'database.php';
    $db = new Database();
    $result = $db->selectOne("SELECT 1 as test");
    echo "   ✅ Database: " . ($result['test'] == 1 ? 'OK' : 'ERRO') . "\n";
} catch (Exception $e) {
    echo "   ❌ Database: " . $e->getMessage() . "\n";
}

// Teste 4: BackupManager
echo "\n4. Testando BackupManager...\n";
try {
    require_once 'BackupManager.php';
    $backup = new BackupManager();
    echo "   ✅ BackupManager criado com sucesso\n";
} catch (Exception $e) {
    echo "   ❌ BackupManager: " . $e->getMessage() . "\n";
}

// Teste 5: AuditLogger
echo "\n5. Testando AuditLogger...\n";
try {
    require_once 'AuditLogger.php';
    $audit = new AuditLogger();
    echo "   ✅ AuditLogger criado com sucesso\n";
} catch (Exception $e) {
    echo "   ❌ AuditLogger: " . $e->getMessage() . "\n";
}

echo "\n🎉 Teste simples concluído!\n";
