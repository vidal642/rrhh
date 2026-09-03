<template>
  <div class="page-wrapper">
    <div class="page-top">
      <div>
        <h1 class="page-title">Planillas y Nómina</h1>
        <p class="page-subtitle">Cálculo automático de salarios desde asistencia · Bolivianos (Bs.)</p>
      </div>
    </div>

    <div class="custom-tabs">
      <button class="tab-item" :class="{ active: tabActivo === 'planillas' }" @click="tabActivo = 'planillas'">Planillas</button>
      <button class="tab-item" :class="{ active: tabActivo === 'adelantos' }" @click="tabActivo = 'adelantos'">Adelantos</button>
    </div>

    <div v-show="tabActivo === 'planillas'" class="tab-content">
      <div class="page-actions" style="margin-bottom: 1.25rem; justify-content: flex-end; display: flex; gap: 0.625rem;">
        <BotonActualizar :cargando="cargando" @actualizar="cargarDatos(paginaActual)" />
        <button class="btn btn-auto" @click="abrirModalGenerar" :disabled="generando">
          <svg v-if="generando" class="spinner-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
          </svg>
          {{ generando ? 'Generando...' : 'Generar Automática' }}
        </button>
        <button class="btn btn-primary" @click="abrirModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Registrar Planilla
        </button>
      </div>

    <div class="glass-panel">
      <div class="table-toolbar">
        <div class="input-wrapper search-input">
          <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
          <input type="text" class="input-control" placeholder="Buscar empleado..." v-model="busqueda" />
        </div>
        <select class="input-control" style="width:190px" v-model="filtroMesAnio">
          <option v-for="m in opcionesMeses" :key="m.val" :value="m.val">{{ m.label }}</option>
        </select>
        <span class="text-muted text-sm">{{ totalElementos }} planillas</span>
      </div>

      <div class="data-table-wrapper">
        <SkeletonLoader v-if="cargando" tipo="tabla" :filas="6" />

        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Empleado</th>
              <th>Cargo</th>
              <th>Salario Base</th>
              <th>Horas Extra</th>
              <th>Bonos</th>
              <th>Descuentos</th>
              <th>Total Neto</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="planillas.length===0"><td colspan="9" class="empty-row">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="36" height="36" style="opacity:.3"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <span>No hay planillas para este mes</span>
            </td></tr>
            <template v-else>
              <tr v-for="row in planillas" :key="row.id_planilla">
                <td>
                  <div class="emp-cell">
                    <div class="emp-avatar">{{ iniciales(row.empleado) }}</div>
                    <div>
                      <div class="font-semibold">{{ row.empleado?.nombre }} {{ row.empleado?.apellido }}</div>
                      <div class="text-xs text-muted">{{ row.empleado?.departamento?.nombre || 'Sin departamento' }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-sm">{{ row.empleado?.cargo?.nombre || '—' }}</td>
                <td><span class="salary-badge">Bs. {{ fmt(row.salario_base) }}</span></td>
                <td class="text-sm" style="color:#7c3aed">
                  <span v-if="row.horas_extra_cantidad">{{ row.horas_extra_cantidad }}h → Bs. {{ fmt(row.horas_extra) }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-sm text-secondary-color">{{ row.bonos > 0 ? `+ Bs. ${fmt(row.bonos)}` : '—' }}</td>
                <td class="text-sm" style="color:var(--danger-600)">{{ row.descuentos > 0 ? `- Bs. ${fmt(row.descuentos)}` : '—' }}</td>
                <td><span class="net-salary">Bs. {{ fmt(row.salario_total) }}</span></td>
                <td><InsigniaEstado :estado="row.estado" /></td>
                <td>
                  <div class="action-btns">
                    <button class="btn-icon text-success" v-if="row.estado === 'Pendiente'" @click="marcarPagado(row)" title="Marcar como pagado">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                    <button class="btn-icon" @click="abrirModal(row)" title="Editar" :disabled="row.estado === 'Pagado'">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn-icon danger" @click="eliminarRegistro(row)" title="Eliminar">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <PaginacionBase
        v-if="!cargando && totalPaginas > 1"
        :pagina-actual="paginaActual"
        :total-paginas="totalPaginas"
        :total="totalElementos"
        :por-pagina="porPagina"
        @cambiar="irAPagina"
      />
    </div>
    </div>

    <div v-show="tabActivo === 'adelantos'" class="tab-content">
      <div class="page-actions" style="margin-bottom: 1.25rem; justify-content: flex-end; display: flex; gap: 0.625rem;">
        <BotonActualizar :cargando="cargandoAdelantos" @actualizar="cargarAdelantosGlobales" />
        <button class="btn btn-primary" @click="abrirModalAdelantoGlobal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Registrar Adelanto
        </button>
      </div>

      <div class="glass-panel">
        <div class="table-toolbar">
          <div class="input-wrapper search-input">
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
            <input type="text" class="input-control" placeholder="Buscar empleado..." v-model="busquedaAdelantos" />
          </div>
          <select class="input-control" style="width:190px" v-model="filtroEstadoAdelanto">
            <option value="">Todos los Estados</option>
            <option value="Pendiente">Pendiente</option>
            <option value="Aprobado">Aprobado</option>
            <option value="Rechazado">Rechazado</option>
            <option value="Aplicado">Aplicado</option>
          </select>
          <span class="text-muted text-sm">{{ filtradosAdelantos.length }} registros</span>
        </div>

        <div class="data-table-wrapper">
          <SkeletonLoader v-if="cargandoAdelantos" tipo="tabla" :filas="5" />

          <table v-else class="data-table">
            <thead>
              <tr>
                <th>Empleado</th>
                <th>Monto</th>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filtradosAdelantos.length===0"><td colspan="6" class="empty-row">No se encontraron adelantos.</td></tr>
              <template v-else>
                <tr v-for="row in elementosAdelantosPaginados" :key="row.id">
                  <td>
                    <div class="emp-cell">
                      <div class="emp-avatar">{{ iniciales(row.empleado) }}</div>
                      <div>
                        <div class="font-semibold">{{ row.empleado?.nombre }} {{ row.empleado?.apellido }}</div>
                        <div class="text-xs text-muted">{{ row.empleado?.cargo?.nombre || 'Sin cargo' }}</div>
                      </div>
                    </div>
                  </td>
                  <td><span class="net-salary text-purple">Bs. {{ fmt(row.monto) }}</span></td>
                  <td class="text-sm">{{ row.fecha }}</td>
                  <td class="text-sm text-muted">{{ row.descripcion || '—' }}</td>
                  <td>
                     <span class="badge" :class="{
                       'badge-warning': row.estado?.toUpperCase() === 'PENDIENTE',
                       'badge-info':    row.estado?.toUpperCase() === 'APROBADO',
                       'badge-danger':  row.estado?.toUpperCase() === 'RECHAZADO',
                       'badge-success': row.estado?.toUpperCase() === 'APLICADO',
                     }">
                       {{ row.estado }}
                     </span>
                  </td>
                  <td>
                    <div class="action-btns">
                      <button v-if="row.estado?.toUpperCase() === 'PENDIENTE'" class="btn-icon text-success" @click="aprobarAdelanto(row)" title="Aprobar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                      </button>
                      <button v-if="row.estado?.toUpperCase() === 'PENDIENTE'" class="btn-icon text-danger" @click="abrirModalRechazo(row)" title="Rechazar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <PaginacionBase
          v-if="!cargandoAdelantos && filtradosAdelantos.length > 10"
          :pagina-actual="paginaActAdelanto"
          :total-paginas="totalPagAdelanto"
          :total="filtradosAdelantos.length"
          :por-pagina="10"
          @cambiar="irAPagAdelanto"
        />
      </div>
    </div>

    <ModalBase :visible="modalVisible" @cerrar="cerrarModal"
               :titulo="idEdicion ? 'Editar Planilla' : 'Nueva Planilla Inteligente'"
               tamano="lg">

      <template #icono>
        <div class="modal-icon-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
      </template>

      <div class="smart-form">

        <div class="form-section">
          <div class="form-section-header">
            <span class="section-num">1</span>
            <div>
              <div class="section-title">Selección</div>
              <div class="section-desc">Empleado y período de la planilla</div>
            </div>
          </div>
          <div class="form-grid-3">
            <div class="form-group col-span-full">
              <label class="form-label">Empleado <span class="required">*</span></label>
              <div class="autocomplete-wrapper">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <select v-model="formulario.id_empleado" class="input-control input-with-icon" :class="{'input-error': errors.id_empleado}"
                        :disabled="idEdicion !== null" @change="onEmpleadoCambia" id="select-empleado">
                  <option value="">— Seleccione un empleado —</option>
                  <option v-for="e in empleados" :key="e.id_empleado" :value="e.id_empleado">
                    {{ e.nombre }} {{ e.apellido }}  ·  {{ e.cargo?.nombre || 'Sin cargo' }}
                  </option>
                </select>
                <p v-if="errors.id_empleado" class="field-error">{{ errors.id_empleado }}</p>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Mes <span class="required">*</span></label>
              <select v-model="formulario.mes" class="input-control" :class="{'input-error': errors.mes}" @change="onPeriodoCambia" id="select-mes">
                <option v-for="m in mesesOpciones" :key="m.val" :value="m.val">{{ m.label }}</option>
              </select>
              <p v-if="errors.mes" class="field-error">{{ errors.mes }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Año <span class="required">*</span></label>
              <select v-model="formulario.anio" class="input-control" :class="{'input-error': errors.anio}" @change="onPeriodoCambia" id="select-anio">
                <option v-for="a in aniosOpciones" :key="a" :value="a">{{ a }}</option>
              </select>
              <p v-if="errors.anio" class="field-error">{{ errors.anio }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Salario Base (Bs.)</label>
              <input type="number" step="0.01" v-model.number="formulario.salario_base"
                     class="input-control" :class="{'input-error': errors.salario_base}" readonly style="background:rgba(79,70,229,.05); font-weight:700" />
              <p v-if="errors.salario_base" class="field-error">{{ errors.salario_base }}</p>
              <span class="form-hint">Cargado automáticamente del perfil</span>
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-header">
            <span class="section-num sec-auto">2</span>
            <div>
              <div class="section-title">Datos de Asistencia <span class="badge-auto">Automático</span></div>
              <div class="section-desc">Calculado desde los registros del módulo de asistencia</div>
            </div>
          </div>

          <div v-if="cargandoAsistencia" class="asist-loading">
            <div class="spinner spinner-dark"></div>
            <span>Consultando asistencia del período...</span>
          </div>

          <div v-else-if="!datosAsistencia && !formulario.id_empleado" class="asist-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28" style="opacity:.4"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
            <span>Seleccione un empleado para ver sus datos de asistencia</span>
          </div>

          <div v-else-if="datosAsistencia" class="asist-cards">
            <div class="asist-card" :class="tarjetaAsistClase(datosAsistencia.dias_trabajados, 20, 15)">
              <div class="asist-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              </div>
              <div class="asist-card-valor">{{ datosAsistencia.dias_trabajados }}</div>
              <div class="asist-card-label">Días trabajados</div>
            </div>
            <div class="asist-card asist-card-blue">
              <div class="asist-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              </div>
              <div class="asist-card-valor">{{ datosAsistencia.horas_trabajadas }}h</div>
              <div class="asist-card-label">Horas trabajadas</div>
            </div>
            <div class="asist-card" :class="datosAsistencia.horas_extra_cantidad > 0 ? 'asist-card-purple' : 'asist-card-neutral'">
              <div class="asist-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
              </div>
              <div class="asist-card-valor">{{ datosAsistencia.horas_extra_cantidad }}h</div>
              <div class="asist-card-label">Horas extra</div>
            </div>
            <div class="asist-card" :class="tarjetaFaltasClase(datosAsistencia.faltas)">
              <div class="asist-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              </div>
              <div class="asist-card-valor">{{ datosAsistencia.faltas }}</div>
              <div class="asist-card-label">Faltas</div>
            </div>
          </div>

          <div v-else-if="formulario.id_empleado && !cargandoAsistencia" class="asist-empty asist-empty-warn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>Sin registros de asistencia para este período. Se usarán los valores que ingreses manualmente.</span>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-header">
            <span class="section-num sec-edit">3</span>
            <div>
              <div class="section-title">Ajustes Manuales</div>
              <div class="section-desc">Bonos adicionales, descuentos manuales y adelantos de salario</div>
            </div>
          </div>
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="color:#16a34a"><polyline points="20 6 9 17 4 12"/></svg>
                Bonos (Bs.)
              </label>
              <input type="number" step="0.01" min="0" v-model.number="formulario.bonos"
                     class="input-control" :class="{'input-error': errors.bonos}" placeholder="0.00" id="input-bonos" @input="errors.bonos=''" />
              <p v-if="errors.bonos" class="field-error">{{ errors.bonos }}</p>
              <span class="form-hint">Bonos de productividad, antiguedad, etc.</span>
            </div>
            <div class="form-group">
              <label class="form-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="color:#dc2626"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                Descuentos y Adelantos
              </label>
              <div class="action-btns" style="margin-bottom:0.5rem">
                <button type="button" class="btn btn-secondary btn-sm" @click="abrirModalDesc">
                  + Añadir Descuento
                </button>
                <button type="button" class="btn btn-secondary btn-sm" @click="modalAdelantosVisible = true">
                  Adelantos ({{ adelantosPendientes.length }})
                </button>
              </div>
              
              <div class="asist-empty" style="padding:0.75rem" v-if="descuentosManuales.length === 0 && adelantosPendientes.length === 0">
                 Sin descuentos manuales ni adelantos.
              </div>
              <ul v-else class="desc-list">
                 <li v-for="(dm, i) in descuentosManuales" :key="'dm'+i" class="desc-item">
                    <div class="desc-info">
                      <span class="desc-label">{{ dm.descripcion }}</span>
                      <span class="text-xs text-muted">{{ dm.fecha }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.5rem">
                       <span class="desc-monto">- Bs. {{ fmt(dm.monto) }}</span>
                       <button type="button" class="btn-icon danger text-xs p-1" @click="eliminarDescManual(i)">✕</button>
                    </div>
                 </li>
                 <li v-for="ad in adelantosPendientes" :key="'ad'+ad.id" class="desc-item desc-adelanto">
                    <div class="desc-info">
                      <span class="desc-label">Adelanto: {{ ad.descripcion || 'Sin detalle' }}</span>
                      <span class="text-xs text-muted">{{ ad.fecha }}</span>
                    </div>
                    <span class="desc-monto text-purple">- Bs. {{ fmt(ad.monto) }}</span>
                 </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="form-section resumen-section">
          <div class="form-section-header">
            <span class="section-num sec-resumen">4</span>
            <div>
              <div class="section-title">Resumen de Pago</div>
              <div class="section-desc">Calculado en tiempo real</div>
            </div>
          </div>
          <div class="resumen-tabla">
            <div class="resumen-fila">
              <span class="resumen-concepto">Salario Base</span>
              <span class="resumen-monto">Bs. {{ fmt(formulario.salario_base) }}</span>
            </div>
            <div class="resumen-fila" v-if="montoHorasExtraCalc > 0">
              <span class="resumen-concepto">
                Horas Extra
                <small v-if="datosAsistencia">({{ datosAsistencia.horas_extra_cantidad }}h × {{ fmt(datosAsistencia.pago_por_hora) }} × 1.5)</small>
              </span>
              <span class="resumen-monto resumen-extra">+ Bs. {{ fmt(montoHorasExtraCalc) }}</span>
            </div>
            <div class="resumen-fila" v-if="formulario.bonos > 0">
              <span class="resumen-concepto">Bonos</span>
              <span class="resumen-monto resumen-bono">+ Bs. {{ fmt(formulario.bonos) }}</span>
            </div>
            <div class="resumen-fila" v-if="montoDescuentoAutoCalc > 0">
              <span class="resumen-concepto">Descuentos por Faltas <small v-if="datosAsistencia">({{ datosAsistencia.faltas }} faltas)</small></span>
              <span class="resumen-monto resumen-descuento">− Bs. {{ fmt(montoDescuentoAutoCalc) }}</span>
            </div>
            <div class="resumen-fila" v-if="montoAdelantosCalc > 0">
              <span class="resumen-concepto">Adelantos Aplicados</span>
              <span class="resumen-monto resumen-descuento">− Bs. {{ fmt(montoAdelantosCalc) }}</span>
            </div>
            <div class="resumen-fila" v-if="montoDescManualesCalc > 0">
              <span class="resumen-concepto">Descuentos Adicionales</span>
              <span class="resumen-monto resumen-descuento">− Bs. {{ fmt(montoDescManualesCalc) }}</span>
            </div>
            <div class="resumen-divisor"></div>
            <div class="resumen-fila resumen-total">
              <span class="resumen-concepto-total">TOTAL NETO</span>
              <span class="resumen-monto-total">Bs. {{ fmt(totalNetoCalc) }}</span>
            </div>
          </div>
        </div>

      </div>

      <template #acciones>
        <button class="btn btn-secondary" @click="cerrarModal">Cancelar</button>
        <button class="btn btn-primary" :disabled="guardando || !formulario.id_empleado" @click="guardar" id="btn-guardar-planilla">
          <span v-if="guardando" class="spinner"></span>
          <span v-else>{{ idEdicion ? 'Actualizar Planilla' : 'Registrar Planilla' }}</span>
        </button>
      </template>
    </ModalBase>

    <ModalBase :visible="modalGenerarVisible" @cerrar="modalGenerarVisible = false"
               titulo="Generar Planilla Automática" tamano="sm">
      <template #icono>
        <div class="modal-icon-badge modal-icon-purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
      </template>
      <div class="generar-modal-body">
        <p>Se calcularán y crearán las planillas de <strong>todos los empleados activos</strong> usando sus datos de asistencia del período:</p>
        <div class="periodo-badge">
          <strong>{{ nombreMes(mesGenerarSeleccionado) }} {{ anioGenerarSeleccionado }}</strong>
        </div>
        <div class="generar-selects">
          <div class="form-group">
            <label class="form-label">Mes</label>
            <select v-model="mesGenerarSeleccionado" class="input-control">
              <option v-for="m in mesesOpciones" :key="m.val" :value="m.val">{{ m.label }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Año</label>
            <select v-model="anioGenerarSeleccionado" class="input-control">
              <option v-for="a in aniosOpciones" :key="a" :value="a">{{ a }}</option>
            </select>
          </div>
        </div>
        <div class="generar-nota">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Los empleados que ya tienen planilla en este período serán omitidos automáticamente.
        </div>
      </div>
      <template #acciones>
        <button class="btn btn-secondary" @click="modalGenerarVisible = false">Cancelar</button>
        <button class="btn btn-auto" :disabled="generando" @click="ejecutarGenerarMasiva" id="btn-generar-masiva">
          <span v-if="generando" class="spinner"></span>
          <span v-else> Generar Ahora</span>
        </button>
      </template>
    </ModalBase>

    <ModalBase :visible="modalDescVisible" @cerrar="modalDescVisible = false" titulo="Agregar Descuento" tamano="sm">
      <div class="smart-form" style="padding: 1rem">
        <div class="form-group">
          <label class="form-label">Monto (Bs.) <span class="required">*</span></label>
          <input type="number" step="0.01" min="0.01" v-model.number="formDesc.monto" class="input-control" />
        </div>
        <div class="form-group" style="margin-top: 1rem">
          <label class="form-label">Fecha <span class="required">*</span></label>
          <input type="date" v-model="formDesc.fecha" class="input-control" :min="fechaActual" />
        </div>
        <div class="form-group" style="margin-top: 1rem">
          <label class="form-label">Motivo (Descripción) <span class="required">*</span></label>
          <textarea v-model="formDesc.descripcion" class="input-control" rows="2" placeholder="Ej. Daño a equipo..."></textarea>
        </div>
      </div>
      <template #acciones>
        <button type="button" class="btn btn-secondary" @click="modalDescVisible = false">Cancelar</button>
        <button type="button" class="btn btn-primary" @click="agregarDescManual" :disabled="!formDesc.monto || !formDesc.descripcion || !formDesc.fecha">Agregar</button>
      </template>
    </ModalBase>

    <ModalBase :visible="modalAdelantosVisible" @cerrar="modalAdelantosVisible = false" titulo="Adelantos Pendientes" tamano="sm">
      <div class="smart-form" style="padding: 1rem">
         <p class="text-sm text-muted" style="margin-bottom:1rem">Estos adelantos se descontarán automáticamente del salario en esta planilla.</p>
         <div v-if="adelantosPendientes.length === 0" class="asist-empty">El empleado no tiene adelantos pendientes.</div>
         <ul v-else class="desc-list">
             <li v-for="ad in adelantosPendientes" :key="ad.id" class="desc-item desc-adelanto">
                <div class="desc-info">
                  <span class="desc-label">{{ ad.descripcion || 'Sin detalle' }}</span>
                  <span class="text-xs text-muted">{{ ad.fecha }}</span>
                </div>
                <span class="desc-monto text-purple">- Bs. {{ fmt(ad.monto) }}</span>
             </li>
         </ul>
      </div>
      <template #acciones>
        <button type="button" class="btn btn-secondary" @click="modalAdelantosVisible = false">Cerrar</button>
      </template>
    </ModalBase>

    <ModalBase :visible="modalRechazoVisible" @cerrar="modalRechazoVisible = false" titulo="Rechazar Adelanto" tamano="sm">
      <div class="smart-form" style="padding: 1rem">
        <p style="margin-bottom: 1rem;">¿Está seguro de rechazar este adelanto?</p>
        <div class="form-group">
          <label class="form-label">Motivo del rechazo (opcional)</label>
          <textarea v-model="motivoRechazo" class="input-control" rows="3" placeholder="Escriba el motivo del rechazo..."></textarea>
        </div>
      </div>
      <template #acciones>
        <button type="button" class="btn btn-secondary" @click="modalRechazoVisible = false">Cancelar</button>
        <button type="button" class="btn btn-primary btn-danger" @click="confirmarRechazo">Rechazar</button>
      </template>
    </ModalBase>
    <ModalBase :visible="modalAdelantoGlobalVisible" @cerrar="modalAdelantoGlobalVisible = false" titulo="Registrar Adelanto" tamano="sm">
      <div class="smart-form" style="padding: 1rem">
        <div class="form-group">
          <label class="form-label">Empleado <span class="required">*</span></label>
          <select v-model="formAdelantoGlobal.empleado_id" class="input-control" :class="{'input-error': errorsAdelantoGlobal.empleado_id}" @change="errorsAdelantoGlobal.empleado_id=''">
            <option value="">— Seleccione un empleado —</option>
            <option v-for="e in empleados" :key="e.id_empleado" :value="e.id_empleado">
              {{ e.nombre }} {{ e.apellido }}
            </option>
          </select>
          <p v-if="errorsAdelantoGlobal.empleado_id" class="field-error">{{ errorsAdelantoGlobal.empleado_id }}</p>
        </div>
        <div class="form-group" style="margin-top: 1rem">
          <label class="form-label">Monto (Bs.) <span class="required">*</span></label>
          <input type="number" step="0.01" min="0.01" v-model.number="formAdelantoGlobal.monto" class="input-control" :class="{'input-error': errorsAdelantoGlobal.monto}" @input="errorsAdelantoGlobal.monto=''" />
          <p v-if="errorsAdelantoGlobal.monto" class="field-error">{{ errorsAdelantoGlobal.monto }}</p>
        </div>
        <div class="form-group" style="margin-top: 1rem">
          <label class="form-label">Fecha <span class="required">*</span></label>
          <input type="date" v-model="formAdelantoGlobal.fecha" class="input-control" :class="{'input-error': errorsAdelantoGlobal.fecha}" @input="errorsAdelantoGlobal.fecha=''" :min="fechaActual" />
          <p v-if="errorsAdelantoGlobal.fecha" class="field-error">{{ errorsAdelantoGlobal.fecha }}</p>
        </div>
        <div class="form-group" style="margin-top: 1rem">
          <label class="form-label">Descripción</label>
          <textarea v-model="formAdelantoGlobal.descripcion" class="input-control" :class="{'input-error': errorsAdelantoGlobal.descripcion}" rows="2" placeholder="Motivo del adelanto..." @input="errorsAdelantoGlobal.descripcion=''"></textarea>
          <p v-if="errorsAdelantoGlobal.descripcion" class="field-error">{{ errorsAdelantoGlobal.descripcion }}</p>
        </div>
      </div>
      <template #acciones>
        <button type="button" class="btn btn-secondary" @click="modalAdelantoGlobalVisible = false">Cancelar</button>
        <button type="button" class="btn btn-primary" @click="guardarAdelantoGlobal" :disabled="!formAdelantoGlobal.empleado_id || !formAdelantoGlobal.monto || !formAdelantoGlobal.fecha">Guardar</button>
      </template>
    </ModalBase>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { PlanillaServicio } from '../../servicios/PlanillaServicio'
import { usarConfirmacion } from '../../composables/usarConfirmacion'
import AdelantoServicio from '../../servicios/AdelantoServicio'
import api from '../../plugins/axios'

const { confirmar } = usarConfirmacion()
import { EmpleadoServicio } from '../../servicios/EmpleadoServicio'
import { usarPaginacion } from '../../composables/usarPaginacion'
import { usarNotificacion } from '../../composables/usarNotificacion'
import TarjetaMetrica from '../../componentes/TarjetaMetrica.vue'
import ModalBase from '../../componentes/ModalBase.vue'
import SkeletonLoader from '../../componentes/SkeletonLoader.vue'
import InsigniaEstado from '../../componentes/InsigniaEstado.vue'
import PaginacionBase from '../../componentes/PaginacionBase.vue'
import BotonActualizar from '../../componentes/BotonActualizar.vue'

const { exito, error: msjError } = usarNotificacion()

// ═══════════════════════════════════════════════
// ESTADO PRINCIPAL
// ═══════════════════════════════════════════════
const tabActivo         = ref('planillas')
const planillas         = ref([])
const empleados         = ref([])
const cargando          = ref(true)
const busqueda          = ref('')
const resumenMensual    = ref(null)
const modalVisible      = ref(false)
const guardando         = ref(false)
const idEdicion         = ref(null)
const errors            = ref({})
const errorsAdelantoGlobal = ref({})

// Asistencia automática
const cargandoAsistencia = ref(false)
const datosAsistencia    = ref(null)

// Modal generar masivo
const modalGenerarVisible     = ref(false)
const generando               = ref(false)
const mesGenerarSeleccionado  = ref(new Date().getMonth() + 1)
const anioGenerarSeleccionado = ref(new Date().getFullYear())

// Descuentos manuales y adelantos
const descuentosManuales    = ref([])
const adelantosPendientes   = ref([])
const modalDescVisible      = ref(false)
const formDesc              = ref({ monto: '', descripcion: '', fecha: new Date().toISOString().split('T')[0] })
const modalAdelantosVisible = ref(false)

// Filtro mes/año tabla principal
const now = new Date()
const fechaActual = now.toISOString().split('T')[0]
const filtroMesAnio = ref(`${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`)

// Lógica Adelantos Globales
const adelantosG              = ref([])
const cargandoAdelantos       = ref(false)
const busquedaAdelantos       = ref('')
const filtroEstadoAdelanto    = ref('')
const modalAdelantoGlobalVisible = ref(false)
const idEdicionAdelanto       = ref(null)
const formAdelantoGlobal      = ref({ empleado_id: '', monto: '', fecha: new Date().toISOString().split('T')[0], descripcion: '' })

// ═══════════════════════════════════════════════
// OPCIONES DE MESES Y AÑOS
// ═══════════════════════════════════════════════
const MESES_NOMBRES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                           'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']

const mesesOpciones = Array.from({ length: 12 }, (_, i) => ({
  val: i + 1,
  label: MESES_NOMBRES[i + 1]
}))

const aniosOpciones = Array.from({ length: 6 }, (_, i) => now.getFullYear() - i)

const nombreMes = (n) => MESES_NOMBRES[n] || '—'

// Opciones del filtro de tabla (últimos 6 meses)
const opcionesMeses = Array.from({ length: 6 }, (_, i) => {
  const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
  return {
    val: `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`,
    label: d.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
            .replace(/^\w/, c => c.toUpperCase())
  }
})

// ═══════════════════════════════════════════════
// FORMULARIO
// ═══════════════════════════════════════════════
const formDefault = () => {
  const [anio, mes] = filtroMesAnio.value.split('-')
  return {
    id_empleado: '',
    mes:         Number(mes),
    anio:        Number(anio),
    salario_base: 0,
    bonos:        0,
  }
}
const formulario = ref(formDefault())

// ═══════════════════════════════════════════════
// CÁLCULOS EN TIEMPO REAL (COMPUTED)
// ═══════════════════════════════════════════════
const montoHorasExtraCalc = computed(() => {
  if (!datosAsistencia.value) return 0
  return datosAsistencia.value.monto_horas_extra || 0
})

const montoDescuentoAutoCalc = computed(() => {
  if (!datosAsistencia.value) return 0
  return datosAsistencia.value.descuento_automatico || 0
})

const montoDescManualesCalc = computed(() => {
  return descuentosManuales.value.reduce((acc, curr) => acc + Number(curr.monto), 0)
})

const montoAdelantosCalc = computed(() => {
  return adelantosPendientes.value.reduce((acc, curr) => acc + Number(curr.monto), 0)
})

const totalNetoCalc = computed(() => {
  const base      = Number(formulario.value.salario_base) || 0
  const bonos     = Number(formulario.value.bonos)        || 0
  const totalDesc = montoDescuentoAutoCalc.value + montoDescManualesCalc.value + montoAdelantosCalc.value
  const horasExt  = montoHorasExtraCalc.value
  return Math.max(0, base + bonos + horasExt - totalDesc)
})

// Acciones Descuentos
const abrirModalDesc = () => {
  formDesc.value = { monto: '', descripcion: '', fecha: new Date().toISOString().split('T')[0] }
  modalDescVisible.value = true
}

const agregarDescManual = () => {
  if (!formDesc.value.monto || !formDesc.value.descripcion) return
  descuentosManuales.value.push({ ...formDesc.value })
  modalDescVisible.value = false
}

const eliminarDescManual = (index) => {
  descuentosManuales.value.splice(index, 1)
}

// ═══════════════════════════════════════════════
// UTILIDADES
// ═══════════════════════════════════════════════
const fmt       = n  => Number(n || 0).toLocaleString('es-BO', { minimumFractionDigits: 2 })
const iniciales = emp => emp ? `${emp.nombre?.[0]||''}${emp.apellido?.[0]||''}`.toUpperCase() : '—'

const tarjetaAsistClase = (val, umbralBueno, umbralMedio) => {
  if (val >= umbralBueno) return 'asist-card-green'
  if (val >= umbralMedio) return 'asist-card-yellow'
  return 'asist-card-red'
}

const tarjetaFaltasClase = (faltas) => {
  if (faltas === 0) return 'asist-card-green'
  if (faltas <= 2)  return 'asist-card-yellow'
  return 'asist-card-red'
}

// ═══════════════════════════════════════════════
// FILTRO Y PAGINACIÓN
// ═══════════════════════════════════════════════
const filtradosAdelantos = computed(() => {
  let l = adelantosG.value
  if (filtroEstadoAdelanto.value) {
    l = l.filter(r => r.estado === filtroEstadoAdelanto.value)
  }
  if (busquedaAdelantos.value) {
    const q = busquedaAdelantos.value.toLowerCase()
    l = l.filter(r => `${r.empleado?.nombre} ${r.empleado?.apellido}`.toLowerCase().includes(q))
  }
  return l
})

const { paginaActual: paginaActAdelanto, totalPaginas: totalPagAdelanto, elementosPaginados: elementosAdelantosPaginados, irAPagina: irAPagAdelanto } = usarPaginacion(filtradosAdelantos, 10)

// Paginacion desde el servidor para Planillas
const paginaActual = ref(1)
const totalPaginas = ref(1)
const totalElementos = ref(0)
const porPagina = ref(10)

const irAPagina = (p) => {
  cargarDatos(p)
}

// ═══════════════════════════════════════════════
// CARGA DE DATOS
// ═══════════════════════════════════════════════
const cargarDatos = async (p = 1) => {
  cargando.value = true
  try {
    const [anio, mes] = filtroMesAnio.value.split('-')
    const params = { mes, anio, page: p, per_page: porPagina.value }
    if (busqueda.value) {
      params.q = busqueda.value
    }
    
    const [planRes, resRes, empRes] = await Promise.all([
      PlanillaServicio.obtenerTodas(params),
      PlanillaServicio.resumenMensual(mes, anio),
      EmpleadoServicio.obtenerTodos()
    ])
    
    const datosPagina = planRes.datos || planRes
    planillas.value = datosPagina.data || []
    paginaActual.value = datosPagina.current_page || 1
    totalPaginas.value = datosPagina.last_page || 1
    totalElementos.value = datosPagina.total || 0
    
    resumenMensual.value = resRes
    empleados.value     = empRes.data || empRes.datos || empRes
  } catch (e) {
    msjError('Error al cargar planillas')
    planillas.value = []
  } finally {
    cargando.value = false
  }
}

const cargarAdelantosGlobales = async () => {
  cargandoAdelantos.value = true
  try {
    const res = await AdelantoServicio.obtenerAdelantos()
    adelantosG.value = res.data?.datos ?? res.data ?? res
  } catch (e) {
    msjError('Error al cargar adelantos')
  } finally {
    cargandoAdelantos.value = false
  }
}

watch(tabActivo, (n) => {
  if (n === 'adelantos' && adelantosG.value.length === 0) cargarAdelantosGlobales()
})

watch(filtroMesAnio, () => cargarDatos(1))
let debounceTimerPla = null
watch(busqueda, () => {
  clearTimeout(debounceTimerPla)
  debounceTimerPla = setTimeout(() => {
    cargarDatos(1)
  }, 300)
})

// ═══════════════════════════════════════════════
// CARGA AUTOMÁTICA DE ASISTENCIA
// ═══════════════════════════════════════════════
const cargarAsistenciaEmpleado = async () => {
  if (!formulario.value.id_empleado || !formulario.value.mes || !formulario.value.anio) {
    datosAsistencia.value = null
    return
  }
  cargandoAsistencia.value = true
  datosAsistencia.value = null
  try {
    const datos = await PlanillaServicio.calcularAsistencia(
      formulario.value.id_empleado,
      formulario.value.mes,
      formulario.value.anio
    )
    datosAsistencia.value = datos
    adelantosPendientes.value = datos.adelantos_pendientes || []
    
    // Auto-cargar salario base si no está seteado aún
    if (!formulario.value.salario_base && datos.salario_base) {
      formulario.value.salario_base = datos.salario_base
    }
  } catch {
    datosAsistencia.value = null
  } finally {
    cargandoAsistencia.value = false
  }
}

const onEmpleadoCambia = () => {
  errors.value.id_empleado = ''
  // Cargar salario base desde el empleado seleccionado
  const emp = empleados.value.find(e => e.id_empleado === formulario.value.id_empleado)
  if (emp) formulario.value.salario_base = emp.salario_base || 0
  cargarAsistenciaEmpleado()
}

const onPeriodoCambia = () => {
  errors.value.mes = ''
  errors.value.anio = ''
  if (formulario.value.id_empleado) cargarAsistenciaEmpleado()
}

// ═══════════════════════════════════════════════
// MODAL PRINCIPAL
// ═══════════════════════════════════════════════
const abrirModal = (rec = null) => {
  if (rec) {
    idEdicion.value = rec.id_planilla
    formulario.value = {
      id_empleado:  rec.id_empleado,
      mes:          rec.mes,
      anio:         rec.anio,
      salario_base: rec.salario_base,
      bonos:        rec.bonos        ?? 0,
    }
    descuentosManuales.value = rec.descuentos_manuales || []
    adelantosPendientes.value = rec.adelantos || []
    
    // Al editar, también cargamos la asistencia
    cargarAsistenciaEmpleado()
  } else {
    idEdicion.value  = null
    formulario.value = formDefault()
    datosAsistencia.value = null
    descuentosManuales.value = []
    adelantosPendientes.value = []
  }
  errors.value = {}
  modalVisible.value = true
}

const cerrarModal = () => {
  modalVisible.value = false
  setTimeout(() => {
    idEdicion.value       = null
    datosAsistencia.value = null
  }, 200)
}

// ═══════════════════════════════════════════════
// GUARDAR
// ═══════════════════════════════════════════════
const validarPlanilla = () => {
  errors.value = {}
  if (!formulario.value.id_empleado) errors.value.id_empleado = 'Seleccione un empleado'
  if (!formulario.value.mes) errors.value.mes = 'El mes es obligatorio'
  if (!formulario.value.anio) errors.value.anio = 'El año es obligatorio'
  if (formulario.value.salario_base === '' || formulario.value.salario_base === null) errors.value.salario_base = 'El salario base es obligatorio'
  if (formulario.value.salario_base < 0) errors.value.salario_base = 'El salario base no puede ser negativo'
  if (formulario.value.bonos < 0) errors.value.bonos = 'Los bonos no pueden ser negativos'
  
  const isValid = Object.keys(errors.value).length === 0
  if (!isValid) msjError('Por favor, complete todos los campos correctamente')
  return isValid
}

const guardar = async () => {
  if (!validarPlanilla()) return
  guardando.value = true
  try {
    // Construir payload con campos de asistencia
    const payload = {
      id_empleado:           formulario.value.id_empleado,
      mes:                   formulario.value.mes,
      anio:                  formulario.value.anio,
      salario_base:          formulario.value.salario_base,
      bonos:                 formulario.value.bonos    || 0,
      horas_extra:           montoHorasExtraCalc.value,
      descuentos_automaticos: montoDescuentoAutoCalc.value,
      descuentos_manuales:   descuentosManuales.value,
    }

    // Incluir campos de asistencia si los tenemos
    if (datosAsistencia.value) {
      payload.dias_trabajados       = datosAsistencia.value.dias_trabajados
      payload.horas_trabajadas_total   = datosAsistencia.value.horas_trabajadas
      payload.horas_extra_cantidad   = datosAsistencia.value.horas_extra_cantidad
    }

    if (idEdicion.value) {
      await PlanillaServicio.actualizar(idEdicion.value, payload)
      exito('Planilla actualizada correctamente')
    } else {
      await PlanillaServicio.crear(payload)
      exito('Planilla registrada correctamente')
    }
    cerrarModal()
    cargarDatos()
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errores || err.response.data.errors;
      for (const key in serverErrors) {
        errors.value[key] = serverErrors[key][0];
      }
      msjError('Por favor, verifique los errores en el formulario.');
    } else {
      msjError(err.response?.data?.mensaje || err.response?.data?.message || 'Error al guardar la planilla')
    }
  } finally {
    guardando.value = false
  }
}

// ═══════════════════════════════════════════════
// MARCAR PAGADO / ELIMINAR
// ═══════════════════════════════════════════════
const marcarPagado = async (row) => {
  const confirmado = await confirmar(
    `¿Confirmar el pago de Bs. ${fmt(row.salario_total)} a ${row.empleado?.nombre} ${row.empleado?.apellido}?`,
    { titulo: 'Confirmar Pago', textoConfirmar: 'Confirmar Pago', textoCancelar: 'Cancelar' }
  )
  if (!confirmado) return
  try {
    await PlanillaServicio.marcarPagado(row.id_planilla)
    exito('Salario marcado como pagado')
    cargarDatos()
  } catch {
    msjError('Error al actualizar estado')
  }
}

const eliminarRegistro = async (row) => {
  const confirmado = await confirmar(
    `¿Eliminar la planilla de ${row.empleado?.nombre} ${row.empleado?.apellido}?`,
    { titulo: 'Eliminar Planilla', textoConfirmar: 'Eliminar', textoCancelar: 'Cancelar' }
  )
  if (!confirmado) return
  try {
    await PlanillaServicio.eliminar(row.id_planilla)
    exito('Planilla eliminada')
    cargarDatos()
  } catch {
    msjError('Error al eliminar')
  }
}

// ═══════════════════════════════════════════════
// GENERAR PLANILLA MASIVA
// ═══════════════════════════════════════════════
const abrirModalGenerar = () => {
  const [anio, mes] = filtroMesAnio.value.split('-')
  mesGenerarSeleccionado.value  = Number(mes)
  anioGenerarSeleccionado.value = Number(anio)
  modalGenerarVisible.value = true
}

const ejecutarGenerarMasiva = async () => {
  generando.value = true
  try {
    const resultado = await PlanillaServicio.generarPlanillaMensual(
      mesGenerarSeleccionado.value,
      anioGenerarSeleccionado.value
    )
    modalGenerarVisible.value = false
    exito(
      `${resultado.creadas} planillas creadas · ${resultado.omitidas} ya existían` +
      (resultado.errores > 0 ? ` · ${resultado.errores} errores` : '')
    )
    cargarDatos()
  } catch (err) {
    if (err.response?.status === 422) {
      msjError('Datos inválidos o período incorrecto.');
    } else {
      msjError(err.response?.data?.mensaje || err.response?.data?.message || 'Error al generar planillas')
    }
  } finally {
    generando.value = false
  }
}

const aprobarAdelanto = async (row) => {
  const ok = await confirmar(`¿Está seguro de aprobar este adelanto?`, {
    titulo: 'Aprobar Adelanto',
    textoConfirmar: 'Aprobar',
    textoCancelar: 'Cancelar'
  })
  if (!ok) return
  try {
    await api.patch(`/adelantos/${row.id}/aprobar`)
    msjExito('El adelanto ha sido aprobado correctamente.')
    cargarAdelantosGlobales()
  } catch (err) {
    msjError(err.response?.data?.mensaje || 'Error al aprobar adelanto')
  }
}

const modalRechazoVisible = ref(false)
const adelantoARechazar = ref(null)
const motivoRechazo = ref('')

const abrirModalRechazo = (row) => {
  adelantoARechazar.value = row
  motivoRechazo.value = ''
  modalRechazoVisible.value = true
}

const confirmarRechazo = async () => {
  if (!adelantoARechazar.value) return
  
  try {
    await api.patch(`/adelantos/${adelantoARechazar.value.id}/rechazar`, {
      motivo_rechazo: motivoRechazo.value
    })
    msjExito('El adelanto ha sido rechazado correctamente.')
    modalRechazoVisible.value = false
    cargarAdelantosGlobales()
  } catch (err) {
    let msg = 'Error al rechazar adelanto'
    if (err.response?.data?.mensaje) msg = err.response.data.mensaje
    else if (err.response?.data?.message) msg = err.response.data.message
    else if (err.message) msg += ': ' + err.message
    msjError(msg)
  }
}


const abrirModalAdelantoGlobal = () => {
  formAdelantoGlobal.value = { empleado_id: '', monto: '', fecha: new Date().toISOString().split('T')[0], descripcion: '' }
  errorsAdelantoGlobal.value = {}
  modalAdelantoGlobalVisible.value = true
}

const validarAdelantoGlobal = () => {
  errorsAdelantoGlobal.value = {}
  if (!formAdelantoGlobal.value.empleado_id) errorsAdelantoGlobal.value.empleado_id = 'Seleccione un empleado'
  if (!formAdelantoGlobal.value.monto || formAdelantoGlobal.value.monto <= 0) errorsAdelantoGlobal.value.monto = 'Ingrese un monto válido'
  if (!formAdelantoGlobal.value.fecha) errorsAdelantoGlobal.value.fecha = 'La fecha es obligatoria'
  
  const isValid = Object.keys(errorsAdelantoGlobal.value).length === 0
  if (!isValid) msjError('Por favor, complete todos los campos correctamente')
  return isValid
}

const guardarAdelantoGlobal = async () => {
  if (!validarAdelantoGlobal()) return
  try {
    await AdelantoServicio.crearAdelanto(formAdelantoGlobal.value)
    exito('Adelanto registrado')
    modalAdelantoGlobalVisible.value = false
    cargarAdelantosGlobales()
  } catch (err) {
    if (err.response?.status === 422) {
      const serverErrors = err.response.data.errores || err.response.data.errors;
      for (const key in serverErrors) {
        errorsAdelantoGlobal.value[key] = serverErrors[key][0];
      }
      msjError('Por favor, verifique los errores en el formulario.');
    } else {
      msjError(err.response?.data?.mensaje || err.response?.data?.message || 'Error al guardar adelanto')
    }
  }
}

onMounted(() => {
  cargarDatos()
  cargarAdelantosGlobales()
})
</script>

<style scoped>
/* ══ Layout ══════════════════════════════════════════════════════ */
.page-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap; }
.page-actions { display: flex; gap: 0.625rem; align-items: center; flex-wrap: wrap; }

/* ══ Tabs ════════════════════════════════════════════════════════ */
.custom-tabs {
  display: flex; gap: 0.5rem; margin-bottom: 1.25rem;
  border-bottom: 1px solid var(--border);
}
.tab-item {
  background: none; border: none; padding: 0.75rem 1.25rem;
  font-size: 0.95rem; font-weight: 600; color: var(--text-muted);
  cursor: pointer; position: relative; transition: color 0.2s;
  border-radius: 8px 8px 0 0;
}
.tab-item:hover { color: var(--text-primary); background: rgba(0,0,0,.02); }
.tab-item.active { color: #7c3aed; }
.tab-item.active::after {
  content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
  height: 2px; background: #7c3aed; border-radius: 2px 2px 0 0;
}
.tab-content { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* ══ Badges ══════════════════════════════════════════════════════ */
.badge { padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; }
.badge-warning { background: rgba(245,158,11,.1); color: #b45309; }
.badge-success { background: rgba(16,185,129,.1); color: #047857; }
.badge-info    { background: rgba(59,130,246,.1);  color: #1d4ed8; }
.badge-danger  { background: rgba(239,68,68,.1);   color: #dc2626; }

/* ══ Botón Auto ══════════════════════════════════════════════════ */
.btn-auto {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.6rem 1.125rem;
  font-size: 0.875rem; font-weight: 600; font-family: inherit;
  border-radius: 8px; border: none; cursor: pointer;
  background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
  transition: all 0.2s;
}
.btn-auto:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(124, 58, 237, 0.45); }
.btn-auto:disabled { opacity: 0.65; cursor: not-allowed; }
.spinner-icon { width: 14px; height: 14px; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ══ Resumen cards ═══════════════════════════════════════════════ */
.payroll-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 1.25rem; }
@media (max-width: 900px) { .payroll-summary { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .payroll-summary { grid-template-columns: 1fr; } }

/* ══ Tabla ═══════════════════════════════════════════════════════ */
.table-toolbar { display: flex; align-items: center; gap: 0.875rem; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
.search-input  { flex: 1; min-width: 180px; max-width: 280px; }
.empty-row     { text-align: center; padding: 3rem 1rem !important; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 0.625rem; font-size: 0.875rem; }
.emp-cell      { display: flex; align-items: center; gap: 0.625rem; }
.emp-avatar    { width: 32px; height: 32px; border-radius: 50%; background: rgba(109,40,217,.1); border: 1px solid rgba(109,40,217,.18); color: var(--primary-600); font-size: .63rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.net-salary    { font-weight: 800; color: #047857; font-size: 0.875rem; }
.salary-badge  { background: rgba(59,130,246,.1); color: var(--primary-700); padding: .2rem .5rem; border-radius: 6px; font-size: .8125rem; font-weight: 600; }
.action-btns   { display: flex; gap: .25rem; }
.table-footer  { display: flex; align-items: center; justify-content: space-between; padding: .875rem 1.5rem; border-top: 1px solid var(--border); gap: .875rem; flex-wrap: wrap; }
.pagination    { display: flex; gap: .5rem; }

/* ══ Modal: Smart Form ═══════════════════════════════════════════ */
.modal-icon-badge {
  width: 32px; height: 32px; border-radius: 8px;
  background: linear-gradient(135deg, #7c3aed, #4f46e5);
  display: flex; align-items: center; justify-content: center; color: #fff;
}
.modal-icon-purple { background: linear-gradient(135deg, #7c3aed, #4f46e5); }

.smart-form { display: flex; flex-direction: column; gap: 0; }

.form-section {
  padding: 1.25rem 0;
  border-bottom: 1px solid var(--border);
}
.form-section:last-child { border-bottom: none; padding-bottom: 0; }
.form-section:first-child { padding-top: 0; }

.form-section-header {
  display: flex; align-items: flex-start; gap: 0.875rem; margin-bottom: 1rem;
}
.section-num {
  width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
  background: var(--primary, #4f46e5); color: #fff;
  font-size: 0.7rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
}
.sec-auto    { background: linear-gradient(135deg, #7c3aed, #4f46e5); }
.sec-edit    { background: #16a34a; }
.sec-resumen { background: linear-gradient(135deg, #0369a1, #0284c7); }

.section-title { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem; }
.section-desc  { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
.badge-auto    { font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 4px; background: linear-gradient(135deg, rgba(124,58,237,.15), rgba(79,70,229,.15)); color: #7c3aed; }

.form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.875rem; }
.form-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.875rem; }
.col-span-full { grid-column: 1 / -1; }
@media (max-width: 600px) { .form-grid-3, .form-grid-2 { grid-template-columns: 1fr; } }

.form-group    { display: flex; flex-direction: column; gap: 0.375rem; }
.form-label    { font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); display: flex; align-items: center; gap: 0.3rem; }
.form-hint     { font-size: 0.72rem; color: var(--text-muted); }
.required      { color: #ef4444; }

.autocomplete-wrapper { position: relative; }
.input-with-icon { padding-left: 2.25rem; }
.input-icon {
  position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%);
  color: var(--text-muted); display: flex; align-items: center;
}

/* ══ Asistencia cards ════════════════════════════════════════════ */
.asist-loading {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 1.25rem; border-radius: 10px;
  background: rgba(79,70,229,.05); color: var(--text-muted); font-size: 0.875rem;
}
.asist-empty {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 1rem 1.25rem; border-radius: 8px;
  background: rgba(0,0,0,.03); color: var(--text-muted); font-size: 0.8rem;
  border: 1px dashed var(--border);
}
.asist-empty-warn { background: rgba(245,158,11,.06); color: #92400e; border-color: rgba(245,158,11,.3); }

.asist-cards {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem;
}
@media (max-width: 640px) { .asist-cards { grid-template-columns: repeat(2, 1fr); } }

.asist-card {
  padding: 1rem;
  border-radius: 10px;
  border: 1.5px solid transparent;
  display: flex; flex-direction: column; align-items: center; gap: 0.25rem;
  text-align: center; transition: transform 0.15s;
}
.asist-card:hover { transform: translateY(-2px); }
.asist-card-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.25rem; }
.asist-card-valor { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.asist-card-label { font-size: 0.7rem; font-weight: 500; opacity: 0.75; }

/* Estados de las tarjetas */
.asist-card-green  { background: rgba(22,163,74,.08);  border-color: rgba(22,163,74,.25);  color: #166534; }
.asist-card-green  .asist-card-icon { background: rgba(22,163,74,.12); }
.asist-card-yellow { background: rgba(245,158,11,.08); border-color: rgba(245,158,11,.25); color: #92400e; }
.asist-card-yellow .asist-card-icon { background: rgba(245,158,11,.12); }
.asist-card-red    { background: rgba(239,68,68,.08);  border-color: rgba(239,68,68,.25);  color: #991b1b; }
.asist-card-red    .asist-card-icon { background: rgba(239,68,68,.12); }
.asist-card-blue   { background: rgba(37,99,235,.08);  border-color: rgba(37,99,235,.25);  color: #1e3a8a; }
.asist-card-blue   .asist-card-icon { background: rgba(37,99,235,.12); }
.asist-card-purple { background: rgba(124,58,237,.08); border-color: rgba(124,58,237,.25); color: #4c1d95; }
.asist-card-purple .asist-card-icon { background: rgba(124,58,237,.12); }
.asist-card-neutral{ background: rgba(100,116,139,.06);border-color: rgba(100,116,139,.2); color: #475569; }
.asist-card-neutral .asist-card-icon { background: rgba(100,116,139,.1); }

/* ══ Resumen de pago ═════════════════════════════════════════════ */
.resumen-section { background: rgba(79,70,229,.03); border-radius: 10px; padding: 1.25rem !important; border: 1px solid rgba(79,70,229,.1) !important; margin-top: 0.25rem; }

.resumen-tabla { display: flex; flex-direction: column; gap: 0; }
.resumen-fila  { display: flex; align-items: center; justify-content: space-between; padding: 0.55rem 0.75rem; border-radius: 6px; transition: background 0.15s; }
.resumen-fila:hover { background: rgba(0,0,0,.03); }
.resumen-concepto { font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.4rem; }
.resumen-concepto small { font-size: 0.72rem; opacity: 0.75; }
.resumen-monto { font-size: 0.875rem; font-weight: 600; color: var(--text-primary); }
.resumen-extra    { color: #7c3aed; }
.resumen-bono     { color: #16a34a; }
.resumen-descuento{ color: #dc2626; }

.resumen-divisor { height: 1px; background: var(--border); margin: 0.5rem 0; }

.resumen-total  { background: linear-gradient(135deg, rgba(79,70,229,.08), rgba(124,58,237,.05)); border-radius: 8px; border: 1px solid rgba(79,70,229,.15); padding: 0.75rem; margin-top: 0.25rem; }
.resumen-concepto-total { font-size: 0.875rem; font-weight: 800; color: var(--text-primary); letter-spacing: 0.05em; }
.resumen-monto-total    { font-size: 1.25rem; font-weight: 900; color: #4f46e5; font-variant-numeric: tabular-nums; }

/* ══ Modal generar masiva ════════════════════════════════════════ */
.generar-modal-body { display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem; color: var(--text-secondary); }
.generar-modal-body p { line-height: 1.6; }
.periodo-badge { display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(124,58,237,.12), rgba(79,70,229,.08)); border: 1px solid rgba(124,58,237,.2); border-radius: 8px; padding: 0.5rem 1.25rem; color: #4c1d95; font-size: 1rem; }
.generar-selects { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.generar-nota { display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.775rem; color: var(--text-muted); background: rgba(0,0,0,.03); padding: 0.625rem; border-radius: 6px; border: 1px dashed var(--border); line-height: 1.5; }

/* ══ Descuentos ══════════════════════════════════════════════════ */
.desc-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.desc-item { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.6rem; border: 1px solid var(--border); border-radius: 6px; background: rgba(255,255,255,0.5); }
.desc-adelanto { background: rgba(124,58,237,.03); border-color: rgba(124,58,237,.15); }
.desc-info { display: flex; flex-direction: column; }
.desc-label { font-size: 0.8rem; font-weight: 600; }
.desc-monto { font-size: 0.85rem; font-weight: 700; color: #dc2626; }
.text-purple { color: #7c3aed !important; }
.p-1 { padding: 0.25rem; }
</style>
