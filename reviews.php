<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_clean_google_review_url(string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    $url = esc_url_raw($url, ['http', 'https']);
    if (!$url) {
        return '';
    }
    $host = wp_parse_url($url, PHP_URL_HOST);
    $host = is_string($host) ? strtolower($host) : '';
    $allowed_hosts = [
        'g.page',
        'google.com',
        'www.google.com',
        'search.google.com',
        'maps.google.com',
        'goo.gl',
        'maps.app.goo.gl',
    ];

    $is_google_host = in_array($host, $allowed_hosts, true) || str_ends_with($host, '.google.com') || str_ends_with($host, '.goo.gl');
    if (!$is_google_host) {
        return '';
    }

    return $url;
}

function menuqr_get_review_settings(int $restaurant_id): array {
    $restaurant = menuqr_get_restaurant($restaurant_id);
    if (!$restaurant) {
        return [
            'enabled' => false,
            'url' => '',
        ];
    }

    $enabled = (int) ($restaurant->google_reviews_enabled ?? 0) === 1;
    $url = menuqr_clean_google_review_url((string) ($restaurant->google_review_link ?? ''));
    $text = sanitize_text_field((string) ($restaurant->review_button_text ?? 'Review us on Google'));
    $message = sanitize_text_field((string) ($restaurant->review_request_message ?? 'Your honest Google review helps us improve.'));

    return [
        'enabled' => $enabled && $url !== '',
        'url' => $url,
        'place_id' => sanitize_text_field((string) ($restaurant->google_place_id ?? '')),
        'button_text' => $text ?: 'Review us on Google',
        'message' => $message ?: 'Your honest Google review helps us improve.',
        'show_after_served' => (int) ($restaurant->show_review_after_served ?? 1) === 1,
        'show_on_bill' => (int) ($restaurant->show_review_on_bill ?? 1) === 1,
        'show_on_print' => (int) ($restaurant->show_review_on_print ?? 1) === 1,
    ];
}

function menuqr_get_review_public_payload(int $restaurant_id): array {
    $settings = menuqr_get_review_settings($restaurant_id);
    return [
        'enabled' => (bool) $settings['enabled'],
        'url' => (string) $settings['url'],
        'button_text' => (string) $settings['button_text'],
        'message' => (string) $settings['message'],
        'show_after_served' => (bool) $settings['show_after_served'],
        'show_on_bill' => (bool) $settings['show_on_bill'],
        'show_on_print' => (bool) $settings['show_on_print'],
    ];
}

function menuqr_track_review_click(int $restaurant_id, int $table_id = 0, int $order_id = 0, int $bill_session_id = 0, string $customer_phone = ''): void {
    global $wpdb;
    if ($restaurant_id <= 0) {
        return;
    }

    $ip = sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $ua = sanitize_text_field(substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250));

    $wpdb->insert(menuqr_table('review_clicks'), [
        'restaurant_id' => $restaurant_id,
        'table_id' => $table_id,
        'order_id' => $order_id,
        'bill_session_id' => $bill_session_id,
        'customer_phone' => sanitize_text_field($customer_phone),
        'ip_hash' => $ip ? wp_hash($ip) : '',
        'user_agent' => $ua,
        'clicked_at' => current_time('mysql'),
    ]);
}

function menuqr_review_click_url(int $restaurant_id, int $table_id = 0, int $order_id = 0, int $bill_session_id = 0, string $customer_phone = ''): string {
    $settings = menuqr_get_review_settings($restaurant_id);
    if (empty($settings['enabled']) || empty($settings['url'])) {
        return '';
    }

    return add_query_arg([
        'menuqr_review_redirect' => 1,
        'r' => $restaurant_id,
        't' => $table_id,
        'order' => $order_id,
        'session' => $bill_session_id,
        'phone' => rawurlencode($customer_phone),
        'nonce' => wp_create_nonce('menuqr_review_' . $restaurant_id),
    ], home_url('/'));
}

function menuqr_handle_review_redirect(): void {
    if (empty($_GET['menuqr_review_redirect'])) {
        return;
    }

    $restaurant_id = absint($_GET['r'] ?? 0);
    $nonce = sanitize_text_field(wp_unslash($_GET['nonce'] ?? ''));
    if (!$restaurant_id || !wp_verify_nonce($nonce, 'menuqr_review_' . $restaurant_id)) {
        wp_die(esc_html__('Invalid review link.', 'menuqr'));
    }

    $settings = menuqr_get_review_settings($restaurant_id);
    if (empty($settings['enabled']) || empty($settings['url'])) {
        wp_die(esc_html__('Google review link is not configured.', 'menuqr'));
    }

    menuqr_track_review_click(
        $restaurant_id,
        absint($_GET['t'] ?? 0),
        absint($_GET['order'] ?? 0),
        absint($_GET['session'] ?? 0),
        sanitize_text_field(rawurldecode((string) ($_GET['phone'] ?? '')))
    );

    wp_safe_redirect($settings['url']);
    exit;
}
add_action('template_redirect', 'menuqr_handle_review_redirect', 1);

function menuqr_save_review_settings(int $restaurant_id, array $input): bool {
    global $wpdb;
    $restaurant_id = absint($restaurant_id);
    if (!$restaurant_id) {
        return false;
    }

    $review_url = menuqr_clean_google_review_url((string) wp_unslash($input['google_review_link'] ?? ''));
    if (!empty($input['google_reviews_enabled']) && $review_url === '') {
        return false;
    }

    $payload = [
        'google_reviews_enabled' => !empty($input['google_reviews_enabled']) ? 1 : 0,
        'google_review_link' => $review_url,
        'google_place_id' => sanitize_text_field(wp_unslash($input['google_place_id'] ?? '')),
        'review_button_text' => sanitize_text_field(wp_unslash($input['review_button_text'] ?? 'Review us on Google')),
        'review_request_message' => sanitize_text_field(wp_unslash($input['review_request_message'] ?? 'Your honest Google review helps us improve.')),
        'show_review_after_served' => !empty($input['show_review_after_served']) ? 1 : 0,
        'show_review_on_bill' => !empty($input['show_review_on_bill']) ? 1 : 0,
        'show_review_on_print' => !empty($input['show_review_on_print']) ? 1 : 0,
        'updated_at' => current_time('mysql'),
    ];

    return false !== $wpdb->update(menuqr_table('restaurants'), $payload, ['id' => $restaurant_id]);
}

function menuqr_get_review_click_stats(int $restaurant_id): array {
    global $wpdb;
    $table = menuqr_table('review_clicks');
    $today = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE restaurant_id = %d AND DATE(clicked_at) = CURDATE()",
        $restaurant_id
    ));
    $month = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE restaurant_id = %d AND YEAR(clicked_at) = YEAR(CURDATE()) AND MONTH(clicked_at) = MONTH(CURDATE())",
        $restaurant_id
    ));
    $total = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE restaurant_id = %d",
        $restaurant_id
    ));

    return [
        'today' => $today,
        'month' => $month,
        'total' => $total,
    ];
}
