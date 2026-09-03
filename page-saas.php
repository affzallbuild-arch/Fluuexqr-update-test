<?php
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<section class="fq-saas-page">
  <div class="mq-container">
    <?php while (have_posts()) : the_post(); ?>
      <?php the_content(); ?>
    <?php endwhile; ?>
  </div>
</section>
<?php get_footer(); ?>
