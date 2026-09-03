<template>
  <transition name="modal-fade">
    <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-glass detalle-modal">
        <div class="modal-header">
          <h3>Detalle de Planilla</h3>
          <button class="btn-close" @click="$emit('close')">&times;</button>
        </div>
        
        <div class="modal-body" v-if="planilla">
          <div class="empleado-info">
            <h4>{{ planilla.empleado?.nombre }} {{ planilla.empleado?.apellido }}</h4>
            <p><strong>Cargo:</strong> {{ planilla.empleado?.cargo?.nombre || '-' }}</p>
            <p><strong>Período:</strong> {{ mesNombre }} {{ planilla.anio }}</p>
            <span class="badge" :class="planilla.estado === 'Pendiente' ? 'badge-warning' : 'badge-success'">
              {{ planilla.estado }}
            </span>
          </div>

          <div class="desglose-section">
            <h5>Ingresos</h5>
            <div class="desglose-item">
              <span>Salario Base</span>
              <span>Bs. {{ formatNum(planilla.salario_base) }}</span>
            </div>
            <div class="desglose-item" v-if="planilla.horas_extra > 0">
              <span>Horas Extra ({{ planilla.horas_extra_cantidad }} hrs)</span>
              <span>Bs. {{ formatNum(planilla.horas_extra) }}</span>
            </div>
            <div class="desglose-item" v-if="planilla.bonos > 0">
              <span>Bonos</span>
              <span>Bs. {{ formatNum(planilla.bonos) }}</span>
            </div>
            <div class="desglose-total">
              <span>Total Ingresos</span>
              <span>Bs. {{ formatNum(totalIngresos) }}</span>
            </div>
          </div>

          <div class="desglose-section">
            <h5>Descuentos</h5>
            <div class="desglose-item" v-if="planilla.descuentos_automaticos > 0">
              <span>Descuentos Automáticos (Faltas/Retrasos)</span>
              <span class="text-danger">- Bs. {{ formatNum(planilla.descuentos_automaticos) }}</span>
            </div>
            <div class="desglose-item" v-if="planilla.adelantos_aplicados > 0">
              <span>Adelantos Aplicados</span>
              <span class="text-danger">- Bs. {{ formatNum(planilla.adelantos_aplicados) }}</span>
            </div>
            <div v-for="desc in (planilla.descuentosManuales || planilla.descuentos_manuales || [])" :key="desc.id_descuento" class="desglose-item">
              <span>{{ desc.descripcion }}</span>
              <span class="text-danger">- Bs. {{ formatNum(desc.monto) }}</span>
            </div>
            <div class="desglose-total text-danger">
              <span>Total Descuentos</span>
              <span>- Bs. {{ formatNum(planilla.descuentos) }}</span>
            </div>
          </div>

          <div class="liquido-pagable">
            <span>Líquido Pagable</span>
            <strong>Bs. {{ formatNum(planilla.salario_total) }}</strong>
          </div>
        </div>

        <div class="modal-body text-center" v-else-if="cargando">
          <span class="spinner-small"></span> Cargando detalle...
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-submit" @click="$emit('close')">Cerrar</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: Boolean,
  planilla: Object,
  cargando: Boolean
})

defineEmits(['close'])

const formatNum = (n) => Number(n || 0).toLocaleString('es-BO', { minimumFractionDigits: 2 })

const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const mesNombre = computed(() => {
  if (!props.planilla) return ''
  return meses[props.planilla.mes - 1] || props.planilla.mes
})

const totalIngresos = computed(() => {
  if (!props.planilla) return 0
  return Number(props.planilla.salario_base || 0) + Number(props.planilla.horas_extra || 0) + Number(props.planilla.bonos || 0)
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(22, 60, 87, 0.6);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.modal-glass {
  background: #ffffff;
  border: none;
  border-radius: 1rem;
  width: 100%;
  max-width: 500px;
  color: #334155;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
}
.btn-close {
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  line-height: 1;
}
.btn-close:hover { color: #0f172a; }
.modal-body {
  padding: 1.5rem;
  max-height: 70vh;
  overflow-y: auto;
}
.empleado-info {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  border: 1px solid #e2e8f0;
}
.empleado-info h4 { margin: 0 0 0.5rem 0; color: #1e293b; font-size: 1.1rem; }
.empleado-info p { margin: 0.25rem 0; font-size: 0.9rem; color: #475569; }
.badge {
  display: inline-block;
  padding: 0.25em 0.6em;
  font-size: 75%;
  font-weight: 700;
  border-radius: 0.25rem;
  margin-top: 0.5rem;
}
.badge-warning { background-color: #fef3c7; color: #b45309; }
.badge-success { background-color: #d1fae5; color: #065f46; }

.desglose-section {
  margin-bottom: 1.5rem;
}
.desglose-section h5 {
  margin: 0 0 0.75rem 0;
  font-size: 1rem;
  color: #334155;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 0.25rem;
}
.desglose-item {
  display: flex;
  justify-content: space-between;
  padding: 0.25rem 0;
  font-size: 0.9rem;
  color: #475569;
}
.desglose-total {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  border-top: 1px dashed #cbd5e1;
  margin-top: 0.25rem;
}
.text-danger { color: #dc2626 !important; }
.liquido-pagable {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #ecfdf5;
  border: 1px solid #10b981;
  border-radius: 0.5rem;
  margin-top: 1.5rem;
  font-size: 1.1rem;
  color: #065f46;
}
.liquido-pagable strong {
  font-size: 1.25rem;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}
.btn-submit {
  background-color: #163C57;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  cursor: pointer;
  font-weight: 600;
  transition: background-color 0.2s;
}
.btn-submit:hover { background-color: #1a4a6b; }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.95); }
</style>
