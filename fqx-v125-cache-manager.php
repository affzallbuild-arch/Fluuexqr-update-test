<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * FluuexQR v125 Cache Manager
 * - Filemtime based CSS/JS versioning for theme assets.
 * - Automatic cache purge only after save/update/delete actions.
 * - LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache, Elementor cache support.
 * - Browser asset cache busting through a saved version token, changed only after write actions.
 */

function fqx_v125_asset_buster(): string {
    $buster = get_option('fqx_asset_cache_buster', '');
    if (!$buster) {
        $buster = (string) time();
        update_option('fqx_asset_cache_buster', $buster, false);
    }
    return (string) $buster;
}

function fqx_v125_theme_asset_path_from_src(string $src): string {
    $src_path = (string) wp_parse_url($src, PHP_URL_PATH);
    $theme_url_path = (string) wp_parse_url(MENUQR_THEME_URI, PHP_URL_PATH);
    if (!$src_path || !$theme_url_path || strpos($src_path, $theme_url_path) === false) {
        return '';
    }
    $relative = ltrim(substr($src_path, strpos($src_path, $theme_url_path) + strlen($theme_url_path)), '/');
    if (!$relative) {
        return '';
    }
    $real = wp_normalize_path(MENUQR_THEME_DIR . '/' . $relative);
    $base = wp_normalize_path(MENUQR_THEME_DIR);
    if (strpos($real, $base) !== 0 || !file_exists($real)) {
        return '';
    }
    return $real;
}

function fqx_v125_filemtime_asset_version_filter(string $src, string $handle): string {
    if (is_admin() || !$src || strpos($src, MENUQR_THEME_URI) === false) {
        return $src;
    }

    $path = fqx_v125_theme_asset_path_from_src($src);
    if (!$path) {
        return $src;
    }

    $file_ver = (string) filemtime($path);
    $asset_ver = $file_ver . '-' . fqx_v125_asset_buster();

    // Replace the existing ver query instead of stacking duplicate params.
    $src = remove_query_arg(['ver', 'fqxv'], $src);
    return add_query_arg('ver', rawurlencode($asset_ver), $src);
}
add_filter('style_loader_src', 'fqx_v125_filemtime_asset_version_filter', 999, 2);
add_filter('script_loader_src', 'fqx_v125_filemtime_asset_version_filter', 999, 2);

function fqx_v125_touch_browser_asset_cache(string $reason = ''): void {
    $token = sprintf('%d-%s', time(), wp_generate_password(6, false, false));
    update_option('fqx_asset_cache_buster', $token, false);
    update_option('fqx_last_cache_purge', [
        'time'   => current_time('mysql'),
        'reason' => sanitize_text_field($reason),
        'user'   => get_current_user_id(),
    ], false);
}

function fqx_v125_call_if_exists(string $function, array $args = []): bool {
    if (function_exists($function)) {
        try {
            call_user_func_array($function, $args);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
    return false;
}

function fqx_v125_purge_litespeed(): bool {
    $done = false;
    if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
        try { LiteSpeed_Cache_API::purge_all(); $done = true; } catch (Throwable $e) {}
    }
    do_action('litespeed_purge_all');
    do_action('litespeed_purge_all_object');
    return true || $done;
}

function fqx_v125_purge_wp_rocket(): bool {
    $done = false;
    $done = fqx_v125_call_if_exists('rocket_clean_domain') || $done;
    $done = fqx_v125_call_if_exists('rocket_clean_minify') || $done;
    $done = fqx_v125_call_if_exists('rocket_clean_cache_busting') || $done;
    if (function_exists('rocket_get_constant') && defined('WP_ROCKET_CACHE_PATH')) {
        $done = fqx_v125_call_if_exists('rocket_rrmdir', [WP_ROCKET_CACHE_PATH]) || $done;
    }
    return $done;
}

function fqx_v125_purge_w3_total_cache(): bool {
    $done = false;
    $done = fqx_v125_call_if_exists('w3tc_flush_all') || $done;
    if (class_exists('W3_Plugin_TotalCacheAdmin')) {
        try {
            $admin = w3_instance('W3_Plugin_TotalCacheAdmin');
            if (is_object($admin) && method_exists($admin, 'flush_all')) {
                $admin->flush_all();
                $done = true;
            }
        } catch (Throwable $e) {}
    }
    return $done;
}

function fqx_v125_purge_wp_super_cache(): bool {
    $done = false;
    if (function_exists('wp_cache_clear_cache')) {
        try {
            wp_cache_clear_cache(is_multisite() ? get_current_blog_id() : 0);
            $done = true;
        } catch (Throwable $e) {
            try { wp_cache_clear_cache(); $done = true; } catch (Throwable $e2) {}
        }
    }
    if (function_exists('prune_super_cache') && defined('WP_CONTENT_DIR')) {
        try {
            prune_super_cache(WP_CONTENT_DIR . '/cache/supercache/', true);
            $done = true;
        } catch (Throwable $e) {}
    }
    return $done;
}

function fqx_v125_purge_elementor(): bool {
    $done = false;
    if (did_action('elementor/loaded') && class_exists('\\Elementor\\Plugin')) {
        try {
            $elementor = \Elementor\Plugin::$instance;
            if (isset($elementor->files_manager) && method_exists($elementor->files_manager, 'clear_cache')) {
                $elementor->files_manager->clear_cache();
                $done = true;
            }
            if (isset($elementor->posts_css_manager) && method_exists($elementor->posts_css_manager, 'clear_cache')) {
                $elementor->posts_css_manager->clear_cache();
                $done = true;
            }
        } catch (Throwable $e) {}
    }
    return $done;
}

function fqx_v125_purge_wordpress_object_cache(): bool {
    $done = false;
    if (function_exists('wp_cache_flush')) {
        try { wp_cache_flush(); $done = true; } catch (Throwable $e) {}
    }
    return $done;
}

function fqx_v125_purge_all_caches(string $reason = 'manual'): void {
    static $already_purged = false;
    if ($already_purged) {
        return;
    }
    $already_purged = true;

    fqx_v125_touch_browser_asset_cache($reason);

    $results = [
        'litespeed' => fqx_v125_purge_litespeed(),
        'wp_rocket' => fqx_v125_purge_wp_rocket(),
        'w3_total_cache' => fqx_v125_purge_w3_total_cache(),
        'wp_super_cache' => fqx_v125_purge_wp_super_cache(),
        'elementor' => fqx_v125_purge_elementor(),
        'object_cache' => fqx_v125_purge_wordpress_object_cache(),
    ];

    do_action('fqx_after_cache_purge', $reason, $results);
    update_option('fqx_last_cache_purge_results', $results, false);
}

function fqx_v125_schedule_cache_purge(string $reason): void {
    $GLOBALS['fqx_v125_needs_cache_purge'] = sanitize_key($reason ?: 'save_action');
}

function fqx_v125_maybe_purge_on_shutdown(): void {
    if (empty($GLOBALS['fqx_v125_needs_cache_purge'])) {
        return;
    }
    fqx_v125_purge_all_caches((string) $GLOBALS['fqx_v125_needs_cache_purge']);
}
add_action('shutdown', 'fqx_v125_maybe_purge_on_shutdown', 1);

function fqx_v125_register_write_action_hooks(): void {
    $admin_post_actions = [
        'menuqr_save_category', 'menuqr_delete_category',
        'menuqr_save_item', 'menuqr_delete_item',
        'menuqr_save_table', 'menuqr_delete_table',
        'menuqr_save_room', 'menuqr_delete_room',
        'menuqr_save_staff', 'menuqr_delete_staff',
        'menuqr_save_payment_form',
        'menuqr_save_qr_template', 'menuqr_save_bill_settings_form',
        'menuqr_save_reviews_form',
        'menuqr_save_coupon', 'menuqr_delete_coupon',
        'menuqr_save_combo', 'menuqr_delete_combo',
        'menuqr_save_plan', 'menuqr_delete_plan',
        'menuqr_save_restaurant_admin', 'menuqr_update_restaurant_subscription',
        'menuqr_save_platform_settings',
        'menuqr_verify_subscription_payment',
        'menuqr_request_subscription_payment',
        'menuqr_mark_bill_payment', 'menuqr_close_bill_session',
        'menuqr_restaurant_approval',
    ];

    foreach ($admin_post_actions as $action) {
        add_action('admin_post_' . $action, function () use ($action): void {
            fqx_v125_schedule_cache_purge($action);
        }, 1);
    }

    $ajax_actions = [
        'menuqr_save_payment_settings',
        'menuqr_update_order_status',
        'menuqr_create_qr_template',
        'menuqr_bulk_generate_qr_templates',
        'fqx_submit_customer_manual_payment',
        'fqx_send_whatsapp_bill',
    ];

    foreach ($ajax_actions as $action) {
        add_action('wp_ajax_' . $action, function () use ($action): void {
            fqx_v125_schedule_cache_purge($action);
        }, 1);
        add_action('wp_ajax_nopriv_' . $action, function () use ($action): void {
            // Public checkout/manual payment updates may change bill/payment badges.
            fqx_v125_schedule_cache_purge($action);
        }, 1);
    }
}
add_action('init', 'fqx_v125_register_write_action_hooks', 20);

function fqx_v125_option_write_cache_hooks(string $option): void {
    $option = (string) $option;
    $watch_exact = [
        'menuqr_platform_settings',
        'fqx_platform_payment_settings',
        'fqx_default_plans_version',
        'fqx_schema_version',
        'fluuexqr_v63_marketing_pages_version',
        'fluuexqr_v62_marketing_pages_version',
    ];
    $watch_prefixes = [
        'menuqr_bill_settings_',
        'menuqr_restaurant_',
        'menuqr_review_settings_',
    ];

    if (in_array($option, $watch_exact, true)) {
        fqx_v125_schedule_cache_purge('option_' . sanitize_key($option));
        return;
    }

    foreach ($watch_prefixes as $prefix) {
        if (strpos($option, $prefix) === 0) {
            fqx_v125_schedule_cache_purge('option_' . sanitize_key($prefix));
            return;
        }
    }
}
add_action('updated_option', function ($option, $old_value, $value): void { fqx_v125_option_write_cache_hooks((string) $option); }, 10, 3);
add_action('added_option', function ($option, $value): void { fqx_v125_option_write_cache_hooks((string) $option); }, 10, 2);
add_action('deleted_option', function ($option): void { fqx_v125_option_write_cache_hooks((string) $option); }, 10, 1);

function fqx_v125_post_write_cache_hooks(int $post_id, WP_Post $post, bool $update): void {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (in_array($post->post_type, ['post', 'page'], true)) {
        fqx_v125_schedule_cache_purge($update ? 'post_update' : 'post_save');
    }
}
add_action('save_post', 'fqx_v125_post_write_cache_hooks', 10, 3);
add_action('deleted_post', function (int $post_id): void { fqx_v125_schedule_cache_purge('post_delete'); }, 10, 1);

function fqx_v125_admin_cache_purge_notice(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    $last = get_option('fqx_last_cache_purge', []);
    if (!is_array($last) || empty($last['time'])) {
        return;
    }
    $ts = strtotime((string) $last['time']);
    if (!$ts || (time() - $ts) > 90) {
        return;
    }
    echo '<div class="notice notice-success is-dismissible"><p><strong>FluuexQR cache cleared.</strong> Updated CSS/JS will load instantly with a fresh asset version.</p></div>';
}
add_action('admin_notices', 'fqx_v125_admin_cache_purge_notice');
