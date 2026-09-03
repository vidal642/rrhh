<template>
  <div class="page-wrapper">
    <div class="page-top">
      <div>
        <h1 class="page-title">Cargos</h1>
        <p class="page-subtitle">Gestión de puestos de trabajo y salarios de referencia</p>
      </div>
      <div style="display: flex; gap: 0.75rem;">
        <BotonActualizar :cargando="loading" @actualizar="fetchCargos" />
        <button class="btn btn-primary" @click="openModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Nuevo Cargo
        </button>
      </div>
    </div>

    <div class="glass-panel">
      <div class="table-toolbar">
        <div class="input-wrapper search-input">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </span>
          <input type="text" class="input-control" placeholder="Buscar cargo o descripción..." v-model="search" />
        </div>
        <span class="text-muted text-sm">{{ filtered.length }} resultado{{ filtered.length !== 1 ? 's' : '' }}</span>
      </div>

      <div class="data-table-wrapper">
        <SkeletonLoader v-if="loading" tipo="tabla" :filas="5" />

        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Cargo</th>
              <th>Departamento</th>
              <th>Salario Referencia</th>
              <th>Descripción</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="error">
              <td colspan="5" class="empty-row error-row">{{ error }}</td>
            </tr>
            <tr v-else-if="filtered.length === 0">
              <td colspan="5" class="empty-row">
                <span class="empty-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                  </svg>
                </span>
                <span>No hay cargos registrados</span>
              </td>
            </tr>
            <template v-else>
              <tr v-for="cargo in paginated" :key="cargo.id_cargo">
                <td>
                  <div class="cargo-cell">
                    <div class="cargo-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                        <rect x="2" y="7" width="20" height="14" rx="2"/>
                        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                        <line x1="12" y1="12" x2="12" y2="17"/>
                        <line x1="9.5" y1="14.5" x2="14.5" y2="14.5"/>
                      </svg>
                    </div>
                    <span class="font-semibold">{{ cargo.nombre }}</span>
                  </div>
                </td>
                <td class="text-sm">{{ cargo.departamento?.nombre || '—' }}</td>
                <td>
                  <span class="salary-badge">
                    Bs. {{ Number(cargo.salario_referencia || 0).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
                  </span>
                </td>
                <td class="text-secondary-color text-sm">{{ cargo.descripcion || '—' }}</td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon" title="Editar" @click="openModal(cargo)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <button class="btn-icon danger" title="Eliminar" @click="confirmDelete(cargo)">
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
        v-if="!loading && filtered.length > perPage"
        :pagina-actual="page"
        :total-paginas="totalPages"
        :total="filtered.length"
        :por-pagina="perPage"
        @cambiar="page = $event"
      />
    </div>

    <!-- Modal con ModalBase -->
    <ModalBase :visible="showModal" @cerrar="closeModal" :titulo="editingId ? 'Editar Cargo' : 'Nuevo Cargo'">
      <div class="form-grid" style="gap:1rem;">
        <div class="form-group">
          <label class="form-label">Nombre del Cargo *</label>
          <input type="text" v-model="form.nombre" class="input-control" :class="{ 'input-error': errors.nombre }"
            placeholder="Ej. Técnico de Minas" @input="errors.nombre = ''" />
          <p v-if="errors.nombre" class="field-error">{{ errors.nombre }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Departamento *</label>
          <select v-model="form.id_departamento" class="input-control" :class="{ 'input-error': errors.id_departamento }" @change="errors.id_departamento = ''">
            <option value="">Seleccione un departamento...</option>
            <option v-for="d in departamentos" :key="d.id_departamento" :value="d.id_departamento">{{ d.nombre }}</option>
          </select>
          <p v-if="errors.id_departamento" class="field-error">{{ errors.id_departamento }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Salario de Referencia (Bs.) *</label>
          <div class="input-wrapper">
            <span class="input-icon" style="font-size:0.75rem;font-weight:700;color:var(--text-secondary);">Bs.</span>
            <input type="number" step="0.01" min="3300" v-model="form.salario_referencia"
              class="input-control" :class="{ 'input-error': errors.salario_referencia }"
              placeholder="0.00" @input="errors.salario_referencia = ''" />
          </div>
          <p v-if="errors.salario_referencia" class="field-error">{{ errors.salario_referencia }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Descripción</label>
          <textarea v-model="form.descripcion" class="input-control" placeholder="Descripción del cargo..." rows="2"></textarea>
        </div>
      </div>
      <template #acciones>
        <button type="button" class="btn btn-secondary" @click="closeModal">Cancelar</button>
        <button type="button" class="btn btn-primary" :disabled="saving" @click="saveCargo">
          <span v-if="saving" class="spinner"></span>
          <span v-else>{{ editingId ? 'Actualizar' : 'Crear Cargo' }}</span>
        </button>
      </template>
    </ModalBase>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '../../plugins/axios'
import { usarConfirmacion }  from '../../composables/usarConfirmacion'
import { usarNotificacion }  from '../../composables/usarNotificacion'
import ModalBase      from '../../componentes/ModalBase.vue'
import SkeletonLoader from '../../componentes/SkeletonLoader.vue'
import PaginacionBase from '../../componentes/PaginacionBase.vue'
import BotonActualizar from '../../componentes/BotonActualizar.vue'
import { usarPaginacion } from '../../composables/usarPaginacion'

const { confirmar }            = usarConfirmacion()
const { exito, error: msjerr } = usarNotificacion()

const cargos    = ref([])
const loading   = ref(true)
const error     = ref(null)
const search    = ref('')
const showModal = ref(false)
const saving    = ref(false)
const editingId = ref(null)
const departamentos = ref([])
const form   = ref({ nombre: '', salario_referencia: 0, descripcion: '', id_departamento: '' })
const errors = ref({})

const filtered   = computed(() => cargos.value.filter(c =>
  c.nombre.toLowerCase().includes(search.value.toLowerCase()) ||
  (c.descripcion || '').toLowerCase().includes(search.value.toLowerCase())
))

const perPage   = 8

const {
  paginaActual: page,
  totalPaginas: totalPages,
  elementosPaginados: paginated
} = usarPaginacion(filtered, perPage)

const fetchCargos = async () => {
  loading.value = true; error.value = null
  try {
    const [resCargos, resDeptos] = await Promise.all([
      api.get('/cargos'),
      api.get('/departamentos')
    ])
    cargos.value = resCargos.data.datos ?? resCargos.data
    departamentos.value = resDeptos.data.datos ?? resDeptos.data
  } catch {
    error.value = 'No se pudieron cargar los datos.'
  } finally { loading.value = false }
}

const openModal = (cargo = null) => {
  editingId.value = cargo?.id_cargo || null
  form.value = cargo ? { ...cargo } : { nombre: '', salario_referencia: 0, descripcion: '', id_departamento: '' }
  errors.value = {}
  showModal.value = true
}
const closeModal = () => { showModal.value = false }

const validate = () => {
  errors.value = {}
  if (!form.value.nombre || !form.value.nombre.trim()) errors.value.nombre = 'El nombre es obligatorio'
  if (!form.value.id_departamento) errors.value.id_departamento = 'Seleccione un departamento'
  if (!form.value.salario_referencia || form.value.salario_referencia < 3300) errors.value.salario_referencia = 'El salario debe ser al menos 3300 Bs (mínimo nacional)'
  
  const isValid = !errors.value.nombre && !errors.value.salario_referencia && !errors.value.id_departamento
  if (!isValid) {
    msjerr('Por favor, complete todos los campos obligatorios.')
  }
  return isValid
}

const saveCargo = async () => {
  if (!validate()) return
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/cargos/${editingId.value}`, form.value)
      exito('Cargo actualizado correctamente')
    } else {
      await api.post('/cargos', form.value)
      exito('Cargo creado correctamente')
    }
    closeModal(); fetchCargos()
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errores || err.response.data.errors;
      for (const key in serverErrors) {
        errors.value[key] = serverErrors[key][0];
      }
      msjerr('Por favor, verifique los errores en el formulario.');
    } else {
      const msg = err.response?.data?.mensaje || err.response?.data?.message || 'Error al guardar el cargo';
      msjerr(msg);
    }
  } finally { saving.value = false }
}

const confirmDelete = async (cargo) => {
  const ok = await confirmar(`¿Eliminar el cargo "${cargo.nombre}"?`, {
    titulo: 'Eliminar Cargo', textoConfirmar: 'Eliminar', textoCancelar: 'Cancelar'
  })
  if (!ok) return
  try {
    await api.delete(`/cargos/${cargo.id_cargo}`)
    exito('Cargo eliminado')
    fetchCargos()
  } catch { msjerr('Error al eliminar el cargo') }
}

fetchCargos()
</script>

<style scoped>
.search-input { flex: 1; min-width: 200px; max-width: 360px; }

.cargo-cell { display: flex; align-items: center; gap: 0.625rem; }
.cargo-icon {
  width: 28px; height: 28px;
  border-radius: 7px;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.15);
  color: var(--secondary-600);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.input-error { border-color: var(--danger-500) !important; }
.input-error:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important; }
.field-error { font-size: 0.7rem; color: var(--danger-600); margin-top: 0.25rem; font-weight: 500; }
</style>
