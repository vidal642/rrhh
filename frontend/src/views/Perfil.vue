<template>
  <div class="page-wrapper perfil-page">
    <div class="header-mobile">
      <h1 class="page-title text-center text-white">Mi Perfil</h1>
    </div>

    <div class="dash-panel profile-card mt-[-2rem]">
      <div class="avatar-container">
        <div class="avatar-circle">
          {{ userInitial }}
        </div>
      </div>
      <h2 class="profile-name">{{ authStore.user?.usuario }}</h2>
      <p class="profile-role text-muted">{{ authStore.user?.rol }}</p>

      <div class="profile-details mt-4">
        <div class="detail-item">
          <div class="detail-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <div class="detail-text">
            <span class="detail-label">Usuario</span>
            <span class="detail-val">{{ authStore.user?.usuario }}</span>
          </div>
        </div>

        <div class="detail-item">
          <div class="detail-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div class="detail-text">
            <span class="detail-label">Rol</span>
            <span class="detail-val">{{ authStore.user?.rol }}</span>
          </div>
        </div>
      </div>

      <button class="btn-logout mt-4" @click="logout">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="mr-2 inline"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Cerrar Sesión
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'

const authStore = useAuthStore()
const router = useRouter()

const userInitial = computed(() => {
  return (authStore.user?.usuario || 'U').charAt(0).toUpperCase()
})

const logout = () => {
  authStore.logout()
  router.push('/')
}
</script>

<style scoped>
.perfil-page {
  background-color: #f8fafc;
  min-height: 100vh;
  padding-bottom: 80px;
}
.header-mobile {
  background-color: #1e3a8a;
  padding: 2.5rem 1rem 3rem 1rem;
  margin: -1.5rem -1.5rem 1.5rem -1.5rem;
}
.text-white { color: white; }
.text-center { text-align: center; }

.profile-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem 1.5rem;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  position: relative;
  margin-top: -3rem;
  z-index: 10;
}
.avatar-container {
  display: flex;
  justify-content: center;
  margin-top: -3.5rem;
  margin-bottom: 1rem;
}
.avatar-circle {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: bold;
  border: 4px solid white;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.profile-name { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
.profile-role { font-size: 0.9rem; margin-bottom: 1rem; }

.profile-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  text-align: left;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--border);
}
.detail-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.detail-icon {
  width: 40px; height: 40px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #64748b;
  display: flex; align-items: center; justify-content: center;
}
.detail-icon svg { width: 20px; height: 20px; }
.detail-text { display: flex; flex-direction: column; }
.detail-label { font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.detail-val { font-size: 1rem; color: #1e293b; font-weight: 500; }

.btn-logout {
  width: 100%;
  padding: 0.875rem;
  background-color: transparent;
  color: #ef4444;
  border: 1px solid #fca5a5;
  border-radius: 0.75rem;
  font-weight: 600;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 2rem;
}
.btn-logout:hover {
  background-color: #fef2f2;
}
.inline { display: inline-block; }
.mr-2 { margin-right: 0.5rem; }
</style>
