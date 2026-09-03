<template>
  <button 
    class="btn btn-secondary" 
    style="display: inline-flex; align-items: center; gap: 0.5rem;" 
    :disabled="cargando" 
    @click="$emit('actualizar')"
  >
    <svg 
      v-if="!cargando" 
      viewBox="0 0 24 24" 
      fill="none" 
      stroke="currentColor" 
      stroke-width="2.5" 
      stroke-linecap="round" 
      stroke-linejoin="round" 
      width="16" 
      height="16"
    >
      <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
      <path d="M3 3v5h5"/>
    </svg>
    <span 
      v-else 
      class="spinner spinner-dark" 
      style="width: 14px; height: 14px; border-width: 2px;"
    ></span>
    {{ cargando ? 'Actualizando...' : 'Actualizar' }}
  </button>
</template>

<script setup>
defineProps({
  cargando: {
    type: Boolean,
    default: false
  }
})
defineEmits(['actualizar'])
</script>
