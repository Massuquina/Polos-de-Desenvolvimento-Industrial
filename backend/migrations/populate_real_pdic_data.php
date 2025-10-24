<?php
/**
 * Seed real de empresas para o Pólo de Desenvolvimento Industrial da Catumbela (PDIC)
 * - Insere empresas reais com base em fontes verificáveis
 * - Cria infraestruturas mínimas associadas às empresas
 * - Gera lotes adicionais se o polo tiver poucos
 * - Liga infraestruturas a lotes e atualiza pivots
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new Database();
    $pdo = $db->connect();

    // Helpers
    $exec = function(PDO $pdo, string $sql, array $params = []) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    };
    $getScalar = function(PDO $pdo, string $sql, array $params = []) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        return ($val === false) ? null : $val;
    };

    $db->beginTransaction();

    // Resolver IDs de localização e polo
    $provinciaId = (int)$getScalar($pdo, "SELECT id FROM provincias WHERE nome = 'Benguela' LIMIT 1");
    if (!$provinciaId) { throw new Exception("Província 'Benguela' não encontrada."); }

    $municipioId = (int)$getScalar($pdo, "SELECT id FROM municipios WHERE nome = 'Catumbela' AND provincia_id = :p LIMIT 1", [':p' => $provinciaId]);
    if (!$municipioId) { throw new Exception("Município 'Catumbela' não encontrado."); }

    $pdicId = (int)$getScalar($pdo, "SELECT id FROM polos_desenvolvimento WHERE nome = :n LIMIT 1", [':n' => 'Polo de Desenvolvimento Industrial da Catumbela']);
    if (!$pdicId) { throw new Exception("Polo 'Polo de Desenvolvimento Industrial da Catumbela' não encontrado."); }

    // Resolver sectores
    $getSectorId = function(PDO $pdo, string $nome) use ($getScalar) {
        $id = $getScalar($pdo, "SELECT id FROM sectores WHERE nome = :n LIMIT 1", [':n' => $nome]);
        if (!$id) {
            // fallback para 'Outros'
            $id = $getScalar($pdo, "SELECT id FROM sectores WHERE nome = 'Outros' LIMIT 1");
        }
        return (int)$id;
    };

    $sectAlimentos = $getSectorId($pdo, 'Alimentar e Bebidas');
    $sectConstrucao = $getSectorId($pdo, 'Construção Civil');

    // Empresas reais (fonte citada em campo observacoes)
    $companies = [
        [
            'nome' => 'Yokimbo - Processamento de Milho',
            'sector_id' => $sectAlimentos,
            'atividade' => 'Processamento de milho e armazenamento',
            'website' => 'https://yokimbo.ao/',
            'email' => null,
            'telefone' => null,
            'fonte' => 'https://yokimbo.ao/sobre'
        ],
        [
            'nome' => 'Grupo Castel Angola - Fábrica SOBA (Catumbela)',
            'sector_id' => $sectAlimentos,
            'atividade' => 'Produção de bebidas',
            'website' => 'https://grupocastelangola.com/',
            'email' => null,
            'telefone' => null,
            'fonte' => 'https://grupocastelangola.com/grupo-castel/historia/'
        ],
        [
            'nome' => 'Cimenfort - Fábrica de Cimento (Catumbela)',
            'sector_id' => $sectConstrucao,
            'atividade' => 'Produção de cimento',
            'website' => null,
            'email' => null,
            'telefone' => null,
            'fonte' => 'https://allafrica.com/stories/201208230211.html'
        ],
    ];

    $createdEmpresas = 0; $createdInfra = 0; $createdLotes = 0; $createdPivot = 0; $linksCount = 0;

    // Gerar lotes adicionais se PDIC tiver poucos
    $existingLotes = (int)$getScalar($pdo, "SELECT COUNT(*) FROM lotes WHERE polo_id = :p", [':p' => $pdicId]);
    if ($existingLotes < 10) {
        for ($i = 1; $i <= 30; $i++) {
            $nomeLote = 'PDIC-' . $i;
            $exists = (int)$getScalar($pdo, "SELECT COUNT(*) FROM lotes WHERE polo_id = :p AND nome_lote = :n", [':p' => $pdicId, ':n' => $nomeLote]);
            if (!$exists) {
                $exec($pdo, "INSERT INTO lotes (polo_id, nome_lote, area_m2, status, descricao) VALUES (:p, :n, :a, 'disponivel', 'Lote gerado para PDIC Catumbela')", [
                    ':p' => $pdicId,
                    ':n' => $nomeLote,
                    ':a' => rand(3000, 12000)
                ]);
                $createdLotes++;
            }
        }
    }

    // Inserir empresas + pivot + infra + link a lote
    foreach ($companies as $c) {
        $empresaId = (int)$getScalar($pdo, "SELECT id FROM empresas WHERE nome_empresa = :n AND polo_id = :p LIMIT 1", [':n' => $c['nome'], ':p' => $pdicId]);
        if (!$empresaId) {
            $observ = 'Inserido via script real (PDIC). Fonte: ' . $c['fonte'];
            $exec($pdo, "INSERT INTO empresas (lote_id, polo_id, nome_empresa, nif, sector_id, atividade_principal, num_funcionarios, contacto_principal, email_principal, telefone_principal, website, data_instalacao, status, observacoes)
                        VALUES (NULL, :polo, :nome, NULL, :sector, :atividade, NULL, :contacto, :email, :telefone, :website, NULL, 'ativa', :obs)", [
                ':polo' => $pdicId,
                ':nome' => $c['nome'],
                ':sector' => $c['sector_id'],
                ':atividade' => $c['atividade'],
                ':contacto' => $c['nome'],
                ':email' => $c['email'],
                ':telefone' => $c['telefone'],
                ':website' => $c['website'],
                ':obs' => $observ
            ]);
            $empresaId = (int)$pdo->lastInsertId();
            $createdEmpresas++;
        }

        // Pivot empresas_polos
        $existsPivot = (int)$getScalar($pdo, "SELECT COUNT(*) FROM empresas_polos WHERE empresa_id = :eid AND polo_id = :pid", [':eid' => $empresaId, ':pid' => $pdicId]);
        if (!$existsPivot) {
            $exec($pdo, "INSERT INTO empresas_polos (empresa_id, polo_id, status) VALUES (:eid, :pid, 'ativa')", [':eid' => $empresaId, ':pid' => $pdicId]);
            $createdPivot++;
        }

        // Infraestrutura mínima associada
        $infraExists = (int)$getScalar($pdo, "SELECT COUNT(*) FROM infraestruturas WHERE polo_id = :polo AND provincia_id = :prov AND municipio_id = :mun AND sector_id <=> :sector AND nome_representante = :repr", [
            ':polo' => $pdicId, ':prov' => $provinciaId, ':mun' => $municipioId, ':sector' => $c['sector_id'], ':repr' => $c['nome']
        ]);
        if (!$infraExists) {
            $exec($pdo, "INSERT INTO infraestruturas (
                polo_id, provincia_id, municipio_id, comuna_id, bairro_local, rua_numero,
                latitude, longitude,
                proprietario_terreno,
                programa,
                area,
                rede_hidraulica, rede_esgotos_domesticos, rede_esgotos_pluviais, rede_eletrica, vias_acesso,
                nome_representante, email_representante, telefone_representante,
                tipologia, sector_id, estado_atual, capacidade_instalada, produtos,
                observacoes, empresa_id
            ) VALUES (
                :polo, :prov, :mun, NULL, 'PDIC Catumbela', NULL,
                NULL, NULL,
                'publico',
                'PDI Angola',
                10000.00,
                'sim','nao','nao','sim','sim',
                :repr, NULL, NULL,
                'industrial', :sector, 'operacional', NULL, :prod,
                'Inserido via script real (PDIC).', :eid
            )", [
                ':polo' => $pdicId,
                ':prov' => $provinciaId,
                ':mun' => $municipioId,
                ':repr' => $c['nome'],
                ':sector' => $c['sector_id'],
                ':prod' => $c['atividade'],
                ':eid' => $empresaId
            ]);
            $createdInfra++;
            $infraId = (int)$pdo->lastInsertId();

            // Ligar a um lote livre e marcar ocupado
            $freeLoteId = (int)$getScalar($pdo, "SELECT id FROM lotes WHERE polo_id = :p AND status != 'ocupado' ORDER BY id LIMIT 1", [':p' => $pdicId]);
            if ($freeLoteId) {
                $alreadyLink = (int)$getScalar($pdo, "SELECT COUNT(*) FROM infraestruturas_lotes WHERE infraestrutura_id = :i AND lote_id = :l", [':i' => $infraId, ':l' => $freeLoteId]);
                if (!$alreadyLink) {
                    $exec($pdo, "INSERT INTO infraestruturas_lotes (infraestrutura_id, lote_id) VALUES (:i, :l)", [':i' => $infraId, ':l' => $freeLoteId]);
                    $linksCount++;
                    $exec($pdo, "UPDATE lotes SET status = 'ocupado', descricao = CONCAT(IFNULL(descricao,''),' | Ocupado por ', :empresa) WHERE id = :l", [':empresa' => $c['nome'], ':l' => $freeLoteId]);
                }
            }
        }
    }

    // Recomputar totais do polo
    $exec($pdo, "UPDATE polos_desenvolvimento pd SET 
        total_lotes = (SELECT COUNT(*) FROM lotes WHERE polo_id = pd.id),
        lotes_ocupados = (SELECT COUNT(*) FROM lotes WHERE polo_id = pd.id AND status = 'ocupado'),
        total_empresas = (SELECT COUNT(*) FROM empresas_polos WHERE polo_id = pd.id)
        WHERE pd.id = :polo", [':polo' => $pdicId]);

    $db->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'PDIC Catumbela populado com dados reais.',
        'polo_id' => $pdicId,
        'created_empresas' => $createdEmpresas,
        'created_infraestruturas' => $createdInfra,
        'created_lotes' => $createdLotes,
        'pivot_links' => $createdPivot,
        'infra_lote_links' => $linksCount
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($db)) { $db->rollback(); }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>