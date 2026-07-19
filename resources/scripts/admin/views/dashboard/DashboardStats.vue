<template>
  <section class="grid gap-5 md:grid-cols-2 2xl:grid-cols-4">
    <DashboardStatsItem
      v-if="userStore.hasAbilities(abilities.VIEW_INVOICE)"
      :loading="!dashboardStore.isDashboardDataLoaded"
      route="/admin/invoices"
      label="Chiffre d’affaires (HT)"
      icon-text="€"
      variant="blue"
      :trend="dashboardStore.salesGrowthPercent"
      description="vs N-1"
    >
      <BaseFormatMoney
        :amount="dashboardStore.totalSalesHt"
        :currency="companyStore.selectedCompanyCurrency"
      />
    </DashboardStatsItem>

    <DashboardStatsItem
      :loading="!dashboardStore.isDashboardDataLoaded"
      route="/admin/payments"
      label="Encaissements"
      icon-text="⌂"
      variant="green"
      :trend="dashboardStore.receiptsGrowthPercent"
      description="vs N-1"
    >
      <BaseFormatMoney
        :amount="dashboardStore.totalReceipts"
        :currency="companyStore.selectedCompanyCurrency"
      />
    </DashboardStatsItem>

    <DashboardStatsItem
      v-if="userStore.hasAbilities(abilities.VIEW_INVOICE)"
      :loading="!dashboardStore.isDashboardDataLoaded"
      route="/admin/invoices"
      label="Factures en attente"
      icon-text="◔"
      variant="orange"
      :description="`${dashboardStore.stats.pendingInvoiceCount} facture${dashboardStore.stats.pendingInvoiceCount > 1 ? 's' : ''}`"
    >
      <BaseFormatMoney
        :amount="dashboardStore.stats.totalAmountDue"
        :currency="companyStore.selectedCompanyCurrency"
      />
    </DashboardStatsItem>

    <DashboardStatsItem
      v-if="userStore.hasAbilities(abilities.VIEW_ESTIMATE)"
      :loading="!dashboardStore.isDashboardDataLoaded"
      route="/admin/estimates"
      label="Devis en attente"
      icon-text="▤"
      variant="purple"
      :description="`${dashboardStore.stats.pendingEstimateCount} devis`"
    >
      <BaseFormatMoney
        :amount="dashboardStore.stats.pendingEstimateAmount"
        :currency="companyStore.selectedCompanyCurrency"
      />
    </DashboardStatsItem>
  </section>
</template>

<script setup>
import abilities from '@/scripts/admin/stub/abilities'
import DashboardStatsItem from './DashboardStatsItem.vue'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useUserStore } from '@/scripts/admin/stores/user'

const dashboardStore = useDashboardStore()
const companyStore = useCompanyStore()
const userStore = useUserStore()
</script>
