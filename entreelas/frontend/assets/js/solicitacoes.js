// Configuração da API
const API_URL = '/Divaconnect/entreelas/backend/api.php';

// Estado
let currentUser = null;
let currentTab = 'enviadas';

// Aguarda DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    
    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;
            switchTab(tab);
        });
    });
    
    // Logout
    document.getElementById('btnLogout').addEventListener('click', logout);
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
    
    // Verifica se é prestadora para mostrar aba de recebidas
    if (!currentUser.eh_prestadora || currentUser.eh_prestadora === '0') {
        document.getElementById('tabRecebidas').style.display = 'none';
    }
    
    // Carrega solicitações
    loadSolicitacoes();
}

function logout() {
    localStorage.removeItem('usuario');
    localStorage.removeItem('token');
    window.location.href = 'login.html';
}

// ========== TABS ==========

function switchTab(tab) {
    currentTab = tab;
    
    // Atualiza botões
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    
    // Atualiza conteúdo
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tab}`).classList.add('active');
    
    // Carrega solicitações da aba
    loadSolicitacoes();
}

// ========== CARREGAR SOLICITAÇÕES ==========

async function loadSolicitacoes() {
    const tipo = currentTab;
    const loadingId = `loading${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
    const listId = `list${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
    const emptyId = `empty${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
    
    showLoading(loadingId, true);
    
    try {
        const action = tipo === 'enviadas' ? 'minhas-solicitacoes' : 'solicitacoes-recebidas';
        
        const response = await fetch(`${API_URL}?action=${action}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const solicitacoes = data.data || [];
            
            if (solicitacoes.length === 0) {
                document.getElementById(emptyId).style.display = 'block';
                document.getElementById(listId).innerHTML = '';
            } else {
                document.getElementById(emptyId).style.display = 'none';
                displaySolicitacoes(solicitacoes, listId, tipo);
            }
        } else {
            showError(listId, data.message || 'Erro ao carregar solicitações');
        }
    } catch (error) {
        console.error('Erro:', error);
        showError(listId, 'Erro ao conectar com servidor');
    } finally {
        showLoading(loadingId, false);
    }
}

function displaySolicitacoes(solicitacoes, listId, tipo) {
    const list = document.getElementById(listId);
    
    list.innerHTML = solicitacoes.map(sol => {
        const isEnviada = tipo === 'enviadas';
        const pessoa = isEnviada ? sol.prestadora_nome : sol.cliente_nome;
        const telefone = isEnviada ? sol.prestadora_telefone : sol.cliente_telefone;
        
        return `
            <div class="solicitacao-card ${sol.status}">
                <div class="solicitacao-header">
                    <div class="solicitacao-info">
                        <h4 class="solicitacao-title">${sol.servico_titulo}</h4>
                        <div class="solicitacao-meta">
                            <span class="meta-item">📅 ${formatDate(sol.data_solicitacao)}</span>
                            <span class="meta-item">📍 ${sol.localizacao}</span>
                            ${sol.preco_estimado ? `<span class="meta-item">💰 R$ ${parseFloat(sol.preco_estimado).toFixed(2)}</span>` : ''}
                        </div>
                    </div>
                    <span class="status-badge ${sol.status}">
                        ${getStatusText(sol.status)}
                    </span>
                </div>
                
                <div class="solicitacao-body">
                    <p class="solicitacao-descricao">${sol.servico_descricao}</p>
                    ${sol.mensagem ? `
                        <div class="solicitacao-mensagem">
                            <strong>Mensagem:</strong> ${sol.mensagem}
                        </div>
                    ` : ''}
                </div>
                
                <div class="solicitacao-footer">
                    <div class="solicitante-info">
                        <div class="solicitante-avatar">${pessoa.charAt(0).toUpperCase()}</div>
                        <div class="solicitante-dados">
                            <h5>${pessoa}</h5>
                            <p>${isEnviada ? 'Prestadora' : 'Cliente'}</p>
                        </div>
                    </div>
                    
                    <div class="solicitacao-actions">
                        ${renderActions(sol, tipo, telefone)}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function renderActions(sol, tipo, telefone) {
    if (tipo === 'recebidas' && sol.status === 'pendente') {
        return `
            <button class="btn-action btn-aceitar" onclick="responderSolicitacao(${sol.id}, 'aceita')">
                ✓ Aceitar
            </button>
            <button class="btn-action btn-recusar" onclick="responderSolicitacao(${sol.id}, 'recusada')">
                ✗ Recusar
            </button>
        `;
    }
    
    if (sol.status === 'aceita') {
        return `
            <button class="btn-action btn-contato" onclick="entrarEmContato('${telefone}')">
                💬 Entrar em Contato
            </button>
        `;
    }
    
    return '';
}

// ========== RESPONDER SOLICITAÇÃO ==========

async function responderSolicitacao(solicitacaoId, status) {
    if (!confirm(`Tem certeza que deseja ${status === 'aceita' ? 'aceitar' : 'recusar'} esta solicitação?`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}?action=responder-solicitacao`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                solicitacao_id: solicitacaoId,
                status: status
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            loadSolicitacoes();
        } else {
            alert(data.message || 'Erro ao responder solicitação');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao conectar com servidor');
    }
}

function entrarEmContato(telefone) {
    const limpo = telefone.replace(/\D/g, '');
    window.open(`https://wa.me/55${limpo}`, '_blank');
}

// ========== UTILIDADES ==========

function getStatusText(status) {
    const texts = {
        'pendente': '⏳ Pendente',
        'aceita': '✓ Aceita',
        'recusada': '✗ Recusada'
    };
    return texts[status] || status;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showLoading(id, show) {
    const element = document.getElementById(id);
    if (element) {
        element.style.display = show ? 'block' : 'none';
    }
}

function showError(listId, message) {
    document.getElementById(listId).innerHTML = `
        <div style="text-align: center; padding: 40px; color: #DC3545;">
            <h4>❌ ${message}</h4>
        </div>
    `;
}