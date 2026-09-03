<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v69 final compatibility fixes.
 * Keeps original working login/signup pages and the user's CF7 contact shortcode.
 */
function fluuexqr_v69_restore_core_pages(): void {
    $pages = [
        'login' => ['Login', '[menuqr_login]', 'page-login.php'],
        'signup' => ['Signup', '[menuqr_signup]', 'page-signup.php'],
        'contact' => ['Contact', '[contact-form-7 id="ee266f0" title="Contact form 1"]', 'page-contact.php'],
        'contact-us' => ['Contact Us', '[contact-form-7 id="ee266f0" title="Contact form 1"]', 'page-contact.php'],
    ];
    foreach ($pages as $slug => $page) {
        $existing = get_page_by_path($slug);
        $args = [
            'post_title' => $page[0],
            'post_name' => $slug,
            'post_content' => $page[1],
            'post_status' => 'publish',
            'post_type' => 'page',
        ];
        if ($existing instanceof WP_Post) {
            $args['ID'] = $existing->ID;
            wp_update_post($args);
            update_post_meta($existing->ID, '_wp_page_template', $page[2]);
        } else {
            $args['meta_input'] = ['_wp_page_template' => $page[2]];
            wp_insert_post($args);
        }
    }
}

function fluuexqr_v69_apply_core_pages_once(): void {
    $version = '1.3.69';
    if (get_option('fluuexqr_v69_core_pages_version') === $version) { return; }
    fluuexqr_v69_restore_core_pages();
    update_option('fluuexqr_v69_core_pages_version', $version, false);
}
add_action('init', 'fluuexqr_v69_apply_core_pages_once', 99);
add_action('after_switch_theme', 'fluuexqr_v69_restore_core_pages', 99);
