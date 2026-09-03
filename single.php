<?php get_header(); ?>
<section class="fq-blog-hero">
  <div class="mq-container narrow">
    <?php echo function_exists('menuqr_breadcrumbs') ? menuqr_breadcrumbs() : ''; ?>
    <h1><?php the_title(); ?></h1>
    <p><?php echo esc_html(get_the_date()); ?> · <?php the_author(); ?></p>
  </div>
</section>
<section class="fq-section">
  <div class="mq-container narrow">
    <article <?php post_class('fq-single-post'); ?>>
      <?php if (has_post_thumbnail()) : ?>
        <div class="fq-single-thumb"><?php the_post_thumbnail('large', ['loading' => 'lazy']); ?></div>
      <?php endif; ?>
      <div class="fq-single-content"><?php the_content(); ?></div>
      <div class="fq-related-posts">
        <h2>Related posts</h2>
        <div class="fq-blog-grid">
          <?php
          $related = get_posts([
              'post_type' => 'post',
              'posts_per_page' => 3,
              'post__not_in' => [get_the_ID()],
              'category__in' => wp_get_post_categories(get_the_ID()),
          ]);
          if ($related) :
              foreach ($related as $post) :
                  setup_postdata($post); ?>
                  <article class="fq-blog-card">
                    <a class="fq-blog-thumb" href="<?php the_permalink(); ?>"><?php if (has_post_thumbnail($post)) { echo get_the_post_thumbnail($post, 'medium_large', ['loading' => 'lazy']); } else { echo '<span>FluuexQR</span>'; } ?></a>
                    <div class="fq-blog-copy">
                      <span><?php echo esc_html(get_the_date('', $post)); ?></span>
                      <h3><a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
                    </div>
                  </article>
              <?php endforeach; wp_reset_postdata();
          else : ?>
            <article class="fq-blog-card"><div class="fq-blog-copy"><p>Add more posts to show related content here.</p></div></article>
          <?php endif; ?>
        </div>
      </div>
    </article>
  </div>
</section>
<?php get_footer(); ?>
