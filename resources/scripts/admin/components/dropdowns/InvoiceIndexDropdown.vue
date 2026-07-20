<template>
  <CreateCreditNoteModal
    v-if="route.name === 'invoices.view'"
    @created="onCreditNoteCreated"
  />

  <BaseDropdown>
    <template #activator>
      <BaseButton v-if="route.name === 'invoices.view'" variant="primary">
        <BaseIcon name="DotsHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="DotsHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_INVOICE)"
      :to="`/admin/invoices/${row.id}/edit`"
    >
      <BaseDropdownItem v-show="row.allow_edit">
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <BaseDropdownItem v-if="route.name === 'invoices.view'" @click="copyPdfUrl">
      <BaseIcon
        name="LinkIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.copy_pdf_url') }}
    </BaseDropdownItem>

    <router-link
      v-if="
        route.name !== 'invoices.view' &&
        userStore.hasAbilities(abilities.VIEW_INVOICE)
      "
      :to="`/admin/invoices/${row.id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.view') }}
      </BaseDropdownItem>
    </router-link>

    <BaseDropdownItem
      v-if="canCreateCreditNote(row)"
      @click="openCreditNoteModal(row)"
    >
      <BaseIcon
        name="DocumentDuplicateIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      Créer un avoir
    </BaseDropdownItem>

    <BaseDropdownItem v-if="canSendInvoice(row)" @click="sendInvoice(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.send_invoice') }}
    </BaseDropdownItem>

    <BaseDropdownItem v-if="canReSendInvoice(row)" @click="sendInvoice(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.resend_invoice') }}
    </BaseDropdownItem>

    <router-link :to="`/admin/payments/${row.id}/create`">
      <BaseDropdownItem
        v-if="
          row.status === 'SENT' &&
          row.due_amount > 0 &&
          route.name !== 'invoices.view'
        "
      >
        <BaseIcon
          name="CreditCardIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('invoices.record_payment') }}
      </BaseDropdownItem>
    </router-link>

    <BaseDropdownItem v-if="canSendInvoice(row)" @click="onMarkAsSent(row.id)">
      <BaseIcon
        name="CheckCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.mark_as_sent') }}
    </BaseDropdownItem>

    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
      @click="duplicateInvoice(row)"
    >
      <BaseIcon
        name="DocumentDuplicateIcon"
        class="w-5 h-5 mr-3 text-blue-500 group-hover:text-blue-600"
      />
      Dupliquer
    </BaseDropdownItem>

    <BaseDropdownItem
      v-if="
        !row.is_finalized && userStore.hasAbilities(abilities.DELETE_INVOICE)
      "
      @click="removeInvoice(row.id)"
    >
      <BaseIcon
        name="TrashIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.delete') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import { inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

import CreateCreditNoteModal from '@/scripts/admin/components/modal-components/CreateCreditNoteModal.vue'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotificationStore } from '@/scripts/stores/notification'

const props = defineProps({
  row: {
    type: Object,
    default: null,
  },
  table: {
    type: Object,
    default: null,
  },
  loadData: {
    type: Function,
    default: () => {},
  },
})

const invoiceStore = useInvoiceStore()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const utils = inject('utils')

function canCreateCreditNote(row) {
  return (
    route.name === 'invoices.view' &&
    row.status !== 'DRAFT' &&
    Number(row.creditable_amount || 0) > 0 &&
    userStore.hasAbilities(abilities.CREATE_INVOICE)
  )
}

function canReSendInvoice(row) {
  return (
    (row.status === 'SENT' || row.status === 'VIEWED') &&
    userStore.hasAbilities(abilities.SEND_INVOICE)
  )
}

function canSendInvoice(row) {
  return (
    row.status === 'DRAFT' &&
    route.name !== 'invoices.view' &&
    userStore.hasAbilities(abilities.SEND_INVOICE)
  )
}

function openCreditNoteModal(invoice) {
  modalStore.openModal({
    title: 'Créer un avoir',
    componentName: 'CreateCreditNoteModal',
    id: invoice.id,
    data: invoice,
    variant: 'md',
  })
}

function onCreditNoteCreated(creditNote) {
  router.push(`/admin/credit-notes/${creditNote.id}/view`)
}

async function removeInvoice(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        invoiceStore.deleteInvoice({ ids: [id] }).then((response) => {
          if (response.data.success) {
            router.push('/admin/invoices')
            props.table && props.table.refresh()

            invoiceStore.$patch((state) => {
              state.selectedInvoices = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

async function duplicateInvoice(data) {
  dialogStore
    .openDialog({
      title: 'Dupliquer cette facture ?',
      message:
        'Une nouvelle facture brouillon reprendra les lignes, prix, TVA, notes et modèle. Vous pourrez remplacer le client avant de l’enregistrer définitivement.',
      yesLabel: 'Dupliquer',
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        invoiceStore.cloneInvoice(data).then((response) => {
          router.push({
            path: `/admin/invoices/${response.data.data.id}/edit`,
            query: { duplicated_from: data.id },
          })
        })
      }
    })
}

async function onMarkAsSent(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.invoice_mark_as_sent'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((response) => {
      if (response) {
        invoiceStore.markAsSent({ id, status: 'SENT' }).then(() => {
          props.table && props.table.refresh()
        })
      }
    })
}

async function sendInvoice(invoice) {
  modalStore.openModal({
    title: t('invoices.send_invoice'),
    componentName: 'SendInvoiceModal',
    id: invoice.id,
    data: invoice,
    variant: 'sm',
  })
}

function copyPdfUrl() {
  const pdfUrl = `${window.location.origin}/invoices/pdf/${props.row.unique_hash}`
  utils.copyTextToClipboard(pdfUrl)

  notificationStore.showNotification({
    type: 'success',
    message: t('general.copied_pdf_url_clipboard'),
  })
}
</script>
