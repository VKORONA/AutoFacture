<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton v-if="route.name === 'estimates.view'" variant="primary">
        <BaseIcon name="DotsHorizontalIcon" class="text-white" />
      </BaseButton>
      <BaseIcon v-else class="text-gray-500" name="DotsHorizontalIcon" />
    </template>

    <BaseDropdownItem v-if="route.name === 'estimates.view'" @click="copyPdfUrl">
      <BaseIcon name="LinkIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />
      {{ $t('general.copy_pdf_url') }}
    </BaseDropdownItem>

    <router-link v-if="userStore.hasAbilities(abilities.EDIT_ESTIMATE)" :to="`/admin/estimates/${row.id}/edit`">
      <BaseDropdownItem><BaseIcon name="PencilIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />{{ $t('general.edit') }}</BaseDropdownItem>
    </router-link>

    <BaseDropdownItem v-if="userStore.hasAbilities(abilities.DELETE_ESTIMATE)" @click="removeEstimate(row.id)">
      <BaseIcon name="TrashIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />{{ $t('general.delete') }}
    </BaseDropdownItem>

    <router-link v-if="route.name !== 'estimates.view' && userStore.hasAbilities(abilities.VIEW_ESTIMATE)" :to="`/admin/estimates/${row.id}/view`">
      <BaseDropdownItem><BaseIcon name="EyeIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />{{ $t('general.view') }}</BaseDropdownItem>
    </router-link>

    <BaseDropdownItem v-if="userStore.hasAbilities(abilities.CREATE_ESTIMATE)" @click="duplicateEstimate(row)">
      <BaseIcon name="DocumentDuplicateIcon" class="w-5 h-5 mr-3 text-blue-500 group-hover:text-blue-600" />Dupliquer
    </BaseDropdownItem>

    <BaseDropdownItem v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)" @click="convertInToinvoice(row.id)">
      <BaseIcon name="DocumentTextIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />{{ $t('estimates.convert_to_invoice') }}
    </BaseDropdownItem>

    <BaseDropdownItem v-if="row.status !== 'SENT' && route.name !== 'estimates.view' && userStore.hasAbilities(abilities.SEND_ESTIMATE)" @click="onMarkAsSent(row.id)">
      <BaseIcon name="CheckCircleIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />{{ $t('estimates.mark_as_sent') }}
    </BaseDropdownItem>

    <BaseDropdownItem v-if="row.status !== 'SENT' && route.name !== 'estimates.view' && userStore.hasAbilities(abilities.SEND_ESTIMATE)" @click="sendEstimate(row)">
      <BaseIcon name="PaperAirplaneIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />{{ $t('estimates.send_estimate') }}
    </BaseDropdownItem>

    <BaseDropdownItem v-if="canResendEstimate(row)" @click="sendEstimate(row)">
      <BaseIcon name="PaperAirplaneIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />{{ $t('estimates.resend_estimate') }}
    </BaseDropdownItem>

    <BaseDropdownItem v-if="row.status !== 'ACCEPTED' && userStore.hasAbilities(abilities.EDIT_ESTIMATE)" @click="onMarkAsAccepted(row.id)">
      <BaseIcon name="CheckCircleIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />{{ $t('estimates.mark_as_accepted') }}
    </BaseDropdownItem>

    <BaseDropdownItem v-if="row.status !== 'REJECTED' && userStore.hasAbilities(abilities.EDIT_ESTIMATE)" @click="onMarkAsRejected(row.id)">
      <BaseIcon name="XCircleIcon" class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" />{{ $t('estimates.mark_as_rejected') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import axios from 'axios'
import { inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useEstimateStore } from '@/scripts/admin/stores/estimate'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import { handleError } from '@/scripts/helpers/error-handling'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotificationStore } from '@/scripts/stores/notification'

const props = defineProps({ row: { type: Object, default: null }, table: { type: Object, default: null } })
const utils = inject('utils')
const estimateStore = useEstimateStore()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()
const { t } = useI18n()
const route = useRoute()
const router = useRouter()

async function removeEstimate(id) {
  const confirmed = await dialogStore.openDialog({ title: t('general.are_you_sure'), message: t('estimates.confirm_delete'), yesLabel: t('general.ok'), noLabel: t('general.cancel'), variant: 'danger', hideNoButton: false, size: 'lg' })
  if (!confirmed) return
  const response = await estimateStore.deleteEstimate({ ids: [id] })
  if (!response) return
  props.table && props.table.refresh()
  if (response.data) router.push('/admin/estimates')
  estimateStore.$patch((state) => { state.selectedEstimates = []; state.selectAllField = false })
}

async function duplicateEstimate(estimate) {
  const confirmed = await dialogStore.openDialog({ title: 'Dupliquer ce devis ?', message: 'Un nouveau devis brouillon reprendra les lignes, prix, TVA, notes et modèle. Vous pourrez remplacer le client. Les photos et pièces jointes ne seront pas recopiées.', yesLabel: 'Dupliquer', noLabel: t('general.cancel'), variant: 'primary', hideNoButton: false, size: 'lg' })
  if (!confirmed) return
  try {
    const response = await axios.post(`/api/v1/estimates/${estimate.id}/clone`)
    router.push({ path: `/admin/estimates/${response.data.data.id}/edit`, query: { duplicated_from: estimate.id } })
  } catch (error) { handleError(error) }
}

async function convertInToinvoice(id) {
  const confirmed = await dialogStore.openDialog({ title: t('general.are_you_sure'), message: t('estimates.confirm_conversion'), yesLabel: t('general.ok'), noLabel: t('general.cancel'), variant: 'primary', hideNoButton: false, size: 'lg' })
  if (!confirmed) return
  const response = await estimateStore.convertToInvoice(id)
  if (response.data) router.push(`/admin/invoices/${response.data.data.id}/edit`)
}

async function onMarkAsSent(id) {
  const confirmed = await dialogStore.openDialog({ title: t('general.are_you_sure'), message: t('estimates.confirm_mark_as_sent'), yesLabel: t('general.ok'), noLabel: t('general.cancel'), variant: 'primary', hideNoButton: false, size: 'lg' })
  if (!confirmed) return
  await estimateStore.markAsSent({ id, status: 'SENT' })
  props.table && props.table.refresh()
}

function canResendEstimate(row) { return (row.status === 'SENT' || row.status === 'VIEWED') && route.name !== 'estimates.view' && userStore.hasAbilities(abilities.SEND_ESTIMATE) }
function sendEstimate(estimate) { modalStore.openModal({ title: t('estimates.send_estimate'), componentName: 'SendEstimateModal', id: estimate.id, data: estimate, variant: 'lg' }) }

async function onMarkAsAccepted(id) {
  const confirmed = await dialogStore.openDialog({ title: t('general.are_you_sure'), message: t('estimates.confirm_mark_as_accepted'), yesLabel: t('general.ok'), noLabel: t('general.cancel'), variant: 'primary', hideNoButton: false, size: 'lg' })
  if (!confirmed) return
  await estimateStore.markAsAccepted({ id, status: 'ACCEPTED' })
  props.table && props.table.refresh()
}

async function onMarkAsRejected(id) {
  const confirmed = await dialogStore.openDialog({ title: t('general.are_you_sure'), message: t('estimates.confirm_mark_as_rejected'), yesLabel: t('general.ok'), noLabel: t('general.cancel'), variant: 'primary', hideNoButton: false, size: 'lg' })
  if (!confirmed) return
  await estimateStore.markAsRejected({ id, status: 'REJECTED' })
  props.table && props.table.refresh()
}

function copyPdfUrl() {
  utils.copyTextToClipboard(`${window.location.origin}/estimates/pdf/${props.row.unique_hash}`)
  notificationStore.showNotification({ type: 'success', message: t('general.copied_pdf_url_clipboard') })
}
</script>
