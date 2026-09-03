<?php
/**
 * FluuexQR v128 Real Bill + Paid Status Sync
 * Fixes customer bill recovery and prevents paid bills showing unpaid/due in restaurant admin or bill page.
 */
if (!defined('ABSPATH')) { exit; }

function fqx_v128_bill_columns_ready(): void {
    static $done = false;
    if ($done) { return; }
    $done = true;
    global $wpdb;
    foreach ([menuqr_table('bills'), menuqr_table('orders')] as $table) {
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) { continue; }
        $cols = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
        if (!is_array($cols)) { continue; }
        if (!in_array('paid_at', $cols, true)) {
            $wpdb->query("ALTER TABLE {$table} ADD paid_at DATETIME NULL AFTER payment_status");
        }
        if (!in_array('transaction_id', $cols, true)) {
            $after = in_array('paid_at', $cols, true) ? 'paid_at' : 'payment_status';
            $wpdb->query("ALTER TABLE {$table} ADD transaction_id VARCHAR(191) NULL AFTER {$after}");
        }
    }
}
add_action('init', 'fqx_v128_bill_columns_ready', 3);

function fqx_v128_status_is_paid(string $status): bool {
    return in_array(strtolower($status), ['paid', 'success', 'captured', 'completed'], true);
}

function fqx_v128_sync_bill_payment_state(object $bill): object {
    global $wpdb;
    if (empty($bill->id)) { return $bill; }
    fqx_v128_bill_columns_ready();

    $bills = menuqr_table('bills');
    $orders = menuqr_table('orders');
    $now = current_time('mysql');
    $bill_id = (int) $bill->id;
    $session_id = (int) ($bill->bill_session_id ?? 0);
    $bill_status = strtolower((string) ($bill->payment_status ?? 'unpaid'));

    // If the bill itself is paid, force all linked orders to paid so every dashboard/card/receipt agrees.
    if (fqx_v128_status_is_paid($bill_status)) {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$orders}
             SET payment_status = 'paid',
                 paid_at = IFNULL(paid_at, %s),
                 payment_reference = CASE WHEN payment_reference IS NULL OR payment_reference = '' THEN 'manual-paid' ELSE payment_reference END,
                 updated_at = %s
             WHERE bill_id = %d OR bill_session_id = %d",
            $now, $now, $bill_id, $session_id
        ));
        $wpdb->update($bills, [
            'payment_status' => 'paid',
            'paid_at' => $bill->paid_at ?: $now,
            'updated_at' => $now,
        ], ['id' => $bill_id]);
        $fresh = menuqr_get_bill_by_id($bill_id);
        return $fresh ?: $bill;
    }

    // If all linked orders are paid but bill row is stale, repair bill row.
    $counts = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) AS total_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_orders,
                MAX(paid_at) AS last_paid_at,
                MAX(transaction_id) AS last_transaction_id
         FROM {$orders}
         WHERE (bill_id = %d OR bill_session_id = %d) AND order_status <> 'cancelled'",
        $bill_id, $session_id
    ));

    if ($counts && (int) $counts->total_orders > 0 && (int) $counts->total_orders === (int) $counts->paid_orders) {
        $wpdb->update($bills, [
            'payment_status' => 'paid',
            'paid_at' => $counts->last_paid_at ?: $now,
            'transaction_id' => $counts->last_transaction_id ?: ($bill->transaction_id ?? ''),
            'bill_status' => 'generated',
            'updated_at' => $now,
        ], ['id' => $bill_id]);
        $fresh = menuqr_get_bill_by_id($bill_id);
        return $fresh ?: $bill;
    }

    return $bill;
}

function fqx_v128_find_latest_bill_for_service(int $restaurant_id, int $table_id = 0, int $room_id = 0, string $order_source = ''): ?object {
    global $wpdb;
    if (!$restaurant_id || (!$table_id && !$room_id)) { return null; }
    $orders = menuqr_table('orders');
    $bills = menuqr_table('bills');
    $lookback = gmdate('Y-m-d H:i:s', current_time('timestamp', true) - (24 * HOUR_IN_SECONDS));
    $is_room = $room_id > 0 || in_array($order_source, ['room', 'room_qr', 'hotel_room'], true);
    if ($is_room) {
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$orders}
             WHERE restaurant_id = %d AND room_id = %d AND created_at >= %s AND order_status <> 'cancelled'
             ORDER BY id DESC LIMIT 1",
            $restaurant_id, $room_id, $lookback
        ));
    } else {
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$orders}
             WHERE restaurant_id = %d AND table_id = %d AND created_at >= %s AND order_status <> 'cancelled'
             ORDER BY id DESC LIMIT 1",
            $restaurant_id, $table_id, $lookback
        ));
    }
    if (!$order) { return null; }

    $bill = null;
    if (!empty($order->bill_id)) {
        $bill = menuqr_get_bill_by_id((int) $order->bill_id);
    }
    if (!$bill && !empty($order->bill_session_id)) {
        $bill = menuqr_recalculate_bill((int) $order->bill_session_id);
    }
    if (!$bill && function_exists('menuqr_v123_force_bill_for_order')) {
        $bill = menuqr_v123_force_bill_for_order((int) $order->id, '');
    }
    if (!$bill) {
        $bill = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$bills} WHERE restaurant_id = %d AND id = %d LIMIT 1",
            $restaurant_id, (int) ($order->bill_id ?? 0)
        ));
    }
    return $bill ? fqx_v128_sync_bill_payment_state(menuqr_repair_bill_access_key($bill)) : null;
}

function fqx_v128_customer_bill_payload(object $bill, string $session_token = ''): array {
    $bill = fqx_v128_sync_bill_payment_state($bill);
    $orders = menuqr_get_session_orders((int) $bill->bill_session_id);
    foreach ($orders as $order) {
        if (function_exists('menuqr_normalize_order_service_point')) {
            $order = menuqr_normalize_order_service_point($order);
        }
        $order->items = json_decode((string) $order->items_json, true) ?: [];
    }
    $session = null;
    global $wpdb;
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

function fqx_v128_ajax_get_customer_bill(): void {
    menuqr_verify_ajax();
    nocache_headers();

    $restaurant_id = absint($_REQUEST['restaurant_id'] ?? 0);
    $table_id = absint($_REQUEST['table_id'] ?? 0);
    $room_id = absint($_REQUEST['room_id'] ?? 0);
    $order_source = sanitize_key(wp_unslash($_REQUEST['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr')));
    $session_token = menuqr_sanitize_session_token(sanitize_text_field(wp_unslash($_REQUEST['bill_session_token'] ?? '')));
    $order_id = absint($_REQUEST['order_id'] ?? ($_REQUEST['last_order_id'] ?? 0));

    if (!$restaurant_id || (!$table_id && !$room_id)) {
        menuqr_json_response(false, ['message' => 'Missing table/room for bill.'], 400);
    }

    $data = ['session' => null, 'bill' => null, 'orders' => [], 'bill_url' => '', 'bill_direct_url' => '', 'print_url' => ''];
    if ($session_token) {
        $data = menuqr_get_customer_bill_data($restaurant_id, $table_id, $session_token, $room_id, $order_source);
        if (!empty($data['bill'])) {
            $data['bill'] = fqx_v128_sync_bill_payment_state((object) $data['bill']);
            $data['bill_direct_url'] = menuqr_bill_access_url((object) $data['bill']);
        }
    }

    if ((empty($data['bill']) || empty($data['bill_direct_url'])) && $order_id && function_exists('menuqr_v123_force_bill_for_order')) {
        $bill = menuqr_v123_force_bill_for_order($order_id, $session_token);
        if ($bill) { $data = fqx_v128_customer_bill_payload($bill, $session_token); }
    }

    if (empty($data['bill']) || empty($data['bill_direct_url'])) {
        $bill = fqx_v128_find_latest_bill_for_service($restaurant_id, $table_id, $room_id, $order_source);
        if ($bill) { $data = fqx_v128_customer_bill_payload($bill, $session_token); }
    }

    menuqr_json_response(true, $data);
}

function fqx_v128_replace_customer_bill_ajax(): void {
    remove_action('wp_ajax_menuqr_get_customer_bill', 'menuqr_ajax_get_customer_bill');
    remove_action('wp_ajax_nopriv_menuqr_get_customer_bill', 'menuqr_ajax_get_customer_bill');
    add_action('wp_ajax_menuqr_get_customer_bill', 'fqx_v128_ajax_get_customer_bill');
    add_action('wp_ajax_nopriv_menuqr_get_customer_bill', 'fqx_v128_ajax_get_customer_bill');
}
add_action('init', 'fqx_v128_replace_customer_bill_ajax', 1);

function fqx_v128_print_recovery_script(): void {
    if (!is_page('menu') && !is_page('bill')) { return; }
    ?>
    <script>
    window.fqxV128BillFix = true;
    document.addEventListener('DOMContentLoaded', function(){
      if (!window.jQuery || !window.menuqr_ajax) return;
      jQuery(document).ajaxSuccess(function(_e,_xhr,settings,response){
        try {
          var data = response && response.data ? response.data : response;
          var bill = data && (data.bill_direct_url || data.bill_url || data.bill_session_url || (data.order && data.order.bill_url));
          if (bill && settings && String(settings.data || '').indexOf('menuqr_get_customer_bill') !== -1) {
            document.body.classList.add('fqx-real-bill-ready');
          }
        } catch(err) {}
      });
    });
    </script>
    <?php
}
add_action('wp_footer', 'fqx_v128_print_recovery_script', 30);
