<?php
/**
 * FluuexQR v142 — Room QR template dashboard UX + reliable save + selected preview.
 * Scope: Room/Hotel QR template picker and printable card preview only.
 */
if (!defined('ABSPATH')) { exit; }

function fqx_v142_template_number_from_id(string $template_id): int {
    $keys = function_exists('fqx_v134_room_template_catalog') ? array_keys(fqx_v134_room_template_catalog()) : ['premium_gold'];
    $idx = array_search($template_id, $keys, true);
    return false === $idx ? 1 : ((int) $idx + 1);
}

function fqx_v142_get_template_label(string $template_id): string {
    if (!function_exists('fqx_v134_room_template_catalog')) { return 'Template 01'; }
    $template_id = function_exists('fqx_v134_normalize_room_template') ? fqx_v134_normalize_room_template($template_id) : sanitize_key($template_id);
    $all = fqx_v134_room_template_catalog();
    return isset($all[$template_id]['name']) ? (string) $all[$template_id]['name'] : 'Template 01 — Luxury Black Gold';
}

function fqx_v142_room_mini_template_svg($room, string $template_id = ''): string {
    if (!function_exists('fqx_v134_room_template_catalog') || !function_exists('fqx_v140_room_template_preview_svg')) { return ''; }
    $template_id = $template_id ? $template_id : (function_exists('fqx_v134_get_room_template_id') ? fqx_v134_get_room_template_id($room) : 'premium_gold');
    $template_id = function_exists('fqx_v134_normalize_room_template') ? fqx_v134_normalize_room_template($template_id) : sanitize_key($template_id);
    $all = fqx_v134_room_template_catalog();
    $tpl = $all[$template_id] ?? reset($all);
    $number = fqx_v142_template_number_from_id($template_id);
    $svg = fqx_v140_room_template_preview_svg($template_id, $tpl, $number);
    $room_number = trim((string) ($room->room_number ?? ''));
    if ($room_number !== '') {
        // Replace demo room numbers in the mini preview with real room number for clarity.
        $svg = preg_replace('/Room\s+20[0-9]/', 'Room ' . esc_html($room_number), $svg);
        $svg = preg_replace('/ROOM\s+20[0-9]/', 'ROOM ' . esc_html($room_number), $svg);
    }
    return $svg;
}

function fqx_v142_room_card_template_quick_form(int $restaurant_id, $room): string {
    if (!is_object($room) || empty($room->id) || !function_exists('fqx_v134_room_template_catalog')) { return ''; }
    $selected = function_exists('fqx_v134_get_room_template_id') ? fqx_v134_get_room_template_id($room) : 'premium_gold';
    $selected = function_exists('fqx_v134_normalize_room_template') ? fqx_v134_normalize_room_template($selected) : sanitize_key($selected);
    $label = fqx_v142_get_template_label($selected);
    $action_url = function_exists('menuqr_restaurant_tab_url') ? menuqr_restaurant_tab_url('rooms') : home_url('/restaurant-dashboard/?tab=rooms');
    ob_start(); ?>
    <div class="fqx-v142-room-template-studio" data-room-id="<?php echo esc_attr((string) $room->id); ?>">
        <div class="fqx-v142-selected-head">
            <span class="fqx-v142-status-dot"></span>
            <div>
                <strong>Selected Room Card Template</strong>
                <small class="fqx-v142-current-name"><?php echo esc_html($label); ?></small>
            </div>
        </div>
        <div class="fqx-v142-mini-preview" aria-label="Selected template preview">
            <?php echo fqx_v142_room_mini_template_svg($room, $selected); ?>
        </div>
        <form method="post" action="<?php echo esc_url($action_url); ?>" class="fqx-v142-template-form">
            <?php wp_nonce_field('fqx_v142_update_room_template', 'fqx_v142_room_template_nonce'); ?>
            <input type="hidden" name="action" value="fqx_v142_update_room_template">
            <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
            <input type="hidden" name="room_id" value="<?php echo esc_attr((string) $room->id); ?>">
            <label for="fqx-v142-template-<?php echo esc_attr((string) $room->id); ?>">Change printable design</label>
            <select id="fqx-v142-template-<?php echo esc_attr((string) $room->id); ?>" name="room_qr_template" class="fqx-v142-template-select" data-current-name="<?php echo esc_attr($label); ?>">
                <?php foreach (fqx_v134_room_template_catalog() as $id => $tpl) : ?>
                    <option value="<?php echo esc_attr($id); ?>" <?php selected($selected, $id); ?>><?php echo esc_html((string) $tpl['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm fqx-v142-save-btn" type="submit">Save & Apply Template</button>
            <p class="fqx-v142-help">After saving, Preview / Print / PNG will use the selected Room card design.</p>
        </form>
    </div>
    <?php return (string) ob_get_clean();
}

function fqx_v142_handle_room_template_save(): void {
    $action = sanitize_key(wp_unslash($_POST['action'] ?? ''));
    if ('fqx_v142_update_room_template' !== $action) { return; }
    if (!is_user_logged_in()) { auth_redirect(); }
    if (function_exists('menuqr_require_role')) { menuqr_require_role(['restaurant_admin', 'super_admin']); }
    $nonce = sanitize_text_field(wp_unslash($_POST['fqx_v142_room_template_nonce'] ?? ''));
    if (!$nonce || !wp_verify_nonce($nonce, 'fqx_v142_update_room_template')) { wp_die(esc_html__('Invalid template update request.', 'menuqr')); }
    if (function_exists('fqx_v134_schema_update')) { fqx_v134_schema_update(); }

    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $room_id = absint($_POST['room_id'] ?? 0);
    if (function_exists('menuqr_validate_restaurant_access')) { menuqr_validate_restaurant_access($restaurant_id); }
    $template = sanitize_key(wp_unslash($_POST['room_qr_template'] ?? 'premium_gold'));
    if (function_exists('fqx_v134_normalize_room_template')) { $template = fqx_v134_normalize_room_template($template); }

    $rooms_table = menuqr_table('rooms');
    $save_scope = sanitize_key(wp_unslash($_POST['save_scope'] ?? 'room'));

    if ('restaurant_default' === $save_scope || $room_id <= 0) {
        update_option('fqx_room_qr_template_default_' . $restaurant_id, $template, false);
        // Keep backward compatibility: existing Room QR generation reads room_qr_template, so update current restaurant rooms safely.
        $wpdb->update(
            $rooms_table,
            ['room_qr_template' => $template, 'updated_at' => current_time('mysql')],
            ['restaurant_id' => $restaurant_id],
            ['%s', '%s'],
            ['%d']
        );
        $room_id = absint($_POST['preview_room_id'] ?? 0);
    } else {
        $room = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$rooms_table} WHERE id = %d AND restaurant_id = %d", $room_id, $restaurant_id));
        if (!$room) { wp_die(esc_html__('Room not found or access denied.', 'menuqr')); }
        $updated = $wpdb->update(
            $rooms_table,
            ['room_qr_template' => $template, 'updated_at' => current_time('mysql')],
            ['id' => $room_id, 'restaurant_id' => $restaurant_id],
            ['%s', '%s'],
            ['%d', '%d']
        );
        if (false === $updated) { wp_die(esc_html__('Could not save room template. Please try again.', 'menuqr')); }
    }

    if (function_exists('fqx_cache_purge_after_update')) { fqx_cache_purge_after_update('room_template_update'); }
    $redirect = function_exists('menuqr_restaurant_tab_url') ? menuqr_restaurant_tab_url('rooms') : home_url('/restaurant-dashboard/?tab=rooms');
    $redirect = add_query_arg(['room_template_saved' => max(1, $room_id), 'selected_template' => $template], $redirect);
    $redirect = add_query_arg(['section' => 'templates', 'template_tab' => 'room'], $redirect);
    if ($room_id > 0) { $redirect = add_query_arg(['room_id' => $room_id], $redirect); }
    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_fqx_v142_update_room_template', 'fqx_v142_handle_room_template_save');
add_action('template_redirect', 'fqx_v142_handle_room_template_save', 1);

add_action('wp_head', function (): void {
    if (function_exists('fqx_v138_is_room_template_admin_context') && !fqx_v138_is_room_template_admin_context()) { return; }
    if (!empty($_GET['room_template_saved'])) {
        echo '<style id="fqx-v142-toast-style">.fqx-v142-room-template-toast{position:fixed;right:18px;bottom:18px;z-index:99999;background:#111827;color:#fff;border:1px solid rgba(255,255,255,.12);box-shadow:0 18px 48px rgba(15,23,42,.24);border-radius:18px;padding:14px 18px;font-weight:900}.fqx-v142-room-template-toast b{color:#fb923c}</style>';
    }
}, 120);

add_action('wp_footer', function (): void {
    if (function_exists('fqx_v138_is_room_template_admin_context') && !fqx_v138_is_room_template_admin_context()) { return; }
    if (!empty($_GET['room_template_saved'])) {
        echo '<div class="fqx-v142-room-template-toast">Room card template <b>saved</b> successfully.</div><script>setTimeout(function(){var t=document.querySelector(".fqx-v142-room-template-toast"); if(t){t.remove();}},3500);</script>';
    }
}, 120);
