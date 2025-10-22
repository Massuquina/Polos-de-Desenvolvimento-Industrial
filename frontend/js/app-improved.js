/**
 * Aplicação principal melhorada - PDI Angola
 * Versão com melhorias de UX, validação e componentes reutilizáveis
 */

class PDIApp {
    constructor() {
        this.currentUser = null;
        this.currentSection = 'dashboard';
        // Usar configuração centralizada
        this.apiBase = window.AppConfig ? window.AppConfig.getApiUrl() : '/pdi_angola/backend/api_improved.php';
        this.isLoading = false;
        this.cache = new Map();
        this.validators = new Map();
        
        this.init();
    }

    /**
     * Inicializa a aplicação
     */
    async init() {
        try {
            UIComponents.showGlobalLoader('Inicializando aplicação...');
            
            // Verificar autenticação
            await this.checkAuth();
            
            // Configurar event listeners
            this.setupEventListeners();
            
            // Configurar validadores de formulário
            this.setupFormValidators();
            
            // Carregar seção inicial
            await this.loadSection(this.currentSection);
            
            // Configurar atualizações automáticas
            this.setupAutoRefresh();
            
            UIComponents.hideGlobalLoader();
            UIComponents.showToast('Aplicação carregada com sucesso!', 'success');
            
        } catch (error) {
            console.error('Erro ao inicializar aplicação:', error);
            UIComponents.hideGlobalLoader();
            UIComponents.showToast('Erro ao carregar aplicação', 'error');
        }
    }

    /**
     * Verifica autenticação do usuário
     */
    async checkAuth() {
        try {
            const response = await this.apiRequest('GET', '/auth/check');
            
            if (response.user) {
                this.currentUser = response.user;
                this.updateUserInterface();
                return true;
            } else {
                this.showLoginModal();
                return false;
            }
        } catch (error) {
            console.error('Erro ao verificar autenticação:', error);
            this.showLoginModal();
            return false;
        }
    }

    /**
     * Atualiza interface do usuário logado
     */
    updateUserInterface() {
        const userNameElement = document.getElementById('userName');
        if (userNameElement && this.currentUser) {
            userNameElement.textContent = this.currentUser.nome || 'Usuário';
        }

        // Mostrar/ocultar elementos baseado no nível de acesso
        this.updateUIBasedOnPermissions();
    }

    /**
     * Atualiza UI baseado nas permissões do usuário
     */
    updateUIBasedOnPermissions() {
        if (!this.currentUser) return;

        const userLevel = this.currentUser.nivel_nome;
        
        // Elementos que requerem permissões específicas
        const adminElements = document.querySelectorAll('[data-permission="admin"]');
        const managerElements = document.querySelectorAll('[data-permission="manager"]');
        
        adminElements.forEach(element => {
            element.style.display = userLevel === 'Administrador' ? 'block' : 'none';
        });
        
        managerElements.forEach(element => {
            element.style.display = ['Administrador', 'Gestor'].includes(userLevel) ? 'block' : 'none';
        });
    }

    /**
     * Mostra modal de login
     */
    showLoginModal() {
        const loginForm = `
            <form id="loginForm" data-validate>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="rememberMe" name="rememberMe">
                        Lembrar-me
                    </label>
                </div>
            </form>
        `;

        const modal = UIComponents.createModal('Login - PDI Angola', loginForm, {
            size: 'small',
            closable: false,
            backdrop: false,
            buttons: [
                {
                    text: 'Entrar',
                    class: 'btn-primary',
                    action: 'login',
                    handler: () => this.handleLogin(modal)
                }
            ]
        });

        // Configurar validação do formulário
        const form = modal.querySelector('#loginForm');
        const validator = new PDIFormValidator(form);
        this.validators.set('login', validator);
    }

    /**
     * Processa login do usuário
     */
    async handleLogin(modal) {
        const validator = this.validators.get('login');
        
        if (!validator.isFormValid()) {
            UIComponents.showToast('Por favor, corrija os erros no formulário', 'warning');
            return;
        }

        const formData = validator.getFormData();
        
        try {
            UIComponents.showGlobalLoader('Fazendo login...');
            
            const response = await this.apiRequest('POST', '/auth/login', {
                email: formData.email,
                password: formData.password
            });

            if (response.user) {
                this.currentUser = response.user;
                
                // Salvar preferência de lembrar
                if (formData.rememberMe) {
                    Utils.saveToLocalStorage('rememberUser', true);
                }
                
                UIComponents.hideModal(modal);
                this.updateUserInterface();
                await this.loadSection('dashboard');
                
                UIComponents.showToast(`Bem-vindo, ${response.user.nome}!`, 'success');
            }
            
        } catch (error) {
            console.error('Erro no login:', error);
            UIComponents.showToast(error.message || 'Erro ao fazer login', 'error');
        } finally {
            UIComponents.hideGlobalLoader();
        }
    }

    /**
     * Configura event listeners
     */
    setupEventListeners() {
        // Navegação
        document.addEventListener('click', (e) => {
            const navLink = e.target.closest('.nav-link');
            if (navLink) {
                e.preventDefault();
                const section = navLink.getAttribute('href').substring(1);
                this.loadSection(section);
            }
        });

        // Logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        }

        // Menu mobile
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const nav = document.getElementById('nav');
        
        if (mobileMenuBtn && nav) {
            mobileMenuBtn.addEventListener('click', () => {
                nav.classList.toggle('active');
            });
        }

        // User dropdown
        const userBtn = document.getElementById('userBtn');
        const userDropdown = document.getElementById('userDropdown');
        
        if (userBtn && userDropdown) {
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });
            
            document.addEventListener('click', () => {
                userDropdown.classList.remove('show');
            });
        }

        // Teclas de atalho
        document.addEventListener('keydown', (e) => {
            // Ctrl + K para pesquisa rápida
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                this.showQuickSearch();
            }
            
            // ESC para fechar modais
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => UIComponents.hideModal(modal));
            }
        });

        // Detectar mudanças de conectividade
        window.addEventListener('online', () => {
            UIComponents.showToast('Conexão restaurada', 'success');
            this.refreshCurrentSection();
        });

        window.addEventListener('offline', () => {
            UIComponents.showToast('Conexão perdida. Trabalhando offline.', 'warning', 0);
        });
    }

    /**
     * Configura validadores de formulário
     */
    setupFormValidators() {
        // Auto-configurar formulários com data-validate
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            const validator = new PDIFormValidator(form);
            this.validators.set(form.id || Utils.generateId(), validator);
        });
    }

    /**
     * Configura atualização automática
     */
    setupAutoRefresh() {
        // Atualizar dashboard a cada 5 minutos
        setInterval(() => {
            if (this.currentSection === 'dashboard' && navigator.onLine) {
                this.refreshDashboardStats();
            }
        }, 5 * 60 * 1000);

        // Limpar cache a cada 30 minutos
        setInterval(() => {
            this.clearCache();
        }, 30 * 60 * 1000);
    }

    /**
     * Carrega uma seção da aplicação
     */
    async loadSection(sectionName) {
        if (this.isLoading) return;
        
        try {
            this.isLoading = true;
            this.currentSection = sectionName;
            
            // Atualizar navegação ativa
            this.updateActiveNavigation(sectionName);
            
            // Mostrar loading
            this.showSectionLoader();
            
            // Carregar conteúdo da seção
            switch (sectionName) {
                case 'dashboard':
                    await this.loadDashboard();
                    break;
                case 'infraestruturas':
                    await this.loadInfraestruturas();
                    break;
                case 'lotes':
                    await this.loadLotes();
                    break;
                case 'empresas':
                    await this.loadEmpresas();
                    break;
                case 'polos':
                    await this.loadPolos();
                    break;
                case 'mapa':
                    await this.loadMapa();
                    break;
                case 'relatorios':
                    await this.loadRelatorios();
                    break;
                default:
                    throw new Error(`Seção não encontrada: ${sectionName}`);
            }
            
            // Ocultar loading
            this.hideSectionLoader();
            
            // Atualizar URL sem recarregar
            Utils.updateURL({ section: sectionName }, true);
            
        } catch (error) {
            console.error(`Erro ao carregar seção ${sectionName}:`, error);
            this.hideSectionLoader();
            UIComponents.showToast(`Erro ao carregar ${sectionName}`, 'error');
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Atualiza navegação ativa
     */
    updateActiveNavigation(sectionName) {
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${sectionName}`) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Mostra loader da seção
     */
    showSectionLoader() {
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            const loader = UIComponents.createSkeleton('text', 5);
            loader.id = 'sectionLoader';
            mainContent.innerHTML = '';
            mainContent.appendChild(loader);
        }
    }

    /**
     * Oculta loader da seção
     */
    hideSectionLoader() {
        const loader = document.getElementById('sectionLoader');
        if (loader) {
            loader.remove();
        }
    }

    /**
     * Carrega dashboard
     */
    async loadDashboard() {
        const stats = await this.getFromCacheOrFetch('dashboard-stats', () => 
            this.apiRequest('GET', '/stats/dashboard')
        );

        const dashboardHTML = this.generateDashboardHTML(stats);
        this.updateMainContent(dashboardHTML);
        
        // Animar contadores
        this.animateDashboardCounters();
        
        // Carregar gráficos
        await this.loadDashboardCharts();
    }

    /**
     * Gera HTML do dashboard
     */
    generateDashboardHTML(stats) {
        return `
            <div class="dashboard">
                <div class="dashboard-header">
                    <h2>Dashboard</h2>
                    <div class="dashboard-actions">
                        <button class="btn btn-primary" onclick="app.refreshDashboardStats()">
                            <i class="fas fa-sync-alt"></i> Atualizar
                        </button>
                        <button class="btn btn-secondary" onclick="app.exportDashboard()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" data-target="${stats.infraestruturas?.total || 0}">0</h3>
                            <p class="stat-label">Infraestruturas</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" data-target="${stats.lotes?.total || 0}">0</h3>
                            <p class="stat-label">Lotes Totais</p>
                            <small class="stat-detail">${stats.lotes?.ocupados || 0} ocupados</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-industry"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" data-target="${stats.empresas?.total || 0}">0</h3>
                            <p class="stat-label">Empresas</p>
                            <small class="stat-detail">${stats.empresas?.ativas || 0} ativas</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-map-pin"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" data-target="${stats.polos?.total || 0}">0</h3>
                            <p class="stat-label">Polos de Desenvolvimento</p>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-charts">
                    <div class="chart-container">
                        <h3>Distribuição por Província</h3>
                        <canvas id="provinciaChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3>Evolução Mensal</h3>
                        <canvas id="evolucaoChart"></canvas>
                    </div>
                </div>
                
                <div class="recent-activities">
                    <h3>Atividades Recentes</h3>
                    <div id="recentActivitiesList">
                        ${UIComponents.createSkeleton('text', 3).outerHTML}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Anima contadores do dashboard
     */
    animateDashboardCounters() {
        const counters = document.querySelectorAll('.stat-number[data-target]');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            UIComponents.animateCounter(counter, target);
        });
    }

    /**
     * Carrega infraestruturas
     */
    async loadInfraestruturas() {
        const infraestruturas = await this.getFromCacheOrFetch('infraestruturas', () => 
            this.apiRequest('GET', '/infraestruturas')
        );

        const infraestruturasHTML = this.generateInfraestruturasHTML(infraestruturas);
        this.updateMainContent(infraestruturasHTML);
        
        // Configurar tabela de dados
        this.setupInfraestruturasTable(infraestruturas);
    }

    /**
     * Gera HTML das infraestruturas
     */
    generateInfraestruturasHTML(infraestruturas) {
        return `
            <div class="infraestruturas">
                <div class="section-header">
                    <h2>Infraestruturas</h2>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="app.showAddInfraestruturaModal()">
                            <i class="fas fa-plus"></i> Nova Infraestrutura
                        </button>
                        <button class="btn btn-secondary" onclick="app.exportInfraestruturas()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
                
                <div class="filters-section">
                    <div class="filters-grid">
                        <select id="poloFilter" class="form-control">
                            <option value="">Todos os Polos</option>
                        </select>
                        <select id="provinciaFilter" class="form-control">
                            <option value="">Todas as Províncias</option>
                        </select>
                        <select id="estadoFilter" class="form-control">
                            <option value="">Todos os Estados</option>
                            <option value="planejado">Planejado</option>
                            <option value="em_construcao">Em Construção</option>
                            <option value="concluido">Concluído</option>
                            <option value="operacional">Operacional</option>
                        </select>
                        <button class="btn btn-secondary" onclick="app.clearFilters()">
                            <i class="fas fa-times"></i> Limpar
                        </button>
                    </div>
                </div>
                
                <div id="infraestruturasTable" class="table-container">
                    <!-- Tabela será gerada dinamicamente -->
                </div>
            </div>
        `;
    }

    /**
     * Configura tabela de infraestruturas
     */
    setupInfraestruturasTable(data) {
        const container = document.getElementById('infraestruturasTable');
        
        const columns = [
            { key: 'polo_nome', title: 'Polo', sortable: true },
            { key: 'provincia_nome', title: 'Província', sortable: true },
            { key: 'municipio_nome', title: 'Município', sortable: true },
            { key: 'estado_atual', title: 'Estado', sortable: true, render: (value) => 
                `<span class="badge badge-${this.getEstadoBadgeClass(value)}">${Utils.capitalizeWords(value)}</span>`
            },
            { key: 'area', title: 'Área (ha)', sortable: true, render: (value) => 
                value ? Utils.formatNumber(value, 2) : '-'
            },
            { key: 'created_at', title: 'Criado em', sortable: true, render: (value) => 
                Utils.formatDate(value)
            }
        ];

        const actions = [
            {
                icon: 'fas fa-eye',
                class: 'btn-info',
                title: 'Visualizar',
                handler: (item) => this.showInfraestruturaDetails(item.id)
            },
            {
                icon: 'fas fa-edit',
                class: 'btn-warning',
                title: 'Editar',
                handler: (item) => this.showEditInfraestruturaModal(item.id)
            },
            {
                icon: 'fas fa-trash',
                class: 'btn-danger',
                title: 'Excluir',
                handler: (item) => this.deleteInfraestrutura(item.id)
            }
        ];

        this.infraestruturasDataTable = UIComponents.createDataTable(
            container, 
            data, 
            columns, 
            { 
                pagination: true, 
                pageSize: 15, 
                search: true, 
                actions: actions 
            }
        );
    }

    /**
     * Obtém classe do badge baseado no estado
     */
    getEstadoBadgeClass(estado) {
        const classes = {
            'planejado': 'info',
            'em_construcao': 'warning',
            'concluido': 'success',
            'operacional': 'primary'
        };
        return classes[estado] || 'secondary';
    }

    /**
     * Faz requisição para API com tratamento de erro
     */
    async apiRequest(method, endpoint, data = null) {
        try {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(this.apiBase + endpoint, options);
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `Erro HTTP: ${response.status}`);
            }

            return await response.json();
            
        } catch (error) {
            console.error('Erro na requisição API:', error);
            
            // Se estiver offline, tentar usar cache
            if (!navigator.onLine && method === 'GET') {
                const cached = this.cache.get(endpoint);
                if (cached) {
                    UIComponents.showToast('Dados do cache (offline)', 'info');
                    return cached;
                }
            }
            
            throw error;
        }
    }

    /**
     * Obtém dados do cache ou faz fetch
     */
    async getFromCacheOrFetch(key, fetchFunction, maxAge = 5 * 60 * 1000) {
        const cached = this.cache.get(key);
        
        if (cached && (Date.now() - cached.timestamp) < maxAge) {
            return cached.data;
        }
        
        try {
            const data = await fetchFunction();
            this.cache.set(key, {
                data: data,
                timestamp: Date.now()
            });
            return data;
        } catch (error) {
            // Se falhar e tiver cache, usar cache
            if (cached) {
                UIComponents.showToast('Usando dados em cache', 'warning');
                return cached.data;
            }
            throw error;
        }
    }

    /**
     * Limpa cache
     */
    clearCache() {
        this.cache.clear();
        console.log('Cache limpo');
    }

    /**
     * Atualiza conteúdo principal
     */
    updateMainContent(html) {
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.innerHTML = html;
        }
    }

    /**
     * Processa logout
     */
    async handleLogout() {
        const confirmed = await UIComponents.showConfirmModal(
            'Tem certeza que deseja sair?',
            { type: 'warning' }
        );

        if (confirmed) {
            try {
                await this.apiRequest('POST', '/auth/logout');
                this.currentUser = null;
                Utils.removeFromLocalStorage('rememberUser');
                this.showLoginModal();
                UIComponents.showToast('Logout realizado com sucesso', 'success');
            } catch (error) {
                console.error('Erro no logout:', error);
                UIComponents.showToast('Erro ao fazer logout', 'error');
            }
        }
    }

    /**
     * Atualiza seção atual
     */
    async refreshCurrentSection() {
        this.clearCache();
        await this.loadSection(this.currentSection);
    }

    /**
     * Atualiza estatísticas do dashboard
     */
    async refreshDashboardStats() {
        this.cache.delete('dashboard-stats');
        if (this.currentSection === 'dashboard') {
            await this.loadDashboard();
        }
    }

    /**
     * Mostra pesquisa rápida
     */
    showQuickSearch() {
        const searchHTML = `
            <div class="quick-search">
                <input type="text" id="quickSearchInput" class="form-control" placeholder="Pesquisar infraestruturas, empresas, lotes..." autofocus>
                <div id="quickSearchResults" class="search-results"></div>
            </div>
        `;

        const modal = UIComponents.createModal('Pesquisa Rápida', searchHTML, {
            size: 'medium',
            buttons: []
        });

        const searchInput = modal.querySelector('#quickSearchInput');
        const resultsContainer = modal.querySelector('#quickSearchResults');

        // Debounce da pesquisa
        const debouncedSearch = Utils.debounce(async (query) => {
            if (query.length >= 2) {
                try {
                    const results = await this.apiRequest('GET', `/search?q=${encodeURIComponent(query)}`);
                    this.displaySearchResults(resultsContainer, results);
                } catch (error) {
                    console.error('Erro na pesquisa:', error);
                }
            } else {
                resultsContainer.innerHTML = '';
            }
        }, 300);

        searchInput.addEventListener('input', (e) => {
            debouncedSearch(e.target.value);
        });
    }

    /**
     * Exibe resultados da pesquisa
     */
    displaySearchResults(container, results) {
        if (!results || results.length === 0) {
            container.innerHTML = '<p class="no-results">Nenhum resultado encontrado</p>';
            return;
        }

        const html = results.map(result => `
            <div class="search-result-item" onclick="app.openSearchResult('${result.type}', ${result.id})">
                <div class="result-icon">
                    <i class="fas fa-${this.getSearchResultIcon(result.type)}"></i>
                </div>
                <div class="result-content">
                    <h4>${result.title}</h4>
                    <p>${result.description}</p>
                    <small class="result-type">${Utils.capitalizeWords(result.type)}</small>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    /**
     * Obtém ícone para resultado de pesquisa
     */
    getSearchResultIcon(type) {
        const icons = {
            'infraestrutura': 'building',
            'empresa': 'industry',
            'lote': 'map-marked-alt',
            'polo': 'map-pin'
        };
        return icons[type] || 'search';
    }

    /**
     * Abre resultado da pesquisa
     */
    async openSearchResult(type, id) {
        // Fechar modal de pesquisa
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => UIComponents.hideModal(modal));

        // Navegar para a seção apropriada e mostrar detalhes
        switch (type) {
            case 'infraestrutura':
                await this.loadSection('infraestruturas');
                this.showInfraestruturaDetails(id);
                break;
            case 'empresa':
                await this.loadSection('empresas');
                this.showEmpresaDetails(id);
                break;
            case 'lote':
                await this.loadSection('lotes');
                this.showLoteDetails(id);
                break;
            case 'polo':
                await this.loadSection('polos');
                this.showPoloDetails(id);
                break;
        }
    }

    // Métodos placeholder para outras funcionalidades
    async loadLotes() { /* Implementar */ }
    async loadEmpresas() { /* Implementar */ }
    async loadPolos() { /* Implementar */ }
    async loadMapa() { /* Implementar */ }
    async loadRelatorios() { /* Implementar */ }
    async loadDashboardCharts() { /* Implementar */ }
    showInfraestruturaDetails(id) { /* Implementar */ }
    showEditInfraestruturaModal(id) { /* Implementar */ }
    showAddInfraestruturaModal() { /* Implementar */ }
    deleteInfraestrutura(id) { /* Implementar */ }
    exportInfraestruturas() { /* Implementar */ }
    exportDashboard() { /* Implementar */ }
    clearFilters() { /* Implementar */ }
}

// Inicializar aplicação quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.app = new PDIApp();
});

// Disponibilizar globalmente para compatibilidade
window.PDIApp = PDIApp;

