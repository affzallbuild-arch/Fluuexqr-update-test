<?php
if (!defined('ABSPATH')) { exit; }
$signup_url = function_exists('menuqr_get_page_url_by_slug') ? menuqr_get_page_url_by_slug('signup') : wp_registration_url();
$demo_url = function_exists('menuqr_get_demo_menu_url') ? menuqr_get_demo_menu_url() : home_url('/menu/');
$features_url = function_exists('menuqr_get_page_url_by_slug') ? menuqr_get_page_url_by_slug('features') : home_url('/features/');
$contact_url = function_exists('menuqr_get_page_url_by_slug') ? menuqr_get_page_url_by_slug('contact-us') : home_url('/contact-us/');
get_header();
?>
<div class="fq91-wp-home">
<!-- ─────────────────────── HERO ─────────────────────── -->
<section class="hero" id="home">
  <div class="hero-bg">
    <div class="hero-mesh"></div>
    <div class="hero-grid"></div>
    <div class="orb orb1"></div>
    <div class="orb orb2"></div>
    <div class="orb orb3"></div>
  </div>

  <div class="wrap">
    <div class="hero-inner">
      <!-- Content -->
      <div class="hero-content">
        <div class="tag"><span class="tag-dot"></span> Live · 500+ Restaurants Trust Us</div>
        <h1 class="h1 hero-headline">
          <span class="line">Restaurant & Hotel</span>
          <span class="line"><span class="ember">QR Ordering</span></span>
          <span class="line">System India</span>
        </h1>
        <div class="hero-pills">
          <span class="pill"><span class="pdot" style="background:#FF6B28"></span>QR Ordering</span>
          <span class="pill"><span class="pdot" style="background:#00E5A0"></span>Live Kitchen</span>
          <span class="pill"><span class="pdot" style="background:#FFB800"></span>Smart Billing</span>
          <span class="pill"><span class="pdot" style="background:#4F8EFF"></span>UPI Pay</span>
          <span class="pill"><span class="pdot" style="background:#25D366"></span>WhatsApp</span>
          <span class="pill"><span class="pdot" style="background:#A78BFA"></span>Analytics</span>
          <span class="pill"><span class="pdot" style="background:#FF4500"></span>Hotel Room QR</span>
        </div>
        <p class="lead">FluuexQR is a smart restaurant QR ordering system for QR menu, table ordering, hotel room QR, kitchen display, billing, UPI/Razorpay payments, WhatsApp bill, paid/unpaid billing and restaurant automation in Purnea, Bihar, Delhi, Mumbai and across India.</p>
        <div class="hero-btns" style="display:flex;gap:14px;flex-wrap:wrap;margin:28px 0">
          <a href="<?php echo esc_url($signup_url); ?>" class="btn btn-fire btn-lg">🚀 Start 10-Day Free Trial</a>
          <a href="<?php echo esc_url($demo_url); ?>" class="btn btn-ghost btn-lg">▶ &nbsp;Live Demo</a>
        </div>
        <div class="trust-row">
          <div style="display:flex;align-items:center;gap:12px">
            <div class="trust-avs">
              <div class="av">RK</div><div class="av">AS</div><div class="av">MH</div><div class="av">PT</div>
            </div>
            <div class="trust-txt"><b>500+ Restaurants</b>already using FluuexQR</div>
          </div>
          <div class="trust-sep"></div>
          <div class="trust-stat"><b>4.8 ★</b>Average rating</div>
          <div class="trust-sep"></div>
          <div class="trust-stat"><b>₹999/mo</b>Starting price</div>
        </div>
      </div>

      <!-- Visual -->
      <div class="hero-visual">
        <div class="phone-wrap">
          <!-- Floating notifications -->
          <div class="notif n1">
            <div class="notif-icon" style="background:rgba(255,69,0,.15)">🔔</div>
            <div class="notif-meta">New Order — Table 7<span>2 seconds ago</span></div>
          </div>
          <div class="notif n2">
            <div class="notif-icon" style="background:rgba(0,229,160,.15)">💳</div>
            <div class="notif-meta">₹840 Received<span>Via PhonePe</span></div>
          </div>
          <div class="notif n3">
            <div class="notif-icon" style="background:rgba(255,184,0,.15)">👨‍🍳</div>
            <div class="notif-meta">Table 4 — Ready!<span>Kitchen Display</span></div>
          </div>
          <div class="notif n4">
            <div class="notif-icon" style="background:rgba(79,142,255,.15)">📊</div>
            <div class="notif-meta">₹12,480 Today<span>+34% vs yesterday</span></div>
          </div>

          <!-- Phone -->
          <div class="phone">
            <div class="phone-island"><div class="cam"></div><div class="cam-led"></div></div>
            <div class="phone-screen" id="phoneScreen">
              <div class="ps-header">
                <div class="ps-restaurant">🍽️ Sher-e-Punjab</div>
                <div class="ps-sub">Purnia, Bihar · Scan &amp; Order</div>
                <div class="ps-table-tag">📍 Table 7 &nbsp;·&nbsp; Dine-In</div>
              </div>
              <div class="ps-search">🔍 &nbsp;Search menu items…</div>
              <div class="ps-cat-row">
                <div class="ps-cat active">All</div>
                <div class="ps-cat">🍛 Main</div>
                <div class="ps-cat">🍞 Bread</div>
                <div class="ps-cat">🥤 Drinks</div>
                <div class="ps-cat">🍨 Dessert</div>
              </div>
              <div class="ps-section">🍛 Main Course</div>
              <div class="ps-item">
                <div class="ps-img" style="background:#FFE8D6">🍗</div>
                <div class="ps-info">
                  <div class="ps-name">Butter Chicken</div>
                  <div class="ps-desc">Creamy tomato gravy, boneless</div>
                  <div class="ps-row"><div class="ps-price">₹280</div></div>
                </div>
                <div class="ps-add">+</div>
              </div>
              <div class="ps-item">
                <div class="ps-img" style="background:#FFF0E0">🥘</div>
                <div class="ps-info">
                  <div class="ps-name">Dal Makhani</div>
                  <div class="ps-desc">Slow cooked black lentils</div>
                  <div class="ps-row"><div class="ps-price">₹180</div></div>
                </div>
                <div class="ps-add">+</div>
              </div>
              <div class="ps-item">
                <div class="ps-img" style="background:#E8F5E9">🥬</div>
                <div class="ps-info">
                  <div class="ps-name">Paneer Tikka Masala</div>
                  <div class="ps-desc">Grilled cottage cheese, spiced</div>
                  <div class="ps-row"><div class="ps-price">₹240</div></div>
                </div>
                <div class="ps-add">+</div>
              </div>
              <div class="ps-section">🍞 Breads</div>
              <div class="ps-item">
                <div class="ps-img" style="background:#FFF8E1">🫓</div>
                <div class="ps-info">
                  <div class="ps-name">Butter Naan</div>
                  <div class="ps-desc">Tandoor baked, buttered</div>
                  <div class="ps-row"><div class="ps-price">₹60</div></div>
                </div>
                <div class="ps-add">+</div>
              </div>
              <div class="ps-section">🥤 Beverages</div>
              <div class="ps-item">
                <div class="ps-img" style="background:#FFF3E0">🥛</div>
                <div class="ps-info">
                  <div class="ps-name">Mango Lassi</div>
                  <div class="ps-desc">Fresh churned, chilled</div>
                  <div class="ps-row"><div class="ps-price">₹90</div></div>
                </div>
                <div class="ps-add">+</div>
              </div>
              <div class="ps-item">
                <div class="ps-img" style="background:#E8F5E9">☕</div>
                <div class="ps-info">
                  <div class="ps-name">Masala Chai</div>
                  <div class="ps-desc">Kadak, with ginger</div>
                  <div class="ps-row"><div class="ps-price">₹40</div></div>
                </div>
                <div class="ps-add">+</div>
              </div>
              <div style="height:54px"></div>
              <div class="ps-cart-bar">
                <div class="ps-cart-btn">🛒 View Cart (2) · ₹340</div>
                <div class="ps-call-btn">🔔 Call</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ─────────────────── MARQUEE ─────────────────── -->
<div class="marquee-strip">
  <div class="marquee-track" id="mtrack">
    <!-- items injected by JS -->
  </div>
</div>

<!-- ─────────────────── STATS ─────────────────── -->
<section class="stats-sec sec-sm">
  <div class="wrap">
    <div class="stats-grid">
      <div class="stat-cell sr"><div class="stat-num"><span class="cnt" data-to="500">0</span><span class="unit">+</span></div><div class="stat-lbl">Restaurants Onboarded</div></div>
      <div class="stat-cell sr d1"><div class="stat-num">₹<span class="cnt" data-to="999">0</span></div><div class="stat-lbl">Starting Plan / Month</div></div>
      <div class="stat-cell sr d2"><div class="stat-num"><span class="cnt" data-to="98">0</span><span class="unit">%</span></div><div class="stat-lbl">Customer Satisfaction</div></div>
      <div class="stat-cell sr d3"><div class="stat-num"><span class="cnt" data-to="30">0</span><span class="unit">min</span></div><div class="stat-lbl">Average Setup Time</div></div>
    </div>
  </div>
</section>

<!-- ─────────────────── HOW IT WORKS ─────────────────── -->
<section class="how-sec sec" id="how">
  <div class="wrap">
    <div class="center sr">
      <div class="tag">How It Works</div>
      <h2 class="h2">Up &amp; Running in <span class="ember">30 Minutes</span></h2>
      <p class="lead" style="margin:14px auto 0">No tech team needed. Hindi support on WhatsApp. Get live before your next lunch service.</p>
    </div>
    <div class="how-grid">
      <div class="how-card sr d1">
        <div class="how-num">01</div>
        <div class="how-icon">📝</div>
        <h3>Create Account &amp; Upload Menu</h3>
        <p>Sign up free. Add your restaurant, upload your menu with item photos, prices, and categories. Guided dashboard — works on any phone or laptop.</p>
      </div>
      <div class="how-card sr d2">
        <div class="how-num">02</div>
        <div class="how-icon">🖨️</div>
        <h3>Print &amp; Place QR Codes</h3>
        <p>We generate branded, print-ready QR codes for every table or hotel room. Print at any local print shop, place in QR stands — you're ready.</p>
      </div>
      <div class="how-card sr d3">
        <div class="how-num">03</div>
        <div class="how-icon">🚀</div>
        <h3>Go Live &amp; Watch Revenue Grow</h3>
        <p>Customers scan, order, and pay from their phone. Your kitchen gets live orders on the display. Billing is automatic. Analytics start from day one.</p>
      </div>
    </div>
  </div>
</section>

<!-- ─────────────────── FEATURES ─────────────────── -->
<section class="feat-sec sec" id="features">
  <div class="wrap">
    <div class="center sr">
      <div class="tag">Platform Features</div>
      <h2 class="h2">One Platform, <span class="ember">13 Powerful</span> Features</h2>
      <p class="lead" style="margin:14px auto 0">Replace 5 different apps with one FluuexQR subscription. Everything your restaurant needs — included.</p>
    </div>
    <div class="feat-grid" id="featGrid"></div>
  </div>
</section>

<!-- ─────────────────── DASHBOARD ─────────────────── -->
<section class="dash-sec sec" id="dashboard">
  <div class="wrap">
    <div class="dash-inner">
      <div class="dash-copy sr">
        <div class="tag">Admin Dashboard</div>
        <h2 class="h2">Your Restaurant's<br><span class="ember">Command Center</span></h2>
        <p class="lead">Everything you need to run your restaurant intelligently — live orders, revenue charts, peak hours analysis — from any phone, tablet, or computer.</p>
        <ul class="check-list">
          <li><div class="check-ico">✓</div><span><b style="color:#fff">Real-Time Revenue</b> — Watch today's earnings grow live. Compare with yesterday, last week, last month.</span></li>
          <li><div class="check-ico">✓</div><span><b style="color:#fff">Top Dishes Insights</b> — Know which items sell most and maximize your profit margins daily.</span></li>
          <li><div class="check-ico">✓</div><span><b style="color:#fff">Peak Hours Analysis</b> — Know your busiest periods and staff accordingly. Never get caught short.</span></li>
          <li><div class="check-ico">✓</div><span><b style="color:#fff">Full Order History</b> — Every order stored forever. Export as Excel or PDF for your accountant.</span></li>
        </ul>
        <a href="<?php echo esc_url($demo_url); ?>" class="btn btn-fire">▶ See Live Demo</a>
      </div>

      <div class="dash-mockup sr d2">
        <div class="dash-bar">
          <div class="dash-dots"><div class="dot-r"></div><div class="dot-y"></div><div class="dot-g"></div></div>
          <div class="dash-tit">FluuexQR — Admin Dashboard</div>
          <div style="font-size:.65rem;color:var(--faint)">Today · Live</div>
        </div>
        <div class="dash-body">
          <div class="d-card">
            <div class="d-card-title">Today's Revenue</div>
            <div class="d-val">₹12,480</div>
            <div class="d-trend">↑ 34% vs yesterday</div>
          </div>
          <div class="d-card">
            <div class="d-card-title">Orders Today</div>
            <div class="d-val">87</div>
            <div class="d-trend">↑ 18% vs yesterday</div>
          </div>
          <div class="d-chart">
            <div class="d-card-title">Weekly Revenue</div>
            <div class="d-bars">
              <div class="d-bar" style="height:42%"><div class="d-bar-lbl">Mon</div></div>
              <div class="d-bar" style="height:61%"><div class="d-bar-lbl">Tue</div></div>
              <div class="d-bar" style="height:38%"><div class="d-bar-lbl">Wed</div></div>
              <div class="d-bar" style="height:75%"><div class="d-bar-lbl">Thu</div></div>
              <div class="d-bar" style="height:55%"><div class="d-bar-lbl">Fri</div></div>
              <div class="d-bar hi" style="height:92%"><div class="d-bar-lbl">Sat</div></div>
              <div class="d-bar" style="height:68%"><div class="d-bar-lbl">Sun</div></div>
            </div>
          </div>
          <div class="d-orders">
            <div class="d-card-title" style="margin-bottom:12px">Live Orders</div>
            <div class="d-row new"><span>🍗 Table 3 · Butter Chicken ×2</span><span class="d-badge n">New</span></div>
            <div class="d-row pend"><span>🥘 Table 7 · Dal Makhani ×1</span><span class="d-badge p">Preparing</span></div>
            <div class="d-row ready"><span>🥛 Table 1 · Mango Lassi ×3</span><span class="d-badge r">Ready ✓</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ─────────────────── USE CASES ─────────────────── -->
<section class="uc-sec sec" id="use-cases">
  <div class="wrap">
    <div class="center sr">
      <div class="tag">Use Cases</div>
      <h2 class="h2">Built for <span class="ember">Every Food Business</span></h2>
      <p class="lead" style="margin:14px auto 28px">From small dhabas to multi-floor hotels — FluuexQR adapts to your exact operation type.</p>
    </div>
    <div class="tabs">
      <button class="tab on" data-tab="res">🍽️ Restaurant</button>
      <button class="tab" data-tab="hot">🏨 Hotel</button>
      <button class="tab" data-tab="cld">☁️ Cloud Kitchen</button>
    </div>
    <div class="tab-panels">

      <div class="tab-panel on" id="panel-res">
        <div class="uc-card sr">
          <div class="uc-card-tag res">🍽️ For Restaurants & Dhabas</div>
          <h3>Transform Your Restaurant Operations</h3>
          <p>Replace paper menus, handwritten bills, and verbal kitchen orders with one smart digital platform. Works for small dhabas and large multi-floor restaurants alike.</p>
          <ul class="uc-list">
            <li><div class="uc-check">✓</div>Digital menu with photos — update prices from any phone instantly</li>
            <li><div class="uc-check">✓</div>Table-wise ordering — kitchen knows which table ordered what</li>
            <li><div class="uc-check">✓</div>Auto bill generation with GST — zero manual calculation errors</li>
            <li><div class="uc-check">✓</div>UPI payment at the table — PhonePe, Google Pay, Paytm</li>
            <li><div class="uc-check">✓</div>WhatsApp invoice — paperless billing customers love</li>
            <li><div class="uc-check">✓</div>Post-meal feedback — build your local reputation on Google</li>
          </ul>
        </div>
        <div class="uc-card sr d2">
          <div class="uc-card-tag kit">👨‍🍳 Kitchen Workflow</div>
          <h3>Your Kitchen Runs Like Clockwork</h3>
          <p>The Kitchen Display System shows every live order on a screen the moment a customer places it. No paper, no confusion, no communication breakdown.</p>
          <ul class="uc-list">
            <li><div class="uc-check">✓</div>Orders appear instantly on Kitchen Display System screen</li>
            <li><div class="uc-check">✓</div>Color-coded: Pending 🔴 → Preparing 🟡 → Ready 🟢</li>
            <li><div class="uc-check">✓</div>Prep timer on each order — track preparation time precisely</li>
            <li><div class="uc-check">✓</div>Waiter notified on device when food is ready for pickup</li>
            <li><div class="uc-check">✓</div>Works on any Android TV, tablet, or monitor in kitchen</li>
            <li><div class="uc-check">✓</div>Handle rush hour — multiple tables ordering simultaneously</li>
          </ul>
        </div>
      </div>

      <div class="tab-panel" id="panel-hot">
        <div class="uc-card sr">
          <div class="uc-card-tag hot">🏨 For Hotels & Guesthouses</div>
          <h3>Premium Room Service Experience</h3>
          <p>Give guests a 5-star room-dining experience at any budget. QR codes in each room let guests order directly — no phone calls to reception needed.</p>
          <ul class="uc-list">
            <li><div class="uc-check">✓</div>Unique room QR codes — one per room, suite, or floor</li>
            <li><div class="uc-check">✓</div>Guests order from phone — no reception calls needed</li>
            <li><div class="uc-check">✓</div>Room number auto-attached to every kitchen order</li>
            <li><div class="uc-check">✓</div>WhatsApp checkout — contactless bill to guest's number</li>
            <li><div class="uc-check">✓</div>Works for budget hotels, guesthouses, and resorts</li>
            <li><div class="uc-check">✓</div>Better service → better OTA reviews → more bookings</li>
          </ul>
        </div>
        <div class="uc-card sr d2">
          <div class="uc-card-tag hot">📊 Hotel Revenue Impact</div>
          <h3>More Orders, Higher Revenue</h3>
          <p>Hotels using FluuexQR's room QR ordering report significantly more in-room dining orders — because ordering is effortless for guests.</p>
          <ul class="uc-list">
            <li><div class="uc-check">✓</div>Guests order more when the process is friction-free</li>
            <li><div class="uc-check">✓</div>Faster service = better reviews on Booking.com & OTAs</li>
            <li><div class="uc-check">✓</div>Contactless experience impresses health-conscious travelers</li>
            <li><div class="uc-check">✓</div>Daily F&B reports per room category for management</li>
            <li><div class="uc-check">✓</div>Setup in under 30 minutes — no IT team required</li>
            <li><div class="uc-check">✓</div>Starts at ₹999/month — ROI within the first day</li>
          </ul>
        </div>
      </div>

      <div class="tab-panel" id="panel-cld">
        <div class="uc-card sr">
          <div class="uc-card-tag cld">☁️ For Cloud Kitchens</div>
          <h3>Manage Multiple Brands Effortlessly</h3>
          <p>Running multiple brands from one kitchen? FluuexQR's multi-branch system manages separate menus, orders, and analytics for each brand from one login.</p>
          <ul class="uc-list">
            <li><div class="uc-check">✓</div>Separate digital menus for each brand or kitchen</li>
            <li><div class="uc-check">✓</div>Centralized order management — all brands in one view</li>
            <li><div class="uc-check">✓</div>Revenue analytics per brand — know what's profitable</li>
            <li><div class="uc-check">✓</div>WhatsApp billing and UPI for all delivery customers</li>
            <li><div class="uc-check">✓</div>Scale to 10+ brands without additional staff</li>
            <li><div class="uc-check">✓</div>Real-time delivery tracking for each kitchen brand</li>
          </ul>
        </div>
        <div class="uc-card sr d2">
          <div class="uc-card-tag blue">🚴 Delivery Operations</div>
          <h3>Real-Time Delivery Tracking</h3>
          <p>Assign riders, track live, and keep customers updated with WhatsApp notifications — all managed from your FluuexQR dashboard without any extra app.</p>
          <ul class="uc-list">
            <li><div class="uc-check">✓</div>Assign delivery agents to each order instantly</li>
            <li><div class="uc-check">✓</div>Real-time delivery status tracking for every order</li>
            <li><div class="uc-check">✓</div>Customer WhatsApp notifications on status update</li>
            <li><div class="uc-check">✓</div>Cash + UPI payment tracking for delivery orders</li>
            <li><div class="uc-check">✓</div>Delivery agent performance reporting by week/month</li>
            <li><div class="uc-check">✓</div>Zone-based delivery management for large service areas</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ─────────────────── COMPARISON ─────────────────── -->
<section class="cmp-sec sec" id="compare">
  <div class="wrap">
    <div class="center sr">
      <div class="tag">Why Switch</div>
      <h2 class="h2">FluuexQR vs <span class="ember">Old Systems</span></h2>
      <p class="lead" style="margin:14px auto 0">Still running on paper menus and handwritten bills? Here's what you're missing every single day.</p>
    </div>
    <div style="overflow-x:auto;margin-top:0">
      <table class="cmp-table sr d1">
        <thead>
          <tr>
            <th>Feature</th>
            <th class="hi">🟠 FluuexQR (Digital)</th>
            <th class="lo">📋 Traditional System</th>
          </tr>
        </thead>
        <tbody id="cmpBody"></tbody>
      </table>
    </div>
  </div>
</section>



<!-- ─────────────────── LOCAL SEO CITY PAGES ─────────────────── -->
<section class="local-seo-sec sec" id="locations">
  <div class="wrap">
    <div class="center sr">
      <div class="tag">Local SEO Coverage</div>
      <h2 class="h2">Rank FluuexQR in <span class="ember">Purnea, Bihar, Delhi & Mumbai</span></h2>
      <p class="lead" style="margin:14px auto 0">Target restaurant owners and hotel managers with city-wise landing pages, high-intent keywords and conversion-focused demo CTAs.</p>
    </div>
    <div class="local-grid">
      <article class="local-card sr d1">
        <h3>QR Menu System in Purnea</h3>
        <p>Perfect for restaurants, cafés, dhabas and hotels in Purnea that want QR menu, table ordering, kitchen dashboard and smart billing.</p>
        <div class="local-keywords"><span>QR menu Purnea</span><span>restaurant software Purnea</span><span>hotel QR Purnea</span></div>
      </article>
      <article class="local-card sr d2">
        <h3>Restaurant QR Ordering in Bihar</h3>
        <p>Build Bihar-first trust with local support, Hindi onboarding, easy UPI setup and affordable SaaS pricing for small and medium restaurants.</p>
        <div class="local-keywords"><span>QR menu Bihar</span><span>restaurant automation Bihar</span><span>digital menu Bihar</span></div>
      </article>
      <article class="local-card sr d3">
        <h3>Restaurant Software in Delhi</h3>
        <p>For cafés, lounges and cloud kitchens that need fast ordering, real-time kitchen display, multi-branch control and analytics.</p>
        <div class="local-keywords"><span>restaurant software Delhi</span><span>QR ordering Delhi</span><span>cloud kitchen system</span></div>
      </article>
      <article class="local-card sr d4">
        <h3>Hotel Room QR Ordering in Mumbai</h3>
        <p>Help hotels improve room service with room-wise QR ordering, WhatsApp updates, digital bills and staff-friendly order management.</p>
        <div class="local-keywords"><span>hotel QR Mumbai</span><span>room service QR</span><span>restaurant software Mumbai</span></div>
      </article>
    </div>
  </div>
</section>

<!-- ─────────────────── SEO FAQ ─────────────────── -->
<section class="faq-sec sec" id="faq">
  <div class="wrap">
    <div class="center sr">
      <div class="tag">FAQ</div>
      <h2 class="h2">Restaurant Owners Ask <span class="ember">Before Buying</span></h2>
    </div>
    <div class="faq-grid">
      <div class="faq-item sr d1"><h3>What is FluuexQR?</h3><p>FluuexQR is an all-in-one QR ordering system for restaurants and hotels with QR menu, table ordering, room QR ordering, kitchen dashboard, smart billing and UPI payment support.</p></div>
      <div class="faq-item sr d2"><h3>Can hotels use room-wise QR ordering?</h3><p>Yes. Every room can have a unique QR code. Guest scans the QR, places order, and the kitchen receives the order with the correct room number.</p></div>
      <div class="faq-item sr d3"><h3>Is it useful for small restaurants in Bihar?</h3><p>Yes. FluuexQR is designed for small restaurants, cafés and dhabas with affordable monthly plans, Hindi onboarding and easy phone-based management.</p></div>
      <div class="faq-item sr d4"><h3>Does FluuexQR support kitchen display and billing?</h3><p>Yes. Kitchen staff can see live orders with status updates, and restaurant admins can generate running bills, PDF bills and print-ready invoices.</p></div>
    </div>
  </div>
</section>

<!-- ─────────────────── TESTIMONIALS ─────────────────── -->
<section class="testi-sec sec" id="testimonials">
  <div class="wrap">
    <div class="center sr">
      <div class="tag">Testimonials</div>
      <h2 class="h2">Loved by Restaurant <span class="ember">Owners</span></h2>
      <p class="lead" style="margin:14px auto 0">Real words from real restaurant and hotel owners using FluuexQR every day across Bihar and India.</p>
    </div>
    <div class="testi-grid" id="testiGrid"></div>
  </div>
</section>

<!-- ─────────────────── PRICING ─────────────────── -->
<section class="pricing-sec sec" id="pricing">
  <div class="wrap">
    <div class="center sr">
      <div class="tag">Pricing</div>
      <h2 class="h2">Simple, <span class="ember">Transparent</span> Plans</h2>
      <p class="lead" style="margin:14px auto 0">No hidden fees. No contracts. Cancel anytime. Choose what fits your restaurant size.</p>
    </div>
    <div class="pricing-grid" id="pricingGrid"></div>
    <p class="center" style="margin-top:24px;font-size:.83rem;color:var(--muted)">
      All plans include free onboarding support in Hindi &nbsp;·&nbsp; GST billing available &nbsp;·&nbsp;
      <a href="<?php echo esc_url($features_url); ?>" style="color:var(--f2);font-weight:600">Compare full features →</a>
    </p>
  </div>
</section>

<!-- ─────────────────── FINAL CTA ─────────────────── -->
<section class="cta-sec" id="contact">
  <div class="cta-glow"></div>
  <div class="cta-grid-bg"></div>
  <div class="cta-inner sr">
    <div class="tag" style="margin:0 auto 20px;width:fit-content">
      <span class="tag-dot"></span>Join 500+ Restaurants Today
    </div>
    <h2 class="h2">Ready to Modernize<br>Your <span class="ember">Restaurant?</span></h2>
    <p class="lead" style="margin:16px auto 0;text-align:center;color:var(--muted)">
      Start your free trial in under 30 minutes. No credit card needed.<br>Hindi support via WhatsApp &amp; phone. Works on any 4G connection.
    </p>
    <div class="cta-btns">
      <a href="<?php echo esc_url($signup_url); ?>" class="btn btn-fire btn-lg">🚀 Start 10-Day Free Trial</a>
      <a href="<?php echo esc_url($contact_url); ?>" class="btn btn-ghost btn-lg">📅 &nbsp;Book a Demo</a>
    </div>
    <div style="display:flex;align-items:center;justify-content:center;gap:24px;flex-wrap:wrap;margin-top:28px">
      <span style="font-size:.82rem;color:var(--muted)">📞 <a href="tel:+919876543210" style="color:var(--f2);font-weight:600">+91 98765 43210</a></span>
      <span style="font-size:.82rem;color:var(--muted)">💬 <a href="https://wa.me/919876543210" target="_blank" rel="noopener noreferrer" style="color:var(--f2);font-weight:600">WhatsApp Us</a></span>
      <span style="font-size:.82rem;color:var(--muted)">✉️ <a href="mailto:hello@fluuexqr.com" style="color:var(--f2);font-weight:600">hello@fluuexqr.com</a></span>
    </div>
  </div>
</section>
</div>
<?php get_footer(); ?>
