<?php
/**
 * Sistema de Logs de Auditoria
 * Registra todas as ações importantes do sistema
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/SimpleLogger.php';

class AuditLogger {
    private $db;
    private $logger;

    public function __construct() {
        $this->db = new Database();
        
        // Tentar obter logger global, criar um simples se não existir
        global $logger;
        if ($logger && method_exists($logger, 'info')) {
            $this->logger = $logger;
        } else {
            $this->logger = new SimpleLogger();
        }
    }

    /**
     * Registra uma ação de auditoria
     */
    public function log($action, $entity, $entityId = null, $details = [], $userId = null) {
        try {
            $this->createTableIfNotExists();

            $userId = $userId ?: $this->getCurrentUserId();
            $ip = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $endpoint = $_SERVER['REQUEST_URI'] ?? '';

            $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent, endpoint, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $this->db->insert($sql, [
                $userId,
                $action,
                $entity,
                $entityId,
                json_encode($details, JSON_UNESCAPED_UNICODE),
                $ip,
                $userAgent,
                $endpoint
            ]);

            // Log também no arquivo de log
            $this->logger->info("Audit: {$action} on {$entity}" . ($entityId ? " (ID: {$entityId})" : ""), [
                'user_id' => $userId,
                'ip' => $ip,
                'details' => $details
            ]);

        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }

    /**
     * Logs específicos para diferentes entidades
     */
    public function logUserAction($action, $userId, $details = []) {
        $this->log($action, 'user', $userId, $details);
    }

    public function logInfraestruturaAction($action, $infraestruturaId, $details = []) {
        $this->log($action, 'infraestrutura', $infraestruturaId, $details);
    }

    public function logLoteAction($action, $loteId, $details = []) {
        $this->log($action, 'lote', $loteId, $details);
    }

    public function logEmpresaAction($action, $empresaId, $details = []) {
        $this->log($action, 'empresa', $empresaId, $details);
    }

    public function logPoloAction($action, $poloId, $details = []) {
        $this->log($action, 'polo', $poloId, $details);
    }

    public function logLogin($userId, $success = true, $details = []) {
        $action = $success ? 'login_success' : 'login_failed';
        $this->log($action, 'auth', $userId, $details);
    }

    public function logLogout($userId) {
        $this->log('logout', 'auth', $userId);
    }

    public function logPermissionDenied($action, $entity, $entityId = null, $details = []) {
        $this->log('permission_denied', $entity, $entityId, array_merge($details, ['attempted_action' => $action]));
    }

    public function logDataExport($entity, $filters = [], $format = 'html') {
        $this->log('data_export', $entity, null, [
            'filters' => $filters,
            'format' => $format
        ]);
    }

    public function logDataImport($entity, $recordsCount, $details = []) {
        $this->log('data_import', $entity, null, array_merge($details, [
            'records_count' => $recordsCount
        ]));
    }

    /**
     * Obtém logs de auditoria com filtros
     */
    public function getLogs($filters = []) {
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = "al.action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "al.entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "al.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "al.created_at <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['ip_address'])) {
            $where[] = "al.ip_address = ?";
            $params[] = $filters['ip_address'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit = $filters['limit'] ?? 100;
        $offset = $filters['offset'] ?? 0;

        $sql = "SELECT al.*, u.nome as user_name, u.email as user_email
                FROM audit_logs al
                LEFT JOIN usuarios u ON al.user_id = u.id
                {$whereClause}
                ORDER BY al.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->select($sql, $params);
    }

    /**
     * Obtém estatísticas de auditoria
     */
    public function getStats($days = 30) {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $stats = [];

        // Ações por tipo
        $actions = $this->db->select(
            "SELECT action, COUNT(*) as count 
             FROM audit_logs 
             WHERE created_at >= ? 
             GROUP BY action 
             ORDER BY count DESC",
            [$dateFrom]
        );
        $stats['actions'] = $actions;

        // Ações por usuário
        $users = $this->db->select(
            "SELECT u.nome, u.email, COUNT(*) as count 
             FROM audit_logs al
             LEFT JOIN usuarios u ON al.user_id = u.id
             WHERE al.created_at >= ? 
             GROUP BY al.user_id, u.nome, u.email
             ORDER BY count DESC
             LIMIT 10",
            [$dateFrom]
        );
        $stats['top_users'] = $users;

        // Ações por entidade
        $entities = $this->db->select(
            "SELECT entity_type, COUNT(*) as count 
             FROM audit_logs 
             WHERE created_at >= ? 
             GROUP BY entity_type 
             ORDER BY count DESC",
            [$dateFrom]
        );
        $stats['entities'] = $entities;

        // Ações por dia
        $daily = $this->db->select(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM audit_logs 
             WHERE created_at >= ? 
             GROUP BY DATE(created_at) 
             ORDER BY date DESC",
            [$dateFrom]
        );
        $stats['daily'] = $daily;

        return $stats;
    }

    /**
     * Limpa logs antigos
     */
    public function cleanOldLogs($days = 365) {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $this->db->delete(
            "DELETE FROM audit_logs WHERE created_at < ?",
            [$dateFrom]
        );

        $this->logger->info("Cleaned {$deleted} old audit logs");
        return $deleted;
    }

    /**
     * Utilitários
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
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
        
        $this->db->executeMultiple($sql);
    }

    private function getCurrentUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user']['id'] ?? null;
    }

    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
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
}
