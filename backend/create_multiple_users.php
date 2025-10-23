<?php
/**
 * Script para criar múltiplos usuários com diferentes níveis de acesso
 */

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'pdi_angola';
$username = 'root';
$password = '';

header('Content-Type: application/json; charset=utf-8');

try {
    // Conectar ao banco de dados diretamente
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Função para verificar se um nível de acesso existe pelo ID
    function ensureAccessLevelExists($pdo, $id, $nome, $permissoes) {
        $stmt = $pdo->prepare("SELECT id FROM niveis_acesso WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            $stmt = $pdo->prepare("INSERT INTO niveis_acesso (id, nome, permissoes) VALUES (?, ?, ?)");
            $stmt->execute([$id, $nome, $permissoes]);
            return $id;
        } else {
            // Atualizar o nível existente
            $stmt = $pdo->prepare("UPDATE niveis_acesso SET nome = ?, permissoes = ? WHERE id = ?");
            $stmt->execute([$nome, $permissoes, $id]);
            return $id;
        }
    }
    
    // Função para criar ou atualizar um usuário
    function createOrUpdateUser($pdo, $email, $nome, $senha, $nivel_acesso_id) {
        // Verificar se o usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        if ($user) {
            // Atualizar usuário existente
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, senha = ?, nivel_acesso_id = ?, ativo = 1 WHERE id = ?");
            $stmt->execute([$nome, $senha_hash, $nivel_acesso_id, $user['id']]);
            return ["success" => true, "action" => "update", "message" => "Usuário {$email} atualizado com sucesso", "user_id" => $user['id']];
        } else {
            // Criar novo usuário
            $stmt = $pdo->prepare("INSERT INTO usuarios (email, nome, senha, nivel_acesso_id, ativo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$email, $nome, $senha_hash, $nivel_acesso_id]);
            return ["success" => true, "action" => "create", "message" => "Usuário {$email} criado com sucesso", "user_id" => $pdo->lastInsertId()];
        }
    }
    
    // Definir níveis de acesso
    $access_levels = [
        [
            "id" => 1,
            "nome" => "Administrador",
            "permissoes" => json_encode([
                'admin' => true,
                'gerenciar_usuarios' => true,
                'gerenciar_infraestruturas' => true,
                'gerenciar_empresas' => true,
                'gerenciar_lotes' => true,
                'gerar_relatorios' => true
            ])
        ],
        [
            "id" => 2,
            "nome" => "Gestor",
            "permissoes" => json_encode([
                'admin' => false,
                'gerenciar_usuarios' => false,
                'gerenciar_infraestruturas' => true,
                'gerenciar_empresas' => true,
                'gerenciar_lotes' => true,
                'gerar_relatorios' => true
            ])
        ],
        [
            "id" => 3,
            "nome" => "Técnico",
            "permissoes" => json_encode([
                'admin' => false,
                'gerenciar_usuarios' => false,
                'gerenciar_infraestruturas' => true,
                'gerenciar_empresas' => false,
                'gerenciar_lotes' => true,
                'gerar_relatorios' => false
            ])
        ],
        [
            "id" => 4,
            "nome" => "Visualizador",
            "permissoes" => json_encode([
                'admin' => false,
                'gerenciar_usuarios' => false,
                'gerenciar_infraestruturas' => false,
                'gerenciar_empresas' => false,
                'gerenciar_lotes' => false,
                'gerar_relatorios' => true
            ])
        ]
    ];
    
    // Garantir que todos os níveis de acesso existam
    foreach ($access_levels as $level) {
        ensureAccessLevelExists($pdo, $level["id"], $level["nome"], $level["permissoes"]);
    }
    
    // Lista de usuários para criar
    $users = [
        [
            "email" => "admin@pdi.gov.ao",
            "nome" => "Administrador Principal",
            "senha" => "admin123",
            "nivel_acesso_id" => 1
        ],
        [
            "email" => "gestor@pdi.gov.ao",
            "nome" => "Gestor de Projetos",
            "senha" => "gestor123",
            "nivel_acesso_id" => 2
        ],
        [
            "email" => "tecnico@pdi.gov.ao",
            "nome" => "Técnico de Infraestrutura",
            "senha" => "tecnico123",
            "nivel_acesso_id" => 3
        ],
        [
            "email" => "visualizador@pdi.gov.ao",
            "nome" => "Visualizador de Dados",
            "senha" => "visualizador123",
            "nivel_acesso_id" => 4
        ]
    ];
    
    // Criar ou atualizar cada usuário
    $results = [];
    foreach ($users as $user) {
        $results[] = createOrUpdateUser(
            $pdo, 
            $user["email"], 
            $user["nome"], 
            $user["senha"], 
            $user["nivel_acesso_id"]
        );
    }
    
    // Exibir resultados
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro de banco de dados: ' . $e->getMessage()
    ]);
}