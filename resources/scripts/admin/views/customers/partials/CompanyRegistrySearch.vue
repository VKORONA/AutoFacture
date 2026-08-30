<template>
  <section class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
    <div
      class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
    >
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-sm font-semibold text-slate-900">
            Rechercher l’entreprise officielle
          </h3>
          <span
            class="
              rounded-full
              bg-emerald-100
              px-2
              py-0.5
              text-xs
              font-medium
              text-emerald-700
            "
          >
            API gouvernementale
          </span>
        </div>
        <p class="mt-1 text-xs leading-5 text-slate-500">
          Raison sociale, SIREN ou SIRET. AutoFacture interroge le service
          officiel depuis son serveur.
        </p>
      </div>
    </div>

    <form class="mt-4 grid gap-3 lg:grid-cols-12" @submit.prevent="search">
      <label class="lg:col-span-6">
        <span class="text-xs font-medium text-slate-700"
          >Entreprise, SIREN ou SIRET</span
        >
        <input
          v-model.trim="form.q"
          class="
            mt-1.5
            block
            w-full
            rounded-lg
            border-slate-300
            text-sm
            shadow-sm
            focus:border-blue-500 focus:ring-blue-500
          "
          placeholder="Ex. OCTO Technology ou 418166096"
          required
          minlength="2"
        />
      </label>
      <label class="lg:col-span-2">
        <span class="text-xs font-medium text-slate-700">Code postal</span>
        <input
          v-model.trim="form.postal_code"
          class="
            mt-1.5
            block
            w-full
            rounded-lg
            border-slate-300
            text-sm
            shadow-sm
            focus:border-blue-500 focus:ring-blue-500
          "
          inputmode="numeric"
          maxlength="5"
          placeholder="75002"
        />
      </label>
      <label class="lg:col-span-2">
        <span class="text-xs font-medium text-slate-700">Ville</span>
        <input
          v-model.trim="form.city"
          class="
            mt-1.5
            block
            w-full
            rounded-lg
            border-slate-300
            text-sm
            shadow-sm
            focus:border-blue-500 focus:ring-blue-500
          "
          placeholder="Paris"
        />
      </label>
      <div class="flex items-end lg:col-span-2">
        <button
          type="submit"
          class="
            h-10
            w-full
            rounded-lg
            bg-blue-600
            px-4
            text-sm
            font-semibold
            text-white
            shadow-sm
            hover:bg-blue-700
            disabled:cursor-not-allowed disabled:opacity-60
          "
          :disabled="isSearching || form.q.length < 2"
        >
          {{ isSearching ? 'Recherche…' : 'Rechercher' }}
        </button>
      </div>
    </form>

    <div
      v-if="errorMessage"
      class="
        mt-4
        rounded-xl
        border border-red-200
        bg-red-50
        p-3
        text-sm text-red-700
      "
    >
      <div class="font-semibold">La recherche n’a pas abouti</div>
      <p class="mt-1 text-xs">
        {{ errorMessage }} Vous pouvez continuer la saisie manuellement.
      </p>
    </div>

    <div
      v-if="
        hasSearched && !isSearching && results.length === 0 && !errorMessage
      "
      class="
        mt-4
        rounded-xl
        border border-slate-200
        bg-white
        p-4
        text-sm text-slate-600
      "
    >
      Aucune entreprise trouvée. Vérifiez l’orthographe ou essayez avec le SIREN
      ou le SIRET.
    </div>

    <div v-if="results.length" class="mt-5 space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-700"
          >{{ results.length }} établissement(s) proposé(s)</span
        >
        <span class="text-xs text-slate-500"
          >Données vérifiées {{ verifiedDate }}</span
        >
      </div>

      <article
        v-for="company in results"
        :key="company.siret"
        class="rounded-xl border bg-white p-4"
        :class="
          company.duplicate_customer ? 'border-amber-300' : 'border-slate-200'
        "
      >
        <div
          class="
            flex flex-col
            gap-3
            sm:flex-row sm:items-start sm:justify-between
          "
        >
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h4 class="text-sm font-semibold text-slate-900">
                {{ company.company_name }}
              </h4>
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="
                  company.is_active
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-slate-200 text-slate-600'
                "
              >
                {{
                  company.is_active
                    ? 'Établissement actif'
                    : 'Établissement fermé'
                }}
              </span>
              <span
                v-if="company.is_head_office"
                class="
                  rounded-full
                  bg-blue-100
                  px-2
                  py-0.5
                  text-xs
                  font-medium
                  text-blue-700
                "
              >
                Siège
              </span>
            </div>
            <p class="mt-2 text-xs text-slate-600">
              {{ company.address.full }}
            </p>
            <div
              class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500"
            >
              <span
                >SIREN
                <strong class="text-slate-700">{{
                  company.siren
                }}</strong></span
              >
              <span
                >SIRET
                <strong class="text-slate-700">{{
                  company.siret
                }}</strong></span
              >
              <span v-if="company.ape_code"
                >APE
                <strong class="text-slate-700">{{
                  company.ape_code
                }}</strong></span
              >
            </div>
            <div
              v-if="company.duplicate_customer"
              class="
                mt-3
                rounded-lg
                bg-amber-50
                px-3
                py-2
                text-xs text-amber-800
              "
            >
              Ce SIRET est déjà utilisé par le client «
              {{ company.duplicate_customer.name }} ».
            </div>
          </div>
          <button
            type="button"
            class="shrink-0 rounded-lg border px-3 py-2 text-xs font-semibold"
            :class="
              company.duplicate_customer
                ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                : 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100'
            "
            :disabled="Boolean(company.duplicate_customer)"
            @click="pendingCompany = company"
          >
            Sélectionner
          </button>
        </div>
      </article>
    </div>

    <div
      v-if="pendingCompany"
      class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4"
    >
      <div class="text-sm font-semibold text-slate-900">
        Compléter la fiche avec {{ pendingCompany.company_name }} ?
      </div>
      <p class="mt-1 text-xs leading-5 text-slate-600">
        Cette action remplacera les champs d’identité professionnelle et
        l’adresse de facturation visibles dans le formulaire. Rien n’est
        enregistré avant le bouton « Enregistrer ».
      </p>
      <div class="mt-3 flex flex-wrap gap-2">
        <button
          type="button"
          class="
            rounded-lg
            bg-blue-600
            px-4
            py-2
            text-xs
            font-semibold
            text-white
            hover:bg-blue-700
          "
          @click="applySelection"
        >
          Utiliser ces données vérifiées
        </button>
        <button
          type="button"
          class="rounded-lg px-4 py-2 text-xs font-semibold text-slate-600"
          @click="pendingCompany = null"
        >
          Annuler
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useCustomerStore } from '@/scripts/admin/stores/customer'

const props = defineProps({
  currentCustomerId: {
    type: [Number, String],
    default: null,
  },
})
const emit = defineEmits(['select'])
const customerStore = useCustomerStore()
const form = reactive({ q: '', postal_code: '', city: '' })
const results = ref([])
const meta = ref(null)
const pendingCompany = ref(null)
const isSearching = ref(false)
const hasSearched = ref(false)
const errorMessage = ref('')
const verifiedDate = computed(() => {
  if (!meta.value?.verified_at) return 'aujourd’hui'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(meta.value.verified_at))
})

async function search() {
  if (form.q.length < 2) return

  isSearching.value = true
  hasSearched.value = true
  errorMessage.value = ''
  pendingCompany.value = null

  try {
    const response = await customerStore.searchCompanyRegistry({
      q: form.q,
      postal_code: form.postal_code || undefined,
      city: form.city || undefined,
      exclude_customer_id: props.currentCustomerId || undefined,
    })
    results.value = response.data.data
    meta.value = response.data.meta
  } catch (error) {
    results.value = []
    errorMessage.value =
      error.response?.data?.message ||
      'Le service officiel ne répond pas pour le moment.'
  } finally {
    isSearching.value = false
  }
}

function applySelection() {
  emit('select', pendingCompany.value)
  pendingCompany.value = null
}
</script>
