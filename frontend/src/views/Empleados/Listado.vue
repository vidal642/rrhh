<template>
  <div class="page-wrapper">
    <div class="page-top">
      <div>
        <h1 class="page-title">Empleados</h1>
        <p class="page-subtitle">Gestión y control de personal de la Constructora</p>
      </div>
      <div style="display: flex; gap: 0.75rem;">
        <BotonActualizar :cargando="loading" @actualizar="fetchAll" />
        <button class="btn btn-primary" @click="openModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Nuevo Empleado
        </button>
      </div>
    </div>

    <div class="glass-panel">
      <!-- Toolbar -->
      <div class="table-toolbar">
        <div class="input-wrapper search-input">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </span>
          <input type="text" class="input-control" placeholder="Buscar por código, nombre, C.I. o departamento..." v-model="search" />
        </div>
        <div class="filter-group">
          <select class="input-control filter-select" v-model="filterStatus">
            <option value="">Todos los estados</option>
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
            <option value="Vacaciones">Vacaciones</option>
            <option value="Suspendido">Suspendido</option>
            <option value="Retirado">Retirado</option>
          </select>
        </div>
        <span class="text-muted text-sm">{{ totalElementos }} empleado{{ totalElementos !== 1 ? 's' : '' }}</span>
      </div>

      <!-- Table -->
      <div class="data-table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Empleado</th>
              <th>C.I.</th>
              <th>Departamento</th>
              <th>Cargo</th>
              <th>Teléfono</th>
              <th>Salario Base</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="empty-row">
                <div class="spinner spinner-dark"></div>
                <span>Cargando empleados...</span>
              </td>
            </tr>
            <tr v-else-if="error">
              <td colspan="8" class="empty-row error-row">{{ error }}</td>
            </tr>
            <tr v-else-if="empleados.length === 0">
              <td colspan="8" class="empty-row">
                <span class="empty-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="36" height="36">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                  </svg>
                </span>
                <span>No hay empleados registrados</span>
              </td>
            </tr>
            <template v-else>
              <tr v-for="emp in empleados" :key="emp.id_empleado">
                <td>
                  <div class="emp-cell">
                    <div class="emp-avatar">{{ empInitials(emp) }}</div>
                    <div>
                      <div class="font-semibold">{{ emp.nombre }} {{ emp.apellido }}</div>
                      <div class="text-xs text-muted emp-code">{{ emp.codigo_empleado || 'Sin código' }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-sm">{{ emp.ci }} {{ emp.extension_ci ? emp.extension_ci : '' }}</td>
                <td class="text-sm">{{ emp.departamento?.nombre || '—' }}</td>
                <td class="text-sm">{{ emp.cargo?.nombre || '—' }}</td>
                <td class="text-sm text-secondary-color">{{ emp.telefono || '—' }}</td>
                <td class="text-sm font-semibold">Bs. {{ emp.salario_base }}</td>
                <td>
                  <span class="badge" :class="statusBadge(emp.estado)">{{ emp.estado }}</span>
                </td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon" title="Editar" @click="openModal(emp)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <button class="btn-icon danger" title="Eliminar" @click="confirmDelete(emp)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <PaginacionBase
        v-if="!loading && totalPaginas > 1"
        :pagina-actual="paginaActual"
        :total-paginas="totalPaginas"
        :total="totalElementos"
        :por-pagina="porPagina"
        @cambiar="handleCambioPagina"
      />
    </div>

    <!-- Modal -->
    <teleport to="body">
      <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
        <div class="modal-box modal-lg animate-fade-in">
          <div class="modal-header">
            <h3>{{ editingId ? 'Editar Empleado' : 'Nuevo Empleado' }}</h3>
            <button class="modal-close" @click="closeModal">×</button>
          </div>
          <div class="modal-body">
            <div class="form-grid form-grid-2" style="gap:1rem;">
              <div class="form-group">
                <label class="form-label">Nombres *</label>
                <input type="text" v-model="form.nombre" class="input-control" :class="{ 'input-error': errors.nombre }" placeholder="Ej. Juan Carlos" required @input="errors.nombre = ''" />
                <p v-if="errors.nombre" class="field-error">{{ errors.nombre }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Apellidos *</label>
                <input type="text" v-model="form.apellido" class="input-control" :class="{ 'input-error': errors.apellido }" placeholder="Ej. García López" required @input="errors.apellido = ''" />
                <p v-if="errors.apellido" class="field-error">{{ errors.apellido }}</p>
              </div>
              <div class="form-group" style="display: grid; grid-template-columns: 3fr 1fr; gap: 1rem; align-items: start;">
                <div>
                  <label class="form-label">Cédula de Identidad *</label>
                  <input type="text" v-model="form.ci" class="input-control" :class="{ 'input-error': errors.ci }" placeholder="12345678" required @input="form.ci = form.ci.replace(/\D/g, ''); errors.ci = ''" />
                  <p v-if="errors.ci" class="field-error">{{ errors.ci }}</p>
                </div>
                <div>
                  <label class="form-label">Extensión</label>
                  <select v-model="form.extension_ci" class="input-control" :class="{ 'input-error': errors.extension_ci }" @change="errors.extension_ci = ''">
                    <option value="">Ext.</option>
                    <option value="LP">LP</option>
                    <option value="CB">CB</option>
                    <option value="SC">SC</option>
                    <option value="PT">PT</option>
                    <option value="OR">OR</option>
                    <option value="TJ">TJ</option>
                    <option value="CH">CH</option>
                    <option value="BE">BE</option>
                    <option value="PD">PD</option>
                  </select>
                  <p v-if="errors.extension_ci" class="field-error">{{ errors.extension_ci }}</p>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Teléfono</label>
                <input type="text" v-model="form.telefono" class="input-control" :class="{ 'input-error': errors.telefono }" placeholder="70000000" @input="errors.telefono = ''" />
                <p v-if="errors.telefono" class="field-error">{{ errors.telefono }}</p>
              </div>
              <div class="form-group" style="grid-column: 1 / -1">
                <label class="form-label">Dirección</label>
                <input type="text" v-model="form.direccion" class="input-control" :class="{ 'input-error': errors.direccion }" placeholder="Dirección de residencia" @input="errors.direccion = ''" />
                <p v-if="errors.direccion" class="field-error">{{ errors.direccion }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Departamento *</label>
                <select v-model="form.id_departamento" class="input-control" :class="{ 'input-error': errors.id_departamento }" required @change="alCambiarDepartamento">
                  <option value="">Seleccione...</option>
                  <option v-for="d in departamentos" :key="d.id_departamento" :value="d.id_departamento">{{ d.nombre }}</option>
                </select>
                <p v-if="errors.id_departamento" class="field-error">{{ errors.id_departamento }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Cargo *</label>
                <select v-model="form.id_cargo" class="input-control" :class="{ 'input-error': errors.id_cargo }" required :disabled="!form.id_departamento" @change="alCambiarCargo">
                  <option value="">{{ !form.id_departamento ? 'Seleccione primero un departamento...' : 'Seleccione...' }}</option>
                  <option v-for="c in cargosFiltrados" :key="c.id_cargo" :value="c.id_cargo" :disabled="isCargoOcupado(c)">
                    {{ c.nombre }} {{ isCargoOcupado(c) ? '(Ya ocupado)' : '' }}
                  </option>
                </select>
                <p v-if="errors.id_cargo" class="field-error">{{ errors.id_cargo }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Fecha de Contratación *</label>
                <input type="date" v-model="form.fecha_contratacion" class="input-control" :class="{ 'input-error': errors.fecha_contratacion }" required @input="errors.fecha_contratacion = ''" :min="fechaActual" />
                <p v-if="errors.fecha_contratacion" class="field-error">{{ errors.fecha_contratacion }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Salario Base (Bs.) *</label>
                <input type="number" step="0.01" v-model="form.salario_base" class="input-control" :class="{ 'input-error': errors.salario_base }" placeholder="2500" required @input="errors.salario_base = ''" />
                <p v-if="errors.salario_base" class="field-error">{{ errors.salario_base }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Estado</label>
                <select v-model="form.estado" class="input-control" :class="{ 'input-error': errors.estado }" @change="errors.estado = ''">
                  <option value="Activo">Activo</option>
                  <option value="Inactivo">Inactivo</option>
                </select>
                <p v-if="errors.estado" class="field-error">{{ errors.estado }}</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">Cancelar</button>
            <button type="button" class="btn btn-primary" :disabled="saving" @click="saveEmpleado">
              <span v-if="saving" class="spinner"></span>
              <span v-else>{{ editingId ? 'Actualizar Empleado' : 'Crear Empleado' }}</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Toasts -->
    <teleport to="body">
      <div class="toast-container">
        <transition-group name="toast-slide">
          <div v-for="t in toasts" :key="t.id" class="toast" :class="`toast-${t.type}`">
            <span class="toast-icon"></span>
            <span class="toast-msg">{{ t.msg }}</span>
          </div>
        </transition-group>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import api from '../../plugins/axios'
import { usarConfirmacion } from '../../composables/usarConfirmacion'
import PaginacionBase from '../../componentes/PaginacionBase.vue'
import BotonActualizar from '../../componentes/BotonActualizar.vue'

const { confirmar } = usarConfirmacion()

const empleados     = ref([])
const departamentos = ref([])
const cargos        = ref([])
const cargosFiltrados = ref([])
const loadingCargos = ref(false)
const loading   = ref(true)
const error     = ref(null)
const search    = ref('')
const filterStatus = ref('')
const showModal = ref(false)
const saving    = ref(false)
const editingId = ref(null)
const toasts    = ref([])
const errors    = ref({})

const fechaActual = new Date().toISOString().split('T')[0]

const defaultForm = () => ({
  nombre: '',
  apellido: '',
  ci: '',
  extension_ci: '',
  telefono: '',
  direccion: '',
  id_departamento: '',
  id_cargo: '',
  fecha_contratacion: fechaActual,
  salario_base: '',
  estado: 'Activo'
})

const form = ref(defaultForm())

// Paginación desde servidor
const paginaActual = ref(1)
const totalPaginas = ref(1)
const totalElementos = ref(0)
const porPagina = ref(8)

// Watchers para recargar al buscar/filtrar (volviendo a página 1)
let debounceTimer = null
watch(search, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchEmpleados(1), 300)
})
watch(filterStatus, () => fetchEmpleados(1))

const handleCambioPagina = (p) => {
  fetchEmpleados(p)
}

const empInitials = (e) => `${e.nombre?.[0] || ''}${e.apellido?.[0] || ''}`.toUpperCase()

const statusBadge = (s) => {
  const map = { Activo: 'badge-success', Inactivo: 'badge-danger', Vacaciones: 'badge-warning', Suspendido: 'badge-gray', Retirado: 'badge-gray' }
  return map[s] || 'badge-gray'
}

const addToast = (msg, type = 'success') => {
  const id = Date.now()
  toasts.value.push({ id, msg, type })
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id) }, 3500)
}

const fetchEmpleados = async (p = 1) => {
  loading.value = true; error.value = null
  try {
    const params = { page: p, per_page: porPagina.value }
    if (search.value) params.q = search.value
    if (filterStatus.value) params.estado = filterStatus.value

    const res = await api.get('/empleados', { params })
    const datos = res.data.datos || res.data
    
    empleados.value = datos.data || []
    paginaActual.value = datos.current_page || 1
    totalPaginas.value = datos.last_page || 1
    totalElementos.value = datos.total || 0
  } catch {
    error.value = 'No se pudieron cargar los empleados.'
  } finally { loading.value = false }
}

const fetchAll = async () => {
  try {
    const [deptRes, cargosRes] = await Promise.all([
      api.get('/departamentos'),
      api.get('/cargos')
    ])
    departamentos.value = deptRes.data.datos ?? deptRes.data
    cargos.value        = cargosRes.data.datos ?? cargosRes.data
    await fetchEmpleados(1)
  } catch {
    error.value = 'Error al cargar dependencias.'
  }
}

const fetchCargosPorDepartamento = async (idDepto) => {
  if (!idDepto) {
    cargosFiltrados.value = []
    return
  }
  loadingCargos.value = true
  try {
    const res = await api.get(`/departamentos/${idDepto}/cargos`)
    cargosFiltrados.value = res.data.datos ?? res.data
  } catch (err) {
    addToast('Error al cargar los cargos del departamento.', 'error')
    cargosFiltrados.value = []
  } finally {
    loadingCargos.value = false
  }
}

const alCambiarDepartamento = async () => {
  form.value.id_cargo = ''
  if (form.value.id_departamento) {
    await fetchCargosPorDepartamento(form.value.id_departamento)
  } else {
    cargosFiltrados.value = []
  }
}

const alCambiarCargo = () => {
  const cargoSeleccionado = cargosFiltrados.value.find(c => c.id_cargo === form.value.id_cargo)
  if (cargoSeleccionado && cargoSeleccionado.salario_referencia) {
    if (!form.value.salario_base) {
      form.value.salario_base = cargoSeleccionado.salario_referencia
    }
  }
}

const isCargoOcupado = (cargo) => {
  if (cargo.es_unico === true || cargo.es_unico === 1) {
    return empleados.value.some(e => 
      e.id_cargo === cargo.id_cargo && 
      e.id_empleado !== form.value.id_empleado
    );
  }
  return false;
}

const openModal = async (emp = null) => {
  editingId.value = emp?.id_empleado || null
  errors.value = {}
  if (emp) {
    form.value = { ...emp }
    if (form.value.id_departamento) {
      await fetchCargosPorDepartamento(form.value.id_departamento)
    } else {
      cargosFiltrados.value = []
    }
  } else {
    form.value = defaultForm()
    cargosFiltrados.value = []
  }
  showModal.value = true
}
const closeModal = () => { showModal.value = false }

const saveEmpleado = async () => {
  errors.value = {}
  
  if (!form.value.nombre || !form.value.apellido || !form.value.ci || !form.value.id_departamento || !form.value.id_cargo || !form.value.salario_base || form.value.nombre.trim() === '' || form.value.apellido.trim() === '') {
    addToast('Por favor, complete todos los campos obligatorios.', 'error')
    return
  }
  
  const letrasYEspaciosRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/;
  if (!letrasYEspaciosRegex.test(form.value.nombre)) {
    errors.value.nombre = 'El nombre solo puede contener letras y espacios.'
    addToast('Por favor, verifique los errores en el formulario.', 'error')
    return
  }
  
  if (!letrasYEspaciosRegex.test(form.value.apellido)) {
    errors.value.apellido = 'El apellido solo puede contener letras y espacios.'
    addToast('Por favor, verifique los errores en el formulario.', 'error')
    return
  }
  
  if (!/^\d{7,8}$/.test(form.value.ci)) {
    errors.value.ci = 'La Cédula de Identidad debe tener exactamente 7 u 8 dígitos numéricos, sin letras.'
    addToast('Por favor, verifique los errores en el formulario.', 'error')
    return
  }

  if (form.value.telefono && !/^\d{8}$/.test(form.value.telefono)) {
    errors.value.telefono = 'El teléfono debe contener exactamente 8 dígitos numéricos.'
    addToast('Por favor, verifique los errores en el formulario.', 'error')
    return
  }

  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/empleados/${editingId.value}`, form.value)
      addToast('Empleado actualizado correctamente')
    } else {
      await api.post('/empleados', form.value)
      addToast('Empleado creado correctamente')
    }
    closeModal(); fetchAll()
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errores || err.response.data.errors;
      for (const key in serverErrors) {
        errors.value[key] = serverErrors[key][0];
      }
      addToast('Por favor, verifique los errores en el formulario.', 'error');
    } else {
      const msg = err.response?.data?.mensaje || err.response?.data?.message || 'Error al guardar el empleado';
      addToast(msg, 'error');
    }
  } finally { saving.value = false }
}

const confirmDelete = async (emp) => {
  const confirmado = await confirmar(`¿Eliminar a ${emp.nombre} ${emp.apellido}?`, {
    titulo: 'Eliminar Empleado',
    textoConfirmar: 'Eliminar',
    textoCancelar: 'Cancelar'
  })
  
  if (!confirmado) return

  try {
    await api.put(`/empleados/${emp.id_empleado}`, { 
      ...emp, 
      estado: 'Inactivo' 
    })
    addToast('Empleado desactivado (Inactivo) correctamente')
    fetchAll()
  } catch {
    addToast('Error al procesar la eliminación', 'error')
  }
}

fetchAll()
</script>

<style scoped>
.table-toolbar {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
}
.search-input { flex: 1; min-width: 200px; max-width: 360px; }
.filter-group { flex-shrink: 0; }
.filter-select { width: 170px; }

.empty-row {
  text-align: center;
  padding: 3rem 1rem !important;
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.625rem;
  font-size: 0.875rem;
}
.empty-icon { color: var(--border-strong); }
.error-row  { color: var(--danger-600); }

.emp-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.emp-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(109, 40, 217, 0.1);
  border: 1px solid rgba(109, 40, 217, 0.18);
  color: var(--primary-600);
  font-size: 0.7rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.emp-code {
  font-family: 'Courier New', Courier, monospace;
  letter-spacing: 0.03em;
  color: var(--text-muted);
  margin-top: 0.1rem;
}

.action-btns { display: flex; gap: 0.25rem; justify-content: center; }

.table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1.5rem;
  border-top: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 0.875rem;
}
.pagination { display: flex; gap: 0.5rem; }

.toast-slide-enter-active { transition: all 0.32s cubic-bezier(0.34, 1.4, 0.64, 1); }
.toast-slide-leave-active  { transition: all 0.22s ease; }
.toast-slide-enter-from { opacity: 0; transform: translateX(70px); }
.toast-slide-leave-to   { opacity: 0; transform: translateX(70px); }

.input-error { border-color: var(--danger-500) !important; }
.input-error:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important; }
.field-error {
  font-size: 0.7rem;
  color: var(--danger-600);
  margin-top: 0.25rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.2rem;
}
</style>