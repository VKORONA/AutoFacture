<template>
  <div
    v-if="setupRequired"
    class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-5 text-blue-900"
  >
    <div class="flex items-start">
      <div
        class="mr-4 flex h-10 w-10 flex-none items-center justify-center rounded-full bg-blue-100 text-xl font-bold text-blue-700"
      >
        €
      </div>
      <div>
        <h2 class="text-lg font-semibold">
          Configurez votre entreprise avant de créer des documents
        </h2>
        <p class="mt-1 text-sm leading-6">
          Ces informations déterminent les mentions légales, la devise et la TVA
          appliquées aux devis et aux factures.
        </p>
        <ul v-if="setupMissingFields.length" class="mt-3 list-disc pl-5 text-sm">
          <li v-for="field in setupMissingFields" :key="field">
            {{ field }}
          </li>
        </ul>
      </div>
    </div>
  </div>

  <form @submit.prevent="updateCompanyData">
    <BaseSettingCard
      :title="$t('settings.company_info.company_info')"
      :description="$t('settings.company_info.section_description')"
    >
      <BaseInputGrid class="mt-5">
        <BaseInputGroup :label="$tc('settings.company_info.company_logo')">
          <BaseFileUploader
            v-model="previewLogo"
            base64
            @change="onFileInputChange"
            @remove="onFileInputRemove"
          />
        </BaseInputGroup>
      </BaseInputGrid>

      <BaseInputGrid class="mt-5">
        <BaseInputGroup
          :label="$tc('settings.company_info.company_name')"
          :error="v$.name.$error && v$.name.$errors[0].$message"
          required
        >
          <BaseInput
            v-model="companyForm.name"
            :invalid="v$.name.$error"
            @blur="v$.name.$touch()"
          />
        </BaseInputGroup>

        <BaseInputGroup label="Forme juridique">
          <BaseInput v-model="companyForm.legal_form" placeholder="EI, SARL, SASU…" />
        </BaseInputGroup>

        <BaseInputGroup
          label="SIRET"
          :error="v$.siret.$error && 'Le SIRET doit contenir exactement 14 chiffres.'"
          required
        >
          <BaseInput
            v-model="companyForm.siret"
            maxlength="14"
            inputmode="numeric"
            :invalid="v$.siret.$error"
            @blur="v$.siret.$touch()"
          />
        </BaseInputGroup>

        <BaseInputGroup
          label="SIREN"
          :error="v$.siren.$error && 'Le SIREN doit contenir exactement 9 chiffres.'"
        >
          <BaseInput
            v-model="companyForm.siren"
            maxlength="9"
            inputmode="numeric"
            :invalid="v$.siren.$error"
          />
        </BaseInputGroup>

        <BaseInputGroup label="Code APE / NAF">
          <BaseInput v-model="companyForm.ape_code" placeholder="4322B" />
        </BaseInputGroup>

        <BaseInputGroup label="Forme d’immatriculation / ville du RCS">
          <BaseInput v-model="companyForm.rcs_city" placeholder="Toulouse" />
        </BaseInputGroup>

        <BaseInputGroup label="Capital social (€)">
          <BaseInput
            v-model="companyForm.share_capital"
            type="number"
            min="0"
            step="0.01"
          />
        </BaseInputGroup>

        <BaseInputGroup label="Votre entreprise facture-t-elle la TVA ?" required>
          <BaseMultiselect
            v-model="companyForm.vat_regime"
            value-prop="value"
            label="label"
            :options="vatRegimes"
            :can-deselect="false"
            :can-clear="false"
          />
          <p class="mt-2 text-xs leading-5 text-gray-500">
            {{ vatRegimeHelp }}
          </p>
        </BaseInputGroup>

        <BaseInputGroup
          v-if="companyForm.vat_regime === 'standard'"
          label="Numéro de TVA intracommunautaire"
        >
          <BaseInput v-model="companyForm.vat_number" placeholder="FR00123456789" />
        </BaseInputGroup>

        <BaseInputGroup label="E-mail de facturation électronique">
          <BaseInput
            v-model="companyForm.electronic_invoicing_email"
            type="email"
          />
        </BaseInputGroup>

        <BaseInputGroup label="IBAN">
          <BaseInput v-model="companyForm.iban" placeholder="FR76…" />
        </BaseInputGroup>

        <BaseInputGroup label="BIC">
          <BaseInput v-model="companyForm.bic" maxlength="11" />
        </BaseInputGroup>

        <BaseInputGroup :label="$tc('settings.company_info.phone')">
          <BaseInput v-model="companyForm.address.phone" />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$tc('settings.company_info.country')"
          :error="
            v$.address.country_id.$error &&
            v$.address.country_id.$errors[0].$message
          "
          required
        >
          <BaseMultiselect
            v-model="companyForm.address.country_id"
            label="name"
            :invalid="v$.address.country_id.$error"
            :options="globalStore.countries"
            value-prop="id"
            :can-deselect="false"
            :can-clear="false"
            searchable
            track-by="name"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$tc('settings.company_info.state')">
          <BaseInput v-model="companyForm.address.state" name="state" type="text" />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$tc('settings.company_info.city')"
          :error="v$.address.city.$error && 'La ville est obligatoire.'"
          required
        >
          <BaseInput
            v-model="companyForm.address.city"
            type="text"
            :invalid="v$.address.city.$error"
          />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$tc('settings.company_info.zip')"
          :error="v$.address.zip.$error && 'Le code postal est obligatoire.'"
          required
        >
          <BaseInput
            v-model="companyForm.address.zip"
            :invalid="v$.address.zip.$error"
          />
        </BaseInputGroup>

        <div>
          <BaseInputGroup
            :label="$tc('settings.company_info.address')"
            :error="
              v$.address.address_street_1.$error &&
              'L’adresse de l’entreprise est obligatoire.'
            "
            required
          >
            <BaseTextarea
              v-model="companyForm.address.address_street_1"
              rows="2"
              :invalid="v$.address.address_street_1.$error"
            />
          </BaseInputGroup>
          <BaseTextarea
            v-model="companyForm.address.address_street_2"
            rows="2"
            :row="2"
            class="mt-2"
          />
        </div>
      </BaseInputGrid>

      <BaseButton
        :loading="isSaving"
        :disabled="isSaving"
        type="submit"
        class="mt-6"
      >
        <template #left="slotProps">
          <BaseIcon v-if="!isSaving" :class="slotProps.class" name="SaveIcon" />
        </template>
        {{ setupRequired ? 'Valider et commencer' : $tc('settings.company_info.save') }}
      </BaseButton>

      <div v-if="companyStore.companies.length !== 1 && !setupRequired" class="py-5">
        <BaseDivider class="my-4" />
        <h3 class="text-lg font-medium leading-6 text-gray-900">
          {{ $tc('settings.company_info.delete_company') }}
        </h3>
        <div class="mt-2 max-w-xl text-sm text-gray-500">
          <p>{{ $tc('settings.company_info.delete_company_description') }}</p>
        </div>
        <div class="mt-5">
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-100 px-4 py-2 font-medium text-red-700 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:text-sm"
            @click="removeCompany"
          >
            {{ $tc('general.delete') }}
          </button>
        </div>
      </div>
    </BaseSettingCard>
  </form>
  <DeleteCompanyModal />
</template>

<script setup>
import { computed, inject, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { helpers, minLength, required } from '@vuelidate/validators'
import { useVuelidate } from '@vuelidate/core'
import { useI18n } from 'vue-i18n'

import DeleteCompanyModal from '@/scripts/admin/components/modal-components/DeleteCompanyModal.vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useModalStore } from '@/scripts/stores/modal'

const companyStore = useCompanyStore()
const globalStore = useGlobalStore()
const modalStore = useModalStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const utils = inject('utils')

const vatRegimes = [
  { value: 'standard', label: 'Oui — régime réel normal ou simplifié' },
  { value: 'franchise_base', label: 'Non — franchise en base de TVA' },
  { value: 'exempt', label: 'Non — activité exonérée de TVA' },
]

const setupStatus = ref(
  companyStore.selectedCompany?.setup || {
    complete: route.query.setup !== 'required',
    missing_fields: [],
    vat_regime: null,
  }
)

const setupRequired = computed(
  () => route.query.setup === 'required' || !setupStatus.value.complete
)
const setupMissingFields = computed(
  () => setupStatus.value.missing_fields || []
)

const vatRegimeHelp = computed(() => {
  if (companyForm.vat_regime === 'standard') {
    return 'La TVA à 20 % sera proposée par défaut. Les taux français de 10 %, 5,5 % et 2,1 % resteront disponibles.'
  }

  if (companyForm.vat_regime === 'franchise_base') {
    return 'Aucune TVA ne sera facturée. La mention « TVA non applicable, article 293 B du CGI » sera utilisée sur les documents.'
  }

  return 'Aucune TVA ne sera facturée. La mention légale correspondant à votre exonération devra être précisée dans vos modèles.'
})

const isSaving = ref(false)

const companyForm = reactive({
  name: null,
  legal_form: '',
  siren: '',
  siret: '',
  vat_number: '',
  ape_code: '',
  rcs_city: '',
  share_capital: null,
  iban: '',
  bic: '',
  vat_regime: 'standard',
  vat_exempt: false,
  electronic_invoicing_email: '',
  logo: null,
  address: {
    address_street_1: '',
    address_street_2: '',
    website: '',
    country_id: null,
    state: '',
    city: '',
    phone: '',
    zip: '',
  },
})

utils.mergeSettings(companyForm, {
  ...companyStore.selectedCompany,
})

const previewLogo = ref([])
const logoFileBlob = ref(null)
const logoFileName = ref(null)
const isCompanyLogoRemoved = ref(false)

if (companyForm.logo) {
  previewLogo.value.push({ image: companyForm.logo })
}

const exactDigits = (length) => (value) =>
  !value || new RegExp(`^\\d{${length}}$`).test(value)

const rules = computed(() => ({
  name: {
    required: helpers.withMessage(t('validation.required'), required),
    minLength: helpers.withMessage(
      t('validation.name_min_length'),
      minLength(3)
    ),
  },
  siren: { exactDigits: exactDigits(9) },
  siret: {
    required: helpers.withMessage(t('validation.required'), required),
    exactDigits: exactDigits(14),
  },
  address: {
    country_id: {
      required: helpers.withMessage(t('validation.required'), required),
    },
    address_street_1: { required },
    city: { required },
    zip: { required },
  },
}))

const v$ = useVuelidate(rules, computed(() => companyForm))

globalStore.fetchCountries()

function onFileInputChange(fileName, file, fileCount, fileList) {
  logoFileName.value = fileList.name
  logoFileBlob.value = file
}

function onFileInputRemove() {
  logoFileBlob.value = null
  isCompanyLogoRemoved.value = true
}

async function updateCompanyData() {
  v$.value.$touch()
  if (v$.value.$invalid) return

  isSaving.value = true

  try {
    const payload = {
      ...companyForm,
      complete_setup: true,
      vat_exempt: ['franchise_base', 'exempt'].includes(
        companyForm.vat_regime
      ),
      address: { ...companyForm.address },
    }

    const res = await companyStore.updateCompany(payload)

    if (res.data.data && (logoFileBlob.value || isCompanyLogoRemoved.value)) {
      const logoData = new FormData()

      if (logoFileBlob.value) {
        logoData.append(
          'company_logo',
          JSON.stringify({
            name: logoFileName.value,
            data: logoFileBlob.value,
          })
        )
      }

      logoData.append(
        'is_company_logo_removed',
        isCompanyLogoRemoved.value
      )
      await companyStore.updateCompanyLogo(logoData)
      logoFileBlob.value = null
      isCompanyLogoRemoved.value = false
    }

    setupStatus.value =
      res.data.company_setup || res.data.data.setup || setupStatus.value

    await globalStore.bootstrap()

    if (setupStatus.value.complete) {
      await router.replace({ name: 'dashboard' })
    }
  } finally {
    isSaving.value = false
  }
}

function removeCompany() {
  modalStore.openModal({
    title: t('settings.company_info.are_you_absolutely_sure'),
    componentName: 'DeleteCompanyModal',
    size: 'sm',
  })
}
</script>
