# PDI Angola - Sistema de Gestão de Infraestruturas

![Logo PDI Angola](frontend/images/logo-angola.png)

## Descrição

O **PDI Angola** é uma plataforma web completa desenvolvida para a gestão eficiente de infraestruturas, **lotes e empresas** nos Polos de Desenvolvimento Industrial de Angola. O sistema oferece funcionalidades avançadas de CRUD (Criar, Ler, Atualizar, Deletar), visualização geográfica, níveis de acesso diferenciados e interface responsiva, **incluindo o cálculo e exibição do nível de ocupação dos polos**.

## Características Principais

### 🏭 Gestão de Infraestruturas
- Registo completo de infraestruturas por polo industrial
- Informações detalhadas incluindo localização, estado, contactos
- Sistema de filtros avançados por província, município, sector
- Coordenadas GPS para localização precisa

### 📦 Gestão de Lotes
- Registo e gestão de lotes disponíveis, ocupados e reservados nos polos.
- Informações sobre área (m²) e status de ocupação.
- Operações CRUD completas para lotes.

### 🏢 Gestão de Empresas
- Registo e gestão de empresas instaladas nos polos e lotes.
- Detalhes da empresa como NIF, setor de atividade, atividade principal, número de funcionários, contactos e data de instalação.
- Operações CRUD completas para empresas.

### 📊 Nível de Ocupação dos Polos
- Cálculo automático do nível de ocupação de cada polo com base nos lotes disponíveis e ocupados, e empresas instaladas.
- Exibição visual do nível de ocupação no dashboard e nos detalhes dos polos.

### 🗺️ Visualização Geográfica
- Mapa interativo com OpenStreetMap
- Marcadores coloridos por status dos polos
- Popup com informações detalhadas, incluindo dados de lotes e empresas
- Filtros geográficos em tempo real

### 👥 Sistema de Utilizadores
- Três níveis de acesso: Administrador, Editor, Visualizador
- Autenticação segura com JWT
- Gestão completa de utilizadores
- Controlo de permissões por funcionalidade

### 📈 Dashboard Analítico
- Estatísticas em tempo real, incluindo total de lotes, lotes ocupados, total de empresas e nível de ocupação médio.
- Gráficos interativos (Chart.js)
- Indicadores de performance
- Distribuição por província e estado

### 📱 Interface Responsiva
- Design moderno e intuitivo
- Compatível com desktop, tablet e mobile
- Cores baseadas na bandeira de Angola
- Experiência de utilizador otimizada

## Tecnologias Utilizadas

### Backend
- **PHP 8.0+** - Linguagem de programação
- **MySQL 8.0+** - Base de dados
- **Apache 2.4+** - Servidor web
- **JWT** - Autenticação
- **RESTful API** - Arquitetura

### Frontend
- **HTML5** - Estrutura
- **CSS3** - Estilização responsiva
- **JavaScript ES6+** - Interatividade
- **Chart.js** - Gráficos
- **Leaflet** - Mapas interativos

### Infraestrutura
- **WAMP Server** - Ambiente de desenvolvimento
- **OpenStreetMap** - Dados geográficos
- **Font Awesome** - Ícones

## Estrutura do Projeto

```
pdi_angola/
├── backend/                 # API e lógica de negócio
│   ├── api.php             # Endpoint principal da API
│   ├── auth.php            # Sistema de autenticação
│   ├── config.php          # Configurações
│   ├── database.php        # Conexão com base de dados
│   ├── install.php         # Script de instalação
│   ├── logs/               # Ficheiros de log
│   └── .htaccess           # Configurações Apache
├── frontend/               # Interface do utilizador
│   ├── index.html          # Página principal
│   ├── css/
│   │   └── style.css       # Estilos principais
│   ├── js/
│   │   ├── app.js          # Aplicação principal
│   │   ├── dashboard.js    # Gestão do dashboard
│   │   ├── infraestruturas.js # Gestão de infraestruturas
│   │   ├── lotes.js        # Gestão de lotes
│   │   ├── empresas.js     # Gestão de empresas
│   │   └── mapa.js         # Gestão de mapas
│   └── images/             # Recursos visuais
├── database/               # Scripts de base de dados
│   ├── database_schema.sql # Estrutura da base de dados (v1)
│   ├── database_schema_update_v2.sql # Atualização da estrutura (v2)
│   └── insert_data.sql     # Dados iniciais
└── docs/                   # Documentação
    ├── INSTALACAO_WAMP.md  # Guia de instalação
    ├── MANUAL_USUARIO.md   # Manual do utilizador
    └── API_DOCUMENTATION.md # Documentação da API
```

## Instalação Rápida

### Pré-requisitos
- Windows 11
- WAMP Server 3.3.0+
- Navegador web moderno

### Passos de Instalação

1. **Instale o WAMP Server**
   ```
   Descarregue de: https://www.wampserver.com/
   Execute como administrador
   ```

2. **Copie os ficheiros**
   ```
   Extraia para: C:\wamp64\www\pdi_angola\
   ```

3. **Configure a base de dados**
   ```
   Aceda: http://localhost/phpmyadmin
   Crie base de dados: pdi_angola
   Execute: database/database_schema.sql
   Execute: database/insert_data.sql
   ```

4. **Atualize a base de dados (para novas funcionalidades)**
   ```
   Aceda: http://localhost/phpmyadmin
   Selecione a base de dados 'pdi_angola'
   Execute: database/database_schema_update_v2.sql
   ```

5. **Configure a aplicação**
   ```
   Edite: backend/config.php
   Ajuste as credenciais da base de dados
   ```

6. **Aceda à aplicação**
   ```
   URL: http://localhost/pdi_angola/frontend/
   Login: admin@pdi.gov.ao
   Senha: admin123
   ```

## Documentação Completa

- **[Guia de Instalação WAMP](docs/INSTALACAO_WAMP.md)** - Instruções detalhadas para instalação
- **[Manual do Utilizador](docs/MANUAL_USUARIO.md)** - Como usar todas as funcionalidades
- **[Documentação da API](docs/API_DOCUMENTATION.md)** - Referência técnica da API

## Credenciais Padrão

### Administrador
- **Email**: `admin@pdi.gov.ao`
- **Senha**: `admin123`
- **Permissões**: Acesso completo

> **Importante**: Altere a senha padrão após o primeiro acesso.

## Dados Pré-carregados

### Províncias (18)
Todas as províncias de Angola com municípios correspondentes.

### Polos de Desenvolvimento (5)
- Polo Industrial de Viana
- Polo de Desenvolvimento Industrial de Cabinda (PDIC)
- Polo Industrial de Benguela
- Polo Industrial de Lubango
- Polo Industrial de Malanje

### Sectores (12)
- Agricultura e Pecuária
- Indústria Alimentar
- Têxtil e Vestuário
- Madeira e Mobiliário
- Papel e Celulose
- Química e Petroquímica
- Metalurgia e Siderurgia
- Máquinas e Equipamentos
- Material de Transporte
- Construção Civil
- Energia e Utilities
- Tecnologia e Telecomunicações

## Funcionalidades por Nível de Acesso

### 👑 Administrador
- ✅ Gestão completa de infraestruturas, lotes e empresas
- ✅ Gestão de utilizadores
- ✅ Acesso a todas as configurações
- ✅ Eliminação de registos
- ✅ Geração de relatórios completos

### ✏️ Editor
- ✅ Criar e editar infraestruturas, lotes e empresas
- ✅ Visualizar todas as informações
- ✅ Gerar relatórios básicos
- ❌ Gestão de utilizadores
- ❌ Eliminar infraestruturas, lotes e empresas

### 👁️ Visualizador
- ✅ Visualizar informações
- ✅ Usar filtros e pesquisas
- ✅ Aceder ao mapa
- ❌ Criar ou editar dados
- ❌ Gestão de utilizadores

## API Endpoints

### Autenticação
- `POST /api.php/auth/login` - Login
- `POST /api.php/auth/logout` - Logout
- `GET /api.php/auth/check` - Verificar sessão

### Infraestruturas
- `GET /api.php/infraestruturas` - Listar
- `POST /api.php/infraestruturas` - Criar
- `GET /api.php/infraestruturas/{id}` - Obter
- `PUT /api.php/infraestruturas/{id}` - Atualizar
- `DELETE /api.php/infraestruturas/{id}` - Eliminar

### Lotes
- `GET /api.php/lotes` - Listar lotes
- `POST /api.php/lotes` - Criar lote
- `GET /api.php/lotes/{id}` - Obter lote
- `PUT /api.php/lotes/{id}` - Atualizar lote
- `DELETE /api.php/lotes/{id}` - Eliminar lote

### Empresas
- `GET /api.php/empresas` - Listar empresas
- `POST /api.php/empresas` - Criar empresa
- `GET /api.php/empresas/{id}` - Obter empresa
- `PUT /api.php/empresas/{id}` - Atualizar empresa
- `DELETE /api.php/empresas/{id}` - Eliminar empresa

### Dados Geográficos
- `GET /api.php/provincias` - Listar províncias
- `GET /api.php/municipios/{provincia_id}` - Municípios por província
- `GET /api.php/polos` - Listar polos
- `GET /api.php/sectores` - Listar sectores

## Configuração de Produção

### Segurança
1. **Altere senhas padrão**
2. **Configure HTTPS**
3. **Atualize chaves secretas** no `config.php`
4. **Configure firewall** adequadamente
5. **Ative logs de auditoria**

### Performance
1. **Configure cache** do Apache
2. **Otimize consultas** da base de dados
3. **Comprima recursos** estáticos
4. **Configure CDN** se necessário

### Backup
1. **Backup automático** da base de dados
2. **Backup dos ficheiros** da aplicação
3. **Teste restauração** regularmente
4. **Armazenamento seguro** dos backups

## Resolução de Problemas

### Problemas Comuns

**WAMP não inicia (ícone vermelho)**
- Verifique se as portas 80 e 3306 estão livres
- Desative IIS se estiver ativo
- Execute como administrador

**Erro de conexão com base de dados**
- Verifique credenciais no `config.php`
- Confirme se MySQL está a correr
- Teste conexão via phpMyAdmin

**Mapas não carregam**
- Verifique conexão à internet
- Desative bloqueadores de anúncios
- Verifique console do navegador para erros

## Contribuição

### Como Contribuir
1. Faça fork do projeto
2. Crie uma branch para a funcionalidade
3. Implemente as alterações
4. Teste completamente
5. Submeta pull request

### Padrões de Código
- **PHP**: PSR-12
- **JavaScript**: ES6+
- **CSS**: BEM methodology
- **Comentários**: Em português

## Licença

Este projeto está licenciado sob a Licença MIT - veja o ficheiro [LICENSE](LICENSE) para detalhes.

## Suporte

### Contactos
- **Email**: suporte@pdi.gov.ao
- **Telefone**: +244 XXX XXX XXX
- **Website**: https://pdi.gov.ao

### Recursos Adicionais
- **Documentação**: [docs/](docs/)
- **Issues**: Use o sistema de issues do repositório
- **Wiki**: Documentação adicional na wiki

## Changelog

### v1.1.0 (Agosto 2025)
- ✨ **Novas funcionalidades: Gestão de Lotes e Empresas**
- 📦 Adicionadas operações CRUD para Lotes e Empresas
- 📊 Implementado cálculo e exibição do Nível de Ocupação dos Polos
- 🗺️ Atualização dos mapas e dashboard para incluir dados de lotes e empresas
- 📝 Documentação atualizada para as novas funcionalidades

### v1.0.0 (Janeiro 2025)
- ✨ Lançamento inicial
- 🏭 Sistema completo de gestão de infraestruturas
- 🗺️ Integração com mapas interativos
- 👥 Sistema de utilizadores com níveis de acesso
- 📊 Dashboard com gráficos analíticos
- 📱 Interface responsiva
- 🔒 Sistema de autenticação seguro

## Roadmap

### v1.2.0 (Planeado)
- 📧 Sistema de notificações por email
- 📄 Geração de relatórios em PDF
- 🔍 Pesquisa avançada com filtros complexos
- 📱 Aplicação móvel nativa

### v1.3.0 (Futuro)
- 🤖 Integração com IA para análise preditiva
- 🌐 API pública para integração externa
- 📊 Dashboard executivo avançado
- 🔄 Sincronização com sistemas externos

---

**Desenvolvido com ❤️ para Angola**

*Sistema PDI Angola - Gestão Inteligente de Infraestruturas Industriais*


#   P o l o s - d e - D e s e n v o l v i m e n t o - I n d u s t r i a l  
 # Polos-de-Desenvolvimento-Industrial
