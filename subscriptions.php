<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v110 subscription engine.
 * Backward compatible with the existing qrmenu_* tables and adds the requested
 * fqx_* SaaS/payment tables without deleting old data.
 */

function fqx_table(string $suffix): string {
    global $wpdb;
    return $wpdb->prefix . 'fqx_' . sanitize_key($suffix);
}

function fqx_all_feature_keys(): array {
    return [
        'qr_menu',
        'restaurant_logo',
        'restaurant_name',
        'table_number_detection',
        'room_number_detection',
        'mobile_responsive_menu',
        'search_food_items',
        'category_filter',
        'food_item_image',
        'food_item_name',
        'food_description',
        'price_display',
        'veg_nonveg_label',
        'available_unavailable_status',
        'add_to_cart',
        'quantity_plus_minus',
        'customer_notes',
        'cart_summary',
        'checkout',
        'order_confirmation',
        'order_tracking',
        'running_bill_view',
        'review_button',
        'bill_button',
        'whatsapp_bill',
        'table_qr',
        'table_wise_qr_code',
        'table_number_auto_detection',
        'table_wise_order',
        'table_wise_running_bill',
        'table_wise_kitchen_order',
        'table_wise_payment_status',
        'qr_download_png',
        'qr_print_ready_design',
        'qr_template_selection',
        'restaurant_logo_on_qr',
        'room_qr',
        'room_wise_qr_code',
        'room_number_auto_detection',
        'room_service_ordering',
        'room_wise_bill',
        'room_wise_kitchen_order',
        'room_wise_order_tracking',
        'room_wise_payment_status',
        'hotel_guest_food_ordering',
        'hotel_guest_service_request',
        'room_qr_download',
        'room_qr_template',
        'cart',
        'customer_checkout',
        'live_cart_count',
        'add_remove_items',
        'quantity_update',
        'item_total',
        'subtotal',
        'discount',
        'tax_gst',
        'grand_total',
        'customer_name',
        'customer_phone',
        'special_note',
        'payment_method_selection',
        'order_place_button',
        'order_success_page',
        'live_orders',
        'order_number',
        'table_number',
        'room_number',
        'item_details',
        'quantity',
        'order_time',
        'order_status',
        'payment_status',
        'accept_order',
        'reject_order',
        'cancel_order',
        'mark_preparing',
        'mark_ready',
        'mark_served',
        'order_history',
        'filter_by_status',
        'filter_by_date',
        'filter_by_table_room',
        'kitchen_display',
        'live_kitchen_dashboard',
        'auto_refresh_orders',
        'new_order_alert',
        'table_number_show',
        'room_number_show',
        'item_name',
        'item_quantity',
        'cooking_notes',
        'order_timer',
        'pending_orders',
        'accepted_orders',
        'preparing_orders',
        'ready_orders',
        'served_orders',
        'status_buttons',
        'kitchen_staff_login',
        'running_bill',
        'final_bill',
        'bill_number',
        'restaurant_logo_on_bill',
        'table_number_on_bill',
        'room_number_on_bill',
        'item_wise_bill',
        'quantity_wise_bill',
        'gst_tax',
        'coupon_discount',
        'payment_method',
        'paid_unpaid_status',
        'transaction_id',
        'paid_date_time',
        'pdf_bill',
        'thermal_bill',
        'thermal_print_bill',
        'download_bill',
        'print_bill',
        'cash_payment',
        'pay_at_counter',
        'upi_payment',
        'upi_id',
        'upi_qr_code',
        'razorpay_payment',
        'stripe_payment',
        'payment_success',
        'payment_failed',
        'payment_retry',
        'manual_upi_verification',
        'paid_status',
        'unpaid_status',
        'pending_verification',
        'refund_marking',
        'transaction_id_save',
        'plan_purchase',
        'plan_renewal',
        'razorpay_subscription_payment',
        'stripe_subscription_payment',
        'upi_manual_payment',
        'bank_transfer',
        'payment_screenshot_upload',
        'utr_number',
        'super_admin_approval',
        'super_admin_rejection',
        'subscription_invoice',
        'auto_renew',
        'auto_renewal',
        'renewal_reminder',
        'payment_history',
        'whatsapp_pdf_bill_link',
        'whatsapp_order_confirmation',
        'whatsapp_order_status_update',
        'whatsapp_payment_reminder',
        'whatsapp_review_link',
        'whatsapp_table_room_bill',
        'whatsapp_restaurant_support',
        'whatsapp_demo_request',
        'whatsapp_renewal_reminder',
        'menu_management',
        'category_management',
        'table_management',
        'room_management',
        'staff_management',
        'reports',
        'coupons',
        'combos',
        'payment_settings',
        'qr_template_generator',
        'custom_qr_templates',
        'staff_login',
        'reviews',
        'review_link',
        'coupon_code',
        'combo_offers',
        'today_orders',
        'today_revenue',
        'monthly_revenue',
        'total_orders',
        'paid_orders',
        'unpaid_orders',
        'top_selling_items',
        'low_selling_items',
        'table_wise_orders',
        'room_wise_orders',
        'payment_method_report',
        'subscription_renewal',
        'manual_payment_upload',
        'online_plan_purchase',
        'upgrade_downgrade',
        'feature_lock',
        'usage_limit',
        'plan_invoice',
        'subscription_payment',
        'expired_account_notice',
        'new_order_notification',
        'payment_success_notification',
        'payment_failed_notification',
        'subscription_expiry_alert',
        'kitchen_alert',
        'whatsapp_notification',
        'email_notification',
        'admin_notice',
        'help_section',
        'support_contact',
        'whatsapp_support',
        'setup_guide',
        'faq',
        'restaurant_data_isolation',
        'role_based_access',
        'super_admin_access',
        'restaurant_admin_access',
        'kitchen_staff_access',
        'customer_no_login_ordering',
        'nonce_security',
        'sanitized_inputs',
        'escaped_outputs',
        'secure_payment_verification',
        'multi_branch',
        'ai_chatbot',
        'delivery_tracking',
        'custom_domain',
        'api_access',
        'item_images',
        'payment_screenshot',
        'gateway',
        'razorpay',
        'phonepe',
        'auto_paid_status',
        'advanced_reports',
        'custom_bill_branding',
        'review_analytics',
        'premium_qr',
        'support_24_7',
        'priority_support',
        'priority_support_badge',
        'bills',
        'staff'
    ];
}

function fqx_all_limit_keys(): array {
    return ['restaurants','branches','tables','rooms','items','categories','staff','monthly_orders','qr_templates','coupons','combos','reviews','storage_images','daily_qr_scans','active_devices','payment_methods'];
}

function menuqr_plan_matrix(): array {
    $all_on = array_fill_keys(fqx_all_feature_keys(), true);
    $restaurant = $all_on;
    foreach (['room_qr','room_management','room_number_detection','room_number','room_number_show','room_wise_qr_code','room_number_auto_detection','room_service_ordering','room_wise_bill','room_wise_kitchen_order','room_wise_order_tracking','room_wise_payment_status','hotel_guest_food_ordering','hotel_guest_service_request','room_qr_download','room_qr_template','room_wise_orders','hotel_guest_order_tracking','hotel_room_combo','multi_branch','custom_domain','api_access','ai_chatbot'] as $off_key) { $restaurant[$off_key] = false; }
    $base_limits = array_fill_keys(fqx_all_limit_keys(), -1);
    return [
        'free_trial' => ['name'=>'Free Trial','price'=>0,'monthly_price'=>0,'yearly_price'=>0,'setup_fee'=>0,'billing_days'=>10,'duration_days'=>10,'trial_days'=>10,'plan_type'=>'trial','restaurant_limit'=>1,'description'=>'10 days full access trial. No payment required. Trial can be used once per restaurant account.','button_text'=>'Start Free Trial','color'=>'#22c55e','is_recommended'=>0,'sort_order'=>1,'features'=>$all_on,'limits'=>$base_limits],
        'starter_5_table' => ['name'=>'Starter 5 Table','price'=>999,'monthly_price'=>999,'yearly_price'=>9990,'setup_fee'=>0,'billing_days'=>30,'duration_days'=>30,'trial_days'=>0,'plan_type'=>'restaurant','restaurant_limit'=>1,'description'=>'For small restaurants, cafes and dhabas starting with digital ordering. Includes 5 tables, 5 categories, 20 menu items, 2 staff users, billing, WhatsApp bill, UPI/Razorpay/Cash and kitchen flow. Room QR is not included.','button_text'=>'Choose Starter Plan','color'=>'#f59e0b','is_recommended'=>0,'sort_order'=>2,'features'=>$restaurant,'limits'=>array_merge($base_limits, ['tables'=>5,'categories'=>5,'items'=>20,'staff'=>2,'rooms'=>0,'branches'=>1])],
        'restaurant_all_access' => ['name'=>'Restaurant All Access','price'=>1999,'monthly_price'=>1999,'yearly_price'=>19990,'setup_fee'=>0,'billing_days'=>30,'duration_days'=>30,'trial_days'=>0,'plan_type'=>'restaurant','restaurant_limit'=>1,'description'=>'For restaurants, cafes, dhabas, cloud kitchens, fast food shops and sweet shops. Includes unlimited tables, unlimited categories, unlimited menu items, unlimited staff, table QR, KDS, WhatsApp bill, paid/unpaid billing and customer payments.','button_text'=>'Choose Restaurant Plan','color'=>'#ff7a18','is_recommended'=>1,'sort_order'=>3,'features'=>$restaurant,'limits'=>array_merge($base_limits, ['rooms'=>0,'branches'=>1])],
        'hotel_restaurant_full_access' => ['name'=>'Hotel + Restaurant Full Access','price'=>2499,'monthly_price'=>2499,'yearly_price'=>24990,'setup_fee'=>0,'billing_days'=>30,'duration_days'=>30,'trial_days'=>0,'plan_type'=>'hotel','restaurant_limit'=>1,'description'=>'Everything in Restaurant All Access plus hotel room QR ordering, room-wise billing, room service reports and priority support badge.','button_text'=>'Choose Hotel Plan','color'=>'#7c3aed','is_recommended'=>0,'sort_order'=>4,'features'=>array_merge($all_on, ['priority_support'=>true,'priority_support_badge'=>true]),'limits'=>array_merge($base_limits, ['branches'=>1])],
    ];
}

function fqx_encrypt_secret(string $value): string {
    $value = trim($value);
    if ($value === '') { return ''; }
    if (function_exists('openssl_encrypt')) {
        $key = hash('sha256', wp_salt('auth'), true);
        $iv = substr(hash('sha256', wp_salt('secure_auth')), 0, 16);
        $out = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
        return $out ? 'enc:' . $out : $value;
    }
    return $value;
}

function fqx_decrypt_secret(string $value): string {
    if (strpos($value, 'enc:') !== 0 || !function_exists('openssl_decrypt')) { return $value; }
    $key = hash('sha256', wp_salt('auth'), true);
    $iv = substr(hash('sha256', wp_salt('secure_auth')), 0, 16);
    $out = openssl_decrypt(substr($value, 4), 'AES-256-CBC', $key, 0, $iv);
    return $out !== false ? $out : '';
}

function fqx_schema_update(): void {
    if ((int) get_option('fqx_schema_version', 0) >= 111) { return; }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();
    $sql = [];
    $sql[] = "CREATE TABLE " . fqx_table('plans') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(191) NOT NULL, slug VARCHAR(191) NOT NULL,
        description TEXT NULL, monthly_price DECIMAL(10,2) NOT NULL DEFAULT 0, yearly_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        setup_fee DECIMAL(10,2) NOT NULL DEFAULT 0, trial_days INT NOT NULL DEFAULT 0, duration_days INT NOT NULL DEFAULT 30,
        plan_type VARCHAR(40) NOT NULL DEFAULT 'restaurant', status VARCHAR(40) NOT NULL DEFAULT 'active', is_recommended TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0, button_text VARCHAR(191) NULL, color VARCHAR(40) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
        PRIMARY KEY(id), UNIQUE KEY slug(slug), KEY status(status), KEY sort_order(sort_order)
    ) $charset;";
    $sql[] = "CREATE TABLE " . fqx_table('plan_features') . " (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, plan_id BIGINT UNSIGNED NOT NULL, feature_key VARCHAR(120) NOT NULL, is_enabled TINYINT(1) NOT NULL DEFAULT 0, PRIMARY KEY(id), UNIQUE KEY plan_feature(plan_id,feature_key), KEY plan_id(plan_id)) $charset;";
    $sql[] = "CREATE TABLE " . fqx_table('plan_limits') . " (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, plan_id BIGINT UNSIGNED NOT NULL, limit_key VARCHAR(120) NOT NULL, limit_value BIGINT NOT NULL DEFAULT 0, PRIMARY KEY(id), UNIQUE KEY plan_limit(plan_id,limit_key), KEY plan_id(plan_id)) $charset;";
    $sql[] = "CREATE TABLE " . fqx_table('restaurant_subscriptions') . " (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, restaurant_id BIGINT UNSIGNED NOT NULL, plan_id BIGINT UNSIGNED NOT NULL, status VARCHAR(50) NOT NULL DEFAULT 'pending', start_date DATETIME NULL, expiry_date DATETIME NULL, trial_used TINYINT(1) NOT NULL DEFAULT 0, auto_renew_enabled TINYINT(1) NOT NULL DEFAULT 0, gateway VARCHAR(50) NULL, gateway_subscription_id VARCHAR(191) NULL, last_payment_id BIGINT UNSIGNED NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), KEY restaurant_id(restaurant_id), KEY plan_id(plan_id), KEY status(status), KEY expiry_date(expiry_date)) $charset;";
    $sql[] = "CREATE TABLE " . fqx_table('subscription_payments') . " (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, restaurant_id BIGINT UNSIGNED NOT NULL, plan_id BIGINT UNSIGNED NOT NULL, subscription_id BIGINT UNSIGNED NOT NULL DEFAULT 0, amount DECIMAL(10,2) NOT NULL DEFAULT 0, currency VARCHAR(10) NOT NULL DEFAULT 'INR', payment_method VARCHAR(50) NOT NULL DEFAULT 'upi', gateway VARCHAR(50) NULL, gateway_payment_id VARCHAR(191) NULL, gateway_order_id VARCHAR(191) NULL, utr_number VARCHAR(191) NULL, screenshot_url VARCHAR(255) NULL, status VARCHAR(50) NOT NULL DEFAULT 'pending', admin_note TEXT NULL, paid_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), KEY restaurant_id(restaurant_id), KEY plan_id(plan_id), KEY subscription_id(subscription_id), KEY status(status)) $charset;";
    $sql[] = "CREATE TABLE " . fqx_table('restaurant_payment_settings') . " (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, restaurant_id BIGINT UNSIGNED NOT NULL, cash_enabled TINYINT(1) NOT NULL DEFAULT 1, upi_enabled TINYINT(1) NOT NULL DEFAULT 0, upi_id VARCHAR(191) NULL, upi_qr_url VARCHAR(255) NULL, upi_merchant_name VARCHAR(191) NULL, payment_instructions TEXT NULL, manual_verification_required TINYINT(1) NOT NULL DEFAULT 1, razorpay_enabled TINYINT(1) NOT NULL DEFAULT 0, razorpay_key_id VARCHAR(191) NULL, razorpay_key_secret_encrypted TEXT NULL, razorpay_webhook_secret_encrypted TEXT NULL, razorpay_mode VARCHAR(20) NOT NULL DEFAULT 'test', stripe_enabled TINYINT(1) NOT NULL DEFAULT 0, stripe_publishable_key VARCHAR(191) NULL, stripe_secret_key_encrypted TEXT NULL, stripe_webhook_secret_encrypted TEXT NULL, stripe_mode VARCHAR(20) NOT NULL DEFAULT 'test', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), UNIQUE KEY restaurant_id(restaurant_id)) $charset;";
    $sql[] = "CREATE TABLE " . fqx_table('order_payments') . " (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, restaurant_id BIGINT UNSIGNED NOT NULL, order_id BIGINT UNSIGNED NOT NULL DEFAULT 0, bill_id BIGINT UNSIGNED NOT NULL DEFAULT 0, amount DECIMAL(10,2) NOT NULL DEFAULT 0, currency VARCHAR(10) NOT NULL DEFAULT 'INR', payment_method VARCHAR(50) NOT NULL DEFAULT 'cash', gateway VARCHAR(50) NULL, gateway_payment_id VARCHAR(191) NULL, gateway_order_id VARCHAR(191) NULL, transaction_id VARCHAR(191) NULL, utr_number VARCHAR(191) NULL, screenshot_url VARCHAR(255) NULL, status VARCHAR(50) NOT NULL DEFAULT 'unpaid', paid_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), KEY restaurant_id(restaurant_id), KEY order_id(order_id), KEY bill_id(bill_id), KEY status(status)) $charset;";
    foreach ($sql as $s) { dbDelta($s); }

    $alter = [
        menuqr_table('subscription_plans') => [
            'monthly_price DECIMAL(10,2) NOT NULL DEFAULT 0', 'yearly_price DECIMAL(10,2) NOT NULL DEFAULT 0', 'setup_fee DECIMAL(10,2) NOT NULL DEFAULT 0', 'trial_days INT NOT NULL DEFAULT 0', 'duration_days INT NOT NULL DEFAULT 30', 'plan_type VARCHAR(40) NOT NULL DEFAULT \'restaurant\'', 'is_recommended TINYINT(1) NOT NULL DEFAULT 0', 'sort_order INT NOT NULL DEFAULT 0', 'button_text VARCHAR(191) NULL', 'color VARCHAR(40) NULL'
        ],
        menuqr_table('subscriptions') => ['trial_used TINYINT(1) NOT NULL DEFAULT 0', 'auto_renew_enabled TINYINT(1) NOT NULL DEFAULT 0', 'auto_renew_method VARCHAR(50) NULL', 'renewal_reminder_sent VARCHAR(191) NULL', 'gateway VARCHAR(50) NULL', 'gateway_subscription_id VARCHAR(191) NULL', 'last_payment_id BIGINT UNSIGNED NOT NULL DEFAULT 0'],
        menuqr_table('subscription_payments') => ['plan_id BIGINT UNSIGNED NOT NULL DEFAULT 0', 'currency VARCHAR(10) NOT NULL DEFAULT \'INR\'', 'gateway VARCHAR(50) NULL', 'gateway_payment_id VARCHAR(191) NULL', 'gateway_order_id VARCHAR(191) NULL', 'utr_number VARCHAR(191) NULL', 'screenshot_url VARCHAR(255) NULL', 'admin_note TEXT NULL', 'paid_at DATETIME NULL'],
        menuqr_table('orders') => ['transaction_id VARCHAR(191) NULL', 'paid_at DATETIME NULL'],
        menuqr_table('bills') => ['transaction_id VARCHAR(191) NULL', 'paid_at DATETIME NULL'],
        menuqr_table('payment_settings') => ['upi_merchant_name VARCHAR(191) NULL', 'payment_instructions TEXT NULL', 'manual_verification_required TINYINT(1) NOT NULL DEFAULT 1', 'razorpay_mode VARCHAR(20) NOT NULL DEFAULT \'test\'', 'stripe_enabled TINYINT(1) NOT NULL DEFAULT 0', 'stripe_mode VARCHAR(20) NOT NULL DEFAULT \'test\'', 'razorpay_webhook_secret TEXT NULL', 'stripe_webhook_secret TEXT NULL'],
    ];
    foreach ($alter as $table => $columns) {
        foreach ($columns as $def) {
            $name = preg_replace('/\s+.*/', '', $def);
            $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $name));
            if (!$exists) { $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$def}"); }
        }
    }
    update_option('fqx_schema_version', 111, false);
}
add_action('after_switch_theme', 'fqx_schema_update', 5);
add_action('init', 'fqx_schema_update', 3);


function fqx_remove_old_default_plans(): void {
    global $wpdb;
    $allowed = array_keys(menuqr_plan_matrix());
    $placeholders = implode(',', array_fill(0, count($allowed), '%s'));
    $wpdb->query($wpdb->prepare("UPDATE " . menuqr_table('subscription_plans') . " SET status='draft', updated_at=%s WHERE slug NOT IN ($placeholders)", array_merge([current_time('mysql')], $allowed)));
    if (function_exists('fqx_table')) { $wpdb->query($wpdb->prepare("UPDATE " . fqx_table('plans') . " SET status='draft', updated_at=%s WHERE slug NOT IN ($placeholders)", array_merge([current_time('mysql')], $allowed))); }
}

function fqx_create_default_plans(): void {
    if ((int) get_option('fqx_default_plans_version', 0) >= 127) { return; }
    global $wpdb;
    $now = current_time('mysql');
    foreach (menuqr_plan_matrix() as $slug => $plan) {
        $plan_payload = [
            'name' => $plan['name'], 'slug' => $slug, 'description' => $plan['description'],
            'monthly_price' => (float) $plan['monthly_price'], 'yearly_price' => (float) $plan['yearly_price'], 'setup_fee' => (float) $plan['setup_fee'],
            'trial_days' => (int) $plan['trial_days'], 'duration_days' => (int) $plan['duration_days'], 'plan_type' => $plan['plan_type'],
            'status' => 'active', 'is_recommended' => (int) $plan['is_recommended'], 'sort_order' => (int) $plan['sort_order'],
            'button_text' => $plan['button_text'], 'color' => $plan['color'], 'updated_at' => $now,
        ];
        $existing = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM " . fqx_table('plans') . " WHERE slug=%s", $slug));
        if ($existing) { $wpdb->update(fqx_table('plans'), $plan_payload, ['id' => $existing]); $fqx_id = $existing; }
        else { $plan_payload['created_at'] = $now; $wpdb->insert(fqx_table('plans'), $plan_payload); $fqx_id = (int) $wpdb->insert_id; }
        foreach ($plan['features'] as $key => $enabled) {
            $wpdb->replace(fqx_table('plan_features'), ['plan_id'=>$fqx_id,'feature_key'=>$key,'is_enabled'=>$enabled?1:0]);
        }
        foreach ($plan['limits'] as $key => $limit) {
            $wpdb->replace(fqx_table('plan_limits'), ['plan_id'=>$fqx_id,'limit_key'=>$key,'limit_value'=>(int)$limit]);
        }
        $legacy_payload = [
            'name' => $plan['name'], 'slug' => $slug, 'price' => (float) $plan['price'], 'billing_days' => (int) $plan['billing_days'],
            'features' => wp_json_encode(['features'=>$plan['features'], 'limits'=>$plan['limits'], 'restaurant_limit'=>$plan['restaurant_limit']]),
            'description' => $plan['description'], 'status' => 'active', 'monthly_price' => (float) $plan['monthly_price'], 'yearly_price' => (float) $plan['yearly_price'],
            'setup_fee' => (float) $plan['setup_fee'], 'trial_days' => (int) $plan['trial_days'], 'duration_days' => (int) $plan['duration_days'], 'plan_type' => $plan['plan_type'],
            'is_recommended' => (int) $plan['is_recommended'], 'sort_order' => (int) $plan['sort_order'], 'button_text' => $plan['button_text'], 'color' => $plan['color'], 'updated_at' => $now,
        ];
        $legacy = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM " . menuqr_table('subscription_plans') . " WHERE slug=%s", $slug));
        if ($legacy) { $wpdb->update(menuqr_table('subscription_plans'), $legacy_payload, ['id'=>$legacy]); }
        else { $legacy_payload['created_at'] = $now; $wpdb->insert(menuqr_table('subscription_plans'), $legacy_payload); }
    }
    if (function_exists('fqx_remove_old_default_plans')) { fqx_remove_old_default_plans(); }
    update_option('fqx_default_plans_version', 127, false);
}
add_action('init', 'fqx_create_default_plans', 8);
function menuqr_upsert_default_plans(bool $force_sync = false): void { fqx_create_default_plans(); }

function menuqr_get_subscription_plans(): array {
    global $wpdb;
    return (array) $wpdb->get_results("SELECT * FROM " . menuqr_table('subscription_plans') . " WHERE status IN ('active','hidden') AND slug IN ('free_trial','starter_5_table','restaurant_all_access','hotel_restaurant_full_access') ORDER BY COALESCE(sort_order,999), price ASC, id ASC");
}
function fqx_get_active_plans(): array { return menuqr_get_subscription_plans(); }
function fqx_get_plan($plan_id) { return menuqr_get_plan_by_id((int)$plan_id); }
function menuqr_get_plan_by_id(int $plan_id): ?object {
    global $wpdb; if ($plan_id <= 0) return null;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('subscription_plans') . " WHERE id=%d", $plan_id));
}
function menuqr_get_plan_by_slug(string $slug): ?object {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('subscription_plans') . " WHERE slug=%s LIMIT 1", sanitize_key($slug)));
}
function menuqr_get_default_plan_id(): int { $p = menuqr_get_plan_by_slug('free_trial'); return $p ? (int)$p->id : 0; }

function menuqr_get_latest_subscription(int $restaurant_id): ?object {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('subscriptions') . " WHERE restaurant_id=%d ORDER BY id DESC LIMIT 1", $restaurant_id));
}
function fqx_get_restaurant_plan($restaurant_id) { return menuqr_get_latest_subscription((int)$restaurant_id); }
function menuqr_subscription_is_active(int $restaurant_id): bool {
    $s = menuqr_get_latest_subscription($restaurant_id);
    if (!$s || !in_array((string)$s->status, ['active','trial'], true)) return false;
    $ts = strtotime((string)$s->expires_at);
    return $ts !== false && $ts >= current_time('timestamp');
}
function fqx_is_subscription_active($restaurant_id): bool { return menuqr_subscription_is_active((int)$restaurant_id); }
function menuqr_enforce_subscription_or_die(int $restaurant_id): void { if (!menuqr_subscription_is_active($restaurant_id)) menuqr_json_response(false, ['message'=>'Subscription expired. Please renew your FluuexQR plan.'], 403); }

function menuqr_get_plan_config_from_plan(object $plan): array {
    $decoded = json_decode((string)($plan->features ?? ''), true);
    $slug = sanitize_key((string)($plan->slug ?? ''));
    $matrix = menuqr_plan_matrix();
    $base = $matrix[$slug] ?? [];
    if (is_array($decoded) && isset($decoded['features'], $decoded['limits'])) {
        $base['features'] = array_merge($base['features'] ?? [], (array)$decoded['features']);
        $base['limits'] = array_merge($base['limits'] ?? [], (array)$decoded['limits']);
        if (isset($decoded['restaurant_limit'])) $base['restaurant_limit'] = (int)$decoded['restaurant_limit'];
    }
    return $base;
}
function menuqr_get_restaurant_plan_slug(int $restaurant_id): string {
    global $wpdb;
    $s = menuqr_get_latest_subscription($restaurant_id);
    if (!$s || !menuqr_subscription_is_active($restaurant_id)) return 'expired';
    $slug = (string)$wpdb->get_var($wpdb->prepare("SELECT slug FROM " . menuqr_table('subscription_plans') . " WHERE id=%d", (int)$s->plan_id));
    return $slug ?: ((string)$s->status === 'trial' ? 'free_trial' : 'restaurant_all_access');
}
function menuqr_get_restaurant_plan_config(int $restaurant_id): array {
    $plan = null; $s = menuqr_get_latest_subscription($restaurant_id);
    if ($s) $plan = menuqr_get_plan_by_id((int)$s->plan_id);
    if ($plan && menuqr_subscription_is_active($restaurant_id)) return menuqr_get_plan_config_from_plan($plan);
    return ['name'=>'Expired','price'=>0,'billing_days'=>0,'features'=>[],'limits'=>array_fill_keys(fqx_all_limit_keys(),0),'description'=>'Subscription expired.'];
}
function menuqr_plan_allows(int $restaurant_id, string $feature): bool {
    if (!menuqr_subscription_is_active($restaurant_id)) return false;
    $cfg = menuqr_get_restaurant_plan_config($restaurant_id);
    $aliases = ['gateway'=>'razorpay_customer_payment','razorpay'=>'razorpay_customer_payment','bills'=>'running_bill','staff'=>'staff_management','advanced_reports'=>'reports','coupons'=>'coupons','combos'=>'combos','reviews'=>'reviews'];
    $key = sanitize_key($feature);
    return !empty($cfg['features'][$key]) || (!empty($aliases[$key]) && !empty($cfg['features'][$aliases[$key]]));
}
function fqx_has_feature($restaurant_id, $feature_key): bool { return menuqr_plan_allows((int)$restaurant_id, sanitize_key($feature_key)); }
function menuqr_plan_limit(int $restaurant_id, string $limit_key): int {
    $cfg = menuqr_get_restaurant_plan_config($restaurant_id);
    $key = sanitize_key($limit_key);
    $aliases = ['tables'=>'tables','items'=>'items','categories'=>'categories','staff'=>'staff','rooms'=>'rooms'];
    return (int)($cfg['limits'][$key] ?? $cfg['limits'][$aliases[$key] ?? $key] ?? 0);
}
function fqx_get_plan_limit($restaurant_id, $limit_key): int { return menuqr_plan_limit((int)$restaurant_id, sanitize_key($limit_key)); }
function menuqr_plan_usage(int $restaurant_id, string $resource): int {
    global $wpdb; $map = ['tables'=>'tables','items'=>'items','categories'=>'categories','staff'=>'staff','rooms'=>'rooms','coupons'=>'coupons','combos'=>'combos'];
    if (!isset($map[$resource])) return 0;
    return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . menuqr_table($map[$resource]) . " WHERE restaurant_id=%d", $restaurant_id));
}
function menuqr_plan_can_add(int $restaurant_id, string $resource, int $editing_id = 0): bool { $limit = menuqr_plan_limit($restaurant_id,$resource); return $limit < 0 || $editing_id > 0 || menuqr_plan_usage($restaurant_id,$resource) < $limit; }
function fqx_check_limit($restaurant_id, $limit_key, $current_count): bool { $limit=fqx_get_plan_limit((int)$restaurant_id, sanitize_key($limit_key)); return $limit < 0 || (int)$current_count < $limit; }
function menuqr_upgrade_url(string $feature = ''): string { return add_query_arg(array_filter(['tab'=>'subscription','feature'=>sanitize_key($feature)]), menuqr_get_page_url_by_slug('restaurant-dashboard')); }
function menuqr_locked_feature_html(string $title, string $message, string $feature = ''): string { return '<div class="mq-lock-card"><div class="mq-lock-icon">🔒</div><h4>'.esc_html($title).'</h4><p>'.esc_html($message).'</p><a class="btn btn-primary btn-sm" href="'.esc_url(menuqr_upgrade_url($feature)).'">Upgrade Plan</a></div>'; }
function menuqr_plan_label(int $restaurant_id): string { $c=menuqr_get_restaurant_plan_config($restaurant_id); return (string)($c['name'] ?? 'Expired'); }
function menuqr_get_plan_badge_class(int $restaurant_id): string { return 'mq-plan-' . sanitize_html_class(menuqr_get_restaurant_plan_slug($restaurant_id)); }
function menuqr_subscription_status_label(int $restaurant_id): string { $s=menuqr_get_latest_subscription($restaurant_id); if(!$s) return 'Expired'; if(!menuqr_subscription_is_active($restaurant_id)) return ucfirst((string)$s->status); return (string)$s->status === 'trial' ? 'Trial Active' : 'Active'; }
function menuqr_subscription_days_left(int $restaurant_id): int { $s=menuqr_get_latest_subscription($restaurant_id); if(!$s||empty($s->expires_at)) return 0; $ts=strtotime((string)$s->expires_at); return $ts?max(0,(int)ceil(($ts-current_time('timestamp'))/DAY_IN_SECONDS)):0; }

function fqx_log_subscription_action($restaurant_id, $action, $note): void { do_action('fqx_subscription_log', (int)$restaurant_id, sanitize_key($action), sanitize_text_field($note)); }
function fqx_assign_plan_to_restaurant($restaurant_id, $plan_id, $days): bool { menuqr_sync_restaurant_subscription((int)$restaurant_id, 'active', (int)$plan_id, gmdate('Y-m-d H:i:s', current_time('timestamp') + max(1,(int)$days)*DAY_IN_SECONDS)); return true; }
function fqx_extend_subscription($restaurant_id, $plan_id, $days): bool { return fqx_assign_plan_to_restaurant($restaurant_id, $plan_id, $days); }
function menuqr_sync_restaurant_subscription(int $restaurant_id, string $status, int $plan_id = 0, string $expires_at = ''): void {
    global $wpdb; if($restaurant_id<=0) return; $now=current_time('mysql'); $now_ts=current_time('timestamp');
    if($plan_id<=0) $plan_id = menuqr_get_default_plan_id(); $plan=menuqr_get_plan_by_id($plan_id); $days=max(1,(int)($plan->billing_days ?? $plan->duration_days ?? 30));
    $status=sanitize_key($status); if(!in_array($status,['active','trial','pending','inactive','expired','cancelled'],true)) $status='inactive';
    if($expires_at==='') $expires_at = in_array($status,['active','trial'],true) ? gmdate('Y-m-d H:i:s',$now_ts+$days*DAY_IN_SECONDS) : ($status==='pending'?gmdate('Y-m-d H:i:s',$now_ts+$days*DAY_IN_SECONDS):gmdate('Y-m-d H:i:s',$now_ts-DAY_IN_SECONDS));
    $payment_status = in_array($status,['active','trial'],true) ? 'paid' : ($status==='pending'?'pending':'unpaid');
    $latest = menuqr_get_latest_subscription($restaurant_id);
    $payload=['restaurant_id'=>$restaurant_id,'plan_id'=>$plan_id,'starts_at'=>$now,'expires_at'=>$expires_at,'status'=>$status,'payment_status'=>$payment_status,'updated_at'=>$now];
    if($latest) $wpdb->update(menuqr_table('subscriptions'), $payload, ['id'=>(int)$latest->id]); else { $payload['created_at']=$now; $wpdb->insert(menuqr_table('subscriptions'), $payload); }
    $rstatus = in_array($status,['active','trial'],true) ? 'active' : $status;
    $wpdb->update(menuqr_table('restaurants'), ['subscription_status'=>$rstatus,'updated_at'=>$now], ['id'=>$restaurant_id]);
}
function menuqr_mark_expired_subscriptions(): void {
    global $wpdb; $now=current_time('mysql');
    $wpdb->query($wpdb->prepare("UPDATE " . menuqr_table('subscriptions') . " SET status='expired', payment_status='unpaid', updated_at=%s WHERE expires_at < %s AND status IN ('active','trial')", $now, $now));
}
add_action('init','menuqr_mark_expired_subscriptions',20);

function menuqr_activate_subscription_from_payment(object $payment): bool {
    global $wpdb; $sub = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('subscriptions') . " WHERE id=%d", (int)$payment->subscription_id)); if(!$sub) return false;
    $plan = menuqr_get_plan_by_id((int)$sub->plan_id); if(!$plan) return false; $days=max(1,(int)($plan->billing_days ?? 30));
    $status = sanitize_key((string)$plan->slug)==='free_trial' ? 'trial' : 'active';
    menuqr_sync_restaurant_subscription((int)$payment->restaurant_id, $status, (int)$sub->plan_id, gmdate('Y-m-d H:i:s', current_time('timestamp')+$days*DAY_IN_SECONDS));
    $wpdb->update(menuqr_table('subscription_payments'), ['status'=>'verified','paid_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')], ['id'=>(int)$payment->id]);
    return true;
}
function menuqr_create_subscription_payment_request(int $restaurant_id, int $plan_id, string $method, string $reference = '', string $proof_file = ''): int {
    global $wpdb; $plan=menuqr_get_plan_by_id($plan_id); if(!$plan || !in_array((string)$plan->status,['active','hidden'],true)) return 0; $now=current_time('mysql');
    if(sanitize_key((string)$plan->slug)==='free_trial'){
        $used=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . menuqr_table('subscriptions') . " s JOIN " . menuqr_table('subscription_plans') . " p ON p.id=s.plan_id WHERE s.restaurant_id=%d AND p.slug='free_trial' AND s.status IN ('trial','active','expired')", $restaurant_id));
        if($used>0) return 0;
    }
    $wpdb->insert(menuqr_table('subscriptions'), ['restaurant_id'=>$restaurant_id,'plan_id'=>$plan_id,'starts_at'=>$now,'expires_at'=>$now,'status'=>'pending','payment_status'=>'pending','created_at'=>$now,'updated_at'=>$now]);
    $subscription_id=(int)$wpdb->insert_id; if($subscription_id<=0) return 0;
    $amount=(float)($plan->price ?? $plan->monthly_price ?? 0); $status=$amount<=0?'verified':'pending';
    $wpdb->insert(menuqr_table('subscription_payments'), ['restaurant_id'=>$restaurant_id,'subscription_id'=>$subscription_id,'plan_id'=>$plan_id,'amount'=>$amount,'currency'=>'INR','payment_method'=>sanitize_key($method?:'upi'),'gateway'=>sanitize_key($method?:'upi'),'transaction_reference'=>sanitize_text_field($reference),'utr_number'=>sanitize_text_field($reference),'proof_file'=>esc_url_raw($proof_file),'screenshot_url'=>esc_url_raw($proof_file),'status'=>$status,'notes'=>'FluuexQR plan purchase: '.(string)$plan->name,'created_at'=>$now,'updated_at'=>$now]);
    $payment_id=(int)$wpdb->insert_id;
    if($payment_id>0 && $status==='verified') { $p=$wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('subscription_payments') . " WHERE id=%d",$payment_id)); if($p) menuqr_activate_subscription_from_payment($p); }
    else $wpdb->update(menuqr_table('restaurants'), ['subscription_status'=>'pending','updated_at'=>$now], ['id'=>$restaurant_id]);
    return $payment_id;
}
function menuqr_get_subscription_payment_history(int $restaurant_id, int $limit = 50): array {
    global $wpdb;
    return (array)$wpdb->get_results($wpdb->prepare("SELECT p.*, sp.name AS plan_name, sp.slug AS plan_slug, s.expires_at FROM " . menuqr_table('subscription_payments') . " p LEFT JOIN " . menuqr_table('subscriptions') . " s ON s.id=p.subscription_id LEFT JOIN " . menuqr_table('subscription_plans') . " sp ON sp.id=s.plan_id WHERE p.restaurant_id=%d ORDER BY COALESCE(p.created_at, p.updated_at) DESC, p.id DESC LIMIT %d", $restaurant_id,$limit));
}
function fqx_create_subscription_payment_order($restaurant_id, $plan_id, $gateway) { return menuqr_create_subscription_payment_request((int)$restaurant_id,(int)$plan_id,sanitize_key($gateway)); }
function fqx_verify_subscription_payment($payment_data) { return true; }
function fqx_activate_subscription_after_payment($restaurant_id, $plan_id, $payment_id) { return fqx_assign_plan_to_restaurant((int)$restaurant_id,(int)$plan_id,30); }
function fqx_send_renewal_reminder($restaurant_id) { return true; }

function fqx_get_platform_payment_settings(): array {
    $defaults=['razorpay_enabled'=>0,'razorpay_key_id'=>'','razorpay_key_secret'=>'','razorpay_webhook_secret'=>'','razorpay_mode'=>'test','stripe_enabled'=>0,'stripe_publishable_key'=>'','stripe_secret_key'=>'','stripe_webhook_secret'=>'','stripe_mode'=>'test','platform_upi_enabled'=>1,'platform_upi_id'=>'','platform_upi_qr'=>'','upi_merchant_name'=>'FluuexQR','upi_autopay_enabled'=>0,'one_click_renewal_enabled'=>1,'renewal_reminder_days'=>'7,3,1,0','platform_payment_instructions'=>'Pay to FluuexQR and upload UTR/screenshot for verification.','bank_transfer_enabled'=>1,'bank_account_name'=>'','bank_account_number'=>'','bank_ifsc'=>'','bank_name'=>'','bank_branch'=>'','bank_account_type'=>'current','bank_beneficiary_name'=>'','manual_verification_required'=>1,'currency'=>'INR'];
    $saved=get_option('fqx_platform_payment_settings',[]); if(!is_array($saved)) $saved=[]; return array_merge($defaults,$saved);
}
function menuqr_save_platform_payment_settings(array $data): bool {
    $current = fqx_get_platform_payment_settings();
    $rz_secret = trim((string) ($data['razorpay_key_secret'] ?? ''));
    $rz_webhook = trim((string) ($data['razorpay_webhook_secret'] ?? ''));
    $st_secret = trim((string) ($data['stripe_secret_key'] ?? ''));
    $st_webhook = trim((string) ($data['stripe_webhook_secret'] ?? ''));
    $payload=[
        'razorpay_enabled'=>!empty($data['razorpay_enabled'])?1:0,'razorpay_key_id'=>sanitize_text_field($data['razorpay_key_id']??''),'razorpay_key_secret'=>$rz_secret!==''?fqx_encrypt_secret($rz_secret):($current['razorpay_key_secret']??''),'razorpay_webhook_secret'=>$rz_webhook!==''?fqx_encrypt_secret($rz_webhook):($current['razorpay_webhook_secret']??''),'razorpay_mode'=>sanitize_key($data['razorpay_mode']??'test'),
        'stripe_enabled'=>!empty($data['stripe_enabled'])?1:0,'stripe_publishable_key'=>sanitize_text_field($data['stripe_publishable_key']??''),'stripe_secret_key'=>$st_secret!==''?fqx_encrypt_secret($st_secret):($current['stripe_secret_key']??''),'stripe_webhook_secret'=>$st_webhook!==''?fqx_encrypt_secret($st_webhook):($current['stripe_webhook_secret']??''),'stripe_mode'=>sanitize_key($data['stripe_mode']??'test'),
        'platform_upi_enabled'=>!empty($data['platform_upi_enabled'])?1:0,'platform_upi_id'=>sanitize_text_field($data['platform_upi_id']??''),'platform_upi_qr'=>esc_url_raw($data['platform_upi_qr']??''),'upi_merchant_name'=>sanitize_text_field($data['upi_merchant_name']??'FluuexQR'),'upi_autopay_enabled'=>!empty($data['upi_autopay_enabled'])?1:0,'one_click_renewal_enabled'=>!empty($data['one_click_renewal_enabled'])?1:0,'renewal_reminder_days'=>sanitize_text_field($data['renewal_reminder_days']??'7,3,1,0'),'platform_payment_instructions'=>sanitize_textarea_field($data['platform_payment_instructions']??''),
        'bank_transfer_enabled'=>!empty($data['bank_transfer_enabled'])?1:0,'bank_account_name'=>sanitize_text_field($data['bank_account_name']??''),'bank_account_number'=>sanitize_text_field($data['bank_account_number']??''),'bank_ifsc'=>sanitize_text_field($data['bank_ifsc']??''),'bank_name'=>sanitize_text_field($data['bank_name']??''),'bank_branch'=>sanitize_text_field($data['bank_branch']??''),'bank_account_type'=>sanitize_key($data['bank_account_type']??'current'),'bank_beneficiary_name'=>sanitize_text_field($data['bank_beneficiary_name']??''),'manual_verification_required'=>!empty($data['manual_verification_required'])?1:0,'currency'=>sanitize_text_field($data['currency']??'INR')
    ];
    return update_option('fqx_platform_payment_settings',$payload,false);
}


function fqx_platform_payment_methods(): array {
    $s = fqx_get_platform_payment_settings();
    $methods = [];
    if (!empty($s['razorpay_enabled'])) { $methods['razorpay'] = 'Razorpay Online Payment'; }
    if (!empty($s['stripe_enabled'])) { $methods['stripe'] = 'Stripe Online Payment'; }
    if (!empty($s['platform_upi_enabled'])) { $methods['upi'] = 'UPI Manual Payment'; }
    if (!empty($s['bank_transfer_enabled'])) { $methods['bank'] = 'Bank Transfer / NEFT / IMPS'; }
    if (!$methods) { $methods['upi'] = 'UPI Manual Payment'; }
    return $methods;
}
function fqx_build_upi_pay_url(float $amount, string $note = 'FluuexQR Subscription'): string {
    $s = fqx_get_platform_payment_settings();
    $pa = trim((string)($s['platform_upi_id'] ?? ''));
    if ($pa === '') { return ''; }
    $params = ['pa'=>$pa,'pn'=>(string)($s['upi_merchant_name'] ?? 'FluuexQR'),'am'=>number_format($amount,2,'.',''),'cu'=>'INR','tn'=>$note];
    return 'upi://pay?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}
function fqx_update_subscription_auto_renew(int $subscription_id, int $enabled, string $method='razorpay'): bool {
    global $wpdb;
    if ($subscription_id <= 0) { return false; }
    return false !== $wpdb->update(menuqr_table('subscriptions'), ['auto_renew_enabled'=>$enabled?1:0,'auto_renew_method'=>sanitize_key($method),'updated_at'=>current_time('mysql')], ['id'=>$subscription_id]);
}
function fqx_current_subscription_needs_renewal(int $restaurant_id): bool {
    $s = menuqr_get_latest_subscription($restaurant_id);
    return $s && menuqr_subscription_days_left($restaurant_id) <= 7;
}
function fqx_auto_renew_notice_text(int $restaurant_id): string {
    $days = menuqr_subscription_days_left($restaurant_id);
    if ($days <= 0) { return 'Your plan has expired. Renew now to enable customer ordering again.'; }
    if ($days <= 7) { return 'Your plan expires in ' . $days . ' day(s). Use one-click UPI/Razorpay renewal to avoid order blocking.'; }
    return 'Auto-renew reminder is active. You will see renewal prompts before expiry.';
}

function fqx_upsert_order_payment(int $restaurant_id, int $order_id, int $bill_id, float $amount, string $method, string $status, string $gateway_payment_id = '', string $gateway_order_id = ''): int {
    global $wpdb; $now=current_time('mysql'); $table=fqx_table('order_payments');
    $existing=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE order_id=%d ORDER BY id DESC LIMIT 1",$order_id));
    $payload=['restaurant_id'=>$restaurant_id,'order_id'=>$order_id,'bill_id'=>$bill_id,'amount'=>$amount,'currency'=>'INR','payment_method'=>sanitize_key($method),'gateway'=>sanitize_key($method),'gateway_payment_id'=>$gateway_payment_id,'gateway_order_id'=>$gateway_order_id,'transaction_id'=>$gateway_payment_id,'status'=>sanitize_key($status),'paid_at'=>$status==='paid'?$now:null,'updated_at'=>$now];
    if($existing){ $wpdb->update($table,$payload,['id'=>$existing]); return $existing; }
    $payload['created_at']=$now; $wpdb->insert($table,$payload); return (int)$wpdb->insert_id;
}
function fqx_get_restaurant_payment_settings($restaurant_id) { return menuqr_get_payment_settings((int)$restaurant_id); }
function fqx_create_customer_payment_order($restaurant_id,$order_id,$amount,$gateway){ return ['success'=>true,'restaurant_id'=>(int)$restaurant_id,'order_id'=>(int)$order_id,'amount'=>(float)$amount,'gateway'=>sanitize_key($gateway)]; }
function fqx_verify_customer_payment($payment_data){ return true; }
function fqx_mark_order_paid($order_id,$payment_id,$method){ return menuqr_mark_order_paid((int)$order_id,(string)$payment_id,(string)$method); }
function fqx_mark_order_unpaid($order_id){ return menuqr_mark_order_unpaid((int)$order_id); }
function fqx_update_bill_payment_status($bill_id,$status){ return menuqr_update_bill_payment_status((int)$bill_id,(string)$status); }
function menuqr_mark_order_paid(int $order_id, string $payment_id='', string $method='cash'): bool {
    global $wpdb; $order=$wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('orders') . " WHERE id=%d",$order_id)); if(!$order) return false; $now=current_time('mysql');
    $wpdb->update(menuqr_table('orders'), ['payment_method'=>sanitize_key($method),'payment_status'=>'paid','transaction_id'=>$payment_id,'payment_reference'=>$payment_id,'paid_at'=>$now,'updated_at'=>$now], ['id'=>$order_id]);
    if(!empty($order->bill_id)) $wpdb->update(menuqr_table('bills'), ['payment_method'=>sanitize_key($method),'payment_status'=>'paid','transaction_id'=>$payment_id,'paid_at'=>$now,'updated_at'=>$now], ['id'=>(int)$order->bill_id]);
    fqx_upsert_order_payment((int)$order->restaurant_id,$order_id,(int)$order->bill_id,(float)$order->final_total,$method,'paid',$payment_id,''); return true;
}
function menuqr_mark_order_unpaid(int $order_id): bool { global $wpdb; return false !== $wpdb->update(menuqr_table('orders'), ['payment_status'=>'unpaid','paid_at'=>null,'updated_at'=>current_time('mysql')], ['id'=>$order_id]); }
function menuqr_update_bill_payment_status(int $bill_id, string $status): bool { global $wpdb; return false !== $wpdb->update(menuqr_table('bills'), ['payment_status'=>sanitize_key($status),'updated_at'=>current_time('mysql')], ['id'=>$bill_id]); }
