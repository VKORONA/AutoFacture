<template>
  <div class="premium-chart-wrap">
    <canvas ref="canvas" />
  </div>
</template>

<script setup>
import Chart from 'chart.js'
import { computed, inject, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const props = defineProps({
  labels: { type: Array, default: () => [] },
  currentRevenue: { type: Array, default: () => [] },
  previousRevenue: { type: Array, default: () => [] },
  receipts: { type: Array, default: () => [] },
})

const canvas = ref(null)
const companyStore = useCompanyStore()
const utils = inject('utils')
let chart = null

const currency = computed(() => companyStore.selectedCompanyCurrency)

function euroValue(value) {
  return utils.formatMoney(Math.round(Number(value || 0) * 100), currency.value)
}

function datasets() {
  return [
    {
      type: 'bar',
      label: 'Paiements encaissés',
      data: props.receipts.map((value) => Number(value || 0) / 100),
      backgroundColor: 'rgba(96, 165, 250, 0.28)',
      borderColor: 'rgba(96, 165, 250, 0.45)',
      borderWidth: 1,
      barPercentage: 0.56,
      categoryPercentage: 0.7,
      order: 3,
    },
    {
      type: 'line',
      label: 'CA HT N',
      data: props.currentRevenue.map((value) => Number(value || 0) / 100),
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37, 99, 235, 0.08)',
      pointBackgroundColor: '#ffffff',
      pointBorderColor: '#2563eb',
      pointBorderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 5,
      borderWidth: 3,
      lineTension: 0.35,
      fill: false,
      order: 1,
    },
    {
      type: 'line',
      label: 'CA HT N-1',
      data: props.previousRevenue.map((value) => Number(value || 0) / 100),
      borderColor: '#7c3aed',
      backgroundColor: 'transparent',
      pointRadius: 0,
      pointHoverRadius: 4,
      borderWidth: 2,
      borderDash: [8, 7],
      lineTension: 0.35,
      fill: false,
      order: 2,
    },
  ]
}

function createChart() {
  if (!canvas.value) return

  chart = new Chart(canvas.value.getContext('2d'), {
    type: 'bar',
    data: {
      labels: props.labels,
      datasets: datasets(),
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 500 },
      legend: {
        display: true,
        position: 'top',
        align: 'start',
        labels: {
          usePointStyle: true,
          boxWidth: 8,
          fontColor: '#64748b',
          padding: 22,
        },
      },
      scales: {
        xAxes: [
          {
            gridLines: { display: false },
            ticks: { fontColor: '#64748b', fontSize: 11 },
          },
        ],
        yAxes: [
          {
            gridLines: { color: 'rgba(148, 163, 184, 0.16)', zeroLineColor: 'rgba(148, 163, 184, 0.22)' },
            ticks: {
              beginAtZero: true,
              fontColor: '#64748b',
              fontSize: 11,
              callback(value) {
                if (value >= 1000) return `${Math.round(value / 1000)}K €`
                return `${value} €`
              },
            },
          },
        ],
      },
      tooltips: {
        mode: 'index',
        intersect: false,
        backgroundColor: '#ffffff',
        titleFontColor: '#0f172a',
        bodyFontColor: '#334155',
        borderColor: 'rgba(148, 163, 184, 0.28)',
        borderWidth: 1,
        cornerRadius: 12,
        xPadding: 14,
        yPadding: 12,
        callbacks: {
          label(tooltipItem, data) {
            const label = data.datasets[tooltipItem.datasetIndex].label || ''
            return `${label} : ${euroValue(tooltipItem.yLabel)}`
          },
        },
      },
    },
  })
}

function updateChart() {
  if (!chart) return
  chart.data.labels = props.labels
  chart.data.datasets = datasets()
  chart.update()
}

onMounted(createChart)
onBeforeUnmount(() => chart?.destroy())

watch(
  () => [props.labels, props.currentRevenue, props.previousRevenue, props.receipts],
  updateChart,
  { deep: true }
)
</script>

<style scoped>
.premium-chart-wrap {
  position: relative;
  width: 100%;
  height: 315px;
}
</style>
