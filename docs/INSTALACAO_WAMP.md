# Guia de Instalação - PDI Angola no WAMP Server

## Índice

1. [Introdução](#introdução)
2. [Requisitos do Sistema](#requisitos-do-sistema)
3. [Instalação do WAMP Server](#instalação-do-wamp-server)
4. [Configuração do Ambiente](#configuração-do-ambiente)
5. [Instalação da Aplicação](#instalação-da-aplicação)
6. [Configuração da Base de Dados](#configuração-da-base-de-dados)
7. [Configuração do Apache](#configuração-do-apache)
8. [Testes e Verificação](#testes-e-verificação)
9. [Resolução de Problemas](#resolução-de-problemas)
10. [Manutenção e Backup](#manutenção-e-backup)

---

## Introdução

Este guia fornece instruções detalhadas para instalar e configurar a plataforma **PDI Angola - Sistema de Gestão de Infraestruturas** num servidor WAMP no Windows 11. A plataforma foi desenvolvida para gerir infraestruturas, **lotes e empresas** em Polos de Desenvolvimento Industrial de Angola, oferecendo funcionalidades completas de CRUD, autenticação, níveis de acesso e visualização geográfica, **incluindo o nível de ocupação dos polos**.

### Sobre a Aplicação

A PDI Angola é uma aplicação web moderna que combina:
- **Backend**: PHP com MySQL para gestão de dados
- **Frontend**: HTML5, CSS3, JavaScript com interface responsiva
- **Mapas**: Integração com OpenStreetMap para visualização geográfica
- **Segurança**: Sistema de autenticação com níveis de acesso diferenciados
- **Gestão de Lotes**: Funcionalidades para registar e gerir lotes nos polos.
- **Gestão de Empresas**: Funcionalidades para registar e gerir empresas instaladas nos lotes e polos.
- **Nível de Ocupação**: Cálculo e visualização do nível de ocupação dos polos com base nos lotes e empresas.

---

## Requisitos do Sistema

### Requisitos Mínimos

- **Sistema Operativo**: Windows 11 (64-bit)
- **RAM**: 4 GB (recomendado 8 GB)
- **Espaço em Disco**: 2 GB livres
- **Processador**: Intel Core i3 ou equivalente AMD
- **Conexão à Internet**: Para download de componentes e mapas

### Software Necessário

- **WAMP Server 3.3.0** ou superior
- **PHP 8.0** ou superior
- **MySQL 8.0** ou superior
- **Apache 2.4** ou superior
- **Navegador Web**: Chrome, Firefox, Edge ou Safari (versões recentes)

---

## Instalação do WAMP Server

### Passo 1: Download do WAMP Server

1. Aceda ao site oficial: [https://www.wampserver.com/](https://www.wampserver.com/)
2. Clique em "DOWNLOAD" e selecione a versão 64-bit para Windows
3. Descarregue o ficheiro `wampserver3.3.0_x64.exe` (ou versão mais recente)

### Passo 2: Instalação

1. **Execute como Administrador**: Clique com o botão direito no ficheiro descarregado e selecione "Executar como administrador"

2. **Assistente de Instalação**:
   - Aceite os termos de licença
   - Escolha o diretório de instalação (recomendado: `C:\wamp64`)
   - Selecione o navegador padrão (recomendado: Chrome ou Edge)
   - Selecione o editor de texto padrão (recomendado: Notepad++)

3. **Aguarde a conclusão da instalação** (pode demorar alguns minutos)

### Passo 3: Primeira Execução

1. Inicie o WAMP Server através do menu Iniciar ou ícone no ambiente de trabalho
2. Aguarde até o ícone na barra de tarefas ficar verde (pode demorar alguns minutos na primeira execução)
3. Se o ícone permanecer laranja ou vermelho, consulte a secção [Resolução de Problemas](#resolução-de-problemas)

---

## Configuração do Ambiente

### Configuração do PHP

1. **Clique no ícone do WAMP** na barra de tarefas
2. Navegue para **PHP > Versão** e certifique-se de que está a usar PHP 8.0 ou superior
3. Vá para **PHP > Configurações do PHP > php.ini**
4. Localize e modifique as seguintes configurações:

```ini
; Aumentar limites de memória e tempo de execução
memory_limit = 256M
max_execution_time = 300
max_input_time = 300

; Configurações de upload
upload_max_filesize = 10M
post_max_size = 10M

; Extensões necessárias (descomente se necessário)
extension=pdo_mysql
extension=mysqli
extension=openssl
extension=curl
extension=gd
extension=mbstring
extension=json

; Configurações de sessão
session.gc_maxlifetime = 3600
session.cookie_lifetime = 3600

; Timezone
date.timezone = "Africa/Luanda"
```

5. **Reinicie o WAMP** após as alterações

### Configuração do MySQL

1. **Aceda ao phpMyAdmin**: Clique no ícone do WAMP > phpMyAdmin
2. **Login**: Use `root` como utilizador (senha vazia por padrão)
3. **Configurações de Segurança** (recomendado para produção):
   - Clique em "Contas de utilizador"
   - Edite a conta `root`
   - Defina uma senha segura
   - Atualize o ficheiro `config.php` da aplicação com a nova senha

### Configuração do Apache

1. **Virtual Hosts** (opcional mas recomendado):
   - Clique no ícone do WAMP > Apache > httpd.conf
   - Localize e descomente: `Include conf/extra/httpd-vhosts.conf`
   - Edite o ficheiro `httpd-vhosts.conf` para adicionar:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/wamp64/www/pdi_angola/frontend"
    ServerName pdi-angola.local
    ServerAlias www.pdi-angola.local
    
    <Directory "C:/wamp64/www/pdi_angola/frontend">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

2. **Ficheiro Hosts** (se usar Virtual Host):
   - Abra o Bloco de Notas como Administrador
   - Abra o ficheiro: `C:\Windows\System32\drivers\etc\hosts`
   - Adicione a linha: `127.0.0.1 pdi-angola.local`

---

## Instalação da Aplicação

### Passo 1: Extração dos Ficheiros

1. **Copie a pasta `pdi_angola`** para o diretório `C:\wamp64\www\`
2. **Estrutura final** deve ser:
```
C:\wamp64\www\pdi_angola\
├── backend/
├── frontend/
├── database/
└── docs/
```

### Passo 2: Configuração de Permissões

1. **Clique com o botão direito** na pasta `pdi_angola`
2. Selecione **Propriedades > Segurança**
3. **Adicione permissões** para o utilizador `IUSR` e `IIS_IUSRS`:
   - Controlo total para a pasta `backend/logs/`
   - Leitura e execução para as restantes pastas

### Passo 3: Verificação da Configuração

1. **Edite o ficheiro** `backend/config.php`
2. **Verifique as configurações** da base de dados:

```php
define("DB_HOST", "localhost");
define("DB_NAME", "pdi_angola");
define("DB_USER", "root");
define("DB_PASS", ""); // Insira a senha se definiu uma
```

3. **Ajuste outros parâmetros** conforme necessário:
   - `APP_URL`: URL base da aplicação
   - `JWT_SECRET`: Chave secreta para tokens (altere em produção)
   - `PASSWORD_SALT`: Salt para senhas (altere em produção)

---

## Configuração da Base de Dados

### Passo 1: Criação da Base de Dados

1. **Aceda ao phpMyAdmin**: `http://localhost/phpmyadmin`
2. **Clique em "Nova"** no painel esquerdo
3. **Nome da base de dados**: `pdi_angola`
4. **Codificação**: `utf8mb4_unicode_ci`
5. Clique em **"Criar"**

### Passo 2: Execução do Script de Instalação

**Opção A: Via Interface Web (Recomendado)**

1. Navegue para: `http://localhost/pdi_angola/backend/install.php`
2. Clique em **"Instalar Agora"**
3. Aguarde a conclusão (pode demorar alguns minutos)
4. Anote as credenciais do administrador padrão:
   - **Email**: `admin@pdi.gov.ao`
   - **Senha**: `admin123`

**Opção B: Via phpMyAdmin (Manual)**

1. **Aceda ao phpMyAdmin** e selecione a base de dados `pdi_angola`
2. **Clique em "Importar"**
3. **Selecione o ficheiro** `database/database_schema.sql`
4. Clique em **"Executar"**
5. **Repita o processo** com o ficheiro `database/insert_data.sql`

### Passo 3: Atualização da Base de Dados (para novas funcionalidades)

Se já instalou a versão anterior da plataforma, execute o script de atualização:

1. **Aceda ao phpMyAdmin** e selecione a base de dados `pdi_angola`
2. **Clique em "Importar"**
3. **Selecione o ficheiro** `database/database_schema_update_v2.sql`
4. Clique em **"Executar"**

### Passo 4: Verificação da Instalação

1. **Aceda ao phpMyAdmin**
2. **Selecione a base de dados** `pdi_angola`
3. **Verifique se as tabelas foram criadas**:
   - `provincias` (18 registos)
   - `municipios` (164 registos)
   - `polos_desenvolvimento` (5 registos)
   - `usuarios` (1 registo)
   - `niveis_acesso` (3 registos)
   - `sectores` (12 registos)
   - **`lotes`** (nova tabela)
   - **`empresas`** (nova tabela)

---

## Configuração do Apache

### Configuração do .htaccess

1. **Verifique se o mod_rewrite está ativo**:
   - Clique no ícone do WAMP > Apache > Módulos do Apache
   - Certifique-se de que `rewrite_module` está marcado

2. **Configuração para o Backend**:
   - O ficheiro `backend/.htaccess` já está configurado
   - Verifica se as regras de reescrita estão a funcionar

3. **Configuração para o Frontend**:
   - Crie um ficheiro `.htaccess` na pasta `frontend/` se necessário:

```apache
RewriteEngine On

# Redirecionar para HTTPS (opcional, para produção)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Cache para recursos estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
</IfModule>

# Compressão gzip
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### Configuração de Segurança

1. **Ocultar informações do servidor**:
   - Edite `httpd.conf`
   - Adicione ou modifique:

```apache
ServerTokens Prod
ServerSignature Off
```

2. **Proteger ficheiros sensíveis**:
   - O `.htaccess` do backend já inclui proteções
   - Verifique se os ficheiros `.php` de configuração não são acessíveis diretamente

---

## Testes e Verificação

### Teste 1: Verificação do Ambiente

1. **Teste do WAMP**:
   - Navegue para: `http://localhost`
   - Deve ver a página inicial do WAMP
   - Verifique se todos os serviços estão verdes

2. **Teste do PHP**:
   - Navegue para: `http://localhost/?phpinfo=1`
   - Verifique a versão do PHP e extensões carregadas

3. **Teste do MySQL**:
   - Aceda ao phpMyAdmin: `http://localhost/phpmyadmin`
   - Verifique se consegue aceder sem erros

### Teste 2: Verificação da Aplicação

1. **Acesso à Aplicação**:
   - Navegue para: `http://localhost/pdi_angola/frontend/`
   - Deve ver a página de login da aplicação

2. **Teste de Login**:
   - **Email**: `admin@pdi.gov.ao`
   - **Senha**: `admin123`
   - Deve conseguir aceder ao dashboard

3. **Teste das Funcionalidades**:
   - **Dashboard**: Verifique se as estatísticas são exibidas, incluindo os novos cards para Lotes, Empresas e Nível de Ocupação.
   - **Infraestruturas**: Teste a listagem (pode estar vazia inicialmente).
   - **Polos**: Verifique se os 5 polos são exibidos com as informações de lotes, empresas e ocupação.
   - **Lotes**: Navegue para a seção de Lotes e teste as operações CRUD (Criar, Ler, Atualizar, Eliminar).
   - **Empresas**: Navegue para a seção de Empresas e teste as operações CRUD (Criar, Ler, Atualizar, Eliminar).
   - **Mapa**: Confirme se o mapa carrega corretamente e se os popups dos polos exibem as novas informações de lotes e empresas.

### Teste 3: Verificação da API

1. **Teste das Rotas da API**:
   - `http://localhost/pdi_angola/backend/api.php/provincias`
   - `http://localhost/pdi_angola/backend/api.php/polos`
   - `http://localhost/pdi_angola/backend/api.php/lotes` (nova)
   - `http://localhost/pdi_angola/backend/api.php/empresas` (nova)
   - Deve retornar dados em formato JSON

2. **Teste de Autenticação**:
   - Tente aceder a rotas protegidas sem autenticação
   - Deve retornar erro 401 (Não autorizado)

### Teste 4: Verificação Responsiva

1. **Teste em Diferentes Resoluções**:
   - Desktop (1920x1080)
   - Tablet (768x1024)
   - Mobile (375x667)

2. **Teste em Diferentes Navegadores**:
   - Chrome
   - Firefox
   - Edge
   - Safari (se disponível)

---

## Resolução de Problemas

### Problema: Ícone do WAMP Vermelho ou Laranja

**Possíveis Causas e Soluções:**

1. **Porta 80 ocupada**:
   - Verifique se o IIS está a correr: `services.msc` > Desative "World Wide Web Publishing Service"
   - Ou altere a porta do Apache: `httpd.conf` > `Listen 8080`

2. **Porta 3306 ocupada (MySQL)**:
   - Verifique se há outro MySQL a correr
   - Pare outros serviços MySQL: `services.msc`

3. **Firewall do Windows**:
   - Adicione exceções para Apache e MySQL
   - Painel de Controlo > Sistema e Segurança > Firewall do Windows

### Problema: Erro "Call to undefined function mysqli_connect()"

**Solução:**
1. Verifique se a extensão `mysqli` está ativa no `php.ini`
2. Reinicie o WAMP após alterações
3. Confirme no phpinfo se a extensão está carregada

### Problema: Erro de Conexão com a Base de Dados

**Verificações:**
1. **Credenciais**: Confirme utilizador e senha no `config.php`
2. **Serviço MySQL**: Verifique se está a correr no WAMP
3. **Nome da Base de Dados**: Confirme se `pdi_angola` existe
4. **Permissões**: Verifique permissões do utilizador MySQL

### Problema: Erro 404 nas Rotas da API

**Soluções:**
1. **Mod_rewrite**: Ative no Apache
2. **Ficheiro .htaccess**: Verifique se existe e está correto
3. **AllowOverride**: Confirme se está definido como `All` no `httpd.conf`

### Problema: Mapas Não Carregam

**Verificações:**
1. **Conexão à Internet**: Necessária para tiles do OpenStreetMap
2. **JavaScript**: Verifique se não há erros na consola do navegador
3. **HTTPS**: Alguns recursos podem requerer HTTPS

### Problema: Sessões Não Funcionam

**Soluções:**
1. **Pasta de Sessões**: Verifique permissões em `C:\wamp64\tmp`
2. **Configuração PHP**: Confirme `session.save_path` no `php.ini`
3. **Cookies**: Verifique se o navegador aceita cookies

---

## Manutenção e Backup

### Backup Regular

**Backup da Base de Dados:**
1. **Via phpMyAdmin**:
   - Selecione a base de dados `pdi_angola`
   - Clique em "Exportar"
   - Escolha formato SQL
   - Descarregue o ficheiro

2. **Via Linha de Comandos**:
```bash
mysqldump -u root -p pdi_angola > backup_pdi_angola_YYYYMMDD.sql
```

**Backup dos Ficheiros:**
1. Copie toda a pasta `C:\wamp64\www\pdi_angola\`
2. Inclua ficheiros de configuração personalizados
3. Mantenha backups em local seguro (externo)

### Atualizações

**Atualização da Aplicação:**
1. Faça backup completo antes de atualizar
2. Substitua ficheiros mantendo configurações
3. Execute scripts de migração se necessário
4. Teste todas as funcionalidades após atualização

**Atualização do WAMP:**
1. Verifique compatibilidade com a aplicação
2. Faça backup das configurações personalizadas
3. Teste em ambiente de desenvolvimento primeiro

### Monitorização

**Logs a Verificar Regularmente:**
1. **Apache**: `C:\wamp64\logs\apache_error.log`
2. **MySQL**: `C:\wamp64\logs\mysql.log`
3. **PHP**: `C:\wamp64\logs\php_error.log`
4. **Aplicação**: `C:\wamp64\www\pdi_angola\backend\logs\app.log`

**Métricas de Performance:**
1. Tempo de resposta das páginas
2. Uso de memória PHP
3. Tamanho da base de dados
4. Espaço em disco disponível

### Segurança

**Medidas de Segurança Recomendadas:**

1. **Senhas Fortes**:
   - Altere a senha padrão do administrador
   - Use senhas complexas para MySQL
   - Atualize chaves secretas no `config.php`

2. **Atualizações**:
   - Mantenha WAMP atualizado
   - Aplique patches de segurança
   - Monitore vulnerabilidades conhecidas

3. **Acesso Restrito**:
   - Configure firewall adequadamente
   - Limite acesso ao phpMyAdmin
   - Use HTTPS em produção

4. **Backup Seguro**:
   - Encripte backups sensíveis
   - Armazene em locais seguros
   - Teste restauração regularmente

---

## Contacto e Suporte

Para questões técnicas ou suporte adicional:

- **Documentação**: Consulte os ficheiros na pasta `docs/`
- **Logs**: Verifique os ficheiros de log para diagnóstico


