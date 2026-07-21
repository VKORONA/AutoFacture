import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'

export const useMicroEntrepreneurStore = defineStore('micro-entrepreneur', {
  state: () => ({
    isLoading: false,
    isSaving: false,
    isExporting: false,
    report: null,
  }),

  actions: {
    async load(params = {}) {
      this.isLoading = true
      try {
        const response = await axios.get('/api/v1/micro-entrepreneur', { params })
        this.report = response.data
        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async saveSettings(settings, params = {}) {
      this.isSaving = true
      try {
        const response = await axios.put('/api/v1/micro-entrepreneur/settings', settings)
        await this.load(params)
        useNotificationStore().showNotification({
          type: 'success',
          message: 'Paramètres micro-entrepreneur enregistrés.',
        })
        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    async addAdjustment(payload, params = {}) {
      try {
        const response = await axios.post('/api/v1/micro-entrepreneur/adjustments', payload)
        await this.load(params)
        useNotificationStore().showNotification({
          type: 'success',
          message: 'Ajustement de chiffre d’affaires ajouté.',
        })
        return response
      } catch (error) {
        handleError(error)
        throw error
      }
    },

    async removeAdjustment(id, params = {}) {
      try {
        const response = await axios.delete(`/api/v1/micro-entrepreneur/adjustments/${id}`)
        await this.load(params)
        return response
      } catch (error) {
        handleError(error)
        throw error
      }
    },

    async exportCsv(year) {
      this.isExporting = true
      try {
        const response = await axios.get('/api/v1/micro-entrepreneur/export', {
          params: { year },
          responseType: 'blob',
        })
        const url = window.URL.createObjectURL(response.data)
        const link = document.createElement('a')
        link.href = url
        link.download = `declaration-micro-entrepreneur-${year}.csv`
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isExporting = false
      }
    },
  },
})
