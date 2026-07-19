import Guid from 'guid'
import estimateItemStub from './estimate-item'
import taxStub from './tax'

export default function () {
  return {
    id: null,
    asset_draft_token: Guid.raw(),
    customer: null,
    template_name: '',
    tax_per_item: null,
    sales_tax_type: null,
    sales_tax_address_type: null,
    discount_per_item: null,
    estimate_date: '',
    expiry_date: '',
    estimate_number: '',
    customer_id: null,
    sub_total: 0,
    total: 0,
    tax: 0,
    notes: '',
    annex_title: '',
    annex_notes: '',
    include_photo_annex: true,
    attachments: [],
    discount_type: 'fixed',
    discount_val: 0,
    reference_number: null,
    discount: 0,
    items: [
      {
        ...estimateItemStub,
        id: Guid.raw(),
        line_uuid: Guid.raw(),
        line_photos: [],
        taxes: [{ ...taxStub, id: Guid.raw() }],
      },
    ],
    taxes: [],
    customFields: [],
    fields: [],
    selectedNote: null,
    selectedCurrency: '',
  }
}
