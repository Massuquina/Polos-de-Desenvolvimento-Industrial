<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Utilizadores - PDI Angola</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #c41e3a;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            margin-bottom: 10px;
        }

        .controls {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn {
            background: #c41e3a;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn:hover {
            background: #a01a32;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .loading {
            text-align: center;
            padding: 20px;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Gestão de Utilizadores</h1>
            <p>PDI Angola - Sistema de Gestão de Infraestruturas</p>
        </div>

        <div id="alertContainer"></div>

        <div class="controls">
            <button class="btn" onclick="loadUsers()">
                <i class="fas fa-refresh"></i> Atualizar Lista
            </button>
            <button class="btn btn-success" onclick="showCreateUserModal()">
                <i class="fas fa-plus"></i> Novo Utilizador
            </button>
            <button class="btn" onclick="loadAccessLevels()">
                <i class="fas fa-shield-alt"></i> Níveis de Acesso
            </button>
        </div>

        <div class="table-container">
            <div id="loadingIndicator" class="loading">
                <i class="fas fa-spinner fa-spin"></i> Carregando utilizadores...
            </div>
            <div id="usersTable" class="hidden">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Nível</th>
                            <th>Status</th>
                            <th>Último Login</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para criar/editar utilizador -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUserModal()">&times;</span>
            <h2 id="modalTitle">Novo Utilizador</h2>
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                
                <div class="form-group">
                    <label for="nome">Nome Completo *</label>
                    <input type="text" id="nome" name="nome" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="senha">Senha *</label>
                    <input type="password" id="senha" name="senha" required>
                    <small>Mínimo 6 caracteres</small>
                </div>

                <div class="form-group">
                    <label for="nivel_acesso_id">Nível de Acesso *</label>
                    <select id="nivel_acesso_id" name="nivel_acesso_id" required>
                        <option value="">Selecione um nível</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ativo">Status</label>
                    <select id="ativo" name="ativo">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>

                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = window.location.origin + window.location.pathname.replace('admin_users.php', 'api.php');
        let currentUser = null;

        // Verificar autenticação ao carregar
        window.onload = function() {
            checkAuth();
        };

        async function checkAuth() {
            try {
                const response = await fetch(`${API_BASE}/auth/check`, {
                    credentials: 'include'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    currentUser = data.user;
                    
                    // Verificar se é administrador
                    if (data.user.nivel_nome.toLowerCase() !== 'administrador') {
                        showAlert('Acesso negado. Apenas administradores podem acessar esta página.', 'error');
                        return;
                    }
                    
                    loadUsers();
                    loadAccessLevels();
                } else {
                    showAlert('Sessão expirada. Faça login novamente.', 'error');
                    setTimeout(() => {
                        window.location.href = '../frontend/';
                    }, 2000);
                }
            } catch (error) {
                showAlert('Erro de conexão. Verifique sua conexão.', 'error');
            }
        }

        async function loadUsers() {
            try {
                document.getElementById('loadingIndicator').classList.remove('hidden');
                document.getElementById('usersTable').classList.add('hidden');

                const response = await fetch(`${API_BASE}/admin/users`, {
                    credentials: 'include'
                });

                if (response.ok) {
                    const users = await response.json();
                    displayUsers(users);
                } else {
                    const error = await response.json();
                    showAlert('Erro ao carregar utilizadores: ' + error.error, 'error');
                }
            } catch (error) {
                showAlert('Erro de conexão: ' + error.message, 'error');
            } finally {
                document.getElementById('loadingIndicator').classList.add('hidden');
                document.getElementById('usersTable').classList.remove('hidden');
            }
        }

        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';

            users.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.nome}</td>
                    <td>${user.email}</td>
                    <td>${user.nivel_nome}</td>
                    <td>
                        <span class="status-${user.ativo ? 'active' : 'inactive'}">
                            ${user.ativo ? 'Ativo' : 'Inativo'}
                        </span>
                    </td>
                    <td>${user.ultimo_login ? new Date(user.ultimo_login).toLocaleString('pt-PT') : 'Nunca'}</td>
                    <td>${new Date(user.created_at).toLocaleString('pt-PT')}</td>
                    <td>
                        <button class="btn btn-warning" onclick="editUser(${user.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn ${user.ativo ? 'btn-danger' : 'btn-success'}" 
                                onclick="toggleUserStatus(${user.id})" 
                                title="${user.ativo ? 'Desativar' : 'Ativar'}">
                            <i class="fas fa-${user.ativo ? 'ban' : 'check'}"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        async function loadAccessLevels() {
            try {
                const response = await fetch(`${API_BASE}/admin/access-levels`, {
                    credentials: 'include'
                });

                if (response.ok) {
                    const levels = await response.json();
                    const select = document.getElementById('nivel_acesso_id');
                    select.innerHTML = '<option value="">Selecione um nível</option>';
                    
                    levels.forEach(level => {
                        const option = document.createElement('option');
                        option.value = level.id;
                        option.textContent = level.nome;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Erro ao carregar níveis de acesso:', error);
            }
        }

        function showCreateUserModal() {
            document.getElementById('modalTitle').textContent = 'Novo Utilizador';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userModal').style.display = 'block';
        }

        async function editUser(userId) {
            try {
                const response = await fetch(`${API_BASE}/admin/${userId}`, {
                    credentials: 'include'
                });

                if (response.ok) {
                    const user = await response.json();
                    document.getElementById('modalTitle').textContent = 'Editar Utilizador';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('nome').value = user.nome;
                    document.getElementById('email').value = user.email;
                    document.getElementById('senha').value = '';
                    document.getElementById('senha').placeholder = 'Deixe em branco para manter a senha atual';
                    document.getElementById('nivel_acesso_id').value = user.nivel_acesso_id;
                    document.getElementById('ativo').value = user.ativo ? '1' : '0';
                    document.getElementById('userModal').style.display = 'block';
                } else {
                    const error = await response.json();
                    showAlert('Erro ao carregar utilizador: ' + error.error, 'error');
                }
            } catch (error) {
                showAlert('Erro de conexão: ' + error.message, 'error');
            }
        }

        async function toggleUserStatus(userId) {
            if (!confirm('Tem certeza que deseja alterar o status deste utilizador?')) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/admin/${userId}/toggle-status`, {
                    method: 'PATCH',
                    credentials: 'include'
                });

                if (response.ok) {
                    showAlert('Status do utilizador alterado com sucesso!', 'success');
                    loadUsers();
                } else {
                    const error = await response.json();
                    showAlert('Erro ao alterar status: ' + error.error, 'error');
                }
            } catch (error) {
                showAlert('Erro de conexão: ' + error.message, 'error');
            }
        }

        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const userData = Object.fromEntries(formData.entries());
            
            // Remover campos vazios
            if (!userData.senha) {
                delete userData.senha;
            }

            const isEdit = userData.id !== '';
            const url = isEdit ? `${API_BASE}/admin/${userData.id}` : `${API_BASE}/admin/users`;
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify(userData)
                });

                if (response.ok) {
                    const result = await response.json();
                    showAlert(result.message || 'Utilizador salvo com sucesso!', 'success');
                    closeUserModal();
                    loadUsers();
                } else {
                    const error = await response.json();
                    showAlert('Erro ao salvar utilizador: ' + error.error, 'error');
                }
            } catch (error) {
                showAlert('Erro de conexão: ' + error.message, 'error');
            }
        });

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal) {
                closeUserModal();
            }
        }
    </script>
</body>
</html>
