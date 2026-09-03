/**
 * Composable: usarNotificacion
 * Maneja el sistema de toasts/notificaciones de forma global y reutilizable.
 */
import { ref } from 'vue'

// Estado compartido entre componentes (singleton)
const notificaciones = ref([])

export function usarNotificacion() {
  /**
   * Mostrar una notificación toast.
   * @param {string} mensaje - Texto a mostrar
   * @param {'success'|'error'|'warning'|'info'} tipo - Tipo de notificación
   * @param {number} duracion - Duración en ms (default: 3500)
   */
  const notificar = (mensaje, tipo = 'success', duracion = 3500) => {
    const id = Date.now() + Math.random()
    notificaciones.value.push({ id, mensaje, tipo })
    setTimeout(() => {
      notificaciones.value = notificaciones.value.filter(n => n.id !== id)
    }, duracion)
  }

  const exito  = (mensaje) => notificar(mensaje, 'success')
  const error  = (mensaje) => notificar(mensaje, 'error', 5000)
  const aviso  = (mensaje) => notificar(mensaje, 'warning')
  const info   = (mensaje) => notificar(mensaje, 'info')

  const cerrar = (id) => {
    notificaciones.value = notificaciones.value.filter(n => n.id !== id)
  }

  return { notificaciones, notificar, exito, error, aviso, info, cerrar }
}
