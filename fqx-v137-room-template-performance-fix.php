<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v137 — Room template selection reliability + mobile performance cleanup.
 * Scope: does not change pricing/subscription/payment/table workflows.
 */

function fqx_v137_is_operational_page(): bool {
    return is_page(['menu','cart','checkout','bill','order-status','dashboard','kitchen','super-admin-dashboard']);
}

function fqx_v137_is_marketing_page(): bool {
    return is_front_page() || is_home() || is_page(['home','pricing','blog','about','features','contact','saas']);
}

function fqx_v137_dequeue_noncritical_mobile_assets(): void {
    if (is_admin() || fqx_v137_is_operational_page() || !fqx_v137_is_marketing_page()) { return; }

    // Marketing pages do not need bill recovery, tracker, dashboard live refresh, or room template assets.
    $scripts = [
        'fluuexqr-v104-bill-page-force',
        'fluuexqr-v123-real-bill-fix',
        'fqx-v129-menu-bill-tracker-sync',
        'fqx-v130-customer-bill-tracker-fix',
        'fqx-v131-tracker-stable',
        'fqx-v132-live-admin',
        'fqx-v133-room-session',
        'fqx-v134-room-template-picker',
        'fqx-v120-dashboard-fast',
        'fqx-v121-real-ui-fix',
        'fqx-v122-admin-visible',
    ];
    foreach ($scripts as $handle) { wp_dequeue_script($handle); wp_deregister_script($handle); }

    $styles = [
        'fqx-v129-menu-bill-tracker-sync',
        'fqx-v130-customer-bill-tracker-fix',
        'fqx-v131-tracker-stable',
        'fqx-v132-pricing-live-admin',
        'fqx-v133-room-session-wifi-card',
        'fqx-v134-room-template-picker',
        'fqx-v120-blog-admin-fix',
        'fqx-v121-real-ui-fix',
        'fqx-v122-blog-admin-visible',
    ];
    foreach ($styles as $handle) { wp_dequeue_style($handle); wp_deregister_style($handle); }
}
add_action('wp_enqueue_scripts', 'fqx_v137_dequeue_noncritical_mobile_assets', 2500);

function fqx_v137_add_defer_to_safe_scripts($tag, $handle, $src) {
    $defer = [
        'fluuexqr-v90-preview-ui',
        'fluuexqr-v91-v6-mobile-fixed-ui',
        'fluuexqr-v95-premium-responsive',
        'fqx-v119-home-restore',
        'fqx-v134-room-template-picker',
        'fqx-v133-room-session',
    ];
    if (in_array($handle, $defer, true) && false === strpos($tag, ' defer')) {
        return str_replace('<script ', '<script defer ', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'fqx_v137_add_defer_to_safe_scripts', 10, 3);

// Prefer a WebP logo when it exists to improve mobile LCP without changing UI.
function fqx_v137_prefer_webp_brand_logo($url = ''): string {
    $webp_rel = 'assets/images/fluuexqr-logo.webp';
    $webp_path = get_template_directory() . '/' . $webp_rel;
    if (file_exists($webp_path)) { return get_template_directory_uri() . '/' . $webp_rel; }
    return is_string($url) ? $url : '';
}
