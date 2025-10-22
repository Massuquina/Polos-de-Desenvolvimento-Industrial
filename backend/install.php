<?php
/**
 * Script de instalação da aplicação PDI Angola
 * APENAS PARA DESENVOLVIMENTO - Bloqueado em produção
 */

require_once 'config.php';

// Bloquear acesso em produção
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    http_response_code(404);
    echo '<h1>404 - Página não encontrada</h1>';
    exit();
}

class Installer {
    private $pdo;

    public function __construct() {
        try {
            // Conectar ao MySQL sem especificar a base de dados
            $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Erro na conexão: " . $e->getMessage());
        }
    }

    /**
     * Executa a instalação completa
     */
    public function install() {
        echo "<h1>Instalação PDI Angola</h1>\n";
        
        try {
            echo "<p>Criando base de dados...</p>\n";
            $this->createDatabase();
            
            echo "<p>Executando schema da base de dados...</p>\n";
            $this->executeSchema();
            
            echo "<p>Inserindo dados iniciais...</p>\n";
            $this->insertInitialData();
            
            echo "<p><strong>Instalação concluída com sucesso!</strong></p>\n";
            echo "<p>Usuário administrador padrão:</p>\n";
            echo "<ul>\n";
            echo "<li>Email: admin@pdi.gov.ao</li>\n";
            echo "<li>Senha: admin123</li>\n";
            echo "</ul>\n";
            echo "<p><em>Altere a senha após o primeiro login!</em></p>\n";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'><strong>Erro na instalação:</strong> " . $e->getMessage() . "</p>\n";
        }
    }

    /**
     * Cria a base de dados
     */
    private function createDatabase() {
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
        
        // Selecionar a base de dados criada
        $this->pdo->exec("USE " . DB_NAME);
    }

    /**
     * Executa o schema da base de dados
     */
    private function executeSchema() {
        $schemaFile = __DIR__ . '/../database/database_schema.sql';
        
        if (!file_exists($schemaFile)) {
            throw new Exception("Arquivo de schema não encontrado: $schemaFile");
        }
        
        $sql = file_get_contents($schemaFile);
        
        // Remover a linha USE database pois já estamos conectados
        $sql = preg_replace('/USE\s+\w+;/', '', $sql);
        
        // Dividir em statements individuais
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->pdo->exec($statement);
            }
        }
    }

    /**
     * Insere dados iniciais
     */
    private function insertInitialData() {
        $dataFile = __DIR__ . '/../database/insert_data.sql';
        
        if (!file_exists($dataFile)) {
            throw new Exception("Arquivo de dados iniciais não encontrado: $dataFile");
        }
        
        $sql = file_get_contents($dataFile);
        
        // Remover a linha USE database pois já estamos conectados
        $sql = preg_replace('/USE\s+\w+;/', '', $sql);
        
        // Dividir em statements individuais
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->pdo->exec($statement);
            }
        }
    }

    /**
     * Verifica se a instalação já foi executada
     */
    public function isInstalled() {
        try {
            $this->pdo->exec("USE " . DB_NAME);
            $result = $this->pdo->query("SHOW TABLES LIKE 'usuarios'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Verificar se é uma requisição de instalação
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $installer = new Installer();
    
    if ($installer->isInstalled()) {
        echo "<h1>PDI Angola</h1>\n";
        echo "<p>A aplicação já está instalada.</p>\n";
        echo "<p><a href='../frontend/'>Aceder à aplicação</a></p>\n";
    } else {
        echo "<h1>Instalação PDI Angola</h1>\n";
        echo "<p>A aplicação não está instalada.</p>\n";
        echo "<form method='post'>\n";
        echo "<button type='submit'>Instalar Agora</button>\n";
        echo "</form>\n";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installer = new Installer();
    $installer->install();
    echo "<p><a href='../frontend/'>Aceder à aplicação</a></p>\n";
}
?>

