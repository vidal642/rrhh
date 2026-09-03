<template>
  <teleport to="body">
    <transition name="confirm-fade">
      <div v-if="state.visible" class="confirm-overlay" @click.self="cancelar" role="dialog" aria-modal="true">
        <div class="confirm-box animate-pop">

          <!-- Ícono de advertencia -->
          <div class="confirm-icon-wrap">
            <div class="confirm-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
            </div>
          </div>

          <!-- Contenido -->
          <div class="confirm-content">
            <h3 class="confirm-title">{{ state.titulo }}</h3>
            <p class="confirm-message">{{ state.mensaje }}</p>
          </div>

          <!-- Acciones -->
          <div class="confirm-actions">
            <button class="btn-confirm btn-confirm-cancel" @click="cancelar" id="confirm-cancel-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
              {{ state.textoCancelar }}
            </button>
            <button class="btn-confirm btn-confirm-danger" @click="aceptar" id="confirm-accept-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/>
              </svg>
              {{ state.textoConfirmar }}
            </button>
          </div>

        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { usarConfirmacion } from '../composables/usarConfirmacion'

const { state, aceptar, cancelar } = usarConfirmacion()

const handleKeydown = (e) => {
  if (e.key === 'Escape' && state.value.visible) cancelar()
  if (e.key === 'Enter'  && state.value.visible) aceptar()
}

onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
</script>

<style scoped>
.confirm-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(10, 15, 30, 0.65);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.confirm-box {
  background: var(--bg-surface, #ffffff);
  border: 1px solid var(--border, #e2e8f0);
  border-top: 3px solid var(--danger-500, #ef4444);
  border-radius: 18px;
  box-shadow:
    0 25px 60px rgba(0, 0, 0, 0.3),
    0 0 0 1px rgba(239, 68, 68, 0.06);
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  text-align: center;
  padding: 2rem 1.75rem 1.625rem;
}

/* Ícono */
.confirm-icon-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 1.25rem;
}
.confirm-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.08);
  border: 2px solid rgba(239, 68, 68, 0.15);
  color: var(--danger-500, #ef4444);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: iconPulse 2s ease-in-out infinite;
}
.confirm-icon svg { width: 30px; height: 30px; }

@keyframes iconPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
  50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.06); }
}

/* Contenido */
.confirm-content { margin-bottom: 1.75rem; }
.confirm-title {
  font-size: 1.1875rem;
  font-weight: 800;
  color: var(--text-primary, #111827);
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}
.confirm-message {
  font-size: 0.875rem;
  color: var(--text-secondary, #64748b);
  line-height: 1.55;
}

/* Acciones */
.confirm-actions {
  display: flex;
  gap: 0.75rem;
}
.btn-confirm {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.7rem 1rem;
  border-radius: 10px;
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  border: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  outline: none;
}
.btn-confirm:active { transform: scale(0.97); }

.btn-confirm-cancel {
  background: var(--bg-muted, #f8fafc);
  color: var(--text-secondary, #475569);
  border: 1.5px solid var(--border-strong, #cbd5e1);
}
.btn-confirm-cancel:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  color: var(--text-primary, #111827);
}

.btn-confirm-danger {
  background: var(--danger-600, #dc2626);
  color: #fff;
  box-shadow: 0 3px 10px rgba(220, 38, 38, 0.3);
}
.btn-confirm-danger:hover {
  background: var(--danger-500, #ef4444);
  box-shadow: 0 5px 16px rgba(220, 38, 38, 0.4);
  transform: translateY(-1px);
}

/* Animaciones */
.confirm-fade-enter-active { transition: opacity 0.22s ease; }
.confirm-fade-leave-active { transition: opacity 0.18s ease; }
.confirm-fade-enter-from, .confirm-fade-leave-to { opacity: 0; }

.animate-pop {
  animation: popIn 0.3s cubic-bezier(0.34, 1.3, 0.64, 1) forwards;
}
@keyframes popIn {
  0%   { opacity: 0; transform: scale(0.88) translateY(16px); }
  100% { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
