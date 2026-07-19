import { useCompanyStore } from '../stores/company'

function defaultTax() {
  try {
    const settings = useCompanyStore().selectedCompanySettings || {}
    const taxTypeId = Number(settings.autofacture_default_tax_type_id || 0)
    const percent = Number(settings.autofacture_default_tax_percent || 0)

    if (!taxTypeId || !percent || settings.tax_per_item !== 'YES') {
      return {
        name: '',
        tax_type_id: 0,
        percent: null,
      }
    }

    return {
      name: `TVA ${String(percent).replace('.', ',')} %`,
      tax_type_id: taxTypeId,
      percent,
    }
  } catch {
    return {
      name: '',
      tax_type_id: 0,
      percent: null,
    }
  }
}

export default {
  get name() {
    return defaultTax().name
  },
  get tax_type_id() {
    return defaultTax().tax_type_id
  },
  type: 'GENERAL',
  amount: null,
  get percent() {
    return defaultTax().percent
  },
  compound_tax: false,
}
