import axios from 'axios'
import { defineStore } from 'pinia'
import { handleError } from '@/scripts/helpers/error-handling'

export const useDashboardStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'dashboard',

    state: () => ({
      stats: {
        totalAmountDue: 0,
        totalCustomerCount: 0,
        totalInvoiceCount: 0,
        totalEstimateCount: 0,
        pendingInvoiceCount: 0,
        pendingEstimateCount: 0,
        pendingEstimateAmount: 0,
      },

      fiscalPeriod: {
        label: '',
        start: '',
        end: '',
      },

      chartData: {
        months: [],
        invoiceTotals: [],
        previousInvoiceTotals: [],
        expenseTotals: [],
        receiptTotals: [],
        netIncomeTotals: [],
      },

      invoiceDistribution: {
        paid: 0,
        pending: 0,
        overdue: 0,
        creditNotes: 0,
      },

      totalSales: 0,
      totalSalesHt: 0,
      previousSalesHt: 0,
      salesGrowthPercent: 0,
      totalReceipts: 0,
      previousReceipts: 0,
      receiptsGrowthPercent: 0,
      totalExpenses: 0,
      totalNetIncome: 0,

      recentDueInvoices: [],
      recentEstimates: [],

      isDashboardDataLoaded: false,
    }),

    actions: {
      loadData(params) {
        this.isDashboardDataLoaded = false

        return new Promise((resolve, reject) => {
          axios
            .get('/api/v1/dashboard', { params })
            .then((response) => {
              const data = response.data

              this.stats.totalAmountDue = data.total_amount_due
              this.stats.totalCustomerCount = data.total_customer_count
              this.stats.totalInvoiceCount = data.total_invoice_count
              this.stats.totalEstimateCount = data.total_estimate_count
              this.stats.pendingInvoiceCount = data.pending_invoice_count
              this.stats.pendingEstimateCount = data.pending_estimate_count
              this.stats.pendingEstimateAmount = data.pending_estimate_amount

              this.fiscalPeriod = data.fiscal_period || this.fiscalPeriod

              if (data.chart_data) {
                this.chartData.months = data.chart_data.months || []
                this.chartData.invoiceTotals = data.chart_data.invoice_totals || []
                this.chartData.previousInvoiceTotals =
                  data.chart_data.previous_invoice_totals || []
                this.chartData.expenseTotals = data.chart_data.expense_totals || []
                this.chartData.receiptTotals = data.chart_data.receipt_totals || []
                this.chartData.netIncomeTotals =
                  data.chart_data.net_income_totals || []
              }

              const distribution = data.invoice_distribution || {}
              this.invoiceDistribution.paid = distribution.paid || 0
              this.invoiceDistribution.pending = distribution.pending || 0
              this.invoiceDistribution.overdue = distribution.overdue || 0
              this.invoiceDistribution.creditNotes = distribution.credit_notes || 0

              this.totalSales = data.total_sales || 0
              this.totalSalesHt = data.total_sales_ht || data.total_sales || 0
              this.previousSalesHt = data.previous_sales_ht || 0
              this.salesGrowthPercent = data.sales_growth_percent || 0
              this.totalReceipts = data.total_receipts || 0
              this.previousReceipts = data.previous_receipts || 0
              this.receiptsGrowthPercent = data.receipts_growth_percent || 0
              this.totalExpenses = data.total_expenses || 0
              this.totalNetIncome = data.total_net_income || 0

              this.recentDueInvoices = data.recent_due_invoices || []
              this.recentEstimates = data.recent_estimates || []

              this.isDashboardDataLoaded = true
              resolve(response)
            })
            .catch((err) => {
              this.isDashboardDataLoaded = true
              handleError(err)
              reject(err)
            })
        })
      },
    },
  })()
}
