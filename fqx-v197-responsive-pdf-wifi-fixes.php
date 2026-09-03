<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v197 — responsive button/icon fixes + bill PDF click reliability + WiFi template selection UI.
 * Scope: UI, responsiveness, click reliability, safe settings only. Existing workflows unchanged.
 */

if (!function_exists('fqx_v197_is_dashboard_page')) {
    function fqx_v197_is_dashboard_page(): bool {
        if (is_admin()) { return false; }
        if (function_exists('is_page') && (is_page('restaurant-dashboard') || is_page('super-admin-dashboard') || is_page('kitchen-dashboard') || is_page('bill'))) { return true; }
        if (function_exists('is_page_template') && (is_page_template('page-dashboard.php') || is_page_template('page-super-admin.php') || is_page_template('page-kitchen.php'))) { return true; }
        return false;
    }
}

if (!function_exists('fqx_v197_wifi_template_key')) {
    function fqx_v197_wifi_template_key(int $restaurant_id): string {
        return 'fqx_v197_wifi_qr_template_' . max(0, $restaurant_id);
    }
}

if (!function_exists('fqx_v197_get_wifi_template')) {
    function fqx_v197_get_wifi_template(int $restaurant_id): string {
        $template = sanitize_key((string) get_option(fqx_v197_wifi_template_key($restaurant_id), 'hotel_neon_206'));
        $legacy_map = [
            'wifi_clean' => 'corporate_clean',
            'wifi_luxury' => 'hotel_neon_206',
            'wifi_minimal' => 'cream_205',
            'wifi_room_card' => 'royal_201',
        ];
        if (isset($legacy_map[$template])) { $template = $legacy_map[$template]; }
        $allowed = array_keys(fqx_v197_wifi_templates());
        return in_array($template, $allowed, true) ? $template : 'hotel_neon_206';
    }
}

if (!function_exists('fqx_v197_wifi_templates')) {
    function fqx_v197_wifi_templates(): array {
        return [
            'hotel_neon_206' => ['name' => 'Neon Hotel QR', 'type' => 'Room 206 Split Menu + WiFi', 'icon' => '🌃', 'room' => '206'],
            'marble_207' => ['name' => 'Black Marble QR', 'type' => 'Room 207 Luxury Marble', 'icon' => '🖤', 'room' => '207'],
            'royal_201' => ['name' => 'Royal Premium QR', 'type' => 'Room 201 Gold Accent', 'icon' => '👑', 'room' => '201'],
            'classic_209' => ['name' => 'Classic Tall QR', 'type' => 'Room 209 Vertical Layout', 'icon' => '🏨', 'room' => '209'],
            'blue_gold_204' => ['name' => 'Blue Gold QR', 'type' => 'Room 204 Elite Blue', 'icon' => '🔷', 'room' => '204'],
            'elite_208' => ['name' => 'Elite Dark QR', 'type' => 'Room 208 Dark Orange', 'icon' => '✨', 'room' => '208'],
            'cream_205' => ['name' => 'Cream Luxury QR', 'type' => 'Room 205 Light Premium', 'icon' => '☕', 'room' => '205'],
            'corporate_clean' => ['name' => 'Clean Corporate QR', 'type' => 'Business Hotel Clean', 'icon' => '📶', 'room' => '210'],
        ];
    }
}

add_action('wp_enqueue_scripts', function (): void {
    if (!fqx_v197_is_dashboard_page()) { return; }
    $base_uri = defined('MENUQR_THEME_URI') ? MENUQR_THEME_URI : get_template_directory_uri();
    $base_dir = defined('MENUQR_THEME_DIR') ? MENUQR_THEME_DIR : get_template_directory();
    $css_rel = 'assets/css/fqx-v197-responsive-pdf-wifi-fixes.css';
    $js_rel  = 'assets/js/fqx-v197-responsive-pdf-wifi-fixes.js';
    wp_enqueue_style('fqx-v197-responsive-pdf-wifi-fixes', $base_uri . '/' . $css_rel, ['fqx-v196-admin-responsive-button-audit'], file_exists($base_dir . '/' . $css_rel) ? (string) filemtime($base_dir . '/' . $css_rel) : '197');
    wp_enqueue_script('fqx-v197-responsive-pdf-wifi-fixes', $base_uri . '/' . $js_rel, ['fqx-v196-admin-responsive-button-audit'], file_exists($base_dir . '/' . $js_rel) ? (string) filemtime($base_dir . '/' . $js_rel) : '197', true);
}, 130);

add_filter('script_loader_tag', function (string $tag, string $handle, string $src): string {
    if ('fqx-v197-responsive-pdf-wifi-fixes' === $handle) {
        return '<script src="' . esc_url($src) . '" defer></script>';
    }
    return $tag;
}, 10, 3);

add_action('admin_post_fqx_v197_save_wifi_template', function (): void {
    if (!is_user_logged_in()) { wp_die('Login required.'); }
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $current_restaurant_id = function_exists('menuqr_get_current_restaurant_id') ? (int) menuqr_get_current_restaurant_id() : 0;
    if (!$restaurant_id) { $restaurant_id = $current_restaurant_id; }
    if (!$restaurant_id || ($current_restaurant_id && $restaurant_id !== $current_restaurant_id && !current_user_can('manage_options'))) {
        wp_die('Permission denied.');
    }
    check_admin_referer('fqx_v197_save_wifi_template', 'fqx_v197_wifi_template_nonce');
    $template = sanitize_key((string) ($_POST['wifi_qr_template'] ?? 'wifi_clean'));
    $allowed = array_keys(fqx_v197_wifi_templates());
    if (!in_array($template, $allowed, true)) { $template = 'hotel_neon_206'; }
    update_option(fqx_v197_wifi_template_key($restaurant_id), $template, false);
    $redirect = function_exists('menuqr_restaurant_tab_url') ? menuqr_restaurant_tab_url('rooms') : wp_get_referer();
    $redirect = add_query_arg(['section' => 'templates', 'template_tab' => 'wifi', 'wifi_template_saved' => '1', '_cb' => time()], $redirect);
    wp_safe_redirect($redirect);
    exit;
});
