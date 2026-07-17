<?php

namespace Crater\Traits;

trait GeneratesMenuTrait
{
    public function generateMenu($key, $user)
    {
        $menu = [];

        foreach (\Menu::get($key)->items->toArray() as $data) {
            if (! $this->isMenuFeatureEnabled($key, $data->data['name'])) {
                continue;
            }

            if ($user->checkAccess($data)) {
                $menu[] = [
                    'title' => $data->title,
                    'link' => $data->link->path['url'],
                    'icon' => $data->data['icon'],
                    'name' => $data->data['name'],
                    'group' => $data->data['group'],
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

        // Les extensions ou futurs menus non répertoriés restent visibles afin
        // de préserver la compatibilité avec le système modulaire de Crater.
        if (! $featureName) {
            return true;
        }

        return (bool) config("autofacture.{$featuresKey}.{$featureName}", true);
    }
}
