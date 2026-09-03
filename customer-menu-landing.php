<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="mq-shell mq-landing">
    <div class="mq-container">
        <div class="mq-hero">
            <div class="mq-hero-copy">
                <span class="landing-badge">Multi-restaurant QR ordering SaaS</span>
                <h1>Run your menu, <span>orders</span>, kitchen, and subscriptions in one place.</h1>
                <p class="subtitle">Same FluuexQR-inspired dark SaaS UI, customer mobile flow, kitchen panel, and role-based dashboards requested in the uploaded spec.</p>
                <div class="landing-grid-actions">
                    <a class="btn btn-primary btn-lg" href="<?php echo esc_url(menuqr_get_page_url_by_slug('signup')); ?>">Start Free</a>
                    <a class="btn btn-outline btn-lg" href="<?php echo esc_url(menuqr_get_page_url_by_slug('menu')); ?>?r=1&t=1">Open Demo Menu</a>
                </div>
            </div>
            <div class="mq-hero-cards">
                <div class="lcard"><div class="lcard-icon">👑</div><h3>Super Admin</h3><p>Restaurants, plans, analytics, approvals</p></div>
                <div class="lcard"><div class="lcard-icon">🍽️</div><h3>Restaurant Admin</h3><p>Menu, tables, QR, staff, payments</p></div>
                <div class="lcard"><div class="lcard-icon">👨‍🍳</div><h3>Kitchen Display</h3><p>Live AJAX orders with status updates</p></div>
                <div class="lcard"><div class="lcard-icon">📱</div><h3>Customer Menu</h3><p>Cart, checkout, order tracking</p></div>
            </div>
        </div>

        <div class="section-card">
            <div class="page-header"><div class="page-header-left"><h2>How it works</h2><p>Scan, order, prepare, serve.</p></div></div>
            <div class="stat-grid">
                <div class="card"><div class="card-title">Step 1</div><div class="card-value">Scan</div><div class="card-sub">Customer scans table QR and opens the menu.</div></div>
                <div class="card"><div class="card-title">Step 2</div><div class="card-value">Order</div><div class="card-sub">Items go into cart, checkout uses restaurant payment settings.</div></div>
                <div class="card"><div class="card-title">Step 3</div><div class="card-value">Cook</div><div class="card-sub">Kitchen screen polls every 5 seconds for new orders.</div></div>
                <div class="card"><div class="card-title">Step 4</div><div class="card-value">Serve</div><div class="card-sub">Order status moves from pending to served.</div></div>
            </div>
        </div>

        <div class="section-card">
            <div class="page-header"><div class="page-header-left"><h2>Features</h2><p>Exactly aligned to the uploaded SaaS scope.</p></div></div>
            <div class="item-grid">
                <div class="item-card"><div class="item-card-name">QR Menu Ordering</div><div class="item-card-desc">Table-based menu URLs, cart, checkout, live order tracking.</div></div>
                <div class="item-card"><div class="item-card-name">Restaurant Dashboards</div><div class="item-card-desc">Menu CRUD, category CRUD, tables, QR print, staff, payment settings.</div></div>
                <div class="item-card"><div class="item-card-name">Kitchen Panel</div><div class="item-card-desc">Pending/accepted/preparing/ready/served status workflow.</div></div>
                <div class="item-card"><div class="item-card-name">Subscriptions</div><div class="item-card-desc">Free, Basic, Premium plans with expiry enforcement.</div></div>
            </div>
        </div>
    </div>
</section>
