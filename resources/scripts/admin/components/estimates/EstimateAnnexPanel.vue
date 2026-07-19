<template>
  <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start gap-3">
      <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
        <BaseIcon name="PaperClipIcon" class="h-5 w-5" />
      </span>
      <div>
        <h3 class="font-semibold text-slate-900">Annexe au devis</h3>
        <p class="mt-1 text-sm leading-6 text-slate-600">
          Ajoutez une présentation, des précisions techniques et des pièces jointes. Les photos de lignes peuvent être intégrées dans une annexe visuelle paginée.
        </p>
      </div>
    </div>

    <div class="mt-5 space-y-4">
      <label class="block">
        <span class="text-sm font-medium text-slate-800">Titre de l’annexe</span>
        <BaseInput
          v-model="estimate.annex_title"
          class="mt-2"
          placeholder="Ex. Détail photographique et périmètre de la mission"
        />
      </label>

      <label class="block">
        <span class="text-sm font-medium text-slate-800">Texte d’introduction</span>
        <textarea
          v-model="estimate.annex_notes"
          rows="5"
          maxlength="20000"
          class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500"
          placeholder="Décrivez le contenu de l’annexe, les limites de prestation ou les observations utiles."
        />
      </label>

      <label class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4">
        <input
          v-model="estimate.include_photo_annex"
          type="checkbox"
          class="mt-1 rounded border-blue-300 text-blue-600 focus:ring-blue-500"
        />
        <span>
          <span class="block text-sm font-semibold text-blue-900">Inclure les photos détaillées dans le PDF</span>
          <span class="mt-1 block text-xs leading-5 text-blue-700">
            Les images sont automatiquement placées deux par rangée avec des sauts de page maîtrisés.
          </span>
        </span>
      </label>

      <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="text-sm font-semibold text-slate-800">Pièces annexes</div>
            <div class="mt-1 text-xs text-slate-500">PDF ou image, 15 Mo maximum par fichier</div>
          </div>
          <label
            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
            :class="{ 'pointer-events-none opacity-50': assetStore.uploadingAttachment }"
          >
            <BaseIcon name="UploadIcon" class="h-4 w-4" />
            {{ assetStore.uploadingAttachment ? 'Import en cours…' : 'Ajouter une pièce' }}
            <input
              class="hidden"
              type="file"
              accept="application/pdf,image/jpeg,image/png,image/webp"
              @change="onAttachmentSelected"
            />
          </label>
        </div>

        <div v-if="(estimate.attachments || []).length" class="mt-4 space-y-2">
          <div
            v-for="attachment in estimate.attachments"
            :key="attachment.id"
            class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3"
          >
            <div class="flex min-w-0 items-center gap-3">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                <BaseIcon :name="isPdf(attachment) ? 'DocumentTextIcon' : 'PhotographIcon'" class="h-5 w-5" />
              </span>
              <div class="min-w-0">
                <a
                  :href="attachment.download_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="block truncate text-sm font-medium text-blue-700 hover:underline"
                >
                  {{ attachment.original_name }}
                </a>
                <div class="text-xs text-slate-500">{{ formatSize(attachment.size_bytes) }}</div>
              </div>
            </div>
            <button
              type="button"
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50"
              title="Supprimer la pièce jointe"
              @click="removeAttachment(attachment)"
            >
              <BaseIcon name="TrashIcon" class="h-4 w-4" />
            </button>
          </div>
        </div>

        <div v-else class="mt-4 text-xs leading-5 text-slate-500">
          Les PDF sont envoyés avec le devis comme pièces jointes séparées et répertoriés dans l’annexe. Les photos rattachées aux lignes sont intégrées directement au PDF.
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useEstimateAssetStore } from '@/scripts/admin/stores/estimate-assets'

const props = defineProps({
  estimate: {
    type: Object,
    required: true,
  },
})

const assetStore = useEstimateAssetStore()

async function onAttachmentSelected(event) {
  const input = event.target
  const file = input.files?.[0]
  input.value = ''

  if (!file) return

  const allowedTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp',
  ]

  if (!allowedTypes.includes(file.type)) {
    window.alert('Format refusé. Utilisez un PDF ou une image JPG, PNG ou WebP.')
    return
  }

  if (file.size > 15 * 1024 * 1024) {
    window.alert('La pièce jointe dépasse 15 Mo.')
    return
  }

  await assetStore.uploadAttachment(props.estimate, file)
}

async function removeAttachment(attachment) {
  const confirmed = window.confirm('Supprimer définitivement cette pièce annexe ?')
  if (!confirmed) return

  await assetStore.deleteAttachment(props.estimate, attachment)
}

function isPdf(attachment) {
  return attachment.mime_type === 'application/pdf'
}

function formatSize(bytes) {
  if (!bytes) return '0 Ko'
  if (bytes < 1024 * 1024) return `${Math.ceil(bytes / 1024)} Ko`
  return `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} Mo`
}
</script>
