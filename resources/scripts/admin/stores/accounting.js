import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

export const useAccountingStore = defineStore({
  id: 'accounting',

  state: () => ({
    settings: null,
    profiles: {},
    exports: [],
    isLoading: false,
    isSaving: false,
    isGenerating: false,
    downloadingId: null,
  }),

  actions: {
    async load() {
      this.isLoading = true

      try {
        const response = await axios.get('/api/v1/accounting')
        this.settings = response.data.data.settings
        this.profiles = response.data.data.profiles
        this.exports = response.data.data.exports

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async saveSettings(payload) {
      this.isSaving = true

      try {
        const response = await axios.put('/api/v1/accounting/settings', payload)
        this.settings = response.data.data

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    async generateExport(payload) {
      this.isGenerating = true

      try {
        const response = await axios.post('/api/v1/accounting/exports', payload)
        this.exports = [response.data.data, ...this.exports]

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isGenerating = false
      }
    },

    async downloadExport(batch) {
      this.downloadingId = batch.id

      try {
        const response = await axios.get(batch.download_url, {
          responseType: 'blob',
        })
        const url = URL.createObjectURL(response.data)
        const link = document.createElement('a')
        link.href = url
        link.download = `AutoFacture-export-comptable-${batch.period_start}-${batch.period_end}.zip`
        document.body.appendChild(link)
        link.click()
        link.remove()
        URL.revokeObjectURL(url)

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.downloadingId = null
      }
    },
  },
})
