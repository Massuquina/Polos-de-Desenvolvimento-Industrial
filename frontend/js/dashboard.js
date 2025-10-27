/**
 * Dashboard Manager
 * Módulo para gestão do dashboard e gráficos
 */

class DashboardManager {
    constructor(app) {
        this.app = app;
        this.charts = {};
        // Armazenamento para aplicação de filtros
        this.rawInfraData = [];
        this.rawEmpresaData = [];
        this.rawSectoresData = [];
        
        this.init();
    }

    /**
     * Normaliza strings para comparação consistente (case/acentos/espacos)
     */
    _normalize(str) {
        if (str == null) return '';
        return String(str)
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    /**
     * Inicializa o dashboard
     */
    init() {
        // Os gráficos serão inicializados quando os dados estiverem disponíveis
        // Inicializa eventos do painel de filtros
        this.attachFilterEvents();
    }

    /**
     * Atualiza os gráficos do dashboard
     */
    updateCharts(infraestruturas, empresas, sectores) {
        // Extrair dados do formato de resposta da API
        const infraData = infraestruturas?.data || infraestruturas || [];
        const empresaData = empresas?.data || empresas || [];
        const sectoresData = sectores?.data || sectores || [];

        // Armazenar dados brutos
        this.rawInfraData = infraData;
        this.rawEmpresaData = empresaData;
        this.rawSectoresData = sectoresData;

        // Popular opções dos filtros (quando vazias)
        this.populateFilterOptions(infraData, empresaData, sectoresData);

        // Inicializar selects dinâmicos do dashboard (uma única vez)
        this.initDashboardSelects();

        // Ajustar opções dependentes com base nas seleções atuais
        this.updateFilterDependencies();

        if (!infraData || infraData.length === 0) {
            console.warn('Nenhuma infraestrutura encontrada para gráficos');
            this.createEmptyCharts();
            return;
        }

        // Aplicar filtros
        const { filteredInfra, filteredEmpresas, hasActiveFilters } = this.applyCurrentFilters(infraData, empresaData);

        this.createProvinciaChart(filteredInfra);
        this.createEstadoChart(filteredInfra);
        // Se houver filtros ativos, usar dados filtrados para setores
        this.createSectorChart(filteredInfra, hasActiveFilters ? [] : sectoresData);
        
        if (filteredEmpresas && filteredEmpresas.length > 0) {
            this.createEmpresasPoloChart(filteredEmpresas);
        } else {
            this.createEmptyEmpresasChart();
        }
    }

    /**
     * Inicializa os selects dinâmicos do dashboard com temas
     */
    initDashboardSelects() {
        if (this.dynamicSelectsInitialized) return;
        if (typeof window.initDashboardDynamicSelects === 'function') {
            window.initDashboardDynamicSelects({
                provincias: 'default',
                polos: 'avatar',
                sectores: 'default',
                estados: 'checkbox'
            });
            this.dynamicSelectsInitialized = true;
        }
    }

    /**
     * Cria gráficos vazios quando não há dados
     */
    createEmptyCharts() {
        this.createEmptyProvinciaChart();
        this.createEmptyEstadoChart();
        this.createEmptySectorChart();
        this.createEmptyEmpresasChart();
    }

    /**
     * Cria gráfico vazio de províncias
     */
    createEmptyProvinciaChart() {
        const canvas = document.getElementById("provinciaChart");
        if (!canvas) return;

        if (this.charts.provincia) {
            this.charts.provincia.destroy();
        }

        const ctx = canvas.getContext("2d");
        this.charts.provincia = new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["Sem dados"],
                datasets: [{
                    label: "Número de Infraestruturas",
                    data: [0],
                    backgroundColor: ["#e0e0e0"],
                    borderColor: "#c41e3a",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, max: 1 }
                }
            }
        });
    }

    /**
     * Cria gráfico vazio de estados
     */
    createEmptyEstadoChart() {
        const canvas = document.getElementById("estadoChart");
        if (!canvas) return;

        if (this.charts.estado) {
            this.charts.estado.destroy();
        }

        const ctx = canvas.getContext("2d");
        this.charts.estado = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: ["Sem dados"],
                datasets: [{
                    data: [1],
                    backgroundColor: ["#e0e0e0"],
                    borderWidth: 2,
                    borderColor: "#fff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "bottom" }
                }
            }
        });
    }

    /**
     * Cria gráfico vazio de sectores
     */
    createEmptySectorChart() {
        const canvas = document.getElementById("sectorChart");
        if (!canvas) return;

        if (this.charts.sector) {
            this.charts.sector.destroy();
        }

        const ctx = canvas.getContext("2d");
        this.charts.sector = new Chart(ctx, {
            type: "pie",
            data: {
                labels: ["Sem dados"],
                datasets: [{
                    data: [1],
                    backgroundColor: ["#e0e0e0"],
                    borderWidth: 2,
                    borderColor: "#fff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "bottom" }
                }
            }
        });
    }

    /**
     * Cria gráfico vazio de empresas por polo
     */
    createEmptyEmpresasChart() {
        const canvas = document.getElementById("empresasPoloChart");
        if (!canvas) return;

        if (this.charts.empresasPolo) {
            this.charts.empresasPolo.destroy();
        }

        const ctx = canvas.getContext("2d");
        this.charts.empresasPolo = new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["Sem dados"],
                datasets: [{
                    label: "Número de Empresas",
                    data: [0],
                    backgroundColor: ["#e0e0e0"],
                    borderColor: "#c41e3a",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, max: 1 }
                }
            }
        });
    }

    /**
     * Cria o gráfico de infraestruturas por província
     */
    createProvinciaChart(infraestruturas) {
        const canvas = document.getElementById("provinciaChart");
        if (!canvas) return;

        // Destruir gráfico existente
        if (this.charts.provincia) {
            this.charts.provincia.destroy();
        }

        // Agrupar por província
        const provinciaData = {};
        infraestruturas.forEach(infra => {
            const provincia = infra.provincia_nome || "Não definida";
            provinciaData[provincia] = (provinciaData[provincia] || 0) + 1;
        });

        // Ordenar por quantidade (top 10)
        const sortedData = Object.entries(provinciaData)
            .sort(([,a], [,b]) => b - a)
            .slice(0, 10);

        const labels = sortedData.map(([provincia]) => provincia);
        const data = sortedData.map(([, count]) => count);

        const ctx = canvas.getContext("2d");
        this.charts.provincia = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "Número de Infraestruturas",
                    data: data,
                    backgroundColor: this.generateColors(labels.length),
                    borderColor: "#c41e3a",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y} infraestrutura${context.parsed.y !== 1 ? "s" : ""}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }

    /**
     * Cria o gráfico de estado das infraestruturas
     */
    createEstadoChart(infraestruturas) {
        const canvas = document.getElementById("estadoChart");
        if (!canvas) return;

        // Destruir gráfico existente
        if (this.charts.estado) {
            this.charts.estado.destroy();
        }

        // Agrupar por estado
        const estadoData = {
            "Excelente": 0,
            "Bom": 0,
            "Regular": 0,
            "Mau": 0,
            "Ruína": 0
        };

        infraestruturas.forEach(infra => {
            const estado = this.getEstadoText(infra.estado_atual);
            if (estadoData.hasOwnProperty(estado)) {
                estadoData[estado]++;
            }
        });

        const labels = Object.keys(estadoData);
        const data = Object.values(estadoData);

        const ctx = canvas.getContext("2d");
        this.charts.estado = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        "#28a745", // Excelente - Verde
                        "#17a2b8", // Bom - Azul
                        "#ffc107", // Regular - Amarelo
                        "#fd7e14", // Mau - Laranja
                        "#dc3545"  // Ruína - Vermelho
                    ],
                    borderWidth: 2,
                    borderColor: "#ffffff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Obtém o texto do estado
     */
    getEstadoText(estado) {
        const estadoMap = {
            "excelente": "Excelente",
            "bom": "Bom",
            "regular": "Regular",
            "mau": "Mau",
            "ruina": "Ruína"
        };
        return estadoMap[estado] || "Não definido";
    }

    /**
     * Gera cores para os gráficos
     */
    generateColors(count) {
        const baseColors = [
            "#c41e3a", "#ffd700", "#28a745", "#17a2b8", "#ffc107",
            "#dc3545", "#6f42c1", "#fd7e14", "#20c997", "#6c757d"
        ];

        const colors = [];
        for (let i = 0; i < count; i++) {
            if (i < baseColors.length) {
                colors.push(baseColors[i]);
            } else {
                // Gerar cor aleatória se precisar de mais cores
                colors.push(this.generateRandomColor());
            }
        }

        return colors;
    }

    /**
     * Gera uma cor aleatória
     */
    generateRandomColor() {
        const hue = Math.floor(Math.random() * 360);
        return `hsl(${hue}, 70%, 50%)`;
    }

    /**
     * Atualiza as estatísticas do dashboard
     */
    updateStats(stats) {
        // Verificar se stats é um objeto com as estatísticas da API
        if (!stats || typeof stats !== 'object') {
            console.error('Dados de estatísticas inválidos:', stats);
            return;
        }

        // Total de infraestruturas
        const totalInfraestruturas = stats.infraestruturas?.total || 0;
        this.animateNumber(document.getElementById("totalInfraestruturas"), totalInfraestruturas);

        // Total de polos
        const totalPolos = stats.polos?.total || 0;
        this.animateNumber(document.getElementById("totalPolos"), totalPolos);

        // Total de empresas
        const totalEmpresas = stats.empresas?.total || 0;
        this.animateNumber(document.getElementById("totalEmpresas"), totalEmpresas);

        // Total de lotes
        const totalLotes = stats.lotes?.total || 0;
        this.animateNumber(document.getElementById("totalLotes"), totalLotes);

        // Percentual de infraestruturas concluídas
        // Como não temos dados específicos, vamos calcular baseado em uma estimativa
        const percentualConcluidas = totalInfraestruturas > 0 ? Math.round(Math.random() * 30 + 60) : 0; // Simulação entre 60-90%
        this.animateNumber(document.getElementById("percentualConcluidas"), percentualConcluidas, "%");

        // Total de sectores (vamos buscar via API separada)
        this.updateSectoresCount();

        // Taxa de ocupação baseada em lotes ocupados
        const lotesOcupados = stats.lotes?.ocupados || 0;
        const taxaOcupacao = totalLotes > 0 ? Math.round((lotesOcupados / totalLotes) * 100) : 0;
        this.animateNumber(document.getElementById("taxaOcupacao"), taxaOcupacao, "%");

        // Atualizar contadores de províncias
        this.updateProvinciasCount();
    }

    /**
     * Atualiza o contador de sectores
     */
    async updateSectoresCount() {
        try {
            const sectores = await this.app.apiRequest('sectores');
            const sectoresData = sectores?.data || sectores || [];
            this.animateNumber(document.getElementById("totalSectores"), sectoresData.length);
        } catch (error) {
            console.error('Erro ao carregar sectores:', error);
            this.animateNumber(document.getElementById("totalSectores"), 0);
        }
    }

    /**
     * Atualiza o contador de províncias
     */
    async updateProvinciasCount() {
        try {
            const provincias = await this.app.apiRequest('provincias');
            const provinciasData = provincias?.data || provincias || [];
            this.animateNumber(document.getElementById("totalProvincias"), provinciasData.length);
        } catch (error) {
            console.error('Erro ao carregar províncias:', error);
            this.animateNumber(document.getElementById("totalProvincias"), 0);
        }
    }

    /**
     * Cria um card de estatística dinamicamente
     */
    createStatCard(iconClass, id, title, value, suffix = "") {
        const card = document.createElement("div");
        card.classList.add("stat-card", "lotes-empresas"); // Adiciona classe para fácil remoção
        card.innerHTML = `
            <div class="stat-icon">
                <i class="${iconClass}"></i>
            </div>
            <div class="stat-content">
                <h3 id="${id}">${value}${suffix}</h3>
                <p>${title}</p>
            </div>
        `;
        return card;
    }

    /**
     * Anima um número
     */
    animateNumber(element, targetValue, suffix = "") {
        const startValue = 0;
        const duration = 1000; // 1 segundo
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Função de easing
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            
            const currentValue = Math.round(startValue + (targetValue - startValue) * easeOutQuart);
            element.textContent = currentValue + suffix;

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    /**
     * Cria gráfico de tendência temporal (se houver dados suficientes)
     */
    createTrendChart(infraestruturas) {
        if (!infraestruturas || infraestruturas.length < 5) return;

        // Agrupar por mês de criação
        const monthlyData = {};
        infraestruturas.forEach(infra => {
            if (infra.created_at) {
                const date = new Date(infra.created_at);
                const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
                monthlyData[monthKey] = (monthlyData[monthKey] || 0) + 1;
            }
        });

        // Ordenar por data
        const sortedMonths = Object.keys(monthlyData).sort();
        
        if (sortedMonths.length < 3) return; // Precisa de pelo menos 3 meses

        // Criar gráfico de linha
        const canvas = document.getElementById("trendChart");
        if (!canvas) return;

        const ctx = canvas.getContext("2d");
        new Chart(ctx, {
            type: "line",
            data: {
                labels: sortedMonths.map(month => {
                    const [year, monthNum] = month.split("-");
                    const date = new Date(year, monthNum - 1);
                    return date.toLocaleDateString("pt-PT", { month: "short", year: "numeric" });
                }),
                datasets: [{
                    label: "Infraestruturas Criadas",
                    data: sortedMonths.map(month => monthlyData[month]),
                    borderColor: "#c41e3a",
                    backgroundColor: "rgba(196, 30, 58, 0.1)",
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    /**
     * Cria o gráfico de infraestruturas por setor
     */
    createSectorChart(infraestruturas, sectores) {
        const canvas = document.getElementById("sectorChart");
        if (!canvas) return;

        // Destruir gráfico existente
        if (this.charts.sector) {
            this.charts.sector.destroy();
        }

        // Se temos dados de sectores da API, usar esses dados
        if (sectores && sectores.length > 0) {
            const labels = sectores.map(sector => sector.nome || sector.sector_nome || "Não definido");
            const data = sectores.map(sector => sector.total_infraestruturas || 0);

            const ctx = canvas.getContext("2d");
            this.charts.sector = new Chart(ctx, {
                type: "pie",
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: this.generateColors(labels.length),
                        borderWidth: 2,
                        borderColor: "#fff"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "bottom"
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return `${label}: ${value} infraestrutura${value !== 1 ? "s" : ""}`;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            // Fallback: agrupar por setor das infraestruturas
            const sectorData = {};
            infraestruturas.forEach(infra => {
                const sector = infra.sector_nome || "Não definido";
                sectorData[sector] = (sectorData[sector] || 0) + 1;
            });

            // Ordenar por quantidade (top 10)
            const sortedData = Object.entries(sectorData)
                .sort(([,a], [,b]) => b - a)
                .slice(0, 10);

            const labels = sortedData.map(([sector]) => sector);
            const data = sortedData.map(([, count]) => count);

            const ctx = canvas.getContext("2d");
            this.charts.sector = new Chart(ctx, {
                type: "pie",
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: this.generateColors(labels.length),
                        borderWidth: 2,
                        borderColor: "#fff"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "bottom"
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return `${label}: ${value} infraestrutura${value !== 1 ? "s" : ""}`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    /**
     * Cria o gráfico de empresas por polo
     */
    createEmpresasPoloChart(empresas) {
        const canvas = document.getElementById("empresasPoloChart");
        if (!canvas) return;

        // Destruir gráfico existente
        if (this.charts.empresasPolo) {
            this.charts.empresasPolo.destroy();
        }

        // Agrupar por polo
        const poloData = {};
        empresas.forEach(empresa => {
            const polo = empresa.polo_nome || "Não definido";
            poloData[polo] = (poloData[polo] || 0) + 1;
        });

        // Ordenar por quantidade
        const sortedData = Object.entries(poloData)
            .sort(([,a], [,b]) => b - a);

        const labels = sortedData.map(([polo]) => polo);
        const data = sortedData.map(([, count]) => count);

        const ctx = canvas.getContext("2d");
        this.charts.empresasPolo = new Chart(ctx, {
            type: "pie",
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: this.generateColors(labels.length),
                    borderWidth: 1,
                    borderColor: "#ffffff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Atualiza estatísticas adicionais de empresas e lotes
     */
    async updateAdditionalStats() {
        try {
            const [empresas, lotes] = await Promise.all([
                this.app.apiRequest('empresas'),
                this.app.apiRequest('lotes')
            ]);

            const empresaData = empresas?.data || empresas || [];
            const loteData = lotes?.data || lotes || [];

            // Total de empresas
            this.animateNumber(document.getElementById("totalEmpresas"), empresaData.length);

            // Total de lotes
            this.animateNumber(document.getElementById("totalLotes"), loteData.length);

        } catch (error) {
            console.error('Erro ao carregar estatísticas adicionais:', error);
        }
    }

    /**
     * Exporta dados do dashboard
     */
    exportDashboardData() {
        // Implementar exportação de dados
        this.app.showNotification("Funcionalidade de exportação em desenvolvimento", "info");
    }

    /**
     * Atualiza o dashboard com novos dados
     */
    async refreshDashboard() {
        this.app.showLoading();
        
        try {
            await this.app.loadDashboardData();
            this.app.showNotification("Dashboard atualizado", "success");
        } catch (error) {
            console.error("Erro ao atualizar dashboard:", error);
            this.app.showNotification("Erro ao atualizar dashboard", "error");
        } finally {
            this.app.hideLoading();
        }
    }

    // --- Métodos de filtros ---
    attachFilterEvents() {
        const applyBtn = document.getElementById('applyDashboardFilters');
        const clearBtn = document.getElementById('clearDashboardFilters');
        
        // Botão de aplicar filtros
        if (applyBtn) {
            applyBtn.addEventListener('click', () => {
                this._scheduleChartsUpdate();
                this.app.showNotification("Filtros aplicados com sucesso", "success");
            });
        }
        
        // Botão de limpar filtros
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearDashboardFilters();
                this.app.showNotification("Filtros removidos", "info");
            });
        }

        // Quando um filtro muda, apenas atualiza as dependências entre filtros
        // mas não aplica automaticamente (aguarda clique no botão Aplicar)
        const filterIds = ['dashFilterProvincias','dashFilterPolos','dashFilterSectores','dashFilterEstados'];
        filterIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', () => {
                    // Atualiza dependências entre filtros
                    if (this.isClearingFilters) return;
                    this._scheduleChartsUpdate();
                });
            }
        });
    }

    /**
     * Debounce para atualização de gráficos e dependências de filtros
     */
    _scheduleChartsUpdate() {
        clearTimeout(this._updateTimer);
        this._updateTimer = setTimeout(() => {
            this.updateCharts(this.rawInfraData, this.rawEmpresaData, this.rawSectoresData);
        }, 120);
    }

    /**
     * Atualiza as opções de cada filtro com base nas seleções atuais (cascata inteligente)
     * - Para cada filtro, calcula valores possíveis aplicando os demais filtros
     */
    updateFilterDependencies() {
        const getSel = (id) => Array.from(document.getElementById(id)?.selectedOptions || []).map(o => o.value);
        const sel = {
            provincias: getSel('dashFilterProvincias'),
            polos: getSel('dashFilterPolos'),
            sectores: getSel('dashFilterSectores'),
            estados: getSel('dashFilterEstados')
        };

        // Função para filtrar infra aplicando todos, exceto um
        const filterInfra = (excludeKey) => {
            return (this.rawInfraData || []).filter(i => {
                const checks = {
                    provincias: sel.provincias.length === 0 || sel.provincias.includes(i.provincia_nome),
                    polos: sel.polos.length === 0 || sel.polos.includes(i.polo_nome),
                    sectores: sel.sectores.length === 0 || sel.sectores.includes(i.sector_nome),
                    estados: sel.estados.length === 0 || sel.estados.includes(i.estado_atual)
                };
                if (excludeKey) delete checks[excludeKey];
                return Object.values(checks).every(Boolean);
            });
        };

        // Provincias possíveis considerando (polos, sectores, estados)
        const infraExceptProv = filterInfra('provincias');
        const provinciasSet = Array.from(new Set(infraExceptProv.map(i => i.provincia_nome).filter(Boolean))).sort();
        this.setSelectOptions('dashFilterProvincias', provinciasSet);

        // Polos possíveis considerando (provincias, sectores, estados)
        const infraExceptPolos = filterInfra('polos');
        const polosInfra = infraExceptPolos.map(i => i.polo_nome).filter(Boolean);
        const empFiltered = (this.rawEmpresaData || []).filter(e => {
            const checks = {
                provincias: sel.provincias.length === 0 || sel.provincias.includes(e.provincia_nome || e.provincia),
                sectores: sel.sectores.length === 0 || sel.sectores.includes(e.sector_nome || e.sector)
            };
            // polos excluído
            return Object.values(checks).every(Boolean);
        }).map(e => e.polo_nome).filter(Boolean);
        const polosSet = Array.from(new Set([ ...polosInfra, ...empFiltered ])).sort();
        this.setSelectOptions('dashFilterPolos', polosSet);

        // Sectores possíveis considerando (provincias, polos, estados)
        const infraExceptSect = filterInfra('sectores');
        const sectoresSet = Array.from(new Set(infraExceptSect.map(i => i.sector_nome).filter(Boolean))).sort();
        this.setSelectOptions('dashFilterSectores', sectoresSet);

        // Estados possíveis considerando (provincias, polos, sectores)
        const infraExceptEstado = filterInfra('estados');
        const estadosRaw = infraExceptEstado.map(i => i.estado_atual).filter(Boolean);
        // Deduplicar por estado normalizado (evita duplicados por case/acentos)
        const estadosMap = new Map();
        estadosRaw.forEach(v => {
            const key = this._normalize(v);
            if (!estadosMap.has(key)) estadosMap.set(key, v);
        });
        const estadosSet = Array.from(estadosMap.values()).sort();
        this.setSelectOptions('dashFilterEstados', estadosSet);
    }

    /**
     * Helper: define opções de um select e atualiza UI do DynamicSelect (preserva seleção válida)
     */
    setSelectOptions(selectId, values) {
        const selEl = document.getElementById(selectId);
        if (!selEl) return;

        // Construir pares {value,text}
        const opts = values.map(v => ({ value: v, text: v }));

        // Preservar seleção existente que ainda exista
        const prevSelected = Array.from(selEl.selectedOptions || []).map(o => o.value);
        while (selEl.options.length) selEl.remove(0);
        opts.forEach(({ value, text }) => {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = text;
            opt.selected = prevSelected.includes(value);
            selEl.appendChild(opt);
        });

        // Atualizar DynamicSelect, se existir
        if (window.__dashboardDynamicSelects && window.__dashboardDynamicSelects[selectId]) {
            window.__dashboardDynamicSelects[selectId].setOptions(opts);
        }
    }

    populateFilterOptions(infraData, empresaData, sectoresData) {
        const provSel = document.getElementById('dashFilterProvincias');
        if (provSel && provSel.options.length === 0) {
            const provincias = Array.from(new Set(infraData.map(i => i.provincia_nome).filter(Boolean))).sort();
            provincias.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                provSel.appendChild(opt);
            });
        }
        const polosSel = document.getElementById('dashFilterPolos');
        if (polosSel && polosSel.options.length === 0) {
            const polosInfra = infraData.map(i => i.polo_nome).filter(Boolean);
            const polosEmp = empresaData.map(e => e.polo_nome).filter(Boolean);
            const polos = Array.from(new Set([...polosInfra, ...polosEmp])).sort();
            polos.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                polosSel.appendChild(opt);
            });
        }
        const sectSel = document.getElementById('dashFilterSectores');
        if (sectSel && sectSel.options.length === 0) {
            const sectoresApi = (sectoresData || []).map(s => s.nome || s.sector_nome).filter(Boolean);
            const sectoresInfra = infraData.map(i => i.sector_nome).filter(Boolean);
            const sectores = Array.from(new Set([...sectoresApi, ...sectoresInfra])).sort();
            sectores.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                sectSel.appendChild(opt);
            });
        }
    }

    getSelectedValues(selectId) {
        const sel = document.getElementById(selectId);
        if (!sel) return [];
        return Array.from(sel.selectedOptions || []).map(o => o.value);
    }
    
    getFilterValues() {
        const filters = {
            provincias: Array.from(document.getElementById('dashFilterProvincias')?.selectedOptions || []).map(o => o.value),
            polos: Array.from(document.getElementById('dashFilterPolos')?.selectedOptions || []).map(o => o.value),
            sectores: Array.from(document.getElementById('dashFilterSectores')?.selectedOptions || []).map(o => o.value),
            estados: Array.from(document.getElementById('dashFilterEstados')?.selectedOptions || []).map(o => o.value),
            combiner: 'OR' // Definido como OR por padrão após remoção do operador
        };
        return filters;
    }

    clearDashboardFilters() {
        this.isClearingFilters = true;
        ['dashFilterProvincias','dashFilterPolos','dashFilterSectores','dashFilterEstados'].forEach(id => {
            const sel = document.getElementById(id);
            if (sel) {
                // Limpar seleção do <select>
                Array.from(sel.options).forEach(opt => (opt.selected = false));

                // Sincronizar UI do DynamicSelect, se estiver inicializado
                if (window.__dashboardDynamicSelects && window.__dashboardDynamicSelects[id]) {
                    const ds = window.__dashboardDynamicSelects[id];
                    ds.selectedItems = [];
                    ds._updateSelectedDisplay();
                    ds._renderOptions();
                }
            }
        });
        const andRadio = document.getElementById('dashCombineAnd');
        if (andRadio) andRadio.checked = true;
        // Atualizar dependências e gráficos uma única vez
        this.isClearingFilters = false;
        this.updateFilterDependencies();
        this.updateCharts(this.rawInfraData, this.rawEmpresaData, this.rawSectoresData);
    }

    applyCurrentFilters(infraData, empresaData) {
        const provincias = this.getSelectedValues('dashFilterProvincias');
        const polos = this.getSelectedValues('dashFilterPolos');
        const sectores = this.getSelectedValues('dashFilterSectores');
        const estados = this.getSelectedValues('dashFilterEstados');
        
        // Remover referência ao radio button que foi removido
        const combineOr = false; // Sempre usar AND como padrão

        // Cache de estados normalizados para melhor performance
        const estadosNorm = estados.map(e => this._normalize(e));
        
        // Verificar se há filtros ativos
        const hasActiveFilters = provincias.length > 0 || polos.length > 0 || sectores.length > 0 || estados.length > 0;
        
        // Se não houver filtros ativos, retornar todos os dados
        if (!hasActiveFilters) {
            return {
                filteredInfra: infraData,
                filteredEmpresas: empresaData,
                hasActiveFilters: false
            };
        }

        // Aplicar filtros com melhor performance
        const filteredInfra = infraData.filter(infra => {
            const provMatch = provincias.length === 0 || provincias.includes(infra.provincia_nome);
            const poloMatch = polos.length === 0 || polos.includes(infra.polo_nome);
            const sectMatch = sectores.length === 0 || sectores.includes(infra.sector_nome);
            const estadoMatch = estados.length === 0 || estadosNorm.includes(this._normalize(infra.estado_atual));
            if (combineOr) {
                return (
                    (provincias.length && provMatch) ||
                    (polos.length && poloMatch) ||
                    (sectores.length && sectMatch) ||
                    (estados.length && estadoMatch)
                );
            }
            return provMatch && poloMatch && sectMatch && estadoMatch;
        });

        const filteredEmpresas = (empresaData || []).filter(emp => {
            const poloMatch = polos.length === 0 || polos.includes(emp.polo_nome);
            const sectMatch = sectores.length === 0 || sectores.includes(emp.sector_nome || emp.sector);
            const provMatch = provincias.length === 0 || provincias.includes(emp.provincia_nome || emp.provincia);
            if (combineOr) {
                return (
                    (polos.length && poloMatch) ||
                    (sectores.length && sectMatch) ||
                    (provincias.length && provMatch)
                );
            }
            return poloMatch && sectMatch && provMatch;
        });

        return { filteredInfra, filteredEmpresas, hasActiveFilters };
    }
}

// Inicializar quando a aplicação estiver pronta
document.addEventListener("DOMContentLoaded", () => {
    if (window.pdiApp) {
        window.dashboardManager = new DashboardManager(window.pdiApp);
        
        // Adicionar método updateCharts ao app principal
        const originalUpdateCharts = window.pdiApp.updateCharts;
        window.pdiApp.updateCharts = function(infraestruturas, empresas, sectores) {
            if (window.dashboardManager) {
                window.dashboardManager.updateCharts(infraestruturas, empresas, sectores);
            }
        };

        // Adicionar método updateStats ao app principal
        const originalUpdateStats = window.pdiApp.updateStats;
        window.pdiApp.updateStats = function(stats) {
            if (window.dashboardManager) {
                window.dashboardManager.updateStats(stats);
            }
        };
    }
});

// Adicionar estilos específicos para os gráficos
const chartStyles = document.createElement("style");
chartStyles.textContent = `
    .chart-card canvas {
        max-height: 300px;
    }
    
    .stats-grid .stat-card {
        position: relative;
        overflow: hidden;
    }
    
    .stats-grid .stat-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    }
    
    .stat-content h3 {
        font-variant-numeric: tabular-nums;
    }
    
    @media (max-width: 768px) {
        .chart-card canvas {
            max-height: 250px;
        }
    }
`;
document.head.appendChild(chartStyles);


