<template>
  <BaseModal :show="modalActive" @close="closeModal" @open="setData">
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalTitle }}
        <BaseIcon
          name="XIcon"
          class="h-6 w-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>

    <div class="px-8 py-8 sm:p-6">
      <div
        v-if="modalStore.data"
        class="grid grid-cols-1 gap-4 p-1 overflow-x-auto sm:grid-cols-2 lg:grid-cols-3"
      >
        <button
          v-for="template in modalStore.data.templates"
          :key="template.name"
          type="button"
          :class="{
            'border-primary-500 ring-2 ring-primary-100':
              selectedTemplate === template.name,
          }"
          class="relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white text-left transition hover:border-primary-300 hover:shadow-md"
          @click="selectedTemplate = template.name"
        >
          <div class="relative flex min-h-[190px] w-full items-center justify-center bg-gray-50 p-3">
            <img
              v-if="template.path && !brokenImages[template.name]"
              :src="template.path"
              :alt="template.label || template.name"
              class="max-h-[180px] w-full object-contain"
              @error="brokenImages[template.name] = true"
            />
            <div
              v-else
              class="h-[170px] w-full rounded-lg border p-4 shadow-sm"
              :class="fallbackClass(template.theme)"
            >
              <div class="flex items-start justify-between">
                <div>
                  <div class="text-[9px] font-black uppercase tracking-widest opacity-60">
                    AutoFacture
                  </div>
                  <div class="mt-1 text-base font-black">DOCUMENT</div>
                </div>
                <div class="rounded-full border px-2 py-1 text-[7px] font-bold">PRÊT</div>
              </div>
              <div class="mt-4 h-2 rounded bg-current opacity-20"></div>
              <div class="mt-2 h-12 rounded bg-current opacity-10"></div>
              <div class="mt-2 h-8 rounded bg-current opacity-10"></div>
              <div class="mt-3 ml-auto h-10 w-2/5 rounded bg-current opacity-20"></div>
            </div>

            <span
              v-if="selectedTemplate === template.name"
              class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-primary-500 text-white shadow"
            >
              <BaseIcon name="CheckIcon" class="h-4 w-4" />
            </span>
          </div>

          <div class="w-full border-t border-gray-100 p-3">
            <div class="text-sm font-semibold text-gray-900">
              {{ template.label || template.name }}
            </div>
            <div v-if="template.description" class="mt-1 text-xs leading-5 text-gray-500">
              {{ template.description }}
            </div>
          </div>
        </button>
      </div>

      <div v-if="!modalStore.data.store.isEdit" class="z-0 flex ml-3 pt-5">
        <BaseCheckbox
          v-model="modalStore.data.isMarkAsDefault"
          :set-initial-value="false"
          variant="primary"
          :label="$t('general.mark_as_default')"
          :description="modalStore.data.markAsDefaultDescription"
        />
      </div>
    </div>

    <div class="z-0 flex justify-end p-4 border-t border-gray-200 border-solid">
      <BaseButton class="mr-3" variant="primary-outline" @click="closeModal">
        {{ $t('general.cancel') }}
      </BaseButton>
      <BaseButton variant="primary" :disabled="!selectedTemplate" @click="chooseTemplate">
        <template #left="slotProps">
          <BaseIcon name="SaveIcon" :class="slotProps.class" />
        </template>
        {{ $t('general.choose') }}
      </BaseButton>
    </div>
  </BaseModal>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useModalStore } from '@/scripts/stores/modal'
import { useUserStore } from '@/scripts/admin/stores/user'

const modalStore = useModalStore()
const userStore = useUserStore()
const selectedTemplate = ref('')
const brokenImages = reactive({})

const modalActive = computed(() => {
  return modalStore.active && modalStore.componentName === 'SelectTemplate'
})

const modalTitle = computed(() => modalStore.title)

function setData() {
  const current = modalStore.data?.store?.[modalStore.data.storeProp]?.template_name
  selectedTemplate.value = current || modalStore.data?.templates?.[0]?.name || ''
}

async function chooseTemplate() {
  await modalStore.data.store.setTemplate(selectedTemplate.value)

  if (!modalStore.data.store.isEdit && modalStore.data.isMarkAsDefault) {
    if (modalStore.data.storeProp === 'newEstimate') {
      await userStore.updateUserSettings({
        settings: { default_estimate_template: selectedTemplate.value },
      })
    } else if (modalStore.data.storeProp === 'newInvoice') {
      await userStore.updateUserSettings({
        settings: { default_invoice_template: selectedTemplate.value },
      })
    }
  }

  closeModal()
}

function fallbackClass(theme) {
  return {
    premium: 'border-blue-200 bg-white text-blue-950',
    classic: 'border-slate-300 bg-white text-slate-800',
    minimal: 'border-slate-200 bg-white text-slate-700',
    night: 'border-blue-700 bg-slate-950 text-blue-100',
    franchise: 'border-emerald-300 bg-white text-emerald-900',
  }[theme] || 'border-gray-200 bg-white text-gray-800'
}

function closeModal() {
  modalStore.closeModal()

  setTimeout(() => {
    modalStore.$reset()
  }, 300)
}
</script>
