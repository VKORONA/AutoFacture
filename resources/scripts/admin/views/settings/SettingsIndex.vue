<template>
  <BasePage>
    <BasePageHeader :title="$tc('settings.setting', 1)" class="mb-6">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem
          :title="$tc('settings.setting', 2)"
          to="/admin/settings/account-settings"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="w-full mb-6 select-wrapper xl:hidden">
      <BaseMultiselect
        v-model="currentSetting"
        :options="dropdownMenuItems"
        :can-deselect="false"
        value-prop="title"
        track-by="title"
        label="title"
        object
        @update:modelValue="navigateToSetting"
      />
    </div>

    <div class="flex min-w-0 items-start gap-6 pb-12">
      <div class="hidden mt-1 shrink-0 xl:block min-w-[240px]">
        <BaseList>
          <BaseListItem
            v-for="(menuItem, index) in globalStore.settingMenu"
            :key="index"
            :title="$t(menuItem.title)"
            :to="menuItem.link"
            :active="hasActiveUrl(menuItem.link)"
            :index="index"
            class="py-3"
          >
            <template #icon>
              <BaseIcon :name="menuItem.icon"></BaseIcon>
            </template>
          </BaseListItem>
        </BaseList>
      </div>

      <div class="min-w-0 flex-1 overflow-visible">
        <RouterView />
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, watchEffect, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import BaseList from '@/scripts/components/list/BaseList.vue'
import BaseListItem from '@/scripts/components/list/BaseListItem.vue'
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

const currentSetting = ref({})

const globalStore = useGlobalStore()
const route = useRoute()
const router = useRouter()

const dropdownMenuItems = computed(() => {
  return globalStore.settingMenu.map((item) => {
    return Object.assign({}, item, {
      title: t(item.title),
    })
  })
})

watchEffect(() => {
  if (route.path === '/admin/settings') {
    router.push('/admin/settings/account-settings')
  }

  const item = dropdownMenuItems.value.find((item) => {
    return item.link === route.path
  })

  currentSetting.value = item
})

function hasActiveUrl(url) {
  return route.path.indexOf(url) > -1
}

function navigateToSetting(setting) {
  return router.push(setting.link)
}
</script>
