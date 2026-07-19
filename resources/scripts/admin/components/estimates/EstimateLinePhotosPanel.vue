<template>
  <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
            <BaseIcon name="PhotographIcon" class="h-5 w-5" />
          </span>
          <div>
            <h3 class="font-semibold text-slate-900">Photos par ligne de devis</h3>
            <p class="text-xs text-slate-500">Jusqu’à 4 photos par ligne</p>
          </div>
        </div>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
          Chaque image est automatiquement redressée, compressée et placée dans un cadre 4:3.
          Une miniature uniforme apparaît dans le devis et les photos détaillées sont ajoutées à l’annexe.
        </p>
      </div>
      <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
        Mise en page protégée
      </span>
    </div>

    <div class="mt-5 space-y-4">
      <article
        v-for="(item, index) in estimate.items"
        :key="item.line_uuid || item.id"
        class="rounded-xl border border-slate-200 bg-slate-50 p-4"
      >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="min-w-0">
            <div class="text-xs font-semibold uppercase tracking-wide text-blue-600">
              Ligne {{ index + 1 }}
            </div>
            <div class="truncate font-medium text-slate-900">
              {{ item.name || 'Prestation à renseigner' }}
            </div>
          </div>

          <label
            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-50"
            :class="{
              'pointer-events-none opacity-50':
                (item.line_photos || []).length >= 4 ||
                assetStore.uploadingLineUuid === item.line_uuid,
            }"
          >
            <BaseIcon name="CameraIcon" class="h-4 w-4" />
            {{ assetStore.uploadingLineUuid === item.line_uuid ? 'Traitement…' : 'Ajouter une photo' }}
            <input
              class="hidden"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              @change="onPhotoSelected($event, item)"
            />
          </label>
        </div>

        <div v-if="(item.line_photos || []).length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <figure
            v-for="photo in item.line_photos"
            :key="photo.id"
            class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white"
          >
            <div class="aspect-[4/3] bg-slate-100">
              <img
                :src="photo.thumbnail_url"
                :alt="photo.original_name"
                class="h-full w-full object-cover"
              />
            </div>
            <figcaption class="truncate px-2 py-2 text-[11px] text-slate-500">
              {{ photo.original_name }}
            </figcaption>
            <button
              type="button"
              class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white/95 text-red-600 opacity-0 shadow transition group-hover:opacity-100"
              title="Supprimer la photo"
              @click="removePhoto(item, photo)"
            >
              <BaseIcon name="TrashIcon" class="h-4 w-4" />
            </button>
          </figure>
        </div>

        <div v-else class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-center text-xs text-slate-500">
          Aucune photo pour cette ligne. Le devis restera compact.
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { watchEffect } from 'vue'
import Guid from 'guid'
import { useEstimateAssetStore } from '@/scripts/admin/stores/estimate-assets'

const props = defineProps({
  estimate: {
    type: Object,
    required: true,
  },
})

const assetStore = useEstimateAssetStore()

watchEffect(() => {
  props.estimate.items.forEach((item) => {
    if (!item.line_uuid) item.line_uuid = Guid.raw()
    if (!Array.isArray(item.line_photos)) item.line_photos = []
  })
})

async function onPhotoSelected(event, item) {
  const input = event.target
  const file = input.files?.[0]
  input.value = ''

  if (!file) return

  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    window.alert('Format refusé. Utilisez une image JPG, PNG ou WebP.')
    return
  }

  if (file.size > 12 * 1024 * 1024) {
    window.alert('La photo dépasse 12 Mo. Réduisez sa taille avant l’import.')
    return
  }

  if ((item.line_photos || []).length >= 4) {
    window.alert('Quatre photos maximum sont autorisées par ligne.')
    return
  }

  await assetStore.uploadLinePhoto(props.estimate, item, file)
}

async function removePhoto(item, photo) {
  const confirmed = window.confirm('Supprimer cette photo du devis et de son annexe ?')
  if (!confirmed) return

  await assetStore.deleteLinePhoto(item, photo)
}
</script>
