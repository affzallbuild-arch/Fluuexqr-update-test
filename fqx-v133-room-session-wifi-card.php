<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v133 — Room 24-hour customer sessions + printable room card with Menu QR + WiFi QR.
 * Keeps pricing/subscription/payment/table workflow untouched.
 */

function fqx_v133_schema_update(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();

    $sql = [];
    $sql[] = "CREATE TABLE " . fqx_table('room_sessions') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        room_id BIGINT UNSIGNED NOT NULL,
        room_number VARCHAR(50) NULL,
        session_token VARCHAR(191) NOT NULL,
        device_hash VARCHAR(191) NOT NULL,
        started_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        last_activity DATETIME NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY session_token (session_token),
        KEY restaurant_id (restaurant_id),
        KEY room_id (room_id),
        KEY expires_at (expires_at),
        KEY status (status),
        KEY device_room (restaurant_id,room_id,device_hash)
    ) $charset;";

    $sql[] = "CREATE TABLE " . fqx_table('wifi_settings') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        room_id BIGINT UNSIGNED NULL DEFAULT NULL,
        use_global TINYINT(1) NOT NULL DEFAULT 1,
        wifi_enabled TINYINT(1) NOT NULL DEFAULT 0,
        ssid VARCHAR(191) NULL,
        password_encrypted TEXT NULL,
        security_type VARCHAR(20) NOT NULL DEFAULT 'WPA',
        show_password TINYINT(1) NOT NULL DEFAULT 0,
        show_ssid TINYINT(1) NOT NULL DEFAULT 1,
        show_wifi_qr TINYINT(1) NOT NULL DEFAULT 1,
        apply_to_all_rooms TINYINT(1) NOT NULL DEFAULT 1,
        help_text TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY restaurant_room (restaurant_id,room_id),
        KEY restaurant_id (restaurant_id),
        KEY room_id (room_id),
        KEY wifi_enabled (wifi_enabled)
    ) $charset;";

    foreach ($sql as $statement) { dbDelta($statement); }
    update_option('fqx_v133_schema_version', 143, false);
}
add_action('after_switch_theme', 'fqx_v133_schema_update');
add_action('init', function (): void {
    if ((int) get_option('fqx_v133_schema_version', 0) < 143) { fqx_v133_schema_update(); update_option('fqx_v133_schema_version', 143, false); }
    fqx_expire_old_room_sessions();
}, 8);

function fqx_v133_encrypt_value(string $value): string {
    return function_exists('fqx_encrypt_secret') ? fqx_encrypt_secret($value) : base64_encode($value);
}
function fqx_decrypt_wifi_password(string $encrypted_password): string {
    if ($encrypted_password === '') { return ''; }
    if (function_exists('fqx_decrypt_secret')) { return fqx_decrypt_secret($encrypted_password); }
    $decoded = base64_decode($encrypted_password, true);
    return $decoded !== false ? $decoded : $encrypted_password;
}

function fqx_create_device_hash(): string {
    $ua = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    return hash('sha256', $ua . '|' . $ip . '|' . wp_salt('nonce'));
}

function fqx_v133_cookie_name(int $restaurant_id, int $room_id): string {
    return 'fqx_room_session_' . $restaurant_id . '_' . $room_id;
}

function fqx_start_room_session($restaurant_id, $room_id, $device_hash) {
    global $wpdb;
    $restaurant_id = absint($restaurant_id);
    $room_id = absint($room_id);
    $device_hash = sanitize_text_field((string) $device_hash);
    if (!$restaurant_id || !$room_id || $device_hash === '') { return null; }

    $sessions = fqx_table('room_sessions');
    $rooms = menuqr_table('rooms');
    $now = current_time('mysql');
    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$rooms} WHERE id = %d AND restaurant_id = %d", $room_id, $restaurant_id));
    if (!$room) { return null; }

    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$sessions} WHERE restaurant_id = %d AND room_id = %d AND device_hash = %s AND status = 'active' AND expires_at > %s ORDER BY id DESC LIMIT 1",
        $restaurant_id, $room_id, $device_hash, $now
    ));
    if ($existing) {
        $wpdb->update($sessions, ['last_activity' => $now, 'updated_at' => $now], ['id' => (int) $existing->id]);
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sessions} WHERE id = %d", (int) $existing->id));
    }

    $token = wp_generate_password(48, false, false);
    $expires = gmdate('Y-m-d H:i:s', current_time('timestamp', true) + DAY_IN_SECONDS);
    $wpdb->insert($sessions, [
        'restaurant_id' => $restaurant_id,
        'room_id' => $room_id,
        'room_number' => sanitize_text_field((string) $room->room_number),
        'session_token' => $token,
        'device_hash' => $device_hash,
        'started_at' => $now,
        'expires_at' => get_date_from_gmt($expires),
        'last_activity' => $now,
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sessions} WHERE id = %d", (int) $wpdb->insert_id));
}

function fqx_get_room_session($session_token) {
    global $wpdb;
    $token = sanitize_text_field((string) $session_token);
    if ($token === '') { return null; }
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . fqx_table('room_sessions') . " WHERE session_token = %s LIMIT 1", $token));
}
function fqx_is_room_session_active($session_token): bool {
    $s = fqx_get_room_session($session_token);
    return $s && $s->status === 'active' && strtotime((string) $s->expires_at) > current_time('timestamp');
}
function fqx_extend_room_session_activity($session_token): void {
    global $wpdb;
    $s = fqx_get_room_session($session_token);
    if ($s && fqx_is_room_session_active($session_token)) {
        $wpdb->update(fqx_table('room_sessions'), ['last_activity' => current_time('mysql'), 'updated_at' => current_time('mysql')], ['id' => (int) $s->id]);
    }
}
function fqx_expire_old_room_sessions(): void {
    global $wpdb;
    $wpdb->query($wpdb->prepare("UPDATE " . fqx_table('room_sessions') . " SET status = 'expired', updated_at = %s WHERE status = 'active' AND expires_at <= %s", current_time('mysql'), current_time('mysql')));
}
function fqx_get_current_room_session() {
    $restaurant_id = absint($_GET['r'] ?? ($_GET['restaurant_id'] ?? 0));
    $room_id = absint($_GET['room_id'] ?? ($_GET['room'] ?? 0));
    if (!$restaurant_id || !$room_id) { return null; }
    $cookie = sanitize_text_field(wp_unslash($_COOKIE[fqx_v133_cookie_name($restaurant_id, $room_id)] ?? ''));
    return $cookie ? fqx_get_room_session($cookie) : null;
}
function fqx_get_room_session_orders($session_token): array {
    $session = fqx_get_room_session($session_token);
    if (!$session || !fqx_is_room_session_active($session_token)) { return []; }
    global $wpdb;
    $orders = menuqr_table('orders');
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$orders} WHERE restaurant_id = %d AND room_id = %d AND order_source = 'room_qr' AND created_at BETWEEN %s AND %s ORDER BY created_at DESC, id DESC",
        (int) $session->restaurant_id, (int) $session->room_id, (string) $session->started_at, (string) $session->expires_at
    ));
}
function fqx_get_room_session_bill($session_token) {
    $session = fqx_get_room_session($session_token);
    if (!$session || !fqx_is_room_session_active($session_token)) { return null; }
    global $wpdb;
    $bills = menuqr_table('bills');
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$bills} WHERE restaurant_id = %d AND room_id = %d AND order_source = 'room_qr' AND created_at BETWEEN %s AND %s ORDER BY id DESC LIMIT 1",
        (int) $session->restaurant_id, (int) $session->room_id, (string) $session->started_at, (string) $session->expires_at
    ));
}

function fqx_v133_boot_room_session(): void {
    if (is_admin() || !is_page('menu')) { return; }
    $restaurant_id = absint($_GET['r'] ?? ($_GET['restaurant_id'] ?? 0));
    $room_id = absint($_GET['room_id'] ?? ($_GET['room'] ?? 0));
    $source = sanitize_key(wp_unslash($_GET['source'] ?? $_GET['order_source'] ?? ''));
    if (!$restaurant_id || !$room_id || !in_array($source, ['room', 'room_qr', 'hotel_room'], true)) { return; }
    $session = fqx_start_room_session($restaurant_id, $room_id, fqx_create_device_hash());
    if (!$session) { return; }
    $expires_ts = strtotime((string) $session->expires_at);
    $secure = is_ssl();
    $cookie_name = fqx_v133_cookie_name($restaurant_id, $room_id);
    if (!headers_sent()) {
        setcookie($cookie_name, (string) $session->session_token, [
            'expires' => $expires_ts ?: (time() + DAY_IN_SECONDS),
            'path' => COOKIEPATH ?: '/',
            'domain' => COOKIE_DOMAIN ?: '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $GLOBALS['fqx_v133_current_room_session'] = $session;
}
add_action('template_redirect', 'fqx_v133_boot_room_session', 1);

function fqx_v133_room_bill_duration_minutes(int $minutes): int {
    $room_id = absint($_REQUEST['room_id'] ?? ($_REQUEST['room'] ?? 0));
    $source = sanitize_key(wp_unslash($_REQUEST['order_source'] ?? $_REQUEST['source'] ?? ''));
    return ($room_id > 0 || in_array($source, ['room', 'room_qr', 'hotel_room'], true)) ? 1440 : $minutes;
}
add_filter('menuqr_bill_session_minutes', 'fqx_v133_room_bill_duration_minutes', 20);

function fqx_v133_customer_bill_guard(array $data, int $restaurant_id, int $table_id, string $session_token, int $room_id, string $order_source): array {
    if ($room_id > 0 || $order_source === 'room_qr') {
        $room_session = fqx_get_room_session($session_token);
        if ($room_session && !fqx_is_room_session_active($session_token)) {
            return ['bill' => null, 'session' => null, 'orders' => [], 'message' => 'Your room session has expired. Please scan the Room QR again.'];
        }
        fqx_extend_room_session_activity($session_token);
    }
    return $data;
}
add_filter('menuqr_customer_bill_data', 'fqx_v133_customer_bill_guard', 20, 6);

function fqx_escape_wifi_qr_value($value): string {
    $value = (string) $value;
    $value = str_replace('\\', '\\\\', $value);
    $value = str_replace([';', ',', ':', '"'], ['\\;', '\\,', '\\:', '\\"'], $value);
    return $value;
}
function fqx_generate_wifi_qr_data($ssid, $password, $security_type): string {
    $security = strtoupper(sanitize_key((string) $security_type));
    if ($security === 'OPEN' || $security === 'NOPASS' || $security === 'NONE') {
        return 'WIFI:T:nopass;S:' . fqx_escape_wifi_qr_value($ssid) . ';;';
    }
    if (!in_array($security, ['WPA', 'WPA2', 'WPA3', 'WEP'], true)) { $security = 'WPA'; }
    $type = $security === 'WEP' ? 'WEP' : 'WPA';
    return 'WIFI:T:' . $type . ';S:' . fqx_escape_wifi_qr_value($ssid) . ';P:' . fqx_escape_wifi_qr_value($password) . ';;';
}
function fqx_generate_wifi_qr_image($ssid, $password, $security_type, int $size = 220): string {
    return menuqr_get_real_qr_image_url(fqx_generate_wifi_qr_data($ssid, $password, $security_type), $size, 'png');
}

function fqx_get_global_wifi_settings($restaurant_id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . fqx_table('wifi_settings') . " WHERE restaurant_id = %d AND (room_id IS NULL OR room_id = 0) ORDER BY id DESC LIMIT 1", absint($restaurant_id)));
}
function fqx_get_room_wifi_settings($restaurant_id, $room_id) {
    global $wpdb;
    $restaurant_id = absint($restaurant_id);
    $room_id = absint($room_id);
    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . fqx_table('wifi_settings') . " WHERE restaurant_id = %d AND room_id = %d LIMIT 1", $restaurant_id, $room_id));
    $global = fqx_get_global_wifi_settings($restaurant_id);
    if ($room && !(int) $room->use_global && trim((string) $room->ssid) !== '') {
        return $room;
    }
    return $global ?: $room;
}
function fqx_should_show_wifi_qr($restaurant_id, $room_id): bool {
    $settings = fqx_get_room_wifi_settings($restaurant_id, $room_id);
    return $settings && (int) $settings->wifi_enabled === 1 && (int) $settings->show_wifi_qr === 1 && trim((string) $settings->ssid) !== '';
}
function fqx_get_wifi_qr_image_url($restaurant_id, $room_id, int $size = 220): string {
    $settings = fqx_get_room_wifi_settings($restaurant_id, $room_id);
    if (!$settings || !fqx_should_show_wifi_qr($restaurant_id, $room_id)) { return ''; }
    $ssid = (string) $settings->ssid;
    $password = fqx_decrypt_wifi_password((string) $settings->password_encrypted);
    return fqx_generate_wifi_qr_image($ssid, $password, (string) $settings->security_type, $size);
}

function fqx_save_wifi_settings($data) {
    global $wpdb;
    $restaurant_id = absint($data['restaurant_id'] ?? 0);
    $room_id = isset($data['room_id']) && $data['room_id'] !== '' ? absint($data['room_id']) : 0;
    if (!$restaurant_id) { return false; }
    $now = current_time('mysql');
    $payload = [
        'restaurant_id' => $restaurant_id,
        'room_id' => $room_id ?: null,
        'use_global' => !empty($data['use_global']) ? 1 : 0,
        'wifi_enabled' => !empty($data['wifi_enabled']) ? 1 : 0,
        'ssid' => sanitize_text_field((string) ($data['ssid'] ?? '')),
        'security_type' => sanitize_key((string) ($data['security_type'] ?? 'WPA')),
        'show_password' => !empty($data['show_password']) ? 1 : 0,
        'show_ssid' => !empty($data['show_ssid']) ? 1 : 0,
        'show_wifi_qr' => !empty($data['show_wifi_qr']) ? 1 : 0,
        'apply_to_all_rooms' => !empty($data['apply_to_all_rooms']) ? 1 : 0,
        'help_text' => sanitize_textarea_field((string) ($data['help_text'] ?? '')),
        'updated_at' => $now,
    ];
    $password = (string) ($data['wifi_password'] ?? '');
    if ($password !== '') { $payload['password_encrypted'] = fqx_v133_encrypt_value($password); }

    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . fqx_table('wifi_settings') . " WHERE restaurant_id = %d AND " . ($room_id ? "room_id = %d" : "(room_id IS NULL OR room_id = 0)") . " LIMIT 1", $room_id ? [$restaurant_id, $room_id] : [$restaurant_id]));
    if ($existing) {
        return false !== $wpdb->update(fqx_table('wifi_settings'), $payload, ['id' => (int) $existing]);
    }
    $payload['created_at'] = $now;
    return false !== $wpdb->insert(fqx_table('wifi_settings'), $payload);
}

function fqx_v133_handle_save_wifi_settings(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    check_admin_referer('fqx_v133_save_wifi_settings', 'fqx_v133_wifi_nonce');
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    fqx_save_wifi_settings([
        'restaurant_id' => $restaurant_id,
        'room_id' => sanitize_text_field(wp_unslash($_POST['room_id'] ?? '')),
        'use_global' => !empty($_POST['use_global']),
        'wifi_enabled' => !empty($_POST['wifi_enabled']),
        'ssid' => wp_unslash($_POST['ssid'] ?? ''),
        'wifi_password' => wp_unslash($_POST['wifi_password'] ?? ''),
        'security_type' => wp_unslash($_POST['security_type'] ?? 'WPA'),
        'show_password' => !empty($_POST['show_password']),
        'show_ssid' => !empty($_POST['show_ssid']),
        'show_wifi_qr' => !empty($_POST['show_wifi_qr']),
        'apply_to_all_rooms' => !empty($_POST['apply_to_all_rooms']),
        'help_text' => wp_unslash($_POST['help_text'] ?? ''),
    ]);
    if (function_exists('fqx_v125_schedule_cache_purge')) { fqx_v125_schedule_cache_purge('wifi_settings_save'); }
    menuqr_redirect_back_with_status(['mq_notice' => 'wifi_saved'], menuqr_restaurant_tab_url('wifi'));
}
add_action('admin_post_fqx_v133_save_wifi_settings', 'fqx_v133_handle_save_wifi_settings');

function fqx_v133_wifi_notice_map(array $map): array {
    $map['wifi_saved'] = ['success', 'WiFi QR saved successfully.'];
    return $map;
}
add_filter('menuqr_notice_messages', 'fqx_v133_wifi_notice_map');

function fqx_v133_wifi_admin_html(int $restaurant_id, array $rooms): string {
    $global = fqx_get_global_wifi_settings($restaurant_id);
    $ssid = $global ? (string) $global->ssid : '';
    $password = $global ? fqx_decrypt_wifi_password((string) $global->password_encrypted) : '';
    $security = $global ? strtoupper((string) $global->security_type) : 'WPA';
    if ($security === 'OPEN' || $security === 'NOPASS' || $security === 'NONE') { $security = 'open'; }
    if (!in_array($security, ['WPA', 'WPA2', 'WPA3', 'WEP', 'open'], true)) { $security = 'WPA'; }
    $qr = $ssid ? fqx_generate_wifi_qr_image($ssid, $password, $security, 260) : '';
    $enabled = (int) ($global->wifi_enabled ?? 0) === 1;
    $show_qr = (int) ($global->show_wifi_qr ?? 1) === 1;
    $show_ssid = (int) ($global->show_ssid ?? 1) === 1;
    $show_password = (int) ($global->show_password ?? 0) === 1;
    $notice = sanitize_key(wp_unslash($_GET['mq_notice'] ?? ''));
    $error = sanitize_text_field(wp_unslash($_GET['mq_error'] ?? ''));
    $open_selected = strtolower($security) === 'open';
    ob_start(); ?>
    <section class="fq-wifi-qr-page" data-wifi-qr-page="1">
        <div class="fq-wifi-qr-head">
            <div>
                <div class="fq-room-breadcrumb"><a href="<?php echo esc_url(menuqr_restaurant_tab_url('rooms')); ?>">Rooms &amp; QR</a><span>›</span><span>WiFi QR</span></div>
                <h1>WiFi QR</h1>
                <p>Configure WiFi details and QR display preferences for room printable templates.</p>
            </div>
            <a class="fq-back-dashboard" href="<?php echo esc_url(menuqr_restaurant_tab_url('dashboard')); ?>">← Back to Dashboard</a>
        </div>

        <div class="fq-wifi-alert-row">
            <?php if ($notice === 'wifi_saved') : ?>
                <div class="fq-wifi-alert fq-wifi-alert-success"><span>✓</span><strong>Success!</strong> WiFi QR saved successfully.<button type="button" class="fq-alert-close">×</button></div>
            <?php endif; ?>
            <?php if ($error) : ?>
                <div class="fq-wifi-alert fq-wifi-alert-error"><span>×</span><strong>Error!</strong> <?php echo esc_html($error); ?><button type="button" class="fq-alert-close">×</button></div>
            <?php endif; ?>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fq-wifi-qr-form">
            <?php wp_nonce_field('fqx_v133_save_wifi_settings', 'fqx_v133_wifi_nonce'); ?>
            <input type="hidden" name="action" value="fqx_v133_save_wifi_settings">
            <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
            <input type="hidden" name="room_id" value="">
            <input type="hidden" name="use_global" value="0">

            <div class="fq-wifi-stepper">
                <div class="fq-wifi-step is-active"><span>1</span><div><b>Step 1: Add WiFi Details</b><small>Enter your WiFi name and password</small></div></div>
                <div class="fq-wifi-step-arrow">›</div>
                <div class="fq-wifi-step"><span>2</span><div><b>Step 2: Choose Security Type</b><small>Select your WiFi security type</small></div></div>
                <div class="fq-wifi-step-arrow">›</div>
                <div class="fq-wifi-step"><span>3</span><div><b>Step 3: Enable WiFi QR on Room Card</b><small>Choose what to show on the card</small></div></div>
                <div class="fq-wifi-step-arrow">›</div>
                <div class="fq-wifi-step"><span>4</span><div><b>Step 4: Preview / Save</b><small>Preview &amp; save WiFi QR</small></div></div>
            </div>

            <div class="fq-wifi-card-grid">
                <article class="fq-wifi-card fq-wifi-details-card">
                    <h3>Step 1: Add WiFi Details</h3>
                    <div class="fq-wifi-divider"></div>
                    <label class="fq-wifi-toggle-row"><span><b>Enable WiFi QR</b><small>Enable or disable WiFi QR for room templates.</small></span><input type="checkbox" name="wifi_enabled" value="1" <?php checked($enabled); ?>><i></i></label>
                    <div class="fq-field-group"><label>WiFi Name / SSID <em>*</em></label><div class="fq-field-icon"><input class="form-input fq-live-ssid" name="ssid" value="<?php echo esc_attr($ssid); ?>" placeholder="FluuexQR_Hotel"><span>📶</span></div><small>Enter the name of your WiFi network (SSID).</small></div>
                    <div class="fq-field-group"><label>WiFi Password <em>*</em></label><div class="fq-field-icon"><input class="form-input fq-live-password" type="password" name="wifi_password" value="<?php echo esc_attr($password); ?>" placeholder="FluuexQR@2025"><button type="button" class="fq-password-toggle" aria-label="Show password">◌</button></div><small>Enter the password of your WiFi network.</small></div>
                    <div class="fq-wifi-tip"><span>ⓘ</span><p><b>Tip:</b> Using a strong password ensures better security for your guests.</p></div>
                </article>

                <article class="fq-wifi-card fq-wifi-security-card">
                    <h3>Step 2: Choose Security Type</h3>
                    <div class="fq-wifi-divider"></div>
                    <div class="fq-field-group"><label>Security Type <em>*</em></label><div class="fq-field-icon"><select class="form-select fq-security-select" name="security_type"><option value="WPA" <?php selected($security, 'WPA'); ?>>WPA2/WPA3 Personal</option><option value="WPA2" <?php selected($security, 'WPA2'); ?>>WPA/WPA2 Personal</option><option value="WEP" <?php selected($security, 'WEP'); ?>>WEP</option><option value="open" <?php selected($open_selected); ?>>Open (No Password)</option></select><span>🔒</span></div><small>Select the security type used by your WiFi.</small></div>
                    <div class="fq-security-options">
                        <label class="fq-security-option <?php echo in_array($security, ['WPA','WPA3'], true) ? 'is-selected' : ''; ?>"><input type="radio" data-security-value="WPA" <?php checked(in_array($security, ['WPA','WPA3'], true)); ?>><span></span><b>WPA2/WPA3 Personal</b><small>Recommended for most modern routers.</small></label>
                        <label class="fq-security-option <?php echo $security === 'WPA2' ? 'is-selected' : ''; ?>"><input type="radio" data-security-value="WPA2" <?php checked($security === 'WPA2'); ?>><span></span><b>WPA/WPA2 Personal</b><small>Universal compatibility with older devices.</small></label>
                        <label class="fq-security-option <?php echo $security === 'WEP' ? 'is-selected' : ''; ?>"><input type="radio" data-security-value="WEP" <?php checked($security === 'WEP'); ?>><span></span><b>WEP</b><small>Not recommended. Low security.</small></label>
                        <label class="fq-security-option <?php echo $open_selected ? 'is-selected' : ''; ?>"><input type="radio" data-security-value="open" <?php checked($open_selected); ?>><span></span><b>Open (No Password)</b><small>Open network without password.</small></label>
                    </div>
                    <div class="fq-wifi-tip"><span>🛡</span><p>WPA2/WPA3 provides the best balance of security and performance.</p></div>
                </article>

                <article class="fq-wifi-card fq-wifi-toggle-card">
                    <h3>Step 3: Enable WiFi QR on Room Card</h3>
                    <div class="fq-wifi-divider"></div>
                    <label class="fq-wifi-toggle-row"><span><b>Show WiFi Name on Card</b><small>Display WiFi name (SSID) on the room card.</small></span><input type="checkbox" name="show_ssid" value="1" <?php checked($show_ssid); ?>><i></i></label>
                    <label class="fq-wifi-toggle-row"><span><b>Show WiFi Password on Card</b><small>Display WiFi password on the room card.</small></span><input type="checkbox" name="show_password" value="1" <?php checked($show_password); ?>><i></i></label>
                    <label class="fq-wifi-toggle-row"><span><b>Show WiFi QR on Room Template</b><small>Display scannable WiFi QR on the room card.</small></span><input type="checkbox" name="show_wifi_qr" value="1" <?php checked($show_qr); ?>><i></i></label>
                    <input type="hidden" name="apply_to_all_rooms" value="1">
                    <div class="fq-wifi-tip"><span>▦</span><p>Guests can scan the QR code to <b>instantly connect</b> to WiFi without typing.</p></div>
                </article>

                <article class="fq-wifi-card fq-wifi-preview-card">
                    <h3>Step 4: Preview / Save</h3>
                    <div class="fq-wifi-divider"></div>
                    <div class="fq-room-card-preview-title">Room Card Preview</div>
                    <div class="fq-wifi-room-card">
                        <div class="fq-wifi-room-card-head">📶 <b>Stay Connected</b><span>Scan to connect to our WiFi</span></div>
                        <div class="fq-wifi-qr-preview-box">
                            <?php if ($qr) : ?><img class="fq-live-qr-img" src="<?php echo esc_url($qr); ?>" width="220" height="220" loading="lazy" decoding="async" alt="WiFi QR preview"><em>FQ</em><?php else : ?><div class="fq-no-wifi-qr">WiFi QR</div><?php endif; ?>
                        </div>
                        <div class="fq-wifi-preview-info"><small>WiFi Name</small><strong class="fq-preview-ssid"><?php echo esc_html($ssid ?: 'FluuexQR_Hotel'); ?></strong></div>
                        <div class="fq-wifi-preview-info"><small>Password</small><strong class="fq-preview-password"><?php echo esc_html($show_password && $password ? $password : ($password ? '••••••••' : 'FluuexQR@2025')); ?></strong></div>
                    </div>
                    <p class="fq-preview-helper">This is how it will appear on the room card.</p>
                    <button class="fq-save-wifi-btn" type="submit">✓ Save WiFi QR</button>
                    <button class="fq-reset-wifi-btn" type="reset">↻ Reset to Default</button>
                </article>
            </div>
        </form>

        <div class="fq-wifi-info-card">
            <h3>Additional Information</h3>
            <div class="fq-wifi-info-grid">
                <div><span>🔐</span><b>Secure &amp; Private</b><small>WiFi credentials are encrypted and stored securely.</small></div>
                <div><span>📱</span><b>Seamless Connection</b><small>QR code allows guests to connect with one tap.</small></div>
                <div><span>⚙</span><b>Customizable Display</b><small>Choose what details to show on your room cards.</small></div>
            </div>
        </div>
    </section>
    <?php return (string) ob_get_clean();
}

function fqx_v133_room_qr_zip_url(int $restaurant_id): string {
    return add_query_arg(['fqx_room_qr_cards_zip' => 1, 'restaurant_id' => $restaurant_id, '_fqx' => wp_create_nonce('fqx_room_qr_zip_' . $restaurant_id)], home_url('/'));
}

function fqx_v134_room_template_catalog(): array {
    // FluuexQR v179: 10 production-ready Room QR printable templates.
    // IDs are stable and used by admin selection, preview/print and saved room rows.
    return [
        'premium_gold' => ['name' => 'Premium Gold', 'category' => 'Luxury Hotel', 'theme' => 'dark', 'accent' => '#ffb23f', 'accent2' => '#f6c15a', 'bg' => '#071018', 'panel' => '#101923', 'text' => '#ffffff', 'muted' => '#aab4c0', 'layout' => 'split', 'pattern' => 'crown'],
        'royal_black' => ['name' => 'Royal Black', 'category' => 'Royal Suite', 'theme' => 'dark', 'accent' => '#d4af37', 'accent2' => '#fb923c', 'bg' => '#030712', 'panel' => '#0b0f16', 'text' => '#fff7d6', 'muted' => '#d6c48c', 'layout' => 'split', 'pattern' => 'corner'],
        'luxury_hotel' => ['name' => 'Luxury Hotel', 'category' => 'Premium Stay', 'theme' => 'dark', 'accent' => '#f59e0b', 'accent2' => '#facc15', 'bg' => '#111827', 'panel' => '#1f2937', 'text' => '#ffffff', 'muted' => '#d1d5db', 'layout' => 'side_text', 'pattern' => 'royal'],
        'minimal_white' => ['name' => 'Minimal White', 'category' => 'Clean A6', 'theme' => 'light', 'accent' => '#f97316', 'accent2' => '#111827', 'bg' => '#fffaf2', 'panel' => '#ffffff', 'text' => '#111827', 'muted' => '#4b5563', 'layout' => 'split', 'pattern' => 'minimal'],
        'modern_orange' => ['name' => 'Modern Orange', 'category' => 'Restaurant', 'theme' => 'dark', 'accent' => '#ff6b00', 'accent2' => '#ffd166', 'bg' => '#111018', 'panel' => '#1a1420', 'text' => '#ffffff', 'muted' => '#fed7aa', 'layout' => 'split', 'pattern' => 'diagonal'],
        'classic_restaurant' => ['name' => 'Classic Restaurant', 'category' => 'Dine-in + Room', 'theme' => 'light', 'accent' => '#b45309', 'accent2' => '#92400e', 'bg' => '#fff7ed', 'panel' => '#ffffff', 'text' => '#1f2937', 'muted' => '#6b4f2a', 'layout' => 'split', 'pattern' => 'leaf'],
        'room_service_pro' => ['name' => 'Room Service Pro', 'category' => 'Room Service', 'theme' => 'dark', 'accent' => '#38bdf8', 'accent2' => '#f59e0b', 'bg' => '#061226', 'panel' => '#0b203b', 'text' => '#ffffff', 'muted' => '#dbeafe', 'layout' => 'stacked', 'pattern' => 'tech'],
        'dark_elite' => ['name' => 'Dark Elite', 'category' => 'Elite Black', 'theme' => 'dark', 'accent' => '#f5b849', 'accent2' => '#eab308', 'bg' => '#060606', 'panel' => '#101010', 'text' => '#fff7ed', 'muted' => '#e7e5d9', 'layout' => 'steps', 'pattern' => 'marble'],
        'clean_corporate' => ['name' => 'Clean Corporate', 'category' => 'Business Hotel', 'theme' => 'light', 'accent' => '#2563eb', 'accent2' => '#f59e0b', 'bg' => '#f8fafc', 'panel' => '#ffffff', 'text' => '#0f172a', 'muted' => '#475569', 'layout' => 'split', 'pattern' => 'wave'],
        'smart_hotel_qr' => ['name' => 'Smart Hotel QR', 'category' => 'Smart Access', 'theme' => 'dark', 'accent' => '#22c55e', 'accent2' => '#f59e0b', 'bg' => '#04130d', 'panel' => '#0b2018', 'text' => '#ffffff', 'muted' => '#bbf7d0', 'layout' => 'stacked', 'pattern' => 'tech'],
    ];
}


function fqx_v134_normalize_room_template($template_id): string {
    $template_id = sanitize_key((string) $template_id);
    $legacy_map = [
        'luxury_black_gold' => 'premium_gold',
        'clean_white_orange' => 'minimal_white',
        'black_red_modern' => 'modern_orange',
        'royal_navy_gold' => 'royal_black',
        'cream_charcoal' => 'classic_restaurant',
        'neon_blue_orange' => 'room_service_pro',
        'black_marble_gold' => 'dark_elite',
        'premium_navy_stack' => 'luxury_hotel',
        'slim_dark_orange' => 'modern_orange',
        'bold_orange_blue' => 'smart_hotel_qr',
    ];
    if (isset($legacy_map[$template_id])) { $template_id = $legacy_map[$template_id]; }
    $templates = fqx_v134_room_template_catalog();
    return isset($templates[$template_id]) ? $template_id : 'premium_gold';
}


function fqx_v189_get_room_default_template(int $restaurant_id): string {
    $restaurant_id = absint($restaurant_id);
    $option_key = 'fqx_room_qr_template_default_' . $restaurant_id;
    $stored = get_option($option_key, null);
    if (null === $stored || '' === $stored) {
        global $wpdb;
        $rooms_table = function_exists('menuqr_table') ? menuqr_table('rooms') : '';
        $stored = '';
        if ($restaurant_id > 0 && $rooms_table) {
            $stored = (string) $wpdb->get_var($wpdb->prepare("SELECT room_qr_template FROM {$rooms_table} WHERE restaurant_id = %d AND room_qr_template IS NOT NULL AND room_qr_template <> '' ORDER BY updated_at DESC, id DESC LIMIT 1", $restaurant_id));
        }
        $stored = $stored ?: 'premium_gold';
        update_option($option_key, $stored, false);
    }
    return function_exists('fqx_v134_normalize_room_template') ? fqx_v134_normalize_room_template((string) $stored) : sanitize_key((string) ($stored ?: 'premium_gold'));
}

function fqx_v134_get_room_template_id($room): string {
    $restaurant_id = is_object($room) && isset($room->restaurant_id) ? (int) $room->restaurant_id : 0;
    $default = $restaurant_id > 0 && function_exists('fqx_v189_get_room_default_template') ? fqx_v189_get_room_default_template($restaurant_id) : 'premium_gold';
    $stored = is_object($room) && isset($room->room_qr_template) ? (string) $room->room_qr_template : '';
    return fqx_v134_normalize_room_template($stored ?: $default);
}

function fqx_v134_schema_update(): void {
    global $wpdb;
    $rooms = menuqr_table('rooms');
    $cols = $wpdb->get_col("SHOW COLUMNS FROM {$rooms}", 0);
    if (is_array($cols) && !in_array('room_qr_template', $cols, true)) {
        $wpdb->query("ALTER TABLE {$rooms} ADD COLUMN room_qr_template VARCHAR(80) NULL DEFAULT 'premium_gold' AFTER qr_token");
    }
    update_option('fqx_v134_schema_version', 134, false);
}
add_action('after_switch_theme', 'fqx_v134_schema_update');
add_action('init', function (): void {
    if ((int) get_option('fqx_v134_schema_version', 0) < 134) { fqx_v134_schema_update(); }
}, 7);

function fqx_v140_room_template_preview_svg(string $id, array $tpl, int $number): string {
    $accent = esc_attr((string) ($tpl['accent'] ?? '#ff6b00'));
    $accent2 = esc_attr((string) ($tpl['accent2'] ?? $accent));
    $bg = esc_attr((string) ($tpl['bg'] ?? '#07111f'));
    $panel = esc_attr((string) ($tpl['panel'] ?? '#0f172a'));
    $text = esc_attr((string) ($tpl['text'] ?? '#ffffff'));
    $muted = esc_attr((string) ($tpl['muted'] ?? '#cbd5e1'));
    $is_light = (($tpl['theme'] ?? 'dark') === 'light');
    $qr_bg = $is_light ? '#ffffff' : '#f8fafc';
    $footer = $is_light ? '#111827' : 'rgba(255,255,255,.07)';
    $room = 200 + $number;
    $wifi_x = (($tpl['layout'] ?? 'split') === 'stacked') ? 96 : 250;
    $wifi_y = (($tpl['layout'] ?? 'split') === 'stacked') ? 352 : 245;
    $menu_w = (($tpl['layout'] ?? 'split') === 'stacked') ? 268 : 170;
    $menu_h = (($tpl['layout'] ?? 'split') === 'stacked') ? 172 : 225;
    $menu_x = (($tpl['layout'] ?? 'split') === 'stacked') ? 46 : 34;
    $menu_y = (($tpl['layout'] ?? 'split') === 'stacked') ? 160 : 185;
    $pattern = (string) ($tpl['pattern'] ?? 'minimal');
    $decor = '';
    if ($pattern === 'diagonal') {
        $decor = '<path d="M0 0H360V92C318 116 296 143 278 184C256 236 221 270 170 299C108 335 56 378 0 460Z" fill="' . $accent . '" opacity=".16"/><path d="M360 0V520H300C285 430 296 338 360 252Z" fill="' . $accent . '" opacity=".45"/>';
    } elseif ($pattern === 'marble') {
        $decor = '<path d="M24 95C96 56 162 104 238 62C286 36 316 40 342 20" stroke="' . $accent . '" stroke-width="2" opacity=".35" fill="none"/><path d="M0 420C62 372 132 410 212 366C270 334 314 348 360 318" stroke="' . $accent . '" stroke-width="2" opacity=".22" fill="none"/>';
    } elseif ($pattern === 'tech') {
        $decor = '<path d="M16 80H72L95 104H150" stroke="' . $accent . '" stroke-width="2" opacity=".45" fill="none"/><path d="M345 98H292L270 120H230" stroke="' . $accent2 . '" stroke-width="2" opacity=".5" fill="none"/><circle cx="22" cy="112" r="3" fill="' . $accent . '" opacity=".7"/><circle cx="338" cy="132" r="3" fill="' . $accent2 . '" opacity=".7"/>';
    } else {
        $decor = '<path d="M28 28H82M28 28V82M332 28H278M332 28V82M28 492H82M28 492V438M332 492H278M332 492V438" stroke="' . $accent . '" stroke-width="2.5" opacity=".6" fill="none"/>';
    }
    return '<svg class="fqx-v140-preview-svg" xmlns="http://www.w3.org/2000/svg" width="360" height="520" viewBox="0 0 360 520" role="img" aria-label="Template ' . esc_attr((string) $number) . '"><defs><linearGradient id="g' . esc_attr($id) . '" x1="0" x2="1"><stop offset="0" stop-color="' . $accent . '"/><stop offset="1" stop-color="' . $accent2 . '"/></linearGradient><filter id="s' . esc_attr($id) . '"><feDropShadow dx="0" dy="8" stdDeviation="8" flood-color="#000" flood-opacity=".22"/></filter></defs><rect width="360" height="520" rx="20" fill="' . $bg . '"/><rect x="12" y="12" width="336" height="496" rx="18" fill="none" stroke="' . $accent . '" stroke-width="2" opacity=".65"/>' . $decor . '<text x="180" y="50" text-anchor="middle" fill="' . $text . '" font-family="Arial" font-size="27" font-weight="900">Fluuex<tspan fill="' . $accent . '">QR</tspan></text><text x="180" y="70" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="8" letter-spacing="3">HOTEL &amp; RESTAURANT AUTOMATION</text><rect x="84" y="92" width="192" height="46" rx="23" fill="' . ($is_light ? '#111827' : 'rgba(0,0,0,.35)') . '" stroke="' . $accent . '" stroke-width="2"/><text x="180" y="124" text-anchor="middle" fill="' . ($is_light ? '#fff' : $accent) . '" font-family="Arial" font-size="27" font-weight="900">Room ' . esc_html((string) $room) . '</text><rect x="' . $menu_x . '" y="' . $menu_y . '" width="' . $menu_w . '" height="' . $menu_h . '" rx="18" fill="' . $panel . '" stroke="' . $accent . '" stroke-width="2" filter="url(#s' . esc_attr($id) . ')"/><text x="' . ($menu_x + $menu_w / 2) . '" y="' . ($menu_y + 36) . '" text-anchor="middle" fill="' . $accent . '" font-family="Arial" font-size="23" font-weight="900">MENU QR</text><rect x="' . ($menu_x + 30) . '" y="' . ($menu_y + 58) . '" width="' . ($menu_w - 60) . '" height="' . ($menu_w - 60) . '" rx="12" fill="' . $qr_bg . '"/><path d="M' . ($menu_x + 47) . ' ' . ($menu_y + 76) . 'h20v20h-20zM' . ($menu_x + $menu_w - 67) . ' ' . ($menu_y + 76) . 'h20v20h-20zM' . ($menu_x + 47) . ' ' . ($menu_y + $menu_w - 4) . 'h20v20h-20z" fill="#111"/><path d="M' . ($menu_x + 82) . ' ' . ($menu_y + 86) . 'h12v12h-12zM' . ($menu_x + 112) . ' ' . ($menu_y + 104) . 'h10v10h-10zM' . ($menu_x + 76) . ' ' . ($menu_y + 130) . 'h16v16h-16zM' . ($menu_x + 122) . ' ' . ($menu_y + 139) . 'h13v13h-13zM' . ($menu_x + 98) . ' ' . ($menu_y + 158) . 'h11v11h-11z" fill="#111"/><circle cx="' . ($menu_x + $menu_w / 2) . '" cy="' . ($menu_y + $menu_w / 2 + 18) . '" r="16" fill="' . $accent . '"/><text x="' . ($menu_x + $menu_w / 2) . '" y="' . ($menu_y + $menu_h - 44) . '" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="12">Scan to order</text><rect x="' . $wifi_x . '" y="' . $wifi_y . '" width="82" height="120" rx="16" fill="' . $panel . '" stroke="' . $accent . '" stroke-width="2"/><text x="' . ($wifi_x + 41) . '" y="' . ($wifi_y + 28) . '" text-anchor="middle" fill="' . $accent . '" font-family="Arial" font-size="15" font-weight="900">WIFI</text><rect x="' . ($wifi_x + 19) . '" y="' . ($wifi_y + 42) . '" width="44" height="44" rx="8" fill="' . $qr_bg . '"/><path d="M' . ($wifi_x + 27) . ' ' . ($wifi_y + 50) . 'h10v10h-10zM' . ($wifi_x + 48) . ' ' . ($wifi_y + 50) . 'h8v8h-8zM' . ($wifi_x + 30) . ' ' . ($wifi_y + 70) . 'h9v9h-9zM' . ($wifi_x + 50) . ' ' . ($wifi_y + 71) . 'h7v7h-7z" fill="#111"/><text x="' . ($wifi_x + 41) . '" y="' . ($wifi_y + 105) . '" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="9">Hotel_Guest</text><rect x="28" y="438" width="304" height="48" rx="15" fill="' . $footer . '" stroke="' . $accent . '" opacity=".96"/><text x="78" y="467" text-anchor="middle" fill="' . ($is_light ? '#fff' : $text) . '" font-family="Arial" font-size="10">Browse</text><text x="145" y="467" text-anchor="middle" fill="' . ($is_light ? '#fff' : $text) . '" font-family="Arial" font-size="10">Order</text><text x="215" y="467" text-anchor="middle" fill="' . ($is_light ? '#fff' : $text) . '" font-family="Arial" font-size="10">Track</text><text x="282" y="467" text-anchor="middle" fill="' . ($is_light ? '#fff' : $text) . '" font-family="Arial" font-size="10">Bill</text><text x="180" y="504" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="10">Powered by FluuexQR</text></svg>';
}

function fqx_v134_room_template_picker_html($selected = 'luxury_black_gold'): string {
    $selected = fqx_v134_normalize_room_template($selected ?: 'luxury_black_gold');
    $templates = fqx_v134_room_template_catalog();
    ob_start(); ?>
    <div class="fqx-v134-template-picker fqx-v141-template-picker" data-selected-template="<?php echo esc_attr($selected); ?>">
        <div class="fqx-v141-picker-head">
            <div>
                <strong>Select Template</strong>
                <span>Selected Template is highlighted below. It will be used for Preview, Print, Download PNG, Download PDF and WiFi QR card.</span>
            </div>
            <em>Selected Template: <b class="fqx-v144-current-template"><?php echo esc_html($templates[$selected]['name'] ?? 'Template 01'); ?></b></em>
        </div>
        <div class="fqx-v141-template-grid" role="radiogroup" aria-label="Room QR template options">
            <?php $i = 1; foreach ($templates as $id => $tpl) : $checked = ($id === $selected); ?>
                <label class="fqx-v141-template-option <?php echo $checked ? 'is-selected' : ''; ?>" data-template-id="<?php echo esc_attr($id); ?>">
                    <input class="fqx-v141-template-radio" type="radio" name="room_qr_template" value="<?php echo esc_attr($id); ?>" <?php checked($checked); ?>>
                    <span class="fqx-v141-template-card">
                        <span class="fqx-v141-template-top"><span>Template <?php echo esc_html(str_pad((string) $i, 2, '0', STR_PAD_LEFT)); ?></span><b><?php echo $checked ? 'Selected ✓' : 'Select Template'; ?></b></span>
                        <span class="fqx-v141-template-preview"><?php echo fqx_v140_room_template_preview_svg($id, $tpl, $i); ?></span>
                        <span class="fqx-v141-template-name"><?php echo esc_html((string) $tpl['name']); ?></span>
                        <span class="fqx-v141-template-cta"><?php echo $checked ? 'Selected ✓' : 'Tap to Select'; ?></span>
                    </span>
                </label>
            <?php $i++; endforeach; ?>
        </div>
        <div class="fqx-v141-picker-note"><b>Selected Template:</b> <span class="fqx-v144-selected-template-name"><?php echo esc_html($templates[$selected]['name'] ?? 'Template 01'); ?></span>. After selecting, click <b>Save Template / Update Room</b>. Existing Table QR workflow is unchanged.</div>
    </div>
    <?php return (string) ob_get_clean();
}

function fqx_v134_svg_text($text): string { return esc_html((string) $text); }

function fqx_v134_qr_data_uri(string $data, int $size = 300): string {
    $cache_key = 'fqx_v134_qr_' . md5($data . '|' . $size);
    $cached = get_transient($cache_key);
    if (is_string($cached) && $cached !== '') { return $cached; }
    $url = menuqr_get_real_qr_image_url($data, $size, 'png');
    $response = wp_remote_get($url, ['timeout' => 12]);
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        if ($body) {
            $uri = 'data:image/png;base64,' . base64_encode($body);
            set_transient($cache_key, $uri, WEEK_IN_SECONDS);
            return $uri;
        }
    }
    return esc_url($url);
}

function fqx_v134_render_room_card_svg(int $restaurant_id, $room, string $template = ''): string {
    $templates = fqx_v134_room_template_catalog();
    $template_id = fqx_v134_normalize_room_template($template ?: fqx_v134_get_room_template_id($room));
    $tpl = $templates[$template_id];
    $is_light = $tpl['theme'] === 'light';
    $restaurant = menuqr_get_restaurant($restaurant_id);
    $restaurant_name = $restaurant ? fqx_v134_svg_text((string) $restaurant->name) : 'FluuexQR Hotel';
    $hotel_name = $restaurant_name ?: 'Hotel / Restaurant';
    $logo = $restaurant && !empty($restaurant->logo) ? esc_url((string) $restaurant->logo) : (function_exists('fqx_get_brand_logo_url') ? esc_url(fqx_get_brand_logo_url('compact')) : '');
    $room_number = fqx_v134_svg_text((string) $room->room_number);
    $menu_url = add_query_arg(['qr_type' => 'room'], menuqr_get_room_menu_url($restaurant_id, (int) $room->id));
    $menu_qr = esc_attr(fqx_v134_qr_data_uri($menu_url, 430));
    $wifi = fqx_get_room_wifi_settings($restaurant_id, (int) $room->id);
    $wifi_enabled = $wifi && (int) $wifi->wifi_enabled === 1 && (int) $wifi->show_wifi_qr === 1 && trim((string) $wifi->ssid) !== '';
    $ssid_raw = $wifi_enabled ? (string) $wifi->ssid : '';
    $ssid = $wifi_enabled ? fqx_v134_svg_text($ssid_raw) : '';
    $wifi_password = $wifi_enabled ? fqx_decrypt_wifi_password((string) $wifi->password_encrypted) : '';
    $wifi_qr = $wifi_enabled ? esc_attr(fqx_v134_qr_data_uri(fqx_generate_wifi_qr_data($ssid_raw, $wifi_password, (string) $wifi->security_type), 190)) : '';
    $show_password = $wifi_enabled && (int) $wifi->show_password === 1;
    $show_ssid = $wifi_enabled && (int) ($wifi->show_ssid ?? 1) === 1;
    $accent = $tpl['accent']; $bg = $tpl['bg']; $panel = $tpl['panel']; $text = $tpl['text']; $muted = $tpl['muted'];
    $qr_box = $is_light ? '#ffffff' : '#f8fafc';
    $footer_bg = $is_light ? '#111827' : 'rgba(255,255,255,.06)';
    $footer_text = $is_light ? '#ffffff' : $text;
    $logo_svg = $logo ? '<image href="' . $logo . '" x="250" y="34" width="100" height="82" preserveAspectRatio="xMidYMid meet"/>' : '<circle cx="300" cy="74" r="42" fill="' . ($is_light ? '#fff' : '#0f172a') . '" stroke="' . $accent . '" stroke-width="4"/><text x="300" y="84" text-anchor="middle" font-size="28" font-weight="900" font-family="Arial" fill="' . $accent . '">FQ</text>';
    $wifi_block = $wifi_enabled ? '<text x="436" y="366" text-anchor="middle" fill="' . $accent . '" font-family="Arial" font-size="30" font-weight="900">WIFI QR</text><text x="436" y="402" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="18">Scan to connect</text><text x="436" y="426" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="18">to WiFi</text><rect x="362" y="452" width="148" height="148" rx="18" fill="' . $qr_box . '" stroke="' . $accent . '" stroke-width="3"/><image href="' . $wifi_qr . '" x="377" y="467" width="118" height="118"/>' . ($show_ssid ? '<text x="436" y="648" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="17">WiFi Name:</text><text x="436" y="675" text-anchor="middle" fill="' . $accent . '" font-family="Arial" font-size="21" font-weight="900">' . $ssid . '</text>' : '') . ($show_password ? '<text x="436" y="714" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="17">Password:</text><text x="436" y="741" text-anchor="middle" fill="' . $accent . '" font-family="Arial" font-size="21" font-weight="900">' . fqx_v134_svg_text($wifi_password) . '</text>' : '') : '';
    $wifi_panel_split = $wifi_enabled ? '<rect x="378" y="330" width="162" height="430" rx="30" fill="' . $panel . '" stroke="' . $accent . '" stroke-width="4"/>' . $wifi_block : '';
    $wifi_panel_stacked = $wifi_enabled ? '<rect x="82" y="694" width="436" height="150" rx="28" fill="' . $panel . '" stroke="' . $accent . '" stroke-width="3"/>' . str_replace(['x="436"','y="366"','y="402"','y="426"','x="362"','y="452"','x="377"','y="467"','y="648"','y="675"','y="714"','y="741"'], ['x="300"','y="724"','y="752"','y="776"','x="118"','y="714"','x="133"','y="729"','y="785"','y="812"','y="822"','y="842"'], $wifi_block) : '';
    if ($tpl['layout'] === 'stacked') {
        $main = '<rect x="82" y="330" width="436" height="340" rx="34" fill="' . $panel . '" stroke="' . $accent . '" stroke-width="4"/><text x="300" y="386" text-anchor="middle" fill="' . $accent . '" font-family="Arial" font-size="34" font-weight="900">MENU QR</text><text x="300" y="420" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="18">Scan to view menu &amp; order room service</text><rect x="185" y="448" width="230" height="190" rx="24" fill="' . $qr_box . '"/><image href="' . $menu_qr . '" x="207" y="463" width="186" height="160"/>' . $wifi_panel_stacked;
    } else {
        $main = '<rect x="60" y="330" width="295" height="430" rx="34" fill="' . $panel . '" stroke="' . $accent . '" stroke-width="4"/><text x="208" y="386" text-anchor="middle" fill="' . $accent . '" font-family="Arial" font-size="34" font-weight="900">MENU QR</text><text x="208" y="420" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="18">Scan to view menu &amp;</text><text x="208" y="445" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="18">order room service</text><rect x="94" y="480" width="228" height="228" rx="26" fill="' . $qr_box . '"/><image href="' . $menu_qr . '" x="116" y="500" width="184" height="184"/>' . ($tpl['layout'] === 'split_button' ? '<rect x="112" y="716" width="192" height="48" rx="18" fill="' . $accent . '"/><text x="208" y="748" text-anchor="middle" fill="#ffffff" font-family="Arial" font-size="20" font-weight="900">SCAN TO ORDER</text>' : '') . '' . $wifi_panel_split;
    }
    return '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="960" viewBox="0 0 600 960" role="img" aria-label="Room ' . $room_number . ' QR card">
<defs><linearGradient id="roomBadge" x1="0" x2="1"><stop offset="0" stop-color="' . $accent . '"/><stop offset="1" stop-color="#fb923c"/></linearGradient><filter id="shadow"><feDropShadow dx="0" dy="16" stdDeviation="16" flood-color="#000" flood-opacity=".22"/></filter></defs>
<rect width="600" height="960" rx="42" fill="' . $bg . '"/><rect x="20" y="20" width="560" height="920" rx="36" fill="' . $bg . '" stroke="' . $accent . '" stroke-width="3" opacity=".95"/>
' . $logo_svg . '<text x="300" y="135" text-anchor="middle" fill="' . $text . '" font-family="Arial" font-size="36" font-weight="900">FluuexQR</text><text x="300" y="160" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="13" letter-spacing="5">HOTEL &amp; RESTAURANT AUTOMATION</text>
<text x="300" y="216" text-anchor="middle" fill="' . $text . '" font-family="Georgia" font-size="32">' . $hotel_name . '</text><rect x="168" y="242" width="264" height="62" rx="31" fill="' . ($is_light ? '#111827' : 'rgba(0,0,0,.45)') . '" stroke="' . $accent . '" stroke-width="3"/><text x="300" y="284" text-anchor="middle" fill="' . $accent . '" font-family="Arial" font-size="37" font-weight="900">Room ' . $room_number . '</text>
' . $main . '
<rect x="60" y="790" width="480" height="92" rx="24" fill="' . $footer_bg . '" stroke="' . $accent . '" stroke-width="2" opacity=".98"/><g font-family="Arial" font-size="16" font-weight="800" fill="' . $footer_text . '"><text x="118" y="830" text-anchor="middle">📖</text><text x="118" y="858" text-anchor="middle">Browse Menu</text><text x="240" y="830" text-anchor="middle">🍽</text><text x="240" y="858" text-anchor="middle">Order Food</text><text x="360" y="830" text-anchor="middle">🛵</text><text x="360" y="858" text-anchor="middle">Track Order</text><text x="480" y="830" text-anchor="middle">🧾</text><text x="480" y="858" text-anchor="middle">View Bill</text></g>
<text x="300" y="920" text-anchor="middle" fill="' . $muted . '" font-family="Arial" font-size="17">Powered by <tspan fill="' . $accent . '" font-weight="900">FluuexQR</tspan></text>
</svg>';
}

function fqx_v133_room_card_svg(int $restaurant_id, $room, string $template = ''): string {
    return fqx_v134_render_room_card_svg($restaurant_id, $room, $template);
}

function fqx_v133_handle_room_qr_card_download(): void {
    if (empty($_GET['menuqr_room_qr_card_download'])) { return; }
    $restaurant_id = absint($_GET['restaurant_id'] ?? 0);
    $room_id = absint($_GET['room_id'] ?? 0);
    $nonce = sanitize_text_field(wp_unslash($_GET['_menuqr'] ?? ''));
    if (!$restaurant_id || !$room_id || !$nonce || !wp_verify_nonce($nonce, 'menuqr_room_qr_card_' . $restaurant_id . '_' . $room_id)) { wp_die(esc_html__('Invalid room QR request.', 'menuqr')); }
    if (!is_user_logged_in()) { auth_redirect(); }
    if (!current_user_can('manage_options') && $restaurant_id !== menuqr_get_current_restaurant_id()) { wp_die(esc_html__('Restaurant access denied.', 'menuqr')); }
    global $wpdb;
    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('rooms') . " WHERE id = %d AND restaurant_id = %d", $room_id, $restaurant_id));
    if (!$room) { wp_die(esc_html__('Room not found.', 'menuqr')); }
    nocache_headers();
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="room-card-' . sanitize_file_name((string) $room->room_number) . '-menu-wifi.svg"');
    echo fqx_v133_room_card_svg($restaurant_id, $room);
    exit;
}
add_action('init', function (): void {
    if (function_exists('menuqr_handle_room_qr_card_download')) { remove_action('template_redirect', 'menuqr_handle_room_qr_card_download'); }
});
add_action('template_redirect', 'fqx_v133_handle_room_qr_card_download', 1);

function fqx_v133_handle_room_qr_zip(): void {
    if (empty($_GET['fqx_room_qr_cards_zip'])) { return; }
    $restaurant_id = absint($_GET['restaurant_id'] ?? 0);
    $nonce = sanitize_text_field(wp_unslash($_GET['_fqx'] ?? ''));
    if (!$restaurant_id || !$nonce || !wp_verify_nonce($nonce, 'fqx_room_qr_zip_' . $restaurant_id)) { wp_die('Invalid ZIP request.'); }
    if (!is_user_logged_in()) { auth_redirect(); }
    if (!current_user_can('manage_options') && $restaurant_id !== menuqr_get_current_restaurant_id()) { wp_die('Restaurant access denied.'); }
    if (!class_exists('ZipArchive')) { wp_die('ZIP extension is not available on this server. Download each room card separately.'); }
    global $wpdb;
    $rooms = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . menuqr_table('rooms') . " WHERE restaurant_id = %d ORDER BY room_number ASC", $restaurant_id));
    $zip_path = trailingslashit(get_temp_dir()) . 'fluuexqr-room-cards-' . $restaurant_id . '-' . time() . '.zip';
    $zip = new ZipArchive();
    if (true !== $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) { wp_die('Could not create ZIP file.'); }
    foreach ($rooms as $room) { $zip->addFromString('room-' . sanitize_file_name((string) $room->room_number) . '-menu-wifi.svg', fqx_v133_room_card_svg($restaurant_id, $room)); }
    $zip->close();
    nocache_headers();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="fluuexqr-room-cards.zip"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);
    @unlink($zip_path);
    exit;
}
add_action('template_redirect', 'fqx_v133_handle_room_qr_zip', 1);

function fqx_v137_is_room_context_request(): bool {
    $room_id = absint($_GET['room_id'] ?? ($_GET['room'] ?? 0));
    $source = sanitize_key(wp_unslash($_GET['source'] ?? $_GET['order_source'] ?? $_GET['qr_type'] ?? ''));
    return $room_id > 0 || in_array($source, ['room', 'room_qr', 'hotel_room'], true);
}

function fqx_v137_is_restaurant_room_admin_screen(): bool {
    if (is_admin()) { return true; }
    $tab = sanitize_key(wp_unslash($_GET['tab'] ?? ''));
    $action = sanitize_key(wp_unslash($_GET['action'] ?? ''));
    return is_page('dashboard') && in_array($tab, ['rooms', 'wifi'], true);
}

function fqx_v133_enqueue_assets(): void {
    $css = get_template_directory() . '/assets/css/fqx-v133-room-session-wifi-card.css';
    $js = get_template_directory() . '/assets/js/fqx-v133-room-session.js';

    // Performance fix: do not load Room QR/WiFi template CSS on every public page.
    // Load it only on room menu sessions and Restaurant Admin Rooms/WiFi screens.
    if ((is_page('menu') && fqx_v137_is_room_context_request()) || fqx_v137_is_restaurant_room_admin_screen()) {
        wp_enqueue_style('fqx-v133-room-session-wifi-card', get_template_directory_uri() . '/assets/css/fqx-v133-room-session-wifi-card.css', [], file_exists($css) ? (string) filemtime($css) : '137');
    }

    if (is_page('menu') && fqx_v137_is_room_context_request()) {
        wp_enqueue_script('fqx-v133-room-session', get_template_directory_uri() . '/assets/js/fqx-v133-room-session.js', ['jquery'], file_exists($js) ? (string) filemtime($js) : '137', true);
    }
}
add_action('wp_enqueue_scripts', 'fqx_v133_enqueue_assets', 1300);

function fqx_v133_room_qr_card_print_url(int $restaurant_id, int $room_id): string {
    return add_query_arg([
        'fqx_room_qr_card_print' => 1,
        'restaurant_id' => $restaurant_id,
        'room_id' => $room_id,
        '_fqx' => wp_create_nonce('fqx_room_qr_card_print_' . $restaurant_id . '_' . $room_id),
    ], home_url('/'));
}
function fqx_v133_handle_room_qr_card_print(): void {
    if (empty($_GET['fqx_room_qr_card_print'])) { return; }
    $restaurant_id = absint($_GET['restaurant_id'] ?? 0);
    $room_id = absint($_GET['room_id'] ?? 0);
    $nonce = sanitize_text_field(wp_unslash($_GET['_fqx'] ?? ''));
    if (!$restaurant_id || !$room_id || !$nonce || !wp_verify_nonce($nonce, 'fqx_room_qr_card_print_' . $restaurant_id . '_' . $room_id)) { wp_die('Invalid print request.'); }
    if (!is_user_logged_in()) { auth_redirect(); }
    if (!current_user_can('manage_options') && $restaurant_id !== menuqr_get_current_restaurant_id()) { wp_die('Restaurant access denied.'); }
    global $wpdb;
    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('rooms') . " WHERE id = %d AND restaurant_id = %d", $room_id, $restaurant_id));
    if (!$room) { wp_die('Room not found.'); }
    nocache_headers();
    ?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Room <?php echo esc_html((string) $room->room_number); ?> QR Card</title><style>body{margin:0;background:#f8fafc;font-family:Arial,sans-serif}.wrap{min-height:100vh;display:grid;place-items:center;padding:24px}.toolbar{position:fixed;top:14px;right:14px;display:flex;gap:8px}.toolbar button{border:0;border-radius:12px;padding:10px 14px;background:#ff6b00;color:#fff;font-weight:800;cursor:pointer}.card{max-width:600px;width:100%;box-shadow:0 24px 60px rgba(15,23,42,.18)}.card svg{width:100%;height:auto;display:block}@media print{body{background:#fff}.toolbar{display:none}.wrap{padding:0}.card{box-shadow:none;max-width:none;width:100%}}</style></head><body><div class="toolbar"><button onclick="downloadRoomCardPng()">Download PNG</button><button onclick="window.print()">Print / Save PDF</button></div><div class="wrap"><div class="card" id="fqx-room-card"><?php echo fqx_v133_room_card_svg($restaurant_id, $room); ?></div></div><script>function downloadRoomCardPng(){var svg=document.querySelector('#fqx-room-card svg');if(!svg){return;}var data=new XMLSerializer().serializeToString(svg);var blob=new Blob([data],{type:'image/svg+xml;charset=utf-8'});var url=URL.createObjectURL(blob);var img=new Image();img.onload=function(){var c=document.createElement('canvas');c.width=1200;c.height=1920;var ctx=c.getContext('2d');ctx.fillStyle='#ffffff';ctx.fillRect(0,0,c.width,c.height);ctx.drawImage(img,0,0,c.width,c.height);URL.revokeObjectURL(url);var a=document.createElement('a');a.download='room-<?php echo esc_js(sanitize_file_name((string) $room->room_number)); ?>-qr-card.png';a.href=c.toDataURL('image/png');a.click();};img.onerror=function(){alert('PNG download is blocked by the browser because QR images are loaded from an external QR service. Please use Print / Save PDF or Download SVG.');};img.src=url;}</script></body></html><?php
    exit;
}
add_action('template_redirect', 'fqx_v133_handle_room_qr_card_print', 1);

// FluuexQR v134 — enqueue room template picker assets in restaurant admin.
function fqx_v134_enqueue_room_template_admin_assets(): void {
    $is_target = is_admin();
    if (!$is_target && function_exists('fqx_v137_is_restaurant_room_admin_screen')) {
        $is_target = fqx_v137_is_restaurant_room_admin_screen();
    }
    if (!$is_target) { return; }

    $css = get_template_directory() . '/assets/css/fqx-v133-room-session-wifi-card.css';
    $js = get_template_directory() . '/assets/js/fqx-v134-room-template-picker.js';
    wp_enqueue_style('fqx-v134-room-template-picker', get_template_directory_uri() . '/assets/css/fqx-v133-room-session-wifi-card.css', [], file_exists($css) ? (string) filemtime($css) : '137');
    wp_enqueue_script('fqx-v134-room-template-picker', get_template_directory_uri() . '/assets/js/fqx-v134-room-template-picker.js', [], file_exists($js) ? (string) filemtime($js) : '137', true);
}
add_action('admin_enqueue_scripts', 'fqx_v134_enqueue_room_template_admin_assets');
add_action('wp_enqueue_scripts', 'fqx_v134_enqueue_room_template_admin_assets', 1301);
