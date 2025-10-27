/**
 * Gestão de Usuários - Frontend
 * Sistema PDI Angola
 */

class UsuariosManager {
    constructor() {
        this.apiBase = window.location.origin + '/pdi_angola/backend/api.php';
        this.currentUser = null;
        this.usuarios = [];
        this.niveisAcesso = [];
        this.pagination = null;
        this.currentPage = 1;
        this.searchTerm = '';
        this.init();
    }

    async init() {
        // Verificar se o usuário está autenticado e é administrador
        await this.checkAuth();
        
        if (this.currentUser && this.isAdmin()) {
            await this.loadNiveisAcesso();
            await this.loadUsuarios();
            this.setupEventListeners();
        } else {
            this.showAccessDenied();
        }
    }

    async checkAuth() {
        try {
            const response = await fetch(`${this.apiBase}/auth/check`, {
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                this.currentUser = data.user;
            }
        } catch (error) {
            console.error('Erro ao verificar autenticação:', error);
        }
    }

    isAdmin() {
        return this.currentUser && 
               this.currentUser.nivel_nome && 
               this.currentUser.nivel_nome.toLowerCase() === 'administrador';
    }

    async loadUsuarios(page = 1, search = '') {
        try {
            this.showLoading(true);
            
            // Construir URL com parâmetros de paginação e busca
            const params = new URLSearchParams({
                page: page,
                limit: 20
            });
            
            if (search.trim()) {
                params.append('search', search.trim());
            }
            
            const response = await fetch(`${this.apiBase}/admin/users?${params}`, {
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                this.usuarios = data.users;
                this.pagination = data.pagination;
                this.currentPage = page;
                this.searchTerm = search;
                this.renderUsuarios();
                this.renderPagination();
            } else {
                throw new Error('Erro ao carregar usuários');
            }
        } catch (error) {
            console.error('Erro ao carregar usuários:', error);
            this.showAlert('Erro ao carregar usuários: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadNiveisAcesso() {
        try {
            const response = await fetch(`${this.apiBase}/admin/access-levels`, {
                credentials: 'include'
            });

            if (response.ok) {
                this.niveisAcesso = await response.json();
                this.populateAccessLevels();
            }
        } catch (error) {
            console.error('Erro ao carregar níveis de acesso:', error);
        }
    }

    renderUsuarios() {
        const container = document.getElementById('usuariosTableContainer');
        if (!container) return;

        if (this.usuarios.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>Nenhum usuário encontrado</h3>
                    <p>Não há usuários cadastrados no sistema.</p>
                </div>
            `;
            return;
        }

        const table = `
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Nível de Acesso</th>
                            <th>Status</th>
                            <th>Último Login</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.usuarios.map(usuario => this.renderUsuarioRow(usuario)).join('')}
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = table;
    }

    renderPagination() {
        const paginationContainer = document.getElementById('paginationContainer');
        if (!paginationContainer || !this.pagination) return;

        const { current_page, total_pages, total_records, has_prev, has_next } = this.pagination;

        if (total_pages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let paginationHTML = `
            <div class="pagination-info">
                <span>Mostrando ${this.usuarios.length} de ${total_records} usuários</span>
            </div>
            <div class="pagination-controls">
        `;

        // Botão anterior
        if (has_prev) {
            paginationHTML += `
                <button class="btn btn-sm btn-outline-primary" onclick="usuariosManager.loadUsuarios(${current_page - 1}, '${this.searchTerm}')">
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
            `;
        }

        // Números das páginas
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === current_page ? 'btn-primary' : 'btn-outline-primary';
            paginationHTML += `
                <button class="btn btn-sm ${activeClass}" onclick="usuariosManager.loadUsuarios(${i}, '${this.searchTerm}')">
                    ${i}
                </button>
            `;
        }

        // Botão próximo
        if (has_next) {
            paginationHTML += `
                <button class="btn btn-sm btn-outline-primary" onclick="usuariosManager.loadUsuarios(${current_page + 1}, '${this.searchTerm}')">
                    Próximo <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }

        paginationHTML += '</div>';
        paginationContainer.innerHTML = paginationHTML;
    }

    renderUsuarioRow(usuario) {
        const statusClass = usuario.ativo ? 'status-active' : 'status-inactive';
        const statusText = usuario.ativo ? 'Ativo' : 'Inativo';
        const ultimoLogin = usuario.ultimo_login ? 
            new Date(usuario.ultimo_login).toLocaleString('pt-PT') : 'Nunca';
        const criadoEm = new Date(usuario.created_at).toLocaleString('pt-PT');

        return `
            <tr>
                <td>${usuario.id}</td>
                <td>${this.escapeHtml(usuario.nome)}</td>
                <td>${this.escapeHtml(usuario.email)}</td>
                <td>${this.escapeHtml(usuario.nivel_nome || 'N/A')}</td>
                <td><span class="${statusClass}">${statusText}</span></td>
                <td>${ultimoLogin}</td>
                <td>${criadoEm}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-primary" onclick="usuariosManager.editUsuario(${usuario.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm ${usuario.ativo ? 'btn-warning' : 'btn-success'}" 
                                onclick="usuariosManager.toggleStatus(${usuario.id})" 
                                title="${usuario.ativo ? 'Desativar' : 'Ativar'}">
                            <i class="fas fa-${usuario.ativo ? 'ban' : 'check'}"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    async toggleStatus(userId) {
        const usuario = this.usuarios.find(u => u.id === userId);
        if (!usuario) return;

        const action = usuario.ativo ? 'desativar' : 'ativar';
        
        if (!confirm(`Tem certeza que deseja ${action} este usuário?`)) {
            return;
        }

        try {
            const response = await fetch(`${this.apiBase}/admin/${userId}/toggle-status`, {
                method: 'PATCH',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': await this.getCsrfToken()
                }
            });

            if (response.ok) {
                const result = await response.json();
                this.showAlert(result.message || `Usuário ${action} com sucesso!`, 'success');
                await this.loadUsuarios();
            } else {
                throw new Error('Erro ao alterar status do usuário');
            }
        } catch (error) {
            console.error('Erro ao alterar status:', error);
            this.showAlert('Erro ao alterar status: ' + error.message, 'error');
        }
    }

    async editUsuario(userId) {
        try {
            const response = await fetch(`${this.apiBase}/admin/${userId}`, {
                credentials: 'include'
            });

            if (response.ok) {
                const usuario = await response.json();
                this.showEditModal(usuario);
            } else {
                throw new Error('Erro ao carregar dados do usuário');
            }
        } catch (error) {
            console.error('Erro ao carregar usuário:', error);
            this.showAlert('Erro ao carregar usuário: ' + error.message, 'error');
        }
    }

    showEditModal(usuario) {
        const modal = document.getElementById('editUsuarioModal');
        if (!modal) return;

        // Garantir que níveis de acesso estejam populados
        this.populateAccessLevels();

        // Preencher formulário
        document.getElementById('editUserId').value = usuario.id;
        document.getElementById('editNome').value = usuario.nome;
        document.getElementById('editEmail').value = usuario.email;
        document.getElementById('editNivelAcesso').value = usuario.nivel_acesso_id;
        document.getElementById('editAtivo').value = usuario.ativo ? '1' : '0';

        modal.style.display = 'block';
    }

    populateAccessLevels() {
        // Preenche selects de nível de acesso nos modais de edição e criação
        const selects = [
            document.getElementById('editNivelAcesso'),
            document.getElementById('createNivelAcesso'),
        ].filter(Boolean);

        if (!this.niveisAcesso || !Array.isArray(this.niveisAcesso)) return;

        selects.forEach(select => {
            // Limpa opções
            while (select.firstChild) select.removeChild(select.firstChild);
            // Opção padrão
            const optDefault = document.createElement('option');
            optDefault.value = '';
            optDefault.textContent = 'Selecione um nível';
            select.appendChild(optDefault);
            // Popula
            this.niveisAcesso.forEach(level => {
                const opt = document.createElement('option');
                opt.value = level.id;
                opt.textContent = level.nome;
                select.appendChild(opt);
            });
        });
    }

    showCreateModal() {
        if (!this.isAdmin()) {
            this.showAccessDenied();
            return;
        }
        const modal = document.getElementById('createUsuarioModal');
        if (!modal) {
            console.error('Modal não encontrado');
            alert('Erro ao abrir o modal de criação de usuário');
            return;
        }
        // Reset form
        const form = document.getElementById('createUsuarioForm');
        if (form) form.reset();
        // Garantir níveis de acesso carregados
        if (!this.niveisAcesso || !this.niveisAcesso.length) {
            this.loadNiveisAcesso().then(() => this.populateAccessLevels());
        } else {
            this.populateAccessLevels();
        }
        // Forçar exibição do modal com estilo flex
        modal.style.display = 'flex';
        console.log('Modal aberto:', modal);
    }

    closeCreateModal() {
        const modal = document.getElementById('createUsuarioModal');
        if (modal) modal.style.display = 'none';
    }

    async saveNewUsuario(event) {
        event.preventDefault();
        if (!this.isAdmin()) {
            this.showAccessDenied();
            return;
        }
        const form = document.getElementById('createUsuarioForm');
        if (!form) return;

        const nome = document.getElementById('createNome')?.value?.trim();
        const email = document.getElementById('createEmail')?.value?.trim();
        const senha = document.getElementById('createSenha')?.value || '';
        const nivelAcessoId = document.getElementById('createNivelAcesso')?.value;
        const ativo = document.getElementById('createAtivo')?.value;

        if (!nome || !email || !senha || !nivelAcessoId || ativo === undefined) {
            this.showAlert('Por favor, preencha todos os campos obrigatórios.', 'warning');
            return;
        }

        try {
            this.showLoading(true);
            const csrfToken = await this.getCsrfToken();
            const response = await fetch(`${this.apiBase}/admin/users`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken || '',
                },
                credentials: 'include',
                body: JSON.stringify({
                    nome,
                    email,
                    senha,
                    nivel_acesso_id: parseInt(nivelAcessoId, 10),
                    ativo: parseInt(ativo, 10),
                }),
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                throw new Error(err.message || `Erro ao criar usuário: ${response.status}`);
            }

            // Sucesso
            this.showAlert('Usuário criado com sucesso.', 'success');
            this.closeCreateModal();
            await this.loadUsuarios(this.currentPage, this.searchTerm);
        } catch (error) {
            console.error('Erro ao criar usuário:', error);
            this.showAlert(error.message || 'Erro ao criar usuário.', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    setupEventListeners() {
        // Campo de busca
        const searchInput = document.getElementById('searchUsuarios');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.loadUsuarios(1, e.target.value);
                }, 500); // Debounce de 500ms
            });
        }

        // Botão de limpar busca
        const clearSearchBtn = document.getElementById('clearSearch');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                const searchInput = document.getElementById('searchUsuarios');
                if (searchInput) {
                    searchInput.value = '';
                    this.loadUsuarios(1, '');
                }
            });
        }

        // Botão de atualizar
        const refreshBtn = document.getElementById('refreshUsuarios');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', async () => {
                if (!this.isAdmin()) {
                    this.showAccessDenied();
                    return;
                }
                this.loadUsuarios(this.currentPage, this.searchTerm);
            });
        }

        // Botão Novo Usuário
        const newUsuarioBtn = document.getElementById('newUsuarioBtn');
        if (newUsuarioBtn) {
            newUsuarioBtn.addEventListener('click', () => this.showCreateModal());
        }

        // Submit criação
        const createForm = document.getElementById('createUsuarioForm');
        if (createForm) {
            createForm.addEventListener('submit', (e) => this.saveNewUsuario(e));
        }

        // Fechar modal criação
        const closeCreateModalBtn = document.getElementById('closeCreateModal');
        if (closeCreateModalBtn) {
            closeCreateModalBtn.addEventListener('click', () => this.closeCreateModal());
        }

        // Fechar modal ao clicar fora (criação)
        const createModal = document.getElementById('createUsuarioModal');
        if (createModal) {
            createModal.addEventListener('click', (event) => {
                if (event.target === createModal) this.closeCreateModal();
            });
        }

        // Formulário de edição
        const editForm = document.getElementById('editUsuarioForm');
        if (editForm) {
            editForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveUsuario();
            });
        }

        // Fechar modal
        const closeBtn = document.getElementById('closeEditModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeEditModal());
        }

        // Fechar modal clicando fora
        const modal = document.getElementById('editUsuarioModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeEditModal();
                }
            });
        }
    }

    showLoading(show) {
        const loader = document.getElementById('usuariosLoader');
        const container = document.getElementById('usuariosTableContainer');
        
        if (loader && container) {
            if (show) {
                loader.style.display = 'block';
                container.style.display = 'none';
            } else {
                loader.style.display = 'none';
                container.style.display = 'block';
            }
        }
    }

    showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('usuariosAlerts');
        if (!alertContainer) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            ${message}
            <button class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        alertContainer.appendChild(alert);

        // Auto-remover após 5 segundos
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }

    showAccessDenied() {
        const container = document.getElementById('usuarios');
        if (container) {
            container.innerHTML = `
                <div class="access-denied">
                    <i class="fas fa-lock"></i>
                    <h3>Acesso Negado</h3>
                    <p>Você não tem permissão para acessar esta funcionalidade.</p>
                    <p>Apenas administradores podem visualizar e gerenciar usuários.</p>
                </div>
            `;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    async getCsrfToken() {
        try {
            const response = await fetch(`${this.apiBase}/csrf/token`, {
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                return data.token;
            }
            return null;
        } catch (error) {
            console.error('Erro ao obter token CSRF:', error);
            return null;
        }
    }
}

// Inicializar quando a página carregar
let usuariosManager;

document.addEventListener('DOMContentLoaded', () => {
    // Aguardar um pouco para garantir que outros scripts carregaram
    setTimeout(() => {
        usuariosManager = new UsuariosManager();
    }, 100);
});