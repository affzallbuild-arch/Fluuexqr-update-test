<?php
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<section class="fq-contact-page">
  <div class="mq-container fq-contact-grid">
    <div class="fq-contact-card fq-contact-copy">
      <span class="fq-home-kicker">Contact</span>
      <h1>Let’s build a better restaurant flow.</h1>
      <p>Tell us about your restaurant, goals, or support request. We will help you with QR menus, billing, kitchen workflow, and onboarding.</p>
      <div class="fq-contact-points">
        <div><strong>Email</strong><span><a href="mailto:hello@fluuexqr.com">hello@fluuexqr.com</a></span></div>
        <div><strong>Phone</strong><span><a href="tel:+919876543210">+91 98765 43210</a></span></div>
        <div><strong>Support</strong><span>Fast onboarding and setup assistance</span></div>
      </div>
    </div>
    <div class="fq-contact-card fq-contact-form-wrap">
      <?php if (shortcode_exists('contact-form-7')) : ?>
        <?php echo do_shortcode('[contact-form-7 id="ee266f0" title="Contact form 1"]'); ?>
      <?php else : ?>
        <form class="fq-fallback-contact-form" method="post">
          <input type="text" placeholder="Your Name" disabled>
          <input type="email" placeholder="Your Email" disabled>
          <textarea placeholder="Your Message" rows="6" disabled></textarea>
          <button type="button" class="btn btn-primary" disabled>Install Contact Form 7 to enable this form</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php get_footer(); ?>
