<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_table(string $suffix): string {
    global $wpdb;
    return $wpdb->prefix . 'qrmenu_' . $suffix;
}



function menuqr_get_restaurant_bill_settings(int $restaurant_id): array {
    $defaults = [
        'restaurant_logo' => '',
        'restaurant_cover' => '',
        'bill_header_logo' => '',
        'tagline' => '',
        'address' => '',
        'phone' => '',
        'email' => '',
        'gst_number' => '',
        'fssai_number' => '',
        'thank_you_text' => 'Thank you for dining with us!',
        'footer_text' => '',
        'currency_symbol' => '₹',
        'tax_label' => 'GST/Tax',
        'service_charge_enabled' => 1,
        'packaging_charge_enabled' => 1,
        'delivery_charge_enabled' => 1,
        'round_off_enabled' => 1,
        'show_customer_phone' => 1,
        'show_staff_name' => 1,
        'show_payment_method' => 1,
        'show_payment_status' => 1,
        'show_bill_history' => 1,
        'show_powered_by' => 1,
        'bill_watermark_enabled' => 1,
        'bill_watermark_image' => '',
        'bill_watermark_text' => '',
        'bill_watermark_opacity' => '0.06',
        'bill_brand_color' => '#F4B11A',
        'print_paper_size' => '80mm',
        'print_density' => 'normal',
        'service_charge_value' => '5',
        'show_qr_barcode' => 1,
        'show_table_room_number' => 1,
        'show_date_time' => 1,
        'show_tax_breakdown' => 1,
        'show_gst_number' => 1,
        'show_thank_you_note' => 1,
        'show_restaurant_logo' => 1,
        'show_order_type' => 1,
        'show_bill_header_logo' => 1,
        'show_service_charge_on_bill' => 1,
        'whatsapp_bill_template' => "Hello, thank you for ordering from {restaurant_name}.\n\nYour bill is ready.\n\nOrder ID: #{order_id}\nTable: {table}\nTotal: {grand_total}\nPayment Status: {payment_status}\n\nView Bill:\n{bill_url}",
    ];

    if ($restaurant_id <= 0) {
        return $defaults;
    }

    $saved = get_option('menuqr_bill_settings_' . $restaurant_id, []);
    if (!is_array($saved)) {
        $saved = [];
    }

    $settings = array_merge($defaults, $saved);
    $checkbox_keys = [
        'service_charge_enabled',
        'packaging_charge_enabled',
        'delivery_charge_enabled',
        'round_off_enabled',
        'show_customer_phone',
        'show_staff_name',
        'show_payment_method',
        'show_payment_status',
        'show_bill_history',
        'show_powered_by',
        'bill_watermark_enabled',
        'show_qr_barcode',
        'show_table_room_number',
        'show_date_time',
        'show_tax_breakdown',
        'show_gst_number',
        'show_thank_you_note',
        'show_restaurant_logo',
        'show_order_type',
        'show_bill_header_logo',
        'show_service_charge_on_bill',
    ];

    foreach ($checkbox_keys as $key) {
        $settings[$key] = !empty($settings[$key]) ? 1 : 0;
    }

    foreach (['restaurant_logo', 'restaurant_cover', 'bill_header_logo', 'bill_watermark_image'] as $url_key) {
        $settings[$url_key] = esc_url_raw((string) ($settings[$url_key] ?? ''));
    }

    foreach (['tagline', 'address', 'phone', 'email', 'gst_number', 'fssai_number', 'thank_you_text', 'footer_text', 'currency_symbol', 'tax_label', 'bill_watermark_text', 'whatsapp_bill_template', 'bill_brand_color', 'print_paper_size', 'print_density', 'service_charge_value'] as $text_key) {
        $settings[$text_key] = is_string($settings[$text_key]) ? trim(wp_kses_post((string) $settings[$text_key])) : '';
    }

    $settings['bill_watermark_opacity'] = (string) min(0.18, max(0.02, (float) ($settings['bill_watermark_opacity'] ?? '0.06')));

    return $settings;
}

function menuqr_save_restaurant_bill_settings(int $restaurant_id, array $data): bool {
    if ($restaurant_id <= 0) {
        return false;
    }

    $current = menuqr_get_restaurant_bill_settings($restaurant_id);

    $payload = [
        'restaurant_logo' => esc_url_raw((string) ($data['restaurant_logo'] ?? $current['restaurant_logo'])),
        'restaurant_cover' => esc_url_raw((string) ($data['restaurant_cover'] ?? $current['restaurant_cover'])),
        'bill_header_logo' => esc_url_raw((string) ($data['bill_header_logo'] ?? $current['bill_header_logo'] ?? '')),
        'tagline' => sanitize_text_field((string) ($data['tagline'] ?? '')),
        'address' => sanitize_textarea_field((string) ($data['address'] ?? '')),
        'phone' => sanitize_text_field((string) ($data['phone'] ?? '')),
        'email' => sanitize_email((string) ($data['email'] ?? '')),
        'gst_number' => sanitize_text_field((string) ($data['gst_number'] ?? '')),
        'fssai_number' => sanitize_text_field((string) ($data['fssai_number'] ?? '')),
        'thank_you_text' => sanitize_text_field((string) ($data['thank_you_text'] ?? '')),
        'footer_text' => sanitize_textarea_field((string) ($data['footer_text'] ?? '')),
        'currency_symbol' => sanitize_text_field((string) ($data['currency_symbol'] ?? '₹')),
        'tax_label' => sanitize_text_field((string) ($data['tax_label'] ?? 'GST/Tax')),
        'service_charge_enabled' => !empty($data['service_charge_enabled']) ? 1 : 0,
        'packaging_charge_enabled' => !empty($data['packaging_charge_enabled']) ? 1 : 0,
        'delivery_charge_enabled' => !empty($data['delivery_charge_enabled']) ? 1 : 0,
        'round_off_enabled' => !empty($data['round_off_enabled']) ? 1 : 0,
        'show_customer_phone' => !empty($data['show_customer_phone']) ? 1 : 0,
        'show_staff_name' => !empty($data['show_staff_name']) ? 1 : 0,
        'show_payment_method' => !empty($data['show_payment_method']) ? 1 : 0,
        'show_payment_status' => !empty($data['show_payment_status']) ? 1 : 0,
        'show_bill_history' => !empty($data['show_bill_history']) ? 1 : 0,
        'show_powered_by' => !empty($data['show_powered_by']) ? 1 : 0,
        'bill_watermark_enabled' => !empty($data['bill_watermark_enabled']) ? 1 : 0,
        'bill_watermark_image' => esc_url_raw((string) ($data['bill_watermark_image'] ?? $current['bill_watermark_image'])),
        'bill_watermark_text' => sanitize_text_field((string) ($data['bill_watermark_text'] ?? '')),
        'bill_watermark_opacity' => (string) min(0.18, max(0.02, (float) ($data['bill_watermark_opacity'] ?? $current['bill_watermark_opacity'] ?? '0.06'))),
        'whatsapp_bill_template' => sanitize_textarea_field((string) ($data['whatsapp_bill_template'] ?? $current['whatsapp_bill_template'])),
        'bill_brand_color' => sanitize_hex_color((string) ($data['bill_brand_color'] ?? $current['bill_brand_color'] ?? '#F4B11A')) ?: '#F4B11A',
        'print_paper_size' => sanitize_key((string) ($data['print_paper_size'] ?? $current['print_paper_size'] ?? '80mm')),
        'print_density' => sanitize_key((string) ($data['print_density'] ?? $current['print_density'] ?? 'normal')),
        'service_charge_value' => sanitize_text_field((string) ($data['service_charge_value'] ?? $current['service_charge_value'] ?? '5')),
        'show_qr_barcode' => !empty($data['show_qr_barcode']) ? 1 : 0,
        'show_table_room_number' => !empty($data['show_table_room_number']) ? 1 : 0,
        'show_date_time' => !empty($data['show_date_time']) ? 1 : 0,
        'show_tax_breakdown' => !empty($data['show_tax_breakdown']) ? 1 : 0,
        'show_gst_number' => !empty($data['show_gst_number']) ? 1 : 0,
        'show_thank_you_note' => !empty($data['show_thank_you_note']) ? 1 : 0,
        'show_restaurant_logo' => !empty($data['show_restaurant_logo']) ? 1 : 0,
        'show_order_type' => !empty($data['show_order_type']) ? 1 : 0,
        'show_bill_header_logo' => !empty($data['show_bill_header_logo']) ? 1 : 0,
        'show_service_charge_on_bill' => !empty($data['show_service_charge_on_bill']) ? 1 : 0,
    ];

    return update_option('menuqr_bill_settings_' . $restaurant_id, $payload, false);
}

function menuqr_get_restaurant_branding_data(int $restaurant_id): array {
    $restaurant = menuqr_get_restaurant($restaurant_id);
    $settings = menuqr_get_restaurant_bill_settings($restaurant_id);
    $name = $restaurant->name ?? get_bloginfo('name');

    return [
        'name' => $name,
        'tagline' => $settings['tagline'] ?: '',
        'address' => $settings['address'] ?: ($restaurant->address ?? ''),
        'phone' => $settings['phone'] ?: ($restaurant->phone ?? ''),
        'email' => $settings['email'] ?: ($restaurant->email ?? ''),
        'gst_number' => $settings['gst_number'] ?: ($restaurant->gst_number ?? ''),
        'fssai_number' => $settings['fssai_number'] ?: ($restaurant->fssai_number ?? ''),
        'logo' => $settings['restaurant_logo'] ?: ($restaurant->logo ?? ''),
        'cover' => $settings['restaurant_cover'] ?: '',
        'currency_symbol' => $settings['currency_symbol'] ?: '₹',
        'tax_label' => $settings['tax_label'] ?: 'GST/Tax',
        'bill_watermark_image' => $settings['bill_watermark_image'] ?: ($settings['restaurant_logo'] ?: ($restaurant->logo ?? '')),
        'bill_watermark_text' => $settings['bill_watermark_text'] ?: $name,
        'bill_watermark_opacity' => $settings['bill_watermark_opacity'] ?: '0.06',
        'settings' => $settings,
    ];
}

function menuqr_get_restaurant_initials(string $name): string {
    $name = trim(wp_strip_all_tags($name));
    if ($name === '') {
        return 'R';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1));
    }

    return $initials ?: strtoupper(function_exists('mb_substr') ? mb_substr($name, 0, 1) : substr($name, 0, 1));
}

function menuqr_render_restaurant_logo(?string $logo_url, string $name, string $class = 'menuqr-brand-logo'): string {
    $class_attr = sanitize_html_class($class);
    if (!empty($logo_url)) {
        return '<span class="' . esc_attr($class_attr . ' has-image') . '"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($name) . '" loading="lazy"></span>';
    }

    return '<span class="' . esc_attr($class_attr . ' is-fallback') . '"><span>' . esc_html(menuqr_get_restaurant_initials($name)) . '</span></span>';
}


function menuqr_find_table_by_reference(int $restaurant_id, $reference, string $mode = 'auto') {
    $needle = trim((string) $reference);
    $mode = sanitize_key($mode ?: 'auto');
    if ($restaurant_id <= 0 || $needle === '') {
        return null;
    }

    $tables = menuqr_get_tables($restaurant_id);
    $digits = preg_replace('/\D+/', '', $needle);

    // When QR explicitly sends table_no/table_number, never confuse it with DB id.
    if (in_array($mode, ['number', 'table_number', 'label'], true)) {
        foreach ($tables as $table) {
            $number = trim((string) ($table->table_number ?? ''));
            if ($number !== '' && strcasecmp($number, $needle) === 0) {
                return $table;
            }
            $number_digits = preg_replace('/\D+/', '', $number);
            if ($digits !== '' && $number_digits !== '' && $number_digits === $digits) {
                return $table;
            }
        }
        return null;
    }

    // Default generated QR uses table id. Try id first, then visible table number.
    foreach ($tables as $table) {
        if ((string) ((int) ($table->id ?? 0)) === $needle) {
            return $table;
        }
    }
    foreach ($tables as $table) {
        $number = trim((string) ($table->table_number ?? ''));
        if ($number !== '' && strcasecmp($number, $needle) === 0) {
            return $table;
        }
    }

    if ($digits !== '') {
        foreach ($tables as $table) {
            if ((string) ((int) ($table->id ?? 0)) === $digits) {
                return $table;
            }
        }
        foreach ($tables as $table) {
            $number = preg_replace('/\D+/', '', (string) ($table->table_number ?? ''));
            if ($number !== '' && $number === $digits) {
                return $table;
            }
        }
    }

    return null;
}

function menuqr_find_room_by_reference(int $restaurant_id, $reference, string $mode = 'auto') {
    $needle = trim((string) $reference);
    $mode = sanitize_key($mode ?: 'auto');
    if ($restaurant_id <= 0 || $needle === '') {
        return null;
    }

    $rooms = menuqr_get_rooms($restaurant_id);
    $digits = preg_replace('/\D+/', '', $needle);

    // When QR explicitly sends room_no/room_number, never confuse it with DB id.
    if (in_array($mode, ['number', 'room_number', 'label'], true)) {
        foreach ($rooms as $room) {
            $number = trim((string) ($room->room_number ?? ''));
            if ($number !== '' && strcasecmp($number, $needle) === 0) {
                return $room;
            }
            $number_digits = preg_replace('/\D+/', '', $number);
            if ($digits !== '' && $number_digits !== '' && $number_digits === $digits) {
                return $room;
            }
        }
        return null;
    }

    // Default generated QR uses room id. Try id first, then visible room number.
    foreach ($rooms as $room) {
        if ((string) ((int) ($room->id ?? 0)) === $needle) {
            return $room;
        }
    }
    foreach ($rooms as $room) {
        $number = trim((string) ($room->room_number ?? ''));
        if ($number !== '' && strcasecmp($number, $needle) === 0) {
            return $room;
        }
    }

    if ($digits !== '') {
        foreach ($rooms as $room) {
            if ((string) ((int) ($room->id ?? 0)) === $digits) {
                return $room;
            }
        }
        foreach ($rooms as $room) {
            $number = preg_replace('/\D+/', '', (string) ($room->room_number ?? ''));
            if ($number !== '' && $number === $digits) {
                return $room;
            }
        }
    }

    return null;
}

function menuqr_get_table_display_name(int $restaurant_id, int $table_id, string $fallback = ''): string {
    if ($restaurant_id <= 0 || $table_id <= 0) {
        return $fallback ?: '—';
    }

    foreach (menuqr_get_tables($restaurant_id) as $table) {
        if ((int) $table->id === $table_id) {
            $value = trim((string) ($table->table_number ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
    }

    return $fallback ?: '—';
}

function menuqr_format_amount(float $amount, $restaurant_or_symbol = null): string {
    $symbol = '₹';
    if (is_numeric($restaurant_or_symbol)) {
        $settings = menuqr_get_restaurant_bill_settings((int) $restaurant_or_symbol);
        $symbol = $settings['currency_symbol'] ?: '₹';
    } elseif (is_string($restaurant_or_symbol) && $restaurant_or_symbol !== '') {
        $symbol = $restaurant_or_symbol;
    }

    return $symbol . number_format($amount, 2);
}

function menuqr_money(float $amount): string {
    return menuqr_format_amount($amount);
}

function menuqr_json_response(bool $success, array $data = [], int $status = 200): void {
    status_header($status);
    wp_send_json([
        'success' => $success,
        'data'    => $data,
    ], $status);
}


function menuqr_user_matches_staff_record(?WP_User $user = null): bool {
    global $wpdb;
    $user = $user ?: wp_get_current_user();
    if (!$user || empty($user->ID) || empty($user->user_email)) {
        return false;
    }

    $staff_table = menuqr_table('staff');
    $count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$staff_table} WHERE wp_user_id = %d OR email = %s",
        (int) $user->ID,
        (string) $user->user_email
    ));

    return $count > 0;
}

function menuqr_user_is_staff_panel(?WP_User $user = null): bool {
    $user = $user ?: wp_get_current_user();
    if (!$user || empty($user->ID)) {
        return false;
    }

    $roles = (array) $user->roles;
    if (in_array('staff', $roles, true)) {
        return true;
    }

    return menuqr_user_matches_staff_record($user);
}

function menuqr_user_is_restaurant_admin_panel(?WP_User $user = null): bool {
    $user = $user ?: wp_get_current_user();
    if (!$user || empty($user->ID)) {
        return false;
    }

    return in_array('restaurant_admin', (array) $user->roles, true);
}

function menuqr_sync_user_role_context(int $user_id): void {
    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }

    if (current_user_can('manage_options') || in_array('administrator', (array) $user->roles, true) || in_array('super_admin', (array) $user->roles, true)) {
        return;
    }

    if (menuqr_user_matches_staff_record($user) && !in_array('staff', (array) $user->roles, true)) {
        $user->set_role('staff');
        $user = get_userdata($user_id);
    }

    if (in_array('restaurant_admin', (array) $user->roles, true) || in_array('staff', (array) $user->roles, true)) {
        menuqr_repair_user_restaurant_context($user_id);
    }
}


function menuqr_get_user_restaurant_candidate_id(int $user_id): int {
    global $wpdb;
    if ($user_id <= 0) {
        return 0;
    }

    $meta_restaurant_id = (int) get_user_meta($user_id, 'menuqr_restaurant_id', true);
    if ($meta_restaurant_id > 0 && menuqr_get_restaurant($meta_restaurant_id)) {
        return $meta_restaurant_id;
    }

    $restaurants_table = menuqr_table('restaurants');
    $users_table = $wpdb->users;
    $user = get_userdata($user_id);
    if (!$user) {
        return 0;
    }

    $restaurant_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$restaurants_table} WHERE wp_user_id = %d ORDER BY id ASC LIMIT 1",
        $user_id
    ));

    if ($restaurant_id <= 0) {
        $restaurant_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$restaurants_table} WHERE email = %s ORDER BY id ASC LIMIT 1",
            $user->user_email
        ));
    }

    if ($restaurant_id <= 0) {
        $staff_table = menuqr_table('staff');
        $restaurant_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT restaurant_id FROM {$staff_table} WHERE wp_user_id = %d ORDER BY id ASC LIMIT 1",
            $user_id
        ));

        if ($restaurant_id <= 0) {
            $restaurant_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT restaurant_id FROM {$staff_table} WHERE email = %s ORDER BY id ASC LIMIT 1",
                $user->user_email
            ));
        }
    }

    return $restaurant_id > 0 ? $restaurant_id : 0;
}

function menuqr_create_missing_restaurant_for_user(int $user_id): int {
    global $wpdb;

    $user = get_userdata($user_id);
    if (!$user) {
        return 0;
    }

    if (!in_array('restaurant_admin', (array) $user->roles, true)) {
        return 0;
    }

    $restaurants_table = menuqr_table('restaurants');
    $now = current_time('mysql');
    $name = trim((string) get_user_meta($user_id, 'menuqr_restaurant_name', true));
    if ($name === '') {
        $name = trim((string) $user->display_name);
    }
    if ($name === '') {
        $name = preg_replace('/@.*/', '', (string) $user->user_email);
    }
    if ($name === '') {
        $name = 'My Restaurant';
    }

    $slug = sanitize_title($name);
    if ($slug === '') {
        $slug = 'restaurant-' . $user_id;
    }

    $wpdb->insert($restaurants_table, [
        'wp_user_id'          => $user_id,
        'name'                => $name,
        'slug'                => $slug . '-' . wp_generate_password(4, false, false),
        'owner_name'          => $user->display_name ?: $user->user_email,
        'email'               => $user->user_email,
        'phone'               => '',
        'address'             => '',
        'approval_status'     => 'pending',
        'status'              => 'active',
        'subscription_status' => 'inactive',
        'created_at'          => $now,
        'updated_at'          => $now,
    ]);

    return (int) $wpdb->insert_id;
}

function menuqr_repair_user_restaurant_context(int $user_id): int {
    global $wpdb;

    if ($user_id <= 0) {
        return 0;
    }

    $restaurant_id = menuqr_get_user_restaurant_candidate_id($user_id);
    if ($restaurant_id <= 0) {
        $restaurant_id = menuqr_create_missing_restaurant_for_user($user_id);
    }

    if ($restaurant_id <= 0) {
        return 0;
    }

    update_user_meta($user_id, 'menuqr_restaurant_id', $restaurant_id);

    $restaurants_table = menuqr_table('restaurants');
    $wpdb->update(
        $restaurants_table,
        ['wp_user_id' => $user_id, 'updated_at' => current_time('mysql')],
        ['id' => $restaurant_id],
        ['%d', '%s'],
        ['%d']
    );

    if (menuqr_user_has_role('staff') || in_array('staff', (array) get_userdata($user_id)->roles, true)) {
        $staff_table = menuqr_table('staff');
        $wpdb->query($wpdb->prepare(
            "UPDATE {$staff_table} SET wp_user_id = %d, restaurant_id = %d, updated_at = %s WHERE wp_user_id = %d OR email = %s",
            $user_id,
            $restaurant_id,
            current_time('mysql'),
            $user_id,
            get_userdata($user_id)->user_email
        ));
    }

    return $restaurant_id;
}

function menuqr_run_self_repair(): void {
    if (!is_user_logged_in()) {
        return;
    }

    $user = wp_get_current_user();
    if (!$user || empty($user->ID)) {
        return;
    }

    $roles = (array) $user->roles;
    if (!array_intersect($roles, ['restaurant_admin', 'staff'])) {
        return;
    }

    $meta_restaurant_id = (int) get_user_meta($user->ID, 'menuqr_restaurant_id', true);
    if ($meta_restaurant_id > 0 && menuqr_get_restaurant($meta_restaurant_id)) {
        return;
    }

    menuqr_repair_user_restaurant_context((int) $user->ID);
}



function menuqr_get_staff_restaurant_id_by_user(int $user_id): int {
    global $wpdb;

    if ($user_id <= 0) {
        return 0;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return 0;
    }

    $staff_table = menuqr_table('staff');
    $restaurant_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT restaurant_id FROM {$staff_table} WHERE wp_user_id = %d ORDER BY id DESC LIMIT 1",
        $user_id
    ));

    if ($restaurant_id > 0) {
        return $restaurant_id;
    }

    if (!empty($user->user_email)) {
        $restaurant_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT restaurant_id FROM {$staff_table} WHERE email = %s ORDER BY id DESC LIMIT 1",
            (string) $user->user_email
        ));
    }

    return $restaurant_id > 0 ? $restaurant_id : 0;
}




function menuqr_get_user_accessible_restaurant_ids(int $user_id = 0): array {
    global $wpdb;

    $user_id = $user_id > 0 ? $user_id : get_current_user_id();
    if ($user_id <= 0) {
        return [];
    }

    $ids = [];
    $candidate_id = menuqr_get_user_restaurant_candidate_id($user_id);
    if ($candidate_id > 0) {
        $ids[] = $candidate_id;
    }

    $meta_id = (int) get_user_meta($user_id, 'menuqr_restaurant_id', true);
    if ($meta_id > 0) {
        $ids[] = $meta_id;
    }

    $user = get_userdata($user_id);
    if ($user) {
        $restaurants_table = menuqr_table('restaurants');
        $staff_table = menuqr_table('staff');

        $restaurant_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$restaurants_table} WHERE wp_user_id = %d OR email = %s ORDER BY id ASC",
            $user_id,
            (string) $user->user_email
        ));
        if (is_array($restaurant_ids)) {
            foreach ($restaurant_ids as $restaurant_id) {
                $ids[] = (int) $restaurant_id;
            }
        }

        $staff_restaurant_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT restaurant_id FROM {$staff_table} WHERE wp_user_id = %d OR email = %s ORDER BY id ASC",
            $user_id,
            (string) $user->user_email
        ));
        if (is_array($staff_restaurant_ids)) {
            foreach ($staff_restaurant_ids as $restaurant_id) {
                $ids[] = (int) $restaurant_id;
            }
        }
    }

    $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));
    return $ids;
}

function menuqr_get_active_kitchen_orders_by_restaurants(array $restaurant_ids): array {
    global $wpdb;

    $restaurant_ids = array_values(array_filter(array_map('absint', $restaurant_ids)));
    if (empty($restaurant_ids)) {
        return [];
    }

    $orders_table = menuqr_table('orders');
    $tables_table = menuqr_table('tables');
    $rooms_table = menuqr_table('rooms');
    $placeholders = implode(',', array_fill(0, count($restaurant_ids), '%d'));

    $sql = $wpdb->prepare(
        "SELECT o.*,
                COALESCE(NULLIF(NULLIF(o.table_number, ''), '0'), t.table_number) AS table_number,
                COALESCE(NULLIF(NULLIF(o.room_number, ''), '0'), r.room_number) AS room_number,
                CASE
                    WHEN o.room_id > 0 OR COALESCE(NULLIF(NULLIF(o.room_number, ''), '0'), r.room_number) IS NOT NULL THEN 'room_qr'
                    WHEN o.table_id > 0 OR COALESCE(NULLIF(NULLIF(o.table_number, ''), '0'), t.table_number) IS NOT NULL THEN 'table_qr'
                    WHEN COALESCE(NULLIF(o.order_source, ''), '') <> '' THEN o.order_source
                    ELSE 'table_qr'
                END AS order_source,
                TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) AS age_minutes
         FROM {$orders_table} o
         LEFT JOIN {$tables_table} t ON t.id = o.table_id AND t.restaurant_id = o.restaurant_id
         LEFT JOIN {$rooms_table} r ON r.id = o.room_id AND r.restaurant_id = o.restaurant_id
         WHERE o.restaurant_id IN ({$placeholders})
           AND o.order_status IN ('pending','accepted','preparing','ready')
         ORDER BY o.created_at DESC, o.id DESC",
        ...$restaurant_ids
    );

    $rows = $wpdb->get_results($sql);
    if (!is_array($rows)) { return []; }
    return array_map('menuqr_normalize_order_service_point', $rows);
}


function menuqr_clean_service_point_value($value): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '' || $value === '0' || $value === '—' || strtolower($value) === 'null') {
        return '';
    }
    return $value;
}

function menuqr_normalize_order_service_point($order) {
    if (!is_object($order)) {
        return $order;
    }

    $table_number = menuqr_clean_service_point_value($order->table_number ?? '');
    $room_number = menuqr_clean_service_point_value($order->room_number ?? '');
    $table_id = absint($order->table_id ?? 0);
    $room_id = absint($order->room_id ?? 0);
    $source = sanitize_key((string) ($order->order_source ?? ''));

    $is_room = false;
    if ($room_id > 0 || $room_number !== '') {
        $is_room = true;
    } elseif (in_array($source, ['room', 'room_qr', 'hotel_room'], true) && ($table_id <= 0 || $table_number === '')) {
        $is_room = true;
    }

    if ($is_room) {
        $order->order_source = 'room_qr';
        $order->table_id = 0;
        $order->table_number = '';
        $order->room_number = $room_number !== '' ? $room_number : ($room_id > 0 ? (string) $room_id : '');
        $order->service_label = 'Room No';
        $order->service_value = $order->room_number ?: '—';
    } else {
        $order->order_source = 'table_qr';
        $order->room_id = 0;
        $order->room_number = '';
        $order->table_number = $table_number !== '' ? $table_number : ($table_id > 0 ? (string) $table_id : '');
        $order->service_label = 'Table No';
        $order->service_value = $order->table_number ?: '—';
    }

    return $order;
}


function menuqr_get_current_restaurant_id(): int {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return 0;
    }

    if ((menuqr_user_has_role('super_admin') || current_user_can('manage_options')) && isset($_REQUEST['restaurant_id'])) {
        return absint(wp_unslash($_REQUEST['restaurant_id']));
    }

    menuqr_sync_user_role_context($user_id);

    $restaurant_id = (int) get_user_meta($user_id, 'menuqr_restaurant_id', true);
    if ($restaurant_id > 0 && menuqr_get_restaurant($restaurant_id)) {
        return $restaurant_id;
    }

    $restaurant_id = menuqr_repair_user_restaurant_context($user_id);
    if ($restaurant_id > 0) {
        return $restaurant_id;
    }

    $restaurant_id = menuqr_get_staff_restaurant_id_by_user($user_id);
    if ($restaurant_id > 0) {
        update_user_meta($user_id, 'menuqr_restaurant_id', $restaurant_id);
        return $restaurant_id;
    }

    return 0;
}


function menuqr_user_has_role(string $role): bool {
    $user = wp_get_current_user();
    if (!$user || empty($user->ID)) {
        return false;
    }

    $roles = (array) $user->roles;
    if ('super_admin' === $role) {
        return in_array('super_admin', $roles, true) || current_user_can('manage_options') || in_array('administrator', $roles, true);
    }

    if ('staff' === $role) {
        return in_array('staff', $roles, true) || menuqr_user_matches_staff_record($user);
    }

    return in_array($role, $roles, true);
}

function menuqr_current_user_has_panel_role(string $role): bool {
    if (!is_user_logged_in()) {
        return false;
    }

    $user = wp_get_current_user();
    if (!$user || empty($user->ID)) {
        return false;
    }

    if ('super_admin' === $role) {
        return current_user_can('manage_options') || in_array('super_admin', (array) $user->roles, true) || in_array('administrator', (array) $user->roles, true);
    }

    if ('staff' === $role) {
        return in_array('staff', (array) $user->roles, true) || menuqr_user_matches_staff_record($user);
    }

    if ('restaurant_admin' === $role) {
        return in_array('restaurant_admin', (array) $user->roles, true);
    }

    return in_array($role, (array) $user->roles, true);
}

function menuqr_require_role(array $roles): void {
    if (!is_user_logged_in()) {
        wp_safe_redirect(menuqr_get_page_url_by_slug('login'));
        exit;
    }

    menuqr_sync_user_role_context(get_current_user_id());

    foreach ($roles as $role) {
        if (menuqr_current_user_has_panel_role($role)) {
            return;
        }
    }

    wp_safe_redirect(menuqr_get_dashboard_url());
    exit;
}

function menuqr_get_restaurant(int $restaurant_id): ?object {
    global $wpdb;
    $table = menuqr_table('restaurants');
    $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $restaurant_id);
    return $wpdb->get_row($sql);
}

function menuqr_restaurant_is_active(int $restaurant_id): bool {
    $restaurant = menuqr_get_restaurant($restaurant_id);
    if (!$restaurant) {
        return false;
    }
    if ('approved' !== $restaurant->approval_status && 'demo' !== $restaurant->approval_status) {
        return false;
    }
    return menuqr_subscription_is_active($restaurant_id);
}

function menuqr_build_order_code(int $order_id): string {
    return 'MQR-' . str_pad((string) $order_id, 6, '0', STR_PAD_LEFT);
}

function menuqr_get_dashboard_url(): string {
    if (menuqr_current_user_has_panel_role('super_admin')) {
        return menuqr_get_page_url_by_slug('super-admin-dashboard');
    }
    if (menuqr_current_user_has_panel_role('staff')) {
        return menuqr_get_page_url_by_slug('kitchen-dashboard');
    }
    return menuqr_get_page_url_by_slug('restaurant-dashboard');
}

function menuqr_sanitize_order_items($items): array {
    $clean = [];
    if (!is_array($items)) {
        return $clean;
    }
    foreach ($items as $item) {
        $clean[] = [
            'item_id'   => isset($item['item_id']) ? absint($item['item_id']) : 0,
            'name'      => isset($item['name']) ? sanitize_text_field((string) $item['name']) : '',
            'price'     => isset($item['price']) ? (float) $item['price'] : 0,
            'qty'       => isset($item['qty']) ? max(1, absint($item['qty'])) : 1,
            'variants'  => isset($item['variants']) ? array_map('sanitize_text_field', (array) $item['variants']) : [],
            'addons'    => isset($item['addons']) ? array_map('sanitize_text_field', (array) $item['addons']) : [],
            'image'     => isset($item['image']) ? sanitize_text_field((string) $item['image']) : '',
            'emoji'     => isset($item['emoji']) ? sanitize_text_field((string) $item['emoji']) : '',
        ];
    }
    return $clean;
}

function menuqr_get_categories(int $restaurant_id): array {
    global $wpdb;
    $table = menuqr_table('categories');
    $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d ORDER BY sort_order ASC, id ASC", $restaurant_id);
    return (array) $wpdb->get_results($sql);
}

function menuqr_get_items(int $restaurant_id, int $category_id = 0): array {
    global $wpdb;
    $table = menuqr_table('items');
    if ($category_id > 0) {
        $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d AND category_id = %d ORDER BY name ASC", $restaurant_id, $category_id);
    } else {
        $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d ORDER BY category_id ASC, name ASC", $restaurant_id);
    }
    return (array) $wpdb->get_results($sql);
}

function menuqr_get_tables(int $restaurant_id): array {
    global $wpdb;
    $table = menuqr_table('tables');
    $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d ORDER BY CAST(table_number AS UNSIGNED) ASC, table_number ASC", $restaurant_id);
    return (array) $wpdb->get_results($sql);
}


function menuqr_platform_settings(): array {
    $defaults = [
        'platform_name' => get_bloginfo('name') ?: 'FluuexQR',
        'currency_symbol' => '₹',
        'default_tax_rate' => '5',
        'default_service_charge_rate' => '0',
        'support_email' => get_option('admin_email'),
        'support_phone' => '',
        'razorpay_enabled' => 0,
        'stripe_enabled' => 0,
        'allow_restaurant_signup' => 1,
    ];
    $saved = get_option('menuqr_platform_settings', []);
    if (!is_array($saved)) {
        $saved = [];
    }
    return wp_parse_args($saved, $defaults);
}

function menuqr_is_super_admin_user(): bool {
    return menuqr_user_has_role('super_admin');
}

function menuqr_admin_tab_url(string $tab): string {
    return add_query_arg('tab', sanitize_key($tab), menuqr_get_page_url_by_slug('super-admin-dashboard'));
}

function menuqr_restaurant_tab_url(string $tab): string {
    return add_query_arg('tab', sanitize_key($tab), menuqr_get_page_url_by_slug('restaurant-dashboard'));
}


function menuqr_get_restaurant_orders(int $restaurant_id, int $limit = 100): array {
    global $wpdb;

    if ($restaurant_id <= 0) {
        return [];
    }

    $orders_table = menuqr_table('orders');
    $tables_table = menuqr_table('tables');

    $sql = $wpdb->prepare(
        "SELECT o.*, t.table_number
         FROM {$orders_table} o
         LEFT JOIN {$tables_table} t ON t.id = o.table_id
         WHERE o.restaurant_id = %d
         ORDER BY o.created_at DESC, o.id DESC
         LIMIT %d",
        $restaurant_id,
        max(1, $limit)
    );

    $rows = $wpdb->get_results($sql);
    return is_array($rows) ? $rows : [];
}

function menuqr_get_active_kitchen_orders(int $restaurant_id): array {
    global $wpdb;

    if ($restaurant_id <= 0) {
        return [];
    }

    $orders_table = menuqr_table('orders');
    $tables_table = menuqr_table('tables');
    $rooms_table = menuqr_table('rooms');
    $sql = $wpdb->prepare(
        "SELECT o.*,
                COALESCE(NULLIF(NULLIF(o.table_number, ''), '0'), t.table_number) AS table_number,
                COALESCE(NULLIF(NULLIF(o.room_number, ''), '0'), r.room_number) AS room_number,
                CASE
                    WHEN o.room_id > 0 OR COALESCE(NULLIF(NULLIF(o.room_number, ''), '0'), r.room_number) IS NOT NULL THEN 'room_qr'
                    WHEN o.table_id > 0 OR COALESCE(NULLIF(NULLIF(o.table_number, ''), '0'), t.table_number) IS NOT NULL THEN 'table_qr'
                    WHEN COALESCE(NULLIF(o.order_source, ''), '') <> '' THEN o.order_source
                    ELSE 'table_qr'
                END AS order_source,
                TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) AS age_minutes
         FROM {$orders_table} o
         LEFT JOIN {$tables_table} t ON t.id = o.table_id AND t.restaurant_id = o.restaurant_id
         LEFT JOIN {$rooms_table} r ON r.id = o.room_id AND r.restaurant_id = o.restaurant_id
         WHERE o.restaurant_id = %d
           AND o.order_status IN ('pending','accepted','preparing','ready')
         ORDER BY o.created_at DESC, o.id DESC",
        $restaurant_id
    );

    $rows = $wpdb->get_results($sql);
    if (!is_array($rows)) { return []; }
    return array_map('menuqr_normalize_order_service_point', $rows);
}

function menuqr_get_restaurant_staff(int $restaurant_id): array {
    global $wpdb;

    if ($restaurant_id <= 0) {
        return [];
    }

    $staff_table = menuqr_table('staff');
    $sql = $wpdb->prepare(
        "SELECT * FROM {$staff_table} WHERE restaurant_id = %d ORDER BY id DESC",
        $restaurant_id
    );

    $rows = $wpdb->get_results($sql);
    return is_array($rows) ? $rows : [];
}



function menuqr_staff_roles(): array {
    return [
        'manager' => 'Restaurant Manager',
        'cashier' => 'Cashier',
        'chef' => 'Chef',
        'kitchen' => 'Kitchen Staff',
        'waiter' => 'Waiter',
        'steward' => 'Steward',
        'room_service' => 'Room Service',
        'delivery' => 'Delivery Staff',
        'front_office' => 'Front Office',
        'housekeeping' => 'Housekeeping',
        'support' => 'Cleaner / Support',
    ];
}


function menuqr_staff_permissions_catalog(): array {
    return [
        'view_orders' => 'View orders',
        'update_order_status' => 'Update order status',
        'print_bill' => 'Print bill',
        'send_whatsapp_bill' => 'Send WhatsApp bill',
        'manage_menu' => 'Manage menu',
        'manage_tables' => 'Manage tables',
        'manage_payments' => 'Manage payments',
        'view_reports' => 'View reports',
        'manage_reviews' => 'Manage reviews',
    ];
}


function menuqr_default_permissions_for_role(string $role_name): array {
    $role_name = function_exists('fqx_v167_staff_role_alias') ? fqx_v167_staff_role_alias($role_name) : sanitize_key($role_name);
    $map = [
        'kitchen' => ['view_orders', 'update_order_status'],
        'chef' => ['view_orders', 'update_order_status'],
        'waiter' => ['view_orders', 'update_order_status'],
        'steward' => ['view_orders', 'update_order_status'],
        'room_service' => ['view_orders', 'update_order_status'],
        'delivery' => ['view_orders', 'update_order_status'],
        'front_office' => ['view_orders', 'print_bill', 'send_whatsapp_bill'],
        'manager' => ['view_orders', 'update_order_status', 'print_bill', 'send_whatsapp_bill', 'manage_menu', 'manage_tables', 'manage_payments', 'view_reports', 'manage_reviews'],
        'cashier' => ['view_orders', 'print_bill', 'send_whatsapp_bill', 'manage_payments'],
        'housekeeping' => ['view_orders'],
        'support' => [],
    ];
    return $map[$role_name] ?? [];
}


function menuqr_staff_permissions_for_member($member): array {
    $saved = json_decode((string) ($member->permissions_json ?? ''), true);
    if (is_array($saved) && $saved) {
        return array_values(array_map('sanitize_key', $saved));
    }
    return menuqr_default_permissions_for_role((string) ($member->role_name ?? 'kitchen'));
}

function menuqr_bill_format_options(): array {
    return [
        '58mm' => '58mm Thermal',
        '80mm' => '80mm Thermal',
        'a4' => 'A4 Invoice',
    ];
}


function menuqr_get_rooms(int $restaurant_id): array {
    global $wpdb;
    $table = menuqr_table('rooms');
    $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d ORDER BY CAST(room_number AS UNSIGNED) ASC, room_number ASC", $restaurant_id);
    return (array) $wpdb->get_results($sql);
}

function menuqr_get_room_display_name(int $restaurant_id, int $room_id, string $fallback = ''): string {
    if ($restaurant_id <= 0 || $room_id <= 0) {
        return $fallback ?: '—';
    }

    foreach (menuqr_get_rooms($restaurant_id) as $room) {
        if ((int) $room->id === $room_id) {
            $value = trim((string) ($room->room_number ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
    }

    return $fallback ?: '—';
}

function menuqr_get_service_point_context(int $restaurant_id, int $table_id = 0, int $room_id = 0, string $table_reference = '', string $room_reference = ''): array {
    $context = [
        'restaurant_id' => $restaurant_id,
        'table_id' => 0,
        'room_id' => 0,
        'table_number' => '',
        'room_number' => '',
        'order_source' => '',
        'label' => '',
        'label_short' => '',
    ];

    if ($restaurant_id <= 0) {
        return $context;
    }

    $room_lookup = trim($room_reference) !== '' ? $room_reference : (string) $room_id;
    $table_lookup = trim($table_reference) !== '' ? $table_reference : (string) $table_id;

    if ($room_id > 0 || trim($room_reference) !== '') {
        $room = menuqr_find_room_by_reference($restaurant_id, $room_lookup, trim($room_reference) !== '' ? 'number' : 'auto');
        if ($room) {
            $context['room_id'] = (int) ($room->id ?? 0);
            $context['room_number'] = trim((string) ($room->room_number ?? ''));
        } else {
            $context['room_id'] = absint($room_id ?: preg_replace('/\D+/', '', $room_lookup));
            $context['room_number'] = trim($room_reference) !== '' ? trim($room_reference) : (string) $context['room_id'];
        }
        $context['table_id'] = 0;
        $context['table_number'] = '';
        $context['order_source'] = 'room_qr';
        $context['label_short'] = $context['room_number'] !== '' ? $context['room_number'] : (string) $context['room_id'];
        $context['label'] = 'Room ' . $context['label_short'];
        return $context;
    }

    if ($table_id > 0 || trim($table_reference) !== '') {
        $table = menuqr_find_table_by_reference($restaurant_id, $table_lookup, trim($table_reference) !== '' ? 'number' : 'auto');
        if ($table) {
            $context['table_id'] = (int) ($table->id ?? 0);
            $context['table_number'] = trim((string) ($table->table_number ?? ''));
        } else {
            $context['table_id'] = absint($table_id ?: preg_replace('/\D+/', '', $table_lookup));
            $context['table_number'] = trim($table_reference) !== '' ? trim($table_reference) : (string) $context['table_id'];
        }
        $context['room_id'] = 0;
        $context['room_number'] = '';
        $context['order_source'] = 'table_qr';
        $context['label_short'] = $context['table_number'] !== '' ? $context['table_number'] : (string) $context['table_id'];
        $context['label'] = 'Table ' . $context['label_short'];
    }

    return $context;
}
