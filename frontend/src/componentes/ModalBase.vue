<template>
  <!-- Modal reutilizable con teleport al body -->
  <teleport to="body">
    <transition name="modal-fade">
      <div
        v-if="visible"
        class="modal-overlay"
        @click.self="$emit('cerrar')"
        role="dialog"
        :aria-label="titulo"
      >
        <div class="modal-caja animate-modal-in" :class="claseModal">
          <!-- Cabecera -->
          <div class="modal-encabezado">
            <div class="modal-titulo-grupo">
              <slot name="icono"></slot>
              <h3 class="modal-titulo">{{ titulo }}</h3>
            </div>
            <button class="modal-cerrar" @click="$emit('cerrar')" aria-label="Cerrar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>

          <!-- Cuerpo -->
          <div class="modal-cuerpo">
            <slot></slot>
          </div>

          <!-- Pie (botones de acción) -->
          <div class="modal-pie" v-if="$slots.acciones">
            <slot name="acciones"></slot>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
/**
 * Componente: ModalBase
 * Modal reutilizable premium con header, body y footer mediante slots.
 *
 * Props:
 * - visible: boolean — controla si el modal está abierto
 * - titulo: string — título del modal
 * - tamano: 'sm' | 'md' | 'lg' | 'xl' — tamaño del modal (default: 'md')
 *
 * Emits:
 * - cerrar — cuando se hace clic en X o en el overlay
 */
import { computed } from 'vue'

const props = defineProps({
  visible: { type: Boolean, required: true },
  titulo:  { type: String,  default: '' },
  tamano:  { type: String,  default: 'md' },
})

defineEmits(['cerrar'])

const claseModal = computed(() => {
  const tamanos = { sm: 'modal-sm', md: '', lg: 'modal-lg', xl: 'modal-xl' }
  return tamanos[props.tamano] ?? ''
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(10, 15, 30, 0.6);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-caja {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-top: 3px solid var(--primary-500);
  border-radius: var(--radius-xl);
  box-shadow:
    0 24px 64px rgba(0, 0, 0, 0.3),
    0 0 0 1px rgba(124, 58, 237, 0.05);
  width: 100%;
  max-width: 540px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.modal-sm { max-width: 400px; }
.modal-lg { max-width: 720px; }
.modal-xl { max-width: 920px; }

.modal-encabezado {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
  background: linear-gradient(to bottom, rgba(124, 58, 237, 0.02), transparent);
}
.modal-titulo-grupo {
  display: flex;
  align-items: center;
  gap: 0.625rem;
}
.modal-titulo {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  letter-spacing: -0.01em;
}
.modal-cerrar {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  border: 1px solid var(--border-strong);
  background: none;
  cursor: pointer;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
  flex-shrink: 0;
}
.modal-cerrar:hover {
  background: rgba(239, 68, 68, 0.08);
  color: var(--danger-500);
  border-color: rgba(239, 68, 68, 0.25);
}

.modal-cuerpo {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}
.modal-pie {
  display: flex;
  justify-content: flex-end;
  gap: 0.625rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border);
  flex-shrink: 0;
  background: var(--bg-muted);
}

/* Animaciones */
.modal-fade-enter-active { transition: opacity 0.22s ease; }
.modal-fade-leave-active { transition: opacity 0.15s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

.animate-modal-in {
  animation: modalFadeUp 0.28s cubic-bezier(0.34, 1.3, 0.64, 1) forwards;
}
@keyframes modalFadeUp {
  from { opacity: 0; transform: translateY(16px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
