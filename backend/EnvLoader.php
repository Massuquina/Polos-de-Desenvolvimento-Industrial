<?php
/**
 * Carregador de variáveis de ambiente
 * Compatível com PHP 7.4+
 */

class EnvLoader {
    private static $loaded = false;
    private static $env = [];

    /**
     * Carrega variáveis de ambiente do arquivo .env
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }

        $envFile = $path ?: __DIR__ . '/.env';
        
        if (!file_exists($envFile)) {
            // Tentar carregar do arquivo de exemplo se .env não existir
            $exampleFile = __DIR__ . '/env.example';
            if (file_exists($exampleFile)) {
                self::loadFromFile($exampleFile);
            }
            // Se não encontrar nenhum arquivo, usar valores padrão
            self::$loaded = true;
            return;
        }

        self::loadFromFile($envFile);
        self::$loaded = true;
    }

    /**
     * Carrega variáveis de um arquivo específico
     */
    private static function loadFromFile($file) {
        if (!is_readable($file)) {
            return;
        }
        
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (!$lines) {
            return;
        }
        
        foreach ($lines as $line) {
            // Ignorar linhas vazias e comentários
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Processar linha
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Pular se a chave estiver vazia
                if (empty($key)) {
                    continue;
                }

                // Remover aspas se existirem
                if (strlen($value) >= 2 && 
                    (($value[0] === '"' && $value[-1] === '"') || 
                     ($value[0] === "'" && $value[-1] === "'"))) {
                    $value = substr($value, 1, -1);
                }

                self::$env[$key] = $value;
                
                // Definir como variável de ambiente se não existir
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }

    /**
     * Obtém uma variável de ambiente
     */
    public static function get($key, $default = null) {
        self::load();
        
        return $_ENV[$key] ?? self::$env[$key] ?? $default;
    }

    /**
     * Obtém todas as variáveis carregadas
     */
    public static function all() {
        self::load();
        return array_merge($_ENV, self::$env);
    }

    /**
     * Verifica se uma variável existe
     */
    public static function has($key) {
        self::load();
        return array_key_exists($key, $_ENV) || array_key_exists($key, self::$env);
    }
}
