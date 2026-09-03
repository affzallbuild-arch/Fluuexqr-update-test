<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v115 patch for the v112 package.
 * Keeps v112 features intact, hard-deletes old default plans, improves Super Admin manual payment review,
 * and loads only small scoped UI/performance assets.
 */

function fqx_v115_allowed_plan_slugs(): array {
    return ['free_trial', 'starter_5_table', 'restaurant_all_access', 'hotel_restaurant_full_access'];
}

function fqx_v115_hard_delete_old_plans(bool $force = false): void {
    if (!$force && (int) get_option('fqx_v115_old_plans_deleted', 0) >= 1) { return; }
    global $wpdb;
    $allowed = fqx_v115_allowed_plan_slugs();
    $allowed_placeholders = implode(',', array_fill(0, count($allowed), '%s'));

    $restaurant_plan_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM " . menuqr_table('subscription_plans') . " WHERE slug=%s LIMIT 1",
        'restaurant_all_access'
    ));
    if ($restaurant_plan_id <= 0 && function_exists('fqx_create_default_plans')) {
        fqx_create_default_plans();
        $restaurant_plan_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . menuqr_table('subscription_plans') . " WHERE slug=%s LIMIT 1",
            'restaurant_all_access'
        ));
    }

    $old_legacy_ids = (array) $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM " . menuqr_table('subscription_plans') . " WHERE slug NOT IN ($allowed_placeholders)",
        $allowed
    ));
    if ($restaurant_plan_id > 0 && !empty($old_legacy_ids)) {
        $id_placeholders = implode(',', array_fill(0, count($old_legacy_ids), '%d'));
        $wpdb->query($wpdb->prepare(
            "UPDATE " . menuqr_table('subscriptions') . " SET plan_id=%d, updated_at=%s WHERE plan_id IN ($id_placeholders)",
            array_merge([$restaurant_plan_id, current_time('mysql')], array_map('intval', $old_legacy_ids))
        ));
    }
    if (!empty($old_legacy_ids)) {
        $id_placeholders = implode(',', array_fill(0, count($old_legacy_ids), '%d'));
        $wpdb->query($wpdb->prepare(
            "UPDATE " . menuqr_table('subscription_payments') . " SET plan_id=%d WHERE plan_id IN ($id_placeholders)",
            array_merge([$restaurant_plan_id], array_map('intval', $old_legacy_ids))
        ));
    }
    $wpdb->query($wpdb->prepare(
        "DELETE FROM " . menuqr_table('subscription_plans') . " WHERE slug NOT IN ($allowed_placeholders)",
        $allowed
    ));

    if (function_exists('fqx_table')) {
        $fqx_restaurant_plan_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM " . fqx_table('plans') . " WHERE slug=%s LIMIT 1", 'restaurant_all_access'));
        $old_fqx_ids = (array) $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM " . fqx_table('plans') . " WHERE slug NOT IN ($allowed_placeholders)",
            $allowed
        ));
        if ($fqx_restaurant_plan_id > 0 && !empty($old_fqx_ids)) {
            $id_placeholders = implode(',', array_fill(0, count($old_fqx_ids), '%d'));
            $wpdb->query($wpdb->prepare("UPDATE " . fqx_table('restaurant_subscriptions') . " SET plan_id=%d, updated_at=%s WHERE plan_id IN ($id_placeholders)", array_merge([$fqx_restaurant_plan_id, current_time('mysql')], array_map('intval', $old_fqx_ids))));
            $wpdb->query($wpdb->prepare("UPDATE " . fqx_table('subscription_payments') . " SET plan_id=%d WHERE plan_id IN ($id_placeholders)", array_merge([$fqx_restaurant_plan_id], array_map('intval', $old_fqx_ids))));
            $wpdb->query($wpdb->prepare("DELETE FROM " . fqx_table('plan_features') . " WHERE plan_id IN ($id_placeholders)", array_map('intval', $old_fqx_ids)));
            $wpdb->query($wpdb->prepare("DELETE FROM " . fqx_table('plan_limits') . " WHERE plan_id IN ($id_placeholders)", array_map('intval', $old_fqx_ids)));
        }
        $wpdb->query($wpdb->prepare(
            "DELETE FROM " . fqx_table('plans') . " WHERE slug NOT IN ($allowed_placeholders)",
            $allowed
        ));
    }

    update_option('fqx_v115_old_plans_deleted', 1, false);
}
add_action('init', 'fqx_v115_hard_delete_old_plans', 20);
add_action('after_switch_theme', 'fqx_v115_hard_delete_old_plans', 30);

function fqx_get_payment_reference($payment): string {
    foreach (['utr_number', 'transaction_reference', 'gateway_payment_id', 'gateway_order_id'] as $key) {
        if (!empty($payment->{$key})) { return (string) $payment->{$key}; }
    }
    return '';
}

function fqx_get_payment_proof_url($payment): string {
    foreach (['screenshot_url', 'proof_file'] as $key) {
        if (!empty($payment->{$key})) { return (string) $payment->{$key}; }
    }
    return '';
}

function fqx_v115_enqueue_superadmin_assets(): void {
    if (is_page_template('page-super-admin.php') || is_page('super-admin-dashboard')) {
        wp_enqueue_style('fqx-v115-superadmin', MENUQR_THEME_URI . '/assets/css/fqx-v115-superadmin.min.css', ['fqx-v112-complete'], menuqr_asset_version('assets/css/fqx-v115-superadmin.min.css'));
    }
}
add_action('wp_enqueue_scripts', 'fqx_v115_enqueue_superadmin_assets', 45);

function fqx_v115_dequeue_heavy_unused_assets(): void {
    if (is_admin()) { return; }

    // IMPORTANT v119: v112 home page sections depend on fq91 JS/CSS for marquee,
    // feature/pricing/testimonial rendering and scroll reveal. Do not dequeue on home.
    if (is_front_page() || is_home() || is_page(['home'])) { return; }

    $slug = get_post_field('post_name', get_queried_object_id());
    $is_menu_flow = in_array($slug, ['menu','cart','checkout','order-status','bill','demo'], true);
    $is_dashboard_flow = in_array($slug, ['restaurant-dashboard','kitchen-dashboard','super-admin-dashboard'], true);
    if (!$is_menu_flow && !$is_dashboard_flow) {
        foreach (['fluuexqr-v91-v6-mobile-fixed-ui','fluuexqr-v101-foodwala-menu-ui','fluuexqr-v104-bill-page-force'] as $handle) {
            wp_dequeue_script($handle);
            wp_dequeue_style($handle);
        }
    }
}
add_action('wp_enqueue_scripts', 'fqx_v115_dequeue_heavy_unused_assets', 120);
