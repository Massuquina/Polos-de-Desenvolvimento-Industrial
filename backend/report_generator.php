<?php
/**
 * Classe para geração de relatórios em PDF
 * Nova funcionalidade para a plataforma PDI Angola
 */

require_once 'database.php';

class ReportGenerator {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = new Database();
        global $logger;
        $this->logger = $logger;
    }
    
    /**
     * Gera relatório geral de infraestruturas
     */
    public function generateInfraestruturaReport($filters = []) {
        try {
            $startTime = microtime(true);
            
            // Construir query com filtros
            $where = [];
            $params = [];
            
            if (!empty($filters['provincia_id'])) {
                $where[] = "i.provincia_id = ?";
                $params[] = $filters['provincia_id'];
            }
            
            if (!empty($filters['polo_id'])) {
                $where[] = "i.polo_id = ?";
                $params[] = $filters['polo_id'];
            }
            
            if (!empty($filters['sector_id'])) {
                $where[] = "i.sector_id = ?";
                $params[] = $filters['sector_id'];
            }
            
            if (!empty($filters['estado_atual'])) {
                $where[] = "i.estado_atual = ?";
                $params[] = $filters['estado_atual'];
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Buscar dados
            $sql = "SELECT i.*, 
                           p.nome as provincia_nome, 
                           m.nome as municipio_nome,
                           c.nome as comuna_nome,
                           pd.nome as polo_nome,
                           s.nome as sector_nome
                    FROM infraestruturas i
                    JOIN provincias p ON i.provincia_id = p.id
                    JOIN municipios m ON i.municipio_id = m.id
                    LEFT JOIN comunas c ON i.comuna_id = c.id
                    JOIN polos_desenvolvimento pd ON i.polo_id = pd.id
                    LEFT JOIN sectores s ON i.sector_id = s.id
                    $whereClause
                    ORDER BY p.nome, pd.nome, i.created_at";
            
            $infraestruturas = $this->db->select($sql, $params);
            
            // Gerar estatísticas
            $stats = $this->generateInfraestruturaStats($infraestruturas);
            
            // Gerar HTML do relatório
            $html = $this->generateInfraestruturaHTML($infraestruturas, $stats, $filters);
            
            // Converter para PDF
            $pdfPath = $this->convertToPDF($html, 'relatorio_infraestruturas_' . date('Y-m-d_H-i-s') . '.pdf');
            
            $executionTime = microtime(true) - $startTime;
            $this->logger->performance('Generate Infrastructure Report', $executionTime, [
                'total_records' => count($infraestruturas),
                'filters' => $filters
            ]);
            
            $this->logger->userActivity('generate_report', 'infraestruturas', null, [
                'report_type' => 'infrastructure',
                'total_records' => count($infraestruturas),
                'filters' => $filters
            ]);
            
            return $pdfPath;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to generate infrastructure report', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }
    
    /**
     * Gera relatório de lotes
     */
    public function generateLotesReport($filters = []) {
        try {
            $startTime = microtime(true);
            
            // Construir query com filtros
            $where = [];
            $params = [];
            
            if (!empty($filters['polo_id'])) {
                $where[] = "l.polo_id = ?";
                $params[] = $filters['polo_id'];
            }
            
            if (!empty($filters['status'])) {
                $where[] = "l.status = ?";
                $params[] = $filters['status'];
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Buscar dados
            $sql = "SELECT l.*, pd.nome as polo_nome, p.nome as provincia_nome
                    FROM lotes l
                    JOIN polos_desenvolvimento pd ON l.polo_id = pd.id
                    JOIN provincias p ON pd.provincia_id = p.id
                    $whereClause
                    ORDER BY p.nome, pd.nome, l.nome_lote";
            
            $lotes = $this->db->select($sql, $params);
            
            // Gerar estatísticas
            $stats = $this->generateLotesStats($lotes);
            
            // Gerar HTML do relatório
            $html = $this->generateLotesHTML($lotes, $stats, $filters);
            
            // Converter para PDF
            $pdfPath = $this->convertToPDF($html, 'relatorio_lotes_' . date('Y-m-d_H-i-s') . '.pdf');
            
            $executionTime = microtime(true) - $startTime;
            $this->logger->performance('Generate Lotes Report', $executionTime, [
                'total_records' => count($lotes),
                'filters' => $filters
            ]);
            
            $this->logger->userActivity('generate_report', 'lotes', null, [
                'report_type' => 'lotes',
                'total_records' => count($lotes),
                'filters' => $filters
            ]);
            
            return $pdfPath;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to generate lotes report', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }
    
    /**
     * Gera relatório de empresas
     */
    public function generateEmpresasReport($filters = []) {
        try {
            $startTime = microtime(true);
            
            // Construir query com filtros
            $where = [];
            $params = [];
            
            if (!empty($filters['polo_id'])) {
                $where[] = "e.polo_id = ?";
                $params[] = $filters['polo_id'];
            }
            
            if (!empty($filters['sector_id'])) {
                $where[] = "e.sector_id = ?";
                $params[] = $filters['sector_id'];
            }
            
            if (!empty($filters['status'])) {
                $where[] = "e.status = ?";
                $params[] = $filters['status'];
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Buscar dados
            $sql = "SELECT e.*, pd.nome as polo_nome, p.nome as provincia_nome,
                           l.nome_lote, s.nome as sector_nome
                    FROM empresas e
                    JOIN polos_desenvolvimento pd ON e.polo_id = pd.id
                    JOIN provincias p ON pd.provincia_id = p.id
                    LEFT JOIN lotes l ON e.lote_id = l.id
                    LEFT JOIN sectores s ON e.sector_id = s.id
                    $whereClause
                    ORDER BY p.nome, pd.nome, e.nome_empresa";
            
            $empresas = $this->db->select($sql, $params);
            
            // Gerar estatísticas
            $stats = $this->generateEmpresasStats($empresas);
            
            // Gerar HTML do relatório
            $html = $this->generateEmpresasHTML($empresas, $stats, $filters);
            
            // Converter para PDF
            $pdfPath = $this->convertToPDF($html, 'relatorio_empresas_' . date('Y-m-d_H-i-s') . '.pdf');
            
            $executionTime = microtime(true) - $startTime;
            $this->logger->performance('Generate Empresas Report', $executionTime, [
                'total_records' => count($empresas),
                'filters' => $filters
            ]);
            
            $this->logger->userActivity('generate_report', 'empresas', null, [
                'report_type' => 'empresas',
                'total_records' => count($empresas),
                'filters' => $filters
            ]);
            
            return $pdfPath;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to generate empresas report', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }
    
    /**
     * Gera relatório consolidado (dashboard)
     */
    public function generateDashboardReport() {
        try {
            $startTime = microtime(true);
            
            // Buscar dados consolidados
            $stats = [
                'infraestruturas' => $this->getInfraestruturaStats(),
                'lotes' => $this->getLotesStats(),
                'empresas' => $this->getEmpresasStats(),
                'polos' => $this->getPolosStats()
            ];
            
            // Gerar HTML do relatório
            $html = $this->generateDashboardHTML($stats);
            
            // Converter para PDF
            $pdfPath = $this->convertToPDF($html, 'relatorio_dashboard_' . date('Y-m-d_H-i-s') . '.pdf');
            
            $executionTime = microtime(true) - $startTime;
            $this->logger->performance('Generate Dashboard Report', $executionTime);
            
            $this->logger->userActivity('generate_report', 'dashboard', null, [
                'report_type' => 'dashboard'
            ]);
            
            return $pdfPath;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to generate dashboard report', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Gera estatísticas de infraestruturas
     */
    private function generateInfraestruturaStats($infraestruturas) {
        $stats = [
            'total' => count($infraestruturas),
            'por_provincia' => [],
            'por_polo' => [],
            'por_sector' => [],
            'por_estado' => [],
            'com_coordenadas' => 0,
            'area_total' => 0
        ];
        
        foreach ($infraestruturas as $infra) {
            // Por província
            $provincia = $infra['provincia_nome'];
            $stats['por_provincia'][$provincia] = ($stats['por_provincia'][$provincia] ?? 0) + 1;
            
            // Por polo
            $polo = $infra['polo_nome'];
            $stats['por_polo'][$polo] = ($stats['por_polo'][$polo] ?? 0) + 1;
            
            // Por sector
            if ($infra['sector_nome']) {
                $sector = $infra['sector_nome'];
                $stats['por_sector'][$sector] = ($stats['por_sector'][$sector] ?? 0) + 1;
            }
            
            // Por estado
            $estado = $infra['estado_atual'];
            $stats['por_estado'][$estado] = ($stats['por_estado'][$estado] ?? 0) + 1;
            
            // Com coordenadas
            if ($infra['latitude'] && $infra['longitude']) {
                $stats['com_coordenadas']++;
            }
            
            // Área total
            if ($infra['area']) {
                $stats['area_total'] += floatval($infra['area']);
            }
        }
        
        return $stats;
    }
    
    /**
     * Gera estatísticas de lotes
     */
    private function generateLotesStats($lotes) {
        $stats = [
            'total' => count($lotes),
            'por_polo' => [],
            'por_status' => [],
            'area_total' => 0,
            'area_media' => 0
        ];
        
        $areas = [];
        
        foreach ($lotes as $lote) {
            // Por polo
            $polo = $lote['polo_nome'];
            $stats['por_polo'][$polo] = ($stats['por_polo'][$polo] ?? 0) + 1;
            
            // Por status
            $status = $lote['status'];
            $stats['por_status'][$status] = ($stats['por_status'][$status] ?? 0) + 1;
            
            // Área
            if ($lote['area_m2']) {
                $area = floatval($lote['area_m2']);
                $stats['area_total'] += $area;
                $areas[] = $area;
            }
        }
        
        if (!empty($areas)) {
            $stats['area_media'] = array_sum($areas) / count($areas);
        }
        
        return $stats;
    }
    
    /**
     * Gera estatísticas de empresas
     */
    private function generateEmpresasStats($empresas) {
        $stats = [
            'total' => count($empresas),
            'por_polo' => [],
            'por_sector' => [],
            'por_status' => [],
            'funcionarios_total' => 0,
            'funcionarios_media' => 0
        ];
        
        $funcionarios = [];
        
        foreach ($empresas as $empresa) {
            // Por polo
            $polo = $empresa['polo_nome'];
            $stats['por_polo'][$polo] = ($stats['por_polo'][$polo] ?? 0) + 1;
            
            // Por sector
            if ($empresa['sector_nome']) {
                $sector = $empresa['sector_nome'];
                $stats['por_sector'][$sector] = ($stats['por_sector'][$sector] ?? 0) + 1;
            }
            
            // Por status
            $status = $empresa['status'];
            $stats['por_status'][$status] = ($stats['por_status'][$status] ?? 0) + 1;
            
            // Funcionários
            if ($empresa['num_funcionarios']) {
                $num = intval($empresa['num_funcionarios']);
                $stats['funcionarios_total'] += $num;
                $funcionarios[] = $num;
            }
        }
        
        if (!empty($funcionarios)) {
            $stats['funcionarios_media'] = array_sum($funcionarios) / count($funcionarios);
        }
        
        return $stats;
    }
    
    /**
     * Obtém estatísticas gerais de infraestruturas
     */
    private function getInfraestruturaStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 END) as com_coordenadas,
                    SUM(CASE WHEN area IS NOT NULL THEN area ELSE 0 END) as area_total,
                    AVG(CASE WHEN area IS NOT NULL THEN area ELSE NULL END) as area_media
                FROM infraestruturas";
        
        return $this->db->selectOne($sql);
    }
    
    /**
     * Obtém estatísticas gerais de lotes
     */
    private function getLotesStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'disponivel' THEN 1 END) as disponiveis,
                    COUNT(CASE WHEN status = 'ocupado' THEN 1 END) as ocupados,
                    COUNT(CASE WHEN status = 'reservado' THEN 1 END) as reservados,
                    SUM(CASE WHEN area_m2 IS NOT NULL THEN area_m2 ELSE 0 END) as area_total,
                    AVG(CASE WHEN area_m2 IS NOT NULL THEN area_m2 ELSE NULL END) as area_media
                FROM lotes";
        
        return $this->db->selectOne($sql);
    }
    
    /**
     * Obtém estatísticas gerais de empresas
     */
    private function getEmpresasStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'ativa' THEN 1 END) as ativas,
                    COUNT(CASE WHEN status = 'inativa' THEN 1 END) as inativas,
                    SUM(CASE WHEN num_funcionarios IS NOT NULL THEN num_funcionarios ELSE 0 END) as funcionarios_total,
                    AVG(CASE WHEN num_funcionarios IS NOT NULL THEN num_funcionarios ELSE NULL END) as funcionarios_media
                FROM empresas";
        
        return $this->db->selectOne($sql);
    }
    
    /**
     * Obtém estatísticas gerais de polos
     */
    private function getPolosStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'ativo' THEN 1 END) as ativos,
                    COUNT(CASE WHEN status = 'inativo' THEN 1 END) as inativos
                FROM polos_desenvolvimento";
        
        return $this->db->selectOne($sql);
    }
    
    /**
     * Gera HTML para relatório de infraestruturas
     */
    private function generateInfraestruturaHTML($infraestruturas, $stats, $filters) {
        $html = $this->getReportHeader('Relatório de Infraestruturas');
        
        // Filtros aplicados
        if (!empty($filters)) {
            $html .= '<div class="filters-section">';
            $html .= '<h3>Filtros Aplicados:</h3>';
            $html .= '<ul>';
            foreach ($filters as $key => $value) {
                if (!empty($value)) {
                    $html .= "<li><strong>" . ucfirst(str_replace('_', ' ', $key)) . ":</strong> {$value}</li>";
                }
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        // Estatísticas
        $html .= '<div class="stats-section">';
        $html .= '<h3>Estatísticas Gerais:</h3>';
        $html .= '<div class="stats-grid">';
        $html .= "<div class=\"stat-item\"><strong>Total de Infraestruturas:</strong> {$stats['total']}</div>";
        $html .= "<div class=\"stat-item\"><strong>Com Coordenadas GPS:</strong> {$stats['com_coordenadas']}</div>";
        $html .= "<div class=\"stat-item\"><strong>Área Total:</strong> " . number_format($stats['area_total'], 2) . " hectares</div>";
        $html .= '</div>';
        $html .= '</div>';
        
        // Distribuição por província
        if (!empty($stats['por_provincia'])) {
            $html .= '<div class="distribution-section">';
            $html .= '<h3>Distribuição por Província:</h3>';
            $html .= '<table class="report-table">';
            $html .= '<thead><tr><th>Província</th><th>Quantidade</th><th>Percentual</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($stats['por_provincia'] as $provincia => $count) {
                $percent = ($count / $stats['total']) * 100;
                $html .= "<tr><td>{$provincia}</td><td>{$count}</td><td>" . number_format($percent, 1) . "%</td></tr>";
            }
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        // Lista detalhada
        $html .= '<div class="details-section">';
        $html .= '<h3>Lista Detalhada:</h3>';
        $html .= '<table class="report-table">';
        $html .= '<thead><tr><th>ID</th><th>Polo</th><th>Província</th><th>Município</th><th>Sector</th><th>Estado</th><th>Área</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($infraestruturas as $infra) {
            $html .= "<tr>";
            $html .= "<td>{$infra['id']}</td>";
            $html .= "<td>{$infra['polo_nome']}</td>";
            $html .= "<td>{$infra['provincia_nome']}</td>";
            $html .= "<td>{$infra['municipio_nome']}</td>";
            $html .= "<td>" . ($infra['sector_nome'] ?: '-') . "</td>";
            $html .= "<td>{$infra['estado_atual']}</td>";
            $html .= "<td>" . ($infra['area'] ? number_format($infra['area'], 2) . ' ha' : '-') . "</td>";
            $html .= "</tr>";
        }
        $html .= '</tbody></table>';
        $html .= '</div>';
        
        $html .= $this->getReportFooter();
        
        return $html;
    }
    
    /**
     * Gera HTML para relatório de lotes
     */
    private function generateLotesHTML($lotes, $stats, $filters) {
        $html = $this->getReportHeader('Relatório de Lotes');
        
        // Filtros aplicados
        if (!empty($filters)) {
            $html .= '<div class="filters-section">';
            $html .= '<h3>Filtros Aplicados:</h3>';
            $html .= '<ul>';
            foreach ($filters as $key => $value) {
                if (!empty($value)) {
                    $html .= "<li><strong>" . ucfirst(str_replace('_', ' ', $key)) . ":</strong> {$value}</li>";
                }
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        // Estatísticas
        $html .= '<div class="stats-section">';
        $html .= '<h3>Estatísticas Gerais:</h3>';
        $html .= '<div class="stats-grid">';
        $html .= "<div class=\"stat-item\"><strong>Total de Lotes:</strong> {$stats['total']}</div>";
        $html .= "<div class=\"stat-item\"><strong>Área Total:</strong> " . number_format($stats['area_total'], 2) . " m²</div>";
        $html .= "<div class=\"stat-item\"><strong>Área Média:</strong> " . number_format($stats['area_media'], 2) . " m²</div>";
        $html .= '</div>';
        $html .= '</div>';
        
        // Distribuição por status
        if (!empty($stats['por_status'])) {
            $html .= '<div class="distribution-section">';
            $html .= '<h3>Distribuição por Status:</h3>';
            $html .= '<table class="report-table">';
            $html .= '<thead><tr><th>Status</th><th>Quantidade</th><th>Percentual</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($stats['por_status'] as $status => $count) {
                $percent = ($count / $stats['total']) * 100;
                $html .= "<tr><td>" . ucfirst($status) . "</td><td>{$count}</td><td>" . number_format($percent, 1) . "%</td></tr>";
            }
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        // Lista detalhada
        $html .= '<div class="details-section">';
        $html .= '<h3>Lista Detalhada:</h3>';
        $html .= '<table class="report-table">';
        $html .= '<thead><tr><th>ID</th><th>Polo</th><th>Nome do Lote</th><th>Área (m²)</th><th>Status</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($lotes as $lote) {
            $html .= "<tr>";
            $html .= "<td>{$lote['id']}</td>";
            $html .= "<td>{$lote['polo_nome']}</td>";
            $html .= "<td>{$lote['nome_lote']}</td>";
            $html .= "<td>" . ($lote['area_m2'] ? number_format($lote['area_m2'], 2) : '-') . "</td>";
            $html .= "<td>" . ucfirst($lote['status']) . "</td>";
            $html .= "</tr>";
        }
        $html .= '</tbody></table>';
        $html .= '</div>';
        
        $html .= $this->getReportFooter();
        
        return $html;
    }
    
    /**
     * Gera HTML para relatório de empresas
     */
    private function generateEmpresasHTML($empresas, $stats, $filters) {
        $html = $this->getReportHeader('Relatório de Empresas');
        
        // Filtros aplicados
        if (!empty($filters)) {
            $html .= '<div class="filters-section">';
            $html .= '<h3>Filtros Aplicados:</h3>';
            $html .= '<ul>';
            foreach ($filters as $key => $value) {
                if (!empty($value)) {
                    $html .= "<li><strong>" . ucfirst(str_replace('_', ' ', $key)) . ":</strong> {$value}</li>";
                }
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        // Estatísticas
        $html .= '<div class="stats-section">';
        $html .= '<h3>Estatísticas Gerais:</h3>';
        $html .= '<div class="stats-grid">';
        $html .= "<div class=\"stat-item\"><strong>Total de Empresas:</strong> {$stats['total']}</div>";
        $html .= "<div class=\"stat-item\"><strong>Total de Funcionários:</strong> " . number_format($stats['funcionarios_total']) . "</div>";
        $html .= "<div class=\"stat-item\"><strong>Média de Funcionários:</strong> " . number_format($stats['funcionarios_media'], 1) . "</div>";
        $html .= '</div>';
        $html .= '</div>';
        
        // Distribuição por sector
        if (!empty($stats['por_sector'])) {
            $html .= '<div class="distribution-section">';
            $html .= '<h3>Distribuição por Sector:</h3>';
            $html .= '<table class="report-table">';
            $html .= '<thead><tr><th>Sector</th><th>Quantidade</th><th>Percentual</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($stats['por_sector'] as $sector => $count) {
                $percent = ($count / $stats['total']) * 100;
                $html .= "<tr><td>{$sector}</td><td>{$count}</td><td>" . number_format($percent, 1) . "%</td></tr>";
            }
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        // Lista detalhada
        $html .= '<div class="details-section">';
        $html .= '<h3>Lista Detalhada:</h3>';
        $html .= '<table class="report-table">';
        $html .= '<thead><tr><th>ID</th><th>Polo</th><th>Nome da Empresa</th><th>NIF</th><th>Sector</th><th>Funcionários</th><th>Status</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($empresas as $empresa) {
            $html .= "<tr>";
            $html .= "<td>{$empresa['id']}</td>";
            $html .= "<td>{$empresa['polo_nome']}</td>";
            $html .= "<td>{$empresa['nome_empresa']}</td>";
            $html .= "<td>" . ($empresa['nif'] ?: '-') . "</td>";
            $html .= "<td>" . ($empresa['sector_nome'] ?: '-') . "</td>";
            $html .= "<td>" . ($empresa['num_funcionarios'] ? number_format($empresa['num_funcionarios']) : '-') . "</td>";
            $html .= "<td>" . ucfirst($empresa['status']) . "</td>";
            $html .= "</tr>";
        }
        $html .= '</tbody></table>';
        $html .= '</div>';
        
        $html .= $this->getReportFooter();
        
        return $html;
    }
    
    /**
     * Gera HTML para relatório do dashboard
     */
    private function generateDashboardHTML($stats) {
        $html = $this->getReportHeader('Relatório Consolidado - Dashboard');
        
        // Resumo executivo
        $html .= '<div class="executive-summary">';
        $html .= '<h3>Resumo Executivo:</h3>';
        $html .= '<div class="summary-grid">';
        $html .= "<div class=\"summary-item\"><strong>Total de Infraestruturas:</strong> " . number_format($stats['infraestruturas']['total']) . "</div>";
        $html .= "<div class=\"summary-item\"><strong>Total de Lotes:</strong> " . number_format($stats['lotes']['total']) . "</div>";
        $html .= "<div class=\"summary-item\"><strong>Total de Empresas:</strong> " . number_format($stats['empresas']['total']) . "</div>";
        $html .= "<div class=\"summary-item\"><strong>Total de Polos:</strong> " . number_format($stats['polos']['total']) . "</div>";
        $html .= '</div>';
        $html .= '</div>';
        
        // Seção de infraestruturas
        $html .= '<div class="section">';
        $html .= '<h3>Infraestruturas:</h3>';
        $html .= '<ul>';
        $html .= "<li>Total: " . number_format($stats['infraestruturas']['total']) . "</li>";
        $html .= "<li>Com coordenadas GPS: " . number_format($stats['infraestruturas']['com_coordenadas']) . "</li>";
        $html .= "<li>Área total: " . number_format($stats['infraestruturas']['area_total'], 2) . " hectares</li>";
        if ($stats['infraestruturas']['area_media']) {
            $html .= "<li>Área média: " . number_format($stats['infraestruturas']['area_media'], 2) . " hectares</li>";
        }
        $html .= '</ul>';
        $html .= '</div>';
        
        // Seção de lotes
        $html .= '<div class="section">';
        $html .= '<h3>Lotes:</h3>';
        $html .= '<ul>';
        $html .= "<li>Total: " . number_format($stats['lotes']['total']) . "</li>";
        $html .= "<li>Disponíveis: " . number_format($stats['lotes']['disponiveis']) . "</li>";
        $html .= "<li>Ocupados: " . number_format($stats['lotes']['ocupados']) . "</li>";
        $html .= "<li>Reservados: " . number_format($stats['lotes']['reservados']) . "</li>";
        $html .= "<li>Área total: " . number_format($stats['lotes']['area_total'], 2) . " m²</li>";
        if ($stats['lotes']['area_media']) {
            $html .= "<li>Área média: " . number_format($stats['lotes']['area_media'], 2) . " m²</li>";
        }
        $html .= '</ul>';
        $html .= '</div>';
        
        // Seção de empresas
        $html .= '<div class="section">';
        $html .= '<h3>Empresas:</h3>';
        $html .= '<ul>';
        $html .= "<li>Total: " . number_format($stats['empresas']['total']) . "</li>";
        $html .= "<li>Ativas: " . number_format($stats['empresas']['ativas']) . "</li>";
        $html .= "<li>Inativas: " . number_format($stats['empresas']['inativas']) . "</li>";
        $html .= "<li>Total de funcionários: " . number_format($stats['empresas']['funcionarios_total']) . "</li>";
        if ($stats['empresas']['funcionarios_media']) {
            $html .= "<li>Média de funcionários por empresa: " . number_format($stats['empresas']['funcionarios_media'], 1) . "</li>";
        }
        $html .= '</ul>';
        $html .= '</div>';
        
        // Seção de polos
        $html .= '<div class="section">';
        $html .= '<h3>Polos de Desenvolvimento:</h3>';
        $html .= '<ul>';
        $html .= "<li>Total: " . number_format($stats['polos']['total']) . "</li>";
        $html .= "<li>Ativos: " . number_format($stats['polos']['ativos']) . "</li>";
        $html .= "<li>Inativos: " . number_format($stats['polos']['inativos']) . "</li>";
        $html .= '</ul>';
        $html .= '</div>';
        
        $html .= $this->getReportFooter();
        
        return $html;
    }
    
    /**
     * Obtém o cabeçalho padrão dos relatórios
     */
    private function getReportHeader($title) {
        $date = date('d/m/Y H:i:s');
        $userName = 'Sistema';
        
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $userName = $_SESSION['user']['nome'] ?? 'Sistema';
        }
        
        return "
        <!DOCTYPE html>
        <html lang='pt'>
        <head>
            <meta charset='UTF-8'>
            <title>{$title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; border-bottom: 2px solid #d32f2f; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #d32f2f; margin: 0; }
                .header p { margin: 5px 0; color: #666; }
                .stats-section, .distribution-section, .details-section, .filters-section, .executive-summary, .section { margin-bottom: 30px; }
                .stats-grid, .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0; }
                .stat-item, .summary-item { background: #f5f5f5; padding: 10px; border-radius: 5px; }
                .report-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .report-table th, .report-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .report-table th { background-color: #d32f2f; color: white; }
                .report-table tr:nth-child(even) { background-color: #f9f9f9; }
                .filters-section ul { list-style-type: none; padding: 0; }
                .filters-section li { background: #e3f2fd; padding: 5px 10px; margin: 5px 0; border-radius: 3px; }
                .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px; }
                h3 { color: #d32f2f; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>PDI Angola - {$title}</h1>
                <p>Sistema de Gestão de Infraestruturas</p>
                <p>Gerado em: {$date} | Por: {$userName}</p>
            </div>
        ";
    }
    
    /**
     * Obtém o rodapé padrão dos relatórios
     */
    private function getReportFooter() {
        return "
            <div class='footer'>
                <p>PDI Angola - Sistema de Gestão de Infraestruturas</p>
                <p>República de Angola - Ministério da Indústria</p>
                <p>Relatório gerado automaticamente pelo sistema</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Converte HTML para PDF usando wkhtmltopdf ou biblioteca similar
     * Versão melhorada com compatibilidade Windows e segurança
     */
    private function convertToPDF($html, $filename) {
        // Criar diretório de relatórios se não existir
        $reportsDir = __DIR__ . '/reports';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }
        
        // Sanitizar nome do arquivo
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        $pdfPath = $reportsDir . '/' . $filename;
        
        // Salvar HTML temporário
        $tempHtml = tempnam(sys_get_temp_dir(), 'pdi_report_') . '.html';
        file_put_contents($tempHtml, $html);
        
        try {
            // Detectar sistema operacional e verificar wkhtmltopdf
            $wkhtmltopdfPath = $this->findWkhtmltopdf();
            
            if ($wkhtmltopdfPath) {
                // Usar escapeshellarg para segurança
                $command = sprintf(
                    '%s --page-size A4 --orientation Portrait --margin-top 20mm --margin-bottom 20mm --margin-left 15mm --margin-right 15mm %s %s',
                    escapeshellarg($wkhtmltopdfPath),
                    escapeshellarg($tempHtml),
                    escapeshellarg($pdfPath)
                );
                
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0 && file_exists($pdfPath)) {
                    unlink($tempHtml);
                    $this->logger->info("PDF gerado com sucesso", ['file' => $filename]);
                    return $pdfPath;
                }
                
                $this->logger->warning("Falha ao gerar PDF com wkhtmltopdf", [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output)
                ]);
            }
            
            // Fallback: salvar como HTML
            $htmlPath = str_replace('.pdf', '.html', $pdfPath);
            copy($tempHtml, $htmlPath);
            unlink($tempHtml);
            
            $this->logger->info("Relatório salvo como HTML (fallback)", ['file' => basename($htmlPath)]);
            return $htmlPath;
            
        } catch (Exception $e) {
            if (file_exists($tempHtml)) {
                unlink($tempHtml);
            }
            $this->logger->error("Erro ao gerar PDF", ['error' => $e->getMessage()]);
            throw new Exception("Erro ao gerar PDF: " . $e->getMessage());
        }
    }
    
    /**
     * Encontra o executável wkhtmltopdf baseado no sistema operacional
     */
    private function findWkhtmltopdf() {
        // Caminhos comuns para wkhtmltopdf
        $commonPaths = [
            '/usr/local/bin/wkhtmltopdf',
            '/usr/bin/wkhtmltopdf',
            'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            'C:\\wkhtmltopdf\\bin\\wkhtmltopdf.exe'
        ];
        
        // Verificar caminhos comuns primeiro
        foreach ($commonPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Usar comando do sistema para encontrar
        if (PHP_OS_FAMILY === 'Windows') {
            $result = shell_exec('where wkhtmltopdf 2>nul');
        } else {
            $result = shell_exec('which wkhtmltopdf 2>/dev/null');
        }
        
        if ($result) {
            $path = trim($result);
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Lista relatórios disponíveis
     */
    public function listReports() {
        $reportsDir = __DIR__ . '/reports';
        if (!is_dir($reportsDir)) {
            return [];
        }
        
        $reports = [];
        $files = scandir($reportsDir);
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && (strpos($file, '.pdf') !== false || strpos($file, '.html') !== false)) {
                $filePath = $reportsDir . '/' . $file;
                $reports[] = [
                    'filename' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'created_at' => date('Y-m-d H:i:s', filemtime($filePath))
                ];
            }
        }
        
        // Ordenar por data de criação (mais recente primeiro)
        usort($reports, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $reports;
    }
    
    /**
     * Remove relatórios antigos
     */
    public function cleanOldReports($daysOld = 30) {
        $reportsDir = __DIR__ . '/reports';
        if (!is_dir($reportsDir)) {
            return 0;
        }
        
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $removed = 0;
        
        $files = scandir($reportsDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $reportsDir . '/' . $file;
                if (filemtime($filePath) < $cutoffTime) {
                    unlink($filePath);
                    $removed++;
                }
            }
        }
        
        $this->logger->info("Cleaned old reports", [
            'removed_count' => $removed,
            'days_old' => $daysOld
        ]);
        
        return $removed;
    }
}
?>

