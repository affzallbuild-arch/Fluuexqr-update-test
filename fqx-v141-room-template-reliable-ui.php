<?php
/**
 * FluuexQR v141 — Reliable Room/Hotel QR template selector + user-friendly room QR studio.
 * Scope: Room QR template selection/preview/print only. Table, payment, pricing and subscription workflows untouched.
 */
if (!defined('ABSPATH')) { exit; }

add_action('init', function (): void {
    if (function_exists('fqx_v134_schema_update')) { fqx_v134_schema_update(); }
}, 6);

function fqx_v141_room_template_options_html(string $selected): string {
    if (!function_exists('fqx_v134_room_template_catalog')) { return ''; }
    $selected = function_exists('fqx_v134_normalize_room_template') ? fqx_v134_normalize_room_template($selected) : sanitize_key($selected);
    $out = '';
    foreach (fqx_v134_room_template_catalog() as $id => $tpl) {
        $out .= '<option value="' . esc_attr($id) . '" ' . selected($selected, $id, false) . '>' . esc_html((string) $tpl['name']) . '</option>';
    }
    return $out;
}

function fqx_v141_get_room_current_template_name($room): string {
    if (!function_exists('fqx_v134_room_template_catalog')) { return 'Default Room Template'; }
    $id = function_exists('fqx_v134_get_room_template_id') ? fqx_v134_get_room_template_id($room) : 'luxury_black_gold';
    $templates = fqx_v134_room_template_catalog();
    return isset($templates[$id]) ? (string) $templates[$id]['name'] : 'Default Room Template';
}

function fqx_v141_room_card_template_quick_form(int $restaurant_id, $room): string {
    if (!is_object($room) || empty($room->id)) { return ''; }
    $selected = function_exists('fqx_v134_get_room_template_id') ? fqx_v134_get_room_template_id($room) : 'luxury_black_gold';
    ob_start(); ?>
    <div class="fqx-v141-room-studio">
        <span class="fqx-v141-current-template">Current: <?php echo esc_html(fqx_v141_get_room_current_template_name($room)); ?></span>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('fqx_v141_update_room_template', 'fqx_v141_room_template_nonce'); ?>
            <input type="hidden" name="action" value="fqx_v141_update_room_template">
            <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
            <input type="hidden" name="room_id" value="<?php echo esc_attr((string) $room->id); ?>">
            <label for="fqx-room-template-<?php echo esc_attr((string) $room->id); ?>">Printable Card Design</label>
            <select id="fqx-room-template-<?php echo esc_attr((string) $room->id); ?>" name="room_qr_template">
                <?php echo fqx_v141_room_template_options_html($selected); ?>
            </select>
            <button class="btn btn-primary btn-sm" type="submit">Save Template</button>
        </form>
    </div>
    <?php return (string) ob_get_clean();
}

function fqx_v141_handle_update_room_template(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('fqx_v141_room_template_nonce', 'fqx_v141_update_room_template');
    if (function_exists('fqx_v134_schema_update')) { fqx_v134_schema_update(); }
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $room_id = absint($_POST['room_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $template = sanitize_key(wp_unslash($_POST['room_qr_template'] ?? 'luxury_black_gold'));
    if (function_exists('fqx_v134_normalize_room_template')) { $template = fqx_v134_normalize_room_template($template); }
    $rooms_table = menuqr_table('rooms');
    $room = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$rooms_table} WHERE id = %d AND restaurant_id = %d", $room_id, $restaurant_id));
    if (!$room) { menuqr_redirect_back_with_status(['mq_notice' => 'room_invalid'], menuqr_restaurant_tab_url('rooms')); }
    $wpdb->update($rooms_table, ['room_qr_template' => $template, 'updated_at' => current_time('mysql')], ['id' => $room_id, 'restaurant_id' => $restaurant_id]);
    if (function_exists('fqx_cache_purge_after_update')) { fqx_cache_purge_after_update('room_template_update'); }
    menuqr_redirect_back_with_status(['mq_notice' => 'room_saved'], menuqr_restaurant_tab_url('rooms'));
}
add_action('admin_post_fqx_v141_update_room_template', 'fqx_v141_handle_update_room_template');

add_action('wp_head', function (): void {
    if (function_exists('fqx_v138_is_room_template_admin_context') && !fqx_v138_is_room_template_admin_context()) { return; }
    echo '<style id="fqx-v141-room-dashboard-ui">.dashboard-shell .chart-card:has(.fqx-v141-template-picker),.dashboard-shell .chart-card:has(.fqx-v134-template-picker){background:linear-gradient(135deg,#0f172a,#111827)!important;border:1px solid rgba(255,255,255,.08)!important}.dashboard-shell .chart-card:has(.fqx-v141-template-picker) .chart-title,.dashboard-shell .chart-card:has(.fqx-v134-template-picker) .chart-title{color:#fff!important}.dashboard-shell .qr-card{border-radius:22px!important;box-shadow:0 16px 38px rgba(15,23,42,.10)!important}.dashboard-shell .qr-card .page-header-right{gap:8px;flex-wrap:wrap}.dashboard-shell .qr-card .btn{min-height:40px}.dashboard-shell .qr-grid{grid-template-columns:repeat(auto-fit,minmax(260px,1fr))!important}@media(max-width:560px){.dashboard-shell .qr-grid{grid-template-columns:1fr!important}.dashboard-shell .qr-card .btn{width:100%;justify-content:center}.dashboard-shell .chart-grid{grid-template-columns:1fr!important}}</style>';
}, 100);
