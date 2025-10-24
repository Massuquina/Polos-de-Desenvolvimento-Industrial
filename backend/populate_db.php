<?php
// Popular tabelas principais do banco conforme schema do projeto
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

$db = new Database();
$pdo = $db->connect();

function tableExists(Database $db, $table) {
    try {
        $row = $db->selectOne("SHOW TABLES LIKE ?", [$table]);
        return !empty($row);
    } catch (Exception $e) { return false; }
}

function isTableEmpty(Database $db, $table) {
    try {
        $row = $db->selectOne("SELECT COUNT(*) AS count FROM {$table}");
        return (int)($row['count'] ?? 0) === 0;
    } catch (Exception $e) { return true; }
}

function execSqlFile(Database $db, $path) {
    if (file_exists($path)) {
        $sql = file_get_contents($path);
        if ($sql) { $db->executeMultiple($sql); }
    }
}

function logMsg($msg) { echo $msg . "\n"; }

try {
    // 1) Garantir schema base apenas se faltando tabelas principais
    if (!tableExists($db, 'provincias') || !tableExists($db, 'municipios') || !tableExists($db, 'niveis_acesso') || !tableExists($db, 'usuarios')) {
        logMsg('Aplicando schema base...');
        execSqlFile($db, __DIR__ . '/../database/database_schema.sql');
    }

    // 2) Níveis de acesso
    if (isTableEmpty($db, 'niveis_acesso')) {
        logMsg('Preenchendo niveis_acesso...');
        $db->insert("INSERT INTO niveis_acesso (nome, descricao, permissoes) VALUES (?,?,?)", ['Administrador','Acesso total','{}']);
        $db->insert("INSERT INTO niveis_acesso (nome, descricao, permissoes) VALUES (?,?,?)", ['Editor','Pode editar dados','{}']);
        $db->insert("INSERT INTO niveis_acesso (nome, descricao, permissoes) VALUES (?,?,?)", ['Visualizador','Apenas leitura','{}']);
        logMsg('OK niveis_acesso preenchido');
    } else { logMsg('OK niveis_acesso já possui dados'); }

    // 3) Usuários
    if (isTableEmpty($db, 'usuarios')) {
        logMsg('Preenchendo usuarios...');
        $senha = password_hash('admin123', PASSWORD_DEFAULT);
        $db->insert("INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo) VALUES (?,?,?,?,1)", ['Administrador','admin@pdiangola.gov.ao',$senha,1]);
        $db->insert("INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo) VALUES (?,?,?,?,1)", ['Editor','editor@pdiangola.gov.ao',$senha,2]);
        $db->insert("INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo) VALUES (?,?,?,?,1)", ['Visualizador','vis@pdiangola.gov.ao',$senha,3]);
        logMsg('OK usuarios preenchido');
    } else { logMsg('OK usuarios já possui dados'); }

    // 4) Sectores
    if (isTableEmpty($db, 'sectores')) {
        logMsg('Preenchendo sectores...');
        $sectores = ['Agroindústria','Metalurgia','Construção','Logística','Química','Têxtil','Energia','Tecnologia'];
        foreach ($sectores as $s) { $db->insert("INSERT INTO sectores (nome, descricao) VALUES (?,?)", [$s, null]); }
        logMsg('OK sectores preenchido');
    } else { logMsg('OK sectores já possui dados'); }

    // 5) Provincias/Municipios/Polos
    if (isTableEmpty($db, 'provincias')) {
        logMsg('Inserindo dados iniciais (provincias, municipios, polos)...');
        execSqlFile($db, __DIR__ . '/../database/insert_data.sql');
        logMsg('OK dados iniciais inseridos');
    } else { logMsg('OK provincias já possui dados'); }

    // 6) Atualização v2: lotes/empresas e exemplos
    if (!tableExists($db, 'lotes') || !tableExists($db, 'empresas')) {
        logMsg('Aplicando update v2 (lotes/empresas)...');
        execSqlFile($db, __DIR__ . '/../database/database_schema_update_v2.sql');
        logMsg('OK update v2 aplicado');
    } else { logMsg('OK lotes/empresas já existem'); }

    logMsg("\nConcluído: tabelas verificadas e preenchidas conforme necessário.");
} catch (Exception $e) {
    logMsg('Erro: ' . $e->getMessage());
    http_response_code(500);
}