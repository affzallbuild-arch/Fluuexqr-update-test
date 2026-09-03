<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v123 Real Bill Fix
 * - Ensures bill/session schema is repaired after theme update even when theme is already active.
 * - Forces a running bill to exist for every saved order.
 * - Makes Bill icon recover from order_id/session mismatch instead of showing empty bill.
 */

function menuqr_v123_real_bill_bootstrap(): void {
    $stored = (string) get_option('menuqr_v123_real_bill_schema', '');
    if ($stored === 'done') {
        return;
    }

    if (function_exists('menuqr_create_tables')) {
        menuqr_create_tables();
    } elseif (function_exists('menuqr_run_room_qr_schema_updates')) {
        menuqr_run_room_qr_schema_updates();
    }

    menuqr_v123_repair_bill_page();
    update_option('menuqr_v123_real_bill_schema', 'done', false);
}
add_action('init', 'menuqr_v123_real_bill_bootstrap', 4);

function menuqr_v123_repair_bill_page(): void {
    $page = get_page_by_path('bill');
    if ($page instanceof WP_Post) {
        if (trim((string) $page->post_content) !== '[menuqr_bill]' || (string) get_post_meta($page->ID, '_wp_page_template', true) !== 'page-menu.php') {
            wp_update_post([
                'ID' => $page->ID,
                'post_title' => 'Bill',
                'post_name' => 'bill',
                'post_content' => '[menuqr_bill]',
                'post_status' => 'publish',
            ]);
            update_post_meta($page->ID, '_wp_page_template', 'page-menu.php');
        }
        return;
    }

    $id = wp_insert_post([
        'post_title' => 'Bill',
        'post_name' => 'bill',
        'post_content' => '[menuqr_bill]',
        'post_status' => 'publish',
        'post_type' => 'page',
        'meta_input' => ['_wp_page_template' => 'page-menu.php'],
    ]);
    if (!is_wp_error($id) && $id) {
        update_post_meta((int) $id, '_wp_page_template', 'page-menu.php');
    }
}

function menuqr_v123_force_bill_for_order(int $order_id, string $session_token = ''): ?object {
    global $wpdb;
    if ($order_id <= 0) {
        return null;
    }

    $orders = menuqr_table('orders');
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders} WHERE id = %d", $order_id));
    if (!$order) {
        return null;
    }

    if (!empty($order->bill_id)) {
        $bill = menuqr_get_bill_by_id((int) $order->bill_id);
        if ($bill) {
            return menuqr_repair_bill_access_key(menuqr_recalculate_bill((int) $bill->bill_session_id) ?: $bill);
        }
    }

    if (!empty($order->bill_session_id)) {
        $bill = menuqr_recalculate_bill((int) $order->bill_session_id);
        if ($bill) {
            return menuqr_repair_bill_access_key($bill);
        }
    }

    $restaurant_id = (int) $order->restaurant_id;
    $room_id = (int) ($order->room_id ?? 0);
    $table_id = (int) ($order->table_id ?? 0);
    $order_source = sanitize_key((string) ($order->order_source ?? ($room_id > 0 ? 'room_qr' : 'table_qr')));
    if ($room_id > 0 || in_array($order_source, ['room', 'room_qr', 'hotel_room'], true)) {
        $order_source = 'room_qr';
        $table_id = 0;
    } else {
        $order_source = 'table_qr';
        $room_id = 0;
    }

    $token = menuqr_sanitize_session_token($session_token ?: ('order_' . $order_id . '_' . wp_generate_password(24, false, false)));
    $customer_name = sanitize_text_field((string) ($order->customer_name ?? ''));
    $customer_phone = sanitize_text_field((string) ($order->customer_phone ?? ''));

    return menuqr_attach_order_to_running_bill($order_id, $restaurant_id, $table_id, $token, $customer_name, $customer_phone, $room_id, $order_source);
}

function menuqr_v123_bill_notice_style(): void {
    if (!is_page('bill')) { return; }
    echo '<style>.fq-v123-bill-help{max-width:760px;margin:40px auto;padding:24px;border-radius:24px;background:#fff7ed;border:1px solid #fed7aa;color:#7c2d12;box-shadow:0 20px 50px rgba(234,88,12,.12);font-family:Inter,system-ui,sans-serif}.fq-v123-bill-help h2{margin:0 0 8px;font-size:24px}.fq-v123-bill-help p{margin:0;color:#9a3412}</style>';
}
add_action('wp_head', 'menuqr_v123_bill_notice_style');
