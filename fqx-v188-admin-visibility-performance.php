<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v188: Restaurant Admin visibility/contrast/responsiveness/performance-only patch.
 * Does not change workflow, routes, actions, roles, slugs or existing callbacks.
 */

function fqx_v188_is_dashboard_page(): bool {
    if (is_admin()) { return false; }
    return is_page_template('page-dashboard.php')
        || is_page_template('page-super-admin.php')
        || is_page('restaurant-dashboard')
        || is_page('super-admin-dashboard')
        || is_page('kitchen-dashboard');
}

function fqx_v188_enqueue_admin_visibility_assets(): void {
    if (!fqx_v188_is_dashboard_page()) { return; }

    $css_rel = 'assets/css/fqx-v188-admin-visibility-performance.css';
    $js_rel  = 'assets/js/fqx-v188-admin-performance.js';

    if (defined('MENUQR_THEME_URI') && defined('MENUQR_THEME_DIR')) {
        $css_ver = file_exists(MENUQR_THEME_DIR . '/' . $css_rel) ? (string) filemtime(MENUQR_THEME_DIR . '/' . $css_rel) : (function_exists('menuqr_asset_version') ? menuqr_asset_version($css_rel) : '1.0.0');
        $js_ver  = file_exists(MENUQR_THEME_DIR . '/' . $js_rel) ? (string) filemtime(MENUQR_THEME_DIR . '/' . $js_rel) : (function_exists('menuqr_asset_version') ? menuqr_asset_version($js_rel) : '1.0.0');
        wp_enqueue_style('fqx-v188-admin-visibility-performance', MENUQR_THEME_URI . '/' . $css_rel, [], $css_ver);
        wp_enqueue_script('fqx-v188-admin-performance', MENUQR_THEME_URI . '/' . $js_rel, [], $js_ver, true);
    }
}
add_action('wp_enqueue_scripts', 'fqx_v188_enqueue_admin_visibility_assets', 999);

function fqx_v188_defer_admin_performance_script($tag, $handle, $src) {
    if ($handle === 'fqx-v188-admin-performance') {
        return '<script src="' . esc_url($src) . '" defer></script>';
    }
    return $tag;
}
add_filter('script_loader_tag', 'fqx_v188_defer_admin_performance_script', 10, 3);

function fqx_v188_dashboard_body_class(array $classes): array {
    if (fqx_v188_is_dashboard_page()) {
        $classes[] = 'fqx-v188-visibility-fix';
    }
    return $classes;
}
add_filter('body_class', 'fqx_v188_dashboard_body_class');

/**
 * Safe, non-destructive index helper. Adds indexes once, only if the table/columns exist.
 * This improves large multi-restaurant installations without changing any workflow.
 */
function fqx_v188_column_exists(string $table, string $column): bool {
    global $wpdb;
    $found = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
    return !empty($found);
}

function fqx_v188_index_exists(string $table, string $index_name): bool {
    global $wpdb;
    $found = $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$table} WHERE Key_name = %s", $index_name));
    return !empty($found);
}

function fqx_v188_add_index_if_possible(string $table, string $index_name, array $columns): void {
    global $wpdb;
    if (empty($table) || empty($columns) || fqx_v188_index_exists($table, $index_name)) { return; }
    foreach ($columns as $column) {
        if (!fqx_v188_column_exists($table, $column)) { return; }
    }
    $safe_index = preg_replace('/[^A-Za-z0-9_]/', '', $index_name);
    $safe_cols = array_map(static function($col) { return '`' . preg_replace('/[^A-Za-z0-9_]/', '', $col) . '`'; }, $columns);
    if (!$safe_index || empty($safe_cols)) { return; }
    $wpdb->query("ALTER TABLE {$table} ADD INDEX {$safe_index} (" . implode(',', $safe_cols) . ")");
}

function fqx_v188_optimize_indexes_once(): void {
    if ((int) get_option('fqx_v188_indexes_added', 0) >= 1) { return; }
    if (!function_exists('menuqr_table')) { return; }

    $map = [
        'orders' => [
            ['idx_fqx_o_rest_created', ['restaurant_id','created_at']],
            ['idx_fqx_o_rest_status', ['restaurant_id','status']],
            ['idx_fqx_o_rest_payment', ['restaurant_id','payment_status']],
            ['idx_fqx_o_table', ['table_id']],
            ['idx_fqx_o_room', ['room_id']],
        ],
        'bills' => [
            ['idx_fqx_b_rest_created', ['restaurant_id','created_at']],
            ['idx_fqx_b_rest_status', ['restaurant_id','status']],
            ['idx_fqx_b_rest_payment', ['restaurant_id','payment_status']],
            ['idx_fqx_b_order', ['order_id']],
        ],
        'order_items' => [
            ['idx_fqx_oi_order', ['order_id']],
            ['idx_fqx_oi_rest', ['restaurant_id']],
        ],
        'subscriptions' => [
            ['idx_fqx_s_rest', ['restaurant_id']],
            ['idx_fqx_s_plan_status', ['plan_id','status']],
        ],
        'subscription_payments' => [
            ['idx_fqx_sp_rest_created', ['restaurant_id','created_at']],
            ['idx_fqx_sp_status_created', ['status','created_at']],
            ['idx_fqx_sp_plan', ['plan_id']],
        ],
        'review_clicks' => [
            ['idx_fqx_r_rest_created', ['restaurant_id','created_at']],
        ],
        'restaurants' => [
            ['idx_fqx_r_status', ['status']],
            ['idx_fqx_r_sub_status', ['subscription_status']],
        ],
    ];

    foreach ($map as $slug => $indexes) {
        $table = menuqr_table($slug);
        if (!$table) { continue; }
        foreach ($indexes as $index) {
            fqx_v188_add_index_if_possible($table, $index[0], $index[1]);
        }
    }

    update_option('fqx_v188_indexes_added', 1, false);
}
add_action('init', 'fqx_v188_optimize_indexes_once', 60);

/**
 * Lightweight cache helper for dashboard summary counts.
 */
function fqx_v188_cache_key(string $key, int $restaurant_id = 0): string {
    return 'fqx_v188_' . sanitize_key($key) . '_' . absint($restaurant_id);
}

function fqx_v188_get_cached(string $key, int $restaurant_id, callable $callback, int $ttl = 60) {
    $cache_key = fqx_v188_cache_key($key, $restaurant_id);
    $cached = get_transient($cache_key);
    if ($cached !== false) { return $cached; }
    $value = $callback();
    set_transient($cache_key, $value, max(20, $ttl));
    return $value;
}

function fqx_v188_clear_restaurant_cache(int $restaurant_id = 0): void {
    global $wpdb;
    $restaurant_id = absint($restaurant_id);
    $like = $wpdb->esc_like('_transient_fqx_v188_') . '%';
    if ($restaurant_id > 0) {
        $like = $wpdb->esc_like('_transient_fqx_v188_') . '%' . $restaurant_id;
    }
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like, str_replace('_transient_', '_transient_timeout_', $like)));
}
add_action('menuqr_after_order_update', 'fqx_v188_clear_restaurant_cache', 10, 1);
add_action('menuqr_after_bill_update', 'fqx_v188_clear_restaurant_cache', 10, 1);
add_action('menuqr_after_payment_update', 'fqx_v188_clear_restaurant_cache', 10, 1);

function fqx_v188_post_action_cache_clear(): void {
    $restaurant_id = 0;
    foreach (['restaurant_id','rid','current_restaurant_id'] as $key) {
        if (isset($_POST[$key])) { $restaurant_id = absint($_POST[$key]); break; }
    }
    fqx_v188_clear_restaurant_cache($restaurant_id);
}
add_action('admin_post_menuqr_mark_bill_paid', 'fqx_v188_post_action_cache_clear', 99);
add_action('admin_post_menuqr_mark_bill_unpaid', 'fqx_v188_post_action_cache_clear', 99);
add_action('admin_post_menuqr_save_payment_settings', 'fqx_v188_post_action_cache_clear', 99);
add_action('admin_post_menuqr_save_bill_branding', 'fqx_v188_post_action_cache_clear', 99);
