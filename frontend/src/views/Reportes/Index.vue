<template>
  <div class="page-wrapper">
    <div class="page-top">
      <div>
        <h1 class="page-title">Centro de Reportes</h1>
        <p class="page-subtitle">Generación, visualización e impresión de informes institucionales</p>
      </div>
    </div>

    <div class="reports-container no-print">
      <div v-for="cat in categorias" :key="cat.id" class="category-section">
        <h2 class="category-title">{{ cat.title }}</h2>
        <div class="reports-grid">
          <div class="report-card" v-for="r in obtenerReportesPorCategoria(cat.id)" :key="r.id" @click="abrirReporte(r)">
            <div class="report-icon" :class="r.color" v-html="r.icon"></div>
            <div class="report-info">
              <h3>{{ r.title }}</h3>
              <p>{{ r.desc }}</p>
            </div>
            <div class="report-action">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <span>Visualizar</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ModalBase :visible="modalVisible" @cerrar="cerrarModal" :titulo="'Vista Previa de Informe'" class="no-print-modal">

      <div v-if="reporteSeleccionado && !cargando" class="report-filters-bar no-print">
        <h4 class="filters-title">Filtros de Reporte</h4>
        <div class="filters-grid">
          <div class="filter-group" v-if="reporteSeleccionado.filters.includes('departamento')">
            <label class="filter-label">Departamento</label>
            <select v-model="filtros.departamento" class="filter-select" @change="recargarDatosReporte">
              <option value="">[ Todos los Departamentos ]</option>
              <option v-for="dep in departamentos" :key="dep.id_departamento" :value="dep.id_departamento">
                {{ dep.nombre }}
              </option>
            </select>
          </div>
          <div class="filter-group" v-if="reporteSeleccionado.filters.includes('empleado')">
            <label class="filter-label">Empleado</label>
            <select v-model="filtros.empleado" class="filter-select" @change="recargarDatosReporte">
              <option value="">[ Seleccionar Empleado ]</option>
              <option v-for="emp in empleados" :key="emp.id_empleado" :value="emp.id_empleado">
                {{ emp.nombre }} {{ emp.apellido }}
              </option>
            </select>
          </div>
          <div class="filter-group" v-if="reporteSeleccionado.filters.includes('mes')">
            <label class="filter-label">Mes</label>
            <select v-model="filtros.mes" class="filter-select" @change="recargarDatosReporte">
              <option v-for="m in 12" :key="m" :value="m">
                {{ obtenerNombreMes(m) }}
              </option>
            </select>
          </div>
          <div class="filter-group" v-if="reporteSeleccionado.filters.includes('anio')">
            <label class="filter-label">Año</label>
            <select v-model="filtros.anio" class="filter-select" @change="recargarDatosReporte">
              <option v-for="a in añosDisponibles" :key="a" :value="a">
                {{ a }}
              </option>
            </select>
          </div> 
          <div class="filter-group" v-if="reporteSeleccionado.filters.includes('fecha_inicio')">
            <label class="filter-label">Fecha Inicio</label>
            <input type="date" v-model="filtros.fecha_inicio" class="filter-select" @change="recargarDatosReporte" />
          </div>    
          <div class="filter-group" v-if="reporteSeleccionado.filters.includes('fecha_fin')">
            <label class="filter-label">Fecha Fin</label>
            <input type="date" v-model="filtros.fecha_fin" class="filter-select" @change="recargarDatosReporte" />
          </div>
        </div>
      </div>
      <div v-if="cargando" class="loader-container">
        <div class="spinner spinner-dark"></div>
        <span>Compilando y estructurando reporte en tiempo real...</span>
      </div>
      <div v-else class="report-printable-area" id="printable-report">
        <div class="report-header">
          <div class="header-logo-box">
            <img src="/logo_flores.png" alt="Logo Flores Constructora" class="header-logo" />
          </div>
          <div class="header-info-box">
            <h2>FLORES CONSTRUCTORA</h2>
            <p><strong>Departamento:</strong> Gestión de Recursos Humanos</p>
            <p><strong>Ubicación:</strong> Oficina Central - Cochabamba, Bolivia</p>
            <p><strong>Fecha Generación:</strong> {{ fechaActual }}</p>
          </div>
        </div>
        <hr class="report-divider" />
        <div class="report-meta">
          <h1 class="report-title-doc">{{ reporteSeleccionado?.title.toUpperCase() }}</h1>
          <p class="report-desc-doc">{{ reporteSeleccionado?.desc }}</p>
        </div>
        <div class="report-body-data">
          <ReportTableRenderer v-if="reporteSeleccionado" :columns="reporteSeleccionado.columns" :data="datosReporte" />
        </div>
        <div class="report-summary-box" v-if="datosReporte.length > 0">
          <div class="summary-item">
            <span>Total Registros:</span>
            <strong>{{ datosReporte.length }}</strong>
          </div>
        </div>
        <div class="report-signatures">
          <div class="signature-line">
            <hr />
            <p>Generado por Administrador</p>
            <span>Firma de Autoridad RRHH</span>
          </div>
          <div class="signature-line">
            <hr />
            <p>FLORES CONSTRUCTORA</p>
            <span>Sello Oficial</span>
          </div>
        </div>
      </div>

      <template #acciones>
        <button class="btn btn-secondary no-print" @click="cerrarModal">Cerrar</button>
        <button class="btn btn-secondary btn-outline no-print" :disabled="cargando || datosReporte.length === 0" @click="exportarExcel">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13" style="margin-right:4px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Exportar Excel
        </button>
        <button class="btn btn-primary no-print" :disabled="cargando || datosReporte.length === 0" @click="imprimirReporte">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13" style="margin-right:4px;">
            <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
          </svg>
          Imprimir PDF
        </button>
      </template>
    </ModalBase>
  </div>
</template>



<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { usarNotificacion } from '../../composables/usarNotificacion'
import ModalBase from '../../componentes/ModalBase.vue'
import ReportTableRenderer from '../../componentes/reportes/ReportTableRenderer.vue'
import { CATEGORIAS_REPORTES, DEFINICION_REPORTES, ReporteServicio } from '../../servicios/ReporteServicio'
import api from '../../plugins/axios'

const { info, exito, error: msjerr } = usarNotificacion()

const modalVisible = ref(false)
const cargando = ref(false)
const reporteSeleccionado = ref(null)
const datosReporte = ref([])
const departamentos = ref([])
const empleados = ref([])

const categorias = ref(CATEGORIAS_REPORTES)
const reportes = ref(DEFINICION_REPORTES)

const filtros = reactive({
  departamento: '',
  empleado: '',
  mes: new Date().getMonth() + 1,
  anio: new Date().getFullYear(),
  fecha_inicio: '',
  fecha_fin: ''
})

const añosDisponibles = computed(() => {
  const anioActual = new Date().getFullYear()
  return [anioActual - 2, anioActual - 1, anioActual, anioActual + 1]
})

const fechaActual = computed(() => {
  const d = new Date()
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`
})

const obtenerNombreMes = (num) => {
  const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
  return meses[num - 1]
}

const obtenerReportesPorCategoria = (catId) => {
  return reportes.value.filter(r => r.category === catId)
}

const cargarDepartamentos = async () => {
  try {
    const res = await api.get('/departamentos')
    departamentos.value = res.data.datos?.data ?? res.data.datos ?? res.data
  } catch (err) {
    console.error('Error al cargar departamentos:', err)
  }
}

const cargarEmpleados = async () => {
  try {
    const res = await api.get('/empleados', { params: { estado: 'Activo' } })
    empleados.value = res.data.datos?.data ?? res.data.datos ?? res.data
  } catch (err) {
    console.error('Error al cargar empleados:', err)
  }
}

onMounted(() => {
  cargarDepartamentos()
  cargarEmpleados()
})

const abrirReporte = async (r) => {
  reporteSeleccionado.value = r
  modalVisible.value = true

  filtros.departamento = ''
  filtros.empleado = ''
  filtros.mes = new Date().getMonth() + 1
  filtros.anio = new Date().getFullYear()
  filtros.fecha_inicio = ''
  filtros.fecha_fin = ''

  await recargarDatosReporte()
}

const recargarDatosReporte = async () => {
  if (!reporteSeleccionado.value) return
  
  cargando.value = true
  datosReporte.value = []

  try {
    const data = await ReporteServicio.obtenerDatosReporte(reporteSeleccionado.value.id, { ...filtros })
    datosReporte.value = data
    exito(`${reporteSeleccionado.value.title} cargado correctamente`)
  } catch (err) {
    msjerr('Error al obtener los datos para el reporte.')
  } finally {
    cargando.value = false
  }
}

const cerrarModal = () => {
  modalVisible.value = false
  reporteSeleccionado.value = null
  datosReporte.value = []
}

const imprimirReporte = () => {
  const contenido = document.getElementById('printable-report')
  if (!contenido) return

  const html = contenido.innerHTML

  const ventana = window.open('', '_blank', 'width=900,height=700')
  ventana.document.write(`
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8" />
      <title>${reporteSeleccionado.value?.title ?? 'Reporte'}</title>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
      <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #fff; color: #0f172a; padding: 2rem; }
        @page { size: A4 portrait; margin: 15mm 12mm; }

        /* HEADER */
        .report-header { display: flex; align-items: center; gap: 2rem; margin-bottom: 1.25rem; }
        .header-logo { width: 95px; height: 95px; object-fit: contain; }
        .header-info-box h2 { font-size: 1.375rem; font-weight: 800; color: #133c55; letter-spacing: -0.02em; margin-bottom: 0.25rem; }
        .header-info-box p { font-size: 0.75rem; color: #475569; line-height: 1.4; margin: 0; }
        .report-divider { border: 0; border-top: 2px solid #133c55; margin: 1.25rem 0; }

        /* META */
        .report-meta { margin-bottom: 1.25rem; }
        .report-title-doc { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.15rem; }
        .report-desc-doc { font-size: 0.775rem; color: #64748b; }

        /* TABLA */
        .report-table-wrapper { width: 100%; overflow: visible; margin-top: 0.5rem; }
        .report-table { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: auto; }
        .report-table thead { display: table-header-group; }
        .report-table th {
          background: #f1f5f9; border: 1px solid #cbd5e1;
          color: #0f172a; padding: 0.5rem 0.75rem;
          font-size: 0.7rem; font-weight: 700;
          text-transform: uppercase; text-align: left;
        }
        .report-table td {
          border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem;
          font-size: 0.725rem; color: #334155;
          vertical-align: middle; word-break: break-word;
        }
        .report-table tbody tr:nth-child(even) { background: #f8fafc; }
        .report-table tr { page-break-inside: avoid; page-break-after: auto; }
        .empty-state { color: #64748b; font-style: italic; text-align: center; padding: 1rem; }

        /* RESUMEN */
        .report-summary-box {
          display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 1.5rem;
          background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px;
          padding: 1rem 1.5rem; margin: 1.25rem 0 2rem;
        }
        .summary-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; }
        .summary-item strong { font-size: 0.9rem; color: #0f172a; }

        /* FIRMAS */
        .report-signatures { display: flex; justify-content: space-between; margin-top: 4.5rem; gap: 3rem; }
        .signature-line { flex: 1; text-align: center; }
        .signature-line hr { border: 0; border-top: 1.5px solid #94a3b8; width: 70%; margin: 0 auto 0.5rem; }
        .signature-line p { font-size: 0.75rem; font-weight: 700; color: #334155; margin: 0; }
        .signature-line span { font-size: 0.65rem; color: #64748b; display: block; margin-top: 0.1rem; }
      </style>
    </head>
    <body>
      ${html}
    </body>
    </html>
  `)
  ventana.document.close()
  ventana.onload = () => {
    ventana.focus()
    ventana.print()
  }
}

const exportarExcel = async () => {
  if (!reporteSeleccionado.value || datosReporte.value.length === 0) return

  try {
    const cols = reporteSeleccionado.value.columns
    const cabeceras = cols.map(c => c.label)
    
    const datos = datosReporte.value.map(fila => {
      return cols.map(c => {
        let val = fila[c.key]
        if (val === null || val === undefined) val = ''
        return String(val)
      })
    })

    const payload = {
      titulo: reporteSeleccionado.value.title,
      cabeceras: cabeceras,
      datos: datos
    }

    const res = await api.post('/exportar-excel', payload, { responseType: 'blob' })
    
    const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    
    link.setAttribute('href', url)
    link.setAttribute('download', `${reporteSeleccionado.value.title.replace(/\s+/g, '_')}_${fechaActual.value.replace(/\//g, '')}.xlsx`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    exito('Archivo de Excel generado y descargado con éxito')
  } catch (error) {
    msjerr('Hubo un problema al generar el archivo Excel')
    console.error(error)
  }
}
</script>
<style scoped>
.category-section {
  margin-bottom: 2.5rem;
}

.category-title {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--primary-600);
  margin-bottom: 1.25rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid var(--border);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem;
}

.report-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 1.25rem 1.375rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: var(--shadow-sm);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.report-card:hover {
  border-color: var(--primary-400);
}

.report-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s;
}

.report-card:hover .report-icon {
  color: var(--primary-600);
}

.report-icon.violet { background: rgba(124, 58, 237, 0.08); color: #7c3aed; border: 1px solid rgba(124, 58, 237, 0.15); }
.report-icon.green  { background: rgba(16, 185, 129, 0.08);  color: #10b981; border: 1px solid rgba(16, 185, 129, 0.15); }
.report-icon.indigo { background: rgba(19, 60, 85, 0.08);  color: #133c55; border: 1px solid rgba(19, 60, 85, 0.15); } /* Corporativo Petrol */

.report-info { flex: 1; }
.report-info h3 { font-size: 0.9375rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.2rem; }
.report-info p { font-size: 0.75rem; color: var(--text-muted); line-height: 1.4; }

.report-action {
  display: flex; flex-direction: column; align-items: center; gap: 0.2rem;
  color: var(--primary-500); flex-shrink: 0; opacity: 0.7; transition: opacity 0.2s;
}
.report-card:hover .report-action { opacity: 1; }
.report-action span { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; }

.loader-container {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 4rem 1rem; gap: 1rem; font-size: 0.875rem; color: var(--text-muted);
}

/* FILTROS */
.report-filters-bar {
  background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 1.125rem 1.375rem; margin-bottom: 1.25rem;
}
.filters-title { font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.75rem; border-bottom: 1px dashed var(--border); padding-bottom: 0.35rem; }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; }
.filter-group { display: flex; flex-direction: column; gap: 0.35rem; }
.filter-label { font-size: 0.725rem; font-weight: 600; color: var(--text-secondary); }
.filter-select {
  height: 38px; background-color: var(--bg-surface); border: 1px solid var(--border);
  border-radius: var(--radius-md); padding: 0 0.75rem; font-size: 0.8125rem; color: var(--text-primary);
  outline: none; transition: all 0.15s ease; width: 100%;
}
.filter-select:focus { border-color: var(--primary-500); box-shadow: 0 0 0 2px rgba(19, 60, 85, 0.1); }

/* REPORTE IMPRIMIBLE */
.report-printable-area { background: #fff; color: #0f172a; padding: 2rem; font-family: 'Inter', sans-serif; border-radius: var(--radius-md); box-shadow: inset 0 0 0 1px var(--border); }
.report-header { display: flex; align-items: center; gap: 2rem; }
.header-logo-box { flex-shrink: 0; }
.header-logo { width: 95px; height: 95px; object-fit: contain; }
.header-info-box { flex: 1; }
.header-info-box h2 { font-size: 1.375rem; font-weight: 800; color: #133c55; letter-spacing: -0.02em; margin-bottom: 0.25rem; }
.header-info-box p { font-size: 0.75rem; color: #475569; line-height: 1.4; margin: 0; }
.report-divider { border: 0; border-top: 2px solid #133c55; margin: 1.25rem 0; }
.report-meta { margin-bottom: 1.25rem; }
.report-title-doc { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.15rem; }
.report-desc-doc { font-size: 0.775rem; color: #64748b; margin: 0; }
.report-body-data { margin-bottom: 1.75rem; overflow-x: auto; overflow-y: visible; }
.report-summary-box {
  display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 1.5rem;
  background: #f8fafc; border: 1px solid #cbd5e1; border-radius: var(--radius-md);
  padding: 1rem 1.5rem; margin-bottom: 2rem;
}
.summary-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; }
.summary-item strong { font-size: 0.9rem; color: #0f172a; }

.report-signatures { display: flex; justify-content: space-between; margin-top: 4.5rem; gap: 3rem; }
.signature-line { flex: 1; text-align: center; }
.signature-line hr { border: 0; border-top: 1.5px solid #94a3b8; width: 70%; margin: 0 auto 0.5rem; }
.signature-line p { font-size: 0.75rem; font-weight: 700; color: #334155; margin: 0; }
.signature-line span { font-size: 0.65rem; color: #64748b; display: block; margin-top: 0.1rem; }
</style>


