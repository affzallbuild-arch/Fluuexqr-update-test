<?php
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<section class="fq-generic-page fq-policy-page">
  <div class="mq-container narrow">
    <article class="fq-generic-card fq-policy-card">
      <?php while (have_posts()) : the_post(); ?>
        <span class="fq-home-kicker"><?php the_title(); ?></span>
        <h1><?php the_title(); ?></h1>
        <div class="fq-generic-content fq-policy-content"><?php the_content(); ?></div>
      <?php endwhile; ?>
    </article>
  </div>
</section>
<?php get_footer(); ?>
