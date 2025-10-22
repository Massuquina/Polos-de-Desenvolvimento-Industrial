<?php
/**
 * Sistema de Cache
 * Suporte para cache em arquivo e Redis
 */

class Cache {
    private $driver;
    private $ttl;
    private $cachePath;
    private $redis;

    public function __construct() {
        $this->driver = CACHE_DRIVER;
        $this->ttl = CACHE_TTL;
        $this->cachePath = __DIR__ . '/cache/';
        
        if (!CACHE_ENABLED) {
            $this->driver = 'none';
        }

        if ($this->driver === 'file') {
            $this->ensureCacheDirectory();
        } elseif ($this->driver === 'redis') {
            $this->initRedis();
        }
    }

    /**
     * Obtém um valor do cache
     */
    public function get($key) {
        if (!$this->isEnabled()) {
            return null;
        }

        switch ($this->driver) {
            case 'file':
                return $this->getFromFile($key);
            case 'redis':
                return $this->getFromRedis($key);
            default:
                return null;
        }
    }

    /**
     * Define um valor no cache
     */
    public function set($key, $value, $ttl = null) {
        if (!$this->isEnabled()) {
            return false;
        }

        $ttl = $ttl ?: $this->ttl;

        switch ($this->driver) {
            case 'file':
                return $this->setToFile($key, $value, $ttl);
            case 'redis':
                return $this->setToRedis($key, $value, $ttl);
            default:
                return false;
        }
    }

    /**
     * Remove um valor do cache
     */
    public function delete($key) {
        if (!$this->isEnabled()) {
            return false;
        }

        switch ($this->driver) {
            case 'file':
                return $this->deleteFromFile($key);
            case 'redis':
                return $this->deleteFromRedis($key);
            default:
                return false;
        }
    }

    /**
     * Limpa todo o cache
     */
    public function clear() {
        if (!$this->isEnabled()) {
            return false;
        }

        switch ($this->driver) {
            case 'file':
                return $this->clearFileCache();
            case 'redis':
                return $this->clearRedisCache();
            default:
                return false;
        }
    }

    /**
     * Verifica se uma chave existe no cache
     */
    public function has($key) {
        return $this->get($key) !== null;
    }

    /**
     * Obtém ou define um valor (cache-aside pattern)
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Cache baseado em arquivo
     */
    private function getFromFile($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        
        if (!$data || $data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    private function setToFile($key, $value, $ttl) {
        $file = $this->getCacheFile($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];

        return file_put_contents($file, json_encode($data)) !== false;
    }

    private function deleteFromFile($key) {
        $file = $this->getCacheFile($key);
        return file_exists($file) ? unlink($file) : true;
    }

    private function clearFileCache() {
        $files = glob($this->cachePath . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    private function getCacheFile($key) {
        return $this->cachePath . md5($key) . '.cache';
    }

    /**
     * Cache baseado em Redis
     */
    private function initRedis() {
        if (!class_exists('Redis')) {
            $this->driver = 'file';
            return;
        }

        try {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            $this->driver = 'file';
        }
    }

    private function getFromRedis($key) {
        if (!$this->redis) {
            return null;
        }

        $value = $this->redis->get($key);
        return $value ? json_decode($value, true) : null;
    }

    private function setToRedis($key, $value, $ttl) {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->setex($key, $ttl, json_encode($value));
    }

    private function deleteFromRedis($key) {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->del($key) > 0;
    }

    private function clearRedisCache() {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->flushDB();
    }

    /**
     * Utilitários
     */
    private function isEnabled() {
        return CACHE_ENABLED && $this->driver !== 'none';
    }

    private function ensureCacheDirectory() {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Gera chave de cache baseada em parâmetros
     */
    public static function generateKey($prefix, $params = []) {
        $key = $prefix;
        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }
        return $key;
    }

    /**
     * Obtém estatísticas do cache
     */
    public function getStats() {
        switch ($this->driver) {
            case 'file':
                $files = glob($this->cachePath . '*.cache');
                $totalSize = 0;
                $expiredCount = 0;
                
                foreach ($files as $file) {
                    $totalSize += filesize($file);
                    $data = json_decode(file_get_contents($file), true);
                    if ($data && $data['expires'] < time()) {
                        $expiredCount++;
                    }
                }
                
                return [
                    'driver' => 'file',
                    'files_count' => count($files),
                    'total_size' => $totalSize,
                    'expired_files' => $expiredCount
                ];
                
            case 'redis':
                if (!$this->redis) {
                    return ['driver' => 'redis', 'status' => 'disconnected'];
                }
                
                return [
                    'driver' => 'redis',
                    'status' => 'connected',
                    'keys_count' => $this->redis->dbSize(),
                    'memory_usage' => $this->redis->info()['used_memory_human'] ?? 'unknown'
                ];
                
            default:
                return ['driver' => 'none', 'enabled' => false];
        }
    }
}
