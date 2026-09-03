<template>
  <div class="tarjeta-metrica" :class="`tarjeta-${color}`">
    <div class="metrica-icono" :class="colorClase">
      <slot name="icono"></slot>
    </div>

    <div class="metrica-contenido">
      <span class="metrica-titulo">{{ titulo }}</span>
      <div class="metrica-valor-grupo">
        <span class="metrica-valor">{{ valor }}</span>
        <span
          v-if="tendencia !== null"
          class="metrica-tendencia"
          :class="tendencia > 0 ? 'tendencia-positiva' : 'tendencia-negativa'"
        >
          <svg v-if="tendencia > 0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/>
          </svg>
          {{ Math.abs(tendencia) }}%
        </span>
      </div>
      <p v-if="subtexto" class="metrica-subtexto">{{ subtexto }}</p>
    </div>
  </div>
</template>

<script setup>
/**
 * Componente: TarjetaMetrica
 * Tarjeta premium para mostrar estadísticas clave en el dashboard.
 */
import { computed } from 'vue'

const props = defineProps({
  titulo:    { type: String, required: true },
  valor:     { type: [String, Number], required: true },
  tendencia: { type: Number, default: null },
  color:     { type: String, default: 'primario' }, // primario, exito, peligro, advertencia
  subtexto:  { type: String, default: null },
})

const colorClase = computed(() => `icono-${props.color}`)
</script>

<style scoped>
.tarjeta-metrica {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  padding: 1.375rem 1.375rem 1.25rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  box-shadow: var(--shadow-card);
}

.tarjeta-metrica:hover {
  border-color: var(--border-strong);
}

/* Borde superior por variante */
.tarjeta-primario   { border-top: 3px solid var(--primary-500); }
.tarjeta-exito      { border-top: 3px solid var(--secondary-500); }
.tarjeta-peligro    { border-top: 3px solid var(--danger-500); }
.tarjeta-advertencia{ border-top: 3px solid var(--warning-500); }



/* Ícono */
.metrica-icono {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.25s ease;
}
.metrica-icono svg { width: 22px; height: 22px; }

.icono-primario    {
  background: rgba(124, 58, 237, 0.1);
  color: var(--primary-500);
}
.icono-exito       {
  background: rgba(16, 185, 129, 0.1);
  color: var(--secondary-500);
}
.icono-peligro     {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger-500);
}
.icono-advertencia {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning-500);
}

/* Contenido */
.metrica-contenido {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.metrica-titulo {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 0.375rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.metrica-valor-grupo {
  display: flex;
  align-items: baseline;
  gap: 0.625rem;
  flex-wrap: wrap;
}
.metrica-valor {
  font-size: 1.875rem;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1;
  letter-spacing: -0.03em;
  font-variant-numeric: tabular-nums;
}
.metrica-subtexto {
  font-size: 0.7rem;
  color: var(--text-muted);
  margin-top: 0.375rem;
  font-weight: 400;
  line-height: 1.3;
}

/* Tendencia */
.metrica-tendencia {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2rem 0.45rem;
  border-radius: 99px;
}
.metrica-tendencia svg { width: 11px; height: 11px; }
.tendencia-positiva { background: rgba(16, 185, 129, 0.12); color: var(--secondary-600); }
.tendencia-negativa { background: rgba(239, 68, 68, 0.12);  color: var(--danger-600); }
</style>
