# 🚀 Instruções Rápidas de Instalação - PDI Angola

## ⚡ Instalação Express (5 minutos)

### 1️⃣ Instalar WAMP Server
1. Descarregue WAMP Server: https://www.wampserver.com/
2. Execute como **Administrador**
3. Instale no diretório padrão: `C:\wamp64`
4. Aguarde até o ícone ficar **VERDE** na barra de tarefas

### 2️⃣ Extrair a Aplicação
1. Extraia o ficheiro `pdi_angola_completo.zip`
2. Copie a pasta `pdi_angola` para: `C:\wamp64\www\`
3. Resultado: `C:\wamp64\www\pdi_angola\`

### 3️⃣ Configurar Base de Dados
1. Abra: http://localhost/phpmyadmin
2. Clique em **"Nova"** → Nome: `pdi_angola` → **"Criar"**
3. Selecione a base `pdi_angola` → **"Importar"**
4. Escolha: `pdi_angola\database\database_schema.sql` → **"Executar"**
5. Repita com: `pdi_angola\database\insert_data.sql`

### 4️⃣ Aceder à Aplicação
1. Abra: http://localhost/pdi_angola/frontend/
2. **Login**: `admin@pdi.gov.ao`
3. **Senha**: `admin123`
4. **Pronto!** 🎉

---

## 📋 Verificação Rápida

### ✅ Checklist de Funcionamento
- [ ] WAMP ícone verde
- [ ] phpMyAdmin acessível
- [ ] Base de dados `pdi_angola` criada
- [ ] 18 províncias carregadas
- [ ] 5 polos carregados
- [ ] Login funciona
- [ ] Dashboard exibe estatísticas
- [ ] Mapa carrega

### 🔧 Resolução Rápida de Problemas

**WAMP não fica verde:**
- Feche Skype, IIS, outros servidores
- Execute WAMP como Administrador
- Reinicie o computador

**Erro de base de dados:**
- Verifique se MySQL está ativo no WAMP
- Confirme se a base `pdi_angola` existe
- Reimporte os ficheiros SQL

**Página não carrega:**
- Verifique se Apache está ativo
- Confirme o caminho: `C:\wamp64\www\pdi_angola\`
- Teste: http://localhost/ primeiro

---

## 📚 Documentação Completa

- **[Guia Detalhado](docs/INSTALACAO_WAMP.md)** - Instalação completa passo-a-passo
- **[Manual do Utilizador](docs/MANUAL_USUARIO.md)** - Como usar todas as funcionalidades
- **[README.md](README.md)** - Informações técnicas completas

---

## 🆘 Suporte

**Problemas?** Consulte a documentação completa ou:
- Verifique os logs em: `C:\wamp64\logs\`
- Teste em modo incógnito do navegador
- Desative antivírus temporariamente

**Tudo funcionando?** Altere a senha padrão em **Configurações → Perfil**

---

*Desenvolvido para Angola 🇦🇴 - Sistema PDI Angola v1.0*

