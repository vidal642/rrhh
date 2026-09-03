/**
 * Composable: usarPaginacion
 * Maneja la lógica de paginación de forma reutilizable.
 *
 * @param {Ref<Array>} lista - Ref reactiva con la lista completa de elementos
 * @param {number} porPagina - Cantidad de elementos por página (default: 10)
 */
import { ref, computed, watch } from 'vue'

export function usarPaginacion(lista, porPagina = 10) {
  const paginaActual = ref(1)

  // Reiniciar a la primera página cuando cambia la lista
  watch(lista, () => { paginaActual.value = 1 })

  const totalPaginas = computed(() =>
    Math.max(1, Math.ceil(lista.value.length / porPagina))
  )

  const elementosPaginados = computed(() => {
    const inicio = (paginaActual.value - 1) * porPagina
    return lista.value.slice(inicio, inicio + porPagina)
  })

  const indiceInicio = computed(() =>
    lista.value.length ? (paginaActual.value - 1) * porPagina + 1 : 0
  )

  const indiceFin = computed(() =>
    Math.min(paginaActual.value * porPagina, lista.value.length)
  )

  const paginaAnterior = () => {
    if (paginaActual.value > 1) paginaActual.value--
  }

  const paginaSiguiente = () => {
    if (paginaActual.value < totalPaginas.value) paginaActual.value++
  }

  const irAPagina = (num) => {
    if (num >= 1 && num <= totalPaginas.value) paginaActual.value = num
  }

  return {
    paginaActual,
    totalPaginas,
    elementosPaginados,
    indiceInicio,
    indiceFin,
    paginaAnterior,
    paginaSiguiente,
    irAPagina,
  }
}
