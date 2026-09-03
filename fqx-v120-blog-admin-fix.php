<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v120: Blog page + Restaurant Admin UI fix.
 * Keeps v112 home page design untouched. Only repairs /blog and dashboard UI assets.
 */

function fqx_v120_template_path(string $file): string {
    return trailingslashit(get_template_directory()) . ltrim($file, '/');
}

function fqx_v120_repair_blog_page(): void {
    if (is_admin() && !current_user_can('manage_options')) { return; }

    $version = (int) get_option('fqx_v120_blog_repaired', 0);
    if ($version >= 1) { return; }

    $page = get_page_by_path('blog');
    if ($page instanceof WP_Post) {
        wp_update_post([
            'ID' => $page->ID,
            'post_title' => 'Blog',
            'post_name' => 'blog',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '[fqx_blog_posts]',
        ]);
        update_post_meta($page->ID, '_wp_page_template', 'page-blog.php');
    } else {
        $page_id = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Blog',
            'post_name' => 'blog',
            'post_content' => '[fqx_blog_posts]',
        ]);
        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta((int) $page_id, '_wp_page_template', 'page-blog.php');
        }
    }

    // Do not use Blog as the posts index, because WordPress ignores page-blog.php for page_for_posts.
    // Keeping it as a normal page makes /blog/ render the custom FluuexQR blog template.
    if ((int) get_option('page_for_posts') > 0) {
        $blog_page = get_page_by_path('blog');
        if ($blog_page instanceof WP_Post && (int) get_option('page_for_posts') === (int) $blog_page->ID) {
            update_option('page_for_posts', 0);
        }
    }

    update_option('fqx_v120_blog_repaired', 1, false);
}
add_action('after_switch_theme', 'fqx_v120_repair_blog_page', 25);
add_action('init', 'fqx_v120_repair_blog_page', 25);

function fqx_v120_force_blog_template(string $template): string {
    if (is_page('blog')) {
        $custom = fqx_v120_template_path('page-blog.php');
        if (file_exists($custom)) { return $custom; }
    }
    return $template;
}
add_filter('template_include', 'fqx_v120_force_blog_template', 60);

function fqx_v120_blog_query_html(int $paged = 1): string {
    $paged = max(1, $paged);
    $search = sanitize_text_field(wp_unslash($_GET['fq_blog_search'] ?? ''));
    $cat_id = absint($_GET['fq_blog_cat'] ?? 0);

    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'ignore_sticky_posts' => true,
        'posts_per_page' => 9,
        'paged' => $paged,
    ];
    if ($search !== '') { $args['s'] = $search; }
    if ($cat_id > 0) { $args['cat'] = $cat_id; }

    $blog_query = new WP_Query($args);
    $blog_page = get_page_by_path('blog');
    $blog_url = $blog_page instanceof WP_Post ? get_permalink($blog_page) : home_url('/blog/');
    $categories = get_categories(['hide_empty' => true, 'number' => 8, 'orderby' => 'count', 'order' => 'DESC']);
    $recent_posts = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 4, 'ignore_sticky_posts' => true]);

    ob_start();
    ?>
    <div class="fqx-blog-v181-layout">
        <main class="fqx-blog-v181-main">
            <div class="fqx-blog-v181-toolbar">
                <div>
                    <span class="fqx-blog-v181-small">Knowledge Hub</span>
                    <h2>Latest Restaurant Automation Articles</h2>
                </div>
                <form class="fqx-blog-v181-filter" method="get" action="<?php echo esc_url($blog_url); ?>">
                    <input type="search" name="fq_blog_search" value="<?php echo esc_attr($search); ?>" placeholder="Search posts...">
                    <select name="fq_blog_cat" aria-label="Select category">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat) : ?>
                            <option value="<?php echo esc_attr((string) $cat->term_id); ?>" <?php selected($cat_id, (int) $cat->term_id); ?>><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Filter</button>
                    <?php if ($search !== '' || $cat_id > 0) : ?>
                        <a class="fqx-blog-v181-reset" href="<?php echo esc_url($blog_url); ?>">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($categories) : ?>
                <div class="fqx-blog-v181-category-pills">
                    <a class="<?php echo $cat_id === 0 ? 'active' : ''; ?>" href="<?php echo esc_url($blog_url); ?>">All</a>
                    <?php foreach ($categories as $cat) : ?>
                        <a class="<?php echo $cat_id === (int) $cat->term_id ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('fq_blog_cat', (int) $cat->term_id, $blog_url)); ?>"><?php echo esc_html($cat->name); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="fqx-blog-v181-grid">
                <?php if ($blog_query->have_posts()) : ?>
                    <?php while ($blog_query->have_posts()) : $blog_query->the_post(); ?>
                        <?php
                        $post_cats = get_the_category();
                        $first_cat = $post_cats[0]->name ?? 'FluuexQR';
                        $read_time = max(2, (int) ceil(str_word_count(wp_strip_all_tags(get_the_content())) / 220));
                        ?>
                        <article <?php post_class('fqx-blog-v181-card'); ?>>
                            <a class="fqx-blog-v181-thumb" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                                <?php else : ?>
                                    <span>FluuexQR</span>
                                <?php endif; ?>
                                <b><?php echo esc_html($first_cat); ?></b>
                            </a>
                            <div class="fqx-blog-v181-copy">
                                <div class="fqx-blog-v181-meta"><span><?php echo esc_html(get_the_date('M d, Y')); ?></span><span><?php echo esc_html((string) $read_time); ?> min read</span></div>
                                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 26)); ?></p>
                                <div class="fqx-blog-v181-footer">
                                    <span><?php echo esc_html(get_the_author()); ?></span>
                                    <a href="<?php the_permalink(); ?>">Read More →</a>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else : ?>
                    <article class="fqx-blog-v181-empty">
                        <strong>No blogs found.</strong>
                        <p><?php echo ($search !== '' || $cat_id > 0) ? 'Try another search or reset filters.' : 'Go to WordPress Admin → Posts → Add New. Publish your first blog and it will show here.'; ?></p>
                        <a href="<?php echo esc_url($blog_url); ?>">View All Posts</a>
                    </article>
                <?php endif; ?>
            </div>
            <?php
            $pagination_args = [
                'total' => max(1, (int) $blog_query->max_num_pages),
                'current' => $paged,
                'prev_text' => '← Previous',
                'next_text' => 'Next →',
                'type' => 'list',
            ];
            if ($search !== '') { $pagination_args['add_args']['fq_blog_search'] = $search; }
            if ($cat_id > 0) { $pagination_args['add_args']['fq_blog_cat'] = $cat_id; }
            $links = paginate_links($pagination_args);
            if ($links) {
                echo '<nav class="fqx-blog-v181-pagination" aria-label="Blog pagination">' . wp_kses_post($links) . '</nav>';
            }
            ?>
        </main>

        <aside class="fqx-blog-v181-sidebar">
            <div class="fqx-blog-v181-side-card fqx-blog-v181-cta-card">
                <span>Free Demo</span>
                <h3>Want QR ordering in your restaurant?</h3>
                <p>Book a FluuexQR demo and see table QR, room QR, kitchen display and billing workflow live.</p>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>">Book Demo</a>
            </div>
            <?php if ($categories) : ?>
                <div class="fqx-blog-v181-side-card">
                    <h3>Popular Categories</h3>
                    <ul class="fqx-blog-v181-cat-list">
                        <?php foreach ($categories as $cat) : ?>
                            <li><a href="<?php echo esc_url(add_query_arg('fq_blog_cat', (int) $cat->term_id, $blog_url)); ?>"><span><?php echo esc_html($cat->name); ?></span><b><?php echo esc_html((string) $cat->count); ?></b></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($recent_posts) : ?>
                <div class="fqx-blog-v181-side-card">
                    <h3>Recent Guides</h3>
                    <div class="fqx-blog-v181-recent-list">
                        <?php foreach ($recent_posts as $rp) : ?>
                            <a href="<?php echo esc_url(get_permalink($rp)); ?>">
                                <span><?php echo esc_html(get_the_date('M d', $rp)); ?></span>
                                <strong><?php echo esc_html(wp_trim_words(get_the_title($rp), 9)); ?></strong>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
    <?php
    wp_reset_postdata();
    return (string) ob_get_clean();
}
function fqx_v120_blog_shortcode(): string {
    $paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
    return fqx_v120_blog_query_html($paged);
}
add_shortcode('fqx_blog_posts', 'fqx_v120_blog_shortcode');

function fqx_v120_is_restaurant_dashboard_page(): bool {
    return is_page_template('page-dashboard.php') || is_page(['restaurant-dashboard', 'dashboard']) || (is_singular() && has_shortcode((string) get_post_field('post_content', get_queried_object_id()), 'menuqr_dashboard'));
}

function fqx_v120_enqueue_blog_admin_assets(): void {
    if (is_admin()) { return; }

    $is_blog = is_page('blog') || is_page_template('page-blog.php') || is_home();
    $is_dashboard = fqx_v120_is_restaurant_dashboard_page();

    if ($is_blog || $is_dashboard) {
        wp_enqueue_style('fqx-v120-blog-admin-fix', MENUQR_THEME_URI . '/assets/css/fqx-v120-blog-admin-fix.min.css', ['fluuexqr-v81-bundle'], menuqr_asset_version('assets/css/fqx-v120-blog-admin-fix.min.css'));
    }

    if ($is_blog) {
        wp_enqueue_style('fqx-v181-blog-ui', MENUQR_THEME_URI . '/assets/css/fqx-v181-blog-ui.css', [], menuqr_asset_version('assets/css/fqx-v181-blog-ui.css'));
    }

    if ($is_dashboard) {
        wp_enqueue_script('fqx-v120-dashboard-fast', MENUQR_THEME_URI . '/assets/js/fqx-v120-dashboard-fast.min.js', [], menuqr_asset_version('assets/js/fqx-v120-dashboard-fast.min.js'), true);
    }
}
add_action('wp_enqueue_scripts', 'fqx_v120_enqueue_blog_admin_assets', 75);

function fqx_v120_body_classes(array $classes): array {
    if (is_page('blog') || is_page_template('page-blog.php') || is_home()) { $classes[] = 'fqx-v120-blog-page'; }
    if (fqx_v120_is_restaurant_dashboard_page()) { $classes[] = 'fqx-v120-restaurant-admin-ui'; }
    return $classes;
}
add_filter('body_class', 'fqx_v120_body_classes', 30);
