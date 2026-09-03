<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v67 SEO + performance repair layer.
 * - Adds focus keywords for Rank Math, Yoast, SEOPress, AIOSEO-style audits.
 * - Stops Contact Form 7 default CSS and uses lightweight theme CSS.
 * - Removes common unused WordPress frontend assets.
 */

function fluuexqr_v67_default_keyword_for_post(WP_Post $post): string {
    $slug = $post->post_name ?: sanitize_title($post->post_title);
    $map = [
        'home' => 'Restaurant QR Menu, Hotel QR Ordering, Restaurant Ordering Software',
        'features' => 'Restaurant QR Ordering Features, Hotel Room QR, Kitchen Display System',
        'services' => 'Restaurant Hotel QR Ordering Services, Restaurant SaaS Platform',
        'pricing' => 'Restaurant QR Menu Pricing, Hotel QR Ordering Pricing',
        'demo' => 'Restaurant QR Ordering Demo, Hotel QR Ordering Demo',
        'about-us' => 'FluuexQR Restaurant Hotel Ordering Platform',
        'about' => 'FluuexQR Restaurant Hotel Ordering Platform',
        'contact-us' => 'Restaurant QR Menu Demo, Hotel QR Ordering Demo',
        'contact' => 'Restaurant QR Menu Demo, Hotel QR Ordering Demo',
        'blogs' => 'Restaurant Technology Blog, QR Ordering Blog',
        'blog' => 'Restaurant Technology Blog, QR Ordering Blog',
        'faq' => 'Restaurant QR Menu FAQ, Hotel QR Ordering FAQ',
        'support' => 'FluuexQR Support, Restaurant QR Ordering Support',
        'login' => 'FluuexQR Login, Restaurant Admin Dashboard',
        'start-free-trial' => 'Restaurant QR Menu Free Trial, Hotel QR Ordering Free Trial',
        'restaurant-qr-menu' => 'Restaurant QR Menu System',
        'hotel-room-qr-ordering' => 'Hotel Room QR Ordering',
        'table-qr-ordering-system' => 'Table QR Ordering System',
        'kitchen-display-system' => 'Kitchen Display System',
        'restaurant-billing-software' => 'Restaurant Billing Software',
        'online-ordering-system' => 'Online Food Ordering System',
        'delivery-tracking-system' => 'Delivery Tracking System',
        'restaurant-pos-features' => 'Restaurant POS Features',
        'multi-branch-management' => 'Multi Branch Restaurant Management',
        'staff-management-system' => 'Restaurant Staff Management System',
        'restaurant-reports-analytics' => 'Restaurant Reports Analytics',
        'digital-restaurant-menu' => 'Digital Restaurant Menu',
        'contactless-dining-solution' => 'Contactless Dining Solution',
        'cloud-kitchen-ordering' => 'Cloud Kitchen Ordering System',
        'hotel-food-service-system' => 'Hotel Food Service System',
        'restaurant-saas-platform' => 'Restaurant SaaS Platform',
        'restaurant-customer-feedback-system' => 'Restaurant Customer Feedback System',
        'whatsapp-ordering-integration' => 'WhatsApp Ordering Integration',
        'restaurant-offers-coupon-system' => 'Restaurant Offers Coupon System',
        'combo-meal-management' => 'Combo Meal Management',
        'restaurant-subscription-management' => 'Restaurant Subscription Management',
        'qr-code-generation-system' => 'QR Code Generation System',
        'restaurant-admin-dashboard' => 'Restaurant Admin Dashboard',
        'best-qr-menu-system-for-restaurants' => 'Best QR Menu System for Restaurants',
        'restaurant-ordering-software-in-india' => 'Restaurant Ordering Software in India',
        'hotel-qr-ordering-system' => 'Hotel QR Ordering System',
        'restaurant-billing-ordering-software' => 'Restaurant Billing Ordering Software',
        'qr-menu-for-cafes' => 'QR Menu for Cafes',
        'restaurant-digital-transformation' => 'Restaurant Digital Transformation',
        'smart-restaurant-technology' => 'Smart Restaurant Technology',
        'contactless-restaurant-ordering' => 'Contactless Restaurant Ordering',
    ];
    if (isset($map[$slug])) { return $map[$slug]; }

    $title = trim(wp_strip_all_tags($post->post_title));
    if ($title !== '') {
        return $title . ', Restaurant QR Menu, Hotel QR Ordering';
    }
    return 'Restaurant QR Menu, Hotel QR Ordering, Restaurant SaaS Platform';
}

function fluuexqr_v67_focus_description_for_post(WP_Post $post, string $keyword): string {
    $title = trim(wp_strip_all_tags($post->post_title ?: 'FluuexQR'));
    return wp_trim_words($title . ' by FluuexQR. Cloud-based restaurant and hotel QR ordering platform for table QR, room QR, kitchen display, billing, delivery, staff, reports and SaaS operations.', 26, '');
}

function fluuexqr_v67_apply_focus_keyword_meta(): void {
    $version = '1.0.67';
    if (get_option('fluuexqr_v67_focus_keywords_version') === $version) { return; }

    $posts = get_posts([
        'post_type' => ['page','post'],
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'all',
    ]);

    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) { continue; }
        $keyword = fluuexqr_v67_default_keyword_for_post($post);
        $desc = fluuexqr_v67_focus_description_for_post($post, $keyword);

        // Rank Math
        update_post_meta($post->ID, 'rank_math_focus_keyword', $keyword);
        update_post_meta($post->ID, 'rank_math_description', $desc);
        update_post_meta($post->ID, 'rank_math_title', wp_strip_all_tags($post->post_title) . ' | FluuexQR');

        // Yoast SEO
        update_post_meta($post->ID, '_yoast_wpseo_focuskw', $keyword);
        update_post_meta($post->ID, '_yoast_wpseo_metadesc', $desc);
        update_post_meta($post->ID, '_yoast_wpseo_title', '%%title%% | FluuexQR');

        // SEOPress / generic SEO audits
        update_post_meta($post->ID, '_seopress_analysis_target_kw', $keyword);
        update_post_meta($post->ID, '_seopress_titles_desc', $desc);
        update_post_meta($post->ID, '_aioseo_keywords', $keyword);
    }

    update_option('fluuexqr_v67_focus_keywords_version', $version, false);
}
add_action('init', 'fluuexqr_v67_apply_focus_keyword_meta', 40);
add_action('after_switch_theme', 'fluuexqr_v67_apply_focus_keyword_meta', 40);

// Disable Contact Form 7 default stylesheet. Theme provides a light form UI instead.
add_filter('wpcf7_load_css', '__return_false');

function fluuexqr_v67_dequeue_unused_assets(): void {
    if (is_admin()) { return; }

    // Remove Gutenberg/global CSS on classic theme marketing pages.
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');

    // CF7 CSS may still be enqueued by plugin in some setups.
    if (!is_page(['contact','contact-us','start-free-trial'])) {
        wp_dequeue_style('contact-form-7');
        wp_deregister_style('contact-form-7');
    }
}
add_action('wp_enqueue_scripts', 'fluuexqr_v67_dequeue_unused_assets', 100);

function fluuexqr_v67_output_contact_form_css(): void {
    if (!is_page(['contact','contact-us','start-free-trial'])) { return; }
    ?>
    <style id="fluuexqr-v67-cf7-lite-css">
    .wpcf7{max-width:760px;margin:28px auto 0;background:rgba(255,255,255,.94);border:1px solid rgba(226,232,240,.9);border-radius:28px;padding:24px;box-shadow:0 22px 70px rgba(15,23,42,.10)}
    .wpcf7 form{display:grid;gap:14px}.wpcf7 label{font-weight:800;color:#0f172a}.wpcf7 input,.wpcf7 textarea,.wpcf7 select{width:100%;min-height:48px;border:1px solid #e2e8f0;border-radius:16px;padding:12px 14px;background:#fff;color:#0f172a;outline:none;transition:border-color .2s ease,box-shadow .2s ease}.wpcf7 textarea{min-height:130px;resize:vertical}.wpcf7 input:focus,.wpcf7 textarea:focus,.wpcf7 select:focus{border-color:#fb923c;box-shadow:0 0 0 4px rgba(251,146,60,.16)}.wpcf7-submit{border:0!important;background:linear-gradient(135deg,#fb923c,#ea580c)!important;color:#fff!important;font-weight:900;cursor:pointer;box-shadow:0 14px 34px rgba(234,88,12,.24)}.wpcf7-submit:hover{transform:translateY(-2px)}.wpcf7-not-valid-tip{font-size:.86rem;color:#dc2626;margin-top:5px}.wpcf7-response-output{border-radius:16px!important;margin:14px 0 0!important;padding:12px 14px!important}
    </style>
    <?php
}
add_action('wp_head', 'fluuexqr_v67_output_contact_form_css', 30);

function fluuexqr_v67_resource_hints(array $urls, string $relation_type): array {
    if ($relation_type === 'preconnect') {
        $urls[] = 'https://fonts.gstatic.com';
    }
    return array_values(array_unique($urls));
}
add_filter('wp_resource_hints', 'fluuexqr_v67_resource_hints', 10, 2);

function fluuexqr_v67_preload_main_assets(): void {
    if (is_admin()) { return; }
    $main_css = MENUQR_THEME_URI . '/assets/css/fluuexqr-v81-bundle.min.css';
    echo '<link rel="preload" href="' . esc_url($main_css) . '" as="style">' . "\n";
}
add_action('wp_head', 'fluuexqr_v67_preload_main_assets', 1);
