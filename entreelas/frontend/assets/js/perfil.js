
const API_URL = '/Divaconnect/entreelas/backend/api.php';

let currentUser = null;

document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    
    document.getElementById('btnLogout').addEventListener('click', logout);
    document.getElementById('btnUploadFoto').addEventListener('click', () => document.getElementById('inputFoto').click());
    document.getElementById('inputFoto').addEventListener('change', handleFotoUpload);
    document.getElementById('btnRemoverFoto').addEventListener('click', removerFoto);
    document.getElementById('formPerfil').addEventListener('submit', salvarPerfil);
    document.getElementById('formSenha').addEventListener('submit', alterarSenha);
    document.getElementById('btnExcluirConta').addEventListener('click', excluirConta);
    
    document.getElementById('telefone').addEventListener('input', function(e) {
        this.value = phoneMask(this.value);
    });
});


function checkAuth() {
    const usuarioData = localStorage.getItem('usuario');
    
    if (!usuarioData) {
        window.location.href = 'login.html';
        return;
    }
    
    currentUser = JSON.parse(usuarioData);
    document.getElementById('userName').textContent = currentUser.nome.split(' ')[0];
    
    loadPerfil();
}

function logout() {
    localStorage.removeItem('usuario');
    localStorage.removeItem('token');
    window.location.href = 'login.html';
}


async function loadPerfil() {
    try {
        const response = await fetch(`${API_URL}?action=meu-perfil`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const usuario = data.data;
            preencherFormulario(usuario);
        } else {
            showMessage('Erro ao carregar perfil', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showMessage('Erro ao conectar com servidor', 'error');
    }
}

function preencherFormulario(usuario) {
    document.getElementById('nome').value = usuario.nome;
    document.getElementById('email').value = usuario.email;
    document.getElementById('telefone').value = usuario.telefone;
    document.getElementById('cidade').value = usuario.cidade;
    
    const iniciais = usuario.nome.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    document.getElementById('iniciais').textContent = iniciais;
    
    if (usuario.foto_perfil) {
        document.getElementById('fotoPerfil').src = `../backend/uploads/${usuario.foto_perfil}`;
        document.getElementById('fotoPerfil').style.display = 'block';
        document.getElementById('fotoPlaceholder').style.display = 'none';
        document.getElementById('btnRemoverFoto').style.display = 'block';
    }
    
    if (usuario.eh_prestadora == 1) {
        document.getElementById('secaoPrestadora').style.display = 'block';
        document.getElementById('prestadoraBadge').style.display = 'flex';
        document.getElementById('areaAtuacao').value = usuario.area_atuacao || '';
        document.getElementById('descricaoProfissional').value = usuario.descricao_profissional || '';
    }
}

async function handleFotoUpload(e) {
    const file = e.target.files[0];
    
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        alert('Por favor, selecione uma imagem');
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        alert('A imagem deve ter no máximo 5MB');
        return;
    }
    
    const formData = new FormData();
    formData.append('foto', file);
    
    try {
        const response = await fetch(`${API_URL}?action=upload-foto`, {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('fotoPerfil').src = e.target.result;
                document.getElementById('fotoPerfil').style.display = 'block';
                document.getElementById('fotoPlaceholder').style.display = 'none';
                document.getElementById('btnRemoverFoto').style.display = 'block';
            };
            reader.readAsDataURL(file);
            
            currentUser.foto_perfil = data.data.foto_perfil;
            localStorage.setItem('usuario', JSON.stringify(currentUser));
            
            showMessage('Foto atualizada com sucesso!', 'success');
        } else {
            alert(data.message || 'Erro ao enviar foto');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao enviar foto');
    }
}

async function removerFoto() {
    if (!confirm('Tem certeza que deseja remover sua foto de perfil?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}?action=remover-foto`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('fotoPerfil').style.display = 'none';
            document.getElementById('fotoPlaceholder').style.display = 'flex';
            document.getElementById('btnRemoverFoto').style.display = 'none';
            
            currentUser.foto_perfil = null;
            localStorage.setItem('usuario', JSON.stringify(currentUser));
            
            showMessage('Foto removida com sucesso!', 'success');
        } else {
            alert(data.message || 'Erro ao remover foto');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao remover foto');
    }
}


async function salvarPerfil(e) {
    e.preventDefault();
    clearErrors();
    
    const formData = {
        nome: document.getElementById('nome').value.trim(),
        telefone: document.getElementById('telefone').value.trim(),
        cidade: document.getElementById('cidade').value.trim()
    };
    
    if (currentUser.eh_prestadora == 1) {
        formData.area_atuacao = document.getElementById('areaAtuacao').value;
        formData.descricao_profissional = document.getElementById('descricaoProfissional').value.trim();
    }
    
    if (formData.nome.length < 3) {
        showFieldError('nomeError', 'Nome deve ter no mínimo 3 caracteres');
        return;
    }
    
    if (!formData.telefone) {
        showFieldError('telefoneError', 'Telefone é obrigatório');
        return;
    }
    
    if (!formData.cidade) {
        showFieldError('cidadeError', 'Cidade é obrigatória');
        return;
    }
    
    setLoading('btnSalvar', true);
    
    try {
        const response = await fetch(`${API_URL}?action=atualizar-perfil`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            Object.assign(currentUser, formData);
            localStorage.setItem('usuario', JSON.stringify(currentUser));
            
            showMessage('Perfil atualizado com sucesso!', 'success');
        } else {
            showMessage(data.message || 'Erro ao atualizar perfil', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showMessage('Erro ao conectar com servidor', 'error');
    } finally {
        setLoading('btnSalvar', false);
    }
}

async function alterarSenha(e) {
    e.preventDefault();
    
    const senhaAtual = document.getElementById('senhaAtual').value;
    const senhaNova = document.getElementById('senhaNova').value;
    const senhaConfirmar = document.getElementById('senhaConfirmar').value;
    
    if (!senhaAtual || !senhaNova || !senhaConfirmar) {
        showSenhaMessage('Preencha todos os campos', 'error');
        return;
    }
    
    if (senhaNova.length < 8) {
        showSenhaMessage('Nova senha deve ter no mínimo 8 caracteres', 'error');
        return;
    }
    
    if (senhaNova !== senhaConfirmar) {
        showSenhaMessage('As senhas não coincidem', 'error');
        return;
    }
    
    setLoading('btnSenha', true);
    
    try {
        const response = await fetch(`${API_URL}?action=alterar-senha`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                senha_atual: senhaAtual,
                senha_nova: senhaNova
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSenhaMessage('Senha alterada com sucesso!', 'success');
            document.getElementById('formSenha').reset();
        } else {
            showSenhaMessage(data.message || 'Erro ao alterar senha', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showSenhaMessage('Erro ao conectar com servidor', 'error');
    } finally {
        setLoading('btnSenha', false);
    }
}


async function excluirConta() {
    const confirmacao = prompt('Esta ação é PERMANENTE e não pode ser desfeita.\n\nDigite "EXCLUIR" para confirmar:');
    
    if (confirmacao !== 'EXCLUIR') {
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}?action=excluir-conta`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Conta excluída com sucesso. Você será redirecionado.');
            localStorage.clear();
            window.location.href = '../index.html';
        } else {
            alert(data.message || 'Erro ao excluir conta');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao conectar com servidor');
    }
}

function showMessage(message, type) {
    const container = document.getElementById('messageContainer');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    container.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    setTimeout(() => container.innerHTML = '', 5000);
}

function showSenhaMessage(message, type) {
    const container = document.getElementById('senhaMessage');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    container.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    setTimeout(() => container.innerHTML = '', 5000);
}

function showFieldError(fieldId, message) {
    document.getElementById(fieldId).textContent = message;
}

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
}

function setLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    const text = btn.querySelector('[id$="Text"]');
    const loader = btn.querySelector('[id$="Loader"]');
    
    btn.disabled = loading;
    text.style.display = loading ? 'none' : 'block';
    loader.style.display = loading ? 'block' : 'none';
}

function phoneMask(value) {
    if (!value) return '';
    value = value.replace(/\D/g, '');
    value = value.replace(/(\d{2})(\d)/, '($1) $2');
    value = value.replace(/(\d)(\d{4})$/, '$1-$2');
    return value;
}