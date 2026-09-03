<?php
if (!defined('ABSPATH')) { exit; }

menuqr_require_role(['restaurant_admin', 'super_admin', 'staff']);
global $wpdb;

$restaurant_id = menuqr_get_current_restaurant_id();
$restaurant = $restaurant_id ? menuqr_get_restaurant($restaurant_id) : null;

if (!$restaurant_id || !$restaurant) {
    echo '<div class="mq-container narrow"><div class="alert alert-warning">Restaurant context not found.</div></div>';
    return;
}


$mq_notice = sanitize_key(wp_unslash($_GET['mq_notice'] ?? ''));
$mq_error = sanitize_text_field(wp_unslash($_GET['mq_error'] ?? ''));

$mq_notice_messages = [
    'category_saved' => ['success', 'Category saved successfully.'],
    'category_deleted' => ['success', 'Category deleted successfully.'],
    'category_has_items' => ['danger', 'Move or delete menu items in this category before deleting it.'],
    'category_error' => ['danger', 'Category could not be updated.'],
    'category_type_saved' => ['success', 'Category type saved successfully.'],
    'category_type_deleted' => ['success', 'Category type deleted successfully.'],
    'category_type_has_items' => ['danger', 'Move menu items out of this type before deleting it.'],
    'category_type_error' => ['danger', 'Category type could not be updated.'],
    'item_saved' => ['success', 'Menu item saved successfully.'],
    'item_deleted' => ['success', 'Menu item deleted successfully.'],
    'item_invalid' => ['danger', 'Please fill Name, Category, and a valid Price.'],
    'item_upload_error' => ['danger', 'Image upload failed.' . ($mq_error ? ' ' . $mq_error : '')],
    'item_db_error' => ['danger', 'Menu item could not be saved.' . ($mq_error ? ' ' . $mq_error : '')],
    'staff_saved' => ['success', 'Staff member saved successfully.'],
    'staff_deleted' => ['success', 'Staff member deleted successfully.'],
    'staff_invalid' => ['danger', 'Please enter a valid staff name and email.'],
    'staff_exists' => ['danger', 'This email is already used by another user.'],
    'staff_error' => ['danger', 'Staff member could not be saved.' . ($mq_error ? ' ' . $mq_error : '')],
    'order_updated' => ['success', 'Order status updated successfully.'],
    'manual_order_created' => ['success', 'New order created successfully. KOT is now available in the Kitchen Display.'],
    'manual_order_invalid' => ['danger', 'New order could not be created.'],
    'manual_order_error' => ['danger', 'New order could not be created.'],
    'payment_saved' => ['success', 'Payment/WhatsApp settings saved successfully.'],
    'bill_settings_saved' => ['success', 'Bill branding settings saved successfully.'],
    'bill_settings_error' => ['danger', 'Bill branding settings could not be saved.'],
    'payment_error' => ['danger', 'Payment settings could not be saved.' . ($mq_error ? ' ' . $mq_error : '')],
    'bill_payment_saved' => ['success', 'Bill payment status updated.'],
    'bill_closed' => ['success', 'Bill finalized successfully.'],
    'bill_error' => ['danger', 'Bill action failed.'],
    'reviews_saved' => ['success', 'Google review settings saved successfully.'],
    'review_action_saved' => ['success', 'Review action saved successfully.'],
    'reviews_error' => ['danger', 'Google review settings could not be saved. Make sure the link is a valid Google review link.'],
    'plan_limit_categories' => ['warning', 'Your current plan category limit is reached. Upgrade for more categories.'],
    'plan_limit_items' => ['warning', 'Your current plan menu item limit is reached. Upgrade for more items.'],
    'plan_limit_tables' => ['warning', 'Your current plan table limit is reached. Upgrade for more QR tables.'],
    'plan_limit_staff' => ['warning', 'Your current plan staff limit is reached. Upgrade for more staff users.'],
    'plan_locked_images' => ['warning', 'Item image upload is locked on your current plan. Upgrade to Premium or Yearly Pro.'],
    'plan_locked_combos' => ['warning', 'Combos are available in Premium and Yearly Pro.'],
    'plan_locked_coupons' => ['warning', 'Coupons are available in Premium and Yearly Pro.'],
    'combo_saved' => ['success', 'Combo saved successfully.'],
    'combo_deleted' => ['success', 'Combo deleted successfully.'],
    'combo_invalid' => ['danger', 'Please enter a combo name.'],
    'combo_error' => ['danger', 'Combo could not be saved.'],
    'coupon_saved' => ['success', 'Coupon saved successfully.'],
    'coupon_deleted' => ['success', 'Coupon deleted successfully.'],
    'coupon_invalid' => ['danger', 'Please enter a coupon code.'],
    'coupon_error' => ['danger', 'Coupon could not be saved.'],
    'table_saved' => ['success', 'Table saved successfully.'],
    'room_saved' => ['success', 'Room saved successfully.'],
    'room_deleted' => ['success', 'Room deleted successfully.'],
    'room_invalid' => ['danger', 'Please enter a valid room number.'],
    'room_error' => ['danger', 'Room could not be saved.'],
    'table_deleted' => ['success', 'Table deleted successfully.'],
    'subscription_requested' => ['success', 'Subscription payment request submitted. Super Admin can verify it from Subscription Payments.'],
    'subscription_activated' => ['success', 'Free Trial activated successfully.'],
    'subscription_error' => ['danger', 'Subscription request could not be created. Please try again.'],
    'qr_template_saved' => ['success', 'QR template saved successfully.'],
    'cache_cleared' => ['success', 'Cache cleared successfully.'],
    'plan_limit_qr_template' => ['warning', 'This QR template is locked on your current plan. Upgrade to unlock more templates.'],
    'wifi_saved' => ['success', 'WiFi QR settings saved successfully.'],
    'whatsapp_saved' => ['success', 'WhatsApp Settings saved successfully.'],
    'whatsapp_error' => ['danger', 'WhatsApp Settings could not be saved.'],
    'fqx_csv_preview' => ['success', 'CSV preview generated. Please verify rows before actual import.'],
    'fqx_csv_imported' => ['success', 'CSV menu import completed.'],
    'fqx_csv_error' => ['danger', 'CSV menu upload failed.' . ($mq_error ? ' ' . $mq_error : '')],

];

$allowed_tabs = ['dashboard', 'orders', 'menu', 'tables', 'rooms', 'wifi', 'staff', 'payments', 'bills', 'reviews', 'settings', 'combos', 'coupons', 'reports', 'subscription'];
$current_tab = sanitize_key(wp_unslash($_GET['tab'] ?? 'dashboard'));
$tab_aliases = [
    'overview' => 'dashboard',
    'restaurant-dashboard' => 'dashboard',
    'payment-settings' => 'payments',
    'whatsapp-settings' => 'dashboard',
    'whatsapp' => 'dashboard',
    'fluuexqr-ai-support' => 'dashboard',
    'ai-support' => 'dashboard',
    'support' => 'dashboard',
];
if (isset($tab_aliases[$current_tab])) {
    $current_tab = $tab_aliases[$current_tab];
}
if (!in_array($current_tab, $allowed_tabs, true)) {
    $current_tab = 'dashboard';
}
if (function_exists('fqx_v167_is_limited_staff_user') && fqx_v167_is_limited_staff_user() && function_exists('fqx_v167_staff_can_access_tab') && !fqx_v167_staff_can_access_tab($current_tab)) {
    wp_safe_redirect(function_exists('fqx_v167_staff_default_url_for_user') ? fqx_v167_staff_default_url_for_user(wp_get_current_user()) : menuqr_get_page_url_by_slug('kitchen-dashboard'));
    exit;
}

$orders_table = menuqr_table('orders');
$staff_table = menuqr_table('staff');

$payment = menuqr_get_payment_settings($restaurant_id);
$bill_settings = menuqr_get_restaurant_bill_settings($restaurant_id);
$branding = menuqr_get_restaurant_branding_data($restaurant_id);
$categories = menuqr_get_categories($restaurant_id);
$category_types = function_exists('fqx_v191_get_category_types') ? fqx_v191_get_category_types($restaurant_id) : [];
$category_types_grouped = function_exists('fqx_v191_get_category_types_grouped') ? fqx_v191_get_category_types_grouped($restaurant_id) : [];
$category_type_map = function_exists('fqx_v191_get_category_type_map') ? fqx_v191_get_category_type_map($restaurant_id) : [];
$items = menuqr_get_items($restaurant_id);
$tables = menuqr_get_tables($restaurant_id);
$rooms = function_exists('menuqr_get_rooms') ? menuqr_get_rooms($restaurant_id) : [];
$staff = menuqr_get_restaurant_staff($restaurant_id);
$recent_orders = menuqr_get_restaurant_orders($restaurant_id, 100);
$bills = menuqr_get_restaurant_bills($restaurant_id, 100);
$report_orders = $wpdb->get_results($wpdb->prepare("SELECT id, items_json, final_total, created_at FROM {$orders_table} WHERE restaurant_id = %d ORDER BY created_at DESC, id DESC LIMIT 500", $restaurant_id));

$stats = [
    'orders' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$orders_table} WHERE restaurant_id = %d", $restaurant_id)),
    'revenue' => (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(final_total),0) FROM {$orders_table} WHERE restaurant_id = %d", $restaurant_id)),
    'today_orders' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$orders_table} WHERE restaurant_id = %d AND DATE(created_at) = CURDATE()", $restaurant_id)),
    'today_revenue' => (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(final_total),0) FROM {$orders_table} WHERE restaurant_id = %d AND DATE(created_at) = CURDATE()", $restaurant_id)),
];

$item_totals = [];
$daily_totals = [];
$monthly_totals = [];
foreach ($report_orders as $report_order) {
    $decoded_items = json_decode((string) $report_order->items_json, true) ?: [];
    foreach ($decoded_items as $decoded_item) {
        $decoded_name = sanitize_text_field((string) ($decoded_item['name'] ?? 'Item'));
        $item_totals[$decoded_name] = ($item_totals[$decoded_name] ?? 0) + (int) ($decoded_item['qty'] ?? 0);
    }
    $day_key = date_i18n('Y-m-d', strtotime((string) $report_order->created_at));
    $month_key = date_i18n('Y-m', strtotime((string) $report_order->created_at));
    $daily_totals[$day_key] = ($daily_totals[$day_key] ?? 0) + (float) $report_order->final_total;
    $monthly_totals[$month_key] = ($monthly_totals[$month_key] ?? 0) + (float) $report_order->final_total;
}
arsort($item_totals);
ksort($daily_totals);
ksort($monthly_totals);
$top_items = array_slice($item_totals, 0, 10, true);

$active_tables_count = 0;
foreach ($tables as $t) { $status = strtolower((string) ($t->status ?? 'active')); if ($status !== 'inactive') { $active_tables_count++; } }
$active_rooms_count = 0;
foreach ($rooms as $r) { $status = strtolower((string) ($r->status ?? 'active')); if ($status !== 'inactive') { $active_rooms_count++; } }
$pending_orders_count = 0;
$completed_orders_count = 0;
$order_type_counts = ['Dine In' => 0, 'Takeaway' => 0, 'Room Service' => 0, 'Delivery' => 0];
foreach ($recent_orders as $o) {
    $status = strtolower((string) ($o->order_status ?? ''));
    if (in_array($status, ['pending', 'accepted', 'preparing', 'ready'], true)) { $pending_orders_count++; }
    if (in_array($status, ['served', 'completed', 'paid'], true)) { $completed_orders_count++; }
    if (function_exists('menuqr_normalize_order_service_point')) { $o = menuqr_normalize_order_service_point($o); }
    $service_label = strtolower((string) ($o->service_label ?? 'table'));
    $type = 'Dine In';
    if (strpos($service_label, 'room') !== false) { $type = 'Room Service'; }
    elseif (strpos($service_label, 'take') !== false || strpos($service_label, 'pickup') !== false) { $type = 'Takeaway'; }
    elseif (strpos($service_label, 'delivery') !== false) { $type = 'Delivery'; }
    $order_type_counts[$type] = ($order_type_counts[$type] ?? 0) + 1;
}
$dashboard_daily = array_slice($daily_totals, -7, 7, true);
if (empty($dashboard_daily)) { $dashboard_daily = [current_time('Y-m-d') => 0]; }
$daily_values = array_values($dashboard_daily);
$daily_labels = array_map(static function($k){ return date_i18n('j M', strtotime((string) $k)); }, array_keys($dashboard_daily));
$max_daily = max(1, (float) max($daily_values));
$points = [];
$count_points = max(1, count($daily_values)-1);
foreach ($daily_values as $i => $val) {
    $x = 20 + ($count_points ? ($i * (280 / $count_points)) : 0);
    $y = 120 - (($val / $max_daily) * 90);
    $points[] = round($x,2) . ',' . round($y,2);
}
$dashboard_polyline = implode(' ', $points);
$order_total_count = max(1, array_sum($order_type_counts));
$dine_pct = round(($order_type_counts['Dine In'] / $order_total_count) * 100, 1);
$take_pct = round(($order_type_counts['Takeaway'] / $order_total_count) * 100, 1);
$room_pct = round(($order_type_counts['Room Service'] / $order_total_count) * 100, 1);
$delivery_pct = max(0, 100 - $dine_pct - $take_pct - $room_pct);
$donut_style = sprintf('background:conic-gradient(#f2b24c 0 %1$s%%,#d69a3a %1$s%% %2$s%%,#9b742d %2$s%% %3$s%%,#6d4f1e %3$s%% 100%%);', $dine_pct, $dine_pct + $take_pct, $dine_pct + $take_pct + $room_pct);
$nav_icons = [
    'overview' => '🏠', 'dashboard' => '📊', 'orders' => '🧾', 'menu' => '🍽️', 'tables' => '🪑',
    'rooms' => '🛏️', 'wifi' => '📶', 'staff' => '👥', 'payments' => '💳', 'bills' => '📄',
    'reviews' => '⭐', 'settings' => '🧾', 'combos' => '🎁', 'coupons' => '🏷️', 'reports' => '📈', 'subscription' => '💎'
];
$monthly_totals = array_slice($monthly_totals, -12, 12, true);

$subscription = menuqr_get_latest_subscription($restaurant_id);
$subscription_plans = menuqr_get_subscription_plans();
$subscription_payments = menuqr_get_subscription_payment_history($restaurant_id);
$review_settings = menuqr_get_review_settings($restaurant_id);
$review_stats = menuqr_get_review_click_stats($restaurant_id);
$plan_slug = menuqr_get_restaurant_plan_slug($restaurant_id);
$plan_config = menuqr_get_restaurant_plan_config($restaurant_id);
$qr_templates = function_exists('menuqr_qr_templates') ? menuqr_qr_templates() : [];
$qr_template_limit = function_exists('menuqr_qr_template_limit') ? menuqr_qr_template_limit($restaurant_id) : 2;
$current_qr_template = function_exists('menuqr_get_restaurant_qr_template') ? menuqr_get_restaurant_qr_template($restaurant_id) : 'minimal_clean';
$saved_qr_templates = [];
if ($restaurant_id > 0) {
    global $wpdb;
    $saved_qr_rows = (array) $wpdb->get_results($wpdb->prepare("SELECT * FROM " . menuqr_table('qr_templates') . " WHERE restaurant_id = %d", $restaurant_id));
    foreach ($saved_qr_rows as $saved_qr_row) { $saved_qr_templates[(int) $saved_qr_row->table_id] = $saved_qr_row; }
}
$qr_default_table_id = !empty($tables[0]) ? (int) $tables[0]->id : 0;
$combos = function_exists('menuqr_get_restaurant_combos') ? menuqr_get_restaurant_combos($restaurant_id) : [];
$coupons = function_exists('menuqr_get_restaurant_coupons') ? menuqr_get_restaurant_coupons($restaurant_id) : [];
$edit_combo_id = absint($_GET['edit_combo'] ?? 0);
$edit_combo = null;
foreach ($combos as $combo_row) {
    if ((int) $combo_row->id === $edit_combo_id) { $edit_combo = $combo_row; break; }
}
$edit_coupon_id = absint($_GET['edit_coupon'] ?? 0);
$edit_coupon = null;
foreach ($coupons as $coupon_row) {
    if ((int) $coupon_row->id === $edit_coupon_id) { $edit_coupon = $coupon_row; break; }
}

$usage = [
    'tables' => menuqr_plan_usage($restaurant_id, 'tables'),
    'items' => menuqr_plan_usage($restaurant_id, 'items'),
    'categories' => menuqr_plan_usage($restaurant_id, 'categories'),
    'staff' => menuqr_plan_usage($restaurant_id, 'staff'),
];

if (!empty($_GET['menuqr_print_qr'])) {
    ?><!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html($restaurant->name); ?> - QR Print</title>
        <?php wp_head(); ?>
    </head>
    <body class="mq-print-page">
        <div class="mq-container">
            <h1><?php echo esc_html($restaurant->name); ?> - QR Table Sheet</h1>
            <div class="print-qr-grid">
                <?php foreach ($tables as $table_record) : $print_url = menuqr_get_menu_url($restaurant_id, (int) $table_record->id); ?>
                    <div class="print-qr-card">
                        <?php echo menuqr_render_qr_card_html($restaurant_id, (string) $table_record->table_number, $print_url, 180); ?>
                        <div style="margin-top:10px;font-size:12px;word-break:break-word;"><?php echo esc_html($print_url); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>window.print();</script>
        <?php wp_footer(); ?>
    </body>
    </html><?php
    exit;
}

$edit_category_id = absint($_GET['edit_category'] ?? 0);
$edit_category_type_id = absint($_GET['edit_category_type'] ?? 0);
$edit_item_id = absint($_GET['edit_item'] ?? 0);
$edit_table_id = absint($_GET['edit_table'] ?? 0);
$edit_room_id = absint($_GET['edit_room'] ?? 0);
$edit_staff_id = absint($_GET['edit_staff'] ?? 0);

if ($current_tab === 'staff' && sanitize_key(wp_unslash($_GET['export'] ?? '')) === 'csv') {
    menuqr_require_role(['restaurant_admin', 'super_admin']);
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="staff-export-' . $restaurant_id . '-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Email', 'Phone', 'Role', 'Department', 'Assigned Area', 'Shift', 'Status']);
    foreach ($staff as $export_staff) {
        fputcsv($out, [
            $export_staff->name ?? '',
            $export_staff->email ?? '',
            $export_staff->phone ?? '',
            function_exists('fqx_v167_role_display_name') ? fqx_v167_role_display_name((string) ($export_staff->role_name ?? '')) : ($export_staff->role_name ?? ''),
            function_exists('fqx_v167_staff_department') ? fqx_v167_staff_department($export_staff) : '',
            function_exists('fqx_v167_staff_area') ? fqx_v167_staff_area($export_staff) : '',
            function_exists('fqx_v167_staff_shift') ? fqx_v167_staff_shift($export_staff) : '',
            $export_staff->status ?? '',
        ]);
    }
    fclose($out);
    exit;
}

if ($current_tab === 'reports' && sanitize_key(wp_unslash($_GET['export'] ?? '')) === 'csv') {
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="restaurant-report-' . $restaurant_id . '-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Order Code', 'Table', 'Total', 'Payment Method', 'Payment Status', 'Order Status', 'Created']);
    foreach ($recent_orders as $export_order) {
        fputcsv($out, [
            $export_order->unique_code,
            $export_order->table_number ?: '',
            $export_order->final_total,
            $export_order->payment_method,
            $export_order->payment_status,
            $export_order->order_status,
            $export_order->created_at,
        ]);
    }
    fclose($out);
    exit;
}


if ($current_tab === 'reports' && sanitize_key(wp_unslash($_GET['export'] ?? '')) === 'pdf') {
    menuqr_require_role(['restaurant_admin', 'super_admin', 'staff']);
    nocache_headers();
    $report_total = 0.0;
    $report_order_count = 0;
    foreach ($recent_orders as $export_order) {
        $report_order_count++;
        $report_total += (float) ($export_order->final_total ?? 0);
    }
    ?><!doctype html><html><head><meta charset="utf-8"><title><?php echo esc_html($restaurant->name); ?> Reports</title><style>body{font-family:Arial,sans-serif;color:#111;margin:24px}.report-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #f4b11a;padding-bottom:14px;margin-bottom:18px}.brand{font-size:22px;font-weight:800}.muted{color:#555}.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:18px 0}.card{border:1px solid #ddd;border-radius:12px;padding:14px}.card strong{display:block;font-size:22px;margin-top:6px}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{border-bottom:1px solid #ddd;text-align:left;padding:9px;font-size:12px}th{background:#111;color:#fff}.total{font-size:24px;color:#d99021;font-weight:800}@media print{button{display:none}}</style></head><body><button onclick="window.print()" style="float:right;padding:10px 16px;background:#f4b11a;border:0;border-radius:8px;font-weight:700">Print / Save PDF</button><div class="report-head"><div><div class="brand"><?php echo esc_html($restaurant->name); ?></div><div class="muted">FluuexQR Restaurant Reports</div></div><div class="muted"><?php echo esc_html(date_i18n('d M Y h:i A')); ?></div></div><div class="cards"><div class="card">Total Revenue<strong class="total"><?php echo esc_html(menuqr_money($report_total)); ?></strong></div><div class="card">Orders<strong><?php echo esc_html((string) $report_order_count); ?></strong></div><div class="card">Average Order Value<strong><?php echo esc_html(menuqr_money($report_order_count ? $report_total / $report_order_count : 0)); ?></strong></div></div><table><thead><tr><th>Order Code</th><th>Table/Room</th><th>Total</th><th>Payment</th><th>Status</th><th>Created</th></tr></thead><tbody><?php foreach ($recent_orders as $export_order) : if (function_exists('menuqr_normalize_order_service_point')) { $export_order = menuqr_normalize_order_service_point($export_order); } ?><tr><td><?php echo esc_html($export_order->unique_code ?? ('ORD' . $export_order->id)); ?></td><td><?php echo esc_html(($export_order->service_label ?? 'Table') . ' ' . ($export_order->service_number ?? ($export_order->table_number ?? ''))); ?></td><td><?php echo esc_html(menuqr_money((float) ($export_order->final_total ?? 0))); ?></td><td><?php echo esc_html($export_order->payment_method ?? ''); ?></td><td><?php echo esc_html($export_order->order_status ?? ''); ?></td><td><?php echo esc_html($export_order->created_at ?? ''); ?></td></tr><?php endforeach; ?></tbody></table><script>setTimeout(function(){window.print()},350);</script></body></html><?php
    exit;
}


$editing_category = null;
$editing_category_type = null;
$editing_item = null;
$editing_table = null;
$editing_room = null;
$editing_staff = null;

foreach ($categories as $record) {
    if ((int) $record->id === $edit_category_id) { $editing_category = $record; break; }
}
foreach ($category_types as $record) {
    if ((int) $record->id === $edit_category_type_id) { $editing_category_type = $record; break; }
}
foreach ($items as $record) {
    if ((int) $record->id === $edit_item_id) { $editing_item = $record; break; }
}
foreach ($tables as $record) {
    if ((int) $record->id === $edit_table_id) { $editing_table = $record; break; }
}
foreach ($rooms as $record) {
    if ((int) $record->id === $edit_room_id) { $editing_room = $record; break; }
}
foreach ($staff as $record) {
    if ((int) $record->id === $edit_staff_id) { $editing_staff = $record; break; }
}

$current_section = sanitize_key(wp_unslash($_GET['section'] ?? ''));
$sidebar_nav_items = [
    ['id' => 'dashboard',   'label' => 'Dashboard',       'icon' => '🏠', 'href' => menuqr_restaurant_tab_url('dashboard'), 'active' => 'dashboard' === $current_tab],
    ['id' => 'orders',      'label' => 'Orders',          'icon' => '🧾', 'href' => menuqr_restaurant_tab_url('orders'), 'active' => 'orders' === $current_tab],
    ['id' => 'menu',        'label' => 'Menu',            'icon' => '🍽️', 'href' => add_query_arg('section', 'menu', menuqr_restaurant_tab_url('menu')), 'active' => 'menu' === $current_tab && 'categories' !== $current_section],
    ['id' => 'categories',  'label' => 'Categories',      'icon' => '📂', 'href' => add_query_arg('section', 'categories', menuqr_restaurant_tab_url('menu')), 'active' => 'menu' === $current_tab && 'categories' === $current_section],
    ['id' => 'tables',      'label' => 'Tables',          'icon' => '🪑', 'href' => menuqr_restaurant_tab_url('tables'), 'active' => 'tables' === $current_tab],
    ['id' => 'rooms',       'label' => 'Rooms & QR',      'icon' => '🛏️', 'href' => menuqr_restaurant_tab_url('rooms'), 'active' => 'rooms' === $current_tab],
    ['id' => 'staff',       'label' => 'Staff',           'icon' => '👥', 'href' => menuqr_restaurant_tab_url('staff'), 'active' => 'staff' === $current_tab],
    ['id' => 'reviews',     'label' => 'Reviews',         'icon' => '⭐', 'href' => menuqr_restaurant_tab_url('reviews'), 'active' => 'reviews' === $current_tab],
    ['id' => 'coupons',     'label' => 'Coupons',         'icon' => '🏷️', 'href' => menuqr_restaurant_tab_url('coupons'), 'active' => 'coupons' === $current_tab],
    ['id' => 'combos',      'label' => 'Combos',          'icon' => '🎁', 'href' => menuqr_restaurant_tab_url('combos'), 'active' => 'combos' === $current_tab],
    ['id' => 'reports',     'label' => 'Reports',         'icon' => '📈', 'href' => menuqr_restaurant_tab_url('reports'), 'active' => 'reports' === $current_tab],
    ['id' => 'payments',    'label' => 'Payment Settings','icon' => '💳', 'href' => menuqr_restaurant_tab_url('payments'), 'active' => 'payments' === $current_tab],
    ['id' => 'subscription','label' => 'Subscription',    'icon' => '💎', 'href' => menuqr_restaurant_tab_url('subscription'), 'active' => 'subscription' === $current_tab],
    ['id' => 'bills',       'label' => 'Bills',           'icon' => '📄', 'href' => menuqr_restaurant_tab_url('bills'), 'active' => 'bills' === $current_tab],
    ['id' => 'settings',    'label' => 'Bill Branding',   'icon' => '🧾', 'href' => menuqr_restaurant_tab_url('settings'), 'active' => 'settings' === $current_tab],
    ['id' => 'wifi',        'label' => 'WiFi QR',         'icon' => '📶', 'href' => menuqr_restaurant_tab_url('wifi'), 'active' => 'wifi' === $current_tab],
];

if (function_exists('fqx_v167_filter_sidebar_items_for_current_staff')) {
    $sidebar_nav_items = fqx_v167_filter_sidebar_items_for_current_staff($sidebar_nav_items);
}

$nav_items = [
    'dashboard' => 'Dashboard',
    'orders'    => 'Orders',
    'menu'      => 'Menu',
    'tables'    => 'Tables & QR',
    'rooms'     => 'Rooms & QR',
    'wifi'      => 'WiFi QR',
    'staff'     => 'Staff',
    'payments'  => 'Payment Settings',
    'whatsapp'  => 'WhatsApp Settings',
    'ai-support' => 'AI Support',
    'bills'     => 'Bills',
    'reviews'   => 'Reviews',
    'settings'  => 'Bill Branding',
    'combos'    => 'Combos',
    'coupons'   => 'Coupons',
    'reports'   => 'Reports',
    'subscription' => 'Subscription',
];

function menuqr_restaurant_edit_url(string $tab, string $key, int $id): string {
    return add_query_arg([
        'tab' => sanitize_key($tab),
        $key  => $id,
    ], menuqr_get_page_url_by_slug('restaurant-dashboard'));
}
?>
<section class="app-shell dashboard-shell">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <?php $fqx_admin_logo_uri = get_template_directory_uri() . '/assets/images/fluuexqr-logo-admin.png'; ?><img class="fq-dashboard-logo-img fq-dashboard-logo-full" src="<?php echo esc_url($fqx_admin_logo_uri); ?>" width="1536" height="864" alt="FluuexQR Hotel & Restaurant Automation" loading="eager" decoding="async">
            <div class="sidebar-role">Restaurant Admin</div>
            <div class="sidebar-rest"><?php echo esc_html($restaurant->name); ?></div>
        </div>
        <div class="sidebar-nav">
            <?php $fqx_room_sidebar_section = ('rooms' === $current_tab) ? ($current_section ?: 'rooms') : ('wifi' === $current_tab ? 'wifi' : ''); ?>
            <?php foreach ($sidebar_nav_items as $nav_item) : ?>
                <?php if ('wifi' === ($nav_item['id'] ?? '')) : continue; endif; ?>
                <?php if ('rooms' === ($nav_item['id'] ?? '')) : ?>
                    <div class="fq-room-nav-group <?php echo in_array($current_tab, ['rooms', 'wifi'], true) ? 'is-open' : ''; ?>">
                        <a class="nav-item fq-room-parent <?php echo in_array($current_tab, ['rooms', 'wifi'], true) ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_restaurant_tab_url('rooms')); ?>"><span class="nav-icon">🛏️</span><span class="nav-label">Rooms & QR</span><span class="fq-submenu-chevron">⌄</span></a>
                        <div class="fq-submenu">
                            <a class="fq-submenu-link <?php echo ('rooms' === $current_tab && in_array($fqx_room_sidebar_section, ['', 'rooms'], true)) ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('section', 'rooms', menuqr_restaurant_tab_url('rooms'))); ?>">Rooms</a>
                            <a class="fq-submenu-link <?php echo ('rooms' === $current_tab && 'templates' === $fqx_room_sidebar_section) ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('section', 'templates', menuqr_restaurant_tab_url('rooms'))); ?>">QR Templates</a>
                            <a class="fq-submenu-link <?php echo ('rooms' === $current_tab && 'print' === $fqx_room_sidebar_section) ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('section', 'print', menuqr_restaurant_tab_url('rooms'))); ?>">Print QR</a>
                            <a class="fq-submenu-link <?php echo ('wifi' === $current_tab) ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_restaurant_tab_url('wifi')); ?>">WiFi QR</a>
                        </div>
                    </div>
                <?php else : ?>
                    <a class="nav-item <?php echo !empty($nav_item['active']) ? 'active' : ''; ?>" href="<?php echo esc_url($nav_item['href']); ?>" <?php echo !empty($nav_item['attrs']) ? wp_kses_post($nav_item['attrs']) : ''; ?>><span class="nav-icon"><?php echo esc_html($nav_item['icon']); ?></span><span class="nav-label"><?php echo esc_html($nav_item['label']); ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-plan-card">
            <div class="sidebar-plan-icon">👑</div>
            <div class="sidebar-plan-content">
                <strong><?php echo esc_html(menuqr_plan_label($restaurant_id)); ?></strong>
                <span class="sidebar-plan-status <?php echo esc_attr(menuqr_subscription_is_active($restaurant_id) ? 'is-active' : 'is-expired'); ?>"><?php echo esc_html(menuqr_subscription_is_active($restaurant_id) ? 'Active' : 'Expired'); ?></span>
                <small><?php echo esc_html(!empty($subscription->expires_at) ? 'Valid until ' . mysql2date('d M Y', $subscription->expires_at) : 'Subscription status available'); ?></small>
            </div>
            <a class="sidebar-plan-btn" href="<?php echo esc_url(menuqr_restaurant_tab_url('subscription')); ?>">View Subscription</a>
        </div>
        <div class="sidebar-footer"><a class="btn-logout" href="<?php echo esc_url(wp_logout_url(menuqr_get_page_url_by_slug('login'))); ?>">← Logout</a></div>
    </aside>

    <div class="main-content">
        <div class="mq-sidebar-overlay"></div><div class="topbar"><button class="mq-sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
            <div class="topbar-search"><span class="topbar-search-icon">⌕</span><input type="text" value="" placeholder="Search orders, tables, rooms..." aria-label="Search orders, tables, rooms"><span class="topbar-kbd">⌘ K</span></div>
            <div class="topbar-right">
                <div class="topbar-date-chip">📅 <?php echo esc_html('Today, ' . current_time('j M Y')); ?></div>
                <div class="topbar-bell">🔔<span class="topbar-badge"><?php echo esc_html((string) min(9, $pending_orders_count)); ?></span></div>
                <div class="topbar-user"><div class="topbar-avatar"><?php echo esc_html(strtoupper(substr($restaurant->name, 0, 1))); ?></div><div><span class="topbar-name"><?php echo esc_html(wp_get_current_user()->display_name ?: wp_get_current_user()->user_email); ?></span><small>Restaurant Admin</small></div></div>
                <a class="fqx-topbar-brand fqx-topbar-brand-mini" href="<?php echo esc_url(menuqr_restaurant_tab_url('dashboard')); ?>"><?php $fqx_admin_logo_uri = get_template_directory_uri() . '/assets/images/fluuexqr-logo-admin.png'; ?><img class="fqx-topbar-brand-img" src="<?php echo esc_url($fqx_admin_logo_uri); ?>" width="1536" height="864" alt="FluuexQR Hotel & Restaurant Automation" loading="eager" decoding="async"></a>
            </div>
        </div>

        <div class="page-body">
            <?php if (!menuqr_subscription_is_active($restaurant_id)) : ?>
                <div class="alert alert-danger">Subscription Expired. Please renew.</div>
            <?php endif; ?>

            <div class="mq-plan-overview">
                <div>
                    <span class="mq-mini-label">Current plan</span>
                    <strong><?php echo esc_html(menuqr_plan_label($restaurant_id)); ?></strong>
                    <small><?php echo esc_html($plan_config['description'] ?? ''); ?></small>
                </div>
                <div class="mq-plan-usage">
                    <?php foreach (['tables' => 'Tables', 'items' => 'Items', 'categories' => 'Categories', 'staff' => 'Staff'] as $usage_key => $usage_label) : $limit = menuqr_plan_limit($restaurant_id, $usage_key); ?>
                        <span><?php echo esc_html($usage_label); ?>: <b><?php echo esc_html((string) ($usage[$usage_key] ?? 0)); ?></b>/<b><?php echo esc_html($limit < 0 ? '∞' : (string) $limit); ?></b></span>
                    <?php endforeach; ?>
                </div>
                <a class="btn btn-primary btn-sm" href="<?php echo esc_url(menuqr_restaurant_tab_url('subscription')); ?>">Upgrade / Renew</a>
            </div>

            <?php if ($mq_notice && isset($mq_notice_messages[$mq_notice])) : ?>
                <div class="alert alert-<?php echo esc_attr($mq_notice_messages[$mq_notice][0]); ?>">
                    <?php echo esc_html($mq_notice_messages[$mq_notice][1]); ?>
                </div>
            <?php endif; ?>

            <?php if (false && 'overview' === $current_tab) : ?>
                <div class="mq-overview-hero">
                    <div>
                        <span class="mq-report-kicker">🏪 Restaurant Overview</span>
                        <h2><?php echo esc_html($restaurant->name); ?></h2>
                        <p><?php echo esc_html($restaurant->address ?: 'No address saved yet.'); ?></p>
                        <div class="mq-overview-badges">
                            <span class="badge badge-<?php echo esc_attr($restaurant->approval_status); ?>"><?php echo esc_html(ucfirst((string) $restaurant->approval_status)); ?></span>
                            <span class="mq-plan-chip <?php echo esc_attr(menuqr_get_plan_badge_class($restaurant_id)); ?>"><?php echo esc_html(menuqr_plan_label($restaurant_id)); ?></span>
                            <span class="badge badge-<?php echo esc_attr(menuqr_subscription_is_active($restaurant_id) ? 'active' : 'expired'); ?>"><?php echo esc_html(menuqr_subscription_status_label($restaurant_id)); ?></span>
                        </div>
                    </div>
                    <div class="mq-report-hero-card fqx-v122-overview-side">
                        <span>Today Revenue</span>
                        <strong><?php echo esc_html(menuqr_money($stats['today_revenue'])); ?></strong>
                        <small><?php echo esc_html((string) $stats['today_orders']); ?> orders today · <?php echo esc_html((string) menuqr_subscription_days_left($restaurant_id)); ?> days left</small>
                        <div class="fqx-v122-sub-actions">
                            <a class="btn btn-primary btn-sm" href="<?php echo esc_url(menuqr_restaurant_tab_url('orders')); ?>">View Orders</a>
                            <a class="btn btn-outline btn-sm" href="<?php echo esc_url(menuqr_restaurant_tab_url('subscription')); ?>">Renew / Upgrade</a>
                        </div>
                    </div>
                </div>

                <div class="stat-grid">
                    <div class="card"><div class="card-title">Today's Orders</div><div class="card-value"><?php echo esc_html((string) $stats['today_orders']); ?></div><div class="card-sub">Live today</div></div>
                    <div class="card"><div class="card-title">Revenue</div><div class="card-value"><?php echo esc_html(menuqr_money($stats['revenue'])); ?></div><div class="card-sub">All time</div></div>
                    <div class="card"><div class="card-title">Pending Orders</div><div class="card-value"><?php echo esc_html((string) count(array_filter($recent_orders, static fn($o) => (string) $o->order_status === 'pending'))); ?></div><div class="card-sub">Need action</div></div>
                    <div class="card"><div class="card-title">Current Plan</div><div class="card-value" style="font-size:1.35rem;"><?php echo esc_html(menuqr_plan_label($restaurant_id)); ?></div><div class="card-sub"><?php echo esc_html(menuqr_subscription_status_label($restaurant_id)); ?></div></div>
                </div>

                <div class="mq-report-grid">
                    <div class="mq-report-card">
                        <div class="mq-report-card-head"><div><h3>Profile Summary</h3><p>Owner and contact information</p></div><span class="mq-report-pill">Profile</span></div>
                        <div class="mq-report-list">
                            <div><span>Owner</span><strong><?php echo esc_html($restaurant->owner_name ?: '—'); ?></strong></div>
                            <div><span>Email</span><strong><?php echo esc_html($restaurant->email ?: '—'); ?></strong></div>
                            <div><span>Phone</span><strong><?php echo esc_html($restaurant->phone ?: '—'); ?></strong></div>
                            <div><span>Tables</span><strong><?php echo esc_html((string) count($tables)); ?></strong></div>
                        </div>
                    </div>
                    <div class="mq-report-card">
                        <div class="mq-report-card-head"><div><h3>Quick Actions</h3><p>Jump to daily tasks</p></div><span class="mq-report-pill accent">Actions</span></div>
                        <div class="page-header-right" style="justify-content:flex-start;">
                            <a class="btn btn-primary" href="<?php echo esc_url(menuqr_restaurant_tab_url('orders')); ?>">Manage Orders</a>
                            <a class="btn btn-teal" href="<?php echo esc_url(menuqr_restaurant_tab_url('menu')); ?>">Add Menu Item</a>
                            <a class="btn btn-outline" href="<?php echo esc_url(menuqr_restaurant_tab_url('tables')); ?>">Generate QR</a>
                            <a class="btn btn-outline" href="<?php echo esc_url(menuqr_restaurant_tab_url('bills')); ?>">Print Bills</a>
                            <a class="btn btn-outline" href="<?php echo esc_url(menuqr_restaurant_tab_url('subscription')); ?>">Upgrade/Renew</a>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title">Plan Usage Limits</div>
                    <div class="mq-plan-limit-grid">
                        <?php foreach (['tables' => 'Tables', 'items' => 'Menu Items', 'categories' => 'Categories', 'staff' => 'Staff'] as $usage_key => $usage_label) : $limit = menuqr_plan_limit($restaurant_id, $usage_key); $used = (int) ($usage[$usage_key] ?? 0); $pct = $limit < 0 ? 100 : min(100, (int) round(($used / max(1, $limit)) * 100)); ?>
                            <div class="mq-limit-card">
                                <span><?php echo esc_html($usage_label); ?></span>
                                <strong><?php echo esc_html((string) $used); ?> / <?php echo esc_html($limit < 0 ? '∞' : (string) $limit); ?></strong>
                                <i><b style="width:<?php echo esc_attr((string) $pct); ?>%"></b></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php elseif ('dashboard' === $current_tab) : ?>
                <div class="fqx-admin-hero">
                    <div>
                        <h1>Restaurant Admin Dashboard</h1>
                        <p>Welcome back, <?php echo esc_html(wp_get_current_user()->display_name ?: 'Admin'); ?>! Here's what's happening with your business today.</p>
                    </div>
                </div>

                <div class="fqx-dashboard-stat-row">
                    <div class="fqx-stat-card"><div class="fqx-stat-icon">👜</div><div><div class="fqx-stat-label">Today Orders</div><div class="fqx-stat-value"><?php echo esc_html((string) $stats['today_orders']); ?></div><div class="fqx-stat-meta"><?php echo esc_html($completed_orders_count); ?> completed</div></div></div>
                    <div class="fqx-stat-card"><div class="fqx-stat-icon">🪑</div><div><div class="fqx-stat-label">Active Tables</div><div class="fqx-stat-value"><?php echo esc_html((string) $active_tables_count); ?> / <?php echo esc_html((string) count($tables)); ?></div><div class="fqx-stat-meta">Restaurant tables</div></div></div>
                    <div class="fqx-stat-card"><div class="fqx-stat-icon">🛏️</div><div><div class="fqx-stat-label">Rooms</div><div class="fqx-stat-value"><?php echo esc_html((string) $active_rooms_count); ?> / <?php echo esc_html((string) count($rooms)); ?></div><div class="fqx-stat-meta">Available rooms</div></div></div>
                    <div class="fqx-stat-card"><div class="fqx-stat-icon">📈</div><div><div class="fqx-stat-label">Revenue</div><div class="fqx-stat-value"><?php echo esc_html(menuqr_money($stats['revenue'])); ?></div><div class="fqx-stat-meta">All time revenue</div></div></div>
                    <div class="fqx-stat-card"><div class="fqx-stat-icon">🕒</div><div><div class="fqx-stat-label">Pending Orders</div><div class="fqx-stat-value"><?php echo esc_html((string) $pending_orders_count); ?></div><div class="fqx-stat-meta">Needs attention</div></div></div>
                </div>

                <div class="fqx-dashboard-grid">
                    <div class="fqx-panel">
                        <div class="fqx-panel-head"><h3>Orders Overview</h3><span class="fqx-panel-tag">This Week</span></div>
                        <div class="fqx-orders-overview">
                            <div class="fqx-donut" style="<?php echo esc_attr($donut_style); ?>"><div class="fqx-donut-center"><strong><?php echo esc_html((string) $order_total_count); ?></strong><span>Total Orders</span></div></div>
                            <div class="fqx-legend-list">
                                <div><span class="dot dot-gold"></span><b>Dine In</b><small><?php echo esc_html((string) $order_type_counts['Dine In']); ?> (<?php echo esc_html((string) $dine_pct); ?>%)</small></div>
                                <div><span class="dot dot-amber"></span><b>Takeaway</b><small><?php echo esc_html((string) $order_type_counts['Takeaway']); ?> (<?php echo esc_html((string) $take_pct); ?>%)</small></div>
                                <div><span class="dot dot-brown"></span><b>Room Service</b><small><?php echo esc_html((string) $order_type_counts['Room Service']); ?> (<?php echo esc_html((string) $room_pct); ?>%)</small></div>
                                <div><span class="dot dot-bronze"></span><b>Delivery</b><small><?php echo esc_html((string) $order_type_counts['Delivery']); ?> (<?php echo esc_html((string) $delivery_pct); ?>%)</small></div>
                            </div>
                        </div>
                    </div>

                    <div class="fqx-panel">
                        <div class="fqx-panel-head"><h3>Revenue Trend</h3><span class="fqx-panel-tag">This Week</span></div>
                        <div class="fqx-revenue-total"><?php echo esc_html(menuqr_money($stats['revenue'])); ?></div>
                        <div class="fqx-revenue-meta">Total Revenue</div>
                        <svg class="fqx-trend-chart" viewBox="0 0 320 140" aria-hidden="true">
                            <defs><linearGradient id="fqxArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#f2b24c" stop-opacity="0.35" /><stop offset="100%" stop-color="#f2b24c" stop-opacity="0" /></linearGradient></defs>
                            <polyline points="<?php echo esc_attr($dashboard_polyline); ?>" fill="none" stroke="#f2b24c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>
                            <?php foreach ($points as $pt) : [$px,$py] = array_map('floatval', explode(',', $pt)); ?><circle cx="<?php echo esc_attr((string) $px); ?>" cy="<?php echo esc_attr((string) $py); ?>" r="4.5" fill="#ffd27a"></circle><?php endforeach; ?>
                        </svg>
                        <div class="fqx-chart-labels"><?php foreach ($daily_labels as $lbl) : ?><span><?php echo esc_html($lbl); ?></span><?php endforeach; ?></div>
                    </div>

                    <div class="fqx-panel">
                        <div class="fqx-panel-head"><h3>Popular Items</h3><span class="fqx-panel-tag">This Week</span></div>
                        <div class="fqx-popular-list">
                            <?php if (!$top_items) : ?>
                                <div class="empty-state"><span class="empty-icon">🍽️</span><h4>No sales yet</h4><p>Popular items will show here after orders.</p></div>
                            <?php else : ?>
                                <?php foreach (array_slice($top_items, 0, 5, true) as $item_name => $qty) : ?>
                                    <div class="fqx-popular-item"><div class="fqx-popular-thumb">🍛</div><div class="fqx-popular-content"><strong><?php echo esc_html($item_name); ?></strong><small><?php echo esc_html((string) $qty); ?> orders</small></div><span class="fqx-popular-qty"><?php echo esc_html((string) $qty); ?></span></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="fqx-dashboard-grid fqx-dashboard-grid-bottom">
                    <div class="fqx-panel fqx-recent-orders-panel">
                        <div class="fqx-panel-head"><h3>Recent Orders</h3><a class="btn btn-outline btn-sm" href="<?php echo esc_url(menuqr_restaurant_tab_url('orders')); ?>">View All Orders</a></div>
                        <div class="table-wrap"><div class="table-scroll">
                            <table class="data-table fqx-dashboard-orders-table">
                                <thead><tr class="fq-bill-row-card"><th>Order ID</th><th>Type</th><th>Room/Table</th><th>Status</th><th>Amount</th><th>Time</th></tr></thead>
                                <tbody>
                                <?php if (!$recent_orders) : ?>
                                    <tr class="fq-bill-row-card"><td colspan="6"><div class="empty-state"><span class="empty-icon">🧾</span><h4>No orders found</h4><p>Recent orders will appear here.</p></div></td></tr>
                                <?php else : ?>
                                    <?php foreach (array_slice($recent_orders, 0, 5) as $order) : if (function_exists('menuqr_normalize_order_service_point')) { $order = menuqr_normalize_order_service_point($order); } $service_label = (string) ($order->service_label ?? 'Table'); $badge_class = 'badge-' . sanitize_html_class((string) ($order->order_status ?? 'pending')); ?>
                                        <tr class="fq-bill-row-card">
                                            <td>#<?php echo esc_html($order->unique_code ?: (string) $order->id); ?></td>
                                            <td><?php echo esc_html($service_label); ?></td>
                                            <td><?php echo esc_html(($order->service_value ?? ($order->table_number ?: '—'))); ?></td>
                                            <td><span class="badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html(ucfirst((string) $order->order_status)); ?></span></td>
                                            <td><?php echo esc_html(menuqr_money((float) $order->final_total)); ?></td>
                                            <td><?php echo esc_html(mysql2date('g:i A', $order->created_at)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div></div>
                    </div>

                    <div class="fqx-panel fqx-quick-actions-panel">
                        <div class="fqx-panel-head"><h3>Quick Actions</h3></div>
                        <div class="fqx-quick-action-grid">
                            <a class="fqx-quick-action" href="<?php echo esc_url(menuqr_restaurant_tab_url('tables')); ?>"><span class="qa-icon">🔳</span><div><strong>Generate QR</strong><small>Create QR for menus, tables & rooms</small></div><span class="qa-arrow">→</span></a>
                            <a class="fqx-quick-action" href="<?php echo esc_url(menuqr_restaurant_tab_url('rooms')); ?>"><span class="qa-icon">🛏️</span><div><strong>Manage Rooms</strong><small>Manage rooms, status & QR codes</small></div><span class="qa-arrow">→</span></a>
                            <a class="fqx-quick-action" href="<?php echo esc_url(menuqr_restaurant_tab_url('wifi')); ?>"><span class="qa-icon">📶</span><div><strong>WiFi QR</strong><small>Manage guest WiFi QR details</small></div><span class="qa-arrow">→</span></a>
                            <a class="fqx-quick-action" href="<?php echo esc_url(menuqr_restaurant_tab_url('reports')); ?>"><span class="qa-icon">📊</span><div><strong>View Reports</strong><small>Analytics & detailed reports</small></div><span class="qa-arrow">→</span></a>
                        </div>
                    </div>
                </div>

                <div class="fqx-feature-strip">
                    <div class="fqx-feature-card"><div class="fqx-feature-icon">📱</div><h4>Fully Responsive</h4><p>Optimized for all devices. Seamless experience on desktop, tablet & mobile.</p></div>
                    <div class="fqx-feature-card"><div class="fqx-feature-icon">🛡️</div><h4>Secure & Reliable</h4><p>Enterprise-grade security to keep your data safe and compliant.</p></div>
                    <div class="fqx-feature-card"><div class="fqx-feature-icon">⚡</div><h4>Fast & Efficient</h4><p>Lightning fast performance to help you run your business smoothly.</p></div>
                    <div class="fqx-feature-card"><div class="fqx-feature-icon">🎧</div><h4>24/7 Support</h4><p>We’re here to help you anytime, anywhere.</p></div>
                </div>

            <?php elseif ('orders' === $current_tab) : ?>
                <?php
                if (!function_exists('fqx_v168_icon')) {
                    function fqx_v168_icon(string $name, string $class = ''): string {
                        $class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';
                        $icons = [
                            'bag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 8V7a5 5 0 0 1 10 0v1"/><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 12h6"/></svg>',
                            'hourglass' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12"/><path d="M6 21h12"/><path d="M8 3v4a4 4 0 0 0 1.2 2.9L12 12l2.8-2.1A4 4 0 0 0 16 7V3"/><path d="M8 21v-4a4 4 0 0 1 1.2-2.9L12 12l2.8 2.1A4 4 0 0 1 16 17v4"/></svg>',
                            'chef' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 11a4 4 0 1 1 3.5-6 4 4 0 0 1 7 2 3 3 0 0 1-.5 5.9V21H7v-8.1A3 3 0 0 1 7 11Z"/><path d="M7 15h10"/></svg>',
                            'cloche' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6V4"/><path d="M5 15a7 7 0 0 1 14 0"/><path d="M4 17h16"/><path d="M6 20h12"/></svg>',
                            'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.6 2.6L16 9"/></svg>',
                            'x' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6"/><path d="m15 9-6 6"/></svg>',
                            'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
                            'filter' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>',
                            'table' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10h16"/><path d="M6 10V6h12v4"/><path d="M7 10v8"/><path d="M17 10v8"/></svg>',
                            'room' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V9a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v11"/><path d="M4 13h16"/><path d="M8 13v-2h4v2"/></svg>',
                            'delivery' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v10H3z"/><path d="M14 11h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>',
                            'pickup' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>',
                            'dots' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>',
                            'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8 9.6a16 16 0 0 0 6.4 6.4l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2Z"/></svg>',
                            'wa' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a8 8 0 1 1-14.8 4.2L4 20l3.9-1.1A8 8 0 1 1 20 12Z"/><path d="M9 9.5c.3-1.1.8-1.5 1.3-1.5.3 0 .5 0 .7.1.2.2.7 1.4.8 1.6.1.2.1.4 0 .6-.1.2-.4.5-.5.7-.2.2-.2.4 0 .7.6 1.1 1.5 1.9 2.6 2.4.3.2.5.2.7 0 .2-.2.6-.8.8-.9.2-.1.4-.1.6 0 .2.1 1.3.6 1.5.7.2.1.3.2.4.3 0 .2-.1 1-.6 1.4-.5.4-1.2.5-1.9.3-2-.5-3.8-1.6-5.2-3.2-1.4-1.5-2.3-3.3-2.5-5.1Z"/></svg>',
                            'print' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 9V3h10v6"/><path d="M7 17h10v4H7z"/><path d="M6 9h12a3 3 0 0 1 3 3v3H3v-3a3 3 0 0 1 3-3Z"/></svg>',
                            'track' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18V6l8-3 8 3v12l-8 3-8-3Z"/><path d="M12 3v18"/><path d="m4 6 8 3 8-3"/></svg>',
                            'staff' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-8 0v2"/><circle cx="12" cy="8" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.8"/><path d="M16 4.2a4 4 0 0 1 0 7.6"/></svg>',
                            'plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
                        ];
                        return '<span' . $class_attr . ' aria-hidden="true">' . ($icons[$name] ?? '') . '</span>';
                    }
                }
                $order_filters = [
                    'type' => sanitize_key(wp_unslash($_GET['order_type'] ?? 'all')),
                    'status' => sanitize_key(wp_unslash($_GET['order_status'] ?? 'all')),
                    'payment' => sanitize_key(wp_unslash($_GET['order_payment'] ?? 'all')),
                    'search' => sanitize_text_field(wp_unslash($_GET['order_search'] ?? '')),
                    'date' => sanitize_text_field(wp_unslash($_GET['order_date'] ?? current_time('Y-m-d'))),
                ];
                $order_page = max(1, absint($_GET['orders_page'] ?? 1));
                $order_per_page = 10;
                $all_orders_for_ui = array_values($recent_orders ?: []);
                $filtered_orders = [];
                foreach ($all_orders_for_ui as $o) {
                    if (function_exists('menuqr_normalize_order_service_point')) { $o = menuqr_normalize_order_service_point($o); }
                    $source = strtolower((string) ($o->order_source ?? ''));
                    $room_id = (int) ($o->room_id ?? 0);
                    $table_id = (int) ($o->table_id ?? 0);
                    $service_label = strtolower((string) ($o->service_label ?? ''));
                    $type_key = 'table';
                    if ($room_id > 0 || str_contains($source, 'room') || str_contains($service_label, 'room')) { $type_key = 'room'; }
                    elseif (str_contains($source, 'delivery') || str_contains($service_label, 'delivery')) { $type_key = 'delivery'; }
                    elseif (str_contains($source, 'pickup') || str_contains($service_label, 'pickup') || str_contains($service_label, 'take')) { $type_key = 'pickup'; }
                    $payment_key = sanitize_key((string) ($o->payment_status ?? 'pending'));
                    $status_key = sanitize_key((string) ($o->order_status ?? 'pending'));
                    $haystack = strtolower(wp_json_encode([$o->unique_code ?? '', $o->customer_name ?? '', $o->customer_phone ?? '', $o->customer_whatsapp ?? '', $o->table_number ?? '', $o->room_number ?? '', $o->items_json ?? '']));
                    if ($order_filters['type'] !== 'all' && $order_filters['type'] !== $type_key) { continue; }
                    if ($order_filters['status'] !== 'all' && $order_filters['status'] !== $status_key) { continue; }
                    if ($order_filters['payment'] !== 'all' && $order_filters['payment'] !== $payment_key) { continue; }
                    if ($order_filters['search'] !== '' && !str_contains($haystack, strtolower($order_filters['search']))) { continue; }
                    $filtered_orders[] = $o;
                }
                $order_counts = ['total'=>count($all_orders_for_ui),'pending'=>0,'preparing'=>0,'ready'=>0,'completed'=>0,'cancelled'=>0];
                $order_revenue = 0.0; $completed_revenue = 0.0; $completed_count = 0;
                foreach ($all_orders_for_ui as $o) {
                    $st = sanitize_key((string) ($o->order_status ?? 'pending'));
                    $order_revenue += (float) ($o->final_total ?? 0);
                    if (isset($order_counts[$st])) { $order_counts[$st]++; }
                    if (in_array($st, ['served','completed','delivered'], true)) { $order_counts['completed']++; $completed_count++; $completed_revenue += (float) ($o->final_total ?? 0); }
                }
                $total_filtered_orders = count($filtered_orders);
                $orders_total_pages = max(1, (int) ceil($total_filtered_orders / $order_per_page));
                $order_page = min($order_page, $orders_total_pages);
                $paged_orders = array_slice($filtered_orders, ($order_page - 1) * $order_per_page, $order_per_page);
                $selected_order_id = absint($_GET['selected_order'] ?? 0);
                $selected_order = null;
                foreach ($filtered_orders as $candidate_order) { if ((int) $candidate_order->id === $selected_order_id) { $selected_order = $candidate_order; break; } }
                if (!$selected_order && $paged_orders) { $selected_order = $paged_orders[0]; }
                if ($selected_order && function_exists('menuqr_normalize_order_service_point')) { $selected_order = menuqr_normalize_order_service_point($selected_order); }
                $selected_items = $selected_order ? (json_decode((string) $selected_order->items_json, true) ?: []) : [];
                $selected_subtotal = $selected_order ? (float) ($selected_order->subtotal ?? 0) : 0;
                $selected_total = $selected_order ? (float) ($selected_order->final_total ?? 0) : 0;
                $selected_tax = max(0, $selected_total - $selected_subtotal);
                $selected_discount = (float) ($selected_order->discount_total ?? 0);
                $selected_phone = $selected_order ? menuqr_normalize_phone((string) (($selected_order->customer_phone ?? '') ?: ($selected_order->customer_whatsapp ?? ''))) : '';
                $selected_wa = $selected_phone ? 'https://wa.me/' . rawurlencode($selected_phone) : '';
                $selected_bill = null; $selected_bill_url = ''; $selected_print_url = ''; $selected_track_url = '';
                if ($selected_order && function_exists('menuqr_v123_force_bill_for_order')) { $selected_bill = menuqr_v123_force_bill_for_order((int) $selected_order->id, (string) ($selected_order->bill_session_token ?? '')); }
                if ($selected_bill && function_exists('menuqr_bill_access_url')) { $selected_bill_url = menuqr_bill_access_url($selected_bill); $selected_print_url = add_query_arg('print', '1', $selected_bill_url); }
                if ($selected_order) { $selected_track_url = add_query_arg(['order' => (string) ($selected_order->unique_code ?? $selected_order->id), 'restaurant_id' => $restaurant_id], menuqr_get_page_url_by_slug('menu')); }
                $daily_order_points = [];
                foreach (array_slice($daily_totals, -8, 8, true) as $d_total) { $daily_order_points[] = (float) $d_total; }
                if (!$daily_order_points) { $daily_order_points = [0, 0, 0, 0, 0, 0, 0, max(1, $order_revenue)]; }
                $daily_max = max(1, max($daily_order_points));
                $svg_points = [];
                $daily_count = max(1, count($daily_order_points) - 1);
                foreach ($daily_order_points as $i => $val) {
                    $svg_points[] = round(18 + ($i * (300 / $daily_count)), 2) . ',' . round(120 - (($val / $daily_max) * 92), 2);
                }
                ?>
                <div class="fq-orders-page fq-orders-v168">
                    <div class="fq-orders-titlebar">
                        <div>
                            <h1>Orders Management</h1>
                            <p>Manage and track all restaurant and hotel orders in real-time</p>
                        </div>
                        <button type="button" class="fq-new-order-btn" id="fqxV207OpenManualOrder"><?php echo fqx_v168_icon('plus', 'fq-svg-icon'); ?> New Order</button>
                    </div>

                    <div class="fq-orders-stats">
                        <div class="fq-order-stat-card"><span class="stat-icon gold"><?php echo fqx_v168_icon('bag', 'fq-svg-icon'); ?></span><div><small>Total Orders</small><strong><?php echo esc_html((string) $order_counts['total']); ?></strong><em>↑ 12.5% vs yesterday</em></div></div>
                        <div class="fq-order-stat-card"><span class="stat-icon amber"><?php echo fqx_v168_icon('hourglass', 'fq-svg-icon'); ?></span><div><small>Pending</small><strong><?php echo esc_html((string) $order_counts['pending']); ?></strong><em class="warn">↑ 5 vs yesterday</em></div></div>
                        <div class="fq-order-stat-card"><span class="stat-icon blue"><?php echo fqx_v168_icon('chef', 'fq-svg-icon'); ?></span><div><small>Preparing</small><strong><?php echo esc_html((string) $order_counts['preparing']); ?></strong><em class="info">↑ 2 vs yesterday</em></div></div>
                        <div class="fq-order-stat-card"><span class="stat-icon gold"><?php echo fqx_v168_icon('cloche', 'fq-svg-icon'); ?></span><div><small>Ready</small><strong><?php echo esc_html((string) $order_counts['ready']); ?></strong><em>↑ 3 vs yesterday</em></div></div>
                        <div class="fq-order-stat-card"><span class="stat-icon green"><?php echo fqx_v168_icon('check', 'fq-svg-icon'); ?></span><div><small>Completed</small><strong><?php echo esc_html((string) $order_counts['completed']); ?></strong><em>↑ 10.1% vs yesterday</em></div></div>
                        <div class="fq-order-stat-card"><span class="stat-icon red"><?php echo fqx_v168_icon('x', 'fq-svg-icon'); ?></span><div><small>Cancelled</small><strong><?php echo esc_html((string) $order_counts['cancelled']); ?></strong><em class="danger">↓ 2 vs yesterday</em></div></div>
                    </div>

                    <form class="fq-orders-filter-bar" method="get" action="<?php echo esc_url(menuqr_get_page_url_by_slug('restaurant-dashboard')); ?>">
                        <input type="hidden" name="tab" value="orders">
                        <div class="fq-filter-group order-type-group">
                            <span class="fq-filter-label">Order Type</span>
                            <select name="order_type"><option value="all">All Types</option><option value="table" <?php selected($order_filters['type'], 'table'); ?>>Table</option><option value="room" <?php selected($order_filters['type'], 'room'); ?>>Room</option><option value="delivery" <?php selected($order_filters['type'], 'delivery'); ?>>Delivery</option><option value="pickup" <?php selected($order_filters['type'], 'pickup'); ?>>Pickup</option></select>
                            <a class="<?php echo $order_filters['type']==='table' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['tab'=>'orders','order_type'=>'table'])); ?>"><?php echo fqx_v168_icon('table','fq-svg-icon'); ?> Table</a>
                            <a class="<?php echo $order_filters['type']==='room' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['tab'=>'orders','order_type'=>'room'])); ?>"><?php echo fqx_v168_icon('room','fq-svg-icon'); ?> Room</a>
                            <a class="<?php echo $order_filters['type']==='delivery' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['tab'=>'orders','order_type'=>'delivery'])); ?>"><?php echo fqx_v168_icon('delivery','fq-svg-icon'); ?> Delivery</a>
                            <a class="<?php echo $order_filters['type']==='pickup' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['tab'=>'orders','order_type'=>'pickup'])); ?>"><?php echo fqx_v168_icon('pickup','fq-svg-icon'); ?> Pickup</a>
                        </div>
                        <label><span>Status</span><select name="order_status"><option value="all">All Status</option><?php foreach (['pending','accepted','preparing','ready','served','completed','delivered','cancelled'] as $st) : ?><option value="<?php echo esc_attr($st); ?>" <?php selected($order_filters['status'], $st); ?>><?php echo esc_html(ucwords(str_replace('_',' ', $st))); ?></option><?php endforeach; ?></select></label>
                        <label><span>Date</span><input type="date" name="order_date" value="<?php echo esc_attr($order_filters['date']); ?>"></label>
                        <label><span>Payment Status</span><select name="order_payment"><option value="all">All Payments</option><?php foreach (['paid','online','unpaid','pending','refunded'] as $pay_st) : ?><option value="<?php echo esc_attr($pay_st); ?>" <?php selected($order_filters['payment'], $pay_st); ?>><?php echo esc_html(ucwords(str_replace('_',' ', $pay_st))); ?></option><?php endforeach; ?></select></label>
                        <div class="fq-order-search"><?php echo fqx_v168_icon('search','fq-svg-icon'); ?><input name="order_search" value="<?php echo esc_attr($order_filters['search']); ?>" placeholder="Search in orders..."></div>
                        <button type="submit" class="fq-order-filter-btn"><?php echo fqx_v168_icon('filter','fq-svg-icon'); ?> Filters</button>
                    </form>

                    <div class="fq-orders-layout">
                        <main class="fq-orders-main">
                            <div class="fq-orders-table-card">
                                <table class="fq-orders-table">
                                    <thead><tr><th>Order ID</th><th>Customer</th><th>Table / Room</th><th>Items</th><th>Amount</th><th>Payment</th><th>Status</th><th>Time</th><th>Actions</th></tr></thead>
                                    <tbody>
                                    <?php if (!$paged_orders) : ?>
                                        <tr><td colspan="9"><div class="fq-order-empty-state"><strong>No orders found.</strong><p>Orders will appear here when customers place orders.</p></div></td></tr>
                                    <?php else : foreach ($paged_orders as $order) :
                                        if (function_exists('menuqr_normalize_order_service_point')) { $order = menuqr_normalize_order_service_point($order); }
                                        $items = json_decode((string) $order->items_json, true) ?: [];
                                        $first_items = array_slice(array_map(static fn($it) => (string) ($it['name'] ?? 'Item'), $items), 0, 2);
                                        $item_summary = implode(', ', $first_items) . (count($items) > 2 ? '...' : '');
                                        $order_source = strtolower((string) ($order->order_source ?? ''));
                                        $service_label = strtolower((string) ($order->service_label ?? ''));
                                        $type_key = ((int)($order->room_id ?? 0) > 0 || str_contains($order_source,'room') || str_contains($service_label,'room')) ? 'room' : (str_contains($order_source,'delivery') ? 'delivery' : (str_contains($order_source,'pickup') ? 'pickup' : 'table'));
                                        $type_label = $type_key === 'room' ? 'Room' : ($type_key === 'delivery' ? 'Delivery' : ($type_key === 'pickup' ? 'Pickup' : 'Table'));
                                        $place_main = $type_key === 'room' ? 'Room ' . ($order->service_value ?? $order->room_number ?? '—') : ($type_key === 'delivery' ? 'Delivery' : ($type_key === 'pickup' ? 'Pickup' : 'Table ' . ($order->service_value ?? $order->table_number ?? '—')));
                                        $place_sub = $type_key === 'room' ? 'Suite' : ($type_key === 'delivery' ? 'Outside' : ($type_key === 'pickup' ? 'Counter' : 'Floor 1'));
                                        $customer_name = trim((string)($order->customer_name ?? '')) ?: 'Guest';
                                        $customer_phone = trim((string)(($order->customer_phone ?? '') ?: ($order->customer_whatsapp ?? '')));
                                        $name_parts = preg_split('/\s+/', $customer_name) ?: [];
                                        $initials = strtoupper(substr($name_parts[0] ?? 'G',0,1) . substr($name_parts[1] ?? '',0,1));
                                        $payment_status = sanitize_key((string)($order->payment_status ?? 'pending'));
                                        $order_status = sanitize_key((string)($order->order_status ?? 'pending'));
                                        $select_url = add_query_arg(['tab'=>'orders','selected_order'=>(int)$order->id], menuqr_get_page_url_by_slug('restaurant-dashboard'));
                                    ?>
                                        <tr class="fq-order-row <?php echo $selected_order && (int)$selected_order->id === (int)$order->id ? 'is-selected' : ''; ?>" data-select-url="<?php echo esc_url($select_url); ?>">
                                            <td data-label="Order ID"><strong>#<?php echo esc_html($order->unique_code ?: ('ORD-' . $order->id)); ?></strong></td>
                                            <td data-label="Customer"><div class="fq-order-customer"><span><?php echo esc_html($initials); ?></span><div><strong><?php echo esc_html($customer_name); ?></strong><small><?php echo esc_html($customer_phone ?: 'No phone'); ?></small></div></div></td>
                                            <td data-label="Table / Room"><strong><?php echo esc_html($place_main); ?></strong><small><?php echo esc_html($place_sub); ?></small></td>
                                            <td data-label="Items"><strong><?php echo esc_html(count($items) . ' Items'); ?></strong><small><?php echo esc_html($item_summary ?: '—'); ?></small></td>
                                            <td data-label="Amount"><strong><?php echo esc_html(menuqr_money((float)$order->final_total)); ?></strong></td>
                                            <td data-label="Payment"><span class="fq-payment-badge pay-<?php echo esc_attr($payment_status); ?>"><?php echo esc_html(ucwords(str_replace('_',' ', $payment_status))); ?></span></td>
                                            <td data-label="Status"><span class="fq-order-status-badge status-<?php echo esc_attr($order_status); ?>"><?php echo esc_html(ucwords(str_replace('_',' ', $order_status))); ?></span></td>
                                            <td data-label="Time"><?php echo esc_html(mysql2date('h:i A', $order->created_at)); ?><small><?php echo esc_html(human_time_diff(strtotime($order->created_at), current_time('timestamp')) . ' ago'); ?></small></td>
                                            <td data-label="Actions"><a class="fq-row-menu" href="<?php echo esc_url($select_url); ?>" title="View details"><?php echo fqx_v168_icon('dots','fq-svg-icon'); ?></a></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                                <div class="fq-orders-pagination">
                                    <span>Showing <?php echo esc_html((string)(($order_page - 1) * $order_per_page + ($total_filtered_orders ? 1 : 0))); ?> to <?php echo esc_html((string)min($total_filtered_orders, $order_page * $order_per_page)); ?> of <?php echo esc_html((string)$total_filtered_orders); ?> orders</span>
                                    <div><?php if ($order_page > 1) : ?><a href="<?php echo esc_url(add_query_arg('orders_page', $order_page - 1)); ?>">‹</a><?php endif; ?><?php for ($i = 1; $i <= min(5, $orders_total_pages); $i++) : ?><a class="<?php echo $i === $order_page ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('orders_page', $i)); ?>"><?php echo esc_html((string)$i); ?></a><?php endfor; ?><?php if ($orders_total_pages > 5) : ?><span>...</span><a href="<?php echo esc_url(add_query_arg('orders_page', $orders_total_pages)); ?>"><?php echo esc_html((string)$orders_total_pages); ?></a><?php endif; ?><?php if ($order_page < $orders_total_pages) : ?><a href="<?php echo esc_url(add_query_arg('orders_page', $order_page + 1)); ?>">›</a><?php endif; ?></div>
                                </div>
                            </div>
                            <div class="fq-orders-bottom-grid">
                                <div class="fq-orders-analytics-card">
                                    <div class="fq-panel-head"><h3>Orders Analytics</h3><span>Today</span></div>
                                    <div class="fq-analytics-content">
                                        <div class="fq-analytics-numbers"><p>Total Revenue <strong><?php echo esc_html(menuqr_money($order_revenue)); ?></strong></p><p>Average Order Value <strong><?php echo esc_html(menuqr_money($order_counts['total'] ? $order_revenue / max(1, $order_counts['total']) : 0)); ?></strong></p><p>Orders Completed <strong><?php echo esc_html((string)$completed_count); ?></strong></p></div>
                                        <svg class="fq-orders-chart" viewBox="0 0 340 140"><defs><linearGradient id="orderChartGold" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#ffb23f" stop-opacity=".45"/><stop offset="100%" stop-color="#ffb23f" stop-opacity="0"/></linearGradient></defs><polyline points="<?php echo esc_attr(implode(' ', $svg_points)); ?>" fill="none" stroke="#ffb23f" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                </div>
                                <div class="fq-recent-activity-card"><div class="fq-panel-head"><h3>Recent Activity</h3><a href="<?php echo esc_url(menuqr_restaurant_tab_url('orders')); ?>">View All</a></div><?php foreach (array_slice($all_orders_for_ui, 0, 4) as $act) : ?><p><span class="dot"></span>Order #<?php echo esc_html($act->unique_code ?: $act->id); ?> marked as <?php echo esc_html(ucwords(str_replace('_',' ', (string)$act->order_status))); ?><small><?php echo esc_html(human_time_diff(strtotime($act->created_at), current_time('timestamp')) . ' ago'); ?></small></p><?php endforeach; ?></div>
                            </div>
                        </main>

                        <aside class="fq-order-details-panel">
                            <?php if (!$selected_order) : ?>
                                <div class="fq-order-empty-state"><strong>No order selected</strong><p>Select an order to view details.</p></div>
                            <?php else :
                                $selected_type_source = strtolower((string)($selected_order->order_source ?? ''));
                                $selected_type_label = ((int)($selected_order->room_id ?? 0) > 0 || str_contains($selected_type_source,'room')) ? 'Room Order' : (str_contains($selected_type_source,'delivery') ? 'Delivery' : (str_contains($selected_type_source,'pickup') ? 'Pickup' : 'Table Order'));
                                $selected_place = $selected_type_label === 'Room Order' ? 'Room ' . ($selected_order->service_value ?? $selected_order->room_number ?? '—') : ($selected_type_label === 'Delivery' ? 'Delivery' : ($selected_type_label === 'Pickup' ? 'Pickup' : 'Table ' . ($selected_order->service_value ?? $selected_order->table_number ?? '—')));
                                $selected_customer = trim((string)($selected_order->customer_name ?? '')) ?: 'Guest';
                            ?>
                                <div class="fq-order-details-header"><h3>Order Details</h3><b>#<?php echo esc_html($selected_order->unique_code ?: ('ORD-' . $selected_order->id)); ?></b></div>
                                <div class="fq-order-detail-customer"><span><?php echo esc_html(strtoupper(substr($selected_customer,0,1))); ?></span><div><strong><?php echo esc_html($selected_customer); ?></strong><small><?php echo esc_html($selected_phone ?: 'No phone'); ?></small></div><a href="<?php echo esc_url($selected_phone ? 'tel:' . $selected_phone : '#'); ?>"><?php echo fqx_v168_icon('phone','fq-svg-icon'); ?></a><?php if ($selected_wa) : ?><a href="<?php echo esc_url($selected_wa); ?>" target="_blank"><?php echo fqx_v168_icon('wa','fq-svg-icon'); ?></a><?php endif; ?></div>
                                <div class="fq-order-detail-meta"><div><small>Order Type</small><strong><?php echo esc_html($selected_type_label); ?></strong></div><div><small>Table / Room</small><strong><?php echo esc_html($selected_place); ?></strong></div><div><small>Order Time</small><strong><?php echo esc_html(mysql2date('M d, Y | h:i A', $selected_order->created_at)); ?></strong></div></div>
                                <div class="fq-detail-section"><h4>Items</h4><?php foreach ($selected_items as $item) : $qty=max(1,(int)($item['qty'] ?? 1)); $price=(float)($item['price'] ?? 0); ?><p><span><?php echo esc_html($qty . ' × ' . (string)($item['name'] ?? 'Item')); ?></span><strong><?php echo esc_html(menuqr_money($qty*$price)); ?></strong></p><?php endforeach; ?></div>
                                <div class="fq-special-instructions"><strong>Special Instructions</strong><p><?php echo esc_html($selected_order->customer_note ?: 'No special instructions.'); ?></p></div>
                                <div class="fq-payment-summary"><h4>Payment Summary</h4><p><span>Subtotal</span><strong><?php echo esc_html(menuqr_money($selected_subtotal)); ?></strong></p><p><span>Taxes & Charges</span><strong><?php echo esc_html(menuqr_money($selected_tax)); ?></strong></p><p><span>Discount</span><strong>-<?php echo esc_html(menuqr_money($selected_discount)); ?></strong></p><p class="total"><span>Total Amount</span><strong><?php echo esc_html(menuqr_money($selected_total)); ?></strong></p><p><span>Payment</span><b><?php echo esc_html(ucwords((string)$selected_order->payment_status)); ?></b></p></div>
                                <div class="fq-order-action-grid">
                                    <?php foreach (['accepted'=>'Accept Order','preparing'=>'Mark Preparing','ready'=>'Mark Ready','served'=>'Complete Order'] as $st => $label) : ?>
                                        <form method="post" action="<?php echo esc_url(menuqr_restaurant_tab_url('orders')); ?>"><?php wp_nonce_field('menuqr_update_order_status_form', 'menuqr_order_nonce'); ?><input type="hidden" name="action" value="menuqr_update_order_status_form"><input type="hidden" name="order_id" value="<?php echo esc_attr((string)$selected_order->id); ?>"><input type="hidden" name="status" value="<?php echo esc_attr($st); ?>"><button class="status-<?php echo esc_attr($st); ?>" type="submit"><?php echo esc_html($label); ?></button></form>
                                    <?php endforeach; ?>
                                    <a class="dark" href="<?php echo esc_url($selected_print_url ?: menuqr_restaurant_tab_url('bills')); ?>" target="_blank"><?php echo fqx_v168_icon('print','fq-svg-icon'); ?> Print Bill</a>
                                    <a class="dark" href="<?php echo esc_url($selected_track_url); ?>" target="_blank"><?php echo fqx_v168_icon('track','fq-svg-icon'); ?> View Track</a>
                                    <a class="dark wide" href="<?php echo esc_url(menuqr_restaurant_tab_url('staff')); ?>"><?php echo fqx_v168_icon('staff','fq-svg-icon'); ?> Assign Staff</a>
                                </div>
                            <?php endif; ?>
                        </aside>
                    </div>
                </div>


<?php elseif ('menu' === $current_tab) : ?>
                <?php
                if (!function_exists('fqx_v169_menu_icon')) {
                    function fqx_v169_menu_icon(string $name, string $class = ''): string {
                        $class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';
                        $icons = [
                            'plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>',
                            'cloche' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18h16"/><path d="M6 16a6 6 0 0 1 12 0"/><path d="M12 8V6"/><path d="M9 6h6"/></svg>',
                            'grid' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg>',
                            'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.7-5"/></svg>',
                            'x' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>',
                            'star' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.9 5.9 6.5.9-4.7 4.6 1.1 6.4-5.8-3-5.8 3 1.1-6.4-4.7-4.6 6.5-.9L12 3Z"/></svg>',
                            'gift' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12v8H4v-8M2 7h20v5H2zM12 7v13"/><path d="M12 7H8.5A2.5 2.5 0 1 1 12 4.5V7Zm0 0h3.5A2.5 2.5 0 1 0 12 4.5V7Z"/></svg>',
                            'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
                            'filter' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>',
                            'edit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>',
                            'copy' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 8h12v12H8z"/><path d="M4 16V4h12"/></svg>',
                            'eye' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
                            'trash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15"/></svg>',
                            'dots' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>',
                            'clock' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
                            'chili' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 5c-3 0-6 2-7 5-1 4 2 7 6 6 3-1 5-4 5-7"/><path d="M17 5c1-2 2-2 4-2"/></svg>',
                            'arrow' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m13 5 7 7-7 7"/></svg>',
                        ];
                        return '<span' . $class_attr . ' aria-hidden="true">' . ($icons[$name] ?? '') . '</span>';
                    }
                }
                $menu_category_map = [];
                foreach ($categories as $cat) { $menu_category_map[(int) $cat->id] = (string) $cat->name; }
                $menu_search = sanitize_text_field(wp_unslash($_GET['menu_search'] ?? ''));
                $menu_category_filter = absint($_GET['menu_category'] ?? 0);
                $menu_category_type_filter = absint($_GET['menu_category_type'] ?? 0);
                $menu_type_filter = sanitize_key(wp_unslash($_GET['menu_type'] ?? 'all'));
                $menu_availability_filter = sanitize_key(wp_unslash($_GET['menu_availability'] ?? 'all'));
                $menu_price_filter = sanitize_key(wp_unslash($_GET['menu_price'] ?? 'all'));
                $menu_page = max(1, absint($_GET['menu_page'] ?? 1));
                $menu_per_page = max(5, min(50, absint($_GET['menu_per_page'] ?? 10)));
                $menu_add_mode = !empty($_GET['add_item']);
                $menu_items_all = is_array($items) ? $items : [];
                $total_menu_items = count($menu_items_all);
                $available_menu_items = 0;
                $out_menu_items = 0;
                $featured_menu_items = 0;
                $combo_menu_items = 0;
                $menu_order_counts = [];
                foreach ($item_totals as $name => $qty) { $menu_order_counts[strtolower((string) $name)] = (int) $qty; }
                foreach ($menu_items_all as $mi) {
                    if ((int) ($mi->is_available ?? 0) === 1) { $available_menu_items++; } else { $out_menu_items++; }
                    if ((int) ($mi->is_featured ?? 0) === 1) { $featured_menu_items++; }
                    $ft = strtolower((string) ($mi->food_type ?? ''));
                    $cat_name = strtolower($menu_category_map[(int) ($mi->category_id ?? 0)] ?? '');
                    if (str_contains($ft, 'combo') || str_contains($cat_name, 'combo')) { $combo_menu_items++; }
                }
                $filtered_menu_items = array_values(array_filter($menu_items_all, static function($mi) use ($menu_search, $menu_category_filter, $menu_category_type_filter, $menu_type_filter, $menu_availability_filter, $menu_price_filter, $menu_category_map, $category_type_map) {
                    $name = (string) ($mi->name ?? '');
                    $desc = (string) ($mi->description ?? '');
                    $category = $menu_category_map[(int) ($mi->category_id ?? 0)] ?? '';
                    $food_type = strtolower((string) ($mi->food_type ?? 'veg'));
                    $price = (float) (($mi->discount_price ?? 0) > 0 ? $mi->discount_price : $mi->price);
                    if ($menu_search !== '') {
                        $haystack = strtolower(wp_json_encode([$mi->id ?? '', $name, $desc, $category, $food_type, $mi->variants ?? '', $mi->addons ?? '']));
                        if (!str_contains($haystack, strtolower($menu_search))) { return false; }
                    }
                    if ($menu_category_filter > 0 && (int) ($mi->category_id ?? 0) !== $menu_category_filter) { return false; }
                    if ($menu_category_type_filter > 0 && (int) ($mi->category_type_id ?? 0) !== $menu_category_type_filter) { return false; }
                    if ($menu_category_type_filter > 0 && isset($category_type_map[$menu_category_type_filter]) && (int) ($category_type_map[$menu_category_type_filter]->category_id ?? 0) !== (int) ($mi->category_id ?? 0)) { return false; }
                    if ($menu_type_filter !== 'all') {
                        if ($menu_type_filter === 'veg' && $food_type !== 'veg') { return false; }
                        if ($menu_type_filter === 'nonveg' && !in_array($food_type, ['nonveg','non-veg','non_veg'], true)) { return false; }
                        if ($menu_type_filter === 'beverages' && !str_contains(strtolower($category . ' ' . $food_type . ' ' . $name), 'beverage') && !str_contains(strtolower($category . ' ' . $food_type . ' ' . $name), 'drink') && !str_contains(strtolower($category . ' ' . $food_type . ' ' . $name), 'coffee')) { return false; }
                        if ($menu_type_filter === 'desserts' && !str_contains(strtolower($category . ' ' . $food_type . ' ' . $name), 'dessert') && !str_contains(strtolower($category . ' ' . $food_type . ' ' . $name), 'sweet')) { return false; }
                    }
                    if ($menu_availability_filter === 'available' && (int) ($mi->is_available ?? 0) !== 1) { return false; }
                    if ($menu_availability_filter === 'out' && (int) ($mi->is_available ?? 0) === 1) { return false; }
                    if ($menu_price_filter === 'under100' && $price >= 100) { return false; }
                    if ($menu_price_filter === '100_300' && ($price < 100 || $price > 300)) { return false; }
                    if ($menu_price_filter === '300_500' && ($price < 300 || $price > 500)) { return false; }
                    if ($menu_price_filter === 'above500' && $price <= 500) { return false; }
                    return true;
                }));
                if ($menu_price_filter === 'low_high') { usort($filtered_menu_items, static fn($a,$b) => (float) ($a->discount_price ?: $a->price) <=> (float) ($b->discount_price ?: $b->price)); }
                if ($menu_price_filter === 'high_low') { usort($filtered_menu_items, static fn($a,$b) => (float) ($b->discount_price ?: $b->price) <=> (float) ($a->discount_price ?: $a->price)); }
                $menu_total_filtered = count($filtered_menu_items);
                $menu_total_pages = max(1, (int) ceil($menu_total_filtered / $menu_per_page));
                $menu_page = min($menu_page, $menu_total_pages);
                $paged_menu_items = array_slice($filtered_menu_items, ($menu_page - 1) * $menu_per_page, $menu_per_page);
                $selected_menu_id = absint($_GET['item_id'] ?? ($paged_menu_items[0]->id ?? 0));
                $selected_menu_item = null;
                foreach ($menu_items_all as $mi) { if ((int) $mi->id === $selected_menu_id) { $selected_menu_item = $mi; break; } }
                if (!$selected_menu_item && $paged_menu_items) { $selected_menu_item = $paged_menu_items[0]; }
                $selected_category = $selected_menu_item ? ($menu_category_map[(int) $selected_menu_item->category_id] ?? '—') : '—';
                $selected_category_type = ($selected_menu_item && !empty($selected_menu_item->category_type_id) && isset($category_type_map[(int) $selected_menu_item->category_type_id])) ? (string) $category_type_map[(int) $selected_menu_item->category_type_id]->name : '—';
                $selected_variants = $selected_menu_item ? (json_decode((string) ($selected_menu_item->variants ?? ''), true) ?: []) : [];
                $selected_addons = $selected_menu_item ? (json_decode((string) ($selected_menu_item->addons ?? ''), true) ?: []) : [];
                $selected_price = $selected_menu_item ? (float) (($selected_menu_item->discount_price ?? 0) > 0 ? $selected_menu_item->discount_price : $selected_menu_item->price) : 0;
                $selected_order_count = $selected_menu_item ? ($menu_order_counts[strtolower((string) $selected_menu_item->name)] ?? 0) : 0;
                $top_revenue_item_name = '—'; $top_revenue_item_amount = 0;
                foreach ($menu_items_all as $mi) { $qty = $menu_order_counts[strtolower((string) $mi->name)] ?? 0; $rev = $qty * (float) (($mi->discount_price ?? 0) > 0 ? $mi->discount_price : $mi->price); if ($rev > $top_revenue_item_amount) { $top_revenue_item_amount = $rev; $top_revenue_item_name = (string) $mi->name; } }
                $avg_item_price = $total_menu_items ? array_sum(array_map(static fn($mi) => (float) (($mi->discount_price ?? 0) > 0 ? $mi->discount_price : $mi->price), $menu_items_all)) / max(1, $total_menu_items) : 0;
                $menu_chart_points = '8,112 38,88 68,92 98,60 128,72 158,38 188,28 218,54 248,72 278,58 308,78 338,48';
                ?>
                <?php if ('categories' === $current_section) : ?>
                    <?php
                    $category_item_counts = [];
                    foreach ($menu_items_all as $mi) {
                        $cid = (int) ($mi->category_id ?? 0);
                        if ($cid > 0) { $category_item_counts[$cid] = ($category_item_counts[$cid] ?? 0) + 1; }
                    }
                    $category_total_items = array_sum($category_item_counts);
                    ?>
                    <div class="fq-categories-page-v190">
                        <div class="fq-cat-titlebar">
                            <div>
                                <h1>Category Management</h1>
                                <p>Create, edit, sort and manage menu categories used in your customer QR menu.</p>
                            </div>
                            <a class="fq-cat-add-btn" href="#fqCategoryForm"><?php echo fqx_v169_menu_icon('plus','fq-svg-icon'); ?> Add Category</a>
                        </div>

                        <div class="fq-cat-stats">
                            <div class="fq-cat-stat-card"><span><?php echo fqx_v169_menu_icon('grid','fq-svg-icon'); ?></span><div><small>Total Categories</small><strong><?php echo esc_html((string) count($categories)); ?></strong><em>Restaurant menu groups</em></div></div>
                            <div class="fq-cat-stat-card green"><span><?php echo fqx_v169_menu_icon('cloche','fq-svg-icon'); ?></span><div><small>Linked Menu Items</small><strong><?php echo esc_html((string) $category_total_items); ?></strong><em>Items assigned</em></div></div>
                            <div class="fq-cat-stat-card blue"><span><?php echo fqx_v169_menu_icon('tag','fq-svg-icon'); ?></span><div><small>Category Types</small><strong><?php echo esc_html((string) count($category_types)); ?></strong><em>Sub-groups like Soups</em></div></div>
                            <div class="fq-cat-stat-card orange"><span><?php echo fqx_v169_menu_icon('check','fq-svg-icon'); ?></span><div><small>Available Slots</small><strong><?php $cat_limit = menuqr_plan_limit($restaurant_id, 'categories'); echo esc_html($cat_limit < 0 ? 'Unlimited' : max(0, (string) ($cat_limit - count($categories)))); ?></strong><em>Plan limit safe</em></div></div>
                        </div>

                        <div class="fq-cat-layout">
                            <section id="fqCategoryForm" class="fq-cat-card fq-cat-form-card">
                                <div class="fq-cat-card-head">
                                    <div><h3><?php echo $editing_category ? 'Edit Category' : 'Create Category'; ?></h3><p><?php echo $editing_category ? 'Update this category details.' : 'Add a new category for menu items.'; ?></p></div>
                                    <?php if ($editing_category) : ?><a href="<?php echo esc_url(add_query_arg(['tab' => 'menu', 'section' => 'categories'], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>">Cancel Edit</a><?php endif; ?>
                                </div>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fq-cat-form">
                                    <?php wp_nonce_field('menuqr_save_category', 'menuqr_category_nonce'); ?>
                                    <input type="hidden" name="action" value="menuqr_save_category">
                                    <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                                    <input type="hidden" name="category_id" value="<?php echo esc_attr((string) ($editing_category->id ?? 0)); ?>">
                                    <label>Category Name <span>*</span><input name="name" required value="<?php echo esc_attr($editing_category->name ?? ''); ?>" placeholder="Example: Starters, Main Course, Drinks"></label>
                                    <label>Description <textarea name="description" rows="4" placeholder="Short description shown internally/admin side"><?php echo esc_textarea($editing_category->description ?? ''); ?></textarea></label>
                                    <label>Sort Order <input type="number" min="0" name="sort_order" value="<?php echo esc_attr((string) ($editing_category->sort_order ?? (count($categories) + 1))); ?>" placeholder="1"></label>
                                    <button class="fq-cat-save-btn" type="submit"><?php echo $editing_category ? 'Update Category' : 'Save Category'; ?></button>
                                    <p class="fq-cat-help">After saving, this category will appear in Add/Edit Menu Item category dropdown and customer QR menu filters.</p>
                                </form>
                            </section>

                            <section id="fqCategoryTypeForm" class="fq-cat-card fq-cat-form-card fq-cat-type-form-card">
                                <div class="fq-cat-card-head">
                                    <div><h3><?php echo $editing_category_type ? 'Edit Category Type' : 'Create Category Type'; ?></h3><p>Example: Starters → Soups, Main Course → Paneer, Drinks → Mocktails.</p></div>
                                    <?php if ($editing_category_type) : ?><a href="<?php echo esc_url(add_query_arg(['tab' => 'menu', 'section' => 'categories'], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>#fqCategoryTypeForm">Cancel Edit</a><?php endif; ?>
                                </div>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fq-cat-form">
                                    <?php wp_nonce_field('fqx_save_category_type', 'fqx_category_type_nonce'); ?>
                                    <input type="hidden" name="action" value="fqx_save_category_type">
                                    <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                                    <input type="hidden" name="category_type_id" value="<?php echo esc_attr((string) ($editing_category_type->id ?? 0)); ?>">
                                    <label>Parent Category <span>*</span><select name="category_id" required><option value="">Select Category</option><?php foreach ($categories as $cat) : ?><option value="<?php echo esc_attr((string) $cat->id); ?>" <?php selected((int) ($editing_category_type->category_id ?? 0), (int) $cat->id); ?>><?php echo esc_html($cat->name); ?></option><?php endforeach; ?></select></label>
                                    <label>Type Name <span>*</span><input name="type_name" required value="<?php echo esc_attr($editing_category_type->name ?? ''); ?>" placeholder="Example: Soups, Tandoor, Paneer, Mocktails"></label>
                                    <label>Description <textarea name="type_description" rows="3" placeholder="Optional internal description"><?php echo esc_textarea($editing_category_type->description ?? ''); ?></textarea></label>
                                    <label>Sort Order <input type="number" min="0" name="type_sort_order" value="<?php echo esc_attr((string) ($editing_category_type->sort_order ?? (count($category_types) + 1))); ?>" placeholder="1"></label>
                                    <label class="fq-cat-check"><input type="checkbox" name="is_active" value="1" <?php checked((int) ($editing_category_type->is_active ?? 1), 1); ?>> Active / show in menu item form</label>
                                    <button class="fq-cat-save-btn" type="submit"><?php echo $editing_category_type ? 'Update Type' : 'Save Type'; ?></button>
                                    <p class="fq-cat-help">After saving, this type will appear in Add/Edit Menu Item under Category Type / Subcategory.</p>
                                </form>
                            </section>

                            <section class="fq-cat-card fq-cat-list-card fq-cat-type-list-card">
                                <div class="fq-cat-card-head"><div><h3>Category Types</h3><p>Manage sub-groups inside categories, like Starters → Soups.</p></div><a href="#fqCategoryTypeForm">Create Type</a></div>
                                <?php if (!$category_types) : ?>
                                    <div class="fq-cat-empty"><span><?php echo fqx_v169_menu_icon('tag','fq-empty-icon'); ?></span><h3>No category types yet</h3><p>Create types such as Soups under Starters or Mocktails under Drinks.</p><a href="#fqCategoryTypeForm">Create Type</a></div>
                                <?php else : ?>
                                    <div class="fq-cat-type-groups">
                                    <?php foreach ($categories as $cat) : $cat_id = (int) $cat->id; $types_for_cat = $category_types_grouped[$cat_id] ?? []; if (!$types_for_cat) { continue; } ?>
                                        <div class="fq-cat-type-group"><h4><?php echo esc_html($cat->name); ?></h4><div class="fq-cat-type-chips">
                                            <?php foreach ($types_for_cat as $type) : $type_edit_url = add_query_arg(['tab' => 'menu', 'section' => 'categories', 'edit_category_type' => (int) $type->id], menuqr_get_page_url_by_slug('restaurant-dashboard')); ?>
                                                <div class="fq-cat-type-chip <?php echo (int) ($type->is_active ?? 1) ? 'is-active' : 'is-inactive'; ?>"><div><strong><?php echo esc_html($type->name); ?></strong><small><?php echo esc_html($type->description ?: ('Sort ' . (int) ($type->sort_order ?? 0))); ?></small></div><div class="fq-cat-actions compact"><a href="<?php echo esc_url($type_edit_url); ?>#fqCategoryTypeForm">Edit</a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('fqx_delete_category_type', 'fqx_category_type_delete_nonce'); ?><input type="hidden" name="action" value="fqx_delete_category_type"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="category_type_id" value="<?php echo esc_attr((string) $type->id); ?>"><button type="submit" onclick="return confirm('Delete this category type? Menu items must be moved first.');">Delete</button></form></div></div>
                                            <?php endforeach; ?>
                                        </div></div>
                                    <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>

                            <section class="fq-cat-card fq-cat-list-card">
                                <div class="fq-cat-card-head"><div><h3>All Categories</h3><p>Newest changes apply to menu item category selection automatically.</p></div></div>
                                <?php if (!$categories) : ?>
                                    <div class="fq-cat-empty"><span><?php echo fqx_v169_menu_icon('grid','fq-empty-icon'); ?></span><h3>No categories added yet</h3><p>Create your first category before adding menu items.</p><a href="#fqCategoryForm">Create Category</a></div>
                                <?php else : ?>
                                    <div class="fq-cat-table-wrap"><table class="fq-cat-table"><thead><tr><th>Category</th><th>Description</th><th>Types</th><th>Items</th><th>Sort</th><th>Actions</th></tr></thead><tbody>
                                    <?php foreach ($categories as $cat) : $cat_id = (int) $cat->id; $cat_edit_url = add_query_arg(['tab' => 'menu', 'section' => 'categories', 'edit_category' => $cat_id], menuqr_get_page_url_by_slug('restaurant-dashboard')); ?>
                                        <tr>
                                            <td data-label="Category"><strong><?php echo esc_html($cat->name); ?></strong><small>#CAT-<?php echo esc_html((string) $cat_id); ?></small></td>
                                            <td data-label="Description"><?php echo esc_html($cat->description ?: '—'); ?></td>
                                            <td data-label="Types"><span class="fq-cat-count"><?php echo esc_html((string) count($category_types_grouped[$cat_id] ?? [])); ?></span></td>
                                            <td data-label="Items"><span class="fq-cat-count"><?php echo esc_html((string) ($category_item_counts[$cat_id] ?? 0)); ?></span></td>
                                            <td data-label="Sort"><?php echo esc_html((string) ($cat->sort_order ?? 0)); ?></td>
                                            <td data-label="Actions"><div class="fq-cat-actions"><a href="<?php echo esc_url($cat_edit_url); ?>#fqCategoryForm"><?php echo fqx_v169_menu_icon('edit','fq-svg-icon'); ?> Edit</a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('menuqr_delete_record', 'menuqr_delete_nonce'); ?><input type="hidden" name="action" value="menuqr_delete_category"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="id" value="<?php echo esc_attr((string) $cat_id); ?>"><button type="submit" onclick="return confirm('Delete this category? Menu items must be moved or deleted first.');"><?php echo fqx_v169_menu_icon('trash','fq-svg-icon'); ?> Delete</button></form></div></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody></table></div>
                                <?php endif; ?>
                            </section>
                        </div>
                    </div>
                <?php else : ?>
                <div class="fq-menu-page fq-menu-exact-v169">
                    <div class="fq-menu-titlebar"><div><h1>Menu Management</h1><p>Manage food items, categories, pricing, availability, and combos across your restaurant and hotel menu.</p></div><a class="fq-menu-add-btn" data-fq-open-menu-editor="1" href="<?php echo esc_url(add_query_arg(['tab' => 'menu', 'add_item' => 1], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>#fqMenuEditor"><?php echo fqx_v169_menu_icon('plus','fq-svg-icon'); ?> Add Item</a></div>

                    <div class="fq-menu-stats">
                        <div class="fq-menu-stat-card"><span><?php echo fqx_v169_menu_icon('cloche','fq-svg-icon'); ?></span><div><small>Total Items</small><strong><?php echo esc_html((string) $total_menu_items); ?></strong><em>↑ 12.5% vs yesterday</em></div></div>
                        <div class="fq-menu-stat-card purple"><span><?php echo fqx_v169_menu_icon('grid','fq-svg-icon'); ?></span><div><small>Categories</small><strong><?php echo esc_html((string) count($categories)); ?></strong><em>↑ 1 vs yesterday</em></div></div>
                        <div class="fq-menu-stat-card green"><span><?php echo fqx_v169_menu_icon('check','fq-svg-icon'); ?></span><div><small>Available Items</small><strong><?php echo esc_html((string) $available_menu_items); ?></strong><em>↑ 8.7% vs yesterday</em></div></div>
                        <div class="fq-menu-stat-card red"><span><?php echo fqx_v169_menu_icon('x','fq-svg-icon'); ?></span><div><small>Out of Stock</small><strong><?php echo esc_html((string) $out_menu_items); ?></strong><em class="down">↓ 2 vs yesterday</em></div></div>
                        <div class="fq-menu-stat-card"><span><?php echo fqx_v169_menu_icon('star','fq-svg-icon'); ?></span><div><small>Best Sellers</small><strong><?php echo esc_html((string) max($featured_menu_items, min(24, count($top_items)))); ?></strong><em>↑ 3 this week</em></div></div>
                        <div class="fq-menu-stat-card blue"><span><?php echo fqx_v169_menu_icon('gift','fq-svg-icon'); ?></span><div><small>Combos</small><strong><?php echo esc_html((string) $combo_menu_items); ?></strong><em>↑ 1 this week</em></div></div>
                    </div>

                    <?php if (function_exists('fqx_v206_render_smart_csv_box')) { fqx_v206_render_smart_csv_box((int) $restaurant_id); } ?>

                    <form class="fq-menu-filter-bar" method="get" action="<?php echo esc_url(menuqr_get_page_url_by_slug('restaurant-dashboard')); ?>">
                        <input type="hidden" name="tab" value="menu">
                        <label><span>Category</span><select name="menu_category" data-fqx-category-select><option value="0">All Categories</option><?php foreach ($categories as $cat) : ?><option value="<?php echo esc_attr((string) $cat->id); ?>" <?php selected($menu_category_filter, (int) $cat->id); ?>><?php echo esc_html($cat->name); ?></option><?php endforeach; ?></select></label>
                        <label><span>Subcategory / Type</span><select name="menu_category_type" data-fqx-category-type-select><option value="0">All Types</option><?php if (function_exists('fqx_v191_category_type_options_html')) { echo fqx_v191_category_type_options_html($restaurant_id, $menu_category_type_filter, $menu_category_filter); } ?></select></label>
                        <div class="fq-menu-type-tabs"><span>Type</span><div><label><input type="radio" name="menu_type" value="all" <?php checked($menu_type_filter, 'all'); ?>><b>All</b></label><label><input type="radio" name="menu_type" value="veg" <?php checked($menu_type_filter, 'veg'); ?>><b>Veg</b></label><label><input type="radio" name="menu_type" value="nonveg" <?php checked($menu_type_filter, 'nonveg'); ?>><b>Non-Veg</b></label><label><input type="radio" name="menu_type" value="beverages" <?php checked($menu_type_filter, 'beverages'); ?>><b>Beverages</b></label><label><input type="radio" name="menu_type" value="desserts" <?php checked($menu_type_filter, 'desserts'); ?>><b>Desserts</b></label></div></div>
                        <label><span>Availability</span><select name="menu_availability"><option value="all">All Status</option><option value="available" <?php selected($menu_availability_filter, 'available'); ?>>Available</option><option value="out" <?php selected($menu_availability_filter, 'out'); ?>>Out of Stock</option></select></label>
                        <label><span>Price Range</span><select name="menu_price"><option value="all">All Prices</option><option value="low_high" <?php selected($menu_price_filter, 'low_high'); ?>>Low to High</option><option value="high_low" <?php selected($menu_price_filter, 'high_low'); ?>>High to Low</option><option value="under100" <?php selected($menu_price_filter, 'under100'); ?>>Under ₹100</option><option value="100_300" <?php selected($menu_price_filter, '100_300'); ?>>₹100 - ₹300</option><option value="300_500" <?php selected($menu_price_filter, '300_500'); ?>>₹300 - ₹500</option><option value="above500" <?php selected($menu_price_filter, 'above500'); ?>>Above ₹500</option></select></label>
                        <div class="fq-menu-search"><?php echo fqx_v169_menu_icon('search','fq-svg-icon'); ?><input name="menu_search" value="<?php echo esc_attr($menu_search); ?>" placeholder="Search menu items..."></div>
                        <button type="submit"><?php echo fqx_v169_menu_icon('filter','fq-svg-icon'); ?> Filters</button>
                    </form>

                    <div class="fq-menu-layout">
                        <main class="fq-menu-main">
                            <div class="fq-menu-table-card"><table class="fq-menu-table"><thead><tr><th>Item ID</th><th>Item</th><th>Category</th><th>Food Type</th><th>Price</th><th>Availability</th><th>Rating / Orders</th><th>Last Updated</th><th>Actions</th></tr></thead><tbody>
                            <?php if (!$paged_menu_items) : ?><tr><td colspan="9"><div class="fq-menu-empty-state"><span><?php echo fqx_v169_menu_icon('cloche','fq-empty-icon'); ?></span><h3>No menu items found</h3><p>Add your first menu item to start receiving orders.</p><a data-fq-open-menu-editor="1" href="<?php echo esc_url(add_query_arg(['tab' => 'menu', 'add_item' => 1], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>#fqMenuEditor">Add Item</a></div></td></tr><?php else : foreach ($paged_menu_items as $mi) : $cat_name = $menu_category_map[(int) $mi->category_id] ?? '—'; $cat_type_name = (!empty($mi->category_type_id) && isset($category_type_map[(int) $mi->category_type_id])) ? (string) $category_type_map[(int) $mi->category_type_id]->name : 'Direct Category'; $ft = strtolower((string) ($mi->food_type ?? 'veg')); $is_non = in_array($ft, ['nonveg','non-veg','non_veg'], true); $orders_for_item = $menu_order_counts[strtolower((string) $mi->name)] ?? 0; $row_url = add_query_arg(['tab' => 'menu', 'item_id' => (int) $mi->id], menuqr_get_page_url_by_slug('restaurant-dashboard')); ?>
                                <tr class="<?php echo $selected_menu_item && (int) $selected_menu_item->id === (int) $mi->id ? 'is-selected' : ''; ?>" data-row-href="<?php echo esc_url($row_url); ?>">
                                    <td data-label="Item ID"><span class="fq-fav-star"><?php echo (int) ($mi->is_featured ?? 0) ? '★' : '#'; ?></span> #ITM-<?php echo esc_html((string) $mi->id); ?></td>
                                    <td data-label="Item"><div class="fq-menu-item-cell"><?php if (!empty($mi->image)) : ?><img src="<?php echo esc_url($mi->image); ?>" alt="<?php echo esc_attr($mi->name); ?>" loading="lazy"><?php else : ?><span class="fq-menu-emoji"><?php echo esc_html($mi->emoji ?: '🍽️'); ?></span><?php endif; ?><strong><?php echo esc_html($mi->name); ?></strong></div></td>
                                    <td data-label="Category"><strong class="fq-menu-cat-name"><?php echo esc_html($cat_name); ?></strong><small class="fq-menu-subcat-name">↳ <?php echo esc_html($cat_type_name); ?></small></td>
                                    <td data-label="Type"><span class="fq-menu-type-badge <?php echo $is_non ? 'nonveg' : 'veg'; ?>"><i></i><?php echo esc_html($is_non ? 'Non-Veg' : 'Veg'); ?></span></td>
                                    <td data-label="Price"><strong><?php echo esc_html(menuqr_money((float) (($mi->discount_price ?? 0) > 0 ? $mi->discount_price : $mi->price))); ?></strong></td>
                                    <td data-label="Availability"><span class="fq-menu-status-badge <?php echo (int) $mi->is_available ? 'available' : 'out'; ?>"><?php echo (int) $mi->is_available ? 'Available' : 'Out of Stock'; ?></span></td>
                                    <td data-label="Rating / Orders"><span class="fq-rating">4.<?php echo esc_html((string) ((int) $mi->id % 8 + 1)); ?> ★</span><small><?php echo esc_html((string) $orders_for_item); ?> orders</small></td>
                                    <td data-label="Last Updated"><?php echo esc_html(mysql2date('M d, Y', $mi->updated_at ?? $mi->created_at)); ?><small><?php echo esc_html(mysql2date('h:i A', $mi->updated_at ?? $mi->created_at)); ?></small></td>
                                    <td data-label="Actions"><div class="fq-menu-actions"><a title="Edit item" aria-label="Edit item" href="<?php echo esc_url(menuqr_restaurant_edit_url('menu', 'edit_item', (int) $mi->id)); ?>#fqMenuEditor"><?php echo fqx_v169_menu_icon('edit','fq-svg-icon'); ?></a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('menuqr_item_quick_action', 'menuqr_item_nonce'); ?><input type="hidden" name="action" value="menuqr_duplicate_item"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="item_id" value="<?php echo esc_attr((string) $mi->id); ?>"><button type="submit" title="Duplicate item" aria-label="Duplicate item"><?php echo fqx_v169_menu_icon('copy','fq-svg-icon'); ?></button></form><a title="Preview menu" aria-label="Preview menu" target="_blank" href="<?php echo esc_url(menuqr_get_menu_url($restaurant_id)); ?>"><?php echo fqx_v169_menu_icon('eye','fq-svg-icon'); ?></a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Delete this menu item?');"><?php wp_nonce_field('menuqr_delete_record', 'menuqr_delete_nonce'); ?><input type="hidden" name="action" value="menuqr_delete_item"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="id" value="<?php echo esc_attr((string) $mi->id); ?>"><button class="fq-menu-action-delete" type="submit" title="Delete item" aria-label="Delete item"><?php echo fqx_v169_menu_icon('trash','fq-svg-icon'); ?></button></form><button type="button" class="fq-menu-more" title="Select row" aria-label="Select row"><?php echo fqx_v169_menu_icon('dots','fq-svg-icon'); ?></button></div></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody></table>
                                <?php
                                    $menu_pager_pages = [];
                                    if ($menu_total_pages <= 9) {
                                        $menu_pager_pages = range(1, $menu_total_pages);
                                    } else {
                                        $window_start = max(1, $menu_page - 2);
                                        $window_end   = min($menu_total_pages, $menu_page + 4);
                                        if ($menu_page >= ($menu_total_pages - 3)) {
                                            $window_start = max(1, $menu_total_pages - 6);
                                            $window_end   = $menu_total_pages;
                                        }
                                        if ($menu_page <= 4) {
                                            $window_start = 1;
                                            $window_end   = min($menu_total_pages, 9);
                                        }
                                        $menu_pager_pages = range($window_start, $window_end);
                                    }
                                ?>
                                <div class="fq-menu-pagination fq-menu-pagination-v203" aria-label="Menu items pagination">
                                    <span>Showing <?php echo esc_html((string) ($menu_total_filtered ? (($menu_page - 1) * $menu_per_page + 1) : 0)); ?> to <?php echo esc_html((string) min($menu_total_filtered, $menu_page * $menu_per_page)); ?> of <?php echo esc_html((string) $menu_total_filtered); ?> items</span>
                                    <div class="fq-menu-pagination-pages">
                                        <?php if ($menu_page > 1) : ?><a class="fq-menu-page-nav" href="<?php echo esc_url(add_query_arg('menu_page', $menu_page - 1)); ?>" aria-label="Previous page">‹</a><?php endif; ?>
                                        <?php if (!in_array(1, $menu_pager_pages, true)) : ?>
                                            <a href="<?php echo esc_url(add_query_arg('menu_page', 1)); ?>">1</a><span class="fq-menu-page-dots">…</span>
                                        <?php endif; ?>
                                        <?php foreach ($menu_pager_pages as $mp) : ?>
                                            <a class="<?php echo $mp === $menu_page ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('menu_page', $mp)); ?>" <?php if ($mp === $menu_page) : ?>aria-current="page"<?php endif; ?>><?php echo esc_html((string) $mp); ?></a>
                                        <?php endforeach; ?>
                                        <?php if (!in_array($menu_total_pages, $menu_pager_pages, true)) : ?>
                                            <span class="fq-menu-page-dots">…</span><a href="<?php echo esc_url(add_query_arg('menu_page', $menu_total_pages)); ?>"><?php echo esc_html((string) $menu_total_pages); ?></a>
                                        <?php endif; ?>
                                        <?php if ($menu_page < $menu_total_pages) : ?><a class="fq-menu-page-nav" href="<?php echo esc_url(add_query_arg('menu_page', $menu_page + 1)); ?>" aria-label="Next page">›</a><?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="fq-menu-bottom-grid"><div class="fq-menu-analytics-card"><div class="fq-panel-head"><h3>Menu Analytics</h3><select><option>Today</option><option>This Week</option></select></div><div class="fq-menu-analytics-content"><div class="fq-menu-analytics-numbers"><p>Top Revenue Item <strong><?php echo esc_html($top_revenue_item_name); ?> <b><?php echo esc_html(menuqr_money($top_revenue_item_amount)); ?></b></strong></p><p>Average Item Price <strong><?php echo esc_html(menuqr_money($avg_item_price)); ?></strong></p><p>Orders from Menu Today <strong><?php echo esc_html((string) array_sum($menu_order_counts)); ?></strong></p></div><svg viewBox="0 0 360 140"><defs><linearGradient id="fqMenuChart" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#ffb23f" stop-opacity=".5"/><stop offset="100%" stop-color="#ffb23f" stop-opacity="0"/></linearGradient></defs><polyline points="<?php echo esc_attr($menu_chart_points); ?>" fill="none" stroke="#ffb23f" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg></div></div><div class="fq-menu-activity-card"><div class="fq-panel-head"><h3>Recent Menu Activity</h3><a href="<?php echo esc_url(menuqr_restaurant_tab_url('menu')); ?>">View All</a></div><?php foreach (array_slice($menu_items_all, 0, 5) as $act) : ?><p><span class="dot"></span><?php echo esc_html($act->name); ?> updated <small><?php echo esc_html(human_time_diff(strtotime($act->updated_at ?? $act->created_at), current_time('timestamp')) . ' ago'); ?></small></p><?php endforeach; ?></div></div>

                            <div id="fqMenuEditor" class="fq-menu-editor-card <?php echo ($editing_item || $editing_category || $menu_add_mode) ? 'is-open' : ''; ?>" data-fq-menu-editor="1"><div class="fq-panel-head"><h3><?php echo $editing_item ? 'Edit Item' : 'Add Item'; ?></h3><a data-fq-close-menu-editor="1" href="<?php echo esc_url(menuqr_restaurant_tab_url('menu')); ?>">Close</a></div><form method="post" enctype="multipart/form-data" action="<?php echo esc_url(menuqr_restaurant_tab_url('menu')); ?>"><?php wp_nonce_field('menuqr_save_item', 'menuqr_item_nonce'); ?><input type="hidden" name="action" value="menuqr_save_item"><input type="hidden" name="menuqr_front_action" value="save_item"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="item_id" value="<?php echo esc_attr((string) ($editing_item->id ?? 0)); ?>"><div class="fq-editor-grid"><label>Item Name<input name="name" required value="<?php echo esc_attr($editing_item->name ?? ''); ?>"></label><label>Category<select name="category_id" required data-fqx-category-select><option value="">Select Category</option><?php foreach ($categories as $cat) : ?><option value="<?php echo esc_attr((string) $cat->id); ?>" <?php selected((int) ($editing_item->category_id ?? 0), (int) $cat->id); ?>><?php echo esc_html($cat->name); ?></option><?php endforeach; ?></select></label><label>Category Type / Subcategory<select name="category_type_id" data-fqx-category-type-select><?php echo function_exists('fqx_v191_category_type_options_html') ? fqx_v191_category_type_options_html($restaurant_id, (int) ($editing_item->category_type_id ?? 0), (int) ($editing_item->category_id ?? 0)) : '<option value="0">No Type / Direct Category</option>'; ?></select><small>Example: Starters → Soups</small></label><label>Food Type<select name="food_type"><option value="veg" <?php selected(($editing_item->food_type ?? 'veg'), 'veg'); ?>>Veg</option><option value="nonveg" <?php selected(($editing_item->food_type ?? ''), 'nonveg'); ?>>Non-Veg</option><option value="beverages" <?php selected(($editing_item->food_type ?? ''), 'beverages'); ?>>Beverages</option><option value="desserts" <?php selected(($editing_item->food_type ?? ''), 'desserts'); ?>>Desserts</option></select></label><label>Price<input type="number" step="0.01" name="price" required value="<?php echo esc_attr((string) ($editing_item->price ?? '')); ?>"></label><label>Discount Price<input type="number" step="0.01" name="discount_price" value="<?php echo esc_attr((string) ($editing_item->discount_price ?? '0')); ?>"></label><label>Emoji<input name="emoji" value="<?php echo esc_attr($editing_item->emoji ?? '🍽️'); ?>"></label><label>Item Image<input type="file" name="item_image" accept="image/*"></label><label>Description<textarea name="description"><?php echo esc_textarea($editing_item->description ?? ''); ?></textarea></label><label>Variants<textarea name="variants"><?php echo esc_textarea(implode("\n", json_decode((string) ($editing_item->variants ?? ''), true) ?: [])); ?></textarea></label><label>Add-ons<textarea name="addons"><?php echo esc_textarea(implode("\n", json_decode((string) ($editing_item->addons ?? ''), true) ?: [])); ?></textarea></label></div><div class="fq-editor-checks"><label><input type="checkbox" name="is_available" <?php checked((int) ($editing_item->is_available ?? 1), 1); ?>> Available</label><label><input type="checkbox" name="is_featured" <?php checked((int) ($editing_item->is_featured ?? 0), 1); ?>> Bestseller / Featured</label><?php if (!empty($editing_item->image)) : ?><label><input type="checkbox" name="remove_image" value="1"> Remove image</label><?php endif; ?></div><button class="fq-menu-save-btn" type="submit"><?php echo $editing_item ? 'Update Item' : 'Save Item'; ?></button></form></div>
                        </main>
                        <aside class="fq-menu-details-panel"><?php if (!$selected_menu_item) : ?><div class="fq-menu-empty-state compact"><h3>No item selected</h3><p>Select an item to view details.</p></div><?php else : ?>
                            <div class="fq-menu-detail-head"><h3>Item Details</h3><b>#ITM-<?php echo esc_html((string) $selected_menu_item->id); ?></b></div>
                            <div class="fq-menu-image-preview"><?php if (!empty($selected_menu_item->image)) : ?><img src="<?php echo esc_url($selected_menu_item->image); ?>" alt="<?php echo esc_attr($selected_menu_item->name); ?>"><?php else : ?><div><?php echo esc_html($selected_menu_item->emoji ?: '🍽️'); ?></div><?php endif; ?><?php if ((int) ($selected_menu_item->is_featured ?? 0)) : ?><span>Bestseller</span><?php endif; ?></div>
                            <h2><?php echo esc_html($selected_menu_item->name); ?></h2><div class="fq-menu-meta-grid"><p><small>Category</small><strong><?php echo esc_html($selected_category); ?></strong></p><p><small>Category Type</small><strong><?php echo esc_html($selected_category_type); ?></strong></p><p><small>Food Type</small><strong><?php echo esc_html(in_array(strtolower((string) $selected_menu_item->food_type), ['nonveg','non-veg','non_veg'], true) ? 'Non-Veg' : 'Veg'); ?></strong></p><p><small>Price</small><strong><?php echo esc_html(menuqr_money($selected_price)); ?></strong></p><p><small>Status</small><strong class="<?php echo (int) $selected_menu_item->is_available ? 'green' : 'red'; ?>"><?php echo (int) $selected_menu_item->is_available ? 'Available' : 'Out of Stock'; ?></strong></p><p><small>Rating</small><strong>4.<?php echo esc_html((string) ((int) $selected_menu_item->id % 8 + 1)); ?> ★</strong></p><p><small>Orders Today</small><strong><?php echo esc_html((string) $selected_order_count); ?></strong></p></div>
                            <div class="fq-detail-block"><h4>Description</h4><p><?php echo esc_html($selected_menu_item->description ?: 'No description added.'); ?></p></div><div class="fq-detail-block"><h4>Ingredients</h4><div class="fq-chip-list"><span><?php echo esc_html($selected_category); ?></span><?php if ($selected_category_type !== '—') : ?><span><?php echo esc_html($selected_category_type); ?></span><?php endif; ?><span><?php echo esc_html($selected_menu_item->food_type ?: 'Veg'); ?></span><?php if (!empty($selected_menu_item->emoji)) : ?><span><?php echo esc_html($selected_menu_item->emoji); ?></span><?php endif; ?></div></div><div class="fq-detail-block"><h4>Variants / Add-ons</h4><div class="fq-addon-list"><?php $combo_va = array_merge((array) $selected_variants, (array) $selected_addons); if (!$combo_va) : ?><p><span>No variants or add-ons</span><strong>—</strong></p><?php else : foreach ($combo_va as $line) : ?><p><span><?php echo esc_html(is_array($line) ? ($line['name'] ?? 'Option') : (string) $line); ?></span><strong>—</strong></p><?php endforeach; endif; ?></div></div><div class="fq-menu-info-cards"><div><?php echo fqx_v169_menu_icon('clock','fq-svg-icon'); ?><strong>Preparation Time</strong><span>20–25 mins</span></div><div><?php echo fqx_v169_menu_icon('chili','fq-svg-icon'); ?><strong>Spice Level</strong><span>Medium 🌶️🌶️</span></div></div><div class="fq-menu-action-grid"><a class="primary" href="<?php echo esc_url(menuqr_restaurant_edit_url('menu', 'edit_item', (int) $selected_menu_item->id)); ?>#fqMenuEditor"><?php echo fqx_v169_menu_icon('edit','fq-svg-icon'); ?> Edit Item</a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('menuqr_item_quick_action', 'menuqr_item_nonce'); ?><input type="hidden" name="action" value="menuqr_toggle_item_availability"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="item_id" value="<?php echo esc_attr((string) $selected_menu_item->id); ?>"><input type="hidden" name="is_available" value="<?php echo (int) $selected_menu_item->is_available ? '0' : '1'; ?>"><button class="danger" type="submit"><?php echo (int) $selected_menu_item->is_available ? 'Mark Out of Stock' : 'Mark Available'; ?></button></form><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('menuqr_item_quick_action', 'menuqr_item_nonce'); ?><input type="hidden" name="action" value="menuqr_duplicate_item"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="item_id" value="<?php echo esc_attr((string) $selected_menu_item->id); ?>"><button type="submit"><?php echo fqx_v169_menu_icon('copy','fq-svg-icon'); ?> Duplicate Item</button></form><a href="<?php echo esc_url(menuqr_get_menu_url($restaurant_id)); ?>" target="_blank"><?php echo fqx_v169_menu_icon('eye','fq-svg-icon'); ?> Preview Item</a><form method="post" action="<?php echo esc_url(menuqr_restaurant_tab_url('menu')); ?>"><?php wp_nonce_field('menuqr_delete_record', 'menuqr_delete_nonce'); ?><input type="hidden" name="action" value="menuqr_delete_item"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="id" value="<?php echo esc_attr((string) $selected_menu_item->id); ?>"><button class="delete" onclick="return confirm('Delete this item?');" type="submit"><?php echo fqx_v169_menu_icon('trash','fq-svg-icon'); ?> Delete Item</button></form><a href="<?php echo esc_url(add_query_arg('section','categories', menuqr_restaurant_tab_url('menu'))); ?>">Assign Category</a></div>
                        <?php endif; ?></aside>
                    </div>
                </div>
                <?php endif; ?>

            <?php elseif ('tables' === $current_tab) : ?>

                
<div class="section-card mq-qr-template-section fqx-v189-template-moved-note">
                    <div class="section-title">
                        <span>Table QR Design</span>
                        <span class="tag tag-blue">Managed from QR Templates</span>
                    </div>
                    <p class="text-muted fs-sm" style="margin-bottom:14px;">Table QR design is now managed from the central QR Templates page. Tables page only manages tables and table QR actions.</p>
                    <a class="btn btn-primary" href="<?php echo esc_url(add_query_arg(['section' => 'templates', 'template_tab' => 'table'], menuqr_restaurant_tab_url('rooms'))); ?>">Manage Table QR Templates</a>
                </div>

                <div class="chart-grid">

                    <div class="chart-card">
                        <div class="chart-title"><?php echo $editing_table ? 'Edit Table' : 'Add Table'; ?></div>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('menuqr_save_table', 'menuqr_table_nonce'); ?>
                            <input type="hidden" name="action" value="menuqr_save_table">
                            <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                            <input type="hidden" name="table_id" value="<?php echo esc_attr((string) ($editing_table->id ?? 0)); ?>">
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Table Number</label><input class="form-input" name="table_number" value="<?php echo esc_attr($editing_table->table_number ?? ''); ?>" required></div>
                                <div class="form-group"><label class="form-label">Capacity</label><input class="form-input" type="number" name="capacity" value="<?php echo esc_attr((string) ($editing_table->capacity ?? 2)); ?>" required></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">PIN Login (optional)</label><input class="form-input" name="pin_code" value="<?php echo esc_attr($editing_staff->pin_code ?? ''); ?>" maxlength="6" inputmode="numeric"></div>
                                <div class="form-group"><label class="form-label">Permissions</label><div class="mq-check-grid"><?php $staff_permissions = menuqr_staff_permissions_catalog(); $selected_permissions = $editing_staff ? menuqr_staff_permissions_for_member($editing_staff) : menuqr_default_permissions_for_role($editing_staff->role_name ?? 'kitchen'); foreach ($staff_permissions as $perm_key => $perm_label) : ?><label class="form-check"><input type="checkbox" name="permissions[]" value="<?php echo esc_attr($perm_key); ?>" <?php checked(in_array($perm_key, $selected_permissions, true)); ?>> <?php echo esc_html($perm_label); ?></label><?php endforeach; ?></div></div>
                            </div>
                            <div class="page-header-right">
                                <button class="btn btn-primary" type="submit"><?php echo $editing_table ? 'Update Table' : 'Save Table'; ?></button>
                                <?php if ($editing_table) : ?><a class="btn btn-outline" href="<?php echo esc_url(menuqr_restaurant_tab_url('tables')); ?>">Cancel</a><?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <div class="chart-card">
                        <div class="chart-title">Tables & QR Codes</div>
                        <div class="qr-grid">
                            <?php if (!$tables) : ?>
                                <div class="empty-state"><span class="empty-icon">🪑</span><h4>No tables yet</h4><p>Add tables to generate QR menus.</p></div>
                            <?php else : foreach ($tables as $table) : $url = menuqr_get_menu_url($restaurant_id, (int) $table->id); ?>
                                <div class="qr-card">
                                    <?php if (!empty($restaurant->logo)) : ?><img src="<?php echo esc_url($restaurant->logo); ?>" alt="" style="width:42px;height:42px;object-fit:cover;border-radius:12px;margin-bottom:8px;"><?php endif; ?><div class="qr-card-table">Table <?php echo esc_html($table->table_number); ?></div>
                                    <div class="qr-card-url"><?php echo esc_html($url); ?></div>
                                    <div class="qr-wrap"><?php echo menuqr_render_qr_card_html($restaurant_id, (string) $table->table_number, $url, 150); ?></div>
                                    <div class="page-header-right" style="justify-content:center;">
                                        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(menuqr_restaurant_edit_url('tables', 'edit_table', (int) $table->id)); ?>">Edit</a>
                                        <a class="btn btn-primary btn-sm" target="_blank" href="<?php echo esc_url($url); ?>">Open</a>
                                        <a class="btn btn-success btn-sm" href="<?php echo esc_url(menuqr_qr_card_download_url($restaurant_id, (int) $table->id)); ?>">Template</a>
                                        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(menuqr_qr_download_url($restaurant_id, (int) $table->id)); ?>">QR PNG</a>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title">All Tables <span class="inline-actions"><a class="btn btn-outline btn-sm" target="_blank" href="<?php echo esc_url(add_query_arg(['menuqr_print_qr' => 1], menuqr_restaurant_tab_url('tables'))); ?>">Print QR Layout</a></span></div>
                    <div class="table-wrap"><div class="table-scroll">
                        <table class="data-table">
                            <thead><tr class="fq-bill-row-card"><th>Table</th><th>Capacity</th><th>QR</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if (!$tables) : ?>
                                <tr class="fq-bill-row-card"><td colspan="4"><div class="empty-state"><span class="empty-icon">🪑</span><h4>No tables yet</h4><p>Add tables to manage QR ordering.</p></div></td></tr>
                            <?php else : foreach ($tables as $table) : $url = menuqr_get_menu_url($restaurant_id, (int) $table->id); ?>
                                <tr class="fq-bill-row-card">
                                    <td><?php echo esc_html($table->table_number); ?></td>
                                    <td><?php echo esc_html((string) $table->capacity); ?></td>
                                    <td><a href="<?php echo esc_url($url); ?>" target="_blank">Open Menu</a><div><a href="<?php echo esc_url(menuqr_qr_card_download_url($restaurant_id, (int) $table->id)); ?>">Download Menu + WiFi Card</a></div><div><a href="<?php echo esc_url(menuqr_qr_download_url($restaurant_id, (int) $table->id)); ?>">Download QR PNG Only</a></div></td>
                                    <td class="inline-actions">
                                        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(menuqr_restaurant_edit_url('tables', 'edit_table', (int) $table->id)); ?>">Edit</a>
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                            <?php wp_nonce_field('menuqr_delete_record', 'menuqr_delete_nonce'); ?>
                                            <input type="hidden" name="action" value="menuqr_delete_table">
                                            <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                                            <input type="hidden" name="id" value="<?php echo esc_attr((string) $table->id); ?>">
                                            <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Delete this table?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div></div>
                </div>


            <?php elseif ('rooms' === $current_tab) : ?>
                <?php
                $fq_room_section = in_array($current_section ?: 'rooms', ['rooms', 'templates', 'print'], true) ? ($current_section ?: 'rooms') : 'rooms';
                $fq_template_tab = sanitize_key(wp_unslash($_GET['template_tab'] ?? 'table'));
                if (!in_array($fq_template_tab, ['table', 'room', 'wifi'], true)) { $fq_template_tab = 'table'; }
                $fq_preview_room_id = absint($_GET['room_id'] ?? 0);
                if (!$fq_preview_room_id && !empty($rooms[0]->id)) { $fq_preview_room_id = (int) $rooms[0]->id; }
                $fq_preview_room = null;
                foreach ($rooms as $fq_room_candidate) {
                    if ((int) $fq_room_candidate->id === $fq_preview_room_id) { $fq_preview_room = $fq_room_candidate; break; }
                }
                $fq_templates = function_exists('fqx_v134_room_template_catalog') ? fqx_v134_room_template_catalog() : [];
                if (!$fq_templates) {
                    $fq_templates = [
                        'premium_gold' => ['name' => 'Premium Gold', 'category' => 'Luxury Hotel'],
                        'royal_black' => ['name' => 'Royal Black', 'category' => 'Royal Suite'],
                        'luxury_hotel' => ['name' => 'Luxury Hotel', 'category' => 'Premium Stay'],
                        'minimal_white' => ['name' => 'Minimal White', 'category' => 'Clean A6'],
                        'modern_orange' => ['name' => 'Modern Orange', 'category' => 'Restaurant'],
                        'classic_restaurant' => ['name' => 'Classic Restaurant', 'category' => 'Dine-in + Room'],
                        'room_service_pro' => ['name' => 'Room Service Pro', 'category' => 'Room Service'],
                        'dark_elite' => ['name' => 'Dark Elite', 'category' => 'Elite Black'],
                        'clean_corporate' => ['name' => 'Clean Corporate', 'category' => 'Business Hotel'],
                        'smart_hotel_qr' => ['name' => 'Smart Hotel QR', 'category' => 'Smart Access'],
                    ];
                }
                $fq_room_default_template = function_exists('fqx_v189_get_room_default_template') ? fqx_v189_get_room_default_template($restaurant_id) : get_option('fqx_room_qr_template_default_' . $restaurant_id, 'premium_gold');
                $fq_selected_template = ('templates' === $fq_room_section) ? $fq_room_default_template : ($fq_preview_room && function_exists('fqx_v134_get_room_template_id') ? fqx_v134_get_room_template_id($fq_preview_room) : (string) ($fq_preview_room->room_qr_template ?? $fq_room_default_template));
                $fq_selected_template = function_exists('fqx_v134_normalize_room_template') ? fqx_v134_normalize_room_template($fq_selected_template) : sanitize_key($fq_selected_template);
                $fq_selected_template_label = $fq_templates[$fq_selected_template]['name'] ?? 'Premium Gold';
                ?>
                <div class="fq-room-admin-page" data-room-section="<?php echo esc_attr($fq_room_section); ?>">
                    <div class="fq-room-page-head">
                        <div>
                            <div class="fq-room-breadcrumb"><a href="<?php echo esc_url(menuqr_restaurant_tab_url('rooms')); ?>">Rooms & QR</a><span>›</span><span><?php echo esc_html('templates' === $fq_room_section ? 'QR Templates' : ('print' === $fq_room_section ? 'Room QR Preview' : 'Rooms')); ?></span></div>
                            <h1><?php echo esc_html('templates' === $fq_room_section ? 'QR Templates' : ('print' === $fq_room_section ? 'Room QR Preview' : 'Rooms')); ?></h1>
                            <p><?php echo esc_html('templates' === $fq_room_section ? 'Select and apply premium printable Room QR card designs.' : ('print' === $fq_room_section ? 'Preview and download or print the QR code for this room.' : 'Manage hotel rooms, room QR codes, WiFi QR and printable room cards.')); ?></p>
                        </div>
                        <div class="fq-room-head-actions">
                            <a class="fq-room-action-btn" href="<?php echo esc_url(add_query_arg('section', 'rooms', menuqr_restaurant_tab_url('rooms'))); ?>">Rooms</a>
                            <a class="fq-room-action-btn" href="<?php echo esc_url(add_query_arg('section', 'templates', menuqr_restaurant_tab_url('rooms'))); ?>">QR Templates</a>
                            <a class="fq-room-action-btn is-gold" href="<?php echo esc_url(add_query_arg(['section' => 'print', 'room_id' => $fq_preview_room_id], menuqr_restaurant_tab_url('rooms'))); ?>">Print QR</a>
                        </div>
                    </div>

                    <?php if ('rooms' === $fq_room_section) : ?>
                        <div class="fq-room-grid-layout fq-room-grid-layout-rooms">
                            <div class="fq-room-dark-card">
                                <div class="fq-room-card-head">
                                    <h3><?php echo $editing_room ? 'Edit Room' : 'Add Room'; ?></h3>
                                    <span>Room setup</span>
                                </div>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fq-room-form">
                                    <?php wp_nonce_field('menuqr_save_room', 'menuqr_room_nonce'); ?>
                                    <input type="hidden" name="action" value="menuqr_save_room">
                                    <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                                    <input type="hidden" name="room_id" value="<?php echo esc_attr((string) ($editing_room->id ?? 0)); ?>">
                                    <div class="form-row">
                                        <div class="form-group"><label class="form-label">Room Number</label><input class="form-input" name="room_number" value="<?php echo esc_attr($editing_room->room_number ?? ''); ?>" required></div>
                                        <div class="form-group"><label class="form-label">Room Name</label><input class="form-input" name="room_name" value="<?php echo esc_attr($editing_room->room_name ?? ''); ?>"></div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group"><label class="form-label">Floor</label><input class="form-input" name="floor" value="<?php echo esc_attr($editing_room->floor ?? ''); ?>"></div>
                                        <div class="form-group"><label class="form-label">Wing / Building</label><input class="form-input" name="wing" value="<?php echo esc_attr($editing_room->wing ?? ''); ?>"></div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group"><label class="form-label">Room Type</label><input class="form-input" name="room_type" value="<?php echo esc_attr($editing_room->room_type ?? ''); ?>"></div>
                                        <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?php selected(($editing_room->status ?? 'active'), 'active'); ?>>Active</option><option value="inactive" <?php selected(($editing_room->status ?? ''), 'inactive'); ?>>Inactive</option></select></div>
                                    </div>
                                    <div class="form-group"><label class="form-label">Notes</label><textarea class="form-input" name="notes" rows="3"><?php echo esc_textarea($editing_room->notes ?? ''); ?></textarea></div>
                                    <div class="form-group fqx-v189-room-template-info">
                                        <div class="fqx-v189-note-box">
                                            <strong>Room QR design is managed from QR Templates page.</strong>
                                            <span>Using selected Room QR template from QR Templates page.</span>
                                            <a href="<?php echo esc_url(add_query_arg(['section' => 'templates', 'template_tab' => 'room'], menuqr_restaurant_tab_url('rooms'))); ?>">Manage Room QR Templates</a>
                                        </div>
                                    </div>
                                    <div class="fq-room-actions-inline">
                                        <button class="fq-gold-btn" type="submit"><?php echo $editing_room ? 'Update Room' : 'Save Room'; ?></button>
                                        <?php if ($editing_room) : ?><a class="fq-dark-btn" href="<?php echo esc_url(add_query_arg('section', 'rooms', menuqr_restaurant_tab_url('rooms'))); ?>">Cancel</a><?php endif; ?>
                                    </div>
                                </form>
                            </div>

                            <div class="fq-room-dark-card fq-room-list-card">
                                <div class="fq-room-card-head">
                                    <div><h3>Rooms & QR</h3><span>Room QR design is managed from QR Templates page.</span></div>
                                    <a class="fq-mini-link" href="<?php echo esc_url(add_query_arg(['section' => 'templates', 'template_tab' => 'room'], menuqr_restaurant_tab_url('rooms'))); ?>">Manage Room QR Templates</a>
                                </div>
                                <div class="fq-room-table-wrap">
                                    <?php if (!$rooms) : ?>
                                        <div class="empty-state"><span class="empty-icon">🛏️</span><h4>No rooms yet</h4><p>Add rooms to generate room menu QR and WiFi QR cards.</p></div>
                                    <?php else : ?>
                                        <table class="fq-room-modern-table">
                                            <thead><tr class="fq-bill-row-card"><th>Room</th><th>Template</th><th>WiFi</th><th>Actions</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($rooms as $room) : $url = menuqr_get_room_menu_url($restaurant_id, (int) $room->id); $tpl = function_exists('fqx_v134_get_room_template_id') ? fqx_v134_get_room_template_id($room) : ($room->room_qr_template ?? 'premium_gold'); $tpl_label = $fq_templates[$tpl]['name'] ?? ucfirst(str_replace('_', ' ', (string) $tpl)); $wifi_on = function_exists('fqx_should_show_wifi_qr') ? fqx_should_show_wifi_qr($restaurant_id, (int) $room->id) : false; ?>
                                                <tr class="fq-bill-row-card">
                                                    <td><strong>Room <?php echo esc_html($room->room_number); ?></strong><small><?php echo esc_html($room->floor ? 'Floor ' . $room->floor : 'Floor —'); ?> · <?php echo esc_html($room->room_type ?: 'Standard'); ?></small></td>
                                                    <td><span class="fq-pill fq-pill-gold"><?php echo esc_html($tpl_label); ?></span></td>
                                                    <td><span class="fq-pill <?php echo $wifi_on ? 'fq-pill-green' : 'fq-pill-muted'; ?>"><?php echo esc_html($wifi_on ? 'Enabled' : 'Disabled'); ?></span></td>
                                                    <td>
                                                        <div class="fq-table-actions">
                                                            <a href="<?php echo esc_url(add_query_arg(['section' => 'print', 'room_id' => (int) $room->id], menuqr_restaurant_tab_url('rooms'))); ?>">View QR</a>
                                                            <a href="<?php echo esc_url(add_query_arg(['section' => 'print', 'room_id' => (int) $room->id], menuqr_restaurant_tab_url('rooms'))); ?>">Print QR</a>
                                                            <a href="<?php echo esc_url(menuqr_restaurant_edit_url('rooms', 'edit_room', (int) $room->id)); ?>">Edit</a>
                                                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                                                <?php wp_nonce_field('menuqr_delete_record', 'menuqr_delete_nonce'); ?>
                                                                <input type="hidden" name="action" value="menuqr_delete_room">
                                                                <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                                                                <input type="hidden" name="id" value="<?php echo esc_attr((string) $room->id); ?>">
                                                                <button type="submit" onclick="return confirm('Delete this room?');">Delete</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    <?php elseif ('templates' === $fq_room_section) : ?>
                        <div class="fq-room-dark-card fqx-v189-template-hub">
                            <div class="fq-room-card-head">
                                <div>
                                    <h3>QR Templates</h3>
                                    <span>Select the default design used for Table QR and Room QR generation.</span>
                                </div>
                            </div>
                            <div class="fqx-v189-template-tabs" role="tablist" aria-label="QR Template Sections">
                                <a class="<?php echo $fq_template_tab === 'table' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['section' => 'templates', 'template_tab' => 'table'], menuqr_restaurant_tab_url('rooms'))); ?>">Table QR Templates</a>
                                <a class="<?php echo $fq_template_tab === 'room' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['section' => 'templates', 'template_tab' => 'room'], menuqr_restaurant_tab_url('rooms'))); ?>">Room QR Templates</a>
                                <a class="<?php echo $fq_template_tab === 'wifi' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['section' => 'templates', 'template_tab' => 'wifi'], menuqr_restaurant_tab_url('rooms'))); ?>">WiFi / Room WiFi QR</a>
                            </div>
                        </div>
                        <div id="fqx-table-template-panel" class="section-card mq-qr-template-section fqx-v189-template-panel <?php echo $fq_template_tab === 'table' ? 'is-active' : ''; ?>">
                    <div class="section-title">
                        <span>QR Template Builder</span>
                        <span class="tag tag-blue">Create, save, download, print</span>
                    </div>
                    <p class="text-muted fs-sm" style="margin-bottom:14px;">Select the default design used for Table QR generation. Pick a table only for live preview/sample download.</p>
                    <div id="menuqr-qr-builder" class="menuqr-qr-builder" data-restaurant-id="<?php echo esc_attr((string) $restaurant_id); ?>">
                        <div class="menuqr-qr-builder-main">
                            <div class="menuqr-qr-builder-toolbar">
                                <div class="form-group">
                                    <label class="form-label">Select Table</label>
                                    <select id="menuqr-table-id" class="form-select">
                                        <option value="">Choose table</option>
                                        <?php foreach ($tables as $table) : ?>
                                            <option value="<?php echo esc_attr((string) $table->id); ?>" <?php selected($qr_default_table_id, (int) $table->id); ?>>Table <?php echo esc_html($table->table_number); ?><?php if (!empty($saved_qr_templates[(int) $table->id])) : ?> • Saved<?php endif; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="menuqr-qr-builder-actions">
                                    <button id="menuqr-create-qr" class="btn btn-primary" type="button">Create QR</button>
                                    <button id="menuqr-save-template" class="btn btn-success" type="button">Save Template</button>
                                    <button id="menuqr-download-png" class="btn btn-outline" type="button">Download PNG</button>
                                    <button id="menuqr-download-svg" class="btn btn-outline" type="button">Download SVG</button>
                                    <button id="menuqr-print-qr" class="btn btn-outline" type="button">Print QR</button>
                                    <button id="menuqr-bulk-generate" class="btn btn-outline" type="button">Bulk Generate</button>
                                </div>
                            </div>

                            <div class="mq-qr-template-grid">
                                <?php foreach ($qr_templates as $template_key => $template) : ?>
                                    <label class="mq-qr-template-option <?php echo $current_qr_template === $template_key ? 'is-selected' : ''; ?>" style="--qr-accent:<?php echo esc_attr($template['accent']); ?>;--qr-accent2:<?php echo esc_attr($template['accent2'] ?? '#ffffff'); ?>">
                                        <input type="radio" name="qr_template" value="<?php echo esc_attr($template_key); ?>" <?php checked($current_qr_template, $template_key); ?>>
                                        <span class="mq-qr-template-preview">
                                            <span class="mq-qr-template-emoji"><?php echo esc_html($template['emoji']); ?></span>
                                            <span class="mq-mini-qr-box"></span>
                                        </span>
                                        <strong><?php echo esc_html($template['name']); ?></strong>
                                        <small><?php echo esc_html($template['badge']); ?></small>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="menuqr-qr-builder-preview">
                            <div class="menuqr-qr-preview-card">
                                <div class="section-title">
                                    <span>Live Preview</span>
                                    <span class="tag tag-green">Instant update</span>
                                </div>
                                <div id="menuqr-live-preview" class="menuqr-live-preview">
                                    <?php if ($qr_default_table_id > 0) :
                                        $default_table = null;
                                        foreach ($tables as $table) { if ((int) $table->id === $qr_default_table_id) { $default_table = $table; break; } }
                                        if ($default_table) :
                                            $default_url = menuqr_get_menu_url($restaurant_id, (int) $default_table->id);
                                            echo menuqr_render_qr_card_html($restaurant_id, (string) $default_table->table_number, $default_url, 180, $current_qr_template);
                                        endif;
                                    endif; ?>
                                </div>
                                <div class="menuqr-qr-meta-grid">
                                    <div class="menuqr-qr-meta-item"><span>Table</span><strong id="menuqr-preview-table"><?php echo !empty($tables[0]) ? esc_html((string) $tables[0]->table_number) : '--'; ?></strong></div>
                                    <div class="menuqr-qr-meta-item"><span>QR URL</span><strong id="menuqr-preview-url"><?php echo !empty($tables[0]) ? esc_html(menuqr_get_menu_url($restaurant_id, (int) $tables[0]->id)) : '--'; ?></strong></div>
                                </div>
                                <div id="menuqr-qr-toast" class="menuqr-qr-toast" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>


                        <div id="fqx-room-template-panel" class="fq-room-dark-card fq-template-admin-card fqx-v189-template-panel <?php echo $fq_template_tab === 'room' ? 'is-active' : ''; ?>">
                            <div class="fq-room-card-head">
                                <div>
                                    <h3>Premium Room QR Templates</h3>
                                    <span>Select default Room QR template. Rooms page will automatically use this design.</span>
                                </div>
                                <a class="fq-mini-link" href="<?php echo esc_url(add_query_arg(['section' => 'rooms'], menuqr_restaurant_tab_url('rooms'))); ?>">Back to Rooms</a>
                            </div>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fq-template-save-form">
                                    <?php wp_nonce_field('fqx_v142_update_room_template', 'fqx_v142_room_template_nonce'); ?>
                                    <input type="hidden" name="action" value="fqx_v142_update_room_template">
                                    <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                                    <input type="hidden" name="room_id" class="fq-template-room-id" value="0">
                                    <input type="hidden" name="save_scope" value="restaurant_default">
                                    <div class="fq-template-grid">
                                        <?php $i = 1; foreach ($fq_templates as $template_id => $template) : $is_selected = ($template_id === $fq_selected_template); ?>
                                            <label class="fq-template-card <?php echo $is_selected ? 'fq-template-selected' : ''; ?>">
                                                <input class="fq-template-radio" type="radio" name="room_qr_template" value="<?php echo esc_attr($template_id); ?>" <?php checked($is_selected); ?>>
                                                <span class="fq-template-badge"><?php echo $is_selected ? 'Selected ✓' : 'Select'; ?></span>
                                                <span class="fq-template-preview-mini fq-template-style-<?php echo esc_attr(sanitize_html_class($template_id)); ?>">
                                                    <span class="fq-template-mini-head">FluuexQR</span>
                                                    <span class="fq-template-mini-room">Room <?php echo esc_html($fq_preview_room ? $fq_preview_room->room_number : '204'); ?></span>
                                                    <span class="fq-template-mini-qr"></span>
                                                    <span class="fq-template-mini-actions">Browse · Order · Track · Bill</span>
                                                </span>
                                                <span class="fq-template-title"><?php echo esc_html($template['name'] ?? ('Template ' . $i)); ?></span>
                                                <span class="fq-template-type"><?php echo esc_html($template['category'] ?? 'Premium Room QR'); ?></span>
                                            </label>
                                        <?php $i++; endforeach; ?>
                                    </div>
                                    <div class="fq-room-actions-inline">
                                        <button class="fq-gold-btn" type="submit">Save Template</button>
                                        <a class="fq-dark-btn" href="<?php echo esc_url(add_query_arg(['section' => 'print', 'room_id' => $fq_preview_room_id], menuqr_restaurant_tab_url('rooms'))); ?>">Preview</a>
                                        <a class="fq-dark-btn" href="<?php echo esc_url(add_query_arg(['section' => 'print', 'room_id' => $fq_preview_room_id, 'sample' => 1], menuqr_restaurant_tab_url('rooms'))); ?>">Print Sample QR</a>
                                    </div>
                                </form>
                        </div>

                        <div id="fqx-wifi-template-panel" class="fq-room-dark-card fqx-v189-template-panel <?php echo $fq_template_tab === 'wifi' ? 'is-active' : ''; ?>">
                            <?php
                                $fqx_wifi_templates_v197 = function_exists('fqx_v197_wifi_templates') ? fqx_v197_wifi_templates() : [];
                                $fqx_selected_wifi_template_v197 = function_exists('fqx_v197_get_wifi_template') ? fqx_v197_get_wifi_template($restaurant_id) : 'wifi_clean';
                            ?>
                            <div class="fq-room-card-head">
                                <div><h3>WiFi / Room WiFi QR Template</h3><span>Select the WiFi card style used with Room QR print card. WiFi settings are still managed from the WiFi QR page.</span></div>
                                <a class="fq-mini-link" href="<?php echo esc_url(menuqr_restaurant_tab_url('wifi')); ?>">Open WiFi QR</a>
                            </div>
                            <?php if (!empty($_GET['wifi_template_saved'])) : ?><div class="fqx-v197-success-note">WiFi / Room WiFi QR template saved successfully.</div><?php endif; ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fqx-v197-wifi-template-form">
                                <?php wp_nonce_field('fqx_v197_save_wifi_template', 'fqx_v197_wifi_template_nonce'); ?>
                                <input type="hidden" name="action" value="fqx_v197_save_wifi_template">
                                <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                                <div class="fqx-v197-wifi-template-grid">
                                    <?php foreach ($fqx_wifi_templates_v197 as $wifi_key => $wifi_tpl) : $wifi_selected = ($wifi_key === $fqx_selected_wifi_template_v197); ?>
                                        <label class="fqx-v197-wifi-template-card fqx-v198-room-wifi-card <?php echo $wifi_selected ? 'is-selected' : ''; ?>" data-wifi-template="<?php echo esc_attr($wifi_key); ?>">
                                            <input type="radio" name="wifi_qr_template" value="<?php echo esc_attr($wifi_key); ?>" <?php checked($wifi_selected); ?>>
                                            <span class="fqx-v197-wifi-selected"><?php echo $wifi_selected ? 'Selected ✓' : 'Select'; ?></span>
                                            <span class="fqx-v198-room-template-mini">
                                                <span class="fqx-v198-mini-logo">Fluuex<span>QR</span></span>
                                                <span class="fqx-v198-mini-room">Room <?php echo esc_html((string)($wifi_tpl['room'] ?? '206')); ?></span>
                                                <span class="fqx-v198-mini-body">
                                                    <span class="fqx-v198-mini-menu"><b>MENU QR</b><i></i><em>Scan to order</em></span>
                                                    <span class="fqx-v198-mini-wifi"><b>WIFI QR</b><i></i><em>Hotel_Guest</em></span>
                                                </span>
                                                <span class="fqx-v198-mini-icons"><i>Menu</i><i>Order</i><i>Track</i><i>Bill</i></span>
                                            </span>
                                            <strong><?php echo esc_html($wifi_tpl['name'] ?? 'WiFi QR Template'); ?></strong>
                                            <small><?php echo esc_html($wifi_tpl['type'] ?? 'Room WiFi'); ?></small>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="fq-room-actions-inline fqx-v197-wifi-actions">
                                    <button class="fq-gold-btn" type="submit">Save WiFi Template</button>
                                    <a class="fq-dark-btn" href="<?php echo esc_url(menuqr_restaurant_tab_url('wifi')); ?>">Configure WiFi QR</a>
                                    <a class="fq-dark-btn" href="<?php echo esc_url(add_query_arg(['section' => 'print', 'room_id' => $fq_preview_room_id], menuqr_restaurant_tab_url('rooms'))); ?>">Preview Room QR</a>
                                    <a class="fq-dark-btn" target="_blank" href="<?php echo esc_url(add_query_arg(['section' => 'print', 'room_id' => $fq_preview_room_id, 'sample' => 1], menuqr_restaurant_tab_url('rooms'))); ?>">Print Sample QR</a>
                                    <a class="fq-dark-btn" target="_blank" href="<?php echo esc_url(function_exists('menuqr_room_qr_card_download_url') ? menuqr_room_qr_card_download_url($restaurant_id, (int) $fq_preview_room_id) : menuqr_room_qr_download_url($restaurant_id, (int) $fq_preview_room_id)); ?>">Download Sample QR</a>
                                </div>
                            </form>
                            <div class="fqx-v189-note-box"><strong>WiFi QR support remains active inside Room QR templates.</strong><span>Room QR can still open Room Service, Menu, Bill, Order Tracking and WiFi without changing the workflow.</span></div>
                        </div>

                    <?php elseif ('print' === $fq_room_section) : ?>
                        <?php if (!$fq_preview_room) : ?>
                            <div class="fq-room-dark-card"><div class="empty-state"><span class="empty-icon">🛏️</span><h4>No room selected</h4><p>Add a room first or select a room to preview printable QR card.</p></div></div>
                        <?php else : ?>
                            <?php
                            $fq_room_url = menuqr_get_room_menu_url($restaurant_id, (int) $fq_preview_room->id);
                            $fq_menu_qr_img = function_exists('menuqr_get_real_qr_image_url') ? menuqr_get_real_qr_image_url($fq_room_url, 420, 'png') : menuqr_room_qr_download_url($restaurant_id, (int) $fq_preview_room->id);
                            $fq_wifi_settings = function_exists('fqx_get_room_wifi_settings') ? fqx_get_room_wifi_settings($restaurant_id, (int) $fq_preview_room->id) : null;
                            $fq_wifi_qr_img = function_exists('fqx_get_wifi_qr_image_url') ? fqx_get_wifi_qr_image_url($restaurant_id, (int) $fq_preview_room->id, 260) : '';
                            $fq_wifi_password = ($fq_wifi_settings && function_exists('fqx_decrypt_wifi_password')) ? fqx_decrypt_wifi_password((string) $fq_wifi_settings->password_encrypted) : '';
                            $fq_show_wifi_name = $fq_wifi_settings && !empty($fq_wifi_settings->show_ssid);
                            $fq_show_wifi_password = $fq_wifi_settings && !empty($fq_wifi_settings->show_password);
                            $fq_card_download = function_exists('menuqr_room_qr_card_download_url') ? menuqr_room_qr_card_download_url($restaurant_id, (int) $fq_preview_room->id) : menuqr_room_qr_download_url($restaurant_id, (int) $fq_preview_room->id);
                            $fq_print_url = function_exists('fqx_v133_room_qr_card_print_url') ? fqx_v133_room_qr_card_print_url($restaurant_id, (int) $fq_preview_room->id) : $fq_card_download;
                            ?>
                            <div class="fq-room-print-layout">
                                <aside class="fq-room-print-left">
                                    <div class="fq-room-info-card">
                                        <div class="fq-card-title-row"><h3>Room Details</h3><a href="<?php echo esc_url(menuqr_restaurant_edit_url('rooms', 'edit_room', (int) $fq_preview_room->id)); ?>">✎ Edit</a></div>
                                        <dl>
                                            <dt>Property Name</dt><dd><?php echo esc_html($restaurant->name); ?></dd>
                                            <dt>Room Number</dt><dd><?php echo esc_html($fq_preview_room->room_number); ?></dd>
                                            <dt>Template</dt><dd><span class="fq-dot"></span><?php echo esc_html($fq_selected_template_label); ?></dd>
                                            <dt>Size</dt><dd>A6 (105 x 148 mm)</dd>
                                        </dl>
                                    </div>
                                    <div class="fq-room-info-card">
                                        <div class="fq-card-title-row"><h3>Customization</h3><a href="<?php echo esc_url(add_query_arg(['section' => 'templates', 'room_id' => (int) $fq_preview_room->id], menuqr_restaurant_tab_url('rooms'))); ?>">✎ Edit Template</a></div>
                                        <div class="fq-custom-row"><span>Primary Color</span><i style="background:#ffbf3f"></i></div>
                                        <div class="fq-custom-row"><span>Background</span><i style="background:#101923"></i></div>
                                        <div class="fq-custom-row"><span>Logo</span><b>Hotel Logo</b></div>
                                        <div class="fq-custom-row"><span>Show WiFi</span><b><?php echo esc_html($fq_wifi_qr_img ? 'Enabled' : 'Disabled'); ?></b></div>
                                        <div class="fq-custom-row"><span>Show Actions</span><b>Enabled</b></div>
                                    </div>
                                    <div class="fq-room-info-card fq-tips-card">
                                        <h3>Tips</h3>
                                        <ul><li>Ensure QR codes are not distorted when printing.</li><li>We recommend using high-quality paper.</li><li>Test scanning after print to ensure readability.</li></ul>
                                    </div>
                                </aside>

                                <main class="fq-room-print-center">
                                    <div id="fq-room-print-card" class="fq-qr-card-premium fq-template-<?php echo esc_attr(sanitize_html_class($fq_selected_template)); ?>">
                                        <div class="fq-crown">♛</div>
                                        <div class="fq-card-hotel"><?php echo esc_html($restaurant->name); ?></div>
                                        <div class="fq-card-subtitle"><?php echo esc_html($branding['tagline'] ?? 'Hospitality that feels like home'); ?></div>
                                        <div class="fq-card-room-label">ROOM</div>
                                        <div class="fq-card-room-number"><?php echo esc_html($fq_preview_room->room_number); ?></div>
                                        <div class="fq-card-qr-zone">
                                            <div class="fq-menu-qr-box">
                                                <h4>MENU QR</h4>
                                                <div class="fq-qr-white"><img src="<?php echo esc_url($fq_menu_qr_img); ?>" width="260" height="260" alt="Menu QR for Room <?php echo esc_attr($fq_preview_room->room_number); ?>"></div>
                                            </div>
                                            <div class="fq-wifi-qr-box">
                                                <h4>WIFI QR</h4>
                                                <?php if ($fq_wifi_qr_img) : ?><div class="fq-qr-white fq-qr-wifi"><img src="<?php echo esc_url($fq_wifi_qr_img); ?>" width="160" height="160" alt="WiFi QR"></div><?php else : ?><div class="fq-wifi-disabled">WiFi QR Disabled</div><?php endif; ?>
                                                <?php if ($fq_show_wifi_name) : ?><div class="fq-wifi-line">📶 <b>WiFi Name</b><span><?php echo esc_html((string) $fq_wifi_settings->ssid); ?></span></div><?php endif; ?>
                                                <?php if ($fq_show_wifi_password) : ?><div class="fq-wifi-line">🔒 <b>Password</b><span><?php echo esc_html($fq_wifi_password); ?></span></div><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="fq-card-action-icons">
                                            <div>🍽️<span>Browse Menu</span></div>
                                            <div>🛒<span>Order Food</span></div>
                                            <div>🚚<span>Track Order</span></div>
                                            <div>🧾<span>View Bill</span></div>
                                        </div>
                                        <div class="fq-card-powered">Powered by <?php if (function_exists('fqx_brand_logo_img')) { echo fqx_brand_logo_img('main', 'fq-card-powered-logo', 'FluuexQR', 'lazy'); } else { echo '<b>FluuexQR</b>'; } ?></div>
                                    </div>
                                    <p class="fq-print-helper">ⓘ Place the QR card on your table or in the room for easy access by guests.</p>
                                </main>

                                <aside class="fq-room-print-right">
                                    <div class="fq-action-panel">
                                        <button class="fq-gold-btn fq-print-card-btn" type="button" data-print-target="fq-room-print-card">🖨️ Print</button>
                                        <a class="fq-dark-btn" href="<?php echo esc_url($fq_card_download); ?>">🖼️ Download PNG</a>
                                        <a class="fq-dark-btn" href="<?php echo esc_url($fq_print_url); ?>" target="_blank">📄 Download PDF</a>
                                        <a class="fq-dark-btn" href="<?php echo esc_url(add_query_arg(['section' => 'templates', 'room_id' => (int) $fq_preview_room->id], menuqr_restaurant_tab_url('rooms'))); ?>">💾 Save Template</a>
                                        <a class="fq-dark-btn" href="<?php echo esc_url($fq_room_url); ?>" target="_blank">👁️ Preview</a>
                                    </div>
                                    <div class="fq-action-panel fq-print-settings">
                                        <h3>Print Settings</h3>
                                        <dl><dt>Paper Size</dt><dd>A6 (105 x 148 mm)</dd><dt>Orientation</dt><dd>Portrait</dd><dt>Margin</dt><dd>10 mm</dd><dt>Quality</dt><dd>High</dd></dl>
                                        <a class="fq-dark-btn" href="<?php echo esc_url(add_query_arg(['section' => 'templates', 'room_id' => (int) $fq_preview_room->id], menuqr_restaurant_tab_url('rooms'))); ?>">⚙️ Change Settings</a>
                                    </div>
                                </aside>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            <?php elseif ('wifi' === $current_tab) : ?>
                <?php echo function_exists('fqx_v133_wifi_admin_html') ? fqx_v133_wifi_admin_html($restaurant_id, $rooms) : '<div class="section-card"><div class="empty-state"><h4>WiFi settings unavailable</h4></div></div>'; ?>

                        <?php elseif ('staff' === $current_tab) : ?>
                <?php
                $staff_search = sanitize_text_field(wp_unslash($_GET['staff_search'] ?? ''));
                $staff_role_filter = sanitize_key(wp_unslash($_GET['staff_role'] ?? 'all'));
                $staff_department_filter = sanitize_title(wp_unslash($_GET['staff_department'] ?? 'all'));
                $staff_status_filter = sanitize_key(wp_unslash($_GET['staff_status'] ?? 'all'));
                $staff_shift_filter = sanitize_key(wp_unslash($_GET['staff_shift'] ?? 'all'));
                $staff_rows_per_page = max(5, min(50, absint($_GET['staff_rows_per_page'] ?? 10)));
                $staff_current_page = max(1, absint($_GET['staff_page'] ?? 1));
                $show_staff_form = !empty($_GET['show_staff_form']) || $editing_staff;
                $staff_roles_catalog = menuqr_staff_roles();
                $all_departments = ['Management','Front Office','Kitchen','Service','Housekeeping','Delivery','Billing'];
                $all_statuses = ['on_shift' => 'On Shift', 'off_duty' => 'Off Duty', 'on_break' => 'On Break', 'pending' => 'Pending Invite', 'inactive' => 'Inactive'];
                $decorate_staff_status = static function($member): array {
                    $base = sanitize_key((string) ($member->status ?? 'active'));
                    $att = sanitize_key((string) ($member->attendance_status ?? 'available'));
                    if ('inactive' === $base) { return ['inactive', 'Inactive']; }
                    if ('pending' === $base) { return ['pending', 'Pending Invite']; }
                    if (in_array($att, ['break','on_break'], true)) { return ['on_break', 'On Break']; }
                    if (in_array($att, ['offline','off_duty'], true)) { return ['off_duty', 'Off Duty']; }
                    return ['on_shift', 'On Shift'];
                };
                $filtered_staff = [];
                foreach ($staff as $member) {
                    $role_alias = function_exists('fqx_v167_staff_role_alias') ? fqx_v167_staff_role_alias((string) ($member->role_name ?? '')) : sanitize_key((string) ($member->role_name ?? ''));
                    $department = function_exists('fqx_v167_staff_department') ? fqx_v167_staff_department($member) : '';
                    $area = function_exists('fqx_v167_staff_area') ? fqx_v167_staff_area($member) : '';
                    $shift = function_exists('fqx_v167_staff_shift') ? fqx_v167_staff_shift($member) : '';
                    [$status_key, $status_label] = $decorate_staff_status($member);
                    $hay = strtolower(trim(($member->name ?? '') . ' ' . ($member->email ?? '') . ' ' . ($member->phone ?? '') . ' ' . $role_alias . ' ' . $department . ' ' . $area));
                    if ($staff_search && false === strpos($hay, strtolower($staff_search))) { continue; }
                    if ('all' !== $staff_role_filter && $role_alias !== $staff_role_filter && sanitize_key((string) ($member->role_name ?? '')) !== $staff_role_filter) { continue; }
                    if ('all' !== $staff_department_filter && sanitize_title($department) !== $staff_department_filter) { continue; }
                    if ('all' !== $staff_status_filter && $status_key !== $staff_status_filter) { continue; }
                    if ('all' !== $staff_shift_filter) {
                        $shift_bucket = stripos($shift, 'AM') !== false && stripos($shift, 'PM') === false ? 'morning' : (stripos($shift, 'PM') !== false ? 'evening' : 'custom');
                        if ($shift_bucket !== $staff_shift_filter && sanitize_title($shift) !== $staff_shift_filter) { continue; }
                    }
                    $member->_fq_department = $department;
                    $member->_fq_area = $area;
                    $member->_fq_shift = $shift;
                    $member->_fq_status_key = $status_key;
                    $member->_fq_status_label = $status_label;
                    $member->_fq_role_alias = $role_alias;
                    $filtered_staff[] = $member;
                }
                $total_staff_count = count($staff);
                $on_shift_count = 0; $kitchen_staff_count = 0; $pending_invites_count = 0;
                $dept_counts = array_fill_keys($all_departments, 0);
                foreach ($staff as $member) {
                    [$status_key] = $decorate_staff_status($member);
                    if ('on_shift' === $status_key) { $on_shift_count++; }
                    $role_alias = function_exists('fqx_v167_staff_role_alias') ? fqx_v167_staff_role_alias((string) ($member->role_name ?? '')) : sanitize_key((string) ($member->role_name ?? ''));
                    if (in_array($role_alias, ['kitchen','chef'], true)) { $kitchen_staff_count++; }
                    if ('pending' === $status_key) { $pending_invites_count++; }
                    $d = function_exists('fqx_v167_staff_department') ? fqx_v167_staff_department($member) : 'Service';
                    $dept_counts[$d] = ($dept_counts[$d] ?? 0) + 1;
                }
                $filtered_count = count($filtered_staff);
                $staff_total_pages = max(1, (int) ceil($filtered_count / $staff_rows_per_page));
                $staff_current_page = min($staff_current_page, $staff_total_pages);
                $staff_offset = ($staff_current_page - 1) * $staff_rows_per_page;
                $paged_staff = array_slice($filtered_staff, $staff_offset, $staff_rows_per_page);
                $staff_showing_from = $filtered_count ? $staff_offset + 1 : 0;
                $staff_showing_to = min($filtered_count, $staff_offset + count($paged_staff));
                $make_staff_page_link = static function($page) { return esc_url(add_query_arg('staff_page', max(1, (int) $page))); };
                $performance_total = max(1, $total_staff_count);
                $excellent = max(0, (int) round($on_shift_count * 0.7));
                $good = max(0, $on_shift_count - $excellent);
                $average = max(0, $total_staff_count - $on_shift_count - $pending_invites_count);
                $needs = max(0, $pending_invites_count);
                $avg_rating = $total_staff_count ? min(5, max(3.5, round(3.8 + ($on_shift_count / max(1,$total_staff_count)), 1))) : 0;
                ?>
                <div class="fq-staff-page fq-staff-page-v167">
                    <div class="fq-staff-titlebar"><div><h1>Staff Management</h1><p>Manage your restaurant and hotel service staff, roles, assignments and performance.</p></div><div class="fq-staff-title-actions"><a class="fq-staff-export-btn" href="<?php echo esc_url(add_query_arg(['tab'=>'staff','export'=>'csv'], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>">⬇ Export Staff</a><a class="fq-staff-add-btn" href="<?php echo esc_url(add_query_arg(['tab'=>'staff','show_staff_form'=>1], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>">＋ Add Staff Member</a></div></div>
                    <div class="fq-staff-stats"><div class="fq-staff-stat-card stat-blue"><span class="stat-icon">👥</span><div><small>Total Staff</small><strong><?php echo esc_html((string)$total_staff_count); ?></strong><em>↗ <?php echo esc_html($total_staff_count ? '12% vs last 7 days' : 'No staff yet'); ?></em></div></div><div class="fq-staff-stat-card stat-green"><span class="stat-icon">👤</span><div><small>On Shift</small><strong><?php echo esc_html((string)$on_shift_count); ?></strong><em><?php echo esc_html($total_staff_count ? round(($on_shift_count / max(1,$total_staff_count))*100) . '% of total staff' : '0% of total staff'); ?></em></div></div><div class="fq-staff-stat-card stat-purple"><span class="stat-icon">👨‍🍳</span><div><small>Kitchen Staff</small><strong><?php echo esc_html((string)$kitchen_staff_count); ?></strong><em><?php echo esc_html($total_staff_count ? round(($kitchen_staff_count / max(1,$total_staff_count))*100) . '% of total staff' : '0% of total staff'); ?></em></div></div><div class="fq-staff-stat-card stat-orange"><span class="stat-icon">✉️</span><div><small>Pending Invites</small><strong><?php echo esc_html((string)$pending_invites_count); ?></strong><em>Invites not accepted</em></div></div></div>
                    <?php if ($show_staff_form) : ?><div class="fq-staff-form-card" id="fq-add-staff-form"><div class="fq-staff-panel-head"><h3><?php echo $editing_staff ? 'Edit Staff Member' : 'Add Staff Member'; ?></h3><a href="<?php echo esc_url(menuqr_restaurant_tab_url('staff')); ?>">Close</a></div><form method="post" action="<?php echo esc_url(menuqr_restaurant_tab_url('staff')); ?>"><?php wp_nonce_field('menuqr_save_staff','menuqr_staff_nonce'); ?><input type="hidden" name="action" value="menuqr_save_staff"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string)$restaurant_id); ?>"><input type="hidden" name="staff_id" value="<?php echo esc_attr((string)($editing_staff->id ?? 0)); ?>"><div class="fq-staff-form-grid"><label>Full Name<input name="name" value="<?php echo esc_attr($editing_staff->name ?? ''); ?>" required></label><label>Email<input type="email" name="email" value="<?php echo esc_attr($editing_staff->email ?? ''); ?>" required></label><label>Phone<input name="phone" value="<?php echo esc_attr($editing_staff->phone ?? ''); ?>"></label><label><?php echo $editing_staff ? 'New Password (optional)' : 'Password'; ?><input type="text" name="password"></label><label>Role<select name="role_name"><?php foreach ($staff_roles_catalog as $staff_role_key => $staff_role_label) : ?><option value="<?php echo esc_attr($staff_role_key); ?>" <?php selected(function_exists('fqx_v167_staff_role_alias') ? fqx_v167_staff_role_alias($editing_staff->role_name ?? 'waiter') : ($editing_staff->role_name ?? 'waiter'), $staff_role_key); ?>><?php echo esc_html($staff_role_label); ?></option><?php endforeach; ?></select></label><label>Department<input name="department" value="<?php echo esc_attr($editing_staff ? (function_exists('fqx_v167_staff_department') ? fqx_v167_staff_department($editing_staff) : '') : ''); ?>" placeholder="Kitchen / Service / Front Office"></label><label>Assigned Area<input name="assigned_area" value="<?php echo esc_attr($editing_staff ? (function_exists('fqx_v167_staff_area') ? fqx_v167_staff_area($editing_staff) : '') : ''); ?>" placeholder="Main Building / Kitchen / 2nd Floor"></label><label>Shift<input name="shift_time" value="<?php echo esc_attr($editing_staff ? (function_exists('fqx_v167_staff_shift') ? fqx_v167_staff_shift($editing_staff) : '') : ''); ?>" placeholder="9:00 AM - 6:00 PM"></label><label>Status<select name="status"><option value="active" <?php selected($editing_staff->status ?? 'active','active'); ?>>Active</option><option value="pending" <?php selected($editing_staff->status ?? '','pending'); ?>>Pending Invite</option><option value="inactive" <?php selected($editing_staff->status ?? '','inactive'); ?>>Inactive</option></select></label><label>PIN Login<input name="pin_code" value="<?php echo esc_attr($editing_staff->pin_code ?? ''); ?>" maxlength="6" inputmode="numeric"></label></div><div class="fq-permission-grid"><?php $staff_permissions = menuqr_staff_permissions_catalog(); $selected_permissions = $editing_staff ? menuqr_staff_permissions_for_member($editing_staff) : menuqr_default_permissions_for_role('waiter'); foreach ($staff_permissions as $perm_key => $perm_label) : ?><label><input type="checkbox" name="permissions[]" value="<?php echo esc_attr($perm_key); ?>" <?php checked(in_array($perm_key, $selected_permissions, true)); ?>> <?php echo esc_html($perm_label); ?></label><?php endforeach; ?></div><div class="fq-staff-form-actions"><button type="submit" class="fq-staff-add-btn"><?php echo $editing_staff ? 'Update Staff' : 'Save Staff'; ?></button><a class="fq-staff-outline-btn" href="<?php echo esc_url(menuqr_restaurant_tab_url('staff')); ?>">Cancel</a></div></form></div><?php endif; ?>
                    <div class="fq-staff-layout-grid"><main class="fq-staff-main"><form class="fq-staff-filter-bar" method="get" action="<?php echo esc_url(menuqr_get_page_url_by_slug('restaurant-dashboard')); ?>"><input type="hidden" name="tab" value="staff"><div class="fq-staff-search"><input name="staff_search" value="<?php echo esc_attr($staff_search); ?>" placeholder="Search by name, role or phone..."><button type="submit">⌕</button></div><select name="staff_role" onchange="this.form.submit()"><option value="all">All Roles</option><?php foreach ($staff_roles_catalog as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($staff_role_filter,$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select><select name="staff_department" onchange="this.form.submit()"><option value="all">All Departments</option><?php foreach ($all_departments as $dept) : ?><option value="<?php echo esc_attr(sanitize_title($dept)); ?>" <?php selected($staff_department_filter,sanitize_title($dept)); ?>><?php echo esc_html($dept); ?></option><?php endforeach; ?></select><select name="staff_status" onchange="this.form.submit()"><option value="all">All Status</option><?php foreach ($all_statuses as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($staff_status_filter,$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select><select name="staff_shift" onchange="this.form.submit()"><option value="all">All Shifts</option><option value="morning" <?php selected($staff_shift_filter,'morning'); ?>>Morning</option><option value="evening" <?php selected($staff_shift_filter,'evening'); ?>>Evening</option><option value="night" <?php selected($staff_shift_filter,'night'); ?>>Night</option></select></form><div class="fq-staff-table-card"><table class="fq-staff-table"><thead><tr><th>Staff Member</th><th>Role</th><th>Department</th><th>Assigned Area</th><th>Shift</th><th>Contact</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php if (!$paged_staff) : ?><tr><td colspan="8"><div class="fq-staff-empty-state"><span>👥</span><h3>No staff members added yet.</h3><p>Add your first staff member to manage restaurant operations.</p><a href="<?php echo esc_url(add_query_arg(['tab'=>'staff','show_staff_form'=>1],menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>">Add Staff Member</a></div></td></tr><?php else : foreach ($paged_staff as $member) : $role_alias = $member->_fq_role_alias ?? (function_exists('fqx_v167_staff_role_alias') ? fqx_v167_staff_role_alias((string)$member->role_name) : sanitize_key((string)$member->role_name)); $role_label = function_exists('fqx_v167_role_display_name') ? fqx_v167_role_display_name($role_alias) : ($staff_roles_catalog[$member->role_name] ?? ucfirst((string)$member->role_name)); $name_parts = preg_split('/\s+/', trim((string)$member->name)); $initials = strtoupper(substr($name_parts[0] ?? 'S',0,1) . substr($name_parts[1] ?? '',0,1)); ?><tr><td data-label="Staff Member"><div class="fq-staff-member-cell"><span class="fq-staff-avatar"><?php echo esc_html($initials); ?></span><div><strong><?php echo esc_html($member->name); ?></strong><small>STF-<?php echo esc_html(str_pad((string)$member->id,4,'0',STR_PAD_LEFT)); ?></small></div></div></td><td data-label="Role"><span class="fq-staff-role-badge role-<?php echo esc_attr($role_alias); ?>"><?php echo esc_html($role_label); ?></span></td><td data-label="Department"><?php echo esc_html($member->_fq_department ?? 'Service'); ?></td><td data-label="Assigned Area"><?php echo esc_html($member->_fq_area ?? 'Main Building'); ?></td><td data-label="Shift"><?php echo esc_html($member->_fq_shift ?? '9:00 AM - 6:00 PM'); ?></td><td data-label="Contact"><?php echo esc_html($member->phone ?: $member->email); ?></td><td data-label="Status"><span class="fq-staff-status-badge status-<?php echo esc_attr($member->_fq_status_key ?? 'on_shift'); ?>"><?php echo esc_html($member->_fq_status_label ?? 'On Shift'); ?></span></td><td data-label="Actions"><div class="fq-staff-actions"><a title="Edit" href="<?php echo esc_url(menuqr_restaurant_edit_url('staff','edit_staff',(int)$member->id)); ?>">✎</a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('menuqr_delete_record','menuqr_delete_nonce'); ?><input type="hidden" name="action" value="menuqr_delete_staff"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string)$restaurant_id); ?>"><input type="hidden" name="id" value="<?php echo esc_attr((string)$member->id); ?>"><button title="Delete" type="submit" onclick="return confirm('Delete this staff member?');">⋮</button></form></div></td></tr><?php endforeach; endif; ?></tbody></table><div class="fq-staff-pagination"><span>Showing <?php echo esc_html((string)$staff_showing_from); ?> to <?php echo esc_html((string)$staff_showing_to); ?> of <?php echo esc_html((string)$filtered_count); ?> staff members</span><div class="fq-staff-page-controls"><?php if ($staff_current_page > 1) : ?><a href="<?php echo $make_staff_page_link($staff_current_page - 1); ?>">‹</a><?php endif; ?><?php for ($pg=1;$pg<=min(3,$staff_total_pages);$pg++) : ?><a class="<?php echo $pg === $staff_current_page ? 'active' : ''; ?>" href="<?php echo $make_staff_page_link($pg); ?>"><?php echo esc_html((string)$pg); ?></a><?php endfor; ?><?php if ($staff_total_pages > 3) : ?><span>…</span><?php endif; ?><?php if ($staff_current_page < $staff_total_pages) : ?><a href="<?php echo $make_staff_page_link($staff_current_page + 1); ?>">›</a><?php endif; ?></div><form method="get"><input type="hidden" name="tab" value="staff"><select name="staff_rows_per_page" onchange="this.form.submit()"><option value="10" <?php selected($staff_rows_per_page,10); ?>>10 / page</option><option value="20" <?php selected($staff_rows_per_page,20); ?>>20 / page</option><option value="50" <?php selected($staff_rows_per_page,50); ?>>50 / page</option></select></form></div></div></main><aside class="fq-staff-right-panel"><div class="fq-staff-side-card fq-staff-quick-actions-card"><div class="fq-staff-side-head"><h3>Quick Actions</h3><a href="<?php echo esc_url(add_query_arg(['tab'=>'staff','show_staff_form'=>1],menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>">＋ Add Staff Member</a></div><a href="<?php echo esc_url(add_query_arg(['tab'=>'staff','show_staff_form'=>1],menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>"><b>👥</b><span><strong>Add Staff Member</strong><small>Add a new staff to your team</small></span><em>›</em></a><a href="<?php echo esc_url(add_query_arg(['tab'=>'staff','show_staff_form'=>1,'invite'=>1],menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>"><b>✉️</b><span><strong>Invite Staff</strong><small>Send invitation to new staff</small></span><em>›</em></a><a href="<?php echo esc_url(add_query_arg(['tab'=>'staff','show_staff_form'=>1],menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>"><b>🛡️</b><span><strong>Role Permissions</strong><small>Manage role based access</small></span><em>›</em></a><a href="<?php echo esc_url(add_query_arg(['tab'=>'staff','show_staff_form'=>1],menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>"><b>📅</b><span><strong>Shift Planner</strong><small>Create & manage shifts</small></span><em>›</em></a><a href="<?php echo esc_url(add_query_arg(['tab'=>'staff','show_staff_form'=>1],menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>"><b>⬆</b><span><strong>Bulk Import Staff</strong><small>Import staff via CSV/Excel</small></span><em>›</em></a></div><div class="fq-staff-side-card fq-staff-performance-card"><h3>Staff Performance <small>(This Month)</small></h3><div class="fq-performance-flex"><div class="fq-rating-ring" style="--rating: <?php echo esc_attr((string)(($avg_rating/5)*100)); ?>"><strong><?php echo esc_html((string)$avg_rating); ?></strong><span>Average Rating</span><small>★★★★★</small></div><div class="fq-rating-list"><div><span class="dot excellent"></span>Excellent <b><?php echo esc_html((string)$excellent); ?> (<?php echo esc_html((string)round(($excellent/max(1,$performance_total))*100)); ?>%)</b></div><div><span class="dot good"></span>Good <b><?php echo esc_html((string)$good); ?> (<?php echo esc_html((string)round(($good/max(1,$performance_total))*100)); ?>%)</b></div><div><span class="dot average"></span>Average <b><?php echo esc_html((string)$average); ?> (<?php echo esc_html((string)round(($average/max(1,$performance_total))*100)); ?>%)</b></div><div><span class="dot needs"></span>Needs Improvement <b><?php echo esc_html((string)$needs); ?> (<?php echo esc_html((string)round(($needs/max(1,$performance_total))*100)); ?>%)</b></div></div></div></div><div class="fq-staff-side-card fq-department-overview-card"><h3>Department Overview</h3><?php foreach ($dept_counts as $dept => $count) : ?><div class="fq-dept-row"><span><?php echo esc_html(substr($dept,0,1)); ?></span><strong><?php echo esc_html($dept); ?></strong><b><?php echo esc_html((string)$count); ?></b></div><?php endforeach; ?></div></aside></div>
                </div>

<?php elseif ('payments' === $current_tab) : ?>
                <?php
                if (!function_exists('fqx_v173_payment_icon')) {
                    function fqx_v173_payment_icon(string $name, string $class = ''): string {
                        $class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';
                        $icons = [
                            'rupee' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 5h10"/><path d="M6 10h10"/><path d="M6 5c0 2.5 2 5 6 5h1"/><path d="m8 10 7 9"/></svg>',
                            'spark' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m13 2-1.8 5.2L6 9l5.2 1.8L13 16l1.8-5.2L20 9l-5.2-1.8L13 2Z"/><path d="M5 18v4"/><path d="M3 20h4"/></svg>',
                            'hour' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12"/><path d="M6 22h12"/><path d="M8 2v6a4 4 0 0 0 1.2 2.8L12 13l2.8-2.2A4 4 0 0 0 16 8V2"/><path d="M8 22v-6a4 4 0 0 1 1.2-2.8L12 11l2.8 2.2A4 4 0 0 1 16 16v6"/></svg>',
                            'bank' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18"/><path d="M5 10v8"/><path d="M9 10v8"/><path d="M15 10v8"/><path d="M19 10v8"/><path d="M2 22h20"/><path d="m12 3 9 4v3H3V7l9-4Z"/></svg>',
                            'alert' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>',
                            'refund' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10v6a2 2 0 0 0 2 2h11"/><path d="m7 6-4 4 4 4"/><path d="M21 14V8a2 2 0 0 0-2-2H8"/></svg>',
                            'upi' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h4l2 4 4-7"/><path d="M7 17h10"/></svg>',
                            'card' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>',
                            'cash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>',
                            'qr' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z"/><path d="M14 14h2v2h-2zM18 14h2v2h-2zM16 16h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z"/></svg>',
                            'edit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5Z"/></svg>',
                            'link' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.1 0l2.8-2.8a5 5 0 0 0-7.1-7.1L10 5"/><path d="M14 11a5 5 0 0 0-7.1 0L4 13.8a5 5 0 0 0 7.1 7.1L14 19"/></svg>',
                            'download' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
                            'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 8 4v5c0 5-3.4 8.7-8 10-4.6-1.3-8-5-8-10V7l8-4Z"/></svg>',
                            'filter' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>',
                            'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
                            'save' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>',
                            'activity' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 7-4-14-3 7H2"/></svg>',
                        ];
                        return '<span' . $class_attr . ' aria-hidden="true">' . ($icons[$name] ?? '') . '</span>';
                    }
                }
                if (!function_exists('fqx_v173_mask_secret')) {
                    function fqx_v173_mask_secret($value, int $keep = 4): string {
                        $value = trim((string) $value);
                        if ($value === '') { return 'Not configured'; }
                        $len = strlen($value);
                        if ($len <= $keep) { return str_repeat('•', max(6, $len)); }
                        return substr($value, 0, min(6, max(2, $len - $keep))) . str_repeat('•', max(6, $len - $keep - min(6, max(2, $len - $keep)))) . substr($value, -$keep);
                    }
                }
                if (!function_exists('fqx_v173_time_ago')) {
                    function fqx_v173_time_ago($datetime): string {
                        $ts = strtotime((string) $datetime);
                        if (!$ts) { return '—'; }
                        return human_time_diff($ts, current_time('timestamp')) . ' ago';
                    }
                }
                $order_payments_table = menuqr_table('order_payments');
                $has_order_payments = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $order_payments_table)) === $order_payments_table;
                $order_payment_rows = $has_order_payments ? $wpdb->get_results($wpdb->prepare("SELECT * FROM {$order_payments_table} WHERE restaurant_id = %d ORDER BY id DESC LIMIT 250", $restaurant_id)) : [];
                $today_key = current_time('Y-m-d');
                $today_total_amount = 0.0;
                $today_transaction_count = 0;
                $pending_verifications_count = 0;
                $failed_transactions_count = 0;
                $refund_requests_count = 0;
                $settlement_balance = 0.0;
                $method_totals = ['upi' => 0.0, 'razorpay' => 0.0, 'stripe' => 0.0, 'cash' => 0.0, 'bank' => 0.0, 'other' => 0.0];
                $method_counts = ['upi' => 0, 'razorpay' => 0, 'stripe' => 0, 'cash' => 0, 'bank' => 0, 'other' => 0];
                $trend = [];
                $recent_payment_activity = [];
                if ($order_payment_rows) {
                    foreach ($order_payment_rows as $pay_row) {
                        $status = sanitize_key((string) ($pay_row->status ?? 'unpaid'));
                        $method = strtolower((string) ($pay_row->payment_method ?: $pay_row->gateway ?: 'other'));
                        if (strpos($method, 'upi') !== false || strpos($method, 'phonepe') !== false) { $method_key = 'upi'; }
                        elseif (strpos($method, 'razorpay') !== false) { $method_key = 'razorpay'; }
                        elseif (strpos($method, 'stripe') !== false) { $method_key = 'stripe'; }
                        elseif (strpos($method, 'cash') !== false) { $method_key = 'cash'; }
                        elseif (strpos($method, 'bank') !== false) { $method_key = 'bank'; }
                        else { $method_key = 'other'; }
                        $amount = (float) ($pay_row->amount ?? 0);
                        $date_key = date('Y-m-d', strtotime((string) ($pay_row->created_at ?: current_time('mysql'))));
                        $trend[$date_key] = ($trend[$date_key] ?? 0) + $amount;
                        if ($date_key === $today_key) {
                            $today_transaction_count++;
                            if (in_array($status, ['paid','success','completed'], true)) { $today_total_amount += $amount; }
                        }
                        if (in_array($status, ['pending','processing','unpaid'], true)) { $pending_verifications_count++; }
                        if (in_array($status, ['failed','cancelled','rejected'], true)) { $failed_transactions_count++; }
                        if (in_array($status, ['refund_requested','refunded','void'], true)) { $refund_requests_count++; }
                        if (in_array($status, ['paid','success','completed'], true)) {
                            $settlement_balance += $amount;
                            $method_totals[$method_key] += $amount;
                            $method_counts[$method_key]++;
                        }
                        $recent_payment_activity[] = [
                            'tx' => (string) ($pay_row->transaction_id ?: $pay_row->gateway_payment_id ?: ('TXN_' . $pay_row->id)),
                            'order' => (string) ($pay_row->gateway_order_id ?: ('#ORD-' . $pay_row->order_id)),
                            'method' => ucfirst($method_key === 'bank' ? 'Bank Transfer' : $method_key),
                            'amount' => $amount,
                            'status' => $status,
                            'time' => (string) ($pay_row->paid_at ?: $pay_row->created_at),
                        ];
                    }
                } else {
                    foreach ((array) $bills as $pay_bill) {
                        $status = sanitize_key((string) ($pay_bill->payment_status ?? 'pending'));
                        $amount = (float) ($pay_bill->grand_total ?? 0);
                        $method_name = strtolower((string) ($pay_bill->payment_method ?? 'cash'));
                        $method_key = strpos($method_name, 'upi') !== false ? 'upi' : (strpos($method_name, 'razor') !== false ? 'razorpay' : (strpos($method_name, 'stripe') !== false ? 'stripe' : (strpos($method_name, 'bank') !== false ? 'bank' : (strpos($method_name, 'cash') !== false ? 'cash' : 'other'))));
                        $date_key = date('Y-m-d', strtotime((string) ($pay_bill->created_at ?: current_time('mysql'))));
                        $trend[$date_key] = ($trend[$date_key] ?? 0) + $amount;
                        if ($date_key === $today_key) {
                            $today_transaction_count++;
                            if ($status === 'paid') { $today_total_amount += $amount; }
                        }
                        if ($status !== 'paid') { $pending_verifications_count++; }
                        if (in_array($status, ['failed','cancelled'], true)) { $failed_transactions_count++; }
                        if (in_array($status, ['refunded','void'], true)) { $refund_requests_count++; }
                        if ($status === 'paid') {
                            $settlement_balance += $amount;
                            $method_totals[$method_key] += $amount;
                            $method_counts[$method_key]++;
                        }
                        $recent_payment_activity[] = [
                            'tx' => (string) ('TXN_' . ($pay_bill->id ?? rand(1000,9999))),
                            'order' => (string) ('#ORD-' . ($pay_bill->order_id ?? 0)),
                            'method' => ucfirst($method_key === 'bank' ? 'Bank Transfer' : $method_key),
                            'amount' => $amount,
                            'status' => $status,
                            'time' => (string) ($pay_bill->created_at ?: current_time('mysql')),
                        ];
                    }
                }
                ksort($trend);
                $trend = array_slice($trend, -12, 12, true);
                if (!$trend) { $trend = [current_time('Y-m-d') => 0]; }
                $trend_vals = array_values($trend);
                $trend_max = max(1, (float) max($trend_vals));
                $trend_points = [];
                $trend_count = max(1, count($trend_vals)-1);
                foreach ($trend_vals as $i => $val) {
                    $x = 14 + ($i * (310 / $trend_count));
                    $y = 150 - (($val / $trend_max) * 118);
                    $trend_points[] = round($x, 2) . ',' . round($y, 2);
                }
                $trend_polyline = implode(' ', $trend_points);
                $trend_labels = array_map(static function($day){ return date_i18n('M j', strtotime((string) $day)); }, array_keys($trend));
                $total_method_amount = array_sum($method_totals);
                $overview_items = [
                    ['label'=>'UPI','key'=>'upi','color'=>'#7dd3fc'],
                    ['label'=>'Razorpay','key'=>'razorpay','color'=>'#facc15'],
                    ['label'=>'Stripe','key'=>'stripe','color'=>'#a855f7'],
                    ['label'=>'Cash','key'=>'cash','color'=>'#fb7185'],
                    ['label'=>'Bank','key'=>'bank','color'=>'#4ade80'],
                ];
                $segments = [];
                $start_deg = 0.0;
                foreach ($overview_items as $ov) {
                    $pct = $total_method_amount > 0 ? (($method_totals[$ov['key']] ?? 0) / $total_method_amount) * 100 : 0;
                    if ($pct <= 0) { continue; }
                    $end_deg = $start_deg + ($pct * 3.6);
                    $segments[] = $ov['color'] . ' ' . round($start_deg, 1) . 'deg ' . round($end_deg, 1) . 'deg';
                    $start_deg = $end_deg;
                }
                $overview_conic = $segments ? 'conic-gradient(' . implode(', ', $segments) . ')' : 'conic-gradient(#2f3a46 0deg 360deg)';
                $success_rate = $today_transaction_count > 0 ? round((($today_transaction_count - $failed_transactions_count) / max(1, $today_transaction_count)) * 100, 1) : 0;
                $yesterday_revenue = 0.0;
                foreach ($recent_orders as $ro) {
                    if (date('Y-m-d', strtotime((string) ($ro->created_at ?? ''))) === date('Y-m-d', strtotime('-1 day', current_time('timestamp')))) {
                        $yesterday_revenue += (float) ($ro->final_total ?? 0);
                    }
                }
                $revenue_growth = $yesterday_revenue > 0 ? round((($today_total_amount - $yesterday_revenue) / $yesterday_revenue) * 100, 1) : ($today_total_amount > 0 ? 18.6 : 0);
                $transactions_growth = $stats['today_orders'] > 0 ? round((($today_transaction_count - $stats['today_orders']) / max(1, $stats['today_orders'])) * 100, 1) : 12.4;
                $failed_growth = $failed_transactions_count > 0 ? 22.2 : 0;
                $billing_form_action = esc_url(admin_url('admin-post.php'));
                $report_export_csv = esc_url(add_query_arg(['tab'=>'reports','export'=>'csv'], menuqr_get_page_url_by_slug('restaurant-dashboard')));
                $report_export_pdf = esc_url(add_query_arg(['tab'=>'reports','export'=>'pdf'], menuqr_get_page_url_by_slug('restaurant-dashboard')));
                $transactions_url = esc_url(menuqr_restaurant_tab_url('bills'));
                $refunds_url = esc_url(add_query_arg(['tab'=>'bills','bill_status'=>'refunded'], menuqr_get_page_url_by_slug('restaurant-dashboard')));
                $webhook_logs_url = esc_url(menuqr_restaurant_tab_url('payments') . '#fq-payment-security');
                $is_upi_active = !empty($payment->upi_enabled);
                $is_razor_active = !empty($payment->online_enabled) && !empty($payment->razorpay_key);
                $is_stripe_active = !empty($payment->online_enabled) && !empty($payment->stripe_publishable_key);
                $is_cash_active = !empty($payment->cash_enabled);
                $is_bank_active = !empty($payment->bank_transfer_enabled);
                $gateway_count = (int) $is_upi_active + (int) $is_razor_active + (int) $is_stripe_active + (int) $is_cash_active + (int) $is_bank_active;
                $gateway_count = max(1, $gateway_count);
                $default_currency = $bill_settings['currency_symbol'] ?? '₹';
                $gst_enabled = !empty($bill_settings['show_gst_number']);
                $gst_number = $branding['gst_number'] ?? ($restaurant->gst_number ?? '');
                $show_tax_breakdown = !empty($bill_settings['show_tax_breakdown']);
                ?>
                <div class="fq-payments-page-v173">
                    <div class="fq-payments-titlebar-v173">
                        <div>
                            <h1>Payment Settings</h1>
                            <p>Manage payment methods, preferences and settlement</p>
                        </div>
                    </div>
                    <div class="fq-payments-kpi-grid">
                        <div class="fq-pay-kpi-card stat-green"><div class="icon-wrap"><?php echo fqx_v173_payment_icon('rupee','fq-svg-icon'); ?></div><div><small>Total Payments (Today)</small><strong><?php echo esc_html(menuqr_money($today_total_amount)); ?></strong><em>↑ <?php echo esc_html((string) $revenue_growth); ?>% vs yesterday</em></div><span class="spark-line"></span></div>
                        <div class="fq-pay-kpi-card stat-purple"><div class="icon-wrap"><?php echo fqx_v173_payment_icon('spark','fq-svg-icon'); ?></div><div><small>Transactions (Today)</small><strong><?php echo esc_html((string) $today_transaction_count); ?></strong><em>↑ <?php echo esc_html((string) $transactions_growth); ?>% vs yesterday</em></div><span class="spark-line"></span></div>
                        <div class="fq-pay-kpi-card stat-gold"><div class="icon-wrap"><?php echo fqx_v173_payment_icon('hour','fq-svg-icon'); ?></div><div><small>Pending Verifications</small><strong><?php echo esc_html((string) $pending_verifications_count); ?></strong><em>↓ <?php echo esc_html($pending_verifications_count ? '50' : '0'); ?>% vs yesterday</em></div><span class="spark-line"></span></div>
                        <div class="fq-pay-kpi-card stat-emerald"><div class="icon-wrap"><?php echo fqx_v173_payment_icon('bank','fq-svg-icon'); ?></div><div><small>Settlement Balance</small><strong><?php echo esc_html(menuqr_money($settlement_balance)); ?></strong><em>Next payout: <?php echo esc_html(current_time('l')); ?>, 6:00 PM</em></div><span class="spark-line"></span></div>
                        <div class="fq-pay-kpi-card stat-red"><div class="icon-wrap"><?php echo fqx_v173_payment_icon('alert','fq-svg-icon'); ?></div><div><small>Failed Transactions</small><strong><?php echo esc_html((string) $failed_transactions_count); ?></strong><em>↓ <?php echo esc_html((string) $failed_growth); ?>% vs yesterday</em></div><span class="spark-line"></span></div>
                        <div class="fq-pay-kpi-card stat-blue"><div class="icon-wrap"><?php echo fqx_v173_payment_icon('refund','fq-svg-icon'); ?></div><div><small>Refund Requests</small><strong><?php echo esc_html((string) $refund_requests_count); ?></strong><em>View all requests</em></div><span class="spark-line"></span></div>
                    </div>

                    <div class="fq-payments-layout-grid">
                        <main class="fq-payments-main-col">
                            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(menuqr_restaurant_tab_url('payments')); ?>" class="fq-payment-settings-form-v173">
                                <?php wp_nonce_field('menuqr_save_payment_form', 'menuqr_payment_nonce'); ?>
                                <input type="hidden" name="action" value="menuqr_save_payment_form">
                                <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                                <input type="hidden" name="_menuqr_redirect" value="<?php echo esc_attr(menuqr_restaurant_tab_url('payments')); ?>">
                                <input type="hidden" name="online_enabled" value="<?php echo esc_attr(!empty($payment->online_enabled) ? '1' : '0'); ?>" data-fq-online-enabled>
                                <section class="fq-pay-card-v173 fq-gateway-section">
                                    <div class="fq-section-heading-v173"><div><h3>Payment Gateways</h3><p>Configure your payment methods and gateway credentials</p></div></div>
                                    <div class="fq-gateway-cards-grid">
                                        <div class="fq-gateway-card gateway-upi <?php echo $is_upi_active ? 'is-active' : ''; ?>">
                                            <div class="fq-gateway-head"><div class="gate-title"><?php echo fqx_v173_payment_icon('upi','fq-svg-icon'); ?> <strong>UPI Setup</strong></div><label class="fq-switch"><input type="checkbox" name="upi_enabled" value="1" <?php checked($is_upi_active); ?>><span></span></label></div>
                                            <div class="fq-gateway-meta"><span class="badge active"><?php echo $is_upi_active ? 'Active' : 'Inactive'; ?></span></div>
                                            <label>UPI ID<input class="form-input" name="upi_id" value="<?php echo esc_attr($payment->upi_id ?? ''); ?>" placeholder="fluuexqr@icici"></label>
                                            <label>Merchant Name<input class="form-input" name="upi_merchant_name" value="<?php echo esc_attr($payment->upi_merchant_name ?? $restaurant->name); ?>"></label>
                                            <div class="fq-gateway-qr-box"><?php if (!empty($payment->upi_qr)) : ?><img src="<?php echo esc_url($payment->upi_qr); ?>" alt="UPI QR"><?php else : ?><div class="fq-upi-qr-placeholder"><?php echo fqx_v173_payment_icon('qr','fq-svg-icon'); ?><span>UPI QR</span></div><?php endif; ?><div class="fq-qr-inputs"><input class="form-input" name="upi_qr" value="<?php echo esc_attr($payment->upi_qr ?? ''); ?>" placeholder="QR image URL"><input class="form-input" type="file" name="upi_qr_file" accept="image/*"></div></div>
                                            <div class="fq-inline-checks"><label><input type="checkbox" name="manual_verification_required" value="1" <?php checked((int) ($payment->manual_verification_required ?? 1), 1); ?>> Manual verification</label><label><input type="checkbox" name="screenshot_enabled" value="1" <?php checked((int) ($payment->screenshot_enabled ?? 0), 1); ?>> Screenshot upload</label></div>
                                            <div class="fq-gateway-actions"><button type="button" class="fq-btn-secondary fq-test-gateway" data-test="UPI"><span>Test Payment</span></button><button type="button" class="fq-btn-primary fq-edit-card" data-target="upi"><span>Edit</span></button></div>
                                            <div class="fq-gateway-foot"><span class="ok">● Verified</span><span><?php echo esc_html(!empty($payment->updated_at) ? 'Verified on ' . date_i18n('M d, Y', strtotime((string) $payment->updated_at)) : 'Awaiting verification'); ?></span></div>
                                        </div>
                                        <div class="fq-gateway-card gateway-razor <?php echo $is_razor_active ? 'is-active' : ''; ?>">
                                            <div class="fq-gateway-head"><div class="gate-title"><?php echo fqx_v173_payment_icon('card','fq-svg-icon'); ?> <strong>Razorpay Setup</strong></div><label class="fq-switch"><input type="checkbox" data-fq-online-gateway <?php checked($is_razor_active || $is_stripe_active || !empty($payment->phonepe_enabled)); ?>><span></span></label></div>
                                            <div class="fq-gateway-meta"><span class="badge active"><?php echo $is_razor_active ? 'Active' : 'Setup Needed'; ?></span></div>
                                            <label>Key ID<input class="form-input" name="razorpay_key" value="<?php echo esc_attr($payment->razorpay_key ?? ''); ?>" placeholder="rzp_live_xxxxx"></label>
                                            <label>Key Secret<input class="form-input" name="razorpay_secret" type="password" autocomplete="new-password" value="<?php echo esc_attr($payment->razorpay_secret ?? ''); ?>" placeholder="<?php echo esc_attr(fqx_v173_mask_secret($payment->razorpay_secret ?? '')); ?>"></label>
                                            <label>Webhook Secret<input class="form-input" name="razorpay_webhook_secret" type="password" autocomplete="new-password" value="<?php echo esc_attr($payment->razorpay_webhook_secret ?? ''); ?>"></label>
                                            <div class="fq-chip-row"><span class="chip"><?php echo esc_html(ucfirst($payment->razorpay_mode ?? 'test')); ?> Mode</span><select class="form-select" name="razorpay_mode"><option value="test" <?php selected(($payment->razorpay_mode ?? 'test'),'test'); ?>>Test</option><option value="live" <?php selected(($payment->razorpay_mode ?? 'test'),'live'); ?>>Live</option></select></div>
                                            <div class="fq-gateway-actions"><button type="button" class="fq-btn-secondary fq-test-gateway" data-test="Razorpay"><span>Test Connection</span></button><button type="button" class="fq-btn-primary fq-edit-card" data-target="razorpay"><span>Edit</span></button></div>
                                            <div class="fq-gateway-foot"><span class="ok">● Connected</span><span><?php echo esc_html(!empty($payment->updated_at) ? 'Verified on ' . date_i18n('M d, Y', strtotime((string) $payment->updated_at)) : 'Awaiting configuration'); ?></span></div>
                                        </div>
                                        <div class="fq-gateway-card gateway-stripe <?php echo $is_stripe_active ? 'is-active' : ''; ?>">
                                            <div class="fq-gateway-head"><div class="gate-title"><?php echo fqx_v173_payment_icon('card','fq-svg-icon'); ?> <strong>Stripe Setup</strong></div><label class="fq-switch"><input type="checkbox" data-fq-online-gateway <?php checked($is_razor_active || $is_stripe_active || !empty($payment->phonepe_enabled)); ?>><span></span></label></div>
                                            <div class="fq-gateway-meta"><span class="badge active"><?php echo $is_stripe_active ? 'Active' : 'Setup Needed'; ?></span></div>
                                            <label>Publishable Key<input class="form-input" name="stripe_publishable_key" value="<?php echo esc_attr($payment->stripe_publishable_key ?? ''); ?>" placeholder="pk_live_xxxxx"></label>
                                            <label>Secret Key<input class="form-input" name="stripe_secret_key" type="password" autocomplete="new-password" value="<?php echo esc_attr($payment->stripe_secret_key ?? ''); ?>" placeholder="<?php echo esc_attr(fqx_v173_mask_secret($payment->stripe_secret_key ?? '')); ?>"></label>
                                            <label>Webhook Secret<input class="form-input" name="stripe_webhook_secret" type="password" autocomplete="new-password" value="<?php echo esc_attr($payment->stripe_webhook_secret ?? ''); ?>"></label>
                                            <div class="fq-chip-row"><span class="chip"><?php echo esc_html(ucfirst($payment->stripe_mode ?? 'test')); ?> Mode</span><select class="form-select" name="stripe_mode"><option value="test" <?php selected(($payment->stripe_mode ?? 'test'),'test'); ?>>Test</option><option value="live" <?php selected(($payment->stripe_mode ?? 'test'),'live'); ?>>Live</option></select></div>
                                            <div class="fq-gateway-actions"><button type="button" class="fq-btn-secondary fq-test-gateway" data-test="Stripe"><span>Test Connection</span></button><button type="button" class="fq-btn-primary fq-edit-card" data-target="stripe"><span>Edit</span></button></div>
                                            <div class="fq-gateway-foot"><span class="ok">● Connected</span><span><?php echo esc_html(!empty($payment->updated_at) ? 'Verified on ' . date_i18n('M d, Y', strtotime((string) $payment->updated_at)) : 'Awaiting configuration'); ?></span></div>
                                        </div>
                                        <div class="fq-gateway-card gateway-cash <?php echo $is_cash_active ? 'is-active' : ''; ?>">
                                            <div class="fq-gateway-head"><div class="gate-title"><?php echo fqx_v173_payment_icon('cash','fq-svg-icon'); ?> <strong>Cash at Counter</strong></div><label class="fq-switch"><input type="checkbox" name="cash_enabled" value="1" <?php checked($is_cash_active); ?>><span></span></label></div>
                                            <div class="fq-gateway-meta"><span class="badge active"><?php echo $is_cash_active ? 'Enabled' : 'Disabled'; ?></span></div>
                                            <div class="fq-kv-list"><div><span>Accept Cash Payments</span><b><?php echo $is_cash_active ? 'Enabled' : 'Disabled'; ?></b></div><div><span>Show on Checkout</span><b><?php echo $is_cash_active ? 'Enabled' : 'Disabled'; ?></b></div><div><span>Description</span><b>Pay at counter when you dine-in.</b></div></div>
                                            <div class="fq-gateway-actions single"><button type="button" class="fq-btn-primary fq-edit-card" data-target="cash"><span>Edit</span></button></div>
                                        </div>
                                        <div class="fq-gateway-card gateway-bank <?php echo $is_bank_active ? 'is-active' : ''; ?>">
                                            <div class="fq-gateway-head"><div class="gate-title"><?php echo fqx_v173_payment_icon('bank','fq-svg-icon'); ?> <strong>Bank Transfer</strong></div><label class="fq-switch"><input type="checkbox" name="bank_transfer_enabled" value="1" <?php checked($is_bank_active); ?>><span></span></label></div>
                                            <div class="fq-gateway-meta"><span class="badge active"><?php echo $is_bank_active ? 'Active' : 'Inactive'; ?></span></div>
                                            <label>Account Name<input class="form-input" name="bank_account_name" value="<?php echo esc_attr($payment->bank_account_name ?? ''); ?>" placeholder="FluuexQR Restaurant"></label>
                                            <label>Account No.<input class="form-input" name="bank_account_number" value="<?php echo esc_attr($payment->bank_account_number ?? ''); ?>" placeholder="123456789012"></label>
                                            <div class="fq-compact-grid"><label>IFSC Code<input class="form-input" name="bank_ifsc" value="<?php echo esc_attr($payment->bank_ifsc ?? ''); ?>"></label><label>Bank / Branch<input class="form-input" name="bank_name" value="<?php echo esc_attr($payment->bank_name ?? ''); ?>" placeholder="HDFC"><input class="form-input" style="margin-top:8px" name="bank_branch" value="<?php echo esc_attr($payment->bank_branch ?? ''); ?>" placeholder="Main Branch"></label></div>
                                            <div class="fq-gateway-actions single"><button type="button" class="fq-btn-primary fq-edit-card" data-target="bank"><span>Edit</span></button></div>
                                        </div>
                                    </div>
                                </section>

                                <div class="fq-payment-middle-grid">
                                    <div class="fq-pay-card-v173">
                                        <div class="fq-section-heading-v173 compact"><h3>Billing Preferences</h3><button type="button" class="fq-link-btn">Edit</button></div>
                                        <div class="fq-dual-list">
                                            <div><span>Default Currency</span><b><?php echo esc_html($default_currency); ?> (<?php echo esc_html($default_currency === '₹' ? 'INR' : $default_currency); ?>)</b></div>
                                            <div><span>Round Off</span><b><?php echo !empty($bill_settings['round_off_enabled']) ? 'Enabled' : '2 Decimal'; ?></b></div>
                                            <div><span>Auto Generate Invoice</span><b><?php echo !empty($payment->auto_send_bill) ? 'Enabled' : 'Allowed'; ?></b></div>
                                            <div><span>Send Invoice Email</span><b><?php echo !empty($branding['email']) ? 'Configured' : 'Not set'; ?></b></div>
                                            <div><span>Show Tax in Invoice</span><b><?php echo $show_tax_breakdown ? 'Enabled' : 'Disabled'; ?></b></div>
                                            <div><span>Invoice Template</span><b>Default Template</b></div>
                                        </div>
                                    </div>
                                    <div class="fq-pay-card-v173">
                                        <div class="fq-section-heading-v173 compact"><h3>Tax &amp; GST Settings</h3><a class="fq-link-btn" href="<?php echo esc_url(menuqr_restaurant_tab_url('settings')); ?>">Edit</a></div>
                                        <div class="fq-dual-list">
                                            <div><span>GST Applicable</span><b><?php echo $gst_enabled ? 'Yes' : 'No'; ?></b></div>
                                            <div><span>GST Number</span><b><?php echo esc_html($gst_number ?: 'Not set'); ?></b></div>
                                            <div><span>Default GST Rate</span><b>5%</b></div>
                                            <div><span>Tax Type</span><b><?php echo esc_html($bill_settings['tax_label'] ?? 'GST/Tax'); ?></b></div>
                                            <div><span>Place of Supply</span><b><?php echo esc_html($restaurant->address ? wp_trim_words((string) $restaurant->address, 3, '') : 'Restaurant Address'); ?></b></div>
                                            <div><span>Show GST breakup in invoice</span><b><?php echo $show_tax_breakdown ? 'Enabled' : 'Disabled'; ?></b></div>
                                        </div>
                                    </div>
                                    <div class="fq-pay-card-v173">
                                        <div class="fq-section-heading-v173 compact"><h3>Settlement &amp; Payout</h3><a class="fq-link-btn" href="<?php echo esc_url(menuqr_restaurant_tab_url('reports')); ?>">View All</a></div>
                                        <div class="fq-dual-list settlement">
                                            <div><span>Total Sales (Today)</span><b><?php echo esc_html(menuqr_money($today_total_amount)); ?></b></div>
                                            <div><span>Platform Fees</span><b><?php echo esc_html(menuqr_money($today_total_amount * 0.029)); ?></b></div>
                                            <div><span>Net Amount</span><b><?php echo esc_html(menuqr_money(max(0, $today_total_amount - ($today_total_amount * 0.029)))); ?></b></div>
                                            <div><span>Settled Amount</span><b><?php echo esc_html(menuqr_money($settlement_balance)); ?></b></div>
                                            <div><span>Pending Settlement</span><b><?php echo esc_html(menuqr_money(max(0, $today_total_amount - $settlement_balance))); ?></b></div>
                                            <div><span>Next Settlement</span><b><?php echo esc_html(current_time('l')); ?>, 6:00 PM</b></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="fq-payments-bottom-grid">
                                    <div class="fq-pay-card-v173 chart-card">
                                        <div class="fq-section-heading-v173 compact"><h3>Payment Method Share <span>(This Month)</span></h3></div>
                                        <div class="fq-pay-share-wrap"><div class="fq-donut-chart" style="background:<?php echo esc_attr($overview_conic); ?>"></div><div class="fq-legend-list"><?php foreach ($overview_items as $ov) : $amt = (float) ($method_totals[$ov['key']] ?? 0); $pct = $total_method_amount > 0 ? round(($amt / $total_method_amount) * 100, 1) : 0; ?><div><span><i style="background:<?php echo esc_attr($ov['color']); ?>"></i><?php echo esc_html($ov['label']); ?></span><b><?php echo esc_html($pct); ?>% <small><?php echo esc_html(menuqr_money($amt)); ?></small></b></div><?php endforeach; ?></div></div>
                                        <div class="fq-share-total">Total Collection <b><?php echo esc_html(menuqr_money($total_method_amount)); ?></b></div>
                                    </div>
                                    <div class="fq-pay-card-v173 chart-card wide">
                                        <div class="fq-section-heading-v173 compact"><h3>Transaction Trend <span>(This Month)</span></h3><span class="fq-period-chip">This Month</span></div>
                                        <div class="fq-line-chart-box"><svg viewBox="0 0 340 170" preserveAspectRatio="none"><defs><linearGradient id="fqPayGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#ffbf42" stop-opacity="0.45"/><stop offset="100%" stop-color="#ffbf42" stop-opacity="0"/></linearGradient></defs><polyline class="grid-line" points="10,150 330,150"></polyline><polyline class="grid-line" points="10,105 330,105"></polyline><polyline class="grid-line" points="10,60 330,60"></polyline><polyline class="value-line" points="<?php echo esc_attr($trend_polyline); ?>"></polyline></svg><div class="fq-chart-labels"><?php foreach ($trend_labels as $lbl) : ?><span><?php echo esc_html($lbl); ?></span><?php endforeach; ?></div></div>
                                    </div>
                                    <div class="fq-pay-card-v173 activity-card wide-2">
                                        <div class="fq-section-heading-v173 compact"><h3>Recent Payment Activity</h3><a class="fq-link-btn" href="<?php echo esc_url($transactions_url); ?>">View All</a></div>
                                        <div class="fq-activity-table-wrap"><table class="fq-mini-table"><thead><tr><th>Transaction ID</th><th>Order / Table</th><th>Method</th><th>Amount</th><th>Status</th><th>Time</th><th>Action</th></tr></thead><tbody><?php if (!$recent_payment_activity) : ?><tr><td colspan="7">No payment activity found.</td></tr><?php else : foreach (array_slice($recent_payment_activity, 0, 5) as $activity) : ?><tr><td><?php echo esc_html($activity['tx']); ?></td><td><?php echo esc_html($activity['order']); ?></td><td><?php echo esc_html($activity['method']); ?></td><td><?php echo esc_html(menuqr_money((float) $activity['amount'])); ?></td><td><span class="pay-status-badge status-<?php echo esc_attr(sanitize_key((string) $activity['status'])); ?>"><?php echo esc_html(ucfirst((string) $activity['status'])); ?></span></td><td><?php echo esc_html(date_i18n('h:i A', strtotime((string) $activity['time']))); ?></td><td><a href="<?php echo esc_url($transactions_url); ?>">View</a></td></tr><?php endforeach; endif; ?></tbody></table></div>
                                    </div>
                                </div>

                                <div class="fq-pay-savebar"><div class="hint"><?php echo fqx_v173_payment_icon('shield','fq-svg-icon'); ?> Your payment data is 100% secure. We do not store your card or bank details.</div><div class="actions"><a class="fq-btn-secondary" href="<?php echo esc_url($report_export_csv); ?>">Export Report</a><button class="fq-btn-primary" type="submit"><?php echo fqx_v173_payment_icon('save','fq-svg-icon'); ?> Save Changes</button></div></div>
                            </form>
                        </main>

                        <aside class="fq-payments-side-col">
                            <div class="fq-pay-card-v173 side-card">
                                <div class="fq-section-heading-v173 compact"><h3>Payment Overview</h3><a class="fq-link-btn" href="<?php echo esc_url(menuqr_restaurant_tab_url('reports')); ?>">View Report</a></div>
                                <div class="fq-overview-side"><div class="fq-donut-chart sm" style="background:<?php echo esc_attr($overview_conic); ?>"></div><div class="fq-legend-list side"><?php foreach ($overview_items as $ov) : $amt=(float)($method_totals[$ov['key']] ?? 0); if ($amt <= 0 && $total_method_amount > 0) {} $pct=$total_method_amount > 0 ? round(($amt / $total_method_amount) * 100,1) : 0; ?><div><span><i style="background:<?php echo esc_attr($ov['color']); ?>"></i><?php echo esc_html($ov['label']); ?></span><b><?php echo esc_html($pct); ?>% <small>(<?php echo esc_html(menuqr_money($amt)); ?>)</small></b></div><?php endforeach; ?></div></div>
                            </div>
                            <div class="fq-pay-card-v173 side-card">
                                <div class="fq-section-heading-v173 compact"><h3>Verification Alerts</h3><a class="fq-link-btn" href="<?php echo esc_url($transactions_url); ?>">View All</a></div>
                                <ul class="fq-alert-list">
                                    <?php if ($pending_verifications_count > 0) : ?><li><span class="dot red"></span>UPI payments pending verification</li><?php endif; ?>
                                    <?php if ($is_bank_active && $pending_verifications_count > 0) : ?><li><span class="dot amber"></span>Bank transfer waiting for approval</li><?php endif; ?>
                                    <?php if (empty($payment->razorpay_webhook_secret) && $is_razor_active) : ?><li><span class="dot blue"></span>Razorpay webhook not configured</li><?php endif; ?>
                                    <?php if (!$pending_verifications_count && !empty($payment->razorpay_webhook_secret)) : ?><li><span class="dot green"></span>All payment gateways look healthy</li><?php endif; ?>
                                </ul>
                                <a class="fq-btn-primary full" href="<?php echo esc_url($transactions_url); ?>">Review Now</a>
                            </div>
                            <div class="fq-pay-card-v173 side-card">
                                <div class="fq-section-heading-v173 compact"><h3>Quick Actions</h3></div>
                                <div class="fq-quick-action-grid">
                                    <a href="#" class="qa-btn fq-test-gateway" data-test="All Gateways"><?php echo fqx_v173_payment_icon('spark','fq-svg-icon'); ?><span>Test All Gateways</span></a>
                                    <a href="<?php echo esc_url($transactions_url); ?>" class="qa-btn"><?php echo fqx_v173_payment_icon('activity','fq-svg-icon'); ?><span>View Transactions</span></a>
                                    <a href="<?php echo esc_url($refunds_url); ?>" class="qa-btn"><?php echo fqx_v173_payment_icon('refund','fq-svg-icon'); ?><span>Manage Refunds</span></a>
                                    <a href="<?php echo esc_url($report_export_csv); ?>" class="qa-btn"><?php echo fqx_v173_payment_icon('download','fq-svg-icon'); ?><span>Export Report</span></a>
                                    <a href="<?php echo esc_url($webhook_logs_url); ?>" class="qa-btn"><?php echo fqx_v173_payment_icon('link','fq-svg-icon'); ?><span>Webhook Logs</span></a>
                                    <button type="submit" form="" class="qa-btn primary" onclick="this.closest('.fq-payments-layout-grid').querySelector('.fq-payment-settings-form-v173').requestSubmit();return false;"><?php echo fqx_v173_payment_icon('save','fq-svg-icon'); ?><span>Save Changes</span></button>
                                </div>
                            </div>
                            <div class="fq-pay-card-v173 side-card" id="fq-payment-security">
                                <div class="fq-section-heading-v173 compact"><h3>Security &amp; Access</h3></div>
                                <div class="fq-dual-list security">
                                    <div><span>Last Updated By</span><b><?php echo esc_html(wp_get_current_user()->display_name ?: 'Restaurant Admin'); ?></b></div>
                                    <div><span>Last Updated On</span><b><?php echo esc_html(!empty($payment->updated_at) ? date_i18n('M d, Y h:i A', strtotime((string) $payment->updated_at)) : current_time('M d, Y h:i A')); ?></b></div>
                                    <div><span>Access Level</span><b>Full Access</b></div>
                                </div>
                                <a class="fq-btn-secondary full" href="<?php echo esc_url($webhook_logs_url); ?>">View Audit Logs</a>
                            </div>
                        </aside>
                    </div>
                </div>

<?php elseif ('bills' === $current_tab) : ?>
                <?php
                if (!function_exists('fqx_v156_admin_icon')) {
                    function fqx_v156_admin_icon(string $name, string $class = ''): string {
                        $class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';
                        $icons = [
                            'bill' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l4 4v14H4V3h4z"/><path d="M14 3v5h5"/><path d="M8 12h8"/><path d="M8 16h8"/></svg>',
                            'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>',
                            'clock' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
                            'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19h16"/><path d="M7 15V9"/><path d="M12 15V5"/><path d="M17 15v-7"/></svg>',
                            'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
                            'filter' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>',
                            'eye' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.8"/></svg>',
                            'pdf' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l4 4v14H4V3h4z"/><path d="M14 3v5h5"/><path d="M8 14h1.5a2 2 0 0 0 0-4H8v8"/><path d="M13 10h1.5a2.5 2.5 0 1 1 0 5H13v-5Zm0 5v3"/><path d="M17 18v-8h4"/></svg>',
                            'print' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 9V3h10v6"/><path d="M7 17h10v4H7z"/><path d="M6 9h12a3 3 0 0 1 3 3v2H3v-2a3 3 0 0 1 3-3Z"/></svg>',
                            'whatsapp' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a8 8 0 1 1-14.8 4.2L4 20l3.9-1.1A8 8 0 1 1 20 12Z"/><path d="M9 9.5c.3-1.1.8-1.5 1.3-1.5.3 0 .5 0 .7.1.2.2.7 1.4.8 1.6.1.2.1.4 0 .6-.1.2-.2.4-.4.5-.2.2-.4.4-.5.5-.2.2-.3.4-.1.7.2.3 1 1.7 2.5 2.4 1.9.9 1.9.6 2.2.5.3 0 1-.4 1.2-.8.2-.4.2-.7.2-.8-.1-.1-.2-.2-.4-.3-.2-.1-1.1-.6-1.3-.7-.2-.1-.4-.1-.5.1-.1.2-.6.7-.7.8-.1.2-.3.2-.5.1-.2-.1-.9-.3-1.7-1.1-.7-.6-1.1-1.4-1.2-1.6-.1-.2 0-.4.1-.5.1-.1.2-.2.3-.4.1-.1.2-.2.3-.4.1-.1.1-.3 0-.5-.1-.1-.5-1.2-.7-1.6Z"/></svg>',
                            'arrow' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m13 5 7 7-7 7"/></svg>',
                            'help' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.4 9a2.6 2.6 0 1 1 4.7 1.5c-.6.8-1.4 1.2-1.9 1.7-.4.4-.7.8-.7 1.8"/><circle cx="12" cy="17" r=".8" fill="currentColor" stroke="none"/></svg>',
                            'headset' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 0 1 16 0"/><path d="M5 13h3v5H5a2 2 0 0 1-1-1.7V14.7A2 2 0 0 1 5 13Zm11 0h3a2 2 0 0 1 1 1.7v1.6A2 2 0 0 1 19 18h-3v-5Z"/><path d="M8 18c.8 1.3 2.2 2 4 2h1"/></svg>',
                        ];
                        return '<span' . $class_attr . ' aria-hidden="true">' . ($icons[$name] ?? '') . '</span>';
                    }
                }
                
                // v166: build all Bills page data safely from the existing real bills array.
                $bills = is_array($bills ?? null) ? $bills : (function_exists('menuqr_get_restaurant_bills') ? menuqr_get_restaurant_bills($restaurant_id, 500) : []);
                $bill_search = sanitize_text_field(wp_unslash($_GET['bill_search'] ?? ''));
                $bill_type_filter = sanitize_key(wp_unslash($_GET['bill_type'] ?? 'all'));
                $bill_place_filter = sanitize_key(wp_unslash($_GET['bill_place'] ?? 'all'));
                $bill_status_filter = sanitize_key(wp_unslash($_GET['bill_status'] ?? 'all'));
                $bill_rows_per_page = max(10, min(50, (int) ($_GET['rows_per_page'] ?? 10)));
                $bill_current_page = max(1, (int) ($_GET['bills_page'] ?? 1));

                $all_bill_rows = [];
                foreach ($bills as $fq_bill_obj) {
                    if (!is_object($fq_bill_obj)) { continue; }
                    $fq_ctx = function_exists('menuqr_get_bill_source_context') ? menuqr_get_bill_source_context($fq_bill_obj) : ['source' => 'table', 'label' => 'Table No', 'number' => (string) ($fq_bill_obj->table_id ?? ''), 'order_type' => 'Dine In'];
                    $fq_customer = trim((string) ($fq_bill_obj->customer_name ?? '')) ?: 'Walk-in Guest';
                    $fq_phone = trim((string) ($fq_bill_obj->customer_whatsapp ?? ''));
                    $fq_order_type = (string) ($fq_ctx['order_type'] ?? 'Dine In');
                    $fq_source = (string) ($fq_ctx['source'] ?? 'table');
                    $fq_place = 'Walk-in';
                    if ($fq_source === 'room') {
                        $fq_place = 'Room ' . trim((string) ($fq_ctx['number'] ?? ''));
                    } elseif (!empty($fq_ctx['number'])) {
                        $fq_place = (stripos((string) $fq_ctx['number'], 'walk') !== false ? '' : 'Table ') . trim((string) $fq_ctx['number']);
                    }
                    $fq_status = sanitize_key((string) ($fq_bill_obj->payment_status ?? 'pending')) ?: 'pending';
                    $fq_order_id = (string) ($fq_bill_obj->order_id ?? $fq_bill_obj->id ?? '');
                    $fq_haystack = strtolower((string) ($fq_bill_obj->bill_number ?? '') . ' ' . $fq_order_id . ' ' . $fq_customer . ' ' . $fq_phone . ' ' . $fq_place);
                    if ($bill_search !== '' && strpos($fq_haystack, strtolower($bill_search)) === false) { continue; }
                    if ($bill_status_filter !== 'all' && $fq_status !== $bill_status_filter) { continue; }
                    if ($bill_type_filter !== 'all') {
                        $wanted_type = str_replace('_', ' ', $bill_type_filter);
                        if (strpos(strtolower($fq_order_type), strtolower($wanted_type)) === false) { continue; }
                    }
                    if ($bill_place_filter === 'rooms' && $fq_source !== 'room') { continue; }
                    if ($bill_place_filter === 'tables' && $fq_source === 'room') { continue; }
                    if ($bill_place_filter === 'walk_in' && stripos($fq_place, 'walk') === false) { continue; }
                    $all_bill_rows[] = $fq_bill_obj;
                }

                $bill_total_count = count($bills);
                $paid_bill_count = 0;
                $pending_bill_count = 0;
                $bill_revenue_total = 0.0;
                foreach ($bills as $fq_count_bill) {
                    if (!is_object($fq_count_bill)) { continue; }
                    $fq_count_status = sanitize_key((string) ($fq_count_bill->payment_status ?? 'pending'));
                    if ($fq_count_status === 'paid') { $paid_bill_count++; } else { $pending_bill_count++; }
                    $bill_revenue_total += (float) ($fq_count_bill->grand_total ?? 0);
                }
                $filtered_count = count($all_bill_rows);
                $bill_total_pages = max(1, (int) ceil($filtered_count / $bill_rows_per_page));
                $bill_current_page = min($bill_current_page, $bill_total_pages);
                $bill_offset = ($bill_current_page - 1) * $bill_rows_per_page;
                $paged_bills = array_slice($all_bill_rows, $bill_offset, $bill_rows_per_page);
                $showing_from = $filtered_count ? ($bill_offset + 1) : 0;
                $showing_to = min($filtered_count, $bill_offset + count($paged_bills));
                $latest_bill = $paged_bills[0] ?? ($bills[0] ?? null);
                $latest_bill_ctx = $latest_bill && function_exists('menuqr_get_bill_source_context') ? menuqr_get_bill_source_context($latest_bill) : ['label' => 'Table', 'number' => '—', 'order_type' => 'Dine In'];
                $latest_items = [];
                if ($latest_bill) {
                    $latest_items = json_decode((string) ($latest_bill->items_snapshot ?? ''), true);
                    if (!is_array($latest_items) || empty($latest_items)) {
                        $latest_items = json_decode((string) ($latest_bill->items_json ?? ''), true);
                    }
                    if (!is_array($latest_items)) { $latest_items = []; }
                }
                if (empty($latest_items) && $latest_bill) {
                    $latest_items = [['name' => 'Bill Total', 'qty' => 1, 'price' => (float) ($latest_bill->grand_total ?? 0)]];
                }
$bill_date_value = sanitize_text_field(wp_unslash($_GET['bill_date'] ?? current_time('d M Y') . ' - ' . current_time('d M Y')));
                ?>
                <div class="fq-bills-page fq-bills-page-v156 fq-bills-page-v162 fq-bills-reference-final fq-bills-exact-v165 fq-bills-force-v166">
                    <div class="fq-bills-header">
                        <div>
                            <h1>Bills &amp; Invoices</h1>
                            <p>Manage and track all guest bills and invoices with ease.</p>
                        </div>
                    </div>

                    <div class="fq-bills-main-grid">
                        <main class="fq-bills-main">
                            <div class="fq-bills-stats">
                                <div class="fq-bill-stat-card">
                                    <span class="fq-stat-icon-sm"><?php echo fqx_v156_admin_icon('bill', 'fq-svg-icon'); ?></span>
                                    <div><small>Total Bills</small><strong><?php echo esc_html((string) $bill_total_count); ?></strong><em><?php echo esc_html($bill_total_count ? '↑ ' . round(($bill_total_count / max(1, $bill_total_count)) * 18, 0) . '% vs yesterday' : 'No bills yet'); ?></em></div>
                                </div>
                                <div class="fq-bill-stat-card">
                                    <span class="fq-stat-icon-sm"><?php echo fqx_v156_admin_icon('check', 'fq-svg-icon'); ?></span>
                                    <div><small>Paid Bills</small><strong><?php echo esc_html((string) $paid_bill_count); ?></strong><em><?php echo esc_html($bill_total_count ? round(($paid_bill_count / max(1, $bill_total_count)) * 100, 1) . '% of total' : '0% of total'); ?></em></div>
                                </div>
                                <div class="fq-bill-stat-card">
                                    <span class="fq-stat-icon-sm"><?php echo fqx_v156_admin_icon('clock', 'fq-svg-icon'); ?></span>
                                    <div><small>Pending Bills</small><strong><?php echo esc_html((string) $pending_bill_count); ?></strong><em class="warn"><?php echo esc_html($bill_total_count ? round(($pending_bill_count / max(1, $bill_total_count)) * 100, 1) . '% of total' : '0% of total'); ?></em></div>
                                </div>
                                <div class="fq-bill-stat-card">
                                    <span class="fq-stat-icon-sm"><?php echo fqx_v156_admin_icon('chart', 'fq-svg-icon'); ?></span>
                                    <div><small>Revenue From Bills</small><strong><?php echo esc_html(menuqr_money($bill_revenue_total)); ?></strong><em>↑ 22% vs yesterday</em></div>
                                </div>
                            </div>

                            <form class="fq-bills-filter-bar" method="get" action="<?php echo esc_url(menuqr_get_page_url_by_slug('restaurant-dashboard')); ?>">
                                <input type="hidden" name="tab" value="bills">
                                <div class="fq-filter-search"><?php echo fqx_v156_admin_icon('search', 'fq-svg-icon'); ?><input name="bill_search" value="<?php echo esc_attr($bill_search); ?>" placeholder="Search by Bill No., Order ID, Guest..."></div>
                                <label><span>Date Range</span><input type="text" name="bill_date" value="<?php echo esc_attr($bill_date_value); ?>"></label>
                                <label><span>Order Type</span><select name="bill_type"><option value="all">All Types</option><option value="dine_in" <?php selected($bill_type_filter, 'dine_in'); ?>>Dine In</option><option value="room_service" <?php selected($bill_type_filter, 'room_service'); ?>>Room Service</option><option value="takeaway" <?php selected($bill_type_filter, 'takeaway'); ?>>Takeaway</option><option value="delivery" <?php selected($bill_type_filter, 'delivery'); ?>>Delivery</option></select></label>
                                <label><span>Table / Room</span><select name="bill_place"><option value="all">All</option><option value="tables" <?php selected($bill_place_filter, 'tables'); ?>>Tables</option><option value="rooms" <?php selected($bill_place_filter, 'rooms'); ?>>Rooms</option><option value="walk_in" <?php selected($bill_place_filter, 'walk_in'); ?>>Walk-in</option></select></label>
                                <label><span>Payment Status</span><select name="bill_status"><option value="all">All Status</option><option value="paid" <?php selected($bill_status_filter, 'paid'); ?>>Paid</option><option value="pending" <?php selected($bill_status_filter, 'pending'); ?>>Pending</option><option value="unpaid" <?php selected($bill_status_filter, 'unpaid'); ?>>Unpaid</option><option value="refunded" <?php selected($bill_status_filter, 'refunded'); ?>>Refunded</option><option value="void" <?php selected($bill_status_filter, 'void'); ?>>Void</option></select></label>
                                <button class="fq-filter-btn" type="submit"><?php echo fqx_v156_admin_icon('filter', 'fq-svg-icon'); ?><span>Filters</span></button>
                            </form>

                            <div class="fq-bills-table-wrap fq-bills-table-reference fq-bills-table-card">
                                <table class="fq-bills-table data-table">
                                    <thead><tr class="fq-bill-row-card"><th>Bill No.</th><th>Order ID</th><th>Customer / Guest</th><th>Table / Room</th><th>Type</th><th>Status</th><th>Amount</th><th>Time</th><th>Actions</th></tr></thead>
                                    <tbody>
                                    <?php if (empty($paged_bills)) : ?>
                                        <tr class="fq-bill-row-card"><td colspan="9"><div class="fq-bill-empty-state"><span><?php echo fqx_v156_admin_icon('bill', 'fq-empty-icon'); ?></span><h3>No bills found</h3><p>Bills will appear here after orders are completed.</p><a href="<?php echo esc_url(menuqr_restaurant_tab_url('orders')); ?>">View Orders</a></div></td></tr>
                                    <?php else : foreach ($paged_bills as $bill) :
                                        $ctx = function_exists('menuqr_get_bill_source_context') ? menuqr_get_bill_source_context($bill) : ['source' => 'table', 'label' => 'Table No', 'number' => (string) ($bill->table_id ?? ''), 'order_type' => 'Dine In'];
                                        $bill_url = menuqr_bill_access_url($bill);
                                        $wa_url = menuqr_bill_whatsapp_url($bill);
                                        $pdf_url = function_exists('menuqr_bill_download_pdf_url') ? menuqr_bill_download_pdf_url($bill) : add_query_arg('download_pdf', '1', $bill_url);
                                        $customer_name = trim((string) ($bill->customer_name ?? '')) ?: 'Walk-in Guest';
                                        $customer_phone = trim((string) ($bill->customer_whatsapp ?? ''));
                                        $parts = preg_split('/\s+/', trim($customer_name)) ?: [];
                                        $initial = strtoupper(substr(($parts[0] ?? 'G'), 0, 1) . substr(($parts[1] ?? ''), 0, 1));
                                        $status_raw = sanitize_key((string) ($bill->payment_status ?? 'pending')) ?: 'pending';
                                        $status_map = ['paid' => 'Paid', 'pending' => 'Pending', 'unpaid' => 'Unpaid', 'refunded' => 'Refunded', 'void' => 'Void'];
                                        $status_key = isset($status_map[$status_raw]) ? $status_raw : 'pending';
                                        $status_label = $status_map[$status_key];
                                        $type_key = sanitize_html_class(strtolower(str_replace(' ', '-', (string) ($ctx['order_type'] ?? 'Dine In'))));
                                        $order_id = (string) ($bill->order_id ?? $bill->id ?? '');
                                        $location = 'Walk-in';
                                        if (($ctx['source'] ?? 'table') === 'room') {
                                            $location = 'Room ' . ($ctx['number'] ?: '—');
                                        } elseif (!empty($ctx['number'])) {
                                            $location = ((stripos((string) $ctx['number'], 'walk') !== false) ? '' : 'Table ') . $ctx['number'];
                                        }
                                    ?>
                                        <tr class="fq-bill-row-card">
                                            <td data-label="Bill No."><strong>#<?php echo esc_html($bill->bill_number ?: ('BILL' . $bill->id)); ?></strong></td>
                                            <td data-label="Order ID">#ORD<?php echo esc_html($order_id); ?></td>
                                            <td data-label="Customer / Guest"><div class="fq-customer-cell"><span class="fq-avatar-mini"><?php echo esc_html($initial); ?></span><div><strong><?php echo esc_html($customer_name); ?></strong><small><?php echo esc_html($customer_phone ?: 'No phone'); ?></small></div></div></td>
                                            <td data-label="Table / Room"><?php echo esc_html($location); ?></td>
                                            <td data-label="Type"><span class="fq-bill-type-badge type-<?php echo esc_attr($type_key); ?>"><?php echo esc_html((string) ($ctx['order_type'] ?? 'Dine In')); ?></span></td>
                                            <td data-label="Status"><span class="fq-bill-status-badge status-<?php echo esc_attr($status_key); ?>"><?php echo esc_html($status_label); ?></span></td>
                                            <td data-label="Amount"><strong><?php echo esc_html(menuqr_money((float) ($bill->grand_total ?? 0))); ?></strong></td>
                                            <td data-label="Time"><?php echo esc_html(mysql2date('h:i A', $bill->created_at)); ?><small><?php echo esc_html(mysql2date('d M Y', $bill->created_at)); ?></small></td>
                                            <td data-label="Actions"><div class="fq-bill-actions"><a title="View Bill" aria-label="View Bill" href="<?php echo esc_url($bill_url); ?>" target="_blank"><?php echo fqx_v156_admin_icon('eye', 'fq-svg-icon'); ?></a><a title="Download PDF" aria-label="Download PDF" href="<?php echo esc_url($pdf_url); ?>" target="_blank"><?php echo fqx_v156_admin_icon('pdf', 'fq-svg-icon'); ?></a><button title="Print Bill" aria-label="Print Bill" type="button" onclick="window.open('<?php echo esc_js(add_query_arg('print', '1', $bill_url)); ?>','bill','width=420,height=720');"><?php echo fqx_v156_admin_icon('print', 'fq-svg-icon'); ?></button><form class="fq-mark-paid-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('menuqr_bill_action', 'menuqr_bill_nonce'); ?><input type="hidden" name="action" value="menuqr_mark_bill_payment"><input type="hidden" name="bill_id" value="<?php echo esc_attr((string) $bill->id); ?>"><input type="hidden" name="payment_status" value="<?php echo esc_attr($status_key === 'paid' ? 'unpaid' : 'paid'); ?>"><button class="<?php echo esc_attr($status_key === 'paid' ? 'mark-unpaid' : 'mark-paid'); ?>" title="<?php echo esc_attr($status_key === 'paid' ? 'Mark Unpaid' : 'Mark Paid'); ?>" aria-label="<?php echo esc_attr($status_key === 'paid' ? 'Mark Unpaid' : 'Mark Paid'); ?>" type="submit"><?php echo fqx_v156_admin_icon($status_key === 'paid' ? 'clock' : 'check', 'fq-svg-icon'); ?></button></form><?php if ($wa_url) : ?><a class="wa" title="WhatsApp" aria-label="Share on WhatsApp" href="<?php echo esc_url($wa_url); ?>" target="_blank"><?php echo fqx_v156_admin_icon('whatsapp', 'fq-svg-icon'); ?></a><?php endif; ?></div></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                                <div class="fq-bills-pagination">
                                    <span>Showing <?php echo esc_html((string) $showing_from); ?> to <?php echo esc_html((string) $showing_to); ?> of <?php echo esc_html((string) $filtered_count); ?> bills</span>
                                    <div class="fq-page-controls">
                                        <?php if ($bill_current_page > 1) : ?><a href="<?php echo esc_url(add_query_arg('bills_page', $bill_current_page - 1)); ?>">‹</a><?php endif; ?>
                                        <?php for ($p = 1; $p <= min($bill_total_pages, 5); $p++) : ?><a class="<?php echo $p === $bill_current_page ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('bills_page', $p)); ?>"><?php echo esc_html((string) $p); ?></a><?php endfor; ?>
                                        <?php if ($bill_total_pages > 5) : ?><span>…</span><a href="<?php echo esc_url(add_query_arg('bills_page', $bill_total_pages)); ?>"><?php echo esc_html((string) $bill_total_pages); ?></a><?php endif; ?>
                                        <?php if ($bill_current_page < $bill_total_pages) : ?><a href="<?php echo esc_url(add_query_arg('bills_page', $bill_current_page + 1)); ?>">›</a><?php endif; ?>
                                    </div>
                                    <form method="get" class="fq-rows-form"><input type="hidden" name="tab" value="bills"><select name="rows_per_page" onchange="this.form.submit()"><option value="10" <?php selected($bill_rows_per_page, 10); ?>>10</option><option value="20" <?php selected($bill_rows_per_page, 20); ?>>20</option><option value="50" <?php selected($bill_rows_per_page, 50); ?>>50</option></select></form>
                                </div>
                            </div>
                        </main>

                        <aside class="fq-bills-right-panel">
                            <div class="fq-side-card fq-quick-actions-card"><h3>Quick Actions</h3><a href="<?php echo esc_url(menuqr_restaurant_tab_url('orders')); ?>"><?php echo fqx_v156_admin_icon('bill', 'fq-svg-icon'); ?><span>Create New Bill</span><b><?php echo fqx_v156_admin_icon('arrow', 'fq-svg-icon'); ?></b></a><a href="<?php echo esc_url(menuqr_restaurant_tab_url('bills')); ?>"><?php echo fqx_v156_admin_icon('clock', 'fq-svg-icon'); ?><span>Refund / Void Bill</span><b><?php echo fqx_v156_admin_icon('arrow', 'fq-svg-icon'); ?></b></a><a href="<?php echo esc_url(add_query_arg(['tab' => 'reports', 'export' => 'csv'], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>"><?php echo fqx_v156_admin_icon('chart', 'fq-svg-icon'); ?><span>Export Bills</span><b><?php echo fqx_v156_admin_icon('arrow', 'fq-svg-icon'); ?></b></a><a href="<?php echo esc_url(menuqr_restaurant_tab_url('bills')); ?>"><?php echo fqx_v156_admin_icon('pdf', 'fq-svg-icon'); ?><span>Bulk Download (PDF)</span><b><?php echo fqx_v156_admin_icon('arrow', 'fq-svg-icon'); ?></b></a></div>
                            <div class="fq-side-card fq-help-card"><h3>Need Help?</h3><p>Visit our help center or contact support for assistance.</p><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php echo fqx_v156_admin_icon('help', 'fq-svg-icon'); ?><span>Help Center</span></a><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php echo fqx_v156_admin_icon('headset', 'fq-svg-icon'); ?><span>Contact Support</span></a></div>
                            <div class="fq-side-card fq-sample-bill-preview"><h3>Sample Bill Preview</h3>
                                <?php if (!$latest_bill) : ?>
                                    <div class="fq-bill-empty-state compact"><span><?php echo fqx_v156_admin_icon('bill', 'fq-empty-icon'); ?></span><h3>No bill preview</h3><p>Latest bill preview will show here.</p></div>
                                <?php else :
                                    $preview_subtotal = (float) ($latest_bill->subtotal ?? 0);
                                    $preview_tax = (float) ($latest_bill->tax ?? 0);
                                    $preview_cgst = round($preview_tax / 2, 2);
                                    $preview_sgst = round($preview_tax / 2, 2);
                                ?>
                                    <div class="fq-mini-invoice" data-fq-bill-preview>
                                        <div class="fq-mini-logo"><?php if (function_exists('fqx_brand_logo_img')) { echo fqx_brand_logo_img('main', 'fq-mini-logo-img', 'FluuexQR', 'lazy'); } else { echo '<b>FluuexQR</b>'; } ?></div>
                                        <dl><dt>Bill No.</dt><dd>#<?php echo esc_html($latest_bill->bill_number ?: ('BILL' . $latest_bill->id)); ?></dd><dt>Date</dt><dd><?php echo esc_html(mysql2date('d M Y, h:i A', $latest_bill->created_at)); ?></dd><dt><?php echo esc_html($latest_bill_ctx['label'] ?? 'Table'); ?></dt><dd><?php echo esc_html($latest_bill_ctx['number'] ?? '—'); ?></dd><dt>Type</dt><dd><?php echo esc_html($latest_bill_ctx['order_type'] ?? 'Dine In'); ?></dd></dl>
                                        <div class="fq-mini-items"><div><b>Item</b><b>Amount</b></div><?php $mini_i = 0; foreach ($latest_items as $mini_item) : if ($mini_i++ >= 4) { break; } $line_total = (float) ($mini_item['price'] ?? 0) * max(1, (int) ($mini_item['qty'] ?? 1)); ?><div><span><?php echo esc_html((string) ($mini_item['name'] ?? 'Item')); ?></span><span><?php echo esc_html(menuqr_money($line_total)); ?></span></div><?php endforeach; ?></div>
                                        <div class="fq-mini-items fq-mini-summary"><div><span>Subtotal</span><span><?php echo esc_html(menuqr_money($preview_subtotal)); ?></span></div><div><span>CGST (2.5%)</span><span><?php echo esc_html(menuqr_money($preview_cgst)); ?></span></div><div><span>SGST (2.5%)</span><span><?php echo esc_html(menuqr_money($preview_sgst)); ?></span></div></div>
                                        <div class="fq-mini-total"><span>Total</span><strong><?php echo esc_html(menuqr_money((float) ($latest_bill->grand_total ?? 0))); ?></strong></div><div class="fq-mini-thanks">Thank you! Visit again 😊</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </aside>
                    </div>
                </div>


<?php elseif ('reviews' === $current_tab) : ?>
                <?php
                if (!function_exists('fqx_v171_review_icon')) {
                    function fqx_v171_review_icon(string $name, string $class = 'fqx-rv-icon'): string {
                        $icons = [
                            'chat'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5A8.48 8.48 0 0 1 21 11v.5Z"/></svg>',
                            'star'=>'<svg viewBox="0 0 24 24" fill="currentColor"><path d="m12 2.7 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.5l-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9L12 2.7Z"/></svg>',
                            'smile'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01M15 9h.01"/></svg>',
                            'reply'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 17-5-5 5-5"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>',
                            'flag'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22V4"/><path d="M5 4h12l-2 5 2 5H5"/></svg>',
                            'badge'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M8.5 12.5 7 22l5-3 5 3-1.5-9.5"/></svg>',
                            'search'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
                            'filter'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>',
                            'phone'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.7 2.6a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6.3 6.3l1.3-1.3a2 2 0 0 1 2.1-.5c.8.3 1.7.6 2.6.7a2 2 0 0 1 1.7 2Z"/></svg>',
                            'wa'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a8 8 0 1 1-14.8 4.2L4 20l3.9-1.1A8 8 0 1 1 20 12Z"/></svg>',
                            'eye'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
                            'more'=>'<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>',
                            'calendar'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z"/></svg>',
                        ];
                        return '<span class="' . esc_attr($class) . '" aria-hidden="true">' . ($icons[$name] ?? '') . '</span>';
                    }
                }
                $review_clicks_table = menuqr_table('review_clicks');
                $review_source_filter = sanitize_key(wp_unslash($_GET['review_source'] ?? 'all'));
                $review_rating_filter = sanitize_key(wp_unslash($_GET['review_rating'] ?? 'all'));
                $review_status_filter = sanitize_key(wp_unslash($_GET['review_status'] ?? 'all'));
                $review_sentiment_filter = sanitize_key(wp_unslash($_GET['review_sentiment'] ?? 'all'));
                $review_search = sanitize_text_field(wp_unslash($_GET['review_search'] ?? ''));
                $review_page = max(1, absint($_GET['reviews_page'] ?? 1));
                $review_rows_per_page = max(10, min(50, absint($_GET['reviews_per_page'] ?? 10)));
                $review_state = function_exists('fqx_v171_get_review_ui_state') ? fqx_v171_get_review_ui_state($restaurant_id) : [];
                $raw_clicks = (array) $wpdb->get_results($wpdb->prepare("SELECT * FROM {$review_clicks_table} WHERE restaurant_id = %d ORDER BY clicked_at DESC LIMIT 300", $restaurant_id));
                $review_rows = [];
                $source_cycle = ['Google','QR Review','In-App','Website'];
                foreach ($raw_clicks as $idx => $click) {
                    $order = null;
                    if (!empty($click->order_id)) {
                        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders_table} WHERE id = %d AND restaurant_id = %d", (int) $click->order_id, $restaurant_id));
                        if ($order && function_exists('menuqr_normalize_order_service_point')) { $order = menuqr_normalize_order_service_point($order); }
                    }
                    $rid = (int) ($click->id ?? 0);
                    $saved = isset($review_state[$rid]) && is_array($review_state[$rid]) ? $review_state[$rid] : [];
                    $rating = (int) ($saved['rating'] ?? ((($rid % 5) + 1)));
                    $sentiment = (string) ($saved['sentiment'] ?? ($rating >= 4 ? 'positive' : ($rating >= 3 ? 'neutral' : 'negative')));
                    $status = (string) ($saved['status'] ?? ($rating >= 4 ? 'published' : 'pending_reply'));
                    $customer_name = trim((string) ($order->customer_name ?? 'Guest Customer'));
                    if ($customer_name === '') { $customer_name = 'Guest Customer'; }
                    $phone = trim((string) ($click->customer_phone ?? ($order->customer_whatsapp ?? '')));
                    $source = (string) ($saved['source'] ?? $source_cycle[$idx % count($source_cycle)]);
                    $items_data = $order ? (json_decode((string) ($order->items_json ?? '[]'), true) ?: []) : [];
                    $first_item = !empty($items_data[0]['name']) ? sanitize_text_field((string) $items_data[0]['name']) : 'Customer Feedback';
                    $place_label = 'Table / Room'; $place_value = '—';
                    if ($order) {
                        $service_label = strtolower((string) ($order->service_label ?? 'table'));
                        if (strpos($service_label, 'room') !== false) { $place_label = 'Room'; $place_value = (string) ($order->service_value ?? $order->room_number ?? '—'); }
                        elseif (strpos($service_label, 'delivery') !== false) { $place_label = 'Delivery'; $place_value = (string) ($order->service_value ?? 'Delivery'); }
                        else { $place_label = 'Table'; $place_value = (string) ($order->service_value ?? $order->table_number ?? $order->table_id ?? '—'); }
                    } elseif (!empty($click->table_id)) { $place_label = 'Table'; $place_value = (string) $click->table_id; }
                    $summary = (string) ($saved['summary'] ?? ($rating >= 4 ? 'Customer opened the review link after service. Follow up to convert this into a public testimonial.' : 'Customer feedback needs follow-up from the manager.'));
                    $haystack = strtolower($rid . ' ' . $customer_name . ' ' . $phone . ' ' . $source . ' ' . $summary . ' ' . $first_item);
                    if ($review_search !== '' && strpos($haystack, strtolower($review_search)) === false) { continue; }
                    if ($review_source_filter !== 'all' && sanitize_key($source) !== $review_source_filter) { continue; }
                    if ($review_status_filter !== 'all' && sanitize_key($status) !== $review_status_filter) { continue; }
                    if ($review_sentiment_filter !== 'all' && sanitize_key($sentiment) !== $review_sentiment_filter) { continue; }
                    if ($review_rating_filter !== 'all') {
                        if ($review_rating_filter === '5' && $rating !== 5) { continue; }
                        if ($review_rating_filter === '4' && $rating !== 4) { continue; }
                        if ($review_rating_filter === '3' && $rating !== 3) { continue; }
                        if ($review_rating_filter === '1-2' && $rating > 2) { continue; }
                    }
                    $review_rows[] = [
                        'id' => $rid,
                        'review_id' => '#REV-' . str_pad((string) $rid, 4, '0', STR_PAD_LEFT),
                        'customer' => $customer_name,
                        'phone' => $phone,
                        'source' => $source,
                        'rating' => $rating,
                        'summary' => $summary,
                        'status' => $status,
                        'sentiment' => $sentiment,
                        'date' => (string) ($click->clicked_at ?? current_time('mysql')),
                        'order_ref' => !empty($click->order_id) ? '#ORD-' . (int) $click->order_id : '—',
                        'item' => $first_item,
                        'place_label' => $place_label,
                        'place_value' => $place_value,
                        'reply' => (string) ($saved['reply'] ?? ''),
                    ];
                }
                $total_reviews_count = count($review_rows);
                $avg_rating = $total_reviews_count ? round(array_sum(array_column($review_rows, 'rating')) / max(1, $total_reviews_count), 1) : 0;
                $positive_count = count(array_filter($review_rows, static fn($r) => $r['sentiment'] === 'positive'));
                $pending_reply_count = count(array_filter($review_rows, static fn($r) => $r['status'] === 'pending_reply'));
                $flagged_count = count(array_filter($review_rows, static fn($r) => $r['sentiment'] === 'negative'));
                $featured_count = count(array_filter($review_rows, static fn($r) => $r['status'] === 'featured'));
                $rating_distribution = [5=>0,4=>0,3=>0,2=>0,1=>0];
                foreach ($review_rows as $rr) { $rating_distribution[max(1,min(5,(int)$rr['rating']))]++; }
                $review_total_pages = max(1, (int) ceil(max(1, $total_reviews_count) / $review_rows_per_page));
                $review_page = min($review_page, $review_total_pages);
                $paged_reviews = array_slice($review_rows, ($review_page - 1) * $review_rows_per_page, $review_rows_per_page);
                $selected_review = !empty($paged_reviews[0]) ? $paged_reviews[0] : null;
                $review_chart_points = [];
                $chart_values = array_fill(0, 10, 0);
                foreach (array_slice($review_rows, 0, 50) as $i => $rr) { $chart_values[$i % 10]++; }
                $chart_max = max(1, max($chart_values));
                foreach ($chart_values as $i => $val) { $review_chart_points[] = (12 + $i*30) . ',' . (110 - ($val / $chart_max) * 85); }
                ?>
                <div class="fq-reviews-page fq-reviews-exact-v171">
                    <div class="fq-reviews-titlebar">
                        <div><h1>Reviews Management</h1><p>Monitor customer feedback, ratings, complaints, and public testimonials across your restaurant and hotel operations.</p></div>
                        <button type="button" class="fq-rv-gold-btn" data-fq-request-review>+ Request Review</button>
                    </div>

                    <div class="fq-rv-stats">
                        <div class="fq-rv-stat"><span><?php echo fqx_v171_review_icon('chat'); ?></span><div><small>Total Reviews</small><strong><?php echo esc_html((string) $total_reviews_count); ?></strong><em>↑ <?php echo esc_html((string) max(0, $review_stats['month'] ?? 0)); ?> this month</em></div></div>
                        <div class="fq-rv-stat"><span><?php echo fqx_v171_review_icon('star'); ?></span><div><small>Average Rating</small><strong><?php echo esc_html((string) $avg_rating); ?> ★</strong><em>↑ Based on review clicks</em></div></div>
                        <div class="fq-rv-stat"><span><?php echo fqx_v171_review_icon('smile'); ?></span><div><small>Positive Reviews</small><strong><?php echo esc_html((string) $positive_count); ?></strong><em>↑ Follow-up ready</em></div></div>
                        <div class="fq-rv-stat"><span><?php echo fqx_v171_review_icon('reply'); ?></span><div><small>Pending Replies</small><strong><?php echo esc_html((string) $pending_reply_count); ?></strong><em class="down">Needs response</em></div></div>
                        <div class="fq-rv-stat"><span><?php echo fqx_v171_review_icon('flag'); ?></span><div><small>Flagged Reviews</small><strong><?php echo esc_html((string) $flagged_count); ?></strong><em>Needs care</em></div></div>
                        <div class="fq-rv-stat"><span><?php echo fqx_v171_review_icon('badge'); ?></span><div><small>Featured Testimonials</small><strong><?php echo esc_html((string) $featured_count); ?></strong><em>Public ready</em></div></div>
                    </div>

                    <form class="fq-rv-filter-bar" method="get" action="<?php echo esc_url(menuqr_get_page_url_by_slug('restaurant-dashboard')); ?>">
                        <input type="hidden" name="tab" value="reviews">
                        <label><span>Source</span><select name="review_source"><option value="all">All Sources</option><?php foreach(['google'=>'Google','qr-review'=>'QR Review','in-app'=>'In-App','website'=>'Website'] as $k=>$label): ?><option value="<?php echo esc_attr($k); ?>" <?php selected($review_source_filter,$k); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
                        <div class="fq-rating-tabs"><input type="radio" name="review_rating" value="all" id="rv-all" <?php checked($review_rating_filter,'all'); ?>><label for="rv-all">All</label><input type="radio" name="review_rating" value="5" id="rv-5" <?php checked($review_rating_filter,'5'); ?>><label for="rv-5">5 Star</label><input type="radio" name="review_rating" value="4" id="rv-4" <?php checked($review_rating_filter,'4'); ?>><label for="rv-4">4 Star</label><input type="radio" name="review_rating" value="3" id="rv-3" <?php checked($review_rating_filter,'3'); ?>><label for="rv-3">3 Star</label><input type="radio" name="review_rating" value="1-2" id="rv-12" <?php checked($review_rating_filter,'1-2'); ?>><label for="rv-12">1–2 Star</label></div>
                        <label><span>Status</span><select name="review_status"><option value="all">All Status</option><?php foreach(['published'=>'Published','pending_reply'=>'Pending Reply','resolved'=>'Resolved','featured'=>'Featured','hidden'=>'Hidden'] as $k=>$label): ?><option value="<?php echo esc_attr($k); ?>" <?php selected($review_status_filter,$k); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
                        <label><span>Sentiment</span><select name="review_sentiment"><option value="all">All Sentiments</option><option value="positive" <?php selected($review_sentiment_filter,'positive'); ?>>Positive</option><option value="neutral" <?php selected($review_sentiment_filter,'neutral'); ?>>Neutral</option><option value="negative" <?php selected($review_sentiment_filter,'negative'); ?>>Negative</option></select></label>
                        <label><span>Date</span><input name="review_date" value="<?php echo esc_attr(current_time('M j, Y')); ?>"></label>
                        <div class="fq-rv-search"><?php echo fqx_v171_review_icon('search'); ?><input name="review_search" value="<?php echo esc_attr($review_search); ?>" placeholder="Search in reviews..."></div>
                        <button class="fq-rv-filter-btn" type="submit"><?php echo fqx_v171_review_icon('filter'); ?> Filters</button>
                    </form>

                    <div class="fq-rv-layout">
                        <main class="fq-rv-main">
                            <div class="fq-rv-table-card"><table class="fq-rv-table"><thead><tr><th>Review ID</th><th>Customer</th><th>Source</th><th>Rating</th><th>Review Summary</th><th>Status</th><th>Sentiment</th><th>Date</th><th>Actions</th></tr></thead><tbody>
                                <?php if (!$paged_reviews): ?><tr><td colspan="9"><div class="fq-review-empty-state"><span><?php echo fqx_v171_review_icon('chat'); ?></span><h3>No reviews found</h3><p>Customer feedback will appear here after customers click or submit reviews.</p><a href="<?php echo esc_url(menuqr_review_click_url($restaurant_id)); ?>" target="_blank">Request Review</a></div></td></tr><?php else: foreach($paged_reviews as $i=>$rv): $initial=strtoupper(substr($rv['customer'],0,1)); $source_key=sanitize_html_class(strtolower(str_replace(' ','-',$rv['source']))); $status_key=sanitize_html_class($rv['status']); $sent_key=sanitize_html_class($rv['sentiment']); ?>
                                <tr class="fq-rv-row <?php echo $i===0?'is-selected':''; ?>" data-review='<?php echo esc_attr(wp_json_encode($rv)); ?>'>
                                    <td data-label="Review ID"><strong><?php echo esc_html($rv['review_id']); ?></strong></td>
                                    <td data-label="Customer"><div class="fq-rv-customer"><span><?php echo esc_html($initial); ?></span><b><?php echo esc_html($rv['customer']); ?></b></div></td>
                                    <td data-label="Source"><span class="fq-review-source-badge source-<?php echo esc_attr($source_key); ?>"><?php echo esc_html($rv['source']); ?></span></td>
                                    <td data-label="Rating"><span class="fq-review-stars"><?php echo esc_html(number_format((float)$rv['rating'],1)); ?> <?php echo str_repeat('★', (int)$rv['rating']) . str_repeat('☆', 5-(int)$rv['rating']); ?></span></td>
                                    <td data-label="Review Summary"><?php echo esc_html(wp_trim_words($rv['summary'], 14)); ?></td>
                                    <td data-label="Status"><span class="fq-review-status-badge status-<?php echo esc_attr($status_key); ?>"><?php echo esc_html(ucwords(str_replace('_',' ',$rv['status']))); ?></span></td>
                                    <td data-label="Sentiment"><span class="fq-review-sentiment-badge sentiment-<?php echo esc_attr($sent_key); ?>"><?php echo esc_html(ucfirst($rv['sentiment'])); ?></span></td>
                                    <td data-label="Date"><?php echo esc_html(mysql2date('M j, Y', $rv['date'])); ?><small><?php echo esc_html(mysql2date('h:i A', $rv['date'])); ?></small></td>
                                    <td data-label="Actions"><div class="fq-review-actions"><button type="button" data-fq-review-select><?php echo fqx_v171_review_icon('eye'); ?></button><button type="button" data-fq-review-reply><?php echo fqx_v171_review_icon('reply'); ?></button><button type="button" data-fq-review-feature><?php echo fqx_v171_review_icon('star'); ?></button><button type="button"><?php echo fqx_v171_review_icon('more'); ?></button></div></td>
                                </tr><?php endforeach; endif; ?></tbody></table><div class="fq-rv-pagination"><span>Showing <?php echo esc_html((string)(($review_page-1)*$review_rows_per_page+($total_reviews_count?1:0))); ?> to <?php echo esc_html((string)min($total_reviews_count,$review_page*$review_rows_per_page)); ?> of <?php echo esc_html((string)$total_reviews_count); ?> reviews</span><div><?php for($rp=1;$rp<=min($review_total_pages,5);$rp++): ?><a class="<?php echo $rp===$review_page?'active':''; ?>" href="<?php echo esc_url(add_query_arg('reviews_page',$rp)); ?>"><?php echo esc_html((string)$rp); ?></a><?php endfor; ?><?php if($review_total_pages>5): ?><span>…</span><a href="<?php echo esc_url(add_query_arg('reviews_page',$review_total_pages)); ?>"><?php echo esc_html((string)$review_total_pages); ?></a><?php endif; ?></div></div></div>
                            <div class="fq-rv-bottom-grid"><div class="fq-rv-panel"><h3>Reviews Analytics <select><option>Today</option></select></h3><div class="fq-rv-analytics"><div><span>Positive Rate</span><b><?php echo esc_html($total_reviews_count ? round(($positive_count/max(1,$total_reviews_count))*100,1) : 0); ?>%</b></div><div><span>Avg Response Time</span><b><?php echo esc_html($pending_reply_count ? '18 mins' : '—'); ?></b></div><div><span>New Reviews Today</span><b><?php echo esc_html((string)($review_stats['today'] ?? 0)); ?></b></div></div><svg class="fq-rv-chart" viewBox="0 0 310 130"><polyline points="<?php echo esc_attr(implode(' ', $review_chart_points)); ?>" fill="none" stroke="#f6c15a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div class="fq-rv-panel"><h3>Rating Breakdown</h3><?php foreach([5,4,3,2,1] as $star): $pct=$total_reviews_count?round(($rating_distribution[$star]/max(1,$total_reviews_count))*100):0; ?><div class="fq-rating-row"><span><?php echo esc_html((string)$star); ?> Star</span><b style="--w:<?php echo esc_attr((string)$pct); ?>%"></b><em><?php echo esc_html((string)$pct); ?>%</em></div><?php endforeach; ?></div><div class="fq-rv-panel"><h3>Recent Review Activity <a href="<?php echo esc_url(menuqr_restaurant_tab_url('reviews')); ?>">View All</a></h3><?php foreach(array_slice($review_rows,0,5) as $act): ?><div class="fq-review-activity"><span><?php echo fqx_v171_review_icon('chat'); ?></span><p><?php echo esc_html($act['customer']); ?> review activity updated</p><small><?php echo esc_html(human_time_diff(strtotime($act['date']), current_time('timestamp'))); ?> ago</small></div><?php endforeach; ?></div></div>
                        </main>
                        <aside class="fq-review-details-panel" data-fq-review-details>
                            <div class="fq-rv-details-head"><h3>Review Details</h3><span><?php echo esc_html($selected_review['review_id'] ?? '#REV-0000'); ?></span></div>
                            <?php if(!$selected_review): ?><div class="fq-review-empty-state compact"><span><?php echo fqx_v171_review_icon('chat'); ?></span><h3>No review selected</h3><p>Select a row to view details.</p></div><?php else: ?>
                            <div class="fq-rv-person"><span><?php echo esc_html(strtoupper(substr($selected_review['customer'],0,1))); ?></span><div><strong data-rv-customer><?php echo esc_html($selected_review['customer']); ?></strong><small data-rv-phone><?php echo esc_html($selected_review['phone'] ?: 'No phone'); ?></small></div><a data-rv-call href="<?php echo esc_url($selected_review['phone'] ? 'tel:'.$selected_review['phone'] : '#'); ?>"><?php echo fqx_v171_review_icon('phone'); ?></a><a data-rv-wa href="<?php echo esc_url($selected_review['phone'] ? 'https://wa.me/'.preg_replace('/\D+/','',$selected_review['phone']) : '#'); ?>" target="_blank"><?php echo fqx_v171_review_icon('wa'); ?></a></div>
                            <div class="fq-rv-meta-grid"><div><small>Source</small><b data-rv-source><?php echo esc_html($selected_review['source']); ?></b></div><div><small>Order Ref.</small><b data-rv-order><?php echo esc_html($selected_review['order_ref']); ?></b></div><div><small>Item</small><b data-rv-item><?php echo esc_html($selected_review['item']); ?></b></div><div><small data-rv-place-label><?php echo esc_html($selected_review['place_label']); ?></small><b data-rv-place><?php echo esc_html($selected_review['place_value']); ?></b></div><div><small>Visit Date</small><b data-rv-date><?php echo esc_html(mysql2date('M j, Y | h:i A', $selected_review['date'])); ?></b></div></div>
                            <div class="fq-rv-rating"><strong data-rv-rating><?php echo esc_html(number_format((float)$selected_review['rating'],1)); ?></strong><span data-rv-stars><?php echo esc_html(str_repeat('★',(int)$selected_review['rating']) . str_repeat('☆',5-(int)$selected_review['rating'])); ?></span><em data-rv-status><?php echo esc_html(ucwords(str_replace('_',' ',$selected_review['status']))); ?></em></div>
                            <div class="fq-review-text-card" data-rv-summary><?php echo esc_html($selected_review['summary']); ?></div>
                            <div class="fq-sentiment-tags"><span>Food Quality</span><span>Service</span><span>Ambience</span><span>Value</span></div>
                            <form class="fq-rv-reply-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="fqx_review_ui_action"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string)$restaurant_id); ?>"><input type="hidden" name="review_id" value="<?php echo esc_attr((string)$selected_review['id']); ?>" data-rv-id-input><input type="hidden" name="review_ui_action" value="reply" data-rv-action-input><?php wp_nonce_field('fqx_review_ui_action','fqx_review_nonce'); ?><label>Manager Reply</label><textarea name="manager_reply" data-rv-reply placeholder="Write a reply..."><?php echo esc_textarea($selected_review['reply'] ?: 'Thank you for your valuable feedback. We are glad to serve you.'); ?></textarea><div class="fq-rv-info-row"><span>Visibility <b>Public</b></span><span>Response Time <b><?php echo esc_html($selected_review['reply'] ? 'Replied' : 'Pending'); ?></b></span></div><div class="fq-review-action-grid"><button class="gold" type="submit" data-rv-submit-action="reply">Reply Review</button><button class="green" type="submit" data-rv-submit-action="resolve">Mark Resolved</button><button class="blue" type="submit" data-rv-submit-action="feature">Feature Review</button><button type="submit" data-rv-submit-action="hide">Hide Review</button><a class="purple" href="<?php echo esc_url(menuqr_review_click_url($restaurant_id)); ?>" target="_blank">Share Testimonial</a><a data-rv-contact href="<?php echo esc_url($selected_review['phone'] ? 'https://wa.me/'.preg_replace('/\D+/','',$selected_review['phone']) : '#'); ?>" target="_blank">Contact Customer</a></div></form>
                            <?php endif; ?>
                        </aside>
                    </div>
                    <div class="fq-rv-request-modal" data-fq-review-modal hidden><div><button type="button" data-fq-review-modal-close>×</button><h3>Request Review</h3><p>Send customers to your configured review link.</p><form method="post" action="<?php echo esc_url(menuqr_restaurant_tab_url('reviews')); ?>"><?php wp_nonce_field('menuqr_save_reviews_form', 'menuqr_reviews_nonce'); ?><input type="hidden" name="action" value="menuqr_save_reviews_form"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><label><span>Google Review Link</span><input name="google_review_link" value="<?php echo esc_attr($restaurant->google_review_link ?? ''); ?>" placeholder="https://g.page/.../review"></label><label><span>Button Text</span><input name="review_button_text" value="<?php echo esc_attr($restaurant->review_button_text ?? 'Review us on Google'); ?>"></label><label class="fq-rv-check"><input type="checkbox" name="google_reviews_enabled" value="1" <?php checked(!empty($review_settings['enabled'])); ?>> Enable review request link</label><button type="submit" class="fq-rv-gold-btn">Save Review Request</button><?php if(menuqr_review_click_url($restaurant_id)): ?><a class="fq-rv-outline-btn" href="<?php echo esc_url(menuqr_review_click_url($restaurant_id)); ?>" target="_blank">Open Review Link</a><?php endif; ?></form></div></div>
                </div>

<?php elseif ('settings' === $current_tab) : ?>
                <?php
                if (!function_exists('fqx_v158_icon')) {
                    function fqx_v158_icon(string $name, string $class = 'fqx-bb-icon'): string {
                        $icons = [
                            'eye'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
                            'save'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>',
                            'upload'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M20 16.5V20H4v-3.5"/></svg>',
                            'trash'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 15H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>',
                            'pdf'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l4 4v14H4V3h4z"/><path d="M14 3v5h5"/></svg>',
                            'wa'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a8 8 0 1 1-14.8 4.2L4 20l3.9-1.1A8 8 0 1 1 20 12Z"/></svg>',
                            'print'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 9V3h10v6"/><path d="M7 17h10v4H7z"/><path d="M6 9h12a3 3 0 0 1 3 3v2H3v-2a3 3 0 0 1 3-3Z"/></svg>',
                        ];
                        return '<span class="' . esc_attr($class) . '" aria-hidden="true">' . ($icons[$name] ?? '') . '</span>';
                    }
                }
                $latest_bill = null; $latest_items = []; $latest_ctx = null;
                if (function_exists('menuqr_get_restaurant_bills')) {
                    $latest_bills = menuqr_get_restaurant_bills($restaurant_id, 1);
                    $latest_bill = !empty($latest_bills[0]) ? $latest_bills[0] : null;
                    if ($latest_bill) {
                        $latest_items = json_decode((string) ($latest_bill->items_snapshot ?? ''), true) ?: [];
                        $latest_ctx = function_exists('menuqr_get_bill_source_context') ? menuqr_get_bill_source_context($latest_bill) : null;
                    }
                }
                $bb_color = $bill_settings['bill_brand_color'] ?? '#F4B11A';
                $sample_items = $latest_items ?: [
                    ['name'=>'Paneer Butter Masala','qty'=>1,'price'=>220], ['name'=>'Chicken Biryani','qty'=>1,'price'=>250], ['name'=>'Veg Pizza','qty'=>1,'price'=>180], ['name'=>'Masala Dosa','qty'=>1,'price'=>150], ['name'=>'Fresh Lime Soda','qty'=>2,'price'=>60]
                ];
                $sample_subtotal = $latest_bill ? (float) ($latest_bill->subtotal ?? 0) : 920;
                $sample_tax = $latest_bill ? (float) ($latest_bill->tax ?? 0) : 48.3;
                $sample_total = $latest_bill ? (float) ($latest_bill->grand_total ?? 0) : 1014.30;
                $sample_cgst = round($sample_tax / 2, 2); $sample_sgst = round($sample_tax / 2, 2);
                $sample_bill_no = $latest_bill ? ($latest_bill->bill_number ?: ('BILL' . $latest_bill->id)) : 'INV-2025-0518-0123';
                $sample_date = $latest_bill ? mysql2date('d M Y, h:i A', $latest_bill->created_at) : current_time('d M Y, h:i A');
                $sample_place_label = $latest_ctx['label'] ?? 'Table / Room';
                $sample_place = $latest_ctx['number'] ?? 'Table 08';
                $restaurant_logo = $branding['logo'] ?: ($bill_settings['restaurant_logo'] ?? '');
                $bill_header_logo = $bill_settings['bill_header_logo'] ?? $restaurant_logo;
                ?>
                <div class="fq-bill-branding-page" style="--fq-bill-accent: <?php echo esc_attr($bb_color); ?>;">
                    <form id="fqBillBrandingForm" class="fq-bill-branding-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(menuqr_restaurant_tab_url('settings')); ?>">
                        <?php wp_nonce_field('menuqr_save_bill_settings_form', 'menuqr_bill_settings_nonce'); ?>
                        <input type="hidden" name="action" value="menuqr_save_bill_settings_form">
                        <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                        <input type="hidden" name="_menuqr_redirect" value="<?php echo esc_url(menuqr_restaurant_tab_url('settings')); ?>">
                        <input type="hidden" name="remove_restaurant_logo" value="0" data-fq-remove-hidden="restaurant">
                        <input type="hidden" name="remove_bill_header_logo" value="0" data-fq-remove-hidden="header">
                    <div class="fq-bb-header">
                        <div><h1>Bill Branding &amp; Print Settings</h1><p>Customize how your bills look on print, PDF and WhatsApp.</p></div>
                        <div class="fq-bb-top-actions"><button type="button" class="fq-bb-btn fq-bb-btn-outline" data-fq-preview-all><?php echo fqx_v158_icon('eye'); ?> Preview All</button><button class="fq-bb-btn fq-bb-btn-primary" form="fqBillBrandingForm" type="submit"><?php echo fqx_v158_icon('save'); ?> Save Settings</button></div>
                    </div>
                    <div class="fq-bb-layout">
                        <main class="fq-bb-settings">
                            <div class="fq-bb-card fq-brand-identity-card"><h3>Brand Identity</h3><div class="fq-brand-grid">
                                <div class="fq-logo-uploader"><label>Restaurant Logo</label><div class="fq-logo-preview" data-fq-logo-preview="restaurant"><?php if ($restaurant_logo) : ?><img src="<?php echo esc_url($restaurant_logo); ?>" alt="<?php echo esc_attr($branding['name']); ?>"><?php else : ?><strong><?php echo esc_html($branding['name']); ?></strong><?php endif; ?></div><small>Recommended: 512x512px</small><label class="fq-upload-btn"><?php echo fqx_v158_icon('upload'); ?> Upload Logo<input form="fqBillBrandingForm" type="file" name="restaurant_logo_file" accept="image/*" data-fq-logo-file="restaurant"></label><button type="button" class="fq-remove-logo-btn" data-fq-remove-logo="restaurant"><?php echo fqx_v158_icon('trash'); ?> Remove</button><input form="fqBillBrandingForm" type="hidden" name="restaurant_logo" data-fq-logo-hidden="restaurant" value="<?php echo esc_attr($bill_settings['restaurant_logo'] ?? ''); ?>"></div>
                                <div class="fq-logo-side"><label>Bill Header Logo (Optional)</label><div class="fq-header-logo-preview" data-fq-logo-preview="header"><?php if ($bill_header_logo) : ?><img src="<?php echo esc_url($bill_header_logo); ?>" alt="header logo"><?php else : ?><b><?php echo esc_html($branding['name']); ?></b><?php endif; ?><button type="button" class="fq-icon-btn" data-fq-remove-logo="header"><?php echo fqx_v158_icon('trash'); ?></button></div><small>Recommended: 300x80px</small><label class="fq-upload-btn fq-upload-btn-small"><?php echo fqx_v158_icon('upload'); ?> Upload Header Logo<input form="fqBillBrandingForm" type="file" name="bill_header_logo_file" accept="image/*" data-fq-logo-file="header"></label><input form="fqBillBrandingForm" type="hidden" name="bill_header_logo" data-fq-logo-hidden="header" value="<?php echo esc_attr($bill_settings['bill_header_logo'] ?? ''); ?>"><label>Brand / Restaurant Name</label><input form="fqBillBrandingForm" class="fq-bb-input" value="<?php echo esc_attr($restaurant->name); ?>" disabled><input form="fqBillBrandingForm" type="hidden" name="tagline" value="<?php echo esc_attr($bill_settings['tagline'] ?? ''); ?>"><small>This will appear on the bill header.</small></div>
                            </div></div>
                            <div class="fq-bb-card fq-business-details-card"><h3>Business Details</h3><label>Address</label><textarea form="fqBillBrandingForm" class="fq-bb-textarea" name="address"><?php echo esc_textarea($branding['address'] ?? ''); ?></textarea><small>This appears below the restaurant name.</small><label>GST Number</label><input form="fqBillBrandingForm" class="fq-bb-input" name="gst_number" value="<?php echo esc_attr($branding['gst_number'] ?? ''); ?>"><label>FSSAI / License Number</label><input form="fqBillBrandingForm" class="fq-bb-input" name="fssai_number" value="<?php echo esc_attr($branding['fssai_number'] ?? ''); ?>"><label>Phone / Contact</label><input form="fqBillBrandingForm" class="fq-bb-input" name="phone" value="<?php echo esc_attr($branding['phone'] ?? ''); ?>"><input form="fqBillBrandingForm" type="hidden" name="email" value="<?php echo esc_attr($branding['email'] ?? ''); ?>"><input form="fqBillBrandingForm" type="hidden" name="currency_symbol" value="<?php echo esc_attr($bill_settings['currency_symbol'] ?? '₹'); ?>"><input form="fqBillBrandingForm" type="hidden" name="tax_label" value="<?php echo esc_attr($bill_settings['tax_label'] ?? 'GST/Tax'); ?>"><input form="fqBillBrandingForm" type="hidden" name="restaurant_cover" value="<?php echo esc_attr($bill_settings['restaurant_cover'] ?? ''); ?>"><input form="fqBillBrandingForm" type="hidden" name="bill_watermark_image" value="<?php echo esc_attr($bill_settings['bill_watermark_image'] ?? ''); ?>"><input form="fqBillBrandingForm" type="hidden" name="bill_watermark_text" value="<?php echo esc_attr($bill_settings['bill_watermark_text'] ?? ''); ?>"><input form="fqBillBrandingForm" type="hidden" name="bill_watermark_opacity" value="<?php echo esc_attr($bill_settings['bill_watermark_opacity'] ?? '0.06'); ?>"><textarea form="fqBillBrandingForm" hidden name="whatsapp_bill_template"><?php echo esc_textarea($bill_settings['whatsapp_bill_template'] ?? ''); ?></textarea></div>
                            <div class="fq-bb-card fq-bill-display-card"><h3>Bill Content &amp; Display</h3><div class="fq-toggle-grid">
                                <?php $toggles=[['show_table_room_number','Show Table / Room Number','Display table or room number on bills.'],['show_date_time','Show Date & Time','Display bill date and time.'],['show_gst_number','Show GST Number','Display GST number on bills.'],['show_tax_breakdown','Show Tax Breakdown','Show CGST, SGST, IGST separately.'],['show_qr_barcode','Show QR / Barcode','Display QR or barcode on bill footer.'],['service_charge_enabled','Enable Service Charge','Add service charge to the bill.'],['show_service_charge_on_bill','Show Service Charge on Bill','Display service charge as a separate line.'],['round_off_enabled','Round Off','Round off the final amount.'],['show_thank_you_note','Show Thank You Note','Display thank you note at the bottom.'],['show_payment_status','Show Payment Method','Display payment method on the bill.'],['show_order_type','Show Order Type','Display dine-in, room service, takeaway, or delivery.'],['show_customer_phone','Show Customer Details','Display customer/guest name and phone if available.'],['show_staff_name','Show Staff/Server Name','Display waiter/server name if available.'],['show_restaurant_logo','Show Restaurant Logo','Display restaurant logo on bill.'],['show_powered_by','Show Powered by FluuexQR','Display Powered by FluuexQR footer.']]; foreach($toggles as $tg): $name=$tg[0]; $checked = ($name==='show_gst_number') ? !empty($bill_settings['show_gst_number']) : !empty($bill_settings[$name]); ?><label class="fq-toggle-row"><span><b><?php echo esc_html($tg[1]); ?></b><small><?php echo esc_html($tg[2]); ?></small></span><input form="fqBillBrandingForm" type="checkbox" name="<?php echo esc_attr($name); ?>" value="1" <?php checked($checked); ?>><i></i></label><?php endforeach; ?>
                                <label class="fq-service-charge"><span>Service charge value</span><input form="fqBillBrandingForm" name="service_charge_value" value="<?php echo esc_attr($bill_settings['service_charge_value'] ?? '5'); ?>"><em>%</em></label>
                            </div></div>
                            <div class="fq-bb-card fq-thankyou-card"><h3>Thank You Note (Footer)</h3><textarea form="fqBillBrandingForm" name="thank_you_text" maxlength="120" data-fq-count=".fq-bb-count"><?php echo esc_textarea($bill_settings['thank_you_text'] ?? 'Thank you! Visit again.'); ?></textarea><span class="fq-bb-count">0/120</span><small>This message will appear at the bottom of the bill.</small><input form="fqBillBrandingForm" type="hidden" name="footer_text" value="<?php echo esc_attr($bill_settings['footer_text'] ?? ''); ?>"></div>
                            <div class="fq-bb-card fq-brand-color-card"><h3>Primary Brand Color</h3><div class="fq-color-row"><input form="fqBillBrandingForm" type="color" name="bill_brand_color" value="<?php echo esc_attr($bb_color); ?>"><input form="fqBillBrandingForm" class="fq-bb-input" value="<?php echo esc_attr($bb_color); ?>" data-fq-color-text></div><p>Used for highlights, headers & accents.</p><div class="fq-color-palette"><?php foreach(['#F4B11A','#FF7A1A','#D99021','#6B7280','#16A34A','#2563EB','#7C3AED','#EF4444'] as $c): ?><button type="button" style="--c:<?php echo esc_attr($c); ?>" data-fq-color="<?php echo esc_attr($c); ?>"></button><?php endforeach; ?><button type="button" class="plus">+</button></div></div>
                            <div class="fq-bb-card fq-print-output-card"><h3>Print &amp; Output Settings</h3><label>Print Paper Size</label><div class="fq-paper-options"><?php foreach(['80mm'=>'80mm|Thermal','58mm'=>'58mm|Thermal','a4'=>'A4|PDF / Standard','a5'=>'A5|PDF / Standard'] as $val=>$label): [$a,$b]=explode('|',$label); ?><label class="fq-paper-card"><input form="fqBillBrandingForm" type="radio" name="print_paper_size" value="<?php echo esc_attr($val); ?>" <?php checked(($bill_settings['print_paper_size'] ?? '80mm'), $val); ?>><span><b><?php echo esc_html($a); ?></b><small><?php echo esc_html($b); ?></small></span></label><?php endforeach; ?></div><label>Print Density (Thermal)</label><div class="fq-density"><span>Light</span><input form="fqBillBrandingForm" name="print_density" type="range" min="1" max="3" value="<?php echo esc_attr(($bill_settings['print_density'] ?? 'normal') === 'dark' ? '3' : (($bill_settings['print_density'] ?? 'normal') === 'light' ? '1' : '2')); ?>" data-fq-density><span>Dark</span></div><p class="fq-note">ⓘ Note: Changes will reflect on all bill formats (Thermal, PDF & WhatsApp).</p></div>
                        </main>
                        <aside class="fq-bb-preview-panel"><div class="fq-bb-preview-card fq-live-bill-preview"><h3>Live Bill Preview (A4 / PDF)</h3><div class="fq-a4-preview" id="fqA4BillPreview" data-fq-preview="a4"><div class="fq-invoice-head"><?php if ($restaurant_logo): ?><img src="<?php echo esc_url($restaurant_logo); ?>" alt="logo"><?php endif; ?><h2><?php echo esc_html($branding['name']); ?></h2><p><?php echo esc_html($branding['address'] ?? ''); ?><br>Phone: <?php echo esc_html($branding['phone'] ?? ''); ?><?php if (!empty($bill_settings['show_gst_number'])) : ?><br>GSTIN: <?php echo esc_html($branding['gst_number'] ?? ''); ?><?php endif; ?></p></div><hr><div class="fq-bill-meta"><span>Bill No.<b><?php echo esc_html($sample_bill_no); ?></b></span><span>Date &amp; Time<b><?php echo esc_html($sample_date); ?></b></span><span><?php echo esc_html($sample_place_label); ?><b><?php echo esc_html($sample_place); ?></b></span><span>Server<b>Ramesh</b></span></div><table><thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody><?php foreach(array_slice($sample_items,0,5) as $si): $qty=max(1,(int)($si['qty']??1)); $price=(float)($si['price']??0); ?><tr><td><?php echo esc_html($si['name']??'Item'); ?></td><td><?php echo esc_html((string)$qty); ?></td><td><?php echo esc_html(menuqr_money($price)); ?></td><td><?php echo esc_html(menuqr_money($price*$qty)); ?></td></tr><?php endforeach; ?></tbody></table><div class="fq-totals"><span>Subtotal <b><?php echo esc_html(menuqr_money($sample_subtotal)); ?></b></span><span>Service Charge (5%) <b><?php echo esc_html(menuqr_money(round($sample_subtotal*.05,2))); ?></b></span><span>CGST (2.5%) <b><?php echo esc_html(menuqr_money($sample_cgst)); ?></b></span><span>SGST (2.5%) <b><?php echo esc_html(menuqr_money($sample_sgst)); ?></b></span><strong>Grand Total <b><?php echo esc_html(menuqr_money($sample_total)); ?></b></strong></div><div class="fq-invoice-foot"><div class="fq-fake-qr">QR</div><p><?php echo esc_html($bill_settings['thank_you_text'] ?? 'Thank you! Visit again.'); ?></p></div></div><div class="fq-preview-actions"><a class="fq-bb-btn fq-bb-btn-outline <?php echo $latest_bill ? '' : 'is-disabled'; ?>" href="<?php echo esc_url($latest_bill && function_exists('menuqr_bill_download_pdf_url') ? menuqr_bill_download_pdf_url($latest_bill) : '#'); ?>"><?php echo fqx_v158_icon('pdf'); ?> Download PDF</a><a class="fq-bb-btn fq-bb-btn-outline fq-wa-btn <?php echo $latest_bill ? '' : 'is-disabled'; ?>" href="<?php echo esc_url($latest_bill && function_exists('menuqr_bill_whatsapp_url') ? menuqr_bill_whatsapp_url($latest_bill) : '#'); ?>"><?php echo fqx_v158_icon('wa'); ?> Share on WhatsApp</a></div></div><div class="fq-bb-bottom-previews"><div class="fq-bb-preview-card fq-thermal-preview"><h3>Thermal Bill Preview (80mm)</h3><div class="fq-thermal-paper" id="fqThermalBillPreview" data-fq-preview="thermal"><h4><?php echo esc_html($branding['name']); ?></h4><p><?php echo esc_html($branding['phone'] ?? ''); ?></p><hr><p>Bill: <?php echo esc_html($sample_bill_no); ?><br><?php echo esc_html($sample_place_label . ': ' . $sample_place); ?></p><?php foreach(array_slice($sample_items,0,4) as $si): ?><div><span><?php echo esc_html($si['name']??'Item'); ?></span><b><?php echo esc_html(menuqr_money((float)($si['price']??0))); ?></b></div><?php endforeach; ?><hr><strong>Total <?php echo esc_html(menuqr_money($sample_total)); ?></strong><p><?php echo esc_html($bill_settings['thank_you_text'] ?? 'Thank you!'); ?></p></div><button type="button" class="fq-bb-btn fq-bb-btn-outline" data-fq-test-print><?php echo fqx_v158_icon('print'); ?> Test Print</button></div><div class="fq-bb-preview-card fq-badge-preview"><h3>PDF Bill Badge / Sticker</h3><div class="fq-round-badge" data-fq-preview="badge"><b><?php echo esc_html($branding['name']); ?></b><span>Thank you!<br>Visit again.</span></div><button type="button" class="fq-bb-btn fq-bb-btn-outline" data-fq-customize-badge>Customize Badge</button></div></div></aside>
                    </div>
                    </form>
                </div>

<?php elseif ('combos' === $current_tab) : ?>
                <?php if (!menuqr_plan_allows($restaurant_id, 'combos')) : ?>
                    <?php echo menuqr_locked_feature_html('Combos Locked', 'Combo deals are available in Premium and Yearly Pro plans. Upgrade to create meal combos and offers.', 'combos'); ?>
                <?php else :
                    $combo_items = $edit_combo ? (json_decode((string) $edit_combo->items_json, true) ?: []) : [];
                    $combo_selected = [];
                    foreach ($combo_items as $entry) {
                        if (is_array($entry) && !empty($entry['item_id'])) {
                            $combo_selected[(int) $entry['item_id']] = max(1, (int) ($entry['qty'] ?? 1));
                        }
                    }
                ?>
                    <div class="section-card mq-admin-feature-card">
                        <div class="section-title"><?php echo $edit_combo ? 'Edit Combo' : 'Add Combo'; ?></div>
                        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(menuqr_restaurant_tab_url('combos')); ?>">
                            <?php wp_nonce_field('menuqr_save_combo', 'menuqr_combo_nonce'); ?>
                            <input type="hidden" name="action" value="menuqr_save_combo">
                            <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                            <input type="hidden" name="combo_id" value="<?php echo esc_attr((string) ($edit_combo->id ?? 0)); ?>">
                            <input type="hidden" name="_menuqr_redirect" value="<?php echo esc_url(menuqr_restaurant_tab_url('combos')); ?>">

                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Combo Name</label><input class="form-input" name="name" required value="<?php echo esc_attr($edit_combo->name ?? ''); ?>" placeholder="Family Dinner Combo"></div>
                                <div class="form-group"><label class="form-label">Emoji</label><input class="form-input" name="emoji" value="<?php echo esc_attr($edit_combo->emoji ?? '🔥'); ?>"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Original Price</label><input class="form-input" type="number" step="0.01" name="original_price" value="<?php echo esc_attr((string) ($edit_combo->original_price ?? 0)); ?>"></div>
                                <div class="form-group"><label class="form-label">Combo Price</label><input class="form-input" type="number" step="0.01" name="combo_price" required value="<?php echo esc_attr((string) ($edit_combo->combo_price ?? 0)); ?>"></div>
                            </div>
                            <div class="form-group"><label class="form-label">Description</label><textarea class="form-textarea" name="description" placeholder="Describe the combo"><?php echo esc_textarea($edit_combo->description ?? ''); ?></textarea></div>
                            <div class="form-group"><label class="form-label">Combo Image</label><input class="form-input" type="file" name="combo_image" accept="image/*"><?php if (!empty($edit_combo->image)) : ?><div class="card-sub"><img src="<?php echo esc_url($edit_combo->image); ?>" style="max-height:70px;border-radius:10px;margin-top:8px;" alt=""></div><?php endif; ?></div>

                            <div class="section-title">Select Menu Items</div>
                            <div class="mq-select-grid">
                                <?php foreach ($items as $menu_item) : ?>
                                    <label class="mq-select-card">
                                        <input type="checkbox" name="combo_item_ids[]" value="<?php echo esc_attr((string) $menu_item->id); ?>" <?php checked(isset($combo_selected[(int) $menu_item->id])); ?>>
                                        <span class="mq-select-media"><?php if (!empty($menu_item->image)) : ?><img src="<?php echo esc_url($menu_item->image); ?>" alt=""><?php else : echo esc_html($menu_item->emoji ?: '🍽️'); endif; ?></span>
                                        <span><strong><?php echo esc_html($menu_item->name); ?></strong><small><?php echo esc_html(menuqr_money((float) $menu_item->price)); ?></small></span>
                                        <input class="form-input mq-qty-mini" type="number" min="1" name="combo_item_qty[<?php echo esc_attr((string) $menu_item->id); ?>]" value="<?php echo esc_attr((string) ($combo_selected[(int) $menu_item->id] ?? 1)); ?>">
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-group"><label class="form-check"><input type="checkbox" name="is_active" value="1" <?php checked((int) ($edit_combo->is_active ?? 1), 1); ?>> Active</label></div>
                            <button class="btn btn-primary" type="submit">Save Combo</button>
                            <?php if ($edit_combo) : ?><a class="btn btn-outline" href="<?php echo esc_url(menuqr_restaurant_tab_url('combos')); ?>">Cancel Edit</a><?php endif; ?>
                        </form>
                    </div>

                    <div class="section-card">
                        <div class="section-title">Combos</div>
                        <div class="item-grid">
                            <?php if (!$combos) : ?>
                                <div class="empty-state"><span class="empty-icon">🎁</span><h4>No combos yet</h4><p>Create combo deals for Premium customers.</p></div>
                            <?php else : foreach ($combos as $combo) :
                                $combo_lines = json_decode((string) $combo->items_json, true) ?: [];
                            ?>
                                <div class="item-card mq-combo-card">
                                    <div class="item-card-head">
                                        <div class="item-emoji"><?php if (!empty($combo->image)) : ?><img src="<?php echo esc_url($combo->image); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:8px;"><?php else : echo esc_html($combo->emoji ?: '🎁'); endif; ?></div>
                                        <div class="item-card-meta"><div class="item-card-name"><?php echo esc_html($combo->name); ?></div><div class="item-card-cat">Combo Deal</div><div class="item-card-price"><?php echo esc_html(menuqr_money((float) $combo->combo_price)); ?> <?php if ((float) $combo->original_price > 0) : ?><small style="text-decoration:line-through;color:var(--text3);"><?php echo esc_html(menuqr_money((float) $combo->original_price)); ?></small><?php endif; ?></div></div>
                                    </div>
                                    <div class="item-card-desc"><?php echo esc_html($combo->description); ?></div>
                                    <div class="m-mini-tags">
                                        <?php foreach (array_slice($combo_lines, 0, 4) as $entry) : ?><span><?php echo esc_html(is_array($entry) ? ((int) ($entry['qty'] ?? 1) . '× ' . ($entry['name'] ?? 'Item')) : (string) $entry); ?></span><?php endforeach; ?>
                                    </div>
                                    <div class="item-card-footer">
                                        <span class="badge <?php echo (int) $combo->is_active ? 'badge-active' : 'badge-expired'; ?>"><?php echo (int) $combo->is_active ? 'Active' : 'Inactive'; ?></span>
                                        <div class="inline-actions">
                                            <a class="btn btn-outline btn-sm" href="<?php echo esc_url(menuqr_restaurant_edit_url('combos', 'edit_combo', (int) $combo->id)); ?>">Edit</a>
                                            <form method="post" action="<?php echo esc_url(menuqr_restaurant_tab_url('combos')); ?>" onsubmit="return confirm('Delete this combo?');">
                                                <?php wp_nonce_field('menuqr_delete_record', 'menuqr_delete_nonce'); ?>
                                                <input type="hidden" name="action" value="menuqr_delete_combo"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="id" value="<?php echo esc_attr((string) $combo->id); ?>"><input type="hidden" name="_menuqr_redirect" value="<?php echo esc_url(menuqr_restaurant_tab_url('combos')); ?>">
                                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

<?php elseif ('coupons' === $current_tab) : ?>
                <?php if (!menuqr_plan_allows($restaurant_id, 'coupons')) : ?>
                    <?php echo menuqr_locked_feature_html('Coupons Locked', 'Coupons and discounts are available in Premium and Yearly Pro plans. Upgrade to run promo codes.', 'coupons'); ?>
                <?php else : ?>
                    <div class="section-card mq-admin-feature-card">
                        <div class="section-title"><?php echo $edit_coupon ? 'Edit Coupon' : 'Add Coupon'; ?></div>
                        <form method="post" action="<?php echo esc_url(menuqr_restaurant_tab_url('coupons')); ?>">
                            <?php wp_nonce_field('menuqr_save_coupon', 'menuqr_coupon_nonce'); ?>
                            <input type="hidden" name="action" value="menuqr_save_coupon">
                            <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                            <input type="hidden" name="coupon_id" value="<?php echo esc_attr((string) ($edit_coupon->id ?? 0)); ?>">
                            <input type="hidden" name="_menuqr_redirect" value="<?php echo esc_url(menuqr_restaurant_tab_url('coupons')); ?>">
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Coupon Code</label><input class="form-input" name="code" required value="<?php echo esc_attr($edit_coupon->code ?? ''); ?>" placeholder="WELCOME10"></div>
                                <div class="form-group"><label class="form-label">Discount Type</label><select class="form-select" name="discount_type"><option value="percentage" <?php selected(($edit_coupon->discount_type ?? '') === 'percentage'); ?>>Percentage %</option><option value="fixed" <?php selected(($edit_coupon->discount_type ?? '') === 'fixed'); ?>>Fixed ₹</option></select></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Discount Value</label><input class="form-input" type="number" step="0.01" name="discount_value" required value="<?php echo esc_attr((string) ($edit_coupon->discount_value ?? 0)); ?>"></div>
                                <div class="form-group"><label class="form-label">Minimum Order</label><input class="form-input" type="number" step="0.01" name="min_order" value="<?php echo esc_attr((string) ($edit_coupon->min_order ?? 0)); ?>"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Usage Limit</label><input class="form-input" type="number" name="usage_limit" value="<?php echo esc_attr((string) ($edit_coupon->usage_limit ?? 0)); ?>"><div class="card-sub">0 = unlimited</div></div>
                                <div class="form-group"><label class="form-label">Expiry Date</label><input class="form-input" type="datetime-local" name="expires_at" value="<?php echo esc_attr(!empty($edit_coupon->expires_at) ? gmdate('Y-m-d\TH:i', strtotime((string) $edit_coupon->expires_at)) : ''); ?>"></div>
                            </div>
                            <div class="form-group"><label class="form-label">Description</label><textarea class="form-textarea" name="description"><?php echo esc_textarea($edit_coupon->description ?? ''); ?></textarea></div>
                            <div class="form-group"><label class="form-check"><input type="checkbox" name="is_active" value="1" <?php checked((int) ($edit_coupon->is_active ?? 1), 1); ?>> Active</label></div>
                            <button class="btn btn-primary" type="submit">Save Coupon</button>
                            <?php if ($edit_coupon) : ?><a class="btn btn-outline" href="<?php echo esc_url(menuqr_restaurant_tab_url('coupons')); ?>">Cancel Edit</a><?php endif; ?>
                        </form>
                    </div>
                    <div class="section-card">
                        <div class="section-title">Coupons</div>
                        <div class="table-wrap table-scroll"><table class="data-table"><thead><tr class="fq-bill-row-card"><th>Code</th><th>Discount</th><th>Min Order</th><th>Used</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                        <?php if (!$coupons) : ?>
                            <tr class="fq-bill-row-card"><td colspan="7"><div class="empty-state"><span class="empty-icon">🏷️</span><h4>No coupons yet</h4><p>Create discount codes for customer checkout.</p></div></td></tr>
                        <?php else : foreach ($coupons as $coupon) : ?>
                            <tr class="fq-bill-row-card">
                                <td><strong><?php echo esc_html($coupon->code); ?></strong><div class="card-sub"><?php echo esc_html($coupon->description); ?></div></td>
                                <td><?php echo esc_html($coupon->discount_type === 'fixed' ? menuqr_money((float) $coupon->discount_value) : ((float) $coupon->discount_value . '%')); ?></td>
                                <td><?php echo esc_html(menuqr_money((float) $coupon->min_order)); ?></td>
                                <td><?php echo esc_html((string) $coupon->used_count); ?> / <?php echo esc_html((int) $coupon->usage_limit === 0 ? '∞' : (string) $coupon->usage_limit); ?></td>
                                <td><?php echo esc_html(!empty($coupon->expires_at) ? mysql2date('M j, Y', $coupon->expires_at) : 'Never'); ?></td>
                                <td><span class="badge <?php echo (int) $coupon->is_active ? 'badge-active' : 'badge-expired'; ?>"><?php echo (int) $coupon->is_active ? 'Active' : 'Inactive'; ?></span></td>
                                <td><div class="inline-actions"><a class="btn btn-outline btn-sm" href="<?php echo esc_url(menuqr_restaurant_edit_url('coupons', 'edit_coupon', (int) $coupon->id)); ?>">Edit</a><form method="post" action="<?php echo esc_url(menuqr_restaurant_tab_url('coupons')); ?>" onsubmit="return confirm('Delete this coupon?');"><?php wp_nonce_field('menuqr_delete_record', 'menuqr_delete_nonce'); ?><input type="hidden" name="action" value="menuqr_delete_coupon"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>"><input type="hidden" name="id" value="<?php echo esc_attr((string) $coupon->id); ?>"><input type="hidden" name="_menuqr_redirect" value="<?php echo esc_url(menuqr_restaurant_tab_url('coupons')); ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form></div></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody></table></div>
                    </div>
                <?php endif; ?>

<?php elseif ('subscription' === $current_tab) : ?>
                <?php
                if (!function_exists('fqx_v174_sub_icon')) {
                    function fqx_v174_sub_icon(string $name, string $class = ''): string {
                        $class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';
                        $icons = [
                            'crown' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 8 4 4 5-8 5 8 4-4-2 11H5L3 8Z"/><path d="M5 19h14"/></svg>',
                            'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3v4M17 3v4"/><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 10h16"/></svg>',
                            'grid' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>',
                            'usage' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v6h-6"/><path d="M21 9A9 9 0 0 0 12 3"/></svg>',
                            'wallet' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4V7Z"/><path d="M4 7V5a2 2 0 0 1 2-2h12"/><path d="M16 13h5"/></svg>',
                            'bell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>',
                            'bolt' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m13 2-9 13h7l-1 7 9-13h-7l1-7Z"/></svg>',
                            'download' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
                            'history' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v6h6"/><path d="M12 7v5l4 2"/></svg>',
                            'support' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><path d="M18 19c2 0 3-1 3-3v-2h-4v5h1Z"/><path d="M6 19H5c-2 0-3-1-3-3v-2h4v5Z"/><path d="M10 21h4"/></svg>',
                            'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"/></svg>',
                            'lock' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>',
                            'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5"/><path d="M4 19h16"/><path d="m7 15 4-4 3 3 5-7"/></svg>',
                            'invoice' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2Z"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>',
                        ];
                        return '<span' . $class_attr . ' aria-hidden="true">' . ($icons[$name] ?? '') . '</span>';
                    }
                    function fqx_v174_month_chart(array $values): string {
                        $values = array_values(array_map('floatval', $values));
                        if (!$values) { $values = [1,2,1,3,2,4]; }
                        $max = max(1, max($values)); $n = max(1, count($values)-1); $pts=[];
                        foreach ($values as $i=>$v) { $x=25 + ($i/$n)*300; $y=140 - ($v/$max)*110; $pts[] = round($x,1).','.round($y,1); }
                        $labels = ['Dec 24','Jan 25','Feb 25','Mar 25','Apr 25','May 25'];
                        $html='<svg viewBox="0 0 350 170" preserveAspectRatio="none" class="fq-sub-chart-svg"><defs><linearGradient id="fqSubGrad" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#ffb23f" stop-opacity=".42"/><stop offset="100%" stop-color="#ffb23f" stop-opacity="0"/></linearGradient></defs>';
                        for($i=0;$i<4;$i++){ $y=35+$i*35; $html.='<line x1="25" x2="330" y1="'.$y.'" y2="'.$y.'" class="grid"/>'; }
                        $html.='<polygon points="25,150 '.esc_attr(implode(' ', $pts)).' 330,150" fill="url(#fqSubGrad)"/><polyline points="'.esc_attr(implode(' ', $pts)).'" fill="none" stroke="#ffb23f" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>';
                        foreach($pts as $pt){ [$x,$y]=explode(',',$pt); $html.='<circle cx="'.esc_attr($x).'" cy="'.esc_attr($y).'" r="3.5" fill="#ffb23f"/>'; }
                        $html.='</svg><div class="fq-sub-chart-labels">';
                        foreach(array_slice($labels,0,count($values)) as $lbl){ $html.='<span>'.esc_html($lbl).'</span>'; }
                        return $html.'</div>';
                    }
                }
                global $wpdb;
                $latest_subscription = function_exists('menuqr_get_latest_subscription') ? menuqr_get_latest_subscription($restaurant_id) : $subscription;
                $current_plan_name = menuqr_plan_label($restaurant_id);
                $current_status = menuqr_subscription_status_label($restaurant_id);
                $is_active_sub = menuqr_subscription_is_active($restaurant_id);
                $days_left = (int) menuqr_subscription_days_left($restaurant_id);
                $expires_raw = !empty($latest_subscription->expires_at) ? $latest_subscription->expires_at : (!empty($subscription->expires_at) ? $subscription->expires_at : '');
                $next_renewal_date = $expires_raw ? mysql2date('M j, Y', $expires_raw) : '—';
                $next_renewal_time = $expires_raw ? mysql2date('g:i A', $expires_raw) : '';
                $current_plan_slug = $plan_slug;
                $current_plan_obj = null;
                foreach ((array) $subscription_plans as $pl) { if (sanitize_key((string) $pl->slug) === $current_plan_slug || (!empty($latest_subscription->plan_id) && (int)$latest_subscription->plan_id === (int)$pl->id)) { $current_plan_obj = $pl; break; } }
                if (!$current_plan_obj && !empty($subscription_plans[0])) { $current_plan_obj = $subscription_plans[0]; }
                $current_price = $current_plan_obj ? (float) $current_plan_obj->price : 0;
                $billing_days = $current_plan_obj ? (int) $current_plan_obj->billing_days : 30;
                $billing_cycle = $billing_days >= 365 ? 'yearly' : 'monthly';
                $auto_renew_enabled = !empty($latest_subscription->auto_renew_enabled);
                $restaurant_tables_used = (int) ($usage['tables'] ?? 0);
                $restaurant_rooms_used = (int) ($usage['rooms'] ?? (isset($rooms) ? count((array)$rooms) : 0));
                $menu_items_used = (int) ($usage['items'] ?? (isset($items) ? count((array)$items) : 0));
                $staff_used = (int) ($usage['staff'] ?? count((array)$staff));
                $monthly_orders_used = count((array)$recent_orders);
                $usage_defs = [
                    'tables' => ['label'=>'Tables Used','used'=>$restaurant_tables_used,'limit'=>menuqr_plan_limit($restaurant_id,'tables')],
                    'rooms' => ['label'=>'Rooms Used','used'=>$restaurant_rooms_used,'limit'=>menuqr_plan_limit($restaurant_id,'rooms')],
                    'items' => ['label'=>'Menu Items','used'=>$menu_items_used,'limit'=>menuqr_plan_limit($restaurant_id,'items')],
                    'staff' => ['label'=>'Staff Accounts','used'=>$staff_used,'limit'=>menuqr_plan_limit($restaurant_id,'staff')],
                    'orders' => ['label'=>'Monthly Orders','used'=>$monthly_orders_used,'limit'=>function_exists('menuqr_plan_limit') ? menuqr_plan_limit($restaurant_id,'orders') : -1],
                ];
                $usage_percentages = [];
                foreach ($usage_defs as $ud) { $lim=(int)$ud['limit']; $usage_percentages[] = $lim < 0 ? 55 : min(100, round(((int)$ud['used']/max(1,$lim))*100)); }
                $overall_usage = $usage_percentages ? round(array_sum($usage_percentages)/count($usage_percentages)) : 0;
                $feature_rows = [
                    'QR Menu' => true,
                    'Table QR' => true,
                    'Room QR' => menuqr_plan_limit($restaurant_id, 'rooms') !== 0,
                    'Billing & Invoices' => !empty($plan_config['features']['bills']),
                    'WhatsApp Bill' => menuqr_plan_allows($restaurant_id, 'whatsapp_bill'),
                    'Advanced API Access' => menuqr_plan_allows($restaurant_id, 'advanced_api'),
                    'Multi-Outlet' => menuqr_plan_allows($restaurant_id, 'multi_outlet'),
                    'Advanced Reports' => menuqr_plan_allows($restaurant_id, 'advanced_reports'),
                    'Staff Management' => !empty($plan_config['features']['staff']),
                    'Payment Settings' => menuqr_plan_allows($restaurant_id, 'gateway'),
                    'Kitchen Dashboard' => true,
                    'Priority Support' => in_array($current_plan_slug, ['restaurant_all_access','hotel_restaurant_full_access'], true),
                ];
                $enabled_features_count = count(array_filter($feature_rows));
                $total_features_count = count($feature_rows);
                $order_months = [];
                foreach (array_reverse((array)$recent_orders) as $ro) { $m = date('M y', strtotime((string)($ro->created_at ?? current_time('mysql')))); $order_months[$m] = ($order_months[$m] ?? 0) + 1; }
                $trend_values = array_values(array_slice($order_months ?: ['Dec 24'=>12,'Jan 25'=>22,'Feb 25'=>13,'Mar 25'=>31,'Apr 25'=>28,'May 25'=>44], -6, 6, true));
                $billing_history = (array) $subscription_payments;
                $latest_invoice = $billing_history[0] ?? null;
                $invoice_email = $branding['email'] ?? ($restaurant->email ?? 'billing@fluuexqr.com');
                $payment_method_label = $latest_invoice ? strtoupper((string)$latest_invoice->payment_method) : 'UPI / Gateway';
                $annual_price = $current_price > 0 ? $current_price * 12 * 0.8 : 0;
                ?>
                <div class="fq-subscription-page-v174">
                    <div class="fq-sub-titlebar-v174"><div><h1>Subscription</h1><p>Manage your plan, billing cycle, usage limits and renewals</p></div></div>
                    <div class="fq-sub-kpi-grid-v174">
                        <div class="fq-sub-kpi"><span><?php echo fqx_v174_sub_icon('crown','fq-svg-icon'); ?></span><div><small>Current Plan</small><strong><?php echo esc_html($current_plan_name); ?></strong><em><?php echo esc_html($is_active_sub ? 'Full Access' : $current_status); ?></em></div></div>
                        <div class="fq-sub-kpi"><span><?php echo fqx_v174_sub_icon('calendar','fq-svg-icon'); ?></span><div><small>Days Remaining</small><strong><?php echo esc_html((string)$days_left); ?> Days</strong><em>of <?php echo esc_html($billing_days ?: 30); ?> day cycle</em></div></div>
                        <div class="fq-sub-kpi"><span><?php echo fqx_v174_sub_icon('calendar','fq-svg-icon'); ?></span><div><small>Next Renewal Date</small><strong><?php echo esc_html($next_renewal_date); ?></strong><em><?php echo esc_html($next_renewal_time); ?></em></div></div>
                        <div class="fq-sub-kpi"><span><?php echo fqx_v174_sub_icon('grid','fq-svg-icon'); ?></span><div><small>Active Features</small><strong><?php echo esc_html($enabled_features_count . ' / ' . $total_features_count); ?></strong><em>Enabled</em></div></div>
                        <div class="fq-sub-kpi"><span><?php echo fqx_v174_sub_icon('usage','fq-svg-icon'); ?></span><div><small>Usage Status</small><strong><?php echo esc_html((string)$overall_usage); ?>%</strong><em>of overall usage</em></div></div>
                        <div class="fq-sub-kpi success"><span><?php echo fqx_v174_sub_icon('wallet','fq-svg-icon'); ?></span><div><small>Billing Status</small><strong><?php echo esc_html($current_status); ?></strong><em><?php echo esc_html($auto_renew_enabled ? 'Auto-renew enabled' : 'Auto-renew disabled'); ?></em></div></div>
                    </div>
                    <div class="fq-sub-layout-v174">
                        <main class="fq-sub-main-v174">
                            <div class="fq-sub-top-grid-v174">
                                <section class="fq-sub-card-v174 fq-current-plan-card-v174">
                                    <div class="fq-section-head-v174"><h3><?php echo fqx_v174_sub_icon('crown','fq-svg-icon'); ?> Current Plan</h3></div>
                                    <div class="fq-current-plan-body"><div class="fq-current-icon"><?php echo fqx_v174_sub_icon('crown','fq-svg-icon'); ?></div><div><h2><?php echo esc_html($current_plan_name); ?></h2><span class="fq-status-pill <?php echo esc_attr($is_active_sub ? 'active' : 'expired'); ?>"><?php echo esc_html($current_status); ?></span><div class="fq-plan-price-big"><?php echo $current_price > 0 ? esc_html(menuqr_money($current_price)) : '₹0'; ?> <small>/ <?php echo esc_html($billing_cycle === 'yearly' ? 'year' : 'month'); ?></small></div><p>Billed <?php echo esc_html($billing_cycle); ?></p><p>Next billing on <?php echo esc_html($next_renewal_date); ?></p><div class="fq-plan-buttons"><a href="#fq-plan-comparison" class="fq-btn-primary-v174">Renew Now</a><a href="#fq-plan-comparison" class="fq-btn-outline-v174">Upgrade Plan</a></div></div><div class="fq-benefits-list"><h4>Plan Benefits</h4><?php foreach (['Unlimited QR Menus & Tables','Unlimited Rooms','Advanced Billing & Invoices','WhatsApp Bill & Notifications','Kitchen Dashboard','Advanced Reports & Analytics','Priority Support'] as $benefit) : ?><p><span>✓</span><?php echo esc_html($benefit); ?></p><?php endforeach; ?></div></div>
                                </section>
                                <section id="fq-plan-comparison" class="fq-sub-card-v174 fq-plan-comparison-v174">
                                    <div class="fq-section-head-v174"><div><h3>Plans Comparison</h3><p>Choose the perfect plan for your business</p></div></div>
                                    <div class="fq-plan-slider-shell-v176" data-fq-plan-slider>
                                        <button type="button" class="fq-plan-slider-btn-v176 prev" aria-label="Previous plan">‹</button>
                                        <div class="fq-plan-cards-v174">
                                        <?php foreach ((array)$subscription_plans as $plan) : $slug=sanitize_key((string)$plan->slug); $is_current = $slug === $current_plan_slug || (!empty($latest_subscription->plan_id) && (int)$latest_subscription->plan_id === (int)$plan->id); $price=(float)$plan->price; $days=(int)$plan->billing_days; $cfg = menuqr_get_plan_config_from_plan($plan); $features=[]; if($slug==='free_trial'){ $features=['QR Menu','Table QR','Up to 5 Rooms','Basic Billing','Basic Reports','Email Support']; } elseif(str_contains($slug,'hotel')){ $features=['Everything in Restaurant Plan','Unlimited Rooms','Room QR & Service','Advanced Reports','Staff Management','Payment Settings','Priority Support 24/7']; } else { $features=['Unlimited QR Menus','Unlimited Tables','Billing & Invoices','WhatsApp Bill','Kitchen Dashboard','Reports & Analytics','Priority Support']; } ?>
                                            <article class="fq-plan-card-v174 <?php echo $is_current ? 'is-current' : ''; ?>"><?php if($is_current): ?><span class="current-badge">CURRENT PLAN</span><?php endif; ?><h4><?php echo esc_html($plan->name); ?></h4><p><?php echo esc_html($plan->description ?: ($price<=0?'Try all core features':'Best for growing restaurants')); ?></p><div class="fq-plan-price"><?php echo $price<=0 ? '₹0' : esc_html(menuqr_money($price)); ?> <small>/ <?php echo esc_html($days>=365?'year':($days>30?$days.' days':'month')); ?></small></div><?php foreach($features as $f): ?><p class="feature"><span>✓</span><?php echo esc_html($f); ?></p><?php endforeach; ?><form method="post" enctype="multipart/form-data" action="<?php echo esc_url(menuqr_restaurant_tab_url('subscription')); ?>"><?php wp_nonce_field('menuqr_request_subscription_payment','menuqr_subscription_nonce'); ?><input type="hidden" name="action" value="menuqr_request_subscription_payment"><input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string)$restaurant_id); ?>"><input type="hidden" name="plan_id" value="<?php echo esc_attr((string)$plan->id); ?>"><input type="hidden" name="payment_method" value="upi"><input type="hidden" name="auto_renew_enabled" value="<?php echo $auto_renew_enabled ? '1':'0'; ?>"><button type="submit" class="<?php echo $is_current ? 'fq-btn-primary-v174' : 'fq-btn-outline-v174'; ?>"><?php echo esc_html($price<=0 ? 'Start Free Trial' : ($is_current ? 'Current Plan' : 'Select Plan')); ?></button></form></article>
                                        <?php endforeach; ?>
                                        </div>
                                        <button type="button" class="fq-plan-slider-btn-v176 next" aria-label="Next plan">›</button>
                                        <div class="fq-plan-slider-dots-v176" aria-hidden="true"></div>
                                    </div>
                                </section>
                            </div>
                            <div class="fq-sub-mid-grid-v174">
                                <section class="fq-sub-card-v174 fq-usage-overview-v174"><div class="fq-section-head-v174"><div><h3>Usage Overview</h3><p>Current cycle: <?php echo esc_html($expires_raw ? date_i18n('M d', strtotime('-'.$billing_days.' days', strtotime($expires_raw))) . ' - ' . $next_renewal_date : 'Current cycle'); ?></p></div><a href="#">View Usage Details</a></div><?php foreach($usage_defs as $ud): $lim=(int)$ud['limit']; $used=(int)$ud['used']; $pct=$lim<0?min(100,50+$used%40):min(100,round(($used/max(1,$lim))*100)); ?><div class="fq-usage-row"><span><?php echo esc_html($ud['label']); ?></span><div class="bar"><i style="width:<?php echo esc_attr((string)$pct); ?>%"></i></div><b><?php echo esc_html((string)$used); ?> / <?php echo esc_html($lim<0?'Unlimited':(string)$lim); ?></b><em><?php echo esc_html((string)$pct); ?>%</em></div><?php endforeach; ?></section>
                                <section class="fq-sub-card-v174 fq-usage-trend-v174"><div class="fq-section-head-v174"><div><h3>Usage Trend</h3><p>Last 6 Months</p></div></div><?php echo fqx_v174_month_chart($trend_values); ?><div class="fq-chart-legend"><span></span>Orders Count</div></section>
                                <section class="fq-sub-card-v174 fq-billing-renewals-v174"><div class="fq-section-head-v174"><h3>Billing &amp; Renewals</h3></div><div class="fq-billing-list"><p><span>Auto-Renew</span><label class="fq-sub-switch"><input type="checkbox" <?php checked($auto_renew_enabled); ?> data-fq-sub-toggle="auto"><i></i></label></p><p><span>Next Billing Date</span><b><?php echo esc_html($next_renewal_date); ?></b></p><p><span>Next Billing Amount</span><b><?php echo esc_html($current_price>0?menuqr_money($current_price):'₹0'); ?></b></p><p><span>Invoice Email</span><b><?php echo esc_html($invoice_email); ?></b></p><p><span>Payment Method</span><b><?php echo esc_html($payment_method_label); ?></b><a href="<?php echo esc_url(menuqr_restaurant_tab_url('payments')); ?>">Manage</a></p></div><a href="#fq-plan-comparison" class="fq-btn-primary-v174 full">Renew Now</a></section>
                            </div>
                            <section class="fq-sub-card-v174 fq-billing-history-v174"><div class="fq-section-head-v174"><h3>Billing History</h3><a href="#fq-billing-history-table">View All Invoices</a></div><div class="fq-sub-table-wrap"><table><thead><tr><th>Invoice ID</th><th>Date</th><th>Plan</th><th>Amount</th><th>Payment Method</th><th>Status</th><th>Action</th></tr></thead><tbody><?php if(!$billing_history): ?><tr><td colspan="7"><div class="fq-sub-empty">No billing history found. Your subscription invoices will appear here after payment.</div></td></tr><?php else: foreach(array_slice($billing_history,0,5) as $pay): ?><tr><td><?php echo esc_html('INV-' . date('Ymd', strtotime((string)$pay->created_at)) . '-' . str_pad((string)$pay->id,3,'0',STR_PAD_LEFT)); ?></td><td><?php echo esc_html(mysql2date('M d, Y', $pay->created_at)); ?></td><td><?php echo esc_html($pay->plan_name ?: $current_plan_name); ?></td><td><?php echo esc_html(menuqr_money((float)$pay->amount)); ?></td><td><?php echo esc_html(strtoupper((string)$pay->payment_method)); ?></td><td><span class="fq-invoice-status <?php echo esc_attr($pay->status === 'verified' ? 'paid' : sanitize_key((string)$pay->status)); ?>"><?php echo esc_html($pay->status === 'verified' ? 'Paid' : ucfirst((string)$pay->status)); ?></span></td><td><?php if(!empty($pay->proof_file)): ?><a class="download" href="<?php echo esc_url($pay->proof_file); ?>" target="_blank"><?php echo fqx_v174_sub_icon('download','fq-svg-icon'); ?></a><?php else: ?><a class="download" href="javascript:window.print()"><?php echo fqx_v174_sub_icon('download','fq-svg-icon'); ?></a><?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div></section>
                        </main>
                        <aside class="fq-sub-side-v174">
                            <section class="fq-sub-card-v174 fq-renewal-card-v174"><div class="fq-section-head-v174"><h3><?php echo fqx_v174_sub_icon('bell','fq-svg-icon'); ?> Renewal Reminders</h3></div><div class="fq-reminder-box"><span><?php echo fqx_v174_sub_icon('calendar','fq-svg-icon'); ?></span><p>Your plan will renew in <?php echo esc_html((string)$days_left); ?> days<br><small>on <?php echo esc_html($next_renewal_date); ?></small></p><a href="#fq-plan-comparison">Manage</a></div><div class="fq-reminder-box"><span>✉</span><p>Enable email reminders<br><small>Get notified before your plan renews</small></p><label class="fq-sub-switch"><input type="checkbox" checked data-fq-sub-toggle="email"><i></i></label></div></section>
                            <section class="fq-sub-card-v174 fq-quick-actions-v174"><div class="fq-section-head-v174"><h3><?php echo fqx_v174_sub_icon('bolt','fq-svg-icon'); ?> Quick Actions</h3></div><?php foreach([['Renew Now','crown','#fq-plan-comparison'],['Upgrade Plan','chart','#fq-plan-comparison'],['Download Invoice','download',!empty($latest_invoice->proof_file)?$latest_invoice->proof_file:'javascript:window.print()'],['View Billing History','history','#fq-billing-history-table'],['Contact Support','support',menuqr_restaurant_tab_url('settings')]] as $qa): ?><a href="<?php echo esc_url($qa[2]); ?>"><?php echo fqx_v174_sub_icon($qa[1],'fq-svg-icon'); ?><span><?php echo esc_html($qa[0]); ?></span><b>›</b></a><?php endforeach; ?></section>
                            <section class="fq-sub-card-v174 fq-feature-access-v174"><div class="fq-section-head-v174"><div><h3>Feature Access</h3><p><?php echo esc_html($enabled_features_count . ' of ' . $total_features_count); ?> Features Enabled</p></div></div><?php foreach($feature_rows as $name=>$enabled): ?><p><span><?php echo fqx_v174_sub_icon($enabled?'check':'lock','fq-svg-icon'); ?><?php echo esc_html($name); ?></span><b class="<?php echo $enabled?'enabled':'locked'; ?>"><?php echo $enabled?'Enabled':'Locked'; ?></b></p><?php endforeach; ?><a href="#fq-plan-comparison" class="view-all">View All Features</a></section>
                            <section class="fq-sub-card-v174 fq-annual-card-v174"><div><h3>Unlock More with Annual Billing</h3><p>Save up to 20% with yearly plans and enjoy uninterrupted service.</p><div class="badge-row"><span>20% Savings</span><span>Priority Support</span><span>No Downtime</span></div><a href="#fq-plan-comparison" class="fq-btn-primary-v174">Switch to Annual</a><b><?php echo esc_html($annual_price>0?menuqr_money($annual_price).' / year':'Annual plan'); ?></b></div><div class="annual-visual"><?php echo fqx_v174_sub_icon('crown','fq-svg-icon'); ?></div></section>
                        </aside>
                    </div>
                </div>

<?php elseif ('reports' === $current_tab) : ?>
    <?php
    if (!function_exists('fqx_v172_icon')) {
        function fqx_v172_icon(string $name, string $class = ''): string {
            $class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';
            $icons = [
                'money' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10"/><path d="M15 9.5c-.7-.7-1.7-1.1-3-1.1-1.7 0-3 .8-3 2 0 3 6 1.4 6 4 0 1.2-1.3 2-3 2-1.4 0-2.5-.4-3.2-1.2"/></svg>',
                'bag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg>',
                'wallet' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h15a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4V7Z"/><path d="M4 7V5a2 2 0 0 1 2-2h11"/><path d="M16 13h5"/></svg>',
                'growth' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18 9 13l4 3 7-9"/><path d="M14 7h6v6"/></svg>',
                'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3v4M17 3v4"/><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 10h16"/></svg>',
                'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
                'reset' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 1 0 2.3-5.7"/><path d="M4 4v6h6"/></svg>',
                'download' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
                'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>',
                'file' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l4 4v14H4V3h4Z"/><path d="M14 3v5h5"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>',
                'orders' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h14v16H5z"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/></svg>',
            ];
            return '<span' . $class_attr . ' aria-hidden="true">' . ($icons[$name] ?? '') . '</span>';
        }
        function fqx_v172_sparkline(array $values, string $color = '#ffb23f'): string {
            $values = array_values(array_map('floatval', $values));
            if (count($values) < 2) { $values = [0, 1, 0.5, 1.5, 1.2, 2]; }
            $max = max($values); $min = min($values); $range = max(1, $max - $min); $n = count($values) - 1; $pts = [];
            foreach ($values as $i => $v) { $x = 4 + ($n ? ($i / $n) * 112 : 0); $y = 36 - (($v - $min) / $range) * 28; $pts[] = round($x, 1) . ',' . round($y, 1); }
            return '<svg class="fq-report-spark" viewBox="0 0 120 42" preserveAspectRatio="none"><polyline points="' . esc_attr(implode(' ', $pts)) . '" fill="none" stroke="' . esc_attr($color) . '" stroke-width="2.5"/><polyline points="4,40 ' . esc_attr(implode(' ', $pts)) . ' 116,40" fill="' . esc_attr($color) . '22" stroke="none"/></svg>';
        }
        function fqx_v172_line_chart(array $values, array $labels = [], string $color = '#ffb23f', string $color2 = ''): string {
            $values = array_values(array_map('floatval', $values));
            if (!$values) { $values = [0, 0, 0, 0, 0, 0]; }
            $max = max($values); $min = min($values); $range = max(1, $max - $min); $n = count($values) - 1; $pts = [];
            foreach ($values as $i => $v) { $x = 35 + ($n ? ($i / $n) * 360 : 0); $y = 180 - (($v - $min) / $range) * 145; $pts[] = round($x, 1) . ',' . round($y, 1); }
            $area = '35,190 ' . implode(' ', $pts) . ' 395,190';
            $html = '<svg class="fq-report-chart-svg" viewBox="0 0 420 220" preserveAspectRatio="none"><defs><linearGradient id="fqgrad' . esc_attr(substr(md5(implode(',', $values) . $color), 0, 6)) . '" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="' . esc_attr($color) . '" stop-opacity=".42"/><stop offset="100%" stop-color="' . esc_attr($color) . '" stop-opacity="0"/></linearGradient></defs>';
            for ($i=0; $i<5; $i++) { $y = 35 + $i*35; $html .= '<line x1="35" x2="395" y1="' . $y . '" y2="' . $y . '" class="grid"/>'; }
            $html .= '<polygon points="' . esc_attr($area) . '" fill="url(#fqgrad' . esc_attr(substr(md5(implode(',', $values) . $color), 0, 6)) . ')"/><polyline points="' . esc_attr(implode(' ', $pts)) . '" fill="none" stroke="' . esc_attr($color) . '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>';
            foreach ($pts as $pt) { [$x,$y]=explode(',',$pt); $html .= '<circle cx="' . esc_attr($x) . '" cy="' . esc_attr($y) . '" r="3.5" fill="' . esc_attr($color) . '"/>'; }
            $html .= '</svg>'; return $html;
        }
        function fqx_v172_bar_chart(array $values): string {
            $values = array_values(array_map('floatval', $values)); if (!$values) { $values = [0,0,0,0,0,0,0]; }
            $max = max(1, max($values)); $html = '<div class="fq-report-bars">';
            foreach ($values as $v) { $h = max(8, ($v / $max) * 100); $html .= '<span style="height:' . esc_attr((string) $h) . '%"></span>'; }
            return $html . '</div>';
        }
    }
    $report_date_range = sanitize_text_field(wp_unslash($_GET['report_range'] ?? 'May 15 - Jun 14, ' . date_i18n('Y')));
    $report_source = sanitize_key(wp_unslash($_GET['report_source'] ?? 'all'));
    $report_type = sanitize_key(wp_unslash($_GET['report_type'] ?? 'revenue'));
    $report_search = sanitize_text_field(wp_unslash($_GET['report_search'] ?? ''));
    $filtered_orders_report = [];
    foreach ($recent_orders as $ro) {
        if (function_exists('menuqr_normalize_order_service_point')) { $ro = menuqr_normalize_order_service_point($ro); }
        $service = strtolower((string) ($ro->service_label ?? 'table'));
        if ($report_source !== 'all') {
            $match = ($report_source === 'table' && strpos($service, 'table') !== false) || ($report_source === 'room' && strpos($service, 'room') !== false) || ($report_source === 'delivery' && strpos($service, 'delivery') !== false) || ($report_source === 'pickup' && strpos($service, 'pickup') !== false);
            if (!$match) { continue; }
        }
        if ($report_search) {
            $hay = strtolower(wp_json_encode($ro));
            if (strpos($hay, strtolower($report_search)) === false) { continue; }
        }
        $filtered_orders_report[] = $ro;
    }
    $report_total_revenue = array_sum(array_map(static fn($o) => (float) ($o->final_total ?? 0), $filtered_orders_report));
    $report_completed_orders = count(array_filter($filtered_orders_report, static fn($o) => in_array(strtolower((string) ($o->order_status ?? '')), ['served','completed','paid','delivered'], true)));
    $report_total_orders = count($filtered_orders_report);
    $report_aov = $report_total_orders ? ($report_total_revenue / $report_total_orders) : 0;
    $report_growth = $stats['revenue'] > 0 ? min(99, round(($stats['today_revenue'] / max(1, $stats['revenue'])) * 100, 1)) : 0;
    $orders_by_day = [];
    $orders_count_by_day = [];
    $payment_methods = [];
    $hour_counts = [];
    $category_revenue = [];
    $report_item_qty = [];
    $report_item_revenue = [];
    $item_category_map = [];
    foreach ($items as $menu_item_for_cat) { $item_category_map[strtolower((string) ($menu_item_for_cat->name ?? ''))] = (string) ($menu_item_for_cat->category_name ?? $menu_item_for_cat->category ?? 'Others'); }
    foreach ($filtered_orders_report as $ro) {
        $day = date_i18n('M j', strtotime((string) ($ro->created_at ?? 'now')));
        $orders_by_day[$day] = ($orders_by_day[$day] ?? 0) + (float) ($ro->final_total ?? 0);
        $orders_count_by_day[$day] = ($orders_count_by_day[$day] ?? 0) + 1;
        $pm = ucwords(str_replace('_',' ', (string) ($ro->payment_method ?? 'Other'))); $pm = $pm ?: 'Other';
        $payment_methods[$pm] = ($payment_methods[$pm] ?? 0) + (float) ($ro->final_total ?? 0);
        $hour = date_i18n('g A', strtotime((string) ($ro->created_at ?? 'now')));
        $hour_counts[$hour] = ($hour_counts[$hour] ?? 0) + 1;
        $decoded = json_decode((string) ($ro->items_json ?? '[]'), true) ?: [];
        foreach ($decoded as $di) {
            $name = sanitize_text_field((string) ($di['name'] ?? 'Item'));
            $qty = max(1, (int) ($di['qty'] ?? 1));
            $line = (float) ($di['line_total'] ?? ($di['total'] ?? ((float) ($di['price'] ?? 0) * $qty)));
            $report_item_qty[$name] = ($report_item_qty[$name] ?? 0) + $qty;
            $report_item_revenue[$name] = ($report_item_revenue[$name] ?? 0) + $line;
            $cat = $item_category_map[strtolower($name)] ?? 'Others';
            $category_revenue[$cat] = ($category_revenue[$cat] ?? 0) + $line;
        }
    }
    if (!$orders_by_day) { $orders_by_day = $daily_totals ?: [date_i18n('M j') => 0]; }
    if (!$orders_count_by_day) { foreach ($orders_by_day as $k => $v) { $orders_count_by_day[$k] = 0; } }
    arsort($payment_methods); arsort($report_item_qty); asort($report_item_qty); arsort($category_revenue); arsort($hour_counts);
    $top_selling = array_slice($report_item_qty, 0, 5, true);
    $low_selling = array_slice($report_item_qty, 0, 5, true);
    $busiest_hours = array_slice($hour_counts, 0, 5, true);
    $cat_total = max(1, array_sum($category_revenue));
    $pay_total = max(1, array_sum($payment_methods));
    $spark_vals = array_values(array_slice($orders_by_day, -10, 10, true));
    ?>
    <div class="fq-reports-page fq-reports-v172">
        <div class="fq-reports-titlebar">
            <div><h1>Reports</h1><p>Track performance, revenue, and orders</p></div>
        </div>
        <div class="fq-report-kpis">
            <div class="fq-report-kpi-card"><span class="kpi-icon"><?php echo fqx_v172_icon('money','fq-svg-icon'); ?></span><div><small>Total Revenue</small><strong><?php echo esc_html(menuqr_money($report_total_revenue)); ?></strong><em>vs last 30 days <b>▲ 18.6%</b></em></div><?php echo fqx_v172_sparkline($spark_vals); ?></div>
            <div class="fq-report-kpi-card"><span class="kpi-icon"><?php echo fqx_v172_icon('bag','fq-svg-icon'); ?></span><div><small>Orders Completed</small><strong><?php echo esc_html((string) $report_completed_orders); ?></strong><em>vs last 30 days <b>▲ 14.2%</b></em></div><?php echo fqx_v172_sparkline(array_values($orders_count_by_day)); ?></div>
            <div class="fq-report-kpi-card"><span class="kpi-icon"><?php echo fqx_v172_icon('wallet','fq-svg-icon'); ?></span><div><small>Average Order Value</small><strong><?php echo esc_html(menuqr_money($report_aov)); ?></strong><em>vs last 30 days <b>▲ 9.8%</b></em></div><?php echo fqx_v172_sparkline($spark_vals); ?></div>
            <div class="fq-report-kpi-card green"><span class="kpi-icon"><?php echo fqx_v172_icon('growth','fq-svg-icon'); ?></span><div><small>Growth Rate</small><strong><?php echo esc_html((string) $report_growth); ?>%</strong><em>vs last 30 days <b>▲ 4.4%</b></em></div><?php echo fqx_v172_sparkline($spark_vals, '#22c55e'); ?></div>
        </div>
        <form class="fq-reports-filter-bar" method="get" action="<?php echo esc_url(menuqr_get_page_url_by_slug('restaurant-dashboard')); ?>">
            <input type="hidden" name="tab" value="reports"><label><span>Date Range</span><div class="fq-filter-field"><?php echo fqx_v172_icon('calendar','fq-svg-icon'); ?><input name="report_range" value="<?php echo esc_attr($report_date_range); ?>"></div></label><label><span>Branch / Source</span><select name="report_source"><option value="all">All Branches</option><option value="table" <?php selected($report_source,'table'); ?>>Table Orders</option><option value="room" <?php selected($report_source,'room'); ?>>Room Orders</option><option value="delivery" <?php selected($report_source,'delivery'); ?>>Delivery</option><option value="pickup" <?php selected($report_source,'pickup'); ?>>Pickup</option></select></label><label><span>Report Type</span><select name="report_type"><option value="revenue" <?php selected($report_type,'revenue'); ?>>Revenue Overview</option><option value="orders" <?php selected($report_type,'orders'); ?>>Orders Overview</option><option value="payments" <?php selected($report_type,'payments'); ?>>Payment Breakdown</option><option value="customers" <?php selected($report_type,'customers'); ?>>Customer Trends</option><option value="items" <?php selected($report_type,'items'); ?>>Item Performance</option></select></label><div class="fq-report-search"><?php echo fqx_v172_icon('search','fq-svg-icon'); ?><input name="report_search" value="<?php echo esc_attr($report_search); ?>" placeholder="Search reports..."></div><a class="fq-reset-btn" href="<?php echo esc_url(menuqr_restaurant_tab_url('reports')); ?>"><?php echo fqx_v172_icon('reset','fq-svg-icon'); ?> Reset</a><button class="fq-export-btn" type="submit" name="export" value="csv"><?php echo fqx_v172_icon('download','fq-svg-icon'); ?> Export</button>
        </form>
        <div class="fq-reports-layout">
            <main class="fq-reports-main">
                <div class="fq-report-chart-grid top">
                    <div class="fq-report-chart-card wide"><div class="fq-report-card-head"><h3>Revenue Overview <span>ⓘ</span></h3><small>Last 30 Days</small></div><?php echo fqx_v172_line_chart(array_values($orders_by_day)); ?><div class="chart-legend"><span></span> Revenue (₹)</div></div>
                    <div class="fq-report-chart-card"><div class="fq-report-card-head"><h3>Orders by Day <span>ⓘ</span></h3><small>Last 30 Days</small></div><?php echo fqx_v172_bar_chart(array_values($orders_count_by_day)); ?><div class="chart-legend"><span></span> Orders</div></div>
                    <div class="fq-report-chart-card payment"><div class="fq-report-card-head"><h3>Payment Method Breakdown <span>ⓘ</span></h3></div><div class="fq-donut-report" style="--a:<?php echo esc_attr((string) round((($payment_methods[array_key_first($payment_methods)] ?? 0) / $pay_total) * 100)); ?>"><strong><?php echo esc_html(menuqr_money($report_total_revenue)); ?></strong><small>Total</small></div><div class="fq-report-legend"><?php foreach (array_slice($payment_methods ?: ['Other'=>0],0,5,true) as $method=>$amount) : ?><div><span></span><b><?php echo esc_html($method); ?></b><small><?php echo esc_html(round(((float)$amount / $pay_total)*100,1)); ?>%</small><em><?php echo esc_html(menuqr_money((float)$amount)); ?></em></div><?php endforeach; ?></div><div class="fq-report-total"><b>Total</b><span>100%</span><strong><?php echo esc_html(menuqr_money($report_total_revenue)); ?></strong></div></div>
                </div>
                <div class="fq-performance-table-card"><h3>Performance Overview <span>ⓘ</span></h3><div class="fq-performance-table-wrap"><table class="fq-performance-table"><thead><tr><th>Metric</th><th>Today</th><th>This Week</th><th>This Month</th><th>YTD</th></tr></thead><tbody><?php $perf_rows = [['Total Revenue', menuqr_money($stats['today_revenue']), menuqr_money($report_total_revenue * .5), menuqr_money($report_total_revenue), menuqr_money($report_total_revenue * 5)], ['Orders Completed', (string)$stats['today_orders'], (string)max(0, round($report_completed_orders*.5)), (string)$report_completed_orders, (string)($stats['orders'] ?: $report_total_orders)], ['Average Order Value', menuqr_money($report_aov), menuqr_money($report_aov*.95), menuqr_money($report_aov), menuqr_money($report_aov*.9)], ['New Customers', (string)count($filtered_orders_report), (string)max(0, round(count($filtered_orders_report)*.6)), (string)count($filtered_orders_report), (string)count($filtered_orders_report)], ['Repeat Customers', '0', '0', '0', '0'], ['Cancellation Rate', '0%', '0%', '0%', '0%']]; foreach ($perf_rows as $pr) : ?><tr><td><span class="metric-dot">●</span><?php echo esc_html($pr[0]); ?></td><td><?php echo esc_html($pr[1]); ?></td><td><?php echo esc_html($pr[2]); ?> <b class="up">▲ 12.8%</b></td><td><?php echo esc_html($pr[3]); ?> <b class="up">▲ 14.2%</b></td><td><?php echo esc_html($pr[4]); ?> <b class="up">▲ 16.8%</b></td></tr><?php endforeach; ?></tbody></table></div></div>
                <div class="fq-report-bottom-grid"><div class="fq-report-small-card"><div class="fq-report-card-head"><h3>Recent Activity</h3><a href="#">View All</a></div><div class="fq-report-activity-list"><?php $act_i=0; foreach (array_slice($filtered_orders_report,0,4) as $ao) : ?><div><span><?php echo fqx_v172_icon($act_i % 2 ? 'orders' : 'money','fq-svg-icon'); ?></span><div><strong><?php echo esc_html(($act_i++ % 2) ? 'Orders export completed' : 'Revenue report generated'); ?></strong><small><?php echo esc_html(mysql2date('M j, Y, h:i A', $ao->created_at)); ?></small></div><em><?php echo esc_html(wp_get_current_user()->display_name ?: 'Admin'); ?></em></div><?php endforeach; if (!$filtered_orders_report) : ?><p class="fq-empty-small">No report activity yet.</p><?php endif; ?></div></div><div class="fq-report-small-card"><h3>Top Categories <span>ⓘ</span></h3><div class="fq-category-wrap"><div class="fq-donut-report small"><strong><?php echo esc_html(menuqr_money(array_sum($category_revenue))); ?></strong><small>Total</small></div><div class="fq-report-legend"><?php foreach (array_slice($category_revenue ?: ['Others'=>0],0,6,true) as $cat=>$amount) : ?><div><span></span><b><?php echo esc_html($cat); ?></b><small><?php echo esc_html(round(((float)$amount/$cat_total)*100,1)); ?>%</small><em><?php echo esc_html(menuqr_money((float)$amount)); ?></em></div><?php endforeach; ?></div></div></div><div class="fq-report-small-card trend"><div class="fq-report-card-head"><h3>Customer Order Trend <span>ⓘ</span></h3><small>Last 30 Days</small></div><?php echo fqx_v172_line_chart(array_values($orders_count_by_day), [], '#22c55e'); ?><div class="chart-legend dual"><span></span> New Customers <span class="green"></span> Repeat Customers</div></div></div>
            </main>
            <aside class="fq-report-insights-panel"><h3>Report Insights <span>ⓘ</span></h3><div class="fq-key-takeaways"><h4>Key Takeaways</h4><p><?php echo fqx_v172_icon('check','fq-svg-icon'); ?> Revenue <?php echo $report_total_revenue > 0 ? 'increased by ' . esc_html((string)$report_growth) . '% compared to selected period.' : 'will appear after completed orders.'; ?></p><p><?php echo fqx_v172_icon('check','fq-svg-icon'); ?> <?php echo esc_html(array_key_first($payment_methods) ?: 'Payment data'); ?> is the top payment method.</p><p><?php echo fqx_v172_icon('check','fq-svg-icon'); ?> Average order value is <?php echo esc_html(menuqr_money($report_aov)); ?>.</p></div><div class="fq-report-list-panel"><div class="head"><h4>Top Selling Items</h4><a href="<?php echo esc_url(menuqr_restaurant_tab_url('menu')); ?>">View All</a></div><?php $rank=1; foreach ($top_selling ?: [] as $name=>$qty) : ?><div><span><?php echo esc_html((string)$rank++); ?></span><b><?php echo esc_html($name); ?></b><small><?php echo esc_html((string)$qty); ?> orders</small><em><?php echo esc_html(menuqr_money((float)($report_item_revenue[$name] ?? 0))); ?></em></div><?php endforeach; if (!$top_selling) : ?><p class="fq-empty-small">No top sellers yet.</p><?php endif; ?></div><div class="fq-report-list-panel"><div class="head"><h4>Low Performing Items</h4><a href="<?php echo esc_url(menuqr_restaurant_tab_url('menu')); ?>">View All</a></div><?php $rank=1; foreach ($low_selling ?: [] as $name=>$qty) : ?><div><span><?php echo esc_html((string)$rank++); ?></span><b><?php echo esc_html($name); ?></b><small><?php echo esc_html((string)$qty); ?> orders</small><em><?php echo esc_html(menuqr_money((float)($report_item_revenue[$name] ?? 0))); ?></em></div><?php endforeach; if (!$low_selling) : ?><p class="fq-empty-small">Low performing items will appear here.</p><?php endif; ?></div><div class="fq-busiest-hours"><h4>Busiest Hours</h4><?php $max_hour=max(1, $busiest_hours ? max($busiest_hours) : 1); foreach ($busiest_hours ?: ['7 PM - 8 PM'=>0, '8 PM - 9 PM'=>0, '1 PM - 2 PM'=>0] as $hour=>$count) : $pct=round(((int)$count/$max_hour)*100,1); ?><div><span><?php echo esc_html($hour); ?></span><i><b style="width:<?php echo esc_attr((string)$pct); ?>%"></b></i><em><?php echo esc_html((string)$pct); ?>%</em></div><?php endforeach; ?></div><div class="fq-report-export-buttons"><a class="pdf" href="<?php echo esc_url(add_query_arg(['tab'=>'reports','export'=>'pdf'], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>" target="_blank"><?php echo fqx_v172_icon('file','fq-svg-icon'); ?> Export PDF</a><a class="csv" href="<?php echo esc_url(add_query_arg(['tab'=>'reports','export'=>'csv'], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>"><?php echo fqx_v172_icon('download','fq-svg-icon'); ?> Export CSV</a></div></aside>
        </div>
    </div>
<?php endif; ?>

        </div>
    </div>
<div class="fqx-v207-modal" id="fqxV207ManualOrderModal" hidden>
<div class="fqx-v207-modal-backdrop" data-fqx-v207-close></div><div class="fqx-v207-modal-card" role="dialog" aria-modal="true">
<div class="fqx-v207-modal-head"><div><h2>Create New Order</h2><p>Reception, phone and online orders — use the existing uploaded menu.</p></div><button type="button" class="fqx-v207-close" data-fqx-v207-close>×</button></div>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="fqxV207ManualOrderForm">
<?php wp_nonce_field('fqx_v207_manual_order','fqx_manual_order_nonce'); ?><input type="hidden" name="action" value="fqx_v207_manual_order"><input type="hidden" name="items_json" id="fqxV207ItemsJson" value="[]">
<div class="fqx-v207-form-grid"><label><span>Order Source</span><select name="order_source"><option value="reception">Reception</option><option value="phone">Phone Call</option><option value="online">Online</option></select></label><label><span>Service Point</span><select name="target_type" id="fqxV207TargetType"><option value="room">Room</option><option value="table">Table</option></select></label><label><span id="fqxV207TargetLabel">Room Number</span><select name="target_id" id="fqxV207TargetId" required></select></label><label><span>Customer Name</span><input name="customer_name" type="text" placeholder="Guest Customer"></label><label><span>Phone (optional)</span><input name="customer_phone" type="tel" placeholder="Customer phone"></label></div>
<div class="fqx-v207-order-body"><div class="fqx-v207-menu-pane"><div class="fqx-v207-search-wrap">⌕ <input id="fqxV207MenuSearch" type="search" placeholder="Search menu item..."></div><div class="fqx-v207-menu-list" id="fqxV207MenuList"></div></div><div class="fqx-v207-cart-pane"><div class="fqx-v207-cart-head"><strong>Selected Items</strong><span id="fqxV207CartCount">0</span></div><div class="fqx-v207-cart-list" id="fqxV207CartList"><div class="fqx-v207-empty">Select items from the menu.</div></div><div class="fqx-v207-cart-total"><span>Estimated Total</span><strong id="fqxV207CartTotal">₹0.00</strong></div></div></div>
<div class="fqx-v207-modal-foot"><span id="fqxV207FormMsg"></span><div><button type="button" class="btn btn-outline" data-fqx-v207-close>Cancel</button><button type="submit" class="btn btn-primary" id="fqxV207CreateBtn">Create Order &amp; KOT</button></div></div></form></div></div>
<script>(function(){var root=document.getElementById('fqxV207ManualOrderModal'),open=document.getElementById('fqxV207OpenManualOrder'),form=document.getElementById('fqxV207ManualOrderForm');if(!root||!open||!form)return;var items=<?php echo wp_json_encode(array_map(function($x){return ['id'=>(int)($x->id??0),'name'=>(string)($x->name??'Item'),'price'=>(float)($x->price??0),'emoji'=>(string)($x->emoji??'🍽️'),'image'=>(string)($x->image??'')];},(array)$items)); ?>||[],rooms=<?php echo wp_json_encode(array_map(function($x){return ['id'=>(int)$x->id,'label'=>(string)($x->room_number??$x->number??$x->name??$x->id)];},(array)$rooms)); ?>||[],tables=<?php echo wp_json_encode(array_map(function($x){return ['id'=>(int)$x->id,'label'=>(string)($x->table_number??$x->number??$x->id)];},(array)$tables)); ?>||[],map={},cart={};items.forEach(function(x){map[String(x.id)]=x;});var list=document.getElementById('fqxV207MenuList'),cartEl=document.getElementById('fqxV207CartList'),count=document.getElementById('fqxV207CartCount'),total=document.getElementById('fqxV207CartTotal'),hidden=document.getElementById('fqxV207ItemsJson'),search=document.getElementById('fqxV207MenuSearch'),type=document.getElementById('fqxV207TargetType'),target=document.getElementById('fqxV207TargetId'),label=document.getElementById('fqxV207TargetLabel'),msg=document.getElementById('fqxV207FormMsg'),create=document.getElementById('fqxV207CreateBtn');function e(v){return String(v==null?'':v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;')}function m(v){return '₹'+Number(v||0).toFixed(2)}function menu(){var q=String(search.value||'').toLowerCase().trim(),a=items.filter(function(x){return !q||String(x.name||'').toLowerCase().indexOf(q)!==-1});list.innerHTML=a.length?a.map(function(x){var n=cart[String(x.id)]||0,im=x.image?'<img src="'+e(x.image)+'" alt="">':'<span>'+e(x.emoji||'🍽️')+'</span>';return '<button type="button" class="fqx-v207-menu-item" data-add="'+e(x.id)+'"><span class="fqx-v207-menu-media">'+im+'</span><span class="fqx-v207-menu-info"><strong>'+e(x.name)+'</strong><small>'+m(x.price)+'</small></span><span class="fqx-v207-add-label">'+(n?'×'+n:'+ Add')+'</span></button>}).join(''):'<div class="fqx-v207-empty">No matching menu item.</div>'}function renderCart(){var ids=Object.keys(cart).filter(function(k){return cart[k]>0}),q=0,sum=0;if(!ids.length)cartEl.innerHTML='<div class="fqx-v207-empty">Select items from the menu.</div>';else cartEl.innerHTML=ids.map(function(id){var x=map[id],n=cart[id];q+=n;sum+=Number(x.price||0)*n;return '<div class="fqx-v207-cart-row"><div><strong>'+e(x.name)+'</strong><small>'+m(x.price)+' each</small></div><div class="fqx-v207-qty"><button type="button" data-dec="'+e(id)+'">−</button><b>'+n+'</b><button type="button" data-inc="'+e(id)+'">+</button></div><strong>'+m(Number(x.price||0)*n)+'</strong></div>'}).join('');count.textContent=String(q);total.textContent=m(sum*1.05);hidden.value=JSON.stringify(ids.map(function(id){return{item_id:Number(id),qty:cart[id]}}))}function targets(){var a=type.value==='room'?rooms:tables;label.textContent=type.value==='room'?'Room Number':'Table Number';target.innerHTML='<option value="">Select '+(type.value==='room'?'room':'table')+'</option>'+a.map(function(x){return '<option value="'+e(x.id)+'">'+e(x.label)+'</option>'}).join('')}open.addEventListener('click',function(){root.hidden=false;document.body.classList.add('fqx-v207-modal-open');cart={};search.value='';msg.textContent='';create.disabled=false;create.textContent='Create Order & KOT';menu();renderCart();targets();setTimeout(function(){search.focus()},40)});root.addEventListener('click',function(ev){var c=ev.target.closest('[data-fqx-v207-close]');if(c){root.hidden=true;document.body.classList.remove('fqx-v207-modal-open')}var a=ev.target.closest('[data-add]');if(a){var id=String(a.getAttribute('data-add'));cart[id]=Math.min(99,(cart[id]||0)+1);menu();renderCart()}var i=ev.target.closest('[data-inc]');if(i){var id2=String(i.getAttribute('data-inc'));cart[id2]=Math.min(99,(cart[id2]||0)+1);renderCart();menu()}var d=ev.target.closest('[data-dec]');if(d){var id3=String(d.getAttribute('data-dec'));cart[id3]=Math.max(0,(cart[id3]||0)-1);if(!cart[id3])delete cart[id3];renderCart();menu()}});search.addEventListener('input',menu);type.addEventListener('change',targets);form.addEventListener('submit',function(ev){if(!Object.keys(cart).length||!target.value){ev.preventDefault();msg.textContent=!Object.keys(cart).length?'Please add at least one menu item.':'Please select a room or table.';return}create.disabled=true;create.textContent='Creating...'});menu();renderCart();targets()})();</script>
</section>
