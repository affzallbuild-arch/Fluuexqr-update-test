<?php
if (!defined('ABSPATH')) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class('menuqr-public-menu-page'); ?>>
<?php wp_body_open(); ?>
<main class="site-main site-main-customer-menu">
<?php
if (is_page('bill')) {
    echo do_shortcode('[menuqr_bill]');
} else {
    echo do_shortcode('[menuqr_menu]');
}
?>
</main>
<?php wp_footer(); ?>
</body>
</html>
