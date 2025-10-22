<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

try {
    $db = new Database();
    $pdo = $db->connect();

    $sql = "ALTER TABLE polos_desenvolvimento ADD COLUMN IF NOT EXISTS perimeter_geojson TEXT NULL COMMENT 'GeoJSON do perímetro do polo (Polygon/MultiPolygon)';";

    // MySQL 5.x não suporta IF NOT EXISTS em ADD COLUMN. Fazer verificação manual.
    $check = $pdo->query("SHOW COLUMNS FROM polos_desenvolvimento LIKE 'perimeter_geojson'");
    if ($check->rowCount() === 0) {
        $pdo->exec("ALTER TABLE polos_desenvolvimento ADD COLUMN perimeter_geojson TEXT NULL COMMENT 'GeoJSON do perímetro do polo (Polygon/MultiPolygon)'");
        echo "Coluna perimeter_geojson adicionada com sucesso.\n";
    } else {
        echo "Coluna perimeter_geojson já existe.\n";
    }

    exit(0);
} catch (Exception $e) {
    echo "Erro ao aplicar migration: " . $e->getMessage() . "\n";
    exit(1);
}