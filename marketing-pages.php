<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v62 Premium SaaS Website + SEO Marketing Layer.
 * Creates/updates all requested pages with premium responsive UI content.
 */

function fluuexqr_v62_feature_list(): array {
    return [
        ['QR Menu Ordering','Customers scan QR and order from a mobile browser without installing an app.','📲'],
        ['Table QR Ordering','Table-wise QR codes send orders with exact table number to the kitchen.','🪑'],
        ['Hotel Room QR','Room QR codes auto-detect room number for hotel food and service orders.','🏨'],
        ['Waiter Calling','Guests can request help, service or bill without waiting manually.','🔔'],
        ['Kitchen Dashboard','Live orders with accept, preparing, ready and served status.','👨‍🍳'],
        ['Billing System','Generate order bills, print invoices and keep restaurant records clean.','🧾'],
        ['Invoice Printing','Professional bill layout with restaurant details and logo support.','🖨️'],
        ['Staff Login','Separate access for restaurant admin, kitchen staff and delivery team.','👥'],
        ['Admin Dashboard','Control menu, tables, rooms, orders, reports and subscription from one place.','📊'],
        ['Delivery Tracking','Assign delivery orders and open Google Maps navigation quickly.','🛵'],
        ['Real-time Orders','New customer orders reach dashboard/kitchen without manual refresh.','⚡'],
        ['Live Kitchen Status','Track accepted, preparing, ready and served orders visually.','🔥'],
        ['Multi Branch','Manage multiple restaurant branches from one SaaS platform.','🏬'],
        ['Restaurant Analytics','Track revenue, order volume, popular items and operational insights.','📈'],
        ['Customer Reviews','Collect feedback and improve service quality.','⭐'],
        ['WhatsApp Notifications','Use WhatsApp-style flows for faster customer communication.','💬'],
        ['Offers & Coupons','Create offers, coupon codes and promotional campaigns.','🏷️'],
        ['Combo Meals','Bundle popular items into smart combo deals.','🍱'],
        ['Subscription Management','SaaS plan, renewal, expiry and restaurant access control.','💎'],
        ['Role Based Access','Secure permission control for owner, staff, kitchen and delivery users.','🔐'],
        ['Secure Payments','Payment-ready system structure for restaurant and SaaS workflows.','💳'],
        ['Cloud Based System','Accessible anywhere with a browser, optimized for shared hosting.','☁️'],
    ];
}

function fluuexqr_v62_page_url(string $slug): string {
    if (function_exists('menuqr_get_page_url_by_slug')) {
        return menuqr_get_page_url_by_slug($slug);
    }
    return home_url('/' . trim($slug, '/') . '/');
}
function fluuexqr_v62_trial_url(): string {
    return function_exists('menuqr_get_plan_signup_url') ? menuqr_get_plan_signup_url('free_trial') : fluuexqr_v62_page_url('start-free-trial');
}

function fluuexqr_v62_service_data(): array {
    return [
        'restaurant-qr-menu' => ['Restaurant QR Menu System','Restaurant QR Menu','restaurants, cafes and food courts','Give every table a smart QR menu where customers browse items, add to cart and place orders from their phone.',['QR code menu for every table','Customer self-ordering without app install','Menu category, item, image and price management','Live kitchen order push','Digital billing and customer feedback'],'📲'],
        'hotel-room-qr-ordering' => ['Hotel Room QR Ordering','Hotel QR Ordering','hotels, resorts and guest houses','Place QR codes inside rooms so guests order food and services with automatic room number tracking.',['Room-wise QR code generation','Room number auto-detection','Guest food ordering from room','Kitchen display with room number','Billing integration for hotel food service'],'🏨'],
        'table-qr-ordering-system' => ['Table QR Ordering System','Table QR Ordering','restaurants and cafes','Let customers order directly from the table and reduce waiter dependency during busy hours.',['Table-wise QR codes','Direct order from table','Less waiter dependency','Fast order acceptance in kitchen','Table number shown in order display'],'🪑'],
        'kitchen-display-system' => ['Kitchen Display System','Kitchen Display System','restaurants, hotels and cloud kitchens','A live order board for kitchen teams with order timers, food status and table/room tracking.',['Live order queue','Accept, Preparing, Ready and Served status','Table and room number tracking','Order timer and preparation workflow','Kitchen-friendly responsive display'],'👨‍🍳'],
        'restaurant-billing-software' => ['Restaurant Billing Software','Restaurant Billing Software','restaurants and hotels','Generate clean restaurant bills from QR, room, table and online orders.',['GST-ready bill layout options','Logo and restaurant detail support','Invoice printing workflow','Order-wise bill generation','Admin billing controls'],'🧾'],
        'online-ordering-system' => ['Online Food Ordering System','Restaurant Ordering Software','restaurants and cloud kitchens','Accept online food orders from your own website with cart, checkout and status tracking.',['Customer online ordering page','Cart and checkout workflow','Delivery or pickup support','Order tracking status','Admin and kitchen notifications'],'🛒'],
        'delivery-tracking-system' => ['Delivery Tracking System','Delivery Management System','restaurants offering local delivery','Assign orders to delivery staff and help them navigate with Google Maps links.',['Delivery boy assignment','Google Maps navigation link','Delivery status updates','Customer order tracking','Delivery history panel'],'🛵'],
        'restaurant-pos-features' => ['Restaurant POS Features','Restaurant POS Features','restaurants and food businesses','A browser-based restaurant operations system combining menu, order, billing and reports.',['Menu, orders and billing in one dashboard','Staff access control','Coupons and combo meals','Reports and revenue overview','Fast browser-based workflow'],'🏪'],
        'multi-branch-management' => ['Multi Branch Management','Multi Branch Restaurant Management','restaurant chains and franchise brands','Manage multiple branches, reports and access from one central platform.',['Branch-wise restaurant control','Centralized reports','Branch staff management','Plan based branch access','Scalable SaaS operations'],'🏬'],
        'staff-management-system' => ['Staff Management System','Restaurant Staff Management','restaurants and hotels','Create controlled access for kitchen, delivery and staff users.',['Role based staff login','Kitchen and delivery access','Admin controlled permissions','Secure staff workflow','Operational accountability'],'👥'],
        'restaurant-reports-analytics' => ['Restaurant Reports & Analytics','Restaurant Analytics & Reports','restaurant owners and hotel managers','Understand orders, revenue, popular items and daily performance with clean analytics.',['Sales reports','Order analytics','Popular item insights','Revenue overview','Operational performance tracking'],'📈'],
        'digital-restaurant-menu' => ['Digital Restaurant Menu','Digital Menu Management','restaurants, cafes and hotels','Update menu items, prices, images and availability instantly from the admin dashboard.',['Easy menu updates','Item images and prices','Category wise menu','Availability on/off controls','Mobile-first menu UI'],'🍽️'],
        'contactless-dining-solution' => ['Contactless Dining Solution','Contactless Dining','restaurants and cafes','Modern contactless ordering experience for safer, faster and premium dining.',['Scan QR and order','No app required','Reduced waiting time','Better hygiene experience','Modern dining workflow'],'✨'],
        'cloud-kitchen-ordering' => ['Cloud Kitchen Ordering System','Cloud Kitchen Ordering','cloud kitchens and delivery-first restaurants','Run delivery-first operations with online orders, kitchen queue and delivery workflow.',['Online order workflow','Kitchen order queue','Delivery status management','Menu and combo controls','Reports for owners'],'☁️'],
        'hotel-food-service-system' => ['Hotel Food Service System','Hotel Food Ordering','hotels and resorts','Room service ordering system for hotels with room number in kitchen display and bill flow.',['Room QR ordering','Room number in kitchen display','Guest service request flow','Bill integration','Hotel-friendly admin dashboard'],'🛎️'],
        'restaurant-saas-platform' => ['Restaurant SaaS Platform','Restaurant SaaS','restaurants, hotels and SaaS operators','A complete SaaS-ready platform for restaurant onboarding, subscription plans and operations.',['Restaurant onboarding flow','Plan based access','Subscription expiry control','Super admin revenue overview','Multi-tenant style business structure'],'💎'],
        'restaurant-customer-feedback-system' => ['Restaurant Customer Feedback System','Restaurant Customer Feedback','restaurants, cafes and hotels','Collect customer feedback and reviews after dining or room service orders.',['Customer rating capture','Review management','Service quality insights','Admin review overview','Trust building for restaurants'],'⭐'],
        'whatsapp-ordering-integration' => ['WhatsApp Ordering Integration','WhatsApp Ordering','restaurants and local food businesses','Support WhatsApp-friendly ordering and customer communication workflows.',['WhatsApp contact CTA','Order communication support','Customer update workflow','Easy inquiry handling','Local restaurant friendly process'],'💬'],
        'restaurant-offers-coupon-system' => ['Restaurant Offers & Coupon System','Restaurant Offers & Coupons','restaurants and cafes','Create offers and coupons to increase repeat orders and launch food promotions.',['Coupon code management','Offer campaigns','Discount workflow','Promotion friendly UI','Customer conversion boost'],'🏷️'],
        'combo-meal-management' => ['Combo Meal Management','Restaurant Combo Meals','restaurants, cafes and cloud kitchens','Create combo meals and bundle offers to improve average order value.',['Combo item setup','Bundle pricing','Meal promotion sections','Admin menu control','Customer-friendly upsell'],'🍱'],
        'restaurant-subscription-management' => ['Restaurant Subscription Management','Restaurant Subscription Management','SaaS restaurant platforms','Manage SaaS plans, restaurant renewals, active/expired access and subscriptions.',['Starter, Basic and Premium plans','Renewal workflow','Expiry status','Plan based features','Super admin subscription view'],'📅'],
        'qr-code-generation-system' => ['QR Code Generation System','QR Code Generation','restaurants, hotels and cafes','Generate QR codes for tables, rooms and ordering pages from the restaurant admin.',['Table QR generation','Room QR generation','Download/print ready flow','Restaurant branding support','Scan-to-order URL structure'],'🔳'],
        'restaurant-admin-dashboard' => ['Restaurant Admin Dashboard','Restaurant Admin Dashboard','restaurant owners and managers','A central dashboard to manage orders, menu, tables, rooms, staff, bills, reports and reviews.',['Orders management','Menu management','Tables and rooms','Staff and billing','Reports, coupons and reviews'],'🖥️'],
    ];
}

function fluuexqr_v62_industry_cards(): string {
    $items = ['Restaurants'=>'🍽️','Hotels'=>'🏨','Cafes'=>'☕','Resorts'=>'🌴','Cloud Kitchens'=>'☁️','Food Courts'=>'🏬','Fine Dining'=>'🥂','Bars & Lounges'=>'🍹'];
    $out = '<div class="fq-v62-industry-grid">';
    foreach ($items as $name => $icon) { $out .= '<div class="fq-v62-industry"><span>'.esc_html($icon).'</span><strong>'.esc_html($name).'</strong></div>'; }
    return $out . '</div>';
}

function fluuexqr_v62_dashboard_visuals(): string {
    $cards = [
        ['Super Admin','Manage restaurants, subscriptions, plans, payments, revenue and analytics.','🏢'],
        ['Restaurant Admin','Orders, menu, tables, rooms, staff, billing, reports, coupons and reviews.','🖥️'],
        ['Kitchen Dashboard','Live orders with Accept, Preparing, Ready, Served, timer, table and room number.','👨‍🍳'],
        ['Delivery Panel','Assigned orders, delivery status, Google Maps navigation and delivery history.','🛵'],
    ];
    $out = '<section class="fq-v62-section"><div class="fq-v62-section-head"><span>Dashboards</span><h2>Every team gets a focused dashboard</h2><p>FluuexQR shows the right tools to the right user so operations stay fast and controlled.</p></div><div class="fq-v62-dashboard-grid">';
    foreach ($cards as $c) { $out .= '<article class="fq-v62-dashboard-card"><div class="fq-v62-icon">'.esc_html($c[2]).'</div><h3>'.esc_html($c[0]).'</h3><p>'.esc_html($c[1]).'</p><div class="fq-v62-mini-bars"><i></i><i></i><i></i></div></article>'; }
    return $out . '</div></section>';
}

function fluuexqr_v62_features_full_html(): string {
    $features = fluuexqr_v62_feature_list();
    ob_start(); ?>
    <section class="fq-v62-hero fq-v62-hero-features">
      <div class="fq-v62-hero-copy">
        <span class="fq-v62-badge">All Features</span>
        <h1>Complete Restaurant + Hotel QR Ordering Features</h1>
        <p>Explore every feature inside FluuexQR: table QR, room QR, kitchen display, billing, delivery, staff, analytics, subscription, coupons, combos and admin dashboards.</p>
        <div class="fq-v62-actions"><a class="btn btn-primary" href="<?php echo esc_url(fluuexqr_v62_trial_url()); ?>">Start Free Trial</a><a class="btn btn-ghost" href="<?php echo esc_url(fluuexqr_v62_page_url('demo')); ?>">Book Live Demo</a></div>
      </div>
      <div class="fq-v62-visual-panel">
        <div class="fq-v62-phone"><b>Live Order</b><span>Room 204 · ₹860</span><em>Preparing</em></div>
        <div class="fq-v62-float-card one">QR Scan → Menu</div>
        <div class="fq-v62-float-card two">Kitchen Alert</div>
        <div class="fq-v62-float-card three">Bill Ready</div>
      </div>
    </section>
    <section class="fq-v62-section">
      <div class="fq-v62-section-head"><span>Feature library</span><h2>Everything your food business needs</h2><p>Each card is built with conversion-focused visual design, hover effects and mobile-first responsiveness.</p></div>
      <div class="fq-v62-feature-grid">
      <?php foreach ($features as $f): ?>
        <article class="fq-v62-feature-card"><div class="fq-v62-icon"><?php echo esc_html($f[2]); ?></div><h3><?php echo esc_html($f[0]); ?></h3><p><?php echo esc_html($f[1]); ?></p><a href="<?php echo esc_url(fluuexqr_v62_page_url('contact-us')); ?>">Learn how it works →</a></article>
      <?php endforeach; ?>
      </div>
    </section>
    <section class="fq-v62-section fq-v62-workflow"><div class="fq-v62-section-head"><span>Workflow</span><h2>From scan to served in one smooth flow</h2></div><div class="fq-v62-steps"><div><b>1</b><h3>Customer scans QR</h3><p>Table or room number is detected from QR URL.</p></div><div><b>2</b><h3>Order reaches kitchen</h3><p>Kitchen accepts and updates preparation status.</p></div><div><b>3</b><h3>Bill & delivery</h3><p>Admin generates bill or assigns delivery staff.</p></div></div></section>
    <?php echo fluuexqr_v62_dashboard_visuals(); ?>
    <?php echo fluuexqr_v62_pricing_preview(); ?>
    <?php return (string) ob_get_clean();
}

function fluuexqr_v62_service_page_html(string $title, string $keyword, string $audience, string $intro = '', array $benefits = [], string $emoji = '🚀'): string {
    $benefits = $benefits ?: ['Mobile-first ordering experience','Live order flow to kitchen dashboard','Restaurant admin control panel','Billing, reports and staff workflow','Fast setup for restaurants, hotels and cafes'];
    $features = fluuexqr_v62_feature_list();
    ob_start(); ?>
    <section class="fq-v62-hero">
      <div class="fq-v62-hero-copy">
        <span class="fq-v62-badge"><?php echo esc_html($keyword); ?></span>
        <h1><?php echo esc_html($title); ?></h1>
        <p><?php echo esc_html($intro ?: ($keyword . ' for ' . $audience . ' with QR ordering, room/table tracking, kitchen display, billing, staff control, delivery workflow and analytics in one cloud platform.')); ?></p>
        <div class="fq-v62-actions"><a class="btn btn-primary" href="<?php echo esc_url(fluuexqr_v62_trial_url()); ?>">Start Free Trial</a><a class="btn btn-ghost" href="<?php echo esc_url(fluuexqr_v62_page_url('demo')); ?>">Book Live Demo</a></div>
      </div>
      <div class="fq-v62-service-visual"><div class="fq-v62-visual-emoji"><?php echo esc_html($emoji); ?></div><div class="fq-v62-live-card"><b>Live Order</b><span><?php echo esc_html(str_contains(strtolower($title),'hotel') || str_contains(strtolower($title),'room') ? 'Room 204' : 'Table 07'); ?></span><em>Ready in 08:42</em></div></div>
    </section>
    <section class="fq-v62-section">
      <div class="fq-v62-section-head"><span>Included</span><h2>What this page covers</h2><p>Built for <?php echo esc_html($audience); ?> that want faster ordering, fewer manual mistakes and premium customer experience.</p></div>
      <div class="fq-v62-benefit-grid">
        <?php foreach ($benefits as $benefit): ?><article><strong>✓ <?php echo esc_html($benefit); ?></strong><p>Part of the FluuexQR operational workflow with admin, customer and staff dashboards.</p></article><?php endforeach; ?>
      </div>
    </section>
    <section class="fq-v62-section fq-v62-dark-band"><div class="fq-v62-section-head"><span>Workflow</span><h2>How <?php echo esc_html($title); ?> works</h2></div><div class="fq-v62-steps"><div><b>01</b><h3>Setup</h3><p>Create menu, tables, rooms, staff and service settings from admin.</p></div><div><b>02</b><h3>Customer order</h3><p>Customer scans QR, adds items and places order from mobile.</p></div><div><b>03</b><h3>Operations</h3><p>Kitchen, billing, reports and delivery workflow stay connected.</p></div></div></section>
    <section class="fq-v62-section"><div class="fq-v62-section-head"><span>Connected features</span><h2>Works with the full FluuexQR platform</h2></div><div class="fq-v62-feature-grid compact">
      <?php foreach (array_slice($features, 0, 12) as $f): ?><article class="fq-v62-feature-card"><div class="fq-v62-icon"><?php echo esc_html($f[2]); ?></div><h3><?php echo esc_html($f[0]); ?></h3><p><?php echo esc_html($f[1]); ?></p></article><?php endforeach; ?>
    </div></section>
    <section class="fq-v62-cta"><h2>Ready to digitize your restaurant or hotel?</h2><p>Launch QR ordering, room orders, kitchen display, billing, reports and customer experience from one premium SaaS platform.</p><a class="btn btn-primary" href="<?php echo esc_url(fluuexqr_v62_page_url('contact-us')); ?>">Talk to FluuexQR</a></section>
    <?php return (string) ob_get_clean();
}

function fluuexqr_v62_pricing_preview(): string {
    $plans = [
        ['Starter','Free Trial','Test QR ordering and demo setup','Start Free Trial'],
        ['Basic','₹1,999/mo','Best for single restaurant with table QR, KDS and billing','Most Popular'],
        ['Premium','Custom','Multi branch, hotel room QR, delivery and advanced reports','For Growth'],
    ];
    $out = '<section class="fq-v62-section fq-v62-pricing"><div class="fq-v62-section-head"><span>Pricing</span><h2>Simple plans designed to push restaurants toward Basic</h2><p>Starter is for trial, Basic gives strong daily operations, Premium is for multi-branch and hotel scale.</p></div><div class="fq-v62-plan-grid">';
    foreach ($plans as $p) { $out .= '<article class="fq-v62-plan"><span>'.esc_html($p[3]).'</span><h3>'.esc_html($p[0]).'</h3><strong>'.esc_html($p[1]).'</strong><p>'.esc_html($p[2]).'</p><a class="btn btn-primary" href="'.esc_url(fluuexqr_v62_trial_url()).'">Choose Plan</a></article>'; }
    return $out . '</div></section>';
}

function fluuexqr_v62_plain_content(string $title, string $body, string $badge = 'FluuexQR'): string {
    return '<section class="fq-v62-hero simple"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">' . esc_html($badge) . '</span><h1>' . esc_html($title) . '</h1><p>' . esc_html($body) . '</p><div class="fq-v62-actions"><a class="btn btn-primary" href="' . esc_url(fluuexqr_v62_trial_url()) . '">Start Free Trial</a><a class="btn btn-ghost" href="' . esc_url(fluuexqr_v62_page_url('demo')) . '">View Demo</a></div></div></section>' . fluuexqr_v62_industry_cards();
}

function fluuexqr_v62_home_content(): string {
    ob_start(); ?>
    <section class="fq-v62-hero home">
      <div class="fq-v62-hero-copy"><span class="fq-v62-badge">Restaurant + Hotel SaaS</span><h1>Modern Restaurant & Hotel QR Ordering System</h1><p>Manage restaurant tables, hotel room orders, kitchen operations, billing, delivery, staff, and customer ordering from one powerful cloud platform.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="<?php echo esc_url(fluuexqr_v62_trial_url()); ?>">Start Free Trial</a><a class="btn btn-ghost" href="<?php echo esc_url(fluuexqr_v62_page_url('demo')); ?>">Book Live Demo</a></div></div>
      <div class="fq-v62-visual-panel"><div class="fq-v62-dashboard-mock"><b>Today Orders</b><strong>128</strong><span>+22% faster operations</span></div><div class="fq-v62-phone"><b>Scan QR</b><span>Table 05 · 4 items</span><em>Send to Kitchen</em></div><div class="fq-v62-float-card one">Live Kitchen</div><div class="fq-v62-float-card two">Room QR</div></div>
    </section>
    <section class="fq-v62-section"><div class="fq-v62-section-head"><span>Business types</span><h2>Built for restaurants, hotels and food businesses</h2></div><?php echo fluuexqr_v62_industry_cards(); ?></section>
    <section class="fq-v62-section"><div class="fq-v62-section-head"><span>Features</span><h2>Premium features that look visual and work practically</h2><p>All essential modules are shown clearly so restaurant and hotel owners understand the value quickly.</p></div><div class="fq-v62-feature-grid"><?php foreach (array_slice(fluuexqr_v62_feature_list(),0,12) as $f): ?><article class="fq-v62-feature-card"><div class="fq-v62-icon"><?php echo esc_html($f[2]); ?></div><h3><?php echo esc_html($f[0]); ?></h3><p><?php echo esc_html($f[1]); ?></p></article><?php endforeach; ?></div><div class="fq-v62-center"><a class="btn btn-primary" href="<?php echo esc_url(fluuexqr_v62_page_url('features')); ?>">Open All Features</a></div></section>
    <?php echo fluuexqr_v62_service_page_html('Hotel Room QR System','Room QR Ordering','hotels and resorts','Guests scan room QR, order food from their room, room number auto-detects, and orders directly reach kitchen with billing integration.',['QR inside hotel rooms','Guest scans QR from mobile','Food ordering from room','Room number auto-detection','Orders directly reach kitchen','Billing integration','Service request system'],'🏨'); ?>
    <?php echo fluuexqr_v62_dashboard_visuals(); ?>
    <?php echo fluuexqr_v62_pricing_preview(); ?>
    <?php return (string) ob_get_clean();
}

function fluuexqr_v62_faq_content(): string {
    $faqs = [
        ['What is FluuexQR?','FluuexQR is a cloud-based Restaurant and Hotel QR Ordering SaaS Platform for QR menu, table ordering, room ordering, kitchen display, billing, delivery and reports.'],
        ['Can hotels use room QR ordering?','Yes. Hotels can place QR codes inside rooms so guests can order food or services with room number auto-detection.'],
        ['Can customers order without app?','Yes. Customers scan QR and order from browser without installing an app.'],
        ['Does it support delivery?','Yes. It supports delivery boy assignment, Google Maps navigation and delivery status tracking.'],
        ['Is billing included?','Yes. FluuexQR includes restaurant billing and invoice workflow.'],
        ['Can restaurants manage multiple branches?','Yes. Premium setup can support multi-branch management.'],
        ['Does kitchen get live orders?','Yes. Kitchen dashboard shows live orders with accept, preparing, ready and served status.'],
        ['Does it support staff accounts?','Yes. Restaurant staff, kitchen users and delivery users can get role-based access.'],
    ];
    $out = '<section class="fq-v62-hero simple"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">FAQ</span><h1>Frequently Asked Questions</h1><p>Answers about FluuexQR restaurant and hotel QR ordering platform.</p></div></section><section class="fq-v62-faq-list">';
    foreach ($faqs as $i=>$f) { $out .= '<details '.($i===0?'open':'').'><summary>'.esc_html($f[0]).'</summary><p>'.esc_html($f[1]).'</p></details>'; }
    return $out . '</section>';
}

function fluuexqr_v62_contact_content(): string {
    return '<section class="fq-v62-hero simple"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Contact</span><h1>Book FluuexQR Demo</h1><p>Tell us your restaurant or hotel requirement. We can help with QR ordering, room QR, table QR, KDS, billing, delivery workflow, staff and subscription setup.</p><div class="fq-v62-actions"><a class="btn btn-primary" href="tel:+910000000000">Call Now</a><a class="btn btn-ghost" href="mailto:hello@fluuexqr.com">Email Us</a></div></div></section><section class="fq-v62-section"><div class="fq-v62-contact-grid"><article><h3>Sales Demo</h3><p>Show owners customer menu, admin dashboard, kitchen display and billing workflow.</p></article><article><h3>Setup Support</h3><p>Restaurant menu upload, table/room QR setup, staff login and dashboard configuration.</p></article><article><h3>Custom Requirement</h3><p>Delivery tracking, multi branch, hotel room service, offers, combos and reporting.</p></article></div></section>';
}

function fluuexqr_v62_blogs_content(): string {
    return '<section class="fq-v62-hero simple"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">Blogs</span><h1>Restaurant Technology Blogs</h1><p>SEO-friendly articles on QR ordering, hotel ordering, restaurant automation, delivery management and contactless dining.</p></div></section><section class="fq-v62-section"><div class="fq-v62-feature-grid"><article class="fq-v62-feature-card"><h3>Restaurant Technology</h3><p>Modern tools for restaurant owners.</p></article><article class="fq-v62-feature-card"><h3>QR Ordering</h3><p>How QR menus improve operations.</p></article><article class="fq-v62-feature-card"><h3>Hotel Ordering</h3><p>Room QR and service workflow.</p></article><article class="fq-v62-feature-card"><h3>Delivery Management</h3><p>Track and assign delivery orders.</p></article></div></section>';
}

function fluuexqr_v62_create_marketing_pages(): void {
    $pages = [
        'home' => ['Home', fluuexqr_v62_home_content()],
        'features' => ['Features', fluuexqr_v62_features_full_html()],
        'demo' => ['Demo', fluuexqr_v62_plain_content('FluuexQR Live Demo','Explore customer ordering UI, restaurant dashboard, kitchen display, billing screen, QR generation, hotel room ordering and delivery tracking workflow.','Demo')],
        'about-us' => ['About Us', fluuexqr_v62_plain_content('About FluuexQR','FluuexQR is a cloud-based Restaurant and Hotel Digital Ordering SaaS Platform that helps restaurants, cafes, hotels, resorts, food courts, cloud kitchens, bars and lounges digitize ordering, kitchen operations, billing and customer experience.')],
        'contact-us' => ['Contact Us', fluuexqr_v62_contact_content()],
        'blogs' => ['Blogs', fluuexqr_v62_blogs_content()],
        'faq' => ['FAQ', fluuexqr_v62_faq_content()],
        'support' => ['Support', fluuexqr_v62_plain_content('FluuexQR Support','Get help with setup, QR generation, menu upload, table QR, room QR, kitchen dashboard, billing, delivery workflow, staff login and subscription management.','Support')],
        'start-free-trial' => ['Start Free Trial', fluuexqr_v62_plain_content('Start Your Free Trial','Try FluuexQR for restaurant and hotel QR ordering, table and room QR, kitchen display, billing, delivery workflow and admin dashboard.','Free Trial')],
    ];

    foreach (fluuexqr_v62_service_data() as $slug => $data) {
        $pages[$slug] = [$data[0], fluuexqr_v62_service_page_html($data[0], $data[1], $data[2], $data[3], $data[4] ?? [], $data[5] ?? '🚀')];
    }

    $landing_pages = [
        'best-qr-menu-system-for-restaurants' => ['Best QR Menu System for Restaurants','Restaurant QR Menu','restaurants that want modern digital ordering'],
        'restaurant-ordering-software-in-india' => ['Restaurant Ordering Software in India','Restaurant Ordering Software','Indian restaurants, cafes and hotels'],
        'hotel-qr-ordering-system' => ['Hotel QR Ordering System','Hotel QR Ordering','hotels, resorts and room service teams'],
        'restaurant-billing-ordering-software' => ['Restaurant Billing & Ordering Software','Restaurant Billing Software','restaurants that need ordering plus billing'],
        'qr-menu-for-cafes' => ['QR Menu for Cafes','QR Menu for Cafes','cafes and quick service restaurants'],
        'restaurant-digital-transformation' => ['Restaurant Digital Transformation','Restaurant Digital Transformation','restaurants upgrading from manual operations'],
        'smart-restaurant-technology' => ['Smart Restaurant Technology','Smart Restaurant Technology','modern restaurants and hotel food services'],
        'contactless-restaurant-ordering' => ['Contactless Restaurant Ordering','Contactless Restaurant Ordering','restaurants offering contactless dining'],
    ];
    foreach ($landing_pages as $slug => $data) {
        $pages[$slug] = [$data[0], fluuexqr_v62_service_page_html($data[0], $data[1], $data[2])];
    }

    foreach ($pages as $slug => $page) {
        $existing = get_page_by_path($slug);
        $args = ['post_title'=>$page[0], 'post_name'=>$slug, 'post_content'=>$page[1], 'post_status'=>'publish', 'post_type'=>'page'];
        if ($existing instanceof WP_Post) { $args['ID'] = $existing->ID; wp_update_post($args); update_post_meta($existing->ID, '_wp_page_template', 'page-saas.php'); }
        else { $args['meta_input'] = ['_wp_page_template' => 'page-saas.php']; wp_insert_post($args); }
    }
}

function fluuexqr_v62_install_marketing_pages_once(): void {
    $version = '1.4.62';
    if (get_option('fluuexqr_v62_marketing_pages_version') === $version) { return; }
    fluuexqr_v62_create_marketing_pages();
    update_option('fluuexqr_v62_marketing_pages_version', $version, false);
}
function fluuexqr_v62_force_marketing_pages_on_switch(): void {
    fluuexqr_v62_create_marketing_pages();
    update_option('fluuexqr_v62_marketing_pages_version', '1.4.62', false);
}
add_action('after_switch_theme', 'fluuexqr_v62_force_marketing_pages_on_switch', 20);
add_action('init', 'fluuexqr_v62_install_marketing_pages_once', 20);

function fluuexqr_v61_feature_list(): array { return array_map(fn($f) => $f[0], fluuexqr_v62_feature_list()); }
function fluuexqr_v61_service_page_html(string $title, string $keyword, string $audience, array $benefits = []): string { return fluuexqr_v62_service_page_html($title, $keyword, $audience, '', $benefits); }
function fluuexqr_v61_plain_content(string $title, string $body): string { return fluuexqr_v62_plain_content($title, $body); }
function fluuexqr_v61_create_marketing_pages(): void { fluuexqr_v62_create_marketing_pages(); }

function fluuexqr_v61_seed_blog_posts(): void {
    $posts = [
        'Why Restaurants Need QR Ordering in 2026' => 'QR ordering helps restaurants reduce waiting time, improve table turnover, manage kitchen orders faster and offer a modern contactless dining experience.',
        'How Hotel Room QR Ordering Improves Guest Experience' => 'Room QR ordering lets hotel guests scan, order food and send requests without calling reception, while the kitchen receives room-wise orders instantly.',
        'Kitchen Display System: The Backbone of Fast Restaurant Operations' => 'A kitchen display system organizes live orders, preparation status, order timers and ready/served workflow for restaurant teams.',
        'Restaurant Billing and QR Ordering in One Platform' => 'Using one platform for menu, orders, kitchen and billing reduces manual errors and helps owners manage daily operations from one dashboard.',
        'Best QR Menu System for Restaurants in India' => 'A complete QR menu system should include table QR, room QR, kitchen display, billing, coupons, reports and staff control.',
        'How Contactless Dining Helps Cafes and Restaurants' => 'Contactless dining allows customers to scan a QR code, view menu, order quickly and reduce manual waiting time.',
    ];
    foreach ($posts as $title => $excerpt) {
        if (get_page_by_title($title, OBJECT, 'post')) { continue; }
        wp_insert_post(['post_title'=>$title,'post_status'=>'publish','post_type'=>'post','post_excerpt'=>$excerpt,'post_content'=>'<p>' . esc_html($excerpt) . '</p><p>FluuexQR combines restaurant QR menu, hotel room QR ordering, kitchen display, billing, staff management, delivery workflow and analytics for modern food businesses.</p>']);
    }
}
add_action('after_switch_theme', 'fluuexqr_v61_seed_blog_posts', 25);

function fluuexqr_v61_marketing_meta(): array {
    $meta = [
        'features' => ['FluuexQR Features | Restaurant + Hotel QR Ordering Platform','Explore all FluuexQR features including table QR, room QR, KDS, billing, delivery, staff, reports, coupons, combos and SaaS subscription.'],
        'restaurant-qr-menu' => ['Restaurant QR Menu System | FluuexQR','Launch a modern restaurant QR menu with table ordering, kitchen display, billing and customer feedback.'],
        'hotel-room-qr-ordering' => ['Hotel QR Ordering System | Room QR Ordering | FluuexQR','Enable hotel room QR ordering with room number auto-detection, kitchen display and billing integration.'],
        'kitchen-display-system' => ['Kitchen Display System for Restaurants | FluuexQR','Manage live orders, table/room numbers, preparation status and kitchen workflow with FluuexQR KDS.'],
        'restaurant-billing-software' => ['Restaurant Billing Software with QR Ordering | FluuexQR','Restaurant billing, invoice printing, QR ordering and kitchen operations in one SaaS platform.'],
        'restaurant-ordering-software-in-india' => ['Restaurant Ordering Software in India | FluuexQR','Cloud-based restaurant ordering software for India with QR menu, billing, KDS, delivery and reports.'],
        'hotel-qr-ordering-system' => ['Hotel QR Ordering System | FluuexQR','Room QR food ordering system for hotels and resorts with direct kitchen order flow.'],
        'best-qr-menu-system-for-restaurants' => ['Best QR Menu System for Restaurants | FluuexQR','Premium QR menu system for restaurants with table ordering, live kitchen display, billing and analytics.'],
    ];
    foreach (fluuexqr_v62_service_data() as $slug => $data) { $meta[$slug] = [$data[0] . ' | FluuexQR', $data[1] . ' for ' . $data[2] . ' with QR ordering, KDS, billing, staff and reports.']; }
    return $meta;
}

function fluuexqr_v61_faq_schema(): void {
    if (!is_page('faq')) { return; }
    $schema = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
    $faqs = [
        ['What is FluuexQR?','FluuexQR is a cloud-based Restaurant and Hotel QR Ordering SaaS Platform for QR menu, table ordering, room ordering, kitchen display, billing, delivery and reports.'],
        ['Can hotels use room QR ordering?','Yes. Hotels can place QR codes inside rooms so guests can order food or services with room number auto-detection.'],
        ['Can customers order without app?','Yes. Customers scan QR and order from browser without installing an app.'],
        ['Does it support delivery?','Yes. It supports delivery boy assignment, Google Maps navigation and delivery status tracking.'],
        ['Is billing included?','Yes. FluuexQR includes restaurant billing and invoice workflow.'],
        ['Can restaurants manage multiple branches?','Yes. Premium setup can support multi-branch management.'],
        ['Does kitchen get live orders?','Yes. Kitchen dashboard shows live orders with accept, preparing, ready and served status.'],
        ['Does it support staff accounts?','Yes. Restaurant staff, kitchen users and delivery users can get role-based access.'],
    ];
    foreach ($faqs as $faq) { $schema['mainEntity'][] = ['@type'=>'Question','name'=>$faq[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$faq[1]]]; }
    echo "\n<script type=\"application/ld+json\">" . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'fluuexqr_v61_faq_schema', 31);
?>
