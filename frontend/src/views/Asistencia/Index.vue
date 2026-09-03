<template>
  <div class="page-wrapper">
    <div class="page-top">
      <div>
        <h1 class="page-title">Gestión de Asistencia</h1>
        <p class="page-subtitle">Central de control de asistencias y enrolamiento biométrico</p>
      </div>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="tabs-nav">
      <button 
        class="tab-btn" 
        :class="{ active: currentTab === 'listado' }" 
        @click="currentTab = 'listado'"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
          <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
        </svg>
        Listado de Asistencias
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: currentTab === 'facial' }" 
        @click="currentTab = 'facial'"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        Reconocimiento Facial
      </button>
    </div>

    <!-- TABS CONTENT -->
    <div class="tab-content">
      <Transition name="fade" mode="out-in">
        <KeepAlive>
          <component :is="currentComponent" />
        </KeepAlive>
      </Transition>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

import AsistenciaListado from './Listado.vue'
import ReconocimientoFacialAdmin from './ReconocimientoFacialAdmin.vue'

const currentTab = ref('listado')

const currentComponent = computed(() => {
  return currentTab.value === 'listado' ? AsistenciaListado : ReconocimientoFacialAdmin
})
</script>

<style scoped>
.page-wrapper {
  padding: 1.5rem;
}
.page-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.2rem;
}
.page-subtitle {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0;
}

/* Tabs */
.tabs-nav {
  display: flex;
  gap: 1rem;
  border-bottom: 1px solid var(--border);
  margin-bottom: 1.5rem;
}
.tab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--text-secondary);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: -1px; /* Overlap border-bottom */
}
.tab-btn:hover {
  color: var(--primary-600);
}
.tab-btn.active {
  color: var(--primary-600);
  border-bottom-color: var(--primary-600);
}

.tab-content {
  position: relative;
}

/* Transiciones */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: translateY(5px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}
</style>
