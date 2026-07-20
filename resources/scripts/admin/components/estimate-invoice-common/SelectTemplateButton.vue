<template>
  <div>
    <label class="flex text-gray-800 font-medium text-sm mb-2">
      {{ $t('general.select_template') }}
      <span class="text-sm text-red-500"> *</span>
    </label>
    <BaseButton
      type="button"
      class="flex justify-center w-full text-sm lg:w-auto hover:bg-gray-200"
      variant="gray"
      @click="openTemplateModal"
    >
      <template #right="slotProps">
        <BaseIcon name="PencilIcon" :class="slotProps.class" />
      </template>
      {{ currentTemplateLabel }}
    </BaseButton>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useModalStore } from '@/scripts/stores/modal'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  store: {
    type: Object,
    default: null,
  },
  storeProp: {
    type: String,
    default: '',
  },
  isMarkAsDefault: {
    type: Boolean,
    default: false,
  },
})

const modalStore = useModalStore()
const { t } = useI18n()

const currentTemplateLabel = computed(() => {
  const name = props.store?.[props.storeProp]?.template_name
  const template = props.store?.templates?.find((item) => item.name === name)

  return template?.label || name || t('general.choose_template')
})

function openTemplateModal() {
  let markAsDefaultDescription = ''

  if (props.storeProp === 'newEstimate') {
    markAsDefaultDescription = t(
      'estimates.mark_as_default_estimate_template_description'
    )
  } else if (props.storeProp === 'newInvoice') {
    markAsDefaultDescription = t(
      'invoices.mark_as_default_invoice_template_description'
    )
  }

  modalStore.openModal({
    title: t('general.choose_template'),
    componentName: 'SelectTemplate',
    data: {
      templates: props.store.templates,
      store: props.store,
      storeProp: props.storeProp,
      isMarkAsDefault: props.isMarkAsDefault,
      markAsDefaultDescription,
    },
  })
}
</script>
