<template>
  <BasePage>
    <form @submit.prevent="submitCustomerData">
      <BasePageHeader :title="pageTitle">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
          <BaseBreadcrumbItem :title="$tc('customers.customer', 2)" to="/admin/customers" />
          <BaseBreadcrumbItem :title="pageTitle" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <BaseButton type="submit" :loading="isSaving" :disabled="isSaving">
            <template #left="slotProps">
              <BaseIcon name="SaveIcon" :class="slotProps.class" />
            </template>
            {{ isEdit ? $t('customers.update_customer') : $t('customers.save_customer') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard class="mt-5">
        <section class="grid grid-cols-5 gap-4 mb-8">
          <div class="col-span-5 lg:col-span-1">
            <h6 class="text-lg font-semibold">Identité du client</h6>
            <p class="mt-1 text-sm text-gray-500">
              Le type de client détermine le circuit de facturation électronique et les informations à demander.
            </p>
          </div>

          <div class="col-span-5 lg:col-span-4">
            <div class="grid gap-3 sm:grid-cols-2">
              <button
                type="button"
                :class="customerTypeButtonClass('business')"
                @click="setCustomerType('business')"
              >
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                  <BaseIcon name="OfficeBuildingIcon" class="h-6 w-6" />
                </span>
                <span class="text-left">
                  <span class="block text-sm font-semibold text-slate-900">Client professionnel</span>
                  <span class="mt-1 block text-xs leading-5 text-slate-500">
                    Entreprise, association ou professionnel indépendant.
                  </span>
                </span>
              </button>

              <button
                type="button"
                :class="customerTypeButtonClass('individual')"
                @click="setCustomerType('individual')"
              >
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                  <BaseIcon name="UserIcon" class="h-6 w-6" />
                </span>
                <span class="text-left">
                  <span class="block text-sm font-semibold text-slate-900">Client particulier</span>
                  <span class="mt-1 block text-xs leading-5 text-slate-500">
                    Consommateur final, sans identifiants professionnels.
                  </span>
                </span>
              </button>
            </div>

            <div
              class="mt-4 rounded-2xl border p-4"
              :class="isBusinessCustomer ? 'border-blue-200 bg-blue-50' : 'border-emerald-200 bg-emerald-50'"
            >
              <div class="flex items-start gap-3">
                <span
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white"
                  :class="isBusinessCustomer ? 'text-blue-700' : 'text-emerald-700'"
                >
                  <BaseIcon :name="isBusinessCustomer ? 'DocumentTextIcon' : 'CheckCircleIcon'" class="h-5 w-5" />
                </span>
                <div>
                  <div class="text-sm font-semibold text-slate-900">Préparation à la facturation électronique</div>
                  <div class="mt-1 text-sm font-medium" :class="statusTextClass">
                    {{ electronicInvoicingTitle }}
                  </div>
                  <p class="mt-1 text-xs leading-5 text-slate-600">
                    {{ electronicInvoicingMessage }}
                  </p>
                </div>
              </div>
            </div>

            <CompanyRegistrySearch
              v-if="isBusinessCustomer"
              :current-customer-id="customerStore.currentCustomer.id"
              @select="applyRegistryCompany"
            />

            <BaseInputGrid class="mt-6">
              <BaseInputGroup
                :label="isBusinessCustomer ? 'Nom commercial ou raison sociale' : 'Nom et prénom'"
                required
                :error="fieldError('name')"
              >
                <BaseInput
                  v-model.trim="customerStore.currentCustomer.name"
                  :invalid="v$.currentCustomer.name.$error"
                  @input="v$.currentCustomer.name.$touch()"
                />
              </BaseInputGroup>

              <BaseInputGroup
                v-if="isBusinessCustomer"
                label="Raison sociale complète"
              >
                <BaseInput v-model.trim="customerStore.currentCustomer.company_name" />
              </BaseInputGroup>

              <BaseInputGroup :label="isBusinessCustomer ? 'Contact principal' : 'Contact complémentaire'">
                <BaseInput v-model.trim="customerStore.currentCustomer.contact_name" />
              </BaseInputGroup>

              <template v-if="isBusinessCustomer">
                <BaseInputGroup label="SIREN" :error="fieldError('siren')">
                  <BaseInput
                    v-model.trim="customerStore.currentCustomer.siren"
                    maxlength="9"
                    inputmode="numeric"
                    placeholder="9 chiffres"
                    :invalid="v$.currentCustomer.siren.$error"
                    @input="v$.currentCustomer.siren.$touch()"
                  />
                </BaseInputGroup>

                <BaseInputGroup label="SIRET" :error="fieldError('siret')">
                  <BaseInput
                    v-model.trim="customerStore.currentCustomer.siret"
                    maxlength="14"
                    inputmode="numeric"
                    placeholder="14 chiffres"
                    :invalid="v$.currentCustomer.siret.$error"
                    @input="v$.currentCustomer.siret.$touch()"
                  />
                </BaseInputGroup>

                <BaseInputGroup label="TVA intracommunautaire">
                  <BaseInput
                    v-model.trim="customerStore.currentCustomer.vat_number"
                    placeholder="FR00123456789"
                  />
                </BaseInputGroup>

                <BaseInputGroup label="Code APE / NAF">
                  <BaseInput v-model.trim="customerStore.currentCustomer.ape_code" placeholder="4322B" />
                </BaseInputGroup>

                <BaseInputGroup label="Code de forme juridique">
                  <BaseInput
                    v-model.trim="customerStore.currentCustomer.legal_form"
                    placeholder="Code officiel"
                  />
                </BaseInputGroup>
              </template>

              <BaseInputGroup :label="$t('customers.email')" :error="fieldError('email')">
                <BaseInput
                  v-model.trim="customerStore.currentCustomer.email"
                  type="email"
                  :invalid="v$.currentCustomer.email.$error"
                  @input="v$.currentCustomer.email.$touch()"
                />
              </BaseInputGroup>

              <BaseInputGroup
                v-if="isBusinessCustomer"
                label="E-mail de facturation électronique"
                :error="fieldError('electronic_invoicing_email')"
              >
                <BaseInput
                  v-model.trim="customerStore.currentCustomer.electronic_invoicing_email"
                  type="email"
                  placeholder="facturation@entreprise.fr"
                  :invalid="v$.currentCustomer.electronic_invoicing_email.$error"
                  @input="v$.currentCustomer.electronic_invoicing_email.$touch()"
                />
              </BaseInputGroup>

              <BaseInputGroup :label="$t('customers.phone')">
                <BaseInput v-model.trim="customerStore.currentCustomer.phone" />
              </BaseInputGroup>

              <BaseInputGroup :label="$t('customers.website')" :error="fieldError('website')">
                <BaseInput
                  v-model.trim="customerStore.currentCustomer.website"
                  type="url"
                  :invalid="v$.currentCustomer.website.$error"
                  @input="v$.currentCustomer.website.$touch()"
                />
              </BaseInputGroup>

              <BaseInputGroup :label="$t('customers.primary_currency')" required :error="fieldError('currency_id')">
                <BaseMultiselect
                  v-model="customerStore.currentCustomer.currency_id"
                  value-prop="id"
                  label="name"
                  track-by="name"
                  :options="globalStore.currencies"
                  searchable
                  :can-deselect="false"
                  :can-clear="false"
                  :invalid="v$.currentCustomer.currency_id.$error"
                />
              </BaseInputGroup>
            </BaseInputGrid>
          </div>
        </section>

        <BaseDivider class="mb-8" />

        <section class="grid grid-cols-5 gap-4 mb-8">
          <div class="col-span-5 lg:col-span-1">
            <h6 class="text-lg font-semibold">Adresse de facturation</h6>
          </div>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup label="Nom affiché">
              <BaseInput v-model.trim="customerStore.currentCustomer.billing.name" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('customers.country')">
              <BaseMultiselect
                v-model="customerStore.currentCustomer.billing.country_id"
                value-prop="id"
                label="name"
                track-by="name"
                searchable
                :options="globalStore.countries"
              />
            </BaseInputGroup>

            <BaseInputGroup label="Adresse">
              <BaseInput v-model.trim="customerStore.currentCustomer.billing.address_street_1" />
            </BaseInputGroup>

            <BaseInputGroup label="Complément d'adresse">
              <BaseInput v-model.trim="customerStore.currentCustomer.billing.address_street_2" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('customers.zip_code')">
              <BaseInput v-model.trim="customerStore.currentCustomer.billing.zip" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('customers.city')">
              <BaseInput v-model.trim="customerStore.currentCustomer.billing.city" />
            </BaseInputGroup>
          </BaseInputGrid>
        </section>

        <BaseDivider class="mb-8" />

        <section class="grid grid-cols-5 gap-4 mb-8">
          <div class="col-span-5 lg:col-span-1">
            <h6 class="text-lg font-semibold">Portail client</h6>
            <p class="mt-1 text-sm text-gray-500">Optionnel pour le MVP.</p>
          </div>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <div class="md:col-span-2">
              <BaseSwitch v-model="customerStore.currentCustomer.enable_portal" />
            </div>

            <BaseInputGroup
              v-if="customerStore.currentCustomer.enable_portal"
              :label="$t('customers.password')"
              :error="fieldError('password')"
            >
              <BaseInput
                v-model.trim="customerStore.currentCustomer.password"
                :type="showPassword ? 'text' : 'password'"
                :invalid="v$.currentCustomer.password.$error"
                @input="v$.currentCustomer.password.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup
              v-if="customerStore.currentCustomer.enable_portal"
              label="Confirmer le mot de passe"
              :error="fieldError('confirm_password')"
            >
              <BaseInput
                v-model.trim="customerStore.currentCustomer.confirm_password"
                :type="showPassword ? 'text' : 'password'"
                :invalid="v$.currentCustomer.confirm_password.$error"
                @input="v$.currentCustomer.confirm_password.$touch()"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </section>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import useVuelidate from '@vuelidate/core'
import {
  email,
  helpers,
  minLength,
  required,
  requiredIf,
  sameAs,
  url,
} from '@vuelidate/validators'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import CompanyRegistrySearch from './partials/CompanyRegistrySearch.vue'

const customerStore = useCustomerStore()
const globalStore = useGlobalStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const isSaving = ref(false)
const showPassword = ref(false)
const isEdit = computed(() => route.name === 'customers.edit')
const pageTitle = computed(() => isEdit.value ? t('customers.edit_customer') : t('customers.new_customer'))
const isBusinessCustomer = computed(() => customerStore.currentCustomer.customer_type === 'business')
const hasProfessionalIdentifier = computed(() => Boolean(
  customerStore.currentCustomer.siret || customerStore.currentCustomer.vat_number
))
const electronicInvoicingTitle = computed(() => {
  if (!isBusinessCustomer.value) return 'Pas de facturation électronique B2B nécessaire'
  return hasProfessionalIdentifier.value
    ? 'Client professionnel prêt pour la facturation électronique'
    : 'Informations professionnelles à compléter'
})
const electronicInvoicingMessage = computed(() => {
  if (!isBusinessCustomer.value) {
    return 'Le client recevra une facture PDF standard. La vente B2C restera identifiable pour le e-reporting lorsque celui-ci s’applique à votre entreprise.'
  }
  if (hasProfessionalIdentifier.value) {
    return 'Les informations du client permettront de préparer une facture structurée Factur-X et son acheminement par une plateforme agréée.'
  }
  return 'Ajoutez le SIRET ou le numéro de TVA du client pour sécuriser le futur circuit B2B.'
})
const statusTextClass = computed(() => {
  if (!isBusinessCustomer.value) return 'text-emerald-700'
  return hasProfessionalIdentifier.value ? 'text-blue-700' : 'text-amber-700'
})

const optionalExactDigits = (length, message) => helpers.withMessage(
  message,
  (value) => !value || new RegExp(`^\\d{${length}}$`).test(String(value).replace(/\\D/g, ''))
)

const rules = computed(() => ({
  currentCustomer: {
    name: {
      required: helpers.withMessage(t('validation.required'), required),
      minLength: helpers.withMessage(t('validation.name_min_length', { count: 3 }), minLength(3)),
    },
    siren: {
      format: optionalExactDigits(9, 'Le SIREN doit contenir exactement 9 chiffres.'),
    },
    siret: {
      format: optionalExactDigits(14, 'Le SIRET doit contenir exactement 14 chiffres.'),
    },
    email: {
      required: helpers.withMessage(
        t('validation.required'),
        requiredIf(() => customerStore.currentCustomer.enable_portal)
      ),
      email: helpers.withMessage(t('validation.email_incorrect'), email),
    },
    electronic_invoicing_email: {
      email: helpers.withMessage(t('validation.email_incorrect'), email),
    },
    website: {
      url: helpers.withMessage(t('validation.invalid_url'), url),
    },
    currency_id: {
      required: helpers.withMessage(t('validation.required'), required),
    },
    password: {
      required: helpers.withMessage(
        t('validation.required'),
        requiredIf(() => customerStore.currentCustomer.enable_portal && !customerStore.currentCustomer.password_added)
      ),
      minLength: helpers.withMessage(t('validation.password_min_length', { count: 8 }), minLength(8)),
    },
    confirm_password: {
      sameAsPassword: helpers.withMessage(
        t('validation.password_incorrect'),
        sameAs(computed(() => customerStore.currentCustomer.password))
      ),
    },
  },
}))

const v$ = useVuelidate(rules, customerStore)

customerStore.resetCurrentCustomer()
customerStore.fetchCustomerInitialSettings(isEdit.value)

watch(
  () => customerStore.currentCustomer.customer_type,
  (customerType) => {
    if (customerType !== 'individual') return

    Object.assign(customerStore.currentCustomer, {
      company_name: '',
      siren: '',
      siret: '',
      vat_number: '',
      ape_code: '',
      legal_form: '',
      registry_checked_at: null,
      registry_source: null,
      electronic_invoicing_email: '',
    })
  }
)

function setCustomerType(customerType) {
  customerStore.currentCustomer.customer_type = customerType
}

function customerTypeButtonClass(customerType) {
  const active = customerStore.currentCustomer.customer_type === customerType
  return [
    'flex w-full items-start gap-3 rounded-2xl border p-4 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
    active
      ? 'border-blue-500 bg-blue-50 shadow-sm'
      : 'border-slate-200 bg-white hover:border-blue-300 hover:bg-slate-50',
  ]
}

function fieldError(field) {
  const validation = v$.value.currentCustomer[field]
  return validation && validation.$error ? validation.$errors[0].$message : ''
}

function applyRegistryCompany(company) {
  const france = globalStore.countries.find((country) => country.code === 'FR')

  Object.assign(customerStore.currentCustomer, {
    customer_type: 'business',
    name: company.name,
    company_name: company.company_name,
    siren: company.siren,
    siret: company.siret,
    vat_number: company.vat_number || '',
    ape_code: company.ape_code || '',
    legal_form: company.legal_form || '',
    registry_checked_at: company.verified_at,
    registry_source: company.source,
  })

  Object.assign(customerStore.currentCustomer.billing, {
    name: company.company_name,
    address_street_1: company.address.street || company.address.full,
    zip: company.address.postal_code,
    city: company.address.city,
    country_id: france?.id || customerStore.currentCustomer.billing.country_id,
  })
}

async function submitCustomerData() {
  v$.value.$touch()
  if (v$.value.$invalid) return

  isSaving.value = true
  try {
    const action = isEdit.value ? customerStore.updateCustomer : customerStore.addCustomer
    const response = await action({ ...customerStore.currentCustomer })
    router.push(`/admin/customers/${response.data.data.id}/view`)
  } finally {
    isSaving.value = false
  }
}
</script>
