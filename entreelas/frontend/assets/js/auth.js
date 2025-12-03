// Configuração da API
const API_URL = '/Divaconnect/entreelas/backend/api.php';

// Aguarda o DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== LOGIN ==========
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        initLoginPage();
    }

    // ========== CADASTRO ==========
    const cadastroForm = document.getElementById('cadastroForm');
    if (cadastroForm) {
        initCadastroPage();
    }
});

// ========== FUNÇÕES DO LOGIN ==========
function initLoginPage() {
    const loginForm = document.getElementById('loginForm');
    const togglePassword = document.getElementById('togglePassword');
    const senhaInput = document.getElementById('senha');

    // Toggle mostrar/ocultar senha
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
            senhaInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }

    // Submit do formulário
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Limpa erros anteriores
        clearErrors();
        clearMessages();

        // Pega os dados
        const email = document.getElementById('email').value.trim();
        const senha = document.getElementById('senha').value;

        // Validações
        let hasError = false;

        if (!email || !validateEmail(email)) {
            showFieldError('emailError', 'Email inválido');
            hasError = true;
        }

        if (!senha || senha.length < 8) {
            showFieldError('senhaError', 'Senha deve ter no mínimo 8 caracteres');
            hasError = true;
        }

        if (hasError) return;

        // Mostra loading
        setLoading(true);

        try {
            const response = await fetch(`${API_URL}?action=login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                mode: 'cors',
                credentials: 'include',
                body: JSON.stringify({ email, senha })
            });

            const data = await response.json();

            if (data.success) {
                // Salva dados no localStorage
                localStorage.setItem('usuario', JSON.stringify(data.usuario));
                localStorage.setItem('token', data.token || 'logged');
                
                showMessage('Login realizado com sucesso! Redirecionando...', 'success');
                
                // Redireciona após 1 segundo
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 1000);
            } else {
                showMessage(data.message || 'Erro ao fazer login', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showMessage('Erro ao conectar com o servidor', 'error');
        } finally {
            setLoading(false);
        }
    });
}

// ========== FUNÇÕES DO CADASTRO ==========
function initCadastroPage() {
    const cadastroForm = document.getElementById('cadastroForm');
    const ehPrestadoraCheckbox = document.getElementById('ehPrestadora');
    const prestadoraFields = document.getElementById('prestadoraFields');
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const senhaInput = document.getElementById('senha');
    const confirmarSenhaInput = document.getElementById('confirmarSenha');
    const telefoneInput = document.getElementById('telefone');

    // Máscara de telefone
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            this.value = phoneMask(this.value);
        });
    }

    // Toggle campos de prestadora
    if (ehPrestadoraCheckbox && prestadoraFields) {
        ehPrestadoraCheckbox.addEventListener('change', function() {
            if (this.checked) {
                prestadoraFields.style.display = 'block';
                document.getElementById('areaAtuacao').setAttribute('required', 'required');
            } else {
                prestadoraFields.style.display = 'none';
                document.getElementById('areaAtuacao').removeAttribute('required');
                document.getElementById('areaAtuacao').value = '';
                document.getElementById('descricaoProfissional').value = '';
            }
        });
    }

    // Toggle mostrar/ocultar senha
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
            senhaInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }

    if (toggleConfirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmarSenhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmarSenhaInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }

    // Submit do formulário
    cadastroForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Limpa erros anteriores
        clearErrors();
        clearMessages();

        // Pega os dados
        const formData = {
            nome: document.getElementById('nome').value.trim(),
            email: document.getElementById('email').value.trim(),
            telefone: document.getElementById('telefone').value.trim(),
            cidade: document.getElementById('cidade').value.trim(),
            senha: document.getElementById('senha').value,
            confirmarSenha: document.getElementById('confirmarSenha').value,
            ehPrestadora: document.getElementById('ehPrestadora').checked,
            areaAtuacao: document.getElementById('areaAtuacao').value,
            descricaoProfissional: document.getElementById('descricaoProfissional').value.trim(),
            termos: document.getElementById('termos').checked
        };

        // Validações
        let hasError = false;

        if (!formData.nome || formData.nome.length < 3) {
            showFieldError('nomeError', 'Nome deve ter no mínimo 3 caracteres');
            hasError = true;
        }

        if (!formData.email || !validateEmail(formData.email)) {
            showFieldError('emailError', 'Email inválido');
            hasError = true;
        }

        if (!formData.telefone) {
            showFieldError('telefoneError', 'Telefone é obrigatório');
            hasError = true;
        }

        if (!formData.cidade) {
            showFieldError('cidadeError', 'Cidade é obrigatória');
            hasError = true;
        }

        if (!formData.senha || formData.senha.length < 8) {
            showFieldError('senhaError', 'Senha deve ter no mínimo 8 caracteres');
            hasError = true;
        }

        if (formData.senha !== formData.confirmarSenha) {
            showFieldError('confirmarSenhaError', 'As senhas não coincidem');
            hasError = true;
        }

        if (formData.ehPrestadora && !formData.areaAtuacao) {
            showFieldError('areaAtuacaoError', 'Selecione uma área de atuação');
            hasError = true;
        }

        if (!formData.termos) {
            showMessage('Você deve aceitar os termos de uso', 'error');
            hasError = true;
        }

        if (hasError) return;

        // Mostra loading
        setLoading(true);

        try {
            const response = await fetch(`${API_URL}?action=cadastro`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                mode: 'cors',
                credentials: 'include',
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                showMessage('Cadastro realizado com sucesso! Redirecionando para login...', 'success');
                
                // Redireciona após 2 segundos
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                showMessage(data.message || 'Erro ao realizar cadastro', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showMessage('Erro ao conectar com o servidor', 'error');
        } finally {
            setLoading(false);
        }
    });
}

// ========== FUNÇÕES AUXILIARES ==========

// Mostra erro em campo específico
function showFieldError(fieldId, message) {
    const errorElement = document.getElementById(fieldId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.color = '#DC3545';
    }
}

// Limpa todos os erros
function clearErrors() {
    document.querySelectorAll('.form-error').forEach(el => {
        el.textContent = '';
    });
}

// Mostra mensagem geral
function showMessage(message, type) {
    const messageContainer = document.getElementById('messageContainer');
    if (!messageContainer) return;

    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    messageContainer.innerHTML = `
        <div class="alert ${alertClass}">
            ${message}
        </div>
    `;
}

// Limpa mensagens
function clearMessages() {
    const messageContainer = document.getElementById('messageContainer');
    if (messageContainer) {
        messageContainer.innerHTML = '';
    }
}

// Controla estado de loading do botão
function setLoading(isLoading) {
    const btnSubmit = document.getElementById('btnSubmit');
    const btnText = document.getElementById('btnText');
    const btnLoader = document.getElementById('btnLoader');

    if (isLoading) {
        btnSubmit.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'block';
    } else {
        btnSubmit.disabled = false;
        btnText.style.display = 'block';
        btnLoader.style.display = 'none';
    }
}

// Valida email (já definida no main.js, mas repetindo por segurança)
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Máscara de telefone (já definida no main.js, mas repetindo por segurança)
function phoneMask(value) {
    if (!value) return '';
    value = value.replace(/\D/g, '');
    value = value.replace(/(\d{2})(\d)/, '($1) $2');
    value = value.replace(/(\d)(\d{4})$/, '$1-$2');
    return value;
}