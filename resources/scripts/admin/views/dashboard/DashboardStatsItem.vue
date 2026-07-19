<template>
  <router-link v-if="!loading" :to="route" class="metric-card" :class="`metric-${variant}`">
    <div class="metric-glow" />
    <div class="metric-content">
      <span class="metric-label">{{ label }}</span>
      <strong class="metric-value"><slot /></strong>
      <div v-if="trend !== null || description" class="metric-meta">
        <span v-if="trend !== null" class="metric-trend" :class="{ negative: trend < 0 }">
          {{ trend >= 0 ? '↗' : '↘' }} {{ Math.abs(trend).toLocaleString('fr-FR') }} %
        </span>
        <span v-if="description" class="metric-description">{{ description }}</span>
      </div>
    </div>

    <div class="metric-icon">
      <component :is="iconComponent" v-if="iconComponent" class="w-10 h-10" />
      <span v-else>{{ iconText }}</span>
    </div>

    <svg class="metric-sparkline" viewBox="0 0 120 42" aria-hidden="true">
      <path d="M2 35 C 16 30, 20 34, 31 25 S 51 18, 61 25 S 78 32, 88 18 S 104 20, 118 7" />
    </svg>
  </router-link>

  <div v-else class="metric-card metric-loading">
    <div class="animate-pulse h-5 w-24 rounded bg-slate-200" />
    <div class="animate-pulse mt-5 h-8 w-40 rounded bg-slate-200" />
  </div>
</template>

<script setup>
defineProps({
  iconComponent: { type: Object, default: null },
  iconText: { type: String, default: '€' },
  loading: { type: Boolean, default: false },
  route: { type: String, required: true },
  label: { type: String, required: true },
  trend: { type: Number, default: null },
  description: { type: String, default: '' },
  variant: {
    type: String,
    default: 'blue',
    validator: (value) => ['blue', 'green', 'orange', 'purple'].includes(value),
  },
})
</script>

<style scoped>
.metric-card {
  position: relative;
  min-height: 154px;
  overflow: hidden;
  display: flex;
  justify-content: space-between;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 22px;
  padding: 22px;
  background: #fff;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.07);
  transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
}

.metric-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 22px 50px rgba(15, 23, 42, 0.12);
}

.metric-blue {
  color: #fff;
  border-color: rgba(99, 102, 241, 0.45);
  background: linear-gradient(135deg, #2563eb 0%, #4f46e5 55%, #8b5cf6 115%);
  box-shadow: 0 22px 52px rgba(37, 99, 235, 0.28);
}

.metric-green { background: linear-gradient(135deg, #f4fffb, #ecfdf5); }
.metric-orange { background: linear-gradient(135deg, #fffaf5, #fff7ed); }
.metric-purple { background: linear-gradient(135deg, #fdfaff, #faf5ff); }

.metric-content { position: relative; z-index: 2; min-width: 0; }
.metric-label { display: block; font-size: 0.78rem; font-weight: 650; color: #64748b; }
.metric-blue .metric-label { color: rgba(255, 255, 255, 0.88); }
.metric-value { display: block; margin-top: 10px; font-size: clamp(1.35rem, 2vw, 1.9rem); line-height: 1.15; color: #0f172a; }
.metric-blue .metric-value { color: #fff; }
.metric-meta { display: flex; align-items: center; gap: 8px; margin-top: 14px; font-size: 0.75rem; }
.metric-trend { font-weight: 750; color: #059669; }
.metric-blue .metric-trend { color: #fff; }
.metric-trend.negative { color: #e11d48; }
.metric-description { color: #64748b; }
.metric-blue .metric-description { color: rgba(255, 255, 255, 0.78); }

.metric-icon {
  position: relative;
  z-index: 2;
  width: 52px;
  height: 52px;
  flex: 0 0 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 17px;
  font-size: 1.45rem;
  font-weight: 750;
  color: #2563eb;
  background: rgba(255, 255, 255, 0.82);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}
.metric-green .metric-icon { color: #059669; }
.metric-orange .metric-icon { color: #f97316; }
.metric-purple .metric-icon { color: #7c3aed; }

.metric-sparkline {
  position: absolute;
  right: 16px;
  bottom: 12px;
  width: 112px;
  height: 42px;
  opacity: 0.9;
}
.metric-sparkline path { fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; }
.metric-green .metric-sparkline { color: #10b981; }
.metric-orange .metric-sparkline,
.metric-purple .metric-sparkline { display: none; }
.metric-glow { position: absolute; inset: auto -35px -60px auto; width: 150px; height: 150px; border-radius: 999px; background: rgba(255,255,255,.14); filter: blur(2px); }
.metric-loading { display: block; background: #fff; }
</style>
