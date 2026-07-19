<template>
  <div v-if="isAppLoaded" class="premium-app-shell">
    <NotificationRoot />

    <SiteHeader />
    <SiteSidebar />
    <ExchangeRateBulkUpdateModal />

    <main class="premium-main">
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
  min-height: 100vh;
  color: #0f172a;
  background: #f4f7fb;
}

.premium-main {
  min-height: 100vh;
  padding-top: 76px;
  padding-left: 272px;
  overflow-x: hidden;
}

.premium-route-view {
  min-height: calc(100vh - 76px);
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
