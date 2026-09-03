<template>
  <div class="page-wrapper">
    <div class="page-top">
      <div>
        <h1 class="page-title">Usuarios del Sistema</h1>
        <p class="page-subtitle">Gestión de accesos y roles administrativos de la Constructora</p>
      </div>
      <button class="btn btn-primary" @click="openModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nuevo Usuario
      </button>
    </div>

    <div class="glass-panel">
      <div class="table-toolbar">
        <div class="input-wrapper search-input">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </span>
          <input type="text" class="input-control" placeholder="Buscar por usuario o rol..." v-model="search" />
        </div>
        <select class="input-control" style="width:155px" v-model="filterRole">
          <option value="">Todos los roles</option>
          <option value="Administrador">Administrador</option>
          <option value="RRHH">RRHH</option>
          <option value="Supervisor">Supervisor</option>
          <option value="Empleado">Empleado</option>
        </select>
        <span class="text-muted text-sm">{{ filtered.length }} usuario{{ filtered.length !== 1 ? 's' : '' }}</span>
      </div>

      <div class="data-table-wrapper">
        <SkeletonLoader v-if="loading" tipo="tabla" :filas="5" />

        <table v-else class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Usuario</th>
              <th>Empleado Asociado</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="5" class="empty-row">
                <span class="empty-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                  </svg>
                </span>
                <span>No hay usuarios registrados</span>
              </td>
            </tr>
            <template v-else>
              <tr v-for="u in paginated" :key="u.id_usuario">
                <td class="text-muted text-sm">#{{ u.id_usuario }}</td>
                <td>
                  <div class="emp-cell">
                    <div class="emp-avatar">{{ initials(u.usuario) }}</div>
                    <div>
                      <span class="font-semibold">{{ u.usuario }}</span>
                    </div>
                  </div>
                </td>
                <td class="text-sm text-secondary-color">
                  {{ u.empleado ? `${u.empleado.nombre} ${u.empleado.apellido}` : '—' }}
                </td>
                <td>
                  <span class="role-badge" :class="roleClass(u.rol)">
                    <span class="role-dot"></span>
                    {{ u.rol }}
                  </span>
                </td>
                <td>
                  <label class="toggle-switch">
                    <input type="checkbox" :checked="u.estado === 'Activo'" @change="toggleEstado(u, $event)" />
                    <span class="slider"></span>
                  </label>
                </td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon" @click="openModal(u)" title="Editar">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <button class="btn-icon danger" @click="confirmDelete(u)" title="Eliminar">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
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
        v-if="!loading && filtered.length > perPage"
        :pagina-actual="page"
        :total-paginas="totalPages"
        :total="filtered.length"
        :por-pagina="perPage"
        @cambiar="page = $event"
      />
    </div>

    <ModalBase :visible="showModal" @cerrar="closeModal" :titulo="editingId ? 'Editar Usuario' : 'Nuevo Usuario'">
      <div class="form-grid" style="gap:1rem">
        <div class="form-group">
          <label class="form-label">Nombre de Usuario *</label>
          <input type="text" v-model="form.usuario" class="input-control" :class="{ 'input-error': errors.usuario }"
            placeholder="Ej. jperez" @input="errors.usuario = ''" />
          <p v-if="errors.usuario" class="field-error">{{ errors.usuario }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Empleado Asociado</label>
          <select v-model="form.id_empleado" class="input-control">
            <option value="">Ninguno</option>
            <option v-for="e in empleados" :key="e.id_empleado" :value="e.id_empleado">
              {{ e.nombre }} {{ e.apellido }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Rol *</label>
          <select v-model="form.rol" class="input-control" :class="{'input-error': errors.rol}" @change="errors.rol=''">
            <option value="Administrador">Administrador</option>
            <option value="Empleado">Empleado</option>
          </select>
          <p v-if="errors.rol" class="field-error">{{ errors.rol }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">{{ editingId ? 'Contraseña (vacío = no cambiar)' : 'Contraseña *' }}</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input :type="showPwd ? 'text' : 'password'" v-model="form.password"
              class="input-control" :class="{ 'input-error': errors.password }"
              placeholder="Mínimo 6 caracteres" @input="errors.password = ''" />
            <button type="button" class="input-action" @click="showPwd = !showPwd" tabindex="-1">
              <svg v-if="showPwd" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
          <p v-if="errors.password" class="field-error">{{ errors.password }}</p>
        </div>
        <div class="form-group" v-if="form.password">
          <label class="form-label">Confirmar Contraseña *</label>
          <input type="password" v-model="form.password_confirmation"
            class="input-control" :class="{ 'input-error': errors.confirm }"
            placeholder="Repita la contraseña" @input="errors.confirm = ''" />
          <p v-if="errors.confirm" class="field-error">{{ errors.confirm }}</p>
        </div>
      </div>
      <template #acciones>
        <button class="btn btn-secondary" @click="closeModal">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner"></span>
          <span v-else>{{ editingId ? 'Actualizar' : 'Crear Usuario' }}</span>
        </button>
      </template>
    </ModalBase>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../plugins/axios'
import { usarConfirmacion }  from '../../composables/usarConfirmacion'
import { usarNotificacion }  from '../../composables/usarNotificacion'
import ModalBase      from '../../componentes/ModalBase.vue'
import SkeletonLoader from '../../componentes/SkeletonLoader.vue'
import PaginacionBase from '../../componentes/PaginacionBase.vue'
import { usarPaginacion } from '../../composables/usarPaginacion'

const { confirmar }            = usarConfirmacion()
const { exito, error: msjerr } = usarNotificacion()

const users      = ref([])
const empleados  = ref([])
const loading    = ref(true)
const search     = ref('')
const filterRole = ref('')
const showModal  = ref(false)
const saving     = ref(false)
const editingId  = ref(null)
const showPwd    = ref(false)
const errors     = ref({})

const defaultForm = () => ({ usuario: '', id_empleado: '', rol: 'RRHH', password: '', password_confirmation: '' })
const form = ref(defaultForm())

const filtered = computed(() => {
  let l = users.value
  if (search.value) {
    const q = search.value.toLowerCase()
    l = l.filter(u => u.usuario?.toLowerCase().includes(q) || u.rol?.toLowerCase().includes(q))
  }
  if (filterRole.value) l = l.filter(u => u.rol === filterRole.value)
  return l
})

const perPage    = 8

const {
  paginaActual: page,
  totalPaginas: totalPages,
  elementosPaginados: paginated
} = usarPaginacion(filtered, perPage)

const initials  = (n = '') => n.slice(0, 2).toUpperCase()
const roleClass = r => ({ Administrador: 'role-admin', RRHH: 'role-hr', Supervisor: 'role-sup', Empleado: 'role-emp' }[r] || '')

const cargarDatos = async () => {
  loading.value = true
  try {
    const [userRes, empRes] = await Promise.all([api.get('/usuarios'), api.get('/empleados')])
    users.value     = userRes.data.datos?.data ?? userRes.data.datos ?? userRes.data
    empleados.value = empRes.data.datos?.data ?? empRes.data.datos ?? empRes.data
  } catch { msjerr('Error al cargar datos') }
  finally { loading.value = false }
}

const openModal = (u = null) => {
  editingId.value = u?.id_usuario || null
  form.value = u ? { usuario: u.usuario, id_empleado: u.id_empleado || '', rol: u.rol, password: '', password_confirmation: '' } : defaultForm()
  errors.value = {}
  showPwd.value = false
  showModal.value = true
}
const closeModal = () => { showModal.value = false }

const validate = () => {
  errors.value = {}
  if (!form.value.usuario || !form.value.usuario.trim()) errors.value.usuario = 'El nombre de usuario es obligatorio'
  if (!form.value.rol) errors.value.rol = 'El rol es obligatorio'
  if (!editingId.value && !form.value.password) errors.value.password = 'La contraseña es obligatoria para usuarios nuevos'
  if (form.value.password && form.value.password.length < 6) errors.value.password = 'Mínimo 6 caracteres'
  if (form.value.password && form.value.password !== form.value.password_confirmation) errors.value.confirm = 'Las contraseñas no coinciden'
  
  const isValid = Object.keys(errors.value).length === 0
  if (!isValid) {
    msjerr('Por favor, complete todos los campos correctamente')
  }
  return isValid
}

const save = async () => {
  if (!validate()) return
  saving.value = true
  try {
    const payload = { ...form.value }
    if (!payload.id_empleado) payload.id_empleado = null
    if (editingId.value && !payload.password) { delete payload.password; delete payload.password_confirmation }
    if (editingId.value) {
      await api.put(`/usuarios/${editingId.value}`, payload)
      exito('Usuario actualizado correctamente')
    } else {
      await api.post('/usuarios', payload)
      exito('Usuario creado correctamente')
    }
    closeModal(); cargarDatos()
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errores || err.response.data.errors;
      for (const key in serverErrors) {
        // En usuarios las contraseñas no coinciden puede venir como 'password'
        errors.value[key] = serverErrors[key][0];
      }
      msjerr('Por favor, verifique los errores en el formulario.');
    } else {
      msjerr(err.response?.data?.mensaje || err.response?.data?.message || 'Error al guardar el usuario')
    }
  } finally { saving.value = false }
}

const confirmDelete = async (u) => {
  const ok = await confirmar(`¿Eliminar usuario "${u.usuario}"?`, {
    titulo: 'Eliminar Usuario', textoConfirmar: 'Eliminar', textoCancelar: 'Cancelar'
  })
  if (!ok) return
  try {
    await api.delete(`/usuarios/${u.id_usuario}`)
    exito('Usuario eliminado correctamente')
    cargarDatos()
  } catch (err) { msjerr(err.response?.data?.message || 'Error al eliminar el usuario') }
}

const toggleEstado = async (u, event) => {
  const checkbox = event.target
  const nuevoEstado = u.estado === 'Activo' ? 'Inactivo' : 'Activo'
  
  const ok = await confirmar(`¿Estás seguro de cambiar el estado a ${nuevoEstado}?`, {
    titulo: 'Confirmar Acción', textoConfirmar: 'Sí, cambiar', textoCancelar: 'Cancelar'
  })
  
  if (!ok) {
    checkbox.checked = u.estado === 'Activo'
    return
  }
  
  try {
    await api.patch(`/usuarios/${u.id_usuario}/estado`, { estado: nuevoEstado })
    u.estado = nuevoEstado
    exito(`Usuario marcado como ${nuevoEstado}`)
  } catch (err) {
    checkbox.checked = u.estado === 'Activo'
    msjerr(err.response?.data?.mensaje || 'Error al actualizar el estado')
  }
}

onMounted(cargarDatos)
</script>

<style scoped>
.search-input { flex: 1; min-width: 200px; max-width: 300px; }

.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.6875rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 99px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border: 1px solid transparent;
}
.role-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: 0.85; flex-shrink: 0; }

.role-admin { background: rgba(124, 58, 237, 0.1); color: #6d28d9; border-color: rgba(124, 58, 237, 0.2); }
.role-hr    { background: rgba(37, 99, 235, 0.1);  color: #1d4ed8; border-color: rgba(37, 99, 235, 0.2); }
.role-sup   { background: rgba(217, 119, 6, 0.1);  color: #b45309; border-color: rgba(217, 119, 6, 0.2); }
.role-emp   { background: rgba(16, 185, 129, 0.1); color: #047857; border-color: rgba(16, 185, 129, 0.2); }

.input-error { border-color: var(--danger-500) !important; }
.input-error:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important; }
.field-error { font-size: 0.7rem; color: var(--danger-600); margin-top: 0.25rem; font-weight: 500; }

/* Toggle Switch Styles */
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 40px;
  height: 22px;
}
.toggle-switch input { 
  opacity: 0;
  width: 0;
  height: 0;
}
.slider {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: #cbd5e1;
  transition: .4s;
  border-radius: 34px;
}
.slider:before {
  position: absolute;
  content: "";
  height: 16px;
  width: 16px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
input:checked + .slider {
  background-color: #10b981;
}
input:checked + .slider:before {
  transform: translateX(18px);
}
</style>
