import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

export const useElectronicInvoicingStore = defineStore({
  id: 'electronic-invoicing',

  state: () => ({
    connection: null,
    company: null,
    links: {},
    security: {},
    isLoading: false,
    isSaving: false,
    lastTest: null,
  }),

  actions: {
    applyPayload(payload) {
      this.connection = payload.data
      this.company = payload.company
      this.links = payload.links || {}
      this.security = payload.security || {}
      this.lastTest = payload.test || null
    },

    async load() {
      this.isLoading = true

      try {
        const response = await axios.get('/api/v1/electronic-invoicing/connection')
        this.applyPayload(response.data)

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async updateProgress(data) {
      this.isSaving = true

      try {
        const response = await axios.post(
          '/api/v1/electronic-invoicing/connection/progress',
          data
        )
        this.applyPayload(response.data)

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    async saveCredentials(data) {
      this.isSaving = true

      try {
        const response = await axios.put(
          '/api/v1/electronic-invoicing/connection/credentials',
          data
        )
        this.applyPayload(response.data)

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    async testConnection() {
      this.isSaving = true

      try {
        const response = await axios.post(
          '/api/v1/electronic-invoicing/connection/test'
        )
        this.applyPayload(response.data)

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    async disconnect() {
      this.isSaving = true

      try {
        const response = await axios.delete(
          '/api/v1/electronic-invoicing/connection'
        )
        this.applyPayload(response.data)

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },
  },
})
