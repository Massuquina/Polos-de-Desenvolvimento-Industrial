// Script para corrigir o problema do modal
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se o botão existe
    const newUsuarioBtn = document.getElementById('newUsuarioBtn');
    if (newUsuarioBtn) {
        console.log('Botão Novo Usuário encontrado, adicionando evento');
        
        // Adicionar evento diretamente
        newUsuarioBtn.addEventListener('click', function() {
            const modal = document.getElementById('createUsuarioModal');
            if (modal) {
                console.log('Abrindo modal diretamente');
                modal.style.display = 'flex';
            } else {
                console.error('Modal não encontrado');
            }
        });
    } else {
        console.error('Botão Novo Usuário não encontrado');
    }
    
    // Adicionar evento para fechar o modal
    const closeCreateModal = document.getElementById('closeCreateModal');
    if (closeCreateModal) {
        closeCreateModal.addEventListener('click', function() {
            const modal = document.getElementById('createUsuarioModal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    // Fechar ao clicar fora do modal
    const createModal = document.getElementById('createUsuarioModal');
    if (createModal) {
        createModal.addEventListener('click', function(event) {
            if (event.target === createModal) {
                createModal.style.display = 'none';
            }
        });
    }
});