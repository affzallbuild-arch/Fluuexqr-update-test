<?php
if (!defined('ABSPATH')) {
    exit;
}

define('MENUQR_THEME_VERSION', '1.0.207');

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle === '' || strpos((string) $haystack, (string) $needle) !== false;
    }
}
define('MENUQR_THEME_DIR', get_template_directory());
define('MENUQR_THEME_URI', get_template_directory_uri());

require_once MENUQR_THEME_DIR . '/inc/helpers.php';
require_once MENUQR_THEME_DIR . '/inc/database.php';
require_once MENUQR_THEME_DIR . '/inc/roles.php';
require_once MENUQR_THEME_DIR . '/inc/auth.php';
require_once MENUQR_THEME_DIR . '/inc/subscriptions.php';
require_once MENUQR_THEME_DIR . '/inc/payments.php';
require_once MENUQR_THEME_DIR . '/inc/billing.php';
require_once MENUQR_THEME_DIR . '/inc/reviews.php';
require_once MENUQR_THEME_DIR . '/inc/qr-generator.php';
require_once MENUQR_THEME_DIR . '/inc/demo-data.php';
require_once MENUQR_THEME_DIR . '/inc/ajax.php';
require_once MENUQR_THEME_DIR . '/inc/actions.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v207-order-operations.php';
require_once MENUQR_THEME_DIR . '/inc/marketing-pages.php';
require_once MENUQR_THEME_DIR . '/inc/marketing-pages-v63.php';
require_once MENUQR_THEME_DIR . '/inc/seo-performance-v67.php';
require_once MENUQR_THEME_DIR . '/inc/v69-final-fixes.php';
require_once MENUQR_THEME_DIR . '/inc/v81-seo-performance-final.php';
require_once MENUQR_THEME_DIR . '/inc/v90-preview-pages.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v112-complete.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v115-v112-superadmin.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v116-home-pricing-blog-ui.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v120-blog-admin-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v121-seo-speed-real-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v122-visible-blog-admin.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v123-real-bill-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v124-paid-due-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v125-cache-manager.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v127-starter-table-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v128-real-bill-paid-sync.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v129-menu-bill-tracker-sync.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v130-customer-bill-tracker-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v131-tracker-stable.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v132-pricing-live-admin.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v178-customer-paid-visible-sync.php';

function menuqr_theme_setup(): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo');
    register_nav_menus([
        'primary' => __('Primary Menu', 'menuqr'),
        'footer'  => __('Footer Menu', 'menuqr'),
    ]);
}
add_action('after_setup_theme', 'menuqr_theme_setup');


function fqx_get_brand_logo_url($variant = 'main'): string {
    $variant = sanitize_key((string) $variant);
    $map = [
        'main'    => ['assets/images/fluuexqr-logo-main.webp', 'assets/images/fluuexqr-logo-main.png', 'assets/images/fluuexqr-logo.webp', 'assets/images/fluuexqr-logo.png'],
        'dark'    => ['assets/images/fluuexqr-logo-main.webp', 'assets/images/fluuexqr-logo-main.png', 'assets/images/fluuexqr-logo.webp', 'assets/images/fluuexqr-logo.png'],
        'compact' => ['assets/images/fluuexqr-icon.webp', 'assets/images/fluuexqr-icon.png', 'assets/images/site-icon.png'],
        'icon'    => ['assets/images/fluuexqr-icon.webp', 'assets/images/fluuexqr-icon.png', 'assets/images/site-icon.png'],
        'favicon' => ['assets/images/site-icon.png', 'assets/images/fluuexqr-icon.png'],
    ];
    $candidates = $map[$variant] ?? $map['main'];
    foreach ($candidates as $relative) {
        $path = MENUQR_THEME_DIR . '/' . $relative;
        if (file_exists($path)) {
            return MENUQR_THEME_URI . '/' . $relative . '?ver=' . filemtime($path);
        }
    }
    return '';
}

function menuqr_get_brand_logo_url(): string {
    return fqx_get_brand_logo_url('main');
}

function fqx_brand_logo_img(string $variant = 'main', string $class = 'fqx-brand-logo', string $alt = 'FluuexQR Hotel & Restaurant Automation', string $loading = 'eager'): string {
    $url = fqx_get_brand_logo_url($variant);
    if (!$url) { return ''; }
    $relative = strpos($url, 'assets/') !== false ? substr($url, strpos($url, 'assets/')) : '';
    $relative = strtok($relative, '?');
    $width = 1446; $height = 544;
    if (in_array(sanitize_key($variant), ['compact', 'icon', 'favicon'], true)) { $width = 512; $height = 512; }
    $path = $relative ? MENUQR_THEME_DIR . '/' . $relative : '';
    if ($path && file_exists($path)) {
        $size = @getimagesize($path);
        if (is_array($size)) { $width = (int) $size[0]; $height = (int) $size[1]; }
    }
    return '<img class="' . esc_attr($class) . '" src="' . esc_url($url) . '" width="' . esc_attr((string) $width) . '" height="' . esc_attr((string) $height) . '" alt="' . esc_attr($alt) . '" loading="' . esc_attr($loading) . '" decoding="async" fetchpriority="' . ($loading === 'eager' ? 'high' : 'auto') . '">';
}




add_filter('get_site_icon_url', function ($url, $size = 512, $blog_id = 0) {
    if (!empty($url)) { return $url; }
    return function_exists('fqx_get_brand_logo_url') ? fqx_get_brand_logo_url('favicon') : $url;
}, 10, 3);

function menuqr_asset_version(string $relative_path): string {
    $path = MENUQR_THEME_DIR . '/' . ltrim($relative_path, '/');
    if (file_exists($path)) {
        return (string) filemtime($path);
    }
    return MENUQR_THEME_VERSION;
}

function menuqr_enqueue_assets(): void {
    // v81: performance mode. Load one bundled stylesheet and one bundled script instead of many small files.
    wp_enqueue_style('fluuexqr-v81-bundle', MENUQR_THEME_URI . '/assets/css/fluuexqr-v81-bundle.min.css', [], menuqr_asset_version('assets/css/fluuexqr-v81-bundle.min.css'));
    wp_enqueue_style('fluuexqr-v90-preview-ui', MENUQR_THEME_URI . '/assets/css/fq90-preview-ui.css', ['fluuexqr-v81-bundle'], menuqr_asset_version('assets/css/fq90-preview-ui.css'));
    wp_enqueue_style('fluuexqr-v91-v6-mobile-fixed-ui', MENUQR_THEME_URI . '/assets/css/fq91-v6-mobile-fixed.css', ['fluuexqr-v90-preview-ui'], menuqr_asset_version('assets/css/fq91-v6-mobile-fixed.css'));
    wp_enqueue_style('fluuexqr-v95-premium-responsive', MENUQR_THEME_URI . '/assets/css/fq95-premium-responsive.min.css', ['fluuexqr-v91-v6-mobile-fixed-ui'], menuqr_asset_version('assets/css/fq95-premium-responsive.min.css'));
    wp_enqueue_style('fluuexqr-v97-mobile-header-only-fix', MENUQR_THEME_URI . '/assets/css/fq97-mobile-header-only-fix.min.css', ['fluuexqr-v95-premium-responsive'], menuqr_asset_version('assets/css/fq97-mobile-header-only-fix.min.css'));
    wp_enqueue_style('fluuexqr-v99-phone-header-final', MENUQR_THEME_URI . '/assets/css/fq99-phone-header-final.min.css', ['fluuexqr-v97-mobile-header-only-fix'], menuqr_asset_version('assets/css/fq99-phone-header-final.min.css'));
    wp_enqueue_style('fluuexqr-v101-foodwala-menu-ui', MENUQR_THEME_URI . '/assets/css/fq101-foodwala-menu-ui.css', ['fluuexqr-v99-phone-header-final'], menuqr_asset_version('assets/css/fq101-foodwala-menu-ui.css'));

    wp_enqueue_script('jquery');
    wp_enqueue_script('fluuexqr-v81-bundle', MENUQR_THEME_URI . '/assets/js/fluuexqr-v81-bundle.min.js', ['jquery'], menuqr_asset_version('assets/js/fluuexqr-v81-bundle.min.js'), true);
    wp_enqueue_script('fluuexqr-v90-preview-ui', MENUQR_THEME_URI . '/assets/js/fq90-preview-ui.js', [], menuqr_asset_version('assets/js/fq90-preview-ui.js'), true);
    wp_enqueue_script('fluuexqr-v91-v6-mobile-fixed-ui', MENUQR_THEME_URI . '/assets/js/fq91-v6-mobile-fixed.js', [], menuqr_asset_version('assets/js/fq91-v6-mobile-fixed.js'), true);
    wp_enqueue_script('fluuexqr-v95-premium-responsive', MENUQR_THEME_URI . '/assets/js/fq95-premium-responsive.min.js', ['fluuexqr-v81-bundle'], menuqr_asset_version('assets/js/fq95-premium-responsive.min.js'), true);
    wp_enqueue_script('fluuexqr-v104-bill-page-force', MENUQR_THEME_URI . '/assets/js/fq104-bill-page-force.js', ['jquery', 'fluuexqr-v81-bundle'], menuqr_asset_version('assets/js/fq104-bill-page-force.js'), true);
    wp_enqueue_script('fluuexqr-v123-real-bill-fix', MENUQR_THEME_URI . '/assets/js/fq123-real-bill-fix.js', ['jquery', 'fluuexqr-v81-bundle', 'fluuexqr-v104-bill-page-force'], menuqr_asset_version('assets/js/fq123-real-bill-fix.js'), true);

    $config = [
        'ajax_url'        => admin_url('admin-ajax.php'),
        'nonce'           => wp_create_nonce('menuqr_nonce'),
        'theme_url'       => MENUQR_THEME_URI,
        'site_url'        => home_url('/'),
        'current_user_id' => get_current_user_id(),
        'refresh_ms'      => 5000,
        'menu_url'        => menuqr_get_page_url_by_slug('menu'),
        'bill_url'        => menuqr_get_page_url_by_slug('bill') ?: home_url('/bill/'),
        'dashboard_url'   => menuqr_get_dashboard_url(),
        'kitchen_url'     => menuqr_get_page_url_by_slug('kitchen-dashboard'),
        'super_admin_url' => menuqr_get_page_url_by_slug('super-admin-dashboard'),
        'is_dashboard'    => menuqr_is_dashboard_context(),
        'cache_version'   => menuqr_asset_version('assets/css/fluuexqr-v81-bundle.min.css') . '-' . menuqr_asset_version('assets/js/fluuexqr-v81-bundle.min.js'),
        'brand_logo'      => menuqr_get_brand_logo_url(),
        'demo_restaurant_id' => menuqr_get_demo_restaurant_id(),
        'demo_table_id'   => menuqr_get_demo_table_id(),
        'demo_menu_url'   => menuqr_get_demo_menu_url(),
        'qr_actions'      => [
            'create' => 'menuqr_create_qr_template',
            'preview' => 'menuqr_preview_qr_template',
            'bulk' => 'menuqr_bulk_generate_qr_templates',
        ],
    ];

    wp_localize_script('fluuexqr-v81-bundle', 'menuqr_ajax', $config);
    wp_add_inline_script('fluuexqr-v81-bundle', 'window.menuqr_ajax = window.menuqr_ajax || {};', 'before');
}
add_action('wp_enqueue_scripts', 'menuqr_enqueue_assets');

function menuqr_register_shortcodes(): void {
    add_shortcode('menuqr_homepage', 'menuqr_shortcode_homepage');
    add_shortcode('menuqr_pricing', 'menuqr_shortcode_pricing');
    add_shortcode('menuqr_login', 'menuqr_shortcode_login');
    add_shortcode('menuqr_signup', 'menuqr_shortcode_signup');
    add_shortcode('menuqr_contact', 'menuqr_shortcode_contact');
    add_shortcode('menuqr_menu', 'menuqr_shortcode_menu');
    add_shortcode('menuqr_dashboard', 'menuqr_shortcode_dashboard');
    add_shortcode('menuqr_kitchen', 'menuqr_shortcode_kitchen');
    add_shortcode('menuqr_super_admin', 'menuqr_shortcode_super_admin');
    add_shortcode('menuqr_bill', 'menuqr_shortcode_bill');
}
add_action('init', 'menuqr_register_shortcodes');

function menuqr_shortcode_homepage(): string {
    ob_start();
    get_template_part('templates/customer-menu', 'landing');
    return (string) ob_get_clean();
}

function menuqr_shortcode_pricing(): string {
    $signup_url = menuqr_get_page_url_by_slug('signup');
    ob_start();
    ?>
    <section class="fqx-marketing-page fqx-section fqx-pricing-preview">
        <div class="fqx-wrap">
            <div class="fqx-section-head">
                <span class="fqx-kicker">FluuexQR Pricing</span>
                <h2>Only 3 plans. Simple for restaurant owners.</h2>
                <p>Free Trial, Starter 5 Table, Restaurant All Access, and Hotel + Restaurant Full Access.</p>
            </div>
            <div class="fqx-price-grid">
                <article class="fqx-price-card"><span>Free Trial</span><h3>₹0 <small>/ 10 days</small></h3><p>Full access during trial.</p><ul><li>All features ON</li><li>No payment required</li><li>Trial countdown</li></ul><a class="fqx-btn fqx-btn-soft" href="<?php echo esc_url($signup_url . '?plan=free_trial'); ?>">Start Trial</a></article>
                <article class="fqx-price-card"><span>Starter 5 Table</span><h3>₹999 <small>/ month</small></h3><p>For small restaurants starting digital ordering.</p><ul><li>5 tables + 5 categories</li><li>20 menu items + 2 staff</li><li>No Room QR</li></ul><a class="fqx-btn fqx-btn-soft" href="<?php echo esc_url($signup_url . '?plan=starter_5_table'); ?>">Choose Starter</a></article>
                <article class="fqx-price-card featured"><em>Recommended</em><span>Restaurant All Access</span><h3>₹1,999 <small>/ month</small></h3><p>For restaurants, cafés, dhabas and cloud kitchens.</p><ul><li>Table QR + kitchen</li><li>Billing + WhatsApp bill</li><li>UPI/Razorpay/Cash</li></ul><a class="fqx-btn fqx-btn-primary" href="<?php echo esc_url($signup_url . '?plan=restaurant_all_access'); ?>">Choose Plan</a></article>
                <article class="fqx-price-card"><span>Hotel + Restaurant</span><h3>₹2,499 <small>/ month</small></h3><p>For hotels, resorts and guest houses.</p><ul><li>Room QR ordering</li><li>Room-wise bill</li><li>Priority support</li></ul><a class="fqx-btn fqx-btn-soft" href="<?php echo esc_url($signup_url . '?plan=hotel_restaurant_full_access'); ?>">Choose Hotel Plan</a></article>
            </div>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

function menuqr_shortcode_login(): string {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        ob_start();
        ?>
        <section class="mq-login-wrap">
            <div class="login-card">
                <div class="login-logo">
                    <h1>Fluuex<span>QR</span></h1>
                    <p>You are already logged in.</p>
                </div>
                <div class="alert alert-info">Logged in as <?php echo esc_html($user->display_name ?: $user->user_email); ?>.</div>
                <div class="mq-actions-center" style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
                    <a class="btn btn-primary btn-lg" href="<?php echo esc_url(menuqr_get_dashboard_url()); ?>">Go to Dashboard</a>
                    <a class="btn btn-outline btn-lg" href="<?php echo esc_url(wp_logout_url(menuqr_get_page_url_by_slug('login'))); ?>">Logout</a>
                </div>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    ob_start();
    ?>
    <section class="mq-login-wrap">
        <div class="login-card">
            <div class="login-logo">
                <h1>Fluuex<span>QR</span></h1>
                <p>Sign in to your account</p>
            </div>
            <?php if (isset($_GET['login']) && 'failed' === sanitize_text_field(wp_unslash($_GET['login']))) : ?>
                <div class="alert alert-danger">Invalid email or password.</div>
            <?php endif; ?>
            <?php if (isset($_GET['logout']) && 'success' === sanitize_text_field(wp_unslash($_GET['logout']))) : ?>
                <div class="alert alert-success">You have been logged out successfully.</div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('menuqr_login_action', 'menuqr_login_nonce'); ?>
                <input type="hidden" name="action" value="menuqr_login">
                <div class="form-group">
                    <label class="form-label" for="menuqr_email"><?php esc_html_e('Email Address', 'menuqr'); ?></label>
                    <input class="form-input" id="menuqr_email" type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="menuqr_pass"><?php esc_html_e('Password', 'menuqr'); ?></label>
                    <input class="form-input" id="menuqr_pass" type="password" name="password" required>
                </div>
                <button class="btn btn-primary btn-full btn-lg" type="submit"><?php esc_html_e('Sign In →', 'menuqr'); ?></button>
            </form>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

function menuqr_shortcode_signup(): string {
    $plans = menuqr_get_subscription_plans();
    $signup_error = sanitize_text_field(wp_unslash($_GET['signup_error'] ?? ''));
    $selected_plan_slug = sanitize_key(wp_unslash($_GET['plan'] ?? ''));
    ob_start();
    ?>
    <section class="mq-shell mq-signup">
        <div class="mq-container narrow">
            <div class="section-card">
                <div class="page-header">
                    <div class="page-header-left">
                        <h2><?php esc_html_e('Restaurant Signup', 'menuqr'); ?></h2>
                        <p><?php esc_html_e('Create your restaurant admin account and request activation.', 'menuqr'); ?></p>
                    </div>
                </div>
                <?php if ($signup_error) : ?>
                    <div class="alert alert-danger">
                        <?php
                        $signup_messages = [
                            'invalid_request' => __('Form expired or invalid request. Please submit again.', 'menuqr'),
                            'closed' => __('Restaurant signup is currently disabled by the platform owner.', 'menuqr'),
                            'missing_fields' => __('Please fill in all required fields.', 'menuqr'),
                            'weak_password' => __('Password must be at least 8 characters.', 'menuqr'),
                            'exists' => __('An account already exists with this email.', 'menuqr'),
                        ];
                        echo esc_html($signup_messages[$signup_error] ?? __('Signup failed. Please try again.', 'menuqr'));
                        ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="<?php echo esc_url(menuqr_get_page_url_by_slug('signup')); ?>">
                    <?php wp_nonce_field('menuqr_signup_action'); ?>
                    <input type="hidden" name="menuqr_form" value="signup">
                    <input type="hidden" name="action" value="menuqr_signup">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?php esc_html_e('Restaurant Name', 'menuqr'); ?></label>
                            <input class="form-input" name="restaurant_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php esc_html_e('Owner Name', 'menuqr'); ?></label>
                            <input class="form-input" name="owner_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?php esc_html_e('Email', 'menuqr'); ?></label>
                            <input class="form-input" type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php esc_html_e('Phone', 'menuqr'); ?></label>
                            <input class="form-input" name="phone" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?php esc_html_e('Password', 'menuqr'); ?></label>
                            <input class="form-input" type="password" name="password" required minlength="8">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php esc_html_e('Plan', 'menuqr'); ?></label>
                            <select class="form-select" name="plan_id" required>
                                <?php foreach ($plans as $plan) : $plan_slug = sanitize_key((string) ($plan->slug ?? '')); ?>
                                    <option value="<?php echo esc_attr((string) $plan->id); ?>" <?php selected($selected_plan_slug, $plan_slug); ?>><?php echo esc_html($plan->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php esc_html_e('Address', 'menuqr'); ?></label>
                        <textarea class="form-textarea" name="address" required></textarea>
                    </div>
                    <button class="btn btn-primary btn-lg" type="submit"><?php esc_html_e('Create Restaurant Account', 'menuqr'); ?></button>
                </form>
            </div>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}


function menuqr_shortcode_contact(): string {
    $contact_status = sanitize_key(wp_unslash($_GET['contact_status'] ?? ''));
    $cf7_shortcode = '[contact-form-7 id="ee266f0" title="Contact form 1"]';
    $has_cf7 = shortcode_exists('contact-form-7');
    ob_start();
    ?>
    <section class="fq-contact-page-shell">
        <div class="mq-container fq-contact-layout">
            <div class="fq-contact-copy-card">
                <span class="fq-home-kicker">Contact FluuexQR</span>
                <h1>Let's build a faster, smoother restaurant workflow.</h1>
                <p>Share your requirement, and our team will help with onboarding, QR setup, billing, kitchen flow, and launch guidance.</p>
                <div class="fq-contact-points">
                    <div class="fq-contact-point"><strong>Email</strong><span>hello@fluuexqr.com</span></div>
                    <div class="fq-contact-point"><strong>Support</strong><span>support@fluuexqr.com</span></div>
                    <div class="fq-contact-point"><strong>Location</strong><span>Noida, Uttar Pradesh, India</span></div>
                </div>
            </div>
            <div class="fq-contact-form-card">
                <?php if ('success' === $contact_status) : ?>
                    <div class="alert alert-success"><?php esc_html_e('Your message was sent successfully. We will contact you soon.', 'menuqr'); ?></div>
                <?php elseif ('error' === $contact_status) : ?>
                    <div class="alert alert-danger"><?php esc_html_e('Unable to send your message right now. Please try again.', 'menuqr'); ?></div>
                <?php endif; ?>

                <?php if ($has_cf7) : ?>
                    <div class="fq-contact-shortcode-wrap">
                        <?php echo do_shortcode($cf7_shortcode); ?>
                    </div>
                <?php else : ?>
                    <div class="fq-contact-fallback-note">Contact Form 7 plugin not active. Using built-in contact form.</div>
                    <form method="post" action="<?php echo esc_url(menuqr_get_page_url_by_slug('contact')); ?>" class="fq-contact-fallback-form">
                        <?php wp_nonce_field('menuqr_contact_action'); ?>
                        <input type="hidden" name="menuqr_form" value="contact">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><?php esc_html_e('Name', 'menuqr'); ?></label>
                                <input class="form-input" name="name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php esc_html_e('Email', 'menuqr'); ?></label>
                                <input class="form-input" type="email" name="email" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php esc_html_e('Message', 'menuqr'); ?></label>
                            <textarea class="form-textarea" name="message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg"><?php esc_html_e('Send Message', 'menuqr'); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}


function menuqr_handle_contact_form(): void {
    if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) {
        return;
    }
    if (!isset($_POST['menuqr_form']) || 'contact' !== sanitize_key(wp_unslash($_POST['menuqr_form']))) {
        return;
    }
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'menuqr_contact_action')) {
        wp_safe_redirect(add_query_arg('contact_status', 'error', menuqr_get_page_url_by_slug('contact')));
        exit;
    }

    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $message = wp_kses_post(wp_unslash($_POST['message'] ?? ''));

    if ($name === '' || $email === '' || $message === '') {
        wp_safe_redirect(add_query_arg('contact_status', 'error', menuqr_get_page_url_by_slug('contact')));
        exit;
    }

    $admin_email = get_option('admin_email');
    $subject = sprintf('FluuexQR Contact: %s', $name);
    $body = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";
    $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];

    $sent = wp_mail($admin_email, $subject, $body, $headers);

    wp_safe_redirect(add_query_arg('contact_status', $sent ? 'success' : 'error', menuqr_get_page_url_by_slug('contact')));
    exit;
}
add_action('template_redirect', 'menuqr_handle_contact_form', 1);

function menuqr_shortcode_menu(): string {
    ob_start();
    get_template_part('templates/customer-menu');
    get_template_part('templates/cart');
    get_template_part('templates/checkout');
    get_template_part('templates/order-status');
    return (string) ob_get_clean();
}

function menuqr_shortcode_dashboard(): string {
    if (!is_user_logged_in()) {
        return '<div class="mq-container narrow"><div class="alert alert-warning">Please log in first.</div></div>';
    }
    ob_start();
    get_template_part('templates/restaurant-dashboard');
    return (string) ob_get_clean();
}

function menuqr_shortcode_kitchen(): string {
    if (!is_user_logged_in()) {
        return '<div class="mq-container narrow"><div class="alert alert-warning">Please log in first.</div></div>';
    }
    ob_start();
    get_template_part('templates/kitchen-dashboard');
    return (string) ob_get_clean();
}

function menuqr_shortcode_super_admin(): string {
    if (!is_user_logged_in()) {
        return '<div class="mq-container narrow"><div class="alert alert-warning">Please log in first.</div></div>';
    }
    ob_start();
    get_template_part('templates/super-admin-dashboard');
    return (string) ob_get_clean();
}


function menuqr_shortcode_bill(): string {
    ob_start();
    get_template_part('templates/bill');
    return (string) ob_get_clean();
}

function menuqr_activation(): void {
    menuqr_register_roles();
    menuqr_create_tables();
    menuqr_create_default_pages();
    menuqr_seed_demo_data();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'menuqr_activation');
add_action('init', 'menuqr_run_self_repair', 5);

function menuqr_create_default_pages(): void {
    $pages = [
        'home' => ['title' => 'Home', 'content' => '', 'template' => 'front-page.php'],
        'pricing' => ['title' => 'Pricing', 'content' => '', 'template' => 'page-pricing.php'],
        'login' => ['title' => 'Login', 'content' => '[menuqr_login]', 'template' => 'page-login.php'],
        'signup' => ['title' => 'Signup', 'content' => '[menuqr_signup]', 'template' => 'page-signup.php'],
        'contact' => ['title' => 'Contact', 'content' => '[contact-form-7 id="ee266f0" title="Contact form 1"]', 'template' => 'page-contact.php'],
        'features' => ['title' => 'Features', 'content' => '[menuqr_homepage]', 'template' => 'front-page.php'],
        'demo' => ['title' => 'Demo', 'content' => '[menuqr_menu]', 'template' => 'page-menu.php'],
        'cart' => ['title' => 'Cart', 'content' => '[menuqr_menu]', 'template' => 'page-menu.php'],
        'checkout' => ['title' => 'Checkout', 'content' => '[menuqr_menu]', 'template' => 'page-menu.php'],
        'order-status' => ['title' => 'Order Status', 'content' => '[menuqr_menu]', 'template' => 'page-menu.php'],
        'faq' => ['title' => 'FAQ', 'content' => '<h2>Frequently Asked Questions</h2><p>Find answers about QR menu setup, billing, WhatsApp invoice delivery, kitchen workflow, and onboarding.</p>', 'template' => 'page.php'],
        'menu' => ['title' => 'Menu', 'content' => '[menuqr_menu]', 'template' => 'page-menu.php'],
        'bill' => ['title' => 'Bill', 'content' => '[menuqr_bill]', 'template' => 'page-menu.php'],
        'restaurant-dashboard' => ['title' => 'Restaurant Dashboard', 'content' => '[menuqr_dashboard]', 'template' => 'page-dashboard.php'],
        'kitchen-dashboard' => ['title' => 'Kitchen Dashboard', 'content' => '[menuqr_kitchen]', 'template' => 'page-kitchen.php'],
        'super-admin-dashboard' => ['title' => 'Super Admin Dashboard', 'content' => '[menuqr_super_admin]', 'template' => 'page-super-admin.php'],
        'help-center' => ['title' => 'Help Center', 'content' => '<h2>Help Center</h2><p>Welcome to FluuexQR support. Use this page for onboarding help, FAQ links, and customer support information.</p>', 'template' => 'page.php'],
        'documentation' => ['title' => 'Documentation', 'content' => '<h2>Documentation</h2><p>Use this page for setup guides, QR creation, billing, kitchen, and staff documentation.</p>', 'template' => 'page.php'],
        'status' => ['title' => 'System Status', 'content' => '<h2>System Status</h2><p>All FluuexQR systems are operating normally. You can replace this page with live uptime content later.</p>', 'template' => 'page.php'],
        'privacy-policy' => ['title' => 'Privacy Policy', 'content' => '<h2>Privacy Policy</h2><p>We respect your privacy and protect customer, restaurant, billing, and order data with secure handling, encrypted storage, and role-based access.</p><h3>Information We Collect</h3><p>We may collect restaurant profile data, customer order information, billing data, staff information, and support/contact details required to operate FluuexQR.</p><h3>How We Use Data</h3><p>We use data to provide QR ordering, billing, WhatsApp invoice workflows, analytics, support, and service improvements.</p><h3>Cookies & Analytics</h3><p>We may use cookies or analytics tools to improve performance, security, and user experience.</p><h3>Contact</h3><p>For privacy requests, contact hello@fluuexqr.com.</p>', 'template' => 'page.php'],
        'terms-of-service' => ['title' => 'Terms of Service', 'content' => '<h2>Terms of Service</h2><p>By using FluuexQR, you agree to use the platform lawfully and responsibly for restaurant operations, ordering, billing, and staff management.</p><h3>Service Usage</h3><p>Subscriptions, plans, features, limits, and support may vary by plan. Abuse, fraud, reverse engineering, or illegal use is prohibited.</p><h3>Payments</h3><p>Paid plans renew according to their billing cycle unless cancelled. Fees are non-refundable except where required by law or an approved written policy.</p><h3>Availability</h3><p>We aim for reliable service, but uptime, maintenance, feature changes, and third-party integrations may vary.</p><h3>Contact</h3><p>Questions about these terms can be sent to hello@fluuexqr.com.</p>', 'template' => 'page.php'],
        'blog' => ['title' => 'Blog', 'content' => '', 'template' => 'page-blog.php'],
    ];

    foreach ($pages as $slug => $page) {
        $existing = get_page_by_path($slug);
        if ($existing instanceof WP_Post) {
            wp_update_post([
                'ID'           => $existing->ID,
                'post_title'   => $page['title'],
                'post_name'    => $slug,
                'post_content' => $page['content'],
                'post_status'  => 'publish',
            ]);
            update_post_meta($existing->ID, '_wp_page_template', $page['template']);
            continue;
        }
        wp_insert_post([
            'post_title'   => $page['title'],
            'post_name'    => $slug,
            'post_content' => $page['content'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'meta_input'   => [
                '_wp_page_template' => $page['template'],
            ],
        ]);
    }

    $home = get_page_by_path('home');
    if ($home instanceof WP_Post) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home->ID);
    }

    // v121: keep /blog/ as a normal page with the FluuexQR blog template.
    // WordPress ignores page-blog.php when this page is set as page_for_posts.
    $blog = get_page_by_path('blog');
    if ($blog instanceof WP_Post && (int) get_option('page_for_posts') === (int) $blog->ID) {
        update_option('page_for_posts', 0);
    }
}

function menuqr_get_page_url_by_slug(string $slug): string {
    $page = get_page_by_path($slug);
    return $page instanceof WP_Post ? get_permalink($page) : home_url('/' . $slug . '/');
}


function menuqr_get_plan_signup_url(string $plan_slug = ''): string {
    $url = menuqr_get_page_url_by_slug('signup');
    $plan_slug = sanitize_key($plan_slug);
    if ($plan_slug !== '') {
        $url = add_query_arg(['plan' => $plan_slug], $url);
    }
    return $url;
}


function menuqr_send_dashboard_nocache_headers(): void {
    if (is_admin()) {
        return;
    }

    $dashboard_slugs = [
        'restaurant-dashboard',
        'kitchen-dashboard',
        'super-admin-dashboard',
        'menu',
        'bill',
        'checkout',
        'cart',
        'order-status',
    ];

    if (is_page($dashboard_slugs)) {
        nocache_headers();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    }
}
add_action('template_redirect', 'menuqr_send_dashboard_nocache_headers', 0);



function menuqr_is_dashboard_context(): bool {
    if (is_admin()) {
        return false;
    }

    return is_page([
        'restaurant-dashboard',
        'kitchen-dashboard',
        'super-admin-dashboard',
    ]);
}

function menuqr_is_customer_menu_context(): bool {
    return is_page(['menu', 'cart', 'checkout', 'order-status', 'bill']) || is_page_template('page-menu.php');
}

function menuqr_is_public_shell_context(): bool {
    return !menuqr_is_dashboard_context() && !menuqr_is_customer_menu_context();
}


function menuqr_get_demo_restaurant_id(): int {
    global $wpdb;
    $restaurants_table = menuqr_table('restaurants');
    $id = (int) $wpdb->get_var("SELECT id FROM {$restaurants_table} WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    if ($id <= 0 && function_exists('menuqr_seed_demo_data')) {
        menuqr_seed_demo_data();
        $id = (int) $wpdb->get_var("SELECT id FROM {$restaurants_table} WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    }
    return max(0, $id);
}

function menuqr_get_demo_table_id(int $restaurant_id = 0): int {
    global $wpdb;
    $restaurant_id = $restaurant_id > 0 ? $restaurant_id : menuqr_get_demo_restaurant_id();
    if ($restaurant_id <= 0) {
        return 0;
    }
    $tables_table = menuqr_table('tables');
    $id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tables_table} WHERE restaurant_id = %d ORDER BY id ASC LIMIT 1",
        $restaurant_id
    ));
    if ($id <= 0 && function_exists('menuqr_seed_demo_data')) {
        menuqr_seed_demo_data();
        $id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tables_table} WHERE restaurant_id = %d ORDER BY id ASC LIMIT 1",
            $restaurant_id
        ));
    }
    return max(0, $id);
}

function menuqr_get_demo_menu_url(): string {
    $restaurant_id = menuqr_get_demo_restaurant_id();
    $table_id = menuqr_get_demo_table_id($restaurant_id);
    $base = menuqr_get_page_url_by_slug('menu');
    if ($restaurant_id > 0 && $table_id > 0) {
        return add_query_arg(['r' => $restaurant_id, 't' => $table_id], $base);
    }
    return $base;
}

function menuqr_public_nav_items(): array {
    return [
        'home'     => home_url('/'),
        'features' => menuqr_get_page_url_by_slug('features'),
        'pricing'  => menuqr_get_page_url_by_slug('pricing'),
        'demo'     => menuqr_get_page_url_by_slug('demo'),
        'blog'     => menuqr_get_page_url_by_slug('blog'),
        'services' => menuqr_get_page_url_by_slug('services'),
        'about'    => menuqr_get_page_url_by_slug('about-us'),
        'support'  => menuqr_get_page_url_by_slug('support'),
        'contact'  => menuqr_get_page_url_by_slug('contact-us'),
    ];
}

function menuqr_clear_runtime_cache(): void {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_menuqr_%' OR option_name LIKE '_transient_timeout_menuqr_%'");
    wp_cache_flush();
    if (function_exists('liteSpeedPurgeAll')) {
        liteSpeedPurgeAll();
    }
    do_action('menuqr_cache_cleared');
}

function menuqr_get_current_meta_title(): string {
    if (is_front_page()) {
        return 'FluuexQR - QR Menu System for Restaurants';
    }
    if (is_singular('post')) {
        return single_post_title('', false) . ' | FluuexQR Blog';
    }
    if (is_home() || is_post_type_archive('post') || is_category() || is_tag()) {
        return trim(wp_get_document_title() . ' | FluuexQR Blog');
    }
    return wp_get_document_title();
}

function menuqr_get_current_meta_description(): string {
    if (is_front_page()) {
        return 'FluuexQR helps restaurants manage QR menus, room orders, kitchen display, billing, delivery and reports in one SaaS platform.';
    }
    if (is_singular('post')) {
        $excerpt = get_the_excerpt();
        return wp_strip_all_tags($excerpt ?: get_bloginfo('description'));
    }
    return 'FluuexQR is a restaurant and hotel SaaS for QR ordering, kitchen display, billing, delivery, staff and reports.';
}

function menuqr_get_current_og_image(): string {
    if (is_singular() && has_post_thumbnail()) {
        return (string) get_the_post_thumbnail_url(get_the_ID(), 'large');
    }
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo = wp_get_attachment_image_url($custom_logo_id, 'full');
        if ($logo) {
            return $logo;
        }
    }
    return '';
}

function menuqr_output_seo_meta(): void {
    if (is_admin()) {
        return;
    }

    $title = menuqr_get_current_meta_title();
    $description = menuqr_get_current_meta_description();
    if (function_exists('fluuexqr_v61_marketing_meta') && is_page()) {
        $slug = get_post_field('post_name', get_queried_object_id());
        $map = fluuexqr_v61_marketing_meta();
        if (isset($map[$slug])) {
            $title = $map[$slug][0];
            $description = $map[$slug][1];
        }
    }
    $canonical = is_singular() ? get_permalink() : home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''));
    $image = menuqr_get_current_og_image();
    if (function_exists('fluuexqr_v81_trim_meta_description')) {
        $description = fluuexqr_v81_trim_meta_description($description);
    }
    $site_name = 'FluuexQR';

    echo "\n" . '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:type" content="' . (is_singular('post') ? 'article' : 'website') . '">' . "\n";
    if ($image) {
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
    } else {
        echo '<meta name="twitter:card" content="summary">' . "\n";
    }
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
}

function menuqr_get_custom_meta_for_page(): array {
    if (is_page('pricing')) {
        return ['title' => 'Pricing - FluuexQR', 'description' => 'Compare FluuexQR pricing plans for QR menus, billing, kitchen display, analytics, and WhatsApp invoices.'];
    }
    if (is_page('contact')) {
        return ['title' => 'Contact - FluuexQR', 'description' => 'Contact FluuexQR for restaurant QR ordering, billing, kitchen, onboarding, support, and demo requests.'];
    }
    if (is_page('privacy-policy')) {
        return ['title' => 'Privacy Policy - FluuexQR', 'description' => 'Read the FluuexQR privacy policy covering data handling, restaurant information, order data, and customer privacy.'];
    }
    if (is_page('terms-of-service')) {
        return ['title' => 'Terms of Service - FluuexQR', 'description' => 'Review the FluuexQR terms of service for subscriptions, billing, usage rules, and platform conditions.'];
    }
    return [];
}

add_action('wp_head', 'menuqr_output_seo_meta', 2);

function menuqr_output_schema_markup(): void {
    if (is_admin()) {
        return;
    }

    $schemas = [];
    $schemas[] = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'FluuexQR',
        'url' => home_url('/'),
        'logo' => menuqr_get_current_og_image(),
        'description' => 'Restaurant QR ordering system, restaurant billing system, kitchen order management system, and WhatsApp bill platform.',
    ];

    if (is_front_page()) {
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'FluuexQR',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'description' => 'QR menu system for restaurants with restaurant QR ordering, billing, kitchen display, staff control, and WhatsApp bills.',
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'INR',
                'price' => '999',
            ],
            'keywords' => [
                'QR menu system for restaurants',
                'Restaurant QR ordering system',
                'Digital menu for restaurants',
                'QR code menu India',
                'Restaurant billing system',
                'WhatsApp bill restaurant',
                'Kitchen order management system',
                'Restaurant SaaS platform',
            ],
        ];
    }

    if (is_page() && has_block('core/faq')) {
        // Reserved for block-driven FAQ pages.
    }

    if (is_singular('post')) {
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => home_url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Blog',
                    'item' => get_post_type_archive_link('post') ?: home_url('/blog/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => get_the_title(),
                    'item' => get_permalink(),
                ],
            ],
        ];
    }

    echo "\n" . '<script type="application/ld+json">' . wp_json_encode($schemas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'menuqr_output_schema_markup', 30);

function menuqr_breadcrumbs(): string {
    $items = [
        ['label' => 'Home', 'url' => home_url('/')],
    ];

    if (is_home() || is_post_type_archive('post') || is_category() || is_tag()) {
        $items[] = ['label' => 'Blog', 'url' => get_post_type_archive_link('post') ?: home_url('/blog/')];
    } elseif (is_singular('post')) {
        $items[] = ['label' => 'Blog', 'url' => get_post_type_archive_link('post') ?: home_url('/blog/')];
        $items[] = ['label' => get_the_title(), 'url' => get_permalink()];
    } elseif (is_page() && !is_front_page()) {
        $items[] = ['label' => get_the_title(), 'url' => get_permalink()];
    }

    $html = '<nav class="mq-breadcrumbs" aria-label="Breadcrumb">';
    foreach ($items as $index => $item) {
        if ($index > 0) {
            $html .= '<span class="sep">/</span>';
        }
        $html .= '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
    }
    $html .= '</nav>';

    return $html;
}

function menuqr_output_noindex_for_private_panels(): void {
    if (!menuqr_is_dashboard_context()) {
        return;
    }
    echo "<meta name=\"robots\" content=\"noindex,nofollow\">\n";
}
add_action('wp_head', 'menuqr_output_noindex_for_private_panels', 1);


/**
 * v1.3.60 Performance + responsive hardening layer.
 * Keeps existing FluuexQR business logic intact and improves frontend loading on shared hosting.
 */
function menuqr_performance_resource_hints(array $urls, string $relation_type): array {
    if ('preconnect' === $relation_type) {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = 'https://fonts.gstatic.com';
    }
    if ('dns-prefetch' === $relation_type) {
        $urls[] = '//maps.googleapis.com';
        $urls[] = '//www.google.com';
    }
    return array_values(array_unique($urls));
}
add_filter('wp_resource_hints', 'menuqr_performance_resource_hints', 10, 2);

function menuqr_performance_cleanup_head(): void {
    if (is_admin()) {
        return;
    }
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
}
add_action('init', 'menuqr_performance_cleanup_head');

function menuqr_defer_frontend_scripts(string $tag, string $handle, string $src): string {
    if (is_admin() || empty($src)) {
        return $tag;
    }
    $defer_handles = ['menuqr-ajax', 'menuqr-app', 'menuqr-performance-responsive'];
    if (in_array($handle, $defer_handles, true) && false === strpos($tag, ' defer')) {
        return str_replace(' src=', ' defer src=', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'menuqr_defer_frontend_scripts', 10, 3);

function menuqr_logo_image_attributes(string $html, int $custom_logo_id): string {
    if (!$custom_logo_id) {
        return $html;
    }
    $html = str_replace('<img ', '<img loading="eager" decoding="async" fetchpriority="high" ', $html);
    return $html;
}
add_filter('get_custom_logo', 'menuqr_logo_image_attributes', 10, 2);

function menuqr_auto_image_loading_attrs(array $attr, $attachment, $size): array {
    if (empty($attr['loading'])) {
        $attr['loading'] = 'lazy';
    }
    if (empty($attr['decoding'])) {
        $attr['decoding'] = 'async';
    }
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'menuqr_auto_image_loading_attrs', 10, 3);

function menuqr_add_body_performance_classes(array $classes): array {
    $classes[] = 'fluuexqr-v60-performance';
    if (function_exists('menuqr_is_dashboard_context') && menuqr_is_dashboard_context()) {
        $classes[] = 'fluuexqr-dashboard-view';
    }
    if (function_exists('menuqr_is_customer_menu_context') && menuqr_is_customer_menu_context()) {
        $classes[] = 'fluuexqr-customer-menu-view';
    }
    return $classes;
}
add_filter('body_class', 'menuqr_add_body_performance_classes');

require_once MENUQR_THEME_DIR . '/inc/fqx-v119-home-restore.php';

// FluuexQR v133: Room 24-hour session + printable room card with Menu QR + WiFi QR.
require_once MENUQR_THEME_DIR . '/inc/fqx-v133-room-session-wifi-card.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v137-room-template-performance-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v138-v133-room-template-hard-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v141-room-template-reliable-ui.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v142-room-template-dashboard-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v144-ui-performance.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v145-restaurant-admin-ui.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v167-staff-management-ui.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v177-performance-optimizer.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v182-admin-routing-support-fix.php';
require_once MENUQR_THEME_DIR . '/inc/fqx-v183-frontend-ai-help.php';



/** v169 Menu Management safe actions: duplicate + availability toggle. */
if (!function_exists('fqx_v169_handle_toggle_item_availability')) {
    function fqx_v169_handle_toggle_item_availability(): void {
        if (!function_exists('menuqr_require_role')) { wp_die('Unauthorized'); }
        menuqr_require_role(['restaurant_admin', 'super_admin']);
        if (!isset($_POST['menuqr_item_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['menuqr_item_nonce'])), 'menuqr_item_quick_action')) {
            wp_die('Security check failed');
        }
        global $wpdb;
        $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
        $item_id = absint($_POST['item_id'] ?? 0);
        $status = !empty($_POST['is_available']) ? 1 : 0;
        menuqr_validate_restaurant_access($restaurant_id);
        if ($restaurant_id <= 0 || $item_id <= 0) {
            menuqr_redirect_back_with_status(['mq_notice' => 'item_invalid']);
        }
        $updated = false !== $wpdb->update(menuqr_table('items'), ['is_available' => $status, 'updated_at' => current_time('mysql')], ['id' => $item_id, 'restaurant_id' => $restaurant_id], ['%d','%s'], ['%d','%d']);
        menuqr_redirect_back_with_status(['mq_notice' => $updated ? 'item_saved' : 'item_db_error']);
    }
    add_action('admin_post_menuqr_toggle_item_availability', 'fqx_v169_handle_toggle_item_availability');
}

if (!function_exists('fqx_v169_handle_duplicate_item')) {
    function fqx_v169_handle_duplicate_item(): void {
        if (!function_exists('menuqr_require_role')) { wp_die('Unauthorized'); }
        menuqr_require_role(['restaurant_admin', 'super_admin']);
        if (!isset($_POST['menuqr_item_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['menuqr_item_nonce'])), 'menuqr_item_quick_action')) {
            wp_die('Security check failed');
        }
        global $wpdb;
        $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
        $item_id = absint($_POST['item_id'] ?? 0);
        menuqr_validate_restaurant_access($restaurant_id);
        $table = menuqr_table('items');
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND restaurant_id = %d", $item_id, $restaurant_id), ARRAY_A);
        if (!$item) { menuqr_redirect_back_with_status(['mq_notice' => 'item_invalid']); }
        unset($item['id']);
        $item['name'] = sanitize_text_field(($item['name'] ?? 'Menu Item') . ' Copy');
        $item['is_featured'] = 0;
        $item['created_at'] = current_time('mysql');
        $item['updated_at'] = current_time('mysql');
        $inserted = false !== $wpdb->insert($table, $item);
        menuqr_redirect_back_with_status(['mq_notice' => $inserted ? 'item_saved' : 'item_db_error']);
    }
    add_action('admin_post_menuqr_duplicate_item', 'fqx_v169_handle_duplicate_item');
}


// FluuexQR v171: Review management UI action handlers (safe option-backed state for review click rows).
if (!function_exists('fqx_v171_get_review_ui_state')) {
    function fqx_v171_get_review_ui_state(int $restaurant_id): array {
        $restaurant_id = absint($restaurant_id);
        $state = get_option('fqx_review_ui_state_' . $restaurant_id, []);
        return is_array($state) ? $state : [];
    }
}
if (!function_exists('fqx_v171_save_review_ui_state')) {
    function fqx_v171_save_review_ui_state(int $restaurant_id, array $state): void {
        update_option('fqx_review_ui_state_' . absint($restaurant_id), $state, false);
    }
}
if (!function_exists('fqx_v171_handle_review_ui_action')) {
    function fqx_v171_handle_review_ui_action(): void {
        menuqr_require_role(['restaurant_admin', 'super_admin', 'staff']);
        check_admin_referer('fqx_review_ui_action', 'fqx_review_nonce');
        $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
        menuqr_validate_restaurant_access($restaurant_id);
        $review_id = absint($_POST['review_id'] ?? 0);
        $review_action = sanitize_key(wp_unslash($_POST['review_ui_action'] ?? ''));
        $reply = sanitize_textarea_field(wp_unslash($_POST['manager_reply'] ?? ''));
        $state = fqx_v171_get_review_ui_state($restaurant_id);
        if ($review_id > 0) {
            $current = isset($state[$review_id]) && is_array($state[$review_id]) ? $state[$review_id] : [];
            if ($reply !== '') {
                $current['reply'] = $reply;
                $current['replied_at'] = current_time('mysql');
                $current['status'] = 'resolved';
            }
            switch ($review_action) {
                case 'resolve':
                    $current['status'] = 'resolved';
                    $current['resolved_at'] = current_time('mysql');
                    break;
                case 'feature':
                    $current['status'] = 'featured';
                    $current['featured'] = 1;
                    break;
                case 'hide':
                    $current['status'] = 'hidden';
                    $current['hidden'] = 1;
                    break;
                case 'publish':
                    $current['status'] = 'published';
                    $current['hidden'] = 0;
                    break;
                case 'reply':
                    if ($reply === '') {
                        $current['status'] = $current['status'] ?? 'pending_reply';
                    }
                    break;
            }
            $current['updated_at'] = current_time('mysql');
            $state[$review_id] = $current;
            fqx_v171_save_review_ui_state($restaurant_id, $state);
        }
        wp_safe_redirect(add_query_arg(['tab' => 'reviews', 'mq_notice' => 'review_action_saved'], menuqr_get_page_url_by_slug('restaurant-dashboard')));
        exit;
    }
    add_action('admin_post_fqx_review_ui_action', 'fqx_v171_handle_review_ui_action');
}

require_once MENUQR_THEME_DIR . '/inc/fqx-v180-home-page-working.php';

require_once MENUQR_THEME_DIR . '/inc/fqx-v185-superadmin-payment-gateway.php';

// v188 Restaurant Admin visibility, contrast, responsiveness and performance-only patch
require_once MENUQR_THEME_DIR . '/inc/fqx-v188-admin-visibility-performance.php';

// v191 Category Types / Subcategories under categories
require_once MENUQR_THEME_DIR . '/inc/fqx-v191-category-types.php';

// v194 Fast/smooth admin cache, bill and category reliability patch
require_once MENUQR_THEME_DIR . '/inc/fqx-v194-fast-smooth-admin.php';

require_once MENUQR_THEME_DIR . '/inc/fqx-v195-action-icon-audit-fixes.php';

// v196 Admin all-device responsive + button/icon audit hardfix
require_once MENUQR_THEME_DIR . '/inc/fqx-v196-admin-responsive-button-audit.php';

require_once MENUQR_THEME_DIR . '/inc/fqx-v197-responsive-pdf-wifi-fixes.php';

require_once MENUQR_THEME_DIR . '/inc/fqx-v198-mobile-responsive-room-wifi-final.php';

// v200 Secure QR token system with backwards compatibility
require_once MENUQR_THEME_DIR . '/inc/fqx-v200-secure-token-system.php';
// v206 Smart CSV Menu Upload
require_once MENUQR_THEME_DIR . '/inc/fqx-v206-smart-csv-menu-upload.php';
