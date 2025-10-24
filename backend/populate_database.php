<?php
/**
 * Script para preencher as tabelas da base de dados
 * Sistema PDI Angola
 */

// Carregar configurações
require_once 'config.php';
require_once 'database.php';
require_once 'EnvLoader.php';

// Inicializar o carregador de variáveis de ambiente
$envLoader = new EnvLoader(__DIR__);
$envLoader->load();

// Inicializar conexão com o banco de dados
$db = new Database();
$conn = $db->connect();

// Função para exibir mensagens
function showMessage($message, $type = 'info') {
    echo "<div style='padding: 10px; margin: 5px; border-radius: 5px; background-color: " . 
         ($type == 'success' ? '#d4edda' : ($type == 'error' ? '#f8d7da' : '#d1ecf1')) . 
         "; color: " . 
         ($type == 'success' ? '#155724' : ($type == 'error' ? '#721c24' : '#0c5460')) . 
         ";'>" . $message . "</div>";
}

// Função para verificar se uma tabela está vazia
function isTableEmpty($conn, $table) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] == 0;
}

// Função para inserir dados na tabela niveis_acesso
function populateNiveisAcesso($conn) {
    if (isTableEmpty($conn, 'niveis_acesso')) {
        try {
            $niveis = [
                ['nome' => 'Administrador', 'descricao' => 'Acesso completo ao sistema'],
                ['nome' => 'Gestor', 'descricao' => 'Acesso a gestão de dados e relatórios'],
                ['nome' => 'Operador', 'descricao' => 'Acesso básico para operações do dia a dia'],
                ['nome' => 'Visualizador', 'descricao' => 'Acesso somente leitura']
            ];
            
            $stmt = $conn->prepare("INSERT INTO niveis_acesso (nome, descricao) VALUES (:nome, :descricao)");
            
            foreach ($niveis as $nivel) {
                $stmt->bindParam(':nome', $nivel['nome']);
                $stmt->bindParam(':descricao', $nivel['descricao']);
                $stmt->execute();
            }
            
            showMessage("Níveis de acesso inseridos com sucesso!", 'success');
            return true;
        } catch (PDOException $e) {
            showMessage("Erro ao inserir níveis de acesso: " . $e->getMessage(), 'error');
            return false;
        }
    } else {
        showMessage("A tabela niveis_acesso já possui dados.", 'info');
        return true;
    }
}

// Função para inserir dados na tabela usuarios
function populateUsuarios($conn) {
    if (isTableEmpty($conn, 'usuarios')) {
        try {
            // Verificar se existem níveis de acesso
            $stmt = $conn->prepare("SELECT id FROM niveis_acesso WHERE nome = 'Administrador'");
            $stmt->execute();
            $adminNivel = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$adminNivel) {
                showMessage("Nível de acesso 'Administrador' não encontrado. Insira níveis de acesso primeiro.", 'error');
                return false;
            }
            
            $adminId = $adminNivel['id'];
            
            // Criar usuário administrador
            $nome = "Administrador";
            $email = "admin@example.com";
            $senha = password_hash("admin123", PASSWORD_DEFAULT);
            $ativo = 1;
            
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo) VALUES (:nome, :email, :senha, :nivel_acesso_id, :ativo)");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':nivel_acesso_id', $adminId);
            $stmt->bindParam(':ativo', $ativo);
            $stmt->execute();
            
            // Criar outros usuários
            $usuarios = [
                ['nome' => 'Gestor Teste', 'email' => 'gestor@example.com', 'senha' => 'gestor123', 'nivel' => 'Gestor'],
                ['nome' => 'Operador Teste', 'email' => 'operador@example.com', 'senha' => 'operador123', 'nivel' => 'Operador'],
                ['nome' => 'Visualizador Teste', 'email' => 'visualizador@example.com', 'senha' => 'visualizador123', 'nivel' => 'Visualizador']
            ];
            
            foreach ($usuarios as $usuario) {
                // Obter ID do nível de acesso
                $stmt = $conn->prepare("SELECT id FROM niveis_acesso WHERE nome = :nome");
                $stmt->bindParam(':nome', $usuario['nivel']);
                $stmt->execute();
                $nivelResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($nivelResult) {
                    $nivelId = $nivelResult['id'];
                    
                    $nome = $usuario['nome'];
                    $email = $usuario['email'];
                    $senha = password_hash($usuario['senha'], PASSWORD_DEFAULT);
                    $ativo = 1;
                    
                    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso_id, ativo) VALUES (:nome, :email, :senha, :nivel_acesso_id, :ativo)");
                    $stmt->bindParam(':nome', $nome);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':senha', $senha);
                    $stmt->bindParam(':nivel_acesso_id', $nivelId);
                    $stmt->bindParam(':ativo', $ativo);
                    $stmt->execute();
                }
            }
            
            showMessage("Usuários inseridos com sucesso!", 'success');
            return true;
        } catch (PDOException $e) {
            showMessage("Erro ao inserir usuários: " . $e->getMessage(), 'error');
            return false;
        }
    } else {
        showMessage("A tabela usuarios já possui dados.", 'info');
        return true;
    }
}

// Função para inserir dados na tabela provincias
function populateProvincias($conn) {
    if (isTableEmpty($conn, 'provincias')) {
        try {
            $provincias = [
                'Bengo', 'Benguela', 'Bié', 'Cabinda', 'Cuando Cubango', 
                'Cuanza Norte', 'Cuanza Sul', 'Cunene', 'Huambo', 'Huíla', 
                'Luanda', 'Lunda Norte', 'Lunda Sul', 'Malanje', 'Moxico', 
                'Namibe', 'Uíge', 'Zaire'
            ];
            
            $stmt = $conn->prepare("INSERT INTO provincias (nome) VALUES (:nome)");
            
            foreach ($provincias as $provincia) {
                $stmt->bindParam(':nome', $provincia);
                $stmt->execute();
            }
            
            showMessage("Províncias inseridas com sucesso!", 'success');
            return true;
        } catch (PDOException $e) {
            showMessage("Erro ao inserir províncias: " . $e->getMessage(), 'error');
            return false;
        }
    } else {
        showMessage("A tabela provincias já possui dados.", 'info');
        return true;
    }
}

// Função para inserir dados na tabela municipios
function populateMunicipios($conn) {
    if (isTableEmpty($conn, 'municipios')) {
        try {
            // Mapa de alguns municípios por província
            $municipiosPorProvincia = [
                'Luanda' => ['Luanda', 'Belas', 'Cacuaco', 'Cazenga', 'Icolo e Bengo', 'Quiçama', 'Talatona', 'Viana'],
                'Benguela' => ['Benguela', 'Baía Farta', 'Balombo', 'Bocoio', 'Caimbambo', 'Catumbela', 'Chongoroi', 'Cubal', 'Ganda', 'Lobito'],
                'Huambo' => ['Huambo', 'Bailundo', 'Caála', 'Catchiungo', 'Ekunha', 'Londuimbali', 'Longonjo', 'Mungo', 'Tchicala-Tcholoanga', 'Tchindjenje', 'Ukuma']
            ];
            
            foreach ($municipiosPorProvincia as $provinciaNome => $municipios) {
                // Obter ID da província
                $stmt = $conn->prepare("SELECT id FROM provincias WHERE nome = :nome");
                $stmt->bindParam(':nome', $provinciaNome);
                $stmt->execute();
                $provinciaResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($provinciaResult) {
                    $provinciaId = $provinciaResult['id'];
                    
                    $stmt = $conn->prepare("INSERT INTO municipios (nome, provincia_id) VALUES (:nome, :provincia_id)");
                    
                    foreach ($municipios as $municipio) {
                        $stmt->bindParam(':nome', $municipio);
                        $stmt->bindParam(':provincia_id', $provinciaId);
                        $stmt->execute();
                    }
                }
            }
            
            showMessage("Municípios inseridos com sucesso!", 'success');
            return true;
        } catch (PDOException $e) {
            showMessage("Erro ao inserir municípios: " . $e->getMessage(), 'error');
            return false;
        }
    } else {
        showMessage("A tabela municipios já possui dados.", 'info');
        return true;
    }
}

// Função para inserir dados na tabela empresas
function populateEmpresas($conn) {
    if (isTableEmpty($conn, 'empresas')) {
        try {
            $empresas = [
                ['nome' => 'Empresa A', 'nif' => '5000123456', 'endereco' => 'Rua A, nº 123, Luanda', 'telefone' => '+244 923 456 789', 'email' => 'contato@empresaa.co.ao'],
                ['nome' => 'Empresa B', 'nif' => '5000234567', 'endereco' => 'Rua B, nº 456, Benguela', 'telefone' => '+244 923 567 890', 'email' => 'contato@empresab.co.ao'],
                ['nome' => 'Empresa C', 'nif' => '5000345678', 'endereco' => 'Rua C, nº 789, Huambo', 'telefone' => '+244 923 678 901', 'email' => 'contato@empresac.co.ao'],
                ['nome' => 'Empresa D', 'nif' => '5000456789', 'endereco' => 'Rua D, nº 012, Lubango', 'telefone' => '+244 923 789 012', 'email' => 'contato@empresad.co.ao'],
                ['nome' => 'Empresa E', 'nif' => '5000567890', 'endereco' => 'Rua E, nº 345, Namibe', 'telefone' => '+244 923 890 123', 'email' => 'contato@empresae.co.ao']
            ];
            
            $stmt = $conn->prepare("INSERT INTO empresas (nome, nif, endereco, telefone, email) VALUES (:nome, :nif, :endereco, :telefone, :email)");
            
            foreach ($empresas as $empresa) {
                $stmt->bindParam(':nome', $empresa['nome']);
                $stmt->bindParam(':nif', $empresa['nif']);
                $stmt->bindParam(':endereco', $empresa['endereco']);
                $stmt->bindParam(':telefone', $empresa['telefone']);
                $stmt->bindParam(':email', $empresa['email']);
                $stmt->execute();
            }
            
            showMessage("Empresas inseridas com sucesso!", 'success');
            return true;
        } catch (PDOException $e) {
            showMessage("Erro ao inserir empresas: " . $e->getMessage(), 'error');
            return false;
        }
    } else {
        showMessage("A tabela empresas já possui dados.", 'info');
        return true;
    }
}

// Função para inserir dados na tabela lotes
function populateLotes($conn) {
    if (isTableEmpty($conn, 'lotes')) {
        try {
            // Obter IDs de municípios
            $stmt = $conn->prepare("SELECT id, nome FROM municipios LIMIT 5");
            $stmt->execute();
            $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($municipios) == 0) {
                showMessage("Não há municípios cadastrados. Insira municípios primeiro.", 'error');
                return false;
            }
            
            $lotes = [
                ['codigo' => 'L001', 'nome' => 'Lote 1', 'area' => 1000.50, 'descricao' => 'Lote residencial'],
                ['codigo' => 'L002', 'nome' => 'Lote 2', 'area' => 2500.75, 'descricao' => 'Lote comercial'],
                ['codigo' => 'L003', 'nome' => 'Lote 3', 'area' => 5000.25, 'descricao' => 'Lote industrial'],
                ['codigo' => 'L004', 'nome' => 'Lote 4', 'area' => 1200.00, 'descricao' => 'Lote misto'],
                ['codigo' => 'L005', 'nome' => 'Lote 5', 'area' => 3000.50, 'descricao' => 'Lote agrícola']
            ];
            
            $stmt = $conn->prepare("INSERT INTO lotes (codigo, nome, area, descricao, municipio_id) VALUES (:codigo, :nome, :area, :descricao, :municipio_id)");
            
            for ($i = 0; $i < count($lotes); $i++) {
                $municipioId = $municipios[$i % count($municipios)]['id'];
                
                $stmt->bindParam(':codigo', $lotes[$i]['codigo']);
                $stmt->bindParam(':nome', $lotes[$i]['nome']);
                $stmt->bindParam(':area', $lotes[$i]['area']);
                $stmt->bindParam(':descricao', $lotes[$i]['descricao']);
                $stmt->bindParam(':municipio_id', $municipioId);
                $stmt->execute();
            }
            
            showMessage("Lotes inseridos com sucesso!", 'success');
            return true;
        } catch (PDOException $e) {
            showMessage("Erro ao inserir lotes: " . $e->getMessage(), 'error');
            return false;
        }
    } else {
        showMessage("A tabela lotes já possui dados.", 'info');
        return true;
    }
}

// Função para inserir dados na tabela infraestruturas
function populateInfraestruturas($conn) {
    if (isTableEmpty($conn, 'infraestruturas')) {
        try {
            // Obter IDs de lotes
            $stmt = $conn->prepare("SELECT id, nome FROM lotes");
            $stmt->execute();
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($lotes) == 0) {
                showMessage("Não há lotes cadastrados. Insira lotes primeiro.", 'error');
                return false;
            }
            
            $infraestruturas = [
                ['tipo' => 'Estrada', 'nome' => 'Estrada Principal', 'descricao' => 'Estrada asfaltada de acesso principal'],
                ['tipo' => 'Ponte', 'nome' => 'Ponte Rio Kwanza', 'descricao' => 'Ponte sobre o rio Kwanza'],
                ['tipo' => 'Escola', 'nome' => 'Escola Municipal', 'descricao' => 'Escola de ensino fundamental'],
                ['tipo' => 'Hospital', 'nome' => 'Hospital Regional', 'descricao' => 'Hospital com atendimento de emergência'],
                ['tipo' => 'Barragem', 'nome' => 'Barragem Hidroelétrica', 'descricao' => 'Barragem para geração de energia']
            ];
            
            $stmt = $conn->prepare("INSERT INTO infraestruturas (tipo, nome, descricao, lote_id) VALUES (:tipo, :nome, :descricao, :lote_id)");
            
            for ($i = 0; $i < count($infraestruturas); $i++) {
                $loteId = $lotes[$i % count($lotes)]['id'];
                
                $stmt->bindParam(':tipo', $infraestruturas[$i]['tipo']);
                $stmt->bindParam(':nome', $infraestruturas[$i]['nome']);
                $stmt->bindParam(':descricao', $infraestruturas[$i]['descricao']);
                $stmt->bindParam(':lote_id', $loteId);
                $stmt->execute();
            }
            
            showMessage("Infraestruturas inseridas com sucesso!", 'success');
            return true;
        } catch (PDOException $e) {
            showMessage("Erro ao inserir infraestruturas: " . $e->getMessage(), 'error');
            return false;
        }
    } else {
        showMessage("A tabela infraestruturas já possui dados.", 'info');
        return true;
    }
}

// Função para inserir dados na tabela empresas_lotes
function populateEmpresasLotes($conn) {
    if (isTableEmpty($conn, 'empresas_lotes')) {
        try {
            // Obter IDs de empresas
            $stmt = $conn->prepare("SELECT id FROM empresas");
            $stmt->execute();
            $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter IDs de lotes
            $stmt = $conn->prepare("SELECT id FROM lotes");
            $stmt->execute();
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($empresas) == 0 || count($lotes) == 0) {
                showMessage("Não há empresas ou lotes cadastrados. Insira-os primeiro.", 'error');
                return false;
            }
            
            $stmt = $conn->prepare("INSERT INTO empresas_lotes (empresa_id, lote_id, data_atribuicao, observacoes) VALUES (:empresa_id, :lote_id, :data_atribuicao, :observacoes)");
            
            // Atribuir alguns lotes a empresas
            $atribuicoes = [
                ['empresa' => 0, 'lote' => 0, 'data' => '2023-01-15', 'obs' => 'Atribuição inicial'],
                ['empresa' => 1, 'lote' => 1, 'data' => '2023-02-20', 'obs' => 'Projeto residencial'],
                ['empresa' => 2, 'lote' => 2, 'data' => '2023-03-10', 'obs' => 'Expansão industrial'],
                ['empresa' => 3, 'lote' => 3, 'data' => '2023-04-05', 'obs' => 'Desenvolvimento comercial'],
                ['empresa' => 4, 'lote' => 4, 'data' => '2023-05-12', 'obs' => 'Projeto agrícola']
            ];
            
            foreach ($atribuicoes as $atribuicao) {
                if (isset($empresas[$atribuicao['empresa']]) && isset($lotes[$atribuicao['lote']])) {
                    $empresaId = $empresas[$atribuicao['empresa']]['id'];
                    $loteId = $lotes[$atribuicao['lote']]['id'];
                    $data = $atribuicao['data'];
                    $obs = $atribuicao['obs'];
                    
                    $stmt->bindParam(':empresa_id', $empresaId);
                    $stmt->bindParam(':lote_id', $loteId);
                    $stmt->bindParam(':data_atribuicao', $data);
                    $stmt->bindParam(':observacoes', $obs);
                    $stmt->execute();
                }
            }
            
            showMessage("Relações entre empresas e lotes inseridas com sucesso!", 'success');
            return true;
        } catch (PDOException $e) {
            showMessage("Erro ao inserir relações entre empresas e lotes: " . $e->getMessage(), 'error');
            return false;
        }
    } else {
        showMessage("A tabela empresas_lotes já possui dados.", 'info');
        return true;
    }
}

// Verificar se o script está sendo executado diretamente
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
    // Estilo básico para a página
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Preencher Base de Dados - PDI Angola</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            h1 { color: #333; }
            .container { max-width: 800px; margin: 0 auto; }
            .btn { 
                display: inline-block; 
                padding: 10px 15px; 
                background-color: #4CAF50; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px; 
                margin-top: 20px;
            }
            .btn:hover { background-color: #45a049; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Preencher Base de Dados - PDI Angola</h1>";
    
    // Executar funções para preencher as tabelas
    populateNiveisAcesso($conn);
    populateUsuarios($conn);
    populateProvincias($conn);
    populateMunicipios($conn);
    populateEmpresas($conn);
    populateLotes($conn);
    populateInfraestruturas($conn);
    populateEmpresasLotes($conn);
    
    echo "<a href='../frontend/index.html' class='btn'>Voltar para o Sistema</a>
        </div>
    </body>
    </html>";
}
?>