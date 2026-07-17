<template>
  <svg
    viewBox="0 0 260 56"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-label="AutoFacture"
  >
    <rect x="1" y="1" width="54" height="54" rx="14" :fill="darkColor" />
    <path
      d="M16 15.5h24c2.2 0 4 1.8 4 4v22c0 2.2-1.8 4-4 4H16c-2.2 0-4-1.8-4-4v-22c0-2.2 1.8-4 4-4Z"
      fill="white"
      fill-opacity="0.97"
    />
    <path d="M19 24h18M19 30h18M19 36h10" :stroke="darkColor" stroke-width="3" stroke-linecap="round" />
    <circle cx="38" cy="38" r="6.5" :fill="lightColor" />
    <path d="m35.5 38 1.7 1.8 3.6-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    <text
      x="68"
      y="37"
      :fill="darkColor"
      font-size="29"
      font-family="Inter, Arial, sans-serif"
      font-weight="750"
      letter-spacing="-1"
    >AutoFacture</text>
  </svg>
</template>

<script setup>
defineProps({
  darkColor: {
    type: String,
    default: 'rgba(var(--color-primary-500), var(--tw-text-opacity))',
  },
  lightColor: {
    type: String,
    default: 'rgba(var(--color-primary-400), var(--tw-text-opacity))',
  },
})
</script>
