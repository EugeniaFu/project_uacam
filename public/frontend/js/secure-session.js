/**
 * Función mejorada de logout que invalida la sesión en el servidor
 * Previene que el usuario pueda volver atrás usando el botón del navegador
 */
async function logout() {
  try {
    // Llamar al endpoint de logout en el servidor para invalidar el token
    try {
      await apiFetch('/auth/logout', 'POST');
    } catch (e) {
      console.log('Error al invalidar token en servidor:', e);
    }
    
    // Limpiar COMPLETAMENTE todos los datos de la sesión del cliente
    localStorage.clear();
    sessionStorage.clear();
    
    // Limpiar cookies si existen
    document.cookie.split(";").forEach(function(c) { 
      document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
    });
    
    // Manipular el historial del navegador para prevenir navegación hacia atrás
    // Reemplazar todas las entradas del historial con la página de login
    history.pushState(null, '', 'index.html');
    history.pushState(null, '', 'index.html');
    
    // Redirigir al login usando replace() para limpiar el historial
    window.location.replace('index.html');
  } catch (error) {
    console.error('Error durante logout:', error);
    // Limpiar datos locales incluso si hay error
    localStorage.clear();
    sessionStorage.clear();
    window.location.replace('index.html');
  }
}

/**
 * Validar sesión: Verifica que el token sea válido con el servidor
 * Se ejecuta al cargar cualquier página protegida
 */
async function validateSessionWithServer() {
  const token = localStorage.getItem('token');
  
  // Si no hay token, redirigir al login
  if (!token) {
    console.log('No hay token, redirigiendo a login');
    window.location.replace('index.html');
    return false;
  }
  
  // Verificar que el token es válido haciendo una petición segura
  try {
    const response = await apiFetch('/dashboard/summary', 'GET');
    
    if (!response.ok || response.status === 401) {
      console.log('Token inválido, redirigiendo a login');
      localStorage.removeItem('token');
      localStorage.removeItem('userName');
      localStorage.removeItem('role');
      window.location.replace('index.html');
      return false;
    }
    
    return true;
  } catch (error) {
    console.error('Error validando sesión:', error);
    localStorage.removeItem('token');
    localStorage.removeItem('userName');
    localStorage.removeItem('role');
    window.location.replace('index.html');
    return false;
  }
}

/**
 * Validar sesión simple: solo verifica que exista el token
 * Se ejecuta al cargar cualquier página protegida
 */
function validateSession() {
  const token = localStorage.getItem('token');
  
  // Si no hay token, redirigir al login
  if (!token) {
    window.location.href = 'index.html';
    return false;
  }
  
  return true;
}

/**
 * Prevenir el caché del navegador para páginas protegidas
 */
function preventPageCache() {
  // Agregar meta tags para prevenir caché
  const meta1 = document.createElement('meta');
  meta1.httpEquiv = 'Cache-Control';
  meta1.content = 'no-store, no-cache, must-revalidate, max-age=0';
  document.head.appendChild(meta1);
  
  const meta2 = document.createElement('meta');
  meta2.httpEquiv = 'Pragma';
  meta2.content = 'no-cache';
  document.head.appendChild(meta2);
  
  const meta3 = document.createElement('meta');
  meta3.httpEquiv = 'Expires';
  meta3.content = '0';
  document.head.appendChild(meta3);
}

/**
 * Prevenir navegación hacia atrás después del logout
 * Esta función detecta cuando el usuario intenta regresar a una página protegida
 * después de haber cerrado sesión
 */
function preventBackNavigation() {
  // Detectar cuando la página se carga desde el caché (botón atrás)
  window.addEventListener('pageshow', function(event) {
    // Si la página se carga desde el caché del navegador (bfcache)
    if (event.persisted) {
      // Verificar si hay sesión válida
      const token = localStorage.getItem('token');
      if (!token) {
        // No hay token, redirigir al login
        window.location.replace('index.html');
      } else {
        // Hay token, validar con el servidor
        validateSessionWithServer();
      }
    }
  });
  
  // Detectar cuando el usuario usa el botón atrás
  window.addEventListener('popstate', function(event) {
    const token = localStorage.getItem('token');
    if (!token) {
      // No hay token, prevenir navegación y redirigir
      window.location.replace('index.html');
    }
  });
  
  // Verificar sesión periódicamente mientras la página está activa
  let sessionCheckInterval = setInterval(function() {
    const token = localStorage.getItem('token');
    if (!token) {
      clearInterval(sessionCheckInterval);
      window.location.replace('index.html');
    }
  }, 5000); // Verificar cada 5 segundos
  
  // Limpiar el intervalo cuando la página se descarga
  window.addEventListener('beforeunload', function() {
    clearInterval(sessionCheckInterval);
  });
}

/**
 * Inicializar validación de sesión: Valida el token antes de cargar la página
 * Llama validateSessionWithServer() en x-init de Alpine.js
 */
function ensureValidSession() {
  // Validación síncrona rápida
  if (!validateSession()) {
    return;
  }
  
  // Validación asíncrona con servidor (en segundo plano)
  validateSessionWithServer();
}

// Inicializar prevención de navegación hacia atrás cuando cargue el DOM
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    preventPageCache();
    preventBackNavigation();
  });
} else {
  // El DOM ya está cargado
  preventPageCache();
  preventBackNavigation();
}
