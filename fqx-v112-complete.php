<?php
if (!defined('ABSPATH')) { exit; }

function fqx_v112_schema_update(): void {
    if ((int) get_option('fqx_v112_schema_version', 0) >= 112) { return; }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE " . fqx_table('subscription_logs') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        restaurant_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        subscription_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        action VARCHAR(120) NOT NULL,
        note TEXT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        PRIMARY KEY(id), KEY restaurant_id(restaurant_id), KEY subscription_id(subscription_id), KEY action(action)
    ) $charset;");
    $adds = [
        menuqr_table('payment_settings') => ['upi_merchant_name VARCHAR(191) NULL','payment_instructions TEXT NULL','manual_verification_required TINYINT(1) NOT NULL DEFAULT 1','bank_transfer_enabled TINYINT(1) NOT NULL DEFAULT 0','bank_account_name VARCHAR(191) NULL','bank_account_number VARCHAR(100) NULL','bank_ifsc VARCHAR(50) NULL','bank_name VARCHAR(191) NULL','bank_branch VARCHAR(191) NULL','razorpay_webhook_secret TEXT NULL','razorpay_mode VARCHAR(20) NOT NULL DEFAULT \'test\'','stripe_webhook_secret TEXT NULL','stripe_mode VARCHAR(20) NOT NULL DEFAULT \'test\'','whatsapp_enabled TINYINT(1) NOT NULL DEFAULT 0','whatsapp_number VARCHAR(60) NULL','whatsapp_api_token TEXT NULL','bill_message_template TEXT NULL','payment_reminder_template TEXT NULL','review_request_template TEXT NULL','auto_send_bill TINYINT(1) NOT NULL DEFAULT 0'],
        fqx_table('restaurant_payment_settings') => ['whatsapp_enabled TINYINT(1) NOT NULL DEFAULT 0','whatsapp_number VARCHAR(60) NULL','whatsapp_api_token_encrypted TEXT NULL','bank_transfer_enabled TINYINT(1) NOT NULL DEFAULT 0','bank_account_name VARCHAR(191) NULL','bank_account_number VARCHAR(100) NULL','bank_ifsc VARCHAR(50) NULL','bank_name VARCHAR(191) NULL','bank_branch VARCHAR(191) NULL','bill_message_template TEXT NULL','payment_reminder_template TEXT NULL','review_request_template TEXT NULL','auto_send_bill TINYINT(1) NOT NULL DEFAULT 0'],
        menuqr_table('orders') => ['transaction_id VARCHAR(191) NULL','paid_at DATETIME NULL'],
        menuqr_table('bills') => ['transaction_id VARCHAR(191) NULL','paid_at DATETIME NULL'],
        menuqr_table('subscriptions') => ['trial_used TINYINT(1) NOT NULL DEFAULT 0','auto_renew_enabled TINYINT(1) NOT NULL DEFAULT 0','auto_renew_method VARCHAR(50) NULL','renewal_reminder_sent VARCHAR(191) NULL','gateway VARCHAR(50) NULL','gateway_subscription_id VARCHAR(191) NULL','last_payment_id BIGINT UNSIGNED NOT NULL DEFAULT 0'],
        menuqr_table('subscription_payments') => ['plan_id BIGINT UNSIGNED NOT NULL DEFAULT 0','currency VARCHAR(10) NOT NULL DEFAULT \'INR\'','gateway VARCHAR(50) NULL','gateway_payment_id VARCHAR(191) NULL','gateway_order_id VARCHAR(191) NULL','utr_number VARCHAR(191) NULL','screenshot_url VARCHAR(255) NULL','admin_note TEXT NULL','paid_at DATETIME NULL']
    ];
    foreach ($adds as $table => $defs) {
        foreach ($defs as $def) {
            $col = preg_replace('/\s+.*/', '', $def);
            $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $col));
            if (!$exists) { $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$def}"); }
        }
    }
    update_option('fqx_v112_schema_version', 112, false);
}
add_action('after_switch_theme', 'fqx_v112_schema_update', 4);
add_action('init', 'fqx_v112_schema_update', 2);
add_action('init', function(){ if ((int) get_option('fqx_default_plans_version', 0) < 112 && function_exists('fqx_create_default_plans')) { fqx_create_default_plans(); } }, 9);

add_action('fqx_subscription_log', function(int $restaurant_id, string $action, string $note){
    global $wpdb; $sub = menuqr_get_latest_subscription($restaurant_id);
    $wpdb->insert(fqx_table('subscription_logs'), ['restaurant_id'=>$restaurant_id,'subscription_id'=>$sub?(int)$sub->id:0,'action'=>sanitize_key($action),'note'=>sanitize_textarea_field($note),'created_by'=>get_current_user_id(),'created_at'=>current_time('mysql')]);
}, 10, 3);

function fqx_v112_limit_label($value): string { return ((int)$value < 0) ? 'Unlimited' : (string)(int)$value; }
function fqx_v112_group_label(string $key): string { return ucwords(str_replace('_',' ', $key)); }

function fqx_generate_whatsapp_bill_message($bill_id): string {
    $bill = function_exists('menuqr_get_bill_by_id') ? menuqr_get_bill_by_id((int)$bill_id) : null; if (!$bill) { return ''; }
    $restaurant = menuqr_get_restaurant((int)$bill->restaurant_id);
    $status = strtoupper((string)($bill->payment_status ?? 'unpaid'));
    $table_room = !empty($bill->room_number) ? 'Room ' . $bill->room_number : ('Table ' . ($bill->table_number ?: $bill->table_id));
    $bill_url = add_query_arg(['bill_id'=>(int)$bill->id,'key'=>(string)$bill->access_key], menuqr_get_page_url_by_slug('bill') ?: home_url('/bill/'));
    $review_url = add_query_arg(['r'=>(int)$bill->restaurant_id,'bill'=>(int)$bill->id,'review'=>1], menuqr_get_page_url_by_slug('menu') ?: home_url('/menu/'));
    $settings = menuqr_get_payment_settings((int)$bill->restaurant_id);
    $tpl = trim((string)($settings->bill_message_template ?? '')) ?: "Hi 👋 Thank you for ordering from {restaurant_name}.\nBill No: {bill_no}\nTable/Room: {table_or_room}\nTotal: ₹{total}\nPayment Status: {status}\nDownload Bill: {bill_link}\nReview: {review_link}\nPowered by FluuexQR";
    return strtr($tpl, ['{restaurant_name}'=>$restaurant?(string)$restaurant->name:'Restaurant','{bill_no}'=>(string)$bill->bill_number,'{table_or_room}'=>$table_room,'{total}'=>number_format((float)$bill->grand_total,2),'{status}'=>$status,'{bill_link}'=>$bill_url,'{review_link}'=>$review_url,'{order_id}'=>(string)$bill->id,'{payment_status}'=>$status,'{grand_total}'=>number_format((float)$bill->grand_total,2),'{customer_name}'=>(string)($bill->customer_name ?? '')]);
}
function fqx_get_whatsapp_bill_link($bill_id): string {
    $bill = function_exists('menuqr_get_bill_by_id') ? menuqr_get_bill_by_id((int)$bill_id) : null; if (!$bill) { return ''; }
    $settings = menuqr_get_payment_settings((int)$bill->restaurant_id);
    $phone = preg_replace('/\D+/', '', (string)($bill->customer_whatsapp ?: $settings->whatsapp_number));
    if ($phone !== '' && strpos($phone, '91') !== 0 && strlen($phone) === 10) { $phone = '91' . $phone; }
    return 'https://wa.me/' . $phone . '?text=' . rawurlencode(fqx_generate_whatsapp_bill_message((int)$bill_id));
}
function fqx_send_whatsapp_bill($bill_id) { return fqx_get_whatsapp_bill_link((int)$bill_id); }
function fqx_send_whatsapp_review_request($order_id) { return true; }
function fqx_send_whatsapp_payment_reminder($bill_id) { return fqx_get_whatsapp_bill_link((int)$bill_id); }
function fqx_v112_render_whatsapp_button($bill_id): string { $url=fqx_get_whatsapp_bill_link((int)$bill_id); return $url ? '<a class="btn btn-outline btn-sm fqx-wa-btn" target="_blank" rel="noopener" href="'.esc_url($url).'">📲 WhatsApp Bill</a>' : ''; }

add_action('wp_ajax_fqx_send_whatsapp_bill', function(){ check_ajax_referer('menuqr_nonce','nonce'); $bill_id=absint($_POST['bill_id']??0); $bill=menuqr_get_bill_by_id($bill_id); if(!$bill){wp_send_json_error(['message'=>'Bill not found']);} menuqr_validate_restaurant_access((int)$bill->restaurant_id); wp_send_json_success(['url'=>fqx_get_whatsapp_bill_link($bill_id),'message'=>fqx_generate_whatsapp_bill_message($bill_id)]); });

function fqx_v112_save_customer_manual_payment(): void {
    check_ajax_referer('menuqr_nonce','nonce'); global $wpdb;
    $order_id=absint($_POST['order_id']??0); $bill_id=absint($_POST['bill_id']??0); $utr=sanitize_text_field(wp_unslash($_POST['utr_number']??'')); $method=sanitize_key(wp_unslash($_POST['payment_method']??'upi'));
    $order=$order_id?$wpdb->get_row($wpdb->prepare("SELECT * FROM ".menuqr_table('orders')." WHERE id=%d",$order_id)):null; $bill=$bill_id?menuqr_get_bill_by_id($bill_id):null; $restaurant_id=$order?(int)$order->restaurant_id:($bill?(int)$bill->restaurant_id:0); if(!$restaurant_id){wp_send_json_error(['message'=>'Order/Bill not found']);}
    $amount=$order?(float)$order->final_total:(float)$bill->grand_total; fqx_upsert_order_payment($restaurant_id,$order_id,$bill_id,$amount,$method,'pending_verification',$utr,'');
    if($order_id){$wpdb->update(menuqr_table('orders'),['payment_method'=>$method,'payment_status'=>'pending_verification','payment_reference'=>$utr,'updated_at'=>current_time('mysql')],['id'=>$order_id]);}
    if($bill_id){$wpdb->update(menuqr_table('bills'),['payment_method'=>$method,'payment_status'=>'pending_verification','transaction_id'=>$utr,'updated_at'=>current_time('mysql')],['id'=>$bill_id]);}
    wp_send_json_success(['message'=>'Payment submitted for verification.']);
}
add_action('wp_ajax_fqx_submit_customer_manual_payment','fqx_v112_save_customer_manual_payment');
add_action('wp_ajax_nopriv_fqx_submit_customer_manual_payment','fqx_v112_save_customer_manual_payment');
function fqx_v112_verify_razorpay_signature(string $order_id,string $payment_id,string $signature,string $secret): bool { return $order_id!=='' && $payment_id!=='' && $signature!=='' && $secret!=='' && hash_equals(hash_hmac('sha256',$order_id.'|'.$payment_id,$secret),$signature); }
function fqx_v112_restrict_expired_customer_menu(): void { if(!is_page('menu')){return;} $restaurant_id=absint($_GET['r']??$_GET['restaurant_id']??0); if($restaurant_id && function_exists('menuqr_subscription_is_active') && !menuqr_subscription_is_active($restaurant_id)){ wp_die('<div style="font-family:system-ui;max-width:520px;margin:80px auto;padding:28px;border-radius:24px;background:#fff7ed;border:1px solid #fed7aa;text-align:center"><h2 style="margin:0 0 10px;color:#9a3412">Subscription inactive</h2><p>This restaurant’s FluuexQR subscription is inactive. Please contact restaurant staff.</p></div>','FluuexQR Subscription Inactive',['response'=>402]); } }
add_action('template_redirect','fqx_v112_restrict_expired_customer_menu',1);
function fqx_v112_enqueue_assets(): void { wp_enqueue_style('fqx-v112-complete',MENUQR_THEME_URI.'/assets/css/fqx-v112-complete.css',['fluuexqr-v101-foodwala-menu-ui'],menuqr_asset_version('assets/css/fqx-v112-complete.css')); wp_enqueue_script('fqx-v112-complete',MENUQR_THEME_URI.'/assets/js/fqx-v112-complete.js',['jquery','fluuexqr-v81-bundle'],menuqr_asset_version('assets/js/fqx-v112-complete.js'),true); }
add_action('wp_enqueue_scripts','fqx_v112_enqueue_assets',30);
