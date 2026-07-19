<template>
  <BaseCard>
    <h6 class="font-medium text-lg text-left">
      {{ $t('settings.exchange_rate.title') }}
    </h6>
    <p class="mt-2 text-sm leading-snug text-gray-500" style="max-width: 680px">
      {{
        $t('settings.exchange_rate.description', {
          currency: companyStore.selectedCompanyCurrency.name,
        })
      }}
    </p>

    <form action="" @submit.prevent="submitBulkUpdate">
      <ValidateEach
        v-for="(currency, index) in exchangeRateStore.bulkCurrencies"
        :key="index"
        :state="currency"
        :rules="currencyArrayRules"
      >
        <template #default="{ v }">
          <BaseInputGroup
            class="my-5"
            :label="`${currency.code} to ${companyStore.selectedCompanyCurrency.code}`"
            :error="
              v.exchange_rate.$error && v.exchange_rate.$errors[0].$message
            "
            required
          >
            <BaseInput
              v-model="currency.exchange_rate"
              :addon="`1 ${currency.code} =`"
              :invalid="v.exchange_rate.$error"
              @input="v.exchange_rate.$touch()"
            >
              <template #right>
                <span class="text-gray-500 sm:text-sm">
                  {{ companyStore.selectedCompanyCurrency.code }}
                </span>
              </template>
            </BaseInput>
            <span class="text-gray-400 text-xs mt-2 font-light">
              {{
                $t('settings.exchange_rate.exchange_help_text', {
                  currency: currency.code,
                  baseCurrency: companyStore.selectedCompanyCurrency.code,
                })
              }}
            </span>
          </BaseInputGroup>
        </template>
      </ValidateEach>
      <div
        class="
          z-0
          flex
          justify-end
          mt-4
          pt-4
          border-t border-gray-200 border-solid border-modal-bg
        "
      >
        <BaseButton :loading="isSaving" variant="primary" type="submit">
          {{ $t('general.save') }}
        </BaseButton>
      </div>
    </form>
  </BaseCard>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import useVuelidate from '@vuelidate/core'
import { decimal, helpers, required } from '@vuelidate/validators'
import { ValidateEach } from '@vuelidate/components'
import { useExchangeRateStore } from '@/scripts/admin/stores/exchange-rate'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const exchangeRateStore = useExchangeRateStore()
const companyStore = useCompanyStore()
const { t } = useI18n()
const isSaving = ref(false)
const v = useVuelidate()
const emit = defineEmits(['update'])

const currencyArrayRules = {
  exchange_rate: {
    required: helpers.withMessage(t('validation.required'), required),
    decimal: helpers.withMessage(t('validation.valid_exchange_rate'), decimal),
  },
}

async function submitBulkUpdate() {
  v.value.$touch()
  if (v.value.$invalid) return

  isSaving.value = true
  try {
    const currencies = exchangeRateStore.bulkCurrencies.map((currency) => ({
      id: currency.id,
      exchange_rate: currency.exchange_rate,
    }))
    const response = await exchangeRateStore.updateBulkExchangeRate({ currencies })

    if (response.data.success) {
      emit('update', response.data.success)
    }
  } finally {
    isSaving.value = false
  }
}
</script>
