<?php
/**
 * Classe para gestão da conexão com a base de dados
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $pdo;

    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }

    /**
     * Estabelece conexão com a base de dados
     */
    public function connect() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                throw new Exception("Erro na conexão com a base de dados: " . $e->getMessage());
            }
        }
        
        return $this->pdo;
    }

    /**
     * Executa uma query SELECT
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro na consulta: " . $e->getMessage());
        }
    }

    /**
     * Executa uma query SELECT e retorna apenas um resultado
     */
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Erro na consulta: " . $e->getMessage());
        }
    }

    /**
     * Executa uma query INSERT
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $this->connect()->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erro na inserção: " . $e->getMessage());
        }
    }

    /**
     * Executa uma query UPDATE
     */
    public function update($sql, $params = []) {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Erro na atualização: " . $e->getMessage());
        }
    }

    /**
     * Executa uma query DELETE
     */
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Erro na exclusão: " . $e->getMessage());
        }
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        return $this->connect()->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit() {
        return $this->connect()->commit();
    }

    /**
     * Desfaz uma transação
     */
    public function rollback() {
        return $this->connect()->rollback();
    }

    /**
     * Verifica se está em uma transação
     */
    public function inTransaction() {
        return $this->connect()->inTransaction();
    }

    /**
     * Executa múltiplas queries (útil para scripts de instalação)
     */
    public function executeMultiple($sql) {
        try {
            $this->connect()->exec($sql);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro na execução múltipla: " . $e->getMessage());
        }
    }
}

