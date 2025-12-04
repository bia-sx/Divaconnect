// Configuração da API
const API_URL = '/Divaconnect/entreelas/backend/api.php';

// Estado
let currentUser = null;
let editandoServicoId = null;

// Aguarda DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    
    // Event listeners
    document.getElementById('btnLogout').addEventListener('click', logout);
    document.getElementById('btnAddServico').addEventListener('click', () => openModal());
    document.getElementById('closeModal').addEventListener('click', closeModal);
    document.getElementById('btnCancelar').addEventListener('click', closeModal);
    document.getElementById('formServico').addEventListener('submit', salvarServico);
    
    // Fecha modal ao clicar fora
    document.getElementById('modalServico').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
});

// ========== AUTENTICAÇÃO ==========

function checkAuth() {
    const usuarioData = localStorage.getItem('usuario');
    
    if (!usuarioData) {
        window.location.href = 'login.html';
        return;
    }
    
    currentUser = JSON.parse(usuarioData);
    document.getElementById('userName').textContent = currentUser.nome.split(' ')[0];
    
    // Verifica se é prestadora
    if (!currentUser.eh_prestadora || currentUser.eh_prestadora === '0') {
        alert('Apenas prestadoras podem acessar esta página');
        window.location.href = 'dashboard.html';
        return;
    }
    
    loadServicos();
}

function logout() {
    localStorage.removeItem('usuario');
    localStorage.removeItem('token');
    window.location.href = 'login.html';
}

// ========== CARREGAR SERVIÇOS ==========

async function loadServicos() {
    showLoading(true);
    
    try {
        const response = await fetch(`${API_URL}?action=meus-servicos`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const servicos = data.data || [];
            
            if (servicos.length === 0) {
                document.getElementById('emptyServicos').style.display = 'block';
                document.getElementById('servicosGrid').innerHTML = '';
            } else {
                document.getElementById('emptyServicos').style.display = 'none';
                displayServicos(servicos);
            }
        } else {
            showError(data.message || 'Erro ao carregar serviços');
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro ao conectar com servidor');
    } finally {
        showLoading(false);
    }
}

function displayServicos(servicos) {
    const grid = document.getElementById('servicosGrid');
    
    grid.innerHTML = servicos.map(s => `
        <div class="servico-card-own">
            <span class="servico-status ${s.ativo == 1 ? 'status-ativo' : 'status-inativo'}">
                ${s.ativo == 1 ? '✓ Ativo' : '✗ Inativo'}
            </span>
            
            <div class="servico-card-header">
                <div class="servico-card-icon">${getCategoryIcon(s.categoria)}</div>
                <div class="servico-card-info">
                    <h4 class="servico-card-title">${s.titulo}</h4>
                    <span class="servico-card-category">${s.categoria}</span>
                </div>
            </div>
            
            <p class="servico-card-description">${s.descricao}</p>
            
            <div class="servico-card-meta">
                <span class="meta-badge">📍 ${s.localizacao}</span>
                ${s.preco_estimado ? `<span class="meta-badge">💰 R$ ${parseFloat(s.preco_estimado).toFixed(2)}</span>` : ''}
                ${s.solicitacoes_pendentes > 0 ? `<span class="meta-badge">📋 ${s.solicitacoes_pendentes} pendente(s)</span>` : ''}
            </div>
            
            <div class="servico-card-actions">
                <button class="btn-action-card btn-editar" onclick="editarServico(${s.id})">
                    ✏️ Editar
                </button>
                <button class="btn-action-card btn-toggle" onclick="toggleServico(${s.id}, ${s.ativo})">
                    ${s.ativo == 1 ? '⏸️ Desativar' : '▶️ Ativar'}
                </button>
                <button class="btn-action-card btn-excluir" onclick="excluirServico(${s.id})">
                    🗑️ Excluir
                </button>
            </div>
        </div>
    `).join('');
}

function getCategoryIcon(categoria) {
    const icons = {
        'Eletricista': '💡', 'Babá': '👶', 'Diarista': '🧹',
        'Cozinheira': '👩‍🍳', 'Cuidadora de Idosos': '💅', 'Manicure': '💅',
        'Costureira': '🧵', 'Personal Trainer': '💪', 'Psicóloga': '🧠',
        'Encanadora': '🔧', 'Pintora': '🎨', 'Jardineira': '🌱'
    };
    return icons[categoria] || '⭐';
}

// ========== MODAL ==========

function openModal(servico = null) {
    editandoServicoId = servico ? servico.id : null;
    
    document.getElementById('modalTitle').textContent = servico ? 'Editar Serviço' : 'Adicionar Serviço';
    document.getElementById('btnSalvarText').textContent = servico ? 'Salvar Alterações' : 'Adicionar Serviço';
    
    if (servico) {
        document.getElementById('servicoId').value = servico.id;
        document.getElementById('titulo').value = servico.titulo;
        document.getElementById('categoria').value = servico.categoria;
        document.getElementById('descricao').value = servico.descricao;
        document.getElementById('precoEstimado').value = servico.preco_estimado || '';
        document.getElementById('localizacao').value = servico.localizacao;
    } else {
        document.getElementById('formServico').reset();
        document.getElementById('servicoId').value = '';
    }
    
    clearErrors();
    document.getElementById('modalMessage').innerHTML = '';
    document.getElementById('modalServico').classList.add('active');
}

function closeModal() {
    document.getElementById('modalServico').classList.remove('active');
    document.getElementById('formServico').reset();
    editandoServicoId = null;
}

async function editarServico(id) {
    try {
        const response = await fetch(`${API_URL}?action=meus-servicos`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const servico = data.data.find(s => s.id == id);
            if (servico) {
                openModal(servico);
            }
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao carregar serviço');
    }
}

// ========== SALVAR SERVIÇO ==========

async function salvarServico(e) {
    e.preventDefault();
    clearErrors();
    
    const formData = {
        titulo: document.getElementById('titulo').value.trim(),
        categoria: document.getElementById('categoria').value,
        descricao: document.getElementById('descricao').value.trim(),
        preco_estimado: document.getElementById('precoEstimado').value || null,
        localizacao: document.getElementById('localizacao').value.trim()
    };
    
    // Validações
    let hasError = false;
    
    if (formData.titulo.length < 5) {
        showFieldError('tituloError', 'Título deve ter no mínimo 5 caracteres');
        hasError = true;
    }
    
    if (!formData.categoria) {
        showFieldError('categoriaError', 'Selecione uma categoria');
        hasError = true;
    }
    
    if (formData.descricao.length < 20) {
        showFieldError('descricaoError', 'Descrição deve ter no mínimo 20 caracteres');
        hasError = true;
    }
    
    if (!formData.localizacao) {
        showFieldError('localizacaoError', 'Localização é obrigatória');
        hasError = true;
    }
    
    if (hasError) return;
    
    setLoadingButton(true);
    
    try {
        let action = 'criar-servico';
        
        if (editandoServicoId) {
            action = 'atualizar-servico';
            formData.servico_id = editandoServicoId;
        }
        
        const response = await fetch(`${API_URL}?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showModalMessage(data.message, 'success');
            setTimeout(() => {
                closeModal();
                loadServicos();
            }, 1500);
        } else {
            showModalMessage(data.message || 'Erro ao salvar serviço', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showModalMessage('Erro ao conectar com servidor', 'error');
    } finally {
        setLoadingButton(false);
    }
}

// ========== TOGGLE E EXCLUIR ==========

async function toggleServico(id, ativo) {
    const novoStatus = ativo == 1 ? 0 : 1;
    
    try {
        const response = await fetch(`${API_URL}?action=atualizar-servico`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                servico_id: id,
                ativo: novoStatus
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadServicos();
        } else {
            alert(data.message || 'Erro ao atualizar serviço');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao conectar com servidor');
    }
}

async function excluirServico(id) {
    if (!confirm('Tem certeza que deseja excluir este serviço? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}?action=deletar-servico`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ servico_id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            loadServicos();
        } else {
            alert(data.message || 'Erro ao excluir serviço');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao conectar com servidor');
    }
}

// ========== UTILIDADES ==========

function showLoading(show) {
    document.getElementById('loadingServicos').style.display = show ? 'block' : 'none';
}

function showError(message) {
    document.getElementById('servicosGrid').innerHTML = `
        <div style="text-align: center; padding: 40px; color: #DC3545;">
            <h4>❌ ${message}</h4>
        </div>
    `;
}

function showFieldError(fieldId, message) {
    const errorElement = document.getElementById(fieldId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.color = '#DC3545';
    }
}

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(el => {
        el.textContent = '';
    });
}

function showModalMessage(message, type) {
    const container = document.getElementById('modalMessage');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    container.innerHTML = `
        <div class="alert ${alertClass}" style="margin-bottom: 16px; padding: 12px; border-radius: 8px;">
            ${message}
        </div>
    `;
}

function setLoadingButton(loading) {
    const btn = document.getElementById('btnSalvar');
    const text = document.getElementById('btnSalvarText');
    const loader = document.getElementById('btnSalvarLoader');
    
    btn.disabled = loading;
    text.style.display = loading ? 'none' : 'block';
    loader.style.display = loading ? 'block' : 'none';
}