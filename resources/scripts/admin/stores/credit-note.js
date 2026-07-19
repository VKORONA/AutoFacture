import axios from 'axios'
import { defineStore } from 'pinia'

import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'

export const useCreditNoteStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const notificationStore = useNotificationStore()

  return defineStoreFunc({
    id: 'credit-note',

    state: () => ({
      creditNotes: [],
      currentCreditNote: null,
      creditNoteTotalCount: 0,
      isLoading: false,
    }),

    actions: {
      fetchCreditNotes(params = {}) {
        this.isLoading = true

        return axios
          .get('/api/v1/credit-notes', { params })
          .then((response) => {
            this.creditNotes = response.data.data
            this.creditNoteTotalCount = response.data.meta.total

            return response
          })
          .catch((error) => {
            handleError(error)
            throw error
          })
          .finally(() => {
            this.isLoading = false
          })
      },

      fetchCreditNote(id) {
        this.isLoading = true

        return axios
          .get(`/api/v1/credit-notes/${id}`)
          .then((response) => {
            this.currentCreditNote = response.data.data

            return response
          })
          .catch((error) => {
            handleError(error)
            throw error
          })
          .finally(() => {
            this.isLoading = false
          })
      },

      createCreditNote(invoiceId, data) {
        return axios
          .post(`/api/v1/invoices/${invoiceId}/credit-notes`, data)
          .then((response) => {
            this.creditNotes = [response.data.data, ...this.creditNotes]
            this.creditNoteTotalCount += 1

            notificationStore.showNotification({
              type: 'success',
              message: `L’avoir ${response.data.data.credit_note_number} a été émis et scellé.`,
            })

            return response
          })
          .catch((error) => {
            handleError(error)
            throw error
          })
      },

      resetCurrentCreditNote() {
        this.currentCreditNote = null
      },
    },
  })()
}
