import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

export const useEstimateAssetStore = defineStore({
  id: 'estimate-assets',

  state: () => ({
    uploadingLineUuid: null,
    uploadingAttachment: false,
  }),

  actions: {
    async uploadLinePhoto(estimate, item, file) {
      this.uploadingLineUuid = item.line_uuid

      const formData = new FormData()
      formData.append('photo', file)
      formData.append('line_uuid', item.line_uuid)
      formData.append('draft_token', estimate.asset_draft_token)

      if (estimate.id) {
        formData.append('estimate_id', estimate.id)
      }

      try {
        const response = await axios.post(
          '/api/v1/estimate-assets/photos',
          formData
        )

        item.line_photos = [...(item.line_photos || []), response.data.data]

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.uploadingLineUuid = null
      }
    },

    async deleteLinePhoto(item, photo) {
      try {
        const response = await axios.delete(
          `/api/v1/estimate-assets/photos/${photo.id}`
        )

        item.line_photos = (item.line_photos || []).filter(
          (current) => current.id !== photo.id
        )

        return response
      } catch (error) {
        handleError(error)
        throw error
      }
    },

    async uploadAttachment(estimate, file) {
      this.uploadingAttachment = true

      const formData = new FormData()
      formData.append('attachment', file)
      formData.append('draft_token', estimate.asset_draft_token)

      if (estimate.id) {
        formData.append('estimate_id', estimate.id)
      }

      try {
        const response = await axios.post(
          '/api/v1/estimate-assets/attachments',
          formData
        )

        estimate.attachments = [
          ...(estimate.attachments || []),
          response.data.data,
        ]

        return response
      } catch (error) {
        handleError(error)
        throw error
      } finally {
        this.uploadingAttachment = false
      }
    },

    async deleteAttachment(estimate, attachment) {
      try {
        const response = await axios.delete(
          `/api/v1/estimate-assets/attachments/${attachment.id}`
        )

        estimate.attachments = (estimate.attachments || []).filter(
          (current) => current.id !== attachment.id
        )

        return response
      } catch (error) {
        handleError(error)
        throw error
      }
    },
  },
})
