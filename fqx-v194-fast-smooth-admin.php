<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v194: Fast/smooth admin hotfix.
 * Scope: cache bypass for dashboards, bill one-click reliability, category smoothness,
 * visibility/responsive CSS, safe indexes and cache clearing.
 * No workflow/table/function/action/slug/role rename.
 */

if (!function_exists('fqx_v194_is_admin_dashboard_context')) {
    function fqx_v194_is_admin_dashboard_context(): bool {
        if (is_admin()) { return false; }
        return is_page('restaurant-dashboard') || is_page('super-admin-dashboard') || is_page('kitchen-dashboard') || is_page_template('page-dashboard.php') || is_page_template('page-super-admin.php');
    }
}

if (!function_exists('fqx_v194_no_cache_dashboard_pages')) {
    function fqx_v194_no_cache_dashboard_pages(): void {
        if (!fqx_v194_is_admin_dashboard_context()) { return; }
        if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
        if (!defined('DONOTCDN')) { define('DONOTCDN', true); }
        nocache_headers();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    }
}
add_action('template_redirect', 'fqx_v194_no_cache_dashboard_pages', 0);
add_action('send_headers', 'fqx_v194_no_cache_dashboard_pages', 0);

if (!function_exists('fqx_v194_enqueue_assets')) {
    function fqx_v194_enqueue_assets(): void {
        if (!fqx_v194_is_admin_dashboard_context()) { return; }
        $css_rel = 'assets/css/fqx-v194-fast-smooth-admin.css';
        $js_rel = 'assets/js/fqx-v194-fast-smooth-admin.js';
        $dir = get_template_directory();
        $uri = get_template_directory_uri();
        $css_ver = file_exists($dir . '/' . $css_rel) ? (string) filemtime($dir . '/' . $css_rel) : '194';
        $js_ver = file_exists($dir . '/' . $js_rel) ? (string) filemtime($dir . '/' . $js_rel) : '194';
        wp_enqueue_style('fqx-v194-fast-smooth-admin', $uri . '/' . $css_rel, [], $css_ver);
        wp_enqueue_script('fqx-v194-fast-smooth-admin', $uri . '/' . $js_rel, [], $js_ver, true);
    }
}
add_action('wp_enqueue_scripts', 'fqx_v194_enqueue_assets', 1000);

if (!function_exists('fqx_v194_defer_script')) {
    function fqx_v194_defer_script($tag, $handle, $src) {
        if ($handle === 'fqx-v194-fast-smooth-admin') {
            return '<script src="' . esc_url($src) . '" defer></script>';
        }
        return $tag;
    }
}
add_filter('script_loader_tag', 'fqx_v194_defer_script', 10, 3);

if (!function_exists('fqx_v194_clear_runtime_cache')) {
    function fqx_v194_clear_runtime_cache(int $restaurant_id = 0): void {
        if (function_exists('fqx_v188_clear_restaurant_cache')) { fqx_v188_clear_restaurant_cache($restaurant_id); }
        if (function_exists('menuqr_clear_runtime_cache')) { menuqr_clear_runtime_cache(); }
        wp_cache_flush_runtime();
    }
}

if (!function_exists('fqx_v194_pre_action_clear')) {
    function fqx_v194_pre_action_clear(): void {
        $restaurant_id = 0;
        foreach (['restaurant_id','rid','current_restaurant_id'] as $key) {
            if (isset($_POST[$key])) { $restaurant_id = absint($_POST[$key]); break; }
        }
        if (!$restaurant_id && function_exists('menuqr_get_current_restaurant_id')) {
            $restaurant_id = (int) menuqr_get_current_restaurant_id();
        }
        fqx_v194_clear_runtime_cache($restaurant_id);
    }
}

foreach ([
    'menuqr_save_category','menuqr_delete_category','fqx_save_category_type','fqx_delete_category_type',
    'menuqr_save_item','menuqr_delete_item','menuqr_duplicate_item','menuqr_toggle_item_availability',
    'menuqr_mark_bill_payment','menuqr_close_bill_session','menuqr_save_bill_settings_form','menuqr_save_payment_settings'
] as $fqx_v194_action) {
    add_action('admin_post_' . $fqx_v194_action, 'fqx_v194_pre_action_clear', 1);
}

if (!function_exists('fqx_v194_add_cachebuster_to_dashboard_redirect')) {
    function fqx_v194_add_cachebuster_to_dashboard_redirect($location, $status) {
        if (!$location || false === strpos($location, 'restaurant-dashboard')) { return $location; }
        if (false !== strpos($location, '_fqxv=')) { return $location; }
        return add_query_arg('_fqxv', time(), $location);
    }
}
add_filter('wp_redirect', 'fqx_v194_add_cachebuster_to_dashboard_redirect', 10, 2);

if (!function_exists('fqx_v194_column_exists')) {
    function fqx_v194_column_exists(string $table, string $column): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
    }
}
if (!function_exists('fqx_v194_index_exists')) {
    function fqx_v194_index_exists(string $table, string $index): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$table} WHERE Key_name = %s", $index));
    }
}
if (!function_exists('fqx_v194_add_index')) {
    function fqx_v194_add_index(string $table, string $index, array $columns): void {
        global $wpdb;
        if (!$table || !$index || fqx_v194_index_exists($table, $index)) { return; }
        foreach ($columns as $col) { if (!fqx_v194_column_exists($table, $col)) { return; } }
        $safe_index = preg_replace('/[^A-Za-z0-9_]/', '', $index);
        $safe_cols = array_map(static fn($c) => '`' . preg_replace('/[^A-Za-z0-9_]/', '', $c) . '`', $columns);
        $wpdb->query("ALTER TABLE {$table} ADD INDEX {$safe_index} (" . implode(',', $safe_cols) . ")");
    }
}
if (!function_exists('fqx_v194_add_fast_indexes_once')) {
    function fqx_v194_add_fast_indexes_once(): void {
        if ((int) get_option('fqx_v194_fast_indexes_added', 0) >= 1 || !function_exists('menuqr_table')) { return; }
        $indexes = [
            [menuqr_table('categories'), 'idx_fqx_cat_rest_sort', ['restaurant_id','sort_order']],
            [menuqr_table('categories'), 'idx_fqx_cat_rest_name', ['restaurant_id','name']],
            [menuqr_table('items'), 'idx_fqx_items_rest_cat', ['restaurant_id','category_id']],
            [menuqr_table('items'), 'idx_fqx_items_rest_cat_type', ['restaurant_id','category_id','category_type_id']],
            [menuqr_table('bills'), 'idx_fqx_bills_rest_created_id', ['restaurant_id','created_at','id']],
            [menuqr_table('orders'), 'idx_fqx_orders_rest_created_id', ['restaurant_id','created_at','id']],
            [menuqr_table('orders'), 'idx_fqx_orders_rest_bill', ['restaurant_id','bill_id']],
        ];
        if (function_exists('fqx_v191_category_types_table')) {
            $indexes[] = [fqx_v191_category_types_table(), 'idx_fqx_cat_type_rest_cat_sort', ['restaurant_id','category_id','sort_order']];
            $indexes[] = [fqx_v191_category_types_table(), 'idx_fqx_cat_type_rest_name', ['restaurant_id','name']];
        }
        foreach ($indexes as $idx) { fqx_v194_add_index($idx[0], $idx[1], $idx[2]); }
        update_option('fqx_v194_fast_indexes_added', 1, false);
    }
}
add_action('init', 'fqx_v194_add_fast_indexes_once', 80);

if (!function_exists('fqx_v194_admin_notice_cache_hint')) {
    function fqx_v194_admin_notice_cache_hint(): void {
        if (!fqx_v194_is_admin_dashboard_context()) { return; }
        echo "\n<!-- FluuexQR v194 fast-smooth admin active: nocache dashboard, cache-buster redirects, category/bill speed fixes -->\n";
    }
}
add_action('wp_footer', 'fqx_v194_admin_notice_cache_hint', 999);
