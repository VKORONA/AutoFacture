<template>
  <BasePage v-if="creditNote">
    <BasePageHeader :title="creditNote.credit_note_number">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem title="Tableau de bord" to="/admin/dashboard" />
        <BaseBreadcrumbItem title="Avoirs" to="/admin/credit-notes" />
        <BaseBreadcrumbItem
          :title="creditNote.credit_note_number"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <a :href="creditNote.pdf_url" target="_blank" rel="noopener">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="DocumentDownloadIcon" :class="slotProps.class" />
            </template>
            Ouvrir le PDF
          </BaseButton>
        </a>
      </template>
    </BasePageHeader>

    <div class="grid grid-cols-1 gap-6 mt-8 xl:grid-cols-3">
      <BaseCard class="xl:col-span-2">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <div>
            <div class="text-xs font-medium text-gray-500 uppercase">
              Date d’émission
            </div>
            <div class="mt-1 text-sm font-semibold">
              {{ creditNote.formatted_issue_date }}
            </div>
          </div>

          <div>
            <div class="text-xs font-medium text-gray-500 uppercase">
              Facture d’origine
            </div>
            <router-link
              :to="`/admin/invoices/${creditNote.invoice_id}/view`"
              class="inline-block mt-1 text-sm font-semibold text-primary-500"
            >
              {{ creditNote.invoice_number }}
            </router-link>
          </div>

          <div>
            <div class="text-xs font-medium text-gray-500 uppercase">
              Client
            </div>
            <div class="mt-1 text-sm font-semibold">
              {{
                creditNote.customer?.company_name ||
                creditNote.customer?.name ||
                '—'
              }}
            </div>
          </div>

          <div>
            <div class="text-xs font-medium text-gray-500 uppercase">
              Statut
            </div>
            <span
              class="inline-flex px-2 py-1 mt-1 text-xs font-medium text-green-700 bg-green-100 rounded-full"
            >
              Émis et scellé
            </span>
          </div>
        </div>

        <div class="pt-5 mt-6 border-t border-gray-200">
          <div class="text-xs font-medium text-gray-500 uppercase">Motif</div>
          <p class="mt-2 text-sm text-gray-800 whitespace-pre-wrap">
            {{ creditNote.reason }}
          </p>
        </div>
      </BaseCard>

      <BaseCard>
        <h2 class="mb-4 text-sm font-semibold text-gray-800">
          Synthèse comptable
        </h2>

        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">Montant HT</span>
            <BaseFormatMoney
              :amount="creditNote.sub_total"
              :currency="currency"
            />
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">TVA correspondante</span>
            <BaseFormatMoney :amount="creditNote.tax" :currency="currency" />
          </div>
          <div
            class="flex justify-between pt-3 text-base font-semibold border-t border-gray-200"
          >
            <span>Total de l’avoir</span>
            <BaseFormatMoney :amount="creditNote.total" :currency="currency" />
          </div>
        </div>

        <div class="p-3 mt-5 text-xs rounded-md" :class="settlementClass">
          <template v-if="creditNote.refundable_amount > 0">
            <strong>Remboursement à traiter :</strong><br>
            <BaseFormatMoney
              class="mt-1 text-sm font-semibold"
              :amount="creditNote.refundable_amount"
              :currency="currency"
            />
          </template>
          <template v-else>
            Le montant a été intégralement imputé sur le solde de la facture.
          </template>
        </div>
      </BaseCard>
    </div>

    <BaseCard class="mt-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-gray-800">Document PDF</h2>
        <span class="text-xs text-gray-500">
          Document généré depuis le snapshot scellé
        </span>
      </div>

      <iframe
        :src="creditNote.pdf_url"
        class="w-full bg-white border border-gray-300 rounded-md"
        style="height: 72vh"
      />
    </BaseCard>

    <div class="mt-4 text-xs text-gray-400 break-all">
      Empreinte SHA-256 : {{ creditNote.immutable_hash }}
    </div>
  </BasePage>

  <div v-else class="flex items-center justify-center h-64">
    <LoadingIcon class="w-8 h-8 text-primary-500 animate-spin" />
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'

import { useCreditNoteStore } from '@/scripts/admin/stores/credit-note'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'

const route = useRoute()
const creditNoteStore = useCreditNoteStore()

const creditNote = computed(() => creditNoteStore.currentCreditNote)

const currency = computed(() => {
  return creditNote.value?.customer?.currency || creditNote.value?.currency || null
})

const settlementClass = computed(() => {
  return creditNote.value?.refundable_amount > 0
    ? 'text-amber-800 bg-amber-50'
    : 'text-green-800 bg-green-50'
})

watch(
  () => route.params.id,
  (id) => {
    if (id) {
      creditNoteStore.fetchCreditNote(id)
    }
  },
  { immediate: true }
)
</script>
