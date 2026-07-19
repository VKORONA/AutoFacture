<template>
  <BasePage>
    <div class="mx-auto w-full max-w-7xl space-y-6 pb-10">
      <section
        class="relative overflow-hidden rounded-[28px] bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-900 px-6 py-8 text-white shadow-2xl shadow-blue-950/20 md:px-10"
      >
        <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-cyan-400/20 blur-3xl" />
        <div class="absolute -bottom-28 left-1/3 h-64 w-64 rounded-full bg-violet-500/20 blur-3xl" />
        <div class="relative grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
          <div>
            <div
              class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-cyan-100"
            >
              <span class="h-2 w-2 rounded-full bg-cyan-300 shadow-[0_0_16px_rgba(103,232,249,0.9)]" />
              Export pour le comptable
            </div>
            <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
              Un lot comptable contrôlé, documenté et portable
            </h1>
            <p class="mt-4 max-w-3xl text-sm leading-7 text-blue-100 md:text-base">
              AutoFacture génère les écritures de ventes, avoirs et règlements, les PDF
              justificatifs, un manifeste d’intégrité et les profils d’import des principaux
              logiciels comptables.
            </p>
          </div>
          <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur">
            <div class="text-sm text-blue-100">Dernier lot réussi</div>
            <div class="mt-2 text-2xl font-bold">{{ latestCompletedLabel }}</div>
            <div class="mt-1 text-xs text-blue-100">Équilibre débit / crédit vérifié</div>
          </div>
        </div>
      </section>

      <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
        <strong>Information importante :</strong> le fichier « journal FEC-compatible » contient
        les écritures produites par AutoFacture. Il ne constitue pas un FEC réglementaire complet
        tant que le logiciel ne tient pas toute la comptabilité de l’entreprise.
      </section>

      <div v-if="store.isLoading" class="grid gap-6 xl:grid-cols-3">
        <div class="h-96 animate-pulse rounded-3xl bg-slate-200 xl:col-span-2" />
        <div class="h-96 animate-pulse rounded-3xl bg-slate-200" />
      </div>

      <template v-else-if="settingsForm">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(340px,0.9fr)]">
          <main class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
              <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                  <h2 class="text-xl font-bold text-slate-900">Créer un nouveau lot</h2>
                  <p class="mt-1 text-sm text-slate-500">
                    Seules les factures finalisées et les avoirs émis sont inclus.
                  </p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                  Contrôle automatique
                </span>
              </div>

              <form class="mt-6 grid gap-5" @submit.prevent="generateExport">
                <div class="grid gap-5 md:grid-cols-2">
                  <label class="block">
                    <span class="text-sm font-semibold text-slate-800">Début de période</span>
                    <input
                      v-model="exportForm.period_start"
                      type="date"
                      class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    />
                  </label>
                  <label class="block">
                    <span class="text-sm font-semibold text-slate-800">Fin de période</span>
                    <input
                      v-model="exportForm.period_end"
                      type="date"
                      class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    />
                  </label>
                </div>

                <label class="block">
                  <span class="text-sm font-semibold text-slate-800">Profil du logiciel comptable</span>
                  <select
                    v-model="exportForm.profile"
                    class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option v-for="(label, value) in store.profiles" :key="value" :value="value">
                      {{ label }}
                    </option>
                  </select>
                  <span class="mt-2 block text-xs leading-5 text-slate-500">
                    Le CSV universel et le journal FEC-compatible sont toujours inclus. Le profil
                    ajoute un fichier préformaté pour faciliter l’import.
                  </span>
                </label>

                <div class="grid gap-3 md:grid-cols-3">
                  <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4">
                    <input v-model="exportForm.include_payments" type="checkbox" class="mt-1 rounded border-slate-300 text-blue-600" />
                    <span><strong class="block text-sm text-slate-900">Règlements</strong><span class="text-xs text-slate-500">Journal de banque</span></span>
                  </label>
                  <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4">
                    <input v-model="exportForm.include_documents" type="checkbox" class="mt-1 rounded border-slate-300 text-blue-600" />
                    <span><strong class="block text-sm text-slate-900">Justificatifs</strong><span class="text-xs text-slate-500">PDF factures et avoirs</span></span>
                  </label>
                  <label class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4">
                    <input v-model="exportForm.include_commercial_annexes" type="checkbox" class="mt-1 rounded border-slate-300 text-blue-600" />
                    <span><strong class="block text-sm text-slate-900">Annexes commerciales</strong><span class="text-xs text-slate-500">Photos et pièces des devis</span></span>
                  </label>
                </div>

                <button
                  type="submit"
                  class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-violet-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="store.isGenerating"
                >
                  <BaseIcon name="ArchiveIcon" class="h-5 w-5" />
                  {{ store.isGenerating ? 'Génération et contrôles…' : 'Générer le pack comptable ZIP' }}
                </button>
              </form>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
              <div class="flex items-center justify-between gap-4">
                <div>
                  <h2 class="text-xl font-bold text-slate-900">Historique des exports</h2>
                  <p class="mt-1 text-sm text-slate-500">Chaque lot reste identifié et vérifiable.</p>
                </div>
                <button type="button" class="text-sm font-semibold text-blue-700" @click="store.load()">
                  Actualiser
                </button>
              </div>

              <div v-if="!store.exports.length" class="mt-6 rounded-2xl bg-slate-50 p-8 text-center text-sm text-slate-500">
                Aucun lot comptable n’a encore été généré.
              </div>

              <div v-else class="mt-6 space-y-3">
                <article
                  v-for="batch in store.exports"
                  :key="batch.id"
                  class="rounded-2xl border border-slate-200 p-4 transition hover:border-blue-200 hover:bg-blue-50/30"
                >
                  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                      <div class="flex flex-wrap items-center gap-2">
                        <strong class="text-slate-900">{{ periodLabel(batch) }}</strong>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(batch.status)">
                          {{ statusLabel(batch.status) }}
                        </span>
                      </div>
                      <div class="mt-2 text-xs text-slate-500">
                        {{ profileLabel(batch.profile) }} · {{ batch.invoice_count }} facture(s) ·
                        {{ batch.credit_note_count }} avoir(s) · {{ batch.payment_count }} règlement(s)
                      </div>
                      <div v-if="batch.status === 'completed'" class="mt-2 text-xs font-medium text-emerald-700">
                        Débit {{ money(batch.total_debit) }} · Crédit {{ money(batch.total_credit) }} · Écart {{ money(batch.difference) }}
                      </div>
                      <div v-if="batch.error_message" class="mt-2 text-xs text-red-600">{{ batch.error_message }}</div>
                    </div>

                    <button
                      v-if="batch.download_url"
                      type="button"
                      class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
                      :disabled="store.downloadingId === batch.id"
                      @click="store.downloadExport(batch)"
                    >
                      <BaseIcon name="DownloadIcon" class="h-4 w-4" />
                      {{ store.downloadingId === batch.id ? 'Téléchargement…' : 'Télécharger' }}
                    </button>
                  </div>
                </article>
              </div>
            </section>
          </main>

          <aside class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
              <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                  <BaseIcon name="CogIcon" class="h-6 w-6" />
                </div>
                <div>
                  <h2 class="font-bold text-slate-900">Paramètres comptables</h2>
                  <p class="text-xs text-slate-500">À faire valider par le comptable</p>
                </div>
              </div>

              <form class="mt-5 space-y-4" @submit.prevent="saveSettings">
                <label class="block">
                  <span class="text-xs font-semibold text-slate-700">Mode comptable</span>
                  <select v-model="settingsForm.accounting_mode" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" :disabled="!isOwner">
                    <option value="accrual">Comptabilité d’engagement</option>
                    <option value="cash">Recettes / dépenses</option>
                  </select>
                </label>

                <div class="grid grid-cols-2 gap-3">
                  <label class="block"><span class="text-xs font-semibold text-slate-700">Journal ventes</span><input v-model.trim="settingsForm.sales_journal_code" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" :disabled="!isOwner" /></label>
                  <label class="block"><span class="text-xs font-semibold text-slate-700">Journal banque</span><input v-model.trim="settingsForm.bank_journal_code" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" :disabled="!isOwner" /></label>
                </div>

                <label class="block"><span class="text-xs font-semibold text-slate-700">Compte client collectif</span><input v-model.trim="settingsForm.customer_control_account" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" :disabled="!isOwner" /></label>
                <label class="block"><span class="text-xs font-semibold text-slate-700">Ventes de prestations</span><input v-model.trim="settingsForm.sales_services_account" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" :disabled="!isOwner" /></label>
                <label class="block"><span class="text-xs font-semibold text-slate-700">Ventes de marchandises</span><input v-model.trim="settingsForm.sales_goods_account" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" :disabled="!isOwner" /></label>
                <label class="block"><span class="text-xs font-semibold text-slate-700">Compte bancaire</span><input v-model.trim="settingsForm.bank_account" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" :disabled="!isOwner" /></label>
                <label class="block"><span class="text-xs font-semibold text-slate-700">Compte d’écarts d’arrondis</span><input v-model.trim="settingsForm.rounding_account" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" :disabled="!isOwner" /></label>

                <div>
                  <div class="text-xs font-semibold text-slate-700">Comptes de TVA collectée</div>
                  <div class="mt-2 grid grid-cols-2 gap-3">
                    <label v-for="rate in vatRates" :key="rate" class="block">
                      <span class="text-[11px] text-slate-500">TVA {{ rate }} %</span>
                      <input v-model.trim="settingsForm.vat_accounts[rate]" class="mt-1 w-full rounded-xl border-slate-300 text-sm" :disabled="!isOwner" />
                    </label>
                  </div>
                </div>

                <button
                  type="submit"
                  class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                  :disabled="!isOwner || store.isSaving"
                >
                  {{ store.isSaving ? 'Enregistrement…' : 'Enregistrer la configuration' }}
                </button>
                <p v-if="!isOwner" class="text-xs leading-5 text-amber-700">
                  Seul le propriétaire de l’entreprise peut modifier les comptes.
                </p>
              </form>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
              <h2 class="font-bold text-slate-900">Contenu du pack</h2>
              <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                <li class="flex gap-3"><BaseIcon name="CheckIcon" class="mt-1 h-4 w-4 shrink-0 text-emerald-600" /> CSV universel UTF-8 avec BOM</li>
                <li class="flex gap-3"><BaseIcon name="CheckIcon" class="mt-1 h-4 w-4 shrink-0 text-emerald-600" /> Journal ventes FEC-compatible à 18 colonnes</li>
                <li class="flex gap-3"><BaseIcon name="CheckIcon" class="mt-1 h-4 w-4 shrink-0 text-emerald-600" /> Fichier préformaté selon le profil sélectionné</li>
                <li class="flex gap-3"><BaseIcon name="CheckIcon" class="mt-1 h-4 w-4 shrink-0 text-emerald-600" /> Clients, règlements et justificatifs</li>
                <li class="flex gap-3"><BaseIcon name="CheckIcon" class="mt-1 h-4 w-4 shrink-0 text-emerald-600" /> Manifeste SHA-256 et rapport de contrôle PDF</li>
              </ul>
            </section>
          </aside>
        </div>
      </template>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useAccountingStore } from '@/scripts/admin/stores/accounting'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useUserStore } from '@/scripts/admin/stores/user'

const store = useAccountingStore()
const companyStore = useCompanyStore()
const userStore = useUserStore()
const settingsForm = ref(null)
const vatRates = ['20', '10', '5.5', '2.1']

const today = new Date()
const year = today.getFullYear()
const exportForm = reactive({
  period_start: `${year}-01-01`,
  period_end: `${year}-12-31`,
  profile: 'universal_csv',
  include_payments: true,
  include_documents: true,
  include_commercial_annexes: false,
})

const isOwner = computed(() => {
  return Number(companyStore.selectedCompany?.owner_id) === Number(userStore.currentUser?.id)
})

const latestCompletedLabel = computed(() => {
  const batch = store.exports.find((item) => item.status === 'completed')
  if (!batch) return 'Aucun lot'
  return periodLabel(batch)
})

onMounted(async () => {
  await store.load()
  settingsForm.value = JSON.parse(JSON.stringify(store.settings))
  exportForm.profile = store.settings.export_profile
  exportForm.include_payments = store.settings.include_payments
  exportForm.include_documents = store.settings.include_documents
  exportForm.include_commercial_annexes = store.settings.include_commercial_annexes
})

async function saveSettings() {
  await store.saveSettings(settingsForm.value)
  settingsForm.value = JSON.parse(JSON.stringify(store.settings))
}

async function generateExport() {
  await store.generateExport({ ...exportForm })
}

function profileLabel(profile) {
  return store.profiles[profile] || profile
}

function statusLabel(status) {
  return {
    completed: 'Prêt',
    processing: 'En cours',
    failed: 'Échec',
  }[status] || status
}

function statusClass(status) {
  if (status === 'completed') return 'bg-emerald-100 text-emerald-700'
  if (status === 'failed') return 'bg-red-100 text-red-700'
  return 'bg-amber-100 text-amber-700'
}

function periodLabel(batch) {
  return `${formatDate(batch.period_start)} → ${formatDate(batch.period_end)}`
}

function formatDate(value) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('fr-FR').format(new Date(`${value}T12:00:00`))
}

function money(cents) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format((Number(cents) || 0) / 100)
}
</script>
