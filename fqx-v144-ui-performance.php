<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v144 — User-friendly UI + mobile performance pass.
 * Scope: UI clarity and asset loading only. No pricing/subscription/payment/table workflow changes.
 */

function fqx_v144_is_dashboard_page(): bool {
    return function_exists('is_page') && is_page(['dashboard', 'super-admin-dashboard', 'kitchen-dashboard', 'kitchen']);
}

function fqx_v144_is_room_admin_page(): bool {
    if (is_admin()) { return true; }
    if (!function_exists('is_page') || !is_page('dashboard')) { return false; }
    $tab = sanitize_key(wp_unslash($_GET['tab'] ?? ''));
    $action = sanitize_key(wp_unslash($_GET['action'] ?? ''));
    $page = sanitize_key(wp_unslash($_GET['page'] ?? ''));
    return in_array($tab, ['rooms', 'wifi'], true) || in_array($action, ['add_room', 'edit_room', 'rooms', 'wifi'], true) || in_array($page, ['rooms', 'wifi'], true);
}

function fqx_v144_is_customer_runtime_page(): bool {
    return function_exists('is_page') && is_page(['menu', 'cart', 'checkout', 'bill', 'order-status']);
}

function fqx_v144_is_marketing_page(): bool {
    return (function_exists('is_front_page') && is_front_page()) || (function_exists('is_home') && is_home()) || (function_exists('is_page') && is_page(['home','pricing','blog','about','features','contact','saas','login','signup','register']));
}

function fqx_v144_should_load_ui_assets(): bool {
    return is_admin() || fqx_v144_is_dashboard_page() || fqx_v144_is_room_admin_page() || fqx_v144_is_customer_runtime_page() || fqx_v144_is_marketing_page() || !empty($_GET['fqx_room_qr_card_print']);
}

function fqx_v144_enqueue_ui_assets(): void {
    if (!fqx_v144_should_load_ui_assets()) { return; }
    $css = get_template_directory() . '/assets/css/fqx-v144-ui-performance.min.css';
    wp_enqueue_style('fqx-v144-ui-performance', get_template_directory_uri() . '/assets/css/fqx-v144-ui-performance.min.css', [], file_exists($css) ? (string) filemtime($css) : '144');

    if (fqx_v144_is_room_admin_page() || is_admin()) {
        $js = get_template_directory() . '/assets/js/fqx-v144-ui-performance.min.js';
        wp_enqueue_script('fqx-v144-ui-performance', get_template_directory_uri() . '/assets/js/fqx-v144-ui-performance.min.js', [], file_exists($js) ? (string) filemtime($js) : '144', true);
    }
}
add_action('wp_enqueue_scripts', 'fqx_v144_enqueue_ui_assets', 3900);
add_action('admin_enqueue_scripts', 'fqx_v144_enqueue_ui_assets', 90);

function fqx_v144_mobile_asset_cleanup(): void {
    if (is_admin()) { return; }

    $room_admin = fqx_v144_is_room_admin_page();
    $customer = fqx_v144_is_customer_runtime_page();
    $dashboard = fqx_v144_is_dashboard_page();
    $marketing = fqx_v144_is_marketing_page();

    // Marketing pages need header/home UI only; remove customer, room, bill, tracker, dashboard scripts.
    if ($marketing && !$customer && !$dashboard && !$room_admin) {
        $remove_scripts = [
            'fluuexqr-v90-preview-ui','fluuexqr-v104-bill-page-force','fluuexqr-v123-real-bill-fix',
            'fqx-v129-menu-bill-tracker-sync','fqx-v130-customer-bill-tracker-fix','fqx-v131-tracker-stable',
            'fqx-v132-live-admin','fqx-v133-room-session','fqx-v134-room-template-picker','fqx-v138-room-template-picker',
            'fqx-v120-dashboard-fast','fqx-v121-real-ui-fix','fqx-v122-admin-visible','fqx-v144-ui-performance'
        ];
        foreach ($remove_scripts as $handle) { wp_dequeue_script($handle); }

        $remove_styles = [
            'fluuexqr-v90-preview-ui','fqx-v129-menu-bill-tracker-sync','fqx-v130-customer-bill-tracker-fix','fqx-v131-tracker-stable',
            'fqx-v132-pricing-live-admin','fqx-v133-room-session-wifi-card','fqx-v134-room-template-picker','fqx-v138-room-template-admin',
            'fqx-v120-blog-admin-fix','fqx-v121-real-ui-fix','fqx-v122-blog-admin-visible'
        ];
        foreach ($remove_styles as $handle) { wp_dequeue_style($handle); }
    }

    // Customer runtime does not need room template picker or admin dashboard assets.
    if ($customer && !$room_admin) {
        foreach (['fqx-v134-room-template-picker','fqx-v138-room-template-picker','fqx-v132-live-admin','fqx-v120-dashboard-fast'] as $handle) { wp_dequeue_script($handle); }
        foreach (['fqx-v134-room-template-picker','fqx-v138-room-template-admin','fqx-v132-pricing-live-admin','fqx-v120-blog-admin-fix'] as $handle) { wp_dequeue_style($handle); }
    }

    // Dashboard pages should not load customer menu recovery scripts unless they are needed by active tab.
    if ($dashboard && !$customer) {
        foreach (['fqx-v129-menu-bill-tracker-sync','fqx-v130-customer-bill-tracker-fix','fqx-v131-tracker-stable'] as $handle) { wp_dequeue_script($handle); }
        foreach (['fqx-v129-menu-bill-tracker-sync','fqx-v130-customer-bill-tracker-fix','fqx-v131-tracker-stable'] as $handle) { wp_dequeue_style($handle); }
    }
}
add_action('wp_enqueue_scripts', 'fqx_v144_mobile_asset_cleanup', 4200);

function fqx_v144_defer_safe_scripts($tag, $handle, $src) {
    $defer = [
        'fluuexqr-v90-preview-ui','fluuexqr-v91-v6-mobile-fixed-ui','fluuexqr-v95-premium-responsive','fluuexqr-v104-bill-page-force',
        'fluuexqr-v123-real-bill-fix','fqx-v119-home-restore','fqx-v120-dashboard-fast','fqx-v121-real-ui-fix','fqx-v122-admin-visible',
        'fqx-v129-menu-bill-tracker-sync','fqx-v130-customer-bill-tracker-fix','fqx-v131-tracker-stable','fqx-v132-live-admin',
        'fqx-v133-room-session','fqx-v134-room-template-picker','fqx-v138-room-template-picker','fqx-v144-ui-performance'
    ];
    if (in_array($handle, $defer, true) && false === strpos($tag, ' defer') && false === strpos($tag, ' async')) {
        return str_replace('<script ', '<script defer ', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'fqx_v144_defer_safe_scripts', 20, 3);

function fqx_v144_preload_brand_logo(): void {
    if (is_admin()) { return; }
    if (!function_exists('fqx_get_brand_logo_url')) { return; }
    $logo = fqx_get_brand_logo_url('main');
    if (!$logo) { return; }
    echo '<link rel="preload" as="image" href="' . esc_url($logo) . '" fetchpriority="high">' . "\n";
}
add_action('wp_head', 'fqx_v144_preload_brand_logo', 2);

function fqx_v144_body_classes(array $classes): array {
    $classes[] = 'fqx-v144-ui';
    if (fqx_v144_is_room_admin_page()) { $classes[] = 'fqx-v144-room-admin'; }
    if (fqx_v144_is_marketing_page()) { $classes[] = 'fqx-v144-marketing-fast'; }
    return $classes;
}
add_filter('body_class', 'fqx_v144_body_classes');
