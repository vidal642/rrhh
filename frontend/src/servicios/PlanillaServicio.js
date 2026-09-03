/**
 * Servicio de Planillas
 * Centraliza todas las llamadas a la API de gestión de planillas.
 */
import api from '../plugins/axios'

const extraerDatos = (respuesta) => respuesta.data?.datos ?? respuesta.data

export const PlanillaServicio = {
  /**
   * Obtener todas las planillas con filtros opcionales.
   * @param {Object} filtros - { mes, anio, empleado_id, estado, departamento_id }
   */
  async obtenerTodas(filtros = {}) {
    const respuesta = await api.get('/planillas', { params: filtros })
    return extraerDatos(respuesta)
  },

  /**
   * Obtener resumen mensual de planillas.
   * @param {number} mes
   * @param {number} anio
   */
  async resumenMensual(mes, anio) {
    const respuesta = await api.get('/planillas/resumen', { params: { mes, anio } })
    return extraerDatos(respuesta)
  },

  /**
   * Crear una nueva planilla. El total se calcula automáticamente en el backend.
   * @param {Object} datos - { id_empleado, mes, anio, salario_base, bonos, descuentos, horas_extra, ... }
   */
  async crear(datos) {
    const respuesta = await api.post('/planillas', datos)
    return extraerDatos(respuesta)
  },

  /**
   * Actualizar una planilla.
   * @param {number} id
   * @param {Object} datos
   */
  async actualizar(id, datos) {
    const respuesta = await api.put(`/planillas/${id}`, datos)
    return extraerDatos(respuesta)
  },

  /**
   * Marcar una planilla como pagada.
   * @param {number} id
   */
  async marcarPagado(id) {
    const respuesta = await api.patch(`/planillas/${id}/pagar`)
    return extraerDatos(respuesta)
  },

  /**
   * Eliminar una planilla.
   * @param {number} id
   */
  async eliminar(id) {
    const respuesta = await api.delete(`/planillas/${id}`)
    return respuesta.data
  },

  // ═══════════════════════════════════════════════════════
  // NUEVOS MÉTODOS: AUTOMATIZACIÓN DESDE ASISTENCIA
  // ═══════════════════════════════════════════════════════

  /**
   * Calcular estadísticas de asistencia de un empleado para un mes/año.
   * Retorna: dias_trabajados, horas_trabajadas, horas_extra_cantidad, faltas, monto_horas_extra.
   *
   * @param {number} idEmpleado
   * @param {number} mes
   * @param {number} anio
   */
  async calcularAsistencia(idEmpleado, mes, anio) {
    const respuesta = await api.get('/planillas/calcular-asistencia', {
      params: { id_empleado: idEmpleado, mes, anio }
    })
    return extraerDatos(respuesta)
  },

  /**
   * Generar planillas automáticas para todos los empleados activos de un mes.
   * Omite empleados que ya tienen planilla creada en ese período.
   *
   * @param {number} mes
   * @param {number} anio
   */
  async generarPlanillaMensual(mes, anio) {
    const respuesta = await api.post('/planillas/generar-mensual', { mes, anio })
    return extraerDatos(respuesta)
  },
}
