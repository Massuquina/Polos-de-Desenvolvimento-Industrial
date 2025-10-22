<?php
/**
 * Classe para logging melhorado
 * Melhoria de monitoramento para a plataforma PDI Angola
 */

class Logger {
    private $logFile;
    private $logLevel;
    
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    const CRITICAL = 4;
    
    private $levels = [
        self::DEBUG => 'DEBUG',
        self::INFO => 'INFO',
        self::WARNING => 'WARNING',
        self::ERROR => 'ERROR',
        self::CRITICAL => 'CRITICAL'
    ];
    
    public function __construct($logFile = null, $logLevel = self::INFO) {
        $this->logFile = $logFile ?: LOG_FILE;
        $this->logLevel = $this->parseLogLevel($logLevel);
        
        // Criar diretório de logs se não existir
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Converte string de nível para constante
     */
    private function parseLogLevel($level) {
        if (is_string($level)) {
            switch (strtoupper($level)) {
                case 'DEBUG': return self::DEBUG;
                case 'INFO': return self::INFO;
                case 'WARNING': return self::WARNING;
                case 'ERROR': return self::ERROR;
                case 'CRITICAL': return self::CRITICAL;
                default: return self::INFO;
            }
        }
        return $level;
    }
    
    /**
     * Escreve uma mensagem no log
     */
    private function write($level, $message, $context = []) {
        if ($level < $this->logLevel) {
            return; // Não loga se o nível for menor que o configurado
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $levelName = $this->levels[$level];
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        // Informações da requisição
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
        
        // Informações do usuário (se disponível)
        $userId = 'guest';
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['id'] ?? 'guest';
        }
        
        $logEntry = sprintf(
            "[%s] %s: %s | IP: %s | Method: %s | URI: %s | User: %s | UserAgent: %s%s\n",
            $timestamp,
            $levelName,
            $message,
            $ip,
            $method,
            $uri,
            $userId,
            $userAgent,
            $contextStr
        );
        
        // Escrever no arquivo de log
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Se for erro crítico, também enviar por email (se configurado)
        if ($level >= self::ERROR) {
            $this->notifyError($levelName, $message, $context);
        }
    }
    
    /**
     * Log de debug
     */
    public function debug($message, $context = []) {
        $this->write(self::DEBUG, $message, $context);
    }
    
    /**
     * Log de informação
     */
    public function info($message, $context = []) {
        $this->write(self::INFO, $message, $context);
    }
    
    /**
     * Log de aviso
     */
    public function warning($message, $context = []) {
        $this->write(self::WARNING, $message, $context);
    }
    
    /**
     * Log de erro
     */
    public function error($message, $context = []) {
        $this->write(self::ERROR, $message, $context);
    }
    
    /**
     * Log de erro crítico
     */
    public function critical($message, $context = []) {
        $this->write(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log de atividade de usuário
     */
    public function userActivity($action, $resource = null, $resourceId = null, $details = []) {
        $userId = 'guest';
        $userName = 'Guest';
        
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['id'] ?? 'guest';
            $userName = $_SESSION['user']['nome'] ?? 'Guest';
        }
        
        $context = array_merge([
            'user_id' => $userId,
            'user_name' => $userName,
            'action' => $action,
            'resource' => $resource,
            'resource_id' => $resourceId
        ], $details);
        
        $message = sprintf(
            "User Activity: %s performed '%s'%s%s",
            $userName,
            $action,
            $resource ? " on {$resource}" : '',
            $resourceId ? " (ID: {$resourceId})" : ''
        );
        
        $this->info($message, $context);
    }
    
    /**
     * Log de tentativa de login
     */
    public function loginAttempt($email, $success, $reason = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $context = [
            'email' => $email,
            'success' => $success,
            'ip' => $ip,
            'user_agent' => $userAgent
        ];
        
        if ($reason) {
            $context['reason'] = $reason;
        }
        
        $message = sprintf(
            "Login attempt for '%s' from IP %s: %s%s",
            $email,
            $ip,
            $success ? 'SUCCESS' : 'FAILED',
            $reason ? " ({$reason})" : ''
        );
        
        if ($success) {
            $this->info($message, $context);
        } else {
            $this->warning($message, $context);
        }
    }
    
    /**
     * Log de erro de API
     */
    public function apiError($endpoint, $method, $error, $statusCode = 500) {
        $context = [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'error' => $error
        ];
        
        $message = sprintf(
            "API Error: %s %s returned %d - %s",
            $method,
            $endpoint,
            $statusCode,
            $error
        );
        
        $this->error($message, $context);
    }
    
    /**
     * Log de performance (tempo de execução)
     */
    public function performance($operation, $executionTime, $details = []) {
        $context = array_merge([
            'operation' => $operation,
            'execution_time_ms' => round($executionTime * 1000, 2)
        ], $details);
        
        $message = sprintf(
            "Performance: %s took %.2f ms",
            $operation,
            $executionTime * 1000
        );
        
        // Log como warning se demorar mais de 1 segundo
        if ($executionTime > 1.0) {
            $this->warning($message, $context);
        } else {
            $this->debug($message, $context);
        }
    }
    
    /**
     * Log de acesso a dados sensíveis
     */
    public function dataAccess($table, $action, $recordId = null, $fields = []) {
        $userId = 'guest';
        $userName = 'Guest';
        
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['id'] ?? 'guest';
            $userName = $_SESSION['user']['nome'] ?? 'Guest';
        }
        
        $context = [
            'user_id' => $userId,
            'user_name' => $userName,
            'table' => $table,
            'action' => $action,
            'record_id' => $recordId,
            'fields' => $fields
        ];
        
        $message = sprintf(
            "Data Access: %s performed '%s' on table '%s'%s",
            $userName,
            $action,
            $table,
            $recordId ? " (Record ID: {$recordId})" : ''
        );
        
        $this->info($message, $context);
    }
    
    /**
     * Log de tentativa de acesso não autorizado
     */
    public function unauthorizedAccess($resource, $requiredPermission, $userPermissions = []) {
        $userId = 'guest';
        $userName = 'Guest';
        
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['id'] ?? 'guest';
            $userName = $_SESSION['user']['nome'] ?? 'Guest';
        }
        
        $context = [
            'user_id' => $userId,
            'user_name' => $userName,
            'resource' => $resource,
            'required_permission' => $requiredPermission,
            'user_permissions' => $userPermissions
        ];
        
        $message = sprintf(
            "Unauthorized Access: %s attempted to access '%s' without '%s' permission",
            $userName,
            $resource,
            $requiredPermission
        );
        
        $this->warning($message, $context);
    }
    
    /**
     * Notifica erros críticos (placeholder para implementação futura de email)
     */
    private function notifyError($level, $message, $context) {
        // TODO: Implementar notificação por email para erros críticos
        // Esta funcionalidade pode ser implementada posteriormente
    }
    
    /**
     * Rotaciona logs antigos
     */
    public function rotateLogs($maxSize = 10485760, $maxFiles = 5) { // 10MB por padrão
        if (!file_exists($this->logFile)) {
            return;
        }
        
        if (filesize($this->logFile) > $maxSize) {
            // Mover logs antigos
            for ($i = $maxFiles - 1; $i > 0; $i--) {
                $oldFile = $this->logFile . '.' . $i;
                $newFile = $this->logFile . '.' . ($i + 1);
                
                if (file_exists($oldFile)) {
                    if ($i == $maxFiles - 1) {
                        unlink($oldFile); // Remover o mais antigo
                    } else {
                        rename($oldFile, $newFile);
                    }
                }
            }
            
            // Mover o arquivo atual
            rename($this->logFile, $this->logFile . '.1');
            
            $this->info('Log rotated due to size limit');
        }
    }
    
    /**
     * Obtém estatísticas dos logs
     */
    public function getStats($days = 7) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $stats = [
            'total_entries' => 0,
            'by_level' => [],
            'by_day' => [],
            'top_ips' => [],
            'top_users' => [],
            'errors' => []
        ];
        
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $handle = fopen($this->logFile, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2}).*?\] (\w+):.*? IP: ([^\|]+).*? User: ([^\|]+)/', $line, $matches)) {
                    $date = $matches[1];
                    $level = $matches[2];
                    $ip = trim($matches[3]);
                    $user = trim($matches[4]);
                    
                    if ($date >= $cutoffDate) {
                        $stats['total_entries']++;
                        
                        // Por nível
                        $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
                        
                        // Por dia
                        $stats['by_day'][$date] = ($stats['by_day'][$date] ?? 0) + 1;
                        
                        // Por IP
                        $stats['top_ips'][$ip] = ($stats['top_ips'][$ip] ?? 0) + 1;
                        
                        // Por usuário
                        $stats['top_users'][$user] = ($stats['top_users'][$user] ?? 0) + 1;
                        
                        // Erros
                        if (in_array($level, ['ERROR', 'CRITICAL'])) {
                            $stats['errors'][] = [
                                'date' => $date,
                                'level' => $level,
                                'message' => substr($line, 0, 200) . '...'
                            ];
                        }
                    }
                }
            }
            fclose($handle);
        }
        
        // Ordenar estatísticas
        arsort($stats['top_ips']);
        arsort($stats['top_users']);
        $stats['top_ips'] = array_slice($stats['top_ips'], 0, 10, true);
        $stats['top_users'] = array_slice($stats['top_users'], 0, 10, true);
        $stats['errors'] = array_slice($stats['errors'], -20); // Últimos 20 erros
        
        return $stats;
    }
}

// Instância global do logger
$GLOBALS['logger'] = new Logger();

/**
 * Função helper para logging rápido
 */
function logMessage($level, $message, $context = []) {
    global $logger;
    
    switch (strtolower($level)) {
        case 'debug':
            $logger->debug($message, $context);
            break;
        case 'info':
            $logger->info($message, $context);
            break;
        case 'warning':
            $logger->warning($message, $context);
            break;
        case 'error':
            $logger->error($message, $context);
            break;
        case 'critical':
            $logger->critical($message, $context);
            break;
    }
}
?>

