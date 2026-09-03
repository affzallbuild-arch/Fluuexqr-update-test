<?php get_header(); ?>
<div class="mq-container narrow"><div class="section-card"><?php if (have_posts()) : while (have_posts()) : the_post(); the_content(); endwhile; endif; ?></div></div>
<?php get_footer(); ?>
