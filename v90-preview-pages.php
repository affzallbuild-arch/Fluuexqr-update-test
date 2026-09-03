<?php
if (!defined('ABSPATH')) { exit; }
function fluuexqr_v90_page_content(string $title, string $keyword, string $body): string {
    return '<section class="fq-saas-page"><div class="mq-container"><section class="fq-v62-hero simple"><div class="fq-v62-hero-copy"><span class="fq-v62-badge">'.esc_html($keyword).'</span><h1>'.esc_html($title).'</h1><p>'.esc_html($body).'</p><div class="fq-v62-actions"><a class="btn btn-primary" href="'.esc_url(menuqr_get_page_url_by_slug('signup')).'">Start Free Trial</a><a class="btn btn-ghost" href="'.esc_url(menuqr_get_page_url_by_slug('contact-us')).'">Contact FluuexQR</a></div></div></section></div></section>';
}
function fluuexqr_v90_create_missing_pages(): void {
    $pages = [
        'restaurant-qr-menu-system-purnia' => ['Restaurant QR Menu System in Purnia','QR Menu Purnia','FluuexQR helps restaurants, cafes, dhabas and hotels in Purnia launch QR menu, table ordering, hotel room QR, kitchen display, smart billing and UPI payments.'],
        'restaurant-qr-menu-system-katihar' => ['Restaurant QR Menu System in Katihar','QR Menu Katihar','Digitize restaurant ordering in Katihar with QR menu software, kitchen dashboard, billing, hotel room service QR and restaurant automation.'],
        'restaurant-qr-menu-system-saharsa' => ['Restaurant QR Menu System in Saharsa','QR Menu Saharsa','Build a modern restaurant ordering experience in Saharsa with table QR, room QR, thermal bills, UPI payments and live kitchen display.'],
        'restaurant-qr-menu-system-patna' => ['Restaurant QR Menu System in Patna','QR Menu Patna','A professional QR ordering and billing platform for Patna restaurants, hotels, cafes and cloud kitchens.'],
        'restaurant-qr-menu-system-delhi' => ['Restaurant QR Ordering Software in Delhi','Restaurant Software Delhi','FluuexQR supports Delhi restaurants, lounges and cloud kitchens with QR ordering, KDS, billing, delivery and analytics.'],
        'restaurant-qr-menu-system-mumbai' => ['Hotel Room QR Ordering System in Mumbai','Hotel QR Mumbai','Mumbai hotels and restaurants can use FluuexQR for room-wise QR ordering, room service, kitchen display and smart restaurant billing.'],
        'help-center' => ['FluuexQR Help Center','FluuexQR Support','Get help with QR setup, menu upload, room QR, table QR, kitchen dashboard, billing and restaurant automation.'],
        'documentation' => ['FluuexQR Documentation','Restaurant QR Documentation','Setup documentation for restaurant QR menu, hotel room QR ordering, KDS, billing and staff management.'],
        'faq' => ['FluuexQR FAQ','Restaurant QR FAQ','Frequently asked questions about FluuexQR restaurant and hotel QR ordering platform.'],
    ];
    foreach ($pages as $slug=>$data) {
        $existing = get_page_by_path($slug);
        $args = ['post_title'=>$data[0],'post_name'=>$slug,'post_content'=>fluuexqr_v90_page_content($data[0],$data[1],$data[2]),'post_status'=>'publish','post_type'=>'page'];
        if ($existing instanceof WP_Post) { $args['ID']=$existing->ID; wp_update_post($args); }
        else { wp_insert_post($args); }
    }
}
function fluuexqr_v90_install_once(): void {
    if (get_option('fluuexqr_v90_preview_pages_version') === '1.0.90') { return; }
    fluuexqr_v90_create_missing_pages();
    update_option('fluuexqr_v90_preview_pages_version','1.0.90',false);
}
add_action('init','fluuexqr_v90_install_once',40);
add_action('after_switch_theme','fluuexqr_v90_create_missing_pages',40);
