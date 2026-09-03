<?php
if (!defined('ABSPATH')) { exit; }
get_header();
$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
?>
<main class="fqx-v120-blog-wrap">
    <section class="fqx-v120-blog-hero">
        <div class="mq-container">
            <?php echo function_exists('menuqr_breadcrumbs') ? menuqr_breadcrumbs() : ''; ?>
            <span class="fqx-v120-kicker">FluuexQR Blog</span>
            <h1>Restaurant QR ordering, billing and hotel automation guides.</h1>
            <p>Latest articles for QR menu, WhatsApp bill, kitchen dashboard, hotel room ordering and restaurant automation.</p>
        </div>
    </section>
    <section class="fqx-v120-blog-section">
        <div class="mq-container">
            <?php echo function_exists('fqx_v120_blog_query_html') ? fqx_v120_blog_query_html($paged) : ''; ?>
        </div>
    </section>
</main>
<?php get_footer(); ?>
