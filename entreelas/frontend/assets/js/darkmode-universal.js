// ==========================================
// DARKMODE UNIVERSAL - DivaConnect
// Funciona em todas as páginas do site
// ==========================================

(function() {
  'use strict';

  // Aplicar tema imediatamente para evitar flash
  const savedTheme = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  
  if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
    document.documentElement.classList.add('dark-mode');
  }

  // Inicializar quando o DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    createDarkModeToggle();
    setupThemeListener();
  }

  function createDarkModeToggle() {
    // Procurar por containers possíveis
    const containers = [
      document.querySelector('.header-buttons'),
      document.querySelector('.header .container'),
      document.querySelector('header'),
      document.querySelector('nav')
    ];

    // Encontrar o primeiro container válido
    const container = containers.find(c => c !== null);
    
    if (!container) {
      console.warn('DivaConnect: Nenhum container encontrado para o dark mode toggle');
      return;
    }

    // Verificar se já existe um toggle
    if (document.getElementById('darkModeToggle')) {
      return;
    }

    // Criar o toggle
    const toggleWrapper = document.createElement('div');
    toggleWrapper.className = 'dark-mode-toggle-wrapper';
    toggleWrapper.innerHTML = `
      <button 
        class="dark-mode-toggle" 
        id="darkModeToggle" 
        aria-label="Alternar modo escuro"
        title="Alternar entre modo claro e escuro"
      >
        <span class="toggle-icon sun-icon">☀️</span>
        <span class="toggle-icon moon-icon">🌙</span>
      </button>
    `;

    // Inserir no container apropriado
    if (container.classList.contains('header-buttons')) {
      container.insertBefore(toggleWrapper, container.firstChild);
    } else {
      container.appendChild(toggleWrapper);
    }

    // Adicionar event listener
    const toggle = document.getElementById('darkModeToggle');
    if (toggle) {
      toggle.addEventListener('click', toggleDarkMode);
      
      // Atualizar estado visual inicial
      updateToggleState();
    }
  }

  function toggleDarkMode() {
    const isDark = document.documentElement.classList.contains('dark-mode');
    
    if (isDark) {
      disableDarkMode();
    } else {
      enableDarkMode();
    }
  }

  function enableDarkMode() {
    document.documentElement.classList.add('dark-mode');
    document.body.classList.add('dark-mode');
    localStorage.setItem('theme', 'dark');
    updateToggleState();
    
    // Disparar evento customizado para outros scripts
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: 'dark' } }));
  }

  function disableDarkMode() {
    document.documentElement.classList.remove('dark-mode');
    document.body.classList.remove('dark-mode');
    localStorage.setItem('theme', 'light');
    updateToggleState();
    
    // Disparar evento customizado para outros scripts
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: 'light' } }));
  }

  function updateToggleState() {
    const toggle = document.getElementById('darkModeToggle');
    if (!toggle) return;

    const isDark = document.documentElement.classList.contains('dark-mode');
    
    if (isDark) {
      toggle.classList.add('dark');
    } else {
      toggle.classList.remove('dark');
    }
  }

  function setupThemeListener() {
    // Listener para mudanças na preferência do sistema
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    mediaQuery.addEventListener('change', (e) => {
      // Só aplicar se o usuário não tiver definido manualmente
      if (!localStorage.getItem('theme')) {
        if (e.matches) {
          enableDarkMode();
        } else {
          disableDarkMode();
        }
      }
    });
  }

  // Exportar funções globalmente para uso em outros scripts
  window.DivaConnectTheme = {
    toggle: toggleDarkMode,
    enable: enableDarkMode,
    disable: disableDarkMode,
    isDark: () => document.documentElement.classList.contains('dark-mode'),
    get: () => localStorage.getItem('theme') || 'light'
  };

})();