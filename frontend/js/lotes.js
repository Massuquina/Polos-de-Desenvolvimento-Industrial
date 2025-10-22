/**
 * Lotes Manager
 * Módulo para gestão de lotes
 */

class LotesManager {
    constructor(app) {
        this.app = app;
        this.currentPage = 1;
        this.currentFilters = {};
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Botão de adicionar lote
        const addLoteBtn = document.getElementById("addLoteBtn");
        if (addLoteBtn) {
            addLoteBtn.addEventListener("click", () => this.showLoteModal());
        }

        // Filtros
        const filterLotePolo = document.getElementById("filterLotePolo");
        const filterLoteStatus = document.getElementById("filterLoteStatus");
        const applyLoteFiltersBtn = document.getElementById("applyLoteFiltersBtn");

        if (applyLoteFiltersBtn) {
            applyLoteFiltersBtn.addEventListener("click", () => {
                this.currentFilters = {
                    polo_id: filterLotePolo.value,
                    status: filterLoteStatus.value
                };
                this.currentPage = 1;
                this.loadLotes();
            });
        }
    }

    async loadLotes() {
        this.app.showLoading();
        try {
            const params = { page: this.currentPage, ...this.currentFilters };
            const response = await this.app.apiRequest("lotes", { params });
            this.displayLotes(response.data);
            this.setupPagination(response.pagination);
        } catch (error) {
            console.error("Erro ao carregar lotes:", error);
            this.app.showNotification("Erro ao carregar lotes.", "error");
        } finally {
            this.app.hideLoading();
        }
    }

    displayLotes(lotes) {
        const lotesTableBody = document.getElementById("lotesTableBody");
        if (!lotesTableBody) return;

        lotesTableBody.innerHTML = "";

        if (lotes.length === 0) {
            lotesTableBody.innerHTML = `<tr><td colspan="7" class="no-data">Nenhum lote encontrado.</td></tr>`;
            return;
        }

        lotes.forEach(lote => {
            const row = lotesTableBody.insertRow();
            row.innerHTML = `
                <td>${lote.id}</td>
                <td>${lote.nome_lote}</td>
                <td>${lote.polo_nome}</td>
                <td>${lote.area_m2 ? `${lote.area_m2} m²` : "N/A"}</td>
                <td><span class="status-badge status-${lote.status}">${this.getStatusText(lote.status)}</span></td>
                <td>${lote.descricao || "N/A"}</td>
                <td class="actions">
                    <button class="btn btn-info btn-sm" onclick="pdiApp.lotesManager.viewLote(${lote.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="pdiApp.lotesManager.editLote(${lote.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="pdiApp.lotesManager.deleteLote(${lote.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
        });
    }

    setupPagination(pagination) {
        const lotesPagination = document.getElementById("lotesPagination");
        if (!lotesPagination) return;

        lotesPagination.innerHTML = "";

        for (let i = 1; i <= pagination.pages; i++) {
            const button = document.createElement("button");
            button.textContent = i;
            button.classList.add("btn");
            if (i === pagination.page) {
                button.classList.add("active");
            }
            button.addEventListener("click", () => {
                this.currentPage = i;
                this.loadLotes();
            });
            lotesPagination.appendChild(button);
        }
    }

    async showLoteModal(lote = null) {
        const polos = await this.app.apiRequest("polos");
        let poloOptions = 
            `<option value="">Selecione um Polo</option>` +
            polos.map(p => `<option value="${p.id}">${p.nome}</option>`).join("");

        const statusOptions = [
            { value: "disponivel", text: "Disponível" },
            { value: "ocupado", text: "Ocupado" },
            { value: "reservado", text: "Reservado" },
            { value: "em_implementacao", text: "Em Implementação" }
        ].map(s => `<option value="${s.value}">${s.text}</option>`).join("");

        const isEdit = lote !== null;
        const title = isEdit ? "Editar Lote" : "Adicionar Novo Lote";
        const formContent = `
            <form id="loteForm">
                <div class="form-group">
                    <label for="polo_id">Polo de Desenvolvimento Industrial:</label>
                    <select id="polo_id" name="polo_id" required>
                        ${poloOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label for="nome_lote">Nome do Lote:</label>
                    <input type="text" id="nome_lote" name="nome_lote" value="${lote?.nome_lote || ""}" required>
                </div>
                <div class="form-group">
                    <label for="area_m2">Área (m²):</label>
                    <input type="number" id="area_m2" name="area_m2" value="${lote?.area_m2 || ""}">
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        ${statusOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao">${lote?.descricao || ""}</textarea>
                </div>
            </form>
        `;

        this.app.showModal(title, formContent, async () => {
            const formData = new FormData(document.getElementById("loteForm"));
            const data = Object.fromEntries(formData.entries());
            
            this.app.showLoading();
            try {
                if (isEdit) {
                    await this.app.apiRequest(`lotes/${lote.id}`, { method: "PUT", body: JSON.stringify(data) });
                    this.app.showNotification("Lote atualizado com sucesso!", "success");
                } else {
                    await this.app.apiRequest("lotes", { method: "POST", body: JSON.stringify(data) });
                    this.app.showNotification("Lote adicionado com sucesso!", "success");
                }
                this.app.hideModal("dynamicModal");
                this.loadLotes();
                this.app.loadDashboardData(); // Atualizar dashboard
                this.app.mapaManager.loadPolosOnMap(); // Atualizar mapa
            } catch (error) {
                console.error("Erro ao salvar lote:", error);
                this.app.showNotification(`Erro ao salvar lote: ${error.message}`, "error");
            } finally {
                this.app.hideLoading();
            }
        });

        if (isEdit) {
            document.getElementById("polo_id").value = lote.polo_id;
            document.getElementById("status").value = lote.status;
        }
    }

    async viewLote(id) {
        this.app.showLoading();
        try {
            const lote = await this.app.apiRequest(`lotes/${id}`);
            const content = `
                <div class="info-grid">
                    <div class="info-item"><label>ID:</label><span>${lote.id}</span></div>
                    <div class="info-item"><label>Nome do Lote:</label><span>${lote.nome_lote}</span></div>
                    <div class="info-item"><label>Polo:</label><span>${lote.polo_nome}</span></div>
                    <div class="info-item"><label>Área (m²):</label><span>${lote.area_m2 || "N/A"}</span></div>
                    <div class="info-item"><label>Status:</label><span class="status-badge status-${lote.status}">${this.getStatusText(lote.status)}</span></div>
                    <div class="info-item"><label>Descrição:</label><span>${lote.descricao || "N/A"}</span></div>
                    <div class="info-item"><label>Criado em:</label><span>${new Date(lote.created_at).toLocaleString()}</span></div>
                    <div class="info-item"><label>Última Atualização:</label><span>${new Date(lote.updated_at).toLocaleString()}</span></div>
                </div>
            `;
            this.app.showModal(`Detalhes do Lote: ${lote.nome_lote}`, content, null, true);
        } catch (error) {
            console.error("Erro ao carregar detalhes do lote:", error);
            this.app.showNotification("Erro ao carregar detalhes do lote.", "error");
        } finally {
            this.app.hideLoading();
        }
    }

    async editLote(id) {
        this.app.showLoading();
        try {
            const lote = await this.app.apiRequest(`lotes/${id}`);
            this.showLoteModal(lote);
        } catch (error) {
            console.error("Erro ao carregar lote para edição:", error);
            this.app.showNotification("Erro ao carregar lote para edição.", "error");
        } finally {
            this.app.hideLoading();
        }
    }

    async deleteLote(id) {
        if (!confirm("Tem certeza que deseja eliminar este lote?")) return;

        this.app.showLoading();
        try {
            await this.app.apiRequest(`lotes/${id}`, { method: "DELETE" });
            this.app.showNotification("Lote eliminado com sucesso!", "success");
            this.loadLotes();
            this.app.loadDashboardData(); // Atualizar dashboard
            this.app.mapaManager.loadPolosOnMap(); // Atualizar mapa
        } catch (error) {
            console.error("Erro ao eliminar lote:", error);
            this.app.showNotification(`Erro ao eliminar lote: ${error.message}`, "error");
        } finally {
            this.app.hideLoading();
        }
    }

    getStatusText(status) {
        const statusMap = {
            "disponivel": "Disponível",
            "ocupado": "Ocupado",
            "reservado": "Reservado",
            "em_implementacao": "Em Implementação"
        };
        return statusMap[status] || status;
    }
}

// Inicializar quando a aplicação estiver pronta
document.addEventListener("DOMContentLoaded", () => {
    if (window.pdiApp) {
        window.lotesManager = new LotesManager(window.pdiApp);
    }
});


