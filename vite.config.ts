import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'node:path'

export default defineConfig({
  base: '/build/',
  plugins: [vue()],
  publicDir: 'resources/static',
  server: {
    port: 3000,
    watch: {
      ignored: ['**/.env/**'],
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources'),
      'vue-i18n': 'vue-i18n/dist/vue-i18n.cjs.js',
    },
  },
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'resources/scripts/main.js'),
    },
  },
})
