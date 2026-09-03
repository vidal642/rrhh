<template>
  <div class="report-table-wrapper">
    <table class="report-table">
      <thead>
        <tr>
          <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in data" :key="index">
          <td v-for="col in columns" :key="col.key">
            <!-- Render custom slot if provided, else generic formatting -->
            <slot :name="col.key" :item="row" :value="row[col.key]">
              {{ formatValue(row[col.key], col.type) }}
            </slot>
          </td>
        </tr>
        <tr v-if="data.length === 0">
          <td :colspan="columns.length" class="text-center empty-state py-4">
            No se encontraron registros para los filtros seleccionados.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  columns: {
    type: Array,
    required: true,
    // Formato: [{ key: 'nombre', label: 'Nombre Completo', type: 'text|money|badge' }]
  },
  data: {
    type: Array,
    required: true
  }
})

const formatValue = (value, type) => {
  if (value === null || value === undefined) return '—'
  if (type === 'money') {
    return 'Bs. ' + parseFloat(value).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  }
  return value
}
</script>

<style scoped>
.report-table-wrapper {
  width: 100%;
  overflow-x: auto;
  margin-top: 0.5rem;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
}

.report-table th {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  padding: 0.625rem 0.875rem;
  font-size: 0.725rem;
  font-weight: 700;
  text-transform: uppercase;
  text-align: left;
}

.report-table td {
  border: 1px solid #e2e8f0;
  padding: 0.625rem 0.875rem;
  font-size: 0.775rem;
  color: #334155;
  vertical-align: middle;
}

.report-table tbody tr:nth-child(even) {
  background: #f8fafc;
}

.empty-state {
  color: #64748b;
  font-style: italic;
}
</style>
