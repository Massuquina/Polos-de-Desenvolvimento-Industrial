<?php
/**
 * Teste do BackupManager
 * Verifica se o sistema de backup está funcionando
 */

// Suprimir warnings para teste
error_reporting(E_ERROR | E_PARSE);

require_once 'BackupManager.php';

echo "🧪 Testando BackupManager...\n\n";

try {
    // Teste 1: Criar instância
    echo "1. Criando instância do BackupManager...\n";
    $backupManager = new BackupManager();
    echo "   ✅ BackupManager criado com sucesso\n\n";

    // Teste 2: Listar backups existentes
    echo "2. Listando backups existentes...\n";
    $backups = $backupManager->listBackups();
    echo "   📊 Total de backups: " . count($backups) . "\n";
    
    if (count($backups) > 0) {
        echo "   📋 Backups encontrados:\n";
        foreach ($backups as $backup) {
            echo "      - {$backup['name']} ({$backup['created_at']})\n";
        }
    } else {
        echo "   ℹ️  Nenhum backup encontrado\n";
    }
    echo "\n";

    // Teste 3: Criar backup de configuração (mais leve)
    echo "3. Testando backup de configuração...\n";
    $configBackup = $backupManager->createConfigBackup();
    if ($configBackup) {
        echo "   ✅ Backup de configuração criado: {$configBackup}\n";
    } else {
        echo "   ❌ Falha ao criar backup de configuração\n";
    }
    echo "\n";

    // Teste 4: Verificar se diretório de backup existe
    echo "4. Verificando diretório de backup...\n";
    $backupPath = BACKUP_PATH;
    if (is_dir($backupPath)) {
        echo "   ✅ Diretório de backup existe: {$backupPath}\n";
        if (is_writable($backupPath)) {
            echo "   ✅ Diretório é gravável\n";
        } else {
            echo "   ⚠️  Diretório não é gravável\n";
        }
    } else {
        echo "   ❌ Diretório de backup não existe: {$backupPath}\n";
    }
    echo "\n";

    echo "🎉 Teste do BackupManager concluído!\n";

} catch (Exception $e) {
    echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
    echo "📝 Verifique as configurações e permissões\n";
}
