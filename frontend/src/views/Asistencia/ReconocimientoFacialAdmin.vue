<template>
  <div class="admin-facial-content">
    <div v-if="currentView !== 'menu'" class="header-actions">
      <button class="btn btn-outline" @click="goBack">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Volver a Opciones
      </button>
    </div>

    <!-- VISTA PRINCIPAL: MENÚ DE OPCIONES -->
    <div v-if="currentView === 'menu'" class="menu-grid">
      <!-- Mensaje de éxito -->
      <div v-if="successMessage" class="alert-success-custom">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        {{ successMessage }}
      </div>
      <div class="menu-card" @click="currentView = 'asistencia'">
        <div class="icon-wrapper bg-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <h3>Marcar Asistencia</h3>
        <p>Abre el escáner facial continuo para registrar entradas y salidas del personal.</p>
      </div>

      <div class="menu-card" @click="currentView = 'registro_paso_1'">
        <div class="icon-wrapper bg-secondary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/><circle cx="9" cy="7" r="4"/>
          </svg>
        </div>
        <h3>Registrar Rostro</h3>
        <p>Vincula la biometría facial a un empleado específico ingresando su código.</p>
      </div>
    </div>

    <!-- VISTA A: MARCAR ASISTENCIA (INTEGRA LOGICA DE AsistenciaFacial.vue) -->
    <div v-if="currentView === 'asistencia'" class="scanner-container">
      <AsistenciaFacial />
    </div>

    <!-- VISTA B: REGISTRAR ROSTRO -->
    <div v-if="currentView.startsWith('registro')" class="registro-container">
      
      <!-- PASO 1: BUSCAR Y SELECCIONAR EMPLEADO -->
      <div v-if="currentView === 'registro_paso_1'" class="registro-card animate-in search-card">
        <h2>Buscar Empleado</h2>
        <p class="text-secondary mb-4">Busca y selecciona al empleado para registrar su rostro.</p>
        
        <div class="search-box">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" v-model="searchQuery" @input="buscarEmpleados" class="form-input search-input" placeholder="Buscar por nombre, apellido o código..." autofocus />
        </div>

        <div class="empleados-list">
          <div v-if="isLoading" class="loading-state">
            <span class="spinner-small"></span> Buscando...
          </div>
          <div v-else-if="empleados.length === 0 && searchQuery.length > 2" class="empty-state">
            No se encontraron empleados.
          </div>
          <div v-else class="empleado-item" v-for="emp in empleados" :key="emp.id_empleado">
            <div class="emp-info">
              <div class="avatar">{{ emp.nombre_completo.charAt(0) }}</div>
              <div class="emp-details">
                <strong>{{ emp.nombre_completo }}</strong>
                <span class="emp-meta">Cód: {{ emp.codigo_empleado }} | {{ emp.cargo }}</span>
                <span v-if="emp.rostro_registrado" class="badge-sm bg-warning mt-1">Ya tiene rostro</span>
              </div>
            </div>
            <button class="btn btn-outline btn-sm" @click="seleccionarEmpleado(emp)">Seleccionar</button>
          </div>
        </div>
      </div>

      <!-- PASO 2: CONFIRMAR EMPLEADO -->
      <div v-if="currentView === 'registro_paso_confirmar'" class="registro-card animate-in">
        <h2>Confirmar Empleado</h2>
        <p class="text-secondary mb-4">Verifica los datos antes de abrir la cámara.</p>
        
        <div class="confirm-details mb-4">
          <div class="empleado-badge confirm-badge">
            <div class="avatar">{{ validatedEmployee?.nombre_completo.charAt(0) }}</div>
            <div>
              <strong>{{ validatedEmployee?.nombre_completo }}</strong>
              <span class="emp-meta">Código: {{ validatedEmployee?.codigo_empleado }}</span>
              <span class="emp-meta">Cargo: {{ validatedEmployee?.cargo }}</span>
              <span class="badge-sm" :class="validatedEmployee?.rostro_registrado ? 'bg-warning' : 'bg-success'">
                {{ validatedEmployee?.rostro_registrado ? 'Ya tiene rostro registrado' : 'Sin rostro registrado' }}
              </span>
            </div>
          </div>
        </div>

        <div class="action-buttons">
          <button class="btn btn-outline flex-1" @click="currentView = 'registro_paso_1'">Cancelar</button>
          <button class="btn btn-primary flex-1" @click="currentView = 'registro_paso_2'">
            Continuar a cámara
          </button>
        </div>
      </div>

      <!-- PASO 2: ESCÁNER DE REGISTRO (INTEGRA LOGICA DE RegistrarRostro.vue) -->
      <div v-if="currentView === 'registro_paso_2'" class="registro-card animate-in scan-card">
        <div class="empleado-badge mb-4">
          <div class="avatar">{{ validatedEmployee?.nombre_completo.charAt(0) }}</div>
          <div>
            <strong>{{ validatedEmployee?.nombre_completo }}</strong>
            <span class="badge-sm" :class="validatedEmployee?.rostro_registrado ? 'bg-warning' : 'bg-success'">
              {{ validatedEmployee?.rostro_registrado ? 'Ya tiene rostro' : 'Sin rostro' }}
            </span>
          </div>
        </div>

        <RegistrarRostro :id-empleado="validatedEmployee?.id_empleado" @on-success="onRegistroExitoso" />
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../../plugins/axios'

// Importamos las vistas existentes para reusarlas como componentes dentro de este panel
import AsistenciaFacial from '../AsistenciaFacial.vue'
import RegistrarRostro from '../RegistrarRostro.vue'

const currentView = ref('menu')
const searchQuery = ref('')
const empleados = ref([])
const isLoading = ref(false)
const validatedEmployee = ref(null)
const successMessage = ref('')

let searchTimeout = null

const goBack = () => {
  currentView.value = 'menu'
  searchQuery.value = ''
  empleados.value = []
  validatedEmployee.value = null
  successMessage.value = ''
}

const buscarEmpleados = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(async () => {
    if (searchQuery.value.length < 2) {
      empleados.value = []
      return
    }
    isLoading.value = true
    try {
      const res = await api.get('/empleados/disponibles-para-rostro', {
        params: { buscar: searchQuery.value }
      })
      empleados.value = res.data.datos || []
    } catch (err) {
      console.error(err)
    } finally {
      isLoading.value = false
    }
  }, 300)
}

const seleccionarEmpleado = (emp) => {
  validatedEmployee.value = emp
  currentView.value = 'registro_paso_confirmar'
}

const onRegistroExitoso = () => {
  successMessage.value = "Rostro vinculado correctamente"
  setTimeout(() => {
    goBack()
  }, 2000)
}
</script>

<style scoped>
.alert-success-custom {
  grid-column: 1 / -1;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.3);
  color: #059669;
  padding: 1rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
}
.admin-facial-content {
  /* Removemos el padding para que encaje bien en el tab content */
  width: 100%;
}
.header-actions {
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: flex-end;
}
.text-primary { color: var(--text-primary); margin: 0; font-size: 1.5rem; font-weight: 700; }
.text-secondary { color: var(--text-secondary); margin: 0; font-size: 0.875rem; }
.mb-4 { margin-bottom: 1rem; }
.mt-2 { margin-top: 0.5rem; }
.w-full { width: 100%; }

.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.menu-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-sm);
}
.menu-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-300);
}

.icon-wrapper {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: white;
}
.bg-primary { background: var(--primary-500); }
.bg-secondary { background: var(--secondary-500); }

.menu-card h3 { margin: 0 0 0.5rem; font-size: 1.25rem; font-weight: 700; color: var(--text-primary); }
.menu-card p { margin: 0; font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5; }

.btn {
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; font-size: 0.875rem;
  cursor: pointer; transition: all 0.2s; border: none;
}
.btn-primary { background: var(--primary-600); color: white; }
.btn-primary:hover:not(:disabled) { background: var(--primary-700); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-primary); }
.btn-outline:hover { background: var(--bg-hover); }

.scanner-container {
  /* Hacemos que AsistenciaFacial se adapte al contenedor sin ocupar todo el vh */
  border-radius: 16px;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}
.scanner-container :deep(.kiosco-page) {
  min-height: auto;
  padding: 0;
}

.registro-container {
  display: flex; justify-content: center;
}
.registro-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 2rem;
  width: 100%;
  max-width: 400px;
  box-shadow: var(--shadow-sm);
}
.scan-card {
  max-width: 600px;
  padding: 0;
  overflow: hidden;
}
.scan-card :deep(.registrar-page) {
  min-height: auto;
  padding: 1rem;
}
.scan-card :deep(.registrar-card) {
  box-shadow: none;
  border: none;
}
.animate-in { animation: slideUp 0.3s ease-out forwards; }
@keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.form-group { margin-bottom: 1.25rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600; color: var(--text-primary); }
.form-input {
  width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px;
  font-size: 1rem; color: var(--text-primary); background: var(--bg-body);
}
.form-input:focus { outline: none; border-color: var(--primary-500); box-shadow: 0 0 0 3px rgba(19, 60, 85, 0.1); }

.spinner {
  width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white;
  border-radius: 50%; animation: spin 0.8s linear infinite;
}
.spinner-small {
  width: 16px; height: 16px; border: 2px solid var(--border); border-top-color: var(--primary-500);
  border-radius: 50%; animation: spin 0.8s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empleado-badge {
  display: flex; align-items: center; gap: 1rem;
  padding: 1rem; background: var(--bg-body); border-bottom: 1px solid var(--border);
}
.confirm-badge {
  border-radius: 12px; border: 1px solid var(--border); border-bottom: 1px solid var(--border);
}
.avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-200); color: var(--primary-700); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; flex-shrink: 0; }
.empleado-badge div { display: flex; flex-direction: column; gap: 0.2rem; }
.empleado-badge strong { color: var(--text-primary); font-size: 1rem; }
.badge-sm { padding: 0.1rem 0.5rem; border-radius: 99px; font-size: 0.7rem; font-weight: 600; width: fit-content; }
.bg-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.bg-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

.search-card { max-width: 500px; }
.search-box { position: relative; margin-bottom: 1.5rem; }
.search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--text-secondary); }
.search-input { padding-left: 2.75rem; }
.empleados-list { max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; }
.empleados-list::-webkit-scrollbar { width: 6px; }
.empleados-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
.empleado-item { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; transition: all 0.2s; }
.empleado-item:hover { border-color: var(--primary-300); background: var(--bg-hover); }
.emp-info { display: flex; align-items: center; gap: 0.75rem; }
.emp-details { display: flex; flex-direction: column; }
.emp-details strong { font-size: 0.9rem; color: var(--text-primary); }
.emp-meta { font-size: 0.75rem; color: var(--text-secondary); }
.btn-sm { padding: 0.4rem 0.8rem; font-size: 0.75rem; }
.loading-state, .empty-state { text-align: center; padding: 2rem; color: var(--text-secondary); font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.action-buttons { display: flex; gap: 1rem; }
.flex-1 { flex: 1; }
.mt-1 { margin-top: 0.25rem; }
</style>
