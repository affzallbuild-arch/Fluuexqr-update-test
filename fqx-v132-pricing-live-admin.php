<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v132: Starter plan visibility on homepage/pricing and live Restaurant Admin orders/bills refresh.
 */
function fqx_v132_ensure_pages_and_pricing(): void {
    $pages = [
        'pricing' => ['Pricing', 'page-pricing.php', ''],
        'blog' => ['Blog', 'page-blog.php', '[fqx_blog_posts]'],
    ];
    foreach ($pages as $slug => $data) {
        [$title, $template, $content] = $data;
        $page = get_page_by_path($slug);
        if ($page instanceof WP_Post) {
            if ($page->post_status !== 'publish' || get_page_template_slug($page->ID) !== $template) {
                wp_update_post(['ID' => $page->ID, 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug]);
                update_post_meta($page->ID, '_wp_page_template', $template);
            }
        } else {
            $id = wp_insert_post([
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => $slug,
                'post_content' => $content,
            ]);
            if ($id && !is_wp_error($id)) { update_post_meta((int) $id, '_wp_page_template', $template); }
        }
    }

    $blog = get_page_by_path('blog');
    if ($blog instanceof WP_Post && (int) get_option('page_for_posts') === (int) $blog->ID) {
        update_option('page_for_posts', 0, false);
    }
}
add_action('init', 'fqx_v132_ensure_pages_and_pricing', 1200);
add_action('after_switch_theme', 'fqx_v132_ensure_pages_and_pricing', 1200);

function fqx_v132_enqueue_assets(): void {
    if (is_admin()) { return; }
    if (is_front_page() || is_page(['home', 'pricing', 'restaurant-dashboard']) || is_page_template('page-dashboard.php')) {
        wp_enqueue_style('fqx-v132-pricing-live-admin', MENUQR_THEME_URI . '/assets/css/fqx-v132-pricing-live-admin.css', [], menuqr_asset_version('assets/css/fqx-v132-pricing-live-admin.css'));
    }
    if (is_page(['restaurant-dashboard', 'dashboard']) || is_page_template('page-dashboard.php')) {
        wp_enqueue_script('fqx-v132-live-admin', MENUQR_THEME_URI . '/assets/js/fqx-v132-live-admin.js', ['jquery'], menuqr_asset_version('assets/js/fqx-v132-live-admin.js'), true);
    }
}
add_action('wp_enqueue_scripts', 'fqx_v132_enqueue_assets', 1200);

function fqx_v132_plan_card_data(): array {
    return [
        ['slug'=>'free_trial','name'=>'Free Trial','price'=>'₹0','period'=>'/ 10 Days','desc'=>'Try complete FluuexQR before paid plan.','features'=>['Full access for 10 days','QR menu + table/room trial','Kitchen display + billing','No payment required'],'badge'=>''],
        ['slug'=>'starter_5_table','name'=>'Starter 5 Table','price'=>'₹999','period'=>'/ month','desc'=>'Best for small cafes, dhabas and starting restaurants.','features'=>['5 tables included','5 categories','20 menu items','2 staff users','Room QR not included'],'badge'=>'Small Restaurant'],
        ['slug'=>'restaurant_all_access','name'=>'Restaurant All Access','price'=>'₹1,999','period'=>'/ month','desc'=>'Unlimited restaurant/table QR ordering.','features'=>['Unlimited tables','Unlimited categories/items','Kitchen display','WhatsApp bill','UPI/Razorpay/Cash'],'badge'=>'Most Popular'],
        ['slug'=>'hotel_restaurant_full_access','name'=>'Hotel + Restaurant Full Access','price'=>'₹2,499','period'=>'/ month','desc'=>'Restaurant + hotel room QR system.','features'=>['Everything in restaurant plan','Room QR ordering','Room-wise bill','Room tracking in kitchen','Priority support'],'badge'=>'Hotel Plan'],
    ];
}

function fqx_v132_order_rows_html(int $restaurant_id, int $limit = 100): string {
    $orders = menuqr_get_restaurant_orders($restaurant_id, $limit);
    ob_start();
    if (!$orders) {
        echo '<tr><td colspan="8"><div class="empty-state"><span class="empty-icon">🧾</span><h4>No orders found</h4><p>New customer orders will appear here automatically without refresh.</p></div></td></tr>';
    } else {
        $labels = ['First Order','Second Order','Third Order','Fourth Order','Fifth Order'];
        $i = 0;
        foreach ($orders as $order) {
            if (function_exists('menuqr_normalize_order_service_point')) { $order = menuqr_normalize_order_service_point($order); }
            $items = json_decode((string) ($order->items_json ?? '[]'), true) ?: [];
            $label = $labels[$i] ?? ('Order #' . ($i + 1));
            $i++;
            ?>
            <tr data-order-id="<?php echo esc_attr((string) $order->id); ?>">
                <td><strong><?php echo esc_html($label); ?></strong><br><span class="tag tag-blue"><?php echo esc_html($order->unique_code ?? ('#'.$order->id)); ?></span><br><span class="text-muted"><?php echo esc_html(mysql2date('M j, g:i a', $order->created_at)); ?></span></td>
                <td><?php echo esc_html(($order->service_label ?? 'Table No') . ': ' . ($order->service_value ?? ($order->table_number ?: '—'))); ?></td>
                <td><?php foreach ($items as $item) : ?><div class="mq-order-mini-item"><?php if (!empty($item['image'])) : ?><img src="<?php echo esc_url($item['image']); ?>" alt=""><?php else : ?><span><?php echo esc_html((string) ($item['emoji'] ?? '🍽️')); ?></span><?php endif; ?><b><?php echo esc_html((string) ($item['qty'] ?? 1)); ?> × <?php echo esc_html((string) ($item['name'] ?? 'Item')); ?></b></div><?php endforeach; ?></td>
                <td><div>Subtotal: <?php echo esc_html(menuqr_money((float) ($order->subtotal ?? 0))); ?></div><div>Total: <strong><?php echo esc_html(menuqr_money((float) ($order->final_total ?? 0))); ?></strong></div></td>
                <td><div><?php echo esc_html(ucfirst((string) ($order->payment_method ?? 'cash'))); ?></div><span class="badge badge-<?php echo esc_attr($order->payment_status ?? 'unpaid'); ?>"><?php echo esc_html(ucfirst((string) ($order->payment_status ?? 'unpaid'))); ?></span><?php if (!empty($order->payment_reference)) : ?><div class="text-muted">Ref: <?php echo esc_html($order->payment_reference); ?></div><?php endif; ?></td>
                <td><span class="badge badge-<?php echo esc_attr($order->order_status ?? 'pending'); ?>"><?php echo esc_html(ucfirst((string) ($order->order_status ?? 'pending'))); ?></span></td>
                <td><?php echo esc_html($order->customer_note ?: '—'); ?></td>
                <td>
                    <form method="post" action="<?php echo esc_url(menuqr_restaurant_tab_url('orders')); ?>" class="inline-actions fqx-live-order-form">
                        <?php wp_nonce_field('menuqr_update_order_status_form', 'menuqr_order_nonce'); ?>
                        <input type="hidden" name="action" value="menuqr_update_order_status_form">
                        <input type="hidden" name="order_id" value="<?php echo esc_attr((string) $order->id); ?>">
                        <select class="form-select" name="status"><?php foreach (['pending','accepted','preparing','ready','served','cancelled'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($order->order_status, $status); ?>><?php echo esc_html(ucfirst($status)); ?></option><?php endforeach; ?></select>
                        <button class="btn btn-primary btn-sm" type="submit">Update</button>
                    </form>
                </td>
            </tr>
            <?php
        }
    }
    return (string) ob_get_clean();
}

function fqx_v132_bill_rows_html(int $restaurant_id, int $limit = 100): string {
    $bills = menuqr_get_restaurant_bills($restaurant_id, $limit);
    ob_start();
    if (!$bills) {
        echo '<tr><td colspan="6"><div class="empty-state"><span class="empty-icon">🧾</span><h4>No bills yet</h4><p>Bills will appear automatically after customer orders.</p></div></td></tr>';
    } else {
        foreach ($bills as $bill) {
            if (function_exists('fqx_v128_sync_bill_payment_state')) { $bill = fqx_v128_sync_bill_payment_state($bill); }
            $items = json_decode((string) ($bill->items_snapshot ?? '[]'), true) ?: [];
            $wa_url = function_exists('menuqr_bill_whatsapp_url') ? menuqr_bill_whatsapp_url($bill) : '';
            $bill_url = function_exists('menuqr_bill_access_url') ? menuqr_bill_access_url($bill) : home_url('/bill/?bill_id=' . (int) $bill->id);
            ?>
            <tr data-bill-id="<?php echo esc_attr((string) $bill->id); ?>">
                <td><strong><?php echo esc_html($bill->bill_number); ?></strong><br><span class="text-muted">Table/Room #<?php echo esc_html((string) ($bill->table_id ?: '—')); ?> · <?php echo esc_html(mysql2date('d M, h:i A', $bill->created_at)); ?></span></td>
                <td><?php echo esc_html($bill->customer_name ?: 'Walk-in customer'); ?><br><span class="text-muted"><?php echo esc_html($bill->customer_whatsapp ?: 'No WhatsApp'); ?></span></td>
                <td><strong><?php echo esc_html(menuqr_money((float) ($bill->grand_total ?? 0))); ?></strong><br><span class="text-muted"><?php echo esc_html((string) count($items)); ?> item groups</span></td>
                <td><span class="badge badge-<?php echo esc_attr(($bill->payment_status ?? '') === 'paid' ? 'paid' : 'unpaid'); ?>"><?php echo esc_html(strtoupper((string) ($bill->payment_status ?: 'unpaid'))); ?></span><br><span class="text-muted"><?php echo esc_html(strtoupper((string) ($bill->payment_method ?: 'cash'))); ?></span></td>
                <td><span class="badge badge-<?php echo esc_attr(($bill->bill_status ?? '') === 'generated' ? 'served' : 'pending'); ?>"><?php echo esc_html(strtoupper((string) ($bill->bill_status ?: 'running'))); ?></span><br><span class="text-muted">Expires: <?php echo esc_html(mysql2date('h:i A', $bill->expires_at ?: $bill->updated_at)); ?></span></td>
                <td><div class="inline-actions" style="flex-wrap:wrap;"><a class="btn btn-outline btn-sm" href="<?php echo esc_url($bill_url); ?>" target="_blank">View</a><button class="btn btn-primary btn-sm" type="button" onclick="window.open('<?php echo esc_js(add_query_arg('print', '1', $bill_url)); ?>','bill','width=420,height=720');">Print</button><?php if ($wa_url) : ?><a class="btn btn-success btn-sm" href="<?php echo esc_url($wa_url); ?>" target="_blank">WhatsApp Bill</a><?php endif; ?><form method="post" style="display:inline;"><?php wp_nonce_field('menuqr_bill_action', 'menuqr_bill_nonce'); ?><input type="hidden" name="action" value="menuqr_mark_bill_payment"><input type="hidden" name="bill_id" value="<?php echo esc_attr((string) $bill->id); ?>"><input type="hidden" name="payment_status" value="<?php echo esc_attr(($bill->payment_status ?? '') === 'paid' ? 'unpaid' : 'paid'); ?>"><button class="btn btn-teal btn-sm" type="submit"><?php echo esc_html(($bill->payment_status ?? '') === 'paid' ? 'Mark Unpaid' : 'Mark Paid'); ?></button></form></div></td>
            </tr>
            <?php
        }
    }
    return (string) ob_get_clean();
}

function fqx_v132_restaurant_live_snapshot(): void {
    check_ajax_referer('menuqr_nonce', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'Login required'], 403); }
    $restaurant_id = menuqr_get_current_restaurant_id();
    if (!$restaurant_id) { wp_send_json_error(['message' => 'Restaurant not found'], 404); }
    $orders = menuqr_get_restaurant_orders($restaurant_id, 100);
    $bills = menuqr_get_restaurant_bills($restaurant_id, 100);
    $today_orders = 0; $today_revenue = 0.0; $pending_orders = 0; $revenue = 0.0;
    $today = current_time('Y-m-d');
    foreach ($orders as $o) {
        $revenue += (float) ($o->final_total ?? 0);
        if ((string) ($o->order_status ?? '') === 'pending') { $pending_orders++; }
        if (substr((string) ($o->created_at ?? ''), 0, 10) === $today) { $today_orders++; $today_revenue += (float) ($o->final_total ?? 0); }
    }
    wp_send_json_success([
        'orders_html' => fqx_v132_order_rows_html($restaurant_id, 100),
        'bills_html' => fqx_v132_bill_rows_html($restaurant_id, 100),
        'counts' => [
            'orders' => count($orders),
            'today_orders' => $today_orders,
            'pending_orders' => $pending_orders,
            'revenue' => menuqr_money($revenue),
            'today_revenue' => menuqr_money($today_revenue),
            'bills' => count($bills),
        ],
        'server_time' => current_time('mysql'),
    ]);
}
add_action('wp_ajax_fqx_v132_restaurant_live_snapshot', 'fqx_v132_restaurant_live_snapshot');
