<?php
/**
 * Classe para validação e sanitização de dados
 * Melhoria de segurança para a plataforma PDI Angola
 */

class Validator {
    private $errors = [];
    
    /**
     * Sanitiza uma string removendo caracteres perigosos
     */
    public function sanitizeString($input) {
        if ($input === null) return null;
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitiza um email
     */
    public function sanitizeEmail($email) {
        if ($email === null) return null;
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitiza um número inteiro
     */
    public function sanitizeInt($input) {
        if ($input === null || $input === '') return null;
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitiza um número decimal
     */
    public function sanitizeFloat($input) {
        if ($input === null || $input === '') return null;
        return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Valida se um campo é obrigatório
     */
    public function required($value, $fieldName) {
        if (empty($value) && $value !== 0 && $value !== '0') {
            $this->errors[] = "O campo {$fieldName} é obrigatório";
            return false;
        }
        return true;
    }
    
    /**
     * Valida email
     */
    public function email($email, $fieldName = 'email') {
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "O campo {$fieldName} deve conter um email válido";
            return false;
        }
        return true;
    }
    
    /**
     * Valida comprimento mínimo
     */
    public function minLength($value, $min, $fieldName) {
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[] = "O campo {$fieldName} deve ter pelo menos {$min} caracteres";
            return false;
        }
        return true;
    }
    
    /**
     * Valida comprimento máximo
     */
    public function maxLength($value, $max, $fieldName) {
        if (!empty($value) && strlen($value) > $max) {
            $this->errors[] = "O campo {$fieldName} deve ter no máximo {$max} caracteres";
            return false;
        }
        return true;
    }
    
    /**
     * Valida se um valor está dentro de uma lista de opções
     */
    public function inArray($value, $options, $fieldName) {
        if (!empty($value) && !in_array($value, $options)) {
            $this->errors[] = "O campo {$fieldName} contém um valor inválido";
            return false;
        }
        return true;
    }
    
    /**
     * Valida número inteiro
     */
    public function integer($value, $fieldName) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[] = "O campo {$fieldName} deve ser um número inteiro válido";
            return false;
        }
        return true;
    }
    
    /**
     * Valida número decimal
     */
    public function float($value, $fieldName) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_FLOAT)) {
            $this->errors[] = "O campo {$fieldName} deve ser um número válido";
            return false;
        }
        return true;
    }
    
    /**
     * Valida data no formato Y-m-d
     */
    public function date($date, $fieldName) {
        if (!empty($date)) {
            $d = DateTime::createFromFormat('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) {
                $this->errors[] = "O campo {$fieldName} deve conter uma data válida (YYYY-MM-DD)";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Valida coordenadas GPS (latitude)
     */
    public function latitude($lat, $fieldName = 'latitude') {
        if (!empty($lat)) {
            $lat = floatval($lat);
            if ($lat < -90 || $lat > 90) {
                $this->errors[] = "O campo {$fieldName} deve estar entre -90 e 90";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Valida coordenadas GPS (longitude)
     */
    public function longitude($lng, $fieldName = 'longitude') {
        if (!empty($lng)) {
            $lng = floatval($lng);
            if ($lng < -180 || $lng > 180) {
                $this->errors[] = "O campo {$fieldName} deve estar entre -180 e 180";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Valida NIF angolano (formato básico)
     */
    public function nifAngola($nif, $fieldName = 'NIF') {
        if (!empty($nif)) {
            // Remove espaços e caracteres especiais
            $nif = preg_replace('/[^0-9]/', '', $nif);
            
            // Verifica se tem 9 dígitos
            if (strlen($nif) !== 9) {
                $this->errors[] = "O {$fieldName} deve ter 9 dígitos";
                return false;
            }
            
            // Verifica se não são todos os mesmos dígitos
            if (preg_match('/^(\d)\1{8}$/', $nif)) {
                $this->errors[] = "O {$fieldName} não pode ter todos os dígitos iguais";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Valida telefone angolano
     */
    public function telefoneAngola($telefone, $fieldName = 'telefone') {
        if (!empty($telefone)) {
            // Remove espaços e caracteres especiais
            $telefone = preg_replace('/[^0-9+]/', '', $telefone);
            
            // Verifica formatos válidos para Angola
            $patterns = [
                '/^9[0-9]{8}$/',           // 9XXXXXXXX (formato nacional)
                '/^\+2449[0-9]{8}$/',      // +2449XXXXXXXX (formato internacional)
                '/^2449[0-9]{8}$/',        // 2449XXXXXXXX (formato sem +)
            ];
            
            $valid = false;
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $telefone)) {
                    $valid = true;
                    break;
                }
            }
            
            if (!$valid) {
                $this->errors[] = "O {$fieldName} deve ter um formato válido para Angola";
                return false;
            }
        }
        return true;
    }
    
    /**
     * Valida dados de infraestrutura
     */
    public function validateInfraestrutura($data) {
        $this->errors = []; // Reset errors
        
        // Campos obrigatórios
        $this->required($data['polo_id'] ?? null, 'Polo');
        $this->required($data['provincia_id'] ?? null, 'Província');
        $this->required($data['municipio_id'] ?? null, 'Município');
        
        // Validações específicas
        $this->integer($data['polo_id'] ?? null, 'Polo');
        $this->integer($data['provincia_id'] ?? null, 'Província');
        $this->integer($data['municipio_id'] ?? null, 'Município');
        $this->integer($data['comuna_id'] ?? null, 'Comuna');
        $this->integer($data['sector_id'] ?? null, 'Sector');
        
        // Coordenadas GPS
        $this->latitude($data['latitude'] ?? null);
        $this->longitude($data['longitude'] ?? null);
        
        // Campos de texto com limite
        $this->maxLength($data['bairro_local'] ?? '', 255, 'Bairro/Local');
        $this->maxLength($data['rua_numero'] ?? '', 255, 'Rua/Número');
        $this->maxLength($data['nome_representante'] ?? '', 255, 'Nome do Representante');
        
        // Email do representante
        $this->email($data['email_representante'] ?? null, 'Email do Representante');
        
        // Telefone do representante
        $this->telefoneAngola($data['telefone_representante'] ?? null, 'Telefone do Representante');
        
        // Validações de valores específicos
        $proprietarioOptions = ['publico', 'privado', 'misto'];
        $this->inArray($data['proprietario_terreno'] ?? null, $proprietarioOptions, 'Proprietário do Terreno');
        
        $estadoOptions = ['excelente', 'bom', 'regular', 'mau', 'pessimo'];
        $this->inArray($data['estado_atual'] ?? null, $estadoOptions, 'Estado Atual');
        
        $redeOptions = ['sim', 'nao', 'parcial'];
        $this->inArray($data['rede_hidraulica'] ?? null, $redeOptions, 'Rede Hidráulica');
        $this->inArray($data['rede_esgotos_domesticos'] ?? null, $redeOptions, 'Rede de Esgotos Domésticos');
        $this->inArray($data['rede_esgotos_pluviais'] ?? null, $redeOptions, 'Rede de Esgotos Pluviais');
        $this->inArray($data['rede_eletrica'] ?? null, $redeOptions, 'Rede Elétrica');
        $this->inArray($data['vias_acesso'] ?? null, $redeOptions, 'Vias de Acesso');
        
        // Ano de construção
        if (!empty($data['ano_construcao'])) {
            $ano = intval($data['ano_construcao']);
            if ($ano < 1900 || $ano > date('Y') + 5) {
                $this->errors[] = "O ano de construção deve estar entre 1900 e " . (date('Y') + 5);
            }
        }
        
        // Área
        $this->float($data['area'] ?? null, 'Área');
        if (!empty($data['area']) && floatval($data['area']) <= 0) {
            $this->errors[] = "A área deve ser um valor positivo";
        }
        
        return empty($this->errors);
    }
    
    /**
     * Valida dados de lote
     */
    public function validateLote($data) {
        $this->errors = []; // Reset errors
        
        // Campos obrigatórios
        $this->required($data['polo_id'] ?? null, 'Polo');
        $this->required($data['nome_lote'] ?? null, 'Nome do Lote');
        
        // Validações específicas
        $this->integer($data['polo_id'] ?? null, 'Polo');
        $this->maxLength($data['nome_lote'] ?? '', 255, 'Nome do Lote');
        
        // Área
        $this->float($data['area_m2'] ?? null, 'Área (m²)');
        if (!empty($data['area_m2']) && floatval($data['area_m2']) <= 0) {
            $this->errors[] = "A área deve ser um valor positivo";
        }
        
        // Status
        $statusOptions = ['disponivel', 'ocupado', 'reservado', 'em_desenvolvimento'];
        $this->inArray($data['status'] ?? null, $statusOptions, 'Status');
        
        // Descrição
        $this->maxLength($data['descricao'] ?? '', 1000, 'Descrição');
        
        return empty($this->errors);
    }
    
    /**
     * Valida dados de empresa
     */
    public function validateEmpresa($data) {
        $this->errors = []; // Reset errors
        
        // Campos obrigatórios
        $this->required($data['polo_id'] ?? null, 'Polo');
        $this->required($data['nome_empresa'] ?? null, 'Nome da Empresa');
        
        // Validações específicas
        $this->integer($data['polo_id'] ?? null, 'Polo');
        $this->integer($data['lote_id'] ?? null, 'Lote');
        $this->integer($data['sector_id'] ?? null, 'Sector');
        
        $this->maxLength($data['nome_empresa'] ?? '', 255, 'Nome da Empresa');
        
        // NIF
        $this->nifAngola($data['nif'] ?? null);
        
        // Número de funcionários
        $this->integer($data['num_funcionarios'] ?? null, 'Número de Funcionários');
        if (!empty($data['num_funcionarios']) && intval($data['num_funcionarios']) < 0) {
            $this->errors[] = "O número de funcionários deve ser um valor positivo";
        }
        
        // Contatos
        $this->maxLength($data['contacto_principal'] ?? '', 255, 'Contacto Principal');
        $this->email($data['email_principal'] ?? null, 'Email Principal');
        $this->telefoneAngola($data['telefone_principal'] ?? null, 'Telefone Principal');
        
        // Website
        if (!empty($data['website'])) {
            if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
                $this->errors[] = "O website deve ser uma URL válida";
            }
        }
        
        // Data de instalação
        $this->date($data['data_instalacao'] ?? null, 'Data de Instalação');
        
        // Status
        $statusOptions = ['ativa', 'inativa', 'em_implementacao', 'em_expansao'];
        $this->inArray($data['status'] ?? null, $statusOptions, 'Status');
        
        // Observações
        $this->maxLength($data['observacoes'] ?? '', 2000, 'Observações');
        
        return empty($this->errors);
    }
    
    /**
     * Sanitiza dados de infraestrutura
     */
    public function sanitizeInfraestrutura($data) {
        return [
            'polo_id' => $this->sanitizeInt($data['polo_id'] ?? null),
            'provincia_id' => $this->sanitizeInt($data['provincia_id'] ?? null),
            'municipio_id' => $this->sanitizeInt($data['municipio_id'] ?? null),
            'comuna_id' => $this->sanitizeInt($data['comuna_id'] ?? null),
            'bairro_local' => $this->sanitizeString($data['bairro_local'] ?? null),
            'rua_numero' => $this->sanitizeString($data['rua_numero'] ?? null),
            'latitude' => $this->sanitizeFloat($data['latitude'] ?? null),
            'longitude' => $this->sanitizeFloat($data['longitude'] ?? null),
            'proprietario_terreno' => $this->sanitizeString($data['proprietario_terreno'] ?? 'publico'),
            'programa' => $this->sanitizeString($data['programa'] ?? null),
            'area' => $this->sanitizeFloat($data['area'] ?? null),
            'confrontacao_norte' => $this->sanitizeString($data['confrontacao_norte'] ?? null),
            'confrontacao_sul' => $this->sanitizeString($data['confrontacao_sul'] ?? null),
            'confrontacao_este' => $this->sanitizeString($data['confrontacao_este'] ?? null),
            'confrontacao_oeste' => $this->sanitizeString($data['confrontacao_oeste'] ?? null),
            'pre_existencias' => $this->sanitizeString($data['pre_existencias'] ?? null),
            'ocupacao_indevida' => $this->sanitizeString($data['ocupacao_indevida'] ?? null),
            'condicoes_solos' => $this->sanitizeString($data['condicoes_solos'] ?? null),
            'condicao_vegetacao' => $this->sanitizeString($data['condicao_vegetacao'] ?? null),
            'vedacoes' => $this->sanitizeString($data['vedacoes'] ?? null),
            'rede_hidraulica' => $this->sanitizeString($data['rede_hidraulica'] ?? 'nao'),
            'rede_esgotos_domesticos' => $this->sanitizeString($data['rede_esgotos_domesticos'] ?? 'nao'),
            'rede_esgotos_pluviais' => $this->sanitizeString($data['rede_esgotos_pluviais'] ?? 'nao'),
            'rede_eletrica' => $this->sanitizeString($data['rede_eletrica'] ?? 'nao'),
            'vias_acesso' => $this->sanitizeString($data['vias_acesso'] ?? 'nao'),
            'nome_representante' => $this->sanitizeString($data['nome_representante'] ?? null),
            'email_representante' => $this->sanitizeEmail($data['email_representante'] ?? null),
            'telefone_representante' => $this->sanitizeString($data['telefone_representante'] ?? null),
            'ano_construcao' => $this->sanitizeInt($data['ano_construcao'] ?? null),
            'tipologia' => $this->sanitizeString($data['tipologia'] ?? null),
            'sector_id' => $this->sanitizeInt($data['sector_id'] ?? null),
            'estado_atual' => $this->sanitizeString($data['estado_atual'] ?? 'bom'),
            'capacidade_instalada' => $this->sanitizeString($data['capacidade_instalada'] ?? null),
            'produtos' => $this->sanitizeString($data['produtos'] ?? null),
            'constrangimentos' => $this->sanitizeString($data['constrangimentos'] ?? null),
            'observacoes' => $this->sanitizeString($data['observacoes'] ?? null)
        ];
    }
    
    /**
     * Sanitiza dados de lote
     */
    public function sanitizeLote($data) {
        return [
            'polo_id' => $this->sanitizeInt($data['polo_id'] ?? null),
            'nome_lote' => $this->sanitizeString($data['nome_lote'] ?? null),
            'area_m2' => $this->sanitizeFloat($data['area_m2'] ?? null),
            'status' => $this->sanitizeString($data['status'] ?? 'disponivel'),
            'descricao' => $this->sanitizeString($data['descricao'] ?? null)
        ];
    }
    
    /**
     * Sanitiza dados de empresa
     */
    public function sanitizeEmpresa($data) {
        return [
            'lote_id' => $this->sanitizeInt($data['lote_id'] ?? null),
            'polo_id' => $this->sanitizeInt($data['polo_id'] ?? null),
            'nome_empresa' => $this->sanitizeString($data['nome_empresa'] ?? null),
            'nif' => $this->sanitizeString($data['nif'] ?? null),
            'sector_id' => $this->sanitizeInt($data['sector_id'] ?? null),
            'atividade_principal' => $this->sanitizeString($data['atividade_principal'] ?? null),
            'num_funcionarios' => $this->sanitizeInt($data['num_funcionarios'] ?? null),
            'contacto_principal' => $this->sanitizeString($data['contacto_principal'] ?? null),
            'email_principal' => $this->sanitizeEmail($data['email_principal'] ?? null),
            'telefone_principal' => $this->sanitizeString($data['telefone_principal'] ?? null),
            'website' => $this->sanitizeString($data['website'] ?? null),
            'data_instalacao' => $this->sanitizeString($data['data_instalacao'] ?? null),
            'status' => $this->sanitizeString($data['status'] ?? 'ativa'),
            'observacoes' => $this->sanitizeString($data['observacoes'] ?? null)
        ];
    }
    
    /**
     * Retorna os erros de validação
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Verifica se há erros
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Limpa os erros
     */
    public function clearErrors() {
        $this->errors = [];
    }
}
?>

