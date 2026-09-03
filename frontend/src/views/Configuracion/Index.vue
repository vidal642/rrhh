<template>
  <div class="page-wrapper">
    <div class="page-top">
      <div>
        <h1 class="page-title">Configuración</h1>
        <p class="page-subtitle">Ajustes generales del sistema de Recursos Humanos</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary" @click="resetForm" :disabled="saving">
          Restablecer Cambios
        </button>
        <button class="btn btn-primary" @click="confirmarGuardado" :disabled="saving">
          <span v-if="saving" class="spinner"></span>
          <template v-else>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
              stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Guardar cambios
          </template>
        </button>
      </div>
    </div>

    <div class="config-container">
      <!-- Sidebar de navegación -->
      <div class="config-sidebar glass-panel">
        <nav class="config-tabs">
          <button
            v-for="tab in tabs" :key="tab.id"
            class="config-tab-btn"
            :class="{ active: activeTab === tab.id }"
            @click="activeTab = tab.id"
          >
            <span class="tab-icon" :class="tab.colorClass" v-html="tab.icon"></span>
            <div class="tab-text">
              <span class="tab-title">{{ tab.title }}</span>
              <span class="tab-subtitle">{{ tab.subtitle }}</span>
            </div>
          </button>
        </nav>
      </div>

      <!-- Contenido de las pestañas -->
      <div class="config-content glass-panel">
        <div v-if="loading" class="loading-state">
          <div class="spinner spinner-dark" style="width:2rem; height:2rem;"></div>
          <p>Cargando configuración...</p>
        </div>

        <div v-else class="tab-pane animate-fade-in">

          <!-- ═══════════════════════════════════════════════════
               TAB: ASISTENCIA
          ════════════════════════════════════════════════════ -->
          <div v-show="activeTab === 'asistencia'">
            <div class="config-section-header">
              <h3>Configuración de Asistencia</h3>
              <p>Horarios, tolerancias, geolocalización y reglas para el control de entrada/salida.</p>
            </div>

            <!-- Bloque: Horarios -->
            <div class="config-block">
              <div class="block-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Horarios laborales
              </div>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Hora de entrada estándar <span class="required">*</span></label>
                  <input type="time" v-model="settings.hora_entrada" class="input-control"
                    :class="{'input-error': errors.hora_entrada}"
                    @change="errors.hora_entrada = ''" />
                  <p v-if="errors.hora_entrada" class="field-error">{{ errors.hora_entrada }}</p>
                </div>
                <div class="form-group">
                  <label class="form-label">Hora de salida estándar <span class="required">*</span></label>
                  <input type="time" v-model="settings.hora_salida" class="input-control"
                    :class="{'input-error': errors.hora_salida}"
                    @change="errors.hora_salida = ''" />
                  <p v-if="errors.hora_salida" class="field-error">{{ errors.hora_salida }}</p>
                </div>
                <div class="form-group">
                  <label class="form-label">Minutos de tolerancia <span class="required">*</span></label>
                  <input type="number" v-model.number="settings.tolerancia_minutos" class="input-control"
                    min="0" :class="{'input-error': errors.tolerancia_minutos}"
                    @input="errors.tolerancia_minutos = ''" />
                  <p v-if="errors.tolerancia_minutos" class="field-error">{{ errors.tolerancia_minutos }}</p>
                  <span class="form-hint">Margen permitido antes de marcar retraso.</span>
                </div>
                <div class="form-group">
                  <label class="form-label">Días laborales <span class="required">*</span></label>
                  <select v-model="settings.dias_laborales" class="input-control"
                    :class="{'input-error': errors.dias_laborales}"
                    @change="errors.dias_laborales = ''">
                    <option value="Lunes a Viernes">Lunes a Viernes</option>
                    <option value="Lunes a Sábado">Lunes a Sábado</option>
                  </select>
                  <p v-if="errors.dias_laborales" class="field-error">{{ errors.dias_laborales }}</p>
                </div>
              </div>
            </div>

            <!-- Bloque: Geolocalización -->
            <div class="config-block">
              <div class="block-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Geolocalización para asistencia
              </div>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Latitud de la empresa</label>
                  <input type="number" v-model.number="settings.empresa_lat" class="input-control"
                    step="0.000001" placeholder="-17.394291"
                    :class="{'input-error': errors.empresa_lat}"
                    @input="errors.empresa_lat = ''" />
                  <p v-if="errors.empresa_lat" class="field-error">{{ errors.empresa_lat }}</p>
                  <span class="form-hint">Entre -90 y 90. Ej: <code>-17.394291</code></span>
                </div>
                <div class="form-group">
                  <label class="form-label">Longitud de la empresa</label>
                  <input type="number" v-model.number="settings.empresa_lon" class="input-control"
                    step="0.000001" placeholder="-66.074135"
                    :class="{'input-error': errors.empresa_lon}"
                    @input="errors.empresa_lon = ''" />
                  <p v-if="errors.empresa_lon" class="field-error">{{ errors.empresa_lon }}</p>
                  <span class="form-hint">Entre -180 y 180. Ej: <code>-66.074135</code></span>
                </div>
                <div class="form-group radio-field">
                  <label class="form-label">Radio permitido <span class="required">*</span></label>
                  <div class="input-suffix-wrap">
                    <input type="number" v-model.number="settings.radio_asistencia" class="input-control"
                      min="1" :class="{'input-error': errors.radio_asistencia}"
                      @input="errors.radio_asistencia = ''" />
                    <span class="input-suffix">metros</span>
                  </div>
                  <p v-if="errors.radio_asistencia" class="field-error">{{ errors.radio_asistencia }}</p>
                  <span class="form-hint">Distancia máxima desde la empresa para registrar asistencia.</span>
                </div>
              </div>

              <div class="form-group switch-group" style="margin-top:0.5rem;">
                <label class="switch-container">
                  <input type="checkbox" v-model="settings.validacion_ubicacion" />
                  <span class="switch-slider"></span>
                  <div class="switch-text">
                    <span class="switch-title">Activar validación de ubicación GPS</span>
                    <span class="form-hint">Si está desactivado, se ignoran la latitud, longitud y radio al registrar asistencia.</span>
                  </div>
                </label>
              </div>
            </div>

            <!-- Bloque: Controles y notificaciones -->
            <div class="config-block">
              <div class="block-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                Controles de asistencia
              </div>
              <div class="switches-grid">
                <div class="form-group switch-group">
                  <label class="switch-container">
                    <input type="checkbox" v-model="settings.reconocimiento_facial" />
                    <span class="switch-slider"></span>
                    <div class="switch-text">
                      <span class="switch-title">Activar reconocimiento facial</span>
                      <span class="form-hint">Permite registrar entrada/salida usando la cámara.</span>
                    </div>
                  </label>
                </div>
                <div class="form-group switch-group">
                  <label class="switch-container">
                    <input type="checkbox" v-model="settings.descuentos_retrasos" />
                    <span class="switch-slider"></span>
                    <div class="switch-text">
                      <span class="switch-title">Descuentos automáticos por retrasos</span>
                      <span class="form-hint">Aplica descuento proporcional por llegadas tarde.</span>
                    </div>
                  </label>
                </div>
                <div class="form-group switch-group">
                  <label class="switch-container">
                    <input type="checkbox" v-model="settings.descuentos_faltas" />
                    <span class="switch-slider"></span>
                    <div class="switch-text">
                      <span class="switch-title">Descuentos automáticos por faltas</span>
                      <span class="form-hint">Descuenta el día no laborado si no hay registro.</span>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════
               TAB: PLANILLAS
          ════════════════════════════════════════════════════ -->
          <div v-show="activeTab === 'planillas'">
            <div class="config-section-header">
              <h3>Configuración de Planillas</h3>
              <p>Ajustes salariales, períodos de corte y generación de pagos mensuales.</p>
            </div>

            <div class="config-block">
              <div class="block-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Parámetros económicos
              </div>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Moneda <span class="required">*</span></label>
                  <select v-model="settings.moneda" class="input-control"
                    :class="{'input-error': errors.moneda}"
                    @change="errors.moneda = ''">
                    <option value="BOB">Bolivianos (Bs.)</option>
                    <option value="USD">Dólares (USD)</option>
                  </select>
                  <p v-if="errors.moneda" class="field-error">{{ errors.moneda }}</p>
                </div>
                <div class="form-group">
                  <label class="form-label">Salario mínimo <span class="required">*</span></label>
                  <input type="number" v-model.number="settings.salario_minimo" class="input-control"
                    min="0" step="0.01" :class="{'input-error': errors.salario_minimo}"
                    @input="errors.salario_minimo = ''" />
                  <p v-if="errors.salario_minimo" class="field-error">{{ errors.salario_minimo }}</p>
                  <span class="form-hint">Sueldo mínimo nacional de referencia.</span>
                </div>
                <div class="form-group">
                  <label class="form-label">Día de corte <span class="required">*</span></label>
                  <div class="input-suffix-wrap">
                    <input type="number" v-model.number="settings.dia_corte" class="input-control"
                      min="1" max="31" :class="{'input-error': errors.dia_corte}"
                      @input="errors.dia_corte = ''" />
                    <span class="input-suffix">del mes</span>
                  </div>
                  <p v-if="errors.dia_corte" class="field-error">{{ errors.dia_corte }}</p>
                  <span class="form-hint">Día en que se cierra el registro de asistencia mensual.</span>
                </div>
              </div>
            </div>

            <div class="config-block">
              <div class="block-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                Automatizaciones
              </div>
              <div class="switches-grid">
                <div class="form-group switch-group">
                  <label class="switch-container">
                    <input type="checkbox" v-model="settings.calculo_automatico_salarios" />
                    <span class="switch-slider"></span>
                    <div class="switch-text">
                      <span class="switch-title">Cálculo automático de salarios</span>
                      <span class="form-hint">Calcula montos automáticamente en base a días trabajados.</span>
                    </div>
                  </label>
                </div>
                <div class="form-group switch-group">
                  <label class="switch-container">
                    <input type="checkbox" v-model="settings.calculo_automatico_horas" />
                    <span class="switch-slider"></span>
                    <div class="switch-text">
                      <span class="switch-title">Cálculo automático de horas trabajadas</span>
                      <span class="form-hint">Obtiene las horas directamente del control de asistencia.</span>
                    </div>
                  </label>
                </div>
                <div class="form-group switch-group">
                  <label class="switch-container">
                    <input type="checkbox" v-model="settings.aplicacion_automatica_adelantos" />
                    <span class="switch-slider"></span>
                    <div class="switch-text">
                      <span class="switch-title">Aplicación automática de adelantos</span>
                      <span class="form-hint">Descuenta los adelantos aprobados en la planilla del mes.</span>
                    </div>
                  </label>
                </div>
                <div class="form-group switch-group">
                  <label class="switch-container">
                    <input type="checkbox" v-model="settings.aplicacion_automatica_descuentos" />
                    <span class="switch-slider"></span>
                    <div class="switch-text">
                      <span class="switch-title">Aplicación automática de descuentos</span>
                      <span class="form-hint">Aplica los descuentos por faltas registrados en la planilla.</span>
                    </div>
                  </label>
                </div>
                <div class="form-group switch-group">
                  <label class="switch-container">
                    <input type="checkbox" v-model="settings.generacion_automatica_planillas" />
                    <span class="switch-slider"></span>
                    <div class="switch-text">
                      <span class="switch-title">Generación automática de planillas</span>
                      <span class="form-hint">Genera la nómina el día de corte sin requerir acción manual.</span>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════
               TAB: SISTEMA
          ════════════════════════════════════════════════════ -->
          <div v-show="activeTab === 'sistema'">
            <div class="config-section-header">
              <h3>Ajustes del Sistema</h3>
              <p>Preferencias visuales, zona horaria y notificaciones del sistema.</p>
            </div>

            <div class="config-block">
              <div class="block-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Preferencias generales
              </div>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Zona horaria <span class="required">*</span></label>
                  <select v-model="settings.zona_horaria" class="input-control"
                    :class="{'input-error': errors.zona_horaria}"
                    @change="errors.zona_horaria = ''">
                    <option value="America/La_Paz">Bolivia (UTC-4)</option>
                    <option value="America/Lima">Perú (UTC-5)</option>
                    <option value="America/Santiago">Chile (UTC-3/-4)</option>
                    <option value="America/Bogota">Colombia (UTC-5)</option>
                    <option value="America/Buenos_Aires">Argentina (UTC-3)</option>
                  </select>
                  <p v-if="errors.zona_horaria" class="field-error">{{ errors.zona_horaria }}</p>
                </div>
                <div class="form-group">
                  <label class="form-label">Tema visual <span class="required">*</span></label>
                  <select v-model="settings.tema_visual" class="input-control"
                    :class="{'input-error': errors.tema_visual}"
                    @change="errors.tema_visual = ''">
                    <option value="claro">Claro</option>
                    <option value="oscuro">Oscuro</option>
                  </select>
                  <p v-if="errors.tema_visual" class="field-error">{{ errors.tema_visual }}</p>
                </div>
              </div>
            </div>

            <div class="config-block">
              <div class="block-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                Control y seguridad
              </div>
              <div class="switches-grid">
                <div class="form-group switch-group">
                  <label class="switch-container">
                    <input type="checkbox" v-model="settings.auditoria" />
                    <span class="switch-slider"></span>
                    <div class="switch-text">
                      <span class="switch-title">Registro de auditoría</span>
                      <span class="form-hint">Guarda un log de quién hace cambios en el sistema.</span>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../plugins/axios'
import { usarNotificacion } from '../../composables/usarNotificacion'
import { usarConfirmacion } from '../../composables/usarConfirmacion'

const { exito, error: msjerr } = usarNotificacion()
const { confirmar } = usarConfirmacion()

const loading = ref(true)
const saving  = ref(false)
const activeTab = ref('asistencia')   // Tab inicial: Asistencia (no Empresa)
const errors = ref({})

// ── Pestañas (sin Empresa) ──────────────────────────────────────────────────
const tabs = [
  {
    id: 'asistencia',
    title: 'Asistencia',
    subtitle: 'Horarios y geolocalización',
    colorClass: 'blue',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'
  },
  {
    id: 'planillas',
    title: 'Planillas',
    subtitle: 'Cálculos y salarios',
    colorClass: 'green',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>'
  },
  {
    id: 'sistema',
    title: 'Sistema',
    subtitle: 'Preferencias generales',
    colorClass: 'amber',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'
  }
]

// ── Estado del formulario (claves = claves exactas de la BD) ────────────────
const defaultSettings = {
  // Asistencia
  hora_entrada:                    '08:00',
  hora_salida:                     '17:00',
  tolerancia_minutos:              0,
  dias_laborales:                  'Lunes a Sábado',
  reconocimiento_facial:           false,
  descuentos_retrasos:             false,
  descuentos_faltas:               false,
  empresa_lat:                     null,
  empresa_lon:                     null,
  radio_asistencia:                500,
  validacion_ubicacion:            true,
  // Planillas
  moneda:                          'BOB',
  salario_minimo:                  2500,
  dia_corte:                       25,
  calculo_automatico_salarios:     true,
  calculo_automatico_horas:        true,
  aplicacion_automatica_adelantos: true,
  aplicacion_automatica_descuentos:true,
  generacion_automatica_planillas: false,
  // Sistema
  zona_horaria:                    'America/La_Paz',
  tema_visual:                     'claro',
  auditoria:                       true,
}

const originalSettings = ref({})
const settings = ref({ ...defaultSettings })

// ── Carga de configuración ──────────────────────────────────────────────────
const fetchSettings = async () => {
  loading.value = true
  try {
    const res = await api.get('/configuracion')
    const datos = res.data.datos || res.data || {}

    // Mezclar datos del servidor sobre los defaults
    const merged = { ...defaultSettings }
    for (const [k, v] of Object.entries(datos)) {
      if (k in merged) {
        // Castear booleanos que llegan como true/false desde el backend
        if (typeof merged[k] === 'boolean') {
          merged[k] = v === true || v === 'true' || v === 1
        } else if (typeof merged[k] === 'number') {
          merged[k] = v !== null && v !== '' ? Number(v) : merged[k]
        } else {
          merged[k] = v
        }
      }
    }

    settings.value = merged
    originalSettings.value = JSON.parse(JSON.stringify(merged))
  } catch (err) {
    msjerr('No se pudo cargar la configuración de la base de datos.')
  } finally {
    loading.value = false
  }
}

// ── Restablecer ─────────────────────────────────────────────────────────────
const resetForm = async () => {
  const ok = await confirmar('¿Descartar todos los cambios no guardados?', {
    titulo: 'Restablecer Cambios',
    textoConfirmar: 'Sí, descartar',
    textoCancelar: 'Cancelar'
  })
  if (ok) {
    settings.value = JSON.parse(JSON.stringify(originalSettings.value))
    errors.value = {}
  }
}

// ── Validaciones del frontend ───────────────────────────────────────────────
const validateFrontend = () => {
  errors.value = {}
  let isValid = true

  // Horas
  if (!settings.value.hora_entrada) {
    errors.value.hora_entrada = 'La hora de entrada es obligatoria.'
    isValid = false
  }
  if (!settings.value.hora_salida) {
    errors.value.hora_salida = 'La hora de salida es obligatoria.'
    isValid = false
  }
  if (settings.value.hora_entrada && settings.value.hora_salida &&
      settings.value.hora_entrada >= settings.value.hora_salida) {
    errors.value.hora_salida = 'La hora de salida debe ser posterior a la hora de entrada.'
    isValid = false
  }

  // Tolerancia
  if (settings.value.tolerancia_minutos === '' || settings.value.tolerancia_minutos < 0) {
    errors.value.tolerancia_minutos = 'La tolerancia no puede ser negativa.'
    isValid = false
  }

  // Geolocalización
  if (settings.value.empresa_lat !== null && settings.value.empresa_lat !== '') {
    const lat = Number(settings.value.empresa_lat)
    if (isNaN(lat) || lat < -90 || lat > 90) {
      errors.value.empresa_lat = 'La latitud debe estar entre -90 y 90.'
      isValid = false
    }
  }
  if (settings.value.empresa_lon !== null && settings.value.empresa_lon !== '') {
    const lon = Number(settings.value.empresa_lon)
    if (isNaN(lon) || lon < -180 || lon > 180) {
      errors.value.empresa_lon = 'La longitud debe estar entre -180 y 180.'
      isValid = false
    }
  }
  if (!settings.value.radio_asistencia || settings.value.radio_asistencia < 1) {
    errors.value.radio_asistencia = 'El radio debe ser mayor a 0 metros.'
    isValid = false
  }

  // Planillas
  if (settings.value.salario_minimo === '' || settings.value.salario_minimo < 0) {
    errors.value.salario_minimo = 'El salario mínimo no puede ser negativo.'
    isValid = false
  }
  if (!settings.value.dia_corte || settings.value.dia_corte < 1 || settings.value.dia_corte > 31) {
    errors.value.dia_corte = 'El día de corte debe estar entre 1 y 31.'
    isValid = false
  }

  // Sistema
  if (!settings.value.zona_horaria) {
    errors.value.zona_horaria = 'La zona horaria es obligatoria.'
    isValid = false
  }
  if (!settings.value.tema_visual) {
    errors.value.tema_visual = 'El tema visual es obligatorio.'
    isValid = false
  }

  if (!isValid) {
    msjerr('Por favor, revise los campos marcados en rojo.')
  }
  return isValid
}

// ── Confirmar y guardar ─────────────────────────────────────────────────────
const confirmarGuardado = async () => {
  if (!validateFrontend()) return

  const ok = await confirmar(
    '¿Guardar los cambios en la configuración? Algunos ajustes como la hora de entrada y la geolocalización se aplicarán de inmediato en los próximos registros de asistencia.',
    {
      titulo: 'Guardar Configuración',
      textoConfirmar: 'Sí, guardar',
      textoCancelar: 'Cancelar'
    }
  )
  if (ok) saveSettings()
}

const saveSettings = async () => {
  saving.value = true
  try {
    // Construir payload: convertir booleanos y números correctamente
    const payload = {}
    for (const [k, v] of Object.entries(settings.value)) {
      if (typeof v === 'boolean') {
        payload[k] = v ? 1 : 0   // Laravel castea 0/1 como boolean
      } else if (v === null || v === '') {
        // Enviar null como cadena vacía si el campo lo admite (lat/lon opcionales)
        payload[k] = ''
      } else {
        payload[k] = v
      }
    }

    await api.put('/configuracion', payload)

    originalSettings.value = JSON.parse(JSON.stringify(settings.value))
    exito('Configuración guardada exitosamente.')
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errores || err.response.data.errors || {}
      for (const key in serverErrors) {
        errors.value[key] = Array.isArray(serverErrors[key])
          ? serverErrors[key][0]
          : serverErrors[key]
      }
      msjerr('Errores de validación devueltos por el servidor.')
    } else {
      msjerr(err.response?.data?.mensaje || err.response?.data?.message || 'Error al guardar la configuración.')
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
.config-container {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
  align-items: start;
}

/* Sidebar */
.config-sidebar { padding: 1rem; }

.config-tabs {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.config-tab-btn {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1rem;
  border: 1px solid transparent;
  border-radius: 8px;
  background: transparent;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
}
.config-tab-btn:hover { background: var(--bg-muted); }
.config-tab-btn.active {
  background: var(--bg-color);
  border-color: var(--border);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
}

.tab-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.tab-icon.blue   { background: rgba(37,99,235,0.1);  color: #2563eb; }
.tab-icon.green  { background: rgba(5,150,105,0.1);  color: #059669; }
.tab-icon.amber  { background: rgba(217,119,6,0.1);  color: #d97706; }

.tab-text { display: flex; flex-direction: column; }
.tab-title { font-weight: 600; color: var(--text-primary); font-size: 0.95rem; }
.tab-subtitle { font-size: 0.75rem; color: var(--text-muted); }

/* Contenido */
.config-content { padding: 2rem; min-height: 500px; }

.config-section-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border);
}
.config-section-header h3 {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}
.config-section-header p { color: var(--text-muted); font-size: 0.9rem; }

/* Bloques */
.config-block {
  margin-bottom: 1.75rem;
  padding: 1.25rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: rgba(0,0,0,0.01);
}
.block-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  margin-bottom: 1rem;
}

/* Grilla de switches */
.switches-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 0.75rem;
}

/* Input con sufijo */
.input-suffix-wrap {
  display: flex;
  align-items: center;
  gap: 0;
}
.input-suffix-wrap .input-control {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  flex: 1;
}
.input-suffix {
  padding: 0 0.75rem;
  height: 100%;
  background: var(--bg-muted);
  border: 1px solid var(--border);
  border-left: none;
  border-radius: 0 6px 6px 0;
  font-size: 0.85rem;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  white-space: nowrap;
  line-height: 1;
  min-height: 38px;
}

/* Switches */
.switch-group {
  padding: 0.875rem 1rem;
  background: rgba(79,70,229,.03);
  border: 1px solid rgba(79,70,229,.09);
  border-radius: 8px;
}
.switch-container {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
  cursor: pointer;
}
.switch-container input {
  opacity: 0; width: 0; height: 0; position: absolute;
}
.switch-slider {
  position: relative;
  width: 44px; height: 24px;
  background-color: var(--text-muted);
  border-radius: 34px;
  transition: .3s;
  flex-shrink: 0;
  margin-top: 0.15rem;
}
.switch-slider:before {
  position: absolute; content: "";
  height: 18px; width: 18px;
  left: 3px; bottom: 3px;
  background-color: white;
  border-radius: 50%;
  transition: .3s;
}
.switch-container input:checked + .switch-slider { background-color: var(--primary-color); }
.switch-container input:checked + .switch-slider:before { transform: translateX(20px); }
.switch-text { display: flex; flex-direction: column; }
.switch-title { font-weight: 600; color: var(--text-primary); font-size: 0.9rem; }

/* Estado de carga */
.loading-state {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  height: 300px; color: var(--text-muted); gap: 1rem;
}

/* Responsive */
@media (max-width: 900px) {
  .config-container { grid-template-columns: 1fr; }
  .config-tabs { flex-direction: row; overflow-x: auto; padding-bottom: 0.5rem; }
  .config-tab-btn { flex: 1; min-width: 160px; }
  .switches-grid { grid-template-columns: 1fr; }
}
</style>
