import addressStub from '@/scripts/admin/stub/address.js'

export default function () {
  return {
    name: '',
    customer_type: 'business',
    company_name: '',
    contact_name: '',
    siren: '',
    siret: '',
    vat_number: '',
    ape_code: '',
    email: '',
    electronic_invoicing_email: '',
    phone: null,
    password: '',
    confirm_password: '',
    currency_id: null,
    website: null,
    prefix: '',
    billing: { ...addressStub },
    shipping: { ...addressStub },
    customFields: [],
    fields: [],
    enable_portal: false,
  }
}
