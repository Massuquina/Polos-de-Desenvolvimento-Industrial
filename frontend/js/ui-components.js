/**
 * Componentes de UI reutilizáveis
 * Melhoria de UX para a plataforma PDI Angola
 */

class UIComponents {
    /**
     * Cria um loading spinner
     */
    static createLoader(size = 'medium', color = '#d32f2f') {
        const loader = document.createElement('div');
        loader.className = `loader loader-${size}`;
        loader.innerHTML = `
            <div class="spinner" style="border-top-color: ${color}"></div>
            <span class="loader-text">Carregando...</span>
        `;
        
        return loader;
    }

    /**
     * Mostra loading global
     */
    static showGlobalLoader(message = 'Carregando...') {
        let loader = document.getElementById('globalLoader');
        
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'globalLoader';
            loader.className = 'global-loader';
            loader.innerHTML = `
                <div class="global-loader-backdrop"></div>
                <div class="global-loader-content">
                    <div class="spinner"></div>
                    <span class="loader-message">${message}</span>
                </div>
            `;
            document.body.appendChild(loader);
        } else {
            loader.querySelector('.loader-message').textContent = message;
        }
        
        loader.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Oculta loading global
     */
    static hideGlobalLoader() {
        const loader = document.getElementById('globalLoader');
        if (loader) {
            loader.classList.remove('show');
            document.body.style.overflow = '';
            
            setTimeout(() => {
                if (loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
            }, 300);
        }
    }

    /**
     * Cria uma notificação toast
     */
    static showToast(message, type = 'info', duration = 4000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-exclamation-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };
        
        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Container de toasts
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        // Animação de entrada
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Auto-remover
        if (duration > 0) {
            setTimeout(() => {
                this.removeToast(toast);
            }, duration);
        }
        
        return toast;
    }

    /**
     * Remove toast
     */
    static removeToast(toast) {
        toast.classList.add('hide');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    /**
     * Cria um modal dinâmico
     */
    static createModal(title, content, options = {}) {
        const defaultOptions = {
            size: 'medium', // small, medium, large, full
            closable: true,
            backdrop: true,
            buttons: [],
            onShow: null,
            onHide: null
        };
        
        const opts = { ...defaultOptions, ...options };
        
        const modal = document.createElement('div');
        modal.className = `modal modal-${opts.size}`;
        modal.innerHTML = `
            <div class="modal-backdrop ${opts.backdrop ? 'clickable' : ''}"></div>
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">${title}</h3>
                        ${opts.closable ? '<button class="modal-close"><i class="fas fa-times"></i></button>' : ''}
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                    ${opts.buttons.length > 0 ? `
                        <div class="modal-footer">
                            ${opts.buttons.map(btn => `
                                <button class="btn ${btn.class || 'btn-secondary'}" data-action="${btn.action || ''}">${btn.text}</button>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        // Event listeners
        if (opts.closable) {
            const closeBtn = modal.querySelector('.modal-close');
            closeBtn.addEventListener('click', () => this.hideModal(modal));
        }
        
        if (opts.backdrop) {
            const backdrop = modal.querySelector('.modal-backdrop.clickable');
            backdrop.addEventListener('click', () => this.hideModal(modal));
        }
        
        // Botões de ação
        opts.buttons.forEach(btn => {
            const button = modal.querySelector(`[data-action="${btn.action}"]`);
            if (button && btn.handler) {
                button.addEventListener('click', (e) => btn.handler(e, modal));
            }
        });
        
        document.body.appendChild(modal);
        
        // Mostrar modal
        setTimeout(() => {
            modal.classList.add('show');
            if (opts.onShow) opts.onShow(modal);
        }, 10);
        
        // Prevenir scroll do body
        document.body.style.overflow = 'hidden';
        
        return modal;
    }

    /**
     * Oculta modal
     */
    static hideModal(modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
    }

    /**
     * Cria um modal de confirmação
     */
    static showConfirmModal(message, options = {}) {
        return new Promise((resolve) => {
            const defaultOptions = {
                title: 'Confirmação',
                confirmText: 'Confirmar',
                cancelText: 'Cancelar',
                type: 'warning' // success, warning, danger, info
            };
            
            const opts = { ...defaultOptions, ...options };
            
            const icons = {
                success: '<i class="fas fa-check-circle text-success"></i>',
                warning: '<i class="fas fa-exclamation-triangle text-warning"></i>',
                danger: '<i class="fas fa-exclamation-circle text-danger"></i>',
                info: '<i class="fas fa-info-circle text-info"></i>'
            };
            
            const content = `
                <div class="confirm-modal-content">
                    <div class="confirm-icon">${icons[opts.type]}</div>
                    <p class="confirm-message">${message}</p>
                </div>
            `;
            
            const modal = this.createModal(opts.title, content, {
                size: 'small',
                buttons: [
                    {
                        text: opts.cancelText,
                        class: 'btn-secondary',
                        action: 'cancel',
                        handler: () => {
                            resolve(false);
                            this.hideModal(modal);
                        }
                    },
                    {
                        text: opts.confirmText,
                        class: opts.type === 'danger' ? 'btn-danger' : 'btn-primary',
                        action: 'confirm',
                        handler: () => {
                            resolve(true);
                            this.hideModal(modal);
                        }
                    }
                ]
            });
        });
    }

    /**
     * Cria um dropdown customizado
     */
    static createDropdown(trigger, items, options = {}) {
        const defaultOptions = {
            position: 'bottom-left', // bottom-left, bottom-right, top-left, top-right
            closeOnClick: true,
            className: ''
        };
        
        const opts = { ...defaultOptions, ...options };
        
        const dropdown = document.createElement('div');
        dropdown.className = `dropdown ${opts.className}`;
        dropdown.innerHTML = `
            <div class="dropdown-menu dropdown-${opts.position}">
                ${items.map(item => {
                    if (item.divider) {
                        return '<div class="dropdown-divider"></div>';
                    }
                    return `
                        <a href="#" class="dropdown-item ${item.disabled ? 'disabled' : ''}" data-action="${item.action || ''}">
                            ${item.icon ? `<i class="${item.icon}"></i>` : ''}
                            <span>${item.text}</span>
                        </a>
                    `;
                }).join('')}
            </div>
        `;
        
        // Posicionar dropdown
        trigger.parentNode.insertBefore(dropdown, trigger.nextSibling);
        
        // Event listeners
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        
        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
        
        // Ações dos itens
        dropdown.addEventListener('click', (e) => {
            e.preventDefault();
            const item = e.target.closest('.dropdown-item');
            if (item && !item.classList.contains('disabled')) {
                const action = item.getAttribute('data-action');
                const itemData = items.find(i => i.action === action);
                
                if (itemData && itemData.handler) {
                    itemData.handler(e, item);
                }
                
                if (opts.closeOnClick) {
                    dropdown.classList.remove('show');
                }
            }
        });
        
        return dropdown;
    }

    /**
     * Cria uma tabela com funcionalidades avançadas
     */
    static createDataTable(container, data, columns, options = {}) {
        const defaultOptions = {
            pagination: true,
            pageSize: 10,
            search: true,
            sort: true,
            actions: [],
            emptyMessage: 'Nenhum dado encontrado',
            loadingMessage: 'Carregando...'
        };
        
        const opts = { ...defaultOptions, ...options };
        
        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'data-table-wrapper';
        
        // Cabeçalho com pesquisa
        if (opts.search) {
            const header = document.createElement('div');
            header.className = 'data-table-header';
            header.innerHTML = `
                <div class="data-table-search">
                    <input type="text" class="form-control" placeholder="Pesquisar..." />
                    <i class="fas fa-search"></i>
                </div>
            `;
            tableWrapper.appendChild(header);
        }
        
        // Tabela
        const table = document.createElement('table');
        table.className = 'data-table';
        
        // Cabeçalho da tabela
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        
        columns.forEach(col => {
            const th = document.createElement('th');
            th.textContent = col.title;
            
            if (opts.sort && col.sortable !== false) {
                th.classList.add('sortable');
                th.innerHTML += ' <i class="fas fa-sort"></i>';
                th.addEventListener('click', () => {
                    // Implementar ordenação
                    this.sortTable(table, col.key);
                });
            }
            
            headerRow.appendChild(th);
        });
        
        if (opts.actions.length > 0) {
            const actionTh = document.createElement('th');
            actionTh.textContent = 'Ações';
            actionTh.className = 'actions-column';
            headerRow.appendChild(actionTh);
        }
        
        thead.appendChild(headerRow);
        table.appendChild(thead);
        
        // Corpo da tabela
        const tbody = document.createElement('tbody');
        table.appendChild(tbody);
        
        tableWrapper.appendChild(table);
        
        // Paginação
        if (opts.pagination) {
            const pagination = document.createElement('div');
            pagination.className = 'data-table-pagination';
            tableWrapper.appendChild(pagination);
        }
        
        container.appendChild(tableWrapper);
        
        // Renderizar dados
        this.renderTableData(tbody, data, columns, opts);
        
        return {
            element: tableWrapper,
            refresh: (newData) => this.renderTableData(tbody, newData, columns, opts),
            search: (query) => this.searchTable(tbody, data, columns, query, opts),
            sort: (key, direction) => this.sortTable(table, key, direction)
        };
    }

    /**
     * Renderiza dados da tabela
     */
    static renderTableData(tbody, data, columns, options) {
        tbody.innerHTML = '';
        
        if (!data || data.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = columns.length + (options.actions.length > 0 ? 1 : 0);
            cell.textContent = options.emptyMessage;
            cell.className = 'empty-message';
            row.appendChild(cell);
            tbody.appendChild(row);
            return;
        }
        
        data.forEach(item => {
            const row = document.createElement('tr');
            
            columns.forEach(col => {
                const cell = document.createElement('td');
                
                if (col.render) {
                    cell.innerHTML = col.render(item[col.key], item);
                } else {
                    cell.textContent = item[col.key] || '-';
                }
                
                row.appendChild(cell);
            });
            
            // Ações
            if (options.actions.length > 0) {
                const actionCell = document.createElement('td');
                actionCell.className = 'actions-cell';
                
                options.actions.forEach(action => {
                    const button = document.createElement('button');
                    button.className = `btn btn-sm ${action.class || 'btn-secondary'}`;
                    button.innerHTML = action.icon ? `<i class="${action.icon}"></i>` : action.text;
                    button.title = action.title || action.text;
                    
                    if (action.handler) {
                        button.addEventListener('click', (e) => action.handler(item, e));
                    }
                    
                    actionCell.appendChild(button);
                });
                
                row.appendChild(actionCell);
            }
            
            tbody.appendChild(row);
        });
    }

    /**
     * Cria um progress bar
     */
    static createProgressBar(value, max = 100, options = {}) {
        const defaultOptions = {
            showText: true,
            animated: false,
            color: '#d32f2f',
            height: '20px'
        };
        
        const opts = { ...defaultOptions, ...options };
        const percentage = Math.round((value / max) * 100);
        
        const progressBar = document.createElement('div');
        progressBar.className = `progress-bar ${opts.animated ? 'animated' : ''}`;
        progressBar.style.height = opts.height;
        
        progressBar.innerHTML = `
            <div class="progress-fill" style="width: ${percentage}%; background-color: ${opts.color};">
                ${opts.showText ? `<span class="progress-text">${percentage}%</span>` : ''}
            </div>
        `;
        
        return progressBar;
    }

    /**
     * Cria um badge/tag
     */
    static createBadge(text, type = 'primary') {
        const badge = document.createElement('span');
        badge.className = `badge badge-${type}`;
        badge.textContent = text;
        return badge;
    }

    /**
     * Cria um tooltip
     */
    static addTooltip(element, text, position = 'top') {
        element.setAttribute('data-tooltip', text);
        element.setAttribute('data-tooltip-position', position);
        element.classList.add('has-tooltip');
        
        let tooltip = null;
        
        element.addEventListener('mouseenter', () => {
            tooltip = document.createElement('div');
            tooltip.className = `tooltip tooltip-${position}`;
            tooltip.textContent = text;
            document.body.appendChild(tooltip);
            
            const rect = element.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            let top, left;
            
            switch (position) {
                case 'top':
                    top = rect.top - tooltipRect.height - 8;
                    left = rect.left + (rect.width - tooltipRect.width) / 2;
                    break;
                case 'bottom':
                    top = rect.bottom + 8;
                    left = rect.left + (rect.width - tooltipRect.width) / 2;
                    break;
                case 'left':
                    top = rect.top + (rect.height - tooltipRect.height) / 2;
                    left = rect.left - tooltipRect.width - 8;
                    break;
                case 'right':
                    top = rect.top + (rect.height - tooltipRect.height) / 2;
                    left = rect.right + 8;
                    break;
            }
            
            tooltip.style.top = `${top}px`;
            tooltip.style.left = `${left}px`;
            tooltip.classList.add('show');
        });
        
        element.addEventListener('mouseleave', () => {
            if (tooltip) {
                tooltip.classList.remove('show');
                setTimeout(() => {
                    if (tooltip.parentNode) {
                        tooltip.parentNode.removeChild(tooltip);
                    }
                }, 200);
            }
        });
    }

    /**
     * Cria um accordion
     */
    static createAccordion(container, items) {
        const accordion = document.createElement('div');
        accordion.className = 'accordion';
        
        items.forEach((item, index) => {
            const accordionItem = document.createElement('div');
            accordionItem.className = 'accordion-item';
            
            accordionItem.innerHTML = `
                <div class="accordion-header" data-index="${index}">
                    <h3>${item.title}</h3>
                    <i class="fas fa-chevron-down accordion-icon"></i>
                </div>
                <div class="accordion-content">
                    <div class="accordion-body">
                        ${item.content}
                    </div>
                </div>
            `;
            
            const header = accordionItem.querySelector('.accordion-header');
            const content = accordionItem.querySelector('.accordion-content');
            
            header.addEventListener('click', () => {
                const isActive = accordionItem.classList.contains('active');
                
                // Fechar todos os outros itens
                accordion.querySelectorAll('.accordion-item').forEach(otherItem => {
                    otherItem.classList.remove('active');
                });
                
                // Toggle do item atual
                if (!isActive) {
                    accordionItem.classList.add('active');
                }
            });
            
            accordion.appendChild(accordionItem);
        });
        
        container.appendChild(accordion);
        return accordion;
    }

    /**
     * Cria um skeleton loader
     */
    static createSkeleton(type = 'text', count = 1) {
        const skeleton = document.createElement('div');
        skeleton.className = 'skeleton-wrapper';
        
        for (let i = 0; i < count; i++) {
            const item = document.createElement('div');
            item.className = `skeleton skeleton-${type}`;
            skeleton.appendChild(item);
        }
        
        return skeleton;
    }

    /**
     * Anima contadores
     */
    static animateCounter(element, target, duration = 2000) {
        const start = parseInt(element.textContent) || 0;
        const increment = (target - start) / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            
            if ((increment > 0 && current >= target) || (increment < 0 && current <= target)) {
                element.textContent = Utils.formatNumber(target);
                clearInterval(timer);
            } else {
                element.textContent = Utils.formatNumber(Math.floor(current));
            }
        }, 16);
    }
}

// Disponibilizar globalmente
window.UIComponents = UIComponents;

// Auto-inicialização de tooltips
document.addEventListener('DOMContentLoaded', () => {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        const text = element.getAttribute('data-tooltip');
        const position = element.getAttribute('data-tooltip-position') || 'top';
        UIComponents.addTooltip(element, text, position);
    });
});

