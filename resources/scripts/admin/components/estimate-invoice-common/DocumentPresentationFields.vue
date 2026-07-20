<template>
  <section class="mt-6 rounded-3xl border border-blue-100 bg-white p-5 shadow-sm sm:p-6">
    <div class="flex items-start gap-3">
      <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
        <BaseIcon name="PhotographIcon" class="h-6 w-6" />
      </span>
      <div>
        <h3 class="text-base font-semibold text-slate-950">Présentation Premium du document</h3>
        <p class="mt-1 text-sm leading-6 text-slate-600">
          Ces informations alimentent les blocs « Référence / projet », « Chantier » et
          « Bon de commande / interlocuteur » du modèle Premium AutoFacture.
        </p>
      </div>
    </div>

    <BaseInputGrid class="mt-5">
      <BaseInputGroup label="Nom du projet ou de la mission" :content-loading="isLoading">
        <BaseInput
          v-model.trim="document.project_name"
          :content-loading="isLoading"
          maxlength="255"
          placeholder="Ex. Contrôle technique – Résidence Les Alizés"
        />
      </BaseInputGroup>

      <BaseInputGroup label="Adresse du chantier ou lieu d’intervention" :content-loading="isLoading">
        <BaseInput
          v-model.trim="document.project_address"
          :content-loading="isLoading"
          maxlength="500"
          placeholder="Ex. 8 rue des Mouettes, 17000 La Rochelle"
        />
      </BaseInputGroup>

      <BaseInputGroup label="Numéro de bon de commande" :content-loading="isLoading">
        <BaseInput
          v-model.trim="document.purchase_order_number"
          :content-loading="isLoading"
          maxlength="100"
          placeholder="Ex. BC-2026-019"
        />
      </BaseInputGroup>

      <BaseInputGroup label="Interlocuteur du projet" :content-loading="isLoading">
        <BaseInput
          v-model.trim="document.project_contact"
          :content-loading="isLoading"
          maxlength="255"
          placeholder="Ex. M. Julien Martin"
        />
      </BaseInputGroup>

      <BaseInputGroup label="Conditions de paiement affichées" :content-loading="isLoading">
        <BaseInput
          v-model.trim="document.payment_terms_label"
          :content-loading="isLoading"
          maxlength="255"
          placeholder="Ex. 30 jours fin de mois"
        />
      </BaseInputGroup>

      <div class="flex items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        <BaseCheckbox v-model="document.show_sepa_qr" />
        <div class="ml-3">
          <div class="text-sm font-semibold text-slate-900">Afficher le QR code SEPA</div>
          <p class="mt-1 text-xs leading-5 text-slate-500">
            Le QR code reprend les coordonnées bancaires de l’entreprise. Sur une
            facture, le montant restant dû et la référence sont également préremplis.
          </p>
        </div>
      </div>
    </BaseInputGrid>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  store: {
    type: Object,
    required: true,
  },
  storeProp: {
    type: String,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
})

const document = computed(() => props.store[props.storeProp])
</script>
