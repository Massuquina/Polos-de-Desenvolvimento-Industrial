/**
 * Módulo de Autenticação - PDI Angola
 * Gestão de login, logout e verificação de sessão
 */

class AuthManager {
    constructor() {
        // Usar configuração centralizada
        this.apiBase = window.AppConfig ? window.AppConfig.getLegacyApiUrl() : 'http://localhost/pdi_angola/backend/api.php';
        this.currentUser = null;
        this.init();
    }

    /**
     * Inicializa o módulo de autenticação
     */
    init() {
        this.checkAuthStatus();
        this.setupEventListeners();
    }

    /**
     * Configura os event listeners
     */
    setupEventListeners() {
        // O logout global é gerido pela aplicação principal (app.js).
        // Evitamos adicionar um segundo event listener aqui para prevenir duplicação.
    }

    /**
     * Verifica o status de autenticação
     */
    async checkAuthStatus() {
        try {
            const response = await fetch(`${this.apiBase}/auth/check`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                var ct = null;
                try { ct = response.headers ? response.headers.get('content-type') : null; } catch (e) { ct = null; }
                var rawText = '';
                try { rawText = await response.clone().text(); } catch (e) { rawText = ''; }
                var looksJson = false;
                if (ct && ct.indexOf('application/json') !== -1) { looksJson = true; }
                else if (rawText) { looksJson = /^[\s]*[\{\[]/.test(rawText); }
                
                if (looksJson) {
                    try {
                        var data = JSON.parse(rawText);
                        if (data && data.user) {
                            this.currentUser = data.user;
                            this.updateUI();
                            return true;
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                    }
                }
            }
        } catch (error) {
            console.error('Erro ao verificar autenticação:', error);
        }

        this.currentUser = null;
        return false;
    }

    /**
     * Realiza login
     */
    async login(email, password) {
        try {
            const response = await fetch(`${this.apiBase}/auth/login`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ email, password })
            });

            var ct = null;
            try { ct = response.headers ? response.headers.get('content-type') : null; } catch (e) { ct = null; }
            var rawText = '';
            try { rawText = await response.clone().text(); } catch (e) { rawText = ''; }
            var looksJson = false;
            if (ct && ct.indexOf('application/json') !== -1) { looksJson = true; }
            else if (rawText) { looksJson = /^[\s]*[\{\[]/.test(rawText); }
            var data = null;
            if (looksJson) {
                try { data = JSON.parse(rawText); } catch (e) { data = null; }
            }

            if (response.ok && data && data.user) {
                this.currentUser = data.user;
                this.updateUI();
                return { success: true, user: data.user };
            } else {
                return { success: false, error: (data && data.error) ? data.error : 'Erro no login' };
            }
        } catch (error) {
            console.error('Erro no login:', error);
            return { success: false, error: 'Erro de conexão' };
        }
    }

    /**
     * Realiza logout
     */
    async logout() {
        try {
            await fetch(`${this.apiBase}/auth/logout`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        } catch (error) {
            console.error('Erro no logout:', error);
        }

        this.currentUser = null;
        this.updateUI();
        
        // Mostrar modal de login se existir
        if (window.app && typeof window.app.showLoginModal === 'function') {
            window.app.showLoginModal();
        }
    }

    /**
     * Atualiza a interface do usuário
     */
    updateUI() {
        const userNameElement = document.getElementById('userName');
        const userMenu = document.getElementById('userMenu');

        if (this.currentUser) {
            if (userNameElement) {
                userNameElement.textContent = this.currentUser.nome || this.currentUser.email;
            }
            if (userMenu) {
                userMenu.style.display = 'block';
            }
        } else {
            if (userNameElement) {
                userNameElement.textContent = 'Utilizador';
            }
            if (userMenu) {
                userMenu.style.display = 'none';
            }
        }
    }

    /**
     * Verifica se o usuário está autenticado
     */
    isAuthenticated() {
        return this.currentUser !== null;
    }

    /**
     * Obtém o usuário atual
     */
    getCurrentUser() {
        return this.currentUser;
    }

    /**
     * Verifica se o usuário tem uma permissão específica
     */
    hasPermission(permission) {
        if (!this.currentUser) return false;
        
        const userLevel = this.currentUser.nivel_acesso;
        
        switch (permission) {
            case 'admin':
                return userLevel === 'administrador';
            case 'edit':
                return ['administrador', 'editor'].includes(userLevel);
            case 'view':
                return ['administrador', 'editor', 'visualizador'].includes(userLevel);
            default:
                return false;
        }
    }
}

// Inicializar o módulo de autenticação quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    window.authManager = new AuthManager();
});