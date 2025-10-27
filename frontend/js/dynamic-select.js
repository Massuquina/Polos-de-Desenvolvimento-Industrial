/**
 * DynamicSelect
 * Componente leve de multi-select com temas: 'default', 'avatar', 'checkbox'.
 * Integra com <select multiple>, mantendo sincronização dos valores.
 */
(function(){
    class DynamicSelect {
        constructor(selectEl, opts = {}) {
            this.selectEl = selectEl;
            this.theme = (selectEl.getAttribute('data-theme') || opts.theme || 'checkbox').toLowerCase();
            this.isOpen = false;
            this.allItems = [];
            this.filteredData = [];
            this.selectedItems = [];

            this._bootstrapFromSelect();
            this._buildUI();
            this._bindEvents();
            this._syncFromSelect();
            this._renderOptions();
            this._updateSelectedDisplay();
        }

        /**
         * Atualiza programaticamente as opções do select e reflete na UI
         * newOptions: Array<{ value, text }>
         */
        setOptions(newOptions = []) {
            // Atualizar <select>
            while (this.selectEl.options.length) this.selectEl.remove(0);
            newOptions.forEach(({ value, text }) => {
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = text;
                // manter seleção se ainda existir
                opt.selected = this.selectedItems.some(s => s.value === value);
                this.selectEl.appendChild(opt);
            });

            // Remover itens selecionados que não existem mais
            this.selectedItems = this.selectedItems.filter(s => newOptions.some(n => n.value === s.value));

            // Atualizar dados internos e UI
            this._bootstrapFromSelect();
            this._updateSelectedDisplay();
            // Limpar busca ao atualizar conjunto de opções para evitar lista vazia indevida
            if (this.searchInput) this.searchInput.value = '';
            this._filterOptions();
        }
        _bootstrapFromSelect() {
            const options = Array.from(this.selectEl.options || []);
            this.allItems = options.map(opt => ({ value: opt.value, text: opt.textContent }));
            this.filteredData = this.allItems.slice();
        }

        _buildUI() {
            // Container
            this.container = document.createElement('div');
            this.container.className = `dynamic-select-container ds-theme--${this.theme}`;

            // Input visual
            this.input = document.createElement('div');
            this.input.className = 'dynamic-select-input';
            this.input.setAttribute('role','button');
            this.input.tabIndex = 0;

            // Setinha
            this.arrow = document.createElement('span');
            this.arrow.className = 'dynamic-select-arrow';
            this.input.appendChild(this.arrow);

            // Dropdown
            this.dropdown = document.createElement('div');
            this.dropdown.className = 'dynamic-select-dropdown';

            // Campo de busca
            const searchWrap = document.createElement('div');
            searchWrap.style.padding = '8px 10px';
            this.searchInput = document.createElement('input');
            this.searchInput.type = 'text';
            this.searchInput.placeholder = 'Pesquisar...';
            this.searchInput.style.width = '100%';
            this.searchInput.style.padding = '8px 10px';
            this.searchInput.style.border = '1px solid var(--ds-border-color)';
            this.searchInput.style.borderRadius = '6px';
            searchWrap.appendChild(this.searchInput);

            // Lista de opções
            this.optionsList = document.createElement('ul');
            this.optionsList.className = 'dynamic-select-options';
            this.optionsList.style.listStyle = 'none';
            this.optionsList.style.margin = '0';
            this.optionsList.style.padding = '6px 0';

            this.dropdown.appendChild(searchWrap);
            this.dropdown.appendChild(this.optionsList);

            // Inserir UI
            this.selectEl.style.display = 'none';
            this.selectEl.parentNode.insertBefore(this.container, this.selectEl);
            this.container.appendChild(this.input);
            this.container.appendChild(this.dropdown);
        }

        _bindEvents() {
            // Toggle
            this.input.addEventListener('click', () => this._toggleDropdown());
            this.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this._toggleDropdown();
                }
            });

            // Busca
            this.searchInput.addEventListener('input', () => this._filterOptions());

            // Seleção por delegação: garante cliques mesmo após re-render
            this.optionsList.addEventListener('click', (e) => {
                const li = e.target.closest('.dynamic-select-option');
                if (!li) return;
                const idx = Array.from(this.optionsList.children).indexOf(li);
                const item = this.filteredData[idx];
                if (item) this._toggleItem(item);
            });

            // Clique fora
            document.addEventListener('click', (e) => {
                if (!this.container.contains(e.target)) {
                    this._closeDropdown();
                }
            });
        }

        _toggleDropdown() {
            if (this.isOpen) this._closeDropdown(); else this._openDropdown();
        }

        _openDropdown() {
            this.dropdown.classList.add('show');
            this.container.classList.add('open');
            this.isOpen = true;
            this.searchInput.focus();
            this._filterOptions();

            // Efeito
            this.input.style.borderColor = '#4a90e2';
            this.input.style.boxShadow = '0 0 0 3px rgba(74, 144, 226, 0.25)';
        }

        _closeDropdown() {
            this.container.classList.remove('open');
            this.isOpen = false;
            this.searchInput.value = '';
            this._filterOptions();
            this.dropdown.classList.remove('show');
            this.input.style.borderColor = '';
            this.input.style.boxShadow = '';
        }

        _filterOptions() {
            const q = (this.searchInput.value || '').toLowerCase();
            this.filteredData = this.allItems.filter(item => item.text.toLowerCase().includes(q));
            this._renderOptions();
        }

        _renderOptions() {
            this.optionsList.innerHTML = '';

            if (this.filteredData.length === 0) {
                const empty = document.createElement('li');
                empty.className = 'dynamic-select-option empty';
                empty.textContent = 'Nenhum resultado encontrado';
                this.optionsList.appendChild(empty);
                return;
            }

            this.filteredData.forEach((item, idx) => {
                const li = document.createElement('li');
                li.className = 'dynamic-select-option';

                // Montar conteúdo por tema
                const content = document.createElement('div');
                content.className = 'ds-option-content';

                if (this.theme === 'avatar') {
                    const avatar = document.createElement('div');
                    avatar.className = 'ds-avatar';
                    content.appendChild(avatar);
                } else if (this.theme === 'checkbox') {
                    const dot = document.createElement('span');
                    dot.className = 'ds-dot';
                    content.appendChild(dot);
                }

                const texts = document.createElement('div');
                texts.className = 'ds-texts';
                const primary = document.createElement('span');
                primary.className = 'ds-primary';
                primary.textContent = item.text;
                texts.appendChild(primary);
                content.appendChild(texts);

                let right;
                if (this.theme === 'checkbox') {
                    right = document.createElement('span');
                    right.className = 'ds-checkbox';
                } else {
                    right = document.createElement('span');
                    right.className = 'ds-check';
                }
                content.appendChild(right);

                li.appendChild(content);

                // Estado selecionado
                const isSelected = this.selectedItems.some(s => s.value === item.value);
                if (isSelected) li.classList.add('selected');

                // Delay de animação
                li.style.animationDelay = `${idx * 30}ms`;

                this.optionsList.appendChild(li);
            });
        }

        _toggleItem(item) {
            const idx = this.selectedItems.findIndex(s => s.value === item.value);
            if (idx > -1) this.selectedItems.splice(idx, 1); else this.selectedItems.push({ value: item.value, text: item.text });

            // Sincronizar com <select>
            Array.from(this.selectEl.options).forEach(opt => {
                opt.selected = this.selectedItems.some(s => s.value === opt.value);
            });

            // Atualizar UI
            this._updateSelectedDisplay();
            this._renderOptions();

            // Evento change
            this.selectEl.dispatchEvent(new Event('change'));
        }

        _updateSelectedDisplay() {
            // Limpar tags anteriores
            const prevTags = this.input.querySelectorAll('.dynamic-select-tag');
            prevTags.forEach(t => t.remove());

            if (this.selectedItems.length === 0) {
                // Remover qualquer placeholder existente antes de inserir um novo
                const existingPh = this.input.querySelector('.ds-placeholder');
                if (existingPh) existingPh.remove();
                const placeholder = document.createElement('span');
                placeholder.className = 'ds-placeholder';
                placeholder.style.color = 'var(--ds-muted)';
                placeholder.textContent = 'Selecione...';
                this.input.insertBefore(placeholder, this.arrow);
            } else {
                // Remover placeholder
                const ph = this.input.querySelector('.ds-placeholder');
                if (ph) ph.remove();

                this.selectedItems.forEach(sel => {
                    const tag = document.createElement('span');
                    tag.className = 'dynamic-select-tag';
                    tag.textContent = sel.text;
                    this.input.insertBefore(tag, this.arrow);
                });
            }
        }

        _syncFromSelect() {
            this.selectedItems = Array.from(this.selectEl.selectedOptions || []).map(o => ({ value: o.value, text: o.textContent }));
        }
    }

    // Inicializador para o dashboard
    window.initDashboardDynamicSelects = function(themeMap = {}) {
        const targets = [
            { id: 'dashFilterProvincias', theme: themeMap.provincias || 'default' },
            { id: 'dashFilterPolos', theme: themeMap.polos || 'avatar' },
            { id: 'dashFilterSectores', theme: themeMap.sectores || 'default' },
            { id: 'dashFilterEstados', theme: themeMap.estados || 'checkbox' }
        ];

        window.__dashboardDynamicSelects = window.__dashboardDynamicSelects || {};

        targets.forEach(t => {
            const el = document.getElementById(t.id);
            if (!el) return;
            if (window.__dashboardDynamicSelects[t.id]) return; // evitar reinicialização
            el.setAttribute('data-theme', t.theme);
            window.__dashboardDynamicSelects[t.id] = new DynamicSelect(el, { theme: t.theme });
        });
    };
})();