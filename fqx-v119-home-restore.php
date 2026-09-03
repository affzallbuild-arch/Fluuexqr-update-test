<?php
if (!defined('ABSPATH')) { exit; }

function fqx_v119_home_restore_assets(): void {
    if (!is_front_page() && !is_page('home')) { return; }

    // Keep the original v112 homepage assets active on the homepage.
    wp_enqueue_style('fluuexqr-v91-v6-mobile-fixed-ui', MENUQR_THEME_URI . '/assets/css/fq91-v6-mobile-fixed.css', ['fluuexqr-v90-preview-ui'], menuqr_asset_version('assets/css/fq91-v6-mobile-fixed.css'));
    wp_enqueue_script('fluuexqr-v91-v6-mobile-fixed-ui', MENUQR_THEME_URI . '/assets/js/fq91-v6-mobile-fixed.js', [], menuqr_asset_version('assets/js/fq91-v6-mobile-fixed.js'), true);

    wp_enqueue_style('fqx-v119-home-restore', MENUQR_THEME_URI . '/assets/css/fqx-v119-home-restore.min.css', ['fluuexqr-v91-v6-mobile-fixed-ui'], menuqr_asset_version('assets/css/fqx-v119-home-restore.min.css'));
    wp_enqueue_script('fqx-v119-home-restore', MENUQR_THEME_URI . '/assets/js/fqx-v119-home-restore.js', ['fluuexqr-v91-v6-mobile-fixed-ui'], menuqr_asset_version('assets/js/fqx-v119-home-restore.js'), true);
}
add_action('wp_enqueue_scripts', 'fqx_v119_home_restore_assets', 999);

function fqx_v119_force_home_template_fix(): void {
    if ((int) get_option('fqx_v119_home_template_fixed', 0) >= 1) { return; }
    $home = get_page_by_path('home');
    if ($home instanceof WP_Post) {
        update_post_meta($home->ID, '_wp_page_template', 'front-page.php');
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home->ID);
    }
    update_option('fqx_v119_home_template_fixed', 1, false);
}
add_action('after_switch_theme', 'fqx_v119_force_home_template_fix', 30);
add_action('init', 'fqx_v119_force_home_template_fix', 30);
