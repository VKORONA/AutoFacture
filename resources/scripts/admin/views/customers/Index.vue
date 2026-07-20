<template>
  <BasePage>
    <BasePageHeader :title="$t('customers.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem
          :title="$tc('customers.customer', 2)"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-5">
          <BaseButton
            v-show="customerStore.totalCustomers"
            variant="primary-outline"
            @click="toggleFilter"
          >
            {{ $t('general.filter') }}
            <template #right="slotProps">
              <BaseIcon
                v-if="!showFilters"
                name="FilterIcon"
                :class="slotProps.class"
              />
              <BaseIcon v-else name="XIcon" :class="slotProps.class" />
            </template>
          </BaseButton>

          <BaseButton
            v-if="userStore.hasAbilities(abilities.CREATE_CUSTOMER)"
            @click="$router.push('customers/create')"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('customers.new_customer') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="$t('customers.display_name')" class="text-left">
        <BaseInput
          v-model="filters.display_name"
          type="text"
          name="name"
          autocomplete="off"
        />
      </BaseInputGroup>

      <BaseInputGroup label="Type de client" class="text-left">
        <BaseMultiselect
          v-model="filters.customer_type"
          value-prop="value"
          label="label"
          :options="customerTypeOptions"
          :can-deselect="true"
          :can-clear="true"
          placeholder="Tous les clients"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('customers.contact_name')" class="text-left">
        <BaseInput
          v-model="filters.contact_name"
          type="text"
          name="address_name"
          autocomplete="off"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('customers.phone')" class="text-left">
        <BaseInput
          v-model="filters.phone"
          type="text"
          name="phone"
          autocomplete="off"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('customers.no_customers')"
      :description="$t('customers.list_of_customers')"
    >
      <AstronautIcon class="mt-5 mb-4" />

      <template #actions>
        <BaseButton
          v-if="userStore.hasAbilities(abilities.CREATE_CUSTOMER)"
          variant="primary-outline"
          @click="$router.push('/admin/customers/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('customers.add_new_customer') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div class="relative flex items-center justify-end h-5">
        <BaseDropdown v-if="customerStore.selectedCustomers.length">
          <template #activator>
            <span
              class="flex text-sm font-medium cursor-pointer select-none text-primary-400"
            >
              {{ $t('general.actions') }}
              <BaseIcon name="ChevronDownIcon" />
            </span>
          </template>
          <BaseDropdownItem @click="removeMultipleCustomers">
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        ref="tableComponent"
        class="mt-3"
        :data="fetchData"
        :columns="customerColumns"
      >
        <template #header>
          <div class="absolute z-10 items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="selectAllFieldStatus"
              variant="primary"
              @change="customerStore.selectAllCustomers"
            />
          </div>
        </template>

        <template #cell-status="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.data.id"
              v-model="selectField"
              :value="row.data.id"
              variant="primary"
            />
          </div>
        </template>

        <template #cell-name="{ row }">
          <router-link :to="{ path: `customers/${row.data.id}/view` }">
            <BaseText
              :text="row.data.name"
              :length="30"
              tag="span"
              class="font-medium text-primary-500 flex flex-col"
            />
            <BaseText
              :text="row.data.contact_name ? row.data.contact_name : ''"
              :length="30"
              tag="span"
              class="text-xs text-gray-400"
            />
          </router-link>
        </template>

        <template #cell-customer_type="{ row }">
          <span
            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
            :class="
              row.data.customer_type === 'individual'
                ? 'bg-violet-100 text-violet-800'
                : 'bg-blue-100 text-blue-800'
            "
          >
            {{ row.data.customer_type === 'individual' ? 'Particulier' : 'Professionnel' }}
          </span>
        </template>

        <template #cell-phone="{ row }">
          <span>{{ row.data.phone ? row.data.phone : '-' }}</span>
        </template>

        <template #cell-due_amount="{ row }">
          <BaseFormatMoney
            :amount="row.data.due_amount || 0"
            :currency="row.data.currency"
          />
        </template>

        <template #cell-created_at="{ row }">
          <span>{{ row.data.formatted_created_at }}</span>
        </template>

        <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
          <CustomerDropdown
            :row="row.data"
            :table="tableComponent"
            :load-data="refreshTable"
          />
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { debouncedWatch } from '@vueuse/core'
import { reactive, ref, computed, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useUserStore } from '@/scripts/admin/stores/user'

import abilities from '@/scripts/admin/stub/abilities'

import CustomerDropdown from '@/scripts/admin/components/dropdowns/CustomerIndexDropdown.vue'
import AstronautIcon from '@/scripts/components/icons/empty/AstronautIcon.vue'

const dialogStore = useDialogStore()
const customerStore = useCustomerStore()
const userStore = useUserStore()

const tableComponent = ref(null)
const showFilters = ref(false)
const isFetchingInitialData = ref(true)
const { t } = useI18n()

const customerTypeOptions = [
  { value: 'business', label: 'Professionnel' },
  { value: 'individual', label: 'Particulier' },
]

const filters = reactive({
  display_name: '',
  customer_type: null,
  contact_name: '',
  phone: '',
})

const showEmptyScreen = computed(
  () => !customerStore.totalCustomers && !isFetchingInitialData.value
)

const selectField = computed({
  get: () => customerStore.selectedCustomers,
  set: (value) => customerStore.selectCustomer(value),
})

const selectAllFieldStatus = computed({
  get: () => customerStore.selectAllField,
  set: (value) => customerStore.setSelectAllState(value),
})

const customerColumns = computed(() => [
  {
    key: 'status',
    thClass: 'extra w-10 pr-0',
    sortable: false,
    tdClass: 'font-medium text-gray-900 pr-0',
  },
  {
    key: 'name',
    label: t('customers.name'),
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
  },
  { key: 'customer_type', label: 'Type' },
  { key: 'phone', label: t('customers.phone') },
  { key: 'due_amount', label: t('customers.amount_due') },
  { key: 'created_at', label: t('items.added_on') },
  {
    key: 'actions',
    tdClass: 'text-right text-sm font-medium pl-0',
    thClass: 'pl-0',
    sortable: false,
  },
])

debouncedWatch(filters, refreshTable, { debounce: 500 })

onUnmounted(() => {
  if (customerStore.selectAllField) customerStore.selectAllCustomers()
})

function refreshTable() {
  tableComponent.value?.refresh()
}

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_CUSTOMER,
    abilities.EDIT_CUSTOMER,
    abilities.VIEW_CUSTOMER,
  ])
}

async function fetchData({ page, sort }) {
  const data = {
    display_name: filters.display_name,
    customer_type: filters.customer_type,
    contact_name: filters.contact_name,
    phone: filters.phone,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isFetchingInitialData.value = true
  const response = await customerStore.fetchCustomers(data)
  isFetchingInitialData.value = false

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 10,
    },
  }
}

function clearFilter() {
  filters.display_name = ''
  filters.customer_type = null
  filters.contact_name = ''
  filters.phone = ''
}

function toggleFilter() {
  if (showFilters.value) clearFilter()
  showFilters.value = !showFilters.value
}

function removeMultipleCustomers() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('customers.confirm_delete', 2),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (!res) return
      customerStore.deleteMultipleCustomers().then((response) => {
        if (response.data) refreshTable()
      })
    })
}
</script>
