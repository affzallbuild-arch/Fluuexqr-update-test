<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_verify_ajax(): void {
    check_ajax_referer('menuqr_nonce', 'nonce');
}


function menuqr_public_combo_items(int $restaurant_id): array {
    if (!menuqr_plan_allows($restaurant_id, 'combos')) {
        return [];
    }

    global $wpdb;
    $table = menuqr_table('combos');
    $combos = (array) $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE restaurant_id = %d AND is_active = 1 ORDER BY id DESC",
        $restaurant_id
    ));

    return array_map(static function ($combo) {
        $items = json_decode((string) $combo->items_json, true) ?: [];
        return (object) [
            'id' => -1 * (int) $combo->id,
            'restaurant_id' => (int) $combo->restaurant_id,
            'category_id' => -999,
            'name' => $combo->name,
            'description' => trim((string) $combo->description . (empty($items) ? '' : ' Includes: ' . implode(', ', array_map(static function ($entry) { if (is_array($entry)) { $qty = max(1, (int) ($entry['qty'] ?? 1)); return $qty . '× ' . (string) ($entry['name'] ?? 'Item'); } return (string) $entry; }, $items)))),
            'price' => (float) $combo->combo_price,
            'tax_rate' => 5,
            'service_charge_rate' => 0,
            'image' => (string) $combo->image,
            'emoji' => (string) ($combo->emoji ?: '🎁'),
            'variants' => [],
            'addons' => [],
            'is_available' => 1,
            'is_featured' => 1,
            'is_combo' => 1,
        ];
    }, $combos);
}

function menuqr_get_active_coupon(int $restaurant_id, string $code): ?object {
    global $wpdb;
    $code = strtoupper(sanitize_text_field($code));
    if ($code === '' || !menuqr_plan_allows($restaurant_id, 'coupons')) {
        return null;
    }

    $table = menuqr_table('coupons');
    $now = current_time('mysql');
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table}
         WHERE restaurant_id = %d AND code = %s AND is_active = 1
         AND (starts_at IS NULL OR starts_at <= %s)
         AND (expires_at IS NULL OR expires_at >= %s)
         AND (usage_limit = 0 OR used_count < usage_limit)
         LIMIT 1",
        $restaurant_id,
        $code,
        $now,
        $now
    ));
}

function menuqr_calculate_coupon_discount(object $coupon, float $subtotal): float {
    if ($subtotal < (float) $coupon->min_order) {
        return 0.0;
    }

    if ((string) $coupon->discount_type === 'fixed') {
        return min($subtotal, (float) $coupon->discount_value);
    }

    return min($subtotal, round($subtotal * ((float) $coupon->discount_value / 100), 2));
}

function menuqr_ajax_get_menu(): void {
    nocache_headers();
    menuqr_verify_ajax();

    $restaurant_id = absint($_REQUEST['restaurant_id'] ?? ($_REQUEST['r'] ?? 0));
    $source_raw = sanitize_key(wp_unslash($_REQUEST['source'] ?? $_REQUEST['order_source'] ?? ''));
    $table_id = absint($_REQUEST['table_id'] ?? ($_REQUEST['t'] ?? ($_REQUEST['table'] ?? 0)));
    $table_ref = sanitize_text_field(wp_unslash($_REQUEST['table_no'] ?? ($_REQUEST['table_number'] ?? ($_REQUEST['table_label'] ?? ''))));
    $room_id = absint($_REQUEST['room_id'] ?? ($_REQUEST['room'] ?? 0));
    $room_ref = sanitize_text_field(wp_unslash($_REQUEST['room_no'] ?? ($_REQUEST['room_number'] ?? ($_REQUEST['room_label'] ?? ''))));

    if (function_exists('fqx_v200_resolve_ajax_service_or_die')) {
        $fqx_ctx = fqx_v200_resolve_ajax_service_or_die();
        if (empty($fqx_ctx['legacy_mode'])) {
            $restaurant_id = (int) ($fqx_ctx['restaurant_id'] ?? $restaurant_id);
            $table_id = (int) ($fqx_ctx['table_id'] ?? 0);
            $room_id = (int) ($fqx_ctx['room_id'] ?? 0);
            $source_raw = (string) ($fqx_ctx['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr'));
            $table_ref = (string) ($fqx_ctx['table_number'] ?? '');
            $room_ref = (string) ($fqx_ctx['room_number'] ?? '');
        }
    }

    if (in_array($source_raw, ['room', 'room_qr', 'hotel_room'], true)) {
        $table_id = 0;
    } elseif (in_array($source_raw, ['table', 'table_qr'], true)) {
        $room_id = 0;
    }

    if (!$restaurant_id) {
        menuqr_json_response(false, ['message' => 'Invalid restaurant.'], 400);
    }

    if (!menuqr_restaurant_is_active($restaurant_id)) {
        menuqr_json_response(false, ['message' => 'Subscription Expired. Please renew.'], 403);
    }

    $restaurant = menuqr_get_restaurant($restaurant_id);
    if (!$restaurant) {
        menuqr_json_response(false, ['message' => 'Restaurant not found.'], 404);
    }

    $categories = menuqr_get_categories($restaurant_id);
    $items = menuqr_get_items($restaurant_id);

    $combo_items = menuqr_public_combo_items($restaurant_id);
    if (!empty($combo_items)) {
        array_unshift($categories, (object) ['id' => -999, 'restaurant_id' => $restaurant_id, 'name' => 'Combos & Deals', 'description' => 'Premium combos', 'sort_order' => -1]);
        $items = array_merge($combo_items, $items);
    }

    $tables = menuqr_get_tables($restaurant_id);
    $rooms = function_exists('menuqr_get_rooms') ? menuqr_get_rooms($restaurant_id) : [];
    $payment = menuqr_get_payment_settings($restaurant_id);

    // v83: Resolve service point once and return the same label to JS.
    $service_context = function_exists('menuqr_get_service_point_context') ? menuqr_get_service_point_context($restaurant_id, $table_id, $room_id, $table_ref, $room_ref) : [];
    $order_source = (string) ($service_context['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr'));
    $table_match = null;
    $room_match = null;

    if ($order_source === 'room_qr') {
        $room_id = (int) ($service_context['room_id'] ?? $room_id);
        $room_number = (string) ($service_context['room_number'] ?? ($room_ref ?: $room_id));
        foreach ($rooms as $room) {
            if ((int) ($room->id ?? 0) === $room_id) { $room_match = $room; break; }
        }
        if (!$room_match) {
            $room_match = (object) [
                'id' => $room_id,
                'room_number' => $room_number,
                'room_name' => 'Room ' . $room_number,
                'label' => 'Room ' . $room_number,
            ];
        }
        $table_id = 0;
    } elseif ($order_source === 'table_qr') {
        $table_id = (int) ($service_context['table_id'] ?? $table_id);
        $table_number = (string) ($service_context['table_number'] ?? ($table_ref ?: $table_id));
        foreach ($tables as $table) {
            if ((int) ($table->id ?? 0) === $table_id) { $table_match = $table; break; }
        }
        if (!$table_match) {
            $table_match = (object) [
                'id' => $table_id,
                'table_number' => $table_number,
                'table_no' => $table_number,
                'table_name' => 'Table ' . $table_number,
                'table_code' => 'T' . $table_id,
                'label' => 'Table ' . $table_number,
            ];
        }
        $room_id = 0;
    }

    $branding = menuqr_get_restaurant_branding_data($restaurant_id);
    $restaurant->logo = $branding['logo'] ?: ((string) ($restaurant->logo ?? ''));
    $restaurant->address = $branding['address'] ?: ((string) ($restaurant->address ?? ''));
    $restaurant->phone = $branding['phone'] ?: ((string) ($restaurant->phone ?? ''));
    $restaurant->email = $branding['email'] ?: ((string) ($restaurant->email ?? ''));
    $restaurant->gst_number = $branding['gst_number'] ?: ((string) ($restaurant->gst_number ?? ''));
    $restaurant->fssai_number = $branding['fssai_number'] ?: ((string) ($restaurant->fssai_number ?? ''));
    $restaurant->tagline = $branding['tagline'] ?: '';
    $restaurant->currency_symbol = $branding['currency_symbol'] ?: '₹';
    $restaurant->tax_label = $branding['tax_label'] ?: 'GST/Tax';

    menuqr_json_response(true, [
        'restaurant' => $restaurant,
        'branding' => $branding,
        'table' => $table_match,
        'room' => $room_match,
        'service_context' => $service_context,
        'order_source' => $order_source,
        'categories' => $categories,
        'items' => array_map(static function ($item) {
            $item->variants = json_decode((string) $item->variants, true) ?: [];
            $item->addons = json_decode((string) $item->addons, true) ?: [];
            return $item;
        }, $items),
        'payment' => array_merge((array) $payment, ['gateway' => menuqr_gateway_public_payload($payment)]),
        'reviews' => menuqr_get_review_public_payload($restaurant_id),
        'review_url' => menuqr_review_click_url($restaurant_id, $table_id),
    ]);
}
add_action('wp_ajax_menuqr_get_menu', 'menuqr_ajax_get_menu');
add_action('wp_ajax_nopriv_menuqr_get_menu', 'menuqr_ajax_get_menu');

function menuqr_create_customer_order_record(int $restaurant_id, int $table_id, array $items, string $payment_method, string $payment_status, string $customer_note = '', string $payment_reference = '', string $payment_screenshot = '', string $gateway_provider = '', string $gateway_order_id = '', string $bill_session_token = '', string $customer_name = '', string $customer_whatsapp = '', string $coupon_code = '', int $room_id = 0, string $order_source = ''): array {
    $table_id = absint($table_id);
    $room_id = absint($room_id);
    $order_source = sanitize_key($order_source ?: ($room_id > 0 ? 'room_qr' : 'table_qr'));

    if (!$restaurant_id || (!$table_id && !$room_id) || empty($items)) {
        return ['success' => false, 'message' => 'Missing order data.', 'status' => 400];
    }

    if ('room_qr' === $order_source && $room_id <= 0) {
        $order_source = 'table_qr';
    }

    if ('table_qr' === $order_source && $table_id <= 0 && $room_id > 0) {
        $order_source = 'room_qr';
    }

    $service_context = function_exists('menuqr_get_service_point_context') ? menuqr_get_service_point_context($restaurant_id, $table_id, $room_id) : [];
    $table_number = '';
    $room_number = '';
    if (($service_context['order_source'] ?? '') === 'room_qr') {
        $room_id = (int) ($service_context['room_id'] ?? $room_id);
        $room_number = (string) ($service_context['room_number'] ?? '');
        $table_id = 0;
        $table_number = '';
        $order_source = 'room_qr';
    } else {
        $table_id = (int) ($service_context['table_id'] ?? $table_id);
        $table_number = (string) ($service_context['table_number'] ?? '');
        $room_id = 0;
        $room_number = '';
        $order_source = 'table_qr';
    }

    // v84: Enforce exactly one source before saving. Room orders never keep table fields; table orders never keep room fields.
    if ($order_source === 'room_qr') {
        $table_id = 0;
        $table_number = '';
        $room_number = menuqr_clean_service_point_value($room_number ?: $room_id);
    } else {
        $room_id = 0;
        $room_number = '';
        $table_number = menuqr_clean_service_point_value($table_number ?: $table_id);
        $order_source = 'table_qr';
    }

    menuqr_enforce_subscription_or_die($restaurant_id);

    global $wpdb;
    $table_orders = menuqr_table('orders');

    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 1));
    }
    $discount = 0.0;
    $coupon = menuqr_get_active_coupon($restaurant_id, $coupon_code);
    if ($coupon) {
        $discount = menuqr_calculate_coupon_discount($coupon, $subtotal);
    }
    $taxable_subtotal = max(0, $subtotal - $discount);
    $tax = round($taxable_subtotal * 0.05, 2);
    $service_charge = round($taxable_subtotal * 0.00, 2);
    $final_total = round($taxable_subtotal + $tax + $service_charge, 2);

    $service_point_id = $room_id > 0 ? $room_id : $table_id;
    $duplicate_key = md5($restaurant_id . '|' . $order_source . '|' . $service_point_id . '|' . wp_json_encode($items) . '|' . $payment_method . '|' . gmdate('Y-m-d H:i'));
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_orders} WHERE duplicate_key = %s AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 1",
        $duplicate_key
    ));
    if ($existing) {
        return ['success' => false, 'message' => 'Duplicate order prevented.', 'status' => 409, 'order_id' => (int) $existing];
    }

    $now = current_time('mysql');
    $inserted = $wpdb->insert($table_orders, [
        'restaurant_id'      => $restaurant_id,
        'table_id'           => $table_id,
        'table_number'       => $table_number,
        'room_id'            => $room_id,
        'room_number'        => $room_number,
        'order_source'       => $order_source,
        'unique_code'        => 'MQR-' . wp_generate_password(8, false, false),
        'customer_name'      => sanitize_text_field($customer_name),
        'customer_phone'     => menuqr_normalize_phone($customer_whatsapp),
        'items_json'         => wp_json_encode($items),
        'subtotal'           => $subtotal,
        'tax'                => $tax,
        'service_charge'     => $service_charge,
        'final_total'        => $final_total,
        'payment_method'     => $payment_method,
        'payment_status'     => $payment_status,
        'order_status'       => 'pending',
        'payment_reference'  => $payment_reference,
        'gateway_provider'   => $gateway_provider,
        'gateway_order_id'   => $gateway_order_id,
        'payment_screenshot' => $payment_screenshot,
        'customer_note'      => $customer_note . ($coupon && $discount > 0 ? "
Coupon " . $coupon->code . " applied: -" . menuqr_money($discount) : ''),
        'duplicate_key'      => $duplicate_key,
        'created_at'         => $now,
        'updated_at'         => $now,
    ]);

    if (false === $inserted) {
        return ['success' => false, 'message' => 'Order could not be saved.', 'status' => 500];
    }

    $order_id = (int) $wpdb->insert_id;

    // v123: Always create a real running bill for every successful order.
    // Earlier versions only created a bill when the browser sent bill_session_token; if the token
    // was missing/stale, orders saved but the bill icon opened an empty/expired bill page.
    $bill_session_token = menuqr_sanitize_session_token($bill_session_token);
    $bill = null;
    if ($coupon && $discount > 0) {
        $wpdb->query($wpdb->prepare("UPDATE " . menuqr_table('coupons') . " SET used_count = used_count + 1, updated_at = %s WHERE id = %d", $now, (int) $coupon->id));
    }

    $bill = menuqr_attach_order_to_running_bill($order_id, $restaurant_id, $table_id, $bill_session_token, $customer_name, $customer_whatsapp, $room_id, $order_source);
    if (!$bill) {
        // Recovery path: create/fix the bill by reading the just-saved order.
        $bill = function_exists('menuqr_v123_force_bill_for_order') ? menuqr_v123_force_bill_for_order($order_id, $bill_session_token) : null;
    }

    return [
        'success' => true,
        'order_id' => $order_id,
        'order_code' => menuqr_build_order_code($order_id),
        'total' => $final_total,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'service_charge' => $service_charge,
        'bill_id' => $bill ? (int) $bill->id : 0,
        'bill_url' => $bill ? menuqr_bill_access_url($bill) : '',
        'bill_direct_url' => $bill ? menuqr_bill_access_url($bill) : '',
        'bill_session_url' => $bill ? menuqr_bill_session_access_url($restaurant_id, $table_id, $bill_session_token, $room_id, $order_source) : '',
        'bill_session_token' => $bill_session_token,
    ];
}

function menuqr_ajax_place_order(): void {
    menuqr_verify_ajax();

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $table_id = absint($_POST['table_id'] ?? 0);
    $room_id = absint($_POST['room_id'] ?? 0);
    $order_source = sanitize_key(wp_unslash($_POST['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr')));
    $items = json_decode(stripslashes((string) ($_POST['items'] ?? '[]')), true);
    $items = menuqr_sanitize_order_items($items);
    $payment_method = sanitize_key(wp_unslash($_POST['payment_method'] ?? 'cash'));
    $payment_reference = sanitize_text_field(wp_unslash($_POST['payment_reference'] ?? ''));
    $customer_note = sanitize_textarea_field(wp_unslash($_POST['customer_note'] ?? ''));
    $bill_session_token = menuqr_sanitize_session_token(sanitize_text_field(wp_unslash($_POST['bill_session_token'] ?? '')));
    $customer_name = sanitize_text_field(wp_unslash($_POST['customer_name'] ?? ''));
    $customer_whatsapp = sanitize_text_field(wp_unslash($_POST['customer_whatsapp'] ?? ''));
    $coupon_code = strtoupper(sanitize_text_field(wp_unslash($_POST['coupon_code'] ?? '')));

    if (function_exists('fqx_v200_resolve_ajax_service_or_die')) {
        $fqx_ctx = fqx_v200_resolve_ajax_service_or_die();
        if (empty($fqx_ctx['legacy_mode'])) {
            $restaurant_id = (int) ($fqx_ctx['restaurant_id'] ?? $restaurant_id);
            $table_id = (int) ($fqx_ctx['table_id'] ?? 0);
            $room_id = (int) ($fqx_ctx['room_id'] ?? 0);
            $order_source = (string) ($fqx_ctx['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr'));
        }
    }

    $payment_screenshot = '';

    if (!empty($_FILES['payment_screenshot']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploaded = wp_handle_upload($_FILES['payment_screenshot'], ['test_form' => false]);
        if (!isset($uploaded['error']) && !empty($uploaded['url'])) {
            $payment_screenshot = esc_url_raw($uploaded['url']);
        }
    }

    $status = 'cash' === $payment_method ? 'unpaid' : 'pending';
    if ('online' === $payment_method) {
        $status = 'unpaid';
    }

    $created = menuqr_create_customer_order_record(
        $restaurant_id,
        $table_id,
        $items,
        $payment_method,
        $status,
        $customer_note,
        $payment_reference,
        $payment_screenshot,
        '',
        '',
        $bill_session_token,
        $customer_name,
        $customer_whatsapp,
        $coupon_code,
        $room_id,
        $order_source
    );

    if (empty($created['success'])) {
        menuqr_json_response(false, ['message' => $created['message']], (int) ($created['status'] ?? 400));
    }

    menuqr_json_response(true, [
        'message' => 'Order placed successfully.',
        'order_id' => $created['order_id'],
        'order_code' => $created['order_code'],
        'total' => $created['total'],
        'payment_status' => $status,
        'bill_id' => $created['bill_id'] ?? 0,
        'bill_url' => $created['bill_url'] ?? '',
        'bill_direct_url' => $created['bill_direct_url'] ?? ($created['bill_url'] ?? ''),
        'bill_session_url' => $created['bill_session_url'] ?? '',
        'bill_session_token' => $created['bill_session_token'] ?? $bill_session_token,
    ]);
}

function menuqr_razorpay_create_remote_order(object $payment, int $order_id, float $amount): array {
    $amount_paise = max(100, (int) round($amount * 100));
    $response = wp_remote_post('https://api.razorpay.com/v1/orders', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($payment->razorpay_key . ':' . $payment->razorpay_secret),
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'amount' => $amount_paise,
            'currency' => 'INR',
            'receipt' => 'menuqr_' . $order_id,
            'payment_capture' => 1,
            'notes' => [
                'menuqr_order_id' => (string) $order_id,
                'restaurant_id' => (string) $payment->restaurant_id,
            ],
        ]),
    ]);

    if (is_wp_error($response)) {
        return ['success' => false, 'message' => $response->get_error_message()];
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300 || empty($body['id'])) {
        return ['success' => false, 'message' => $body['error']['description'] ?? 'Razorpay order creation failed.'];
    }

    return ['success' => true, 'razorpay_order_id' => sanitize_text_field($body['id']), 'amount' => $amount_paise, 'currency' => 'INR'];
}

function menuqr_ajax_create_gateway_order(): void {
    menuqr_verify_ajax();

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $table_id = absint($_POST['table_id'] ?? 0);
    $room_id = absint($_POST['room_id'] ?? 0);
    $order_source = sanitize_key(wp_unslash($_POST['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr')));
    $items = json_decode(stripslashes((string) ($_POST['items'] ?? '[]')), true);
    $items = menuqr_sanitize_order_items($items);
    $customer_note = sanitize_textarea_field(wp_unslash($_POST['customer_note'] ?? ''));
    $bill_session_token = menuqr_sanitize_session_token(sanitize_text_field(wp_unslash($_POST['bill_session_token'] ?? '')));
    $customer_name = sanitize_text_field(wp_unslash($_POST['customer_name'] ?? ''));
    $customer_whatsapp = sanitize_text_field(wp_unslash($_POST['customer_whatsapp'] ?? ''));
    $coupon_code = strtoupper(sanitize_text_field(wp_unslash($_POST['coupon_code'] ?? '')));

    if (function_exists('fqx_v200_resolve_ajax_service_or_die')) {
        $fqx_ctx = fqx_v200_resolve_ajax_service_or_die();
        if (empty($fqx_ctx['legacy_mode'])) {
            $restaurant_id = (int) ($fqx_ctx['restaurant_id'] ?? $restaurant_id);
            $table_id = (int) ($fqx_ctx['table_id'] ?? 0);
            $room_id = (int) ($fqx_ctx['room_id'] ?? 0);
            $order_source = (string) ($fqx_ctx['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr'));
        }
    }

    if (!$restaurant_id || (!$table_id && !$room_id) || empty($items)) {
        menuqr_json_response(false, ['message' => 'Missing order data.'], 400);
    }

    if (function_exists('menuqr_plan_allows') && !menuqr_plan_allows($restaurant_id, 'gateway')) {
        menuqr_json_response(false, ['message' => 'Online gateway is available only in Premium or Yearly Pro plans.'], 403);
    }

    $payment = menuqr_get_payment_settings($restaurant_id);
    $provider = sanitize_key(wp_unslash($_POST['provider'] ?? ($payment->gateway_provider ?: 'razorpay')));

    if ('phonepe' === $provider && menuqr_payment_has_phonepe($payment)) {
        $provider = 'phonepe';
    } elseif (menuqr_payment_has_razorpay($payment)) {
        $provider = 'razorpay';
    } else {
        menuqr_json_response(false, ['message' => 'Online gateway is not configured for this restaurant.'], 400);
    }

    $created = menuqr_create_customer_order_record($restaurant_id, $table_id, $items, $provider, 'unpaid', $customer_note, '', '', $provider, '', $bill_session_token, $customer_name, $customer_whatsapp, $coupon_code, $room_id, $order_source);
    if (empty($created['success'])) {
        menuqr_json_response(false, ['message' => $created['message']], (int) ($created['status'] ?? 400));
    }

    global $wpdb;
    $orders = menuqr_table('orders');
    $restaurant = menuqr_get_restaurant($restaurant_id);

    if ('razorpay' === $provider) {
        $remote = menuqr_razorpay_create_remote_order($payment, (int) $created['order_id'], (float) $created['total']);
        if (empty($remote['success'])) {
            $wpdb->update($orders, ['payment_status' => 'failed', 'updated_at' => current_time('mysql')], ['id' => (int) $created['order_id']]);
            menuqr_json_response(false, ['message' => $remote['message'] ?? 'Razorpay failed.'], 500);
        }

        $wpdb->update($orders, [
            'payment_status' => 'pending',
            'gateway_provider' => 'razorpay',
            'gateway_order_id' => $remote['razorpay_order_id'],
            'updated_at' => current_time('mysql'),
        ], ['id' => (int) $created['order_id']]);

        menuqr_json_response(true, [
            'provider' => 'razorpay',
            'order_id' => (int) $created['order_id'],
            'order_code' => $created['order_code'],
            'razorpay_order_id' => $remote['razorpay_order_id'],
            'key' => $payment->razorpay_key,
            'amount' => $remote['amount'],
            'currency' => 'INR',
            'name' => $restaurant ? $restaurant->name : get_bloginfo('name'),
            'description' => 'FluuexQR Order ' . $created['order_code'],
            'total' => $created['total'],
            'bill_id' => $created['bill_id'] ?? 0,
            'bill_url' => $created['bill_url'] ?? '',
            'bill_direct_url' => $created['bill_direct_url'] ?? ($created['bill_url'] ?? ''),
            'bill_session_url' => $created['bill_session_url'] ?? '',
            'bill_session_token' => $created['bill_session_token'] ?? $bill_session_token,
        ]);
    }

    $wpdb->update($orders, [
        'gateway_provider' => 'phonepe',
        'payment_status' => 'pending',
        'updated_at' => current_time('mysql'),
    ], ['id' => (int) $created['order_id']]);

    menuqr_json_response(true, [
        'provider' => 'phonepe',
        'order_id' => (int) $created['order_id'],
        'order_code' => $created['order_code'],
        'total' => $created['total'],
        'bill_id' => $created['bill_id'] ?? 0,
        'bill_url' => $created['bill_url'] ?? '',
        'bill_direct_url' => $created['bill_direct_url'] ?? ($created['bill_url'] ?? ''),
        'bill_session_url' => $created['bill_session_url'] ?? '',
        'bill_session_token' => $created['bill_session_token'] ?? $bill_session_token,
        'message' => 'PhonePe credentials are saved. Use PhonePe production callback/API setup to complete auto-verification.',
    ]);
}

function menuqr_ajax_verify_razorpay_payment(): void {
    menuqr_verify_ajax();

    $order_id = absint($_POST['order_id'] ?? 0);
    $razorpay_order_id = sanitize_text_field(wp_unslash($_POST['razorpay_order_id'] ?? ''));
    $razorpay_payment_id = sanitize_text_field(wp_unslash($_POST['razorpay_payment_id'] ?? ''));
    $razorpay_signature = sanitize_text_field(wp_unslash($_POST['razorpay_signature'] ?? ''));

    if (!$order_id || !$razorpay_order_id || !$razorpay_payment_id || !$razorpay_signature) {
        menuqr_json_response(false, ['message' => 'Missing Razorpay verification data.'], 400);
    }

    global $wpdb;
    $orders = menuqr_table('orders');
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders} WHERE id = %d", $order_id));
    if (!$order) {
        menuqr_json_response(false, ['message' => 'Order not found.'], 404);
    }

    $payment = menuqr_get_payment_settings((int) $order->restaurant_id);
    if (empty($payment->razorpay_secret)) {
        menuqr_json_response(false, ['message' => 'Razorpay secret missing.'], 400);
    }

    $expected = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $payment->razorpay_secret);
    if (!hash_equals($expected, $razorpay_signature)) {
        $wpdb->update($orders, [
            'payment_status' => 'failed',
            'gateway_payment_id' => $razorpay_payment_id,
            'gateway_signature' => $razorpay_signature,
            'payment_reference' => $razorpay_payment_id,
            'updated_at' => current_time('mysql'),
        ], ['id' => $order_id]);
        menuqr_json_response(false, ['message' => 'Payment signature verification failed.'], 400);
    }

    $wpdb->update($orders, [
        'payment_method' => 'razorpay',
        'payment_status' => 'paid',
        'gateway_provider' => 'razorpay',
        'gateway_order_id' => $razorpay_order_id,
        'gateway_payment_id' => $razorpay_payment_id,
        'gateway_signature' => $razorpay_signature,
        'payment_reference' => $razorpay_payment_id,
        'updated_at' => current_time('mysql'),
    ], ['id' => $order_id]);

    menuqr_update_bill_after_order_payment($order_id);
    $updated_order = $wpdb->get_row($wpdb->prepare("SELECT bill_id FROM {$orders} WHERE id = %d", $order_id));
    $bill = $updated_order ? menuqr_get_bill_by_id((int) $updated_order->bill_id) : null;
    if (function_exists('fqx_upsert_order_payment')) {
        fqx_upsert_order_payment((int) $order->restaurant_id, $order_id, $bill ? (int) $bill->id : 0, (float) $order->final_total, 'razorpay', 'paid', $razorpay_payment_id, $razorpay_order_id);
    }

    menuqr_json_response(true, [
        'message' => 'Payment verified.',
        'order_id' => $order_id,
        'payment_status' => 'paid',
        'bill_id' => $bill ? (int) $bill->id : 0,
        'bill_url' => $bill ? menuqr_bill_access_url($bill) : '',
        'bill_direct_url' => $bill ? menuqr_bill_access_url($bill) : '',
    ]);
}

function menuqr_ajax_mark_gateway_unpaid(): void {
    menuqr_verify_ajax();
    $order_id = absint($_POST['order_id'] ?? 0);
    if (!$order_id) {
        menuqr_json_response(false, ['message' => 'Missing order.'], 400);
    }

    global $wpdb;
    $orders = menuqr_table('orders');
    $wpdb->update($orders, [
        'payment_status' => 'unpaid',
        'updated_at' => current_time('mysql'),
    ], ['id' => $order_id, 'payment_status' => 'pending']);
    menuqr_update_bill_after_order_payment($order_id);

    menuqr_json_response(true, ['message' => 'Payment marked unpaid.', 'order_id' => $order_id]);
}
add_action('wp_ajax_menuqr_place_order', 'menuqr_ajax_place_order');
add_action('wp_ajax_nopriv_menuqr_place_order', 'menuqr_ajax_place_order');
add_action('wp_ajax_menuqr_create_gateway_order', 'menuqr_ajax_create_gateway_order');
add_action('wp_ajax_nopriv_menuqr_create_gateway_order', 'menuqr_ajax_create_gateway_order');
add_action('wp_ajax_menuqr_verify_razorpay_payment', 'menuqr_ajax_verify_razorpay_payment');
add_action('wp_ajax_nopriv_menuqr_verify_razorpay_payment', 'menuqr_ajax_verify_razorpay_payment');
add_action('wp_ajax_menuqr_mark_gateway_unpaid', 'menuqr_ajax_mark_gateway_unpaid');
add_action('wp_ajax_nopriv_menuqr_mark_gateway_unpaid', 'menuqr_ajax_mark_gateway_unpaid');


function menuqr_ajax_get_order_status(): void {
    menuqr_verify_ajax();
    global $wpdb;
    $order_id = absint($_GET['order_id'] ?? 0);
    $table = menuqr_table('orders');
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $order_id));
    if (!$order) {
        menuqr_json_response(false, ['message' => 'Order not found.'], 404);
    }
    if (function_exists('fqx_v200_find_service_by_token')) {
        $token = fqx_v200_get_request_token();
        if ($token !== '') {
            $ctx = fqx_v200_find_service_by_token($token);
            if (!$ctx || (int)$ctx['restaurant_id'] !== (int)$order->restaurant_id || (int)$ctx['table_id'] !== (int)($order->table_id ?? 0) || (int)$ctx['room_id'] !== (int)($order->room_id ?? 0)) {
                menuqr_json_response(false, ['message' => 'This order does not belong to this secure QR session.'], 403);
            }
        }
    }
    $order->items = json_decode((string) $order->items_json, true) ?: [];
    $bill = !empty($order->bill_id) ? menuqr_get_bill_by_id((int) $order->bill_id) : null;
    if (!$bill && !empty($order->bill_session_id)) {
        $bill = menuqr_recalculate_bill((int) $order->bill_session_id);
    }
    if ($bill && function_exists('fqx_v178_force_customer_paid_status_sync')) {
        $bill = fqx_v178_force_customer_paid_status_sync($bill);
    }
    $order->bill_url = $bill ? menuqr_bill_access_url($bill) : '';
    $order->bill_payment_status = $bill ? (string) $bill->payment_status : (string) $order->payment_status;
    $order->bill_total = $bill ? (float) $bill->grand_total : (float) $order->final_total;
    $order->review = menuqr_get_review_public_payload((int) $order->restaurant_id);
    $order->review_url = '';
    if (!empty($order->review['enabled'])) {
        $order->review_url = menuqr_review_click_url((int) $order->restaurant_id, (int) $order->table_id, (int) $order->id, (int) ($order->bill_session_id ?? 0), (string) ($order->customer_phone ?? ''));
    }
    menuqr_json_response(true, ['order' => $order]);
}
add_action('wp_ajax_menuqr_get_order_status', 'menuqr_ajax_get_order_status');
add_action('wp_ajax_nopriv_menuqr_get_order_status', 'menuqr_ajax_get_order_status');




function menuqr_ajax_get_customer_bill(): void {
    menuqr_verify_ajax();
    nocache_headers();

    $restaurant_id = absint($_REQUEST['restaurant_id'] ?? 0);
    $table_id = absint($_REQUEST['table_id'] ?? 0);
    $room_id = absint($_REQUEST['room_id'] ?? 0);
    $order_source = sanitize_key(wp_unslash($_REQUEST['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr')));
    $session_token = menuqr_sanitize_session_token(sanitize_text_field(wp_unslash($_REQUEST['bill_session_token'] ?? '')));
    $order_id = absint($_REQUEST['order_id'] ?? ($_REQUEST['last_order_id'] ?? 0));

    if (function_exists('fqx_v200_resolve_ajax_service_or_die')) {
        $fqx_ctx = fqx_v200_resolve_ajax_service_or_die();
        if (empty($fqx_ctx['legacy_mode'])) {
            $restaurant_id = (int) ($fqx_ctx['restaurant_id'] ?? $restaurant_id);
            $table_id = (int) ($fqx_ctx['table_id'] ?? 0);
            $room_id = (int) ($fqx_ctx['room_id'] ?? 0);
            $order_source = (string) ($fqx_ctx['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr'));
        }
    }

    if (!$restaurant_id || (!$table_id && !$room_id) || !$session_token) {
        menuqr_json_response(false, ['message' => 'Missing bill session.'], 400);
    }

    $data = menuqr_get_customer_bill_data($restaurant_id, $table_id, $session_token, $room_id, $order_source);
    if ((empty($data['bill']) || empty($data['bill_url'])) && $order_id && function_exists('menuqr_v123_force_bill_for_order')) {
        $bill = menuqr_v123_force_bill_for_order($order_id, $session_token);
        if ($bill) {
            $data = menuqr_get_customer_bill_data((int) $bill->restaurant_id, (int) $bill->table_id, $session_token, (int) ($bill->room_id ?? 0), (string) ($bill->order_source ?? $order_source));
            $data['bill_direct_url'] = menuqr_bill_access_url($bill);
            $data['bill_url'] = menuqr_bill_session_access_url((int) $bill->restaurant_id, (int) $bill->table_id, $session_token, (int) ($bill->room_id ?? 0), (string) ($bill->order_source ?? $order_source));
        }
    }
    if (!empty($data['bill']) && is_object($data['bill']) && function_exists('fqx_v178_force_customer_paid_status_sync')) {
        $data['bill'] = fqx_v178_force_customer_paid_status_sync($data['bill']);
        if (!empty($data['orders']) && is_array($data['orders'])) {
            foreach ($data['orders'] as $customer_bill_order) {
                if (is_object($customer_bill_order)) {
                    $customer_bill_order->bill_payment_status = (string) ($data['bill']->payment_status ?? $customer_bill_order->payment_status ?? 'unpaid');
                }
            }
        }
    }
    menuqr_json_response(true, $data);
}
add_action('wp_ajax_menuqr_get_customer_bill', 'menuqr_ajax_get_customer_bill');
add_action('wp_ajax_nopriv_menuqr_get_customer_bill', 'menuqr_ajax_get_customer_bill');

function menuqr_ajax_update_bill_customer(): void {
    menuqr_verify_ajax();

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $table_id = absint($_POST['table_id'] ?? 0);
    $room_id = absint($_POST['room_id'] ?? 0);
    $order_source = sanitize_key(wp_unslash($_POST['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr')));
    $session_token = menuqr_sanitize_session_token(sanitize_text_field(wp_unslash($_POST['bill_session_token'] ?? '')));
    $customer_name = sanitize_text_field(wp_unslash($_POST['customer_name'] ?? ''));
    $customer_whatsapp = sanitize_text_field(wp_unslash($_POST['customer_whatsapp'] ?? ''));
    $coupon_code = strtoupper(sanitize_text_field(wp_unslash($_POST['coupon_code'] ?? '')));

    if (!$restaurant_id || (!$table_id && !$room_id) || !$session_token) {
        menuqr_json_response(false, ['message' => 'Missing bill session.'], 400);
    }

    $session = menuqr_get_or_create_bill_session($restaurant_id, $table_id, $session_token, $customer_name, $customer_whatsapp, $room_id, $order_source);
    $bill = menuqr_recalculate_bill((int) $session->id);
    $orders = menuqr_get_session_orders((int) $session->id);
    foreach ($orders as $order) {
        if (function_exists('menuqr_normalize_order_service_point')) {
            $order = menuqr_normalize_order_service_point($order);
        }
        $order->items = json_decode((string) $order->items_json, true) ?: [];
    }

    menuqr_json_response(true, [
        'message' => 'Bill customer details saved.',
        'session' => $session,
        'bill' => $bill,
        'orders' => $orders,
        'bill_url' => $bill ? menuqr_bill_session_access_url($restaurant_id, $table_id, $session_token, $room_id, $order_source) : '',
        'bill_direct_url' => $bill ? menuqr_bill_access_url($bill) : '',
        'print_url' => $bill ? menuqr_print_bill_session_access_url($restaurant_id, $table_id, $session_token, $room_id, $order_source) : '',
    ]);
}
add_action('wp_ajax_menuqr_update_bill_customer', 'menuqr_ajax_update_bill_customer');
add_action('wp_ajax_nopriv_menuqr_update_bill_customer', 'menuqr_ajax_update_bill_customer');


function menuqr_ajax_get_kitchen_orders(): void {
    menuqr_verify_ajax();
    nocache_headers();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');

    if (!is_user_logged_in()) {
        menuqr_json_response(false, ['message' => 'Login required.'], 401);
    }

    $user_id = get_current_user_id();
    menuqr_sync_user_role_context($user_id);

    $restaurant_ids = menuqr_get_user_accessible_restaurant_ids($user_id);
    $requested_restaurant_id = absint($_REQUEST['restaurant_id'] ?? 0);
    $requested_restaurant_ids = [];
    if (!empty($_REQUEST['restaurant_ids'])) {
        $requested_restaurant_ids = array_values(array_unique(array_filter(array_map('absint', explode(',', sanitize_text_field(wp_unslash($_REQUEST['restaurant_ids'])))))));
    }

    if ($requested_restaurant_id > 0) {
        $requested_restaurant_ids[] = $requested_restaurant_id;
    }

    if (current_user_can('manage_options') || menuqr_user_has_role('super_admin')) {
        if (!empty($requested_restaurant_ids)) {
            $restaurant_ids = $requested_restaurant_ids;
        }
    } else {
        if (!empty($requested_restaurant_ids)) {
            $restaurant_ids = array_values(array_intersect($restaurant_ids, $requested_restaurant_ids));
        }
    }

    if (empty($restaurant_ids)) {
        $fallback_restaurant_id = menuqr_get_current_restaurant_id();
        if ($fallback_restaurant_id > 0) {
            $restaurant_ids = [$fallback_restaurant_id];
        }
    }

    if (empty($restaurant_ids)) {
        menuqr_json_response(false, ['message' => 'Kitchen restaurant mapping not found.'], 400);
    }

    $restaurant_ids = array_values(array_unique(array_filter(array_map('absint', $restaurant_ids))));
    $orders = count($restaurant_ids) === 1
        ? menuqr_get_active_kitchen_orders((int) $restaurant_ids[0])
        : menuqr_get_active_kitchen_orders_by_restaurants($restaurant_ids);

    foreach ($orders as $order) {
        if (function_exists('menuqr_normalize_order_service_point')) {
            $order = menuqr_normalize_order_service_point($order);
        }
        $order->items = json_decode((string) $order->items_json, true) ?: [];
    }

    menuqr_json_response(true, [
        'orders' => $orders,
        'count'  => count($orders),
        'restaurant_ids' => $restaurant_ids,
        'generated_at' => time(),
    ]);
}
add_action('wp_ajax_menuqr_get_kitchen_orders', 'menuqr_ajax_get_kitchen_orders');

function menuqr_ajax_update_order_status(): void {
    menuqr_verify_ajax();
    if (!is_user_logged_in()) {
        menuqr_json_response(false, ['message' => 'Login required.'], 401);
    }
    $allowed = ['pending', 'accepted', 'preparing', 'ready', 'served', 'cancelled'];
    $order_id = absint($_POST['order_id'] ?? 0);
    $status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'pending'));
    if (!in_array($status, $allowed, true)) {
        menuqr_json_response(false, ['message' => 'Invalid status.'], 400);
    }
    global $wpdb;
    $orders = menuqr_table('orders');
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders} WHERE id = %d", $order_id));
    if (!$order) {
        menuqr_json_response(false, ['message' => 'Order not found.'], 404);
    }

    $user_id = get_current_user_id();
    $restaurant_ids = menuqr_get_user_accessible_restaurant_ids($user_id);
    $current_restaurant_id = menuqr_get_current_restaurant_id();
    if ($current_restaurant_id > 0) {
        $restaurant_ids[] = $current_restaurant_id;
    }
    $restaurant_ids = array_values(array_unique(array_filter(array_map('absint', $restaurant_ids))));

    if (!menuqr_user_has_role('super_admin') && !current_user_can('manage_options') && !in_array((int) $order->restaurant_id, $restaurant_ids, true)) {
        menuqr_json_response(false, ['message' => 'Access denied.'], 403);
    }
    $remarks = sanitize_textarea_field(wp_unslash($_POST['remarks'] ?? ''));
    if ($status === 'cancelled' && $remarks === '') {
        menuqr_json_response(false, ['message' => 'Cancellation reason is required.'], 400);
    }
    $update_data = ['order_status' => $status, 'updated_at' => current_time('mysql')];
    if ($status === 'cancelled') {
        $existing_note = trim((string) ($order->customer_note ?? ''));
        $cancel_note = 'Cancellation reason: ' . $remarks;
        $update_data['customer_note'] = $existing_note !== '' ? ($existing_note . "\n" . $cancel_note) : $cancel_note;
    }
    $wpdb->update($orders, $update_data, ['id' => $order_id]);
    menuqr_json_response(true, ['message' => 'Order updated.']);
}
add_action('wp_ajax_menuqr_update_order_status', 'menuqr_ajax_update_order_status');

function menuqr_ajax_save_payment_settings(): void {
    menuqr_verify_ajax();
    if (!is_user_logged_in()) {
        menuqr_json_response(false, ['message' => 'Login required.'], 401);
    }
    $restaurant_id = menuqr_get_current_restaurant_id();
    if (!$restaurant_id) {
        menuqr_json_response(false, ['message' => 'Restaurant missing.'], 400);
    }
    $saved = menuqr_save_payment_settings($restaurant_id, $_POST);
    menuqr_json_response($saved, ['message' => $saved ? 'Payment settings saved.' : 'Failed to save settings.']);
}
add_action('wp_ajax_menuqr_save_payment_settings', 'menuqr_ajax_save_payment_settings');

function menuqr_ajax_dashboard_data(): void {
    menuqr_verify_ajax();
    if (!is_user_logged_in()) {
        menuqr_json_response(false, ['message' => 'Login required.'], 401);
    }
    global $wpdb;
    $restaurant_id = menuqr_get_current_restaurant_id();
    $orders_table = menuqr_table('orders');
    $items_table  = menuqr_table('items');
    $restaurants_table = menuqr_table('restaurants');

    if (menuqr_user_has_role('super_admin')) {
        $order_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$orders_table}");
        $revenue = (float) $wpdb->get_var("SELECT COALESCE(SUM(final_total),0) FROM {$orders_table}");
        $restaurants = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$restaurants_table}");
        $items = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$items_table}");
    } else {
        $order_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$orders_table} WHERE restaurant_id = %d", $restaurant_id));
        $revenue = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(final_total),0) FROM {$orders_table} WHERE restaurant_id = %d", $restaurant_id));
        $restaurants = 1;
        $items = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$items_table} WHERE restaurant_id = %d", $restaurant_id));
    }

    menuqr_json_response(true, [
        'stats' => [
            'orders'      => $order_count,
            'revenue'     => menuqr_money($revenue),
            'restaurants' => $restaurants,
            'items'       => $items,
        ],
    ]);
}
add_action('wp_ajax_menuqr_dashboard_data', 'menuqr_ajax_dashboard_data');

function menuqr_ajax_apply_coupon(): void {
    nocache_headers();
    menuqr_verify_ajax();
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $code = strtoupper(sanitize_text_field(wp_unslash($_POST['coupon_code'] ?? '')));
    $subtotal = (float) wp_unslash($_POST['subtotal'] ?? 0);

    $coupon = menuqr_get_active_coupon($restaurant_id, $code);
    if (!$coupon) {
        menuqr_json_response(false, ['message' => 'Coupon not found or expired.'], 404);
    }

    $discount = menuqr_calculate_coupon_discount($coupon, $subtotal);
    if ($discount <= 0) {
        menuqr_json_response(false, ['message' => 'Minimum order not reached for this coupon.'], 400);
    }

    menuqr_json_response(true, [
        'code' => $coupon->code,
        'discount' => $discount,
        'message' => 'Coupon applied successfully.',
    ]);
}
add_action('wp_ajax_menuqr_apply_coupon', 'menuqr_ajax_apply_coupon');
add_action('wp_ajax_nopriv_menuqr_apply_coupon', 'menuqr_ajax_apply_coupon');


function menuqr_ajax_preview_qr_template(): void {
    menuqr_verify_ajax();
    menuqr_require_role(['restaurant_admin', 'super_admin']);

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $table_id = absint($_POST['table_id'] ?? 0);
    $template_key = sanitize_key(wp_unslash($_POST['template_key'] ?? 'minimal_clean'));

    if (!$restaurant_id || !$table_id) {
        menuqr_json_response(false, ['message' => 'Restaurant and table are required.'], 400);
    }

    menuqr_validate_restaurant_access($restaurant_id);
    $payload = menuqr_prepare_qr_template_payload($restaurant_id, $table_id, $template_key);

    if (empty($payload)) {
        menuqr_json_response(false, ['message' => 'Table not found.'], 404);
    }

    menuqr_json_response(true, [
        'message' => 'Preview updated.',
        'record' => $payload,
    ]);
}
add_action('wp_ajax_menuqr_preview_qr_template', 'menuqr_ajax_preview_qr_template');

function menuqr_ajax_create_qr_template(): void {
    menuqr_verify_ajax();
    menuqr_require_role(['restaurant_admin', 'super_admin']);

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $table_id = absint($_POST['table_id'] ?? 0);
    $template_key = sanitize_key(wp_unslash($_POST['template_key'] ?? 'minimal_clean'));

    if (!$restaurant_id || !$table_id) {
        menuqr_json_response(false, ['message' => 'Restaurant and table are required.'], 400);
    }

    menuqr_validate_restaurant_access($restaurant_id);
    $saved = menuqr_save_qr_template_record($restaurant_id, $table_id, $template_key);

    if (empty($saved['success'])) {
        menuqr_json_response(false, ['message' => $saved['message'] ?? 'QR template could not be created.'], 500);
    }

    menuqr_json_response(true, [
        'message' => $saved['message'],
        'record' => $saved['record'],
    ]);
}
add_action('wp_ajax_menuqr_create_qr_template', 'menuqr_ajax_create_qr_template');

function menuqr_ajax_bulk_generate_qr_templates(): void {
    menuqr_verify_ajax();
    menuqr_require_role(['restaurant_admin', 'super_admin']);

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $template_key = sanitize_key(wp_unslash($_POST['template_key'] ?? 'minimal_clean'));
    menuqr_validate_restaurant_access($restaurant_id);

    $tables = menuqr_get_tables($restaurant_id);
    $created = [];

    foreach ($tables as $table) {
        $saved = menuqr_save_qr_template_record($restaurant_id, (int) $table->id, $template_key);
        if (!empty($saved['success']) && !empty($saved['record'])) {
            $created[] = [
                'id' => (int) ($saved['record']['id'] ?? 0),
                'table_id' => (int) $table->id,
                'table_number' => (string) $table->table_number,
                'qr_url' => (string) $saved['record']['qr_url'],
            ];
        }
    }

    menuqr_json_response(true, [
        'message' => sprintf('%d QR templates generated successfully.', count($created)),
        'created' => $created,
    ]);
}
add_action('wp_ajax_menuqr_bulk_generate_qr_templates', 'menuqr_ajax_bulk_generate_qr_templates');
