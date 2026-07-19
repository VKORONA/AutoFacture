<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardStats from './DashboardStats.vue'
import DashboardChart from './DashboardChart.vue'
import DashboardTable from './DashboardTable.vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import abilities from '@/scripts/admin/stub/abilities'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const dashboardStore = useDashboardStore()

const firstName = computed(() => {
  const name = userStore.currentUser?.name || 'Bienvenue'
  return name.trim().split(/\s+/)[0]
})

const currentDate = computed(() =>
  new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date())
)

onMounted(async () => {
  if (route.meta.ability && !userStore.hasAbilities(route.meta.ability)) {
    router.push({ name: 'account.settings' })
    return
  }

  if (route.meta.isOwner && !userStore.currentUser.is_owner) {
    router.push({ name: 'account.settings' })
    return
  }

  if (userStore.hasAbilities(abilities.DASHBOARD)) {
    await dashboardStore.loadData()
  }
})
</script>

<template>
  <div class="premium-dashboard">
    <div class="dashboard-aura aura-one" />
    <div class="dashboard-aura aura-two" />

    <div class="dashboard-content">
      <header class="welcome-header">
        <div>
          <p class="welcome-date">{{ currentDate }}</p>
          <h1>Bonjour {{ firstName }} <span aria-hidden="true">👋</span></h1>
          <p>Bienvenue dans votre espace de gestion AutoFacture.</p>
        </div>

        <div class="welcome-actions">
          <router-link to="/admin/estimates/create" class="welcome-button secondary">
            <span>+</span> Nouveau devis
          </router-link>
          <router-link to="/admin/invoices/create" class="welcome-button primary">
            <span>+</span> Nouvelle facture
          </router-link>
        </div>
      </header>

      <DashboardStats />
      <DashboardChart />
      <DashboardTable />

      <footer class="dashboard-footer">
        <span class="shield">◇</span>
        Données locales protégées et accès sécurisé selon les bonnes pratiques RGPD.
      </footer>
    </div>
  </div>
</template>

<style scoped>
.premium-dashboard {
  position: relative;
  min-height: 100%;
  overflow: hidden;
  background:
    linear-gradient(180deg, rgba(248,250,252,.8), rgba(241,245,249,.96)),
    radial-gradient(circle at top right, rgba(99,102,241,.09), transparent 35%);
}
.dashboard-content { position: relative; z-index: 2; width: 100%; max-width: 1680px; margin: 0 auto; padding: 26px 26px 38px; }
.dashboard-aura { position: absolute; border-radius: 999px; filter: blur(80px); opacity: .26; pointer-events: none; }
.aura-one { width: 340px; height: 340px; right: -90px; top: -100px; background: #a78bfa; }
.aura-two { width: 300px; height: 300px; left: 20%; bottom: -180px; background: #60a5fa; }
.welcome-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 24px; }
.welcome-date { margin-bottom: 7px; color: #7c3aed; font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; }
.welcome-header h1 { color: #0f172a; font-size: clamp(1.65rem, 2.6vw, 2.25rem); font-weight: 850; letter-spacing: -.045em; line-height: 1.1; }
.welcome-header h1 span { display: inline-block; transform-origin: bottom center; animation: wave 2.6s ease-in-out infinite; }
.welcome-header p:last-child { margin-top: 7px; color: #64748b; font-size: .82rem; }
.welcome-actions { display: flex; gap: 10px; }
.welcome-button { display: inline-flex; align-items: center; gap: 8px; padding: 11px 15px; border-radius: 13px; font-size: .75rem; font-weight: 750; transition: transform .18s ease, box-shadow .18s ease; }
.welcome-button:hover { transform: translateY(-2px); }
.welcome-button span { font-size: 1rem; line-height: 1; }
.welcome-button.secondary { color: #4338ca; border: 1px solid #c7d2fe; background: rgba(255,255,255,.82); }
.welcome-button.primary { color: #fff; border: 1px solid rgba(255,255,255,.2); background: linear-gradient(135deg,#2563eb,#7c3aed); box-shadow: 0 12px 28px rgba(79,70,229,.26); }
.dashboard-footer { display: flex; justify-content: center; align-items: center; gap: 8px; padding: 28px 10px 0; color: #94a3b8; font-size: .68rem; }
.shield { color: #2563eb; font-size: 1rem; font-weight: 800; }
@keyframes wave { 0%,60%,100% { transform: rotate(0deg); } 70% { transform: rotate(16deg); } 80% { transform: rotate(-8deg); } 90% { transform: rotate(8deg); } }
@media (max-width: 760px) {
  .dashboard-content { padding: 20px 16px 32px; }
  .welcome-header { align-items: flex-start; flex-direction: column; }
  .welcome-actions { width: 100%; }
  .welcome-button { flex: 1; justify-content: center; }
}
</style>
