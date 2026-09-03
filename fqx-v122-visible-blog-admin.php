<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v122: make Blog visible in header/footer/home, force /blog page, and load the visible premium admin skin.
 */
function fqx_v122_repair_visible_pages(): void {
    $version = (int) get_option('fqx_v122_visible_blog_admin_done', 0);
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

    update_option('fqx_v122_visible_blog_admin_done', 1, false);
}
add_action('after_switch_theme', 'fqx_v122_repair_visible_pages', 999);
add_action('init', 'fqx_v122_repair_visible_pages', 999);

function fqx_v122_force_blog_and_dashboard_templates(string $template): string {
    $theme_dir = trailingslashit(get_template_directory());
    if (is_page('blog')) {
        $custom = $theme_dir . 'page-blog.php';
        if (file_exists($custom)) { return $custom; }
    }
    if (is_page('restaurant-dashboard') || is_page('dashboard')) {
        $custom = $theme_dir . 'page-dashboard.php';
        if (file_exists($custom)) { return $custom; }
    }
    if (is_page('pricing')) {
        $custom = $theme_dir . 'page-pricing.php';
        if (file_exists($custom)) { return $custom; }
    }
    return $template;
}
add_filter('template_include', 'fqx_v122_force_blog_and_dashboard_templates', 1000);

function fqx_v122_is_restaurant_dashboard(): bool {
    if (is_page(['restaurant-dashboard', 'dashboard']) || is_page_template('page-dashboard.php')) { return true; }
    $id = get_queried_object_id();
    return $id > 0 && has_shortcode((string) get_post_field('post_content', $id), 'menuqr_dashboard');
}

function fqx_v122_body_classes(array $classes): array {
    if (is_front_page()) { $classes[] = 'fqx-v122-home-blog-linked'; }
    if (is_page('blog') || is_page_template('page-blog.php')) { $classes[] = 'fqx-v122-blog-page'; }
    if (fqx_v122_is_restaurant_dashboard()) { $classes[] = 'fqx-v122-admin-visible'; }
    return $classes;
}
add_filter('body_class', 'fqx_v122_body_classes', 1000);

function fqx_v122_enqueue_visible_assets(): void {
    if (is_admin()) { return; }
    $is_blog = is_page('blog') || is_page_template('page-blog.php');
    $is_dashboard = fqx_v122_is_restaurant_dashboard();
    if (is_front_page() || $is_blog || $is_dashboard || is_page('pricing')) {
        wp_enqueue_style('fqx-v122-blog-admin-visible', MENUQR_THEME_URI . '/assets/css/fqx-v122-blog-admin-visible.min.css', [], menuqr_asset_version('assets/css/fqx-v122-blog-admin-visible.min.css'));
    }
    if ($is_dashboard) {
        wp_enqueue_script('fqx-v122-admin-visible', MENUQR_THEME_URI . '/assets/js/fqx-v122-admin-visible.min.js', [], menuqr_asset_version('assets/js/fqx-v122-admin-visible.min.js'), true);
    }
}
add_action('wp_enqueue_scripts', 'fqx_v122_enqueue_visible_assets', 1001);

function fqx_v122_flush_rewrite_once(): void {
    if ((int) get_option('fqx_v122_rewrite_flushed', 0) >= 1) { return; }
    flush_rewrite_rules(false);
    update_option('fqx_v122_rewrite_flushed', 1, false);
}
add_action('init', 'fqx_v122_flush_rewrite_once', 1000);
