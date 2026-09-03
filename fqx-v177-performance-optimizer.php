<?php
/**
 * FluuexQR v177 — Performance optimizer.
 * Goal: keep UI/UX exactly same while reducing unused CSS/JS, cleaning safe temp data,
 * deferring non-critical scripts, and purging cache only after save/update actions.
 */
if (!defined('ABSPATH')) { exit; }

function fqx_v177_current_slug(): string {
    if (function_exists('is_page') && is_page()) {
        $post = get_post();
        if ($post && !empty($post->post_name)) { return sanitize_key($post->post_name); }
    }
    return '';
}

function fqx_v177_current_tab(): string {
    return isset($_GET['tab']) ? sanitize_key(wp_unslash((string) $_GET['tab'])) : '';
}

function fqx_v177_is_restaurant_admin(): bool {
    if (function_exists('fqx_v145_is_restaurant_admin_ui') && fqx_v145_is_restaurant_admin_ui()) { return true; }
    return function_exists('is_page') && is_page('restaurant-dashboard');
}

function fqx_v177_is_customer_flow(): bool {
    if (!function_exists('is_page')) { return false; }
    return is_page(['menu','cart','checkout','bill','order-status']);
}

function fqx_v177_is_kitchen_flow(): bool {
    return function_exists('is_page') && is_page('kitchen-dashboard');
}

function fqx_v177_is_super_admin(): bool {
    return function_exists('is_page') && is_page('super-admin-dashboard');
}

function fqx_v177_is_marketing(): bool {
    if (is_admin()) { return false; }
    if (fqx_v177_is_restaurant_admin() || fqx_v177_is_customer_flow() || fqx_v177_is_kitchen_flow() || fqx_v177_is_super_admin()) { return false; }
    if (is_front_page() || is_home()) { return true; }
    return function_exists('is_page') && is_page(['home','pricing','blog','about','features','contact','saas','login','signup']);
}

function fqx_v177_dequeue_handles(array $handles, string $type = 'script'): void {
    foreach ($handles as $handle) {
        if ('style' === $type) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        } else {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }
}

function fqx_v177_asset_cleanup(): void {
    if (is_admin()) { return; }

    $admin_styles = ['fqx-restaurant-admin-v145','fqx-v115-superadmin','fqx-v120-blog-admin-fix','fqx-v121-real-ui-fix','fqx-v122-blog-admin-visible','fqx-v132-pricing-live-admin'];
    $admin_scripts = ['fqx-restaurant-admin-v145','fqx-v120-dashboard-fast','fqx-v121-real-ui-fix','fqx-v122-admin-visible','fqx-v132-live-admin'];
    $room_styles = ['fqx-v133-room-session-wifi-card','fqx-v134-room-template-picker','fqx-v138-room-template-admin'];
    $room_scripts = ['fqx-v133-room-session','fqx-v134-room-template-picker','fqx-v138-room-template-picker'];
    $customer_styles = ['fqx-v129-menu-bill-tracker-sync','fqx-v130-customer-bill-tracker-fix','fqx-v131-tracker-stable'];
    $customer_scripts = ['fqx-v129-menu-bill-tracker-sync','fqx-v130-customer-bill-tracker-fix','fqx-v131-tracker-stable','fluuexqr-v104-bill-page-force','fluuexqr-v123-real-bill-fix'];
    $marketing_styles = ['fqx-v116-home-dashboard','fqx-v119-home-restore','fqx-v120-blog-admin-fix','fqx-v121-real-ui-fix','fqx-v122-blog-admin-visible','fqx-v144-ui-performance'];
    $marketing_scripts = ['fqx-v119-home-restore','fqx-v121-real-ui-fix','fqx-v122-admin-visible','fqx-v144-ui-performance'];

    if (fqx_v177_is_restaurant_admin()) {
        $tab = fqx_v177_current_tab();
        // Customer/menu tracking assets are not needed inside Restaurant Admin.
        fqx_v177_dequeue_handles($customer_styles, 'style');
        fqx_v177_dequeue_handles($customer_scripts, 'script');
        // Marketing-only assets are not needed inside dashboard pages.
        fqx_v177_dequeue_handles(['fqx-v116-home-dashboard','fqx-v119-home-restore'], 'style');
        fqx_v177_dequeue_handles(['fqx-v119-home-restore'], 'script');
        // Room template picker/CSS only on Rooms/WiFi/QR screens.
        if (!in_array($tab, ['rooms','wifi','wifi-qr','qr-templates','print-qr'], true)) {
            fqx_v177_dequeue_handles($room_styles, 'style');
            fqx_v177_dequeue_handles($room_scripts, 'script');
        }
        return;
    }

    if (fqx_v177_is_customer_flow()) {
        fqx_v177_dequeue_handles($admin_styles, 'style');
        fqx_v177_dequeue_handles($admin_scripts, 'script');
        fqx_v177_dequeue_handles($room_styles, 'style');
        fqx_v177_dequeue_handles($room_scripts, 'script');
        fqx_v177_dequeue_handles(['fqx-v116-home-dashboard','fqx-v119-home-restore','fqx-v144-ui-performance'], 'style');
        fqx_v177_dequeue_handles(['fqx-v119-home-restore','fqx-v144-ui-performance'], 'script');
        return;
    }

    if (fqx_v177_is_kitchen_flow() || fqx_v177_is_super_admin()) {
        fqx_v177_dequeue_handles($customer_styles, 'style');
        fqx_v177_dequeue_handles($customer_scripts, 'script');
        fqx_v177_dequeue_handles($room_styles, 'style');
        fqx_v177_dequeue_handles($room_scripts, 'script');
        return;
    }

    if (fqx_v177_is_marketing()) {
        // Marketing pages should not carry dashboard/customer/order recovery assets.
        fqx_v177_dequeue_handles(array_merge($admin_styles, $room_styles, $customer_styles), 'style');
        fqx_v177_dequeue_handles(array_merge($admin_scripts, $room_scripts, $customer_scripts), 'script');
        // Keep main marketing bundle + homepage/pricing UI only; remove duplicate preview/dashboard fixes.
        fqx_v177_dequeue_handles(['fluuexqr-v90-preview-ui','fluuexqr-v91-v6-mobile-fixed-ui','fluuexqr-v95-premium-responsive'], 'script');
    }
}
add_action('wp_enqueue_scripts', 'fqx_v177_asset_cleanup', 9999);

function fqx_v177_defer_scripts(string $tag, string $handle, string $src): string {
    if (is_admin() || '' === $src) { return $tag; }
    $exclude = ['jquery','jquery-core','jquery-migrate','fluuexqr-v81-bundle'];
    if (in_array($handle, $exclude, true)) { return $tag; }
    if (false !== strpos($tag, ' defer') || false !== strpos($tag, ' async')) { return $tag; }
    return str_replace('<script ', '<script defer ', $tag);
}
add_filter('script_loader_tag', 'fqx_v177_defer_scripts', 99, 3);

function fqx_v177_cleanup_wp_assets(): void {
    if (is_admin()) { return; }
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('global-styles');
    wp_dequeue_script('wp-embed');
}
add_action('wp_enqueue_scripts', 'fqx_v177_cleanup_wp_assets', 10000);

function fqx_v177_preload_brand_logo(): void {
    if (is_admin() || !function_exists('fqx_get_brand_logo_url')) { return; }
    $url = fqx_get_brand_logo_url('main');
    if (!$url) { return; }
    echo '<link rel="preload" as="image" href="' . esc_url($url) . '" fetchpriority="high">' . "\n";
}
add_action('wp_head', 'fqx_v177_preload_brand_logo', 2);

function fqx_v177_purge_all_caches(): void {
    if (function_exists('litespeed_purge_all')) { litespeed_purge_all(); }
    if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) { LiteSpeed_Cache_API::purge_all(); }
    if (function_exists('rocket_clean_domain')) { rocket_clean_domain(); }
    if (function_exists('w3tc_flush_all')) { w3tc_flush_all(); }
    if (function_exists('wp_cache_clear_cache')) { wp_cache_clear_cache(); }
    if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
    delete_transient('fqx_v177_temp_cleanup_done');
}

function fqx_v177_maybe_purge_after_save(): void {
    if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) { return; }
    $action = isset($_POST['action']) ? sanitize_key(wp_unslash((string) $_POST['action'])) : '';
    if (!$action) { return; }
    $purge_keywords = ['save','update','delete','generate','upload','payment','subscription','template','room','table','menuqr','fqx'];
    foreach ($purge_keywords as $keyword) {
        if (false !== strpos($action, $keyword)) {
            fqx_v177_purge_all_caches();
            return;
        }
    }
}
add_action('shutdown', 'fqx_v177_maybe_purge_after_save', 20);

function fqx_v177_cleanup_safe_temp_data(): void {
    if (get_transient('fqx_v177_temp_cleanup_done')) { return; }
    global $wpdb;
    // Delete expired transients and only clearly temporary FluuexQR transients/options.
    $now = time();
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', $now));
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_fqx_tmp_%' OR option_name LIKE '_transient_menuqr_tmp_%' OR option_name LIKE '_transient_fluuexqr_tmp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_fqx_tmp_%' OR option_name LIKE '_transient_timeout_menuqr_tmp_%' OR option_name LIKE '_transient_timeout_fluuexqr_tmp_%'");
    set_transient('fqx_v177_temp_cleanup_done', 1, DAY_IN_SECONDS);
}
add_action('init', 'fqx_v177_cleanup_safe_temp_data', 20);

function fqx_v177_add_cache_headers(): void {
    if (is_admin() || headers_sent()) { return; }
    if (fqx_v177_is_customer_flow() || fqx_v177_is_restaurant_admin()) { return; }
    header('Cache-Control: public, max-age=600, stale-while-revalidate=86400');
}
add_action('send_headers', 'fqx_v177_add_cache_headers');
