import { ref } from 'vue'

// Estado global para el modal de confirmación
const state = ref({
  visible: false,
  mensaje: 'Esta operación es irreversible',
  titulo: '¿Está seguro que desea eliminar este registro?',
  textoConfirmar: 'Eliminar',
  textoCancelar: 'Cancelar',
  resolverPromise: null
})

export function usarConfirmacion() {
  /**
   * Muestra el modal de confirmación y retorna una promesa
   * @param {string} mensaje - Mensaje a mostrar
   * @param {Object} opciones - Opciones extra (titulo, textoConfirmar, textoCancelar)
   * @returns {Promise<boolean>}
   */
  const confirmar = (mensaje, opciones = {}) => {
    return new Promise((resolve) => {
      state.value = {
        visible: true,
        mensaje: mensaje || 'Esta operación es irreversible',
        titulo: opciones.titulo || '¿Está seguro que desea eliminar este registro?',
        textoConfirmar: opciones.textoConfirmar || 'Eliminar',
        textoCancelar: opciones.textoCancelar || 'Cancelar',
        resolverPromise: resolve
      }
    })
  }

  const aceptar = () => {
    if (state.value.resolverPromise) {
      state.value.resolverPromise(true)
    }
    state.value.visible = false
  }

  const cancelar = () => {
    if (state.value.resolverPromise) {
      state.value.resolverPromise(false)
    }
    state.value.visible = false
  }

  return { state, confirmar, aceptar, cancelar }
}
