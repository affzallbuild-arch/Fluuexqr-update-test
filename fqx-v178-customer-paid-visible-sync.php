<?php
/**
 * FluuexQR v178 Customer Paid Status Visible Sync
 * Ensures Restaurant Admin "Mark Paid" is reflected instantly on customer bill/tracker.
 */
if (!defined('ABSPATH')) { exit; }

function fqx_v178_is_paid_status($status): bool {
    return in_array(strtolower((string) $status), ['paid', 'success', 'captured', 'completed'], true);
}

function fqx_v178_force_customer_paid_status_sync(object $bill): object {
    global $wpdb;
    if (empty($bill->id)) { return $bill; }
    $bills = menuqr_table('bills');
    $orders = menuqr_table('orders');
    $payments = menuqr_table('order_payments');
    $sessions = menuqr_table('bill_sessions');
    $bill_id = (int) $bill->id;
    $session_id = (int) ($bill->bill_session_id ?? 0);
    $now = current_time('mysql');

    $fresh = menuqr_get_bill_by_id($bill_id);
    if ($fresh) { $bill = $fresh; }

    $paid = fqx_v178_is_paid_status($bill->payment_status ?? '');

    if (!$paid && $session_id > 0) {
        $paid = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$bills} WHERE bill_session_id = %d AND payment_status IN ('paid','success','captured','completed') LIMIT 1",
            $session_id
        ));
    }

    if (!$paid && $session_id > 0) {
        $counts = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) total_orders,
                    SUM(CASE WHEN payment_status IN ('paid','success','captured','completed') THEN 1 ELSE 0 END) paid_orders
             FROM {$orders}
             WHERE bill_session_id = %d AND order_status <> 'cancelled'",
            $session_id
        ));
        $paid = $counts && (int) $counts->total_orders > 0 && (int) $counts->total_orders === (int) $counts->paid_orders;
    }

    if (!$paid || $session_id <= 0) { return $bill; }

    $wpdb->query($wpdb->prepare(
        "UPDATE {$orders}
         SET payment_status = 'paid',
             payment_method = CASE WHEN payment_method IS NULL OR payment_method = '' OR payment_method = 'mixed' THEN 'cash' ELSE payment_method END,
             payment_reference = CASE WHEN payment_reference IS NULL OR payment_reference = '' THEN 'manual-paid' ELSE payment_reference END,
             transaction_id = CASE WHEN transaction_id IS NULL OR transaction_id = '' THEN 'manual-paid' ELSE transaction_id END,
             paid_at = IFNULL(paid_at, %s),
             updated_at = %s
         WHERE bill_session_id = %d OR bill_id = %d",
        $now,
        $now,
        $session_id,
        $bill_id
    ));

    $wpdb->query($wpdb->prepare(
        "UPDATE {$bills}
         SET payment_status = 'paid',
             payment_method = CASE WHEN payment_method IS NULL OR payment_method = '' OR payment_method = 'mixed' THEN 'cash' ELSE payment_method END,
             transaction_id = CASE WHEN transaction_id IS NULL OR transaction_id = '' THEN 'manual-paid' ELSE transaction_id END,
             paid_at = IFNULL(paid_at, %s),
             bill_status = 'generated',
             updated_at = %s
         WHERE bill_session_id = %d",
        $now,
        $now,
        $session_id
    ));

    $wpdb->update($sessions, [
        'status' => 'closed',
        'closed_at' => $now,
        'updated_at' => $now,
    ], ['id' => $session_id]);

    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $payments)) === $payments) {
        $linked_orders = $wpdb->get_results($wpdb->prepare("SELECT id, restaurant_id, final_total FROM {$orders} WHERE bill_session_id = %d OR bill_id = %d", $session_id, $bill_id));
        foreach ($linked_orders as $order) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$payments} WHERE order_id = %d AND bill_id = %d LIMIT 1", (int) $order->id, $bill_id));
            if (!$exists) {
                $wpdb->insert($payments, [
                    'restaurant_id' => (int) $order->restaurant_id,
                    'order_id' => (int) $order->id,
                    'bill_id' => $bill_id,
                    'amount' => (float) $order->final_total,
                    'currency' => 'INR',
                    'payment_method' => 'cash',
                    'gateway' => 'manual',
                    'transaction_id' => 'manual-paid',
                    'status' => 'paid',
                    'paid_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    if (function_exists('menuqr_purge_all_caches_after_save')) {
        menuqr_purge_all_caches_after_save('customer_bill_paid_sync');
    }

    $fresh = menuqr_get_bill_by_id($bill_id);
    return $fresh ?: $bill;
}

add_filter('nocache_headers', function($headers){
    if (!empty($_GET['bill']) || !empty($_GET['session']) || is_page('bill')) {
        $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
        $headers['Pragma'] = 'no-cache';
        $headers['Expires'] = 'Wed, 11 Jan 1984 05:00:00 GMT';
    }
    return $headers;
});
