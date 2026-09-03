<template>
  <div class="layout">

    <!-- Overlay móvil -->
    <transition name="overlay-fade">
      <div
        v-if="sidebarOpen && isMobile"
        class="sidebar-overlay"
        @click="sidebarOpen = false"
      ></div>
    </transition>

    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'sidebar-collapsed': !sidebarOpen, 'hidden-mobile-emp': isMobile && authStore.user?.rol === 'Empleado' }">

      <!-- Logo -->
      <div class="sidebar-logo">
        <div class="logo-mark">
          <img src="/logo_flores.png" alt="Logo" class="logo-img" />
        </div>
        <transition name="fade-text">
          <div class="logo-text" v-show="sidebarOpen">
            <span class="logo-name">Constructora Flores</span>
            <span class="logo-sub">Panel Administrativo</span>
          </div>
        </transition>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">

        <!-- PRINCIPAL -->
        <transition name="fade-text">
          <div class="nav-section-label" v-show="sidebarOpen">PRINCIPAL</div>
        </transition>
        <router-link to="/dashboard" class="nav-link" active-class="nav-link--active" exact>
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
              <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Dashboard</span>
        </router-link>

        <!-- GESTIÓN -->
        <transition name="fade-text">
          <div class="nav-section-label" v-show="sidebarOpen">GESTIÓN</div>
        </transition>

        <router-link to="/empleados" class="nav-link" active-class="nav-link--active" v-if="authStore.user?.rol !== 'Empleado'">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Empleados</span>
        </router-link>

        <router-link to="/departamentos" class="nav-link" active-class="nav-link--active" v-if="authStore.user?.rol !== 'Empleado'">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Departamentos</span>
        </router-link>

        <router-link to="/cargos" class="nav-link" active-class="nav-link--active" v-if="authStore.user?.rol !== 'Empleado'">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
              <line x1="12" y1="12" x2="12" y2="17"/><line x1="9.5" y1="14.5" x2="14.5" y2="14.5"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Cargos</span>
        </router-link>

        <!-- Asistencia General -->
        <router-link to="/asistencia" class="nav-link" active-class="nav-link--active">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Gestión de Asistencia</span>
        </router-link>



        <!-- Secciones del Administrador -->
        <router-link to="/ausencias" class="nav-link" active-class="nav-link--active" v-if="authStore.user?.rol !== 'Empleado'">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Ausencias</span>
        </router-link>

        <router-link to="/planillas" class="nav-link" active-class="nav-link--active" v-if="authStore.user?.rol !== 'Empleado'">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Planillas</span>
        </router-link>
        <!-- ADMINISTRACIÓN -->
        <transition name="fade-text">
          <div class="nav-section-label" v-show="sidebarOpen" v-if="authStore.user?.rol !== 'Empleado'">ADMINISTRACIÓN</div>
        </transition>

        <router-link to="/usuarios" class="nav-link" active-class="nav-link--active" v-if="authStore.user?.rol !== 'Empleado'">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Usuarios</span>
        </router-link>

        <router-link to="/reportes" class="nav-link" active-class="nav-link--active" v-if="authStore.user?.rol !== 'Empleado'">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Reportes</span>
        </router-link>

        <router-link to="/configuracion" class="nav-link" active-class="nav-link--active" v-if="authStore.user?.rol !== 'Empleado'">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="3"/>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
          </span>
          <span class="nav-label" v-show="sidebarOpen">Configuración</span>
        </router-link>

      </nav>

      <!-- Sidebar Footer -->
      <div class="sidebar-footer">
        <!-- Info del Sistema (Simple y Limpia) -->
        <div class="sidebar-system-info" v-show="sidebarOpen">
          <span class="system-title">Sistema RRHH v1.0</span>
          <span class="system-subtitle">Constructora Flores © 2026</span>
        </div>
        <button class="sidebar-logout" @click="logout" :title="sidebarOpen ? '' : 'Cerrar sesión'">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
          </span>
          <span v-show="sidebarOpen" class="nav-label">Cerrar sesión</span>
        </button>
      </div>
    </aside>

    <!-- Main -->
    <div class="main-area">
      <!-- Top Navbar -->
      <header class="topbar" :class="{ 'hidden-mobile-emp': isMobile && authStore.user?.rol === 'Empleado' }">
        <button class="topbar-toggle" @click="toggleSidebar" aria-label="Toggle sidebar" id="sidebar-toggle-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
            <line x1="3" y1="6"  x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>

        <div class="topbar-breadcrumb">
          <div class="breadcrumb-inner">
            <span class="topbar-route">{{ currentRoute }}</span>
          </div>
        </div>

        <div class="topbar-right">
          <div class="topbar-avatar">
            <div class="user-avatar-md">{{ userInitial }}</div>
            <div class="topbar-avatar-info">
              <span class="topbar-user-name">{{ authStore.user?.usuario || 'Administrador' }}</span>
              <span class="topbar-user-role">{{ authStore.user?.rol || 'Administrador del sistema' }}</span>
            </div>
          </div>
        </div>
      </header>

      <!-- Content -->
      <main class="page-content">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>


  </div>

  <ToastNotificacion />
  <ConfirmacionModal />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../store/auth'
import ToastNotificacion from '../componentes/ToastNotificacion.vue'
import ConfirmacionModal from '../componentes/ConfirmacionModal.vue'

const sidebarOpen = ref(true)
const isMobile    = ref(false)
const router      = useRouter()
const route       = useRoute()
const authStore   = useAuthStore()

const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768
  if (isMobile.value) sidebarOpen.value = false
}

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})
onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

const userInitial = computed(() =>
  (authStore.user?.usuario || 'A').charAt(0).toUpperCase()
)

const currentRoute = computed(() => {
  const path = route?.path || router?.currentRoute?.value?.path || '';
  const map = {
    '/dashboard':     'Dashboard',
    '/empleados':     'Empleados',
    '/departamentos': 'Departamentos',
    '/cargos':        'Cargos',
    '/asistencia':    'Control de Asistencia',
    '/ausencias':     'Gestión de Ausencias',
    '/planillas':     'Planillas y Nómina',
    '/usuarios':      'Usuarios del Sistema',
    '/reportes':      'Reportes',
    '/configuracion': 'Configuración',
    '/mi-asistencia': 'Mi Asistencia',
    '/mis-planillas': 'Mis Planillas',
    '/perfil':        'Mi Perfil',
  }
  return map[path] || 'Dashboard'
})

const logout = () => {
  authStore.logout()
  router.push('/')
}
</script>

<style scoped>
/* ========================
   LAYOUT SHELL
   ======================== */
.layout {
  display: flex;
  min-height: 100vh;
  background: var(--bg-body);
}

/* ========================
   SIDEBAR OVERLAY (móvil)
   ======================== */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(10, 15, 30, 0.5);
  backdrop-filter: blur(2px);
  z-index: 150;
}
.overlay-fade-enter-active { transition: opacity 0.22s ease; }
.overlay-fade-leave-active { transition: opacity 0.18s ease; }
.overlay-fade-enter-from, .overlay-fade-leave-to { opacity: 0; }

/* ========================
   SIDEBAR
   ======================== */
.sidebar {
  width: 256px;
  min-height: 100vh;
  background: var(--sidebar-bg);
  display: flex;
  flex-direction: column;
  transition: width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow: hidden;
  border-right: 1px solid rgba(255, 255, 255, 0.04);
  box-shadow: 2px 0 20px rgba(0, 0, 0, 0.25);
  z-index: 200;
}
.sidebar-collapsed { width: 68px; }

/* Logo */
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.125rem 1rem;
  border-bottom: 1px solid var(--sidebar-border);
  min-height: 64px;
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.02);
}
.logo-mark {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: transparent;
}
.logo-img { width: 38px; height: 38px; object-fit: contain; border-radius: 10px; }
.logo-text { display: flex; flex-direction: column; overflow: hidden; white-space: nowrap; }
.logo-name {
  font-size: 0.875rem;
  font-weight: 700;
  color: #e2e8f0;
  letter-spacing: -0.02em;
  line-height: 1.3;
}
.logo-sub {
  font-size: 0.5625rem;
  color: var(--sidebar-section);
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-top: 0.1rem;
}

/* Nav */
.sidebar-nav {
  flex: 1;
  padding: 0.625rem 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.075rem;
  overflow-y: auto;
  overflow-x: hidden;
}
.nav-section-label {
  font-size: 0.5rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: var(--sidebar-section);
  padding: 0.875rem 0.75rem 0.3rem;
  text-transform: uppercase;
  white-space: nowrap;
}
.nav-link {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.5rem 0.625rem;
  border-radius: 8px;
  color: var(--sidebar-text);
  transition: all 0.18s ease;
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  position: relative;
}
.nav-link:hover {
  background: rgba(255, 255, 255, 0.06);
  color: var(--sidebar-text-hover);
}
.nav-link--active {
  background: var(--sidebar-active-bg) !important;
  color: var(--sidebar-active-text) !important;
}
.nav-link--active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 18%;
  bottom: 18%;
  width: 3px;
  border-radius: 0 3px 3px 0;
  background: var(--secondary-400);
  box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
}
.nav-icon {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: 7px;
}
.nav-icon svg { width: 16px; height: 16px; }
.nav-label { font-size: 0.8125rem; font-weight: 500; overflow: hidden; white-space: nowrap; }

/* Sidebar Footer */
.sidebar-footer {
  padding: 0.5rem;
  border-top: 1px solid var(--sidebar-border);
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.01);
}
.sidebar-system-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0.65rem 0.5rem;
  text-align: center;
  margin-bottom: 0.3rem;
  opacity: 0.5; /* Muted flat design */
  transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.sidebar-system-info:hover {
  opacity: 0.85; /* Gentle hover effect */
}
.system-title {
  font-size: 0.725rem;
  font-weight: 600;
  color: #94a3b8; /* Muted slate gray */
  letter-spacing: 0.01em;
}
.system-subtitle {
  font-size: 0.575rem;
  color: #64748b; /* Soft lighter slate */
  margin-top: 0.15rem;
  font-weight: 400;
}
.sidebar-logout {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.5rem 0.625rem;
  border-radius: 8px;
  background: none;
  border: none;
  cursor: pointer;
  color: rgba(148, 163, 184, 0.55);
  transition: all 0.18s ease;
  font-family: 'Inter', sans-serif;
  font-size: 0.8rem;
  font-weight: 500;
  width: 100%;
  text-align: left;
}
.sidebar-logout:hover {
  background: rgba(239, 68, 68, 0.08);
  color: #fca5a5;
}

/* ========================
   MAIN AREA
   ======================== */
.main-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
}

.topbar {
  height: 62px;
  background: var(--bg-surface);
  border-bottom: 1.5px solid var(--border);
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0 1.5rem;
  position: sticky;
  top: 0;
  z-index: 90;
  flex-shrink: 0;
  box-shadow: 0 1px 0 var(--border), 0 2px 8px rgba(0, 0, 0, 0.03);
}
.topbar-toggle {
  background: none;
  border: 1.5px solid var(--border-strong);
  border-radius: var(--radius-sm);
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--text-muted);
  transition: var(--transition-fast);
  flex-shrink: 0;
}
.topbar-toggle:hover {
  background: var(--bg-hover);
  color: var(--primary-600);
  border-color: var(--primary-300);
}
.topbar-breadcrumb { flex: 1; }
.breadcrumb-inner { display: flex; align-items: center; gap: 0.5rem; }
.topbar-route {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-primary);
  letter-spacing: -0.015em;
}
.topbar-right { display: flex; align-items: center; gap: 0.875rem; }
.topbar-avatar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.3rem 0.875rem 0.3rem 0.4rem;
  border-radius: 99px;
  border: 1.5px solid var(--border);
  cursor: pointer;
  transition: var(--transition-fast);
  background: var(--bg-muted);
}
.topbar-avatar:hover {
  border-color: var(--primary-300);
  background: var(--bg-hover);
}
.user-avatar-md {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(19, 60, 85, 0.15), rgba(61, 133, 169, 0.08));
  border: 1.5px solid rgba(19, 60, 85, 0.25);
  color: var(--primary-600);
  font-size: 0.7rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.topbar-avatar-info { display: flex; flex-direction: column; }
.topbar-user-name {
  font-size: 0.8rem;
  font-weight: 600;
  line-height: 1.2;
  color: var(--text-primary);
}
.topbar-user-role {
  font-size: 0.575rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.page-content {
  flex: 1;
  overflow-y: auto;
  background: var(--bg-body);
}

/* Page Transitions */
.page-enter-active { transition: all 0.22s ease; }
.page-leave-active { transition: all 0.16s ease; }
.page-enter-from { opacity: 0; transform: translateY(6px); }
.page-leave-to   { opacity: 0; transform: translateY(-4px); }

/* Fade Text */
.fade-text-enter-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.fade-text-leave-active { transition: opacity 0.12s ease; }
.fade-text-enter-from { opacity: 0; transform: translateX(-6px); }
.fade-text-leave-to   { opacity: 0; }

/* ========================
   RESPONSIVE
   ======================== */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    height: 100vh;
    top: 0;
    left: 0;
    z-index: 200;
    transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1), width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .sidebar-collapsed {
    width: 256px;
    transform: translateX(-100%);
  }
  .topbar-avatar-info { display: none; }
  .main-area { min-width: 0; width: 100%; }
}

@media (max-width: 480px) {
  .topbar { padding: 0 1rem; }
}

.hidden-mobile-emp {
  display: none !important;
}

/* Bottom Navigation (Mobile Empleado) */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 65px;
  background-color: #ffffff;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-around;
  align-items: center;
  z-index: 999;
  box-shadow: 0 -4px 10px rgba(0,0,0,0.02);
}
.bnav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  text-decoration: none;
  font-size: 0.65rem;
  font-weight: 500;
  gap: 0.2rem;
  width: 20%;
}
.bnav-item svg { width: 22px; height: 22px; }
.bnav-active {
  color: var(--secondary-500);
}
.bnav-active svg {
  stroke-width: 2.5;
}
</style>
