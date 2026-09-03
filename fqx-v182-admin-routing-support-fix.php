<?php
/**
 * FluuexQR v182 — admin routing/support stability fixes.
 * Keeps dashboard stable; WhatsApp Settings and AI Support admin pages are currently hidden.
 */
if (!defined('ABSPATH')) { exit; }

function fqx_v182_admin_url(string $tab, array $args = []): string {
    $args = array_merge(['tab' => sanitize_key($tab)], $args);
    return add_query_arg($args, function_exists('menuqr_get_page_url_by_slug') ? menuqr_get_page_url_by_slug('restaurant-dashboard') : home_url('/restaurant-dashboard/'));
}

function fqx_v182_normalize_dashboard_route(): void {
    if (is_admin() || !function_exists('is_page') || !is_page('restaurant-dashboard')) { return; }
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash((string) $_GET['tab'])) : '';
    $section = isset($_GET['section']) ? sanitize_key(wp_unslash((string) $_GET['section'])) : '';
    $target = '';
    if ('' === $tab || 'overview' === $tab || 'restaurant-dashboard' === $tab) {
        $target = fqx_v182_admin_url('dashboard');
    } elseif ('payment-settings' === $tab) {
        $target = fqx_v182_admin_url('payments');
    } elseif ('whatsapp-settings' === $tab || 'whatsapp' === $tab || ('payments' === $tab && 'whatsapp' === $section) || 'fluuexqr-ai-support' === $tab || 'ai-support' === $tab || 'support' === $tab) {
        $target = fqx_v182_admin_url('dashboard');
    }
    if ($target) {
        wp_safe_redirect($target, 302);
        exit;
    }
}
add_action('template_redirect', 'fqx_v182_normalize_dashboard_route', 1);

function fqx_v182_payment_object_to_array(object $payment): array {
    $fields = [
        'cash_enabled','upi_enabled','upi_id','upi_qr','upi_merchant_name','payment_instructions','manual_verification_required','screenshot_enabled','online_enabled',
        'bank_transfer_enabled','bank_account_name','bank_account_number','bank_ifsc','bank_name','bank_branch',
        'razorpay_key','razorpay_secret','razorpay_webhook_secret','razorpay_mode','stripe_publishable_key','stripe_secret_key','stripe_webhook_secret','stripe_mode',
        'whatsapp_enabled','whatsapp_number','whatsapp_api_token','bill_message_template','payment_reminder_template','review_request_template','auto_send_bill',
        'gateway_provider','phonepe_enabled','phonepe_client_id','phonepe_client_secret','phonepe_client_version','phonepe_merchant_id','phonepe_environment'
    ];
    $data = [];
    foreach ($fields as $field) { $data[$field] = $payment->{$field} ?? ''; }
    return $data;
}

function fqx_v182_handle_save_whatsapp_settings(): void {
    if (!function_exists('menuqr_require_role')) { wp_die('FluuexQR core missing.'); }
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    menuqr_require_post_nonce('fqx_whatsapp_nonce', 'fqx_save_whatsapp_settings');
    $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
    menuqr_validate_restaurant_access($restaurant_id);
    $current = menuqr_get_payment_settings($restaurant_id);
    $data = fqx_v182_payment_object_to_array($current);
    $data['whatsapp_enabled'] = !empty($_POST['whatsapp_enabled']);
    $data['whatsapp_number'] = wp_unslash($_POST['whatsapp_number'] ?? '');
    $data['whatsapp_api_token'] = wp_unslash($_POST['whatsapp_api_token'] ?? '');
    $data['bill_message_template'] = wp_unslash($_POST['bill_message_template'] ?? '');
    $data['payment_reminder_template'] = wp_unslash($_POST['payment_reminder_template'] ?? '');
    $data['review_request_template'] = wp_unslash($_POST['review_request_template'] ?? '');
    $data['order_update_template'] = wp_unslash($_POST['order_update_template'] ?? ($current->order_update_template ?? ''));
    $data['auto_send_bill'] = !empty($_POST['auto_send_bill']);
    $ok = menuqr_save_payment_settings($restaurant_id, $data);
    if (function_exists('fqx_clear_cache_after_update')) { fqx_clear_cache_after_update(); }
    elseif (function_exists('fqx_v177_purge_all_caches')) { fqx_v177_purge_all_caches(); }
    $redirect = fqx_v182_admin_url('whatsapp', ['mq_notice' => $ok ? 'whatsapp_saved' : 'whatsapp_error']);
    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_fqx_save_whatsapp_settings', 'fqx_v182_handle_save_whatsapp_settings');

function fqx_clear_cache_after_update(): void {
    if (function_exists('fqx_v177_purge_all_caches')) { fqx_v177_purge_all_caches(); return; }
    if (function_exists('litespeed_purge_all')) { litespeed_purge_all(); }
    if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) { LiteSpeed_Cache_API::purge_all(); }
    if (function_exists('rocket_clean_domain')) { rocket_clean_domain(); }
    if (function_exists('w3tc_flush_all')) { w3tc_flush_all(); }
    if (function_exists('wp_cache_clear_cache')) { wp_cache_clear_cache(); }
    if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
}

function fqx_v182_admin_critical_css(): void {
    if (is_admin() || !function_exists('is_page') || !is_page('restaurant-dashboard')) { return; }
    ?>
    <style id="fqx-v182-dashboard-stability">
      body.fqx-v145-restaurant-admin .app-shell.dashboard-shell{opacity:1!important;visibility:visible!important;background:#071018!important;transition:none!important}
      body.fqx-v145-restaurant-admin .page-body{background:#071018!important}
      body.fqx-v145-restaurant-admin .old-dashboard,body.fqx-v145-restaurant-admin .legacy-dashboard,body.fqx-v145-restaurant-admin [data-old-dashboard],body.fqx-v145-restaurant-admin .mq-old-dashboard{display:none!important;visibility:hidden!important}
      .fqx-v182-route-loading{opacity:.78;pointer-events:none}
      .fqx-ai-support-page,.fqx-whatsapp-settings-page{display:grid;gap:18px;color:#fff}.fqx-ai-grid,.fqx-wa-grid{display:grid;grid-template-columns:minmax(0,1.35fr) 360px;gap:18px}.fqx-ai-card,.fqx-wa-card{background:linear-gradient(180deg,rgba(16,25,35,.98),rgba(10,16,23,.96));border:1px solid rgba(246,193,90,.18);border-radius:22px;box-shadow:0 18px 40px rgba(0,0,0,.25);padding:20px}.fqx-ai-card h3,.fqx-wa-card h3{margin:0 0 12px;color:#fff}.fqx-ai-titlebar,.fqx-wa-titlebar{display:flex;align-items:center;justify-content:space-between;gap:14px}.fqx-ai-titlebar h1,.fqx-wa-titlebar h1{margin:0;color:#fff;font-size:34px}.fqx-ai-titlebar p,.fqx-wa-titlebar p{margin:6px 0 0;color:#f6c15a}.fqx-wa-form label{display:grid;gap:7px;color:#cfd8e3;font-weight:700;font-size:13px;margin-bottom:14px}.fqx-wa-form input,.fqx-wa-form textarea,.fqx-ai-input{background:#0f1822;border:1px solid rgba(246,193,90,.18);border-radius:14px;color:#fff;padding:13px 14px;width:100%;font:inherit}.fqx-wa-form textarea{min-height:105px;resize:vertical}.fqx-v182-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(180deg,#f7c456,#e8a628);color:#1a1305;border:0;border-radius:13px;padding:12px 16px;font-weight:900;text-decoration:none;cursor:pointer}.fqx-v182-btn.secondary{background:#111a24;color:#fff;border:1px solid rgba(246,193,90,.2)}.fqx-ai-prompts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.fqx-ai-prompt{background:#101923;border:1px solid rgba(246,193,90,.16);border-radius:16px;padding:14px;color:#dbe5ef;cursor:pointer}.fqx-ai-chatbox{min-height:260px;max-height:360px;overflow:auto;background:#0f1822;border:1px solid rgba(246,193,90,.12);border-radius:18px;padding:14px}.fqx-ai-msg{padding:12px 14px;border-radius:14px;margin:0 0 10px;background:#131f2a;color:#dce7f3}.fqx-ai-msg.user{background:rgba(246,193,90,.13);border:1px solid rgba(246,193,90,.2)}.fqx-wa-toggle{display:flex!important;align-items:center!important;justify-content:space-between!important;grid-template-columns:none!important;background:#101923;border:1px solid rgba(246,193,90,.14);border-radius:16px;padding:14px}.fqx-wa-toggle input{width:auto}.fqx-token-mask{font-family:monospace;color:#9fb0c1;font-size:12px}.fqx-route-error{display:none;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fecaca;padding:10px 12px;border-radius:12px;margin-top:10px}.fqx-route-error.show{display:block}@media(max-width:980px){.fqx-ai-grid,.fqx-wa-grid{grid-template-columns:1fr}.fqx-ai-prompts{grid-template-columns:1fr}.fqx-ai-titlebar,.fqx-wa-titlebar{align-items:flex-start;flex-direction:column}.fqx-ai-titlebar h1,.fqx-wa-titlebar h1{font-size:28px}}
    </style>
    <?php
}
add_action('wp_head', 'fqx_v182_admin_critical_css', 0);

