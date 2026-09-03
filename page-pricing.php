<?php
if (!defined('ABSPATH')) { exit; }
$signup_url = function_exists('menuqr_get_page_url_by_slug') ? menuqr_get_page_url_by_slug('signup') : wp_registration_url();
$contact_url = function_exists('menuqr_get_page_url_by_slug') ? menuqr_get_page_url_by_slug('contact') : home_url('/contact/');
get_header();
$plans = [
  ['slug'=>'free_trial','label'=>'Free Trial','price'=>'₹0','period'=>'/ 10 Days','best'=>'Test complete FluuexQR setup','class'=>'','features'=>['Full access during trial','QR menu + cart + checkout','Table QR + Room QR during trial','Kitchen display + billing','UPI/Razorpay setup allowed','Trial cannot repeat automatically'],'missing'=>[],'btn'=>'Start Free Trial'],
  ['slug'=>'starter_5_table','label'=>'Starter 5 Table','price'=>'₹999','period'=>'/ month','best'=>'Small restaurants, cafés, dhabas with limited menu','class'=>'','features'=>['5 table QR ordering','5 categories','20 menu items','2 staff users','Cart, checkout and KDS','WhatsApp bill + paid/unpaid billing','Cash/UPI/Razorpay payment'],'missing'=>['Hotel Room QR','Room-wise billing','More than 5 tables/categories'],'btn'=>'Choose Starter Plan'],
  ['slug'=>'restaurant_all_access','label'=>'Restaurant All Access','price'=>'₹1,999','period'=>'/ month','best'=>'Restaurants, cafés, dhabas, cloud kitchens','class'=>'featured','features'=>['Restaurant QR menu','Table QR ordering','Kitchen display','Running/PDF/Thermal bill','WhatsApp bill + review link','UPI/Razorpay/Cash payment','Coupons, combos, reports, staff'],'missing'=>['Hotel Room QR','Room-wise billing','Room service ordering'],'btn'=>'Choose Restaurant Plan'],
  ['slug'=>'hotel_restaurant_full_access','label'=>'Hotel + Restaurant Full Access','price'=>'₹2,499','period'=>'/ month','best'=>'Hotels, resorts, guest houses','class'=>'','features'=>['Everything in Restaurant All Access','Hotel Room QR ordering','Room-wise bill','Room number kitchen tracking','Hotel guest order tracking','Room WhatsApp bill','Priority support badge'],'missing'=>['White label','Custom domain','API access by default'],'btn'=>'Choose Hotel Plan'],
];
?>
<main class="fqx-marketing-page fqx-pricing-page-new">
  <section class="fqx-page-hero-new">
    <div class="fqx-wrap"><span class="fqx-kicker">FluuexQR Pricing</span><h1>Simple plans. No confusion.</h1><p>Choose Free Trial, Starter 5 Table, Restaurant All Access, or Hotel + Restaurant Full Access. Old Basic/Premium/Growth plans are removed.</p></div>
  </section>
  <section class="fqx-section">
    <div class="fqx-wrap">
      <div class="fqx-price-grid full">
        <?php foreach ($plans as $p): ?>
        <article class="fqx-price-card <?php echo esc_attr($p['class']); ?>">
          <?php if ($p['class']==='featured'): ?><em>Recommended</em><?php endif; ?>
          <span><?php echo esc_html($p['label']); ?></span>
          <h3><?php echo esc_html($p['price']); ?> <small><?php echo esc_html($p['period']); ?></small></h3>
          <p><?php echo esc_html($p['best']); ?></p>
          <h4>Included</h4><ul><?php foreach ($p['features'] as $f): ?><li>✓ <?php echo esc_html($f); ?></li><?php endforeach; ?></ul>
          <?php if (!empty($p['missing'])): ?><h4>Not included by default</h4><ul class="muted-list"><?php foreach ($p['missing'] as $f): ?><li>– <?php echo esc_html($f); ?></li><?php endforeach; ?></ul><?php endif; ?>
          <a class="fqx-btn <?php echo $p['class']==='featured' ? 'fqx-btn-primary' : 'fqx-btn-soft'; ?>" href="<?php echo esc_url($signup_url . '?plan=' . $p['slug']); ?>"><?php echo esc_html($p['btn']); ?></a>
        </article>
        <?php endforeach; ?>
      </div>
      <div class="fqx-compare-card">
        <h2>Need help choosing?</h2><p>Use ₹1,999 for restaurant/table QR. Use ₹2,499 when hotel room QR and room-wise billing are required.</p><a class="fqx-btn fqx-btn-primary" href="<?php echo esc_url($contact_url); ?>">Talk to FluuexQR</a>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>
