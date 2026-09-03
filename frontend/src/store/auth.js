import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: sessionStorage.getItem('token') || null,
    verificado: false,
    // null = no consultado, true = tiene rostro, false = sin rostro
    rostroRegistrado: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    esAdmin: (state) => !state.user?.id_empleado || state.user?.rol?.toLowerCase() === 'admin' || state.user?.rol?.toLowerCase() === 'administrador',
  },
  actions: {
    setAuth(user, token) {
      this.user = user
      this.token = token
      this.verificado = true
      this.rostroRegistrado = null  // resetear al hacer login
      sessionStorage.setItem('token', token)
    },
    logout() {
      this.user = null
      this.token = null
      this.verificado = true
      this.rostroRegistrado = null
      sessionStorage.removeItem('token')
    },
    async verificarToken() {
      if (!this.token) {
        this.verificado = true
        return false
      }
      try {
        const { default: api } = await import('../plugins/axios')
        const res = await api.get('/user')
        this.user = res.data.datos ?? res.data
        this.verificado = true
        return true
      } catch (err) {
        this.logout()
        return false
      }
    },
    async verificarRostroRegistrado() {
      try {
        const { default: api } = await import('../plugins/axios')
        const res = await api.get('/reconocimiento/estado')
        const datos = res.data.datos ?? res.data
        this.rostroRegistrado = datos.rostro_registrado ?? true
        return this.rostroRegistrado
      } catch {
        // Si falla la consulta y es empleado, asumimos que no tiene rostro para forzar registro
        if (this.user?.rol === 'Empleado') {
          this.rostroRegistrado = false
          return false
        }
        // Para otros roles, no bloqueamos el acceso
        this.rostroRegistrado = true
        return true
      }
    }
  }
})
