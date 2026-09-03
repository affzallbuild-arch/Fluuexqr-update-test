<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v131 Tracker Stability Patch
 * - Removes top blank space on customer tracker panel.
 * - Stops Pending/Running header flicker by showing a stable "Running Order" label.
 * - Keeps 4-hour tracker and order-wise cards from v129/v130.
 */

function fqx_v131_enqueue_tracker_stable_assets(): void {
    if (!is_page(['menu', 'bill', 'order-status', 'cart', 'checkout']) && !is_page_template('page-menu.php')) { return; }

    // v130 script was fixing bill/tracker, but its open handler can create scroll jumps.
    // Keep v130 PHP/AJAX backend, replace only frontend tracker behavior.
    wp_dequeue_script('fqx-v129-menu-bill-tracker-sync');
    wp_dequeue_style('fqx-v129-menu-bill-tracker-sync');
    wp_dequeue_script('fqx-v130-customer-bill-tracker-fix');
    wp_dequeue_style('fqx-v130-customer-bill-tracker-fix');

    $js  = get_template_directory() . '/assets/js/fqx-v131-tracker-stable.js';
    $css = get_template_directory() . '/assets/css/fqx-v131-tracker-stable.css';

    wp_enqueue_style(
        'fqx-v131-tracker-stable',
        get_template_directory_uri() . '/assets/css/fqx-v131-tracker-stable.css',
        [],
        file_exists($css) ? (string) filemtime($css) : '131'
    );

    wp_enqueue_script(
        'fqx-v131-tracker-stable',
        get_template_directory_uri() . '/assets/js/fqx-v131-tracker-stable.js',
        ['jquery'],
        file_exists($js) ? (string) filemtime($js) : '131',
        true
    );
}
add_action('wp_enqueue_scripts', 'fqx_v131_enqueue_tracker_stable_assets', 999);
