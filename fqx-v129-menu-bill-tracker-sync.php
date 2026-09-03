<?php
/**
 * FluuexQR v129 Menu Bill Status + 4 Hour Multi Order Tracker Fix
 */
if (!defined('ABSPATH')) { exit; }

function fqx_v129_bill_status_columns_ready(): void {
    if (function_exists('fqx_v128_bill_columns_ready')) { fqx_v128_bill_columns_ready(); }
}
add_action('init', 'fqx_v129_bill_status_columns_ready', 4);

function fqx_v129_is_paid_status($status): bool {
    return in_array(strtolower((string) $status), ['paid', 'success', 'captured', 'completed'], true);
}

function fqx_v129_sync_customer_bill_payment_state(object $bill): object {
    global $wpdb;
    if (empty($bill->id)) { return $bill; }
    fqx_v129_bill_status_columns_ready();

    if (function_exists('fqx_v128_sync_bill_payment_state')) {
        $bill = fqx_v128_sync_bill_payment_state($bill);
    }

    $bills = menuqr_table('bills');
    $orders = menuqr_table('orders');
    $order_payments = menuqr_table('order_payments');
    $bill_id = (int) $bill->id;
    $session_id = (int) ($bill->bill_session_id ?? 0);
    $now = current_time('mysql');

    $has_paid_payment = false;
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $order_payments)) === $order_payments) {
        $has_paid_payment = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$order_payments} WHERE bill_id = %d AND status IN ('paid','success','captured','completed') ORDER BY id DESC LIMIT 1",
            $bill_id
        ));
    }

    $linked = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) total_orders,
                SUM(CASE WHEN payment_status IN ('paid','success','captured','completed') THEN 1 ELSE 0 END) paid_orders,
                MAX(paid_at) last_paid_at,
                MAX(transaction_id) last_transaction_id,
                MAX(payment_reference) last_payment_reference
         FROM {$orders}
         WHERE (bill_id = %d OR bill_session_id = %d) AND order_status <> 'cancelled'",
        $bill_id,
        $session_id
    ));

    $bill_is_paid = fqx_v129_is_paid_status($bill->payment_status ?? '');
    $all_orders_paid = $linked && (int) $linked->total_orders > 0 && (int) $linked->total_orders === (int) $linked->paid_orders;

    if ($bill_is_paid || $has_paid_payment || $all_orders_paid) {
        $transaction = (string) ($bill->transaction_id ?? '');
        if (!$transaction && $linked) {
            $transaction = (string) ($linked->last_transaction_id ?: $linked->last_payment_reference ?: 'manual-paid');
        }
        if (!$transaction) { $transaction = 'manual-paid'; }

        $wpdb->query($wpdb->prepare(
            "UPDATE {$orders}
             SET payment_status = 'paid',
                 payment_method = CASE WHEN payment_method IS NULL OR payment_method = '' OR payment_method = 'mixed' THEN 'cash' ELSE payment_method END,
                 payment_reference = CASE WHEN payment_reference IS NULL OR payment_reference = '' THEN %s ELSE payment_reference END,
                 transaction_id = CASE WHEN transaction_id IS NULL OR transaction_id = '' THEN %s ELSE transaction_id END,
                 paid_at = IFNULL(paid_at, %s),
                 updated_at = %s
             WHERE bill_id = %d OR bill_session_id = %d",
            $transaction,
            $transaction,
            $now,
            $now,
            $bill_id,
            $session_id
        ));

        $wpdb->update($bills, [
            'payment_status' => 'paid',
            'payment_method' => !empty($bill->payment_method) && $bill->payment_method !== 'mixed' ? (string) $bill->payment_method : 'cash',
            'transaction_id' => $transaction,
            'paid_at' => !empty($bill->paid_at) ? (string) $bill->paid_at : ($linked && !empty($linked->last_paid_at) ? (string) $linked->last_paid_at : $now),
            'bill_status' => 'generated',
            'updated_at' => $now,
        ], ['id' => $bill_id]);

        $fresh = menuqr_get_bill_by_id($bill_id);
        return $fresh ?: $bill;
    }

    return $bill;
}

function fqx_v129_bill_payload(object $bill, string $session_token = ''): array {
    $bill = fqx_v129_sync_customer_bill_payment_state($bill);
    $orders = menuqr_get_session_orders((int) $bill->bill_session_id);
    $i = 1;
    foreach ($orders as $order) {
        if (function_exists('menuqr_normalize_order_service_point')) { $order = menuqr_normalize_order_service_point($order); }
        $order->items = json_decode((string) $order->items_json, true) ?: [];
        $order->order_index = $i++;
        $order->bill_payment_status = (string) $bill->payment_status;
        $order->bill_url = menuqr_bill_access_url($bill);
        $order->bill_total = (float) $bill->grand_total;
    }
    global $wpdb;
    $session = null;
    if (!empty($bill->bill_session_id)) {
        $session = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . menuqr_table('bill_sessions') . ' WHERE id = %d', (int) $bill->bill_session_id));
    }
    $table_id = (int) ($bill->table_id ?? 0);
    $room_id = (int) ($bill->room_id ?? 0);
    $order_source = (string) ($bill->order_source ?? ($room_id > 0 ? 'room_qr' : 'table_qr'));
    $session_url = $session_token ? menuqr_bill_session_access_url((int)$bill->restaurant_id, $table_id, $session_token, $room_id, $order_source) : menuqr_bill_access_url($bill);
    return [
        'session' => $session,
        'bill' => $bill,
        'orders' => $orders,
        'bill_url' => $session_url,
        'bill_direct_url' => menuqr_bill_access_url($bill),
        'print_url' => add_query_arg('print', '1', menuqr_bill_access_url($bill)),
        'review' => menuqr_get_review_public_payload((int) $bill->restaurant_id),
        'review_url' => menuqr_review_click_url((int) $bill->restaurant_id, $table_id, 0, (int) $bill->bill_session_id, (string) ($bill->customer_whatsapp ?? '')),
        'source_context' => menuqr_get_bill_source_context($bill),
        'table_label' => menuqr_get_bill_source_context($bill)['number'] ?? '',
        'restaurant_branding' => menuqr_get_restaurant_branding_data((int) $bill->restaurant_id),
    ];
}

function fqx_v129_find_latest_bill_for_service(int $restaurant_id, int $table_id = 0, int $room_id = 0, string $order_source = '', int $order_id = 0): ?object {
    global $wpdb;
    if ($order_id > 0) {
        $order = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . menuqr_table('orders') . ' WHERE id = %d LIMIT 1', $order_id));
    } else {
        $orders = menuqr_table('orders');
        $lookback = get_date_from_gmt(gmdate('Y-m-d H:i:s', current_time('timestamp', true) - (4 * HOUR_IN_SECONDS)));
        $is_room = $room_id > 0 || in_array($order_source, ['room', 'room_qr', 'hotel_room'], true);
        if ($is_room) {
            $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders} WHERE restaurant_id = %d AND room_id = %d AND created_at >= %s AND order_status <> 'cancelled' ORDER BY id DESC LIMIT 1", $restaurant_id, $room_id, $lookback));
        } else {
            $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders} WHERE restaurant_id = %d AND table_id = %d AND created_at >= %s AND order_status <> 'cancelled' ORDER BY id DESC LIMIT 1", $restaurant_id, $table_id, $lookback));
        }
    }
    if (empty($order)) { return null; }

    $bill = null;
    if (!empty($order->bill_id)) { $bill = menuqr_get_bill_by_id((int) $order->bill_id); }
    if (!$bill && !empty($order->bill_session_id)) { $bill = menuqr_recalculate_bill((int) $order->bill_session_id); }
    if (!$bill && function_exists('menuqr_v123_force_bill_for_order')) { $bill = menuqr_v123_force_bill_for_order((int) $order->id, ''); }
    return $bill ? fqx_v129_sync_customer_bill_payment_state(menuqr_repair_bill_access_key($bill)) : null;
}

function fqx_v129_ajax_get_customer_bill(): void {
    menuqr_verify_ajax();
    nocache_headers();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    $restaurant_id = absint($_REQUEST['restaurant_id'] ?? 0);
    $table_id = absint($_REQUEST['table_id'] ?? 0);
    $room_id = absint($_REQUEST['room_id'] ?? 0);
    $order_source = sanitize_key(wp_unslash($_REQUEST['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr')));
    $session_token = menuqr_sanitize_session_token(sanitize_text_field(wp_unslash($_REQUEST['bill_session_token'] ?? '')));
    $order_id = absint($_REQUEST['order_id'] ?? ($_REQUEST['last_order_id'] ?? 0));

    if (!$restaurant_id || (!$table_id && !$room_id)) {
        menuqr_json_response(false, ['message' => 'Missing table/room for bill.'], 400);
    }

    $bill = null;
    if ($session_token) {
        $session = menuqr_get_recent_bill_session($restaurant_id, $table_id, $session_token, $room_id, $order_source);
        if ($session) { $bill = menuqr_recalculate_bill((int) $session->id); }
    }
    if (!$bill && $order_id) { $bill = fqx_v129_find_latest_bill_for_service($restaurant_id, $table_id, $room_id, $order_source, $order_id); }
    if (!$bill) { $bill = fqx_v129_find_latest_bill_for_service($restaurant_id, $table_id, $room_id, $order_source); }

    if (!$bill) {
        menuqr_json_response(true, ['session' => null, 'bill' => null, 'orders' => [], 'bill_url' => '', 'bill_direct_url' => '', 'print_url' => '']);
    }

    menuqr_json_response(true, fqx_v129_bill_payload($bill, $session_token));
}

function fqx_v129_ajax_get_running_order_tracker(): void {
    // Same payload as bill, but endpoint name makes JS intent clear.
    fqx_v129_ajax_get_customer_bill();
}

function fqx_v129_replace_customer_bill_ajax(): void {
    remove_action('wp_ajax_menuqr_get_customer_bill', 'menuqr_ajax_get_customer_bill');
    remove_action('wp_ajax_nopriv_menuqr_get_customer_bill', 'menuqr_ajax_get_customer_bill');
    remove_action('wp_ajax_menuqr_get_customer_bill', 'fqx_v128_ajax_get_customer_bill');
    remove_action('wp_ajax_nopriv_menuqr_get_customer_bill', 'fqx_v128_ajax_get_customer_bill');
    add_action('wp_ajax_menuqr_get_customer_bill', 'fqx_v129_ajax_get_customer_bill');
    add_action('wp_ajax_nopriv_menuqr_get_customer_bill', 'fqx_v129_ajax_get_customer_bill');
    add_action('wp_ajax_menuqr_get_running_order_tracker', 'fqx_v129_ajax_get_running_order_tracker');
    add_action('wp_ajax_nopriv_menuqr_get_running_order_tracker', 'fqx_v129_ajax_get_running_order_tracker');
}
add_action('init', 'fqx_v129_replace_customer_bill_ajax', 6);

function fqx_v129_enqueue_menu_bill_tracker_assets(): void {
    if (!is_page(['menu', 'bill', 'order-status', 'cart', 'checkout'])) { return; }
    $js = get_template_directory() . '/assets/js/fqx-v129-menu-bill-tracker-sync.js';
    $css = get_template_directory() . '/assets/css/fqx-v129-menu-bill-tracker-sync.css';
    wp_enqueue_style('fqx-v129-menu-bill-tracker-sync', get_template_directory_uri() . '/assets/css/fqx-v129-menu-bill-tracker-sync.css', [], file_exists($css) ? (string) filemtime($css) : '129');
    wp_enqueue_script('fqx-v129-menu-bill-tracker-sync', get_template_directory_uri() . '/assets/js/fqx-v129-menu-bill-tracker-sync.js', ['jquery'], file_exists($js) ? (string) filemtime($js) : '129', true);
}
add_action('wp_enqueue_scripts', 'fqx_v129_enqueue_menu_bill_tracker_assets', 99);
