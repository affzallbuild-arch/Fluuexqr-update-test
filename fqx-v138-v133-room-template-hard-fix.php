<?php
/**
 * FluuexQR v138 — v133-based Room QR template hard fix.
 * Keeps all v133 workflows unchanged and only stabilizes room template selection/print and mobile asset loading.
 */
if (!defined('ABSPATH')) { exit; }

// Make sure the room_qr_template column exists before Room save runs.
add_action('admin_post_menuqr_save_room', function (): void {
    if (function_exists('fqx_v134_schema_update')) { fqx_v134_schema_update(); }
}, 1);

// Extra-safe front dashboard detection: some installs use tab=rooms, some preserve action/edit_room only.
function fqx_v138_is_room_template_admin_context(): bool {
    if (is_admin()) { return true; }
    if (!function_exists('is_page') || !is_page('dashboard')) { return false; }
    $tab = sanitize_key(wp_unslash($_GET['tab'] ?? ''));
    $action = sanitize_key(wp_unslash($_GET['action'] ?? ''));
    $page = sanitize_key(wp_unslash($_GET['page'] ?? ''));
    return in_array($tab, ['rooms','wifi'], true)
        || in_array($action, ['edit_room','add_room','rooms','wifi'], true)
        || in_array($page, ['rooms','wifi'], true);
}

// Force template picker assets on Restaurant Admin Rooms/WiFi screen only.
add_action('wp_enqueue_scripts', function (): void {
    if (!fqx_v138_is_room_template_admin_context()) { return; }
    $css_file = get_template_directory() . '/assets/css/fqx-v133-room-session-wifi-card.css';
    $js_file  = get_template_directory() . '/assets/js/fqx-v134-room-template-picker.js';
    wp_enqueue_style('fqx-v138-room-template-admin', get_template_directory_uri() . '/assets/css/fqx-v133-room-session-wifi-card.css', [], file_exists($css_file) ? (string) filemtime($css_file) : '138');
    wp_enqueue_script('fqx-v138-room-template-picker', get_template_directory_uri() . '/assets/js/fqx-v134-room-template-picker.js', [], file_exists($js_file) ? (string) filemtime($js_file) : '138', true);
}, 3000);

// Keep room template assets away from normal marketing pages for phone performance.
add_action('wp_enqueue_scripts', function (): void {
    if (fqx_v138_is_room_template_admin_context()) { return; }
    if (function_exists('fqx_v137_is_room_context_request') && is_page('menu') && fqx_v137_is_room_context_request()) { return; }
    wp_dequeue_style('fqx-v133-room-session-wifi-card');
    wp_dequeue_style('fqx-v134-room-template-picker');
    wp_dequeue_style('fqx-v138-room-template-admin');
    wp_dequeue_script('fqx-v134-room-template-picker');
    wp_dequeue_script('fqx-v138-room-template-picker');
}, 3500);

// Small inline CSS fallback so selected state is visible even if older cached CSS is present.
add_action('wp_head', function (): void {
    if (!fqx_v138_is_room_template_admin_context()) { return; }
    echo '<style id="fqx-v138-room-template-fallback">.fqx-v134-template-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}.fqx-v134-template-card{border:2px solid rgba(255,255,255,.12);border-radius:18px;overflow:hidden;background:#0f172a;color:#fff;cursor:pointer;transition:.2s;min-height:44px}.fqx-v134-template-card:hover,.fqx-v134-template-card.is-selected{border-color:#ff6b00;box-shadow:0 16px 38px rgba(255,107,0,.25);transform:translateY(-2px)}.fqx-v134-template-card img{width:100%;aspect-ratio:3/4;object-fit:cover;display:block}.fqx-v134-template-card .fqx-v134-select-badge{display:inline-flex;align-items:center;justify-content:center;margin:10px;border-radius:999px;background:linear-gradient(135deg,#ff6b00,#ff9f1c);color:#fff;font-weight:800;padding:8px 12px;font-size:12px}</style>';
}, 99);
