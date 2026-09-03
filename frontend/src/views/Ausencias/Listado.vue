<template>
  <div class="page-wrapper">
    <div class="page-top" v-if="authStore.user?.rol !== 'Empleado'">
      <div>
        <h1 class="page-title">Gestión de Ausencias</h1>
        <p class="page-subtitle">Vacaciones, permisos y bajas médicas del personal de la Constructora</p>
      </div>
      <div style="display: flex; gap: 0.75rem;">
        <BotonActualizar :cargando="cargando" @actualizar="cargarDatos" />
        <button class="btn btn-primary" @click="abrirModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nueva Solicitud
        </button>
      </div>
    </div>

    <!-- Mobile-like Header for Empleados -->
    <div class="header-mobile" v-else>
      <h1 class="page-title text-center">Mis Ausencias y Permisos</h1>
      <div style="display: flex; gap: 0.75rem;">
        <BotonActualizar :cargando="cargando" @actualizar="cargarDatos" />
        <button class="btn-solicitar" @click="abrirModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Solicitar
        </button>
      </div>
    </div>

    <div class="glass-panel" v-if="authStore.user?.rol !== 'Empleado'">
      <div class="table-toolbar">
        <div class="input-wrapper search-input">
          <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input type="text" class="input-control" placeholder="Buscar empleado..." v-model="busqueda" />
        </div>
        <select class="input-control" style="width:155px" v-model="filtroTipo">
          <option value="">Todos los tipos</option>
          <option value="Vacación">Vacación</option>
          <option value="Permiso">Permiso</option>
          <option value="Baja médica">Baja médica</option>
        </select>
        <select class="input-control" style="width:155px" v-model="filtroEstado">
          <option value="">Todos los estados</option>
          <option value="Pendiente">Pendiente</option>
          <option value="Aprobado">Aprobado</option>
          <option value="Rechazado">Rechazado</option>
        </select>
        <span class="text-muted text-sm">{{ filtrados.length }} registros</span>
      </div>

      <div class="data-table-wrapper">
        <SkeletonLoader v-if="cargando" tipo="tabla" :filas="6" />

        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Empleado</th>
              <th>Tipo</th>
              <th>Fecha Inicio</th>
              <th>Fecha Fin</th>
              <th>Días</th>
              <th>Motivo</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtrados.length===0">
              <td colspan="8" class="empty-row-premium">
                <div class="empty-illustration">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="empty-text-title">Sin solicitudes recientes</div>
                <div class="empty-text-sub">Parece que aún no tienes vacaciones, permisos ni bajas médicas registradas.</div>
              </td>
            </tr>
            <template v-else>
              <tr v-for="rec in elementosPaginados" :key="rec.id_ausencia">
                <td>
                  <div class="emp-cell">
                    <div class="emp-avatar">{{ iniciales(rec.empleado) }}</div>
                    <span class="font-semibold">{{ rec.empleado?.nombre }} {{ rec.empleado?.apellido }}</span>
                  </div>
                </td>
                <td><span class="type-chip" :class="claseTipo(rec.tipo)">{{ rec.tipo || 'Vacación' }}</span></td>
                <td class="text-sm">{{ rec.fecha_inicio }}</td>
                <td class="text-sm">{{ rec.fecha_fin }}</td>
                <td class="text-sm font-semibold">{{ calcularDias(rec.fecha_inicio, rec.fecha_fin) }}</td>
                <td class="text-sm text-secondary-color">{{ rec.motivo || '—' }}</td>
                <td><InsigniaEstado :estado="rec.estado" /></td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon text-success" title="Aprobar" @click="cambiarEstado(rec, 'Aprobado')" v-if="rec.estado==='Pendiente' && authStore.user?.rol !== 'Empleado'">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                    <button class="btn-icon danger" title="Rechazar" @click="cambiarEstado(rec, 'Rechazado')" v-if="rec.estado==='Pendiente' && authStore.user?.rol !== 'Empleado'">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                    <button class="btn-icon" title="Editar" @click="abrirModal(rec)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn-icon danger" title="Eliminar" @click="eliminarRegistro(rec)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <PaginacionBase
        v-if="!cargando && filtrados.length > 10"
        :pagina-actual="paginaActual"
        :total-paginas="totalPaginas"
        :total="filtrados.length"
        :por-pagina="10"
        @cambiar="irAPagina"
      />
    </div>

    <!-- Layout de Tarjetas para Empleado -->
    <div class="mobile-layout" v-if="authStore.user?.rol === 'Empleado'">
      <div v-if="cargando" class="text-center p-4">
        <span class="spinner spinner-dark"></span>
        <p class="mt-2 text-muted">Cargando ausencias...</p>
      </div>
      <div v-else-if="filtrados.length === 0" class="empty-state text-center p-4">
        <div class="empty-illustration">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <p class="empty-text-title">Sin solicitudes recientes</p>
        <p class="empty-text-sub">Parece que aún no tienes vacaciones, permisos ni bajas médicas registradas.</p>
      </div>
      <div v-else class="cards-list">
        <div v-for="rec in elementosPaginados" :key="rec.id_ausencia" class="mobile-card">
          <div class="card-header">
            <h3 class="card-title">{{ rec.tipo || 'Vacación' }}</h3>
            <span class="badge" :class="badgeClase(rec.estado)">{{ rec.estado }}</span>
          </div>
          <div class="card-body">
            <p class="card-desc">{{ rec.motivo || 'Sin motivo' }}</p>
            <p class="card-date">{{ rec.fecha_inicio }} <span v-if="rec.fecha_fin && rec.fecha_fin !== rec.fecha_inicio">al {{ rec.fecha_fin }}</span></p>
          </div>
          <!-- Empleado puede eliminar/editar si está pendiente -->
          <div class="card-footer" v-if="rec.estado === 'Pendiente'">
            <button class="btn-text text-primary" @click="abrirModal(rec)">Editar</button>
            <button class="btn-text text-danger" @click="eliminarRegistro(rec)">Eliminar</button>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal Formulario -->
    <ModalBase :visible="modalVisible" @cerrar="cerrarModal" :titulo="idEdicion ? 'Editar Solicitud' : 'Nueva Solicitud de Ausencia'">
      <div class="form-grid form-grid-2" style="gap:1rem">
        <div class="form-group" style="grid-column:1/-1" v-if="authStore.user?.rol !== 'Empleado'">
          <label class="form-label">Empleado *</label>
          <select v-model="formulario.id_empleado" class="input-control" :class="{'input-error': errors.id_empleado}" required :disabled="idEdicion !== null" @change="errors.id_empleado=''">
            <option value="">Seleccione empleado...</option>
            <option v-for="e in empleados" :key="e.id_empleado" :value="e.id_empleado">{{ e.nombre }} {{ e.apellido }}</option>
          </select>
          <p v-if="errors.id_empleado" class="field-error">{{ errors.id_empleado }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Tipo *</label>
          <select v-model="formulario.tipo" class="input-control" :class="{'input-error': errors.tipo}" @change="errors.tipo=''">
            <option value="Vacación">Vacación</option>
            <option value="Permiso">Permiso</option>
            <option value="Baja médica">Baja médica</option>
          </select>
          <p v-if="errors.tipo" class="field-error">{{ errors.tipo }}</p>
        </div>
        <div class="form-group" v-if="authStore.user?.rol !== 'Empleado'">
          <label class="form-label">Estado</label>
          <select v-model="formulario.estado" class="input-control">
            <option value="Pendiente">Pendiente</option>
            <option value="Aprobado">Aprobado</option>
            <option value="Rechazado">Rechazado</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Fecha Inicio *</label>
          <input type="date" v-model="formulario.fecha_inicio" class="input-control" :class="{'input-error': errors.fecha_inicio}" required @input="errors.fecha_inicio=''" :min="fechaActual" />
          <p v-if="errors.fecha_inicio" class="field-error">{{ errors.fecha_inicio }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Fecha Fin *</label>
          <input type="date" v-model="formulario.fecha_fin" class="input-control" :class="{'input-error': errors.fecha_fin}" required @input="errors.fecha_fin=''" :min="fechaActual" />
          <p v-if="errors.fecha_fin" class="field-error">{{ errors.fecha_fin }}</p>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Motivo</label>
          <textarea v-model="formulario.motivo" class="input-control" :class="{'input-error': errors.motivo}" rows="2" placeholder="Describa el motivo..." @input="errors.motivo=''"></textarea>
          <p v-if="errors.motivo" class="field-error">{{ errors.motivo }}</p>
        </div>
      </div>
      <template #acciones>
        <button class="btn btn-secondary" @click="cerrarModal">Cancelar</button>
        <button class="btn btn-primary" :disabled="guardando" @click="guardar">
          <span v-if="guardando" class="spinner"></span>
          <span v-else>{{ idEdicion ? 'Actualizar' : 'Registrar' }}</span>
        </button>
      </template>
    </ModalBase>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { AusenciaServicio } from '../../servicios/AusenciaServicio'
import { usarConfirmacion } from '../../composables/usarConfirmacion'
import { EmpleadoServicio } from '../../servicios/EmpleadoServicio'
import { usarPaginacion } from '../../composables/usarPaginacion'
import { usarNotificacion } from '../../composables/usarNotificacion'
import { useAuthStore } from '../../store/auth'
import ModalBase      from '../../componentes/ModalBase.vue'
import InsigniaEstado from '../../componentes/InsigniaEstado.vue'
import SkeletonLoader from '../../componentes/SkeletonLoader.vue'
import PaginacionBase from '../../componentes/PaginacionBase.vue'
import BotonActualizar from '../../componentes/BotonActualizar.vue'

const { confirmar } = usarConfirmacion()
const { exito, error: msjError } = usarNotificacion()
const authStore = useAuthStore()

const registros = ref([])
const empleados = ref([])
const cargando = ref(true)
const busqueda = ref('')
const filtroTipo = ref('')
const filtroEstado = ref('')

const modalVisible = ref(false)
const guardando = ref(false)
const idEdicion = ref(null)

const fechaActual = new Date().toISOString().split('T')[0]

const formDefault = () => ({ id_empleado: '', tipo: 'Vacación', estado: 'Pendiente', fecha_inicio: '', fecha_fin: '', motivo: '' })
const formulario = ref(formDefault())
const errors = ref({})

const iniciales = (emp) => emp ? `${emp.nombre?.[0]||''}${emp.apellido?.[0]||''}`.toUpperCase() : '—'
const claseTipo = (t) => ({ 'Vacación': 'type-vacation', 'Permiso': 'type-permit', 'Baja médica': 'type-medical' }[t] || 'type-vacation')

const badgeClase = (estado) => {
  if (estado === 'Aprobado') return 'badge-aprobado'
  if (estado === 'Rechazado') return 'badge-rechazado'
  return 'badge-pendiente'
}

const calcularDias = (inicio, fin) => {
  if (!inicio || !fin) return 0
  const fIni = new Date(inicio)
  const fFin = new Date(fin)
  const diffTime = Math.abs(fFin - fIni)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays + 1
}

const filtrados = computed(() => {
  let l = registros.value
  if (busqueda.value) {
    const q = busqueda.value.toLowerCase()
    l = l.filter(r => `${r.empleado?.nombre} ${r.empleado?.apellido}`.toLowerCase().includes(q))
  }
  return l
})

const { paginaActual, totalPaginas, elementosPaginados, irAPagina } = usarPaginacion(filtrados, 10)

const cargarDatos = async () => {
  cargando.value = true
  try {
    const params = {
      tipo: filtroTipo.value || undefined,
      estado: filtroEstado.value || undefined
    }
    // Siempre cargamos ausencias
    const ausRes = await AusenciaServicio.obtenerTodas(params)
    registros.value = ausRes

    // Intentamos cargar empleados solo si no es Empleado, y capturamos error para que no bloquee
    empleados.value = []
    if (authStore.user?.rol !== 'Empleado') {
      try {
        const empRes = await EmpleadoServicio.obtenerTodos()
        empleados.value = empRes.data || empRes.datos || empRes
      } catch (errEmp) {
        console.warn("No se pudieron cargar empleados:", errEmp)
      }
    }
  } catch (e) {
    msjError('Error al cargar ausencias')
    registros.value = []
  } finally {
    cargando.value = false
  }
}

watch([filtroTipo, filtroEstado], cargarDatos)

const abrirModal = (rec = null) => {
  if (rec) {
    idEdicion.value = rec.id_ausencia
    formulario.value = {
      id_empleado: rec.id_empleado,
      tipo: rec.tipo || 'Vacación',
      estado: rec.estado || 'Pendiente',
      fecha_inicio: rec.fecha_inicio,
      fecha_fin: rec.fecha_fin,
      motivo: rec.motivo || ''
    }
  } else {
    idEdicion.value = null
    formulario.value = formDefault()
    if (authStore.user?.rol === 'Empleado') {
      formulario.value.id_empleado = authStore.user?.id_empleado
    }
  }
  errors.value = {}
  modalVisible.value = true
}

const cerrarModal = () => {
  modalVisible.value = false
  setTimeout(() => { idEdicion.value = null }, 200)
}

const validar = () => {
  errors.value = {}
  if (authStore.user?.rol !== 'Empleado' && !formulario.value.id_empleado) {
    errors.value.id_empleado = 'Seleccione un empleado válido'
  }
  if (!formulario.value.fecha_inicio) errors.value.fecha_inicio = 'La fecha inicio es obligatoria'
  if (!formulario.value.fecha_fin) errors.value.fecha_fin = 'La fecha fin es obligatoria'
  
  const isValid = !errors.value.id_empleado && !errors.value.fecha_inicio && !errors.value.fecha_fin
  if (!isValid) {
    msjError('Por favor, complete todos los campos obligatorios.')
  }
  return isValid
}

const guardar = async () => {
  if (!validar()) return
  
  guardando.value = true
  try {
    if (idEdicion.value) {
      await AusenciaServicio.actualizar(idEdicion.value, formulario.value)
      exito('Solicitud actualizada')
    } else {
      await AusenciaServicio.registrar(formulario.value)
      exito('Solicitud registrada correctamente')
    }
    cerrarModal()
    cargarDatos()
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errores || err.response.data.errors;
      for (const key in serverErrors) {
        errors.value[key] = serverErrors[key][0];
      }
      msjError('Por favor, verifique los errores en el formulario.');
    } else {
      msjError(err.response?.data?.mensaje || err.response?.data?.message || 'Error al guardar la solicitud');
    }
  } finally {
    guardando.value = false
  }
}

const cambiarEstado = async (rec, estado) => {
  try {
    await AusenciaServicio.cambiarEstado(rec.id_ausencia, estado)
    exito(`Solicitud ${estado.toLowerCase()} correctamente`)
    cargarDatos()
  } catch (e) {
    msjError('Error al cambiar el estado')
  }
}

const eliminarRegistro = async (rec) => {
  const confirmado = await confirmar(`¿Eliminar la solicitud de ${rec.empleado?.nombre}?`, {
    titulo: 'Eliminar Solicitud',
    textoConfirmar: 'Eliminar',
    textoCancelar: 'Cancelar'
  })
  if (!confirmado) return
  try {
    await AusenciaServicio.eliminar(rec.id_ausencia)
    exito('Solicitud eliminada')
    cargarDatos()
  } catch (e) {
    msjError('Error al eliminar')
  }
}

onMounted(cargarDatos)
</script>

<style scoped>
.search-input {
  flex: 1;
  min-width: 180px;
  max-width: 260px;
}

.emp-cell {
  display: flex;
  align-items: center;
  gap: 0.625rem;
}

.emp-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: rgba(109, 40, 217, 0.1);
  border: 1px solid rgba(109, 40, 217, 0.18);
  color: var(--primary-600);
  font-size: 0.63rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.type-chip {
  display: inline-block;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 99px;
  border: 1px solid transparent;
}
.type-chip.type-vacation { background: rgba(37, 99, 235, 0.08); color: #2563eb; border-color: rgba(37, 99, 235, 0.15); }
.type-chip.type-permit { background: rgba(217, 119, 6, 0.08); color: #d97706; border-color: rgba(217, 119, 6, 0.15); }
.type-chip.type-medical { background: rgba(225, 29, 72, 0.08); color: #e11d48; border-color: rgba(225, 29, 72, 0.15); }

.action-btns {
  display: flex;
  gap: 0.25rem;
}

.text-success { color: #10b981; }
.text-success:hover { background: rgba(16, 185, 129, 0.1); }
.input-error { border-color: var(--danger-500) !important; }
.input-error:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important; }
.field-error { font-size: 0.7rem; color: var(--danger-600); margin-top: 0.25rem; font-weight: 500; }
.empty-row-premium {
  padding: 4rem 2rem !important;
  text-align: center;
  background: transparent !important;
  border-bottom: none !important;
}
.empty-illustration {
  background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%);
  width: 90px;
  height: 90px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem auto;
  color: #6366f1;
  box-shadow: 0 10px 25px rgba(99, 102, 241, 0.15);
}
.empty-text-title {
  font-size: 1.3rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 0.5rem;
}
.empty-text-sub {
  color: #64748b;
  font-size: 0.95rem;
  max-width: 350px;
  margin: 0 auto;
}
.btn-primary {
  background: #163C57 !important;
  border: none !important;
  color: white !important;
  transition: background 0.2s !important;
}
.btn-primary:hover {
  background: #1a4a6b !important;
}

/* --- ESTILOS MOBILE (EMPLEADO) --- */
.header-mobile {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.btn-solicitar {
  background-color: #163C57;
  color: white;
  border: none;
  border-radius: 0.5rem;
  padding: 0.6rem 1.2rem;
  font-weight: 600;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-solicitar:hover {
  background-color: #1a4a6b;
}

.mobile-layout {
  padding: 0.5rem;
  padding-bottom: 80px;
}

.cards-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.mobile-card {
  background: #ffffff;
  border-radius: 0.5rem;
  padding: 1.25rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.card-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #334155;
  margin: 0;
}

.badge {
  padding: 0.2rem 0.6rem;
  border-radius: 2rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-pendiente {
  background: #fffbeb;
  color: #d97706;
  border: 1px solid #fde68a;
}
.badge-aprobado {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}
.badge-rechazado {
  background: #fef2f2;
  color: #e11d48;
  border: 1px solid #fecaca;
}

.card-body {
  margin-bottom: 1rem;
}

.card-desc {
  font-size: 0.95rem;
  color: #475569;
  margin: 0 0 0.25rem 0;
}

.card-date {
  font-size: 0.85rem;
  color: #94a3b8;
  margin: 0;
}

.card-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  border-top: 1px solid #f1f5f9;
  padding-top: 0.75rem;
}

.btn-text {
  background: transparent;
  border: none;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
}

.text-primary { color: #163C57; }
.text-danger { color: #ef4444; }
</style>
