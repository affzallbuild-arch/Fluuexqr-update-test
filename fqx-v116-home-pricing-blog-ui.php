<?php
if (!defined('ABSPATH')) { exit; }

function fqx_v116_enqueue_home_dashboard_assets(): void {
    // v119: Keep original v112 home page design same-to-same. Load v116 UI CSS only
    // where it is needed: pricing, blog and dashboard pages.
    if (is_front_page() || is_page('home')) { return; }
    if (is_page_template('page-pricing.php') || is_page_template('page-blog.php') || is_home() || is_page(['pricing','blog','restaurant-dashboard']) || is_page_template('page-dashboard.php')) {
        wp_enqueue_style('fqx-v116-home-dashboard', MENUQR_THEME_URI . '/assets/css/fqx-v116-home-dashboard.min.css', ['fluuexqr-v81-bundle'], menuqr_asset_version('assets/css/fqx-v116-home-dashboard.min.css'));
    }
}
add_action('wp_enqueue_scripts', 'fqx_v116_enqueue_home_dashboard_assets', 55);

function fqx_v116_repair_core_pages(): void {
    if ((int) get_option('fqx_v116_pages_repaired', 0) >= 1) { return; }
    $updates = [
        'home' => ['title' => 'Home', 'template' => 'front-page.php', 'content' => ''],
        'pricing' => ['title' => 'Pricing', 'template' => 'page-pricing.php', 'content' => ''],
        'blog' => ['title' => 'Blog', 'template' => 'page-blog.php', 'content' => ''],
        'restaurant-dashboard' => ['title' => 'Restaurant Dashboard', 'template' => 'page-dashboard.php', 'content' => '[menuqr_dashboard]'],
    ];
    foreach ($updates as $slug => $data) {
        $page = get_page_by_path($slug);
        if ($page instanceof WP_Post) {
            wp_update_post(['ID'=>$page->ID,'post_title'=>$data['title'],'post_name'=>$slug,'post_status'=>'publish','post_content'=>$data['content']]);
            update_post_meta($page->ID, '_wp_page_template', $data['template']);
        } else {
            $id = wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$data['title'],'post_name'=>$slug,'post_content'=>$data['content']]);
            if ($id && !is_wp_error($id)) { update_post_meta((int)$id, '_wp_page_template', $data['template']); }
        }
    }
    $home = get_page_by_path('home');
    if ($home instanceof WP_Post) { update_option('show_on_front', 'page'); update_option('page_on_front', $home->ID); }
    // v121: Blog must remain a normal page so page-blog.php renders.
    $blog = get_page_by_path('blog');
    if ($blog instanceof WP_Post && (int) get_option('page_for_posts') === (int) $blog->ID) { update_option('page_for_posts', 0); }
    update_option('fqx_v116_pages_repaired', 1, false);
}
add_action('after_switch_theme', 'fqx_v116_repair_core_pages', 20);
add_action('init', 'fqx_v116_repair_core_pages', 20);

function fqx_v116_body_classes(array $classes): array {
    if (is_front_page()) { $classes[] = 'fqx-v116-home-active'; }
    if (is_page_template('page-pricing.php')) { $classes[] = 'fqx-v116-pricing-active'; }
    if (is_home() || is_page_template('page-blog.php')) { $classes[] = 'fqx-v116-blog-active'; }
    if (is_page_template('page-dashboard.php') || is_page('restaurant-dashboard')) { $classes[] = 'fqx-v116-dashboard-active'; }
    return $classes;
}
add_filter('body_class', 'fqx_v116_body_classes');

function fqx_v116_remove_expensive_embeds_on_marketing(): void {
    if (is_admin()) { return; }
    if (is_front_page() || is_page(['pricing','blog']) || is_home()) {
        wp_deregister_script('wp-embed');
    }
}
add_action('wp_enqueue_scripts', 'fqx_v116_remove_expensive_embeds_on_marketing', 100);

function fqx_v116_final_plan_labels(): array {
    return [
        'free_trial' => ['name'=>'Free Trial','price'=>0,'days'=>10],
        'starter_5_table' => ['name'=>'Starter 5 Table','price'=>999,'days'=>30],
        'restaurant_all_access' => ['name'=>'Restaurant All Access','price'=>1999,'days'=>30],
        'hotel_restaurant_full_access' => ['name'=>'Hotel + Restaurant Full Access','price'=>2499,'days'=>30],
    ];
}
