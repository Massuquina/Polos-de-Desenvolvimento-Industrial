/**
 * Mapa Manager
 * Módulo para gestão de mapas e visualização geográfica
 */

class MapaManager {
    constructor(app) {
        this.app = app;
        this.map = null;
        this.markers = [];
        this.polosData = [];
        
        this.init();
    }

    /**
     * Inicializa o gestor de mapas
     */
    init() {
        // O mapa será inicializado quando a seção for ativada
    }

    /**
     * Inicializa o mapa
     */
    async initMap() {
        if (this.map) {
            return; // Mapa já inicializado
        }

        const mapContainer = document.getElementById("map");
        if (!mapContainer) {
            console.error("Container do mapa não encontrado");
            return;
        }

        try {
            // Coordenadas centrais de Angola
            const angolaCenter = [-11.2027, 17.8739];

            // Criar o mapa
            this.map = L.map("map").setView(angolaCenter, 6);

            // Adicionar camada de tiles (ESRI Hybrid preferencial; fallback para OSM)
            if (L.esri && L.esri.basemapLayer) {
                this.esriImagery = L.esri.basemapLayer('Imagery');
                this.esriLabels = L.esri.basemapLayer('ImageryLabels');
                this.esriImagery.addTo(this.map);
                this.esriLabels.addTo(this.map);

                this.osmLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    attribution: "© OpenStreetMap contributors",
                    maxZoom: 19
                });

                const baseLayers = {
                    "ESRI Imagery": this.esriImagery,
                    "OpenStreetMap": this.osmLayer
                };
                const overlays = {
                    "ESRI Labels": this.esriLabels
                };
                this.layerControl = L.control.layers(baseLayers, overlays, { collapsed: true }).addTo(this.map);
            } else {
                this.osmLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    attribution: "© OpenStreetMap contributors",
                    maxZoom: 19
                }).addTo(this.map);
            }

            // Carregar e exibir os polos
            await this.loadPolosOnMap();

            // Adicionar controles personalizados
            this.addMapControls();

        } catch (error) {
            console.error("Erro ao inicializar mapa:", error);
            this.showMapError();
        }
    }

    /**
     * Carrega os polos no mapa
     */
    async loadPolosOnMap() {
        try {
            const polos = await this.app.apiRequest("polos");
            this.polosData = polos;
            
            this.clearMarkers();
            this.addPolosToMap(polos);
            
        } catch (error) {
            console.error("Erro ao carregar polos no mapa:", error);
        }
    }

    /**
     * Adiciona os polos ao mapa
     */
    addPolosToMap(polos) {
        polos.forEach(polo => {
            if (polo.latitude && polo.longitude) {
                this.addPoloMarker(polo);
            }
        });

        // Ajustar visualização para mostrar todos os marcadores
        if (this.markers.length > 0) {
            const group = new L.featureGroup(this.markers);
            this.map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    /**
     * Adiciona um marcador de polo ao mapa
     */
    addPoloMarker(polo) {
        const lat = parseFloat(polo.latitude);
        const lng = parseFloat(polo.longitude);

        if (isNaN(lat) || isNaN(lng)) {
            console.warn(`Coordenadas inválidas para polo ${polo.nome}:`, polo.latitude, polo.longitude);
            return;
        }

        // Criar ícone personalizado baseado no status
        const icon = this.createPoloIcon(polo.status);

        // Criar marcador
        const marker = L.marker([lat, lng], { icon })
            .addTo(this.map)
            .bindPopup(this.createPoloPopup(polo))
            .on('click', () => this.onPoloClick(polo));

        // Desenhar perímetro real do polo, se disponível
        if (this.hasPoloPerimeter(polo)) {
            this.addPoloPerimeter(polo);
        }

        this.markers.push(marker);
    }

    /**
     * Verifica se há dados de perímetro para o polo
     */
    hasPoloPerimeter(polo) {
        return !!(polo.perimeter_geojson || polo.perimetro_geojson || polo.perimetro_coords || polo.perimeter_coords || polo.perimeter);
    }

    /**
     * Obtém coordenadas de perímetro a partir do polo (GeoJSON ou lista de coordenadas)
     */
    getPoloPerimeterCoords(polo) {
        try {
            let geojson = polo.perimeter_geojson || polo.perimetro_geojson;
            if (typeof geojson === 'string') {
                geojson = JSON.parse(geojson);
            }
            if (geojson && geojson.type) {
                if (geojson.type === 'Polygon') {
                    return geojson.coordinates[0].map(([lng, lat]) => [lat, lng]);
                }
                if (geojson.type === 'MultiPolygon') {
                    const first = geojson.coordinates[0][0];
                    return first.map(([lng, lat]) => [lat, lng]);
                }
            }
            const coordsRaw = polo.perimetro_coords || polo.perimeter_coords || polo.perimeter;
            if (Array.isArray(coordsRaw)) {
                if (Array.isArray(coordsRaw[0])) {
                    return coordsRaw.map(([lat, lng]) => [parseFloat(lat), parseFloat(lng)]);
                }
            }
            if (typeof coordsRaw === 'string') {
                const pairs = coordsRaw.split(';').map(p => p.trim()).filter(Boolean);
                const coords = pairs.map(p => {
                    const [lat, lng] = p.split(',').map(Number);
                    return [lat, lng];
                });
                if (coords.length >= 3) return coords;
            }
        } catch (e) {
            console.warn('Falha ao parsear perímetro do polo', e);
        }
        return null;
    }

    /**
     * Adiciona perímetro real do polo (se fornecido). Remove o losango.
     */
    addPoloPerimeter(polo) {
        const coords = this.getPoloPerimeterCoords(polo);
        if (!coords || coords.length < 3) return;

        const colors = {
            'ativo': '#28a745',
            'em_construcao': '#ffc107',
            'planejado': '#17a2b8',
            'inativo': '#dc3545'
        };
        const color = colors[polo.status] || '#6c757d';

        const perimeter = L.polygon(coords, {
            color: color,
            weight: 2,
            opacity: 0.9,
            fillColor: color,
            fillOpacity: 0.15
        }).addTo(this.map);

        perimeter.bindTooltip(`
            <strong>${polo.nome}</strong><br>
            Área: ${polo.area_total || 'N/D'} hectares<br>
            Status: ${this.getStatusText(polo.status)}
        `, {
            permanent: false,
            direction: 'center',
            className: 'polo-perimeter-tooltip'
        });

        perimeter.on('click', () => this.onPoloClick(polo));
        this.markers.push(perimeter);
    }

    /**
     * Cria ícone personalizado para o polo
     */
    createPoloIcon(status) {
        const colors = {
            "ativo": "#28a745",
            "inativo": "#dc3545",
            "em_construcao": "#ffc107",
            "planejado": "#17a2b8"
        };

        const color = colors[status] || "#6c757d";

        return L.divIcon({
            className: "polo-marker",
            html: `
                <div class="polo-marker-inner" style="background-color: ${color};">
                    <i class="fas fa-industry"></i>
                </div>
            `,
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15]
        });
    }

    /**
     * Cria popup para o polo
     */
    createPoloPopup(polo) {
        const statusText = this.getStatusText(polo.status);
        const statusClass = `status-${polo.status}`;

        const nivelOcupacao = polo.total_lotes > 0 
            ? ((polo.lotes_ocupados / polo.total_lotes) * 100).toFixed(1) 
            : 0;

        return `
            <div class="polo-popup">
                <h3>${polo.nome}</h3>
                <div class="polo-popup-info">
                    <p><strong>Localização:</strong> ${polo.provincia_nome}, ${polo.municipio_nome}</p>
                    ${polo.endereco ? `<p><strong>Endereço:</strong> ${polo.endereco}</p>` : ""}
                    ${polo.area_total ? `<p><strong>Área:</strong> ${polo.area_total} hectares</p>` : ""}
                    <p><strong>Lotes:</strong> ${polo.lotes_ocupados} / ${polo.total_lotes}</p>
                    <p><strong>Empresas:</strong> ${polo.total_empresas}</p>
                    <p><strong>Ocupação:</strong> ${nivelOcupacao}%</p>
                    <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${statusText}</span></p>
                    ${polo.descricao ? `<p><strong>Descrição:</strong> ${polo.descricao}</p>` : ""}
                </div>
                <div class="polo-popup-actions">
                    <button class="btn btn-primary btn-sm" onclick="mapaManager.viewPoloDetails(${polo.id})">
                        <i class="fas fa-eye"></i> Ver Detalhes
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Manipula clique no polo
     */
    onPoloClick(polo) {
        console.log("Polo clicado:", polo);
        // Pode adicionar lógica adicional aqui
    }

    /**
     * Visualiza detalhes do polo
     */
    async viewPoloDetails(poloId) {
        try {
            // Carregar infraestruturas do polo
            const infraestruturas = await this.app.apiRequest(`infraestruturas?polo_id=${poloId}`);
            this.showPoloDetailsModal(poloId, infraestruturas);
        } catch (error) {
            console.error("Erro ao carregar detalhes do polo:", error);
            this.app.showNotification("Erro ao carregar detalhes do polo", "error");
        }
    }

    /**
     * Mostra modal com detalhes do polo
     */
    showPoloDetailsModal(poloId, infraestruturas) {
        const polo = this.polosData.find(p => p.id == poloId);
        if (!polo) return;

        const modal = document.createElement("div");
        modal.className = "modal";
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 700px;">
                <div class="modal-header">
                    <h2>${polo.nome}</h2>
                    <button class="modal-close" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="polo-details">
                        <div class="polo-details-info">
                            <h3>Informações Gerais</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Província:</label>
                                    <span>${polo.provincia_nome}</span>
                                </div>
                                <div class="info-item">
                                    <label>Município:</label>
                                    <span>${polo.municipio_nome}</span>
                                </div>
                                ${polo.comuna_nome ? `
                                    <div class="info-item">
                                        <label>Comuna:</label>
                                        <span>${polo.comuna_nome}</span>
                                    </div>
                                ` : ""}
                                ${polo.endereco ? `
                                    <div class="info-item">
                                        <label>Endereço:</label>
                                        <span>${polo.endereco}</span>
                                    </div>
                                ` : ""}
                                ${polo.area_total ? `
                                    <div class="info-item">
                                        <label>Área Total:</label>
                                        <span>${polo.area_total} hectares</span>
                                    </div>
                                ` : ""}
                                <div class="info-item">
                                    <label>Lotes Totais:</label>
                                    <span>${polo.total_lotes || 0}</span>
                                </div>
                                <div class="info-item">
                                    <label>Lotes Ocupados:</label>
                                    <span>${polo.lotes_ocupados || 0}</span>
                                </div>
                                <div class="info-item">
                                    <label>Total de Empresas:</label>
                                    <span>${polo.total_empresas || 0}</span>
                                </div>
                                <div class="info-item">
                                    <label>Nível de Ocupação:</label>
                                    <span>${polo.total_lotes > 0 ? ((polo.lotes_ocupados / polo.total_lotes) * 100).toFixed(1) : 0}%</span>
                                </div>
                                <div class="info-item">
                                    <label>Status:</label>
                                    <span class="status-badge status-${polo.status}">${this.getStatusText(polo.status)}</span>
                                </div>
                            </div>
                            ${polo.descricao ? `
                                <div class="polo-description">
                                    <h4>Descrição</h4>
                                    <p>${polo.descricao}</p>
                                </div>
                            ` : ""}
                        </div>
                        
                        <div class="polo-infraestruturas">
                            <h3>Infraestruturas (${infraestruturas.data ? infraestruturas.data.length : 0})</h3>
                            ${infraestruturas.data && infraestruturas.data.length > 0 ? `
                                <div class="infraestruturas-list">
                                    ${infraestruturas.data.slice(0, 5).map(infra => `
                                        <div class="infraestrutura-item">
                                            <div class="infraestrutura-info">
                                                <strong>ID ${infra.id}</strong>
                                                ${infra.sector_nome ? `<span class="sector">${infra.sector_nome}</span>` : ""}
                                            </div>
                                            <span class="status-badge status-${infra.estado_atual}">
                                                ${this.getEstadoText(infra.estado_atual)}
                                            </span>
                                        </div>
                                    `).join("")}
                                    ${infraestruturas.data.length > 5 ? `
                                        <div class="more-infraestruturas">
                                            <small>E mais ${infraestruturas.data.length - 5} infraestrutura(s)...</small>
                                        </div>
                                    ` : ""}
                                </div>
                            ` : `
                                <p class="no-infraestruturas">Nenhuma infraestrutura registada neste polo.</p>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        // Mostrar modal
        setTimeout(() => modal.classList.add("show"), 10);

        // Event listeners
        modal.querySelector(".modal-close").addEventListener("click", () => {
            modal.remove();
        });

        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    /**
     * Adiciona controles personalizados ao mapa
     */
    addMapControls() {
        // Controle de filtros
        const filterControl = L.control({ position: "topright" });
        filterControl.onAdd = () => {
            const div = L.DomUtil.create("div", "map-filter-control");
            div.innerHTML = `
                <div class="map-filter-panel">
                    <h4>Filtros</h4>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select id="mapStatusFilter">
                            <option value="">Todos</option>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="em_construcao">Em Construção</option>
                            <option value="planejado">Planejado</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Província:</label>
                        <select id="mapProvinciaFilter">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="mapaManager.applyMapFilters()">
                        Aplicar
                    </button>
                </div>
            `;
            
            // Prevenir propagação de eventos do mapa
            L.DomEvent.disableClickPropagation(div);
            L.DomEvent.disableScrollPropagation(div);
            
            return div;
        };
        filterControl.addTo(this.map);

        // Carregar opções de província
        this.loadProvinciaOptions();

        // Controle de legenda
        const legendControl = L.control({ position: "bottomright" });
        legendControl.onAdd = () => {
            const div = L.DomUtil.create("div", "map-legend");
            div.innerHTML = `
                <h4>Legenda</h4>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #28a745;"></div>
                    <span>Ativo</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #ffc107;"></div>
                    <span>Em Construção</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #17a2b8;"></div>
                    <span>Planejado</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #dc3545;"></div>
                    <span>Inativo</span>
                </div>
            `;
            return div;
        };
        legendControl.addTo(this.map);
    }

    /**
     * Carrega opções de província para o filtro
     */
    async loadProvinciaOptions() {
        try {
            const provincias = await this.app.apiRequest("provincias");
            const select = document.getElementById("mapProvinciaFilter");
            
            if (select) {
                provincias.forEach(provincia => {
                    const option = document.createElement("option");
                    option.value = provincia.id;
                    option.textContent = provincia.nome;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error("Erro ao carregar províncias:", error);
        }
    }

    /**
     * Aplica filtros no mapa
     */
    applyMapFilters() {
        const statusFilter = document.getElementById("mapStatusFilter")?.value;
        const provinciaFilter = document.getElementById("mapProvinciaFilter")?.value;

        let filteredPolos = this.polosData;

        if (statusFilter) {
            filteredPolos = filteredPolos.filter(polo => polo.status === statusFilter);
        }

        if (provinciaFilter) {
            filteredPolos = filteredPolos.filter(polo => polo.provincia_id == provinciaFilter);
        }

        this.clearMarkers();
        this.addPolosToMap(filteredPolos);

        this.app.showNotification(`Filtro aplicado: ${filteredPolos.length} polo(s) encontrado(s)`, "info");
    }

    /**
     * Limpa todos os marcadores
     */
    clearMarkers() {
        this.markers.forEach(marker => {
            this.map.removeLayer(marker);
        });
        this.markers = [];
    }

    /**
     * Obtém texto do status
     */
    getStatusText(status) {
        const statusMap = {
            "ativo": "Ativo",
            "inativo": "Inativo",
            "em_construcao": "Em Construção",
            "planejado": "Planejado"
        };
        return statusMap[status] || status;
    }

    /**
     * Obtém texto do estado
     */
    getEstadoText(estado) {
        const estadoMap = {
            "excelente": "Excelente",
            "bom": "Bom",
            "regular": "Regular",
            "mau": "Mau",
            "ruina": "Ruína"
        };
        return estadoMap[estado] || estado;
    }

    /**
     * Mostra erro no mapa
     */
    showMapError() {
        const mapContainer = document.getElementById("map");
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="map-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Não foi possível carregar o mapa. Verifique sua conexão com a internet ou tente novamente mais tarde.</p>
                </div>
            `;
            mapContainer.style.display = "flex";
            mapContainer.style.justifyContent = "center";
            mapContainer.style.alignItems = "center";
            mapContainer.style.height = "400px";
            mapContainer.style.backgroundColor = "#f0f0f0";
            mapContainer.style.color = "#666";
            mapContainer.style.textAlign = "center";
            mapContainer.style.flexDirection = "column";
        }
    }
}

// Inicializar quando a aplicação estiver pronta
document.addEventListener("DOMContentLoaded", () => {
    if (window.pdiApp) {
        window.mapaManager = new MapaManager(window.pdiApp);
    }
});


