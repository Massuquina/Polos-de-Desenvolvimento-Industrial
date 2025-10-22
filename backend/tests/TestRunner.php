<?php
/**
 * Test Runner para PDI Angola
 * Executa testes unitários básicos
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../Cache.php';
require_once __DIR__ . '/../CSRF.php';
require_once __DIR__ . '/../RateLimiter.php';

class TestRunner {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    private $results = [];

    public function __construct() {
        $this->registerTests();
    }

    /**
     * Registra todos os testes
     */
    private function registerTests() {
        $this->tests = [
            'Database Connection' => [$this, 'testDatabaseConnection'],
            'Cache System' => [$this, 'testCacheSystem'],
            'CSRF Protection' => [$this, 'testCSRFProtection'],
            'Rate Limiting' => [$this, 'testRateLimiting'],
            'Environment Variables' => [$this, 'testEnvironmentVariables']
        ];
    }

    /**
     * Executa todos os testes
     */
    public function runAll() {
        echo "🧪 Executando testes PDI Angola v1.2.0\n";
        echo str_repeat("=", 50) . "\n\n";

        foreach ($this->tests as $name => $test) {
            $this->runTest($name, $test);
        }

        $this->printSummary();
    }

    /**
     * Executa um teste específico
     */
    private function runTest($name, $test) {
        echo "🔍 Testando: {$name}... ";
        
        try {
            $result = call_user_func($test);
            if ($result) {
                echo "✅ PASSOU\n";
                $this->passed++;
                $this->results[] = ['name' => $name, 'status' => 'PASSOU', 'error' => null];
            } else {
                echo "❌ FALHOU\n";
                $this->failed++;
                $this->results[] = ['name' => $name, 'status' => 'FALHOU', 'error' => 'Teste retornou false'];
            }
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            $this->failed++;
            $this->results[] = ['name' => $name, 'status' => 'ERRO', 'error' => $e->getMessage()];
        }
    }

    /**
     * Testa conexão com base de dados
     */
    public function testDatabaseConnection() {
        $db = new Database();
        $result = $db->selectOne("SELECT 1 as test");
        return isset($result['test']) && $result['test'] == 1;
    }

    /**
     * Testa sistema de cache
     */
    public function testCacheSystem() {
        $cache = new Cache();
        
        // Teste básico de set/get
        $key = 'test_key_' . time();
        $value = 'test_value_' . time();
        
        $setResult = $cache->set($key, $value, 60);
        $getValue = $cache->get($key);
        $hasKey = $cache->has($key);
        
        $cache->delete($key);
        
        return $setResult && $getValue === $value && $hasKey;
    }

    /**
     * Testa proteção CSRF
     */
    public function testCSRFProtection() {
        // Teste de geração de token
        $token1 = CSRF::generateToken();
        $token2 = CSRF::generateToken();
        
        // Tokens devem ser diferentes
        if ($token1 === $token2) {
            return false;
        }
        
        // Teste de validação
        $valid = CSRF::validateToken($token1);
        $invalid = CSRF::validateToken('invalid_token');
        
        return $valid && !$invalid;
    }

    /**
     * Testa rate limiting
     */
    public function testRateLimiting() {
        $rateLimiter = new RateLimiter();
        
        // Teste básico de verificação
        $result = $rateLimiter->checkLimit('127.0.0.1');
        
        // Deve permitir pelo menos uma requisição
        return $result === true;
    }

    /**
     * Testa variáveis de ambiente
     */
    public function testEnvironmentVariables() {
        // Verificar se constantes importantes estão definidas
        $required = [
            'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_CHARSET',
            'APP_NAME', 'APP_VERSION', 'JWT_SECRET'
        ];
        
        foreach ($required as $constant) {
            if (!defined($constant)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Imprime resumo dos testes
     */
    private function printSummary() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 RESUMO DOS TESTES\n";
        echo str_repeat("=", 50) . "\n";
        echo "✅ Testes que passaram: {$this->passed}\n";
        echo "❌ Testes que falharam: {$this->failed}\n";
        echo "📈 Taxa de sucesso: " . round(($this->passed / ($this->passed + $this->failed)) * 100, 2) . "%\n\n";

        if ($this->failed > 0) {
            echo "🔍 DETALHES DOS FALHAS:\n";
            echo str_repeat("-", 30) . "\n";
            foreach ($this->results as $result) {
                if ($result['status'] !== 'PASSOU') {
                    echo "❌ {$result['name']}: {$result['error']}\n";
                }
            }
        }

        echo "\n" . ($this->failed === 0 ? "🎉 Todos os testes passaram!" : "⚠️  Alguns testes falharam") . "\n";
    }

    /**
     * Executa apenas um teste específico
     */
    public function runTestByName($testName) {
        if (isset($this->tests[$testName])) {
            $this->runTest($testName, $this->tests[$testName]);
        } else {
            echo "❌ Teste '{$testName}' não encontrado\n";
        }
    }
}

// Executar testes se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'TestRunner.php') {
    $runner = new TestRunner();
    
    if (isset($_GET['test'])) {
        $runner->runTestByName($_GET['test']);
    } else {
        $runner->runAll();
    }
}
