<?php

namespace Crater\Traits;

trait GeneratesMenuTrait
{
    public function generateMenu($key, $user)
    {
        $menu = [];
        $configKey = [
            'main_menu' => 'crater.main_menu',
            'setting_menu' => 'crater.setting_menu',
            'customer_portal_menu' => 'crater.customer_menu',
        ][$key] ?? null;

        if (! $configKey) {
            return $menu;
        }

        foreach (config($configKey, []) as $data) {
            if (! $this->isMenuFeatureEnabled($key, $data['name'])) {
                continue;
            }

            $accessDescriptor = (object) ['data' => $data];

            if ($user->checkAccess($accessDescriptor)) {
                $menu[] = [
                    'title' => $data['title'],
                    'link' => $data['link'],
                    'icon' => $data['icon'],
                    'name' => $data['name'],
                    'group' => $data['group'],
                ];
            }
        }

        return $menu;
    }

    private function isMenuFeatureEnabled(string $menuKey, string $menuName): bool
    {
        $isSettingMenu = $menuKey === 'setting_menu';
        $mapKey = $isSettingMenu ? 'setting_menu_map' : 'main_menu_map';
        $featuresKey = $isSettingMenu ? 'settings_features' : 'features';
        $featureName = config("autofacture.{$mapKey}.{$menuName}");

        if (! $featureName) {
            return true;
        }

        return (bool) config("autofacture.{$featuresKey}.{$featureName}", true);
    }
}
