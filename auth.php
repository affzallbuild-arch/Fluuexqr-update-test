<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_handle_login(): void {
    if (is_user_logged_in()) {
        wp_safe_redirect(menuqr_get_dashboard_url());
        exit;
    }

    if (!isset($_POST['menuqr_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['menuqr_login_nonce'])), 'menuqr_login_action')) {
        wp_safe_redirect(add_query_arg('login', 'failed', menuqr_get_page_url_by_slug('login')));
        exit;
    }

    $credentials = [
        'user_login'    => sanitize_email(wp_unslash($_POST['email'] ?? '')),
        'user_password' => wp_unslash($_POST['password'] ?? ''),
        'remember'      => true,
    ];

    $user = wp_signon($credentials, false);

    if (is_wp_error($user)) {
        wp_safe_redirect(add_query_arg('login', 'failed', menuqr_get_page_url_by_slug('login')));
        exit;
    }

    menuqr_sync_user_role_context((int) $user->ID);
    wp_set_current_user((int) $user->ID);
    wp_set_auth_cookie((int) $user->ID, true);

    wp_safe_redirect(menuqr_get_user_front_dashboard_url_for_user(get_userdata((int) $user->ID)));
    exit;
}
add_action('admin_post_nopriv_menuqr_login', 'menuqr_handle_login');
add_action('admin_post_menuqr_login', 'menuqr_handle_login');

function menuqr_handle_signup(): void {
    $nonce = '';
    if (isset($_POST['menuqr_signup_nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['menuqr_signup_nonce']));
    } elseif (isset($_POST['_wpnonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce']));
    }

    if (!$nonce || !wp_verify_nonce($nonce, 'menuqr_signup_action')) {
        $redirect = menuqr_get_page_url_by_slug('signup');
        if (!empty($_POST['_wp_http_referer'])) {
            $redirect = esc_url_raw(wp_unslash($_POST['_wp_http_referer']));
        }
        wp_safe_redirect(add_query_arg('signup_error', 'invalid_request', $redirect));
        exit;
    }

    global $wpdb;
    $restaurants_table   = menuqr_table('restaurants');
    $plans_table         = menuqr_table('subscription_plans');
    $subscriptions_table = menuqr_table('subscriptions');

    $email           = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $password        = (string) wp_unslash($_POST['password'] ?? '');
    $restaurant_name = sanitize_text_field(wp_unslash($_POST['restaurant_name'] ?? ''));
    $owner_name      = sanitize_text_field(wp_unslash($_POST['owner_name'] ?? ''));
    $phone           = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $address         = sanitize_textarea_field(wp_unslash($_POST['address'] ?? ''));
    $plan_id         = absint($_POST['plan_id'] ?? 0);
    if ($plan_id <= 0 && function_exists('menuqr_get_plan_by_slug')) {
        $free_trial_plan = menuqr_get_plan_by_slug('free_trial');
        $plan_id = $free_trial_plan ? (int) $free_trial_plan->id : 0;
    }

    $settings = menuqr_platform_settings();
    if (empty($settings['allow_restaurant_signup'])) {
        wp_safe_redirect(add_query_arg('signup_error', 'closed', menuqr_get_page_url_by_slug('signup')));
        exit;
    }

    if (!$restaurant_name || !$owner_name || !$email || !$password || !$plan_id || !$phone || !$address) {
        wp_safe_redirect(add_query_arg('signup_error', 'missing_fields', menuqr_get_page_url_by_slug('signup')));
        exit;
    }

    if (strlen($password) < 8) {
        wp_safe_redirect(add_query_arg('signup_error', 'weak_password', menuqr_get_page_url_by_slug('signup')));
        exit;
    }

    if (email_exists($email) || username_exists($email)) {
        wp_safe_redirect(add_query_arg('signup_error', 'exists', menuqr_get_page_url_by_slug('signup')));
        exit;
    }

    $user_id = wp_create_user($email, $password, $email);
    if (is_wp_error($user_id)) {
        wp_safe_redirect(add_query_arg('signup_error', rawurlencode($user_id->get_error_code()), menuqr_get_page_url_by_slug('signup')));
        exit;
    }

    $user = new WP_User($user_id);
    $user->set_role('restaurant_admin');

    $now = current_time('mysql');
    $slug = sanitize_title($restaurant_name);
    $wpdb->insert($restaurants_table, [
        'wp_user_id'         => $user_id,
        'name'               => $restaurant_name,
        'slug'               => $slug . '-' . wp_generate_password(4, false, false),
        'owner_name'         => $owner_name,
        'email'              => $email,
        'phone'              => $phone,
        'address'            => $address,
        'approval_status'    => 'pending',
        'status'             => 'active',
        'subscription_status'=> 'pending',
        'created_at'         => $now,
        'updated_at'         => $now,
    ]);
    $restaurant_id = (int) $wpdb->insert_id;
    update_user_meta($user_id, 'menuqr_restaurant_id', $restaurant_id);

    $plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$plans_table} WHERE id = %d", $plan_id));
    if ($plan) {
        $starts = current_time('mysql');
        $expires = gmdate('Y-m-d H:i:s', strtotime('+' . (int) $plan->billing_days . ' days', current_time('timestamp', true)));
        $is_free_trial = ((string) $plan->slug === 'free_trial') || (float) $plan->price <= 0;
        $wpdb->insert($subscriptions_table, [
            'restaurant_id'   => $restaurant_id,
            'plan_id'         => $plan_id,
            'starts_at'       => $starts,
            'expires_at'      => $expires,
            'status'          => $is_free_trial ? 'trial' : 'pending',
            'payment_status'  => $is_free_trial ? 'paid' : 'pending',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        if ($is_free_trial) {
            $wpdb->update($restaurants_table, [
                'subscription_status' => 'active',
                'updated_at' => $now,
            ], ['id' => $restaurant_id]);
        }
    }

    wp_safe_redirect(add_query_arg('signup', 'success', menuqr_get_page_url_by_slug('login')));
    exit;
}
add_action('admin_post_nopriv_menuqr_signup', 'menuqr_handle_signup');
add_action('admin_post_menuqr_signup', 'menuqr_handle_signup');

function menuqr_handle_signup_page_post(): void {
    if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
        return;
    }

    $form = sanitize_key(wp_unslash($_POST['menuqr_form'] ?? ''));
    if ('signup' !== $form) {
        return;
    }

    menuqr_handle_signup();
}
add_action('init', 'menuqr_handle_signup_page_post');


function menuqr_get_user_front_dashboard_url_for_user(?WP_User $user = null): string {
    $user = $user ?: wp_get_current_user();
    if (!$user || empty($user->ID)) {
        return home_url('/');
    }

    menuqr_sync_user_role_context((int) $user->ID);
    $user = get_userdata((int) $user->ID);

    if (!$user) {
        return home_url('/');
    }

    if (current_user_can('manage_options') || in_array('super_admin', (array) $user->roles, true) || in_array('administrator', (array) $user->roles, true)) {
        return menuqr_get_page_url_by_slug('super-admin-dashboard');
    }
    if (in_array('staff', (array) $user->roles, true) || menuqr_user_matches_staff_record($user)) {
        if (function_exists('fqx_v167_staff_default_url_for_user')) {
            return fqx_v167_staff_default_url_for_user($user);
        }
        return menuqr_get_page_url_by_slug('kitchen-dashboard');
    }
    return menuqr_get_page_url_by_slug('restaurant-dashboard');
}

function menuqr_filter_login_redirect(string $redirect_to, string $requested_redirect_to, WP_User|WP_Error $user): string {
    if ($user instanceof WP_Error || !$user instanceof WP_User) {
        return $redirect_to;
    }

    $roles = (array) $user->roles;
    if (in_array('restaurant_admin', $roles, true) || in_array('staff', $roles, true) || in_array('super_admin', $roles, true) || in_array('administrator', $roles, true) || user_can($user, 'manage_options') || menuqr_user_matches_staff_record($user)) {
        menuqr_sync_user_role_context((int) $user->ID);
        return menuqr_get_user_front_dashboard_url_for_user(get_userdata((int) $user->ID));
    }

    return $redirect_to;
}
add_filter('login_redirect', 'menuqr_filter_login_redirect', 10, 3);


function menuqr_after_wp_login(string $user_login, WP_User $user): void {
    menuqr_sync_user_role_context((int) $user->ID);
}
add_action('wp_login', 'menuqr_after_wp_login', 10, 2);

function menuqr_redirect_logged_in_auth_pages(): void {
    if (!is_user_logged_in()) {
        return;
    }

    if (!is_page()) {
        return;
    }

    global $post;
    if (!$post instanceof WP_Post) {
        return;
    }

    if (in_array($post->post_name, ['login', 'signup'], true)) {
        wp_safe_redirect(menuqr_get_dashboard_url());
        exit;
    }
}
add_action('template_redirect', 'menuqr_redirect_logged_in_auth_pages', 1);



function menuqr_redirect_non_admins_from_backend(): void {
    if (!is_user_logged_in() || !is_admin()) {
        return;
    }

    if (wp_doing_ajax()) {
        return;
    }

    global $pagenow;
    if (in_array($pagenow, ['admin-post.php', 'async-upload.php', 'admin-ajax.php'], true)) {
        return;
    }

    $user = wp_get_current_user();
    if (!$user || empty($user->ID)) {
        return;
    }

    if (menuqr_user_has_role('super_admin') || current_user_can('manage_options')) {
        return;
    }

    $roles = (array) $user->roles;
    if (!array_intersect($roles, ['restaurant_admin', 'staff']) && !menuqr_user_matches_staff_record($user)) {
        return;
    }

    menuqr_sync_user_role_context((int) $user->ID);
    wp_safe_redirect(menuqr_get_user_front_dashboard_url_for_user(get_userdata((int) $user->ID)));
    exit;
}
add_action('admin_init', 'menuqr_redirect_non_admins_from_backend', 1);

function menuqr_hide_admin_bar_for_panel_users(bool $show): bool {
    if (!is_user_logged_in()) {
        return $show;
    }
    if (menuqr_user_has_role('super_admin') || current_user_can('manage_options')) {
        return $show;
    }
    $user = wp_get_current_user();
    $roles = (array) ($user->roles ?? []);
    if (array_intersect($roles, ['restaurant_admin', 'staff'])) {
        return false;
    }
    return $show;
}
add_filter('show_admin_bar', 'menuqr_hide_admin_bar_for_panel_users');
