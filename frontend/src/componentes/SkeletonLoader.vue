<template>
  <!-- Skeleton Loader premium reutilizable -->
  <div class="skeleton-wrapper" :class="`skeleton-${tipo}`" aria-busy="true" aria-label="Cargando...">

    <!-- TIPO: tabla -->
    <template v-if="tipo === 'tabla'">
      <div class="skeleton-toolbar">
        <div class="skeleton-bar skeleton-bar-search"></div>
        <div class="skeleton-bar skeleton-bar-xs"></div>
      </div>
      <div v-for="i in filas" :key="i" class="skeleton-row">
        <div class="skeleton-cell skeleton-cell-sm"></div>
        <div class="skeleton-cell skeleton-cell-lg">
          <div class="skeleton-avatar"></div>
          <div class="skeleton-bar skeleton-bar-md"></div>
        </div>
        <div class="skeleton-cell skeleton-cell-md"><div class="skeleton-bar skeleton-bar-sm"></div></div>
        <div class="skeleton-cell skeleton-cell-md"><div class="skeleton-pill"></div></div>
        <div class="skeleton-cell skeleton-cell-xs">
          <div class="skeleton-actions">
            <div class="skeleton-btn-icon"></div>
            <div class="skeleton-btn-icon"></div>
          </div>
        </div>
      </div>
    </template>

    <!-- TIPO: cards (dashboard) -->
    <template v-else-if="tipo === 'cards'">
      <div class="skeleton-cards-grid">
        <div v-for="i in filas" :key="i" class="skeleton-card">
          <div class="skeleton-card-icon"></div>
          <div class="skeleton-card-content">
            <div class="skeleton-bar skeleton-bar-xs"></div>
            <div class="skeleton-bar skeleton-bar-lg"></div>
            <div class="skeleton-bar skeleton-bar-sm"></div>
          </div>
        </div>
      </div>
    </template>

    <!-- TIPO: form (modal) -->
    <template v-else-if="tipo === 'form'">
      <div class="skeleton-form">
        <div v-for="i in filas" :key="i" class="skeleton-form-group">
          <div class="skeleton-bar skeleton-bar-xs"></div>
          <div class="skeleton-bar skeleton-bar-full"></div>
        </div>
      </div>
    </template>

    <!-- TIPO: simple (línea) -->
    <template v-else>
      <div v-for="i in filas" :key="i" class="skeleton-line">
        <div class="skeleton-bar" :style="{ width: `${Math.random() * 30 + 60}%` }"></div>
      </div>
    </template>

  </div>
</template>

<script setup>
/**
 * Componente: SkeletonLoader
 * Indicador de carga premium. Reemplaza los spinners básicos.
 *
 * Props:
 * - tipo: 'tabla' | 'cards' | 'form' | 'simple'
 * - filas: número de filas/items a mostrar
 */
defineProps({
  tipo:  { type: String, default: 'tabla' },
  filas: { type: Number, default: 5 },
})
</script>

<style scoped>
/* Animación de pulso */
@keyframes shimmer {
  0%   { background-position: -600px 0; }
  100% { background-position: 600px 0; }
}

.skeleton-wrapper { width: 100%; }

/* Base de elementos skeleton */
.skeleton-bar,
.skeleton-avatar,
.skeleton-pill,
.skeleton-btn-icon,
.skeleton-card-icon,
.skeleton-bar-full {
  border-radius: 6px;
  background: linear-gradient(
    90deg,
    rgba(203, 213, 225, 0.4) 0%,
    rgba(226, 232, 240, 0.7) 40%,
    rgba(203, 213, 225, 0.4) 80%
  );
  background-size: 600px 100%;
  animation: shimmer 1.6s ease-in-out infinite;
}

/* Toolbar skeleton */
.skeleton-toolbar {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border);
}
.skeleton-bar-search { width: 280px; height: 36px; border-radius: var(--radius-sm); }

/* Row skeleton (tabla) */
.skeleton-row {
  display: flex;
  align-items: center;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  gap: 1rem;
  animation: rowIn 0.3s ease both;
}
@keyframes rowIn {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: translateY(0); }
}
.skeleton-row:nth-child(1) { animation-delay: 0.04s; }
.skeleton-row:nth-child(2) { animation-delay: 0.08s; }
.skeleton-row:nth-child(3) { animation-delay: 0.12s; }
.skeleton-row:nth-child(4) { animation-delay: 0.16s; }
.skeleton-row:nth-child(5) { animation-delay: 0.20s; }

.skeleton-cell { display: flex; align-items: center; gap: 0.625rem; }
.skeleton-cell-xs  { flex: 0 0 60px; }
.skeleton-cell-sm  { flex: 0 0 90px; }
.skeleton-cell-md  { flex: 1; }
.skeleton-cell-lg  { flex: 2; }

/* Bars */
.skeleton-bar     { height: 12px; }
.skeleton-bar-xs  { height: 10px; width: 70px; }
.skeleton-bar-sm  { height: 11px; width: 80px; }
.skeleton-bar-md  { height: 12px; width: 130px; }
.skeleton-bar-lg  { height: 22px; width: 80%; }
.skeleton-bar-full { height: 38px; width: 100%; }

/* Avatar skeleton */
.skeleton-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* Pill (badge) skeleton */
.skeleton-pill { height: 20px; width: 70px; border-radius: 99px; }

/* Actions */
.skeleton-actions { display: flex; gap: 0.375rem; }
.skeleton-btn-icon { width: 28px; height: 28px; border-radius: 6px; }

/* Cards skeleton (dashboard) */
.skeleton-cards-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
}
.skeleton-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  padding: 1.375rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}
.skeleton-card-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  flex-shrink: 0;
}
.skeleton-card-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
  padding-top: 0.25rem;
}

/* Form skeleton */
.skeleton-form { display: flex; flex-direction: column; gap: 1rem; }
.skeleton-form-group { display: flex; flex-direction: column; gap: 0.4rem; }

@media (max-width: 768px) {
  .skeleton-cards-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
