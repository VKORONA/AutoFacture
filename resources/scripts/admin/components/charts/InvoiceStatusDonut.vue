<template>
  <div class="status-chart-wrap">
    <canvas ref="canvas" />
    <div class="status-chart-center">
      <strong>{{ total }}</strong>
      <span>documents</span>
    </div>
  </div>
</template>

<script setup>
import Chart from 'chart.js'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  paid: { type: Number, default: 0 },
  pending: { type: Number, default: 0 },
  overdue: { type: Number, default: 0 },
  creditNotes: { type: Number, default: 0 },
})

const canvas = ref(null)
let chart = null

const values = computed(() => [props.paid, props.pending, props.overdue, props.creditNotes])
const total = computed(() => values.value.reduce((sum, value) => sum + Number(value || 0), 0))

function createChart() {
  if (!canvas.value) return

  chart = new Chart(canvas.value.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: ['Payées', 'En attente', 'En retard', 'Avoirs'],
      datasets: [
        {
          data: values.value,
          backgroundColor: ['#34d399', '#fb923c', '#fb7185', '#8b5cf6'],
          borderColor: '#ffffff',
          borderWidth: 3,
          hoverBorderWidth: 3,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutoutPercentage: 68,
      legend: { display: false },
      tooltips: {
        backgroundColor: '#0f172a',
        cornerRadius: 10,
        xPadding: 12,
        yPadding: 10,
      },
      animation: { duration: 500 },
    },
  })
}

function updateChart() {
  if (!chart) return
  chart.data.datasets[0].data = values.value
  chart.update()
}

onMounted(createChart)
onBeforeUnmount(() => chart?.destroy())
watch(values, updateChart, { deep: true })
</script>

<style scoped>
.status-chart-wrap {
  position: relative;
  width: 170px;
  height: 170px;
  flex: 0 0 170px;
}

.status-chart-center {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  color: #0f172a;
}

.status-chart-center strong {
  font-size: 1.7rem;
  line-height: 1;
}

.status-chart-center span {
  margin-top: 0.35rem;
  color: #64748b;
  font-size: 0.75rem;
}
</style>
