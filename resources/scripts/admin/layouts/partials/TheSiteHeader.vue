<template>
  <header class="premium-header">
    <div class="mobile-brand">
      <button class="mobile-menu" @click.prevent="onToggle">
        <BaseIcon name="MenuIcon" class="h-6 w-6" />
      </button>
      <router-link to="/admin/dashboard">
        <MainLogo class="h-auto w-36" light-color="#2563eb" dark-color="#0f172a" />
      </router-link>
    </div>

    <div class="header-search">
      <GlobalSearchBar
        v-if="userStore.currentUser.is_owner || userStore.hasAbilities(abilities.VIEW_CUSTOMER)"
      />
    </div>

    <div class="header-actions">
      <button class="header-icon-button" type="button" aria-label="Notifications">
        <BaseIcon name="BellIcon" class="h-5 w-5" />
        <span class="notification-dot" />
      </button>

      <router-link
        to="/admin/settings/company-info"
        class="header-icon-button"
        aria-label="Paramètres"
      >
        <BaseIcon name="CogIcon" class="h-5 w-5" />
      </router-link>

      <div class="company-wrap">
        <CompanySwitcher />
      </div>

      <BaseDropdown width-class="w-52">
        <template #activator>
          <button class="profile-trigger" type="button">
            <img :src="previewAvatar" alt="Compte utilisateur" @error="useDefaultAvatar" />
            <span class="profile-copy">
              <strong>{{ userStore.currentUser?.name || 'Administrateur' }}</strong>
              <small>Mon compte</small>
            </span>
            <span class="profile-chevron">⌄</span>
          </button>
        </template>

        <router-link to="/admin/settings/account-settings">
          <BaseDropdownItem>
            <BaseIcon name="UserIcon" class="mr-3 h-5 w-5 text-slate-400" />
            Mon compte
          </BaseDropdownItem>
        </router-link>

        <router-link to="/admin/settings/company-info">
          <BaseDropdownItem>
            <BaseIcon name="CogIcon" class="mr-3 h-5 w-5 text-slate-400" />
            Paramètres
          </BaseDropdownItem>
        </router-link>

        <BaseDropdownItem @click="logout">
          <BaseIcon name="LogoutIcon" class="mr-3 h-5 w-5 text-slate-400" />
          {{ $t('navigation.logout') }}
        </BaseDropdownItem>
      </BaseDropdown>
    </div>
  </header>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/scripts/admin/stores/auth'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import CompanySwitcher from '@/scripts/components/CompanySwitcher.vue'
import GlobalSearchBar from '@/scripts/components/GlobalSearchBar.vue'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'

const authStore = useAuthStore()
const userStore = useUserStore()
const globalStore = useGlobalStore()
const router = useRouter()
const avatarFallback = ref(false)

const previewAvatar = computed(() => {
  const avatar = userStore.currentUser?.avatar
  return !avatarFallback.value && typeof avatar === 'string' && avatar.trim()
    ? avatar
    : '/img/default-avatar.jpg'
})

function useDefaultAvatar() {
  avatarFallback.value = true
}

async function logout() {
  await authStore.logout()
  router.push('/login')
}

function onToggle() {
  globalStore.setSidebarVisibility(true)
}
</script>

<style scoped>
.premium-header {
  position: fixed;
  z-index: 24;
  top: 0;
  right: 0;
  left: 272px;
  height: 76px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 22px;
  padding: 0 26px;
  border-bottom: 1px solid rgba(148,163,184,.16);
  background: rgba(255,255,255,.88);
  backdrop-filter: blur(22px);
  box-shadow: 0 8px 30px rgba(15,23,42,.04);
}
.mobile-brand { display: none; }
.header-search { width: min(520px, 46vw); }
.header-actions { margin-left: auto; display: flex; align-items: center; gap: 9px; }
.header-icon-button { position: relative; display: flex; align-items: center; justify-content: center; width: 39px; height: 39px; border: 1px solid transparent; border-radius: 12px; color: #334155; background: transparent; transition: all .18s ease; }
.header-icon-button:hover { color: #2563eb; border-color: #dbeafe; background: #eff6ff; }
.notification-dot { position: absolute; right: 8px; top: 7px; width: 7px; height: 7px; border: 2px solid #fff; border-radius: 999px; background: #ef4444; }
.company-wrap { min-width: 155px; }
.profile-trigger { display: flex; align-items: center; gap: 10px; min-width: 170px; min-height: 46px; padding: 5px 8px; border: 1px solid #e2e8f0; border-radius: 14px; color: #0f172a; background: #fff; box-shadow: 0 7px 20px rgba(15,23,42,.05); }
.profile-trigger img { width: 34px; height: 34px; border-radius: 10px; object-fit: cover; }
.profile-copy { flex: 1; min-width: 0; text-align: left; }
.profile-copy strong { display: block; overflow: hidden; font-size: .68rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.profile-copy small { display: block; margin-top: 2px; color: #94a3b8; font-size: .55rem; }
.profile-chevron { color: #64748b; }
@media (max-width: 1024px) {
  .premium-header { padding: 0 16px; }
  .profile-copy, .profile-chevron { display: none; }
  .profile-trigger { min-width: 46px; width: 46px; padding: 5px; }
  .company-wrap { min-width: 125px; }
}
@media (max-width: 767px) {
  .premium-header { left: 0; height: 66px; justify-content: space-between; }
  .mobile-brand { display: flex; align-items: center; gap: 10px; }
  .mobile-menu { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 11px; color: #334155; background: #f1f5f9; }
  .header-search, .company-wrap, .profile-copy, .profile-chevron { display: none; }
  .header-actions { margin-left: 0; }
  .profile-trigger { min-width: 38px; width: 38px; min-height: 38px; height: 38px; padding: 3px; }
  .profile-trigger img { width: 30px; height: 30px; }
}
</style>
