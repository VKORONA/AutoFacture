<template>
  <BaseModal
    :show="modalActive"
    @close="closeModal"
    @open="resetForm"
  >
    <template #header>
      <div class="flex justify-between w-full">
        <div>
          <div class="font-semibold">Créer un avoir</div>
          <div v-if="invoice" class="mt-1 text-xs text-gray-500">
            Facture {{ invoice.invoice_number }}
          </div>
        </div>
        <BaseIcon
          name="XIcon"
          class="w-6 h-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>

    <form v-if="invoice" @submit.prevent="submit">
      <div class="px-8 py-6 sm:p-6">
        <div class="grid grid-cols-1 gap-3 p-4 mb-6 bg-gray-50 rounded-md sm:grid-cols-3">
          <div>
            <div class="text-xs text-gray-500">Total de la facture</div>
            <BaseFormatMoney
              class="font-semibold"
              :amount="invoice.total"
              :currency="currency"
            />
          </div>
          <div>
            <div class="text-xs text-gray-500">Déjà crédité</div>
            <BaseFormatMoney
              class="font-semibold"
              :amount="invoice.credited_amount || 0"
              :currency="currency"
            />
          </div>
          <div>
            <div class="text-xs text-gray-500">Maximum disponible</div>
            <BaseFormatMoney
              class="font-semibold text-primary-500"
              :amount="invoice.creditable_amount || 0"
              :currency="currency"
            />
          </div>
        </div>

        <BaseInputGroup label="Type d’avoir" required>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label
              class="flex p-3 border rounded-md cursor-pointer"
              :class="form.mode === 'full' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
            >
              <input
                v-model="form.mode"
                type="radio"
                value="full"
                class="mt-1 mr-3"
              >
              <span>
                <span class="block text-sm font-medium">Avoir total</span>
                <span class="block text-xs text-gray-500">
                  Créditer tout le montant encore disponible.
                </span>
              </span>
            </label>

            <label
              class="flex p-3 border rounded-md cursor-pointer"
              :class="form.mode === 'partial' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
            >
              <input
                v-model="form.mode"
                type="radio"
                value="partial"
                class="mt-1 mr-3"
              >
              <span>
                <span class="block text-sm font-medium">Avoir partiel</span>
                <span class="block text-xs text-gray-500">
                  Saisir un montant inférieur au solde disponible.
                </span>
              </span>
            </label>
          </div>
        </BaseInputGroup>

        <BaseInputGroup
          v-if="form.mode === 'partial'"
          class="mt-5"
          label="Montant TTC de l’avoir"
          :error="errors.amount"
          required
        >
          <BaseMoney
            v-model="amount"
            :currency="currency"
            :invalid="Boolean(errors.amount)"
            @update:modelValue="errors.amount = ''"
          />
        </BaseInputGroup>

        <BaseInputGroup
          class="mt-5"
          label="Motif de l’avoir"
          :error="errors.reason"
          required
        >
          <textarea
            v-model="form.reason"
            rows="5"
            maxlength="2000"
            class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:border-primary-500 focus:ring-primary-500"
            :class="{ 'border-red-500': errors.reason }"
            placeholder="Exemple : annulation de la prestation, remise commerciale ou erreur de quantité."
            @input="errors.reason = ''"
          />
          <div class="mt-1 text-xs text-gray-500">
            {{ form.reason.length }}/2 000 caractères
          </div>
        </BaseInputGroup>

        <div class="p-3 mt-5 text-xs text-amber-800 bg-amber-50 rounded-md">
          Un avoir émis est définitif et immuable. Le document fera référence à la facture d’origine et sera scellé avec son empreinte d’intégrité.
        </div>
      </div>

      <div class="flex justify-end p-4 border-t border-gray-200 border-solid">
        <BaseButton
          class="mr-3"
          variant="primary-outline"
          type="button"
          :disabled="isSaving"
          @click="closeModal"
        >
          Annuler
        </BaseButton>
        <BaseButton
          :loading="isSaving"
          :disabled="isSaving"
          variant="primary"
          type="submit"
        >
          Émettre et sceller l’avoir
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'

import { useCreditNoteStore } from '@/scripts/admin/stores/credit-note'
import { useModalStore } from '@/scripts/stores/modal'

const emit = defineEmits(['created'])

const creditNoteStore = useCreditNoteStore()
const modalStore = useModalStore()
const isSaving = ref(false)

const form = reactive({
  mode: 'full',
  amount: 0,
  reason: '',
})

const errors = reactive({
  amount: '',
  reason: '',
})

const modalActive = computed(() => {
  return modalStore.active && modalStore.componentName === 'CreateCreditNoteModal'
})

const invoice = computed(() => modalStore.data || null)

const currency = computed(() => {
  return invoice.value?.customer?.currency || invoice.value?.currency || null
})

const amount = computed({
  get: () => form.amount / 100,
  set: (value) => {
    form.amount = Math.round(Number(value || 0) * 100)
  },
})

function resetForm() {
  form.mode = 'full'
  form.amount = invoice.value?.creditable_amount || 0
  form.reason = ''
  errors.amount = ''
  errors.reason = ''
  isSaving.value = false
}

function validate() {
  errors.amount = ''
  errors.reason = ''

  const reason = form.reason.trim()
  const maximum = invoice.value?.creditable_amount || 0

  if (reason.length < 3) {
    errors.reason = 'Le motif doit contenir au moins trois caractères.'
  }

  if (form.mode === 'partial' && (form.amount < 1 || form.amount > maximum)) {
    errors.amount = 'Le montant doit être supérieur à zéro et ne pas dépasser le maximum disponible.'
  }

  return !errors.amount && !errors.reason
}

async function submit() {
  if (!validate() || isSaving.value) {
    return
  }

  isSaving.value = true

  const payload = {
    reason: form.reason.trim(),
  }

  if (form.mode === 'partial') {
    payload.amount = form.amount
  }

  try {
    const response = await creditNoteStore.createCreditNote(
      invoice.value.id,
      payload
    )

    emit('created', response.data.data)
    closeModal()
  } finally {
    isSaving.value = false
  }
}

function closeModal() {
  modalStore.closeModal()
}
</script>
