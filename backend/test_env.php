<?php
/**
 * Teste do EnvLoader
 * Verifica se o carregamento de variáveis está funcionando
 */

// Suprimir warnings para teste
error_reporting(E_ERROR | E_PARSE);

require_once 'EnvLoader.php';

echo "🧪 Testando EnvLoader...\n\n";

// Teste 1: Carregar variáveis
echo "1. Carregando variáveis de ambiente...\n";
EnvLoader::load();

// Teste 2: Verificar algumas variáveis básicas
echo "2. Verificando variáveis básicas...\n";

$testVars = [
    'DB_HOST' => 'localhost',
    'APP_NAME' => 'PDI Angola - Gestão de Infraestruturas',
    'ENVIRONMENT' => 'development'
];

foreach ($testVars as $var => $expected) {
    $value = EnvLoader::get($var, 'NOT_FOUND');
    if ($value === $expected) {
        echo "   ✅ {$var}: {$value}\n";
    } else {
        echo "   ❌ {$var}: {$value} (esperado: {$expected})\n";
    }
}

// Teste 3: Verificar se consegue obter todas as variáveis
echo "\n3. Listando todas as variáveis carregadas...\n";
$allVars = EnvLoader::all();
echo "   📊 Total de variáveis: " . count($allVars) . "\n";

if (count($allVars) > 0) {
    echo "   ✅ EnvLoader funcionando corretamente\n";
} else {
    echo "   ⚠️  Nenhuma variável carregada\n";
}

echo "\n🎉 Teste concluído!\n";
