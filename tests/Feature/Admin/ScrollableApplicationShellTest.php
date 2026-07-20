<?php

it('uses the premium main area as the single vertical scroll owner', function () {
    $layout = file_get_contents(resource_path('scripts/admin/layouts/LayoutBasic.vue'));
    $basePage = file_get_contents(resource_path('scripts/components/base/BasePage.vue'));

    expect($layout)
        ->toContain('height: 100dvh;')
        ->toContain('overflow-y: auto;')
        ->toContain('overscroll-behavior-y: contain;')
        ->toContain('scrollbar-gutter: stable;')
        ->toContain('-webkit-overflow-scrolling: touch;')
        ->toContain("'premium-main--electronic-invoicing'")
        ->and($basePage)
        ->toContain('overflow: visible;')
        ->not->toContain('overflow: hidden;');
});

it('does not clip settings forms and keeps electronic invoicing cards contrasted', function () {
    $settings = file_get_contents(resource_path('scripts/admin/views/settings/SettingsIndex.vue'));
    $layout = file_get_contents(resource_path('scripts/admin/layouts/LayoutBasic.vue'));

    expect($settings)
        ->toContain('min-w-0 flex-1 overflow-visible')
        ->not->toContain('w-full overflow-hidden')
        ->and($layout)
        ->toContain('premium-main--document-templates')
        ->toContain('linear-gradient(135deg, #020617 0%, #082f78 54%, #312e81 100%)')
        ->toContain('background: rgba(255, 255, 255, .96) !important;');
});
