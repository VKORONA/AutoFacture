<template>
  <BasePage>
    <div class="mx-auto w-full max-w-[1500px] space-y-6 pb-14">
      <section class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-900 px-6 py-8 text-white shadow-2xl shadow-blue-950/20 md:px-10">
        <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl" />
        <div class="absolute -bottom-32 left-1/3 h-72 w-72 rounded-full bg-violet-500/20 blur-3xl" />
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-cyan-100">
              <span class="h-2 w-2 rounded-full bg-emerald-300 shadow-[0_0_16px_rgba(110,231,183,.9)]" />
              Micro-entrepreneur
            </div>
            <h1 class="mt-4 text-3xl font-bold tracking-tight md:text-4xl">
              Préparer la déclaration de chiffre d’affaires
            </h1>
            <p class="mt-4 max-w-4xl text-sm leading-7 text-blue-100 md:text-base">
              AutoFacture répartit les encaissements entre ventes BIC, prestations BIC,
              activités BNC et professions relevant de la Cipav. Les cotisations sont
              estimées à partir de la date réelle des règlements enregistrés.
            </p>
          </div>

          <div class="grid min-w-[300px] grid-cols-2 gap-3">
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <div class="text-xs uppercase tracking-wide text-blue-100">CA encaissé</div>
              <div class="mt-2 text-2xl font-bold">{{ money(report?.totals?.turnover) }}</div>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <div class="text-xs uppercase tracking-wide text-blue-100">À provisionner</div>
              <div class="mt-2 text-2xl font-bold text-emerald-300">{{ money(report?.totals?.total_due) }}</div>
            </div>
          </div>
        </div>
      </section>

      <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
        <strong>Calcul préparatoire :</strong> seuls les règlements enregistrés dans AutoFacture
        sont pris en compte. Le montant officiel affiché dans l’espace Urssaf reste la référence.
        Les dépenses professionnelles ne sont pas déduites du chiffre d’affaires déclaré.
      </section>

      <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-end lg:justify-between">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-600">Exercice</span>
            <select v-model.number="selectedYear" class="mt-2 w-full min-w-[170px] rounded-xl border-slate-300 text-sm" @change="reload">
              <option v-for="year in years" :key="year" :value="year">Exercice {{ year }}</option>
            </select>
          </label>
          <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-600">Affichage</span>
            <select v-model="displayMode" class="mt-2 w-full min-w-[190px] rounded-xl border-slate-300 text-sm">
              <option value="monthly">Par mois</option>
              <option value="quarterly">Par trimestre</option>
            </select>
          </label>
          <div class="flex items-end gap-2">
            <button type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800" @click="reload">
              Actualiser
            </button>
            <button type="button" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" :disabled="store.isExporting" @click="store.exportCsv(selectedYear)">
              {{ store.isExporting ? 'Export…' : 'Export CSV' }}
            </button>
          </div>
        </div>

        <a href="https://www.autoentrepreneur.urssaf.fr" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20">
          Ouvrir mon espace Urssaf
          <span aria-hidden="true">↗</span>
        </a>
      </div>

      <div v-if="store.isLoading" class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div v-for="index in 4" :key="index" class="h-44 animate-pulse rounded-3xl bg-slate-200" />
      </div>

      <template v-else-if="report">
        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
          <article v-for="card in activityCards" :key="card.type" class="relative overflow-hidden rounded-3xl border bg-white p-5 shadow-sm" :class="card.borderClass">
            <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full opacity-25 blur-2xl" :class="card.glowClass" />
            <div class="relative">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-[0.14em]" :class="card.textClass">{{ card.label }}</div>
                  <div class="mt-3 text-2xl font-bold text-slate-950">{{ money(card.turnover) }}</div>
                </div>
                <div class="rounded-2xl px-3 py-2 text-sm font-bold" :class="card.badgeClass">{{ percent(card.socialRate) }}</div>
              </div>
              <dl class="mt-5 grid grid-cols-2 gap-3 text-xs">
                <div><dt class="text-slate-500">Cotisations</dt><dd class="mt-1 font-semibold text-slate-900">{{ money(card.social) }}</dd></div>
                <div><dt class="text-slate-500">CFP</dt><dd class="mt-1 font-semibold text-slate-900">{{ money(card.cfp) }}</dd></div>
                <div><dt class="text-slate-500">Impôt libératoire</dt><dd class="mt-1 font-semibold text-slate-900">{{ money(card.incomeTax) }}</dd></div>
                <div><dt class="text-slate-500">Total estimé</dt><dd class="mt-1 font-bold text-slate-950">{{ money(card.totalDue) }}</dd></div>
              </dl>
            </div>
          </article>
        </section>

        <section v-if="report.unclassified_receipts.count" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
          <strong>{{ report.unclassified_receipts.count }} règlement(s) non classé(s)</strong>
          pour {{ money(report.unclassified_receipts.amount) }}. Ils ne sont pas intégrés dans la déclaration tant qu’ils ne sont pas rattachés à une facture classée ou ajoutés comme ajustement manuel.
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-5 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-xl font-bold text-slate-950">Détail de la déclaration</h2>
              <p class="mt-1 text-sm text-slate-500">Chiffre d’affaires encaissé HT et provisions estimées.</p>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">Barème applicable au mois d’encaissement</span>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-[1220px] w-full text-sm">
              <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                  <th class="sticky left-0 z-10 bg-slate-50 px-5 py-4 text-left">Période</th>
                  <th v-for="type in activityOrder" :key="`${type}-turnover`" class="px-4 py-4 text-right">CA {{ shortLabel(type) }}</th>
                  <th class="px-4 py-4 text-right">Total CA</th>
                  <th class="px-4 py-4 text-right">Cotisations</th>
                  <th class="px-4 py-4 text-right">CFP</th>
                  <th class="px-4 py-4 text-right">Impôt</th>
                  <th class="px-5 py-4 text-right">Total estimé</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="period in displayedPeriods" :key="period.key" class="hover:bg-blue-50/30">
                  <td class="sticky left-0 bg-white px-5 py-4 font-semibold text-slate-900">{{ period.label }}</td>
                  <td v-for="type in activityOrder" :key="`${period.key}-${type}`" class="px-4 py-4 text-right tabular-nums">{{ money(period.activities[type]?.turnover) }}</td>
                  <td class="px-4 py-4 text-right font-bold tabular-nums text-slate-950">{{ money(period.totals.turnover) }}</td>
                  <td class="px-4 py-4 text-right tabular-nums">{{ money(period.totals.social_contributions) }}</td>
                  <td class="px-4 py-4 text-right tabular-nums">{{ money(period.totals.cfp) }}</td>
                  <td class="px-4 py-4 text-right tabular-nums">{{ money(period.totals.income_tax) }}</td>
                  <td class="px-5 py-4 text-right font-bold tabular-nums text-blue-700">{{ money(period.totals.total_due) }}</td>
                </tr>
              </tbody>
              <tfoot class="bg-slate-950 text-white">
                <tr>
                  <td class="sticky left-0 bg-slate-950 px-5 py-4 font-bold">TOTAL {{ selectedYear }}</td>
                  <td v-for="type in activityOrder" :key="`total-${type}`" class="px-4 py-4 text-right font-semibold">{{ money(activityTotal(type).turnover) }}</td>
                  <td class="px-4 py-4 text-right text-base font-bold">{{ money(report.totals.turnover) }}</td>
                  <td class="px-4 py-4 text-right font-semibold">{{ money(report.totals.social_contributions) }}</td>
                  <td class="px-4 py-4 text-right font-semibold">{{ money(report.totals.cfp) }}</td>
                  <td class="px-4 py-4 text-right font-semibold">{{ money(report.totals.income_tax) }}</td>
                  <td class="px-5 py-4 text-right text-base font-bold text-cyan-300">{{ money(report.totals.total_due) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(360px,.65fr)]">
          <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
            <div class="flex items-center justify-between gap-4">
              <div>
                <h2 class="text-xl font-bold text-slate-950">Ajustements manuels</h2>
                <p class="mt-1 text-sm text-slate-500">Remboursement, encaissement hors facture ou reclassement exceptionnel.</p>
              </div>
            </div>

            <form class="mt-6 grid gap-4 md:grid-cols-2" @submit.prevent="addAdjustment">
              <label class="block"><span class="text-xs font-semibold text-slate-700">Date</span><input v-model="adjustmentForm.adjustment_date" type="date" required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" /></label>
              <label class="block"><span class="text-xs font-semibold text-slate-700">Catégorie</span><select v-model="adjustmentForm.business_activity_type" required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm"><option v-for="(label, value) in report.activity_options" :key="value" :value="value">{{ label }}</option></select></label>
              <label class="block"><span class="text-xs font-semibold text-slate-700">Montant HT (€)</span><input v-model.number="adjustmentAmountEuros" type="number" step="0.01" required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" placeholder="Négatif pour un remboursement" /></label>
              <label class="block"><span class="text-xs font-semibold text-slate-700">Motif</span><input v-model.trim="adjustmentForm.label" required maxlength="255" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" /></label>
              <label class="block md:col-span-2"><span class="text-xs font-semibold text-slate-700">Note facultative</span><textarea v-model.trim="adjustmentForm.notes" rows="2" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" /></label>
              <button type="submit" class="w-fit rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Ajouter l’ajustement</button>
            </form>

            <div v-if="report.adjustments.length" class="mt-7 space-y-2">
              <article v-for="adjustment in report.adjustments" :key="adjustment.id" class="flex flex-col gap-3 rounded-2xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div><strong class="text-sm text-slate-900">{{ adjustment.label }}</strong><div class="mt-1 text-xs text-slate-500">{{ adjustment.adjustment_date }} · {{ shortLabel(adjustment.business_activity_type) }}</div></div>
                <div class="flex items-center gap-3"><span class="font-bold" :class="adjustment.amount < 0 ? 'text-red-600' : 'text-emerald-700'">{{ money(adjustment.amount) }}</span><button type="button" class="text-xs font-semibold text-red-600" @click="removeAdjustment(adjustment.id)">Supprimer</button></div>
              </article>
            </div>
          </section>

          <aside class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
              <h2 class="text-lg font-bold text-slate-950">Paramètres du régime</h2>
              <form class="mt-5 space-y-4" @submit.prevent="saveSettings">
                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4"><input v-model="settingsForm.enabled" type="checkbox" class="mt-1 rounded border-slate-300 text-blue-600" /><span><strong class="block text-sm text-slate-900">Entreprise au régime micro</strong><span class="mt-1 block text-xs leading-5 text-slate-500">Active le suivi et les alertes de déclaration.</span></span></label>
                <label class="block"><span class="text-xs font-semibold text-slate-700">Périodicité Urssaf</span><select v-model="settingsForm.declaration_frequency" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm"><option value="monthly">Mensuelle</option><option value="quarterly">Trimestrielle</option></select></label>
                <label class="block"><span class="text-xs font-semibold text-slate-700">Profil CFP principal</span><select v-model="settingsForm.cfp_profile" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm"><option value="commercial">Commercial</option><option value="artisan">Artisan</option><option value="liberal">Profession libérale</option></select></label>
                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4"><input v-model="settingsForm.versement_liberatoire" type="checkbox" class="mt-1 rounded border-slate-300 text-blue-600" /><span><strong class="block text-sm text-slate-900">Versement libératoire</strong><span class="mt-1 block text-xs text-slate-500">Ajoute l’impôt estimé à la provision.</span></span></label>
                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4"><input v-model="settingsForm.acre_enabled" type="checkbox" class="mt-1 rounded border-slate-300 text-blue-600" /><span><strong class="block text-sm text-slate-900">ACRE active</strong><span class="mt-1 block text-xs text-slate-500">À activer uniquement selon l’attestation Urssaf.</span></span></label>
                <div v-if="settingsForm.acre_enabled" class="grid grid-cols-2 gap-3"><label class="block"><span class="text-xs font-semibold text-slate-700">Fin de l’ACRE</span><input v-model="settingsForm.acre_end_date" type="date" required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" /></label><label class="block"><span class="text-xs font-semibold text-slate-700">Part du taux normal</span><input v-model.number="settingsForm.acre_rate_factor" type="number" min="0.01" max="1" step="0.01" required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" /></label></div>
                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-blue-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white" :disabled="store.isSaving">{{ store.isSaving ? 'Enregistrement…' : 'Enregistrer les paramètres' }}</button>
              </form>
            </section>

            <section class="rounded-3xl border p-6 shadow-sm" :class="report.urssaf.automatic_submission_available ? 'border-emerald-200 bg-emerald-50' : 'border-blue-200 bg-blue-50'">
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-2xl shadow-sm">⇄</div>
              <h2 class="mt-4 text-lg font-bold text-slate-950">Télétransmission Urssaf</h2>
              <p class="mt-2 text-sm leading-6 text-slate-700" v-if="report.urssaf.automatic_submission_available">La connexion tiers déclarant est configurée. La transmission devra encore demander une validation explicite avant chaque envoi.</p>
              <p class="mt-2 text-sm leading-6 text-slate-700" v-else>Le dossier est préparé automatiquement, mais l’envoi direct restera désactivé tant qu’AutoFacture n’aura pas obtenu l’habilitation officielle de tiers déclarant et son jeton d’accès.</p>
              <span class="mt-4 inline-flex rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-blue-800 shadow-sm">{{ report.urssaf.automatic_submission_available ? 'API configurée' : 'Habilitation requise' }}</span>
            </section>
          </aside>
        </div>
      </template>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useMicroEntrepreneurStore } from '@/scripts/admin/stores/micro-entrepreneur'

const store = useMicroEntrepreneurStore()
const selectedYear = ref(new Date().getFullYear())
const displayMode = ref('monthly')
const years = computed(() => Array.from({ length: 8 }, (_, index) => 2026 + index))
const report = computed(() => store.report)
const activityOrder = ['goods_bic', 'service_bic', 'service_bnc', 'service_bnc_cipav']
const adjustmentAmountEuros = ref(null)
const adjustmentForm = reactive({ adjustment_date: `${selectedYear.value}-01-01`, business_activity_type: 'service_bic', label: '', notes: '' })
const settingsForm = reactive({ enabled: false, declaration_frequency: 'monthly', cfp_profile: 'commercial', versement_liberatoire: false, acre_enabled: false, acre_end_date: null, acre_rate_factor: 0.5, rate_overrides: {} })

const palettes = {
  goods_bic: { borderClass: 'border-amber-200', glowClass: 'bg-amber-400', textClass: 'text-amber-700', badgeClass: 'bg-amber-100 text-amber-800' },
  service_bic: { borderClass: 'border-emerald-200', glowClass: 'bg-emerald-400', textClass: 'text-emerald-700', badgeClass: 'bg-emerald-100 text-emerald-800' },
  service_bnc: { borderClass: 'border-blue-200', glowClass: 'bg-blue-400', textClass: 'text-blue-700', badgeClass: 'bg-blue-100 text-blue-800' },
  service_bnc_cipav: { borderClass: 'border-violet-200', glowClass: 'bg-violet-400', textClass: 'text-violet-700', badgeClass: 'bg-violet-100 text-violet-800' },
}

const activityCards = computed(() => activityOrder.map((type) => {
  const total = activityTotal(type)
  const firstRate = report.value?.months?.find((month) => month.activities[type])?.activities[type]?.rates?.social_rate || 0
  return { type, label: shortLabel(type), turnover: total.turnover, social: total.social_contributions, cfp: total.cfp, incomeTax: total.income_tax, totalDue: total.total_due, socialRate: firstRate, ...palettes[type] }
}))

const displayedPeriods = computed(() => {
  if (!report.value) return []
  if (displayMode.value === 'monthly') return report.value.months.map((month) => ({ ...month, key: month.month }))

  const quarters = new Map()
  report.value.months.forEach((month) => {
    const monthNumber = Number(month.month.split('-')[1])
    const quarter = Math.ceil(monthNumber / 3)
    const key = `${selectedYear.value}-T${quarter}`
    if (!quarters.has(key)) quarters.set(key, { key, label: `Trimestre ${quarter} ${selectedYear.value}`, activities: {}, totals: emptyTotals() })
    const row = quarters.get(key)
    activityOrder.forEach((type) => {
      if (!row.activities[type]) row.activities[type] = { turnover: 0, social_contributions: 0, cfp: 0, income_tax: 0, total_due: 0 }
      addTotals(row.activities[type], month.activities[type])
    })
    addTotals(row.totals, month.totals)
  })
  return Array.from(quarters.values())
})

watch(report, (value) => {
  if (!value?.settings) return
  Object.assign(settingsForm, value.settings)
  displayMode.value = value.settings.declaration_frequency || 'monthly'
}, { immediate: true })

store.load({ year: selectedYear.value })

function emptyTotals() { return { turnover: 0, social_contributions: 0, cfp: 0, income_tax: 0, total_due: 0 } }
function addTotals(target, source = {}) { Object.keys(emptyTotals()).forEach((key) => { target[key] += Number(source?.[key] || 0) }) }
function money(cents = 0) { return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(Number(cents || 0) / 100) }
function percent(rate = 0) { return `${new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 2 }).format(Number(rate || 0) * 100)} %` }
function shortLabel(type) { return { goods_bic: 'Ventes BIC', service_bic: 'Services BIC', service_bnc: 'Services BNC', service_bnc_cipav: 'BNC Cipav' }[type] || type }
function activityTotal(type) { const result = emptyTotals(); report.value?.months?.forEach((month) => addTotals(result, month.activities[type])); return result }
async function reload() { adjustmentForm.adjustment_date = `${selectedYear.value}-01-01`; await store.load({ year: selectedYear.value }) }
async function saveSettings() { await store.saveSettings({ ...settingsForm }, { year: selectedYear.value }) }
async function addAdjustment() { const amount = Math.round(Number(adjustmentAmountEuros.value) * 100); if (!amount) return; await store.addAdjustment({ ...adjustmentForm, amount }, { year: selectedYear.value }); adjustmentAmountEuros.value = null; adjustmentForm.label = ''; adjustmentForm.notes = '' }
async function removeAdjustment(id) { await store.removeAdjustment(id, { year: selectedYear.value }) }
</script>
