<template>
  <div class="kiosco-page">
    <div class="kiosco-content">
      <div class="kiosco-header">
        <div class="header-left">
          <router-link to="/" class="btn-icon-back" title="Volver al inicio">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
              <line x1="19" y1="12" x2="5" y2="12"></line>
              <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
          </router-link>
          <img src="/logo_flores.png" alt="Logo" class="kiosco-logo" />
        </div>
        <div>
          <h1 class="text-primary">Control de Asistencia</h1>
          <p class="text-secondary">Constructora Flores — Reconocimiento Facial</p>
        </div>
        <div class="kiosco-time">
          <div class="time-display">{{ horaActual }}</div>
          <div class="date-display">{{ fechaActual }}</div>
        </div>
      </div>

      <div class="kiosco-main">
        <div class="camara-panel">
          <div class="camara-box" :class="{ 'estado-reconocido': estado === 'reconocido', 'estado-fallo': estado === 'fallo' }">
            <video ref="videoEl" autoplay muted playsinline class="camara-video"></video>

            <div class="scan-overlay">
              <div class="oval-guide" :class="{ active: estado === 'listo' }"></div>
              <div class="scan-line" v-if="estado === 'listo'"></div>
            </div>

            <transition name="fade">
              <div v-if="estado !== 'listo'" class="resultado-overlay" :class="estado">
                <div class="resultado-icon">
                  <svg v-if="estado === 'reconocido'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="48" height="48">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  <svg v-else-if="estado === 'fallo'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="48" height="48">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                  </svg>
                  <div v-else class="spin-ring"></div>
                </div>
                <p class="resultado-texto">{{ mensajeEstado }}</p>
              </div>
            </transition>
          </div>

          <p class="instruccion-camara">
            <span v-if="estado === 'listo'"> Mira directo a la cámara y presiona el botón</span>
            <span v-else-if="estado === 'procesando'"> Procesando...</span>
            <span v-else-if="estado === 'reconocido'"> {{ resultadoData?.nombre || 'Empleado reconocido' }}</span>
            <span v-else> No se pudo reconocer. Intenta de nuevo.</span>
          </p>
        </div>

        <div class="resultado-panel">
          <div v-if="!resultadoData" class="panel-esperando">
            <div class="pulse-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" class="icon-primary">
                <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
              </svg>
            </div>
            <h3>Listo para registrar</h3>
            <p>Acércate a la cámara y presiona el botón para marcar tu asistencia</p>
          </div>

          <div v-else class="panel-resultado" :class="{ 'panel-ok': estado === 'reconocido', 'panel-fail': estado === 'fallo' }">
            <div class="resultado-badge">
              <span v-if="estado === 'reconocido'"> Asistencia Registrada</span>
              <span v-else> No Reconocido</span>
            </div>

            <template v-if="estado === 'reconocido'">
              <div class="empleado-info">
                <div class="empleado-avatar">{{ iniciales }}</div>
                <div>
                  <h3>{{ resultadoData.nombre }}</h3>
                  <p>{{ resultadoData.hora }}</p>
                </div>
              </div>
              <div class="confianza-bar-wrapper">
                <div class="confianza-label">
                  <span>Confianza</span>
                  <strong>{{ resultadoData.confianza }}%</strong>
                </div>
                <div class="confianza-bar">
                  <div class="confianza-fill" :style="{ width: resultadoData.confianza + '%' }"></div>
                </div>
              </div>
            </template>

            <template v-else>
              <p class="fallo-msg">{{ resultadoData.mensaje || 'El sistema no encontró coincidencias. Asegúrate de estar registrado.' }}</p>
              <router-link v-if="!resultadoData.completado" to="/actualizar-rostro" class="btn btn-outline btn-register-face">
                Actualizar mi rostro
              </router-link>
            </template>
          </div>

          <button
            class="btn btn-verificar"
            @click="verificarRostro"
            :disabled="estado === 'procesando'"
            id="btn-verificar-asistencia"
          >
            <span v-if="estado === 'procesando'" class="spinner"></span>
            <template v-else>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
              </svg>
              Marcar Asistencia
            </template>
          </button>

          <p class="kiosco-footer-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Los datos biométricos están protegidos
          </p>
        </div>
      </div>
    </div>

    <canvas ref="canvasEl" style="display:none"></canvas>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '../plugins/axios'
import * as faceapi from '@vladmandic/face-api'

const videoEl     = ref(null)
const canvasEl    = ref(null)
const estado      = ref('cargando')
const resultadoData = ref(null)
const mensajeEstado = ref('Inicializando sistema...')
const horaActual  = ref('')
const fechaActual = ref('')

let stream = null
let timerReloj = null
let timerReset = null
let faceMatcher = null

const iniciales = computed(() => {
  if (!resultadoData.value?.nombre) return '?'
  return resultadoData.value.nombre.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
})

const actualizarReloj = () => {
  const ahora = new Date()
  horaActual.value = ahora.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  fechaActual.value = ahora.toLocaleDateString('es-PE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

const cargarModelosYDatos = async () => {
  try {
    const modelPath = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/model/'
    mensajeEstado.value = 'Cargando modelos de IA...'
    await Promise.all([
      faceapi.nets.ssdMobilenetv1.loadFromUri(modelPath),
      faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
      faceapi.nets.faceRecognitionNet.loadFromUri(modelPath)
    ])

    mensajeEstado.value = 'Descargando datos biométricos...'
    const res = await api.get('/reconocimiento/embeddings')
    const embeddings = res.data.datos || []

    if (embeddings.length === 0) {
      estado.value = 'fallo'
      mensajeEstado.value = 'No hay rostros registrados en el sistema.'
      return false
    }

    const labeledDescriptors = []
    for (const emp of embeddings) {
      const vals = Object.values(emp.embedding)
      if (vals.length === 128) {
        const arr = new Float32Array(vals)
        const label = JSON.stringify({ id_empleado: emp.id_empleado, nombre: emp.nombre })
        labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(label, [arr]))
      }
    }

    if (labeledDescriptors.length === 0) {
      estado.value = 'fallo'
      mensajeEstado.value = 'No hay rostros 100% compatibles en la BD. Registra tu rostro nuevamente.'
      return false
    }

    faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.45)
    return true
  } catch (err) {
    console.error(err)
    estado.value = 'fallo'
    mensajeEstado.value = 'Error al inicializar el sistema de reconocimiento.'
    return false
  }
}

const iniciarCamara = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
    })
    if (videoEl.value) {
      videoEl.value.srcObject = stream
      await new Promise(r => setTimeout(r, 500))
    }
  } catch {
    estado.value = 'fallo'
    mensajeEstado.value = 'Sin acceso a la cámara'
  }
}

const verificarRostro = async () => {
  if (estado.value === 'procesando' || estado.value === 'cargando') return
  estado.value = 'procesando'
  mensajeEstado.value = 'Analizando rostro...'
  resultadoData.value = null
  clearTimeout(timerReset)

  try {
    const video = videoEl.value
    const detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor()

    if (!detection) {
      estado.value = 'fallo'
      mensajeEstado.value = 'No se detectó ningún rostro.'
      resultadoData.value = { fallo: true }
    } else {
      const match = faceMatcher.findBestMatch(detection.descriptor)

      if (match.label === 'unknown') {
        estado.value = 'fallo'
        mensajeEstado.value = 'Rostro no reconocido'
        resultadoData.value = { fallo: true }
      } else {
        const datosPersona = JSON.parse(match.label)
        const confianzaNum = Math.round((1 - match.distance) * 100)

        mensajeEstado.value = 'Registrando asistencia...'

        const canvas = canvasEl.value
        canvas.width  = video.videoWidth
        canvas.height = video.videoHeight
        canvas.getContext('2d').drawImage(video, 0, 0)
        const base64 = canvas.toDataURL('image/jpeg', 0.85)

        try {
          const res = await api.post('/asistencia/facial', {
            id_empleado: datosPersona.id_empleado,
            confianza: confianzaNum,
            imagen: base64
          })
          
          estado.value = 'reconocido'
          resultadoData.value = {
            nombre: datosPersona.nombre,
            confianza: confianzaNum,
            hora: res.data.datos?.hora_entrada || new Date().toLocaleTimeString('es-PE'),
          }
        } catch (apiErr) {
          estado.value = 'fallo'
          const msg = apiErr.response?.data?.mensaje || 'Error al registrar'
          mensajeEstado.value = msg
          resultadoData.value = { 
            fallo: true, 
            mensaje: msg,
            completado: msg.includes('Ya has registrado')
          }
        }
      }
    }
  } catch (err) {
    console.error('FaceAPI Error:', err)
    estado.value = 'fallo'
    mensajeEstado.value = 'Error: ' + (err.message || 'Excepción desconocida')
    resultadoData.value = { fallo: true }
  }

  timerReset = setTimeout(() => {
    estado.value = 'listo'
    resultadoData.value = null
  }, 5000)
}

onMounted(async () => {
  actualizarReloj()
  timerReloj = setInterval(actualizarReloj, 1000)
  
  const ok = await cargarModelosYDatos()
  if (ok) {
    await iniciarCamara()
    if (estado.value !== 'fallo') {
      estado.value = 'listo'
    }
  }
})

onUnmounted(() => {
  if (stream) stream.getTracks().forEach(t => t.stop())
  clearInterval(timerReloj)
  clearTimeout(timerReset)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

* { box-sizing: border-box; }

.kiosco-page {
  min-height: 100vh;
  background: var(--bg-body);
  display: flex; align-items: center; justify-content: center;
  padding: 1.5rem; position: relative; overflow: hidden;
  font-family: 'Inter', sans-serif;
}

.kiosco-content { position: relative; z-index: 1; width: 100%; max-width: 900px; }

/* Header */
.kiosco-header {
  display: flex; align-items: center; gap: 1.25rem;
  background: var(--bg-surface); border: 1px solid var(--border);
  border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;
  box-shadow: var(--shadow-sm);
}
.header-left { display: flex; align-items: center; gap: 0.75rem; }
.btn-icon-back {
  display: flex; align-items: center; justify-content: center;
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--bg-body); color: var(--text-secondary);
  border: 1px solid var(--border); transition: all 0.2s;
}
.btn-icon-back:hover { background: var(--border); color: var(--text-primary); }
.kiosco-logo { width: 44px; height: 44px; border-radius: 10px; object-fit: contain; }
.text-primary { font-size: 1.125rem; font-weight: 800; color: var(--text-primary); margin: 0 0 .15rem; }
.text-secondary { font-size: .75rem; color: var(--text-secondary); margin: 0; }
.kiosco-time { margin-left: auto; text-align: right; }
.time-display { font-size: 1.5rem; font-weight: 800; color: var(--primary-600); font-variant-numeric: tabular-nums; }
.date-display { font-size: .7rem; color: var(--text-muted); text-transform: capitalize; }

/* Main layout */
.kiosco-main { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }

/* Cámara */
.camara-panel {}
.camara-box {
  position: relative; border-radius: 16px; overflow: hidden;
  background: #000; aspect-ratio: 4/3;
  border: 2px solid rgba(255,255,255,0.08);
  transition: border-color .4s;
}
.camara-box.estado-reconocido { border-color: rgba(16,185,129,.5); box-shadow: 0 0 30px rgba(16,185,129,.15); }
.camara-box.estado-fallo { border-color: rgba(239,68,68,.5); box-shadow: 0 0 30px rgba(239,68,68,.15); }
.camara-video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }

.scan-overlay { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
.oval-guide {
  width: 55%; aspect-ratio: 3/4; border-radius: 50%;
  border: 2px solid rgba(19, 60, 85, .4);
  transition: border-color .3s;
}
.oval-guide.active { border-color: rgba(19, 60, 85, .8); box-shadow: 0 0 0 4px rgba(19, 60, 85, .1); }
.scan-line {
  position: absolute; top: 20%; left: 20%; right: 20%; height: 2px;
  background: linear-gradient(90deg,transparent,var(--primary-500),transparent);
  animation: scanV 2.5s ease-in-out infinite;
}
@keyframes scanV { 0%{top:20%} 50%{top:75%} 100%{top:20%} }

.resultado-overlay {
  position: absolute; inset: 0; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: .75rem;
  background: rgba(0,0,0,.7); backdrop-filter: blur(4px);
}
.resultado-overlay.reconocido { background: rgba(5,46,22,.7); }
.resultado-overlay.fallo { background: rgba(69,10,10,.7); }
.resultado-overlay.procesando { background: rgba(0,0,0,.65); }
.resultado-icon { color: #fff; }
.resultado-icon .spin-ring { width: 48px; height: 48px; border: 3px solid rgba(255,255,255,.3); border-top-color: #6366f1; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { to{transform:rotate(360deg)} }
.resultado-texto { color: #fff; font-weight: 600; font-size: .9rem; }

.instruccion-camara { font-size: .8125rem; color: var(--text-secondary); text-align: center; margin-top: .75rem; min-height: 1.5rem; font-weight: 500; }

/* Panel resultado */
.resultado-panel {
  background: var(--bg-surface); border: 1px solid var(--border);
  border-radius: 16px; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;
  box-shadow: var(--shadow-card);
}

.panel-esperando { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .75rem; text-align: center; }
.pulse-icon { width: 72px; height: 72px; border-radius: 50%; background: rgba(19, 60, 85, .05); border: 1px solid rgba(19, 60, 85, .1); display: flex; align-items: center; justify-content: center; color: var(--primary-500); animation: pulse 2s ease-in-out infinite; }
@keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(19, 60, 85, .15)} 50%{box-shadow:0 0 0 12px transparent} }
.panel-esperando h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0; }
.panel-esperando p { font-size: .8125rem; color: var(--text-secondary); margin: 0; }

.panel-resultado { flex: 1; display: flex; flex-direction: column; gap: 1rem; }
.resultado-badge { padding: .5rem 1rem; border-radius: 8px; font-size: .8125rem; font-weight: 700; text-align: center; }
.panel-ok .resultado-badge { background: rgba(16,185,129,.1); color: #047857; border: 1px solid rgba(16,185,129,.2); }
.panel-fail .resultado-badge { background: rgba(239,68,68,.1); color: #dc2626; border: 1px solid rgba(239,68,68,.2); }

.empleado-info { display: flex; align-items: center; gap: 1rem; }
.empleado-avatar { width: 52px; height: 52px; border-radius: 50%; background: var(--primary-500); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; font-weight: 800; color: #fff; flex-shrink: 0; }
.empleado-info h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0 0 .2rem; }
.empleado-info p { font-size: .8125rem; color: var(--text-secondary); margin: 0; }

.confianza-bar-wrapper {}
.confianza-label { display: flex; justify-content: space-between; font-size: .75rem; color: var(--text-secondary); margin-bottom: .375rem; }
.confianza-label strong { color: var(--secondary-600); }
.confianza-bar { height: 6px; background: rgba(0,0,0,.05); border-radius: 99px; overflow: hidden; }
.confianza-fill { height: 100%; background: var(--secondary-500); border-radius: 99px; transition: width .8s ease; }

.fallo-msg { font-size: .8125rem; color: var(--text-secondary); text-align: center; margin: 0; }
.btn-register-face {
  margin-top: 0.5rem;
  padding: 0.6rem;
  border-radius: 8px;
  background: var(--bg-body);
  color: var(--primary-600);
  border: 1px solid var(--border);
  font-size: 0.85rem;
  font-weight: 600;
  text-align: center;
  text-decoration: none;
  transition: all 0.2s;
}
.btn-register-face:hover {
  background: var(--border);
  color: var(--primary-700);
}

/* Botón */
.btn-verificar {
  display: flex; align-items: center; justify-content: center; gap: .5rem;
  height: 50px; border-radius: 12px; font-size: .9375rem; font-weight: 700;
  background: var(--secondary-500); color: #fff;
  border: none; cursor: pointer; width: 100%;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
  transition: all .2s;
}
.btn-verificar:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); }
.btn-verificar:disabled { opacity: .6; cursor: not-allowed; }
.spinner { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }

.kiosco-footer-note { display: flex; align-items: center; gap: .35rem; font-size: .7rem; color: #334155; justify-content: center; margin: 0; }

.fade-enter-active,.fade-leave-active { transition: opacity .3s; }
.fade-enter-from,.fade-leave-to { opacity: 0; }

@media(max-width:768px) {
  .kiosco-main { grid-template-columns: 1fr; }
  .kiosco-header { flex-wrap: wrap; }
  .kiosco-time { margin-left: 0; }
}
</style>
