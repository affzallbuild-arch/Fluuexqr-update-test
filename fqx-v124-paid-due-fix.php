<?php
/**
 * FluuexQR v124 Paid/Due Sync Fix
 * Ensures paid bills never show due amount after refresh/print/customer bill icon.
 */
if (!defined('ABSPATH')) { exit; }

function menuqr_v124_paid_due_bootstrap(): void {
    if ((string) get_option('menuqr_v124_paid_due_schema', '') === 'done') {
        return;
    }
    global $wpdb;
    foreach ([menuqr_table('bills'), menuqr_table('orders')] as $table) {
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($exists !== $table) { continue; }
        $cols = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
        if (is_array($cols) && !in_array('paid_at', $cols, true)) {
            $wpdb->query("ALTER TABLE {$table} ADD paid_at DATETIME NULL AFTER payment_status");
        }
        if (is_array($cols) && !in_array('transaction_id', $cols, true)) {
            $after = in_array('paid_at', $cols, true) ? 'paid_at' : 'payment_status';
            $wpdb->query("ALTER TABLE {$table} ADD transaction_id VARCHAR(191) NULL AFTER {$after}");
        }
    }
    update_option('menuqr_v124_paid_due_schema', 'done', false);
}
add_action('init', 'menuqr_v124_paid_due_bootstrap', 5);

function menuqr_v124_sync_paid_bill_on_view(object $bill): object {
    if (strtolower((string) ($bill->payment_status ?? '')) !== 'paid') {
        return $bill;
    }
    global $wpdb;
    $orders = menuqr_table('orders');
    $now = current_time('mysql');
    $wpdb->query($wpdb->prepare(
        "UPDATE {$orders} SET payment_status = 'paid', paid_at = IFNULL(paid_at, %s), updated_at = %s WHERE bill_session_id = %d OR bill_id = %d",
        $now,
        $now,
        (int) $bill->bill_session_id,
        (int) $bill->id
    ));
    return $bill;
}
