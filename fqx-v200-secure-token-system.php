<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v200 Secure QR Token compatibility layer.
 * Keeps old numeric URLs working by default, but all newly generated QR links use qr_token.
 */
function fqx_v200_secure_token(string $token = ''): string {
    $token = preg_replace('/[^A-Za-z0-9_\-]/', '', $token);
    return substr((string) $token, 0, 191);
}

function fqx_v200_new_token(): string {
    return wp_generate_password(40, false, false);
}

function fqx_v200_is_strict_mode(): bool {
    return (bool) get_option('fqx_secure_qr_strict_mode', false);
}

function fqx_v200_get_request_token(): string {
    foreach (['qr_token','service_token','token','qt'] as $key) {
        if (isset($_REQUEST[$key])) {
            $token = fqx_v200_secure_token(sanitize_text_field(wp_unslash($_REQUEST[$key])));
            if ($token !== '') { return $token; }
        }
    }
    return '';
}

function fqx_v200_ensure_tokens(): void {
    global $wpdb;
    foreach (['tables','rooms'] as $name) {
        $table = menuqr_table($name);
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if (!$exists) { continue; }
        $cols = (array) $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
        if (!in_array('qr_token', $cols, true)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN qr_token VARCHAR(191) NULL AFTER restaurant_id");
        }
        $empty_rows = (array) $wpdb->get_results("SELECT id FROM {$table} WHERE qr_token IS NULL OR qr_token = '' LIMIT 500");
        foreach ($empty_rows as $row) {
            $wpdb->update($table, ['qr_token' => fqx_v200_new_token(), 'updated_at' => current_time('mysql')], ['id' => (int) $row->id]);
        }
        $indexes = (array) $wpdb->get_col("SHOW INDEX FROM {$table} WHERE Column_name = 'qr_token'", 2);
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE {$table} ADD INDEX qr_token (qr_token)");
        }
    }
}
add_action('init', 'fqx_v200_ensure_tokens', 25);
add_action('after_switch_theme', 'fqx_v200_ensure_tokens', 35);

function fqx_v200_get_or_create_service_token(string $type, int $restaurant_id, int $id): string {
    global $wpdb;
    $table = $type === 'room' ? menuqr_table('rooms') : menuqr_table('tables');
    if ($restaurant_id <= 0 || $id <= 0) { return ''; }
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, qr_token FROM {$table} WHERE id=%d AND restaurant_id=%d LIMIT 1", $id, $restaurant_id));
    if (!$row) { return ''; }
    $token = fqx_v200_secure_token((string) ($row->qr_token ?? ''));
    if ($token === '') {
        $token = fqx_v200_new_token();
        $wpdb->update($table, ['qr_token' => $token, 'updated_at' => current_time('mysql')], ['id' => $id, 'restaurant_id' => $restaurant_id]);
    }
    return $token;
}

function fqx_v200_find_service_by_token(string $token): ?array {
    global $wpdb;
    $token = fqx_v200_secure_token($token);
    if ($token === '') { return null; }
    $tables = menuqr_table('tables');
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tables} WHERE qr_token=%s LIMIT 1", $token));
    if ($row) {
        return [
            'type' => 'table',
            'restaurant_id' => (int) $row->restaurant_id,
            'table_id' => (int) $row->id,
            'room_id' => 0,
            'table_number' => (string) ($row->table_number ?? ''),
            'room_number' => '',
            'order_source' => 'table_qr',
            'qr_token' => $token,
        ];
    }
    $rooms = menuqr_table('rooms');
    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$rooms} WHERE qr_token=%s LIMIT 1", $token));
    if ($room) {
        return [
            'type' => 'room',
            'restaurant_id' => (int) $room->restaurant_id,
            'table_id' => 0,
            'room_id' => (int) $room->id,
            'table_number' => '',
            'room_number' => (string) ($room->room_number ?? ''),
            'order_source' => 'room_qr',
            'qr_token' => $token,
        ];
    }
    return null;
}

function fqx_v200_secure_menu_url(int $restaurant_id, int $table_id = 0, int $room_id = 0): string {
    $base = menuqr_get_page_url_by_slug('menu') ?: home_url('/menu/');
    if ($room_id > 0) {
        $token = fqx_v200_get_or_create_service_token('room', $restaurant_id, $room_id);
        if ($token !== '') { return add_query_arg(['qr_token' => $token, 'source' => 'room'], $base); }
    } else {
        $token = fqx_v200_get_or_create_service_token('table', $restaurant_id, $table_id);
        if ($token !== '') { return add_query_arg(['qr_token' => $token, 'source' => 'table'], $base); }
    }
    return add_query_arg(['r' => $restaurant_id, 'table_id' => $table_id, 'room_id' => $room_id], $base);
}

function fqx_v200_resolve_request_service(array $fallback = []): array {
    $token = fqx_v200_get_request_token();
    if ($token !== '') {
        $ctx = fqx_v200_find_service_by_token($token);
        if (!$ctx) { return ['ok' => false, 'message' => 'Invalid or expired QR token. Please scan the correct QR again.']; }
        $ctx['ok'] = true;
        return $ctx;
    }
    if (fqx_v200_is_strict_mode()) {
        return ['ok' => false, 'message' => 'Secure QR token required. Please scan the correct table/room QR.'];
    }
    return array_merge(['ok' => true, 'qr_token' => '', 'legacy_mode' => true], $fallback);
}

function fqx_v200_resolve_ajax_service_or_die(): array {
    $fallback = [
        'restaurant_id' => absint($_REQUEST['restaurant_id'] ?? ($_REQUEST['r'] ?? 0)),
        'table_id' => absint($_REQUEST['table_id'] ?? ($_REQUEST['t'] ?? 0)),
        'room_id' => absint($_REQUEST['room_id'] ?? ($_REQUEST['room'] ?? 0)),
        'order_source' => sanitize_key(wp_unslash($_REQUEST['order_source'] ?? $_REQUEST['source'] ?? '')),
    ];
    $resolved = fqx_v200_resolve_request_service($fallback);
    if (empty($resolved['ok'])) {
        menuqr_json_response(false, ['message' => $resolved['message'] ?? 'Invalid QR token.'], 403);
    }
    return $resolved;
}

function fqx_v200_footer_ajax_token_bridge(): void {
    if (!is_page('menu')) { return; }
    ?>
<script id="fqx-v200-secure-token-bridge">
(function(){
  function root(){return document.getElementById('menuqr-customer-app');}
  function token(){var r=root();return r ? (r.getAttribute('data-qr-token') || '') : '';}
  function should(action){return ['menuqr_get_menu','menuqr_place_order','menuqr_create_gateway_order','menuqr_get_customer_bill','menuqr_start_bill_session','menuqr_get_order_status'].indexOf(action) !== -1;}
  if(window.jQuery){
    jQuery.ajaxPrefilter(function(options){
      var t=token(); if(!t) return;
      if(options.data instanceof FormData){ var a=options.data.get('action'); if(should(a) && !options.data.get('qr_token')) options.data.append('qr_token',t); return; }
      if(typeof options.data === 'string'){
        var p=new URLSearchParams(options.data); var a=p.get('action'); if(should(a) && !p.get('qr_token')){p.set('qr_token',t); options.data=p.toString();} return;
      }
      if(options.data && typeof options.data === 'object'){ var a=options.data.action; if(should(a) && !options.data.qr_token) options.data.qr_token=t; }
    });
  }
})();
</script>
    <?php
}
add_action('wp_footer', 'fqx_v200_footer_ajax_token_bridge', 98);
