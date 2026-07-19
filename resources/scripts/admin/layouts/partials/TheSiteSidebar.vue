<template>
  <TransitionRoot as="template" :show="globalStore.isSidebarOpen">
    <Dialog
      as="div"
      class="fixed inset-0 z-40 flex md:hidden"
      @close="globalStore.setSidebarVisibility(false)"
    >
      <TransitionChild
        as="template"
        enter="transition-opacity ease-linear duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="transition-opacity ease-linear duration-300"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <DialogOverlay class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" />
      </TransitionChild>

      <TransitionChild
        as="template"
        enter="transition ease-in-out duration-300"
        enter-from="-translate-x-full"
        enter-to="translate-x-0"
        leave="transition ease-in-out duration-300"
        leave-from="translate-x-0"
        leave-to="-translate-x-full"
      >
        <div class="relative flex w-full max-w-[290px] flex-col">
          <PremiumSidebarContent @navigate="globalStore.setSidebarVisibility(false)" />
          <button
            class="absolute -right-12 top-3 flex h-10 w-10 items-center justify-center rounded-full text-white"
            @click="globalStore.setSidebarVisibility(false)"
          >
            <BaseIcon name="XIcon" class="h-6 w-6" />
          </button>
        </div>
      </TransitionChild>
    </Dialog>
  </TransitionRoot>

  <aside class="premium-sidebar hidden md:flex">
    <div class="brand-wrap">
      <router-link to="/admin/dashboard" aria-label="AutoFacture">
        <MainLogo class="h-auto w-[190px]" light-color="#38bdf8" dark-color="#ffffff" />
      </router-link>
      <span class="collapse-mark">‹‹</span>
    </div>

    <nav class="sidebar-nav">
      <template v-for="(menu, groupIndex) in globalStore.menuGroups" :key="groupIndex">
        <router-link
          v-for="item in menu"
          :key="item.name || item.link"
          :to="item.link"
          class="sidebar-link"
          :class="{ active: hasActiveUrl(item.link) }"
        >
          <BaseIcon :name="item.icon" class="sidebar-icon" />
          <span>{{ $t(item.title) }}</span>
        </router-link>
      </template>
    </nav>

    <div class="quick-section">
      <p>Actions rapides</p>
      <router-link to="/admin/estimates/create" class="quick-link">
        <span class="quick-icon green">+</span> Nouveau devis
      </router-link>
      <router-link to="/admin/invoices/create" class="quick-link">
        <span class="quick-icon purple">+</span> Nouvelle facture
      </router-link>
      <router-link to="/admin/customers/create" class="quick-link">
        <span class="quick-icon blue">+</span> Nouveau client
      </router-link>
      <router-link to="/admin/settings/backup" class="quick-link">
        <span class="quick-icon orange">↥</span> Sauvegarde
      </router-link>
    </div>

    <div class="sidebar-bottom">
      <router-link to="/admin/settings/account-settings" class="profile-card">
        <img :src="avatar" alt="Compte utilisateur" @error="avatarFallback = true" />
        <div>
          <strong>{{ userStore.currentUser?.name || 'Administrateur' }}</strong>
          <span>Administrateur</span>
        </div>
        <b>⌄</b>
      </router-link>

      <router-link to="/admin/settings/backup" class="backup-card">
        <div class="backup-title">
          <span class="backup-shield">◇</span>
          <div>
            <strong>Sauvegarde sécurisée</strong>
            <small>Protégez vos données locales</small>
          </div>
        </div>
        <div class="backup-progress"><i /></div>
        <div class="backup-action">Configurer la sauvegarde <span>→</span></div>
      </router-link>
    </div>
  </aside>
</template>

<script setup>
import { computed, defineComponent, h, ref } from 'vue'
import {
  Dialog,
  DialogOverlay,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { useRoute } from 'vue-router'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useUserStore } from '@/scripts/admin/stores/user'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'

const route = useRoute()
const globalStore = useGlobalStore()
const userStore = useUserStore()
const avatarFallback = ref(false)

const avatar = computed(() => {
  const value = userStore.currentUser?.avatar
  return !avatarFallback.value && typeof value === 'string' && value.trim()
    ? value
    : '/img/default-avatar.jpg'
})

function hasActiveUrl(url) {
  if (!url) return false
  return route.path === url || route.path.startsWith(`${url}/`)
}

const PremiumSidebarContent = defineComponent({
  emits: ['navigate'],
  setup(_, { emit }) {
    return () =>
      h('div', { class: 'premium-sidebar mobile-sidebar' }, [
        h('div', { class: 'brand-wrap' }, [
          h(MainLogo, {
            class: 'h-auto w-[190px]',
            lightColor: '#38bdf8',
            darkColor: '#ffffff',
          }),
        ]),
        h(
          'nav',
          { class: 'sidebar-nav' },
          globalStore.menuGroups.flatMap((menu) =>
            menu.map((item) =>
              h(
                'a',
                {
                  href: item.link,
                  class: ['sidebar-link', { active: hasActiveUrl(item.link) }],
                  onClick: (event) => {
                    event.preventDefault()
                    emit('navigate')
                    window.location.assign(item.link)
                  },
                },
                [h('span', { class: 'mobile-link-dot' }), h('span', item.title)]
              )
            )
          )
        ),
      ])
  },
})
</script>

<style scoped>
.premium-sidebar {
  position: fixed;
  inset: 0 auto 0 0;
  z-index: 30;
  width: 272px;
  min-height: 100vh;
  flex-direction: column;
  overflow-y: auto;
  color: #e2e8f0;
  background:
    radial-gradient(circle at 28% 18%, rgba(37,99,235,.22), transparent 30%),
    linear-gradient(180deg, #03112f 0%, #06183f 50%, #04122f 100%);
  box-shadow: 20px 0 55px rgba(15,23,42,.16);
}
.mobile-sidebar { position: relative; display: flex; width: 100%; }
.brand-wrap { display: flex; align-items: center; justify-content: space-between; min-height: 88px; padding: 20px 22px 14px; }
.collapse-mark { color: #94a3b8; font-size: 1.1rem; letter-spacing: -5px; }
.sidebar-nav { display: flex; flex-direction: column; gap: 5px; padding: 6px 16px 0; }
.sidebar-link { position: relative; display: flex; align-items: center; gap: 13px; min-height: 45px; padding: 0 14px; border: 1px solid transparent; border-radius: 12px; color: #dbeafe; font-size: .78rem; font-weight: 620; transition: all .18s ease; }
.sidebar-link:hover { color: #fff; background: rgba(255,255,255,.06); transform: translateX(2px); }
.sidebar-link.active { color: #fff; border-color: rgba(147,197,253,.65); background: linear-gradient(100deg,#0284c7 0%,#2563eb 42%,#7c3aed 100%); box-shadow: 0 0 24px rgba(59,130,246,.45), inset 0 1px rgba(255,255,255,.18); }
.sidebar-icon { width: 19px; height: 19px; color: #93c5fd; }
.sidebar-link.active .sidebar-icon { color: #fff; }
.mobile-link-dot { width: 7px; height: 7px; border-radius: 999px; background: #60a5fa; }
.quick-section { margin: 20px 18px 0; padding-top: 17px; border-top: 1px solid rgba(148,163,184,.16); }
.quick-section > p { margin: 0 0 10px 5px; color: #7f94bc; font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; }
.quick-link { display: flex; align-items: center; gap: 11px; min-height: 38px; padding: 0 6px; color: #dbeafe; font-size: .72rem; transition: color .18s ease; }
.quick-link:hover { color: #fff; }
.quick-icon { display: inline-flex; align-items: center; justify-content: center; width: 23px; height: 23px; border-radius: 7px; color: #fff; font-size: .85rem; font-weight: 800; box-shadow: 0 5px 14px rgba(0,0,0,.18); }
.quick-icon.green { background: linear-gradient(135deg,#10b981,#22c55e); }
.quick-icon.purple { background: linear-gradient(135deg,#8b5cf6,#a855f7); }
.quick-icon.blue { background: linear-gradient(135deg,#0ea5e9,#2563eb); }
.quick-icon.orange { background: linear-gradient(135deg,#fb923c,#f59e0b); }
.sidebar-bottom { margin-top: auto; display: grid; gap: 12px; padding: 18px; }
.profile-card, .backup-card { border: 1px solid rgba(148,163,184,.18); border-radius: 15px; background: rgba(5,23,59,.72); box-shadow: inset 0 1px rgba(255,255,255,.04); }
.profile-card { display: grid; grid-template-columns: 38px minmax(0,1fr) auto; align-items: center; gap: 10px; padding: 12px; }
.profile-card img { width: 38px; height: 38px; border-radius: 12px; object-fit: cover; border: 2px solid rgba(52,211,153,.7); }
.profile-card strong { display: block; overflow: hidden; color: #fff; font-size: .7rem; text-overflow: ellipsis; white-space: nowrap; }
.profile-card span { display: block; margin-top: 3px; color: #7f94bc; font-size: .6rem; }
.profile-card b { color: #cbd5e1; }
.backup-card { padding: 13px; }
.backup-title { display: flex; align-items: center; gap: 10px; }
.backup-shield { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 11px; color: #7dd3fc; background: rgba(14,165,233,.16); font-size: 1.25rem; }
.backup-title strong { display: block; color: #fff; font-size: .66rem; }
.backup-title small { display: block; margin-top: 2px; color: #7f94bc; font-size: .54rem; }
.backup-progress { height: 5px; margin-top: 12px; overflow: hidden; border-radius: 999px; background: rgba(148,163,184,.18); }
.backup-progress i { display: block; width: 72%; height: 100%; border-radius: inherit; background: linear-gradient(90deg,#2563eb,#22d3ee); }
.backup-action { display: flex; justify-content: space-between; margin-top: 10px; color: #93c5fd; font-size: .58rem; }
</style>
