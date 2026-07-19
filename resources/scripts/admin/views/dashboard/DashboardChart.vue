<template>
  <section class="mt-6 grid gap-6 2xl:grid-cols-[minmax(0,1.75fr)_minmax(340px,0.9fr)]">
    <article class="premium-panel revenue-panel">
      <header class="panel-header">
        <div>
          <div class="panel-eyebrow">Pilotage financier</div>
          <h2>Évolution du chiffre d’affaires (HT)</h2>
        </div>
        <div class="fiscal-chip">
          <span class="status-dot" />
          {{ dashboardStore.fiscalPeriod.label || 'Exercice en cours' }}
        </div>
      </header>

      <PremiumRevenueChart
        v-if="dashboardStore.isDashboardDataLoaded"
        :labels="dashboardStore.chartData.months"
        :current-revenue="dashboardStore.chartData.invoiceTotals"
        :previous-revenue="dashboardStore.chartData.previousInvoiceTotals"
        :receipts="dashboardStore.chartData.receiptTotals"
      />
      <ChartPlaceholder v-else />
    </article>

    <div class="grid gap-6">
      <article class="premium-panel status-panel">
        <header class="panel-header compact">
          <div>
            <div class="panel-eyebrow">Suivi des documents</div>
            <h2>Répartition des factures</h2>
          </div>
        </header>

        <div class="status-content">
          <InvoiceStatusDonut
            :paid="dashboardStore.invoiceDistribution.paid"
            :pending="dashboardStore.invoiceDistribution.pending"
            :overdue="dashboardStore.invoiceDistribution.overdue"
            :credit-notes="dashboardStore.invoiceDistribution.creditNotes"
          />

          <div class="status-legend">
            <div v-for="item in distributionItems" :key="item.label" class="legend-row">
              <span class="legend-dot" :style="{ backgroundColor: item.color }" />
              <span class="legend-label">{{ item.label }}</span>
              <strong>{{ item.value }}</strong>
              <small>{{ item.percent }} %</small>
            </div>
          </div>
        </div>

        <router-link to="/admin/invoices" class="detail-link">
          Voir le détail
          <span>→</span>
        </router-link>
      </article>

      <article class="action-panel">
        <div class="action-copy">
          <span class="action-kicker">AutoFacture Premium</span>
          <h2>Créez une facture en 30 secondes</h2>
          <p>Une saisie rapide, des calculs sécurisés et une mise en page professionnelle.</p>
          <router-link to="/admin/invoices/create" class="action-button">
            <span>+</span>
            Nouvelle facture
          </router-link>
        </div>

        <div class="document-orbit" aria-hidden="true">
          <div class="orbit-ring ring-one" />
          <div class="orbit-ring ring-two" />
          <div class="document-card">
            <span>FACTURE</span>
            <i />
            <i />
            <i class="short" />
            <strong>€</strong>
          </div>
        </div>

        <div class="action-features">
          <span>▤ Modèles personnalisés</span>
          <span>✦ Édition intelligente</span>
          <span>◇ Conformité garantie</span>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import PremiumRevenueChart from '@/scripts/admin/components/charts/PremiumRevenueChart.vue'
import InvoiceStatusDonut from '@/scripts/admin/components/charts/InvoiceStatusDonut.vue'
import ChartPlaceholder from './DashboardChartPlaceholder.vue'

const dashboardStore = useDashboardStore()

const distributionItems = computed(() => {
  const source = [
    { label: 'Payées', value: dashboardStore.invoiceDistribution.paid, color: '#34d399' },
    { label: 'En attente', value: dashboardStore.invoiceDistribution.pending, color: '#fb923c' },
    { label: 'En retard', value: dashboardStore.invoiceDistribution.overdue, color: '#fb7185' },
    { label: 'Avoirs', value: dashboardStore.invoiceDistribution.creditNotes, color: '#8b5cf6' },
  ]
  const total = source.reduce((sum, item) => sum + Number(item.value || 0), 0)

  return source.map((item) => ({
    ...item,
    percent: total ? Math.round((item.value / total) * 100) : 0,
  }))
})
</script>

<style scoped>
.premium-panel {
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
  backdrop-filter: blur(18px);
}

.revenue-panel { padding: 24px 26px 18px; min-width: 0; }
.status-panel { padding: 22px; }
.panel-header { display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; margin-bottom: 12px; }
.panel-header.compact { margin-bottom: 4px; }
.panel-header h2 { margin-top: 3px; color: #0f172a; font-size: 1rem; font-weight: 750; letter-spacing: -0.02em; }
.panel-eyebrow { color: #7c3aed; font-size: 0.68rem; font-weight: 750; text-transform: uppercase; letter-spacing: 0.12em; }
.fiscal-chip { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border: 1px solid #dbeafe; border-radius: 12px; color: #334155; background: #f8fbff; font-size: 0.75rem; font-weight: 650; white-space: nowrap; }
.status-dot { width: 7px; height: 7px; border-radius: 999px; background: #22c55e; box-shadow: 0 0 0 4px rgba(34,197,94,.12); }
.status-content { display: flex; align-items: center; gap: 22px; min-height: 190px; }
.status-legend { flex: 1; min-width: 0; }
.legend-row { display: grid; grid-template-columns: 9px minmax(78px,1fr) auto 38px; align-items: center; gap: 8px; padding: 8px 0; color: #475569; font-size: 0.76rem; }
.legend-dot { width: 9px; height: 9px; border-radius: 999px; }
.legend-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.legend-row strong { color: #0f172a; }
.legend-row small { color: #94a3b8; text-align: right; }
.detail-link { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding: 11px 14px; border: 1px solid #dbeafe; border-radius: 12px; color: #2563eb; font-size: .78rem; font-weight: 700; transition: background .18s ease; }
.detail-link:hover { background: #eff6ff; }

.action-panel {
  position: relative;
  min-height: 270px;
  overflow: hidden;
  border-radius: 24px;
  padding: 26px;
  color: #fff;
  background:
    radial-gradient(circle at 82% 38%, rgba(139,92,246,.55), transparent 28%),
    radial-gradient(circle at 70% 90%, rgba(37,99,235,.38), transparent 32%),
    linear-gradient(135deg, #081a5c 0%, #15136a 52%, #25106c 100%);
  box-shadow: 0 24px 58px rgba(30, 41, 115, 0.3);
}
.action-panel::before { content: ''; position: absolute; inset: 0; opacity: .3; background-image: radial-gradient(circle, #fff 0 1px, transparent 1.5px); background-size: 42px 42px; mask-image: linear-gradient(to left, #000, transparent 75%); }
.action-copy { position: relative; z-index: 3; max-width: 55%; }
.action-kicker { font-size: .65rem; color: #c4b5fd; text-transform: uppercase; letter-spacing: .14em; font-weight: 800; }
.action-copy h2 { margin-top: 10px; font-size: 1.42rem; line-height: 1.18; font-weight: 800; letter-spacing: -.03em; }
.action-copy p { margin-top: 10px; color: #cbd5e1; font-size: .78rem; line-height: 1.55; }
.action-button { display: inline-flex; align-items: center; gap: 9px; margin-top: 18px; padding: 11px 16px; border: 1px solid rgba(255,255,255,.42); border-radius: 12px; background: linear-gradient(135deg,#2563eb,#8b5cf6); color: #fff; font-size: .78rem; font-weight: 750; box-shadow: 0 10px 28px rgba(96,72,255,.42); }
.action-button span { display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 999px; color: #4f46e5; background: #fff; font-size: 1rem; }
.document-orbit { position: absolute; z-index: 2; right: 4%; top: 18%; width: 170px; height: 155px; display: flex; align-items: center; justify-content: center; }
.orbit-ring { position: absolute; border: 1px solid rgba(147,197,253,.45); border-radius: 50%; transform: rotate(-18deg); }
.ring-one { width: 170px; height: 74px; box-shadow: 0 0 28px rgba(96,165,250,.32); }
.ring-two { width: 130px; height: 112px; transform: rotate(38deg); }
.document-card { position: relative; width: 78px; height: 108px; padding: 15px 12px; border: 1px solid rgba(255,255,255,.65); border-radius: 10px; background: linear-gradient(160deg,rgba(255,255,255,.96),rgba(219,234,254,.8)); box-shadow: 0 0 32px rgba(167,139,250,.85); transform: rotate(8deg); color: #6366f1; }
.document-card span { font-size: .48rem; font-weight: 800; }
.document-card i { display: block; height: 3px; margin-top: 10px; border-radius: 999px; background: #c4b5fd; }
.document-card i.short { width: 58%; }
.document-card strong { position: absolute; right: 11px; bottom: 10px; }
.action-features { position: absolute; z-index: 3; left: 26px; right: 26px; bottom: 18px; display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.16); color: #ddd6fe; font-size: .62rem; }

@media (max-width: 720px) {
  .status-content { flex-direction: column; }
  .action-copy { max-width: 72%; }
  .document-orbit { right: -28px; opacity: .78; }
  .action-features { grid-template-columns: 1fr; }
  .action-panel { min-height: 340px; }
}
</style>
