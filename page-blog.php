<?php
if (!defined('ABSPATH')) { exit; }
get_header();
$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$published_count = (int) wp_count_posts('post')->publish;
$category_count = (int) wp_count_terms('category', ['hide_empty' => true]);
$latest_post = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'ignore_sticky_posts' => true]);
$latest = $latest_post[0] ?? null;
$latest_month = $latest ? get_the_date('M Y', $latest) : date_i18n('M Y');
?>
<main class="fqx-blog-v181-page">
    <section class="fqx-blog-v181-hero">
        <div class="fqx-blog-v181-bg" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="mq-container fqx-blog-v181-hero-inner">
            <?php echo function_exists('menuqr_breadcrumbs') ? menuqr_breadcrumbs() : ''; ?>
            <div class="fqx-blog-v181-hero-grid">
                <div class="fqx-blog-v181-hero-copy">
                    <span class="fqx-blog-v181-kicker">FluuexQR Blog</span>
                    <h1>Restaurant QR Ordering, Billing & Hotel Automation Guides</h1>
                    <p>Practical guides, growth ideas, QR menu tips, billing workflows, kitchen display tutorials and hotel room QR automation strategies for restaurants and hotels.</p>
                    <form class="fqx-blog-v181-search" method="get" action="<?php echo esc_url(get_permalink()); ?>">
                        <label class="screen-reader-text" for="fq_blog_search">Search blog</label>
                        <input id="fq_blog_search" type="search" name="fq_blog_search" value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_GET['fq_blog_search'] ?? ''))); ?>" placeholder="Search QR menu, billing, hotel room QR...">
                        <button type="submit">Search</button>
                    </form>
                    <div class="fqx-blog-v181-tags">
                        <span>QR Menu</span><span>Hotel Room QR</span><span>Kitchen Display</span><span>Smart Billing</span><span>UPI Payment</span>
                    </div>
                </div>
                <aside class="fqx-blog-v181-hero-card">
                    <div class="fqx-blog-v181-card-glow"></div>
                    <span class="fqx-blog-v181-card-label">Latest Insight</span>
                    <?php if ($latest instanceof WP_Post) : ?>
                        <h2><a href="<?php echo esc_url(get_permalink($latest)); ?>"><?php echo esc_html(get_the_title($latest)); ?></a></h2>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt($latest), 22)); ?></p>
                        <a class="fqx-blog-v181-read" href="<?php echo esc_url(get_permalink($latest)); ?>">Read Latest →</a>
                    <?php else : ?>
                        <h2>Publish your first FluuexQR guide</h2>
                        <p>WordPress Admin → Posts me blog publish karte hi yahan automatic show hoga.</p>
                    <?php endif; ?>
                </aside>
            </div>
            <div class="fqx-blog-v181-stats">
                <div><strong><?php echo esc_html((string) $published_count); ?>+</strong><span>Published Guides</span></div>
                <div><strong><?php echo esc_html((string) $category_count); ?></strong><span>Knowledge Categories</span></div>
                <div><strong><?php echo esc_html($latest_month); ?></strong><span>Latest Updated</span></div>
                <div><strong>India</strong><span>Restaurant SaaS Focus</span></div>
            </div>
        </div>
    </section>

    <section class="fqx-blog-v181-section">
        <div class="mq-container">
            <?php echo function_exists('fqx_v120_blog_query_html') ? fqx_v120_blog_query_html($paged) : do_shortcode('[fqx_blog_posts]'); ?>
        </div>
    </section>
</main>
<?php get_footer(); ?>
