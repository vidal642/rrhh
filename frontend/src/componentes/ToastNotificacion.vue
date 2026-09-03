<template>
  <!-- Contenedor global de notificaciones toast -->
  <div class="notificaciones-contenedor">
    <transition-group name="toast-slide" tag="div" class="toast-stack">
      <div
        v-for="n in notificaciones"
        :key="n.id"
        class="toast-item"
        :class="`toast-${n.tipo}`"
        @click="cerrar(n.id)"
        role="alert"
      >
        <div class="toast-accent"></div>
        <span class="toast-icono">
          <!-- Éxito -->
          <svg v-if="n.tipo === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          <!-- Error -->
          <svg v-else-if="n.tipo === 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
          <!-- Advertencia -->
          <svg v-else-if="n.tipo === 'warning'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <!-- Info -->
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </span>
        <span class="toast-mensaje">{{ n.mensaje }}</span>
        <button class="toast-close" @click.stop="cerrar(n.id)" aria-label="Cerrar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="12" height="12">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
/**
 * Componente: ToastNotificacion
 * Sistema global de notificaciones toast.
 * Se coloca UNA VEZ en el componente raíz (Layout.vue).
 */
import { usarNotificacion } from '../composables/usarNotificacion'

const { notificaciones, cerrar } = usarNotificacion()
</script>

<style scoped>
.notificaciones-contenedor {
  position: fixed;
  top: 1.25rem;
  right: 1.25rem;
  z-index: 9999;
  pointer-events: none;
}

.toast-stack {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.toast-item {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.75rem 1rem 0.75rem 0.875rem;
  background: var(--bg-surface);
  border-radius: var(--radius-md);
  box-shadow:
    0 8px 24px rgba(0, 0, 0, 0.12),
    0 2px 8px rgba(0, 0, 0, 0.08),
    0 0 0 1px var(--border);
  min-width: 270px;
  max-width: 380px;
  cursor: pointer;
  pointer-events: all;
  position: relative;
  overflow: hidden;
}

/* Franja de acento izquierda */
.toast-accent {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  border-radius: 0;
}

.toast-icono {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
}
.toast-icono svg { width: 18px; height: 18px; }
.toast-mensaje {
  flex: 1;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--text-primary);
  line-height: 1.4;
}
.toast-close {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  padding: 0.2rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  opacity: 0.6;
  transition: opacity 0.15s;
  flex-shrink: 0;
}
.toast-close:hover { opacity: 1; }

/* Variantes */
.toast-success .toast-accent { background: var(--secondary-500); }
.toast-success .toast-icono  { color: var(--secondary-500); }

.toast-error .toast-accent   { background: var(--danger-500); }
.toast-error .toast-icono    { color: var(--danger-500); }

.toast-warning .toast-accent { background: var(--warning-500); }
.toast-warning .toast-icono  { color: var(--warning-500); }

.toast-info .toast-accent    { background: var(--info-500, #3b82f6); }
.toast-info .toast-icono     { color: var(--info-500, #3b82f6); }

/* Animaciones */
.toast-slide-enter-active { transition: all 0.32s cubic-bezier(0.34, 1.4, 0.64, 1); }
.toast-slide-leave-active  { transition: all 0.2s ease; }
.toast-slide-enter-from { opacity: 0; transform: translateX(60px); }
.toast-slide-leave-to   { opacity: 0; transform: translateX(60px); }
.toast-slide-move       { transition: transform 0.2s ease; }
</style>
