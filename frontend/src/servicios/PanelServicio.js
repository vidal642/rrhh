/**
 * Servicio del Panel Principal
 * Obtiene métricas y datos para gráficos del dashboard.
 */
import api from '../plugins/axios'

const extraerDatos = (respuesta) => respuesta.data?.datos ?? respuesta.data

export const PanelServicio = {
  /**
   * Obtener todas las métricas del panel principal.
   * Incluye: empleados, asistencia de hoy, ausencias, planillas y datos para gráficos.
   */
  async obtenerMetricas() {
    const respuesta = await api.get('/dashboard')
    return extraerDatos(respuesta)
  },
}
