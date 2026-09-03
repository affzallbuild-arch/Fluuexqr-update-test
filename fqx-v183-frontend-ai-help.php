<?php
/**
 * FluuexQR v183 — frontend AI support chatbot fix and admin menu cleanup.
 */
if (!defined('ABSPATH')) { exit; }

function fqx_v183_is_frontend_ai_context(): bool {
    if (is_admin()) { return false; }
    $is_dashboard = function_exists('menuqr_is_dashboard_context') ? menuqr_is_dashboard_context() : (function_exists('is_page') && is_page(['restaurant-dashboard','super-admin-dashboard','kitchen-dashboard']));
    $is_customer_menu = function_exists('menuqr_is_customer_menu_context') ? menuqr_is_customer_menu_context() : (function_exists('is_page') && is_page(['menu','cart','checkout','bill','order-status']));
    return !$is_dashboard && !$is_customer_menu;
}

function fqx_v183_enqueue_frontend_ai_help(): void {
    if (!fqx_v183_is_frontend_ai_context()) { return; }
    $css = get_template_directory() . '/assets/css/fqx-v183-frontend-ai-help.css';
    $js  = get_template_directory() . '/assets/js/fqx-v183-frontend-ai-help.js';
    if (file_exists($css)) {
        wp_enqueue_style('fqx-v183-frontend-ai-help', get_template_directory_uri() . '/assets/css/fqx-v183-frontend-ai-help.css', [], filemtime($css));
    }
    if (file_exists($js)) {
        wp_enqueue_script('fqx-v183-frontend-ai-help', get_template_directory_uri() . '/assets/js/fqx-v183-frontend-ai-help.js', [], filemtime($js), true);
    }
}
add_action('wp_enqueue_scripts', 'fqx_v183_enqueue_frontend_ai_help', 10000);

function fqx_v183_hide_admin_support_routes(): void {
    if (is_admin() || !function_exists('is_page') || !is_page('restaurant-dashboard')) { return; }
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash((string) $_GET['tab'])) : '';
    if (in_array($tab, ['whatsapp','whatsapp-settings','ai-support','fluuexqr-ai-support','support'], true)) {
        wp_safe_redirect(add_query_arg(['tab' => 'dashboard'], function_exists('menuqr_get_page_url_by_slug') ? menuqr_get_page_url_by_slug('restaurant-dashboard') : home_url('/restaurant-dashboard/')), 302);
        exit;
    }
}
add_action('template_redirect', 'fqx_v183_hide_admin_support_routes', 0);
