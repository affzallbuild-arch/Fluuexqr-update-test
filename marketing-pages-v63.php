<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v63 complete marketing pages layer.
 * Ensures all requested pages exist with more attractive content and page-level UI.
 */

function fluuexqr_v63_services_index_content(): string {
    $cards = '';
    foreach (fluuexqr_v62_service_data() as $slug => $data) {
        $cards .= '<article class="fq-v63-link-card">'
            . '<div class="fq-v62-icon">' . esc_html($data[5] ?? '🚀') . '</div>'
            . '<h3>' . esc_html($data[0]) . '</h3>'
            . '<p>' . esc_html($data[3] ?? '') . '</p>'
            . '<a href="' . esc_url(fluuexqr_v62_page_url($slug)) . '">Open page →</a>'
            . '</article>';
    }

    return '<section class="fq-v62-hero simple"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Services</span><h1>Complete Restaurant & Hotel Service Pages</h1><p>Explore every FluuexQR service page for restaurant QR ordering, hotel room QR, table ordering, kitchen display, billing, delivery, reports, admin dashboard and SaaS subscription management.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="' . esc_url(fluuexqr_v62_trial_url()) . '">Start Free Trial</a><a class="btn btn-ghost" href="' . esc_url(fluuexqr_v62_page_url('contact-us')) . '">Book Live Demo</a></div></div></section>'
    . '<section class="fq-v62-section"><div class="fq-v62-section-head"><span>Service library</span><h2>Every main service has its own page</h2><p>Designed to help restaurant and hotel owners understand the value clearly and convert faster.</p></div><div class="fq-v62-feature-grid">' . $cards . '</div></section>';
}

function fluuexqr_v63_pricing_page_content(): string {
    return '<section class="fq-v62-hero"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Pricing</span><h1>Simple SaaS Pricing for Restaurants & Hotels</h1><p>Choose a plan based on your business size. Starter is ideal for trial, Basic is best for single restaurant operations, and Premium is perfect for multi-branch restaurants and hotels.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="' . esc_url(fluuexqr_v62_trial_url()) . '">Start Free Trial</a><a class="btn btn-ghost" href="' . esc_url(fluuexqr_v62_page_url('contact-us')) . '">Book Live Demo</a></div></div><div class="fq-v62-service-visual"><div class="fq-v62-dashboard-mock"><b>Monthly / Yearly</b><strong>Save 20%</strong><span>Switch billing for better savings</span></div><div class="fq-v62-live-card"><b>Popular Plan</b><span>Basic · Single Restaurant</span><em>Most Chosen</em></div></div></section>'
        . fluuexqr_v62_pricing_preview()
        . '<section class="fq-v62-section"><div class="fq-v62-section-head"><span>Plan highlights</span><h2>What comes inside each plan</h2></div><div class="fq-v62-dashboard-grid"><article class="fq-v62-dashboard-card"><h3>Starter</h3><p>Trial environment, product demo, and feature exploration.</p></article><article class="fq-v62-dashboard-card"><h3>Basic</h3><p>Single restaurant, menu, table QR, room QR, kitchen display, billing and reports.</p></article><article class="fq-v62-dashboard-card"><h3>Premium</h3><p>Multi branch, hotel workflow, delivery features, advanced analytics and unlimited scaling.</p></article><article class="fq-v62-dashboard-card"><h3>Support</h3><p>Setup guidance, menu onboarding and operational assistance.</p></article></div></section>'
        . '<section class="fq-v62-cta"><h2>Need a custom plan for hotel or multi-branch business?</h2><p>Talk to FluuexQR and get the right setup for restaurant QR ordering, room QR, kitchen operations, delivery and billing.</p><a class="btn btn-primary" href="' . esc_url(fluuexqr_v62_page_url('contact-us')) . '">Talk to Sales</a></section>';
}

function fluuexqr_v63_demo_page_content(): string {
    $shots = [
        ['Customer Ordering UI','Mobile-friendly QR menu ordering experience for table, room and online customers.'],
        ['Restaurant Dashboard','Orders, menu, tables, rooms, reports, billing and staff management in one panel.'],
        ['Kitchen Dashboard','Accept, preparing, ready and served workflow with live orders, room and table number.'],
        ['Billing Screen','Professional invoice and bill flow with restaurant details and item summary.'],
        ['QR Generation','Generate QR for tables, hotel rooms and ordering pages from admin.'],
        ['Delivery Tracking','Assign delivery boy and open navigation with Google Maps.'],
    ];
    $cards='';
    foreach ($shots as $s) {
        $cards .= '<article class="fq-v63-shot-card"><div class="fq-v63-shot-top"></div><h3>' . esc_html($s[0]) . '</h3><p>' . esc_html($s[1]) . '</p></article>';
    }
    return '<section class="fq-v62-hero"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Demo</span><h1>See FluuexQR in Action</h1><p>Show restaurant owners the customer ordering UI, admin dashboard, kitchen display, billing screen, QR generation, room ordering and delivery workflow.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="' . esc_url(menuqr_get_demo_menu_url()) . '">Open Demo Menu</a><a class="btn btn-ghost" href="' . esc_url(fluuexqr_v62_page_url('contact-us')) . '">Book Live Demo</a></div></div><div class="fq-v62-visual-panel"><div class="fq-v62-phone"><b>Customer Demo</b><span>2 burgers + 1 cold coffee</span><em>Checkout</em></div><div class="fq-v62-float-card one">Dashboard Preview</div><div class="fq-v62-float-card two">Delivery Tracking</div></div></section><section class="fq-v62-section"><div class="fq-v62-section-head"><span>Screenshots</span><h2>Important screens to show on your SaaS website</h2><p>These sections visually explain how the full FluuexQR system works.</p></div><div class="fq-v62-feature-grid">' . $cards . '</div></section>';
}

function fluuexqr_v63_about_page_content(): string {
    return '<section class="fq-v62-hero"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">About Us</span><h1>FluuexQR is built for modern restaurant & hotel operations</h1><p>FluuexQR is a cloud-based Restaurant & Hotel Digital Ordering SaaS Platform that helps restaurants, cafes, hotels, resorts, food courts, cloud kitchens, bars and lounges digitize ordering, kitchen operations, billing, delivery and customer experience.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="' . esc_url(fluuexqr_v62_trial_url()) . '">Start Free Trial</a><a class="btn btn-ghost" href="' . esc_url(fluuexqr_v62_page_url('services')) . '">Explore Services</a></div></div><div class="fq-v62-service-visual"><div class="fq-v62-visual-emoji">🚀</div><div class="fq-v62-live-card"><b>Built for</b><span>Restaurants · Hotels · Cafes</span><em>Cloud Based</em></div></div></section><section class="fq-v62-section"><div class="fq-v62-section-head"><span>Why FluuexQR</span><h2>One platform for ordering and operations</h2></div><div class="fq-v62-dashboard-grid"><article class="fq-v62-dashboard-card"><h3>Restaurant QR Ordering</h3><p>Turn manual ordering into fast digital ordering.</p></article><article class="fq-v62-dashboard-card"><h3>Hotel Room QR</h3><p>Room service ordering with room auto-detection.</p></article><article class="fq-v62-dashboard-card"><h3>Kitchen & Billing</h3><p>Manage kitchen flow and professional bills from one platform.</p></article><article class="fq-v62-dashboard-card"><h3>SaaS Growth</h3><p>Plans, subscriptions, analytics and scalable multi-branch support.</p></article></div></section>' . fluuexqr_v62_industry_cards();
}

function fluuexqr_v63_support_page_content(): string {
    return '<section class="fq-v62-hero simple"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Support</span><h1>Need help with FluuexQR setup?</h1><p>Get support for restaurant onboarding, menu upload, table and room QR, kitchen dashboard, billing, staff setup, reports and subscription management.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="' . esc_url(fluuexqr_v62_page_url('contact-us')) . '">Contact Support</a><a class="btn btn-ghost" href="' . esc_url(fluuexqr_v62_page_url('faq')) . '">Open FAQ</a></div></div></section><section class="fq-v62-section"><div class="fq-v62-contact-grid"><article><h3>Setup Help</h3><p>Restaurant details, menu import, table QR and room QR setup.</p></article><article><h3>Dashboard Help</h3><p>Admin dashboard, kitchen display, reports and billing guidance.</p></article><article><h3>Business Support</h3><p>Pricing, feature planning, demo support and custom requirements.</p></article></div></section>';
}

function fluuexqr_v63_login_page_content(): string {
    return '<section class="fq-v62-hero simple"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Login</span><h1>Login to FluuexQR</h1><p>Secure access for Super Admin, Restaurant Admin, Kitchen Staff and Delivery Users. Use your account to manage orders, menu, tables, rooms, reports, subscriptions and operations.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="' . esc_url(wp_login_url()) . '">Login Now</a><a class="btn btn-ghost" href="' . esc_url(fluuexqr_v62_page_url('start-free-trial')) . '">Start Free Trial</a></div></div></section><section class="fq-v62-section"><div class="fq-v62-dashboard-grid"><article class="fq-v62-dashboard-card"><h3>Super Admin</h3><p>Manage restaurants, plans, subscriptions and platform reports.</p></article><article class="fq-v62-dashboard-card"><h3>Restaurant Admin</h3><p>Manage menu, orders, rooms, tables, staff, billing and reports.</p></article><article class="fq-v62-dashboard-card"><h3>Kitchen Dashboard</h3><p>Accept, prepare, ready and served workflow for live orders.</p></article><article class="fq-v62-dashboard-card"><h3>Delivery Panel</h3><p>Assigned orders, navigation links and delivery history.</p></article></div></section>';
}

function fluuexqr_v63_trial_page_content(): string {
    return '<section class="fq-v62-hero"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Free Trial</span><h1>Start your FluuexQR free trial</h1><p>Try the restaurant and hotel QR ordering platform with menu management, table and room QR, kitchen display, billing, analytics and attractive customer ordering UI.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="' . esc_url(menuqr_get_page_url_by_slug('signup')) . '">Create Account</a><a class="btn btn-ghost" href="' . esc_url(fluuexqr_v62_page_url('pricing')) . '">View Pricing</a></div></div><div class="fq-v62-service-visual"><div class="fq-v62-dashboard-mock"><b>Free Trial</b><strong>10 Days</strong><span>All core modules ready</span></div><div class="fq-v62-live-card"><b>Includes</b><span>QR Menu · KDS · Billing</span><em>Fast Setup</em></div></div></section>' . fluuexqr_v62_pricing_preview();
}

function fluuexqr_v63_contact_page_content(): string {
    $form = shortcode_exists('contact-form-7') ? do_shortcode('[contact-form-7 id="ee266f0" title="Contact form 1"]') : '<div class="fq-v63-form-fallback"><strong>Contact Form 7 shortcode:</strong><br><code>[contact-form-7 id="ee266f0" title="Contact form 1"]</code></div>';
    return '<section class="fq-v62-hero"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Contact Us</span><h1>Book a live demo of FluuexQR</h1><p>Talk to us about your restaurant or hotel requirement. We can help you with QR ordering, room service QR, billing, kitchen dashboard, delivery tracking, staff setup and SaaS subscription planning.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="tel:+919876543210">Call Now</a><a class="btn btn-ghost" href="mailto:hello@fluuexqr.com">Email Us</a></div></div><div class="fq-v62-service-visual"><div class="fq-v62-visual-emoji">📞</div><div class="fq-v62-live-card"><b>Available for</b><span>Demo · Setup · Support</span><em>Contact Now</em></div></div></section><section class="fq-v62-section"><div class="fq-v62-section-head"><span>Contact Form</span><h2>Send your requirement</h2><p>Submit your restaurant, hotel, cafe, cloud kitchen or multi-branch requirement and we will respond with the best setup plan.</p></div><div class="fq-v63-contact-form-wrap">' . $form . '</div></section><section class="fq-v62-section"><div class="fq-v62-contact-grid"><article><h3>Sales Demo</h3><p>Show clients customer ordering UI, admin dashboard, billing and kitchen workflow.</p></article><article><h3>Business Discussion</h3><p>Single restaurant, multi-branch and hotel room service solutions.</p></article><article><h3>Technical Support</h3><p>Menu, table, room, staff, reports, billing and delivery workflow support.</p></article></div></section>';
}
function fluuexqr_v63_blogs_page_content(): string {
    $topics = ['Restaurant Technology','QR Ordering','Hotel Ordering','Restaurant Automation','Delivery Management','Contactless Dining'];
    $cards = '';
    foreach ($topics as $topic) {
        $cards .= '<article class="fq-v63-link-card"><div class="fq-v62-icon">📝</div><h3>' . esc_html($topic) . '</h3><p>SEO-friendly content topic to help FluuexQR rank on Google and attract restaurant and hotel owners.</p><a href="' . esc_url(home_url('/blog/')) . '">Read articles →</a></article>';
    }
    return '<section class="fq-v62-hero simple"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Blogs</span><h1>SEO blog system for restaurant & hotel technology</h1><p>Publish helpful blog posts about restaurant ordering software, QR menus, hotel ordering, delivery management and contactless dining.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="' . esc_url(home_url('/blog/')) . '">Open Blog</a><a class="btn btn-ghost" href="' . esc_url(fluuexqr_v62_page_url('contact-us')) . '">Talk to Team</a></div></div></section><section class="fq-v62-section"><div class="fq-v62-feature-grid">' . $cards . '</div></section>';
}

function fluuexqr_v63_page_definitions(): array {
    $pages = [
        'home' => ['Home', fluuexqr_v62_home_content()],
        'features' => ['Features', fluuexqr_v62_features_full_html()],
        'services' => ['Services', fluuexqr_v63_services_index_content()],
        'pricing' => ['Pricing', fluuexqr_v63_pricing_page_content()],
        'demo' => ['Demo', fluuexqr_v63_demo_page_content()],
        'about-us' => ['About Us', fluuexqr_v63_about_page_content()],
        'about' => ['About', fluuexqr_v63_about_page_content()],
        'contact-us' => ['Contact Us', fluuexqr_v63_contact_page_content()],
        'contact' => ['Contact', fluuexqr_v63_contact_page_content()],
        'blogs' => ['Blogs', fluuexqr_v63_blogs_page_content()],
        'faq' => ['FAQ', fluuexqr_v62_faq_content()],
        'support' => ['Support', fluuexqr_v63_support_page_content()],
        'login' => ['Login', fluuexqr_v63_login_page_content()],
        'start-free-trial' => ['Start Free Trial', fluuexqr_v63_trial_page_content()],
    ];

    foreach (fluuexqr_v62_service_data() as $slug => $data) {
        $pages[$slug] = [$data[0], fluuexqr_v62_service_page_html($data[0], $data[1], $data[2], $data[3], $data[4] ?? [], $data[5] ?? '🚀')];
    }

    $landing_pages = [
        'best-qr-menu-system-for-restaurants' => ['Best QR Menu System for Restaurants','Restaurant QR Menu','restaurants that want modern digital ordering'],
        'restaurant-ordering-software-in-india' => ['Restaurant Ordering Software in India','Restaurant Ordering Software','Indian restaurants, cafes and hotels'],
        'hotel-qr-ordering-system' => ['Hotel QR Ordering System','Hotel QR Ordering','hotels, resorts and room service teams'],
        'restaurant-billing-ordering-software' => ['Restaurant Billing & Ordering Software','Restaurant Billing Software','restaurants that need ordering plus billing'],
        'qr-menu-for-cafes' => ['QR Menu for Cafes','QR Menu for Cafes','cafes and quick service restaurants'],
        'restaurant-digital-transformation' => ['Restaurant Digital Transformation','Restaurant Digital Transformation','restaurants upgrading from manual operations'],
        'smart-restaurant-technology' => ['Smart Restaurant Technology','Smart Restaurant Technology','modern restaurants and hotel food services'],
        'contactless-restaurant-ordering' => ['Contactless Restaurant Ordering','Contactless Restaurant Ordering','restaurants offering contactless dining'],
    ];
    foreach ($landing_pages as $slug => $data) {
        $pages[$slug] = [$data[0], fluuexqr_v62_service_page_html($data[0], $data[1], $data[2])];
    }

    return $pages;
}

function fluuexqr_v63_create_or_update_pages(): void {
    $pages = fluuexqr_v63_page_definitions();
    foreach ($pages as $slug => $page) {
        $existing = get_page_by_path($slug);
        $args = [
            'post_title' => $page[0],
            'post_name' => $slug,
            'post_content' => $page[1],
            'post_status' => 'publish',
            'post_type' => 'page',
        ];
        if ($existing instanceof WP_Post) {
            $args['ID'] = $existing->ID;
            wp_update_post($args);
            update_post_meta($existing->ID, '_wp_page_template', 'page-saas.php');
        } else {
            $args['meta_input'] = ['_wp_page_template' => 'page-saas.php'];
            wp_insert_post($args);
        }
    }

    $home = get_page_by_path('home');
    if ($home instanceof WP_Post) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home->ID);
    }
}

function fluuexqr_v63_install_marketing_pages_once(): void {
    $version = '1.5.64';
    if (get_option('fluuexqr_v63_marketing_pages_version') === $version) { return; }
    fluuexqr_v63_create_or_update_pages();
    update_option('fluuexqr_v63_marketing_pages_version', $version, false);
}

function fluuexqr_v63_force_marketing_pages_on_switch(): void {
    fluuexqr_v63_create_or_update_pages();
    update_option('fluuexqr_v63_marketing_pages_version', '1.5.64', false);
}
add_action('after_switch_theme', 'fluuexqr_v63_force_marketing_pages_on_switch', 30);
add_action('init', 'fluuexqr_v63_install_marketing_pages_once', 30);
