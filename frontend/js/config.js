/**
 * Configuração centralizada da aplicação PDI Angola
 * Centraliza todas as configurações de API e ambiente
 */

class AppConfig {
    constructor() {
        // Detectar ambiente baseado no hostname
        this.environment = this.detectEnvironment();
        
        // Configurações por ambiente
        this.config = {
            development: {
                apiBase: 'http://localhost/pdi_angola/backend/api_improved.php',
                apiLegacy: 'http://localhost/pdi_angola/backend/api.php',
                enableDebug: true,
                enableCache: false,
                logLevel: 'debug'
            },
            production: {
                apiBase: '/backend/api_improved.php',
                apiLegacy: '/backend/api.php',
                enableDebug: false,
                enableCache: true,
                logLevel: 'error'
            }
        };
        
        // Configurações gerais
        this.general = {
            appName: 'PDI Angola - Gestão de Infraestruturas',
            version: '1.0.0',
            timeout: 30000, // 30 segundos
            retryAttempts: 3,
            cacheExpiry: 300000, // 5 minutos
            pagination: {
                defaultPageSize: 20,
                maxPageSize: 100
            }
        };
    }
    
    /**
     * Detecta o ambiente baseado no hostname
     */
    detectEnvironment() {
        const hostname = window.location.hostname;
        
        if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname.includes('dev')) {
            return 'development';
        }
        
        return 'production';
    }
    
    /**
     * Obtém configuração do ambiente atual
     */
    get() {
        return {
            ...this.config[this.environment],
            ...this.general,
            environment: this.environment
        };
    }
    
    /**
     * Obtém uma configuração específica
     */
    getValue(key, defaultValue = null) {
        const config = this.get();
        return config[key] !== undefined ? config[key] : defaultValue;
    }
    
    /**
     * Verifica se está em desenvolvimento
     */
    isDevelopment() {
        return this.environment === 'development';
    }
    
    /**
     * Verifica se está em produção
     */
    isProduction() {
        return this.environment === 'production';
    }
    
    /**
     * Obtém URL completa da API
     */
    getApiUrl(endpoint = '') {
        const baseUrl = this.getValue('apiBase');
        return endpoint ? `${baseUrl}/${endpoint.replace(/^\//, '')}` : baseUrl;
    }
    
    /**
     * Obtém URL da API legada
     */
    getLegacyApiUrl(endpoint = '') {
        const baseUrl = this.getValue('apiLegacy');
        return endpoint ? `${baseUrl}/${endpoint.replace(/^\//, '')}` : baseUrl;
    }
    
    /**
     * Log condicional baseado no ambiente
     */
    log(level, message, ...args) {
        const currentLogLevel = this.getValue('logLevel');
        const enableDebug = this.getValue('enableDebug');
        
        if (!enableDebug && level === 'debug') {
            return;
        }
        
        const levels = ['debug', 'info', 'warn', 'error'];
        const currentLevelIndex = levels.indexOf(currentLogLevel);
        const messageLevelIndex = levels.indexOf(level);
        
        if (messageLevelIndex >= currentLevelIndex) {
            console[level](message, ...args);
        }
    }
}

// Criar instância global
window.AppConfig = new AppConfig();

// Disponibilizar configuração globalmente
window.CONFIG = window.AppConfig.get();

// Log da configuração carregada (apenas em desenvolvimento)
if (window.AppConfig.isDevelopment()) {
    console.log('🔧 Configuração carregada:', window.CONFIG);
}