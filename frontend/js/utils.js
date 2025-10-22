/**
 * Utilitários gerais para a aplicação PDI Angola
 * Funções auxiliares para melhorar a modularidade e reutilização de código
 */

class Utils {
    /**
     * Formata números com separadores de milhares
     */
    static formatNumber(number, decimals = 0) {
        if (number === null || number === undefined || number === '') {
            return '-';
        }
        
        const num = parseFloat(number);
        if (isNaN(num)) return '-';
        
        return num.toLocaleString('pt-AO', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    /**
     * Formata datas no formato brasileiro
     */
    static formatDate(dateString, includeTime = false) {
        if (!dateString) return '-';
        
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '-';
            
            const options = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            };
            
            if (includeTime) {
                options.hour = '2-digit';
                options.minute = '2-digit';
            }
            
            return date.toLocaleDateString('pt-AO', options);
        } catch (error) {
            console.error('Erro ao formatar data:', error);
            return '-';
        }
    }

    /**
     * Formata valores monetários
     */
    static formatCurrency(value, currency = 'AOA') {
        if (value === null || value === undefined || value === '') {
            return '-';
        }
        
        const num = parseFloat(value);
        if (isNaN(num)) return '-';
        
        return num.toLocaleString('pt-AO', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2
        });
    }

    /**
     * Trunca texto com reticências
     */
    static truncateText(text, maxLength = 50) {
        if (!text || text.length <= maxLength) {
            return text || '';
        }
        return text.substring(0, maxLength) + '...';
    }

    /**
     * Capitaliza a primeira letra de cada palavra
     */
    static capitalizeWords(text) {
        if (!text) return '';
        return text.replace(/\w\S*/g, (txt) => 
            txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
        );
    }

    /**
     * Valida email
     */
    static validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Valida telefone angolano
     */
    static validatePhoneAngola(phone) {
        if (!phone) return true; // Campo opcional
        
        // Remove espaços e caracteres especiais
        const cleanPhone = phone.replace(/[^0-9+]/g, '');
        
        // Verifica formatos válidos para Angola
        const patterns = [
            /^9[0-9]{8}$/,           // 9XXXXXXXX (formato nacional)
            /^\+2449[0-9]{8}$/,      // +2449XXXXXXXX (formato internacional)
            /^2449[0-9]{8}$/,        // 2449XXXXXXXX (formato sem +)
        ];
        
        return patterns.some(pattern => pattern.test(cleanPhone));
    }

    /**
     * Valida NIF angolano
     */
    static validateNIF(nif) {
        if (!nif) return true; // Campo opcional
        
        // Remove espaços e caracteres especiais
        const cleanNIF = nif.replace(/[^0-9]/g, '');
        
        // Verifica se tem 9 dígitos
        if (cleanNIF.length !== 9) return false;
        
        // Verifica se não são todos os mesmos dígitos
        if (/^(\d)\1{8}$/.test(cleanNIF)) return false;
        
        return true;
    }

    /**
     * Debounce para otimizar chamadas de função
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle para limitar chamadas de função
     */
    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Gera ID único
     */
    static generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }

    /**
     * Copia texto para a área de transferência
     */
    static async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            console.error('Erro ao copiar para área de transferência:', err);
            return false;
        }
    }

    /**
     * Converte objeto para query string
     */
    static objectToQueryString(obj) {
        const params = new URLSearchParams();
        
        for (const [key, value] of Object.entries(obj)) {
            if (value !== null && value !== undefined && value !== '') {
                params.append(key, value);
            }
        }
        
        return params.toString();
    }

    /**
     * Converte query string para objeto
     */
    static queryStringToObject(queryString) {
        const params = new URLSearchParams(queryString);
        const obj = {};
        
        for (const [key, value] of params.entries()) {
            obj[key] = value;
        }
        
        return obj;
    }

    /**
     * Sanitiza HTML para prevenir XSS
     */
    static sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Calcula a distância entre duas coordenadas GPS
     */
    static calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Raio da Terra em km
        const dLat = this.toRadians(lat2 - lat1);
        const dLon = this.toRadians(lon2 - lon1);
        
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // Distância em km
    }

    /**
     * Converte graus para radianos
     */
    static toRadians(degrees) {
        return degrees * (Math.PI / 180);
    }

    /**
     * Obtém coordenadas do usuário
     */
    static getCurrentLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocalização não suportada'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                (error) => {
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000 // 5 minutos
                }
            );
        });
    }

    /**
     * Detecta se é dispositivo móvel
     */
    static isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    /**
     * Detecta se é tablet
     */
    static isTablet() {
        return /iPad|Android(?!.*Mobile)/i.test(navigator.userAgent);
    }

    /**
     * Obtém informações do dispositivo
     */
    static getDeviceInfo() {
        return {
            isMobile: this.isMobile(),
            isTablet: this.isTablet(),
            isDesktop: !this.isMobile() && !this.isTablet(),
            userAgent: navigator.userAgent,
            language: navigator.language,
            platform: navigator.platform,
            screenWidth: screen.width,
            screenHeight: screen.height,
            windowWidth: window.innerWidth,
            windowHeight: window.innerHeight
        };
    }

    /**
     * Salva dados no localStorage com verificação de suporte
     */
    static saveToLocalStorage(key, data) {
        try {
            if (typeof Storage !== 'undefined') {
                localStorage.setItem(key, JSON.stringify(data));
                return true;
            }
        } catch (error) {
            console.error('Erro ao salvar no localStorage:', error);
        }
        return false;
    }

    /**
     * Carrega dados do localStorage
     */
    static loadFromLocalStorage(key, defaultValue = null) {
        try {
            if (typeof Storage !== 'undefined') {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            }
        } catch (error) {
            console.error('Erro ao carregar do localStorage:', error);
        }
        return defaultValue;
    }

    /**
     * Remove item do localStorage
     */
    static removeFromLocalStorage(key) {
        try {
            if (typeof Storage !== 'undefined') {
                localStorage.removeItem(key);
                return true;
            }
        } catch (error) {
            console.error('Erro ao remover do localStorage:', error);
        }
        return false;
    }

    /**
     * Limpa o localStorage
     */
    static clearLocalStorage() {
        try {
            if (typeof Storage !== 'undefined') {
                localStorage.clear();
                return true;
            }
        } catch (error) {
            console.error('Erro ao limpar localStorage:', error);
        }
        return false;
    }

    /**
     * Converte arquivo para base64
     */
    static fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }

    /**
     * Valida tamanho de arquivo
     */
    static validateFileSize(file, maxSizeMB = 5) {
        const maxSizeBytes = maxSizeMB * 1024 * 1024;
        return file.size <= maxSizeBytes;
    }

    /**
     * Valida tipo de arquivo
     */
    static validateFileType(file, allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
        const fileExtension = file.name.split('.').pop().toLowerCase();
        return allowedTypes.includes(fileExtension);
    }

    /**
     * Formata tamanho de arquivo
     */
    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Exporta dados para CSV
     */
    static exportToCSV(data, filename = 'export.csv') {
        if (!data || data.length === 0) {
            console.error('Nenhum dado para exportar');
            return;
        }

        // Obter cabeçalhos
        const headers = Object.keys(data[0]);
        
        // Criar conteúdo CSV
        let csvContent = headers.join(',') + '\n';
        
        data.forEach(row => {
            const values = headers.map(header => {
                let value = row[header] || '';
                // Escapar aspas duplas e adicionar aspas se necessário
                if (typeof value === 'string' && (value.includes(',') || value.includes('"') || value.includes('\n'))) {
                    value = '"' + value.replace(/"/g, '""') + '"';
                }
                return value;
            });
            csvContent += values.join(',') + '\n';
        });

        // Criar e baixar arquivo
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    /**
     * Imprime elemento específico
     */
    static printElement(elementId) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.error('Elemento não encontrado:', elementId);
            return;
        }

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Impressão</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        @media print {
                            body { margin: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${element.innerHTML}
                </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }

    /**
     * Scroll suave para elemento
     */
    static scrollToElement(elementId, offset = 0) {
        const element = document.getElementById(elementId);
        if (element) {
            const elementPosition = element.offsetTop - offset;
            window.scrollTo({
                top: elementPosition,
                behavior: 'smooth'
            });
        }
    }

    /**
     * Verifica se elemento está visível na viewport
     */
    static isElementInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Adiciona classe com animação
     */
    static addClassWithAnimation(element, className, duration = 300) {
        element.classList.add(className);
        
        setTimeout(() => {
            element.classList.remove(className);
        }, duration);
    }

    /**
     * Carrega script dinamicamente
     */
    static loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Carrega CSS dinamicamente
     */
    static loadCSS(href) {
        return new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.onload = resolve;
            link.onerror = reject;
            document.head.appendChild(link);
        });
    }

    /**
     * Obtém parâmetros da URL
     */
    static getURLParams() {
        const params = new URLSearchParams(window.location.search);
        const result = {};
        
        for (const [key, value] of params.entries()) {
            result[key] = value;
        }
        
        return result;
    }

    /**
     * Atualiza URL sem recarregar a página
     */
    static updateURL(params, replace = false) {
        const url = new URL(window.location);
        
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                url.searchParams.set(key, params[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        
        if (replace) {
            window.history.replaceState({}, '', url);
        } else {
            window.history.pushState({}, '', url);
        }
    }
}

// Disponibilizar globalmente
window.Utils = Utils;

