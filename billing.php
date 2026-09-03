<?php
if (!defined('ABSPATH')) {
    exit;
}

const MENUQR_BILL_SESSION_MINUTES = 240;

function menuqr_bill_session_minutes(): int {
    return (int) apply_filters('menuqr_bill_session_minutes', MENUQR_BILL_SESSION_MINUTES);
}

function menuqr_normalize_phone(string $phone): string {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10) {
        $phone = '91' . $phone;
    }
    return $phone ?: '';
}

function menuqr_sanitize_session_token(string $token): string {
    $token = preg_replace('/[^a-zA-Z0-9_\-]/', '', $token);
    if (!$token || strlen($token) < 16) {
        $token = wp_generate_password(32, false, false);
    }
    return substr($token, 0, 128);
}



function menuqr_normalize_order_source_label(string $source, int $room_id = 0): string {
    $source = sanitize_key($source);
    if ($room_id > 0 || in_array($source, ['room', 'room_qr', 'hotel_room'], true)) {
        return 'room';
    }
    return 'table';
}

function menuqr_is_room_order_source(string $source, int $room_id = 0): bool {
    return menuqr_normalize_order_source_label($source, $room_id) === 'room';
}

function menuqr_get_table_number_for_bill(int $restaurant_id, int $table_id): string {
    if ($table_id <= 0) { return ''; }
    if (function_exists('menuqr_get_table_display_name')) {
        return trim((string) menuqr_get_table_display_name($restaurant_id, $table_id, (string) $table_id));
    }
    return (string) $table_id;
}

function menuqr_get_room_number_for_bill(int $restaurant_id, int $room_id): string {
    if ($room_id <= 0) { return ''; }
    if (function_exists('menuqr_get_room_display_name')) {
        return trim((string) menuqr_get_room_display_name($restaurant_id, $room_id, (string) $room_id));
    }
    if (function_exists('menuqr_find_room_by_reference')) {
        $room = menuqr_find_room_by_reference($restaurant_id, $room_id);
        if ($room) {
            foreach (['room_number','number','name','room_name','label'] as $key) {
                if (!empty($room->{$key})) { return (string) $room->{$key}; }
            }
        }
    }
    return (string) $room_id;
}

function menuqr_get_bill_source_context(object $bill_or_session): array {
    $restaurant_id = (int) ($bill_or_session->restaurant_id ?? 0);
    $room_id = (int) ($bill_or_session->room_id ?? 0);
    $table_id = (int) ($bill_or_session->table_id ?? 0);
    $source = menuqr_normalize_order_source_label((string) ($bill_or_session->order_source ?? ''), $room_id);
    if ($source === 'room') {
        $room_number = trim((string) ($bill_or_session->room_number ?? '')) ?: menuqr_get_room_number_for_bill($restaurant_id, $room_id);
        return [
            'source' => 'room',
            'label' => 'Room No',
            'number' => $room_number,
            'order_type' => 'Room Service',
            'table_id' => 0,
            'room_id' => $room_id,
        ];
    }
    $table_number = trim((string) ($bill_or_session->table_number ?? '')) ?: menuqr_get_table_number_for_bill($restaurant_id, $table_id);
    return [
        'source' => 'table',
        'label' => 'Table No',
        'number' => $table_number,
        'order_type' => 'Dine In',
        'table_id' => $table_id,
        'room_id' => 0,
    ];
}

function menuqr_bill_download_pdf_url(object $bill): string {
    return add_query_arg('download_pdf', '1', menuqr_bill_access_url($bill));
}

function menuqr_bill_access_url(object $bill): string {
    $bill = menuqr_repair_bill_access_key($bill);
    $url = menuqr_get_page_url_by_slug('bill');
    if (!$url) {
        $url = home_url('/bill/');
    }

    return add_query_arg([
        'bill' => (int) $bill->id,
        'key'  => (string) $bill->access_key,
    ], $url);
}


function menuqr_bill_session_access_url(int $restaurant_id, int $table_id, string $session_token, int $room_id = 0, string $order_source = ''): string {
    $url = menuqr_get_page_url_by_slug('bill');
    if (!$url) {
        $url = home_url('/bill/');
    }

    $params = [
        'r' => $restaurant_id,
        'session' => menuqr_sanitize_session_token($session_token),
    ];
    if ($room_id > 0 || $order_source === 'room_qr') {
        $params['room_id'] = $room_id;
    } else {
        $params['t'] = $table_id;
    }

    return add_query_arg($params, $url);
}

function menuqr_print_bill_session_access_url(int $restaurant_id, int $table_id, string $session_token, int $room_id = 0, string $order_source = ''): string {
    return add_query_arg('print', '1', menuqr_bill_session_access_url($restaurant_id, $table_id, $session_token, $room_id, $order_source));
}

function menuqr_get_bill_by_session_public_access(int $restaurant_id, int $table_id, string $session_token, int $room_id = 0, string $order_source = ''): ?object {
    $session = menuqr_get_recent_bill_session($restaurant_id, $table_id, $session_token, $room_id, $order_source);
    if (!$session) {
        return null;
    }

    $bill = menuqr_recalculate_bill((int) $session->id);
    if ($bill && function_exists('fqx_v178_force_customer_paid_status_sync')) {
        $bill = fqx_v178_force_customer_paid_status_sync($bill);
    }
    return $bill;
}


function menuqr_get_bill_by_id(int $bill_id): ?object {
    global $wpdb;
    if ($bill_id <= 0) {
        return null;
    }
    $table = menuqr_table('bills');
    $bill = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $bill_id));
    return $bill ?: null;
}

function menuqr_get_bill_by_session(int $session_id): ?object {
    global $wpdb;
    if ($session_id <= 0) {
        return null;
    }
    $table = menuqr_table('bills');
    $bill = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE bill_session_id = %d ORDER BY id DESC LIMIT 1", $session_id));
    return $bill ?: null;
}


function menuqr_repair_bill_access_key(object $bill): object {
    global $wpdb;
    if (!empty($bill->access_key)) {
        return $bill;
    }

    $access_key = wp_generate_password(32, false, false);
    $wpdb->update(menuqr_table('bills'), [
        'access_key' => $access_key,
        'updated_at' => current_time('mysql'),
    ], ['id' => (int) $bill->id]);

    $bill->access_key = $access_key;
    return $bill;
}

function menuqr_get_bill_by_public_access(int $bill_id, string $key): ?object {
    $bill = menuqr_get_bill_by_id($bill_id);
    if (!$bill) {
        return null;
    }

    $bill = menuqr_repair_bill_access_key($bill);
    $stored_key = (string) ($bill->access_key ?? '');

    if (!$stored_key || !$key || !hash_equals($stored_key, $key)) {
        return null;
    }

    return $bill;
}

function menuqr_get_recent_bill_session(int $restaurant_id, int $table_id, string $session_token, int $room_id = 0, string $order_source = ''): ?object {
    global $wpdb;
    $sessions = menuqr_table('bill_sessions');
    $token = menuqr_sanitize_session_token($session_token);
    if (!$restaurant_id || (!$table_id && !$room_id) || !$token) {
        return null;
    }

    $lookback = gmdate('Y-m-d H:i:s', current_time('timestamp', true) - (menuqr_bill_session_minutes() * MINUTE_IN_SECONDS));
    $lookback = get_date_from_gmt($lookback);

        if ($room_id > 0 || $order_source === 'room_qr') {
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$sessions}
             WHERE restaurant_id = %d AND room_id = %d AND session_token = %s
               AND (expires_at > %s OR updated_at >= %s)
             ORDER BY 
               CASE WHEN status = 'active' THEN 0 ELSE 1 END,
               updated_at DESC,
               id DESC
             LIMIT 1",
            $restaurant_id,
            $room_id,
            $token,
            current_time('mysql'),
            $lookback
        ));
    } else {
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$sessions}
             WHERE restaurant_id = %d AND table_id = %d AND session_token = %s
               AND (expires_at > %s OR updated_at >= %s)
             ORDER BY 
               CASE WHEN status = 'active' THEN 0 ELSE 1 END,
               updated_at DESC,
               id DESC
             LIMIT 1",
            $restaurant_id,
            $table_id,
            $token,
            current_time('mysql'),
            $lookback
        ));
    }

    return $session ?: null;
}

function menuqr_get_active_bill_session(int $restaurant_id, int $table_id, string $session_token, int $room_id = 0, string $order_source = ''): ?object {
    global $wpdb;
    $sessions = menuqr_table('bill_sessions');
    $token = menuqr_sanitize_session_token($session_token);
    $now = current_time('mysql');

        if ($room_id > 0 || $order_source === 'room_qr') {
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$sessions}
             WHERE restaurant_id = %d AND room_id = %d AND session_token = %s AND status = 'active' AND expires_at > %s
             ORDER BY id DESC LIMIT 1",
            $restaurant_id,
            $room_id,
            $token,
            $now
        ));
    } else {
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$sessions}
             WHERE restaurant_id = %d AND table_id = %d AND session_token = %s AND status = 'active' AND expires_at > %s
             ORDER BY id DESC LIMIT 1",
            $restaurant_id,
            $table_id,
            $token,
            $now
        ));
    }

    return $session ?: null;
}

function menuqr_get_or_create_bill_session(int $restaurant_id, int $table_id, string $session_token, string $customer_name = '', string $customer_whatsapp = '', int $room_id = 0, string $order_source = ''): object {
    global $wpdb;
    $sessions = menuqr_table('bill_sessions');
    $token = menuqr_sanitize_session_token($session_token);
    $customer_name = sanitize_text_field($customer_name);
    $customer_whatsapp = menuqr_normalize_phone($customer_whatsapp);

    $room_id = absint($room_id);
    $order_source = sanitize_key($order_source ?: ($room_id > 0 ? 'room_qr' : 'table_qr'));
    $is_room_order = menuqr_is_room_order_source($order_source, $room_id);
    if ($is_room_order) { $table_id = 0; } else { $room_id = 0; }
    $table_number = $is_room_order ? '' : menuqr_get_table_number_for_bill($restaurant_id, $table_id);
    $room_number = $is_room_order ? menuqr_get_room_number_for_bill($restaurant_id, $room_id) : '';
    $session = menuqr_get_active_bill_session($restaurant_id, $table_id, $token, $room_id, $order_source);
    $now = current_time('mysql');

    if ($session) {
        $update = ['updated_at' => $now];
        if ($customer_name && empty($session->customer_name)) {
            $update['customer_name'] = $customer_name;
        }
        if ($customer_whatsapp && empty($session->customer_whatsapp)) {
            $update['customer_whatsapp'] = $customer_whatsapp;
        }
        if (count($update) > 1) {
            $wpdb->update($sessions, $update, ['id' => (int) $session->id]);
            $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sessions} WHERE id = %d", (int) $session->id));
        }
        return $session;
    }

    $session_minutes = $is_room_order ? 1440 : menuqr_bill_session_minutes();
    $expires = gmdate('Y-m-d H:i:s', current_time('timestamp', true) + ($session_minutes * MINUTE_IN_SECONDS));
    $wpdb->insert($sessions, [
        'restaurant_id'      => $restaurant_id,
        'table_id'           => $is_room_order ? 0 : $table_id,
        'table_number'       => $table_number ?: null,
        'room_id'            => $is_room_order ? $room_id : 0,
        'room_number'        => $room_number ?: null,
        'order_source'       => $is_room_order ? 'room_qr' : 'table_qr',
        'session_token'      => $token,
        'customer_name'      => $customer_name,
        'customer_whatsapp'  => $customer_whatsapp,
        'status'             => 'active',
        'started_at'         => $now,
        'expires_at'         => get_date_from_gmt($expires),
        'created_at'         => $now,
        'updated_at'         => $now,
    ]);

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sessions} WHERE id = %d", (int) $wpdb->insert_id));
}

function menuqr_build_bill_number(int $restaurant_id, int $bill_id): string {
    $year = current_time('Y');
    return sprintf('MQR-%d-%s-%06d', $restaurant_id, $year, $bill_id);
}

function menuqr_calculate_bill_payment_status(array $orders): string {
    if (empty($orders)) {
        return 'unpaid';
    }

    $has_pending = false;
    $has_failed = false;
    foreach ($orders as $order) {
        $status = strtolower((string) $order->payment_status);
        if ('paid' === $status) {
            continue;
        }
        if (in_array($status, ['pending', 'pending_verification'], true)) {
            $has_pending = true;
        } elseif (in_array($status, ['failed'], true)) {
            $has_failed = true;
        } else {
            return 'unpaid';
        }
    }

    if ($has_failed) {
        return 'failed';
    }
    if ($has_pending) {
        return 'pending';
    }

    return 'paid';
}

function menuqr_get_session_orders(int $session_id): array {
    global $wpdb;
    if ($session_id <= 0) {
        return [];
    }
    $orders = menuqr_table('orders');
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$orders} WHERE bill_session_id = %d AND order_status <> 'cancelled' ORDER BY created_at DESC, id DESC",
        $session_id
    )) ?: [];
}

function menuqr_recalculate_bill(int $session_id): ?object {
    global $wpdb;
    $sessions = menuqr_table('bill_sessions');
    $bills = menuqr_table('bills');
    $orders_table = menuqr_table('orders');

    $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sessions} WHERE id = %d", $session_id));
    if (!$session) {
        return null;
    }

    $orders = menuqr_get_session_orders($session_id);
    $subtotal = 0.0;
    $tax = 0.0;
    $service = 0.0;
    $grand = 0.0;
    $items_snapshot = [];
    $payment_methods = [];

    foreach ($orders as $order) {
        $subtotal += (float) $order->subtotal;
        $tax += (float) $order->tax;
        $service += (float) $order->service_charge;
        $grand += (float) $order->final_total;
        $payment_methods[] = (string) $order->payment_method;
        $items = json_decode((string) $order->items_json, true) ?: [];
        foreach ($items as $item) {
            $key = md5(($item['id'] ?? '') . '|' . ($item['name'] ?? '') . '|' . ($item['price'] ?? 0) . '|' . ($item['emoji'] ?? ''));
            if (!isset($items_snapshot[$key])) {
                $items_snapshot[$key] = [
                    'name' => sanitize_text_field((string) ($item['name'] ?? 'Item')),
                    'emoji' => sanitize_text_field((string) ($item['emoji'] ?? '🍽️')),
                    'image' => esc_url_raw((string) ($item['image'] ?? '')),
                    'qty' => 0,
                    'price' => (float) ($item['price'] ?? 0),
                    'total' => 0,
                ];
            }
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $items_snapshot[$key]['qty'] += $qty;
            $items_snapshot[$key]['total'] += ((float) ($item['price'] ?? 0)) * $qty;
        }
    }

    $restaurant_branding = menuqr_get_restaurant_branding_data((int) $session->restaurant_id);
    $restaurant_snapshot = [
        'name' => $restaurant_branding['name'] ?? get_bloginfo('name'),
        'tagline' => $restaurant_branding['tagline'] ?? '',
        'phone' => $restaurant_branding['phone'] ?? '',
        'address' => $restaurant_branding['address'] ?? '',
        'email' => $restaurant_branding['email'] ?? '',
        'logo' => $restaurant_branding['logo'] ?? '',
        'cover' => $restaurant_branding['cover'] ?? '',
        'gst_number' => $restaurant_branding['gst_number'] ?? '',
        'fssai_number' => $restaurant_branding['fssai_number'] ?? '',
        'currency_symbol' => $restaurant_branding['currency_symbol'] ?? '₹',
        'tax_label' => $restaurant_branding['tax_label'] ?? 'GST/Tax',
        'settings' => menuqr_get_restaurant_bill_settings((int) $session->restaurant_id),
    ];

    $payment_methods = array_values(array_unique(array_filter($payment_methods)));
    $payment_method = count($payment_methods) === 1 ? $payment_methods[0] : 'mixed';
    $payment_status = menuqr_calculate_bill_payment_status($orders);

    $bill = menuqr_get_bill_by_session($session_id);
    // v124: If the bill has already been manually or gateway marked as paid, keep it paid.
    // A stale unpaid order row should not make a paid bill show Due again on refresh/print.
    if ($bill && strtolower((string) $bill->payment_status) === 'paid') {
        $payment_status = 'paid';
        $payment_method = (string) ($bill->payment_method ?: $payment_method);
    }
    $now = current_time('mysql');
    $data = [
        'restaurant_id'        => (int) $session->restaurant_id,
        'table_id'             => (int) $session->table_id,
        'table_number'         => (string) ($session->table_number ?? ''),
        'room_id'              => (int) ($session->room_id ?? 0),
        'room_number'          => (string) ($session->room_number ?? ''),
        'order_source'         => (string) ($session->order_source ?? ((int)($session->room_id ?? 0) > 0 ? 'room_qr' : 'table_qr')),
        'bill_session_id'      => $session_id,
        'customer_name'        => (string) $session->customer_name,
        'customer_whatsapp'    => (string) $session->customer_whatsapp,
        'restaurant_snapshot'  => wp_json_encode($restaurant_snapshot),
        'items_snapshot'       => wp_json_encode(array_values($items_snapshot)),
        'subtotal'             => round($subtotal, 2),
        'tax'                  => round($tax, 2),
        'service_charge'       => round($service, 2),
        'discount'             => 0,
        'round_off'            => 0,
        'grand_total'          => round($grand, 2),
        'payment_method'       => $payment_method,
        'payment_status'       => $payment_status,
        'bill_status'          => ($bill && 'generated' === (string) $bill->bill_status) ? 'generated' : 'running',
        'updated_at'           => $now,
    ];

    if ($bill) {
        $wpdb->update($bills, $data, ['id' => (int) $bill->id]);
        $bill_id = (int) $bill->id;
    } else {
        $data['bill_number'] = 'PENDING';
        $data['access_key'] = wp_generate_password(32, false, false);
        $data['created_at'] = $now;
        $wpdb->insert($bills, $data);
        $bill_id = (int) $wpdb->insert_id;
        $wpdb->update($bills, ['bill_number' => menuqr_build_bill_number((int) $session->restaurant_id, $bill_id)], ['id' => $bill_id]);
    }

    $wpdb->query($wpdb->prepare("UPDATE {$orders_table} SET bill_id = %d WHERE bill_session_id = %d", $bill_id, $session_id));

    $bill = menuqr_get_bill_by_id($bill_id);
    return $bill ? menuqr_repair_bill_access_key($bill) : null;
}

function menuqr_attach_order_to_running_bill(int $order_id, int $restaurant_id, int $table_id, string $session_token, string $customer_name = '', string $customer_whatsapp = '', int $room_id = 0, string $order_source = ''): ?object {
    global $wpdb;
    $orders = menuqr_table('orders');
    $session = menuqr_get_or_create_bill_session($restaurant_id, $table_id, $session_token, $customer_name, $customer_whatsapp, $room_id, $order_source);
    if (!$session) {
        return null;
    }

    $is_room_order = menuqr_is_room_order_source($order_source, $room_id);
    $wpdb->update($orders, [
        'bill_session_id' => (int) $session->id,
        'table_id' => $is_room_order ? 0 : absint($table_id),
        'room_id' => $is_room_order ? absint($room_id) : 0,
        'order_source' => $is_room_order ? 'room_qr' : 'table_qr',
        'customer_name' => sanitize_text_field($customer_name),
        'customer_phone' => menuqr_normalize_phone($customer_whatsapp),
        'updated_at' => current_time('mysql'),
    ], ['id' => $order_id]);

    return menuqr_recalculate_bill((int) $session->id);
}

function menuqr_get_customer_bill_data(int $restaurant_id, int $table_id, string $session_token, int $room_id = 0, string $order_source = ''): array {
    $session = menuqr_get_recent_bill_session($restaurant_id, $table_id, $session_token, $room_id, $order_source);
    if (!$session) {
        return ['session' => null, 'bill' => null, 'orders' => [], 'bill_url' => ''];
    }

    $bill = menuqr_recalculate_bill((int) $session->id);
    $orders = menuqr_get_session_orders((int) $session->id);
    foreach ($orders as $order) {
        $order->items = json_decode((string) $order->items_json, true) ?: [];
    }

    $session_url = menuqr_bill_session_access_url($restaurant_id, $table_id, $session_token, $room_id, $order_source);
    $print_url = menuqr_print_bill_session_access_url($restaurant_id, $table_id, $session_token, $room_id, $order_source);

    return [
        'session' => $session,
        'bill' => $bill,
        'orders' => $orders,
        'bill_url' => $bill ? $session_url : '',
        'bill_direct_url' => $bill ? menuqr_bill_access_url($bill) : '',
        'print_url' => $bill ? $print_url : '',
        'review' => menuqr_get_review_public_payload($restaurant_id),
        'review_url' => $bill ? menuqr_review_click_url($restaurant_id, $table_id, 0, (int) $bill->bill_session_id, (string) ($session->customer_whatsapp ?? '')) : '',
        'source_context' => $bill ? menuqr_get_bill_source_context($bill) : [],
        'table_label' => $bill ? menuqr_get_bill_source_context($bill)['number'] : menuqr_get_table_display_name($restaurant_id, $table_id),
        'restaurant_branding' => menuqr_get_restaurant_branding_data($restaurant_id),
    ];
}

function menuqr_get_restaurant_bills(int $restaurant_id, int $limit = 100): array {
    global $wpdb;
    $bills = menuqr_table('bills');
    $sessions = menuqr_table('bill_sessions');
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT b.*, s.started_at, s.expires_at, s.status AS session_status
         FROM {$bills} b
         LEFT JOIN {$sessions} s ON s.id = b.bill_session_id
         WHERE b.restaurant_id = %d
         ORDER BY COALESCE(b.created_at, b.updated_at) DESC, b.id DESC
         LIMIT %d",
        $restaurant_id,
        $limit
    )) ?: [];

    // v128: normalize stale paid/unpaid values before Restaurant Admin renders the table.
    foreach ($rows as $idx => $bill) {
        if (function_exists('fqx_v128_sync_bill_payment_state')) {
            $rows[$idx] = fqx_v128_sync_bill_payment_state($bill);
        }
    }
    return $rows;
}


function menuqr_bill_whatsapp_url(object $bill): string {
    $phone = menuqr_normalize_phone((string) $bill->customer_whatsapp);
    if (!$phone) {
        return '';
    }

    $restaurant = json_decode((string) $bill->restaurant_snapshot, true) ?: [];
    $settings = menuqr_get_restaurant_bill_settings((int) $bill->restaurant_id);
    $review_settings = menuqr_get_review_settings((int) $bill->restaurant_id);
    $review_link = '';
    if (!empty($review_settings['enabled'])) {
        $review_link = menuqr_review_click_url((int) $bill->restaurant_id, (int) $bill->table_id, 0, (int) $bill->bill_session_id, (string) $bill->customer_whatsapp);
    }

    $source_context = menuqr_get_bill_source_context($bill);
    $table_label = ($source_context['label'] ?? 'Table No') . ': ' . ($source_context['number'] ?? '');
    $template = trim((string) ($settings['whatsapp_bill_template'] ?? ''));
    if ($template === '') {
        $template = "Hello, thank you for ordering from {restaurant_name}.

Your bill is ready.

Order ID: #{order_id}
Table: {table}
Total: {grand_total}
Payment Status: {payment_status}

View Bill:
{bill_url}";
    }

    $replacements = [
        '{restaurant_name}' => (string) ($restaurant['name'] ?? get_bloginfo('name')),
        '{order_id}' => (string) $bill->bill_number,
        '{table}' => $table_label,
        '{grand_total}' => menuqr_format_amount((float) $bill->grand_total, (string) ($restaurant['currency_symbol'] ?? '₹')),
        '{payment_status}' => strtoupper((string) $bill->payment_status),
        '{bill_url}' => menuqr_bill_access_url($bill),
        '{customer_name}' => (string) ($bill->customer_name ?: 'Guest'),
        '{review_url}' => $review_link,
    ];

    $message = strtr($template, $replacements);
    if ($review_link && strpos($message, $review_link) === false) {
        $message .= "

Please rate your experience:
" . $review_link;
    }

    return 'https://wa.me/' . rawurlencode($phone) . '?text=' . rawurlencode($message);
}


function menuqr_mark_bill_payment_status(int $bill_id, string $status): bool {
    global $wpdb;
    $status = sanitize_key($status);
    if (!in_array($status, ['paid', 'unpaid', 'pending', 'pending_verification', 'failed', 'refunded', 'cancelled'], true)) {
        return false;
    }

    $bill = menuqr_get_bill_by_id($bill_id);
    if (!$bill) {
        return false;
    }

    if (function_exists('fqx_v128_bill_columns_ready')) {
        fqx_v128_bill_columns_ready();
    }

    $now = current_time('mysql');
    $bills = menuqr_table('bills');
    $orders = menuqr_table('orders');
    $sessions = menuqr_table('bill_sessions');
    $order_payments = menuqr_table('order_payments');
    $payment_reference = ('paid' === $status) ? 'manual-paid' : '';
    $paid_at = ('paid' === $status) ? $now : null;

    // v128: Mark bill + every linked order together. This fixes Restaurant Admin saying Paid
    // while customer bill page still said Unpaid/Due because one recovered order row stayed unpaid.
    $wpdb->query($wpdb->prepare(
        "UPDATE {$orders}
         SET payment_status = %s,
             payment_method = CASE WHEN %s = 'paid' AND (payment_method IS NULL OR payment_method = '' OR payment_method = 'mixed') THEN 'cash' ELSE payment_method END,
             payment_reference = CASE WHEN %s = 'paid' AND (payment_reference IS NULL OR payment_reference = '') THEN %s ELSE payment_reference END,
             transaction_id = CASE WHEN %s = 'paid' AND (transaction_id IS NULL OR transaction_id = '') THEN %s ELSE transaction_id END,
             paid_at = CASE WHEN %s = 'paid' THEN IFNULL(paid_at, %s) ELSE NULL END,
             updated_at = %s
         WHERE bill_session_id = %d OR bill_id = %d",
        $status,
        $status,
        $status,
        $payment_reference,
        $status,
        $payment_reference,
        $status,
        $now,
        $now,
        (int) $bill->bill_session_id,
        (int) $bill->id
    ));

    $current = menuqr_recalculate_bill((int) $bill->bill_session_id) ?: $bill;
    $bill_data = [
        'payment_status' => $status,
        'payment_method' => ('paid' === $status && empty($current->payment_method)) ? 'cash' : (string) ($current->payment_method ?? $bill->payment_method ?? 'cash'),
        'transaction_id' => ('paid' === $status) ? ((string) ($current->transaction_id ?? '') ?: $payment_reference) : '',
        'paid_at' => $paid_at,
        'bill_status' => ('paid' === $status) ? 'generated' : (string) ($current->bill_status ?? $bill->bill_status ?? 'running'),
        'updated_at' => $now,
    ];

    $bill_updated = false !== $wpdb->update($bills, $bill_data, ['id' => (int) ($current->id ?? $bill_id)]);

    // v178: Keep admin bill status, customer bill page, tracker, and payment activity in sync.
    // Update every bill row connected with the same session, because older theme versions can create
    // more than one recovered bill row for the same table/room session.
    if ((int) $bill->bill_session_id > 0) {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$bills}
             SET payment_status = %s,
                 payment_method = CASE WHEN %s = 'paid' AND (payment_method IS NULL OR payment_method = '' OR payment_method = 'mixed') THEN 'cash' ELSE payment_method END,
                 transaction_id = CASE WHEN %s = 'paid' AND (transaction_id IS NULL OR transaction_id = '') THEN %s ELSE transaction_id END,
                 paid_at = CASE WHEN %s = 'paid' THEN IFNULL(paid_at, %s) ELSE NULL END,
                 bill_status = CASE WHEN %s = 'paid' THEN 'generated' ELSE bill_status END,
                 updated_at = %s
             WHERE bill_session_id = %d",
            $status,
            $status,
            $status,
            $payment_reference,
            $status,
            $now,
            $status,
            $now,
            (int) $bill->bill_session_id
        ));
    }

    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $order_payments)) === $order_payments) {
        $orders_linked = $wpdb->get_results($wpdb->prepare(
            "SELECT id, final_total FROM {$orders} WHERE bill_session_id = %d OR bill_id = %d",
            (int) $bill->bill_session_id,
            (int) $bill->id
        ));
        foreach ($orders_linked as $linked_order) {
            $oid = (int) $linked_order->id;
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$order_payments} WHERE order_id = %d AND bill_id = %d LIMIT 1", $oid, (int)$bill_id));
            if ($exists) {
                $wpdb->update($order_payments, [
                    'status' => $status,
                    'payment_method' => ('paid' === $status) ? 'cash' : (string) ($current->payment_method ?? $bill->payment_method ?? 'cash'),
                    'gateway' => ('paid' === $status) ? 'manual' : (string) ($current->gateway ?? 'manual'),
                    'transaction_id' => ('paid' === $status) ? $payment_reference : '',
                    'paid_at' => ('paid' === $status) ? $now : null,
                    'updated_at' => $now,
                ], ['id' => (int) $exists]);
            } elseif ('paid' === $status) {
                $wpdb->insert($order_payments, [
                    'restaurant_id' => (int) $bill->restaurant_id,
                    'order_id' => $oid,
                    'bill_id' => (int) $bill_id,
                    'amount' => (float) ($linked_order->final_total ?? $current->grand_total ?? $bill->grand_total ?? 0),
                    'currency' => 'INR',
                    'payment_method' => 'cash',
                    'gateway' => 'manual',
                    'transaction_id' => $payment_reference,
                    'status' => 'paid',
                    'paid_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    if ('paid' === $status) {
        $wpdb->update($sessions, [
            'status' => 'closed',
            'closed_at' => $now,
            'updated_at' => $now,
        ], ['id' => (int) $bill->bill_session_id]);
    } elseif (in_array($status, ['unpaid', 'pending', 'pending_verification'], true)) {
        $wpdb->update($sessions, [
            'status' => 'active',
            'closed_at' => null,
            'updated_at' => $now,
        ], ['id' => (int) $bill->bill_session_id]);
    }

    if (function_exists('fqx_v128_sync_bill_payment_state')) {
        $fresh = menuqr_get_bill_by_id((int) ($current->id ?? $bill_id));
        if ($fresh) { fqx_v128_sync_bill_payment_state($fresh); }
    }

    if (function_exists('menuqr_purge_all_caches_after_save')) {
        menuqr_purge_all_caches_after_save('bill_payment_status');
    }

    return $bill_updated;
}

function menuqr_bill_due_amount(object $bill): float {
    $status = strtolower((string) ($bill->payment_status ?? 'unpaid'));
    if (in_array($status, ['paid', 'refunded', 'cancelled'], true)) {
        return 0.0;
    }
    return max(0.0, (float) ($bill->grand_total ?? 0));
}

function menuqr_update_bill_after_order_payment(int $order_id): void {
    global $wpdb;
    $orders = menuqr_table('orders');
    $order = $wpdb->get_row($wpdb->prepare("SELECT bill_session_id FROM {$orders} WHERE id = %d", $order_id));
    if ($order && (int) $order->bill_session_id > 0) {
        menuqr_recalculate_bill((int) $order->bill_session_id);
    }
}
