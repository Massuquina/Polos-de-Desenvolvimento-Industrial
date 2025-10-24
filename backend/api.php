<?php
// Incluir bypass de sessão e CSRF
// require_once 'session_bypass.php';
/**
 * API principal para gestão de infraestruturas PDI Angola
 * Versão 1.2.0 - Melhorias de segurança e performance
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';
// require_once 'auth_bypass.php';
require_once 'CSRF.php';
require_once 'RateLimiter.php';
require_once 'Cache.php';
require_once 'AuditLogger.php';
require_once 'UserManager.php';

class API {
    private $db;
    private $auth;
    private $rateLimiter;
    private $cache;
    private $auditLogger;
    private $userManager;

    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth();
        $this->rateLimiter = new RateLimiter();
        $this->cache = new Cache();
        $this->auditLogger = new AuditLogger();
        $this->userManager = new UserManager();
    }

    /**
     * Processa as requisições da API
     */
    public function handleRequest() {
        // Verificar rate limiting
        if (!$this->rateLimiter->checkLimit()) {
            return $this->sendResponse(['error' => 'Muitas requisições. Tente novamente mais tarde.'], 429);
        }

        // Validar CSRF para métodos que modificam dados
        $method = $_SERVER['REQUEST_METHOD'];
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            CSRF::validateRequest();
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/pdi_angola/backend/api.php', '', $path);
        $segments = explode('/', trim($path, '/'));

        try {
            switch ($segments[0]) {
                case 'auth':
                    return $this->handleAuth($method, $segments);
                case 'provincias':
                    return $this->handleProvincias($method, $segments);
                case 'municipios':
                    return $this->handleMunicipios($method, $segments);
                case 'polos':
                    return $this->handlePolos($method, $segments);
                case 'sectores':
                    return $this->handleSectores($method, $segments);
                case 'infraestruturas':
                    return $this->handleInfraestruturas($method, $segments);
                case 'lotes':
                    return $this->handleLotes($method, $segments);
                case 'empresas':
                    return $this->handleEmpresas($method, $segments);
                case 'usuarios':
                    return $this->handleUsuarios($method, $segments);
                case '/csrf/token':
    // Endpoint para obter token CSRF
    header('Content-Type: application/json');
    echo json_encode(['token' => CSRF::generateToken()]);
    break;

case 'admin':
                    return $this->handleAdmin($method, $segments);
                default:
                    return $this->sendResponse(['error' => 'Endpoint não encontrado'], 404);
            }
        } catch (Exception $e) {
            return $this->sendResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Gestão de autenticação
     */
    private function handleAuth($method, $segments) {
        switch ($method) {
            case 'POST':
                if (isset($segments[1]) && $segments[1] === 'login') {
                    return $this->login();
                } elseif (isset($segments[1]) && $segments[1] === 'logout') {
                    return $this->logout();
                }
                break;
            case 'GET':
                if (isset($segments[1]) && $segments[1] === 'check') {
                    return $this->checkAuth();
                }
                break;
        }
        return $this->sendResponse(['error' => 'Método não permitido'], 405);
    }

    /**
     * Login
     */
    private function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->sendResponse(['error' => 'Email e senha são obrigatórios'], 400);
        }

        $user = $this->auth->login($data['email'], $data['password']);
        
        if ($user) {
            $this->auth->startSession($user);
            
            // Log de auditoria
            $this->auditLogger->logLogin($user['id'], true, [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            return $this->sendResponse([
                'user' => $user, 
                'message' => 'Login realizado com sucesso',
                'csrf_token' => CSRF::getCurrentToken()
            ]);
        } else {
            // Log de tentativa de login falhada
            $this->auditLogger->logLogin(null, false, [
                'email' => $data['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            return $this->sendResponse(['error' => 'Credenciais inválidas'], 401);
        }
    }

    /**
     * Logout
     */
    private function logout() {
        $user = $this->auth->requireAuth();
        $this->auth->logout();
        
        // Log de auditoria
        $this->auditLogger->logLogout($user['id']);
        
        return $this->sendResponse(['message' => 'Logout realizado com sucesso']);
    }

    /**
     * Verificar autenticação
     */
    private function checkAuth() {
        if ($this->auth->isSessionValid()) {
            return $this->sendResponse(['user' => $_SESSION['user']]);
        } else {
            return $this->sendResponse(['error' => 'Não autenticado'], 401);
        }
    }

    /**
     * Gestão de províncias
     */
    private function handleProvincias($method, $segments) {
        switch ($method) {
            case 'GET':
                return $this->getProvincias();
            default:
                return $this->sendResponse(['error' => 'Método não permitido'], 405);
        }
    }

    /**
     * Obter todas as províncias
     */
    private function getProvincias() {
        $cacheKey = Cache::generateKey('provincias');
        
        $provincias = $this->cache->remember($cacheKey, function() {
            return $this->db->select("SELECT * FROM provincias ORDER BY nome");
        }, 3600); // Cache por 1 hora
        
        return $this->sendResponse($provincias);
    }

    /**
     * Gestão de municípios
     */
    private function handleMunicipios($method, $segments) {
        switch ($method) {
            case 'GET':
                if (isset($segments[1])) {
                    return $this->getMunicipiosByProvincia($segments[1]);
                }
                return $this->getMunicipios();
            default:
                return $this->sendResponse(['error' => 'Método não permitido'], 405);
        }
    }

    /**
     * Obter todos os municípios
     */
    private function getMunicipios() {
        $sql = "SELECT m.*, p.nome as provincia_nome 
                FROM municipios m 
                JOIN provincias p ON m.provincia_id = p.id 
                ORDER BY p.nome, m.nome";
        $municipios = $this->db->select($sql);
        return $this->sendResponse($municipios);
    }

    /**
     * Obter municípios por província
     */
    private function getMunicipiosByProvincia($provincia_id) {
        $sql = "SELECT * FROM municipios WHERE provincia_id = ? ORDER BY nome";
        $municipios = $this->db->select($sql, [$provincia_id]);
        return $this->sendResponse($municipios);
    }

    /**
     * Gestão de polos
     */
    private function handlePolos($method, $segments) {
        switch ($method) {
            case 'GET':
                return $this->getPolos();
            case 'POST':
                $user = $this->auth->requirePermission('create');
                return $this->createPolo();
            case 'PUT':
                $user = $this->auth->requirePermission('update');
                return $this->updatePolo($segments[1]);
            case 'DELETE':
                $user = $this->auth->requirePermission('delete');
                return $this->deletePolo($segments[1]);
            default:
                return $this->sendResponse(['error' => 'Método não permitido'], 405);
        }
    }

    /**
     * Obter todos os polos
     */
    private function getPolos() {
        $sql = "SELECT pd.*, pd.perimeter_geojson AS perimeter_geojson, p.nome as provincia_nome, m.nome as municipio_nome,
                       c.nome as comuna_nome, 
                       (SELECT COUNT(*) FROM lotes WHERE polo_id = pd.id) as total_lotes,
                       (SELECT COUNT(*) FROM lotes WHERE polo_id = pd.id AND status = 'ocupado') as lotes_ocupados,
                       (SELECT COUNT(*) FROM empresas WHERE polo_id = pd.id) as total_empresas
                FROM polos_desenvolvimento pd
                JOIN provincias p ON pd.provincia_id = p.id
                JOIN municipios m ON pd.municipio_id = m.id
                LEFT JOIN comunas c ON pd.comuna_id = c.id
                ORDER BY p.nome, pd.nome";
        $polos = $this->db->select($sql);
        return $this->sendResponse($polos);
    }

    /**
     * Criar novo polo
     */
    private function createPolo() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['nome']) || !isset($data['provincia_id']) || !isset($data['municipio_id'])) {
            return $this->sendResponse(['error' => 'Dados obrigatórios não fornecidos'], 400);
        }

        $sql = "INSERT INTO polos_desenvolvimento (nome, provincia_id, municipio_id, comuna_id, endereco, latitude, longitude, area_total, status, descricao) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['nome'],
            $data['provincia_id'],
            $data['municipio_id'],
            $data['comuna_id'] ?? null,
            $data['endereco'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['area_total'] ?? null,
            $data['status'] ?? 'planejado',
            $data['descricao'] ?? null
        ];

        try {
            $id = $this->db->insert($sql, $params);
            
            // Log de auditoria
            $this->auditLogger->logPoloAction('create', $id, [
                'nome' => $data['nome'],
                'provincia_id' => $data['provincia_id'],
                'municipio_id' => $data['municipio_id']
            ]);
            
            // Limpar cache relacionado
            $this->cache->delete(Cache::generateKey('polos'));
            
            return $this->sendResponse(['success' => true, 'id' => $id, 'message' => 'Polo criado com sucesso']);
        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Erro ao criar polo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Atualizar polo
     */
    private function updatePolo($id) {
        if (!$id) {
            return $this->sendResponse(['error' => 'ID do polo não fornecido'], 400);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            return $this->sendResponse(['error' => 'Dados não fornecidos'], 400);
        }

        $sql = "UPDATE polos_desenvolvimento SET 
                nome = ?, provincia_id = ?, municipio_id = ?, comuna_id = ?, 
                endereco = ?, latitude = ?, longitude = ?, area_total = ?, 
                status = ?, descricao = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $params = [
            $data['nome'] ?? null,
            $data['provincia_id'] ?? null,
            $data['municipio_id'] ?? null,
            $data['comuna_id'] ?? null,
            $data['endereco'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['area_total'] ?? null,
            $data['status'] ?? null,
            $data['descricao'] ?? null,
            $id
        ];

        try {
            $affected = $this->db->update($sql, $params);
            if ($affected > 0) {
                return $this->sendResponse(['success' => true, 'message' => 'Polo atualizado com sucesso']);
            } else {
                return $this->sendResponse(['error' => 'Polo não encontrado'], 404);
            }
        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Erro ao atualizar polo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar polo
     */
    private function deletePolo($id) {
        if (!$id) {
            return $this->sendResponse(['error' => 'ID do polo não fornecido'], 400);
        }

        try {
            // Verificar se existem lotes ou empresas associadas
            $checkSql = "SELECT 
                        (SELECT COUNT(*) FROM lotes WHERE polo_id = ?) as lotes_count,
                        (SELECT COUNT(*) FROM empresas WHERE polo_id = ?) as empresas_count";
            $check = $this->db->selectOne($checkSql, [$id, $id]);
            
            if ($check['lotes_count'] > 0 || $check['empresas_count'] > 0) {
                return $this->sendResponse(['error' => 'Não é possível eliminar polo com lotes ou empresas associadas'], 400);
            }

            $sql = "DELETE FROM polos_desenvolvimento WHERE id = ?";
            $affected = $this->db->delete($sql, [$id]);
            
            if ($affected > 0) {
                return $this->sendResponse(['success' => true, 'message' => 'Polo eliminado com sucesso']);
            } else {
                return $this->sendResponse(['error' => 'Polo não encontrado'], 404);
            }
        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Erro ao eliminar polo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Gestão de sectores
     */
    private function handleSectores($method, $segments) {
        switch ($method) {
            case 'GET':
                return $this->getSectores();
            default:
                return $this->sendResponse(['error' => 'Método não permitido'], 405);
        }
    }

    /**
     * Obter todos os sectores
     */
    private function getSectores() {
        $cacheKey = Cache::generateKey('sectores');
        
        $sectores = $this->cache->remember($cacheKey, function() {
            return $this->db->select("SELECT * FROM sectores ORDER BY nome");
        }, 3600); // Cache por 1 hora
        
        return $this->sendResponse($sectores);
    }

    /**
     * Gestão de infraestruturas
     */
    private function handleInfraestruturas($method, $segments) {
        switch ($method) {
            case 'GET':
                if (isset($segments[1])) {
                    return $this->getInfraestrutura($segments[1]);
                }
                return $this->getInfraestruturas();
            case 'POST':
                $user = $this->auth->requirePermission('create');
                return $this->createInfraestrutura($user);
            case 'PUT':
                $user = $this->auth->requirePermission('update');
                return $this->updateInfraestrutura($segments[1], $user);
            case 'DELETE':
                $user = $this->auth->requirePermission('delete');
                return $this->deleteInfraestrutura($segments[1]);
            default:
                return $this->sendResponse(['error' => 'Método não permitido'], 405);
        }
    }

    /**
     * Obter todas as infraestruturas
     */
    private function getInfraestruturas() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        // Filtros
        $where = [];
        $params = [];

        if (isset($_GET['polo_id']) && $_GET['polo_id'] !== '') {
            $where[] = "i.polo_id = ?";
            $params[] = $_GET['polo_id'];
        }

        if (isset($_GET['provincia_id']) && $_GET['provincia_id'] !== '') {
            $where[] = "i.provincia_id = ?";
            $params[] = $_GET['provincia_id'];
        }

        if (isset($_GET['sector_id']) && $_GET['sector_id'] !== '') {
            $where[] = "i.sector_id = ?";
            $params[] = $_GET['sector_id'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM infraestruturas i $whereClause";
        $total = $this->db->selectOne($countSql, $params)['total'];

        // Obter dados
        $sql = "SELECT i.*, 
                       p.nome as provincia_nome, 
                       m.nome as municipio_nome,
                       c.nome as comuna_nome,
                       pd.nome as polo_nome,
                       s.nome as sector_nome,
                       e.id as empresa_id,
                       e.nome_empresa as empresa_nome,
                       u1.nome as created_by_name,
                       u2.nome as updated_by_name
                FROM infraestruturas i
                JOIN provincias p ON i.provincia_id = p.id
                JOIN municipios m ON i.municipio_id = m.id
                LEFT JOIN comunas c ON i.comuna_id = c.id
                JOIN polos_desenvolvimento pd ON i.polo_id = pd.id
                LEFT JOIN sectores s ON i.sector_id = s.id
                LEFT JOIN empresas e ON i.empresa_id = e.id
                LEFT JOIN usuarios u1 ON i.created_by = u1.id
                LEFT JOIN usuarios u2 ON i.updated_by = u2.id
                $whereClause
                ORDER BY i.created_at DESC
                LIMIT $limit OFFSET $offset";

        $infraestruturas = $this->db->select($sql, $params);

        return $this->sendResponse([
            'data' => $infraestruturas,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Obter uma infraestrutura específica
     */
    private function getInfraestrutura($id) {
        $sql = "SELECT i.*, 
                       p.nome as provincia_nome, 
                       m.nome as municipio_nome,
                       c.nome as comuna_nome,
                       pd.nome as polo_nome,
                       s.nome as sector_nome,
                       e.id as empresa_id,
                       e.nome_empresa as empresa_nome
                FROM infraestruturas i
                JOIN provincias p ON i.provincia_id = p.id
                JOIN municipios m ON i.municipio_id = m.id
                LEFT JOIN comunas c ON i.comuna_id = c.id
                JOIN polos_desenvolvimento pd ON i.polo_id = pd.id
                LEFT JOIN sectores s ON i.sector_id = s.id
                LEFT JOIN empresas e ON i.empresa_id = e.id
                WHERE i.id = ?";

        $infraestrutura = $this->db->selectOne($sql, [$id]);

        if (!$infraestrutura) {
            return $this->sendResponse(['error' => 'Infraestrutura não encontrada'], 404);
        }

        // Obter dados relacionados
        $infraestrutura['envolvente_exterior'] = $this->db->selectOne(
            "SELECT * FROM envolvente_exterior WHERE infraestrutura_id = ?", [$id]
        );

        $infraestrutura['edificios'] = $this->db->selectOne(
            "SELECT * FROM edificios WHERE infraestrutura_id = ?", [$id]
        );

        $infraestrutura['equipamentos'] = $this->db->selectOne(
            "SELECT * FROM equipamentos WHERE infraestrutura_id = ?", [$id]
        );

        // Lotes associados via tabela de junção (N:M)
        $infraestrutura['lotes'] = $this->db->select(
            "SELECT l.* FROM infraestruturas_lotes il JOIN lotes l ON il.lote_id = l.id WHERE il.infraestrutura_id = ?",
            [$id]
        );

        return $this->sendResponse($infraestrutura);
    }

    /**
     * Criar nova infraestrutura
     */
    private function createInfraestrutura($user) {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validações básicas
        if (!isset($data['polo_id']) || !isset($data['provincia_id']) || !isset($data['municipio_id'])) {
            return $this->sendResponse(['error' => 'Polo, província e município são obrigatórios'], 400);
        }

        try {
            $this->db->beginTransaction();

            // Inserir infraestrutura principal
            $sql = "INSERT INTO infraestruturas (
                        polo_id, provincia_id, municipio_id, comuna_id, bairro_local, rua_numero,
                        latitude, longitude, proprietario_terreno, programa, area,
                        confrontacao_norte, confrontacao_sul, confrontacao_este, confrontacao_oeste,
                        pre_existencias, ocupacao_indevida, condicoes_solos, condicao_vegetacao, vedacoes,
                        rede_hidraulica, rede_esgotos_domesticos, rede_esgotos_pluviais, rede_eletrica, vias_acesso,
                        nome_representante, email_representante, telefone_representante,
                        ano_construcao, tipologia, empresa_id, sector_id, estado_atual, capacidade_instalada,
                        produtos, constrangimentos, observacoes, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $infraestrutura_id = $this->db->insert($sql, [
                $data['polo_id'], $data['provincia_id'], $data['municipio_id'], 
                $data['comuna_id'] ?? null, $data['bairro_local'] ?? null, $data['rua_numero'] ?? null,
                $data['latitude'] ?? null, $data['longitude'] ?? null, $data['proprietario_terreno'] ?? 'publico',
                $data['programa'] ?? null, $data['area'] ?? null,
                $data['confrontacao_norte'] ?? null, $data['confrontacao_sul'] ?? null, 
                $data['confrontacao_este'] ?? null, $data['confrontacao_oeste'] ?? null,
                $data['pre_existencias'] ?? null, $data['ocupacao_indevida'] ?? null, 
                $data['condicoes_solos'] ?? null, $data['condicao_vegetacao'] ?? null, $data['vedacoes'] ?? null,
                $data['rede_hidraulica'] ?? 'nao', $data['rede_esgotos_domesticos'] ?? 'nao', 
                $data['rede_esgotos_pluviais'] ?? 'nao', $data['rede_eletrica'] ?? 'nao', $data['vias_acesso'] ?? 'nao',
                $data['nome_representante'] ?? null, $data['email_representante'] ?? null, $data['telefone_representante'] ?? null,
                $data['ano_construcao'] ?? null, $data['tipologia'] ?? null, $data['empresa_id'] ?? null, $data['sector_id'] ?? null,
                $data['estado_atual'] ?? 'bom', $data['capacidade_instalada'] ?? null,
                $data['produtos'] ?? null, $data['constrangimentos'] ?? null, $data['observacoes'] ?? null,
                $user['id']
            ]);

            // Mapear lotes (N:M)
            if (!empty($data['lote_ids']) && is_array($data['lote_ids'])) {
                $loteIds = array_unique(array_map('intval', $data['lote_ids']));
                foreach ($loteIds as $loteId) {
                    $this->db->update("INSERT INTO infraestruturas_lotes (infraestrutura_id, lote_id) VALUES (?, ?)", [$infraestrutura_id, $loteId]);
                }
            }

            $this->db->commit();

            return $this->sendResponse(['id' => $infraestrutura_id, 'message' => 'Infraestrutura criada com sucesso'], 201);

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Atualizar infraestrutura
     */
    private function updateInfraestrutura($id, $user) {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validações básicas
        if (!isset($data['polo_id']) || !isset($data['provincia_id']) || !isset($data['municipio_id'])) {
            return $this->sendResponse(['error' => 'Polo, província e município são obrigatórios'], 400);
        }

        try {
            $this->db->beginTransaction();

            $sql = "UPDATE infraestruturas SET
                        polo_id = ?, provincia_id = ?, municipio_id = ?, comuna_id = ?, bairro_local = ?, rua_numero = ?,
                        latitude = ?, longitude = ?, proprietario_terreno = ?, programa = ?, area = ?,
                        confrontacao_norte = ?, confrontacao_sul = ?, confrontacao_este = ?, confrontacao_oeste = ?,
                        pre_existencias = ?, ocupacao_indevida = ?, condicoes_solos = ?, condicao_vegetacao = ?, vedacoes = ?,
                        rede_hidraulica = ?, rede_esgotos_domesticos = ?, rede_esgotos_pluviais = ?, rede_eletrica = ?, vias_acesso = ?,
                        nome_representante = ?, email_representante = ?, telefone_representante = ?,
                        ano_construcao = ?, tipologia = ?, empresa_id = ?, sector_id = ?, estado_atual = ?, capacidade_instalada = ?,
                        produtos = ?, constrangimentos = ?, observacoes = ?, updated_by = ?, updated_at = NOW()
                    WHERE id = ?";

            $this->db->update($sql, [
                $data['polo_id'], $data['provincia_id'], $data['municipio_id'], 
                $data['comuna_id'] ?? null, $data['bairro_local'] ?? null, $data['rua_numero'] ?? null,
                $data['latitude'] ?? null, $data['longitude'] ?? null, $data['proprietario_terreno'] ?? 'publico',
                $data['programa'] ?? null, $data['area'] ?? null,
                $data['confrontacao_norte'] ?? null, $data['confrontacao_sul'] ?? null, 
                $data['confrontacao_este'] ?? null, $data['confrontacao_oeste'] ?? null,
                $data['pre_existencias'] ?? null, $data['ocupacao_indevida'] ?? null, 
                $data['condicoes_solos'] ?? null, $data['condicao_vegetacao'] ?? null, $data['vedacoes'] ?? null,
                $data['rede_hidraulica'] ?? 'nao', $data['rede_esgotos_domesticos'] ?? 'nao', 
                $data['rede_esgotos_pluviais'] ?? 'nao', $data['rede_eletrica'] ?? 'nao', $data['vias_acesso'] ?? 'nao',
                $data['nome_representante'] ?? null, $data['email_representante'] ?? null, $data['telefone_representante'] ?? null,
                $data['ano_construcao'] ?? null, $data['tipologia'] ?? null, $data['empresa_id'] ?? null, $data['sector_id'] ?? null,
                $data['estado_atual'] ?? 'bom', $data['capacidade_instalada'] ?? null,
                $data['produtos'] ?? null, $data['constrangimentos'] ?? null, $data['observacoes'] ?? null,
                $user['id'],
                $id
            ]);

            // Sincronizar lotes associados (N:M) se fornecido lote_ids
            if (array_key_exists('lote_ids', $data)) {
                // Apagar mapeamentos existentes
                $this->db->delete("DELETE FROM infraestruturas_lotes WHERE infraestrutura_id = ?", [$id]);

                // Inserir novos mapeamentos se houver IDs
                if (is_array($data['lote_ids']) && !empty($data['lote_ids'])) {
                    $loteIds = array_unique(array_map('intval', $data['lote_ids']));
                    foreach ($loteIds as $loteId) {
                        $this->db->update("INSERT INTO infraestruturas_lotes (infraestrutura_id, lote_id) VALUES (?, ?)", [$id, $loteId]);
                    }
                }
            }

            $this->db->commit();

            return $this->sendResponse(['message' => 'Infraestrutura atualizada com sucesso']);

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Eliminar infraestrutura
     */
    private function deleteInfraestrutura($id) {
        try {
            $this->db->beginTransaction();
            $this->db->delete("DELETE FROM infraestruturas WHERE id = ?", [$id]);
            $this->db->commit();
            return $this->sendResponse(['message' => 'Infraestrutura eliminada com sucesso']);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Gestão de lotes
     */
    private function handleLotes($method, $segments) {
        switch ($method) {
            case 'GET':
                if (isset($segments[1])) {
                    return $this->getLote($segments[1]);
                }
                return $this->getLotes();
            case 'POST':
                $user = $this->auth->requirePermission('manage_lotes');
                return $this->createLote($user);
            case 'PUT':
                $user = $this->auth->requirePermission('manage_lotes');
                return $this->updateLote($segments[1], $user);
            case 'DELETE':
                $user = $this->auth->requirePermission('manage_lotes');
                return $this->deleteLote($segments[1]);
            default:
                return $this->sendResponse(['error' => 'Método não permitido'], 405);
        }
    }

    /**
     * Obter todos os lotes
     */
    private function getLotes() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (isset($_GET['polo_id']) && $_GET['polo_id'] !== '') {
            $where[] = "l.polo_id = ?";
            $params[] = $_GET['polo_id'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $where[] = "l.status = ?";
            $params[] = $_GET['status'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) as total FROM lotes l $whereClause";
        $total = $this->db->selectOne($countSql, $params)['total'];

        $sql = "SELECT l.*, pd.nome as polo_nome
                FROM lotes l
                JOIN polos_desenvolvimento pd ON l.polo_id = pd.id
                $whereClause
                ORDER BY l.created_at DESC
                LIMIT $limit OFFSET $offset";

        $lotes = $this->db->select($sql, $params);

        return $this->sendResponse([
            'data' => $lotes,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Obter um lote específico
     */
    private function getLote($id) {
        $sql = "SELECT l.*, pd.nome as polo_nome
                FROM lotes l
                JOIN polos_desenvolvimento pd ON l.polo_id = pd.id
                WHERE l.id = ?";
        $lote = $this->db->selectOne($sql, [$id]);
        if (!$lote) {
            return $this->sendResponse(['error' => 'Lote não encontrado'], 404);
        }
        return $this->sendResponse($lote);
    }

    /**
     * Criar novo lote
     */
    private function createLote($user) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['polo_id']) || !isset($data['nome_lote'])) {
            return $this->sendResponse(['error' => 'Polo e nome do lote são obrigatórios'], 400);
        }
        try {
            $this->db->beginTransaction();
            $sql = "INSERT INTO lotes (polo_id, nome_lote, area_m2, status, descricao, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            $lote_id = $this->db->insert($sql, [
                $data['polo_id'], $data['nome_lote'], $data['area_m2'] ?? null, 
                $data['status'] ?? 'disponivel', $data['descricao'] ?? null, $user['id']
            ]);
            $this->db->commit();
            return $this->sendResponse(['id' => $lote_id, 'message' => 'Lote criado com sucesso'], 201);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Atualizar lote
     */
    private function updateLote($id, $user) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['polo_id']) || !isset($data['nome_lote'])) {
            return $this->sendResponse(['error' => 'Polo e nome do lote são obrigatórios'], 400);
        }
        try {
            $this->db->beginTransaction();
            $sql = "UPDATE lotes SET polo_id = ?, nome_lote = ?, area_m2 = ?, status = ?, descricao = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
            $this->db->update($sql, [
                $data['polo_id'], $data['nome_lote'], $data['area_m2'] ?? null, 
                $data['status'] ?? 'disponivel', $data['descricao'] ?? null, $user['id'], $id
            ]);
            $this->db->commit();
            return $this->sendResponse(['message' => 'Lote atualizado com sucesso']);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Eliminar lote
     */
    private function deleteLote($id) {
        try {
            $this->db->beginTransaction();
            $this->db->delete("DELETE FROM lotes WHERE id = ?", [$id]);
            $this->db->commit();
            return $this->sendResponse(['message' => 'Lote eliminado com sucesso']);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Gestão de empresas
     */
    private function handleEmpresas($method, $segments) {
        switch ($method) {
            case 'GET':
                if (isset($segments[1])) {
                    return $this->getEmpresa($segments[1]);
                }
                return $this->getEmpresas();
            case 'POST':
                $user = $this->auth->requirePermission('manage_empresas');
                return $this->createEmpresa($user);
            case 'PUT':
                $user = $this->auth->requirePermission('manage_empresas');
                return $this->updateEmpresa($segments[1], $user);
            case 'DELETE':
                $user = $this->auth->requirePermission('manage_empresas');
                return $this->deleteEmpresa($segments[1]);
            default:
                return $this->sendResponse(['error' => 'Método não permitido'], 405);
        }
    }

    /**
     * Obter todas as empresas
     */
    private function getEmpresas() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (isset($_GET['polo_id']) && $_GET['polo_id'] !== '') {
            $where[] = "e.polo_id = ?";
            $params[] = $_GET['polo_id'];
        }
        if (isset($_GET['sector_id']) && $_GET['sector_id'] !== '') {
            $where[] = "e.sector_id = ?";
            $params[] = $_GET['sector_id'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $where[] = "e.status = ?";
            $params[] = $_GET['status'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) as total FROM empresas e $whereClause";
        $total = $this->db->selectOne($countSql, $params)['total'];

        $sql = "SELECT e.*, pd.nome as polo_nome, l.nome_lote, s.nome as sector_nome
                FROM empresas e
                JOIN polos_desenvolvimento pd ON e.polo_id = pd.id
                LEFT JOIN lotes l ON e.lote_id = l.id
                LEFT JOIN sectores s ON e.sector_id = s.id
                $whereClause
                ORDER BY e.created_at DESC
                LIMIT $limit OFFSET $offset";

        $empresas = $this->db->select($sql, $params);

        return $this->sendResponse([
            'data' => $empresas,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Obter uma empresa específica
     */
    private function getEmpresa($id) {
        $sql = "SELECT e.*, pd.nome as polo_nome, l.nome_lote, s.nome as sector_nome
                FROM empresas e
                JOIN polos_desenvolvimento pd ON e.polo_id = pd.id
                LEFT JOIN lotes l ON e.lote_id = l.id
                LEFT JOIN sectores s ON e.sector_id = s.id
                WHERE e.id = ?";
        $empresa = $this->db->selectOne($sql, [$id]);
        if (!$empresa) {
            return $this->sendResponse(['error' => 'Empresa não encontrada'], 404);
        }
        return $this->sendResponse($empresa);
    }

    /**
     * Criar nova empresa
     */
    private function createEmpresa($user) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['polo_id']) || !isset($data['nome_empresa'])) {
            return $this->sendResponse(['error' => 'Polo e nome da empresa são obrigatórios'], 400);
        }
        try {
            $this->db->beginTransaction();
            $sql = "INSERT INTO empresas (
                        lote_id, polo_id, nome_empresa, nif, sector_id, atividade_principal,
                        num_funcionarios, contacto_principal, email_principal, telefone_principal, website,
                        data_instalacao, status, observacoes, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $empresa_id = $this->db->insert($sql, [
                $data['lote_id'] ?? null, $data['polo_id'], $data['nome_empresa'], $data['nif'] ?? null,
                $data['sector_id'] ?? null, $data['atividade_principal'] ?? null, $data['num_funcionarios'] ?? null,
                $data['contacto_principal'] ?? null, $data['email_principal'] ?? null, $data['telefone_principal'] ?? null,
                $data['website'] ?? null, $data['data_instalacao'] ?? null, $data['status'] ?? 'ativa',
                $data['observacoes'] ?? null, $user['id']
            ]);
            $this->db->commit();
            return $this->sendResponse(['id' => $empresa_id, 'message' => 'Empresa criada com sucesso'], 201);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Atualizar empresa
     */
    private function updateEmpresa($id, $user) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['polo_id']) || !isset($data['nome_empresa'])) {
            return $this->sendResponse(['error' => 'Polo e nome da empresa são obrigatórios'], 400);
        }
        try {
            $this->db->beginTransaction();
            $sql = "UPDATE empresas SET
                        lote_id = ?, polo_id = ?, nome_empresa = ?, nif = ?, sector_id = ?, atividade_principal = ?,
                        num_funcionarios = ?, contacto_principal = ?, email_principal = ?, telefone_principal = ?, website = ?,
                        data_instalacao = ?, status = ?, observacoes = ?, updated_by = ?, updated_at = NOW()
                    WHERE id = ?";
            $this->db->update($sql, [
                $data['lote_id'] ?? null, $data['polo_id'], $data['nome_empresa'], $data['nif'] ?? null,
                $data['sector_id'] ?? null, $data['atividade_principal'] ?? null, $data['num_funcionarios'] ?? null,
                $data['contacto_principal'] ?? null, $data['email_principal'] ?? null, $data['telefone_principal'] ?? null,
                $data['website'] ?? null, $data['data_instalacao'] ?? null, $data['status'] ?? 'ativa',
                $data['observacoes'] ?? null, $user['id'], $id
            ]);
            $this->db->commit();
            return $this->sendResponse(['message' => 'Empresa atualizada com sucesso']);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Eliminar empresa
     */
    private function deleteEmpresa($id) {
        try {
            $this->db->beginTransaction();
            $this->db->delete("DELETE FROM empresas WHERE id = ?", [$id]);
            $this->db->commit();
            return $this->sendResponse(['message' => 'Empresa eliminada com sucesso']);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Gestão de usuários
     */
    private function handleUsuarios($method, $segments) {
        $user = $this->auth->requirePermission('manage_users');

        switch ($method) {
            case 'GET':
                return $this->getUsuarios();
            case 'POST':
                return $this->createUsuario();
            default:
                return $this->sendResponse(['error' => 'Método não permitido'], 405);
        }
    }

    /**
     * Obter todos os usuários
     */
    private function getUsuarios() {
        $usuarios = $this->auth->getUsers();
        return $this->sendResponse($usuarios);
    }

    /**
     * Criar novo usuário
     */
    private function createUsuario() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['nome']) || !isset($data['email']) || !isset($data['senha']) || !isset($data['nivel_acesso'])) {
            return $this->sendResponse(['error' => 'Nome, email, senha e nível de acesso são obrigatórios'], 400);
        }

        try {
            $this->db->beginTransaction();
            $user_id = $this->auth->registerUser($data['nome'], $data['email'], $data['senha'], $data['nivel_acesso']);
            $this->db->commit();
            return $this->sendResponse(['id' => $user_id, 'message' => 'Usuário criado com sucesso'], 201);
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Gestão de administradores
     */
    private function handleAdmin($method, $segments) {
        $user = $this->auth->requireAuth();
        
        // Verificar se é administrador
        if (!$this->isAdmin($user)) {
            return $this->sendResponse(['error' => 'Acesso negado. Apenas administradores.'], 403);
        }

        switch ($method) {
            case 'GET':
                if (isset($segments[1]) && $segments[1] === 'users') {
                    return $this->getAllUsers($user['id']);
                } elseif (isset($segments[1]) && $segments[1] === 'access-levels') {
                    return $this->getAccessLevels();
                } elseif (isset($segments[1]) && is_numeric($segments[1])) {
                    return $this->getUser($segments[1], $user['id']);
                }
                break;
            case 'POST':
                if (isset($segments[1]) && $segments[1] === 'users') {
                    return $this->createUser($user['id']);
                }
                break;
            case 'PUT':
                if (isset($segments[1]) && is_numeric($segments[1])) {
                    return $this->updateUser($segments[1], $user['id']);
                }
                break;
            case 'PATCH':
                if (isset($segments[1]) && is_numeric($segments[1]) && isset($segments[2]) && $segments[2] === 'toggle-status') {
                    return $this->toggleUserStatus($segments[1], $user['id']);
                }
                break;
        }
        
        return $this->sendResponse(['error' => 'Endpoint de admin não encontrado'], 404);
    }

    /**
     * Lista todos os utilizadores com paginação e busca
     */
    private function getAllUsers($adminUserId) {
        // Obter parâmetros de query string
        $options = [
            'page' => isset($_GET['page']) ? intval($_GET['page']) : 1,
            'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 20,
            'search' => isset($_GET['search']) ? trim($_GET['search']) : ''
        ];

        $result = $this->userManager->getUsers($adminUserId, $options);
        
        if ($result['success']) {
            return $this->sendResponse([
                'users' => $result['users'],
                'pagination' => $result['pagination'],
                'search' => $result['search']
            ]);
        } else {
            return $this->sendResponse(['error' => $result['error']], 400);
        }
    }

    /**
     * Obtém um utilizador específico
     */
    private function getUser($userId, $adminUserId) {
        $result = $this->userManager->getUser($userId, $adminUserId);
        
        if ($result['success']) {
            return $this->sendResponse($result['user']);
        } else {
            return $this->sendResponse(['error' => $result['error']], 400);
        }
    }

    /**
     * Cria um novo utilizador
     */
    private function createUser($adminUserId) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            return $this->sendResponse(['error' => 'Dados não fornecidos'], 400);
        }

        $result = $this->userManager->createUser($data, $adminUserId);
        
        if ($result['success']) {
            return $this->sendResponse($result, 201);
        } else {
            return $this->sendResponse(['error' => $result['error']], 400);
        }
    }

    /**
     * Atualiza um utilizador
     */
    private function updateUser($userId, $adminUserId) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            return $this->sendResponse(['error' => 'Dados não fornecidos'], 400);
        }

        $result = $this->userManager->updateUser($userId, $data, $adminUserId);
        
        if ($result['success']) {
            return $this->sendResponse($result);
        } else {
            return $this->sendResponse(['error' => $result['error']], 400);
        }
    }

    /**
     * Ativa/desativa utilizador
     */
    private function toggleUserStatus($userId, $adminUserId) {
        $result = $this->userManager->toggleUserStatus($userId, $adminUserId);
        
        if ($result['success']) {
            return $this->sendResponse($result);
        } else {
            return $this->sendResponse(['error' => $result['error']], 400);
        }
    }

    /**
     * Obtém níveis de acesso
     */
    private function getAccessLevels() {
        $result = $this->userManager->getAccessLevels();
        
        if ($result['success']) {
            return $this->sendResponse($result['levels']);
        } else {
            return $this->sendResponse(['error' => $result['error']], 400);
        }
    }

    /**
     * Verifica se utilizador é administrador
     */
    private function isAdmin($user) {
        return isset($user['nivel_nome']) && strtolower($user['nivel_nome']) === 'administrador';
    }

    /**
     * Envia a resposta da API
     */
    private function sendResponse($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Instanciar e processar a requisição
$api = new API();
$api->handleRequest();

