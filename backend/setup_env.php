<?php
/**
 * Script para configurar arquivo .env
 * PDI Angola v1.2.0
 */

$envFile = __DIR__ . '/.env';
$exampleFile = __DIR__ . '/env.example';

// Verificar se .env já existe
if (file_exists($envFile)) {
    echo "✅ Arquivo .env já existe\n";
    echo "📝 Para reconfigurar, delete o arquivo .env e execute novamente\n";
    exit;
}

// Verificar se arquivo de exemplo existe
if (!file_exists($exampleFile)) {
    echo "❌ Arquivo env.example não encontrado\n";
    exit;
}

// Copiar arquivo de exemplo
if (copy($exampleFile, $envFile)) {
    echo "✅ Arquivo .env criado com sucesso\n";
    echo "📝 Configure as credenciais no arquivo .env\n";
    echo "🔧 Localização: {$envFile}\n";
} else {
    echo "❌ Erro ao criar arquivo .env\n";
    echo "📝 Crie manualmente copiando de env.example\n";
}
