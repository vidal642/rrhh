<template>
  <div class="registrar-page">
    <div class="registrar-card animate-in">
      <div class="card-header">
        <div class="icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28">
            <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
          </svg>
        </div>
        <div>
          <h1>Registro Facial</h1>
          <p>Escanea tu rostro para activar tu cuenta</p>
        </div>
      </div>

      <!-- Alertas -->
      <transition name="fade">
        <div class="alert" :class="alerta.tipo" v-if="alerta.mensaje">
          <span>{{ alerta.mensaje }}</span>
        </div>
      </transition>

      <!-- Paso 1: Instrucciones -->
      <div v-if="paso === 'instrucciones'" class="paso-instrucciones">
        <div class="instrucciones-grid">
          <div class="instruc-item" v-for="(item, i) in instrucciones" :key="i">
            <div class="instruc-icon">{{ item.icono }}</div>
            <p>{{ item.texto }}</p>
          </div>
        </div>
        <button class="btn btn-primary" @click="iniciarCamara" id="btn-iniciar-camara" :disabled="!modelosCargados">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
            <path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
          </svg>
          <span v-if="!modelosCargados">Cargando IA...</span>
          <span v-else>Activar Cámara</span>
        </button>
      </div>

      <!-- Paso 2: Cámara activa -->
      <div v-if="paso === 'camara'" class="paso-camara">
        <div class="camara-wrapper">
          <video ref="videoEl" autoplay muted playsinline class="camara-video"></video>
          <!-- Overlay escáner -->
          <div class="scan-overlay">
            <div class="scan-frame" :class="{ 'scan-active': escaneando }">
              <span class="corner tl"></span>
              <span class="corner tr"></span>
              <span class="corner bl"></span>
              <span class="corner br"></span>
              <div class="scan-line" v-if="escaneando"></div>
            </div>
          </div>
        </div>

        <!-- Instrucción de Pose / Estado -->
        <div class="instruccion-pose">
          <p class="instruccion-texto" :class="{ 'text-error': instruccionError }">
            {{ instruccionActual }}
          </p>
        </div>

        <!-- Progreso de capturas -->
        <div class="capturas-progress" v-if="pruebaVidaSuperada">
          <div class="progress-label">
            Capturas: <strong>{{ capturas.length }}</strong> / 10
          </div>
          <div class="progress-bar-container">
            <div class="progress-bar-fill" :style="{ width: (capturas.length / 10 * 100) + '%' }"></div>
          </div>
        </div>

        <div class="botones-camara" v-if="pruebaVidaSuperada">
          <button
            class="btn btn-capture"
            @click="ejecutarCapturaManual"
            :disabled="capturando"
            id="btn-capturar"
          >
            <span v-if="capturando" class="spinner"></span>
            <template v-else>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/>
              </svg>
              {{ capturas.length < 10 ? 'Forzar Captura' : 'Registrar Rostro' }}
            </template>
          </button>
        </div>

        <button class="btn btn-ghost" @click="reiniciar">Reiniciar</button>
      </div>

      <!-- Paso 3: Enviando -->
      <div v-if="paso === 'enviando'" class="paso-enviando">
        <div class="sending-anim">
          <div class="sending-ring"></div>
          <div class="sending-ring ring-2"></div>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32" class="sending-icon">
            <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
          </svg>
        </div>
        <p class="sending-text">Procesando tu rostro...</p>
        <p class="sending-sub">Esto puede tardar unos segundos</p>
      </div>

      <!-- Paso 4: Éxito -->
      <div v-if="paso === 'exito'" class="paso-exito">
        <div class="exito-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <h2>Registro completado</h2>
        <p>Tu identidad facial ha sido guardada correctamente.</p>
        <p class="redirigiendo">Ingresando al sistema en {{ contadorRedir }}s...</p>
        <button class="btn btn-primary" @click="irAlDashboard" id="btn-ir-dashboard">
          Ingresar Ahora
        </button>
      </div>

      <!-- Canvas oculto para captura y análisis de calidad -->
      <canvas ref="canvasEl" style="display:none"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import api from '../plugins/axios'
import * as faceapi from '@vladmandic/face-api'

const props = defineProps({
  idEmpleado: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['onSuccess'])

const router    = useRouter()
const authStore = useAuthStore()

const videoEl   = ref(null)
const canvasEl  = ref(null)
const paso      = ref('instrucciones')  // instrucciones | camara | enviando | exito
const capturas  = ref([]) // Descriptores
const imagenBase64 = ref('') 
const capturando    = ref(false)
const escaneando    = ref(false)
const contadorRedir = ref(3)
const alerta    = ref({ mensaje: '', tipo: '' })
const modelosCargados = ref(false)

const pruebaVidaSuperada = ref(false)
const instruccionActual = ref('Coloque su rostro dentro del área indicada.')
const instruccionError = ref(false)

let stream = null
let timerRedir = null
let bucleCamaraId = null

const totalCapturas = 10
const instruccionesDeCaptura = [
  'Mire al frente.',
  'Gire ligeramente hacia la izquierda.',
  'Gire ligeramente hacia la derecha.',
  'Mire ligeramente hacia arriba.',
  'Mire ligeramente hacia abajo.',
  'Mire al frente nuevamente.',
  'Gire levemente a la izquierda.',
  'Gire levemente a la derecha.',
  'Mire al frente y relaje el rostro.',
  'Última captura, mire a la cámara.'
]

const instrucciones = [
  { icono: '', texto: 'Busca un lugar bien iluminado' },
  { icono: '', texto: 'Centra tu rostro en el recuadro' },
  { icono: '', texto: 'Se tomarán 10 fotos rápidas' },
  { icono: '', texto: 'Deberás parpadear cuando se te pida' },
]

const mostrarAlerta = (mensaje, tipo = 'error') => {
  alerta.value = { mensaje, tipo }
  setTimeout(() => { alerta.value.mensaje = '' }, 5000)
}

onMounted(async () => {
  try {
    const modelPath = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/model/'
    await Promise.all([
      faceapi.nets.ssdMobilenetv1.loadFromUri(modelPath),
      faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
      faceapi.nets.faceRecognitionNet.loadFromUri(modelPath)
    ])
    modelosCargados.value = true
  } catch (err) {
    mostrarAlerta('Error al cargar modelos de IA. Revisa tu conexión a internet.')
  }
})

const iniciarCamara = async () => {
  if (!modelosCargados.value) {
    mostrarAlerta('Los modelos de IA aún se están cargando. Espera un momento.')
    return
  }
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
    })
    paso.value = 'camara'
    await nextTick()
    if (videoEl.value) {
      videoEl.value.srcObject = stream
      await new Promise(r => setTimeout(r, 500))
    }
    escaneando.value = true
    
    // Iniciar el flujo automático
    bucleCamaraId = requestAnimationFrame(procesarFrame)
  } catch (err) {
    mostrarAlerta('No se pudo acceder a la cámara. Verifica los permisos del navegador.')
  }
}

// Variables para control de tiempo y estado en el loop
let lastFrameTime = 0
let lastEar = null
let blinkDetected = false

const procesarFrame = async (time) => {
  if (paso.value !== 'camara') return
  
  // Throttle sólo si ya pasamos la prueba de vida, para no saturar durante las capturas.
  // Durante la prueba de vida necesitamos procesar lo más rápido posible para no perdernos el parpadeo (que dura ~150ms).
  if (pruebaVidaSuperada.value && time - lastFrameTime < 300) {
    bucleCamaraId = requestAnimationFrame(procesarFrame)
    return
  }
  lastFrameTime = time
  
  if (capturando.value) {
    bucleCamaraId = requestAnimationFrame(procesarFrame)
    return
  }
  
  try {
    const video = videoEl.value
    if (!video || video.paused || video.ended) {
      bucleCamaraId = requestAnimationFrame(procesarFrame)
      return
    }

    const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.65 })
    const detecciones = await faceapi.detectAllFaces(video, options).withFaceLandmarks().withFaceDescriptors()
    
    // Control de calidad básico 1: Múltiples rostros
    if (detecciones.length > 1) {
      setInstruccion('Se detectaron varios rostros. Asegúrese de que solamente aparezca una persona.', true)
      bucleCamaraId = requestAnimationFrame(procesarFrame)
      return
    }
    
    if (detecciones.length === 0) {
      setInstruccion('No se detectó ningún rostro.', true)
      bucleCamaraId = requestAnimationFrame(procesarFrame)
      return
    }
    
    const face = detecciones[0]
    
    // Control de calidad 2: Tamaño y posición
    const box = face.detection.box
    const videoWidth = video.videoWidth
    const videoHeight = video.videoHeight
    
    const isCentered = box.x > 0 && box.y > 0 && (box.x + box.width) < videoWidth && (box.y + box.height) < videoHeight
    const faceArea = box.width * box.height
    const screenArea = videoWidth * videoHeight
    
    if (!isCentered) {
      setInstruccion('Mantenga el rostro dentro del área indicada.', true)
      bucleCamaraId = requestAnimationFrame(procesarFrame)
      return
    }
    
    if (faceArea / screenArea < 0.05) {
      setInstruccion('El rostro está demasiado lejos.', true)
      bucleCamaraId = requestAnimationFrame(procesarFrame)
      return
    }
    
    // Control de calidad 3: Iluminación (usando Canvas)
    const canvas = canvasEl.value
    canvas.width = videoWidth
    canvas.height = videoHeight
    const ctx = canvas.getContext('2d', { willReadFrequently: true })
    ctx.drawImage(video, 0, 0, videoWidth, videoHeight)
    
    // Analizar una porción del rostro
    const faceData = ctx.getImageData(box.x, box.y, box.width, box.height)
    let colorSum = 0
    for (let i = 0; i < faceData.data.length; i += 4) {
      const r = faceData.data[i]
      const g = faceData.data[i+1]
      const b = faceData.data[i+2]
      // Luminancia percibida
      const avg = (0.299*r + 0.587*g + 0.114*b)
      colorSum += avg
    }
    const brightness = colorSum / (faceData.width * faceData.height)
    
    if (brightness < 40) {
      setInstruccion('Existe poca iluminación.', true)
      bucleCamaraId = requestAnimationFrame(procesarFrame)
      return
    }
    
    if (brightness > 240) {
      setInstruccion('La imagen está demasiado clara/sobreexpuesta.', true)
      bucleCamaraId = requestAnimationFrame(procesarFrame)
      return
    }
    
    // PRUEBA DE VIDA (Parpadeo)
    if (!pruebaVidaSuperada.value) {
      setInstruccion('Mire directamente a la cámara y parpadee.', false)
      
      const landmarks = face.landmarks
      const leftEye = landmarks.getLeftEye()
      const rightEye = landmarks.getRightEye()
      
      const earLeft = calcularEAR(leftEye)
      const earRight = calcularEAR(rightEye)
      const earPromedio = (earLeft + earRight) / 2
      
      // Umbral típico de ojo cerrado es ~0.2, abierto ~0.3
      if (lastEar !== null) {
        // Umbrales mucho más permisivos:
        if (earPromedio < 0.26) {
          blinkDetected = true
        } else if (blinkDetected && earPromedio > 0.27) {
          // Parpadeo completado
          pruebaVidaSuperada.value = true
          setInstruccion('Prueba de vida válida. ' + instruccionesDeCaptura[0], false)
          blinkDetected = false
        }
      }
      lastEar = earPromedio
      
      bucleCamaraId = requestAnimationFrame(procesarFrame)
      return
    }
    
    // CAPTURA AUTOMÁTICA
    if (pruebaVidaSuperada.value && capturas.value.length < totalCapturas) {
      await capturarInstancia(face, video, ctx)
    }

  } catch (err) {
    console.error(err)
  }
  
  if (paso.value === 'camara') {
    bucleCamaraId = requestAnimationFrame(procesarFrame)
  }
}

const calcularEAR = (eye) => {
  // Distancias verticales
  const v1 = Math.hypot(eye[1].x - eye[5].x, eye[1].y - eye[5].y)
  const v2 = Math.hypot(eye[2].x - eye[4].x, eye[2].y - eye[4].y)
  // Distancia horizontal
  const h = Math.hypot(eye[0].x - eye[3].x, eye[0].y - eye[3].y)
  if (h === 0) return 0
  return (v1 + v2) / (2.0 * h)
}

const setInstruccion = (texto, error) => {
  instruccionActual.value = texto
  instruccionError.value = error
}

const capturarInstancia = async (face, video, ctx) => {
  capturando.value = true
  
  try {
    capturas.value.push(face.descriptor)
    
    // Guardar referencia visual en la primera
    if (capturas.value.length === 1) {
      imagenBase64.value = canvasEl.value.toDataURL('image/jpeg', 0.85)
    }
    
    if (capturas.value.length >= totalCapturas) {
      setInstruccion('Capturas completadas, procesando...', false)
      await enviarRegistro()
    } else {
      setInstruccion('Captura válida. ' + instruccionesDeCaptura[capturas.value.length], false)
      // Pequeña pausa para que el usuario se mueva
      await new Promise(r => setTimeout(r, 1000))
    }
  } finally {
    capturando.value = false
  }
}

const ejecutarCapturaManual = async () => {
  if (capturando.value) return
  if (capturas.value.length >= totalCapturas) {
    await enviarRegistro()
    return
  }
  
  capturando.value = true
  try {
    const video = videoEl.value
    const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.65 })
    const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks().withFaceDescriptor()

    if (!detection) {
      setInstruccion('No se detectó ningún rostro.', true)
      capturando.value = false
      return
    }

    const canvas = canvasEl.value
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    const ctx = canvas.getContext('2d')
    ctx.drawImage(video, 0, 0)
    
    capturas.value.push(detection.descriptor)
    
    if (capturas.value.length === 1) {
      imagenBase64.value = canvas.toDataURL('image/jpeg', 0.85)
    }
    
    if (capturas.value.length >= totalCapturas) {
      setInstruccion('Capturas completadas, procesando...', false)
      await enviarRegistro()
    } else {
      setInstruccion('Captura manual válida. ' + instruccionesDeCaptura[capturas.value.length], false)
    }
  } finally {
    capturando.value = false
  }
}

const calcularPromedioDescriptores = (descriptores) => {
  const length = descriptores[0].length
  const promedio = new Float32Array(length)
  for (let i = 0; i < length; i++) {
    let sum = 0
    for (let j = 0; j < descriptores.length; j++) {
      sum += descriptores[j][i]
    }
    promedio[i] = sum / descriptores.length
  }
  return Array.from(promedio)
}

const enviarRegistro = async () => {
  paso.value = 'enviando'
  detenerCamara()

  try {
    const embeddingFinal = calcularPromedioDescriptores(capturas.value)
    
    if (props.idEmpleado) {
      await api.post(`/empleados/${props.idEmpleado}/actualizar-rostro`, { 
        embedding: embeddingFinal,
        imagen_referencia: imagenBase64.value
      })
      emit('onSuccess')
      return
    }

    await api.post('/reconocimiento/registrar', { 
      embedding: embeddingFinal,
      imagen_referencia: imagenBase64.value
    })
    authStore.rostroRegistrado = true
    paso.value = 'exito'
    iniciarContadorRedireccion()
  } catch (err) {
    const msg = err.response?.data?.mensaje
      || err.response?.data?.detail
      || err.response?.data?.error
      || 'Error al registrar el rostro. Intenta nuevamente.'
    paso.value = 'camara'
    await iniciarCamara()
    mostrarAlerta(msg)
    capturas.value = [] // Resetear capturas si hay fallo en registro (ej. duplicado)
    pruebaVidaSuperada.value = false // Reiniciar prueba de vida por si acaso
  }
}

const iniciarContadorRedireccion = () => {
  timerRedir = setInterval(() => {
    contadorRedir.value--
    if (contadorRedir.value <= 0) {
      clearInterval(timerRedir)
      irAlDashboard()
    }
  }, 1000)
}

const irAlDashboard = () => {
  clearInterval(timerRedir)
  router.push('/dashboard')
}

const reiniciar = () => {
  detenerCamara()
  capturas.value = []
  imagenBase64.value = ''
  alerta.value.mensaje = ''
  pruebaVidaSuperada.value = false
  instruccionActual.value = 'Coloque su rostro dentro del área indicada.'
  instruccionError.value = false
  paso.value = 'instrucciones'
}

const detenerCamara = () => {
  if (bucleCamaraId) {
    cancelAnimationFrame(bucleCamaraId)
    bucleCamaraId = null
  }
  if (stream) {
    stream.getTracks().forEach(t => t.stop())
    stream = null
  }
  escaneando.value = false
}

onUnmounted(() => {
  detenerCamara()
  clearInterval(timerRedir)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

* { box-sizing: border-box; }

.registrar-page {
  width: 100%;
  background: transparent;
  display: flex; align-items: center; justify-content: center;
  padding: 1.5rem; position: relative; overflow: hidden;
  font-family: 'Inter', sans-serif;
}

.registrar-card {
  position: relative; z-index: 1;
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: 24px; padding: 2.5rem 2.25rem;
  width: 100%; max-width: 480px;
  box-shadow: var(--shadow-lg);
}

.animate-in { animation: cardIn 0.5s cubic-bezier(0.34,1.2,0.64,1) forwards; }
@keyframes cardIn { from{opacity:0;transform:translateY(24px) scale(0.97)} to{opacity:1;transform:none} }

/* Header */
.card-header { display:flex; align-items:center; gap:1rem; margin-bottom:1.75rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border); }
.icon-wrapper { width:56px; height:56px; border-radius:16px; background:rgba(19,60,85,.05); border:1px solid rgba(19,60,85,.1); display:flex;align-items:center;justify-content:center; color:var(--primary-600); flex-shrink:0; }
.card-header h1 { font-size:1.25rem; font-weight:800; color:var(--text-primary); margin:0 0 .2rem; }
.card-header p { font-size:.8125rem; color:var(--text-secondary); margin:0; }

/* Alertas */
.alert { padding:.75rem 1rem; border-radius:10px; font-size:.8125rem; font-weight:500; margin-bottom:1.25rem; }
.alert.error { background:rgba(220,38,38,.1); border:1px solid rgba(220,38,38,.25); color:#fca5a5; }
.alert.success { background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.25); color:#6ee7b7; }
.fade-enter-active,.fade-leave-active{transition:opacity .3s}
.fade-enter-from,.fade-leave-to{opacity:0}

/* Instrucciones */
.instrucciones-grid { display:grid; grid-template-columns:1fr 1fr; gap:.875rem; margin-bottom:2rem; }
.instruc-item { background:rgba(0,0,0,0.02); border:1px solid var(--border); border-radius:12px; padding:1rem; text-align:center; }
.instruc-icon { font-size:1.75rem; margin-bottom:.5rem; }
.instruc-item p { font-size:.75rem; color:var(--text-secondary); margin:0; line-height:1.4; }

/* Cámara */
.camara-wrapper { position:relative; border-radius:16px; overflow:hidden; background:#000; margin-bottom:1.25rem; aspect-ratio:4/3; }
.camara-video { width:100%; height:100%; object-fit:cover; transform:scaleX(-1); }

.scan-overlay { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; }
.scan-frame { position:relative; width:55%; aspect-ratio:1; }
.corner { position:absolute; width:20px; height:20px; border-color:var(--primary-500); border-style:solid; }
.corner.tl { top:0;left:0; border-width:3px 0 0 3px; border-radius:4px 0 0 0; }
.corner.tr { top:0;right:0; border-width:3px 3px 0 0; border-radius:0 4px 0 0; }
.corner.bl { bottom:0;left:0; border-width:0 0 3px 3px; border-radius:0 0 0 4px; }
.corner.br { bottom:0;right:0; border-width:0 3px 3px 0; border-radius:0 0 4px 0; }
.scan-line { position:absolute; left:0; right:0; height:2px; background:linear-gradient(90deg,transparent,var(--secondary-500),transparent); animation:scanMove 2s ease-in-out infinite; }
@keyframes scanMove { 0%,100%{top:0} 50%{top:100%} }

/* Instrucción de Pose */
.instruccion-pose { text-align:center; margin-bottom:1rem; padding: 0.5rem; background: rgba(0,0,0,0.03); border-radius: 8px;}
.instruccion-texto { font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin: 0; }
.text-error { color: #dc2626; }

/* Progreso */
.capturas-progress { margin-bottom:1rem; }
.progress-label { font-size:.8125rem; color:var(--text-secondary); margin-bottom: 0.5rem; display: flex; justify-content: space-between;}
.progress-label strong { color:var(--primary-600); }
.progress-bar-container { width: 100%; height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden; }
.progress-bar-fill { height: 100%; background: var(--primary-500); transition: width 0.3s ease; }

/* Enviando */
.paso-enviando { display:flex; flex-direction:column; align-items:center; padding:2rem 0; text-align:center; }
.sending-anim { position:relative; width:100px; height:100px; display:flex; align-items:center; justify-content:center; margin-bottom:1.5rem; }
.sending-ring { position:absolute; inset:0; border-radius:50%; border:3px solid transparent; border-top-color:var(--primary-500); animation:spin 1.2s linear infinite; }
.ring-2 { inset:10px; border-top-color:var(--info-500); animation-duration:1.8s; animation-direction:reverse; }
.sending-icon { color:var(--primary-600); }
@keyframes spin { to{transform:rotate(360deg)} }
.sending-text { font-size:.9375rem; font-weight:600; color:var(--text-primary); margin:.5rem 0; }
.sending-sub { font-size:.8125rem; color:var(--text-secondary); }

/* Éxito */
.paso-exito { text-align:center; padding:1.5rem 0; }
.exito-icon { width:80px; height:80px; border-radius:50%; background:rgba(16,185,129,.1); border:2px solid rgba(16,185,129,.2); display:flex; align-items:center; justify-content:center; color:#10b981; margin:0 auto 1.25rem; }
.paso-exito h2 { font-size:1.375rem; font-weight:800; color:var(--text-primary); margin:0 0 .5rem; }
.paso-exito p { color:var(--text-secondary); font-size:.875rem; margin:0 0 .5rem; }
.redirigiendo { color:var(--primary-600); font-size:.8125rem; margin-bottom:1.5rem !important; }

/* Botones */
.botones-camara { margin-top: 1rem; }
.btn { display:flex; align-items:center; justify-content:center; gap:.5rem; height:46px; border-radius:12px; font-size:.875rem; font-weight:600; cursor:pointer; border:none; width:100%; transition:all .2s; }
.btn-primary { background:var(--secondary-500); color:#fff; box-shadow:0 4px 14px rgba(16,185,129,.3); margin-top:.5rem; }
.btn-primary:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 6px 20px rgba(16,185,129,.4); }
.btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.btn-capture { background:var(--secondary-500); color:#fff; box-shadow:0 4px 14px rgba(16,185,129,.3); margin-top:.5rem; }
.btn-capture:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 6px 20px rgba(16,185,129,.4); }
.btn-capture:disabled { opacity:.5; cursor:not-allowed; }
.btn-ghost { background:rgba(0,0,0,.02); color:var(--text-secondary); border:1px solid var(--border); margin-top:.5rem; }
.btn-ghost:hover { background:rgba(0,0,0,.05); color:var(--text-primary); }
.spinner { width:18px; height:18px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin 0.7s linear infinite; }

@media(max-width:480px) {
  .registrar-card { padding:1.75rem 1.25rem; }
  .instrucciones-grid { grid-template-columns:1fr; }
}
</style>
