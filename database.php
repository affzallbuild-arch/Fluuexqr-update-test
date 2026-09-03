<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_create_tables(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = [];

    $sql[] = "CREATE TABLE " . menuqr_table('restaurants') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        wp_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        name VARCHAR(191) NOT NULL,
        slug VARCHAR(191) NOT NULL,
        owner_name VARCHAR(191) NOT NULL,
        email VARCHAR(191) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        address TEXT NULL,
        logo VARCHAR(255) NULL,
        gst_number VARCHAR(100) NULL,
        fssai_number VARCHAR(100) NULL,
        approval_status VARCHAR(50) NOT NULL DEFAULT 'pending',
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        subscription_status VARCHAR(50) NOT NULL DEFAULT 'inactive',
        trial_ends_at DATETIME NULL,
        google_reviews_enabled TINYINT(1) NOT NULL DEFAULT 0,
        google_review_link VARCHAR(500) NULL,
        google_place_id VARCHAR(191) NULL,
        review_button_text VARCHAR(191) NULL,
        review_request_message TEXT NULL,
        show_review_after_served TINYINT(1) NOT NULL DEFAULT 1,
        show_review_on_bill TINYINT(1) NOT NULL DEFAULT 1,
        show_review_on_print TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY wp_user_id (wp_user_id),
        KEY slug (slug),
        KEY email (email)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('categories') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(191) NOT NULL,
        description TEXT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('items') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        category_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(191) NOT NULL,
        description TEXT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        discount_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        food_type VARCHAR(20) NOT NULL DEFAULT 'veg',
        tax_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
        service_charge_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        image VARCHAR(255) NULL,
        emoji VARCHAR(50) NULL,
        variants LONGTEXT NULL,
        addons LONGTEXT NULL,
        is_available TINYINT(1) NOT NULL DEFAULT 1,
        is_featured TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY category_id (category_id)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('tables') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        table_number VARCHAR(50) NOT NULL,
        capacity INT NOT NULL DEFAULT 2,
        qr_token VARCHAR(191) NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY qr_token (qr_token)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('rooms') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        room_number VARCHAR(50) NOT NULL,
        room_name VARCHAR(191) NULL,
        floor VARCHAR(50) NULL,
        wing VARCHAR(100) NULL,
        room_type VARCHAR(100) NULL,
        qr_token VARCHAR(191) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        notes TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY qr_token (qr_token)
    ) $charset_collate;";

    
    $sql[] = "CREATE TABLE " . menuqr_table('qr_templates') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        table_id BIGINT UNSIGNED NOT NULL,
        template_key VARCHAR(120) NOT NULL,
        qr_url TEXT NOT NULL,
        qr_image TEXT NULL,
        design_settings LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY restaurant_table (restaurant_id, table_id),
        KEY restaurant_id (restaurant_id),
        KEY table_id (table_id),
        KEY template_key (template_key)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('orders') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        table_id BIGINT UNSIGNED NOT NULL,
        unique_code VARCHAR(50) NOT NULL,
        customer_name VARCHAR(191) NULL,
        customer_phone VARCHAR(50) NULL,
        bill_session_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        bill_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        items_json LONGTEXT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        service_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        final_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
        payment_status VARCHAR(50) NOT NULL DEFAULT 'unpaid',
        order_status VARCHAR(50) NOT NULL DEFAULT 'pending',
        payment_reference VARCHAR(191) NULL,
        gateway_provider VARCHAR(50) NULL,
        gateway_order_id VARCHAR(191) NULL,
        gateway_payment_id VARCHAR(191) NULL,
        gateway_signature TEXT NULL,
        payment_screenshot VARCHAR(255) NULL,
        customer_note TEXT NULL,
        duplicate_key VARCHAR(191) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY table_id (table_id),
        KEY bill_session_id (bill_session_id),
        KEY bill_id (bill_id),
        KEY order_status (order_status),
        KEY duplicate_key (duplicate_key),
        KEY unique_code (unique_code)
    ) $charset_collate;";


    $sql[] = "CREATE TABLE " . menuqr_table('bill_sessions') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        table_id BIGINT UNSIGNED NOT NULL,
        session_token VARCHAR(191) NOT NULL,
        customer_name VARCHAR(191) NULL,
        customer_whatsapp VARCHAR(50) NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        started_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        closed_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY table_id (table_id),
        KEY session_token (session_token),
        KEY status (status),
        KEY expires_at (expires_at)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('bills') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        table_id BIGINT UNSIGNED NOT NULL,
        bill_session_id BIGINT UNSIGNED NOT NULL,
        bill_number VARCHAR(80) NOT NULL,
        access_key VARCHAR(64) NOT NULL,
        customer_name VARCHAR(191) NULL,
        customer_whatsapp VARCHAR(50) NULL,
        restaurant_snapshot LONGTEXT NULL,
        items_snapshot LONGTEXT NULL,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        service_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        round_off DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(50) NOT NULL DEFAULT 'mixed',
        payment_status VARCHAR(50) NOT NULL DEFAULT 'unpaid',
        bill_status VARCHAR(50) NOT NULL DEFAULT 'running',
        whatsapp_status VARCHAR(50) NOT NULL DEFAULT 'not_sent',
        whatsapp_sent_at DATETIME NULL,
        print_count INT NOT NULL DEFAULT 0,
        bill_format VARCHAR(50) NOT NULL DEFAULT '80mm',
        packaging_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        delivery_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        staff_name VARCHAR(191) NULL,
        customer_note TEXT NULL,
        thank_you_message VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY table_id (table_id),
        KEY bill_session_id (bill_session_id),
        KEY access_key (access_key),
        KEY payment_status (payment_status),
        KEY bill_status (bill_status)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('payment_settings') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        cash_enabled TINYINT(1) NOT NULL DEFAULT 1,
        upi_enabled TINYINT(1) NOT NULL DEFAULT 0,
        upi_id VARCHAR(191) NULL,
        upi_qr VARCHAR(255) NULL,
        screenshot_enabled TINYINT(1) NOT NULL DEFAULT 0,
        online_enabled TINYINT(1) NOT NULL DEFAULT 0,
        razorpay_key VARCHAR(191) NULL,
        razorpay_secret VARCHAR(191) NULL,
        stripe_publishable_key VARCHAR(191) NULL,
        stripe_secret_key VARCHAR(191) NULL,
        gateway_provider VARCHAR(50) NOT NULL DEFAULT 'razorpay',
        phonepe_enabled TINYINT(1) NOT NULL DEFAULT 0,
        phonepe_client_id VARCHAR(191) NULL,
        phonepe_client_secret TEXT NULL,
        phonepe_client_version VARCHAR(50) NULL,
        phonepe_merchant_id VARCHAR(191) NULL,
        phonepe_environment VARCHAR(50) NOT NULL DEFAULT 'sandbox',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY restaurant_id (restaurant_id)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('staff') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        wp_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        name VARCHAR(191) NOT NULL,
        email VARCHAR(191) NOT NULL,
        phone VARCHAR(50) NULL,
        role_name VARCHAR(50) NOT NULL DEFAULT 'kitchen',
        permissions_json LONGTEXT NULL,
        pin_code VARCHAR(20) NULL,
        attendance_status VARCHAR(50) NOT NULL DEFAULT 'offline',
        last_seen_at DATETIME NULL,
        handled_orders INT NOT NULL DEFAULT 0,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY wp_user_id (wp_user_id)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('subscription_plans') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(191) NOT NULL,
        slug VARCHAR(191) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        billing_days INT NOT NULL DEFAULT 30,
        features LONGTEXT NULL,
        description TEXT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY slug (slug)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('subscriptions') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        plan_id BIGINT UNSIGNED NOT NULL,
        starts_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY plan_id (plan_id),
        KEY status (status)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('subscription_payments') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        subscription_id BIGINT UNSIGNED NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
        transaction_reference VARCHAR(191) NULL,
        proof_file VARCHAR(255) NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        notes TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY subscription_id (subscription_id),
        KEY status (status)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('review_clicks') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        table_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        bill_session_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        customer_phone VARCHAR(50) NULL,
        ip_hash VARCHAR(191) NULL,
        user_agent VARCHAR(255) NULL,
        clicked_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY table_id (table_id),
        KEY order_id (order_id),
        KEY bill_session_id (bill_session_id),
        KEY clicked_at (clicked_at)
    ) $charset_collate;";


    $sql[] = "CREATE TABLE " . menuqr_table('combos') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(191) NOT NULL,
        description TEXT NULL,
        image VARCHAR(255) NULL,
        emoji VARCHAR(50) NULL,
        original_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        combo_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        items_json LONGTEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY is_active (is_active)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE " . menuqr_table('coupons') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL,
        code VARCHAR(80) NOT NULL,
        description TEXT NULL,
        discount_type VARCHAR(20) NOT NULL DEFAULT 'percentage',
        discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        min_order DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        usage_limit INT NOT NULL DEFAULT 0,
        used_count INT NOT NULL DEFAULT 0,
        starts_at DATETIME NULL,
        expires_at DATETIME NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY restaurant_id (restaurant_id),
        KEY code (code),
        KEY is_active (is_active)
    ) $charset_collate;";


    foreach ($sql as $statement) {
        dbDelta($statement);
    }

    menuqr_run_room_qr_schema_updates();
}

function menuqr_run_room_qr_schema_updates(): void {
    global $wpdb;

    $queries = [
        "ALTER TABLE " . menuqr_table('orders') . " ADD COLUMN room_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER table_id",
        "ALTER TABLE " . menuqr_table('orders') . " ADD COLUMN order_source VARCHAR(30) NOT NULL DEFAULT 'table_qr' AFTER room_id",
        "ALTER TABLE " . menuqr_table('orders') . " ADD COLUMN table_number VARCHAR(50) NULL AFTER table_id",
        "ALTER TABLE " . menuqr_table('orders') . " ADD COLUMN room_number VARCHAR(50) NULL AFTER room_id",
        "ALTER TABLE " . menuqr_table('bill_sessions') . " ADD COLUMN room_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER table_id",
        "ALTER TABLE " . menuqr_table('bill_sessions') . " ADD COLUMN order_source VARCHAR(30) NOT NULL DEFAULT 'table_qr' AFTER room_id",
        "ALTER TABLE " . menuqr_table('bill_sessions') . " ADD COLUMN table_number VARCHAR(50) NULL AFTER table_id",
        "ALTER TABLE " . menuqr_table('bill_sessions') . " ADD COLUMN room_number VARCHAR(50) NULL AFTER room_id",
        "ALTER TABLE " . menuqr_table('bills') . " ADD COLUMN room_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER table_id",
        "ALTER TABLE " . menuqr_table('bills') . " ADD COLUMN order_source VARCHAR(30) NOT NULL DEFAULT 'table_qr' AFTER room_id",
        "ALTER TABLE " . menuqr_table('bills') . " ADD COLUMN table_number VARCHAR(50) NULL AFTER table_id",
        "ALTER TABLE " . menuqr_table('bills') . " ADD COLUMN room_number VARCHAR(50) NULL AFTER room_id",
    ];

    foreach ($queries as $query) {
        $wpdb->query($query);
    }
}
