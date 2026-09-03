<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v185 - Super Admin platform subscription payment gateway settings.
 * Keeps restaurant customer-order payment settings completely separate.
 */

if (!function_exists('fqx_v185_mask_secret')) {
    function fqx_v185_mask_secret($value, int $keep = 4): string {
        $value = (string) $value;
        if ($value === '') { return 'Not configured'; }
        if (function_exists('fqx_decrypt_secret')) {
            $maybe = fqx_decrypt_secret($value);
            if (is_string($maybe) && $maybe !== '') { $value = $maybe; }
        }
        $len = strlen($value);
        if ($len <= $keep) { return str_repeat('•', 8); }
        return substr($value, 0, min(6, $len - $keep)) . str_repeat('•', max(6, $len - $keep - min(6, $len - $keep))) . substr($value, -$keep);
    }
}

if (!function_exists('fqx_v185_gateway_status')) {
    function fqx_v185_gateway_status(array $settings, string $gateway): string {
        switch ($gateway) {
            case 'upi': return !empty($settings['platform_upi_enabled']) && !empty($settings['platform_upi_id']) ? 'Active' : 'Setup Needed';
            case 'razorpay': return !empty($settings['razorpay_enabled']) && !empty($settings['razorpay_key_id']) ? 'Active' : 'Setup Needed';
            case 'stripe': return !empty($settings['stripe_enabled']) && !empty($settings['stripe_publishable_key']) ? 'Active' : 'Setup Needed';
            case 'bank': return !empty($settings['bank_transfer_enabled']) && !empty($settings['bank_account_number']) ? 'Active' : 'Setup Needed';
        }
        return 'Setup Needed';
    }
}

if (!function_exists('fqx_v185_gateway_enabled_count')) {
    function fqx_v185_gateway_enabled_count(array $settings): int {
        $count = 0;
        if (!empty($settings['platform_upi_enabled'])) { $count++; }
        if (!empty($settings['razorpay_enabled'])) { $count++; }
        if (!empty($settings['stripe_enabled'])) { $count++; }
        if (!empty($settings['bank_transfer_enabled'])) { $count++; }
        return $count;
    }
}

if (!function_exists('fqx_v185_handle_save_platform_gateway')) {
    function fqx_v185_handle_save_platform_gateway(): void {
        menuqr_require_role(['super_admin']);
        check_admin_referer('fqx_v185_save_platform_gateway', 'fqx_v185_gateway_nonce');

        $data = wp_unslash($_POST);

        if (!empty($_FILES['platform_upi_qr_file']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $file = $_FILES['platform_upi_qr_file'];
            $allowed = ['image/png', 'image/jpeg', 'image/webp'];
            if (!empty($file['type']) && in_array($file['type'], $allowed, true)) {
                $uploaded = wp_handle_upload($file, ['test_form' => false]);
                if (!empty($uploaded['url'])) {
                    $data['platform_upi_qr'] = esc_url_raw($uploaded['url']);
                }
            }
        }

        if (function_exists('menuqr_save_platform_payment_settings')) {
            menuqr_save_platform_payment_settings($data);
        }

        if (function_exists('fqx_clear_cache_after_update')) {
            fqx_clear_cache_after_update();
        } elseif (function_exists('menuqr_clear_platform_cache')) {
            menuqr_clear_platform_cache();
        }

        wp_safe_redirect(add_query_arg(['tab' => 'payment-gateway', 'gateway_saved' => '1'], menuqr_get_page_url_by_slug('super-admin-dashboard')));
        exit;
    }
    add_action('admin_post_fqx_v185_save_platform_gateway', 'fqx_v185_handle_save_platform_gateway');
}
