<template>
  <div class="page-wrapper">

    <div class="page-top">
      <div class="dash-heading">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          {{ fechaActual }}
        </p>
      </div>
      <button class="btn btn-secondary refresh-btn" @click="cargarDatos" :disabled="cargando">
        <span v-if="cargando" class="spinner spinner-dark"></span>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
          <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
          <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
        </svg>
        <span>{{ cargando ? 'Actualizando...' : 'Actualizar' }}</span>
      </button>
    </div>

    <div v-if="cargando" class="loading-state">
      <div class="spinner spinner-dark spinner-lg"></div>
      <p>Cargando métricas del sistema...</p>
    </div>

    <div v-else-if="authStore.user?.rol === 'Empleado'" class="empleado-dashboard">
      <div class="dash-panel welcome-panel">
        <div class="welcome-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <h2>Hola, {{ authStore.user?.usuario || 'Empleado' }}</h2>
        <p>Bienvenido al portal del empleado de Constructora Flores.</p>
        <p class="dash-panel-sub" style="margin-top: 1rem;">
          Desde aquí puedes utilizar el menú lateral para gestionar tus permisos, revisar tus ausencias, y marcar tu asistencia mediante reconocimiento facial.
        </p>
      </div>
    </div>

    <div v-else>
      <div class="stats-grid mb-5">
        <TarjetaMetrica
          titulo="Empleados Activos"
          :valor="datosPanel.empleados?.activos || 0"
          :tendencia="datosPanel.empleados?.nuevos > 0 ? 5 : null"
          color="primario"
          :subtexto="`${datosPanel.empleados?.nuevos || 0} nuevos este mes`"
        >
          <template #icono>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </template>
        </TarjetaMetrica>

        <TarjetaMetrica
          titulo="Asistencia Hoy"
          :valor="`${datosPanel.asistencia_hoy?.presentes || 0} / ${datosPanel.empleados?.activos || 0}`"
          color="exito"
          :subtexto="asistenciaPct + '% de asistencia'"
        >
          <template #icono>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </template>
        </TarjetaMetrica>

        <TarjetaMetrica
          titulo="Ausencias Activas"
          :valor="datosPanel.ausencias?.activas || 0"
          color="advertencia"
          :subtexto="`${datosPanel.ausencias?.pendientes || 0} pendientes de aprobación`"
        >
          <template #icono>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </template>
        </TarjetaMetrica>

        <TarjetaMetrica
          titulo="Total Planillas (Mes)"
          :valor="`Bs. ${formatNum(datosPanel.planillas?.total_a_pagar || 0)}`"
          color="peligro"
          subtexto="Nómina calculada del período"
        >
          <template #icono>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="1" x2="12" y2="23"/>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
          </template>
        </TarjetaMetrica>
      </div>

      <div class="charts-grid mb-5">

        <div class="dash-panel">
          <div class="dash-panel-header">
            <div>
              <h3>Asistencia — Últimos 7 días</h3>
              <p class="dash-panel-sub">Evolución de presencia vs. faltas del personal</p>
            </div>
            <div class="panel-legend">
              <span class="legend-item legend-green">
                <span class="legend-dot"></span>Presentes
              </span>
              <span class="legend-item legend-red">
                <span class="legend-dot"></span>Faltas
              </span>
            </div>
          </div>
          <div class="chart-container">
            <Line v-if="chartDataAsistencia" :data="chartDataAsistencia" :options="chartOptionsLine" />
            <div v-else class="chart-empty">Sin datos de asistencia</div>
          </div>
        </div>

        <div class="dash-panel">
          <div class="dash-panel-header">
            <div>
              <h3>Distribución por Departamento</h3>
              <p class="dash-panel-sub">Personal activo por área</p>
            </div>
          </div>
          <div class="chart-container donut-container">
            <Doughnut v-if="chartDataDept" :data="chartDataDept" :options="chartOptionsDonut" />
            <div v-else class="chart-empty">Sin datos de departamentos</div>
          </div>
        </div>
      </div>

      <div class="secondary-grid">

        <div class="stat-card stat-blue">
          <div class="stat-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
            </svg>
          </div>
          <div class="stat-card-val">{{ datosPanel.empleados?.nuevos || 0 }}</div>
          <div class="stat-card-label">Nuevos empleados<br><small>este mes</small></div>
        </div>

        <div class="stat-card stat-amber">
          <div class="stat-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div class="stat-card-val">{{ datosPanel.asistencia_hoy?.retrasos || 0 }}</div>
          <div class="stat-card-label">Retrasos<br><small>registrados hoy</small></div>
        </div>

        <div class="stat-card stat-red">
          <div class="stat-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <div class="stat-card-val">{{ datosPanel.asistencia_hoy?.faltas || 0 }}</div>
          <div class="stat-card-label">Faltas<br><small>registradas hoy</small></div>
        </div>

        <div class="stat-card stat-turquoise">
          <div class="stat-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
          </div>
          <div class="stat-card-val">{{ datosPanel.ausencias?.pendientes || 0 }}</div>
          <div class="stat-card-label">Solicitudes<br><small>pendientes</small></div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../store/auth'
import { PanelServicio } from '../servicios/PanelServicio'
import TarjetaMetrica from '../componentes/TarjetaMetrica.vue'
import { usarNotificacion } from '../composables/usarNotificacion'

// Chart.js imports
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, PointElement,
  LineElement, Title, Tooltip, Legend, ArcElement, Filler
} from 'chart.js'
import { Line, Doughnut } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, ArcElement, Filler)

const { error: msjError } = usarNotificacion()
const authStore = useAuthStore()

const cargando    = ref(true)
const datosPanel  = ref({})
const fechaActual = new Date().toLocaleDateString('es-ES', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
}).replace(/^\w/, c => c.toUpperCase())

const chartDataAsistencia = ref(null)
const chartDataDept       = ref(null)

// Paleta visual premium
const UI = {
  primary:  '#133c55',
  success:  '#10b981',
  warning:  '#f59e0b',
  danger:   '#ef4444',
  info:     '#0ea5e9',
  indigo:   '#2e6a8d',
  text:     '#64748b',
  grid:     'rgba(226, 232, 240, 0.6)',
  tooltip:  'rgba(15, 23, 42, 0.92)',
}

// Porcentaje de asistencia calculado
const asistenciaPct = computed(() => {
  const presentes = datosPanel.value.asistencia_hoy?.presentes || 0
  const activos   = datosPanel.value.empleados?.activos || 0
  if (!activos) return '0'
  return Math.round((presentes / activos) * 100)
})

const formatNum = (n) => Number(n || 0).toLocaleString('es-BO', { minimumFractionDigits: 2 })

const cargarDatos = async () => {
  cargando.value = true
  try {
    const data = await PanelServicio.obtenerMetricas()
    datosPanel.value = data
    prepararGraficos(data)
  } catch (e) {
    msjError('Error al cargar datos del dashboard')
  } finally {
    cargando.value = false
  }
}

const DIAS_ES = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const prepararGraficos = (data) => {
  // Gráfico de Líneas — Asistencia Semanal (cronológico: más antiguo → más reciente)
  if (data.grafico_asistencia) {
    const historico = [...data.grafico_asistencia].reverse()
    chartDataAsistencia.value = {
      labels: historico.map(d => d.dia),
      datasets: [
        {
          label: 'Presentes',
          data: historico.map(d => d.presentes),
          borderColor: UI.success,
          backgroundColor: `${UI.success}18`,
          borderWidth: 2.5,
          tension: 0.42,
          fill: true,
          pointBackgroundColor: '#fff',
          pointBorderColor: UI.success,
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: UI.success,
        },
        {
          label: 'Faltas',
          data: historico.map(d => d.faltas),
          borderColor: UI.danger,
          backgroundColor: `${UI.danger}0a`,
          borderWidth: 2,
          tension: 0.42,
          fill: false,
          pointBackgroundColor: '#fff',
          pointBorderColor: UI.danger,
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: UI.danger,
          borderDash: [4, 2],
        }
      ]
    }
  }

  // Gráfico de Dona — Departamentos
  if (data.grafico_departamentos) {
    const paleta = [
      UI.primary, UI.info, UI.success, UI.warning,
      UI.danger,  UI.indigo, '#06b6d4', '#14b8a6', '#f43f5e', '#3d85a9'
    ]
    chartDataDept.value = {
      labels: data.grafico_departamentos.map(d => d.departamento),
      datasets: [{
        data: data.grafico_departamentos.map(d => d.cantidad),
        backgroundColor: paleta.map(c => `${c}cc`),
        borderColor: paleta,
        borderWidth: 2,
        hoverOffset: 8,
        hoverBorderWidth: 2,
      }]
    }
  }
}

// Opciones Gráfico de Líneas
const chartOptionsLine = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: UI.tooltip,
      titleFont:  { family: 'Inter', size: 12, weight: '600' },
      bodyFont:   { family: 'Inter', size: 12 },
      padding: 10,
      cornerRadius: 8,
      caretSize: 5,
      displayColors: true,
      boxWidth: 8,
      boxHeight: 8,
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: UI.grid,
        drawBorder: false,
        lineWidth: 1,
      },
      ticks: {
        precision: 0,
        font: { family: 'Inter', size: 11 },
        color: UI.text,
        padding: 6,
      },
      border: { display: false }
    },
    x: {
      grid: { display: false },
      ticks: {
        font: { family: 'Inter', size: 11 },
        color: UI.text,
        padding: 4,
      },
      border: { display: false }
    }
  },
  interaction: { mode: 'nearest', axis: 'x', intersect: false },
  elements: { line: { borderCapStyle: 'round', borderJoinStyle: 'round' } }
}

// Opciones Gráfico de Dona
const chartOptionsDonut = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '70%',
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        usePointStyle: true,
        pointStyle: 'circle',
        padding: 14,
        font: { family: 'Inter', size: 11 },
        color: UI.text,
        boxWidth: 8,
        boxHeight: 8,
      }
    },
    tooltip: {
      backgroundColor: UI.tooltip,
      padding: 10,
      cornerRadius: 8,
      bodyFont: { family: 'Inter', size: 12 },
      titleFont: { family: 'Inter', size: 12, weight: '600' },
    }
  }
}

onMounted(cargarDatos)
</script>

<style scoped>
/* Encabezado */
.dash-heading { display: flex; flex-direction: column; gap: 0.2rem; }
.page-subtitle {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8125rem;
  color: var(--text-muted);
  font-weight: 400;
}
.page-subtitle svg { color: var(--primary-400); }

.refresh-btn { min-width: 130px; }

/* Loading */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 2rem;
  color: var(--text-muted);
  gap: 1rem;
}
.spinner-lg { width: 40px; height: 40px; border-width: 3px; }

/* Grid tarjetas */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
}
.mb-5 { margin-bottom: 1.5rem; }

/* Dashboard Empleado */
.empleado-dashboard {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.welcome-panel {
  text-align: center;
  padding: 3rem 2rem;
  align-items: center;
}
.welcome-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(19, 60, 85, 0.1);
  color: var(--primary-500);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
}
.welcome-panel h2 {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}
.welcome-panel p {
  color: var(--text-secondary);
  font-size: 1rem;
}

/* Gráficos */
.charts-grid {
  display: grid;
  grid-template-columns: 1.85fr 1fr;
  gap: 1.25rem;
}

.dash-panel {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  padding: 1.375rem;
  box-shadow: var(--shadow-card);
  display: flex;
  flex-direction: column;
}

.dash-panel-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1.125rem;
  gap: 0.875rem;
  flex-wrap: wrap;
}
.dash-panel-header h3 {
  font-size: 0.9375rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  letter-spacing: -0.01em;
}
.dash-panel-sub {
  font-size: 0.7rem;
  color: var(--text-muted);
  margin-top: 0.15rem;
}

/* Leyenda manual */
.panel-legend {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  flex-shrink: 0;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--text-secondary);
}
.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
.legend-green .legend-dot { background: #10b981; }
.legend-red   .legend-dot { background: #ef4444; }

.chart-container {
  position: relative;
  height: 280px;
  width: 100%;
}
.donut-container {
  height: 280px;
  display: flex;
  justify-content: center;
}
.chart-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--text-muted);
  font-size: 0.875rem;
}

/* Stats secundarias */
.secondary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
}

.stat-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  padding: 1.375rem 1.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  box-shadow: var(--shadow-card);
  transition: all 0.22s ease;
  position: relative;
  overflow: hidden;
}
.stat-card:hover { border-color: var(--border-strong); }

.stat-card-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.75rem;
}
.stat-card-icon svg { width: 20px; height: 20px; }

.stat-card-val {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.03em;
  margin-bottom: 0.375rem;
}
.stat-card-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 500;
  line-height: 1.4;
}
.stat-card-label small { font-size: 0.7rem; opacity: 0.8; }

/* Variantes de stat cards */
.stat-blue .stat-card-icon { background: rgba(59,130,246,0.1); color: #2563eb; }
.stat-blue .stat-card-val  { color: #1d4ed8; }
.stat-blue { border-top: 3px solid #3b82f6; }

.stat-amber .stat-card-icon { background: rgba(245,158,11,0.1); color: #b45309; }
.stat-amber .stat-card-val  { color: #d97706; }
.stat-amber { border-top: 3px solid #f59e0b; }

.stat-red .stat-card-icon { background: rgba(239,68,68,0.1); color: #dc2626; }
.stat-red .stat-card-val  { color: #dc2626; }
.stat-red { border-top: 3px solid #ef4444; }

.stat-turquoise .stat-card-icon { background: rgba(14, 165, 233, 0.1); color: var(--info-500); }
.stat-turquoise .stat-card-val  { color: var(--info-600); }
.stat-turquoise { border-top: 3px solid var(--info-500); }

/* Responsive */
@media (max-width: 1200px) {
  .stats-grid     { grid-template-columns: repeat(2, 1fr); }
  .secondary-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 900px) {
  .charts-grid { grid-template-columns: 1fr; }
  .chart-container { height: 240px; }
}
@media (max-width: 600px) {
  .stats-grid     { grid-template-columns: 1fr 1fr; }
  .secondary-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 400px) {
  .stats-grid     { grid-template-columns: 1fr; }
  .secondary-grid { grid-template-columns: 1fr; }
}
</style>
