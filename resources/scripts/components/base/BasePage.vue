<template>
  <div class="premium-page-canvas">
    <div class="premium-page-content">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.premium-page-canvas {
  position: relative;
  flex: 1;
  min-height: calc(100vh - 76px);
  overflow: hidden;
  background:
    radial-gradient(circle at 92% 2%, rgba(139,92,246,.08), transparent 28%),
    linear-gradient(180deg, #f8fafc 0%, #f3f6fb 100%);
}

.premium-page-content {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 1680px;
  margin: 0 auto;
  padding: 26px;
}

@media (max-width: 767px) {
  .premium-page-canvas {
    min-height: calc(100vh - 66px);
  }

  .premium-page-content {
    padding: 18px 14px 28px;
  }
}
</style>
