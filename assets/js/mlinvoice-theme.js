/* global MLInvoice, bootstrap, EasyMDE, google, Sortable, moment */
MLInvoice.addModule('Theme', function mlinvoiceTheme() {
  function getStoredTheme() {
    return localStorage.getItem('theme');
  }

  function setStoredTheme(theme) {
    localStorage.setItem('theme', theme);
  }

  function getPreferredTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function getActiveTheme() {
    const stored = getStoredTheme();
    if (['light', 'dark'].includes(stored)) {
      return stored;
    }
    return getPreferredTheme();
  }

  function setTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    // Fix for Bootstrap 5.3 navbar oddity:
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      navbar.setAttribute('data-bs-theme', theme);
    }
  }

  function showSelectedTheme(theme) {
    document.querySelectorAll('.theme-switcher .dropdown-item').forEach(menuItem => {
      const active = menuItem.dataset.bsThemeValue === theme;
      menuItem.classList.toggle('active', active);
      if (active) {
        const activeIcon = menuItem.querySelector('i');
        if (activeIcon) {
          document.querySelectorAll('.theme-icon-active i').forEach(icon => icon.className = activeIcon.className);
        }
      }
    });
  }

  function initControls() {
    document.querySelectorAll('.theme-switcher .dropdown-item').forEach(menuItem => {
      menuItem.addEventListener('click', btn => {
        const selected = menuItem.dataset.bsThemeValue;
        setStoredTheme(selected);
        setTheme(getActiveTheme());
        showSelectedTheme(selected);
      });
    });
  }

  function initPreferredSchemeListener() {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      if (getStoredTheme() === 'auto') {
        setTheme(getPreferredTheme());
      }
    });
  }

  /**
   * Initialize theme switcher
   */
  function init() {
    initControls();
    initPreferredSchemeListener();
    setTheme(getActiveTheme());
    showSelectedTheme(getStoredTheme());
  }

  return {
    init: init
  };
});
