<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_require_post_nonce(string $field, string $action): void {
    $nonce = sanitize_text_field(wp_unslash($_POST[$field] ?? ''));
    if (!$nonce || !wp_verify_nonce($nonce, $action)) {
        wp_die(esc_html__('Invalid request.', 'menuqr'));
    }
}


function menuqr_action_redirect_fallback(): string {
    $action = sanitize_key(wp_unslash($_POST['action'] ?? ''));
    $restaurant_actions = [
        'menuqr_save_category' => 'menu',
        'menuqr_delete_category' => 'menu',
        'menuqr_save_item' => 'menu',
        'menuqr_delete_item' => 'menu',
        'menuqr_save_table' => 'tables',
        'menuqr_save_room' => 'rooms',
        'menuqr_delete_table' => 'tables',
        'menuqr_delete_room' => 'rooms',
        'menuqr_save_staff' => 'staff',
        'menuqr_delete_staff' => 'staff',
        'menuqr_save_payment_form' => 'payments',
        'menuqr_save_qr_template' => 'tables',
        'menuqr_save_bill_settings_form' => 'settings',
        'menuqr_update_order_status_form' => 'orders',
        'menuqr_mark_bill_payment' => 'bills',
        'menuqr_close_bill_session' => 'bills',
        'menuqr_save_reviews_form' => 'reviews',
        'menuqr_delete_coupon' => 'coupons',
        'menuqr_save_coupon' => 'coupons',
        'menuqr_request_subscription_payment' => 'subscription',
        'menuqr_delete_combo' => 'combos',
        'menuqr_save_combo' => 'combos',
    ];

    if (isset($restaurant_actions[$action])) {
        return menuqr_restaurant_tab_url($restaurant_actions[$action]);
    }

    $admin_actions = [
        'menuqr_restaurant_approval' => 'restaurants',
        'menuqr_save_plan' => 'plans',
        'menuqr_verify_subscription_payment' => 'payments',
        'menuqr_save_restaurant_admin' => 'restaurants',
        'menuqr_update_restaurant_subscription' => 'restaurants',
        'menuqr_save_platform_settings' => 'settings',
    ];

    if (isset($admin_actions[$action])) {
        return menuqr_admin_tab_url($admin_actions[$action]);
    }

    return home_url('/');
}

function menuqr_clean_redirect_url(string $url): string {
    return remove_query_arg([
        'edit_item',
        'edit_category',
        'edit_table',
        'edit_staff',
        'edit_combo',
        'edit_coupon',
        'mq_notice',
        'mq_error',
        'mq_ts',
    ], $url);
}

function menuqr_get_safe_post_redirect(string $fallback = ''): string {
    $posted = esc_url_raw(wp_unslash($_POST['_menuqr_redirect'] ?? $_POST['redirect_to'] ?? ''));
    if ($posted) {
        $validated = wp_validate_redirect($posted, '');
        if ($validated) {
            return menuqr_clean_redirect_url($validated);
        }
    }

    $referer = wp_get_referer();
    if ($referer) {
        return menuqr_clean_redirect_url($referer);
    }

    return $fallback ?: menuqr_action_redirect_fallback();
}

function menuqr_redirect_back_with_status(array $args = [], string $fallback = ''): void {
    $url = menuqr_get_safe_post_redirect($fallback);

    $args = array_merge($args, [
        'mq_ts' => time(),
    ]);

    nocache_headers();
    wp_safe_redirect(add_query_arg($args, $url));
    exit;
}

function menuqr_redirect_back(string $fallback = ''): void {
    $url = menuqr_get_safe_post_redirect($fallback);
    nocache_headers();
    wp_safe_redirect($url);
    exit;
}

function menuqr_validate_restaurant_access(int $restaurant_id): void {
    if (menuqr_user_has_role('super_admin') || current_user_can('manage_options')) {
        return;
    }
    if ($restaurant_id !== menuqr_get_current_restaurant_id()) {
        wp_die(esc_html__('Restaurant access denied.', 'menuqr'));
    }
}

function menuqr_handle_front_dashboard_posts(): void {
    if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) {
        return;
    }

    if (is_admin() && !wp_doing_ajax()) {
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['action'] ?? ''));
    $front_actions = [
        'menuqr_save_category',
        'menuqr_delete_category',
        'menuqr_save_item',
        'menuqr_delete_item',
        'menuqr_save_table',
        'menuqr_delete_table',
        'menuqr_save_staff',
        'menuqr_delete_staff',
        'menuqr_save_payment_form',
        'menuqr_update_order_status_form',
        'menuqr_mark_bill_payment',
        'menuqr_close_bill_session',
        'menuqr_save_reviews_form',
        'menuqr_save_combo',
        'menuqr_delete_combo',
        'menuqr_save_coupon',
        'menuqr_delete_coupon',
        'menuqr_request_subscription_payment',
        'menuqr_save_qr_template',
        'menuqr_save_bill_settings_form',
    ];

    if (!in_array($action, $front_actions, true)) {
        return;
    }

    switch ($action) {
        case 'menuqr_save_category':
            menuqr_handle_save_category();
            break;
        case 'menuqr_delete_category':
            menuqr_handle_delete_category();
            break;
        case 'menuqr_save_item':
            menuqr_handle_save_item();
            break;
        case 'menuqr_delete_item':
            menuqr_handle_delete_item();
            break;
        case 'menuqr_save_table':
            menuqr_handle_save_table();
            break;
        case 'menuqr_delete_table':
            menuqr_handle_delete_table();
            break;
        case 'menuqr_save_staff':
            menuqr_handle_save_staff();
            break;
        case 'menuqr_delete_staff':
            menuqr_handle_delete_staff();
            break;
        case 'menuqr_save_payment_form':
            menuqr_handle_payment_form();
            break;
        case 'menuqr_update_order_status_form':
            menuqr_handle_update_order_status_form();
            break;
        case 'menuqr_mark_bill_payment':
            menuqr_handle_mark_bill_payment();
            break;
        case 'menuqr_close_bill_session':
            menuqr_handle_close_bill_session();
            break;
        case 'menuqr_save_reviews_form':
            menuqr_handle_save_reviews_form();
            break;
        case 'menuqr_save_combo':
            menuqr_handle_save_combo();
            break;
        case 'menuqr_delete_combo':
            menuqr_handle_delete_combo();
            break;
        case 'menuqr_save_coupon':
            menuqr_handle_save_coupon();
            break;
        case 'menuqr_delete_coupon':
            menuqr_handle_delete_coupon();
            break;
        case 'menuqr_request_subscription_payment':
            menuqr_handle_request_subscription_payment();
            break;
        case 'menuqr_save_qr_template':
            menuqr_handle_save_qr_template();
            break;
        case 'menuqr_save_bill_settings_form':
            menuqr_handle_save_bill_settings_form();
            break;
    }
}
add_action('init', 'menuqr_handle_front_dashboard_posts', 5);



function menuqr_handle_request_subscription_payment(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_subscription_nonce', 'menuqr_request_subscription_payment');

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);

    $plan_id = absint($_POST['plan_id'] ?? 0);
    $method = sanitize_key(wp_unslash($_POST['payment_method'] ?? 'upi'));
    $reference = sanitize_text_field(wp_unslash($_POST['transaction_reference'] ?? ''));
    $proof_url = '';
    $auto_renew = !empty($_POST['auto_renew_enabled']) ? 1 : 0;

    if (!empty($_FILES['proof_file']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploaded = wp_handle_upload($_FILES['proof_file'], ['test_form' => false]);
        if (!empty($uploaded['url'])) {
            $proof_url = esc_url_raw($uploaded['url']);
        }
    }

    $payment_id = menuqr_create_subscription_payment_request($restaurant_id, $plan_id, $method, $reference, $proof_url);
    if ($payment_id <= 0) {
        menuqr_redirect_back_with_status(['mq_notice' => 'subscription_error'], menuqr_restaurant_tab_url('subscription'));
    }
    if ($auto_renew && function_exists('fqx_update_subscription_auto_renew')) {
        global $wpdb;
        $subscription_id = (int) $wpdb->get_var($wpdb->prepare("SELECT subscription_id FROM " . menuqr_table('subscription_payments') . " WHERE id=%d", $payment_id));
        fqx_update_subscription_auto_renew($subscription_id, 1, $method);
    }

    $plan = menuqr_get_plan_by_id($plan_id);
    $notice = ($plan && (float) $plan->price <= 0) ? 'subscription_activated' : 'subscription_requested';
    menuqr_redirect_back_with_status(['mq_notice' => $notice], menuqr_restaurant_tab_url('subscription'));
}
add_action('admin_post_menuqr_request_subscription_payment', 'menuqr_handle_request_subscription_payment');



function menuqr_handle_save_qr_template(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_qr_template_nonce', 'menuqr_save_qr_template');

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);

    $template = sanitize_key(wp_unslash($_POST['qr_template'] ?? 'minimal_clean'));
    $saved = function_exists('menuqr_set_restaurant_qr_template') ? menuqr_set_restaurant_qr_template($restaurant_id, $template) : false;

    menuqr_redirect_back_with_status([
        'mq_notice' => $saved ? 'qr_template_saved' : 'plan_limit_qr_template',
    ], menuqr_restaurant_tab_url('tables'));
}
add_action('admin_post_menuqr_save_qr_template', 'menuqr_handle_save_qr_template');


function menuqr_handle_save_category(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_category_nonce', 'menuqr_save_category');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $table = menuqr_table('categories');
    $id = absint($_POST['category_id'] ?? 0);
    if (!menuqr_plan_can_add($restaurant_id, 'categories', $id)) {
        menuqr_redirect_back_with_status(['mq_notice' => 'plan_limit_categories']);
    }
    $now = current_time('mysql');
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    if ('' === trim($name)) {
        menuqr_redirect_back_with_status(['mq_notice' => 'category_error'], add_query_arg(['tab' => 'menu', 'section' => 'categories'], menuqr_get_page_url_by_slug('restaurant-dashboard')));
    }
    $payload = [
        'restaurant_id' => $restaurant_id,
        'name' => $name,
        'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
        'sort_order' => absint($_POST['sort_order'] ?? 0),
        'updated_at' => $now,
    ];
    if ($id > 0) {
        $wpdb->update($table, $payload, ['id' => $id, 'restaurant_id' => $restaurant_id]);
    } else {
        $payload['created_at'] = $now;
        $wpdb->insert($table, $payload);
    }
    menuqr_redirect_back_with_status(['mq_notice' => 'category_saved']);
}
add_action('admin_post_menuqr_save_category', 'menuqr_handle_save_category');

function menuqr_handle_delete_category(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_delete_nonce', 'menuqr_delete_record');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $category_id = absint($_POST['id'] ?? 0);
    $items_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM " . menuqr_table('items') . " WHERE restaurant_id = %d AND category_id = %d",
        $restaurant_id,
        $category_id
    ));
    if ($items_count > 0) {
        menuqr_redirect_back_with_status(['mq_notice' => 'category_has_items']);
    }
    $deleted = false !== $wpdb->delete(menuqr_table('categories'), ['id' => $category_id, 'restaurant_id' => $restaurant_id]);
    menuqr_redirect_back_with_status(['mq_notice' => $deleted ? 'category_deleted' : 'category_error']);
}
add_action('admin_post_menuqr_delete_category', 'menuqr_handle_delete_category');


function menuqr_handle_save_item(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_item_nonce', 'menuqr_save_item');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $table = menuqr_table('items');
    $id = absint($_POST['item_id'] ?? 0);
    if (!menuqr_plan_can_add($restaurant_id, 'items', $id)) {
        menuqr_redirect_back_with_status(['mq_notice' => 'plan_limit_items']);
    }
    $now = current_time('mysql');

    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $category_id = absint($_POST['category_id'] ?? 0);
    $price = isset($_POST['price']) ? (float) wp_unslash($_POST['price']) : 0.0;

    if ($restaurant_id <= 0 || $name === '' || $category_id <= 0 || $price < 0) {
        menuqr_redirect_back_with_status(['mq_notice' => 'item_invalid']);
    }

    $existing = $id > 0 ? $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d AND restaurant_id = %d",
        $id,
        $restaurant_id
    )) : null;

    $payload = [
        'restaurant_id' => $restaurant_id,
        'category_id' => $category_id,
        'name' => $name,
        'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
        'price' => $price,
        'discount_price' => isset($_POST['discount_price']) ? (float) wp_unslash($_POST['discount_price']) : 0.0,
        'food_type' => sanitize_key(wp_unslash($_POST['food_type'] ?? 'veg')),
        'tax_rate' => isset($_POST['tax_rate']) ? (float) wp_unslash($_POST['tax_rate']) : 5.0,
        'service_charge_rate' => isset($_POST['service_charge_rate']) ? (float) wp_unslash($_POST['service_charge_rate']) : 0.0,
        'emoji' => sanitize_text_field(wp_unslash($_POST['emoji'] ?? '🍽️')),
        'image' => $existing->image ?? '',
        'variants' => wp_json_encode(array_values(array_filter(array_map('trim', explode("\n", (string) wp_unslash($_POST['variants'] ?? '')))))),
        'addons' => wp_json_encode(array_values(array_filter(array_map('trim', explode("\n", (string) wp_unslash($_POST['addons'] ?? '')))))),
        'is_available' => !empty($_POST['is_available']) ? 1 : 0,
        'is_featured' => !empty($_POST['is_featured']) ? 1 : 0,
        'updated_at' => $now,
    ];
    if (function_exists('fqx_v191_column_exists') && fqx_v191_column_exists($table, 'category_type_id')) {
        $category_type_id = absint($_POST['category_type_id'] ?? 0);
        if ($category_type_id > 0 && function_exists('fqx_v191_get_category_type_map')) {
            $type_map = fqx_v191_get_category_type_map($restaurant_id);
            if (!isset($type_map[$category_type_id]) || (int) ($type_map[$category_type_id]->category_id ?? 0) !== $category_id) {
                $category_type_id = 0;
            }
        }
        $payload['category_type_id'] = $category_type_id;
    }

    if (!empty($_FILES['item_image']['name']) && !menuqr_plan_allows($restaurant_id, 'item_images')) {
        menuqr_redirect_back_with_status(['mq_notice' => 'plan_locked_images']);
    }

    if (!empty($_FILES['item_image']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = $_FILES['item_image'];
        if (empty($file['error'])) {
            $uploaded = wp_handle_upload($file, ['test_form' => false, 'mimes' => [
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif' => 'image/gif',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
            ]]);
            if (!empty($uploaded['error'])) {
                menuqr_redirect_back_with_status(['mq_notice' => 'item_upload_error', 'mq_error' => rawurlencode((string) $uploaded['error'])]);
            }
            if (!empty($uploaded['url'])) {
                $payload['image'] = esc_url_raw($uploaded['url']);
            }
        }
    } elseif (!empty($_POST['remove_image'])) {
        $payload['image'] = '';
    }

    $result = false;
    if ($id > 0) {
        $result = false !== $wpdb->update($table, $payload, ['id' => $id, 'restaurant_id' => $restaurant_id]);
    } else {
        $payload['created_at'] = $now;
        $result = false !== $wpdb->insert($table, $payload);
    }

    if (!$result) {
        menuqr_redirect_back_with_status(['mq_notice' => 'item_db_error', 'mq_error' => rawurlencode((string) $wpdb->last_error)]);
    }

    menuqr_redirect_back_with_status(['mq_notice' => 'item_saved']);
}
add_action('admin_post_menuqr_save_item', 'menuqr_handle_save_item');

function menuqr_handle_front_dashboard_post(): void {
    if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
        return;
    }

    $front_action = sanitize_key(wp_unslash($_POST['menuqr_front_action'] ?? ''));
    if ('save_item' !== $front_action) {
        return;
    }

    menuqr_handle_save_item();
}
add_action('init', 'menuqr_handle_front_dashboard_post', 20);


function menuqr_handle_delete_item(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_delete_nonce', 'menuqr_delete_record');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $deleted = false !== $wpdb->delete(menuqr_table('items'), ['id' => absint($_POST['id'] ?? 0), 'restaurant_id' => $restaurant_id]);
    menuqr_redirect_back_with_status(['mq_notice' => $deleted ? 'item_deleted' : 'item_db_error']);
}
add_action('admin_post_menuqr_delete_item', 'menuqr_handle_delete_item');


function menuqr_get_next_table_number_for_restaurant(int $restaurant_id): string {
    global $wpdb;
    $existing = (array) $wpdb->get_col($wpdb->prepare(
        "SELECT table_number FROM " . menuqr_table('tables') . " WHERE restaurant_id=%d",
        $restaurant_id
    ));
    $used = [];
    foreach ($existing as $num) {
        $num = trim((string) $num);
        if ($num !== '' && ctype_digit($num)) { $used[(int) $num] = true; }
    }
    $next = 1;
    while (isset($used[$next])) { $next++; }
    return (string) $next;
}

function menuqr_table_number_exists_for_restaurant(int $restaurant_id, string $table_number, int $ignore_id = 0): bool {
    global $wpdb;
    $table_number = trim($table_number);
    if ($table_number === '') { return false; }
    $sql = "SELECT id FROM " . menuqr_table('tables') . " WHERE restaurant_id=%d AND table_number=%s";
    $args = [$restaurant_id, $table_number];
    if ($ignore_id > 0) { $sql .= " AND id<>%d"; $args[] = $ignore_id; }
    $sql .= " LIMIT 1";
    return (int) $wpdb->get_var($wpdb->prepare($sql, $args)) > 0;
}

function menuqr_handle_save_table(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_table_nonce', 'menuqr_save_table');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $table = menuqr_table('tables');
    $id = absint($_POST['table_id'] ?? 0);
    if (!menuqr_plan_can_add($restaurant_id, 'tables', $id)) {
        menuqr_redirect_back_with_status(['mq_notice' => 'plan_limit_tables']);
    }
    $now = current_time('mysql');
    $submitted_table_number = trim(sanitize_text_field(wp_unslash($_POST['table_number'] ?? '')));
    if ($submitted_table_number === '') {
        $submitted_table_number = menuqr_get_next_table_number_for_restaurant($restaurant_id);
    }
    if (menuqr_table_number_exists_for_restaurant($restaurant_id, $submitted_table_number, $id)) {
        menuqr_redirect_back_with_status(['mq_notice' => 'duplicate_table_number']);
    }
    $payload = [
        'restaurant_id' => $restaurant_id,
        'table_number' => $submitted_table_number,
        'capacity' => absint($_POST['capacity'] ?? 2),
        'updated_at' => $now,
    ];
    if ($id > 0) {
        $wpdb->update($table, $payload, ['id' => $id, 'restaurant_id' => $restaurant_id]);
    } else {
        $payload['qr_token'] = menuqr_generate_qr_token();
        $payload['created_at'] = $now;
        $wpdb->insert($table, $payload);
    }
    menuqr_redirect_back_with_status(['mq_notice' => 'table_saved']);
}
add_action('admin_post_menuqr_save_table', 'menuqr_handle_save_table');

function menuqr_handle_delete_table(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_delete_nonce', 'menuqr_delete_record');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $wpdb->delete(menuqr_table('tables'), ['id' => absint($_POST['id'] ?? 0), 'restaurant_id' => $restaurant_id]);
    menuqr_redirect_back_with_status(['mq_notice' => 'table_deleted']);
}
add_action('admin_post_menuqr_delete_table', 'menuqr_handle_delete_table');


function menuqr_handle_save_staff(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_staff_nonce', 'menuqr_save_staff');
    global $wpdb;

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);

    $id = absint($_POST['staff_id'] ?? 0);
    if (!menuqr_plan_can_add($restaurant_id, 'staff', $id)) {
        menuqr_redirect_back_with_status(['mq_notice' => 'plan_limit_staff']);
    }

    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $password = (string) wp_unslash($_POST['password'] ?? '');
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $role_name = sanitize_key(wp_unslash($_POST['role_name'] ?? 'kitchen'));
    $status = sanitize_key(wp_unslash($_POST['status'] ?? 'active'));
    $pin_code = preg_replace('/[^0-9]/', '', (string) wp_unslash($_POST['pin_code'] ?? ''));
    $selected_permissions = array_map('sanitize_key', (array) ($_POST['permissions'] ?? []));
    if (!$selected_permissions) {
        $selected_permissions = menuqr_default_permissions_for_role($role_name);
    }
    $now = current_time('mysql');

    if (!$email || !is_email($email) || $name === '') {
        menuqr_redirect_back_with_status(['mq_notice' => 'staff_invalid']);
    }

    $existing = null;
    if ($id > 0) {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . menuqr_table('staff') . " WHERE id = %d AND restaurant_id = %d",
            $id,
            $restaurant_id
        ));
    }

    $user_id = $existing ? (int) $existing->wp_user_id : 0;
    $existing_user_for_email = email_exists($email);

    if ($user_id > 0) {
        $user_update = [
            'ID' => $user_id,
            'display_name' => $name,
            'nickname' => $name,
        ];

        $current_user_obj = get_userdata($user_id);
        if ($current_user_obj && $current_user_obj->user_email !== $email) {
            if ($existing_user_for_email && (int) $existing_user_for_email !== $user_id) {
                menuqr_redirect_back_with_status(['mq_notice' => 'staff_exists']);
            }
            $user_update['user_email'] = $email;
            $user_update['user_login'] = $email;
        }

        if ($password !== '') {
            $user_update['user_pass'] = $password;
        }

        wp_update_user($user_update);
    } else {
        if ($existing_user_for_email) {
            $user_id = (int) $existing_user_for_email;
        } else {
            $user_id = wp_create_user($email, $password ?: wp_generate_password(12, true, true), $email);
            if (is_wp_error($user_id)) {
                menuqr_redirect_back_with_status(['mq_notice' => 'staff_error', 'mq_error' => rawurlencode($user_id->get_error_message())]);
            }
        }

        wp_update_user([
            'ID' => (int) $user_id,
            'display_name' => $name,
            'nickname' => $name,
        ]);
    }

    update_user_meta((int) $user_id, 'menuqr_restaurant_id', $restaurant_id);
    update_user_meta((int) $user_id, 'menuqr_staff_role_name', $role_name);
    update_user_meta((int) $user_id, 'menuqr_staff_permissions', $selected_permissions);
    update_user_meta((int) $user_id, 'fqx_staff_department', sanitize_text_field(wp_unslash($_POST['department'] ?? '')));
    update_user_meta((int) $user_id, 'fqx_staff_assigned_area', sanitize_text_field(wp_unslash($_POST['assigned_area'] ?? '')));
    update_user_meta((int) $user_id, 'fqx_staff_shift_time', sanitize_text_field(wp_unslash($_POST['shift_time'] ?? '')));
    if ($pin_code !== '') {
        update_user_meta((int) $user_id, 'menuqr_staff_pin', $pin_code);
    }

    $wp_user = new WP_User((int) $user_id);
    $wp_user->set_role('staff');

    $payload = [
        'restaurant_id' => $restaurant_id,
        'wp_user_id' => (int) $user_id,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'role_name' => $role_name,
        'permissions_json' => wp_json_encode(array_values(array_unique($selected_permissions))),
        'pin_code' => $pin_code ?: null,
        'attendance_status' => $status === 'active' ? 'available' : 'offline',
        'last_seen_at' => $now,
        'status' => $status,
        'updated_at' => $now,
    ];

    if ($id > 0 && $existing) {
        $wpdb->update(menuqr_table('staff'), $payload, ['id' => $id, 'restaurant_id' => $restaurant_id]);
    } else {
        $payload['created_at'] = $now;
        $wpdb->insert(menuqr_table('staff'), $payload);
    }

    menuqr_redirect_back_with_status(['mq_notice' => 'staff_saved']);
}
add_action('admin_post_menuqr_save_staff', 'menuqr_handle_save_staff');


function menuqr_handle_delete_staff(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_delete_nonce', 'menuqr_delete_record');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $wpdb->delete(menuqr_table('staff'), ['id' => absint($_POST['id'] ?? 0), 'restaurant_id' => $restaurant_id]);
    menuqr_redirect_back_with_status(['mq_notice' => 'staff_deleted']);
}
add_action('admin_post_menuqr_delete_staff', 'menuqr_handle_delete_staff');

function menuqr_handle_payment_form(): void {
    global $wpdb;
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_payment_nonce', 'menuqr_save_payment_form');
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $gateway_allowed = menuqr_plan_allows($restaurant_id, 'gateway');

    $upi_qr_url = wp_unslash($_POST['upi_qr'] ?? '');
    if (!empty($_FILES['upi_qr_file']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploaded_qr = wp_handle_upload($_FILES['upi_qr_file'], ['test_form' => false]);
        if (!empty($uploaded_qr['url'])) {
            $upi_qr_url = esc_url_raw($uploaded_qr['url']);
        }
    }
    $saved = menuqr_save_payment_settings($restaurant_id, [
        'cash_enabled' => !empty($_POST['cash_enabled']),
        'upi_enabled' => !empty($_POST['upi_enabled']),
        'upi_id' => wp_unslash($_POST['upi_id'] ?? ''),
        'upi_qr' => $upi_qr_url,
        'upi_merchant_name' => wp_unslash($_POST['upi_merchant_name'] ?? ''),
        'payment_instructions' => wp_unslash($_POST['payment_instructions'] ?? ''),
        'manual_verification_required' => !empty($_POST['manual_verification_required']),
        'screenshot_enabled' => $gateway_allowed && !empty($_POST['screenshot_enabled']),
        'online_enabled' => $gateway_allowed && !empty($_POST['online_enabled']),
        'razorpay_key' => $gateway_allowed ? wp_unslash($_POST['razorpay_key'] ?? '') : '',
        'razorpay_secret' => $gateway_allowed ? wp_unslash($_POST['razorpay_secret'] ?? '') : '',
        'razorpay_webhook_secret' => $gateway_allowed ? wp_unslash($_POST['razorpay_webhook_secret'] ?? '') : '',
        'razorpay_mode' => $gateway_allowed ? wp_unslash($_POST['razorpay_mode'] ?? 'test') : 'test',
        'stripe_publishable_key' => $gateway_allowed ? wp_unslash($_POST['stripe_publishable_key'] ?? '') : '',
        'stripe_secret_key' => $gateway_allowed ? wp_unslash($_POST['stripe_secret_key'] ?? '') : '',
        'stripe_webhook_secret' => $gateway_allowed ? wp_unslash($_POST['stripe_webhook_secret'] ?? '') : '',
        'stripe_mode' => $gateway_allowed ? wp_unslash($_POST['stripe_mode'] ?? 'test') : 'test',
        'whatsapp_enabled' => !empty($_POST['whatsapp_enabled']),
        'whatsapp_number' => wp_unslash($_POST['whatsapp_number'] ?? ''),
        'whatsapp_api_token' => wp_unslash($_POST['whatsapp_api_token'] ?? ''),
        'bill_message_template' => wp_unslash($_POST['bill_message_template'] ?? ''),
        'payment_reminder_template' => wp_unslash($_POST['payment_reminder_template'] ?? ''),
        'review_request_template' => wp_unslash($_POST['review_request_template'] ?? ''),
        'auto_send_bill' => !empty($_POST['auto_send_bill']),
        'gateway_provider' => $gateway_allowed ? wp_unslash($_POST['gateway_provider'] ?? 'razorpay') : 'razorpay',
        'phonepe_enabled' => $gateway_allowed && !empty($_POST['phonepe_enabled']),
        'phonepe_client_id' => $gateway_allowed ? wp_unslash($_POST['phonepe_client_id'] ?? '') : '',
        'phonepe_client_secret' => $gateway_allowed ? wp_unslash($_POST['phonepe_client_secret'] ?? '') : '',
        'phonepe_client_version' => $gateway_allowed ? wp_unslash($_POST['phonepe_client_version'] ?? '') : '',
        'phonepe_merchant_id' => $gateway_allowed ? wp_unslash($_POST['phonepe_merchant_id'] ?? '') : '',
        'phonepe_environment' => $gateway_allowed ? wp_unslash($_POST['phonepe_environment'] ?? 'sandbox') : 'sandbox',
    ]);

    if (!$saved) {
        menuqr_redirect_back_with_status([
            'mq_notice' => 'payment_error',
            'mq_error' => rawurlencode((string) $wpdb->last_error),
        ]);
    }

    menuqr_redirect_back_with_status(['mq_notice' => 'payment_saved']);
}
add_action('admin_post_menuqr_save_payment_form', 'menuqr_handle_payment_form');

function menuqr_handle_save_reviews_form(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_reviews_nonce', 'menuqr_save_reviews_form');

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);

    $saved = menuqr_save_review_settings($restaurant_id, $_POST);

    menuqr_redirect_back_with_status([
        'mq_notice' => $saved ? 'reviews_saved' : 'reviews_error',
    ], menuqr_restaurant_tab_url('reviews'));
}
add_action('admin_post_menuqr_save_reviews_form', 'menuqr_handle_save_reviews_form');

function menuqr_handle_update_order_status_form(): void {
    menuqr_require_role(['staff', 'restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_order_nonce', 'menuqr_update_order_status_form');
    global $wpdb;
    $order_id = absint($_POST['order_id'] ?? 0);
    $status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'pending'));
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('orders') . " WHERE id = %d", $order_id));
    if (!$order) {
        wp_die(esc_html__('Order not found.', 'menuqr'));
    }
    if (!menuqr_user_has_role('super_admin')) {
        menuqr_validate_restaurant_access((int) $order->restaurant_id);
    }
    $wpdb->update(menuqr_table('orders'), ['order_status' => $status, 'updated_at' => current_time('mysql')], ['id' => $order_id]);
    menuqr_redirect_back_with_status(['mq_notice' => 'order_updated']);
}
add_action('admin_post_menuqr_update_order_status_form', 'menuqr_handle_update_order_status_form');

function menuqr_handle_restaurant_approval(): void {
    menuqr_require_role(['super_admin']);
    menuqr_require_post_nonce('menuqr_admin_nonce', 'menuqr_admin_action');
    global $wpdb;

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    if ($restaurant_id <= 0) {
        wp_die(esc_html__('Restaurant ID missing.', 'menuqr'));
    }

    $restaurant = menuqr_get_restaurant($restaurant_id);
    if (!$restaurant) {
        wp_die(esc_html__('Restaurant not found.', 'menuqr'));
    }

    $requested_status = sanitize_key(wp_unslash($_POST['approval_status'] ?? 'pending'));
    $status_aliases = [
        'approve' => 'approved',
        'approved' => 'approved',
        'active' => 'approved',
        'demo' => 'demo',
        'pending' => 'pending',
        'reject' => 'rejected',
        'rejected' => 'rejected',
        'declined' => 'rejected',
    ];
    $status = $status_aliases[$requested_status] ?? 'pending';

    $payload = [
        'approval_status' => $status,
        'updated_at' => current_time('mysql'),
    ];

    // When Super Admin approves a restaurant, make sure the account itself is active.
    // Previously approval could save but the restaurant still looked rejected/inactive from access checks.
    if (in_array($status, ['approved', 'demo'], true)) {
        $payload['status'] = 'active';
    } elseif ('rejected' === $status) {
        $payload['status'] = 'suspended';
    }

    $updated = $wpdb->update(menuqr_table('restaurants'), $payload, ['id' => $restaurant_id]);
    if (false === $updated) {
        wp_die(esc_html__('Unable to update restaurant approval. Please try again.', 'menuqr'));
    }

    $restaurant = menuqr_get_restaurant($restaurant_id);
    if ($restaurant) {
        $user_id = (int) ($restaurant->wp_user_id ?? 0);
        if ($user_id > 0) {
            update_user_meta($user_id, 'menuqr_restaurant_id', $restaurant_id);
            update_user_meta($user_id, 'menuqr_restaurant_name', (string) $restaurant->name);
            $wp_user = new WP_User($user_id);
            if (!in_array('restaurant_admin', (array) $wp_user->roles, true)) {
                $wp_user->set_role('restaurant_admin');
            }
        }
        menuqr_sync_restaurant_subscription($restaurant_id, (string) $restaurant->subscription_status);
    }

    if (function_exists('fqx_clear_cache_after_update')) {
        fqx_clear_cache_after_update();
    }

    menuqr_redirect_back_with_status([
        'mq_notice' => 'restaurant_approval_updated',
        'approval_status' => $status,
    ], menuqr_admin_tab_url('restaurants'));
}
add_action('admin_post_menuqr_restaurant_approval', 'menuqr_handle_restaurant_approval');

function menuqr_handle_save_plan(): void {
    menuqr_require_role(['super_admin']);
    menuqr_require_post_nonce('menuqr_plan_nonce', 'menuqr_save_plan');
    global $wpdb;

    $id = absint($_POST['plan_id'] ?? 0);
    $now = current_time('mysql');
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $slug = sanitize_title(wp_unslash($_POST['slug'] ?? $name));
    $monthly = (float) ($_POST['monthly_price'] ?? ($_POST['price'] ?? 0));
    $yearly = (float) ($_POST['yearly_price'] ?? 0);
    $days = absint($_POST['duration_days'] ?? ($_POST['billing_days'] ?? 30));
    $features = [];
    foreach (function_exists('fqx_all_feature_keys') ? fqx_all_feature_keys() : [] as $feature_key) {
        $features[$feature_key] = !empty($_POST['feature_' . $feature_key]);
    }
    if (!$features) {
        $raw = array_values(array_filter(array_map('trim', explode("\n", (string) wp_unslash($_POST['features'] ?? '')))));
        foreach ($raw as $row) { $features[sanitize_key($row)] = true; }
    }
    $limits = [];
    foreach (function_exists('fqx_all_limit_keys') ? fqx_all_limit_keys() : [] as $limit_key) {
        $limits[$limit_key] = (int) ($_POST['limit_' . $limit_key] ?? -1);
    }
    $payload = [
        'name' => $name,
        'slug' => $slug,
        'price' => $monthly,
        'billing_days' => max(1, $days),
        'features' => wp_json_encode(['features' => $features, 'limits' => $limits, 'restaurant_limit' => (int) ($_POST['limit_restaurants'] ?? 1)]),
        'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
        'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? 'active')),
        'monthly_price' => $monthly,
        'yearly_price' => $yearly,
        'setup_fee' => (float) ($_POST['setup_fee'] ?? 0),
        'trial_days' => absint($_POST['trial_days'] ?? 0),
        'duration_days' => max(1, $days),
        'plan_type' => sanitize_key($_POST['plan_type'] ?? 'restaurant'),
        'is_recommended' => !empty($_POST['is_recommended']) ? 1 : 0,
        'sort_order' => absint($_POST['sort_order'] ?? 0),
        'button_text' => sanitize_text_field(wp_unslash($_POST['button_text'] ?? 'Choose Plan')),
        'color' => sanitize_hex_color(wp_unslash($_POST['color'] ?? '#ff7a18')) ?: '#ff7a18',
        'updated_at' => $now,
    ];
    if ($id > 0) {
        $wpdb->update(menuqr_table('subscription_plans'), $payload, ['id' => $id]);
    } else {
        $payload['created_at'] = $now;
        $wpdb->insert(menuqr_table('subscription_plans'), $payload);
        $id = (int) $wpdb->insert_id;
    }

    if ($id > 0 && function_exists('fqx_table')) {
        $fqx_plan_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM " . fqx_table('plans') . " WHERE slug=%s", $slug));
        $fqx_payload = [
            'name'=>$name,'slug'=>$slug,'description'=>$payload['description'],'monthly_price'=>$monthly,'yearly_price'=>$yearly,'setup_fee'=>$payload['setup_fee'],'trial_days'=>$payload['trial_days'],'duration_days'=>$payload['duration_days'],'plan_type'=>$payload['plan_type'],'status'=>$payload['status'],'is_recommended'=>$payload['is_recommended'],'sort_order'=>$payload['sort_order'],'button_text'=>$payload['button_text'],'color'=>$payload['color'],'updated_at'=>$now,
        ];
        if ($fqx_plan_id) { $wpdb->update(fqx_table('plans'), $fqx_payload, ['id'=>$fqx_plan_id]); }
        else { $fqx_payload['created_at']=$now; $wpdb->insert(fqx_table('plans'), $fqx_payload); $fqx_plan_id=(int)$wpdb->insert_id; }
        foreach ($features as $key => $enabled) { $wpdb->replace(fqx_table('plan_features'), ['plan_id'=>$fqx_plan_id,'feature_key'=>$key,'is_enabled'=>$enabled?1:0]); }
        foreach ($limits as $key => $value) { $wpdb->replace(fqx_table('plan_limits'), ['plan_id'=>$fqx_plan_id,'limit_key'=>$key,'limit_value'=>(int)$value]); }
    }
    menuqr_redirect_back(menuqr_admin_tab_url('plans'));
}
add_action('admin_post_menuqr_save_plan', 'menuqr_handle_save_plan');

function menuqr_handle_verify_subscription_payment(): void {
    menuqr_require_role(['super_admin']);
    menuqr_require_post_nonce('menuqr_admin_nonce', 'menuqr_admin_action');
    global $wpdb;

    $payment_id = absint($_POST['payment_id'] ?? 0);
    if ($payment_id <= 0) {
        wp_die(esc_html__('Payment ID missing.', 'menuqr'));
    }

    $raw_status = sanitize_key(wp_unslash($_POST['status'] ?? 'verified'));
    $status_aliases = [
        'approve' => 'verified',
        'approved' => 'verified',
        'verify' => 'verified',
        'verified' => 'verified',
        'paid' => 'verified',
        'success' => 'verified',
        'pending' => 'pending',
        'reject' => 'rejected',
        'rejected' => 'rejected',
        'failed' => 'rejected',
    ];
    $status = $status_aliases[$raw_status] ?? 'pending';
    $admin_note = sanitize_textarea_field(wp_unslash($_POST['admin_note'] ?? ''));

    $payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('subscription_payments') . " WHERE id = %d", $payment_id));
    if (!$payment) {
        wp_die(esc_html__('Payment not found.', 'menuqr'));
    }

    $update_payload = [
        'status' => $status,
        'updated_at' => current_time('mysql'),
    ];
    if ($admin_note !== '') {
        $update_payload['admin_note'] = $admin_note;
    }
    if ('verified' === $status) {
        $update_payload['paid_at'] = current_time('mysql');
    }
    $updated = $wpdb->update(menuqr_table('subscription_payments'), $update_payload, ['id' => $payment_id]);
    if (false === $updated) {
        wp_die(esc_html__('Unable to update subscription payment status.', 'menuqr'));
    }

    if ('verified' === $status) {
        $activated = false;
        if (function_exists('menuqr_activate_subscription_from_payment')) {
            $activated = (bool) menuqr_activate_subscription_from_payment($payment);
        }
        // Fallback: some old payment rows may not have subscription_id but do have restaurant_id + plan_id.
        if (!$activated && !empty($payment->restaurant_id) && !empty($payment->plan_id)) {
            $plan = function_exists('menuqr_get_plan_by_id') ? menuqr_get_plan_by_id((int) $payment->plan_id) : null;
            $days = $plan ? max(1, (int) ($plan->billing_days ?? $plan->duration_days ?? 30)) : 30;
            $sub_status = $plan && sanitize_key((string) ($plan->slug ?? '')) === 'free_trial' ? 'trial' : 'active';
            menuqr_sync_restaurant_subscription((int) $payment->restaurant_id, $sub_status, (int) $payment->plan_id, gmdate('Y-m-d H:i:s', current_time('timestamp') + ($days * DAY_IN_SECONDS)));
            $activated = true;
        }
        $wpdb->update(menuqr_table('restaurants'), ['approval_status' => 'approved', 'status' => 'active', 'updated_at' => current_time('mysql')], ['id' => (int) $payment->restaurant_id]);
    } elseif ('pending' === $status) {
        menuqr_sync_restaurant_subscription((int) $payment->restaurant_id, 'pending', 0, '');
    } else {
        menuqr_sync_restaurant_subscription((int) $payment->restaurant_id, 'expired', 0, '');
    }

    if (function_exists('fqx_clear_cache_after_update')) {
        fqx_clear_cache_after_update();
    }

    menuqr_redirect_back_with_status([
        'mq_notice' => 'subscription_payment_status_updated',
        'payment_status' => $status,
    ], menuqr_admin_tab_url('payments'));
}
add_action('admin_post_menuqr_verify_subscription_payment', 'menuqr_handle_verify_subscription_payment');


function menuqr_handle_save_restaurant_admin(): void {
    menuqr_require_role(['super_admin']);
    menuqr_require_post_nonce('menuqr_restaurant_nonce', 'menuqr_save_restaurant_admin');
    global $wpdb;

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $owner_name = sanitize_text_field(wp_unslash($_POST['owner_name'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $address = sanitize_textarea_field(wp_unslash($_POST['address'] ?? ''));
    $approval_status_raw = sanitize_key(wp_unslash($_POST['approval_status'] ?? 'pending'));
    $approval_aliases = ['approve' => 'approved', 'approved' => 'approved', 'active' => 'approved', 'demo' => 'demo', 'pending' => 'pending', 'reject' => 'rejected', 'rejected' => 'rejected', 'declined' => 'rejected'];
    $approval_status = $approval_aliases[$approval_status_raw] ?? 'pending';
    $status = sanitize_key(wp_unslash($_POST['status'] ?? 'active'));
    if (in_array($approval_status, ['approved', 'demo'], true)) {
        $status = 'active';
    } elseif ('rejected' === $approval_status && !in_array($status, ['active', 'inactive', 'suspended'], true)) {
        $status = 'suspended';
    }
    $subscription_status = sanitize_text_field(wp_unslash($_POST['subscription_status'] ?? 'inactive'));
    $password = (string) wp_unslash($_POST['password'] ?? '');
    $now = current_time('mysql');

    if (!$email || !is_email($email) || $name === '') {
        wp_die(esc_html__('Restaurant name and valid email are required.', 'menuqr'));
    }

    $restaurants_table = menuqr_table('restaurants');
    $user_id = 0;

    if ($restaurant_id > 0) {
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$restaurants_table} WHERE id = %d", $restaurant_id));
        if (!$existing) {
            wp_die(esc_html__('Restaurant not found.', 'menuqr'));
        }
        $user_id = (int) $existing->wp_user_id;
        if (!$user_id) {
            $user_id = email_exists($email) ?: 0;
        }
    }

    if (!$user_id) {
        $user_id = email_exists($email) ?: 0;
    }

    if (!$user_id) {
        $user_id = wp_create_user($email, $password ?: wp_generate_password(12, true, true), $email);
        if (is_wp_error($user_id)) {
            wp_die(esc_html($user_id->get_error_message()));
        }
    } else {
        wp_update_user([
            'ID' => (int) $user_id,
            'user_email' => $email,
            'display_name' => $owner_name ?: $name,
        ]);
        if ($password !== '') {
            wp_set_password($password, (int) $user_id);
        }
    }

    $wp_user = new WP_User((int) $user_id);
    $wp_user->set_role('restaurant_admin');
    update_user_meta((int) $user_id, 'menuqr_restaurant_name', $name);

    $payload = [
        'wp_user_id' => (int) $user_id,
        'name' => $name,
        'slug' => sanitize_title($name) ?: ('restaurant-' . time()),
        'owner_name' => $owner_name ?: $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'approval_status' => $approval_status,
        'status' => $status,
        'subscription_status' => $subscription_status,
        'updated_at' => $now,
    ];

    if ($restaurant_id > 0) {
        $wpdb->update($restaurants_table, $payload, ['id' => $restaurant_id]);
    } else {
        $payload['created_at'] = $now;
        $wpdb->insert($restaurants_table, $payload);
        $restaurant_id = (int) $wpdb->insert_id;
    }

    update_user_meta((int) $user_id, 'menuqr_restaurant_id', $restaurant_id);

    if (!menuqr_get_payment_settings($restaurant_id)) {
        menuqr_save_payment_settings($restaurant_id, [
            'cash_enabled' => 1,
            'upi_enabled' => 1,
            'upi_id' => '',
            'upi_qr' => '',
            'screenshot_enabled' => 1,
            'razorpay_key' => '',
            'razorpay_secret' => '',
            'stripe_publishable_key' => '',
            'stripe_secret_key' => '',
        ]);
    }

    $plan_id = absint($_POST['plan_id'] ?? 0);
    $expires_at = sanitize_text_field(wp_unslash($_POST['expires_at'] ?? ''));
    menuqr_sync_restaurant_subscription($restaurant_id, $subscription_status, $plan_id, $expires_at);

    menuqr_redirect_back(menuqr_admin_tab_url('restaurants'));
}
add_action('admin_post_menuqr_save_restaurant_admin', 'menuqr_handle_save_restaurant_admin');


function menuqr_handle_update_restaurant_subscription(): void {
    menuqr_require_role(['super_admin']);
    menuqr_require_post_nonce('menuqr_admin_nonce', 'menuqr_admin_action');
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    $subscription_status = sanitize_text_field(wp_unslash($_POST['subscription_status'] ?? 'inactive'));
    $plan_id = absint($_POST['plan_id'] ?? 0);
    $expires_at = sanitize_text_field(wp_unslash($_POST['expires_at'] ?? ''));
    menuqr_sync_restaurant_subscription($restaurant_id, $subscription_status, $plan_id, $expires_at);
    menuqr_redirect_back(menuqr_admin_tab_url('restaurants'));
}
add_action('admin_post_menuqr_update_restaurant_subscription', 'menuqr_handle_update_restaurant_subscription');


function menuqr_handle_save_platform_settings(): void {
    menuqr_require_role(['super_admin']);
    menuqr_require_post_nonce('menuqr_settings_nonce', 'menuqr_save_platform_settings');

    $settings = [
        'platform_name' => sanitize_text_field(wp_unslash($_POST['platform_name'] ?? 'FluuexQR')),
        'currency_symbol' => sanitize_text_field(wp_unslash($_POST['currency_symbol'] ?? '₹')),
        'default_tax_rate' => sanitize_text_field(wp_unslash($_POST['default_tax_rate'] ?? '5')),
        'default_service_charge_rate' => sanitize_text_field(wp_unslash($_POST['default_service_charge_rate'] ?? '0')),
        'support_email' => sanitize_email(wp_unslash($_POST['support_email'] ?? get_option('admin_email'))),
        'support_phone' => sanitize_text_field(wp_unslash($_POST['support_phone'] ?? '')),
        'razorpay_enabled' => !empty($_POST['razorpay_enabled']) ? 1 : 0,
        'stripe_enabled' => !empty($_POST['stripe_enabled']) ? 1 : 0,
        'allow_restaurant_signup' => !empty($_POST['allow_restaurant_signup']) ? 1 : 0,
        'meta_title_home' => sanitize_text_field(wp_unslash($_POST['meta_title_home'] ?? 'FluuexQR - QR Menu System for Restaurants')),
        'meta_description_home' => sanitize_textarea_field(wp_unslash($_POST['meta_description_home'] ?? '')),
    ];

    update_option('menuqr_platform_settings', $settings);
    if (function_exists('menuqr_save_platform_payment_settings')) {
        menuqr_save_platform_payment_settings($_POST);
    }
    menuqr_redirect_back(menuqr_admin_tab_url('settings'));
}
add_action('admin_post_menuqr_save_platform_settings', 'menuqr_handle_save_platform_settings');


function menuqr_handle_mark_bill_payment(): void {
    menuqr_require_post_nonce('menuqr_bill_nonce', 'menuqr_bill_action');
    $bill_id = absint($_POST['bill_id'] ?? 0);
    $status = sanitize_key(wp_unslash($_POST['payment_status'] ?? 'paid'));
    $bill = menuqr_get_bill_by_id($bill_id);
    if (!$bill) {
        menuqr_redirect_back_with_status(['mq_notice' => 'bill_error']);
    }

    menuqr_validate_restaurant_access((int) $bill->restaurant_id);
    $ok = menuqr_mark_bill_payment_status($bill_id, $status);
    menuqr_redirect_back_with_status(['mq_notice' => $ok ? 'bill_payment_saved' : 'bill_error'], add_query_arg('tab', 'bills', menuqr_get_page_url_by_slug('restaurant-dashboard')));
}

// v186: admin-post hooks for bill payment buttons.
// The Bills page action forms submit to admin-post.php, so these hooks are required.
// Without them the Mark Paid / Mark Unpaid icon can appear clickable but WordPress will not run the handler.
add_action('admin_post_menuqr_mark_bill_payment', 'menuqr_handle_mark_bill_payment');

function menuqr_handle_close_bill_session(): void {
    menuqr_require_post_nonce('menuqr_bill_nonce', 'menuqr_bill_action');
    global $wpdb;
    $bill_id = absint($_POST['bill_id'] ?? 0);
    $bill = menuqr_get_bill_by_id($bill_id);
    if (!$bill) {
        menuqr_redirect_back_with_status(['mq_notice' => 'bill_error']);
    }

    menuqr_validate_restaurant_access((int) $bill->restaurant_id);
    $now = current_time('mysql');
    $wpdb->update(menuqr_table('bill_sessions'), [
        'status' => 'closed',
        'closed_at' => $now,
        'updated_at' => $now,
    ], ['id' => (int) $bill->bill_session_id]);
    $wpdb->update(menuqr_table('bills'), [
        'bill_status' => 'generated',
        'updated_at' => $now,
    ], ['id' => $bill_id]);

    menuqr_redirect_back_with_status(['mq_notice' => 'bill_closed'], add_query_arg('tab', 'bills', menuqr_get_page_url_by_slug('restaurant-dashboard')));
}


// v186: ensure Close Bill action also works when submitted to admin-post.php.
add_action('admin_post_menuqr_close_bill_session', 'menuqr_handle_close_bill_session');

function menuqr_get_restaurant_combos(int $restaurant_id): array {
    global $wpdb;
    $table = menuqr_table('combos');
    return (array) $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d ORDER BY id DESC", $restaurant_id));
}

function menuqr_get_restaurant_coupons(int $restaurant_id): array {
    global $wpdb;
    $table = menuqr_table('coupons');
    return (array) $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d ORDER BY id DESC", $restaurant_id));
}

function menuqr_handle_save_combo(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_combo_nonce', 'menuqr_save_combo');
    global $wpdb;

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);

    if (!menuqr_plan_allows($restaurant_id, 'combos')) {
        menuqr_redirect_back_with_status(['mq_notice' => 'plan_locked_combos']);
    }

    $id = absint($_POST['combo_id'] ?? 0);
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    if ($name === '') {
        menuqr_redirect_back_with_status(['mq_notice' => 'combo_invalid']);
    }

    $selected_ids = array_map('absint', (array) ($_POST['combo_item_ids'] ?? []));
    $qty_map = (array) ($_POST['combo_item_qty'] ?? []);
    $items_json = [];

    if ($selected_ids) {
        $placeholders = implode(',', array_fill(0, count($selected_ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT id, name, price, image, emoji FROM " . menuqr_table('items') . " WHERE restaurant_id = %d AND id IN ({$placeholders})",
            array_merge([$restaurant_id], $selected_ids)
        );
        $combo_items = (array) $wpdb->get_results($query);
        foreach ($combo_items as $combo_item) {
            $qty = max(1, absint($qty_map[(string) $combo_item->id] ?? 1));
            $items_json[] = [
                'item_id' => (int) $combo_item->id,
                'name' => (string) $combo_item->name,
                'qty' => $qty,
                'price' => (float) $combo_item->price,
                'image' => (string) $combo_item->image,
                'emoji' => (string) ($combo_item->emoji ?: '🍽️'),
            ];
        }
    }

    if (!$items_json) {
        $lines = preg_split('/\r\n|\r|\n/', (string) wp_unslash($_POST['items_text'] ?? ''));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') { continue; }
            $items_json[] = ['name' => sanitize_text_field($line), 'qty' => 1, 'price' => 0];
        }
    }

    $now = current_time('mysql');
    $payload = [
        'restaurant_id' => $restaurant_id,
        'name' => $name,
        'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
        'emoji' => sanitize_text_field(wp_unslash($_POST['emoji'] ?? '🔥')),
        'original_price' => (float) wp_unslash($_POST['original_price'] ?? 0),
        'combo_price' => (float) wp_unslash($_POST['combo_price'] ?? 0),
        'items_json' => wp_json_encode($items_json),
        'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        'updated_at' => $now,
    ];

    if (!empty($_FILES['combo_image']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploaded_combo = wp_handle_upload($_FILES['combo_image'], ['test_form' => false]);
        if (!empty($uploaded_combo['url'])) {
            $payload['image'] = esc_url_raw($uploaded_combo['url']);
        }
    }

    if ($id > 0) {
        $ok = false !== $wpdb->update(menuqr_table('combos'), $payload, ['id' => $id, 'restaurant_id' => $restaurant_id]);
    } else {
        $payload['created_at'] = $now;
        $ok = false !== $wpdb->insert(menuqr_table('combos'), $payload);
    }

    menuqr_redirect_back_with_status(['mq_notice' => $ok ? 'combo_saved' : 'combo_error'], menuqr_restaurant_tab_url('combos'));
}
add_action('admin_post_menuqr_save_combo', 'menuqr_handle_save_combo');

function menuqr_handle_delete_combo(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_delete_nonce', 'menuqr_delete_record');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    if (!menuqr_plan_allows($restaurant_id, 'combos')) {
        menuqr_redirect_back_with_status(['mq_notice' => 'plan_locked_combos']);
    }
    $ok = false !== $wpdb->delete(menuqr_table('combos'), ['id' => absint($_POST['id'] ?? 0), 'restaurant_id' => $restaurant_id]);
    menuqr_redirect_back_with_status(['mq_notice' => $ok ? 'combo_deleted' : 'combo_error'], menuqr_restaurant_tab_url('combos'));
}
add_action('admin_post_menuqr_delete_combo', 'menuqr_handle_delete_combo');

function menuqr_handle_save_coupon(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_coupon_nonce', 'menuqr_save_coupon');
    global $wpdb;

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);

    if (!menuqr_plan_allows($restaurant_id, 'coupons')) {
        menuqr_redirect_back_with_status(['mq_notice' => 'plan_locked_coupons']);
    }

    $id = absint($_POST['coupon_id'] ?? 0);
    $code = strtoupper(sanitize_text_field(wp_unslash($_POST['code'] ?? '')));
    if ($code === '') {
        menuqr_redirect_back_with_status(['mq_notice' => 'coupon_invalid']);
    }

    $now = current_time('mysql');
    $starts_at = sanitize_text_field(wp_unslash($_POST['starts_at'] ?? ''));
    $expires_at = sanitize_text_field(wp_unslash($_POST['expires_at'] ?? ''));
    $payload = [
        'restaurant_id' => $restaurant_id,
        'code' => $code,
        'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
        'discount_type' => sanitize_key(wp_unslash($_POST['discount_type'] ?? 'percentage')),
        'discount_value' => (float) wp_unslash($_POST['discount_value'] ?? 0),
        'min_order' => (float) wp_unslash($_POST['min_order'] ?? 0),
        'usage_limit' => absint($_POST['usage_limit'] ?? 0),
        'starts_at' => $starts_at ? gmdate('Y-m-d H:i:s', strtotime($starts_at)) : null,
        'expires_at' => $expires_at ? gmdate('Y-m-d H:i:s', strtotime($expires_at)) : null,
        'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        'updated_at' => $now,
    ];

    if (!empty($_FILES['combo_image']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $uploaded_combo = wp_handle_upload($_FILES['combo_image'], ['test_form' => false]);
        if (!empty($uploaded_combo['url'])) {
            $payload['image'] = esc_url_raw($uploaded_combo['url']);
        }
    }

    if ($id > 0) {
        $ok = false !== $wpdb->update(menuqr_table('coupons'), $payload, ['id' => $id, 'restaurant_id' => $restaurant_id]);
    } else {
        $payload['created_at'] = $now;
        $ok = false !== $wpdb->insert(menuqr_table('coupons'), $payload);
    }

    menuqr_redirect_back_with_status(['mq_notice' => $ok ? 'coupon_saved' : 'coupon_error'], menuqr_restaurant_tab_url('coupons'));
}
add_action('admin_post_menuqr_save_coupon', 'menuqr_handle_save_coupon');

function menuqr_handle_delete_coupon(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_delete_nonce', 'menuqr_delete_record');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    if (!menuqr_plan_allows($restaurant_id, 'coupons')) {
        menuqr_redirect_back_with_status(['mq_notice' => 'plan_locked_coupons']);
    }
    $ok = false !== $wpdb->delete(menuqr_table('coupons'), ['id' => absint($_POST['id'] ?? 0), 'restaurant_id' => $restaurant_id]);
    menuqr_redirect_back_with_status(['mq_notice' => $ok ? 'coupon_deleted' : 'coupon_error'], menuqr_restaurant_tab_url('coupons'));
}
add_action('admin_post_menuqr_delete_coupon', 'menuqr_handle_delete_coupon');


function menuqr_handle_clear_platform_cache(): void {
    menuqr_require_role(['super_admin']);
    menuqr_require_post_nonce('menuqr_clear_cache', 'menuqr_clear_cache_nonce');
    menuqr_clear_runtime_cache();
    menuqr_redirect_back_with_status(['mq_notice' => 'cache_cleared'], menuqr_admin_tab_url('settings'));
}
add_action('admin_post_menuqr_clear_platform_cache', 'menuqr_handle_clear_platform_cache');


function menuqr_handle_save_bill_settings_form(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_bill_settings_nonce', 'menuqr_save_bill_settings_form');

    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);

    $settings = menuqr_get_restaurant_bill_settings($restaurant_id);

    // 1) First save normal posted values. File uploads are applied AFTER this so
    // hidden URL fields can never overwrite a newly uploaded logo.
    foreach (['restaurant_logo', 'restaurant_cover', 'bill_header_logo', 'tagline', 'address', 'phone', 'email', 'gst_number', 'fssai_number', 'thank_you_text', 'footer_text', 'currency_symbol', 'tax_label', 'bill_watermark_image', 'bill_watermark_text', 'bill_watermark_opacity', 'whatsapp_bill_template', 'bill_brand_color', 'print_paper_size', 'print_density', 'service_charge_value'] as $key) {
        if (isset($_POST[$key])) {
            $settings[$key] = wp_unslash($_POST[$key]);
        }
    }

    // 2) Explicit remove flags from UI buttons.
    if (!empty($_POST['remove_restaurant_logo'])) {
        $settings['restaurant_logo'] = '';
    }
    if (!empty($_POST['remove_bill_header_logo'])) {
        $settings['bill_header_logo'] = '';
    }
    if (!empty($_POST['remove_bill_watermark_image'])) {
        $settings['bill_watermark_image'] = '';
    }

    // 3) Normalize range value from the UI to stable setting values.
    if (isset($settings['print_density'])) {
        $density = sanitize_key((string) $settings['print_density']);
        if ($density === '1') { $settings['print_density'] = 'light'; }
        elseif ($density === '3') { $settings['print_density'] = 'dark'; }
        elseif ($density === '2') { $settings['print_density'] = 'normal'; }
    }

    foreach (['service_charge_enabled', 'packaging_charge_enabled', 'delivery_charge_enabled', 'round_off_enabled', 'show_customer_phone', 'show_staff_name', 'show_payment_method', 'show_payment_status', 'show_bill_history', 'show_powered_by', 'bill_watermark_enabled', 'show_qr_barcode', 'show_table_room_number', 'show_date_time', 'show_tax_breakdown', 'show_gst_number', 'show_thank_you_note', 'show_restaurant_logo', 'show_order_type', 'show_bill_header_logo', 'show_service_charge_on_bill'] as $key) {
        $settings[$key] = !empty($_POST[$key]) ? 1 : 0;
    }

    // 4) Process file uploads last. This fixes the issue where logo preview changed
    // but the saved logo did not update.
    foreach (['restaurant_logo_file' => 'restaurant_logo', 'restaurant_cover_file' => 'restaurant_cover', 'bill_header_logo_file' => 'bill_header_logo', 'bill_watermark_image_file' => 'bill_watermark_image'] as $file_key => $setting_key) {
        if (!empty($_FILES[$file_key]['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload = wp_handle_upload($_FILES[$file_key], ['test_form' => false]);
            if (!empty($upload['url'])) {
                $settings[$setting_key] = esc_url_raw((string) $upload['url']);
            }
        }
    }

    $saved = menuqr_save_restaurant_bill_settings($restaurant_id, $settings);

    global $wpdb;
    $restaurants = menuqr_table('restaurants');
    $restaurant_update = [
        'phone' => sanitize_text_field((string) ($settings['phone'] ?? '')),
        'email' => sanitize_email((string) ($settings['email'] ?? '')),
        'address' => sanitize_textarea_field((string) ($settings['address'] ?? '')),
        'logo' => esc_url_raw((string) ($settings['restaurant_logo'] ?? '')),
        'gst_number' => sanitize_text_field((string) ($settings['gst_number'] ?? '')),
        'fssai_number' => sanitize_text_field((string) ($settings['fssai_number'] ?? '')),
        'updated_at' => current_time('mysql'),
    ];
    $wpdb->update($restaurants, $restaurant_update, ['id' => $restaurant_id]);

    if (function_exists('menuqr_purge_all_caches_after_save')) {
        menuqr_purge_all_caches_after_save('bill_branding_settings');
    }

    menuqr_redirect_back_with_status(['mq_notice' => $saved ? 'bill_settings_saved' : 'bill_settings_error'], menuqr_restaurant_tab_url('settings'));
}


function menuqr_handle_save_room(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_room_nonce', 'menuqr_save_room');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $table = menuqr_table('rooms');
    $id = absint($_POST['room_id'] ?? 0);
    $now = current_time('mysql');

    $payload = [
        'restaurant_id' => $restaurant_id,
        'room_number' => sanitize_text_field(wp_unslash($_POST['room_number'] ?? '')),
        'room_name' => sanitize_text_field(wp_unslash($_POST['room_name'] ?? '')),
        'floor' => sanitize_text_field(wp_unslash($_POST['floor'] ?? '')),
        'wing' => sanitize_text_field(wp_unslash($_POST['wing'] ?? '')),
        'room_type' => sanitize_text_field(wp_unslash($_POST['room_type'] ?? '')),
        'status' => sanitize_key(wp_unslash($_POST['status'] ?? 'active')),
        'notes' => sanitize_textarea_field(wp_unslash($_POST['notes'] ?? '')),
        'updated_at' => $now,
    ];

    // v189: Room QR template selection is centralized on QR Templates page.
    // Do not overwrite an existing room template while saving room details unless an older form explicitly submits it.
    $room_template_cols = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
    if (is_array($room_template_cols) && in_array('room_qr_template', $room_template_cols, true) && isset($_POST['room_qr_template'])) {
        $template_id = sanitize_key(wp_unslash($_POST['room_qr_template']));
        if (function_exists('fqx_v134_normalize_room_template')) {
            $template_id = fqx_v134_normalize_room_template($template_id);
        }
        if ($template_id) { $payload['room_qr_template'] = $template_id; }
    }

    if (empty($payload['room_number'])) {
        menuqr_redirect_back_with_status(['mq_notice' => 'room_invalid']);
    }

    if ($id > 0) {
        $saved = $wpdb->update($table, $payload, ['id' => $id, 'restaurant_id' => $restaurant_id]);
    } else {
        $payload['qr_token'] = menuqr_generate_qr_token();
        $payload['created_at'] = $now;
        $saved = $wpdb->insert($table, $payload);
    }

    menuqr_redirect_back_with_status(['mq_notice' => false === $saved ? 'room_error' : 'room_saved']);
}
add_action('admin_post_menuqr_save_room', 'menuqr_handle_save_room');

function menuqr_handle_delete_room(): void {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('menuqr_delete_nonce', 'menuqr_delete_record');
    global $wpdb;
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $deleted = false !== $wpdb->delete(menuqr_table('rooms'), ['id' => absint($_POST['id'] ?? 0), 'restaurant_id' => $restaurant_id]);
    menuqr_redirect_back_with_status(['mq_notice' => $deleted ? 'room_deleted' : 'room_error']);
}
add_action('admin_post_menuqr_delete_room', 'menuqr_handle_delete_room');
