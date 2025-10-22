/**
 * Validador de formulários no lado do cliente
 * Melhoria de UX para a plataforma PDI Angola
 */

class FormValidator {
    constructor(formElement, options = {}) {
        this.form = formElement;
        this.options = {
            showErrorsOnSubmit: true,
            showErrorsOnBlur: true,
            showErrorsOnInput: false,
            errorClass: 'error',
            successClass: 'success',
            errorMessageClass: 'error-message',
            ...options
        };
        
        this.rules = new Map();
        this.errors = new Map();
        this.isValid = true;
        
        this.init();
    }

    /**
     * Inicializa o validador
     */
    init() {
        if (!this.form) {
            console.error('Elemento de formulário não encontrado');
            return;
        }

        this.setupEventListeners();
        this.setupDefaultRules();
    }

    /**
     * Configura os event listeners
     */
    setupEventListeners() {
        // Validação no submit
        this.form.addEventListener('submit', (e) => {
            if (!this.validateAll()) {
                e.preventDefault();
                if (this.options.showErrorsOnSubmit) {
                    this.showAllErrors();
                }
            }
        });

        // Validação em tempo real
        this.form.addEventListener('input', (e) => {
            if (this.options.showErrorsOnInput) {
                this.validateField(e.target);
            }
        });

        this.form.addEventListener('blur', (e) => {
            if (this.options.showErrorsOnBlur) {
                this.validateField(e.target);
            }
        }, true);
    }

    /**
     * Configura regras padrão baseadas em atributos HTML
     */
    setupDefaultRules() {
        const fields = this.form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            const rules = [];
            
            // Required
            if (field.hasAttribute('required')) {
                rules.push({ type: 'required', message: 'Este campo é obrigatório' });
            }
            
            // Email
            if (field.type === 'email') {
                rules.push({ type: 'email', message: 'Digite um email válido' });
            }
            
            // Min length
            if (field.hasAttribute('minlength')) {
                const min = parseInt(field.getAttribute('minlength'));
                rules.push({ 
                    type: 'minLength', 
                    value: min, 
                    message: `Mínimo de ${min} caracteres` 
                });
            }
            
            // Max length
            if (field.hasAttribute('maxlength')) {
                const max = parseInt(field.getAttribute('maxlength'));
                rules.push({ 
                    type: 'maxLength', 
                    value: max, 
                    message: `Máximo de ${max} caracteres` 
                });
            }
            
            // Pattern
            if (field.hasAttribute('pattern')) {
                const pattern = field.getAttribute('pattern');
                rules.push({ 
                    type: 'pattern', 
                    value: new RegExp(pattern), 
                    message: 'Formato inválido' 
                });
            }
            
            // Min/Max para números
            if (field.type === 'number') {
                if (field.hasAttribute('min')) {
                    const min = parseFloat(field.getAttribute('min'));
                    rules.push({ 
                        type: 'min', 
                        value: min, 
                        message: `Valor mínimo: ${min}` 
                    });
                }
                
                if (field.hasAttribute('max')) {
                    const max = parseFloat(field.getAttribute('max'));
                    rules.push({ 
                        type: 'max', 
                        value: max, 
                        message: `Valor máximo: ${max}` 
                    });
                }
            }
            
            if (rules.length > 0) {
                this.addRule(field.name || field.id, rules);
            }
        });
    }

    /**
     * Adiciona regra de validação
     */
    addRule(fieldName, rules) {
        if (!Array.isArray(rules)) {
            rules = [rules];
        }
        
        this.rules.set(fieldName, rules);
        return this;
    }

    /**
     * Valida um campo específico
     */
    validateField(field) {
        const fieldName = field.name || field.id;
        const rules = this.rules.get(fieldName);
        
        if (!rules) return true;
        
        const value = field.value;
        const errors = [];
        
        for (const rule of rules) {
            const result = this.applyRule(value, rule, field);
            if (!result.valid) {
                errors.push(result.message);
            }
        }
        
        if (errors.length > 0) {
            this.errors.set(fieldName, errors);
            this.showFieldError(field, errors[0]);
            return false;
        } else {
            this.errors.delete(fieldName);
            this.clearFieldError(field);
            return true;
        }
    }

    /**
     * Aplica uma regra de validação
     */
    applyRule(value, rule, field) {
        switch (rule.type) {
            case 'required':
                return {
                    valid: value.trim() !== '',
                    message: rule.message
                };
                
            case 'email':
                return {
                    valid: !value || Utils.validateEmail(value),
                    message: rule.message
                };
                
            case 'minLength':
                return {
                    valid: !value || value.length >= rule.value,
                    message: rule.message
                };
                
            case 'maxLength':
                return {
                    valid: !value || value.length <= rule.value,
                    message: rule.message
                };
                
            case 'pattern':
                return {
                    valid: !value || rule.value.test(value),
                    message: rule.message
                };
                
            case 'min':
                const numValue = parseFloat(value);
                return {
                    valid: !value || (!isNaN(numValue) && numValue >= rule.value),
                    message: rule.message
                };
                
            case 'max':
                const maxNumValue = parseFloat(value);
                return {
                    valid: !value || (!isNaN(maxNumValue) && maxNumValue <= rule.value),
                    message: rule.message
                };
                
            case 'phoneAngola':
                return {
                    valid: !value || Utils.validatePhoneAngola(value),
                    message: rule.message || 'Telefone inválido para Angola'
                };
                
            case 'nifAngola':
                return {
                    valid: !value || Utils.validateNIF(value),
                    message: rule.message || 'NIF inválido'
                };
                
            case 'custom':
                return {
                    valid: rule.validator(value, field),
                    message: rule.message
                };
                
            default:
                return { valid: true, message: '' };
        }
    }

    /**
     * Valida todos os campos
     */
    validateAll() {
        const fields = this.form.querySelectorAll('input, select, textarea');
        let allValid = true;
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                allValid = false;
            }
        });
        
        this.isValid = allValid;
        return allValid;
    }

    /**
     * Mostra erro de campo
     */
    showFieldError(field, message) {
        // Adicionar classe de erro
        field.classList.add(this.options.errorClass);
        field.classList.remove(this.options.successClass);
        
        // Remover mensagem de erro existente
        this.clearFieldError(field, false);
        
        // Criar e adicionar mensagem de erro
        const errorElement = document.createElement('div');
        errorElement.className = this.options.errorMessageClass;
        errorElement.textContent = message;
        errorElement.setAttribute('data-field-error', field.name || field.id);
        
        // Inserir após o campo
        field.parentNode.insertBefore(errorElement, field.nextSibling);
        
        // Adicionar animação
        setTimeout(() => {
            errorElement.style.opacity = '1';
            errorElement.style.transform = 'translateY(0)';
        }, 10);
    }

    /**
     * Remove erro de campo
     */
    clearFieldError(field, addSuccessClass = true) {
        // Remover classe de erro
        field.classList.remove(this.options.errorClass);
        
        if (addSuccessClass && field.value.trim() !== '') {
            field.classList.add(this.options.successClass);
        }
        
        // Remover mensagem de erro
        const errorElement = field.parentNode.querySelector(
            `[data-field-error="${field.name || field.id}"]`
        );
        
        if (errorElement) {
            errorElement.style.opacity = '0';
            errorElement.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                if (errorElement.parentNode) {
                    errorElement.parentNode.removeChild(errorElement);
                }
            }, 300);
        }
    }

    /**
     * Mostra todos os erros
     */
    showAllErrors() {
        const fields = this.form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            const fieldName = field.name || field.id;
            const errors = this.errors.get(fieldName);
            
            if (errors && errors.length > 0) {
                this.showFieldError(field, errors[0]);
            }
        });
        
        // Scroll para o primeiro erro
        const firstErrorField = this.form.querySelector(`.${this.options.errorClass}`);
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            firstErrorField.focus();
        }
    }

    /**
     * Remove todos os erros
     */
    clearAllErrors() {
        const fields = this.form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            this.clearFieldError(field, false);
        });
        
        this.errors.clear();
    }

    /**
     * Obtém todos os erros
     */
    getErrors() {
        return Object.fromEntries(this.errors);
    }

    /**
     * Verifica se o formulário é válido
     */
    isFormValid() {
        return this.validateAll();
    }

    /**
     * Reset do formulário
     */
    reset() {
        this.form.reset();
        this.clearAllErrors();
        this.errors.clear();
        this.isValid = true;
    }

    /**
     * Obtém dados do formulário
     */
    getFormData() {
        const formData = new FormData(this.form);
        const data = {};
        
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    }

    /**
     * Preenche o formulário com dados
     */
    setFormData(data) {
        Object.keys(data).forEach(key => {
            const field = this.form.querySelector(`[name="${key}"], #${key}`);
            if (field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = data[key];
                } else {
                    field.value = data[key] || '';
                }
            }
        });
    }

    /**
     * Adiciona validação customizada
     */
    addCustomRule(fieldName, validator, message) {
        const existingRules = this.rules.get(fieldName) || [];
        existingRules.push({
            type: 'custom',
            validator: validator,
            message: message
        });
        
        this.rules.set(fieldName, existingRules);
        return this;
    }

    /**
     * Remove regra de validação
     */
    removeRule(fieldName, ruleType = null) {
        if (!ruleType) {
            this.rules.delete(fieldName);
        } else {
            const rules = this.rules.get(fieldName);
            if (rules) {
                const filteredRules = rules.filter(rule => rule.type !== ruleType);
                this.rules.set(fieldName, filteredRules);
            }
        }
        return this;
    }

    /**
     * Habilita/desabilita validação em tempo real
     */
    setRealTimeValidation(enabled) {
        this.options.showErrorsOnInput = enabled;
        return this;
    }
}

/**
 * Validações específicas para PDI Angola
 */
class PDIFormValidator extends FormValidator {
    constructor(formElement, options = {}) {
        super(formElement, options);
        this.setupPDIRules();
    }

    /**
     * Configura regras específicas do PDI
     */
    setupPDIRules() {
        // Adicionar regras automáticas para campos específicos
        const phoneFields = this.form.querySelectorAll('[data-type="phone"], [name*="telefone"], [name*="phone"]');
        phoneFields.forEach(field => {
            this.addRule(field.name || field.id, {
                type: 'phoneAngola',
                message: 'Digite um telefone válido para Angola'
            });
        });

        const nifFields = this.form.querySelectorAll('[data-type="nif"], [name*="nif"]');
        nifFields.forEach(field => {
            this.addRule(field.name || field.id, {
                type: 'nifAngola',
                message: 'Digite um NIF válido (9 dígitos)'
            });
        });

        // Validação de coordenadas GPS
        const latFields = this.form.querySelectorAll('[name*="latitude"], [name*="lat"]');
        latFields.forEach(field => {
            this.addRule(field.name || field.id, {
                type: 'custom',
                validator: (value) => {
                    if (!value) return true;
                    const num = parseFloat(value);
                    return !isNaN(num) && num >= -90 && num <= 90;
                },
                message: 'Latitude deve estar entre -90 e 90'
            });
        });

        const lngFields = this.form.querySelectorAll('[name*="longitude"], [name*="lng"], [name*="lon"]');
        lngFields.forEach(field => {
            this.addRule(field.name || field.id, {
                type: 'custom',
                validator: (value) => {
                    if (!value) return true;
                    const num = parseFloat(value);
                    return !isNaN(num) && num >= -180 && num <= 180;
                },
                message: 'Longitude deve estar entre -180 e 180'
            });
        });

        // Validação de ano
        const yearFields = this.form.querySelectorAll('[name*="ano"], [data-type="year"]');
        yearFields.forEach(field => {
            this.addRule(field.name || field.id, {
                type: 'custom',
                validator: (value) => {
                    if (!value) return true;
                    const year = parseInt(value);
                    const currentYear = new Date().getFullYear();
                    return !isNaN(year) && year >= 1900 && year <= (currentYear + 5);
                },
                message: `Ano deve estar entre 1900 e ${new Date().getFullYear() + 5}`
            });
        });

        // Validação de área (deve ser positiva)
        const areaFields = this.form.querySelectorAll('[name*="area"], [data-type="area"]');
        areaFields.forEach(field => {
            this.addRule(field.name || field.id, {
                type: 'custom',
                validator: (value) => {
                    if (!value) return true;
                    const num = parseFloat(value);
                    return !isNaN(num) && num > 0;
                },
                message: 'Área deve ser um valor positivo'
            });
        });
    }
}

// Disponibilizar globalmente
window.FormValidator = FormValidator;
window.PDIFormValidator = PDIFormValidator;

// Auto-inicialização para formulários com data-validate
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        new PDIFormValidator(form);
    });
});

