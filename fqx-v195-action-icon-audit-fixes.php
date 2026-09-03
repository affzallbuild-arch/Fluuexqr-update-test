<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v195 code-level audit fixes.
 * Scope: missing direct handlers, icon/tap reliability, category-type smoothness.
 * Existing flows, tables, hooks, URLs, menus and roles are preserved.
 */

if (!function_exists('fqx_v195_safe_admin_notice_redirect')) {
    function fqx_v195_safe_admin_notice_redirect(string $tab, array $args = []): void {
        nocache_headers();
        $url = function_exists('menuqr_admin_tab_url') ? menuqr_admin_tab_url($tab) : home_url('/');
        wp_safe_redirect(add_query_arg(array_merge($args, ['mq_ts' => time()]), $url));
        exit;
    }
}

// Direct admin-post hook for bill settings. Earlier it worked through the front dispatcher,
// but admin-post submission needs a direct hook to avoid one-click/save inconsistency.
add_action('admin_post_menuqr_save_bill_settings_form', function (): void {
    if (function_exists('menuqr_handle_save_bill_settings_form')) {
        menuqr_handle_save_bill_settings_form();
    }
});

// Super Admin delete plan form existed but had no direct handler in the audited package.
// This handler is intentionally safe: it blocks deletion when a plan is already used.
add_action('admin_post_menuqr_delete_plan', function (): void {
    if (function_exists('menuqr_require_role')) {
        menuqr_require_role(['super_admin']);
    } elseif (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'menuqr'));
    }

    if (function_exists('menuqr_require_post_nonce')) {
        menuqr_require_post_nonce('menuqr_delete_plan', 'menuqr_delete_plan_nonce');
    } else {
        check_admin_referer('menuqr_delete_plan_nonce', 'menuqr_delete_plan');
    }

    global $wpdb;
    $plan_id = absint($_POST['plan_id'] ?? 0);
    if ($plan_id <= 0 || !function_exists('menuqr_table')) {
        fqx_v195_safe_admin_notice_redirect('plans', ['mq_notice' => 'plan_delete_error']);
    }

    $plans_table = menuqr_table('subscription_plans');
    $subscriptions_table = menuqr_table('subscriptions');
    $payments_table = menuqr_table('subscription_payments');

    $plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$plans_table} WHERE id = %d LIMIT 1", $plan_id));
    if (!$plan) {
        fqx_v195_safe_admin_notice_redirect('plans', ['mq_notice' => 'plan_not_found']);
    }

    $used_subscriptions = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$subscriptions_table} WHERE plan_id = %d", $plan_id));
    $used_payments = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$payments_table} WHERE plan_id = %d", $plan_id));
    if ($used_subscriptions > 0 || $used_payments > 0) {
        fqx_v195_safe_admin_notice_redirect('plans', ['mq_notice' => 'plan_delete_blocked']);
    }

    $ok = false !== $wpdb->delete($plans_table, ['id' => $plan_id], ['%d']);

    // Keep mirror fqx plan table in sync only when present and not linked.
    if ($ok && function_exists('fqx_table') && !empty($plan->slug)) {
        $fqx_plans_table = fqx_table('plans');
        $fqx_plan_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$fqx_plans_table} WHERE slug = %s LIMIT 1", (string) $plan->slug));
        if ($fqx_plan_id > 0) {
            $wpdb->delete($fqx_plans_table, ['id' => $fqx_plan_id], ['%d']);
        }
    }

    if (function_exists('menuqr_clear_runtime_cache')) { menuqr_clear_runtime_cache(); }
    fqx_v195_safe_admin_notice_redirect('plans', ['mq_notice' => $ok ? 'plan_deleted' : 'plan_delete_error']);
});

add_action('wp_enqueue_scripts', function (): void {
    $is_admin_dash = is_page('restaurant-dashboard') || is_page('super-admin-dashboard') || is_page('kitchen-dashboard') || is_page_template('page-restaurant-dashboard.php') || is_page_template('page-super-admin.php') || is_page_template('page-kitchen.php');
    if (!$is_admin_dash) { return; }

    $base = defined('MENUQR_THEME_URI') ? MENUQR_THEME_URI : get_template_directory_uri();
    $ver = function_exists('menuqr_asset_version') ? menuqr_asset_version('assets/js/fqx-v195-action-icon-audit-fixes.js') : '195';
    wp_enqueue_script('fqx-v195-action-icon-audit-fixes', $base . '/assets/js/fqx-v195-action-icon-audit-fixes.js', [], $ver, true);
    wp_enqueue_style('fqx-v195-action-icon-audit-fixes', $base . '/assets/css/fqx-v195-action-icon-audit-fixes.css', [], function_exists('menuqr_asset_version') ? menuqr_asset_version('assets/css/fqx-v195-action-icon-audit-fixes.css') : '195');
}, 99);
