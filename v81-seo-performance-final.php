<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v81 final SEO + performance layer.
 * Goals:
 * - Reduce theme CSS/JS requests by using bundled assets.
 * - Keep meta descriptions below 160 chars.
 * - Set focus keywords on all pages/posts and align primary keyword with title.
 * - Remove non-critical WordPress/plugin assets where safe.
 */

function fluuexqr_v81_trim_meta_description(string $description): string {
    $description = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($description)));
    if (mb_strlen($description) <= 155) { return $description; }
    return rtrim(mb_substr($description, 0, 154), " ,.-") . '.';
}

function fluuexqr_v81_page_title_map(): array {
    return [
        'home' => 'Restaurant QR Menu Software Home',
        'features' => 'Restaurant QR Ordering Features',
        'services' => 'Restaurant Hotel QR Ordering Services',
        'pricing' => 'Restaurant QR Menu Pricing',
        'demo' => 'Restaurant QR Ordering Demo',
        'about-us' => 'About FluuexQR Restaurant QR Platform',
        'about' => 'About FluuexQR Restaurant QR Platform',
        'contact-us' => 'Restaurant QR Menu Demo Contact',
        'contact' => 'Restaurant QR Menu Demo Contact',
        'blogs' => 'Restaurant Technology Blog',
        'blog' => 'Restaurant Technology Blog',
        'faq' => 'Restaurant QR Menu FAQ',
        'support' => 'Restaurant QR Ordering Support',
        'login' => 'FluuexQR Login Dashboard',
        'signup' => 'FluuexQR Signup',
        'start-free-trial' => 'Restaurant QR Menu Free Trial',
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
}

function fluuexqr_v81_description_for_keyword(string $keyword): string {
    $desc = $keyword . ' by FluuexQR with QR ordering, room QR, kitchen display, billing, delivery, staff and reports for restaurants and hotels.';
    return fluuexqr_v81_trim_meta_description($desc);
}

function fluuexqr_v81_apply_seo_meta(): void {
    $version = '1.0.81';
    if (get_option('fluuexqr_v81_seo_meta_version') === $version) { return; }

    $title_map = fluuexqr_v81_page_title_map();
    $posts = get_posts([
        'post_type' => ['page', 'post'],
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'all',
    ]);

    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) { continue; }

        $slug = $post->post_name ?: sanitize_title($post->post_title);
        $new_title = $title_map[$slug] ?? trim(wp_strip_all_tags($post->post_title));
        if ($new_title === '') { $new_title = 'Restaurant QR Menu Software'; }

        // Update marketing page titles so primary keyword appears in the visible post title.
        if ($post->post_type === 'page' && isset($title_map[$slug]) && $post->post_title !== $new_title) {
            wp_update_post(['ID' => $post->ID, 'post_title' => $new_title]);
        }

        // Primary keyword intentionally mirrors the title so SEO audits pass title-keyword checks.
        $keyword = $new_title;
        $desc = fluuexqr_v81_description_for_keyword($keyword);

        update_post_meta($post->ID, 'rank_math_focus_keyword', $keyword);
        update_post_meta($post->ID, 'rank_math_description', $desc);
        update_post_meta($post->ID, 'rank_math_title', $new_title . ' | FluuexQR');

        update_post_meta($post->ID, '_yoast_wpseo_focuskw', $keyword);
        update_post_meta($post->ID, '_yoast_wpseo_metadesc', $desc);
        update_post_meta($post->ID, '_yoast_wpseo_title', '%%title%% | FluuexQR');

        update_post_meta($post->ID, '_seopress_analysis_target_kw', $keyword);
        update_post_meta($post->ID, '_seopress_titles_title', $new_title . ' | FluuexQR');
        update_post_meta($post->ID, '_seopress_titles_desc', $desc);
        update_post_meta($post->ID, '_aioseo_keywords', $keyword);
        update_post_meta($post->ID, '_aioseo_description', $desc);
    }

    update_option('fluuexqr_v81_seo_meta_version', $version, false);
}
add_action('init', 'fluuexqr_v81_apply_seo_meta', 80);
add_action('after_switch_theme', 'fluuexqr_v81_apply_seo_meta', 80);

// Remove common unused WordPress frontend assets.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');

function fluuexqr_v81_dequeue_extra_assets(): void {
    if (is_admin()) { return; }

    $remove_styles = [
        'wp-block-library','wp-block-library-theme','global-styles','classic-theme-styles',
        'dashicons','contact-form-7','rank-math','yoast-seo-adminbar','seopress-admin-bar',
    ];
    foreach ($remove_styles as $handle) {
        if ($handle === 'dashicons' && is_user_logged_in()) { continue; }
        wp_dequeue_style($handle);
        wp_deregister_style($handle);
    }

    // Keep Contact Form 7 scripts only where the form is visible.
    if (!is_page(['contact','contact-us','start-free-trial'])) {
        foreach (['contact-form-7','wpcf7-recaptcha','google-recaptcha'] as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }

    wp_dequeue_script('wp-embed');
}
add_action('wp_enqueue_scripts', 'fluuexqr_v81_dequeue_extra_assets', 999);

function fluuexqr_v81_resource_preloads(): void {
    if (is_admin()) { return; }
    echo '<link rel="preload" href="' . esc_url(MENUQR_THEME_URI . '/assets/css/fluuexqr-v81-bundle.min.css') . '" as="style">' . "\n";
}
add_action('wp_head', 'fluuexqr_v81_resource_preloads', 1);
