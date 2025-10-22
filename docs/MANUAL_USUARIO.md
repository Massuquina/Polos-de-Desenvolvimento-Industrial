# Manual do Utilizador - PDI Angola

## Índice

1. [Introdução](#introdução)
2. [Acesso ao Sistema](#acesso-ao-sistema)
3. [Interface Principal](#interface-principal)
4. [Dashboard](#dashboard)
5. [Gestão de Infraestruturas](#gestão-de-infraestruturas)
6. [Gestão de Lotes](#gestão-de-lotes)
7. [Gestão de Empresas](#gestão-de-empresas)
8. [Visualização de Polos](#visualização-de-polos)
9. [Mapa Interativo](#mapa-interativo)
10. [Relatórios](#relatórios)
11. [Gestão de Utilizadores](#gestão-de-utilizadores)
12. [Configurações](#configurações)
13. [Resolução de Problemas](#resolução-de-problemas)

---

## Introdução

### Sobre o Sistema PDI Angola

O **PDI Angola** é uma plataforma web desenvolvida para a gestão eficiente de infraestruturas, **lotes e empresas** nos Polos de Desenvolvimento Industrial de Angola. O sistema oferece uma interface moderna e intuitiva para registar, monitorizar e gerir informações sobre infraestruturas, lotes e empresas distribuídas pelos diferentes polos industriais do país, **incluindo o nível de ocupação de cada polo**.

### Funcionalidades Principais

- **Gestão Completa de Infraestruturas**: Criar, visualizar, editar e eliminar registos de infraestruturas
- **Gestão de Lotes**: Criar, visualizar, editar e eliminar registos de lotes, com informações sobre área e status.
- **Gestão de Empresas**: Criar, visualizar, editar e eliminar registos de empresas, associando-as a polos e lotes, com detalhes como NIF, setor, atividade principal e número de funcionários.
- **Nível de Ocupação dos Polos**: Cálculo e exibição do nível de ocupação de cada polo com base na quantidade de lotes disponíveis e ocupados, e empresas instaladas.
- **Localização Geográfica**: Visualização em mapa interativo com coordenadas precisas
- **Filtros Avançados**: Pesquisa por província, município, polo, sector, status de lotes e status de empresas.
- **Dashboard Analítico**: Estatísticas e gráficos em tempo real, incluindo dados de lotes e empresas.
- **Níveis de Acesso**: Sistema de permissões diferenciadas por tipo de utilizador
- **Interface Responsiva**: Compatível com computadores, tablets e telemóveis

### Tipos de Utilizadores

O sistema suporta três níveis de acesso:

1. **Administrador**: Acesso completo a todas as funcionalidades, incluindo gestão de utilizadores, infraestruturas, lotes e empresas.
2. **Editor**: Pode criar, editar e visualizar infraestruturas, lotes e empresas.
3. **Visualizador**: Apenas visualização de dados (sem edição).

---

## Acesso ao Sistema

### Primeiro Acesso

1. **Abra o navegador web** (Chrome, Firefox, Edge ou Safari)
2. **Digite o endereço** da aplicação:
   - Local: `http://localhost/pdi_angola/frontend/`
   - Servidor: `http://[endereço-do-servidor]/pdi_angola/frontend/`

3. **Será apresentado o ecrã de login**

### Credenciais Padrão

Para o primeiro acesso, use as credenciais do administrador:
- **Email**: `admin@pdi.gov.ao`
- **Senha**: `admin123`

> **Importante**: Altere a senha padrão após o primeiro acesso por motivos de segurança.

### Processo de Login

1. **Insira o email** no campo correspondente
2. **Digite a senha** no campo de senha
3. **Clique em "Entrar"** ou pressione Enter
4. **Aguarde o redirecionamento** para o dashboard principal

### Recuperação de Senha

Se esqueceu a sua senha:
1. Clique em **"Esqueceu a senha?"** no ecrã de login
2. Insira o seu email
3. Siga as instruções enviadas por email
4. Defina uma nova senha

---

## Interface Principal

### Cabeçalho (Header)

O cabeçalho contém:
- **Logo e Nome**: PDI Angola - Sistema de Gestão de Infraestruturas
- **Menu de Navegação**: Links para as principais secções
- **Informações do Utilizador**: Nome e opções de conta
- **Botão de Logout**: Para sair do sistema

### Menu de Navegação

- **Dashboard**: Página inicial com estatísticas e gráficos
- **Infraestruturas**: Gestão completa de infraestruturas
- **Lotes**: Gestão de lotes disponíveis e ocupados.
- **Empresas**: Gestão de empresas instaladas nos polos.
- **Polos**: Visualização dos polos de desenvolvimento
- **Mapa**: Visualização geográfica interativa
- **Relatórios**: Geração de relatórios e exportação de dados
- **Utilizadores**: Gestão de contas (apenas administradores)

### Menu do Utilizador

Clique no nome do utilizador no canto superior direito para aceder:
- **Perfil**: Editar informações pessoais
- **Alterar Senha**: Modificar a senha de acesso
- **Configurações**: Preferências do sistema
- **Sair**: Terminar sessão

### Navegação Responsiva

Em dispositivos móveis:
- O menu principal torna-se um menu hambúrguer (☰)
- Clique no ícone para expandir/recolher o menu
- A interface adapta-se automaticamente ao tamanho do ecrã

---

## Dashboard

### Visão Geral

O dashboard é a página inicial do sistema e oferece uma visão geral das informações mais importantes através de:
- Estatísticas resumidas
- Gráficos interativos
- Indicadores de performance
- Acesso rápido às funcionalidades principais

### Cartões de Estatísticas

**Total de Infraestruturas**
- Mostra o número total de infraestruturas registadas
- Atualizado em tempo real

**Total de Polos**
- Número de polos de desenvolvimento cadastrados
- Inclui todos os status (ativo, inativo, em construção, planejado)

**Percentual em Bom Estado**
- Percentagem de infraestruturas em estado "Excelente" ou "Bom"
- Indicador da qualidade geral das infraestruturas

**Total de Lotes**
- Mostra o número total de lotes registados em todos os polos.

**Lotes Ocupados**
- Indica o número de lotes que estão atualmente ocupados por empresas ou reservados.

**Total de Empresas**
- Exibe o número total de empresas instaladas em todos os polos.

**Nível de Ocupação Médio**
- Apresenta a percentagem média de ocupação dos lotes nos polos, calculada com base nos lotes disponíveis e ocupados.

### Gráficos Analíticos

**Gráfico de Infraestruturas por Província**
- Gráfico de barras mostrando a distribuição por província
- Interativo: clique nas barras para mais detalhes
- Mostra as 10 províncias com mais infraestruturas

**Gráfico de Estado das Infraestruturas**
- Gráfico circular (donut) com a distribuição por estado
- Cores diferenciadas para cada estado:
  - Verde: Excelente
  - Azul: Bom
  - Amarelo: Regular
  - Laranja: Mau
  - Vermelho: Ruína

**Gráficos de Lotes e Empresas (Novos)**
- Gráficos adicionais serão exibidos para visualizar a distribuição de lotes por status e empresas por setor ou polo, fornecendo uma visão mais detalhada da ocupação e atividade industrial.

### Filtros do Dashboard

Use os filtros no topo da página para:
- **Filtrar por Província**: Selecione uma província específica
- **Filtrar por Polo**: Escolha um polo particular
- **Período**: Defina um intervalo de datas (se aplicável)

### Atualização de Dados

- Os dados são atualizados automaticamente a cada 5 minutos
- Para atualização manual, clique no botão "Atualizar" (ícone de refresh)
- A última atualização é mostrada no rodapé do dashboard

---

## Gestão de Infraestruturas

### Listagem de Infraestruturas

A página de infraestruturas apresenta uma tabela com todas as infraestruturas registadas:

**Colunas da Tabela:**
- **ID**: Identificador único da infraestrutura
- **Polo**: Nome do polo onde está localizada
- **Província**: Província da localização
- **Município**: Município da localização
- **Sector**: Sector de atividade
- **Estado**: Estado atual da infraestrutura
- **Data de Criação**: Quando foi registada no sistema
- **Ações**: Botões para visualizar, editar ou eliminar

### Filtros e Pesquisa

**Filtros Disponíveis:**
- **Província**: Filtra por província específica
- **Polo**: Filtra por polo de desenvolvimento
- **Sector**: Filtra por sector de atividade
- **Estado**: Filtra por estado da infraestrutura

**Como Usar os Filtros:**
1. Selecione os critérios desejados nos menus dropdown
2. Os resultados são filtrados automaticamente
3. Use "Limpar Filtros" para remover todos os filtros

### Adicionar Nova Infraestrutura

**Pré-requisitos:**
- Ter permissões de "Editor" ou "Administrador"
- Conhecer a localização e detalhes da infraestrutura

**Passos:**
1. **Clique em "Nova Infraestrutura"** (botão verde com ícone +)
2. **Preencha o formulário** com as informações obrigatórias:

**Secção Localização:**
- **Polo**: Selecione o polo de desenvolvimento (obrigatório)
- **Província**: Escolha a província (obrigatório)
- **Município**: Selecione o município (obrigatório)
- **Bairro/Local**: Especifique a localização mais precisa
- **Latitude/Longitude**: Coordenadas GPS (opcional mas recomendado)

**Secção Informações Gerais:**
- **Sector**: Sector de atividade da infraestrutura
- **Estado Atual**: Condição atual (Excelente, Bom, Regular, Mau, Ruína)
- **Área**: Área em metros quadrados
- **Ano de Construção**: Ano em que foi construída

**Secção Contactos:**
- **Nome do Representante**: Pessoa responsável
- **Email do Representante**: Contacto por email
- **Telefone do Representante**: Número de telefone

**Secção Observações:**
- **Observações**: Informações adicionais relevantes

3. **Clique em "Criar"** para salvar a infraestrutura

### Visualizar Infraestrutura

1. **Clique no ícone do olho** (👁️) na coluna "Ações"
2. **Será aberto um modal** com todas as informações da infraestrutura
3. **Todas as informações são apresentadas** de forma organizada
4. **Clique em "Fechar"** ou fora do modal para sair

### Editar Infraestrutura

**Pré-requisitos:**
- Ter permissões de "Editor" ou "Administrador"

**Passos:**
1. **Clique no ícone de edição** (✏️) na coluna "Ações"
2. **Será aberto o formulário** preenchido com os dados atuais
3. **Modifique os campos** desejados
4. **Clique em "Atualizar"** para salvar as alterações

### Eliminar Infraestrutura

**Pré-requisitos:**
- Ter permissões de "Administrador"

**Passos:**
1. **Clique no ícone de eliminação** (🗑️) na coluna "Ações"
2. **Confirme a eliminação** na caixa de diálogo
3. **A infraestrutura será removida** permanentemente

> **Atenção**: A eliminação é irreversível. Certifique-se antes de confirmar.

### Paginação

- **Navegação por páginas**: Use os botões numerados na parte inferior
- **Registos por página**: Configurável (10, 25, 50, 100)
- **Informações da página**: Mostra página atual e total de registos

---

## Gestão de Lotes

### Listagem de Lotes

A página de lotes apresenta uma tabela com todos os lotes registados nos polos:

**Colunas da Tabela:**
- **ID**: Identificador único do lote
- **Polo**: Nome do polo ao qual o lote pertence
- **Nome do Lote**: Nome ou identificação do lote
- **Área (m²)**: Tamanho do lote em metros quadrados
- **Status**: Estado atual do lote (Disponível, Ocupado, Reservado, Em Implementação)
- **Descrição**: Informações adicionais sobre o lote
- **Ações**: Botões para visualizar, editar ou eliminar

### Filtros e Pesquisa

**Filtros Disponíveis:**
- **Polo**: Filtra lotes por polo de desenvolvimento
- **Status**: Filtra lotes por estado de ocupação

**Como Usar os Filtros:**
1. Selecione os critérios desejados nos menus dropdown
2. Os resultados são filtrados automaticamente
3. Use "Limpar Filtros" para remover todos os filtros

### Adicionar Novo Lote

**Pré-requisitos:**
- Ter permissões de "Editor" ou "Administrador"

**Passos:**
1. **Clique em "Novo Lote"** (botão verde com ícone +)
2. **Preencha o formulário** com as informações obrigatórias:
   - **Polo**: Selecione o polo de desenvolvimento (obrigatório)
   - **Nome do Lote**: Nome ou identificação do lote (obrigatório)
   - **Área (m²)**: Tamanho do lote em metros quadrados (opcional)
   - **Status**: Selecione o status inicial (Disponível, Ocupado, etc.)
   - **Descrição**: Informações adicionais

3. **Clique em "Criar"** para salvar o lote

### Visualizar Lote

1. **Clique no ícone do olho** (👁️) na coluna "Ações"
2. **Será aberto um modal** com todas as informações detalhadas do lote.

### Editar Lote

**Pré-requisitos:**
- Ter permissões de "Editor" ou "Administrador"

**Passos:**
1. **Clique no ícone de edição** (✏️) na coluna "Ações"
2. **Será aberto o formulário** preenchido com os dados atuais
3. **Modifique os campos** desejados
4. **Clique em "Atualizar"** para salvar as alterações

### Eliminar Lote

**Pré-requisitos:**
- Ter permissões de "Administrador"

**Passos:**
1. **Clique no ícone de eliminação** (🗑️) na coluna "Ações"
2. **Confirme a eliminação** na caixa de diálogo
3. **O lote será removido** permanentemente

> **Atenção**: A eliminação é irreversível. Certifique-se antes de confirmar.

---

## Gestão de Empresas

### Listagem de Empresas

A página de empresas apresenta uma tabela com todas as empresas registadas nos polos:

**Colunas da Tabela:**
- **ID**: Identificador único da empresa
- **Polo**: Nome do polo onde a empresa está localizada
- **Lote**: Nome do lote onde a empresa está instalada (se aplicável)
- **Nome da Empresa**: Razão social da empresa
- **NIF**: Número de Identificação Fiscal
- **Sector**: Sector de atividade da empresa
- **Status**: Estado atual da empresa (Ativa, Inativa, Em Implementação, Em Expansão)
- **Ações**: Botões para visualizar, editar ou eliminar

### Filtros e Pesquisa

**Filtros Disponíveis:**
- **Polo**: Filtra empresas por polo de desenvolvimento
- **Sector**: Filtra empresas por sector de atividade
- **Status**: Filtra empresas por estado

**Como Usar os Filtros:**
1. Selecione os critérios desejados nos menus dropdown
2. Os resultados são filtrados automaticamente
3. Use "Limpar Filtros" para remover todos os filtros

### Adicionar Nova Empresa

**Pré-requisitos:**
- Ter permissões de "Editor" ou "Administrador"

**Passos:**
1. **Clique em "Nova Empresa"** (botão verde com ícone +)
2. **Preencha o formulário** com as informações obrigatórias:
   - **Polo**: Selecione o polo de desenvolvimento (obrigatório)
   - **Lote**: Selecione o lote onde a empresa será instalada (opcional, mas recomendado se a empresa ocupar um lote específico)
   - **Nome da Empresa**: Razão social (obrigatório)
   - **NIF**: Número de Identificação Fiscal (opcional)
   - **Sector**: Sector de atividade (obrigatório)
   - **Atividade Principal**: Descrição da atividade principal
   - **Número de Funcionários**: Quantidade de funcionários
   - **Contactos**: Email, telefone, website
   - **Data de Instalação**: Data em que a empresa se instalou no polo
   - **Status**: Estado atual da empresa
   - **Observações**: Informações adicionais

3. **Clique em "Criar"** para salvar a empresa

### Visualizar Empresa

1. **Clique no ícone do olho** (👁️) na coluna "Ações"
2. **Será aberto um modal** com todas as informações detalhadas da empresa.

### Editar Empresa

**Pré-requisitos:**
- Ter permissões de "Editor" ou "Administrador"

**Passos:**
1. **Clique no ícone de edição** (✏️) na coluna "Ações"
2. **Será aberto o formulário** preenchido com os dados atuais
3. **Modifique os campos** desejados
4. **Clique em "Atualizar"** para salvar as alterações

### Eliminar Empresa

**Pré-requisitos:**
- Ter permissões de "Administrador"

**Passos:**
1. **Clique no ícone de eliminação** (🗑️) na coluna "Ações"
2. **Confirme a eliminação** na caixa de diálogo
3. **A empresa será removida** permanentemente

> **Atenção**: A eliminação é irreversível. Certifique-se antes de confirmar.

---

## Visualização de Polos

### Lista de Polos

A página de polos apresenta cartões com informações de cada polo de desenvolvimento industrial:

**Informações Exibidas:**
- **Nome do Polo**: Denominação oficial
- **Localização**: Província e município
- **Status**: Estado atual (Ativo, Inativo, Em Construção, Planejado)
- **Área Total**: Extensão em hectares (se disponível)
- **Endereço**: Localização específica
- **Descrição**: Informações adicionais sobre o polo
- **Total de Lotes**: Número total de lotes no polo.
- **Lotes Ocupados**: Número de lotes ocupados no polo.
- **Total de Empresas**: Número de empresas instaladas no polo.
- **Nível de Ocupação**: Percentagem de ocupação dos lotes no polo.

### Status dos Polos

**Códigos de Cores:**
- **Verde**: Ativo - Polo em funcionamento
- **Vermelho**: Inativo - Polo não operacional
- **Amarelo**: Em Construção - Polo em desenvolvimento
- **Azul**: Planejado - Polo em fase de planeamento

### Detalhes do Polo

**Para ver mais informações:**
1. **Clique no cartão do polo** ou no botão "Ver Detalhes"
2. **Será exibido um modal** com informações completas
3. **Inclui lista de infraestruturas, lotes e empresas** associadas ao polo
4. **Mostra estatísticas** específicas do polo, incluindo detalhes de ocupação.

### Filtros de Polos

**Filtros Disponíveis:**
- **Por Status**: Filtra polos por estado atual
- **Por Província**: Mostra apenas polos de uma província
- **Por Área**: Filtra por tamanho (se aplicável)

---

## Mapa Interativo

### Visão Geral do Mapa

O mapa interativo oferece uma visualização geográfica de todos os polos de desenvolvimento industrial de Angola, utilizando tecnologia OpenStreetMap.

### Funcionalidades do Mapa

**Navegação:**
- **Zoom**: Use a roda do rato ou os botões +/- para aproximar/afastar
- **Panorâmica**: Clique e arraste para mover o mapa
- **Zoom para Angola**: Botão para voltar à vista geral do país

**Marcadores dos Polos:**
- **Ícones Coloridos**: Cada polo é representado por um ícone colorido conforme o status
- **Clique no Marcador**: Abre popup com informações básicas, **incluindo total de lotes, lotes ocupados, total de empresas e nível de ocupação**.
- **Duplo Clique**: Abre detalhes completos do polo

### Legenda do Mapa

**Cores dos Marcadores:**
- 🟢 **Verde**: Polos Ativos
- 🟡 **Amarelo**: Polos Em Construção
- 🔵 **Azul**: Polos Planejados
- 🔴 **Vermelho**: Polos Inativos

### Painel de Filtros

**Localizado no canto superior direito:**
- **Filtro por Status**: Mostra apenas polos com status específico
- **Filtro por Província**: Exibe polos de uma província particular
- **Botão "Aplicar"**: Ativa os filtros selecionados
- **Botão "Limpar"**: Remove todos os filtros

### Popup de Informações

**Ao clicar num marcador, é exibido:**
- Nome do polo
- Localização (província e município)
- Status atual
- Área total (se disponível)
- **Total de Lotes**
- **Lotes Ocupados**
- **Total de Empresas**
- **Nível de Ocupação**
- Botão "Ver Detalhes" para mais informações

### Modal de Detalhes do Polo

**Informações Completas:**
- Dados gerais do polo
- Lista de infraestruturas associadas
- **Lista de lotes associados**
- **Lista de empresas associadas**
- Estatísticas do polo, **incluindo detalhes de ocupação**.
- Informações de contacto (se disponível)

### Controlos do Mapa

**Barra de Ferramentas:**
- **Zoom In/Out**: Botões para controlo de zoom
- **Localização Atual**: Centra o mapa em Angola
- **Ecrã Completo**: Expande o mapa para ecrã completo
- **Camadas**: Alterna entre diferentes tipos de mapa (se disponível)

---

## Relatórios

### Tipos de Relatórios

O sistema oferece vários tipos de relatórios para análise e exportação de dados:

**Relatórios Disponíveis:**
1. **Relatório Geral de Infraestruturas**
2. **Relatório por Província**
3. **Relatório por Polo**
4. **Relatório de Estado das Infraestruturas**
5. **Relatório de Contactos**
6. **Relatório de Lotes** (novo)
7. **Relatório de Empresas** (novo)
8. **Relatório de Ocupação de Polos** (novo)

### Geração de Relatórios

**Passos para Gerar:**
1. **Aceda à página "Relatórios"**
2. **Selecione o tipo de relatório** desejado
3. **Configure os filtros** (período, localização, etc.)
4. **Escolha o formato** de exportação (PDF, Excel, CSV)
5. **Clique em "Gerar Relatório"**
6. **Aguarde o processamento** e download automático

### Filtros de Relatórios

**Filtros Comuns:**
- **Período**: Data de início e fim
- **Província**: Específica ou todas
- **Polo**: Específico ou todos
- **Estado**: Estado das infraestruturas
- **Sector**: Sector de atividade
- **Status de Lote**: Para relatórios de lotes
- **Status de Empresa**: Para relatórios de empresas

### Formatos de Exportação

**PDF:**
- Relatório formatado para impressão
- Inclui gráficos e tabelas
- Ideal para apresentações

**Excel (XLSX):**
- Dados em formato de planilha
- Permite análise adicional
- Compatível com Microsoft Excel

**CSV:**
- Formato de texto separado por vírgulas
- Compatível com qualquer software de planilha
- Ideal para importação em outros sistemas

### Agendamento de Relatórios

**Para Administradores:**
1. **Configure relatórios automáticos**
2. **Defina periodicidade** (diário, semanal, mensal)
3. **Especifique destinatários** por email
4. **Ative o agendamento**

---

## Gestão de Utilizadores

> **Nota**: Esta secção está disponível apenas para utilizadores com perfil de "Administrador".

### Lista de Utilizadores

**Informações Exibidas:**
- Nome completo
- Email de acesso
- Nível de acesso
- Status (Ativo/Inativo)
- Data de último acesso
- Data de criação da conta

### Adicionar Novo Utilizador

**Passos:**
1. **Clique em "Novo Utilizador"**
2. **Preencha o formulário:**
   - **Nome Completo**: Nome e apelido
   - **Email**: Endereço de email (será o login)
   - **Senha**: Senha inicial (utilizador deve alterar no primeiro acesso)
   - **Confirmar Senha**: Repetir a senha
   - **Nível de Acesso**: Administrador, Editor ou Visualizador
   - **Telefone**: Número de contacto (opcional)
   - **Cargo**: Função na organização (opcional)

3. **Clique em "Criar Utilizador"**
4. **O utilizador receberá um email** com as credenciais de acesso

### Níveis de Acesso

**Administrador:**
- Acesso completo a todas as funcionalidades, incluindo gestão de utilizadores, infraestruturas, lotes e empresas.
- Pode gerir utilizadores
- Pode eliminar infraestruturas, lotes e empresas
- Acesso a configurações do sistema

**Editor:**
- Pode criar, editar e visualizar infraestruturas, lotes e empresas.
- Pode gerar relatórios
- Não pode gerir utilizadores
- Não pode eliminar infraestruturas, lotes e empresas

**Visualizador:**
- Apenas visualização de dados
- Pode gerar relatórios básicos
- Não pode criar ou editar infraestruturas, lotes ou empresas
- Não pode gerir utilizadores

### Editar Utilizador

**Passos:**
1. **Clique no ícone de edição** na lista de utilizadores
2. **Modifique as informações** necessárias
3. **Não é possível alterar o email** (é o identificador único)
4. **Para alterar a senha**, marque "Redefinir senha"
5. **Clique em "Atualizar"**

### Desativar/Ativar Utilizador

**Para Desativar:**
1. **Clique no botão de status** (verde/vermelho)
2. **Confirme a ação**
3. **O utilizador não conseguirá mais aceder ao sistema**

**Para Reativar:**
1. **Clique no botão de status** novamente
2. **O utilizador volta a ter acesso**

### Eliminar Utilizador

**Passos:**
1. **Clique no ícone de eliminação** na lista de utilizadores
2. **Confirme a ação**
3. **O utilizador será removido** permanentemente

> **Atenção**: A eliminação é irreversível. Certifique-se antes de confirmar.

---

## Configurações

### Preferências do Sistema

**Ajustes Disponíveis:**
- **Idioma**: Selecione o idioma da interface
- **Tema**: Escolha entre tema claro e escuro
- **Notificações**: Ativar/desativar alertas
- **Intervalo de Atualização**: Frequência de atualização de dados

### Gestão de Setores

**Para Administradores:**
- Adicionar, editar e eliminar setores de atividade
- Os setores são usados para classificar infraestruturas e empresas

### Gestão de Níveis de Acesso

**Para Administradores:**
- Definir permissões para cada nível de acesso
- Adicionar novos níveis de acesso (se necessário)

---

## Resolução de Problemas

### Problemas Comuns

- **Página não carrega**: Verifique a conexão com a internet e o status do WAMP Server.
- **Erro de login**: Verifique as credenciais e o status do utilizador.
- **Dados não aparecem**: Verifique a conexão com a base de dados e os filtros aplicados.
- **Mapa não exibe**: Verifique a conexão com a internet e erros no console do navegador.

### Suporte Técnico

- Consulte a documentação técnica (`README.md` e `INSTALACAO_WAMP.md`)
- Verifique os logs do sistema para mensagens de erro
- Contacte o suporte técnico para assistência

---

## Manutenção e Backup

### Backup Regular

- **Base de Dados**: Exporte regularmente a base de dados via phpMyAdmin.
- **Ficheiros**: Faça backup da pasta `pdi_angola`.

### Atualizações

- Mantenha o WAMP Server e o sistema PDI Angola atualizados.
- Faça backup antes de qualquer atualização.

---

## Contacto e Suporte

Para questões técnicas ou suporte adicional:

- **Documentação**: Consulte os ficheiros na pasta `docs/`
- **Logs**: Verifique os ficheiros de log para diagnóstico


