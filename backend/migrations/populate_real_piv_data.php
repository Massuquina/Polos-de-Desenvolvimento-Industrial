<?php
// População de dados reais: Polo Industrial de Viana (PIV)
// Fontes:
// - Diretório de Empresas do PIV: https://polodeviana.com/directorio/ (acesso público)
// - Site do PIV: https://polodeviana.com/
// - Provincias/Municípios: Wikipédia (ver lista): https://pt.wikipedia.org/wiki/Lista_de_municípios_de_Angola_por_província
// Este script insere empresas reais do PIV, cria infraestruturas mínimas associadas e alguns lotes
// e vincula infraestruturas aos lotes, respeitando as regras do schema.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

try {
    // Conexão usando a classe Database do projeto
    $db = new Database();
    $pdo = $db->connect();

    // Iniciar transação
    $db->beginTransaction();

    // Helpers
    $getScalar = function(PDO $pdo, string $sql, array $params = []) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    };

    $exec = function(PDO $pdo, string $sql, array $params = []) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    };

    // Obter IDs base (Luanda/Viana/PIV)
    $provinciaId = (int)$getScalar($pdo, "SELECT id FROM provincias WHERE nome = 'Luanda' LIMIT 1");
    if (!$provinciaId) throw new Exception('Província Luanda não encontrada.');

    $municipioVianaId = (int)$getScalar($pdo, "SELECT id FROM municipios WHERE nome = 'Viana' AND provincia_id = :pid LIMIT 1", [':pid' => $provinciaId]);
    if (!$municipioVianaId) throw new Exception('Município Viana não encontrado em Luanda.');

    $pivId = (int)$getScalar($pdo, "SELECT id FROM polos_desenvolvimento WHERE nome = 'Polo Industrial de Viana' LIMIT 1");
    if (!$pivId) throw new Exception('Polo Industrial de Viana não encontrado.');

    // Mapear sectores por nome para IDs
    $sectorMapNames = [
        'Alimentar e Bebidas' => null,
        'Construção Civil' => null,
        'Plásticos e Borracha' => null,
        'Tecnologia' => null,
        'Metalúrgica' => null,
        'Logística' => null,
        'Outros' => null,
    ];
    foreach ($sectorMapNames as $nome => $null) {
        $id = (int)$getScalar($pdo, "SELECT id FROM sectores WHERE nome = :n LIMIT 1", [':n' => $nome]);
        $sectorMapNames[$nome] = $id ?: null;
    }

    // Lista de empresas reais (parcial) extraída do diretório do PIV
    // Campos: nome_empresa, sector_nome, website, email, telefone
    $empresas = [
        ['Atlântico Foods, Lda', 'Alimentar e Bebidas', 'http://www.atlanticofoods.com', 'geral@atlanticofoods.com', '+244 222 741 119'],
        ['Sá Machado Angola – Construção Civil S.A', 'Construção Civil', 'http://www.sa-machado.com', null, null],
        ['Tecnangol – Engenharia e Construção, S.A', 'Construção Civil', null, 'tecnangol@gmail.com', '939 965 222'],
        ['Griner Engenharia S.A', 'Construção Civil', 'http://www.griner.co', null, null],
        ['Flo-tek', 'Plásticos e Borracha', 'http://www.flotekafrica.com', 'vendasplastek@flotekafrica.com', null],
        ['Métricas Soluções', 'Tecnologia', null, 'ningfanru@zsigroup.com', '+244 930 201 335'],
        ['Firma M.N.B.A., Lda', 'Outros', null, 'geral@mnbacompany.com', '+244 935 085 815'],
        ['Construtel Serviços, Lda', 'Tecnologia', 'http://www.construtel.co.ao', 'geral@construtel.co.ao', '+244 222 00 83 10/11'],
        ['Matra Engenharia e Construção', 'Construção Civil', null, 'geral@matra.co.ao', '+244 949 278 844'],
        ['Best Angola Metal, Lda', 'Metalúrgica', 'http://www.bestangolametal.com', 'bestangolametal@yahoo.com', '+244 947 646 201'],
        ['Okyal-Sistemas de Alumínio, Lda', 'Metalúrgica', null, 'dli@ferlaca.com', '+244 927 169 692'],
        ['Ferlaca – Indústria e Serviços, Lda.', 'Metalúrgica', null, null, null],
    ];

    $createdEmpresas = 0;
    $createdPivot = 0;
    $createdInfra = 0;
    $createdLotes = 0;
    $createdInfraLotes = 0;

    // Inserir empresas e pivot empresas_polos
    foreach ($empresas as $e) {
        [$nomeEmpresa, $sectorNome, $website, $email, $telefone] = $e;
        $sectorId = $sectorMapNames[$sectorNome] ?? null;

        // Evitar duplicados pelo nome
        $empresaId = (int)$getScalar($pdo, "SELECT id FROM empresas WHERE nome_empresa = :nome AND polo_id = :polo LIMIT 1", [':nome' => $nomeEmpresa, ':polo' => $pivId]);
        if (!$empresaId) {
            $exec($pdo, "INSERT INTO empresas (lote_id, polo_id, nome_empresa, nif, sector_id, atividade_principal, num_funcionarios, contacto_principal, email_principal, telefone_principal, website, data_instalacao, status, observacoes)
                        VALUES (NULL, :polo, :nome, NULL, :sector, :atividade, NULL, :contacto, :email, :telefone, :website, NULL, 'ativa', 'Inserido via script real (PIV).')",
                [
                    ':polo' => $pivId,
                    ':nome' => $nomeEmpresa,
                    ':sector' => $sectorId,
                    ':atividade' => $sectorNome,
                    ':contacto' => $nomeEmpresa,
                    ':email' => $email,
                    ':telefone' => $telefone,
                    ':website' => $website,
                ]
            );
            $empresaId = (int)$pdo->lastInsertId();
            $createdEmpresas++;
        }

        // Pivot empresas_polos
        $existsPivot = (int)$getScalar($pdo, "SELECT COUNT(*) FROM empresas_polos WHERE empresa_id = :eid AND polo_id = :pid", [':eid' => $empresaId, ':pid' => $pivId]);
        if (!$existsPivot) {
            $exec($pdo, "INSERT INTO empresas_polos (empresa_id, polo_id) VALUES (:eid, :pid)", [':eid' => $empresaId, ':pid' => $pivId]);
            $createdPivot++;
        }

        // Criar uma infraestrutura básica associada à empresa
        // Apenas se não existir uma infraestrutura com mesmo polo/empresa/sector
        $infraExists = (int)$getScalar($pdo, "SELECT COUNT(*) FROM infraestruturas WHERE polo_id = :polo AND provincia_id = :prov AND municipio_id = :mun AND sector_id <=> :sector AND nome_representante = :repr", [
            ':polo' => $pivId, ':prov' => $provinciaId, ':mun' => $municipioVianaId, ':sector' => $sectorId, ':repr' => $nomeEmpresa
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
                observacoes
            ) VALUES (
                :polo, :prov, :mun, NULL, 'Polo de Viana', NULL,
                NULL, NULL,
                'publico',
                'PDI Angola',
                5000.00,
                'sim','nao','nao','sim','sim',
                :repr, :email, :tel,
                'Unidade Industrial', :sector, 'bom', NULL, NULL,
                'Infra criada via script com base em empresa real do PIV.'
            )",
            [
                ':polo' => $pivId,
                ':prov' => $provinciaId,
                ':mun' => $municipioVianaId,
                ':repr' => $nomeEmpresa,
                ':email' => $email,
                ':tel' => $telefone,
                ':sector' => $sectorId,
            ]);
            $createdInfra++;
        }
    }

    // Criar alguns lotes para o PIV (se não houver muitos)
    $existingLotes = (int)$getScalar($pdo, "SELECT COUNT(*) FROM lotes WHERE polo_id = :p", [':p' => $pivId]);
    if ($existingLotes < 100) {
        for ($i = $existingLotes + 1; $i <= 100; $i++) {
            $nomeLote = 'Lote ' . $i;
            $exec($pdo, "INSERT IGNORE INTO lotes (polo_id, nome_lote, area_m2, status, descricao) VALUES (:p, :n, :a, 'disponivel', 'Criado via script (seed real PIV).')",
                [':p' => $pivId, ':n' => $nomeLote, ':a' => 3000 + ($i * 10)]
            );
            $createdLotes++;
        }
    }

    // Vincular algumas infraestruturas a lotes aleatórios disponíveis
    // Regra: um lote pertence a uma única infraestrutura (UNIQUE lote_id em infraestruturas_lotes)
    $infraList = [];
    $stmt = $pdo->prepare("SELECT id, nome_representante FROM infraestruturas WHERE polo_id = :p ORDER BY id ASC");
    $stmt->execute([':p' => $pivId]);
    $infraList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lotesDisponiveisStmt = $pdo->prepare("SELECT id FROM lotes WHERE polo_id = :p AND status = 'disponivel' ORDER BY id ASC LIMIT 50");
    $lotesDisponiveisStmt->execute([':p' => $pivId]);
    $lotesDisponiveis = $lotesDisponiveisStmt->fetchAll(PDO::FETCH_COLUMN);

    $idx = 0;
    foreach ($infraList as $infra) {
        if ($idx >= count($lotesDisponiveis)) break;
        $infraId = (int)$infra['id'];
        // Alocar 2 lotes por infraestrutura até esgotar
        for ($k = 0; $k < 2 && $idx < count($lotesDisponiveis); $k++) {
            $loteId = (int)$lotesDisponiveis[$idx++];
            // Vincular no pivot
            $existsIL = (int)$getScalar($pdo, "SELECT COUNT(*) FROM infraestruturas_lotes WHERE lote_id = :l", [':l' => $loteId]);
            if (!$existsIL) {
                $exec($pdo, "INSERT INTO infraestruturas_lotes (infraestrutura_id, lote_id) VALUES (:i, :l)", [':i' => $infraId, ':l' => $loteId]);
                // Marcar lote como ocupado
                $exec($pdo, "UPDATE lotes SET status = 'ocupado' WHERE id = :l", [':l' => $loteId]);
                $createdInfraLotes++;
            }
        }
    }

    // Recalcular totais no polo
    $exec($pdo, "UPDATE polos_desenvolvimento pd SET 
        total_lotes = (SELECT COUNT(*) FROM lotes WHERE polo_id = pd.id),
        lotes_ocupados = (SELECT COUNT(*) FROM lotes WHERE polo_id = pd.id AND status = 'ocupado'),
        total_empresas = (SELECT COUNT(*) FROM empresas_polos WHERE polo_id = pd.id)
        WHERE pd.id = :p", [':p' => $pivId]);

    $db->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'População do PIV concluída com dados reais (empresas, infraestruturas e lotes).',
        'stats' => [
            'empresas_inseridas' => $createdEmpresas,
            'pivot_inseridos' => $createdPivot,
            'infraestruturas_criadas' => $createdInfra,
            'lotes_criados' => $createdLotes,
            'infraestruturas_lotes_vinculos' => $createdInfraLotes,
        ],
        'sources' => [
            'PIV Diretório' => 'https://polodeviana.com/directorio/',
            'PIV Site' => 'https://polodeviana.com/',
            'Municípios Wikipédia' => 'https://pt.wikipedia.org/wiki/Lista_de_municípios_de_Angola_por_província'
        ]
    ]);
    
} catch (Throwable $e) {
    if (isset($db) && $db instanceof Database && $db->inTransaction()) {
        $db->rollback();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}