import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/data-isolation'
  },
  {
    path: '/data-isolation',
    name: 'DataIsolation',
    component: () => import('@/views/DataIsolation/index.vue'),
    meta: { title: '数据隔离规则' }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound/index.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  document.title = to.meta.title ? `${to.meta.title} - 数据隔离管理系统` : '数据隔离管理系统'
  next()
})

export default router
