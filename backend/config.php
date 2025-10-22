<?php
/**
 * Configurações da aplicação PDI Angola
 * Compatível com WAMP Server no Windows 11
 * Versão 1.2.0 - Melhorias de segurança e performance
 */

// Carregar variáveis de ambiente
require_once __DIR__ . '/EnvLoader.php';
EnvLoader::load();

// Configurações da base de dados
define('DB_HOST', EnvLoader::get('DB_HOST', 'localhost'));
define('DB_NAME', EnvLoader::get('DB_NAME', 'pdi_angola'));
define('DB_USER', EnvLoader::get('DB_USER', 'root'));
define('DB_PASS', EnvLoader::get('DB_PASS', '')); // Senha vazia por padrão no WAMP
define('DB_CHARSET', EnvLoader::get('DB_CHARSET', 'utf8mb4'));

// Configurações da aplicação
define('APP_NAME', EnvLoader::get('APP_NAME', 'PDI Angola - Gestão de Infraestruturas'));
define('APP_VERSION', EnvLoader::get('APP_VERSION', '1.2.0'));
define('APP_URL', EnvLoader::get('APP_URL', 'http://localhost/pdi_angola'));

// Configurações de segurança
define('JWT_SECRET', EnvLoader::get('JWT_SECRET', 'pdi_angola_secret_key_2025_change_in_production'));
define('PASSWORD_SALT', EnvLoader::get('PASSWORD_SALT', 'pdi_angola_salt_2025_change_in_production'));
define('CSRF_SECRET', EnvLoader::get('CSRF_SECRET', 'pdi_angola_csrf_2025_change_in_production'));

// Configurações de sessão
define('SESSION_LIFETIME', (int)EnvLoader::get('SESSION_LIFETIME', 3600)); // 1 hora

// Configurações de upload
define('UPLOAD_MAX_SIZE', (int)EnvLoader::get('UPLOAD_MAX_SIZE', 5 * 1024 * 1024)); // 5MB
define('UPLOAD_ALLOWED_TYPES', explode(',', EnvLoader::get('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png,pdf,doc,docx')));

// Configurações de paginação
define('ITEMS_PER_PAGE', (int)EnvLoader::get('ITEMS_PER_PAGE', 20));

// Configurações de log
define('LOG_LEVEL', EnvLoader::get('LOG_LEVEL', 'INFO')); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', EnvLoader::get('LOG_FILE', __DIR__ . '/logs/app.log'));

// Configurações de cache
define('CACHE_ENABLED', EnvLoader::get('CACHE_ENABLED', 'true') === 'true');
define('CACHE_DRIVER', EnvLoader::get('CACHE_DRIVER', 'file'));
define('CACHE_TTL', (int)EnvLoader::get('CACHE_TTL', 3600));

// Configurações de rate limiting
define('RATE_LIMIT_ENABLED', EnvLoader::get('RATE_LIMIT_ENABLED', 'true') === 'true');
define('RATE_LIMIT_REQUESTS', (int)EnvLoader::get('RATE_LIMIT_REQUESTS', 100));
define('RATE_LIMIT_WINDOW', (int)EnvLoader::get('RATE_LIMIT_WINDOW', 3600));

// Configurações de backup
define('BACKUP_ENABLED', EnvLoader::get('BACKUP_ENABLED', 'true') === 'true');
define('BACKUP_PATH', EnvLoader::get('BACKUP_PATH', __DIR__ . '/backups/'));
define('BACKUP_RETENTION_DAYS', (int)EnvLoader::get('BACKUP_RETENTION_DAYS', 30));

// Timezone
date_default_timezone_set('Africa/Luanda');

// ConfiguraÃ§Ãµes CORS seguras
// Use apenas esquema+host+porta (origem), sem caminho
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:5500',
    'http://localhost:3000',
    'http://127.0.0.1',
    'http://127.0.0.1:5500',
    'http://127.0.0.1:3000'
]);

// Headers para CORS (seguro com whitelist)
if (php_sapi_name() !== 'cli' && php_sapi_name() !== 'phpdbg') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, ALLOWED_ORIGINS)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    } else {
        $originHost = (isset($_SERVER['HTTP_HOST']) ? ('http://' . $_SERVER['HTTP_HOST']) : 'http://localhost');
        header('Access-Control-Allow-Origin: ' . $originHost);
    }
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
}

// Responder a requisiÃ§Ãµes OPTIONS (preflight)
if (($_SERVER['REQUEST_METHOD'] ?? null) === 'OPTIONS' && php_sapi_name() !== 'cli' && php_sapi_name() !== 'phpdbg') {
    http_response_code(200);
    exit();
}

// Definir ambiente (development ou production)
define('ENVIRONMENT', 'development');

// ConfiguraÃ§Ã£o de erro reporting
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}


// Configurações de email/SMTP
define('SMTP_HOST', EnvLoader::get('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int)EnvLoader::get('SMTP_PORT', 587));
define('SMTP_USERNAME', EnvLoader::get('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', EnvLoader::get('SMTP_PASSWORD', ''));
define('SMTP_SECURE', EnvLoader::get('SMTP_SECURE', 'tls'));
define('SMTP_SENDER_EMAIL', EnvLoader::get('SMTP_SENDER_EMAIL', ''));
define('SMTP_SENDER_NAME', EnvLoader::get('SMTP_SENDER_NAME', 'PDI Angola - Sistema de Gestão'));
define('EMAIL_ENABLED', EnvLoader::get('EMAIL_ENABLED', 'false') === 'true');
define('EMAIL_NATIVE_FALLBACK', EnvLoader::get('EMAIL_NATIVE_FALLBACK', 'false') === 'true');
define('SMTP_DEBUG', EnvLoader::get('SMTP_DEBUG', 'false') === 'true');

// Popular $_ENV para compatibilidade
$_ENV['SMTP_HOST'] = SMTP_HOST;
$_ENV['SMTP_PORT'] = SMTP_PORT;
$_ENV['SMTP_USERNAME'] = SMTP_USERNAME;
$_ENV['SMTP_PASSWORD'] = SMTP_PASSWORD;
$_ENV['SMTP_SECURE'] = SMTP_SECURE;
$_ENV['FROM_EMAIL'] = SMTP_SENDER_EMAIL;
$_ENV['FROM_NAME'] = SMTP_SENDER_NAME;
$_ENV['EMAIL_ENABLED'] = EMAIL_ENABLED ? 'true' : 'false';
$_ENV['EMAIL_NATIVE_FALLBACK'] = EMAIL_NATIVE_FALLBACK ? 'true' : 'false';
$_ENV['SMTP_DEBUG'] = SMTP_DEBUG ? 'true' : 'false';

