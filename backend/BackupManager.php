<?php
/**
 * Sistema de Backup Automático
 * Cria backups da base de dados e arquivos do sistema
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/SimpleLogger.php';

class BackupManager {
    private $db;
    private $backupPath;
    private $retentionDays;
    private $logger;

    /**
     * Garantir que o diretório de backup existe
     */
    private function ensureBackupDirectory() {
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    public function __construct() {
        $this->db = new Database();
        $this->backupPath = BACKUP_PATH;
        $this->retentionDays = BACKUP_RETENTION_DAYS;
        
        // Garantir que o diretório de backup existe
        $this->ensureBackupDirectory();
        
        // Tentar obter logger global, criar um simples se não existir
        global $logger;
        if ($logger && method_exists($logger, 'info')) {
            $this->logger = $logger;
        } else {
            $this->logger = new SimpleLogger();
        }
    }

    /**
     * Cria backup completo do sistema
     */
    public function createFullBackup() {
        if (!BACKUP_ENABLED) {
            return false;
        }

        try {
            $this->ensureBackupDirectory();
            
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = "pdi_angola_backup_{$timestamp}";
            $backupDir = $this->backupPath . $backupName . '/';

            if (!mkdir($backupDir, 0755, true)) {
                throw new Exception("Não foi possível criar diretório de backup");
            }

            // Backup da base de dados
            $dbBackup = $this->createDatabaseBackup($backupDir . 'database.sql');
            
            // Backup dos arquivos
            $filesBackup = $this->createFilesBackup($backupDir . 'files.tar.gz');
            
            // Backup da configuração
            $configBackup = $this->createConfigBackup($backupDir . 'config.json');

            if ($dbBackup && $filesBackup && $configBackup) {
                // Criar arquivo de metadados
                $this->createMetadataFile($backupDir, $backupName);
                
                // Limpar backups antigos
                $this->cleanOldBackups();
                
                $this->logger->info("Backup completo criado: {$backupName}");
                return $backupName;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->error("Erro ao criar backup: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria backup apenas da base de dados
     */
    public function createDatabaseBackup($outputFile = null) {
        try {
            $outputFile = $outputFile ?: $this->backupPath . 'database_' . date('Y-m-d_H-i-s') . '.sql';
            
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_NAME),
                escapeshellarg($outputFile)
            );

            $result = shell_exec($command);
            
            if (file_exists($outputFile) && filesize($outputFile) > 0) {
                $this->logger->info("Backup da base de dados criado: {$outputFile}");
                return $outputFile;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->error("Erro ao criar backup da base de dados: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria backup dos arquivos do sistema
     */
    public function createFilesBackup($outputFile = null) {
        try {
            $outputFile = $outputFile ?: $this->backupPath . 'files_' . date('Y-m-d_H-i-s') . '.tar.gz';
            
            $projectRoot = dirname(__DIR__);
            $excludeFiles = [
                'node_modules',
                '.git',
                'cache',
                'logs',
                'backups',
                'vendor',
                '.env'
            ];

            $excludeString = '';
            foreach ($excludeFiles as $file) {
                $excludeString .= " --exclude='{$file}'";
            }

            $command = "tar -czf " . escapeshellarg($outputFile) . " {$excludeString} -C " . escapeshellarg($projectRoot) . " .";
            
            $result = shell_exec($command);
            
            if (file_exists($outputFile) && filesize($outputFile) > 0) {
                $this->logger->info("Backup dos arquivos criado: {$outputFile}");
                return $outputFile;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->error("Erro ao criar backup dos arquivos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria backup da configuração
     */
    public function createConfigBackup($outputFile = null) {
        try {
            $outputFile = $outputFile ?: $this->backupPath . 'config_' . date('Y-m-d_H-i-s') . '.json';
            
            $config = [
                'app_name' => APP_NAME,
                'app_version' => APP_VERSION,
                'app_url' => APP_URL,
                'environment' => ENVIRONMENT,
                'database' => [
                    'host' => DB_HOST,
                    'name' => DB_NAME,
                    'charset' => DB_CHARSET
                ],
                'backup_created' => date('Y-m-d H:i:s'),
                'backup_version' => '1.2.0'
            ];

            $result = file_put_contents($outputFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            if ($result !== false) {
                $this->logger->info("Backup da configuração criado: {$outputFile}");
                return $outputFile;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->error("Erro ao criar backup da configuração: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista todos os backups disponíveis
     */
    public function listBackups() {
        $backups = [];
        $backupDirs = glob($this->backupPath . 'pdi_angola_backup_*', GLOB_ONLYDIR);
        
        foreach ($backupDirs as $dir) {
            $backupName = basename($dir);
            $metadataFile = $dir . '/metadata.json';
            
            if (file_exists($metadataFile)) {
                $metadata = json_decode(file_get_contents($metadataFile), true);
                $backups[] = array_merge($metadata, [
                    'path' => $dir,
                    'size' => $this->getDirectorySize($dir)
                ]);
            }
        }

        // Ordenar por data de criação (mais recente primeiro)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Restaura um backup
     */
    public function restoreBackup($backupName) {
        try {
            $backupDir = $this->backupPath . $backupName . '/';
            
            if (!is_dir($backupDir)) {
                throw new Exception("Backup não encontrado: {$backupName}");
            }

            $metadataFile = $backupDir . 'metadata.json';
            if (!file_exists($metadataFile)) {
                throw new Exception("Arquivo de metadados não encontrado");
            }

            $metadata = json_decode(file_get_contents($metadataFile), true);
            
            // Restaurar base de dados
            if (file_exists($backupDir . 'database.sql')) {
                $this->restoreDatabase($backupDir . 'database.sql');
            }

            // Restaurar arquivos
            if (file_exists($backupDir . 'files.tar.gz')) {
                $this->restoreFiles($backupDir . 'files.tar.gz');
            }

            $this->logger->info("Backup restaurado com sucesso: {$backupName}");
            return true;

        } catch (Exception $e) {
            $this->logger->error("Erro ao restaurar backup: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove backups antigos
     */
    public function cleanOldBackups() {
        $backups = $this->listBackups();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->retentionDays} days"));
        $removed = 0;

        foreach ($backups as $backup) {
            if (strtotime($backup['created_at']) < strtotime($cutoffDate)) {
                if ($this->removeBackup($backup['name'])) {
                    $removed++;
                }
            }
        }

        $this->logger->info("Removidos {$removed} backups antigos");
        return $removed;
    }

    /**
     * Utilitários
     */

    private function createMetadataFile($backupDir, $backupName) {
        $metadata = [
            'name' => $backupName,
            'created_at' => date('Y-m-d H:i:s'),
            'version' => APP_VERSION,
            'database_size' => filesize($backupDir . 'database.sql'),
            'files_size' => filesize($backupDir . 'files.tar.gz'),
            'config_size' => filesize($backupDir . 'config.json')
        ];

        file_put_contents($backupDir . 'metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
    }

    private function getDirectorySize($dir) {
        $size = 0;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }

    private function restoreDatabase($sqlFile) {
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($sqlFile)
        );

        shell_exec($command);
    }

    private function restoreFiles($tarFile) {
        $projectRoot = dirname(__DIR__);
        $command = "tar -xzf " . escapeshellarg($tarFile) . " -C " . escapeshellarg($projectRoot);
        shell_exec($command);
    }

    private function removeBackup($backupName) {
        $backupDir = $this->backupPath . $backupName . '/';
        
        if (is_dir($backupDir)) {
            $this->deleteDirectory($backupDir);
            return true;
        }
        
        return false;
    }

    private function deleteDirectory($dir) {
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }

    /**
     * Agenda backup automático (para ser chamado via cron)
     */
    public function scheduleBackup() {
        if (!BACKUP_ENABLED) {
            return false;
        }

        // Verificar se já existe backup hoje
        $today = date('Y-m-d');
        $backups = $this->listBackups();
        
        foreach ($backups as $backup) {
            if (strpos($backup['created_at'], $today) === 0) {
                $this->logger->info("Backup já existe para hoje");
                return false;
            }
        }

        return $this->createFullBackup();
    }
}
