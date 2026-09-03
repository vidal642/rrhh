/**
 * Servicio de Empleados
 * Centraliza todas las llamadas a la API relacionadas con empleados.
 */
import api from '../plugins/axios'

// Extraer datos de la respuesta estandarizada
const extraerDatos = (respuesta) => respuesta.data?.datos ?? respuesta.data

export const EmpleadoServicio = {
  /**
   * Obtener todos los empleados con filtros opcionales.
   * @param {Object} filtros - { buscar, estado, departamento_id, cargo_id }
   */
  async obtenerTodos(filtros = {}) {
    const respuesta = await api.get('/empleados', { params: filtros })
    return extraerDatos(respuesta)
  },

  /**
   * Obtener un empleado por ID.
   * @param {number} id
   */
  async obtenerPorId(id) {
    const respuesta = await api.get(`/empleados/${id}`)
    return extraerDatos(respuesta)
  },

  /**
   * Crear un nuevo empleado.
   * @param {Object} datos - Datos del empleado
   */
  async crear(datos) {
    const respuesta = await api.post('/empleados', datos)
    return extraerDatos(respuesta)
  },

  /**
   * Actualizar un empleado existente.
   * @param {number} id
   * @param {Object} datos
   */
  async actualizar(id, datos) {
    const respuesta = await api.put(`/empleados/${id}`, datos)
    return extraerDatos(respuesta)
  },

  /**
   * Eliminar un empleado.
   * @param {number} id
   */
  async eliminar(id) {
    const respuesta = await api.delete(`/empleados/${id}`)
    return respuesta.data
  },
}
