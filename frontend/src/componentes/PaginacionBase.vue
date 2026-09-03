<template>
  <!-- Paginación con números de página -->
  <div class="paginacion" v-if="totalPaginas > 1">
    <span class="paginacion-info">
      Mostrando <strong>{{ inicio }}–{{ fin }}</strong> de <strong>{{ total }}</strong>
    </span>
    <div class="paginacion-controles">
      <!-- Anterior -->
      <button
        class="pag-btn"
        :disabled="paginaActual === 1"
        @click="$emit('cambiar', paginaActual - 1)"
        aria-label="Página anterior"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
      </button>

      <!-- Números -->
      <template v-for="p in paginas" :key="p">
        <span v-if="p === '...'" class="pag-dots">···</span>
        <button
          v-else
          class="pag-btn"
          :class="{ 'pag-btn--active': p === paginaActual }"
          @click="$emit('cambiar', p)"
        >{{ p }}</button>
      </template>

      <!-- Siguiente -->
      <button
        class="pag-btn"
        :disabled="paginaActual === totalPaginas"
        @click="$emit('cambiar', paginaActual + 1)"
        aria-label="Siguiente página"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
/**
 * Componente: PaginacionBase
 * Paginación con números de página premium.
 *
 * Props:
 * - paginaActual: número de la página actual (1-indexed)
 * - totalPaginas: total de páginas
 * - total: total de elementos
 * - porPagina: elementos por página
 *
 * Emits:
 * - cambiar(pagina: number) — cuando el usuario cambia de página
 */
import { computed } from 'vue'

const props = defineProps({
  paginaActual: { type: Number, required: true },
  totalPaginas: { type: Number, required: true },
  total:        { type: Number, required: true },
  porPagina:    { type: Number, default: 10 },
})

defineEmits(['cambiar'])

const inicio = computed(() => Math.min((props.paginaActual - 1) * props.porPagina + 1, props.total))
const fin    = computed(() => Math.min(props.paginaActual * props.porPagina, props.total))

// Lógica de rango con elipsis
const paginas = computed(() => {
  const total = props.totalPaginas
  const cur   = props.paginaActual
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const pages = [1]
  if (cur > 3) pages.push('...')
  const start = Math.max(2, cur - 1)
  const end   = Math.min(total - 1, cur + 1)
  for (let i = start; i <= end; i++) pages.push(i)
  if (cur < total - 2) pages.push('...')
  pages.push(total)
  return pages
})
</script>

<style scoped>
.paginacion {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1.5rem;
  border-top: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 0.875rem;
  background: var(--bg-muted);
  border-radius: 0 0 var(--radius-xl) var(--radius-xl);
}

.paginacion-info {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 400;
}
.paginacion-info strong {
  color: var(--text-secondary);
  font-weight: 600;
}

.paginacion-controles {
  display: flex;
  align-items: center;
  gap: 0.2rem;
}

.pag-btn {
  min-width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.5rem;
  border: 1.5px solid var(--border-strong);
  border-radius: var(--radius-sm);
  background: var(--bg-surface);
  color: var(--text-secondary);
  font-size: 0.8125rem;
  font-weight: 600;
  font-family: 'Inter', sans-serif;
  cursor: pointer;
  transition: all 0.15s ease;
  line-height: 1;
}
.pag-btn:hover:not(:disabled):not(.pag-btn--active) {
  background: var(--bg-hover);
  border-color: var(--primary-300);
  color: var(--primary-600);
}
.pag-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}
.pag-btn--active {
  background: var(--primary-500);
  border-color: var(--primary-500);
  color: #fff;
  box-shadow: 0 2px 8px rgba(124, 58, 237, 0.3);
}

.pag-dots {
  font-size: 0.875rem;
  color: var(--text-muted);
  padding: 0 0.25rem;
  line-height: 32px;
  user-select: none;
}

@media (max-width: 480px) {
  .paginacion { flex-direction: column; align-items: flex-start; }
}
</style>
