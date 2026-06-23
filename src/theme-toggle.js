(function () {
  'use strict';

  const storageKey = 'adminlte-painel-theme';
  const darkTheme = 'dark';
  const lightTheme = 'light';

  function getStoredTheme() {
    return localStorage.getItem(storageKey) || lightTheme;
  }

  function setStoredTheme(theme) {
    localStorage.setItem(storageKey, theme);
  }

  function replaceClass(element, removeClass, addClass) {
    if (!element) {
      return;
    }

    element.classList.remove(removeClass);
    element.classList.add(addClass);
  }

  function applyTheme(theme) {
    const isDark = theme === darkTheme;
    const body = document.body;
    const header = document.querySelector('.main-header');
    const sidebar = document.querySelector('.main-sidebar');

    body.classList.toggle('dark-mode', isDark);

    replaceClass(header, isDark ? 'navbar-white' : 'navbar-dark', isDark ? 'navbar-dark' : 'navbar-white');
    replaceClass(header, isDark ? 'navbar-light' : 'navbar-light', isDark ? 'navbar-dark' : 'navbar-light');
    replaceClass(sidebar, isDark ? 'sidebar-light-primary' : 'sidebar-dark-primary', isDark ? 'sidebar-dark-primary' : 'sidebar-light-primary');

    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
      button.setAttribute('title', isDark ? 'Usar tema claro' : 'Usar tema escuro');
      button.classList.toggle('btn-outline-light', isDark && button.classList.contains('login-theme-toggle'));
      button.classList.toggle('btn-default', !isDark && button.classList.contains('login-theme-toggle'));
    });

    document.querySelectorAll('[data-theme-toggle-icon]').forEach(function (icon) {
      icon.classList.toggle('fa-moon', !isDark);
      icon.classList.toggle('fa-sun', isDark);
    });
  }

  function toggleTheme() {
    const nextTheme = getStoredTheme() === darkTheme ? lightTheme : darkTheme;
    setStoredTheme(nextTheme);
    applyTheme(nextTheme);
  }

  function initThemeToggle() {
    applyTheme(getStoredTheme());

    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      button.addEventListener('click', toggleTheme);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeToggle);
  } else {
    initThemeToggle();
  }
})();
