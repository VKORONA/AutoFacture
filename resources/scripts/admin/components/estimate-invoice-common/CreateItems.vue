<template>
  <table class="text-center item-table min-w-full">
    <colgroup>
      <col style="width: 40%; min-width: 280px" />
      <col style="width: 10%; min-width: 120px" />
      <col style="width: 15%; min-width: 120px" />
      <col
        v-if="store[storeProp].discount_per_item === 'YES'"
        style="width: 15%; min-width: 160px"
      />
      <col style="width: 15%; min-width: 120px" />
    </colgroup>
    <thead class="bg-white border border-gray-200 border-solid">
      <tr>
        <th class="px-5 py-3 text-sm not-italic font-medium leading-5 text-left text-gray-700 border-t border-b border-gray-200 border-solid">
          <BaseContentPlaceholders v-if="isLoading">
            <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
          </BaseContentPlaceholders>
          <span v-else class="pl-7">{{ $tc('items.item', 2) }}</span>
        </th>
        <th class="px-5 py-3 text-sm not-italic font-medium leading-5 text-right text-gray-700 border-t border-b border-gray-200 border-solid">
          <BaseContentPlaceholders v-if="isLoading">
            <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
          </BaseContentPlaceholders>
          <span v-else>{{ $t('invoices.item.quantity') }}</span>
        </th>
        <th class="px-5 py-3 text-sm not-italic font-medium leading-5 text-left text-gray-700 border-t border-b border-gray-200 border-solid">
          <BaseContentPlaceholders v-if="isLoading">
            <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
          </BaseContentPlaceholders>
          <span v-else>{{ $t('invoices.item.price') }}</span>
        </th>
        <th
          v-if="store[storeProp].discount_per_item === 'YES'"
          class="px-5 py-3 text-sm not-italic font-medium leading-5 text-left text-gray-700 border-t border-b border-gray-200 border-solid"
        >
          <BaseContentPlaceholders v-if="isLoading">
            <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
          </BaseContentPlaceholders>
          <span v-else>{{ $t('invoices.item.discount') }}</span>
        </th>
        <th class="px-5 py-3 text-sm not-italic font-medium leading-5 text-right text-gray-700 border-t border-b border-gray-200 border-solid">
          <BaseContentPlaceholders v-if="isLoading">
            <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
          </BaseContentPlaceholders>
          <span v-else class="pr-10 column-heading">{{ $t('invoices.item.amount') }}</span>
        </th>
      </tr>
    </thead>
    <draggable
      v-model="store[storeProp].items"
      item-key="id"
      tag="tbody"
      handle=".handle"
    >
      <template #item="{ element, index }">
        <Item
          :key="element.id"
          :index="index"
          :item-data="element"
          :loading="isLoading"
          :currency="defaultCurrency"
          :item-validation-scope="itemValidationScope"
          :invoice-items="store[storeProp].items"
          :store="store"
          :store-prop="storeProp"
        />
      </template>
    </draggable>
  </table>

  <div
    class="flex items-center justify-center w-full px-6 py-3 text-base border border-t-0 border-gray-200 border-solid cursor-pointer text-primary-400 hover:bg-primary-100"
    @click="addItem"
  >
    <BaseIcon name="PlusCircleIcon" class="mr-2" />
    {{ $t('general.add_new_item') }}
  </div>

  <section v-if="!isLoading" class="mt-4 rounded-2xl border border-blue-100 bg-blue-50/60 p-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h3 class="text-sm font-semibold text-slate-900">Répartition vente / prestations</h3>
        <p class="mt-1 text-xs leading-5 text-slate-600">
          Cette information est mémorisée sur le document pour calculer le chiffre d’affaires encaissé par catégorie. La nature d’un produit du catalogue est reprise automatiquement.
        </p>
      </div>
      <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700 shadow-sm">Déclaration micro</span>
    </div>

    <div class="mt-4 grid gap-3 lg:grid-cols-2">
      <label v-for="(line, index) in store[storeProp].items" :key="line.id" class="rounded-xl border border-blue-100 bg-white p-3">
        <span class="block truncate text-xs font-semibold text-slate-800">{{ line.name || `Ligne ${index + 1}` }}</span>
        <select
          v-model="line.business_activity_type"
          class="mt-2 w-full rounded-lg border-slate-300 text-xs"
          :disabled="Boolean(line.item_id)"
          @change="syncClassification(index, line)"
        >
          <option v-for="option in activityTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
        <span v-if="line.item_id" class="mt-1 block text-[11px] text-slate-500">Défini dans la fiche produit. Modifiez le produit pour changer sa catégorie.</span>
      </label>
    </div>
  </section>

  <EstimateLinePhotosPanel
    v-if="storeProp === 'newEstimate' && !isLoading"
    :estimate="store[storeProp]"
  />
</template>

<script setup>
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useItemStore } from '@/scripts/admin/stores/item'
import { computed, watch } from 'vue'
import Guid from 'guid'
import draggable from 'vuedraggable'
import Item from './CreateItemRow.vue'
import EstimateLinePhotosPanel from '@/scripts/admin/components/estimates/EstimateLinePhotosPanel.vue'

const props = defineProps({
  store: {
    type: Object,
    default: null,
  },
  storeProp: {
    type: String,
    default: '',
  },
  currency: {
    type: [Object, String, null],
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  itemValidationScope: {
    type: String,
    default: '',
  },
})

const companyStore = useCompanyStore()
const itemStore = useItemStore()
const activityTypes = [
  { value: 'goods_bic', label: 'Vente de marchandises / biens (BIC)' },
  { value: 'service_bic', label: 'Prestation commerciale ou artisanale (BIC)' },
  { value: 'service_bnc', label: 'Activité libérale non réglementée (BNC)' },
  { value: 'service_bnc_cipav', label: 'Activité libérale relevant de la Cipav (BNC)' },
]

const defaultCurrency = computed(() => {
  if (props.currency) {
    return props.currency
  }

  return companyStore.selectedCompanyCurrency
})

watch(
  () => props.store[props.storeProp].items.map((line) => line.item_id),
  () => syncCatalogClassifications(),
  { deep: true, immediate: true }
)

function addItem() {
  props.store.addItem()

  const item = props.store[props.storeProp].items.at(-1)
  if (!item.business_activity_type) {
    item.business_activity_type = 'service_bic'
  }

  if (props.storeProp !== 'newEstimate') return

  if (!item.line_uuid) item.line_uuid = Guid.raw()
  if (!Array.isArray(item.line_photos)) item.line_photos = []
}

function syncCatalogClassifications() {
  const catalog = Array.isArray(itemStore.items) ? itemStore.items : []

  props.store[props.storeProp].items.forEach((line, index) => {
    if (!line.business_activity_type) {
      line.business_activity_type = 'service_bic'
    }
    if (!line.item_id) return

    const product = catalog.find((item) => Number(item.id) === Number(line.item_id))
    if (!product?.business_activity_type) return
    if (line.business_activity_type === product.business_activity_type) return

    props.store.$patch((state) => {
      state[props.storeProp].items[index].business_activity_type = product.business_activity_type
    })
  })
}

function syncClassification(index, line) {
  props.store.updateItem({ ...line, index })
}
</script>
