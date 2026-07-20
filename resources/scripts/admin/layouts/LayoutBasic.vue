<template>
  <div v-if="isAppLoaded" class="premium-app-shell">
    <NotificationRoot />

    <SiteHeader />
    <SiteSidebar />
    <ExchangeRateBulkUpdateModal />

    <main
      class="premium-main"
      :class="{
        'premium-main--electronic-invoicing': route.name === 'electronic-invoicing.index',
        'premium-main--document-templates': route.name === 'document-templates.index',
      }"
    >
      <div class="premium-route-view">
        <router-view />
      </div>
    </main>
  </div>

  <BaseGlobalLoader v-else />
</template>

<script setup>
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useModalStore } from '@/scripts/stores/modal'
import { useExchangeRateStore } from '@/scripts/admin/stores/exchange-rate'
import { useCompanyStore } from '@/scripts/admin/stores/company'

import SiteHeader from '@/scripts/admin/layouts/partials/TheSiteHeader.vue'
import SiteSidebar from '@/scripts/admin/layouts/partials/TheSiteSidebar.vue'
import NotificationRoot from '@/scripts/components/notifications/NotificationRoot.vue'
import ExchangeRateBulkUpdateModal from '@/scripts/admin/components/modal-components/ExchangeRateBulkUpdateModal.vue'

const globalStore = useGlobalStore()
const route = useRoute()
const userStore = useUserStore()
const router = useRouter()
const modalStore = useModalStore()
const exchangeRateStore = useExchangeRateStore()
const companyStore = useCompanyStore()

const isAppLoaded = computed(() => globalStore.isAppLoaded)

onMounted(() => {
  globalStore.bootstrap().then((res) => {
    const companySetup = res.data.company_setup

    if (companySetup && !companySetup.complete) {
      if (route.name !== 'company.info') {
        router.replace({
          name: 'company.info',
          query: { setup: 'required' },
        })
      }
      return
    }

    if (route.meta.ability && !userStore.hasAbilities(route.meta.ability)) {
      router.push({ name: 'account.settings' })
    } else if (route.meta.isOwner && !userStore.currentUser.is_owner) {
      router.push({ name: 'account.settings' })
    }

    if (
      res.data.current_company_settings.bulk_exchange_rate_configured === 'NO'
    ) {
      exchangeRateStore.fetchBulkCurrencies().then((response) => {
        if (response.data.currencies.length) {
          modalStore.openModal({
            componentName: 'ExchangeRateBulkUpdateModal',
            size: 'sm',
          })
        } else {
          companyStore.updateCompanySettings({
            data: {
              settings: {
                bulk_exchange_rate_configured: 'YES',
              },
            },
          })
        }
      })
    }
  })
})
</script>

<style scoped>
.premium-app-shell {
  height: 100vh;
  height: 100dvh;
  overflow: hidden;
  color: #0f172a;
  background: #f4f7fb;
}

.premium-main {
  position: relative;
  min-width: 0;
  height: 100vh;
  height: 100dvh;
  min-height: 0;
  padding-top: 76px;
  padding-left: 272px;
  overflow-x: hidden;
  overflow-y: auto;
  overscroll-behavior-y: contain;
  scroll-padding-top: 24px;
  scrollbar-gutter: stable;
  -webkit-overflow-scrolling: touch;
}

.premium-route-view {
  min-width: 0;
  min-height: calc(100vh - 76px);
  padding-bottom: 1px;
}

.premium-main--document-templates :deep(.document-templates-page) {
  min-width: 0;
  padding-bottom: 72px;
}

.premium-main--electronic-invoicing :deep(.premium-page-content > div > section:first-child) {
  border: 1px solid rgba(59, 130, 246, .7) !important;
  background:
    radial-gradient(circle at 88% 0%, rgba(34, 211, 238, .24), transparent 28%),
    radial-gradient(circle at 35% 110%, rgba(139, 92, 246, .22), transparent 35%),
    linear-gradient(135deg, #020617 0%, #082f78 54%, #312e81 100%) !important;
  box-shadow: 0 24px 60px rgba(15, 23, 42, .28) !important;
}

.premium-main--electronic-invoicing :deep(.premium-page-content > div > section:first-child > div.relative > div:first-child > div:first-child) {
  border-color: rgba(255, 255, 255, .42) !important;
  background: rgba(255, 255, 255, .18) !important;
  color: #ffffff !important;
}

.premium-main--electronic-invoicing :deep(.premium-page-content > div > section:first-child > div.relative > div:last-child) {
  border-color: #bfdbfe !important;
  background: rgba(255, 255, 255, .96) !important;
  color: #0f172a !important;
  box-shadow: 0 18px 40px rgba(2, 6, 23, .2);
  backdrop-filter: none !important;
}

.premium-main--electronic-invoicing :deep(.premium-page-content > div > section:first-child > div.relative > div:last-child *) {
  color: inherit !important;
}

.premium-main--electronic-invoicing :deep(.premium-page-content > div > section:first-child > div.relative > div:last-child > div:first-child > span:last-child) {
  background: #2563eb !important;
  color: #ffffff !important;
}

@media (max-width: 767px) {
  .premium-main {
    padding-top: 66px;
    padding-left: 0;
  }

  .premium-route-view {
    min-height: calc(100vh - 66px);
  }
}
</style>
