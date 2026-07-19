<template>
  <section class="mt-6 grid gap-6 xl:grid-cols-2">
    <article class="list-panel">
      <header class="list-header">
        <div>
          <span>Activité récente</span>
          <h2>Dernières factures</h2>
        </div>
        <router-link to="/admin/invoices">Voir toutes →</router-link>
      </header>

      <div v-if="!dashboardStore.isDashboardDataLoaded" class="space-y-3 p-5">
        <div v-for="index in 4" :key="index" class="h-14 animate-pulse rounded-xl bg-slate-100" />
      </div>

      <div v-else-if="dashboardStore.recentDueInvoices.length" class="document-list">
        <router-link
          v-for="invoice in dashboardStore.recentDueInvoices"
          :key="invoice.id"
          :to="`/admin/invoices/${invoice.id}/view`"
          class="document-row"
        >
          <div class="document-icon invoice-icon">▤</div>
          <div class="document-main">
            <strong>{{ invoice.invoice_number }}</strong>
            <span>{{ invoice.customer?.name || 'Client non renseigné' }}</span>
          </div>
          <div class="document-amount">
            <BaseFormatMoney
              :amount="invoice.total"
              :currency="invoice.customer?.currency || companyStore.selectedCompanyCurrency"
            />
          </div>
          <span class="status-pill" :class="invoiceStatus(invoice).className">
            {{ invoiceStatus(invoice).label }}
          </span>
          <time>{{ formatDate(invoice.invoice_date) }}</time>
        </router-link>
      </div>

      <div v-else class="empty-list">Aucune facture pour le moment.</div>
    </article>

    <article class="list-panel">
      <header class="list-header">
        <div>
          <span>Opportunités commerciales</span>
          <h2>Devis récents</h2>
        </div>
        <router-link to="/admin/estimates">Voir tous →</router-link>
      </header>

      <div v-if="!dashboardStore.isDashboardDataLoaded" class="space-y-3 p-5">
        <div v-for="index in 4" :key="index" class="h-14 animate-pulse rounded-xl bg-slate-100" />
      </div>

      <div v-else-if="dashboardStore.recentEstimates.length" class="document-list">
        <router-link
          v-for="estimate in dashboardStore.recentEstimates"
          :key="estimate.id"
          :to="`/admin/estimates/${estimate.id}/view`"
          class="document-row"
        >
          <div class="document-icon estimate-icon">▧</div>
          <div class="document-main">
            <strong>{{ estimate.estimate_number }}</strong>
            <span>{{ estimate.customer?.name || 'Client non renseigné' }}</span>
          </div>
          <div class="document-amount">
            <BaseFormatMoney
              :amount="estimate.total"
              :currency="estimate.customer?.currency || companyStore.selectedCompanyCurrency"
            />
          </div>
          <span class="status-pill" :class="estimateStatus(estimate).className">
            {{ estimateStatus(estimate).label }}
          </span>
          <time>{{ formatDate(estimate.estimate_date) }}</time>
        </router-link>
      </div>

      <div v-else class="empty-list">Aucun devis pour le moment.</div>
    </article>
  </section>
</template>

<script setup>
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const dashboardStore = useDashboardStore()
const companyStore = useCompanyStore()

function formatDate(value) {
  if (!value) return '—'

  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(new Date(value))
}

function invoiceStatus(invoice) {
  if (invoice.paid_status === 'PAID') {
    return { label: 'Payée', className: 'status-paid' }
  }

  if (invoice.due_date && new Date(invoice.due_date) < new Date()) {
    return { label: 'En retard', className: 'status-overdue' }
  }

  return { label: 'En attente', className: 'status-pending' }
}

function estimateStatus(estimate) {
  const statuses = {
    DRAFT: { label: 'Brouillon', className: 'status-draft' },
    SENT: { label: 'Envoyé', className: 'status-sent' },
    VIEWED: { label: 'Consulté', className: 'status-viewed' },
    ACCEPTED: { label: 'Accepté', className: 'status-paid' },
    REJECTED: { label: 'Refusé', className: 'status-overdue' },
    EXPIRED: { label: 'Expiré', className: 'status-overdue' },
  }

  return statuses[estimate.status] || statuses.DRAFT
}
</script>

<style scoped>
.list-panel {
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
}

.list-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 22px 22px 14px;
}

.list-header span {
  color: #8b5cf6;
  font-size: 0.64rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.list-header h2 {
  margin-top: 4px;
  color: #0f172a;
  font-size: 1rem;
  font-weight: 800;
}

.list-header a {
  color: #2563eb;
  font-size: 0.72rem;
  font-weight: 750;
}

.document-list { padding: 0 12px 14px; }
.document-row {
  display: grid;
  grid-template-columns: 38px minmax(0, 1fr) auto auto auto;
  align-items: center;
  gap: 12px;
  min-height: 66px;
  padding: 10px 10px;
  border-radius: 14px;
  transition: background 160ms ease, transform 160ms ease;
}
.document-row:hover { background: #f8fafc; transform: translateX(2px); }
.document-icon { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 11px; font-weight: 800; }
.invoice-icon { color: #7c3aed; background: #f3e8ff; }
.estimate-icon { color: #2563eb; background: #dbeafe; }
.document-main { min-width: 0; }
.document-main strong { display: block; overflow: hidden; color: #0f172a; font-size: .76rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.document-main span { display: block; overflow: hidden; margin-top: 3px; color: #64748b; font-size: .66rem; text-overflow: ellipsis; white-space: nowrap; }
.document-amount { color: #0f172a; font-size: .74rem; font-weight: 800; white-space: nowrap; }
.status-pill { padding: 5px 8px; border-radius: 999px; font-size: .59rem; font-weight: 750; white-space: nowrap; }
.status-paid { color: #047857; background: #d1fae5; }
.status-pending { color: #c2410c; background: #ffedd5; }
.status-overdue { color: #be123c; background: #ffe4e6; }
.status-draft { color: #6d28d9; background: #ede9fe; }
.status-sent { color: #1d4ed8; background: #dbeafe; }
.status-viewed { color: #0369a1; background: #e0f2fe; }
time { color: #94a3b8; font-size: .62rem; white-space: nowrap; }
.empty-list { padding: 34px 22px; color: #94a3b8; font-size: .78rem; text-align: center; }

@media (max-width: 720px) {
  .document-row { grid-template-columns: 38px minmax(0, 1fr) auto; }
  .status-pill, time { display: none; }
}
</style>
