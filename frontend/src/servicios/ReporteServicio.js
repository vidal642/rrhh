import api from '../plugins/axios'

const icons = {
  asistencia: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
  reloj: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
  alerta: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
  ranking: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`,
  users: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
  organigrama: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>`,
  historial: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><rect x="15" y="15" width="8" height="6" rx="1" ry="1"/></svg>`,
  // Salarios
  dinero: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>`,
  bonos: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><polyline points="20 12 20 22 5 22 5 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>`,
  descuentos: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg>`,
  // Vacaciones
  vacaciones: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><path d="M2 22v-3l4-2.5 4 2.5 4-2.5 4 2.5 4-2.5V22H2z"/><path d="M12 4v11"/><path d="M12 9s3-3 6-3 4 5 4 5l-10 1"/></svg>`,
  ausencia: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="20" height="20"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`
}

export const CATEGORIAS_REPORTES = [
  { id: 'asistencia', title: ' REPORTES DE ASISTENCIA' },
  { id: 'empleados', title: ' REPORTES DE EMPLEADOS' },
  { id: 'salarios', title: ' REPORTES SALARIALES' }
]

export const DEFINICION_REPORTES = [
  {
    id: 102, category: 'asistencia', title: 'Historial de Asistencia por Empleado', desc: 'Historial completo del mes mostrando entradas, salidas y estado (puntual, retraso, falta) de un empleado.', color: 'green', icon: icons.reloj,
    filters: ['empleado', 'mes', 'anio'],
    columns: [
      { key: 'fecha', label: 'Fecha' },
      { key: 'hora_entrada', label: 'Entrada / Puntuales' },
      { key: 'hora_salida', label: 'Salida / Retrasos' },
      { key: 'estado_asistencia', label: 'Estado / Faltas' }
    ]
  },
  {
    id: 103, category: 'asistencia', title: 'Retrasos por Empleado', desc: 'Reporte de llegadas tardías y cantidad de retrasos.', color: 'violet', icon: icons.alerta,
    filters: ['mes', 'anio'],
    columns: [
      { key: 'empleado', label: 'Empleado' },
      { key: 'fecha', label: 'Fecha del Retraso' },
      { key: 'hora_ingreso', label: 'Hora Marcada' },
      { key: 'minutos_retraso', label: 'Minutos de Retraso' }
    ]
  },
  {
    id: 105, category: 'asistencia', title: 'Horas Trabajadas', desc: 'Cantidad total de horas trabajadas por empleado.', color: 'indigo', icon: icons.reloj,
    filters: ['mes', 'anio'],
    columns: [
      { key: 'empleado', label: 'Empleado' },
      { key: 'horas_normales', label: 'Horas Normales' },
      { key: 'horas_extra', label: 'Horas Extra' },
      { key: 'total_horas', label: 'Total Horas' }
    ]
  },

  {
    id: 201, category: 'empleados', title: 'Empleados Activos', desc: 'Listado de empleados actualmente activos.', color: 'indigo', icon: icons.users,
    filters: ['departamento'],
    columns: [
      { key: 'nombre', label: 'Nombre Completo' },
      { key: 'ci', label: 'C.I.' },
      { key: 'cargo', label: 'Cargo' },
      { key: 'departamento', label: 'Departamento' },
      { key: 'salario_base', label: 'Salario Base', type: 'money' }
    ]
  },
  {
    id: 204, category: 'empleados', title: 'Empleados por Depto.', desc: 'Distribución del personal por área o departamento.', color: 'indigo', icon: icons.organigrama,
    filters: [],
    columns: [
      { key: 'departamento', label: 'Departamento' },
      { key: 'cantidad', label: 'Nº Empleados' },
      { key: 'jefe', label: 'Jefe de Área' }
    ]
  },

  {
    id: 301, category: 'salarios', title: 'Planilla Mensual', desc: 'Resumen general de salarios pagados por mes.', color: 'green', icon: icons.dinero,
    filters: ['mes', 'anio', 'departamento'],
    columns: [
      { key: 'empleado', label: 'Empleado' },
      { key: 'salario_base', label: 'Salario Base', type: 'money' },
      { key: 'bonos', label: 'Bonos', type: 'money' },
      { key: 'descuentos', label: 'Descuentos', type: 'money' },
      { key: 'salario_total', label: 'Neto Pagado', type: 'money' }
    ]
  },
  {
    id: 304, category: 'salarios', title: 'Historial de Pagos', desc: 'Registro histórico de pagos realizados.', color: 'green', icon: icons.historial,
    filters: ['anio'],
    columns: [
      { key: 'mes_anio', label: 'Periodo' },
      { key: 'fecha_pago', label: 'Fecha de Pago' },
      { key: 'total_empleados', label: 'Empleados Pagados' },
      { key: 'monto_total', label: 'Monto Total', type: 'money' }
    ]
  }
]

const generarMockData = (reportId, filtros) => {
  const data = []
  const { mes, anio, departamento } = filtros

  const nombres = ['Juan Pérez', 'María Gómez', 'Carlos López', 'Ana Martínez', 'Luis Rodríguez', 'Sofía Vargas']
  const cargos = ['Ingeniero Civil', 'Arquitecto', 'Contador', 'Recursos Humanos', 'Obrero', 'Chofer']
  const deptos = ['Proyectos', 'Finanzas', 'RRHH', 'Operaciones']

  for (let i = 1; i <= 6; i++) {
    const nombre = nombres[i - 1]
    const depto = deptos[i % 4]
    if (departamento && departamento !== depto && isNaN(departamento)) continue // Filtro mock simple

    switch (reportId) {
      case 101: data.push({ empleado: nombre, fecha: '15/05/2026', hora_ingreso: '08:05', hora_salida: '17:00', estado: i === 2 ? 'Retraso' : 'Presente' }); break
      case 102: data.push({ empleado: nombre, mes_anio: `${mes || 5}/${anio || 2026}`, dias_trabajados: 22, faltas: i === 3 ? 1 : 0, retrasos: i === 2 ? 2 : 0 }); break
      case 103: data.push({ empleado: nombre, fecha: '10/05/2026', hora_ingreso: '08:15', minutos_retraso: 15 }); break
      case 104: data.push({ empleado: nombre, departamento: depto, fecha: '12/05/2026', justificada: i % 2 === 0 ? 'Sí' : 'No' }); break
      case 105: data.push({ empleado: nombre, horas_normales: 160, horas_extra: i * 2, total_horas: 160 + (i * 2) }); break
      case 106: data.push({ ranking: `#${i}`, empleado: nombre, dias_asistidos: 22, cero_retrasos: i === 1 ? '0' : '1' }); break

      case 201: data.push({ nombre, ci: `123456${i}`, cargo: cargos[i - 1], departamento: depto, salario_base: 3500 + (i * 500) }); break
      case 202: data.push({ nombre, ci: `987654${i}`, cargo: cargos[i - 1], fecha_retiro: '10/02/2026', motivo: 'Renuncia' }); break
      case 203: data.push({ cargo: cargos[i - 1], empleado: nombre, departamento: depto }); break
      case 204: data.push({ departamento: depto, cantidad: i * 3, jefe: nombres[i % 6] }); break
      case 205: data.push({ empleado: nombre, fecha_ingreso: `01/01/202${i - 1}`, antiguedad: 2026 - (2020 + i) + ' años' }); break
      case 206: data.push({ empleado: nombre, estado: i === 4 ? 'Vacaciones' : 'Activo', fecha_cambio: '01/05/2026' }); break

      case 301: data.push({ empleado: nombre, salario_base: 4000, bonos: 500, descuentos: 200, salario_total: 4300 }); break
      case 302: data.push({ empleado: nombre, mes_anio: `${mes || 5}/${anio || 2026}`, monto_bono: 500, motivo: 'Bono Productividad' }); break
      case 303: data.push({ empleado: nombre, mes_anio: `${mes || 5}/${anio || 2026}`, descuento_auto: 50, descuento_manual: 150, total_descuento: 200 }); break
      case 304: data.push({ mes_anio: `${mes || 5}/${anio || 2026}`, fecha_pago: '05/06/2026', total_empleados: 45, monto_total: 185000 }); break
      case 305: data.push({ departamento: depto, cant_empleados: i * 4, total_salarios: 20000, total_bonos: 2000, costo_total: 22000 }); break

      case 401: data.push({ empleado: nombre, fecha_inicio: '10/01/2026', fecha_fin: '25/01/2026', dias: 15 }); break
      case 402: data.push({ empleado: nombre, fecha_solicitud: '05/05/2026', dias_solicitados: 10, estado: 'Pendiente' }); break
      case 403: data.push({ empleado: nombre, fecha: '12/03/2026', motivo: 'Baja Médica' }); break
      case 404: data.push({ empleado: nombre, fecha: '15/04/2026', sancion: 'Descuento 1 día' }); break
      case 405: data.push({ empleado: nombre, fecha: '20/05/2026', horas: 4, estado: 'Aprobado' }); break
    }
  }

  if ([204, 304, 305].includes(reportId)) {
    return Array.from(new Set(data.map(a => JSON.stringify(a)))).map(a => JSON.parse(a)).slice(0, 4)
  }

  return data
}

export const ReporteServicio = {
  async obtenerDatosReporte(reportId, filtros) {
    try {
      if (reportId === 201) {
        const params = { estado: 'Activo', per_page: 'all' }
        if (filtros.departamento) params.id_departamento = filtros.departamento
        const res = await api.get('/empleados', { params })
        return (res.data.datos?.data || res.data.datos || res.data).map(e => ({
          nombre: `${e.nombre} ${e.apellido}`,
          ci: e.ci,
          cargo: e.cargo?.nombre || '—',
          departamento: e.departamento?.nombre || '—',
          salario_base: e.salario_base
        }))
      }

      if (reportId === 301) {
        const params = { mes: filtros.mes, anio: filtros.anio, per_page: 'all' }
        if (filtros.departamento) params.id_departamento = filtros.departamento
        const res = await api.get('/planillas', { params })
        return (res.data.datos?.data || res.data.datos || res.data).map(p => ({
          empleado: p.empleado ? `${p.empleado.nombre} ${p.empleado.apellido}` : '—',
          salario_base: p.salario_base,
          bonos: p.bonos,
          descuentos: p.descuentos,
          salario_total: p.salario_total
        }))
      }

      if (reportId === 102) {
        if (!filtros.id_empleado && !filtros.empleado_id && !filtros.empleado) {
          throw new Error('Debe seleccionar un empleado para este reporte.')
        }
        
        const mes = filtros.mes || new Date().getMonth() + 1
        const anio = filtros.anio || new Date().getFullYear()

        const res = await api.get('/asistencia/reporte-historial', {
          params: {
            id_empleado: filtros.id_empleado || filtros.empleado_id || filtros.empleado,
            mes,
            anio
          }
        })
        
        const data = res.data.datos || res.data
        const historial = data.historial || []

        if (data.totales) {
          historial.push({
            fecha: '=== RESUMEN ===',
            hora_entrada: `Puntuales: ${data.totales.puntuales}`,
            hora_salida: `Retrasos: ${data.totales.retrasos}`,
            estado_asistencia: `Faltas: ${data.totales.faltas}`
          })
        }
        
        return historial
      }

      if ([103, 105].includes(reportId)) {
        const mes = filtros.mes || new Date().getMonth() + 1
        const anio = filtros.anio || new Date().getFullYear()
        const inicio = new Date(anio, mes - 1, 1).toISOString().split('T')[0]
        const fin = new Date(anio, mes, 0).toISOString().split('T')[0]
        const params = { fecha_inicio: inicio, fecha_fin: fin, per_page: 'all' }
        if (reportId === 103) params.estado = 'Retraso'
        const res = await api.get('/asistencia', { params })
        const registros = res.data.datos?.data || res.data.datos || res.data

        if (reportId === 103) {
          return registros.map(r => {
            let min = 0;
            if (r.hora_entrada) {
              const [h, m] = r.hora_entrada.split(':').map(Number)
              if (h > 8 || (h === 8 && m > 0)) {
                min = ((h - 8) * 60) + m
              }
            }
            return {
              empleado: r.empleado ? `${r.empleado.nombre} ${r.empleado.apellido}` : '—',
              fecha: r.fecha,
              hora_ingreso: r.hora_entrada || '—',
              minutos_retraso: min > 0 ? min + ' min' : '—'
            }
          })
        }

        if (reportId === 105) { // Horas Trabajadas
          const agrupado = {}
          registros.forEach(r => {
            if (!agrupado[r.id_empleado]) {
              agrupado[r.id_empleado] = {
                empleado: r.empleado ? `${r.empleado.nombre} ${r.empleado.apellido}` : '—',
                horas_normales: 0,
                horas_extra: 0,
                total_horas: 0
              }
            }
            if (r.horas_trabajadas) {
              const hrs = parseFloat(r.horas_trabajadas)
              agrupado[r.id_empleado].total_horas += hrs
              if (hrs > 8) {
                agrupado[r.id_empleado].horas_normales += 8
                agrupado[r.id_empleado].horas_extra += (hrs - 8)
              } else {
                agrupado[r.id_empleado].horas_normales += hrs
              }
            }
          })
          return Object.values(agrupado).map(a => ({
            ...a,
            horas_normales: a.horas_normales.toFixed(2),
            horas_extra: a.horas_extra.toFixed(2),
            total_horas: a.total_horas.toFixed(2)
          }))
        }
      }

      // 2. Si no hay endpoint mapeado directo, retornar Mock Data realista
      // En un entorno de producción, aquí se llamarían a los nuevos endpoints.
      return new Promise((resolve) => {
        setTimeout(() => resolve(generarMockData(reportId, filtros)), 500)
      })

    } catch (error) {
      console.error(`Error obteniendo reporte ${reportId}:`, error)
      throw error
    }
  }
}
