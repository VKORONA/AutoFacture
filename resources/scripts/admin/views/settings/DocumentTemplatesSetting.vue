<template>
  <BasePage class="document-templates-page">
    <BasePageHeader title="Modèles de factures, devis et avoirs">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem title="Tableau de bord" to="/admin/dashboard" />
        <BaseBreadcrumbItem title="Paramètres" to="/admin/settings/account-settings" />
        <BaseBreadcrumbItem title="Modèles de documents" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">
            Facturation & devis
          </div>
          <h1 class="mt-2 text-2xl font-bold text-slate-950">
            Choisissez le design appliqué par défaut
          </h1>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
            Le modèle choisi est appliqué automatiquement aux nouveaux documents.
            Le modèle d’un document existant reste modifiable tant que le document
            n’est pas finalisé.
          </p>
        </div>

        <BaseButton
          type="button"
          variant="primary"
          :loading="isSaving"
          :disabled="isLoading || isSaving"
          @click="saveDefaults"
        >
          <template #left="slotProps">
            <BaseIcon name="SaveIcon" :class="slotProps.class" />
          </template>
          Enregistrer les modèles par défaut
        </BaseButton>
      </div>

      <div class="mt-6 flex flex-wrap gap-2 rounded-2xl bg-slate-100 p-1.5">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          type="button"
          class="rounded-xl px-4 py-2.5 text-sm font-semibold transition"
          :class="
            activeTab === tab.key
              ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200'
              : 'text-slate-600 hover:bg-white/70 hover:text-slate-900'
          "
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
        </button>
      </div>

      <div v-if="isLoading" class="flex min-h-[360px] items-center justify-center">
        <BaseContentPlaceholders class="w-full" />
      </div>

      <template v-else>
        <div class="mt-7 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
          <button
            v-for="template in visibleTemplates"
            :key="template.name"
            type="button"
            class="group overflow-hidden rounded-2xl border bg-white text-left transition hover:-translate-y-0.5 hover:shadow-lg"
            :class="
              selectedTemplate === template.name
                ? 'border-blue-500 ring-2 ring-blue-100'
                : 'border-slate-200 hover:border-blue-300'
            "
            @click="selectTemplate(template.name)"
          >
            <div
              class="relative aspect-[1/1.18] overflow-hidden border-b border-slate-200 bg-slate-50 p-4"
              :class="previewBackground(template)"
            >
              <img
                v-if="template.path && !brokenImages[template.name]"
                :src="template.path"
                :alt="template.label"
                class="h-full w-full rounded-lg object-contain shadow-sm"
                @error="brokenImages[template.name] = true"
              />
              <div
                v-else
                class="template-fallback h-full rounded-xl border p-4 shadow-sm"
                :class="fallbackClass(template)"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-[10px] font-black uppercase tracking-widest opacity-70">
                      AutoFacture
                    </div>
                    <div class="mt-1 text-lg font-black">
                      {{ activeTab === 'estimate' ? 'DEVIS' : activeTab === 'credit_note' ? 'AVOIR' : 'FACTURE' }}
                    </div>
                  </div>
                  <div class="rounded-full border px-2 py-1 text-[8px] font-bold">
                    {{ activeTab === 'credit_note' ? 'ÉMIS' : 'FINALISÉ' }}
                  </div>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-2">
                  <div class="h-20 rounded-lg bg-current opacity-10"></div>
                  <div class="space-y-2 pt-1">
                    <div class="h-2 rounded bg-current opacity-20"></div>
                    <div class="h-2 w-4/5 rounded bg-current opacity-10"></div>
                    <div class="h-2 w-3/5 rounded bg-current opacity-10"></div>
                  </div>
                </div>
                <div class="mt-4 space-y-2">
                  <div class="h-3 rounded bg-current opacity-20"></div>
                  <div class="h-8 rounded bg-current opacity-10"></div>
                  <div class="h-8 rounded bg-current opacity-10"></div>
                  <div class="ml-auto h-12 w-2/5 rounded bg-current opacity-20"></div>
                </div>
              </div>

              <span
                v-if="selectedTemplate === template.name"
                class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg"
              >
                <BaseIcon name="CheckIcon" class="h-5 w-5" />
              </span>
            </div>

            <div class="p-4">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="font-bold text-slate-950">{{ template.label }}</div>
                  <div class="mt-1 text-xs leading-5 text-slate-500">
                    {{ template.description }}
                  </div>
                </div>
                <span
                  v-if="selectedTemplate === template.name"
                  class="shrink-0 rounded-full bg-blue-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-700"
                >
                  Sélectionné
                </span>
              </div>
            </div>
          </button>
        </div>

        <div
          v-if="!visibleTemplates.length"
          class="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900"
        >
          Aucun modèle n’a été trouvé. Le modèle standard AutoFacture sera utilisé
          jusqu’à la prochaine synchronisation des modèles.
        </div>

        <div class="mt-7 grid gap-4 lg:grid-cols-3">
          <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
            <div class="font-semibold text-blue-950">Factures</div>
            <div class="mt-1 text-sm text-blue-800">
              Modèle par défaut : {{ labelFor(invoiceDefault, invoiceTemplates) }}
            </div>
          </div>
          <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4">
            <div class="font-semibold text-violet-950">Devis</div>
            <div class="mt-1 text-sm text-violet-800">
              Modèle par défaut : {{ labelFor(estimateDefault, estimateTemplates) }}
            </div>
          </div>
          <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
            <div class="font-semibold text-emerald-950">Avoirs</div>
            <div class="mt-1 text-sm text-emerald-800">
              Modèle par défaut : {{ labelFor(creditNoteDefault, creditNoteTemplates) }}
            </div>
          </div>
        </div>
      </template>
    </div>
  </BasePage>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, reactive, ref } from 'vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

const userStore = useUserStore()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const tabs = [
  { key: 'invoice', label: 'Factures' },
  { key: 'estimate', label: 'Devis' },
  { key: 'credit_note', label: 'Avoirs' },
]

const activeTab = ref('invoice')
const isLoading = ref(true)
const isSaving = ref(false)
const brokenImages = reactive({})
const invoiceTemplates = ref([])
const estimateTemplates = ref([])
const invoiceDefault = ref('')
const estimateDefault = ref('')
const creditNoteDefault = ref('premium')

const creditNoteTemplates = [
  templateMetadata('premium', null, 'Premium AutoFacture', 'Présentation moderne bleue et structurée.', 'premium'),
  templateMetadata('classique', null, 'Classique Pro', 'Présentation claire adaptée aux dossiers administratifs.', 'classic'),
  templateMetadata('minimal', null, 'Minimal élégant', 'Mise en page aérée avec une lecture immédiate.', 'minimal'),
  templateMetadata('nuit', null, 'Premium Nuit', 'Contraste sombre haut de gamme pour les documents numériques.', 'night'),
  templateMetadata('franchise-tva', null, 'Franchise TVA', 'Mise en avant de la mention TVA non applicable.', 'franchise'),
]

const visibleTemplates = computed(() => {
  if (activeTab.value === 'estimate') return estimateTemplates.value
  if (activeTab.value === 'credit_note') return creditNoteTemplates
  return invoiceTemplates.value
})

const selectedTemplate = computed(() => {
  if (activeTab.value === 'estimate') return estimateDefault.value
  if (activeTab.value === 'credit_note') return creditNoteDefault.value
  return invoiceDefault.value
})

onMounted(loadTemplates)

async function loadTemplates() {
  isLoading.value = true

  try {
    const [invoiceResponse, estimateResponse] = await Promise.all([
      axios.get('/api/v1/invoices/templates'),
      axios.get('/api/v1/estimates/templates'),
    ])

    invoiceTemplates.value = normalizeTemplates(
      invoiceResponse.data.invoiceTemplates || [],
      'invoice'
    )
    estimateTemplates.value = normalizeTemplates(
      estimateResponse.data.estimateTemplates || [],
      'estimate'
    )

    invoiceDefault.value =
      userStore.currentUserSettings.default_invoice_template ||
      invoiceTemplates.value[0]?.name ||
      ''
    estimateDefault.value =
      userStore.currentUserSettings.default_estimate_template ||
      estimateTemplates.value[0]?.name ||
      ''
    creditNoteDefault.value =
      companyStore.selectedCompanySettings.default_credit_note_template || 'premium'
  } catch (error) {
    handleError(error)
  } finally {
    isLoading.value = false
  }
}

function normalizeTemplates(templates, type) {
  return templates.map((template, index) => {
    const metadata = friendlyMetadata(template.name, index, type)

    return {
      ...template,
      ...metadata,
      label: template.label || metadata.label,
      description: template.description || metadata.description,
      theme: template.theme || metadata.theme,
    }
  })
}

function friendlyMetadata(name, index, type) {
  const normalized = String(name || '').toLowerCase()

  if (normalized.includes('premium')) {
    return templateMetadata(name, null, 'Premium AutoFacture', 'Design moderne, détaillé et professionnel.', 'premium')
  }
  if (normalized.includes('classique')) {
    return templateMetadata(name, null, 'Classique Pro', 'Structure claire et professionnelle.', 'classic')
  }
  if (normalized.includes('minimal')) {
    return templateMetadata(name, null, 'Minimal élégant', 'Design épuré avec une mise en page aérée.', 'minimal')
  }
  if (normalized.includes('nuit')) {
    return templateMetadata(name, null, 'Premium Nuit', 'Design sombre premium pour un rendu haut de gamme.', 'night')
  }
  if (normalized.includes('franchise')) {
    return templateMetadata(name, null, 'Franchise TVA', 'Modèle optimisé pour les entreprises non assujetties.', 'franchise')
  }

  const defaults = [
    ['Premium AutoFacture', 'Design moderne et complet.', 'premium'],
    ['Classique Pro', 'Structure claire et professionnelle.', 'classic'],
    ['Minimal élégant', 'Design épuré et aéré.', 'minimal'],
    ['Premium Nuit', 'Contraste sombre haut de gamme.', 'night'],
    ['Franchise TVA', 'Mention TVA non applicable mise en avant.', 'franchise'],
  ]
  const value = defaults[index] || [`Modèle ${index + 1}`, `Modèle ${type}.`, 'classic']
  return templateMetadata(name, null, value[0], value[1], value[2])
}

function templateMetadata(name, path, label, description, theme) {
  return { name, path, label, description, theme }
}

function selectTemplate(name) {
  if (activeTab.value === 'estimate') {
    estimateDefault.value = name
  } else if (activeTab.value === 'credit_note') {
    creditNoteDefault.value = name
  } else {
    invoiceDefault.value = name
  }
}

async function saveDefaults() {
  isSaving.value = true

  try {
    await Promise.all([
      userStore.updateUserSettings({
        settings: {
          default_invoice_template: invoiceDefault.value,
          default_estimate_template: estimateDefault.value,
        },
      }),
      companyStore.updateCompanySettings({
        data: {
          settings: {
            default_credit_note_template: creditNoteDefault.value,
          },
        },
      }),
    ])

    notificationStore.showNotification({
      type: 'success',
      message: 'Les modèles de documents par défaut ont été enregistrés.',
    })
  } catch (error) {
    handleError(error)
  } finally {
    isSaving.value = false
  }
}

function labelFor(name, templates) {
  return templates.find((template) => template.name === name)?.label || name || 'Non défini'
}

function previewBackground(template) {
  return {
    premium: 'bg-gradient-to-br from-blue-50 to-white',
    classic: 'bg-slate-100',
    minimal: 'bg-white',
    night: 'bg-gradient-to-br from-slate-950 to-blue-950',
    franchise: 'bg-gradient-to-br from-emerald-50 to-white',
  }[template.theme]
}

function fallbackClass(template) {
  return {
    premium: 'border-blue-200 bg-white text-blue-950',
    classic: 'border-slate-300 bg-white text-slate-800',
    minimal: 'border-slate-200 bg-white text-slate-700',
    night: 'border-blue-700 bg-slate-950 text-blue-100',
    franchise: 'border-emerald-300 bg-white text-emerald-900',
  }[template.theme]
}
</script>

<style scoped>
.document-templates-page {
  padding-bottom: 48px;
}
</style>
