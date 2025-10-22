<?php
/**
 * Script de instalação das melhorias de segurança e performance
 * PDI Angola v1.2.0
 */

require_once 'config.php';
require_once 'database.php';
require_once 'BackupManager.php';

class ImprovementsInstaller {
    private $db;
    private $backupManager;

    public function __construct() {
        $this->db = new Database();
        $this->backupManager = new BackupManager();
    }

    /**
     * Executa todas as melhorias
     */
    public function install() {
        echo "🚀 Iniciando instalação das melhorias PDI Angola v1.2.0\n\n";

        try {
            // 1. Criar backup antes das alterações
            echo "📦 Criando backup de segurança...\n";
            $backupName = $this->backupManager->createFullBackup();
            if ($backupName) {
                echo "✅ Backup criado: {$backupName}\n\n";
            } else {
                echo "⚠️  Backup falhou, continuando...\n\n";
            }

            // 2. Criar tabelas de auditoria e rate limiting
            echo "🔧 Criando tabelas de auditoria e rate limiting...\n";
            $this->createAuditTables();
            echo "✅ Tabelas criadas com sucesso\n\n";

            // 3. Criar diretórios necessários
            echo "📁 Criando diretórios necessários...\n";
            $this->createDirectories();
            echo "✅ Diretórios criados com sucesso\n\n";

            // 4. Configurar permissões
            echo "🔐 Configurando permissões...\n";
            $this->configurePermissions();
            echo "✅ Permissões configuradas\n\n";

            // 5. Criar arquivo .env se não existir
            echo "⚙️  Configurando variáveis de ambiente...\n";
            $this->setupEnvironmentFile();
            echo "✅ Configuração de ambiente concluída\n\n";

            // 6. Limpar cache antigo
            echo "🧹 Limpando cache antigo...\n";
            $this->clearOldCache();
            echo "✅ Cache limpo\n\n";

            echo "🎉 Instalação das melhorias concluída com sucesso!\n";
            echo "📋 Próximos passos:\n";
            echo "   1. Configure o arquivo .env com suas credenciais\n";
            echo "   2. Reinicie o servidor web\n";
            echo "   3. Teste o sistema\n\n";

        } catch (Exception $e) {
            echo "❌ Erro durante a instalação: " . $e->getMessage() . "\n";
            echo "🔄 Considere restaurar o backup se necessário\n";
            return false;
        }

        return true;
    }

    /**
     * Cria tabelas de auditoria e rate limiting
     */
    private function createAuditTables() {
        // Tabela de logs de auditoria
        $auditTable = "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT,
            details JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            endpoint VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_action (action),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_created (created_at),
            INDEX idx_ip (ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // Tabela de rate limiting
        $rateLimitTable = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            request_time INT NOT NULL,
            user_agent TEXT,
            endpoint VARCHAR(255),
            INDEX idx_ip_time (ip_address, request_time),
            INDEX idx_time (request_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->executeMultiple($auditTable);
        $this->db->executeMultiple($rateLimitTable);
    }

    /**
     * Cria diretórios necessários
     */
    private function createDirectories() {
        $directories = [
            BACKUP_PATH,
            __DIR__ . '/cache/',
            __DIR__ . '/logs/',
            __DIR__ . '/temp/'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Configura permissões de arquivos
     */
    private function configurePermissions() {
        $files = [
            __DIR__ . '/logs/',
            __DIR__ . '/cache/',
            BACKUP_PATH
        ];

        foreach ($files as $file) {
            if (is_dir($file)) {
                chmod($file, 0755);
            }
        }
    }

    /**
     * Configura arquivo .env
     */
    private function setupEnvironmentFile() {
        $envFile = __DIR__ . '/.env';
        $exampleFile = __DIR__ . '/env.example';

        if (!file_exists($envFile) && file_exists($exampleFile)) {
            copy($exampleFile, $envFile);
            echo "   📄 Arquivo .env criado a partir do exemplo\n";
            echo "   ⚠️  Configure as credenciais no arquivo .env\n";
        } elseif (file_exists($envFile)) {
            echo "   ✅ Arquivo .env já existe\n";
        } else {
            echo "   ⚠️  Arquivo .env não encontrado, crie manualmente\n";
        }
    }

    /**
     * Limpa cache antigo
     */
    private function clearOldCache() {
        $cacheDir = __DIR__ . '/cache/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Verifica se as melhorias já foram instaladas
     */
    public function isInstalled() {
        try {
            // Verificar se as tabelas existem
            $auditExists = $this->db->selectOne("SHOW TABLES LIKE 'audit_logs'");
            $rateLimitExists = $this->db->selectOne("SHOW TABLES LIKE 'rate_limits'");
            
            return $auditExists && $rateLimitExists;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Desinstala as melhorias (reverte alterações)
     */
    public function uninstall() {
        echo "🔄 Desinstalando melhorias...\n";

        try {
            // Remover tabelas
            $this->db->executeMultiple("DROP TABLE IF EXISTS audit_logs");
            $this->db->executeMultiple("DROP TABLE IF EXISTS rate_limits");
            
            // Limpar cache
            $this->clearOldCache();
            
            echo "✅ Melhorias desinstaladas com sucesso\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Erro ao desinstalar: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Executar instalação se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'install_improvements.php') {
    $installer = new ImprovementsInstaller();
    
    if (isset($_GET['action']) && $_GET['action'] === 'uninstall') {
        $installer->uninstall();
    } elseif ($installer->isInstalled()) {
        echo "ℹ️  As melhorias já foram instaladas\n";
        echo "🔄 Para reinstalar, desinstale primeiro: ?action=uninstall\n";
    } else {
        $installer->install();
    }
}
