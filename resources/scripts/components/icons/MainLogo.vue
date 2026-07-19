<template>
  <svg
    viewBox="0 0 260 58"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-label="AutoFacture"
  >
    <defs>
      <linearGradient id="autofacture-a" x1="8" y1="52" x2="52" y2="4" gradientUnits="userSpaceOnUse">
        <stop stop-color="#0EA5E9" />
        <stop offset="0.5" stop-color="#2563EB" />
        <stop offset="1" stop-color="#8B5CF6" />
      </linearGradient>
      <linearGradient id="autofacture-fold" x1="36" y1="19" x2="55" y2="49" gradientUnits="userSpaceOnUse">
        <stop stop-color="#A78BFA" />
        <stop offset="1" stop-color="#6D28D9" />
      </linearGradient>
      <filter id="autofacture-shadow" x="-30%" y="-30%" width="160%" height="160%">
        <feDropShadow dx="0" dy="5" stdDeviation="5" flood-color="#2563EB" flood-opacity="0.25" />
      </filter>
    </defs>

    <g filter="url(#autofacture-shadow)">
      <path
        d="M29.3 5.8c2.2-3.8 7.7-3.8 9.9 0l22 38.1c2.2 3.8-.6 8.6-5 8.6H12.3c-4.4 0-7.2-4.8-5-8.6l22-38.1Z"
        fill="url(#autofacture-a)"
      />
      <path
        d="M34.3 18.4 47.8 42H36.2l-5.8-10.1-5.7 10.1H13.1l13.5-23.6a4.5 4.5 0 0 1 7.7 0Z"
        fill="white"
        fill-opacity="0.94"
      />
      <path
        d="M39.6 23.1 56.2 52.5H43.4L32.6 33.3l7-10.2Z"
        fill="url(#autofacture-fold)"
        fill-opacity="0.95"
      />
    </g>

    <text
      x="72"
      y="38"
      :fill="darkColor"
      font-size="29"
      font-family="Poppins, Inter, Arial, sans-serif"
      font-weight="780"
      letter-spacing="-1.35"
    >Auto</text>
    <text
      x="136"
      y="38"
      :fill="lightColor"
      font-size="29"
      font-family="Poppins, Inter, Arial, sans-serif"
      font-weight="780"
      letter-spacing="-1.35"
    >Facture</text>
  </svg>
</template>

<script setup>
defineProps({
  darkColor: {
    type: String,
    default: '#0f172a',
  },
  lightColor: {
    type: String,
    default: '#2563eb',
  },
})
</script>
