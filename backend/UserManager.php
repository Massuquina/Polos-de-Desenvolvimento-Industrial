<?php
/**
 * Sistema de Gestão de Utilizadores
 * Apenas para administradores
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/SimpleLogger.php';

class UserManager {
    private $db;
    private $logger;

    public function __construct() {
        $this->db = new Database();
        $this->logger = new SimpleLogger();
    }

    /**
     * Cria um novo utilizador (apenas administradores)
     */
    public function createUser($userData, $adminUserId) {
        try {
            // Verificar se o admin tem permissão
            if (!$this->isAdmin($adminUserId)) {
                throw new Exception("Apenas administradores podem criar utilizadores");
            }

            // Validar dados obrigatórios
            $this->validateUserData($userData);

            // Verificar se email já existe
            if ($this->emailExists($userData['email'])) {
                throw new Exception("Email já está em uso");
            }

            // Hash da senha
            $hashedPassword = password_hash($userData['senha'], PASSWORD_DEFAULT);

            // Inserir utilizador
            $sql = "INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $userId = $this->db->insert($sql, [
                $userData['nome'],
                $userData['email'],
                $hashedPassword,
                $userData['nivel_acesso_id'],
                $userData['ativo'] ?? 1
            ]);

            // Log de auditoria
            $this->logger->info("Utilizador criado", [
                'user_id' => $userId,
                'email' => $userData['email'],
                'nivel_acesso_id' => $userData['nivel_acesso_id'],
                'created_by' => $adminUserId
            ]);

            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'Utilizador criado com sucesso'
            ];

        } catch (Exception $e) {
            $this->logger->error("Erro ao criar utilizador: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Atualiza um utilizador existente
     */
    public function updateUser($userId, $userData, $adminUserId) {
        try {
            // Verificar se o admin tem permissão
            if (!$this->isAdmin($adminUserId)) {
                throw new Exception("Apenas administradores podem atualizar utilizadores");
            }

            // Verificar se utilizador existe
            if (!$this->userExists($userId)) {
                throw new Exception("Utilizador não encontrado");
            }

            $updateFields = [];
            $params = [];

            // Atualizar nome se fornecido
            if (isset($userData['nome'])) {
                $updateFields[] = "nome = ?";
                $params[] = $userData['nome'];
            }

            // Atualizar email se fornecido
            if (isset($userData['email'])) {
                if ($this->emailExists($userData['email'], $userId)) {
                    throw new Exception("Email já está em uso por outro utilizador");
                }
                $updateFields[] = "email = ?";
                $params[] = $userData['email'];
            }

            // Atualizar senha se fornecida
            if (isset($userData['senha']) && !empty($userData['senha'])) {
                $updateFields[] = "senha = ?";
                $params[] = password_hash($userData['senha'], PASSWORD_DEFAULT);
            }

            // Atualizar nível de acesso se fornecido
            if (isset($userData['nivel_acesso_id'])) {
                $updateFields[] = "nivel_acesso_id = ?";
                $params[] = $userData['nivel_acesso_id'];
            }

            // Atualizar status se fornecido
            if (isset($userData['ativo'])) {
                $updateFields[] = "ativo = ?";
                $params[] = $userData['ativo'];
            }

            if (empty($updateFields)) {
                throw new Exception("Nenhum campo para atualizar");
            }

            $updateFields[] = "updated_at = NOW()";
            $params[] = $userId;

            $sql = "UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $affected = $this->db->update($sql, $params);

            if ($affected > 0) {
                $this->logger->info("Utilizador atualizado", [
                    'user_id' => $userId,
                    'updated_by' => $adminUserId,
                    'fields' => array_keys($userData)
                ]);

                return [
                    'success' => true,
                    'message' => 'Utilizador atualizado com sucesso'
                ];
            } else {
                throw new Exception("Nenhuma alteração realizada");
            }

        } catch (Exception $e) {
            $this->logger->error("Erro ao atualizar utilizador: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lista todos os utilizadores
     */
    public function getUsers($adminUserId) {
        try {
            // Verificar se o admin tem permissão
            if (!$this->isAdmin($adminUserId)) {
                throw new Exception("Apenas administradores podem listar utilizadores");
            }

            $sql = "SELECT u.id, u.nome, u.email, u.ativo, u.ultimo_login, u.created_at, u.updated_at,
                           na.nome as nivel_nome, na.permissoes
                    FROM usuarios u 
                    JOIN niveis_acesso na ON u.nivel_acesso_id = na.id 
                    ORDER BY u.created_at DESC";

            $users = $this->db->select($sql);

            // Remover senhas dos dados
            foreach ($users as &$user) {
                unset($user['senha']);
                $user['permissoes'] = json_decode($user['permissoes'], true);
            }

            return [
                'success' => true,
                'users' => $users
            ];

        } catch (Exception $e) {
            $this->logger->error("Erro ao listar utilizadores: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém um utilizador específico
     */
    public function getUser($userId, $adminUserId) {
        try {
            // Verificar se o admin tem permissão
            if (!$this->isAdmin($adminUserId)) {
                throw new Exception("Apenas administradores podem visualizar utilizadores");
            }

            $sql = "SELECT u.id, u.nome, u.email, u.ativo, u.ultimo_login, u.created_at, u.updated_at,
                           na.nome as nivel_nome, na.permissoes
                    FROM usuarios u 
                    JOIN niveis_acesso na ON u.nivel_acesso_id = na.id 
                    WHERE u.id = ?";

            $user = $this->db->selectOne($sql, [$userId]);

            if (!$user) {
                throw new Exception("Utilizador não encontrado");
            }

            // Remover senha dos dados
            unset($user['senha']);
            $user['permissoes'] = json_decode($user['permissoes'], true);

            return [
                'success' => true,
                'user' => $user
            ];

        } catch (Exception $e) {
            $this->logger->error("Erro ao obter utilizador: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ativa/desativa um utilizador
     */
    public function toggleUserStatus($userId, $adminUserId) {
        try {
            // Verificar se o admin tem permissão
            if (!$this->isAdmin($adminUserId)) {
                throw new Exception("Apenas administradores podem alterar status de utilizadores");
            }

            // Verificar se utilizador existe
            if (!$this->userExists($userId)) {
                throw new Exception("Utilizador não encontrado");
            }

            $sql = "UPDATE usuarios SET ativo = NOT ativo, updated_at = NOW() WHERE id = ?";
            $affected = $this->db->update($sql, [$userId]);

            if ($affected > 0) {
                $this->logger->info("Status do utilizador alterado", [
                    'user_id' => $userId,
                    'changed_by' => $adminUserId
                ]);

                return [
                    'success' => true,
                    'message' => 'Status do utilizador alterado com sucesso'
                ];
            } else {
                throw new Exception("Falha ao alterar status do utilizador");
            }

        } catch (Exception $e) {
            $this->logger->error("Erro ao alterar status do utilizador: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém níveis de acesso disponíveis
     */
    public function getAccessLevels() {
        try {
            $sql = "SELECT * FROM niveis_acesso ORDER BY nome";
            $levels = $this->db->select($sql);

            // Decodificar permissões
            foreach ($levels as &$level) {
                $level['permissoes'] = json_decode($level['permissoes'], true);
            }

            return [
                'success' => true,
                'levels' => $levels
            ];

        } catch (Exception $e) {
            $this->logger->error("Erro ao obter níveis de acesso: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida dados do utilizador
     */
    private function validateUserData($userData) {
        $required = ['nome', 'email', 'senha', 'nivel_acesso_id'];
        
        foreach ($required as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }

        // Validar email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email inválido");
        }

        // Validar senha
        if (strlen($userData['senha']) < 6) {
            throw new Exception("Senha deve ter pelo menos 6 caracteres");
        }

        // Validar nível de acesso
        if (!$this->accessLevelExists($userData['nivel_acesso_id'])) {
            throw new Exception("Nível de acesso inválido");
        }
    }

    /**
     * Verifica se email já existe
     */
    private function emailExists($email, $excludeUserId = null) {
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        $params = [$email];

        if ($excludeUserId) {
            $sql .= " AND id != ?";
            $params[] = $excludeUserId;
        }

        $user = $this->db->selectOne($sql, $params);
        return $user !== false;
    }

    /**
     * Verifica se utilizador existe
     */
    private function userExists($userId) {
        $user = $this->db->selectOne("SELECT id FROM usuarios WHERE id = ?", [$userId]);
        return $user !== false;
    }

    /**
     * Verifica se nível de acesso existe
     */
    private function accessLevelExists($levelId) {
        $level = $this->db->selectOne("SELECT id FROM niveis_acesso WHERE id = ?", [$levelId]);
        return $level !== false;
    }

    /**
     * Verifica se utilizador é administrador
     */
    private function isAdmin($userId) {
        try {
            $sql = "SELECT na.nome FROM usuarios u 
                    JOIN niveis_acesso na ON u.nivel_acesso_id = na.id 
                    WHERE u.id = ? AND u.ativo = 1";
            
            $user = $this->db->selectOne($sql, [$userId]);
            return $user && strtolower($user['nome']) === 'administrador';
        } catch (Exception $e) {
            return false;
        }
    }
}
