<template>
  <BasePage>
    <BasePageHeader title="Avoirs">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem title="Tableau de bord" to="/admin/dashboard" />
        <BaseBreadcrumbItem title="Avoirs" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton variant="primary-outline" @click="toggleFilters">
          Filtrer
          <template #right="slotProps">
            <BaseIcon
              :name="showFilters ? 'XIcon' : 'FilterIcon'"
              :class="slotProps.class"
            />
          </template>
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilters"
    >
      <BaseInputGroup label="Recherche">
        <BaseInput
          v-model="filters.search"
          placeholder="N° d’avoir, facture, client ou motif"
        >
          <template #left="slotProps">
            <BaseIcon name="SearchIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>

      <BaseInputGroup label="Du">
        <BaseDatePicker
          v-model="filters.from_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup label="Au">
        <BaseDatePicker
          v-model="filters.to_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      title="Aucun avoir émis"
      description="Les avoirs créés depuis une facture apparaîtront ici."
    />

    <div v-show="!showEmptyScreen" class="relative mt-8 table-container">
      <BaseTable
        ref="table"
        :data="fetchData"
        :columns="columns"
        :placeholder-count="creditNoteStore.creditNoteTotalCount >= 20 ? 10 : 5"
      >
        <template #cell-issue_date="{ row }">
          {{ row.data.formatted_issue_date }}
        </template>

        <template #cell-credit_note_number="{ row }">
          <router-link
            :to="`/admin/credit-notes/${row.data.id}/view`"
            class="font-medium text-primary-500"
          >
            {{ row.data.credit_note_number }}
          </router-link>
        </template>

        <template #cell-invoice_number="{ row }">
          <router-link
            v-if="row.data.invoice_id"
            :to="`/admin/invoices/${row.data.invoice_id}/view`"
            class="text-primary-500"
          >
            {{ row.data.invoice_number }}
          </router-link>
        </template>

        <template #cell-customer="{ row }">
          {{ row.data.customer?.name || row.data.customer?.company_name || '—' }}
        </template>

        <template #cell-total="{ row }">
          <BaseFormatMoney
            :amount="row.data.total"
            :currency="row.data.customer?.currency || row.data.currency"
          />
        </template>

        <template #cell-settlement_status="{ row }">
          <span
            class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
            :class="
              row.data.settlement_status === 'TO_REFUND'
                ? 'text-amber-700 bg-amber-100'
                : 'text-green-700 bg-green-100'
            "
          >
            {{
              row.data.settlement_status === 'TO_REFUND'
                ? 'Remboursement à traiter'
                : 'Imputé sur la facture'
            }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <router-link :to="`/admin/credit-notes/${row.data.id}/view`">
            <BaseButton size="sm" variant="primary-outline">
              Consulter
            </BaseButton>
          </router-link>
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { debouncedWatch } from '@vueuse/core'

import { useCreditNoteStore } from '@/scripts/admin/stores/credit-note'

const creditNoteStore = useCreditNoteStore()
const table = ref(null)
const showFilters = ref(false)
const isRequestOngoing = ref(true)

const filters = reactive({
  search: '',
  from_date: '',
  to_date: '',
})

const columns = [
  { key: 'issue_date', label: 'Date' },
  { key: 'credit_note_number', label: 'N° d’avoir' },
  { key: 'invoice_number', label: 'Facture d’origine' },
  { key: 'customer', label: 'Client', sortable: false },
  { key: 'total', label: 'Montant TTC' },
  { key: 'settlement_status', label: 'Traitement', sortable: false },
  {
    key: 'actions',
    label: 'Actions',
    sortable: false,
    thClass: 'text-right',
    tdClass: 'text-right',
  },
]

const showEmptyScreen = computed(() => {
  return !creditNoteStore.creditNoteTotalCount && !isRequestOngoing.value
})

debouncedWatch(
  filters,
  () => {
    refreshTable()
  },
  { debounce: 400 }
)

async function fetchData({ page, sort }) {
  isRequestOngoing.value = true

  try {
    const response = await creditNoteStore.fetchCreditNotes({
      ...filters,
      orderByField: sort.fieldName || 'issue_date',
      orderBy: sort.order || 'desc',
      page,
      limit: 10,
    })

    return {
      data: response.data.data,
      pagination: {
        totalPages: response.data.meta.last_page,
        currentPage: page,
        totalCount: response.data.meta.total,
        limit: 10,
      },
    }
  } finally {
    isRequestOngoing.value = false
  }
}

function refreshTable() {
  table.value && table.value.refresh()
}

function clearFilters() {
  filters.search = ''
  filters.from_date = ''
  filters.to_date = ''
}

function toggleFilters() {
  if (showFilters.value) {
    clearFilters()
  }

  showFilters.value = !showFilters.value
}
</script>
