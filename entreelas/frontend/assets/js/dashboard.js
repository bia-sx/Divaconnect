// Configuração da API
const API_URL = '/Divaconnect/entreelas/backend/api.php';

// Estado global
let currentUser = null;
let allServices = [];
let currentFilters = {
    search: '',
    categoria: '',
    cidade: ''
};

// Aguarda DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    // Verifica autenticação
    checkAuth();
    
    // Event listeners
    document.getElementById('btnLogout').addEventListener('click', logout);
    document.getElementById('btnSearch').addEventListener('click', applyFilters);
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') applyFilters();
    });
    document.getElementById('filterCategoria').addEventListener('change', applyFilters);
    document.getElementById('filterCidade').addEventListener('change', applyFilters);
    document.getElementById('btnClearFilters').addEventListener('click', clearFilters);
    
    // Modal
    document.getElementById('closeModal').addEventListener('click', closeModal);
    document.getElementById('btnCancelar').addEventListener('click', closeModal);
    document.getElementById('formSolicitacao').addEventListener('submit', enviarSolicitacao);
    
    // Fecha modal ao clicar fora
    document.getElementById('modalSolicitacao').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
});

// ========== AUTENTICAÇÃO ==========

async function checkAuth() {
    // Verifica se tem usuário no localStorage
    const usuarioData = localStorage.getItem('usuario');
    
    if (!usuarioData) {
        window.location.href = 'login.html';
        return;
    }
    
    currentUser = JSON.parse(usuarioData);
    
    // Atualiza interface com dados do usuário
    document.getElementById('userName').textContent = currentUser.nome.split(' ')[0];
    document.getElementById('welcomeName').textContent = currentUser.nome.split(' ')[0];
    
    // Carrega serviços
    loadServices();
}

async function logout() {
    try {
        await fetch(`${API_URL}?action=logout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
    } catch (error) {
        console.error('Erro ao fazer logout:', error);
    }
    
    // Limpa dados locais
    localStorage.removeItem('usuario');
    localStorage.removeItem('token');
    
    // Redireciona para login
    window.location.href = 'login.html';
}

// ========== SERVIÇOS ==========

async function loadServices() {
    showLoading(true);
    
    try {
        const response = await fetch(`${API_URL}?action=listar-servicos`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await response.json();
        
        if (data.success) {
            allServices = data.data || [];
            populateCityFilter();
            displayServices(allServices);
        } else {
            showError('Erro ao carregar serviços');
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro ao conectar com servidor');
    } finally {
        showLoading(false);
    }
}

function displayServices(services) {
    const servicesGrid = document.getElementById('servicesGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (!services || services.length === 0) {
        servicesGrid.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    servicesGrid.innerHTML = services.map(service => `
        <div class="service-card" data-service-id="${service.id}">
            <div class="service-header">
                <div class="service-icon">${getCategoryIcon(service.categoria)}</div>
                <div class="service-info-header">
                    <h4 class="service-title">${service.titulo}</h4>
                    <span class="service-category">${service.categoria}</span>
                </div>
            </div>
            
            <p class="service-description">${service.descricao}</p>
            
            <div class="service-footer">
                <div class="service-provider">
                    <div class="provider-avatar">${service.prestadora_nome.charAt(0).toUpperCase()}</div>
                    <div class="provider-info">
                        <span class="provider-name">${service.prestadora_nome}</span>
                        <span class="provider-location">📍 ${service.localizacao}</span>
                    </div>
                </div>
                ${service.preco_estimado ? `<span class="service-price">R$ ${parseFloat(service.preco_estimado).toFixed(2)}</span>` : ''}
            </div>
            
            <button class="btn-solicitar" onclick="openModal(${service.id})">
                Solicitar Serviço
            </button>
        </div>
    `).join('');
}

function getCategoryIcon(categoria) {
    const icons = {
        'Eletricista': '💡',
        'Babá': '👶',
        'Diarista': '🧹',
        'Cozinheira': '👩‍🍳',
        'Cuidadora de Idosos': '💅',
        'Manicure': '💅',
        'Costureira': '🧵',
        'Personal Trainer': '💪',
        'Psicóloga': '🧠',
        'Encanadora': '🔧',
        'Pintora': '🎨',
        'Jardineira': '🌱'
    };
    return icons[categoria] || '⭐';
}

// ========== FILTROS ==========

function applyFilters() {
    currentFilters.search = document.getElementById('searchInput').value.toLowerCase();
    currentFilters.categoria = document.getElementById('filterCategoria').value;
    currentFilters.cidade = document.getElementById('filterCidade').value;
    
    let filtered = allServices;
    
    // Filtro de busca
    if (currentFilters.search) {
        filtered = filtered.filter(s => 
            s.titulo.toLowerCase().includes(currentFilters.search) ||
            s.descricao.toLowerCase().includes(currentFilters.search) ||
            s.prestadora_nome.toLowerCase().includes(currentFilters.search)
        );
    }
    
    // Filtro de categoria
    if (currentFilters.categoria) {
        filtered = filtered.filter(s => s.categoria === currentFilters.categoria);
    }
    
    // Filtro de cidade
    if (currentFilters.cidade) {
        filtered = filtered.filter(s => s.localizacao === currentFilters.cidade);
    }
    
    displayServices(filtered);
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterCategoria').value = '';
    document.getElementById('filterCidade').value = '';
    currentFilters = { search: '', categoria: '', cidade: '' };
    displayServices(allServices);
}

function populateCityFilter() {
    const cities = [...new Set(allServices.map(s => s.localizacao))].sort();
    const select = document.getElementById('filterCidade');
    
    cities.forEach(city => {
        const option = document.createElement('option');
        option.value = city;
        option.textContent = city;
        select.appendChild(option);
    });
}

// ========== MODAL ==========

function openModal(serviceId) {
    const service = allServices.find(s => s.id === parseInt(serviceId));
    if (!service) return;
    
    const modalServiceInfo = document.getElementById('modalServiceInfo');
    modalServiceInfo.innerHTML = `
        <h4 style="margin-bottom: 8px;">${service.titulo}</h4>
        <p style="color: #666; margin-bottom: 8px;">${service.descricao}</p>
        <p style="font-weight: 600; color: #E85D4E;">Prestadora: ${service.prestadora_nome}</p>
        <p style="color: #666;">📍 ${service.localizacao}</p>
        ${service.preco_estimado ? `<p style="font-size: 20px; font-weight: 700; color: #E85D4E; margin-top: 12px;">R$ ${parseFloat(service.preco_estimado).toFixed(2)}</p>` : ''}
    `;
    
    // Armazena ID do serviço no formulário
    document.getElementById('formSolicitacao').dataset.serviceId = serviceId;
    
    // Limpa mensagem e campos
    document.getElementById('modalMessage').innerHTML = '';
    document.getElementById('mensagem').value = '';
    
    // Mostra modal
    document.getElementById('modalSolicitacao').classList.add('active');
}

function closeModal() {
    document.getElementById('modalSolicitacao').classList.remove('active');
}

async function enviarSolicitacao(e) {
    e.preventDefault();
    
    const serviceId = document.getElementById('formSolicitacao').dataset.serviceId;
    const mensagem = document.getElementById('mensagem').value.trim();
    
    setLoadingButton(true);
    
    try {
        const response = await fetch(`${API_URL}?action=solicitar-servico`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                servico_id: parseInt(serviceId),
                mensagem: mensagem
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showModalMessage('Solicitação enviada com sucesso! ✅', 'success');
            setTimeout(() => {
                closeModal();
            }, 2000);
        } else {
            showModalMessage(data.message || 'Erro ao enviar solicitação', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showModalMessage('Erro ao conectar com servidor', 'error');
    } finally {
        setLoadingButton(false);
    }
}

// ========== UTILIDADES ==========

function showLoading(show) {
    document.getElementById('loadingServices').style.display = show ? 'block' : 'none';
}

function showError(message) {
    const servicesGrid = document.getElementById('servicesGrid');
    servicesGrid.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #DC3545;">
            <h4>❌ ${message}</h4>
        </div>
    `;
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
    const btn = document.getElementById('btnEnviarSolicitacao');
    const text = document.getElementById('btnSolicitarText');
    const loader = document.getElementById('btnSolicitarLoader');
    
    btn.disabled = loading;
    text.style.display = loading ? 'none' : 'block';
    loader.style.display = loading ? 'block' : 'none';
}