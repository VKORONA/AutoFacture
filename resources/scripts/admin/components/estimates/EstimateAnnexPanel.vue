<template>
  <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start gap-3">
      <span
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700"
      >
        <BaseIcon name="PaperClipIcon" class="h-5 w-5" />
      </span>
      <div class="min-w-0 flex-1">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h3 class="font-semibold text-slate-900">Annexe au devis</h3>
            <p class="mt-1 text-sm leading-6 text-slate-600">
              Ajoutez une présentation, des précisions techniques et des pièces
              jointes. Les photos de lignes peuvent être intégrées dans une
              annexe visuelle paginée.
            </p>
          </div>
          <BaseButton
            type="button"
            variant="primary-outline"
            class="shrink-0"
            :disabled="assetStore.uploadingAttachment"
            @click="openFilePicker"
          >
            <template #left="slotProps">
              <BaseIcon name="PaperClipIcon" :class="slotProps.class" />
            </template>
            {{ assetStore.uploadingAttachment ? 'Import en cours…' : 'Ajouter une annexe' }}
          </BaseButton>
        </div>
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
          <span class="block text-sm font-semibold text-blue-900">
            Inclure les photos détaillées dans le PDF
          </span>
          <span class="mt-1 block text-xs leading-5 text-blue-700">
            Les images sont automatiquement placées deux par rangée avec des
            sauts de page maîtrisés.
          </span>
        </span>
      </label>

      <div
        class="relative rounded-2xl border-2 border-dashed p-6 text-center transition"
        :class="[
          isDragging
            ? 'border-blue-500 bg-blue-50 shadow-inner'
            : 'border-slate-300 bg-slate-50 hover:border-blue-400 hover:bg-blue-50/50',
          assetStore.uploadingAttachment ? 'pointer-events-none opacity-60' : 'cursor-pointer',
        ]"
        role="button"
        tabindex="0"
        aria-label="Ajouter des pièces annexes au devis"
        @click="openFilePicker"
        @keydown.enter.prevent="openFilePicker"
        @keydown.space.prevent="openFilePicker"
        @dragenter.prevent="isDragging = true"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="onDragLeave"
        @drop.prevent="onDrop"
      >
        <input
          ref="fileInput"
          class="hidden"
          type="file"
          multiple
          accept="application/pdf,image/jpeg,image/png,image/webp"
          @change="onAttachmentSelected"
        />

        <span
          class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-slate-200"
        >
          <BaseIcon name="UploadIcon" class="h-6 w-6" />
        </span>
        <div class="mt-3 text-sm font-semibold text-slate-900">
          {{
            assetStore.uploadingAttachment
              ? 'Import des annexes en cours…'
              : isDragging
                ? 'Déposez les fichiers ici'
                : 'Glissez vos annexes ici ou cliquez pour parcourir'
          }}
        </div>
        <div class="mt-1 text-xs leading-5 text-slate-500">
          PDF, JPG, PNG ou WebP — 15 Mo maximum par fichier. Plusieurs fichiers
          peuvent être déposés en une seule fois.
        </div>
      </div>

      <div v-if="(estimate.attachments || []).length" class="space-y-2">
        <div
          v-for="attachment in estimate.attachments"
          :key="attachment.id"
          class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3"
        >
          <div class="flex min-w-0 items-center gap-3">
            <span
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600"
            >
              <BaseIcon
                :name="isPdf(attachment) ? 'DocumentTextIcon' : 'PhotographIcon'"
                class="h-5 w-5"
              />
            </span>
            <div class="min-w-0">
              <a
                :href="attachment.download_url"
                target="_blank"
                rel="noopener noreferrer"
                class="block truncate text-sm font-medium text-blue-700 hover:underline"
                @click.stop
              >
                {{ attachment.original_name }}
              </a>
              <div class="text-xs text-slate-500">
                {{ formatSize(attachment.size_bytes) }}
              </div>
            </div>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50"
            title="Supprimer la pièce jointe"
            @click.stop="removeAttachment(attachment)"
          >
            <BaseIcon name="TrashIcon" class="h-4 w-4" />
          </button>
        </div>
      </div>

      <div v-else class="text-xs leading-5 text-slate-500">
        Les PDF sont envoyés avec le devis comme pièces jointes séparées et
        répertoriés dans l’annexe. Les photos rattachées aux lignes sont intégrées
        directement au PDF.
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useEstimateAssetStore } from '@/scripts/admin/stores/estimate-assets'

const props = defineProps({
  estimate: {
    type: Object,
    required: true,
  },
})

const assetStore = useEstimateAssetStore()
const fileInput = ref(null)
const isDragging = ref(false)

const allowedTypes = [
  'application/pdf',
  'image/jpeg',
  'image/png',
  'image/webp',
]

function openFilePicker() {
  if (!assetStore.uploadingAttachment) {
    fileInput.value?.click()
  }
}

async function onAttachmentSelected(event) {
  const input = event.target
  const files = Array.from(input.files || [])
  input.value = ''
  await uploadFiles(files)
}

async function onDrop(event) {
  isDragging.value = false
  await uploadFiles(Array.from(event.dataTransfer?.files || []))
}

function onDragLeave(event) {
  if (!event.currentTarget.contains(event.relatedTarget)) {
    isDragging.value = false
  }
}

async function uploadFiles(files) {
  if (!files.length) return

  const errors = []

  for (const file of files) {
    const error = validateFile(file)

    if (error) {
      errors.push(`${file.name} : ${error}`)
      continue
    }

    try {
      await assetStore.uploadAttachment(props.estimate, file)
    } catch (error) {
      errors.push(`${file.name} : l’import a échoué.`)
    }
  }

  if (errors.length) {
    window.alert(errors.join('\n'))
  }
}

function validateFile(file) {
  if (!allowedTypes.includes(file.type)) {
    return 'format refusé. Utilisez un PDF ou une image JPG, PNG ou WebP.'
  }

  if (file.size > 15 * 1024 * 1024) {
    return 'le fichier dépasse 15 Mo.'
  }

  return null
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
