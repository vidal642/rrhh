/**
 * Servicio de Ausencias
 * Centraliza todas las llamadas a la API de gestión de ausencias.
 */
import api from '../plugins/axios'

const extraerDatos = (respuesta) => respuesta.data?.datos ?? respuesta.data

export const AusenciaServicio = {
  /**
   * Obtener todas las ausencias con filtros opcionales.
   * @param {Object} filtros - { tipo, estado, empleado_id, departamento_id, fecha_inicio, fecha_fin }
   */
  async obtenerTodas(filtros = {}) {
    const respuesta = await api.get('/ausencias', { params: filtros })
    return extraerDatos(respuesta)
  },

  /**
   * Registrar una nueva ausencia.
   * @param {Object} datos - { employee_id, tipo, start_date, end_date, reason, status }
   */
  async registrar(datos) {
    const respuesta = await api.post('/ausencias', datos)
    return extraerDatos(respuesta)
  },

  /**
   * Actualizar una ausencia existente.
   * @param {number} id
   * @param {Object} datos
   */
  async actualizar(id, datos) {
    const respuesta = await api.put(`/ausencias/${id}`, datos)
    return extraerDatos(respuesta)
  },

  /**
   * Cambiar el estado de una ausencia (Aprobado / Rechazado / Pendiente).
   * @param {number} id
   * @param {string} estado
   */
  async cambiarEstado(id, estado) {
    const respuesta = await api.patch(`/ausencias/${id}/estado`, { estado })
    return extraerDatos(respuesta)
  },

  /**
   * Eliminar una ausencia.
   * @param {number} id
   */
  async eliminar(id) {
    const respuesta = await api.delete(`/ausencias/${id}`)
    return respuesta.data
  },
}
