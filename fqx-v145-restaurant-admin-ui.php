<?php
/**
 * FluuexQR v145 — Premium Restaurant Admin UI polish.
 * Scope: UI/UX only. Keeps existing backend workflow, forms, fields, nonces, URLs and AJAX actions intact.
 */
if (!defined('ABSPATH')) { exit; }

function fqx_v145_is_restaurant_admin_ui(): bool {
    if (is_admin()) { return false; }
    if (function_exists('is_page') && is_page('restaurant-dashboard')) { return true; }
    if (function_exists('is_page_template') && is_page_template('page-dashboard.php')) { return true; }
    if (is_singular()) {
        $content = (string) get_post_field('post_content', get_queried_object_id());
        if ($content && has_shortcode($content, 'menuqr_dashboard')) { return true; }
    }
    return false;
}

function fqx_v145_enqueue_restaurant_admin_ui(): void {
    if (!fqx_v145_is_restaurant_admin_ui()) { return; }
    $css_rel = file_exists(get_template_directory() . '/assets/css/restaurant-admin.min.css') ? 'assets/css/restaurant-admin.min.css' : 'assets/css/restaurant-admin.css';
    $js_rel  = file_exists(get_template_directory() . '/assets/js/restaurant-admin.min.js') ? 'assets/js/restaurant-admin.min.js' : 'assets/js/restaurant-admin.js';
    $css = get_template_directory() . '/' . $css_rel;
    $js  = get_template_directory() . '/' . $js_rel;
    wp_enqueue_style('fqx-restaurant-admin-v145', get_template_directory_uri() . '/' . $css_rel, [], file_exists($css) ? (string) filemtime($css) : '145');
    wp_enqueue_script('fqx-restaurant-admin-v145', get_template_directory_uri() . '/' . $js_rel, [], file_exists($js) ? (string) filemtime($js) : '145', true);
}
add_action('wp_enqueue_scripts', 'fqx_v145_enqueue_restaurant_admin_ui', 5000);

function fqx_v145_body_classes(array $classes): array {
    if (fqx_v145_is_restaurant_admin_ui()) {
        $classes[] = 'fqx-v145-restaurant-admin';
        $classes[] = 'fqx-admin-premium-ui';
    }
    return $classes;
}
add_filter('body_class', 'fqx_v145_body_classes', 50);

function fqx_v145_defer_admin_ui_script(string $tag, string $handle, string $src): string {
    if ('fqx-restaurant-admin-v145' === $handle) {
        return '<script src="' . esc_url($src) . '" defer></script>';
    }
    return $tag;
}
add_filter('script_loader_tag', 'fqx_v145_defer_admin_ui_script', 10, 3);
