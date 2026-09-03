<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v121 real fixes: force Blog page rendering, visible Restaurant Admin UI skin,
 * SEO meta/schema, and safer front-end performance cleanup.
 */

function fqx_v121_repair_blog_pricing_dashboard_pages(): void {
    $version = (int) get_option('fqx_v121_real_fix_done', 0);
    if ($version >= 1) { return; }

    $pages = [
        'blog' => ['Blog', 'page-blog.php', '[fqx_blog_posts]'],
        'pricing' => ['Pricing', 'page-pricing.php', ''],
        'restaurant-dashboard' => ['Restaurant Dashboard', 'page-dashboard.php', '[menuqr_dashboard]'],
    ];

    foreach ($pages as $slug => $data) {
        [$title, $template, $content] = $data;
        $page = get_page_by_path($slug);
        if ($page instanceof WP_Post) {
            wp_update_post([
                'ID' => $page->ID,
                'post_title' => $title,
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => $content,
            ]);
            update_post_meta($page->ID, '_wp_page_template', $template);
        } else {
            $id = wp_insert_post([
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => $slug,
                'post_content' => $content,
            ]);
            if ($id && !is_wp_error($id)) {
                update_post_meta((int) $id, '_wp_page_template', $template);
            }
        }
    }

    $blog = get_page_by_path('blog');
    if ($blog instanceof WP_Post && (int) get_option('page_for_posts') === (int) $blog->ID) {
        update_option('page_for_posts', 0);
    }

    update_option('fqx_v121_real_fix_done', 1, false);
}
add_action('after_switch_theme', 'fqx_v121_repair_blog_pricing_dashboard_pages', 99);
add_action('init', 'fqx_v121_repair_blog_pricing_dashboard_pages', 99);

function fqx_v121_force_templates(string $template): string {
    $theme_dir = trailingslashit(get_template_directory());
    if (is_page('blog') || (is_home() && get_query_var('pagename') === 'blog')) {
        $custom = $theme_dir . 'page-blog.php';
        if (file_exists($custom)) { return $custom; }
    }
    if (is_page('pricing')) {
        $custom = $theme_dir . 'page-pricing.php';
        if (file_exists($custom)) { return $custom; }
    }
    if (is_page('restaurant-dashboard')) {
        $custom = $theme_dir . 'page-dashboard.php';
        if (file_exists($custom)) { return $custom; }
    }
    return $template;
}
add_filter('template_include', 'fqx_v121_force_templates', 999);

function fqx_v121_body_classes(array $classes): array {
    if (is_page('blog') || is_page_template('page-blog.php')) { $classes[] = 'fqx-v121-blog-fixed'; }
    if (is_page('restaurant-dashboard') || is_page_template('page-dashboard.php')) { $classes[] = 'fqx-v121-restaurant-admin-fixed'; }
    if (is_page('pricing') || is_page_template('page-pricing.php')) { $classes[] = 'fqx-v121-pricing-fixed'; }
    return $classes;
}
add_filter('body_class', 'fqx_v121_body_classes', 99);

function fqx_v121_enqueue_visible_ui_and_speed_assets(): void {
    if (is_admin()) { return; }

    $is_blog = is_page('blog') || is_page_template('page-blog.php');
    $is_pricing = is_page('pricing') || is_page_template('page-pricing.php');
    $is_dashboard = is_page('restaurant-dashboard') || is_page_template('page-dashboard.php') || (is_singular() && has_shortcode((string) get_post_field('post_content', get_queried_object_id()), 'menuqr_dashboard'));

    if ($is_blog || $is_pricing || $is_dashboard) {
        wp_enqueue_style('fqx-v121-real-ui-fix', MENUQR_THEME_URI . '/assets/css/fqx-v121-real-ui-fix.min.css', ['fluuexqr-v81-bundle'], menuqr_asset_version('assets/css/fqx-v121-real-ui-fix.min.css'));
    }
    if ($is_dashboard || $is_blog) {
        wp_enqueue_script('fqx-v121-real-ui-fix', MENUQR_THEME_URI . '/assets/js/fqx-v121-real-ui-fix.min.js', [], menuqr_asset_version('assets/js/fqx-v121-real-ui-fix.min.js'), true);
    }

    // Marketing/blog/pricing pages do not need QR ordering heavy UI scripts.
    if ($is_blog || $is_pricing) {
        wp_dequeue_script('fluuexqr-v90-preview-ui');
        wp_dequeue_script('fluuexqr-v91-v6-mobile-fixed-ui');
        wp_dequeue_script('fluuexqr-v95-premium-responsive');
        wp_dequeue_script('fluuexqr-v104-bill-page-force');
        wp_dequeue_style('fluuexqr-v101-foodwala-menu-ui');
    }
}
add_action('wp_enqueue_scripts', 'fqx_v121_enqueue_visible_ui_and_speed_assets', 999);

function fqx_v121_seo_description(): string {
    if (is_front_page()) {
        return 'FluuexQR is an all-in-one QR ordering system for restaurants and hotels with table QR, room QR, kitchen dashboard, billing, UPI and WhatsApp bill.';
    }
    if (is_page('pricing')) {
        return 'Choose FluuexQR pricing plans: 10-day free trial, Restaurant All Access at ₹1,999/month, and Hotel + Restaurant Full Access at ₹2,499/month.';
    }
    if (is_page('blog') || is_home()) {
        return 'Read FluuexQR blogs about restaurant QR menu systems, hotel room QR ordering, kitchen display, billing, payments, WhatsApp bills and restaurant growth.';
    }
    if (is_singular('post')) {
        return wp_strip_all_tags(wp_trim_words(get_the_excerpt() ?: get_the_content(null, false), 28, ''));
    }
    return wp_strip_all_tags(get_bloginfo('description'));
}

function fqx_v121_document_title_parts(array $title): array {
    if (is_front_page()) {
        $title['title'] = 'Restaurant & Hotel QR Ordering System';
        $title['site'] = 'FluuexQR';
    } elseif (is_page('pricing')) {
        $title['title'] = 'Pricing Plans for Restaurants & Hotels';
        $title['site'] = 'FluuexQR';
    } elseif (is_page('blog') || is_home()) {
        $title['title'] = 'Restaurant QR Ordering Blog';
        $title['site'] = 'FluuexQR';
    }
    return $title;
}
add_filter('document_title_parts', 'fqx_v121_document_title_parts', 99);

function fqx_v121_output_seo_tags(): void {
    if (is_admin()) { return; }
    $desc = fqx_v121_seo_description();
    $canonical = is_singular() ? get_permalink() : home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''));
    $canonical = $canonical ? trailingslashit($canonical) : home_url('/');
    $logo = function_exists('menuqr_get_brand_logo_url') ? menuqr_get_brand_logo_url() : '';
    $title = wp_get_document_title();
    echo "\n" . '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
    echo '<meta property="og:type" content="' . (is_singular('post') ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
    if ($logo) { echo '<meta property="og:image" content="' . esc_url($logo) . '">' . "\n"; }
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => home_url('/#organization'),
                'name' => 'FluuexQR',
                'url' => home_url('/'),
                'logo' => $logo,
                'description' => 'All-in-One QR Ordering System for Restaurants & Hotels.',
            ],
            [
                '@type' => 'WebSite',
                '@id' => home_url('/#website'),
                'url' => home_url('/'),
                'name' => 'FluuexQR',
                'publisher' => ['@id' => home_url('/#organization')],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => home_url('/?s={search_term_string}'),
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ];

    if (is_singular('post')) {
        $schema['@graph'][] = [
            '@type' => 'BlogPosting',
            'headline' => get_the_title(),
            'description' => $desc,
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => ['@type' => 'Person', 'name' => get_the_author()],
            'publisher' => ['@id' => home_url('/#organization')],
            'mainEntityOfPage' => get_permalink(),
        ];
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'fqx_v121_output_seo_tags', 2);

function fqx_v121_speed_cleanup(): void {
    if (is_admin()) { return; }
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
}
add_action('init', 'fqx_v121_speed_cleanup');

function fqx_v121_defer_noncritical_scripts(string $tag, string $handle, string $src): string {
    if (is_admin() || strpos($tag, ' defer') !== false) { return $tag; }
    $defer_handles = ['fqx-v121-real-ui-fix', 'fqx-v120-dashboard-fast', 'fqx-v119-home-restore'];
    if (in_array($handle, $defer_handles, true)) {
        return str_replace(' src=', ' defer src=', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'fqx_v121_defer_noncritical_scripts', 10, 3);
