<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_payment_default_settings(int $restaurant_id): object {
    return (object) [
        'id' => 0,
        'restaurant_id' => $restaurant_id,
        'cash_enabled' => 1,
        'upi_enabled' => 0,
        'upi_id' => '',
        'upi_qr' => '',
        'screenshot_enabled' => 0,
        'bank_transfer_enabled' => 0,
        'bank_account_name' => '',
        'bank_account_number' => '',
        'bank_ifsc' => '',
        'bank_name' => '',
        'bank_branch' => '',
        'online_enabled' => 0,
        'razorpay_key' => '',
        'razorpay_secret' => '',
        'stripe_publishable_key' => '',
        'stripe_secret_key' => '',
        'razorpay_webhook_secret' => '',
        'razorpay_mode' => 'test',
        'stripe_webhook_secret' => '',
        'stripe_mode' => 'test',
        'manual_verification_required' => 1,
        'upi_merchant_name' => '',
        'payment_instructions' => '',
        'whatsapp_enabled' => 0,
        'whatsapp_number' => '',
        'whatsapp_api_token' => '',
        'bill_message_template' => '',
        'payment_reminder_template' => '',
        'review_request_template' => '',
        'auto_send_bill' => 0,
        'gateway_provider' => 'razorpay',
        'phonepe_enabled' => 0,
        'phonepe_client_id' => '',
        'phonepe_client_secret' => '',
        'phonepe_client_version' => '',
        'phonepe_merchant_id' => '',
        'phonepe_environment' => 'sandbox',
        'created_at' => '',
        'updated_at' => '',
    ];
}

function menuqr_get_payment_settings(int $restaurant_id): object {
    global $wpdb;
    $table = menuqr_table('payment_settings');

    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d ORDER BY id DESC LIMIT 1", $restaurant_id));
    if (!$row) {
        return menuqr_payment_default_settings($restaurant_id);
    }

    $defaults = menuqr_payment_default_settings($restaurant_id);
    foreach ($defaults as $key => $value) {
        if (!property_exists($row, $key)) {
            $row->{$key} = $value;
        }
    }

    $row->cash_enabled = (int) $row->cash_enabled;
    $row->upi_enabled = (int) $row->upi_enabled;
    $row->screenshot_enabled = (int) $row->screenshot_enabled;
    $row->online_enabled = (int) $row->online_enabled;
    $row->bank_transfer_enabled = (int) ($row->bank_transfer_enabled ?? 0);
    $row->phonepe_enabled = (int) ($row->phonepe_enabled ?? 0);
    $row->whatsapp_enabled = (int) ($row->whatsapp_enabled ?? 0);
    $row->manual_verification_required = (int) ($row->manual_verification_required ?? 1);

    return $row;
}

function menuqr_save_payment_settings(int $restaurant_id, array $data): bool {
    global $wpdb;
    $table = menuqr_table('payment_settings');
    $now = current_time('mysql');

    $cash_enabled = !empty($data['cash_enabled']) ? 1 : 0;
    $upi_enabled = !empty($data['upi_enabled']) ? 1 : 0;
    $online_enabled = !empty($data['online_enabled']) ? 1 : 0;

    // Never leave checkout without a usable method.
    if (!$cash_enabled && !$upi_enabled && !$online_enabled) {
        $cash_enabled = 1;
    }

    $payload = [
        'restaurant_id'         => $restaurant_id,
        'cash_enabled'          => $cash_enabled,
        'upi_enabled'           => $upi_enabled,
        'upi_id'                => strtolower(preg_replace('/\s+/', '', sanitize_text_field($data['upi_id'] ?? ''))),
        'upi_qr'                => esc_url_raw($data['upi_qr'] ?? ''),
        'upi_merchant_name'     => sanitize_text_field($data['upi_merchant_name'] ?? ''),
        'payment_instructions'  => sanitize_textarea_field($data['payment_instructions'] ?? ''),
        'manual_verification_required' => !empty($data['manual_verification_required']) ? 1 : 0,
        'screenshot_enabled'    => !empty($data['screenshot_enabled']) ? 1 : 0,
        'bank_transfer_enabled' => !empty($data['bank_transfer_enabled']) ? 1 : 0,
        'bank_account_name'     => sanitize_text_field($data['bank_account_name'] ?? ''),
        'bank_account_number'   => sanitize_text_field($data['bank_account_number'] ?? ''),
        'bank_ifsc'             => sanitize_text_field($data['bank_ifsc'] ?? ''),
        'bank_name'             => sanitize_text_field($data['bank_name'] ?? ''),
        'bank_branch'           => sanitize_text_field($data['bank_branch'] ?? ''),
        'online_enabled'        => $online_enabled,
        'razorpay_key'          => sanitize_text_field($data['razorpay_key'] ?? ''),
        'razorpay_secret'       => sanitize_text_field($data['razorpay_secret'] ?? ''),
        'razorpay_webhook_secret' => sanitize_text_field($data['razorpay_webhook_secret'] ?? ''),
        'razorpay_mode'         => sanitize_key($data['razorpay_mode'] ?? 'test'),
        'stripe_publishable_key'=> sanitize_text_field($data['stripe_publishable_key'] ?? ''),
        'stripe_secret_key'     => sanitize_text_field($data['stripe_secret_key'] ?? ''),
        'stripe_webhook_secret'=> sanitize_text_field($data['stripe_webhook_secret'] ?? ''),
        'stripe_mode'          => sanitize_key($data['stripe_mode'] ?? 'test'),
        'whatsapp_enabled'     => !empty($data['whatsapp_enabled']) ? 1 : 0,
        'whatsapp_number'      => sanitize_text_field($data['whatsapp_number'] ?? ''),
        'whatsapp_api_token'   => sanitize_text_field($data['whatsapp_api_token'] ?? ''),
        'bill_message_template'=> sanitize_textarea_field($data['bill_message_template'] ?? ''),
        'payment_reminder_template'=> sanitize_textarea_field($data['payment_reminder_template'] ?? ''),
        'review_request_template'=> sanitize_textarea_field($data['review_request_template'] ?? ''),
        'auto_send_bill'       => !empty($data['auto_send_bill']) ? 1 : 0,
        'gateway_provider'      => sanitize_key($data['gateway_provider'] ?? 'razorpay'),
        'phonepe_enabled'       => !empty($data['phonepe_enabled']) ? 1 : 0,
        'phonepe_client_id'     => sanitize_text_field($data['phonepe_client_id'] ?? ''),
        'phonepe_client_secret' => sanitize_textarea_field($data['phonepe_client_secret'] ?? ''),
        'phonepe_client_version'=> sanitize_text_field($data['phonepe_client_version'] ?? ''),
        'phonepe_merchant_id'   => sanitize_text_field($data['phonepe_merchant_id'] ?? ''),
        'phonepe_environment'   => sanitize_key($data['phonepe_environment'] ?? 'sandbox'),
        'updated_at'            => $now,
    ];

    $existing_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE restaurant_id = %d ORDER BY id DESC LIMIT 1", $restaurant_id));

    $ok = false;
    if ($existing_id > 0) {
        $updated = $wpdb->update($table, $payload, ['id' => $existing_id]);
        $ok = (false !== $updated);
    } else {
        $payload['created_at'] = $now;
        $inserted = $wpdb->insert($table, $payload);
        $ok = (false !== $inserted);
    }

    if ($ok && function_exists('fqx_table')) {
        $secure = [
            'restaurant_id' => $restaurant_id,
            'cash_enabled' => $cash_enabled,
            'upi_enabled' => $upi_enabled,
            'upi_id' => $payload['upi_id'],
            'upi_qr_url' => $payload['upi_qr'],
            'upi_merchant_name' => sanitize_text_field($data['upi_merchant_name'] ?? ''),
            'payment_instructions' => sanitize_textarea_field($data['payment_instructions'] ?? ''),
            'manual_verification_required' => !empty($data['manual_verification_required']) ? 1 : 0,
            'razorpay_enabled' => $online_enabled && !empty($payload['razorpay_key']) ? 1 : 0,
            'razorpay_key_id' => $payload['razorpay_key'],
            'razorpay_key_secret_encrypted' => function_exists('fqx_encrypt_secret') ? fqx_encrypt_secret((string) $payload['razorpay_secret']) : $payload['razorpay_secret'],
            'razorpay_webhook_secret_encrypted' => function_exists('fqx_encrypt_secret') ? fqx_encrypt_secret((string) ($data['razorpay_webhook_secret'] ?? '')) : sanitize_text_field($data['razorpay_webhook_secret'] ?? ''),
            'razorpay_mode' => sanitize_key($data['razorpay_mode'] ?? 'test'),
            'stripe_enabled' => !empty($data['stripe_enabled']) ? 1 : 0,
            'stripe_publishable_key' => $payload['stripe_publishable_key'],
            'stripe_secret_key_encrypted' => function_exists('fqx_encrypt_secret') ? fqx_encrypt_secret((string) $payload['stripe_secret_key']) : $payload['stripe_secret_key'],
            'stripe_webhook_secret_encrypted' => function_exists('fqx_encrypt_secret') ? fqx_encrypt_secret((string) ($data['stripe_webhook_secret'] ?? '')) : sanitize_text_field($data['stripe_webhook_secret'] ?? ''),
            'stripe_mode' => sanitize_key($data['stripe_mode'] ?? 'test'),
            'updated_at' => $now,
        ];
        $rid = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM " . fqx_table('restaurant_payment_settings') . " WHERE restaurant_id=%d", $restaurant_id));
        if ($rid) { $wpdb->update(fqx_table('restaurant_payment_settings'), $secure, ['id' => $rid]); }
        else { $secure['created_at'] = $now; $wpdb->insert(fqx_table('restaurant_payment_settings'), $secure); }
    }

    return $ok;
}


function menuqr_payment_has_razorpay(object $payment): bool {
    return !empty($payment->online_enabled) && !empty($payment->razorpay_key) && !empty($payment->razorpay_secret);
}

function menuqr_payment_has_phonepe(object $payment): bool {
    return !empty($payment->online_enabled)
        && !empty($payment->phonepe_enabled)
        && !empty($payment->phonepe_client_id)
        && !empty($payment->phonepe_client_secret)
        && !empty($payment->phonepe_client_version);
}

function menuqr_gateway_public_payload(object $payment): array {
    $providers = [];
    if (menuqr_payment_has_razorpay($payment)) {
        $providers[] = 'razorpay';
    }
    if (menuqr_payment_has_phonepe($payment)) {
        $providers[] = 'phonepe';
    }

    return [
        'provider' => $payment->gateway_provider ?: ($providers[0] ?? 'razorpay'),
        'providers' => $providers,
        'razorpay_enabled' => menuqr_payment_has_razorpay($payment) ? 1 : 0,
        'phonepe_enabled' => menuqr_payment_has_phonepe($payment) ? 1 : 0,
        'stripe_enabled' => (!empty($payment->stripe_publishable_key) && !empty($payment->stripe_secret_key)) ? 1 : 0,
        'stripe_publishable_key' => !empty($payment->stripe_publishable_key) ? $payment->stripe_publishable_key : '',
        'razorpay_key' => !empty($payment->razorpay_key) ? $payment->razorpay_key : '',
    ];
}
