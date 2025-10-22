/**
 * Empresas Manager
 * Módulo para gestão de empresas
 */

class EmpresasManager {
    constructor(app) {
        this.app = app;
        this.currentPage = 1;
        this.currentFilters = {};
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Botão de adicionar empresa
        const addEmpresaBtn = document.getElementById("addEmpresaBtn");
        if (addEmpresaBtn) {
            addEmpresaBtn.addEventListener("click", () => this.showEmpresaModal());
        }

        // Filtros
        const filterEmpresaPolo = document.getElementById("filterEmpresaPolo");
        const filterEmpresaSector = document.getElementById("filterEmpresaSector");
        const filterEmpresaStatus = document.getElementById("filterEmpresaStatus");
        const applyEmpresaFiltersBtn = document.getElementById("applyEmpresaFiltersBtn");

        if (applyEmpresaFiltersBtn) {
            applyEmpresaFiltersBtn.addEventListener("click", () => {
                this.currentFilters = {
                    polo_id: filterEmpresaPolo.value,
                    sector_id: filterEmpresaSector.value,
                    status: filterEmpresaStatus.value
                };
                this.currentPage = 1;
                this.loadEmpresas();
            });
        }
    }

    async loadEmpresas() {
        this.app.showLoading();
        try {
            const params = { page: this.currentPage, ...this.currentFilters };
            const response = await this.app.apiRequest("empresas", { params });
            this.displayEmpresas(response.data);
            this.setupPagination(response.pagination);
        } catch (error) {
            console.error("Erro ao carregar empresas:", error);
            this.app.showNotification("Erro ao carregar empresas.", "error");
        } finally {
            this.app.hideLoading();
        }
    }

    displayEmpresas(empresas) {
        const empresasTableBody = document.getElementById("empresasTableBody");
        if (!empresasTableBody) return;

        empresasTableBody.innerHTML = "";

        if (empresas.length === 0) {
            empresasTableBody.innerHTML = `<tr><td colspan="8" class="no-data">Nenhuma empresa encontrada.</td></tr>`;
            return;
        }

        empresas.forEach(empresa => {
            const row = empresasTableBody.insertRow();
            row.innerHTML = `
                <td>${empresa.id}</td>
                <td>${empresa.polo_nome}</td>
                <td>${empresa.nome_lote || "N/A"}</td>
                <td>${empresa.nome_empresa}</td>
                <td>${empresa.nif || "N/A"}</td>
                <td>${empresa.sector_nome || "N/A"}</td>
                <td><span class="status-badge status-${empresa.status}">${this.getStatusText(empresa.status)}</span></td>
                <td class="actions">
                    <button class="btn btn-info btn-sm" onclick="pdiApp.empresasManager.viewEmpresa(${empresa.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="pdiApp.empresasManager.editEmpresa(${empresa.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="pdiApp.empresasManager.deleteEmpresa(${empresa.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
        });
    }

    setupPagination(pagination) {
        const empresasPagination = document.getElementById("empresasPagination");
        if (!empresasPagination) return;

        empresasPagination.innerHTML = "";

        for (let i = 1; i <= pagination.pages; i++) {
            const button = document.createElement("button");
            button.textContent = i;
            button.classList.add("btn");
            if (i === pagination.page) {
                button.classList.add("active");
            }
            button.addEventListener("click", () => {
                this.currentPage = i;
                this.loadEmpresas();
            });
            empresasPagination.appendChild(button);
        }
    }

    async showEmpresaModal(empresa = null) {
        const polos = await this.app.apiRequest("polos");
        const lotes = await this.app.apiRequest("lotes");
        const sectores = await this.app.apiRequest("sectores");

        let poloOptions = 
            `<option value="">Selecione um Polo</option>` +
            polos.map(p => `<option value="${p.id}">${p.nome}</option>`).join("");

        let loteOptions = 
            `<option value="">Nenhum</option>` +
            lotes.map(l => `<option value="${l.id}">${l.nome_lote} (${l.polo_nome})</option>`).join("");

        let sectorOptions = 
            `<option value="">Selecione um Sector</option>` +
            sectores.map(s => `<option value="${s.id}">${s.nome}</option>`).join("");

        const statusOptions = [
            { value: "ativa", text: "Ativa" },
            { value: "inativa", text: "Inativa" },
            { value: "em_implementacao", text: "Em Implementação" },
            { value: "em_expansao", text: "Em Expansão" }
        ].map(s => `<option value="${s.value}">${s.text}</option>`).join("");

        const isEdit = empresa !== null;
        const title = isEdit ? "Editar Empresa" : "Adicionar Nova Empresa";
        const formContent = `
            <form id="empresaForm">
                <div class="form-group">
                    <label for="polo_id">Polo de Desenvolvimento Industrial:</label>
                    <select id="polo_id" name="polo_id" required>
                        ${poloOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label for="lote_id">Lote (Opcional):</label>
                    <select id="lote_id" name="lote_id">
                        ${loteOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label for="nome_empresa">Nome da Empresa:</label>
                    <input type="text" id="nome_empresa" name="nome_empresa" value="${empresa?.nome_empresa || ""}" required>
                </div>
                <div class="form-group">
                    <label for="nif">NIF:</label>
                    <input type="text" id="nif" name="nif" value="${empresa?.nif || ""}">
                </div>
                <div class="form-group">
                    <label for="sector_id">Sector:</label>
                    <select id="sector_id" name="sector_id">
                        ${sectorOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label for="atividade_principal">Atividade Principal:</label>
                    <textarea id="atividade_principal" name="atividade_principal">${empresa?.atividade_principal || ""}</textarea>
                </div>
                <div class="form-group">
                    <label for="num_funcionarios">Número de Funcionários:</label>
                    <input type="number" id="num_funcionarios" name="num_funcionarios" value="${empresa?.num_funcionarios || ""}">
                </div>
                <div class="form-group">
                    <label for="contacto_principal">Contacto Principal:</label>
                    <input type="text" id="contacto_principal" name="contacto_principal" value="${empresa?.contacto_principal || ""}">
                </div>
                <div class="form-group">
                    <label for="email_principal">Email Principal:</label>
                    <input type="email" id="email_principal" name="email_principal" value="${empresa?.email_principal || ""}">
                </div>
                <div class="form-group">
                    <label for="telefone_principal">Telefone Principal:</label>
                    <input type="text" id="telefone_principal" name="telefone_principal" value="${empresa?.telefone_principal || ""}">
                </div>
                <div class="form-group">
                    <label for="website">Website:</label>
                    <input type="url" id="website" name="website" value="${empresa?.website || ""}">
                </div>
                <div class="form-group">
                    <label for="data_instalacao">Data de Instalação:</label>
                    <input type="date" id="data_instalacao" name="data_instalacao" value="${empresa?.data_instalacao || ""}">
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        ${statusOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label for="observacoes">Observações:</label>
                    <textarea id="observacoes" name="observacoes">${empresa?.observacoes || ""}</textarea>
                </div>
            </form>
        `;

        this.app.showModal(title, formContent, async () => {
            const formData = new FormData(document.getElementById("empresaForm"));
            const data = Object.fromEntries(formData.entries());
            
            this.app.showLoading();
            try {
                if (isEdit) {
                    await this.app.apiRequest(`empresas/${empresa.id}`, { method: "PUT", body: JSON.stringify(data) });
                    this.app.showNotification("Empresa atualizada com sucesso!", "success");
                } else {
                    await this.app.apiRequest("empresas", { method: "POST", body: JSON.stringify(data) });
                    this.app.showNotification("Empresa adicionada com sucesso!", "success");
                }
                this.app.hideModal("dynamicModal");
                this.loadEmpresas();
                this.app.loadDashboardData(); // Atualizar dashboard
                this.app.mapaManager.loadPolosOnMap(); // Atualizar mapa
            } catch (error) {
                console.error("Erro ao salvar empresa:", error);
                this.app.showNotification(`Erro ao salvar empresa: ${error.message}`, "error");
            } finally {
                this.app.hideLoading();
            }
        });

        if (isEdit) {
            document.getElementById("polo_id").value = empresa.polo_id;
            document.getElementById("lote_id").value = empresa.lote_id || "";
            document.getElementById("sector_id").value = empresa.sector_id || "";
            document.getElementById("status").value = empresa.status;
        }
    }

    async viewEmpresa(id) {
        this.app.showLoading();
        try {
            const empresa = await this.app.apiRequest(`empresas/${id}`);
            const content = `
                <div class="info-grid">
                    <div class="info-item"><label>ID:</label><span>${empresa.id}</span></div>
                    <div class="info-item"><label>Nome da Empresa:</label><span>${empresa.nome_empresa}</span></div>
                    <div class="info-item"><label>Polo:</label><span>${empresa.polo_nome}</span></div>
                    <div class="info-item"><label>Lote:</label><span>${empresa.nome_lote || "N/A"}</span></div>
                    <div class="info-item"><label>NIF:</label><span>${empresa.nif || "N/A"}</span></div>
                    <div class="info-item"><label>Sector:</label><span>${empresa.sector_nome || "N/A"}</span></div>
                    <div class="info-item"><label>Atividade Principal:</label><span>${empresa.atividade_principal || "N/A"}</span></div>
                    <div class="info-item"><label>Número de Funcionários:</label><span>${empresa.num_funcionarios || "N/A"}</span></div>
                    <div class="info-item"><label>Contacto Principal:</label><span>${empresa.contacto_principal || "N/A"}</span></div>
                    <div class="info-item"><label>Email Principal:</label><span>${empresa.email_principal || "N/A"}</span></div>
                    <div class="info-item"><label>Telefone Principal:</label><span>${empresa.telefone_principal || "N/A"}</span></div>
                    <div class="info-item"><label>Website:</label><span>${empresa.website || "N/A"}</span></div>
                    <div class="info-item"><label>Data de Instalação:</label><span>${empresa.data_instalacao || "N/A"}</span></div>
                    <div class="info-item"><label>Status:</label><span class="status-badge status-${empresa.status}">${this.getStatusText(empresa.status)}</span></div>
                    <div class="info-item"><label>Observações:</label><span>${empresa.observacoes || "N/A"}</span></div>
                    <div class="info-item"><label>Criado em:</label><span>${new Date(empresa.created_at).toLocaleString()}</span></div>
                    <div class="info-item"><label>Última Atualização:</label><span>${new Date(empresa.updated_at).toLocaleString()}</span></div>
                </div>
            `;
            this.app.showModal(`Detalhes da Empresa: ${empresa.nome_empresa}`, content, null, true);
        } catch (error) {
            console.error("Erro ao carregar detalhes da empresa:", error);
            this.app.showNotification("Erro ao carregar detalhes da empresa.", "error");
        } finally {
            this.app.hideLoading();
        }
    }

    async editEmpresa(id) {
        this.app.showLoading();
        try {
            const empresa = await this.app.apiRequest(`empresas/${id}`);
            this.showEmpresaModal(empresa);
        } catch (error) {
            console.error("Erro ao carregar empresa para edição:", error);
            this.app.showNotification("Erro ao carregar empresa para edição.", "error");
        } finally {
            this.app.hideLoading();
        }
    }

    async deleteEmpresa(id) {
        if (!confirm("Tem certeza que deseja eliminar esta empresa?")) return;

        this.app.showLoading();
        try {
            await this.app.apiRequest(`empresas/${id}`, { method: "DELETE" });
            this.app.showNotification("Empresa eliminada com sucesso!", "success");
            this.loadEmpresas();
            this.app.loadDashboardData(); // Atualizar dashboard
            this.app.mapaManager.loadPolosOnMap(); // Atualizar mapa
        } catch (error) {
            console.error("Erro ao eliminar empresa:", error);
            this.app.showNotification(`Erro ao eliminar empresa: ${error.message}`, "error");
        } finally {
            this.app.hideLoading();
        }
    }

    getStatusText(status) {
        const statusMap = {
            "ativa": "Ativa",
            "inativa": "Inativa",
            "em_implementacao": "Em Implementação",
            "em_expansao": "Em Expansão"
        };
        return statusMap[status] || status;
    }
}

// Inicializar quando a aplicação estiver pronta
document.addEventListener("DOMContentLoaded", () => {
    if (window.pdiApp) {
        window.empresasManager = new EmpresasManager(window.pdiApp);
    }
});


