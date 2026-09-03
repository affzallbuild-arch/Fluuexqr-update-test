<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v198 — final mobile button responsiveness + duplicate header menu cleanup + Room WiFi template polish.
 * UI-only patch. Existing workflows, hooks, roles, URLs and tables remain unchanged.
 */

if (!function_exists('fqx_v198_is_dashboard_page')) {
    function fqx_v198_is_dashboard_page(): bool {
        if (is_admin()) { return false; }
        if (function_exists('is_page') && (is_page('restaurant-dashboard') || is_page('super-admin-dashboard') || is_page('kitchen-dashboard') || is_page('bill'))) { return true; }
        if (function_exists('is_page_template') && (is_page_template('page-dashboard.php') || is_page_template('page-super-admin.php') || is_page_template('page-kitchen.php'))) { return true; }
        return false;
    }
}

add_action('wp_enqueue_scripts', function (): void {
    if (!fqx_v198_is_dashboard_page()) { return; }
    $base_uri = defined('MENUQR_THEME_URI') ? MENUQR_THEME_URI : get_template_directory_uri();
    $base_dir = defined('MENUQR_THEME_DIR') ? MENUQR_THEME_DIR : get_template_directory();
    $css_rel = 'assets/css/fqx-v198-mobile-responsive-room-wifi-final.css';
    $js_rel  = 'assets/js/fqx-v198-mobile-responsive-room-wifi-final.js';
    wp_enqueue_style('fqx-v198-mobile-responsive-room-wifi-final', $base_uri . '/' . $css_rel, ['fqx-v197-responsive-pdf-wifi-fixes'], file_exists($base_dir . '/' . $css_rel) ? (string) filemtime($base_dir . '/' . $css_rel) : '198');
    wp_enqueue_script('fqx-v198-mobile-responsive-room-wifi-final', $base_uri . '/' . $js_rel, ['fqx-v197-responsive-pdf-wifi-fixes'], file_exists($base_dir . '/' . $js_rel) ? (string) filemtime($base_dir . '/' . $js_rel) : '198', true);
}, 150);

add_filter('script_loader_tag', function (string $tag, string $handle, string $src): string {
    if ('fqx-v198-mobile-responsive-room-wifi-final' === $handle) {
        return '<script src="' . esc_url($src) . '" defer></script>';
    }
    return $tag;
}, 10, 3);
