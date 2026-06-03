// Cargar partial sidebar.html en todos los lugares que tengan id="sidebar-container"
async function loadSidebar() {
  const containers = document.querySelectorAll('[data-sidebar-placeholder]');
  for (const container of containers) {
    try {
      const res = await fetch('partials/sidebar.html');
      if (res.ok) {
        container.innerHTML = await res.text();
      }
    } catch (e) {
      console.error('Error cargando sidebar:', e);
    }
  }
}

// Ejecutar al cargar el DOM
document.addEventListener('DOMContentLoaded', loadSidebar);
