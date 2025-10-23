<?php
/**
 * Logger simples para quando o logger principal não está disponível
 * Usado como fallback em sistemas que não têm logger configurado
 */

class SimpleLogger {
    private $logFile;
    
    public function __construct($logFile = null) {
        $this->logFile = $logFile ?: __DIR__ . '/logs/simple.log';
        
        // Garantir que o diretório de logs existe
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    /**
     * Registra uma mensagem de log com formato melhorado
     */
    private function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        
        // Adicionar informações do usuário ao contexto se disponível
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $context['user_id'] = $_SESSION['user']['id'] ?? 'unknown';
            $context['user_email'] = $_SESSION['user']['email'] ?? 'unknown';
        }
        
        // Adicionar informações do request
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $context['request_uri'] = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        // Escrever no arquivo de log
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Também escrever no error_log do PHP se for ERROR
        if ($level === 'ERROR') {
            error_log("[SimpleLogger] {$message}");
        }
        
        // Para erros críticos, enviar notificação (implementação futura)
        if ($level === 'CRITICAL' || $level === 'EMERGENCY') {
            // TODO: Implementar sistema de notificação para erros críticos
        }
    }
    
    /**
     * Limpa logs antigos (mais de 30 dias)
     */
    public function cleanOldLogs($days = 30) {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES);
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $newLines = [];
        
        foreach ($lines as $line) {
            // Extrair timestamp da linha
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $lineTime = strtotime($matches[1]);
                if ($lineTime > $cutoffTime) {
                    $newLines[] = $line;
                }
            }
        }
        
        file_put_contents($this->logFile, implode(PHP_EOL, $newLines) . PHP_EOL);
    }
}
