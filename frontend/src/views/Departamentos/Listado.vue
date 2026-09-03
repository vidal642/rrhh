<template>
  <div class="page-wrapper">
    <div class="page-top">
      <div>
        <h1 class="page-title">Departamentos</h1>
        <p class="page-subtitle">Gestión de áreas y unidades de la empresa constructora</p>
      </div>
      <div style="display: flex; gap: 0.75rem;">
        <BotonActualizar :cargando="loading" @actualizar="fetchDepartamentos" />
        <button class="btn btn-primary" @click="openModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Nuevo Departamento
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
          <input type="text" class="input-control" placeholder="Buscar departamento o descripción..." v-model="search" />
        </div>
        <span class="text-muted text-sm">{{ filtered.length }} resultado{{ filtered.length !== 1 ? 's' : '' }}</span>
      </div>

      <div class="data-table-wrapper">
        <!-- Skeleton -->
        <SkeletonLoader v-if="loading" tipo="tabla" :filas="5" />

        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="error">
              <td colspan="3" class="empty-row error-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ error }}
              </td>
            </tr>
            <tr v-else-if="filtered.length === 0">
              <td colspan="3" class="empty-row">
                <span class="empty-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                  </svg>
                </span>
                <span>No hay departamentos registrados</span>
              </td>
            </tr>
            <template v-else>
              <tr v-for="dept in paginated" :key="dept.id_departamento">
                <td>
                  <div class="dept-cell">
                    <div class="dept-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                      </svg>
                    </div>
                    <span class="font-semibold">{{ dept.nombre }}</span>
                  </div>
                </td>
                <td class="text-secondary-color text-sm">{{ dept.descripcion || '—' }}</td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon" title="Editar" @click="openModal(dept)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <button class="btn-icon danger" title="Eliminar" @click="confirmDelete(dept)">
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
    <ModalBase :visible="showModal" @cerrar="closeModal" :titulo="editingId ? 'Editar Departamento' : 'Nuevo Departamento'">
      <div class="form-grid" style="gap:1rem;">
        <div class="form-group">
          <label class="form-label">Nombre del Departamento *</label>
          <input
            type="text"
            v-model="form.nombre"
            class="input-control"
            :class="{ 'input-error': errors.nombre }"
            placeholder="Ej. Recursos Humanos"
            @input="errors.nombre = ''"
          />
          <p v-if="errors.nombre" class="field-error">{{ errors.nombre }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Descripción</label>
          <textarea v-model="form.descripcion" class="input-control" placeholder="Descripción del departamento..." rows="2"></textarea>
        </div>
      </div>
      <template #acciones>
        <button type="button" class="btn btn-secondary" @click="closeModal">Cancelar</button>
        <button type="button" class="btn btn-primary" :disabled="saving" @click="saveDepartamento">
          <span v-if="saving" class="spinner"></span>
          <span v-else>{{ editingId ? 'Actualizar' : 'Crear Departamento' }}</span>
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

const departamentos = ref([])
const loading  = ref(true)
const error    = ref(null)
const search   = ref('')
const showModal = ref(false)
const saving    = ref(false)
const editingId = ref(null)
const form   = ref({ nombre: '', descripcion: '' })
const errors = ref({})

const filtered   = computed(() => departamentos.value.filter(d =>
  d.nombre.toLowerCase().includes(search.value.toLowerCase()) ||
  (d.descripcion || '').toLowerCase().includes(search.value.toLowerCase())
))

const perPage = 8

const {
  paginaActual: page,
  totalPaginas: totalPages,
  elementosPaginados: paginated
} = usarPaginacion(filtered, perPage)

const fetchDepartamentos = async () => {
  loading.value = true; error.value = null
  try {
    const res = await api.get('/departamentos')
    departamentos.value = res.data.datos ?? res.data
  } catch {
    error.value = 'No se pudieron cargar los departamentos.'
  } finally { loading.value = false }
}

const openModal = (dept = null) => {
  editingId.value = dept?.id_departamento || null
  form.value = dept ? { ...dept } : { nombre: '', descripcion: '' }
  errors.value = { nombre: '' }
  showModal.value = true
}
const closeModal = () => { showModal.value = false }

const validate = () => {
  errors.value = {}
  if (!form.value.nombre || !form.value.nombre.trim()) {
    errors.value.nombre = 'El nombre es obligatorio'
    msjerr('Por favor, complete todos los campos obligatorios.')
  }
  return !errors.value.nombre
}

const saveDepartamento = async () => {
  if (!validate()) return
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/departamentos/${editingId.value}`, form.value)
      exito('Departamento actualizado correctamente')
    } else {
      await api.post('/departamentos', form.value)
      exito('Departamento creado correctamente')
    }
    closeModal(); fetchDepartamentos()
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errores || err.response.data.errors;
      for (const key in serverErrors) {
        errors.value[key] = serverErrors[key][0];
      }
      msjerr('Por favor, verifique los errores en el formulario.');
    } else {
      const msg = err.response?.data?.mensaje || err.response?.data?.message || 'Error al guardar el departamento';
      msjerr(msg);
    }
  } finally { saving.value = false }
}

const confirmDelete = async (dept) => {
  const ok = await confirmar(`¿Eliminar "${dept.nombre}"? Esta acción no se puede deshacer.`, {
    titulo: 'Eliminar Departamento', textoConfirmar: 'Eliminar', textoCancelar: 'Cancelar'
  })
  if (!ok) return
  try {
    await api.delete(`/departamentos/${dept.id_departamento}`)
    exito('Departamento eliminado')
    fetchDepartamentos()
  } catch { msjerr('Error al eliminar el departamento') }
}

fetchDepartamentos()
</script>

<style scoped>
.search-input { flex: 1; min-width: 200px; max-width: 360px; }

.dept-cell { display: flex; align-items: center; gap: 0.625rem; }
.dept-icon {
  width: 28px; height: 28px;
  border-radius: 7px;
  background: rgba(124, 58, 237, 0.08);
  border: 1px solid rgba(124, 58, 237, 0.12);
  color: var(--primary-500);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* Validación inline */
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
