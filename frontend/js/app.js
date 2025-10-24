/**
 * PDI Angola - Aplicação Principal
 * Sistema de Gestão de Infraestruturas
 */

class PDIApp {
    constructor() {
        // Usar configuração centralizada
        this.apiBase = window.AppConfig ? window.AppConfig.getLegacyApiUrl() : 'http://localhost/pdi_angola/backend/api.php';
        this.apiImproved = window.AppConfig ? window.AppConfig.getApiUrl() : 'http://localhost/pdi_angola/backend/api_improved.php';
        this.currentUser = null;
        this.currentSection = 'dashboard';
        
        // Instâncias dos managers
        this.infraestruturasManager = null;
        this.lotesManager = null;
        this.empresasManager = null;
        this.dashboardManager = null;
        this.mapaManager = null;

        this.init();
    }

    /**
     * Faz requisições para a API melhorada
     */
    async apiRequestImproved(endpoint, options = {}) {
        let url = `${this.apiImproved}/${endpoint}`;
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        };

        // Merge options without using object spread for broader compatibility
        const finalOptions = Object.assign({}, defaultOptions, options || {});
        finalOptions.headers = Object.assign({}, defaultOptions.headers, (options && options.headers) ? options.headers : {});

        // Append params to query string if provided
        if (options && options.params) {
            try {
                const qs = new URLSearchParams(options.params).toString();
                if (qs) {
                    url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
                }
            } catch (e) {
                console.warn('Failed to build query string from params:', e);
            }
            // Ensure params are not passed to fetch options
            delete finalOptions.params;
        }

        try {
            const response = await fetch(url, finalOptions);
            var ct = null;
            try { ct = response.headers ? response.headers.get('content-type') : null; } catch (e) { ct = null; }

            var rawText = '';
            try { rawText = await response.clone().text(); } catch (e) { rawText = ''; }

            var looksJson = false;
            if (ct && ct.indexOf('application/json') !== -1) {
                looksJson = true;
            } else if (rawText) {
                // Heuristic: starts with { or [ after optional whitespace
                looksJson = /^[\s]*[\{\[]/.test(rawText);
            }

            if (!response.ok) {
                var errorData = {};
                if (looksJson) {
                    try { errorData = JSON.parse(rawText); } catch (e) { errorData = {}; }
                }
                throw new Error((errorData && errorData.error) ? errorData.error : ('HTTP ' + response.status));
            }

            if (looksJson) {
                try { return JSON.parse(rawText); } catch (e) { return {}; }
            }
            return {};
        } catch (err) {
            // Network errors or JSON parse issues
            console.error('API request failed:', err);
            throw err;
        }
    }

    /**
     * Faz requisições para a API melhorada
     */
    async apiRequestImproved(endpoint, options = {}) {
        let url = `${this.apiImproved}/${endpoint}`;
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        };

        // Merge options without using object spread for broader compatibility
        const finalOptions = Object.assign({}, defaultOptions, options || {});
        finalOptions.headers = Object.assign({}, defaultOptions.headers, (options && options.headers) ? options.headers : {});

        // Append params to query string if provided
        if (options && options.params) {
            try {
                const qs = new URLSearchParams(options.params).toString();
                if (qs) {
                    url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
                }
            } catch (e) {
                console.warn('Failed to build query string from params:', e);
            }
            // Ensure params are not passed to fetch options
            delete finalOptions.params;
        }

        try {
            const response = await fetch(url, finalOptions);
            var ct = null;
            try { ct = response.headers ? response.headers.get('content-type') : null; } catch (e) { ct = null; }

            var rawText = '';
            try { rawText = await response.clone().text(); } catch (e) { rawText = ''; }

            var looksJson = false;
            if (ct && ct.indexOf('application/json') !== -1) {
                looksJson = true;
            } else if (rawText) {
                // Heuristic: starts with { or [ after optional whitespace
                looksJson = /^[\s]*[\{\[]/.test(rawText);
            }

            if (!response.ok) {
                var errorData = {};
                if (looksJson) {
                    try { errorData = JSON.parse(rawText); } catch (e) { errorData = {}; }
                }
                throw new Error((errorData && errorData.error) ? errorData.error : ('HTTP ' + response.status));
            }

            if (looksJson) {
                try { return JSON.parse(rawText); } catch (e) { return {}; }
            }
            return {};
        } catch (err) {
            // Network errors or JSON parse issues
            console.error('API request failed:', err);
            throw err;
        }
    }

    /**
     * Inicializa a aplicação
     */
    init() {
        this.setupEventListeners();
        this.checkAuthentication();
        // Os managers serão inicializados após a autenticação e carregamento de dados
    }

    /**
     * Configura os event listeners
     */
    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.getAttribute('href').substring(1);
                this.showSection(section);
            });
        });

        // Mobile menu
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const nav = document.getElementById('nav');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                nav.classList.toggle('show');
            });
        }

        // User menu
        const userBtn = document.getElementById('userBtn');
        const userDropdown = document.getElementById('userDropdown');
        
        if (userBtn) {
            userBtn.addEventListener('click', () => {
                userDropdown.classList.toggle('show');
            });
        }

        // Close user dropdown when clicking outside (compat without optional chaining)
        document.addEventListener('click', function(e) {
            if (!userBtn || !userBtn.contains(e.target)) {
                if (userDropdown) {
                    userDropdown.classList.remove('show');
                }
            }
        });

        // Logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        }

        // Login modal
        const loginModal = document.getElementById('loginModal');
        const closeLoginModal = document.getElementById('closeLoginModal');
        const loginForm = document.getElementById('loginForm');

        if (closeLoginModal) {
            closeLoginModal.addEventListener('click', () => {
                this.hideModal('loginModal');
            });
        }

        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin(e);
            });
        }

        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.hideModal(e.target.id);
            }
        });
    }

    /**
     * Verifica se o usuário está autenticado
     */
    async checkAuthentication() {
        try {
            const response = await this.apiRequest('auth/check', { timeoutMs: 5000 });
            
            if (response.user) {
                this.currentUser = response.user;
                this.updateUserInterface();
                this.initializeManagers();
                this.loadInitialData();
            } else {
                this.showLoginModal();
            }
        } catch (error) {
            console.log('Usuário não autenticado ou timeout na verificação:', error && error.message ? error.message : error);
            this.showLoginModal();
        }
    }

    /**
     * Inicializa os managers após a autenticação
     */
    initializeManagers() {
        try {
            if (!this.infraestruturasManager && typeof InfraestruturasManager !== 'undefined') {
                this.infraestruturasManager = new InfraestruturasManager(this);
            }
            if (!this.lotesManager && typeof LotesManager !== 'undefined') {
                this.lotesManager = (typeof window !== 'undefined' && window.lotesManager) ? window.lotesManager : new LotesManager(this);
            }
            if (!this.empresasManager && typeof EmpresasManager !== 'undefined') {
                this.empresasManager = (typeof window !== 'undefined' && window.empresasManager) ? window.empresasManager : new EmpresasManager(this);
            }
            if (!this.dashboardManager && typeof DashboardManager !== 'undefined') {
                this.dashboardManager = new DashboardManager(this);
            }
            if (!this.mapaManager && typeof MapaManager !== 'undefined') {
                this.mapaManager = new MapaManager(this);
            }
        } catch (e) {
            console.error('Erro ao inicializar managers:', e);
        }
    }

    /**
     * Atualiza a interface do usuário
     */
    updateUserInterface() {
        const userName = document.getElementById('userName');
        if (userName && this.currentUser) {
            userName.textContent = this.currentUser.nome;
        }

        // Mostrar/ocultar elementos baseado nas permissões
        this.updatePermissions();
    }

    /**
     * Atualiza elementos baseado nas permissões do usuário
     */
    updatePermissions() {
        if (!this.currentUser || !this.currentUser.permissoes) return;

        const permissions = this.currentUser.permissoes;

        // Botão de adicionar infraestrutura
        const addInfraBtn = document.getElementById('addInfraestruturaBtn');
        if (addInfraBtn) {
            addInfraBtn.style.display = permissions.create ? 'inline-flex' : 'none';
        }

        // Botão de adicionar lote
        const addLoteBtn = document.getElementById('addLoteBtn');
        if (addLoteBtn) {
            addLoteBtn.style.display = permissions.manage_lotes ? 'inline-flex' : 'none';
        }

        // Botão de adicionar empresa
        const addEmpresaBtn = document.getElementById('addEmpresaBtn');
        if (addEmpresaBtn) {
            addEmpresaBtn.style.display = permissions.manage_empresas ? 'inline-flex' : 'none';
        }

        // Outros elementos baseados em permissões podem ser adicionados aqui
    }

    /**
     * Mostra o modal de login
     */
    showLoginModal() {
        // Verificar se já existe um modal de login e removê-lo
        const existingModal = document.getElementById('loginModal');
        if (existingModal) {
            existingModal.parentNode.removeChild(existingModal);
        }
        
        // Obter o conteúdo do formulário de login do HTML
        const loginForm = document.getElementById('loginFormTemplate');
        const loginContent = loginForm ? loginForm.innerHTML : `
            <form id="loginForm" class="login-form" data-validate>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required minlength="6" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i>
                        Entrar
                    </button>
                </div>
            </form>
        `;
        
        // Criar o modal usando UIComponents
        const modal = document.createElement('div');
        modal.id = 'loginModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Iniciar Sessão</h2>
                    <button class="modal-close" id="closeLoginModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    ${loginContent}
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Configurar event listeners
        const closeBtn = modal.querySelector('#closeLoginModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideModal('loginModal'));
        }
        
        const form = modal.querySelector('#loginForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin(e);
            });
            form.reset(); // Limpar campos do formulário
            
            // Focar no campo de email para facilitar o login
            const emailInput = form.querySelector('#email');
            if (emailInput) {
                setTimeout(() => emailInput.focus(), 100);
            }
        }
        
        // Mostrar o modal
        modal.classList.add('show');
    }

    /**
     * Manipula o login
     */
    async handleLogin(e) {
        const formData = new FormData(e.target);
        const email = formData.get('email');
        const password = formData.get('password');

        // Remover mensagem de erro anterior, se existir
        const loginModal = document.getElementById('loginModal');
        const previousError = loginModal.querySelector('.error-message');
        if (previousError) {
            previousError.remove();
        }
        
        this.showLoading();

        try {
            // Garantir que o token CSRF está atualizado antes de fazer login
            if (window.refreshCsrfToken && typeof window.refreshCsrfToken === 'function') {
                try { await window.refreshCsrfToken(); } catch (e) { /* ignore */ }
            }

            const response = await this.apiRequest('auth/login', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });

            if (response.user) {
                this.currentUser = response.user;
                this.hideModal('loginModal');
                this.updateUserInterface();
                this.initializeManagers();
                this.loadInitialData();
                this.showNotification('Login realizado com sucesso!', 'success');
            }
        } catch (error) {
            // Mostrar mensagem de erro no modal
            const form = loginModal.querySelector('#loginForm');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Credenciais inválidas. Por favor, verifique seu email e senha.';
            errorDiv.style.color = 'var(--danger-color)';
            errorDiv.style.padding = 'var(--spacing-sm) 0';
            errorDiv.style.marginBottom = 'var(--spacing-md)';
            errorDiv.style.fontWeight = 'bold';
            errorDiv.style.textAlign = 'center';
            
            // Inserir a mensagem de erro antes do formulário
            form.parentNode.insertBefore(errorDiv, form);
            
            // Também mostrar notificação
            this.showNotification('Credenciais inválidas', 'error');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Faz logout
     */
    async logout() {
        try {
            await this.apiRequest('auth/logout', { method: 'POST' });
        } catch (error) {
            console.error('Erro no logout:', error);
        } finally {
            // Atualizar o token CSRF após destruir a sessão
            if (window.refreshCsrfToken && typeof window.refreshCsrfToken === 'function') {
                try { await window.refreshCsrfToken(); } catch (e) { /* ignore */ }
            }

            this.currentUser = null;
            this.showLoginModal();
        }
    }

    /**
     * Carrega dados iniciais
     */
    async loadInitialData() {
        if (!this.currentUser) return;

        try {
            // Carregar dados para o dashboard
            await this.loadDashboardData();
            
            // Carregar dados para filtros
            await this.loadFilterData();

            // Carregar dados para as seções ativas
            this.loadSectionData(this.currentSection);

        } catch (error) {
            console.error('Erro ao carregar dados iniciais:', error);
        }
    }

    /**
     * Carrega dados do dashboard
     */
    async loadDashboardData() {
        try {
            // Carregar estatísticas do dashboard usando API melhorada
            const stats = await this.apiRequestImproved('stats/dashboard');
            
            // Carregar dados completos para gráficos usando API legada
            const [infraestruturas, empresas, sectores] = await Promise.all([
                this.apiRequest('infraestruturas'),
                this.apiRequest('empresas'),
                this.apiRequest('sectores')
            ]);

            // Atualizar estatísticas
            if (this.dashboardManager) {
                this.dashboardManager.updateStats(stats);
            }
            
            // Atualizar gráficos
            if (this.dashboardManager) {
                this.dashboardManager.updateCharts(infraestruturas, empresas, sectores);
            }
        } catch (error) {
            console.error('Erro ao carregar dados do dashboard:', error);
        }
    }

    /**
     * Carrega dados para filtros
     */
    async loadFilterData() {
        try {
            const [provincias, polos, sectores] = await Promise.all([
                this.apiRequest('provincias'),
                this.apiRequest('polos'),
                this.apiRequest('sectores')
            ]);

            // Filtros de Infraestruturas
            this.populateSelect('filterProvincia', provincias, 'id', 'nome');
            this.populateSelect('filterPolo', polos, 'id', 'nome');
            this.populateSelect('filterSector', sectores, 'id', 'nome');

            // Filtros de Lotes
            this.populateSelect('filterLotePolo', polos, 'id', 'nome');

            // Filtros de Empresas
            this.populateSelect('filterEmpresaPolo', polos, 'id', 'nome');
            this.populateSelect('filterEmpresaSector', sectores, 'id', 'nome');

        } catch (error) {
            console.error('Erro ao carregar dados dos filtros:', error);
        }
    }

    /**
     * Popula um select com dados
     */
    populateSelect(selectId, data, valueField, textField) {
        const select = document.getElementById(selectId);
        if (!select || !data) return;

        // Limpar opções existentes (exceto a primeira)
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

        // Adicionar novas opções
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = item[textField];
            select.appendChild(option);
        });
    }

    /**
     * Mostra uma seção específica
     */
    showSection(sectionId) {
        // Ocultar todas as seções
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });

        // Remover classe active de todos os links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Mostrar seção selecionada
        const section = document.getElementById(sectionId);
        if (section) {
            section.classList.add('active');
        }

        // Adicionar classe active ao link correspondente
        const activeLink = document.querySelector(`[href="#${sectionId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        this.currentSection = sectionId;

        // Carregar dados específicos da seção
        this.loadSectionData(sectionId);
    }

    /**
     * Carrega dados específicos de uma seção
     */
    async loadSectionData(sectionId) {
        switch (sectionId) {
            case 'dashboard':
                if (this.dashboardManager) {
                    await this.loadDashboardData();
                }
                break;
            case 'infraestruturas':
                if (this.infraestruturasManager) {
                    await this.infraestruturasManager.loadInfraestruturas();
                }
                break;
            case 'lotes':
                if (this.lotesManager) {
                    await this.lotesManager.loadLotes();
                }
                break;
            case 'empresas':
                if (this.empresasManager) {
                    await this.empresasManager.loadEmpresas();
                }
                break;
            case 'polos':
                await this.loadPolos();
                break;
            case 'mapa':
                if (this.mapaManager) {
                    this.mapaManager.initMap();
                }
                break;
        }
    }

    /**
     * Carrega e exibe os polos
     */
    async loadPolos() {
        try {
            const polos = await this.apiRequest('polos');
            this.displayPolos(polos);
        } catch (error) {
            console.error('Erro ao carregar polos:', error);
        }
    }

    /**
     * Exibe os polos na interface
     */
    displayPolos(polos) {
        const polosGrid = document.getElementById('polosGrid');
        if (!polosGrid) return;

        polosGrid.innerHTML = '';

        polos.forEach(polo => {
            const poloCard = this.createPoloCard(polo);
            polosGrid.appendChild(poloCard);
        });
    }

    /**
     * Cria um card de polo
     */
    createPoloCard(polo) {
        const card = document.createElement('div');
        card.className = 'polo-card';

        const statusClass = `status-${polo.status}`;
        const statusText = this.getStatusText(polo.status);

        // Calcular nível de ocupação
        const nivelOcupacao = polo.total_lotes > 0 
            ? ((polo.lotes_ocupados / polo.total_lotes) * 100).toFixed(1) 
            : 0;

        card.innerHTML = `
            <div class="polo-card-header">
                <h3>${polo.nome}</h3>
                <span class="status-badge ${statusClass}">${statusText}</span>
            </div>
            <div class="polo-card-body">
                <div class="polo-info">
                    <div class="polo-info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${polo.provincia_nome}, ${polo.municipio_nome}</span>
                    </div>
                    ${polo.area_total ? `
                        <div class="polo-info-item">
                            <i class="fas fa-expand-arrows-alt"></i>
                            <span>${polo.area_total} hectares</span>
                        </div>
                    ` : ''}
                    <div class="polo-info-item">
                        <i class="fas fa-th-large"></i>
                        <span>Lotes: ${polo.lotes_ocupados} / ${polo.total_lotes}</span>
                    </div>
                    <div class="polo-info-item">
                        <i class="fas fa-building"></i>
                        <span>Empresas: ${polo.total_empresas}</span>
                    </div>
                    <div class="polo-info-item">
                        <i class="fas fa-chart-pie"></i>
                        <span>Ocupação: ${nivelOcupacao}%</span>
                    </div>
                    ${polo.endereco ? `
                        <div class="polo-info-item">
                            <i class="fas fa-location-dot"></i>
                            <span>${polo.endereco}</span>
                        </div>
                    ` : ''}
                </div>
                ${polo.descricao ? `<p>${polo.descricao}</p>` : ''}
            </div>
        `;

        return card;
    }

    /**
     * Obtém o texto do status
     */
    getStatusText(status) {
        const statusMap = {
            'ativo': 'Ativo',
            'inativo': 'Inativo',
            'em_construcao': 'Em Construção',
            'planejado': 'Planejado'
        };
        return statusMap[status] || status;
    }

    /**
     * Faz uma requisição para a API
     */
    async apiRequest(endpoint, options = {}) {
        let url = `${this.apiBase}/${endpoint}`;
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        };

        // Merge options without using object spread for broader compatibility
        const finalOptions = Object.assign({}, defaultOptions, options || {});
        finalOptions.headers = Object.assign({}, defaultOptions.headers, (options && options.headers) ? options.headers : {});

        // Implementar timeout opcional
        const controller = new AbortController();
        const timeoutMs = (options && typeof options.timeoutMs === 'number') ? options.timeoutMs : 0;
        let timeoutId = null;
        if (timeoutMs && timeoutMs > 0) {
            timeoutId = setTimeout(() => {
                try { controller.abort(); } catch (e) {}
            }, timeoutMs);
        }
        finalOptions.signal = controller.signal;
        // Remover propriedade customizada para não interferir no fetch
        if (finalOptions.timeoutMs) { try { delete finalOptions.timeoutMs; } catch (e) {} }

        // Append params to query string if provided
        if (options && options.params) {
            try {
                const qs = new URLSearchParams(options.params).toString();
                if (qs) {
                    url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
                }
            } catch (e) {
                console.warn('Failed to build query string from params:', e);
            }
            // Ensure params are not passed to fetch options
            delete finalOptions.params;
        }

        try {
            const response = await fetch(url, finalOptions);
            var ct = null;
            try { ct = response.headers ? response.headers.get('content-type') : null; } catch (e) { ct = null; }

            var rawText = '';
            try { rawText = await response.clone().text(); } catch (e) { rawText = ''; }

            var looksJson = false;
            if (ct && ct.indexOf('application/json') !== -1) {
                looksJson = true;
            } else if (rawText) {
                // Heuristic: starts with { or [ after optional whitespace
                looksJson = /^[\s]*[\{\[]/.test(rawText);
            }

            if (!response.ok) {
                var errorData = {};
                if (looksJson) {
                    try { errorData = JSON.parse(rawText); } catch (e) { errorData = {}; }
                }
                throw new Error((errorData && errorData.error) ? errorData.error : ('HTTP ' + response.status));
            }

            if (looksJson) {
                try { return JSON.parse(rawText); } catch (e) { return {}; }
            }
            return {};
        } catch (err) {
            if (err && err.name === 'AbortError') {
                throw new Error('Tempo excedido na requisição');
            }
            // Network errors or JSON parse issues
            console.error('API request failed:', err);
            throw err;
        } finally {
            if (timeoutId) { try { clearTimeout(timeoutId); } catch (e) {} }
        }
    }

    /**
     * Mostra um modal
     */
    showModal(title, content, onSubmit, isView = false) {
        let modal = document.getElementById('dynamicModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'dynamicModal';
            modal.classList.add('modal');
            document.body.appendChild(modal);
        }

        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${title}</h2>
                    <button class="modal-close" id="closeDynamicModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                <div class="modal-footer">
                    ${isView ? '' : '<button class="btn btn-primary" id="submitDynamicModal">Salvar</button>'}
                    <button class="btn btn-secondary" id="cancelDynamicModal">${isView ? 'Fechar' : 'Cancelar'}</button>
                </div>
            </div>
        `;

        modal.classList.add('show');

        document.getElementById('closeDynamicModal').addEventListener('click', () => this.hideModal('dynamicModal'));
        document.getElementById('cancelDynamicModal').addEventListener('click', () => this.hideModal('dynamicModal'));

        if (onSubmit && !isView) {
            document.getElementById('submitDynamicModal').addEventListener('click', onSubmit);
        }
    }

    /**
     * Oculta um modal
     */
    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    }

    /**
     * Mostra loading
     */
    showLoading() {
        const loading = document.getElementById('loading');
        if (loading) {
            loading.classList.add('show');
        }
    }

    /**
     * Oculta loading
     */
    hideLoading() {
        const loading = document.getElementById('loading');
        if (loading) {
            loading.classList.remove('show');
        }
    }

    /**
     * Mostra notificação
     */
    showNotification(message, type = 'info') {
        let notificationContainer = document.getElementById('notificationContainer');
        if (!notificationContainer) {
            const div = document.createElement('div');
            div.id = 'notificationContainer';
            div.style.position = 'fixed';
            div.style.top = '20px';
            div.style.right = '20px';
            div.style.zIndex = '1000';
            document.body.appendChild(div);
            notificationContainer = div;
        }

        const notification = document.createElement('div');
        notification.classList.add('notification', type);
        notification.textContent = message;
        notificationContainer.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('hide');
            notification.addEventListener('transitionend', () => notification.remove());
        }, 3000);
    }
}

// Inicializa a aplicação
document.addEventListener('DOMContentLoaded', () => {
    window.pdiApp = new PDIApp();
    // Alias global para compatibilidade
    window.app = window.pdiApp;
});

// API Wrapper para simplificar chamadas
class APIWrapper {
    constructor(apiBase) {
        this.apiBase = apiBase;
    }

    async request(endpoint, method = 'GET', data = null, params = null) {
        let url = `${this.apiBase}/${endpoint}`;
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

        if (params) {
            const queryString = new URLSearchParams(params).toString();
            url = `${url}?${queryString}`;
        }

        // Ensure headers keep defaults when custom headers are provided
        const mergedHeaders = Object.assign({}, options.headers);
        options.headers = mergedHeaders;
        try {
            const response = await fetch(url, options);
            var ct = null;
            try { ct = response.headers ? response.headers.get('content-type') : null; } catch (e) { ct = null; }
            var rawText = '';
            try { rawText = await response.clone().text(); } catch (e) { rawText = ''; }
            var looksJson = false;
            if (ct && ct.indexOf('application/json') !== -1) {
                looksJson = true;
            } else if (rawText) {
                looksJson = /^[\s]*[\{\[]/.test(rawText);
            }
            
            if (!response.ok) {
                var errorData = {};
                if (looksJson) {
                    try { errorData = JSON.parse(rawText); } catch (e) { errorData = {}; }
                }
                throw new Error((errorData && errorData.error) ? errorData.error : ('HTTP ' + response.status));
            }
            
            if (looksJson) {
                try { return JSON.parse(rawText); } catch (e) { return {}; }
            }
            return {};
        } catch (err) {
            console.error('API request failed:', err);
            throw err;
        }
    }

    get(endpoint, params = null) {
        return this.request(endpoint, 'GET', null, params);
    }

    post(endpoint, data) {
        return this.request(endpoint, 'POST', data);
    }

    put(endpoint, data) {
        return this.request(endpoint, 'PUT', data);
    }

    delete(endpoint) {
        return this.request(endpoint, 'DELETE');
    }
}

// Adicionar a instância da API ao objeto global da aplicação
document.addEventListener('DOMContentLoaded', () => {
    if (window.pdiApp) {
        window.pdiApp.api = new APIWrapper(window.pdiApp.apiBase);
    }
});


