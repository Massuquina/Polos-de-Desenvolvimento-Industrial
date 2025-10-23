/**
 * Script para corrigir problemas de CSRF
 * Este script injeta o token CSRF em todas as requisições
 */

(function() {
    // Função para obter o token CSRF do servidor
    async function getCsrfToken() {
        try {
            const response = await fetch('/pdi_angola/backend/csrf_token.php', {
                method: 'GET',
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                return data.token;
            }
        } catch (error) {
            console.error('Erro ao obter token CSRF:', error);
        }
        
        // Em caso de erro, gerar um token local
        return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    }
    
    // Obter o token CSRF e armazenar globalmente
    getCsrfToken().then(token => {
        window.csrfToken = token;
        
        // Injetar o token em todas as requisições fetch
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            // Se for uma requisição POST, PUT, DELETE, adicionar o token CSRF
            if (options.method && ['POST', 'PUT', 'DELETE'].includes(options.method.toUpperCase())) {
                // Inicializar headers se não existirem
                options.headers = options.headers || {};
                
                // Adicionar o token CSRF ao header
                options.headers['X-CSRF-Token'] = window.csrfToken;
                
                // Se for um formulário, adicionar o token como campo
                if (options.body instanceof FormData) {
                    options.body.append('csrf_token', window.csrfToken);
                } else if (typeof options.body === 'string' && options.body.startsWith('{')) {
                    // Se for JSON, adicionar o token ao objeto
                    try {
                        const bodyObj = JSON.parse(options.body);
                        bodyObj.csrf_token = window.csrfToken;
                        options.body = JSON.stringify(bodyObj);
                    } catch (e) {
                        // Ignorar erro de parsing
                    }
                }
            }
            
            return originalFetch(url, options);
        };
        
        console.log('CSRF token injetado em todas as requisições');
    });
    
    // Adicionar o script à página
    document.addEventListener('DOMContentLoaded', function() {
        console.log('CSRF fix carregado');
    });
})();