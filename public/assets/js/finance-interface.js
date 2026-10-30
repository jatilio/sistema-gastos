(() => {
  if (window.financeInterfaceLoaded) return;
  window.financeInterfaceLoaded = true;
  const root = document.documentElement;
  root.dataset.financeUi = 'ready';
  const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
  const readTheme = () => { try { return localStorage.getItem('finance-theme'); } catch { return null; } };
  let preference = readTheme();
  function applyTheme(theme) {
    root.dataset.theme = theme;
    root.style.colorScheme = theme;
    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
      button.textContent = theme === 'dark' ? '☀ Modo claro' : '☾ Modo oscuro';
      button.setAttribute('aria-pressed', String(theme === 'dark'));
    });
    if (window.Chart && typeof window.Chart.getChart === 'function') {
      document.querySelectorAll('canvas').forEach(canvas => {
        const chart = window.Chart.getChart(canvas);
        if (!chart) return;
        const color = theme === 'dark' ? '#cad7e3' : '#52667a';
        if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = color;
        Object.values(chart.options.scales || {}).forEach(scale => {
          if (scale.ticks) scale.ticks.color = color;
          if (scale.grid) scale.grid.color = theme === 'dark' ? '#314253' : '#e2e8f0';
        });
        chart.update('none');
      });
    }
  }
  applyTheme(preference === 'dark' || preference === 'light' ? preference : (systemTheme.matches ? 'dark' : 'light'));
  const initialize = () => {
    if (!document.querySelector('[data-theme-toggle]')) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'finance-theme-toggle';
      button.dataset.themeToggle = '';
      button.addEventListener('click', () => {
        preference = root.dataset.theme === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem('finance-theme', preference); } catch {}
        applyTheme(preference);
      });
      const controls = document.createElement('div');
      controls.className = 'finance-preferences';
      controls.appendChild(button);
      const mobileBar = document.querySelector('.mobile-navigation');
      const sidebar = document.querySelector('.sidebar');
      if (mobileBar && sidebar) {
        const desktopSlot = document.createElement('div');
        desktopSlot.className = 'finance-theme-slot';
        sidebar.insertBefore(desktopSlot, sidebar.firstChild);
        desktopSlot.appendChild(controls);
        const relocate = () => {
          const parent = window.innerWidth <= 760 ? mobileBar : desktopSlot;
          if (controls.parentElement !== parent) parent.appendChild(controls);
        };
        relocate();
        window.addEventListener('resize', relocate);
      } else {
        const toolbar = document.createElement('div');
        toolbar.className = 'finance-theme-toolbar';
        toolbar.appendChild(controls);
        document.body.prepend(toolbar);
      }
    }

    document.querySelectorAll('table').forEach(table => {
      if (table.closest('.finance-table-scroll')) return;
      const wrapper = document.createElement('div');
      wrapper.className = 'finance-table-scroll';
      wrapper.tabIndex = 0;
      wrapper.setAttribute('role', 'region');
      wrapper.setAttribute('aria-label', 'Tabla de datos. Desplázate horizontalmente para ver todas las columnas.');
      table.before(wrapper);
      wrapper.appendChild(table);
    });
    applyTheme(root.dataset.theme);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
  window.addEventListener('load', () => applyTheme(root.dataset.theme), { once:true });
  window.addEventListener('storage', event => {
    if (event.key !== 'finance-theme') return;
    preference = event.newValue;
    applyTheme(preference === 'dark' || preference === 'light' ? preference : (systemTheme.matches ? 'dark' : 'light'));
  });
  systemTheme.addEventListener('change', event => { if (!preference) applyTheme(event.matches ? 'dark' : 'light'); });
})();