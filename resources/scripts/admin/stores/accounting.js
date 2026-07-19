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
  },
})
