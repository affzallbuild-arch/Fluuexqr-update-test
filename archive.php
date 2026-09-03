<?php
get_header();
$archive_title = is_home() || is_post_type_archive('post') ? 'FluuexQR Blog' : single_term_title('', false);
$archive_desc = is_home() || is_post_type_archive('post')
    ? 'Insights on QR menu systems, restaurant billing, WhatsApp bills, kitchen management, and restaurant automation.'
    : term_description();
?>
<section class="fq-blog-hero">
  <div class="mq-container narrow">
    <?php echo function_exists('menuqr_breadcrumbs') ? menuqr_breadcrumbs() : ''; ?>
    <h1><?php echo esc_html($archive_title); ?></h1>
    <p><?php echo wp_kses_post($archive_desc ?: 'Latest FluuexQR articles and guides.'); ?></p>
  </div>
</section>
<section class="fq-section">
  <div class="mq-container">
    <div class="fq-blog-grid">
      <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class('fq-blog-card'); ?>>
          <a class="fq-blog-thumb" href="<?php the_permalink(); ?>">
            <?php if (has_post_thumbnail()) { the_post_thumbnail('large', ['loading' => 'lazy']); } else { echo '<span>FluuexQR</span>'; } ?>
          </a>
          <div class="fq-blog-copy">
            <span><?php echo esc_html(get_the_date()); ?> · <?php the_author(); ?></span>
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
            <a class="btn btn-outline btn-sm" href="<?php the_permalink(); ?>">Read More</a>
          </div>
        </article>
      <?php endwhile; else : ?>
        <article class="fq-blog-card"><div class="fq-blog-copy"><h2>No posts yet</h2><p>Publish your first FluuexQR blog post from WordPress admin.</p></div></article>
      <?php endif; ?>
    </div>
    <div class="mq-pagination"><?php the_posts_pagination(); ?></div>
  </div>
</section>
<?php get_footer(); ?>
