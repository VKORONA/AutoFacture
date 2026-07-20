import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import abilities from '@/scripts/admin/stub/abilities'

// Admin routes
import AdminRoutes from '@/scripts/admin/admin-router'
// Customer routes
import CustomerRoutes from '@/scripts/customer/customer-router'

const LayoutBasic = () => import('@/scripts/admin/layouts/LayoutBasic.vue')
const ElectronicInvoicing = () =>
  import('@/scripts/admin/views/electronic-invoicing/Index.vue')
const Accounting = () => import('@/scripts/admin/views/accounting/Index.vue')
const DocumentTemplates = () =>
  import('@/scripts/admin/views/settings/DocumentTemplatesSetting.vue')

const AdditionalAdminRoutes = [
  {
    path: '/admin/electronic-invoicing',
    component: LayoutBasic,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'electronic-invoicing.index',
        meta: {
          requiresAuth: true,
          ability: abilities.VIEW_INVOICE,
        },
        component: ElectronicInvoicing,
      },
    ],
  },
  {
    path: '/admin/accounting',
    component: LayoutBasic,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'accounting.index',
        meta: {
          requiresAuth: true,
          ability: abilities.VIEW_INVOICE,
        },
        component: Accounting,
      },
    ],
  },
  {
    path: '/admin/document-templates',
    component: LayoutBasic,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'document-templates.index',
        meta: {
          requiresAuth: true,
          ability: abilities.VIEW_INVOICE,
        },
        component: DocumentTemplates,
      },
    ],
  },
]

let routes = []
routes = routes.concat(AdditionalAdminRoutes, AdminRoutes, CustomerRoutes)

const router = createRouter({
  history: createWebHistory(),
  linkActiveClass: 'active',
  routes,
})

router.beforeEach((to, from, next) => {
  const userStore = useUserStore()
  const globalStore = useGlobalStore()
  let ability = to.meta.ability
  const { isAppLoaded } = globalStore

  if (ability && isAppLoaded && to.meta.requiresAuth) {
    if (userStore.hasAbilities(ability)) {
      next()
    } else next({ name: 'account.settings' })
  } else if (to.meta.isOwner && isAppLoaded) {
    if (userStore.currentUser.is_owner) {
      next()
    } else next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router
