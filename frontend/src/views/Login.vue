<template>
  <div class="login-page">

    <!-- Fondo decorativo -->
    <div class="login-bg-shapes">
      <div class="bg-shape bg-shape-1"></div>
      <div class="bg-shape bg-shape-2"></div>
      <div class="bg-shape bg-shape-3"></div>
    </div>

    <div class="login-card animate-fade-in">

      <!-- Encabezado de empresa -->
      <div class="login-brand">
        <div class="login-logo-wrapper">
          <img src="/logo_flores.png" alt="Logo Constructora Flores" class="login-logo-img" />
        </div>
        <div class="login-brand-info">
          <span class="login-brand-name">Constructora Flores</span>
          <span class="login-brand-tag">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="10" height="10">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
              <line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            Sistema de Recursos Humanos
          </span>
        </div>
      </div>

      <!-- Título -->
      <div class="login-heading">
        <h2>Bienvenido de nuevo</h2>
        <p>Ingresa tus credenciales para acceder al panel</p>
      </div>

      <!-- Error -->
      <transition name="shake">
        <div class="alert alert-error" v-if="errorMsg">
          <span class="alert-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </span>
          <span>{{ errorMsg }}</span>
        </div>
      </transition>

      <form @submit.prevent="handleLogin" class="login-form" autocomplete="off">

        <div class="form-group">
          <label class="form-label" for="usuario">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            Nombre de Usuario
          </label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
              </svg>
            </span>
            <input
              id="usuario"
              type="text"
              v-model="usuario"
              class="input-control"
              placeholder="usuario"
              required
              autocomplete="username"
            />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            Contraseña
          </label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input
              id="password"
              :type="showPassword ? 'text' : 'password'"
              v-model="password"
              class="input-control"
              placeholder="••••••••"
              required
              autocomplete="current-password"
            />
            <button type="button" class="input-action" @click="showPassword = !showPassword" tabindex="-1" aria-label="Mostrar u ocultar contraseña">
              <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn login-btn" :disabled="loading" id="login-submit-btn">
          <span v-if="loading" class="spinner"></span>
          <template v-else>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
              <polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Ingresar al sistema
          </template>
        </button>
      </form>

      <div class="login-footer">
        <div class="login-security-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
          Conexión segura y cifrada
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import api from '../plugins/axios'

const usuario      = ref('')
const password     = ref('')
const showPassword = ref(false)
const loading      = ref(false)
const errorMsg     = ref('')
const router       = useRouter()
const authStore    = useAuthStore()

const handleLogin = async () => {
  errorMsg.value = ''
  loading.value  = true
  try {
    const response = await api.post('/iniciar-sesion', { usuario: usuario.value, password: password.value })
    const resDatos = response.data.datos || response.data
    if (resDatos && resDatos.token) {
      const user = resDatos.usuario || resDatos.user
      
      // Bloquear acceso a empleados, ya que el panel es solo administrativo
      if (user.rol && user.rol.toLowerCase() === 'empleado') {
        errorMsg.value = 'Acceso denegado. Este panel es exclusivo para administradores.'
        loading.value = false
        return
      }

      authStore.setAuth(user, resDatos.token)
      router.push('/dashboard')
    } else {
      errorMsg.value = 'Error al iniciar sesión. Respuesta inválida del servidor.'
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.mensaje || 'Credenciales inválidas. Verifica tu usuario y contraseña.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ── Página ────────────────────────── */
.login-page {
  min-height: 100vh;
  background: linear-gradient(145deg, #070d1b 0%, #0f172a 40%, #1a0f3a 75%, #0d1525 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  position: relative;
  overflow: hidden;
}

/* Formas decorativas de fondo */
.login-bg-shapes { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }

.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.12;
}
.bg-shape-1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, #133c55, transparent 70%);
  top: -120px; left: -100px;
  animation: float1 12s ease-in-out infinite;
}
.bg-shape-2 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, #10b981, transparent 70%);
  bottom: -80px; right: -80px;
  animation: float2 15s ease-in-out infinite;
}
.bg-shape-3 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, #3b82f6, transparent 70%);
  top: 50%; left: 55%;
  transform: translate(-50%, -50%);
  opacity: 0.06;
  animation: float3 18s ease-in-out infinite;
}

@keyframes float1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(30px, 20px) scale(1.05); }
}
@keyframes float2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-20px, -30px) scale(1.08); }
}
@keyframes float3 {
  0%, 100% { transform: translate(-50%, -50%) scale(1); }
  50% { transform: translate(-50%, -50%) scale(1.15); }
}

/* ── Tarjeta ────────────────────────── */
.login-card {
  position: relative;
  z-index: 1;
  background: rgba(255, 255, 255, 0.97);
  border-radius: 20px;
  padding: 2.25rem 2.125rem 2rem;
  width: 100%;
  max-width: 420px;
  box-shadow:
    0 32px 80px rgba(0, 0, 0, 0.5),
    0 0 0 1px rgba(255, 255, 255, 0.08),
    inset 0 1px 0 rgba(255, 255, 255, 0.6);
  border-top: 3px solid #3d85a9;
}

/* Brand */
.login-brand {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  margin-bottom: 1.625rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(203, 213, 225, 0.5);
}
.login-logo-wrapper {
  width: 52px;
  height: 52px;
  border-radius: 13px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(19, 60, 85, 0.08), rgba(61, 133, 169, 0.04));
  border: 1.5px solid rgba(19, 60, 85, 0.15);
  box-shadow: 0 4px 12px rgba(19, 60, 85, 0.15);
  flex-shrink: 0;
}
.login-logo-img {
  width: 44px;
  height: 44px;
  object-fit: contain;
  border-radius: 10px;
}
.login-brand-info { display: flex; flex-direction: column; }
.login-brand-name {
  font-size: 0.9375rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  line-height: 1.2;
}
.login-brand-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.6875rem;
  color: #64748b;
  margin-top: 0.15rem;
  font-weight: 500;
}

/* Heading */
.login-heading {
  margin-bottom: 1.5rem;
}
.login-heading h2 {
  font-size: 1.375rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.025em;
  margin-bottom: 0.2rem;
}
.login-heading p {
  font-size: 0.8125rem;
  color: #64748b;
  font-weight: 400;
}

/* Alert */
.alert {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.7rem 0.875rem;
  border-radius: 9px;
  font-size: 0.8rem;
  font-weight: 500;
  margin-bottom: 1.125rem;
}
.alert-error {
  background: rgba(220, 38, 38, 0.07);
  border: 1px solid rgba(220, 38, 38, 0.18);
  color: #b91c1c;
}
.alert-icon { display: flex; align-items: center; flex-shrink: 0; margin-top: 1px; }

/* Form */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.125rem;
}

.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  display: flex;
  align-items: center;
  gap: 0.3rem;
  letter-spacing: 0.01em;
}
.form-label svg { color: #94a3b8; }

/* Login Button */
.login-btn {
  height: 44px;
  background: linear-gradient(135deg, #2e6a8d 0%, #133c55 100%);
  color: #fff;
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  gap: 0.5rem;
  margin-top: 0.375rem;
  box-shadow: 0 4px 14px rgba(19, 60, 85, 0.4);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}
.login-btn:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgba(19, 60, 85, 0.5);
  transform: translateY(-1px);
}
.login-btn:active:not(:disabled) { transform: scale(0.98); }
.login-btn:disabled { opacity: 0.65; cursor: not-allowed; }

.login-footer {
  margin-top: 1.375rem;
  display: flex;
  justify-content: center;
}
.login-security-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.7rem;
  color: #94a3b8;
  font-weight: 500;
}
.login-security-badge svg { color: #10b981; }

@keyframes shake {
  0%,100% { transform: translateX(0); }
  20%,60%  { transform: translateX(-5px); }
  40%,80%  { transform: translateX(5px); }
}
.shake-enter-active { animation: shake 0.35s ease; }

.animate-fade-in { animation: cardIn 0.5s cubic-bezier(0.34, 1.2, 0.64, 1) forwards; }
@keyframes cardIn {
  from { opacity: 0; transform: translateY(20px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 480px) {
  .login-card { padding: 1.75rem 1.5rem 1.5rem; border-radius: 16px; }
}
</style>
