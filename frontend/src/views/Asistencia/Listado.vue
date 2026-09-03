<template>
  <div>
    <div class="glass-panel">
      <div class="table-toolbar">
        <div class="input-wrapper search-input">
          <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input type="text" class="input-control" placeholder="Buscar empleado..." v-model="busqueda" />
        </div>
        <input type="date" class="input-control" style="width:160px" v-model="filtroFecha" />
        <select class="input-control" style="width:155px" v-model="filtroEstado">
          <option value="">Todos los estados</option>
          <option value="Presente">Presente</option>
          <option value="Retraso">Retraso</option>
          <option value="Falta">Falta</option>
          <option value="Permiso">Permiso</option>
          <option value="Vacación">Vacación</option>
        </select>
        <div style="display: flex; gap: 0.75rem; margin-left: auto;">
          <BotonActualizar :cargando="cargando" @actualizar="cargarDatos(paginaActual)" />
          <button class="btn btn-primary" @click="abrirModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Añadir Registro
          </button>
        </div>
      </div>

      <div class="data-table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Empleado</th>
              <th>Fecha</th>
              <th>Entrada</th>
              <th>Salida</th>
              <th>Hrs. Trabajadas</th>
              <th>Hrs. Extras</th>
              <th>Método</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="cargando"><td colspan="8" class="empty-row"><div class="spinner spinner-dark"></div><span>Cargando...</span></td></tr>
            <tr v-else-if="registros.length === 0">
              <td colspan="8" class="empty-row">
                <span class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="34" height="34"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                <span>No hay registros de asistencia</span>
              </td>
            </tr>
            <template v-else>
              <tr v-for="rec in registros" :key="rec.id_asistencia">
                <td>
                  <div class="emp-cell">
                    <div class="emp-avatar">{{ iniciales(rec.empleado) }}</div>
                    <span class="font-semibold">{{ rec.empleado?.nombre }} {{ rec.empleado?.apellido }}</span>
                  </div>
                </td>
                <td class="text-sm">{{ rec.fecha }}</td>
                <td class="text-sm">{{ rec.hora_entrada ? rec.hora_entrada.slice(0,5) : '—' }}</td>
                <td class="text-sm">{{ rec.hora_salida ? rec.hora_salida.slice(0,5) : '—' }}</td>
                <td class="text-sm font-semibold">{{ rec.horas_trabajadas !== null ? rec.horas_trabajadas + ' hrs' : '—' }}</td>
                <td class="text-sm font-semibold" style="color: var(--primary-600)">{{ formatearHorasExtras(rec.horas_extras) }}</td>
                <td class="text-sm">{{ rec.estado === 'Falta' && !rec.metodo_registro ? '—' : (rec.metodo_registro || 'Administrador') }}</td>
                <td><InsigniaEstado :estado="rec.estado" /></td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon" @click="rec.id_asistencia ? abrirModal(rec) : null" :title="rec.id_asistencia ? 'Gestionar Horas Extras' : 'No disponible para faltas virtuales'" :disabled="!rec.id_asistencia" :class="{'opacity-50 cursor-not-allowed': !rec.id_asistencia}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                    <button class="btn-icon danger" @click="eliminarRegistro(rec)" title="Eliminar" v-if="rec.id_asistencia"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg></button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <!-- Paginacion -->
      <PaginacionBase
        v-if="!cargando && totalPaginas > 1"
        :pagina-actual="paginaActual"
        :total-paginas="totalPaginas"
        :total="totalElementos"
        :por-pagina="porPagina"
        @cambiar="handleCambioPagina"
      />
    </div>

    <!-- Modal Detalle Asistencia / Nuevo Registro -->
    <ModalBase :visible="modalVisible" @cerrar="cerrarModal" :titulo="idEdicion ? 'Detalle de Asistencia' : 'Nueva Asistencia'">
      <!-- Formulario para Nuevo Registro -->
      <div v-if="!idEdicion" class="form-grid form-grid-2" style="gap:1rem">
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Empleado *</label>
          <select v-model="formulario.id_empleado" class="input-control" :class="{'input-error': errors.id_empleado}" required @change="errors.id_empleado=''">
            <option value="">Seleccione empleado...</option>
            <option v-for="e in empleados" :key="e.id_empleado" :value="e.id_empleado">{{ e.nombre }} {{ e.apellido }}</option>
          </select>
          <p v-if="errors.id_empleado" class="field-error">{{ errors.id_empleado }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Fecha *</label>
          <input type="date" v-model="formulario.fecha" class="input-control" :class="{'input-error': errors.fecha}" required @input="errors.fecha=''" :min="fechaActual" :max="fechaActual" />
          <p v-if="errors.fecha" class="field-error">{{ errors.fecha }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Estado *</label>
          <select v-model="formulario.estado" class="input-control" :class="{'input-error': errors.estado}" required @change="errors.estado=''">
            <option value="Presente">Presente</option>
            <option value="Retraso">Retraso</option>
            <option value="Falta">Falta</option>
            <option value="Permiso">Permiso</option>
            <option value="Vacación">Vacación</option>
          </select>
          <p v-if="errors.estado" class="field-error">{{ errors.estado }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Hora Entrada</label>
          <input type="time" step="1" v-model="formulario.hora_entrada" class="input-control" :class="{'input-error': errors.hora_entrada}" @input="errors.hora_entrada=''" />
          <p v-if="errors.hora_entrada" class="field-error">{{ errors.hora_entrada }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Hora Salida</label>
          <input type="time" step="1" v-model="formulario.hora_salida" class="input-control" :class="{'input-error': errors.hora_salida}" @input="errors.hora_salida=''" />
          <p v-if="errors.hora_salida" class="field-error">{{ errors.hora_salida }}</p>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Método Registro</label>
          <select v-model="formulario.metodo_registro" class="input-control">
            <option value="Administrador">Por Administrador</option>
            <option value="Facial">Reconocimiento Facial</option>
          </select>
        </div>
      </div>

      <!-- Detalle de Asistencia para Editar -->
      <div v-else-if="registroSeleccionado" class="detail-container">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Empleado:</span>
            <span class="detail-value font-semibold">{{ registroSeleccionado.empleado?.nombre }} {{ registroSeleccionado.empleado?.apellido }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Fecha:</span>
            <span class="detail-value">{{ registroSeleccionado.fecha }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Estado:</span>
            <span class="detail-value"><InsigniaEstado :estado="registroSeleccionado.estado" /></span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Entrada:</span>
            <span class="detail-value">{{ registroSeleccionado.hora_entrada ? registroSeleccionado.hora_entrada.slice(0,5) : '—' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Salida:</span>
            <span class="detail-value">{{ registroSeleccionado.hora_salida ? registroSeleccionado.hora_salida.slice(0,5) : '—' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Horas trabajadas:</span>
            <span class="detail-value">{{ registroSeleccionado.horas_trabajadas !== null ? registroSeleccionado.horas_trabajadas + ' hrs' : '—' }}</span>
          </div>
          <div class="detail-item" style="grid-column: 1 / -1;">
            <span class="detail-label">Método:</span>
            <span class="detail-value">{{ registroSeleccionado.estado === 'Falta' && !registroSeleccionado.metodo_registro ? '—' : (registroSeleccionado.metodo_registro || 'Administrador') }}</span>
          </div>
        </div>

        <hr class="detail-divider" />

        <div class="form-group">
          <label class="form-label">Horas extras (HH:MM)</label>
          <input type="time" v-model="formulario.horas_extras" class="input-control" :class="{'input-error': errors.horas_extras}" @input="errors.horas_extras=''" />
          <p v-if="errors.horas_extras" class="field-error">{{ errors.horas_extras }}</p>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
          <label class="form-label">Observación</label>
          <textarea v-model="formulario.observacion" class="input-control" rows="2" placeholder="Opcional. Justificación de las horas extras..."></textarea>
        </div>
      </div>
      <template #acciones>
        <button class="btn btn-secondary" @click="cerrarModal">Cancelar</button>
        <button class="btn btn-primary" :disabled="guardando" @click="guardar">
          <span v-if="guardando" class="spinner"></span>
          <span v-else>Guardar</span>
        </button>
      </template>
    </ModalBase>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { AsistenciaServicio } from '../../servicios/AsistenciaServicio'
import { EmpleadoServicio } from '../../servicios/EmpleadoServicio'
import { usarNotificacion } from '../../composables/usarNotificacion'
import TarjetaMetrica from '../../componentes/TarjetaMetrica.vue'
import ModalBase from '../../componentes/ModalBase.vue'
import InsigniaEstado from '../../componentes/InsigniaEstado.vue'
import { usarConfirmacion } from '../../composables/usarConfirmacion'
import PaginacionBase from '../../componentes/PaginacionBase.vue'
import BotonActualizar from '../../componentes/BotonActualizar.vue'

const { exito, error: msjError } = usarNotificacion()
const { confirmar } = usarConfirmacion()

const registros = ref([])
const empleados = ref([])
const cargando = ref(true)
const busqueda = ref('')
const fechaActual = new Date().toISOString().split('T')[0]
const filtroFecha = ref(fechaActual)
const filtroEstado = ref('')

const modalVisible = ref(false)
const guardando = ref(false)
const idEdicion = ref(null)
const registroSeleccionado = ref(null)

const decimalAHm = (decimal) => {
  if (!decimal) return '00:00'
  const d = parseFloat(decimal)
  if (isNaN(d)) return '00:00'
  const horas = Math.floor(d)
  const minutos = Math.round((d - horas) * 60)
  return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`
}

const hmADecimal = (hm) => {
  if (!hm) return 0.00
  const partes = hm.split(':')
  if (partes.length !== 2) return 0.00
  const horas = parseInt(partes[0], 10)
  const minutos = parseInt(partes[1], 10)
  if (isNaN(horas) || isNaN(minutos)) return 0.00
  return parseFloat((horas + (minutos / 60)).toFixed(2))
}

const formatearHorasExtras = (decimal) => {
  return decimal && decimal > 0 ? decimalAHm(decimal) : '00:00'
}

const formularioPorDefecto = () => ({
  id_empleado: '',
  fecha: new Date().toISOString().split('T')[0],
  hora_entrada: '',
  hora_salida: '',
  estado: 'Presente',
  metodo_registro: 'Administrador',
  horas_extras: '00:00',
  observacion: ''
})
const formulario = ref(formularioPorDefecto())
const errors = ref({})


// Paginación desde servidor
const paginaActual = ref(1)
const totalPaginas = ref(1)
const totalElementos = ref(0)
const porPagina = ref(10)

const handleCambioPagina = (p) => {
  cargarDatos(p)
}

const iniciales = (emp) => {
  if (!emp) return '—'
  return `${emp.nombre?.[0]||''}${emp.apellido?.[0]||''}`.toUpperCase()
}

const cargarDatos = async (p = 1) => {
  cargando.value = true
  try {
    const [asisRes, empRes] = await Promise.all([
      AsistenciaServicio.obtenerTodas({
        page: p,
        per_page: porPagina.value,
        fecha: filtroFecha.value,
        estado: filtroEstado.value || undefined,
        busqueda: busqueda.value || undefined,
      }),
      EmpleadoServicio.obtenerTodos()
    ])
    
    // asisRes.datos contiene data paginada ahora
    const datosPagina = asisRes.datos || asisRes
    registros.value = datosPagina.data || []
    paginaActual.value = datosPagina.current_page || 1
    totalPaginas.value = datosPagina.last_page || 1
    totalElementos.value = datosPagina.total || 0
    
    empleados.value = empRes.data || empRes.datos || empRes
    

  } catch (e) {
    msjError('Error al cargar datos de asistencia')
    registros.value = []
  } finally {
    cargando.value = false
  }
}

watch([filtroFecha, filtroEstado], () => cargarDatos(1))
let debounceTimer = null
watch(busqueda, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    cargarDatos(1)
  }, 300)
})

const abrirModal = (rec = null) => {
  if (rec) {
    idEdicion.value = rec.id_asistencia
    registroSeleccionado.value = rec
    formulario.value = {
      horas_extras: decimalAHm(rec.horas_extras),
      observacion: rec.observacion || ''
    }
  } else {
    idEdicion.value = null
    registroSeleccionado.value = null
    formulario.value = formularioPorDefecto()
  }
  errors.value = {}
  modalVisible.value = true
}

const cerrarModal = () => {
  modalVisible.value = false
  setTimeout(() => { 
    idEdicion.value = null 
    registroSeleccionado.value = null
  }, 200)
}

const validar = () => {
  errors.value = {}
  
  if (!idEdicion.value) {
    if (!formulario.value.id_empleado) errors.value.id_empleado = 'Seleccione un empleado válido'
    if (!formulario.value.fecha) errors.value.fecha = 'La fecha es obligatoria'
    if (!formulario.value.estado) errors.value.estado = 'El estado es obligatorio'
  }

  if (formulario.value.horas_extras) {
    const dec = hmADecimal(formulario.value.horas_extras)
    if (dec < 0) errors.value.horas_extras = 'No puede ser negativo'
  }
  
  const isValid = Object.keys(errors.value).length === 0
  if (!isValid && !idEdicion.value) {
    msjError('Por favor, complete todos los campos obligatorios.')
  }
  return isValid
}

const guardar = async () => {
  if (!validar()) return
  
  guardando.value = true
  try {
    if (idEdicion.value) {
      const datos = {
        horas_extras: hmADecimal(formulario.value.horas_extras),
        observacion: formulario.value.observacion
      }
      await AsistenciaServicio.actualizarHorasExtras(idEdicion.value, datos)
      exito('Horas extras registradas correctamente')
    } else {
      const datos = { ...formulario.value }
      if (!datos.hora_entrada) datos.hora_entrada = null
      if (!datos.hora_salida) datos.hora_salida = null
      delete datos.horas_extras
      delete datos.observacion
      await AsistenciaServicio.registrar(datos)
      exito('Asistencia registrada correctamente')
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
      msjError(err.response?.data?.mensaje || err.response?.data?.message || 'Error al guardar la asistencia');
    }
  } finally {
    guardando.value = false
  }
}

const eliminarRegistro = async (rec) => {
  const confirmado = await confirmar(`¿Eliminar registro de asistencia de ${rec.empleado?.nombre}?`, {
    titulo: 'Eliminar Registro',
    textoConfirmar: 'Eliminar',
    textoCancelar: 'Cancelar'
  })
  
  if (!confirmado) return

  try {
    await AsistenciaServicio.eliminar(rec.id_asistencia)
    exito('Registro eliminado')
    cargarDatos()
  } catch (e) {
    msjError('Error al eliminar registro')
  }
}

onMounted(cargarDatos)
</script>

<style scoped>
.summary-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}

.table-toolbar {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 180px;
  max-width: 280px;
}

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

.empty-icon {
  color: var(--border-strong);
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

.action-btns {
  display: flex;
  gap: 0.25rem;
}

.table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1.5rem;
  border-top: 1px solid var(--border);
  gap: 0.875rem;
  flex-wrap: wrap;
}

.pagination {
  display: flex;
  gap: 0.5rem;
}

@media (max-width: 800px) {
  .summary-row { grid-template-columns: repeat(2, 1fr); }
}
.input-error { border-color: var(--danger-500) !important; }
.input-error:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important; }
.field-error { font-size: 0.7rem; color: var(--danger-600); margin-top: 0.25rem; font-weight: 500; }
</style>

<style scoped>
.detail-container {
  padding: 0.5rem;
}
.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}
.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.detail-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.detail-value {
  font-size: 0.95rem;
  color: var(--text-main);
}
.detail-divider {
  border: 0;
  border-top: 1px solid var(--border);
  margin: 1.5rem 0;
}
</style>
