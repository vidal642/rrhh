import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'

const routes = [
  {
    path: '/',
    name: 'InicioSesion',
    component: () => import('../views/Login.vue'),
    meta: { publica: true }
  },



  // ── Quiosco de asistencia facial (acceso libre desde la red interna) ──
  {
    path: '/asistencia-facial',
    name: 'AsistenciaFacial',
    component: () => import('../views/AsistenciaFacial.vue'),
    meta: { publica: true }
  },

  {
    path: '/dashboard',
    component: () => import('../views/Layout.vue'),
    meta: { requiereAuth: true },
    children: [
      {
        path: '',
        name: 'Panel',
        component: () => import('../views/Dashboard.vue')
      },

      {
        path: '/empleados',
        name: 'Empleados',
        component: () => import('../views/Empleados/Listado.vue')
      },
      {
        path: '/departamentos',
        name: 'Departamentos',
        component: () => import('../views/Departamentos/Listado.vue')
      },
      {
        path: '/cargos',
        name: 'Cargos',
        component: () => import('../views/Cargos/Listado.vue')
      },
      {
        path: '/asistencia',
        name: 'Asistencia',
        component: () => import('../views/Asistencia/Index.vue')
      },
      {
        path: '/ausencias',
        name: 'Ausencias',
        component: () => import('../views/Ausencias/Listado.vue')
      },

      {
        path: '/perfil',
        name: 'Perfil',
        component: () => import('../views/Perfil.vue')
      },
      {
        path: '/planillas',
        name: 'Planillas',
        component: () => import('../views/Planillas/Listado.vue')
      },

      {
        path: '/usuarios',
        name: 'Usuarios',
        component: () => import('../views/Usuarios/Listado.vue')
      },
      {
        path: '/reportes',
        name: 'Reportes',
        component: () => import('../views/Reportes/Index.vue')
      },
      {
        path: '/configuracion',
        name: 'Configuracion',
        component: () => import('../views/Configuracion/Index.vue')
      },
    ]
  },

  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (destino, _origen, siguiente) => {
  const autenticacion = useAuthStore()

  if (autenticacion.token && !autenticacion.verificado) {
    await autenticacion.verificarToken()
  }

  const estaAutenticado = !!autenticacion.token
  const requiereAuth = destino.matched.some(record => record.meta.requiereAuth)
  const esPublica = destino.matched.some(record => record.meta.publica)

  if (requiereAuth && !estaAutenticado) {
    return siguiente({ path: '/', query: { redirigir: destino.fullPath } })
  }

  if (esPublica && estaAutenticado && destino.path === '/') {
    return siguiente({ path: '/dashboard' })
  }

  return siguiente()
})

export default router
