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
    
    private function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        // Escrever no arquivo de log
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Também escrever no error_log do PHP se for ERROR
        if ($level === 'ERROR') {
            error_log("[SimpleLogger] {$message}");
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
