<?php
/**
 * Sistema de Rate Limiting
 * Controla o número de requisições por IP
 */

class RateLimiter {
    private $db;
    private $maxRequests;
    private $windowSeconds;
    private $enabled;

    public function __construct() {
        $this->db = new Database();
        $this->maxRequests = RATE_LIMIT_REQUESTS;
        $this->windowSeconds = RATE_LIMIT_WINDOW;
        $this->enabled = RATE_LIMIT_ENABLED;
    }

    /**
     * Verifica se a requisição está dentro do limite
     */
    public function checkLimit($ip = null) {
        if (!$this->enabled) {
            return true;
        }

        $ip = $ip ?: $this->getClientIP();
        
        // IPs locais têm limite mais permissivo
        if ($this->isLocalIP($ip)) {
            $maxRequests = $this->maxRequests * 10; // 10x mais permissivo para IPs locais
        } else {
            $maxRequests = $this->maxRequests;
        }
        
        $currentTime = time();
        $windowStart = $currentTime - $this->windowSeconds;

        try {
            // Criar tabela se não existir
            $this->createTableIfNotExists();

            // Limpar registros antigos
            $this->cleanOldRecords($windowStart);

            // Contar requisições no período
            $result = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM rate_limits WHERE ip_address = ? AND request_time > ?",
                [$ip, $windowStart]
            );
            
            $count = $result ? (int)$result['count'] : 0;

            if ($count >= $maxRequests) {
                error_log("Rate limit exceeded for IP: $ip ($count/$maxRequests requests)");
                return false;
            }

            // Registrar nova requisição
            $this->recordRequest($ip, $currentTime);

            return true;

        } catch (Exception $e) {
            // Em caso de erro, permitir a requisição mas registrar o erro
            error_log("Rate limiter error: " . $e->getMessage());
            error_log("Rate limiter stack trace: " . $e->getTraceAsString());
            return true;
        }
    }

    /**
     * Obtém o IP do cliente
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Se for uma lista de IPs, pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Verifica se o IP é local/privado
     */
    private function isLocalIP($ip) {
        // IPs locais comuns
        $localIPs = ['127.0.0.1', '::1', 'localhost'];
        
        if (in_array($ip, $localIPs)) {
            return true;
        }
        
        // Verificar se é IP privado
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Cria tabela de rate limiting se não existir
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            request_time INT NOT NULL,
            user_agent TEXT,
            endpoint VARCHAR(255),
            INDEX idx_ip_time (ip_address, request_time),
            INDEX idx_time (request_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->executeMultiple($sql);
    }

    /**
     * Registra uma requisição
     */
    private function recordRequest($ip, $timestamp) {
        $sql = "INSERT INTO rate_limits (ip_address, request_time, user_agent, endpoint) VALUES (?, ?, ?, ?)";
        $this->db->insert($sql, [
            $ip,
            $timestamp,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REQUEST_URI'] ?? ''
        ]);
    }

    /**
     * Limpa registros antigos
     */
    private function cleanOldRecords($windowStart) {
        $this->db->delete("DELETE FROM rate_limits WHERE request_time < ?", [$windowStart]);
    }

    /**
     * Obtém estatísticas de rate limiting
     */
    public function getStats($ip = null) {
        $ip = $ip ?: $this->getClientIP();
        $currentTime = time();
        $windowStart = $currentTime - $this->windowSeconds;

        $stats = $this->db->selectOne(
            "SELECT COUNT(*) as requests, MIN(request_time) as first_request, MAX(request_time) as last_request 
             FROM rate_limits 
             WHERE ip_address = ? AND request_time > ?",
            [$ip, $windowStart]
        );

        return [
            'ip' => $ip,
            'requests' => (int)$stats['requests'],
            'max_requests' => $this->maxRequests,
            'window_seconds' => $this->windowSeconds,
            'remaining' => max(0, $this->maxRequests - $stats['requests']),
            'reset_time' => $currentTime + $this->windowSeconds,
            'first_request' => $stats['first_request'],
            'last_request' => $stats['last_request']
        ];
    }

    /**
     * Limpa rate limits de um IP específico
     */
    public function clearIP($ip) {
        $this->db->delete("DELETE FROM rate_limits WHERE ip_address = ?", [$ip]);
    }

    /**
     * Limpa todos os rate limits
     */
    public function clearAll() {
        $this->db->delete("DELETE FROM rate_limits");
    }
}
