<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_seed_demo_data(): void {
    global $wpdb;

    $now = current_time('mysql');
    $timestamp = current_time('timestamp', true);

    $super_email = 'admin@menuqr.com';
    $rest_email  = 'raj@spiceroute.com';
    $staff_email = 'amit@spiceroute.com';

    $super_id = email_exists($super_email);
    if (!$super_id) {
        $super_id = wp_create_user($super_email, 'admin123', $super_email);
    }
    if (!is_wp_error($super_id) && $super_id) {
        (new WP_User((int) $super_id))->set_role('super_admin');
    }

    $rest_admin_id = email_exists($rest_email);
    if (!$rest_admin_id) {
        $rest_admin_id = wp_create_user($rest_email, 'rest123', $rest_email);
    }
    if (!is_wp_error($rest_admin_id) && $rest_admin_id) {
        (new WP_User((int) $rest_admin_id))->set_role('restaurant_admin');
    }

    $staff_id = email_exists($staff_email);
    if (!$staff_id) {
        $staff_id = wp_create_user($staff_email, 'kitchen123', $staff_email);
    }
    if (!is_wp_error($staff_id) && $staff_id) {
        (new WP_User((int) $staff_id))->set_role('staff');
    }

    $restaurants_table = menuqr_table('restaurants');
    $restaurant_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$restaurants_table} WHERE wp_user_id = %d OR email = %s ORDER BY id ASC LIMIT 1",
        (int) $rest_admin_id,
        $rest_email
    ));

    if ($restaurant_id <= 0) {
        $wpdb->insert($restaurants_table, [
            'wp_user_id'          => (int) $rest_admin_id,
            'name'                => 'The Spice Route',
            'slug'                => 'the-spice-route',
            'owner_name'          => 'Raj Sharma',
            'email'               => $rest_email,
            'phone'               => '9876543210',
            'address'             => '12 MG Road, Bangalore',
            'approval_status'     => 'demo',
            'status'              => 'active',
            'subscription_status' => 'active',
            'google_reviews_enabled' => 1,
            'google_review_link' => 'https://www.google.com/search?q=FluuexQR+restaurant+review',
            'review_button_text' => 'Review Restaurant',
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
        $restaurant_id = (int) $wpdb->insert_id;
    } else {
        $wpdb->update($restaurants_table, [
            'wp_user_id'          => (int) $rest_admin_id,
            'name'                => 'The Spice Route',
            'owner_name'          => 'Raj Sharma',
            'email'               => $rest_email,
            'phone'               => '9876543210',
            'address'             => '12 MG Road, Bangalore',
            'approval_status'     => 'demo',
            'status'              => 'active',
            'subscription_status' => 'active',
            'google_reviews_enabled' => 1,
            'google_review_link' => 'https://www.google.com/search?q=FluuexQR+restaurant+review',
            'review_button_text' => 'Review Restaurant',
            'updated_at'          => $now,
        ], ['id' => $restaurant_id]);
    }

    update_user_meta((int) $rest_admin_id, 'menuqr_restaurant_id', $restaurant_id);
    update_user_meta((int) $staff_id, 'menuqr_restaurant_id', $restaurant_id);

    $plans_table = menuqr_table('subscription_plans');
    $plans = [];
    if (function_exists('menuqr_plan_matrix')) {
        foreach (menuqr_plan_matrix() as $slug => $config) {
            $plans[] = [
                $config['name'],
                $slug,
                $config['price'],
                $config['billing_days'],
                $config,
                $config['description'],
            ];
        }
    } else {
        $plans = [
            ['Free Trial', 'free_trial', 0, 15, ['All features trial'], '15-day full-feature trial'],
            ['Basic', 'basic', 999, 30, ['QR ordering', 'Kitchen', 'Basic billing'], 'For small restaurants'],
            ['Premium', 'premium', 1999, 30, ['Full automation'], 'For growing restaurants'],
            ['Yearly Pro', 'yearly_pro', 29999, 365, ['2 restaurants', 'All features'], 'Best yearly value'],
        ];
    }
    foreach ($plans as $plan) {
        $existing_plan_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$plans_table} WHERE slug = %s ORDER BY id ASC LIMIT 1",
            $plan[1]
        ));
        $payload = [
            'name'         => $plan[0],
            'slug'         => $plan[1],
            'price'        => $plan[2],
            'billing_days' => $plan[3],
            'features'     => wp_json_encode($plan[4]),
            'description'  => $plan[5],
            'status'       => 'active',
            'updated_at'   => $now,
        ];
        if ($existing_plan_id > 0) {
            $wpdb->update($plans_table, $payload, ['id' => $existing_plan_id]);
        } else {
            $payload['created_at'] = $now;
            $wpdb->insert($plans_table, $payload);
        }
    }

    $premium_plan_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$plans_table} WHERE slug = %s ORDER BY id ASC LIMIT 1",
        'premium'
    ));

    $subscriptions_table = menuqr_table('subscriptions');
    $existing_subscription_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$subscriptions_table} WHERE restaurant_id = %d ORDER BY id DESC LIMIT 1",
        $restaurant_id
    ));
    $subscription_payload = [
        'restaurant_id'  => $restaurant_id,
        'plan_id'        => $premium_plan_id ?: 3,
        'starts_at'      => $now,
        'expires_at'     => gmdate('Y-m-d H:i:s', strtotime('+60 days', $timestamp)),
        'status'         => 'active',
        'payment_status' => 'paid',
        'updated_at'     => $now,
    ];
    if ($existing_subscription_id > 0) {
        $wpdb->update($subscriptions_table, $subscription_payload, ['id' => $existing_subscription_id]);
    } else {
        $subscription_payload['created_at'] = $now;
        $wpdb->insert($subscriptions_table, $subscription_payload);
    }

    $payments_table = menuqr_table('payment_settings');
    $existing_payment_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$payments_table} WHERE restaurant_id = %d LIMIT 1",
        $restaurant_id
    ));
    $payment_payload = [
        'restaurant_id'          => $restaurant_id,
        'cash_enabled'           => 1,
        'upi_enabled'            => 1,
        'upi_id'                 => 'spiceroute@upi',
        'upi_qr'                 => '',
        'screenshot_enabled'     => 1,
        'online_enabled'         => 1,
        'razorpay_key'           => 'rzp_test_demo',
        'razorpay_secret'        => 'demo_secret',
        'stripe_publishable_key' => '',
        'stripe_secret_key'      => '',
        'updated_at'             => $now,
    ];
    if ($existing_payment_id > 0) {
        $wpdb->update($payments_table, $payment_payload, ['id' => $existing_payment_id]);
    } else {
        $payment_payload['created_at'] = $now;
        $wpdb->insert($payments_table, $payment_payload);
    }

    $categories_table = menuqr_table('categories');
    $categories = ['Starters', 'Main Course', 'Breads', 'Drinks', 'Desserts'];
    $category_ids = [];
    foreach ($categories as $i => $name) {
        $existing_category_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$categories_table} WHERE restaurant_id = %d AND name = %s ORDER BY id ASC LIMIT 1",
            $restaurant_id,
            $name
        ));
        $category_payload = [
            'restaurant_id' => $restaurant_id,
            'name'          => $name,
            'description'   => $name . ' category',
            'sort_order'    => $i + 1,
            'updated_at'    => $now,
        ];
        if ($existing_category_id > 0) {
            $wpdb->update($categories_table, $category_payload, ['id' => $existing_category_id]);
            $category_ids[] = $existing_category_id;
        } else {
            $category_payload['created_at'] = $now;
            $wpdb->insert($categories_table, $category_payload);
            $category_ids[] = (int) $wpdb->insert_id;
        }
    }

    $items_table = menuqr_table('items');
    $items = [
        ['Paneer Tikka', 'Marinated cottage cheese grilled in tandoor', 260, '🍢', 0],
        ['Veg Spring Roll', 'Crispy rolls with fresh vegetable filling', 180, '🥢', 0],
        ['Butter Chicken', 'Tender chicken in rich creamy tomato gravy', 380, '🍛', 1],
        ['Chicken Biryani', 'Fragrant basmati rice with whole spices', 450, '🍚', 1],
        ['Palak Paneer', 'Cottage cheese in spinach gravy', 300, '🥬', 1],
        ['Garlic Naan', 'Soft bread with garlic butter', 60, '🫓', 2],
        ['Tandoori Roti', 'Whole wheat bread from tandoor', 40, '🫓', 2],
        ['Mango Lassi', 'Fresh mango blended with yogurt', 120, '🥭', 3],
        ['Masala Chai', 'Traditional spiced Indian tea', 60, '☕', 3],
        ['Gulab Jamun', 'Soft milk dumplings in sugar syrup', 120, '🍮', 4],
    ];
    foreach ($items as $i => $item) {
        $existing_item_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$items_table} WHERE restaurant_id = %d AND name = %s ORDER BY id ASC LIMIT 1",
            $restaurant_id,
            $item[0]
        ));
        $item_payload = [
            'restaurant_id'        => $restaurant_id,
            'category_id'          => $category_ids[$item[4]] ?? $category_ids[0],
            'name'                 => $item[0],
            'description'          => $item[1],
            'price'                => $item[2],
            'tax_rate'             => 5,
            'service_charge_rate'  => 0,
            'emoji'                => $item[3],
            'variants'             => wp_json_encode(['Regular', 'Large']),
            'addons'               => wp_json_encode(['Extra Cheese', 'Less Spicy']),
            'is_available'         => 1,
            'is_featured'          => $i < 3 ? 1 : 0,
            'updated_at'           => $now,
        ];
        if ($existing_item_id > 0) {
            $wpdb->update($items_table, $item_payload, ['id' => $existing_item_id]);
        } else {
            $item_payload['created_at'] = $now;
            $wpdb->insert($items_table, $item_payload);
        }
    }

    $tables_table = menuqr_table('tables');
    $table_ids = [];
    foreach (range(1, 5) as $num) {
        $existing_table_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tables_table} WHERE restaurant_id = %d AND table_number = %s ORDER BY id ASC LIMIT 1",
            $restaurant_id,
            (string) $num
        ));
        $table_payload = [
            'restaurant_id' => $restaurant_id,
            'table_number'  => (string) $num,
            'capacity'      => $num < 3 ? 2 : 4,
            'updated_at'    => $now,
        ];
        if ($existing_table_id > 0) {
            $wpdb->update($tables_table, $table_payload, ['id' => $existing_table_id]);
            $table_ids[] = $existing_table_id;
        } else {
            $table_payload['qr_token'] = menuqr_generate_qr_token();
            $table_payload['created_at'] = $now;
            $wpdb->insert($tables_table, $table_payload);
            $table_ids[] = (int) $wpdb->insert_id;
        }
    }

    $staff_table = menuqr_table('staff');
    $existing_staff_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$staff_table} WHERE restaurant_id = %d AND (wp_user_id = %d OR email = %s) ORDER BY id ASC LIMIT 1",
        $restaurant_id,
        (int) $staff_id,
        $staff_email
    ));
    $staff_payload = [
        'restaurant_id' => $restaurant_id,
        'wp_user_id'    => (int) $staff_id,
        'name'          => 'Amit Kumar',
        'email'         => $staff_email,
        'phone'         => '9111222333',
        'role_name'     => 'kitchen',
        'status'        => 'active',
        'updated_at'    => $now,
    ];
    if ($existing_staff_id > 0) {
        $wpdb->update($staff_table, $staff_payload, ['id' => $existing_staff_id]);
    } else {
        $staff_payload['created_at'] = $now;
        $wpdb->insert($staff_table, $staff_payload);
    }

    $orders_table = menuqr_table('orders');
    $existing_order_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$orders_table} WHERE restaurant_id = %d",
        $restaurant_id
    ));
    if ($existing_order_count < 3) {
        $order_items = [
            [
                ['item_id' => 1, 'name' => 'Paneer Tikka', 'price' => 260, 'qty' => 1, 'emoji' => '🍢'],
                ['item_id' => 3, 'name' => 'Butter Chicken', 'price' => 380, 'qty' => 1, 'emoji' => '🍛'],
            ],
            [
                ['item_id' => 4, 'name' => 'Chicken Biryani', 'price' => 450, 'qty' => 2, 'emoji' => '🍚'],
                ['item_id' => 6, 'name' => 'Garlic Naan', 'price' => 60, 'qty' => 4, 'emoji' => '🫓'],
            ],
            [
                ['item_id' => 8, 'name' => 'Mango Lassi', 'price' => 120, 'qty' => 2, 'emoji' => '🥭'],
                ['item_id' => 10, 'name' => 'Gulab Jamun', 'price' => 120, 'qty' => 2, 'emoji' => '🍮'],
            ],
        ];

        foreach ($order_items as $i => $order_set) {
            $subtotal = 0;
            foreach ($order_set as $entry) {
                $subtotal += $entry['price'] * $entry['qty'];
            }
            $tax = round($subtotal * 0.05, 2);
            $total = $subtotal + $tax;
            $created = gmdate('Y-m-d H:i:s', strtotime('-' . (3 - $i) . ' hours', $timestamp));
            $wpdb->insert($orders_table, [
                'restaurant_id'      => $restaurant_id,
                'table_id'           => $table_ids[$i] ?? $table_ids[0],
                'unique_code'        => 'DEMO-' . wp_generate_password(6, false, false),
                'items_json'         => wp_json_encode($order_set),
                'subtotal'           => $subtotal,
                'tax'                => $tax,
                'service_charge'     => 0,
                'final_total'        => $total,
                'payment_method'     => $i === 1 ? 'upi' : 'cash',
                'payment_status'     => $i === 1 ? 'paid' : 'unpaid',
                'order_status'       => ['served', 'preparing', 'pending'][$i],
                'payment_reference'  => $i === 1 ? 'UPI123456' : '',
                'customer_note'      => $i === 2 ? 'Less spicy please' : '',
                'duplicate_key'      => md5($restaurant_id . '|' . ($table_ids[$i] ?? 0) . '|' . wp_json_encode($order_set)),
                'created_at'         => $created,
                'updated_at'         => $created,
            ]);
        }
    }

    menuqr_repair_user_restaurant_context((int) $rest_admin_id);
    menuqr_repair_user_restaurant_context((int) $staff_id);
}
