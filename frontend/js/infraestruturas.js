/**
 * Gestor de Infraestruturas
 * Módulo para gestão CRUD de infraestruturas
 */

class InfraestruturasManager {
    constructor(app) {
        this.app = app;
        this.currentPage = 1;
        this.filters = {};
        
        this.init();
    }

    /**
     * Inicializa o gestor
     */
    init() {
        this.setupEventListeners();
    }

    /**
     * Configura os event listeners
     */
    setupEventListeners() {
        // Botão adicionar infraestrutura
        const addBtn = document.getElementById('addInfraestruturaBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                this.showAddInfraestruturaModal();
            });
        }

        // Filtros
        const filterElements = ['filterProvincia', 'filterPolo', 'filterSector'];
        filterElements.forEach(filterId => {
            const element = document.getElementById(filterId);
            if (element) {
                element.addEventListener('change', () => {
                    this.updateFilters();
                    this.loadInfraestruturas();
                });
            }
        });

        // Limpar filtros
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearFilters();
            });
        }
    }

    /**
     * Carrega as infraestruturas
     */
    async loadInfraestruturas(page = 1) {
        this.app.showLoading();
        
        try {
            const params = new URLSearchParams({
                page: page,
                ...this.filters
            });

            const response = await this.app.apiRequest(`infraestruturas?${params}`);
            
            this.displayInfraestruturas(response.data);
            this.updatePagination(response.pagination);
            
        } catch (error) {
            console.error('Erro ao carregar infraestruturas:', error);
            this.app.showNotification('Erro ao carregar infraestruturas', 'error');
        } finally {
            this.app.hideLoading();
        }
    }

    /**
     * Exibe as infraestruturas na tabela
     */
    displayInfraestruturas(infraestruturas) {
        const tbody = document.getElementById('infraestruturasTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!infraestruturas || infraestruturas.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem;">
                        Nenhuma infraestrutura encontrada
                    </td>
                </tr>
            `;
            return;
        }

        infraestruturas.forEach(infraestrutura => {
            const row = this.createInfraestruturaRow(infraestrutura);
            tbody.appendChild(row);
        });
    }

    /**
     * Cria uma linha da tabela para uma infraestrutura
     */
    createInfraestruturaRow(infraestrutura) {
        const row = document.createElement('tr');
        
        const statusClass = `status-${infraestrutura.estado_atual}`;
        const statusText = this.getEstadoText(infraestrutura.estado_atual);

        row.innerHTML = `
            <td>${infraestrutura.id}</td>
            <td>${infraestrutura.polo_nome || '-'}</td>
            <td>${infraestrutura.provincia_nome || '-'}</td>
            <td>${infraestrutura.municipio_nome || '-'}</td>
            <td>${infraestrutura.sector_nome || '-'}</td>
            <td>
                <span class="status-badge ${statusClass}">${statusText}</span>
            </td>
            <td>${this.app.formatDate(infraestrutura.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action btn-view" onclick="infraestruturasManager.viewInfraestrutura(${infraestrutura.id})" title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${this.app.currentUser?.permissoes?.update ? `
                        <button class="btn-action btn-edit" onclick="infraestruturasManager.editInfraestrutura(${infraestrutura.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                    ` : ''}
                    ${this.app.currentUser?.permissoes?.delete ? `
                        <button class="btn-action btn-delete" onclick="infraestruturasManager.deleteInfraestrutura(${infraestrutura.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;

        return row;
    }

    /**
     * Obtém o texto do estado
     */
    getEstadoText(estado) {
        const estadoMap = {
            'excelente': 'Excelente',
            'bom': 'Bom',
            'regular': 'Regular',
            'mau': 'Mau',
            'ruina': 'Ruína'
        };
        return estadoMap[estado] || estado;
    }

    /**
     * Atualiza a paginação
     */
    updatePagination(pagination) {
        const paginationContainer = document.getElementById('pagination');
        if (!paginationContainer || !pagination) return;

        paginationContainer.innerHTML = '';

        const { page, pages, total } = pagination;

        // Botão anterior
        if (page > 1) {
            const prevBtn = document.createElement('button');
            prevBtn.textContent = 'Anterior';
            prevBtn.addEventListener('click', () => {
                this.loadInfraestruturas(page - 1);
            });
            paginationContainer.appendChild(prevBtn);
        }

        // Números das páginas
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(pages, page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === page ? 'active' : '';
            pageBtn.addEventListener('click', () => {
                this.loadInfraestruturas(i);
            });
            paginationContainer.appendChild(pageBtn);
        }

        // Botão próximo
        if (page < pages) {
            const nextBtn = document.createElement('button');
            nextBtn.textContent = 'Próximo';
            nextBtn.addEventListener('click', () => {
                this.loadInfraestruturas(page + 1);
            });
            paginationContainer.appendChild(nextBtn);
        }

        // Informação da paginação
        const info = document.createElement('span');
        info.className = 'pagination-info';
        info.textContent = `Página ${page} de ${pages} (${total} registos)`;
        paginationContainer.appendChild(info);
    }

    /**
     * Atualiza os filtros
     */
    updateFilters() {
        this.filters = {};

        const filterProvincia = document.getElementById('filterProvincia');
        if (filterProvincia && filterProvincia.value) {
            this.filters.provincia_id = filterProvincia.value;
        }

        const filterPolo = document.getElementById('filterPolo');
        if (filterPolo && filterPolo.value) {
            this.filters.polo_id = filterPolo.value;
        }

        const filterSector = document.getElementById('filterSector');
        if (filterSector && filterSector.value) {
            this.filters.sector_id = filterSector.value;
        }

        this.currentPage = 1;
    }

    /**
     * Limpa os filtros
     */
    clearFilters() {
        document.getElementById('filterProvincia').value = '';
        document.getElementById('filterPolo').value = '';
        document.getElementById('filterSector').value = '';
        
        this.filters = {};
        this.currentPage = 1;
        this.loadInfraestruturas();
    }

    /**
     * Visualiza uma infraestrutura
     */
    async viewInfraestrutura(id) {
        try {
            const infraestrutura = await this.app.apiRequest(`infraestruturas/${id}`);
            this.showInfraestruturaModal(infraestrutura, 'view');
        } catch (error) {
            console.error('Erro ao carregar infraestrutura:', error);
            this.app.showNotification('Erro ao carregar infraestrutura', 'error');
        }
    }

    /**
     * Edita uma infraestrutura
     */
    async editInfraestrutura(id) {
        try {
            const infraestrutura = await this.app.apiRequest(`infraestruturas/${id}`);
            this.showInfraestruturaModal(infraestrutura, 'edit');
        } catch (error) {
            console.error('Erro ao carregar infraestrutura:', error);
            this.app.showNotification('Erro ao carregar infraestrutura', 'error');
        }
    }

    /**
     * Elimina uma infraestrutura
     */
    async deleteInfraestrutura(id) {
        if (!confirm('Tem certeza que deseja eliminar esta infraestrutura?')) {
            return;
        }

        try {
            await this.app.apiRequest(`infraestruturas/${id}`, {
                method: 'DELETE'
            });

            this.app.showNotification('Infraestrutura eliminada com sucesso', 'success');
            this.loadInfraestruturas(this.currentPage);
        } catch (error) {
            console.error('Erro ao eliminar infraestrutura:', error);
            this.app.showNotification('Erro ao eliminar infraestrutura', 'error');
        }
    }

    /**
     * Mostra o modal para adicionar infraestrutura
     */
    showAddInfraestruturaModal() {
        this.showInfraestruturaModal(null, 'add');
    }

    /**
     * Mostra o modal de infraestrutura
     */
    showInfraestruturaModal(infraestrutura, mode) {
        // Criar modal dinamicamente
        const modal = this.createInfraestruturaModal(infraestrutura, mode);
        document.body.appendChild(modal);
        
        // Mostrar modal
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);

        // Configurar event listeners do modal
        this.setupModalEventListeners(modal, infraestrutura, mode);
    }

    /**
     * Cria o modal de infraestrutura
     */
    createInfraestruturaModal(infraestrutura, mode) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'infraestruturaModal';

        const isReadOnly = mode === 'view';
        const title = mode === 'add' ? 'Nova Infraestrutura' : 
                     mode === 'edit' ? 'Editar Infraestrutura' : 
                     'Visualizar Infraestrutura';

        modal.innerHTML = `
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h2>${title}</h2>
                    <button class="modal-close" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="infraestruturaForm" class="infraestrutura-form">
                        <div class="form-sections">
                            <div class="form-section">
                                <h3>Localização</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="polo_id">Polo *</label>
                                        <select id="polo_id" name="polo_id" required ${isReadOnly ? 'disabled' : ''}>
                                            <option value="">Selecione um polo</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="provincia_id">Província *</label>
                                        <select id="provincia_id" name="provincia_id" required ${isReadOnly ? 'disabled' : ''}>
                                            <option value="">Selecione uma província</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="municipio_id">Município *</label>
                                        <select id="municipio_id" name="municipio_id" required ${isReadOnly ? 'disabled' : ''}>
                                            <option value="">Selecione um município</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="bairro_local">Bairro/Local</label>
                                        <input type="text" id="bairro_local" name="bairro_local" ${isReadOnly ? 'readonly' : ''}>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="latitude">Latitude</label>
                                        <input type="number" step="any" id="latitude" name="latitude" ${isReadOnly ? 'readonly' : ''}>
                                    </div>
                                    <div class="form-group">
                                        <label for="longitude">Longitude</label>
                                        <input type="number" step="any" id="longitude" name="longitude" ${isReadOnly ? 'readonly' : ''}>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Informações Gerais</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="sector_id">Sector</label>
                                        <select id="sector_id" name="sector_id" ${isReadOnly ? 'disabled' : ''}>
                                            <option value="">Selecione um sector</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="estado_atual">Estado Atual</label>
                                        <select id="estado_atual" name="estado_atual" ${isReadOnly ? 'disabled' : ''}>
                                            <option value="excelente">Excelente</option>
                                            <option value="bom" selected>Bom</option>
                                            <option value="regular">Regular</option>
                                            <option value="mau">Mau</option>
                                            <option value="ruina">Ruína</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="area">Área (m²)</label>
                                        <input type="number" step="0.01" id="area" name="area" ${isReadOnly ? 'readonly' : ''}>
                                    </div>
                                    <div class="form-group">
                                        <label for="ano_construcao">Ano de Construção</label>
                                        <input type="number" id="ano_construcao" name="ano_construcao" min="1900" max="2030" ${isReadOnly ? 'readonly' : ''}>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Contactos</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nome_representante">Nome do Representante</label>
                                        <input type="text" id="nome_representante" name="nome_representante" ${isReadOnly ? 'readonly' : ''}>
                                    </div>
                                    <div class="form-group">
                                        <label for="email_representante">Email do Representante</label>
                                        <input type="email" id="email_representante" name="email_representante" ${isReadOnly ? 'readonly' : ''}>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="telefone_representante">Telefone do Representante</label>
                                        <input type="tel" id="telefone_representante" name="telefone_representante" ${isReadOnly ? 'readonly' : ''}>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Observações</h3>
                                <div class="form-group">
                                    <label for="observacoes">Observações</label>
                                    <textarea id="observacoes" name="observacoes" rows="4" ${isReadOnly ? 'readonly' : ''}></textarea>
                                </div>
                            </div>
                        </div>

                        ${!isReadOnly ? `
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                                <button type="submit" class="btn btn-primary">
                                    ${mode === 'add' ? 'Criar' : 'Atualizar'}
                                </button>
                            </div>
                        ` : ''}
                    </form>
                </div>
            </div>
        `;

        return modal;
    }

    /**
     * Configura os event listeners do modal
     */
    setupModalEventListeners(modal, infraestrutura, mode) {
        // Fechar modal
        const closeButtons = modal.querySelectorAll('.modal-close');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.remove();
            });
        });

        // Fechar ao clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });

        // Carregar dados dos selects
        this.loadModalData(modal);

        // Preencher dados se for edição ou visualização
        if (infraestrutura) {
            this.populateModalForm(modal, infraestrutura);
        }

        // Submissão do formulário
        if (mode !== 'view') {
            const form = modal.querySelector('#infraestruturaForm');
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit(form, mode, infraestrutura?.id);
                modal.remove();
            });
        }
    }

    /**
     * Carrega dados para os selects do modal
     */
    async loadModalData(modal) {
        try {
            const [provincias, polos, sectores] = await Promise.all([
                this.app.apiRequest('provincias'),
                this.app.apiRequest('polos'),
                this.app.apiRequest('sectores')
            ]);

            this.populateModalSelect(modal, 'provincia_id', provincias, 'id', 'nome');
            this.populateModalSelect(modal, 'polo_id', polos, 'id', 'nome');
            this.populateModalSelect(modal, 'sector_id', sectores, 'id', 'nome');

            // Configurar mudança de província para carregar municípios
            const provinciaSelect = modal.querySelector('#provincia_id');
            if (provinciaSelect) {
                provinciaSelect.addEventListener('change', async () => {
                    const provinciaId = provinciaSelect.value;
                    if (provinciaId) {
                        const municipios = await this.app.apiRequest(`municipios/${provinciaId}`);
                        this.populateModalSelect(modal, 'municipio_id', municipios, 'id', 'nome');
                    } else {
                        this.clearModalSelect(modal, 'municipio_id');
                    }
                });
            }
        } catch (error) {
            console.error('Erro ao carregar dados do modal:', error);
        }
    }

    /**
     * Popula um select no modal
     */
    populateModalSelect(modal, selectId, data, valueField, textField) {
        const select = modal.querySelector(`#${selectId}`);
        if (!select || !data) return;

        // Manter a primeira opção
        const firstOption = select.querySelector('option');
        select.innerHTML = '';
        if (firstOption) {
            select.appendChild(firstOption);
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
     * Limpa um select no modal
     */
    clearModalSelect(modal, selectId) {
        const select = modal.querySelector(`#${selectId}`);
        if (!select) return;

        const firstOption = select.querySelector('option');
        select.innerHTML = '';
        if (firstOption) {
            select.appendChild(firstOption);
        }
    }

    /**
     * Preenche o formulário do modal com dados
     */
    async populateModalForm(modal, infraestrutura) {
        // Preencher campos básicos
        Object.keys(infraestrutura).forEach(key => {
            const field = modal.querySelector(`[name="${key}"]`);
            if (field && infraestrutura[key] !== null) {
                field.value = infraestrutura[key];
            }
        });

        // Carregar municípios se província estiver selecionada
        if (infraestrutura.provincia_id) {
            try {
                const municipios = await this.app.apiRequest(`municipios/${infraestrutura.provincia_id}`);
                this.populateModalSelect(modal, 'municipio_id', municipios, 'id', 'nome');
                
                // Selecionar município após carregar
                setTimeout(() => {
                    const municipioSelect = modal.querySelector('#municipio_id');
                    if (municipioSelect && infraestrutura.municipio_id) {
                        municipioSelect.value = infraestrutura.municipio_id;
                    }
                }, 100);
            } catch (error) {
                console.error('Erro ao carregar municípios:', error);
            }
        }
    }

    /**
     * Manipula a submissão do formulário
     */
    async handleFormSubmit(form, mode, id) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Converter valores vazios para null
        Object.keys(data).forEach(key => {
            if (data[key] === '') {
                data[key] = null;
            }
        });

        this.app.showLoading();

        try {
            if (mode === 'add') {
                await this.app.apiRequest('infraestruturas', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                this.app.showNotification('Infraestrutura criada com sucesso', 'success');
            } else if (mode === 'edit') {
                await this.app.apiRequest(`infraestruturas/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                this.app.showNotification('Infraestrutura atualizada com sucesso', 'success');
            }

            this.loadInfraestruturas(this.currentPage);
        } catch (error) {
            console.error('Erro ao salvar infraestrutura:', error);
            this.app.showNotification('Erro ao salvar infraestrutura', 'error');
        } finally {
            this.app.hideLoading();
        }
    }
}

// Inicializar quando a aplicação estiver pronta
document.addEventListener('DOMContentLoaded', () => {
    if (window.pdiApp) {
        window.infraestruturasManager = new InfraestruturasManager(window.pdiApp);
    }
});

// Adicionar estilos para o modal de infraestrutura
const modalStyles = document.createElement('style');
modalStyles.textContent = `
    .infraestrutura-form .form-sections {
        max-height: 60vh;
        overflow-y: auto;
        padding-right: 1rem;
    }
    
    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .form-section h3 {
        color: var(--primary-color);
        margin-bottom: 1rem;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .form-row:last-child {
        margin-bottom: 0;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-action {
        padding: 0.25rem 0.5rem;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        font-size: 0.875rem;
        transition: var(--transition);
    }
    
    .btn-view {
        background: var(--info-color);
        color: white;
    }
    
    .btn-edit {
        background: var(--warning-color);
        color: white;
    }
    
    .btn-delete {
        background: var(--danger-color);
        color: white;
    }
    
    .btn-action:hover {
        opacity: 0.8;
        transform: translateY(-1px);
    }
    
    .pagination-info {
        margin-left: 1rem;
        color: var(--gray-600);
        font-size: 0.875rem;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
`;
document.head.appendChild(modalStyles);

