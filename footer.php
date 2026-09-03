</main>
<?php
$menuqr_is_dashboard = function_exists('menuqr_is_dashboard_context') ? menuqr_is_dashboard_context() : false;
$menuqr_is_customer_menu_page = function_exists('menuqr_is_customer_menu_context') ? menuqr_is_customer_menu_context() : false;
$logo_url = function_exists('menuqr_get_brand_logo_url') ? menuqr_get_brand_logo_url() : '';
?>
<?php if (!$menuqr_is_dashboard && !$menuqr_is_customer_menu_page) : ?>
<footer class="footer">
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <a class="nav-logo" href="<?php echo esc_url(home_url('/')); ?>" style="display:flex;align-items:center;gap:10px;font-family:var(--fh);font-size:1.3rem;font-weight:800;color:#fff">
          <?php if (function_exists('fqx_brand_logo_img')) { echo fqx_brand_logo_img('main', 'fqx-footer-logo', 'FluuexQR Hotel & Restaurant Automation', 'lazy'); } elseif ($logo_url) { ?><img src="<?php echo esc_url($logo_url); ?>" width="1446" height="544" alt="FluuexQR" loading="lazy" decoding="async"><?php } else { ?><div class="nav-logo-mark">🍽️</div><?php } ?>
        </a>
        <p>All-in-one QR menu ordering, kitchen display, hotel room QR, thermal billing & analytics for restaurants and hotels across India.</p>
        <div class="footer-socials">
          <a href="https://www.facebook.com/share/1Amp2PyWdo/" target="_blank" rel="noopener noreferrer" class="soc" aria-label="Facebook">f</a>
          <a href="https://www.instagram.com/fluuexofficial_?igsh=MTBva3ZxY2xyMzFubA==" target="_blank" rel="noopener noreferrer" class="soc" aria-label="Instagram">◎</a>
          <a href="https://www.linkedin.com/in/fluuex-technologies-259a70408?utm_source=share_via&utm_content=profile&utm_medium=member_android" target="_blank" rel="noopener noreferrer" class="soc" aria-label="LinkedIn">in</a>
          <a href="mailto:hello@fluuexqr.com" class="soc" aria-label="Email">✉</a>
        </div>
      </div>
      <div class="footer-col">
        <h5>Product</h5>
        <ul>
          <li><a href="<?php echo esc_url(home_url('/#features')); ?>">Features</a></li>
          <li><a href="<?php echo esc_url(home_url('/pricing/')); ?>">Pricing</a></li>
          <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a></li>
          <li><a href="<?php echo esc_url(home_url('/#dashboard')); ?>">Dashboard</a></li>
          <li><a href="<?php echo esc_url(home_url('/restaurant-qr-menu/')); ?>">Restaurant QR Menu</a></li>
          <li><a href="<?php echo esc_url(home_url('/hotel-room-qr-ordering/')); ?>">Hotel Room QR</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Support</h5>
        <ul>
          <li><a href="<?php echo esc_url(home_url('/support/')); ?>">Help Center</a></li>
          <li><a href="<?php echo esc_url(home_url('/documentation/')); ?>">Documentation</a></li>
          <li><a href="<?php echo esc_url(home_url('/faq/')); ?>">FAQ</a></li>
          <li><a href="<?php echo esc_url(home_url('/contact-us/')); ?>">Contact Us</a></li>
          <li><a href="mailto:support@fluuexqr.com">support@fluuexqr.com</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Cities</h5>
        <ul>
          <li><a href="<?php echo esc_url(home_url('/restaurant-qr-menu-system-purnia/')); ?>">Purnia</a></li>
          <li><a href="<?php echo esc_url(home_url('/restaurant-qr-menu-system-katihar/')); ?>">Katihar</a></li>
          <li><a href="<?php echo esc_url(home_url('/restaurant-qr-menu-system-saharsa/')); ?>">Saharsa</a></li>
          <li><a href="<?php echo esc_url(home_url('/restaurant-qr-menu-system-patna/')); ?>">Patna</a></li>
          <li><a href="<?php echo esc_url(home_url('/restaurant-qr-menu-system-delhi/')); ?>">Delhi</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© <?php echo esc_html(date('Y')); ?> FluuexQR. All rights reserved. Made with ❤️ for Indian restaurants.</p>
      <div class="footer-btm-links">
        <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a>
        <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>

<div class="fq91-chatbot" id="fq91Chatbot" aria-live="polite">
  <button class="fq91-chat-toggle" id="fq91ChatToggle" type="button" aria-label="Open FluuexQR AI Support">💬</button>
  <section class="fq91-chat-window" role="dialog" aria-label="FluuexQR AI Support Chatbot">
    <div class="fq91-chat-head">
      <div class="fq91-chat-title"><div class="fq91-chat-avatar">🤖</div><div><b>FluuexQR AI Support</b><span>Ask about QR menu, room QR, billing, KDS</span></div></div>
      <button class="fq91-chat-close" id="fq91ChatClose" type="button" aria-label="Close chat">×</button>
    </div>
    <div class="fq91-chat-body" id="fq91ChatBody">
      <div class="fq91-msg bot">Hi! Main FluuexQR AI Support hoon. Main Hindi aur English dono me restaurant owner ko setup, QR menu, room QR, kitchen, billing, payment, staff, reports, subscription aur troubleshooting me help karta hoon. Apni problem likhiye.</div>
      <div class="fq91-quick fqr-ai-quick">
        <button class="fqr-ai-chip" type="button" data-fq91-question="Complete setup guide Hindi">Setup Guide Hindi</button>
        <button class="fqr-ai-chip" type="button" data-fq91-question="Complete setup guide English">Setup Guide English</button>
        <button class="fqr-ai-chip" type="button" data-fq91-question="Menu item add karna hai">Menu Help</button>
        <button class="fqr-ai-chip" type="button" data-fq91-question="Room QR template and WiFi QR help">Room QR Help</button>
        <button class="fqr-ai-chip" type="button" data-fq91-question="Kitchen dashboard order not showing">Kitchen Issue</button>
        <button class="fqr-ai-chip" type="button" data-fq91-question="Bill paid status customer ko show nahi ho raha">Bill/Paid Help</button>
        <button class="fqr-ai-chip" type="button" data-fq91-question="Payment UPI Razorpay Stripe setup">Payment Help</button>
        <button class="fqr-ai-chip" type="button" data-fq91-question="Staff role login help">Staff Roles</button>
      </div>
    </div>
    <form class="fq91-chat-form fqr-ai-form" id="fq91ChatForm">
      <input class="fqr-ai-input" id="fq91ChatInput" type="text" placeholder="Hindi ya English me problem likhiye..." autocomplete="off" aria-label="Hindi ya English me problem likhiye">
      <button class="fqr-ai-send" type="submit">Send</button>
    </form>
  </section>
</div>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
