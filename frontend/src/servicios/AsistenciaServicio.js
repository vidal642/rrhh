/**
 * Servicio de Asistencia
 * Centraliza todas las llamadas a la API de control de asistencia.
 */
import api from '../plugins/axios'

const extraerDatos = (respuesta) => respuesta.data?.datos ?? respuesta.data

export const AsistenciaServicio = {
  /**
   * Obtener asistencias con filtros.
   * @param {Object} filtros - { fecha, fecha_inicio, fecha_fin, empleado_id, estado, departamento_id }
   */
  async obtenerTodas(filtros = {}) {
    const respuesta = await api.get('/asistencia', { params: filtros })
    return extraerDatos(respuesta)
  },

  /**
   * Registrar una nueva asistencia.
   * @param {Object} datos - { employee_id, date, time_in, status, tipo_registro, metodo_registro }
   */
  async registrar(datos) {
    const respuesta = await api.post('/asistencia', datos)
    return extraerDatos(respuesta)
  },

  /**
   * Registrar asistencia con reconocimiento facial y geolocalización.
   * @param {Object} datos - { latitud, longitud, id_empleado, confianza, imagen }
   */
  async registrarFacial(datos) {
    const respuesta = await api.post('/asistencia/geo-facial', datos)
    return respuesta.data
  },

  /**
   * Actualizar asistencia existente.
   * @param {Number|String} id - ID de la asistencia
   * @param {Object} datos - Datos a actualizar
   */
  async actualizar(id, datos) {
    const respuesta = await api.put(`/asistencia/${id}`, datos)
    return extraerDatos(respuesta)
  },

  /**
   * Actualizar manualmente las horas extras y observación.
   * @param {Number|String} id - ID de la asistencia
   * @param {Object} datos - { horas_extras, observacion }
   */
  async actualizarHorasExtras(id, datos) {
    const respuesta = await api.put(`/asistencia/${id}/horas-extras`, datos)
    return extraerDatos(respuesta)
  },

  /**
   * Eliminar un registro de asistencia.
   * @param {number} id
   */
  async eliminar(id) {
    const respuesta = await api.delete(`/asistencia/${id}`)
    return respuesta.data
  },
}
