<?php
if (!defined('ABSPATH')) { exit; }

if (function_exists('nocache_headers')) { nocache_headers(); }

$bill_id = absint($_GET['bill'] ?? ($_GET['bill_id'] ?? 0));
$order_id = absint($_GET['order_id'] ?? ($_GET['order'] ?? 0));
$key = sanitize_text_field(wp_unslash($_GET['key'] ?? ''));
$session_token = menuqr_sanitize_session_token(sanitize_text_field(wp_unslash($_GET['session'] ?? ($_GET['token'] ?? ''))));
$restaurant_id = absint($_GET['r'] ?? 0);
$table_id = absint($_GET['t'] ?? ($_GET['table_id'] ?? 0));
$room_id = absint($_GET['room_id'] ?? ($_GET['room'] ?? 0));
$order_source = sanitize_key(wp_unslash($_GET['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr')));
$auto_print = '1' === sanitize_text_field(wp_unslash($_GET['print'] ?? ''));
$download_pdf = '1' === sanitize_text_field(wp_unslash($_GET['download_pdf'] ?? ''));
$bill = null;

if ($bill_id && $key) {
    $bill = menuqr_get_bill_by_public_access($bill_id, $key);
}
if (!$bill && $restaurant_id && ($table_id || $room_id) && $session_token) {
    $bill = menuqr_get_bill_by_session_public_access($restaurant_id, $table_id, $session_token, $room_id, $order_source);
}
if (!$bill && $bill_id) {
    $maybe_bill = menuqr_get_bill_by_id($bill_id);
    if ($maybe_bill && is_user_logged_in() && (current_user_can('manage_options') || menuqr_get_current_restaurant_id() === (int) $maybe_bill->restaurant_id)) {
        $bill = menuqr_repair_bill_access_key($maybe_bill);
    }
}
if (!$bill && $order_id && function_exists('menuqr_v123_force_bill_for_order')) {
    $bill = menuqr_v123_force_bill_for_order($order_id, $session_token);
}
if (!$bill) {
    echo '<section class="fq-v123-bill-help"><h2>Bill not ready yet</h2><p>Please place an order first, then tap the Bill icon again. If you already ordered, refresh the menu page once and open the Bill icon.</p></section>';
    return;
}

// v124: Recalculate totals, but paid status is preserved by menuqr_recalculate_bill().
$bill = menuqr_recalculate_bill((int) $bill->bill_session_id) ?: $bill;
$bill = menuqr_repair_bill_access_key($bill);
if (function_exists('fqx_v128_sync_bill_payment_state')) { $bill = fqx_v128_sync_bill_payment_state($bill); }
if (function_exists('fqx_v129_sync_customer_bill_payment_state')) { $bill = fqx_v129_sync_customer_bill_payment_state($bill); }
if (function_exists('fqx_v130_sync_customer_bill_payment_state')) { $bill = fqx_v130_sync_customer_bill_payment_state($bill); }
if (function_exists('fqx_v178_force_customer_paid_status_sync')) { $bill = fqx_v178_force_customer_paid_status_sync($bill); }
if (function_exists('menuqr_v124_sync_paid_bill_on_view')) { $bill = menuqr_v124_sync_paid_bill_on_view($bill); }
$bill = menuqr_get_bill_by_id((int) $bill->id) ?: $bill;
$restaurant = json_decode((string) $bill->restaurant_snapshot, true) ?: [];
$settings = !empty($restaurant['settings']) && is_array($restaurant['settings']) ? $restaurant['settings'] : menuqr_get_restaurant_bill_settings((int) $bill->restaurant_id);
$orders = menuqr_get_session_orders((int) $bill->bill_session_id);
foreach ($orders as $order) { $order->items = json_decode((string) $order->items_json, true) ?: []; }
$source_context = menuqr_get_bill_source_context($bill);
$currency_symbol = $restaurant['currency_symbol'] ?? ($settings['currency_symbol'] ?? '₹');
$bill_link = menuqr_bill_access_url($bill);
$pdf_url = menuqr_bill_download_pdf_url($bill);
$wa_url = menuqr_bill_whatsapp_url($bill);
$thank_you = $settings['thank_you_text'] ?: ($bill->thank_you_message ?: 'Thank you for your order!');
$footer_text = trim((string) ($settings['footer_text'] ?? ''));
$show_powered_by = !empty($settings['show_powered_by']);
$show_customer_phone = !empty($settings['show_customer_phone']);
$show_staff_name = !empty($settings['show_staff_name']);
$show_date_time = !empty($settings['show_date_time']);
$show_table_room_number = !empty($settings['show_table_room_number']);
$show_tax_breakdown = !empty($settings['show_tax_breakdown']);
$show_gst_number = !empty($settings['show_gst_number']);
$show_thank_you_note = !empty($settings['show_thank_you_note']);
$show_qr_barcode = !empty($settings['show_qr_barcode']);
$show_order_type = !empty($settings['show_order_type']);
$show_restaurant_logo = !empty($settings['show_restaurant_logo']);
if ($auto_print) {
    $show_customer_phone = true; $show_staff_name = true; $show_date_time = true; $show_table_room_number = true;
    $show_tax_breakdown = true; $show_gst_number = true; $show_thank_you_note = true; $show_qr_barcode = true; $show_order_type = true; $show_restaurant_logo = true; $show_service_charge_on_bill = true;
}
// v178: Customer must always see PAID after Restaurant Admin marks the bill paid.
$show_payment_status = !empty($settings['show_payment_status']) || strtolower((string) ($bill->payment_status ?? '')) === 'paid';
if ($auto_print) { $show_payment_status = true; }
$show_service_charge_on_bill = !empty($settings['show_service_charge_on_bill']);
$tax_label = $settings['tax_label'] ?: ($restaurant['tax_label'] ?? 'GST');
$cgst = round(((float) $bill->tax) / 2, 2);
$sgst = round(((float) $bill->tax) / 2, 2);
$order_type = (string) ($source_context['order_type'] ?? 'Dine In');
$service_number_label = (string) ($source_context['label'] ?? 'Table No');
$service_number = (string) ($source_context['number'] ?? '');
$logo = (!empty($settings['show_bill_header_logo']) && !empty($settings['bill_header_logo'])) ? $settings['bill_header_logo'] : ($restaurant['logo'] ?? ($settings['restaurant_logo'] ?? menuqr_get_brand_logo_url()));
if ($download_pdf || $auto_print) {
    add_filter('show_admin_bar', '__return_false');
}
?>
<section class="fq-thermal-shell">
  <div class="fq-thermal-actions no-print">
    <button class="fq-pos-btn primary" type="button" onclick="window.print()">Print Invoice</button>
    <button class="fq-pos-btn" type="button" id="fqDownloadPdfBtn" data-title="<?php echo esc_attr('Invoice-' . $bill->bill_number); ?>">Download PDF</button>
    <?php if ($wa_url) : ?><a class="fq-pos-btn success" target="_blank" rel="noopener" href="<?php echo esc_url($wa_url); ?>">WhatsApp</a><?php endif; ?>
    <button class="fq-pos-btn" type="button" data-copy="<?php echo esc_url($bill_link); ?>">Copy Link</button>
  </div>

  <article class="fq-thermal-receipt" id="menuqr-print-bill">
    <div class="fq-pos-side-brand left">FluuexQR</div><div class="fq-pos-side-brand right">FluuexQR</div>
    <header class="fq-pos-header">
      <div class="fq-pos-title">TAX INVOICE</div>
      <?php if ($show_restaurant_logo && $logo) : ?><img class="fq-pos-logo" src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($restaurant['name'] ?? 'Restaurant'); ?>"><?php endif; ?>
      <h1><?php echo esc_html($restaurant['name'] ?? get_bloginfo('name')); ?></h1>
      <?php if (!empty($restaurant['address'])) : ?><p><?php echo esc_html($restaurant['address']); ?></p><?php endif; ?>
      <?php if (!empty($restaurant['phone'])) : ?><p>Phone: <?php echo esc_html($restaurant['phone']); ?></p><?php endif; ?>
      <?php if ($show_gst_number && !empty($restaurant['gst_number'])) : ?><p>GSTIN: <?php echo esc_html($restaurant['gst_number']); ?></p><?php endif; ?>
      <?php if (!empty($restaurant['fssai_number'])) : ?><p>FSSAI No: <?php echo esc_html($restaurant['fssai_number']); ?></p><?php endif; ?>
    </header>

    <div class="fq-pos-dash"></div>
    <section class="fq-pos-meta">
      <div><strong>Invoice Number:</strong><span><?php echo esc_html($bill->bill_number); ?></span></div>
      <div><strong>Order ID:</strong><span>#<?php echo esc_html((string) $bill->id); ?></span></div>
      <?php if ($show_date_time) : ?><div><strong>Date & Time:</strong><span><?php echo esc_html(wp_date('d/m/Y h:i A', current_time('timestamp'))); ?></span></div><?php endif; ?>
      <?php if ($show_table_room_number) : ?><div><strong><?php echo esc_html($service_number_label); ?>:</strong><span><?php echo esc_html($service_number ?: '-'); ?></span></div><?php endif; ?>
      <?php if ($show_order_type) : ?><div><strong>Order Type:</strong><span><?php echo esc_html($order_type); ?></span></div><?php endif; ?>
      <?php if ($show_payment_status) : ?><div><strong>Payment Status:</strong><span><?php echo esc_html(strtoupper((string) $bill->payment_status)); ?></span></div><?php endif; ?>
      <?php if ($show_staff_name && !empty($bill->staff_name)) : ?><div><strong>Server:</strong><span><?php echo esc_html($bill->staff_name); ?></span></div><?php endif; ?>
    </section>

    <div class="fq-pos-dash"></div>
    <section class="fq-pos-customer">
      <div class="center strong"><?php echo esc_html($order_type); ?></div>
      <div class="center"><?php echo esc_html($bill->customer_name ?: 'Guest Customer'); ?></div>
      <?php if ($show_customer_phone && !empty($bill->customer_whatsapp)) : ?><div class="center"><?php echo esc_html($bill->customer_whatsapp); ?></div><?php endif; ?>
    </section>

    <div class="fq-pos-dash"></div>
    <?php if (!$orders) : ?>
      <p class="center">No order items found.</p>
    <?php else : ?>
      <?php foreach ($orders as $index => $order) : ?>
        <section class="fq-pos-order-block">
          <div class="fq-pos-order-head">
            <strong>Order <?php echo esc_html((string) ($index + 1)); ?></strong>
            <span><?php echo esc_html(wp_date('d/m/Y h:i A', strtotime(get_gmt_from_date($order->created_at)) ?: current_time('timestamp'))); ?></span>
          </div>
          <div class="fq-pos-items">
            <?php foreach ($order->items as $item) :
              $qty = max(1, (int) ($item['qty'] ?? 1));
              $price = (float) ($item['price'] ?? 0);
              $total = $price * $qty;
              if (isset($item['total'])) { $total = (float) $item['total']; }
            ?>
              <div class="fq-pos-item-row">
                <div class="name"><strong><?php echo esc_html($qty . ' x ' . ($item['name'] ?? 'Item')); ?></strong>
                  <?php if (!empty($item['variant'])) : ?><small><?php echo esc_html((string) $item['variant']); ?></small><?php endif; ?>
                  <?php if (!empty($item['addons']) && is_array($item['addons'])) : ?><small>Add-ons: <?php echo esc_html(implode(', ', array_map('sanitize_text_field', $item['addons']))); ?></small><?php endif; ?>
                  <?php if (!empty($item['combo_items']) && is_array($item['combo_items'])) : ?><small>Combo: <?php echo esc_html(implode(', ', array_map('sanitize_text_field', $item['combo_items']))); ?></small><?php endif; ?>
                </div>
                <div class="price"><?php echo esc_html(menuqr_format_amount($total, $currency_symbol)); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="fq-pos-mini-total"><span>Order Total</span><strong><?php echo esc_html(menuqr_format_amount((float) $order->final_total, $currency_symbol)); ?></strong></div>
        </section>
        <?php if ($index < count($orders) - 1) : ?><div class="fq-pos-dotline"></div><?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>

    <div class="fq-pos-dash"></div>
    <section class="fq-pos-totals">
      <div><span>Subtotal</span><strong><?php echo esc_html(menuqr_format_amount((float) $bill->subtotal, $currency_symbol)); ?></strong></div>
      <?php if ((float) $bill->discount > 0) : ?><div><span>Discount</span><strong>-<?php echo esc_html(menuqr_format_amount((float) $bill->discount, $currency_symbol)); ?></strong></div><?php endif; ?>
      <?php if ((float) $bill->tax > 0 && $show_tax_breakdown) : ?>
        <div><span>CGST</span><strong><?php echo esc_html(menuqr_format_amount($cgst, $currency_symbol)); ?></strong></div>
        <div><span>SGST/UTGST</span><strong><?php echo esc_html(menuqr_format_amount($sgst, $currency_symbol)); ?></strong></div>
      <?php else : ?>
        <div><span><?php echo esc_html($tax_label); ?></span><strong><?php echo esc_html(menuqr_format_amount((float) $bill->tax, $currency_symbol)); ?></strong></div>
      <?php endif; ?>
      <?php if ((float) $bill->service_charge > 0 && $show_service_charge_on_bill) : ?><div><span>Service Charge</span><strong><?php echo esc_html(menuqr_format_amount((float) $bill->service_charge, $currency_symbol)); ?></strong></div><?php endif; ?>
      <?php if ((float) $bill->delivery_charge > 0) : ?><div><span>Delivery Charge</span><strong><?php echo esc_html(menuqr_format_amount((float) $bill->delivery_charge, $currency_symbol)); ?></strong></div><?php endif; ?>
      <?php if ((float) $bill->round_off != 0) : ?><div><span>Round Off</span><strong><?php echo esc_html(menuqr_format_amount((float) $bill->round_off, $currency_symbol)); ?></strong></div><?php endif; ?>
      <div class="grand"><span>Total</span><strong><?php echo esc_html(menuqr_format_amount((float) $bill->grand_total, $currency_symbol)); ?></strong></div>
      <div class="due"><span>Due</span><strong><?php echo esc_html(menuqr_format_amount(function_exists('menuqr_bill_due_amount') ? menuqr_bill_due_amount($bill) : (strtolower((string) $bill->payment_status) === 'paid' ? 0 : (float) $bill->grand_total), $currency_symbol)); ?></strong></div>
    </section>

    <div class="fq-pos-dash"></div>
    <footer class="fq-pos-footer">
      <?php if ($show_thank_you_note) : ?><p class="strong"><?php echo esc_html($thank_you); ?></p><?php endif; ?>
      <?php if (!empty($restaurant['email'])) : ?><p><?php echo esc_html($restaurant['email']); ?></p><?php endif; ?>
      <?php if ($footer_text) : ?><p><?php echo esc_html($footer_text); ?></p><?php endif; ?>
      <?php if ($show_qr_barcode) : ?><div class="fq-pos-qr"><?php echo menuqr_qr_svg($bill_link, 92); ?></div><?php endif; ?>
      <p>Please keep this invoice for billing reference.</p>
      <?php if ($show_powered_by) : ?><p class="powered">Powered by FluuexQR</p><?php endif; ?>
    </footer>
  </article>

  <?php if ($auto_print || $download_pdf) : ?>
    <script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 450); });</script>
  <?php endif; ?>
</section>
