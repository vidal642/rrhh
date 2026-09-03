<template>
  <!-- Insignia de Estado -->
  <span class="insignia" :class="claseEstado">
    <span class="insignia-punto"></span>
    {{ etiqueta || estado }}
  </span>
</template>

<script setup>
/**
 * Componente: InsigniaEstado
 * Badge de estado reutilizable con colores semánticos y punto indicador.
 *
 * Props:
 * - estado: string — valor del estado ('Activo', 'Pendiente', 'Pagado', etc.)
 * - mapa: Object — mapeo personalizado de estado → clase CSS
 * - etiqueta: string — texto a mostrar (si es distinto del estado)
 */
const props = defineProps({
  estado:   { type: String, default: '' },
  mapa:     { type: Object, default: null },
  etiqueta: { type: String, default: null },
})

// Mapeos predefinidos por contexto
const MAPAS = {
  // Empleados
  Activo:      'insignia-success',
  Inactivo:    'insignia-danger',
  Vacaciones:  'insignia-info',
  Suspendido:  'insignia-warning',
  Retirado:    'insignia-gray',
  // Asistencia
  Presente:    'insignia-success',
  Falta:       'insignia-danger',
  Retraso:     'insignia-warning',
  Permiso:     'insignia-info',
  'Vacación':  'insignia-info',
  // Planillas / Ausencias
  Pendiente:   'insignia-warning',
  Pagado:      'insignia-success',
  Aprobado:    'insignia-success',
  Aplicado:    'insignia-success',
  Anulado:     'insignia-gray',
  Rechazado:   'insignia-danger',
  // Tipos de ausencia
  'Baja médica': 'insignia-danger',
}

const claseEstado = computed(() => {
  const mapa = props.mapa ?? MAPAS
  return mapa[props.estado] ?? 'insignia-gray'
})
</script>

<script>
import { computed } from 'vue'
export default { name: 'InsigniaEstado' }
</script>

<style scoped>
.insignia {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.6875rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 99px;
  letter-spacing: 0.04em;
  white-space: nowrap;
  text-transform: uppercase;
  border: 1px solid transparent;
  transition: all 0.15s ease;
}

.insignia-punto {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
  opacity: 0.85;
}

.insignia-success {
  background: rgba(16, 185, 129, 0.1);
  color: #047857;
  border-color: rgba(16, 185, 129, 0.22);
}
.insignia-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border-color: rgba(239, 68, 68, 0.2);
}
.insignia-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #b45309;
  border-color: rgba(245, 158, 11, 0.22);
}
.insignia-info {
  background: rgba(14, 165, 233, 0.1);
  color: var(--info-600);
  border-color: rgba(14, 165, 233, 0.2);
}
.insignia-gray {
  background: rgba(100, 116, 139, 0.08);
  color: #475569;
  border-color: rgba(100, 116, 139, 0.18);
}
</style>
