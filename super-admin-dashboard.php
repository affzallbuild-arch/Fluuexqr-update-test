<?php
if (!defined('ABSPATH')) { exit; }
menuqr_require_role(['super_admin']);
global $wpdb;

$tab = sanitize_key(wp_unslash($_GET['tab'] ?? 'dashboard'));
$allowed_tabs = ['dashboard','restaurants','plans','payment-gateway','payments','renewals','expired','analytics','orders','settings'];
if (!in_array($tab, $allowed_tabs, true)) {
    $tab = 'dashboard';
}

$restaurants_table = menuqr_table('restaurants');
$plans_table = menuqr_table('subscription_plans');
$subscriptions_table = menuqr_table('subscriptions');
$payments_table = menuqr_table('subscription_payments');
$orders_table = menuqr_table('orders');
$users_table = $wpdb->users;

$restaurants = $wpdb->get_results("SELECT * FROM {$restaurants_table} ORDER BY id DESC");
$plans = $wpdb->get_results("SELECT * FROM {$plans_table} WHERE slug IN ('free_trial','starter_5_table','restaurant_all_access','hotel_restaurant_full_access') ORDER BY COALESCE(sort_order,999), price ASC");
$subscriptions = $wpdb->get_results("SELECT s.*, r.name AS restaurant_name, p.name AS plan_name FROM {$subscriptions_table} s LEFT JOIN {$restaurants_table} r ON r.id = s.restaurant_id LEFT JOIN {$plans_table} p ON p.id = s.plan_id ORDER BY s.id DESC");
$current_subscriptions = [];
foreach ($subscriptions as $subscription_row) {
    $rid = (int) $subscription_row->restaurant_id;
    if ($rid > 0 && !isset($current_subscriptions[$rid])) {
        $current_subscriptions[$rid] = $subscription_row;
    }
}
$payments = $wpdb->get_results("SELECT sp.*, r.name AS restaurant_name, p.name AS plan_name FROM {$payments_table} sp LEFT JOIN {$restaurants_table} r ON r.id = sp.restaurant_id LEFT JOIN {$plans_table} p ON p.id = sp.plan_id ORDER BY sp.id DESC");
$orders = $wpdb->get_results("SELECT o.*, r.name AS restaurant_name, t.table_number FROM {$orders_table} o LEFT JOIN {$restaurants_table} r ON r.id = o.restaurant_id LEFT JOIN " . menuqr_table('tables') . " t ON t.id = o.table_id ORDER BY o.id DESC LIMIT 100");
$order_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$orders_table}");
$revenue = (float) $wpdb->get_var("SELECT COALESCE(SUM(final_total),0) FROM {$orders_table}");
$active_restaurants = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$restaurants_table} WHERE status = 'active'");
$pending_payments = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$payments_table} WHERE status = 'pending'");
$platform = menuqr_platform_settings();
$platform_pay = function_exists('fqx_get_platform_payment_settings') ? fqx_get_platform_payment_settings() : [];
$pending_restaurants = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$restaurants_table} WHERE approval_status = 'pending'");
$suspended_restaurants = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$restaurants_table} WHERE status = 'suspended'");
$total_bills = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . menuqr_table('bills'));
$total_reviews = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . menuqr_table('review_clicks'));
$subscription_revenue = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$payments_table} WHERE status = 'approved'");
$edit_restaurant_id = absint($_GET['edit_restaurant'] ?? 0);
$edit_restaurant = $edit_restaurant_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$restaurants_table} WHERE id = %d", $edit_restaurant_id)) : null;
$edit_plan_id = absint($_GET['edit_plan'] ?? 0);
$edit_plan = $edit_plan_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$plans_table} WHERE id = %d", $edit_plan_id)) : null;
$edit_subscription = $edit_restaurant ? ($current_subscriptions[(int) $edit_restaurant->id] ?? null) : null;
$current_user = wp_get_current_user();
?>
<section class="app-shell dashboard-shell fqx-v115-super">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <?php if (function_exists('fqx_brand_logo_img')) { echo fqx_brand_logo_img('main', 'fq-dashboard-logo-img fq-dashboard-logo-full', 'FluuexQR Hotel & Restaurant Automation', 'eager'); } else { $fq_dash_logo = function_exists('menuqr_get_brand_logo_url') ? menuqr_get_brand_logo_url() : ''; if ($fq_dash_logo) { echo '<img class="fq-dashboard-logo-img fq-dashboard-logo-full" src="' . esc_url($fq_dash_logo) . '" alt="FluuexQR" loading="eager" decoding="async">'; } } ?>
            <div class="sidebar-role">Super Admin</div>
            <div class="sidebar-rest">Platform Control</div>
        </div>
        <div class="sidebar-nav">
            <div class="nav-section">Platform</div>
            <a class="nav-item <?php echo $tab === 'dashboard' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('dashboard')); ?>">📊 Dashboard</a>
            <a class="nav-item <?php echo $tab === 'restaurants' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('restaurants')); ?>">🏪 Restaurants</a>
            <a class="nav-item <?php echo $tab === 'plans' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('plans')); ?>">💳 Subscription Plans</a>
            <a class="nav-item <?php echo $tab === 'payment-gateway' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('payment-gateway')); ?>">🏦 Plan Payment Gateway</a>
            <a class="nav-item <?php echo $tab === 'payments' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('payments')); ?>">🧾 Payment Verification</a>
            <a class="nav-item <?php echo $tab === 'renewals' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('renewals')); ?>">🔔 Renewals</a>
            <a class="nav-item <?php echo $tab === 'expired' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('expired')); ?>">⛔ Expired Accounts</a>
            <a class="nav-item <?php echo $tab === 'analytics' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('analytics')); ?>">📈 Plan Analytics</a>
            <a class="nav-item <?php echo $tab === 'orders' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('orders')); ?>">📋 Orders</a>
            <a class="nav-item <?php echo $tab === 'settings' ? 'active' : ''; ?>" href="<?php echo esc_url(menuqr_admin_tab_url('settings')); ?>">⚙️ Settings</a>
        </div>
        <div class="sidebar-footer"><a class="btn-logout" href="<?php echo esc_url(wp_logout_url(menuqr_get_page_url_by_slug('login'))); ?>">← Logout</a></div>
    </aside>
    <div class="mq-sidebar-overlay" aria-hidden="true"></div>
    <div class="main-content">
        <div class="topbar">
            <button class="mq-sidebar-toggle" type="button" aria-label="Toggle menu">☰</button>
            <div class="topbar-title">Super Admin Dashboard</div>
            <div class="topbar-right">
                <span class="topbar-name"><?php echo esc_html($current_user->display_name ?: $current_user->user_email); ?></span>
                <div class="topbar-avatar"><?php echo esc_html(strtoupper(substr($current_user->display_name ?: 'A', 0, 1))); ?></div>
            </div>
        </div>
        <div class="page-body">
            <div class="stat-grid">
                <div class="card"><div class="card-title">Restaurants</div><div class="card-value"><?php echo esc_html((string) count($restaurants)); ?></div><div class="card-sub"><?php echo esc_html((string) $active_restaurants); ?> active</div></div>
                <div class="card"><div class="card-title">Pending Restaurants</div><div class="card-value"><?php echo esc_html((string) $pending_restaurants); ?></div><div class="card-sub">Waiting approval</div></div>
                <div class="card"><div class="card-title">Suspended Restaurants</div><div class="card-value"><?php echo esc_html((string) $suspended_restaurants); ?></div><div class="card-sub">Need attention</div></div>
                <div class="card"><div class="card-title">Orders</div><div class="card-value"><?php echo esc_html((string) $order_count); ?></div><div class="card-sub">Platform total</div></div>
                <div class="card"><div class="card-title">GMV</div><div class="card-value"><?php echo esc_html(menuqr_money($revenue)); ?></div><div class="card-sub">All restaurants</div></div>
                <div class="card"><div class="card-title">Pending Payments</div><div class="card-value"><?php echo esc_html((string) $pending_payments); ?></div><div class="card-sub">Needs review</div></div>
                <div class="card"><div class="card-title">Subscription Revenue</div><div class="card-value"><?php echo esc_html(menuqr_money($subscription_revenue)); ?></div><div class="card-sub">Approved payments</div></div>
                <div class="card"><div class="card-title">Bills / Reviews</div><div class="card-value"><?php echo esc_html((string) $total_bills); ?></div><div class="card-sub"><?php echo esc_html((string) $total_reviews); ?> review clicks</div></div>
            </div>

            <?php if ($tab === 'dashboard') : ?>
                <div class="fqx-v185-admin-guide">
                    <div class="fqx-v185-guide-head">
                        <span>Quick Setup Guide</span>
                        <h2>Super Admin me kya kaha manage karna hai?</h2>
                        <p>Yahan se platform owner restaurants, plans, subscription payments, expiry aur platform payment gateway ko simple steps me manage karega.</p>
                    </div>
                    <div class="fqx-v185-guide-grid">
                        <a href="<?php echo esc_url(menuqr_admin_tab_url('restaurants')); ?>"><b>1</b><strong>Restaurants Access</strong><small>Restaurant approve/suspend, plan assign, expiry update.</small></a>
                        <a href="<?php echo esc_url(menuqr_admin_tab_url('plans')); ?>"><b>2</b><strong>Subscription Plans</strong><small>Plan price, features, limits aur trial manage.</small></a>
                        <a href="<?php echo esc_url(menuqr_admin_tab_url('payment-gateway')); ?>"><b>3</b><strong>Platform Payment Gateway</strong><small>Plan buy/renew ka UPI, Razorpay, Stripe, Bank setup.</small></a>
                        <a href="<?php echo esc_url(menuqr_admin_tab_url('payments')); ?>"><b>4</b><strong>Payment Verification</strong><small>UPI/Bank proof approve/reject karke subscription active.</small></a>
                    </div>
                </div>
                <div class="chart-grid">
                    <div class="chart-card">
                        <div class="chart-title">Platform Overview</div>
                        <div class="sum-row"><span>Total Restaurants</span><span><?php echo esc_html((string) count($restaurants)); ?></span></div>
                        <div class="sum-row"><span>Approved Restaurants</span><span><?php echo esc_html((string) count(array_filter($restaurants, static fn($r) => $r->approval_status === 'approved' || $r->approval_status === 'demo'))); ?></span></div>
                        <div class="sum-row"><span>Active Subscriptions</span><span><?php echo esc_html((string) count(array_filter($subscriptions, static fn($s) => $s->status === 'active' || $s->status === 'trial'))); ?></span></div>
                        <div class="sum-row"><span>Pending Subscription Payments</span><span><?php echo esc_html((string) $pending_payments); ?></span></div>
                        <div class="sum-row"><span>Support Email</span><span><?php echo esc_html($platform['support_email']); ?></span></div>
                        <div class="sum-row"><span>Total Bills</span><span><?php echo esc_html((string) $total_bills); ?></span></div>
                        <div class="sum-row"><span>Total Review Clicks</span><span><?php echo esc_html((string) $total_reviews); ?></span></div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-title">Recent Restaurants</div>
                        <div class="table-wrap"><div class="table-scroll"><table class="data-table">
                            <thead><tr><th>Name</th><th>Approval</th><th>Status</th><th>Subscription</th></tr></thead>
                            <tbody>
                            <?php foreach (array_slice($restaurants, 0, 8) as $restaurant) : ?>
                                <tr>
                                    <td><?php echo esc_html($restaurant->name); ?></td>
                                    <td><?php echo esc_html(ucfirst($restaurant->approval_status)); ?></td>
                                    <td><?php echo esc_html(ucfirst($restaurant->status)); ?></td>
                                    <td><?php echo esc_html(ucfirst($restaurant->subscription_status)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div></div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title">Admin Quick Links</div>
                    <div class="mq-feature-matrix"><div class="is-on"><span>✅</span>Manage QR templates</div><div class="is-on"><span>✅</span>Homepage content</div><div class="is-on"><span>✅</span>Pricing plans</div><div class="is-on"><span>✅</span>Platform payment settings</div><div class="is-on"><span>✅</span>Blog quick links</div><div class="is-on"><span>✅</span>CSV exports</div></div>
                </div>
                <div class="section-card">
                    <div class="section-title">Recent Orders</div>
                    <div class="table-wrap"><div class="table-scroll"><table class="data-table">
                        <thead><tr><th>Order</th><th>Restaurant</th><th>Table</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($orders, 0, 15) as $order) : ?>
                            <tr>
                                <td><?php echo esc_html($order->unique_code); ?></td>
                                <td><?php echo esc_html($order->restaurant_name); ?></td>
                                <td><?php echo esc_html($order->table_number ?: '-'); ?></td>
                                <td><?php echo esc_html(menuqr_money((float) $order->final_total)); ?></td>
                                <td><span class="badge badge-<?php echo esc_attr($order->order_status); ?>"><?php echo esc_html($order->order_status); ?></span></td>
                                <td><?php echo esc_html(mysql2date('d M Y H:i', $order->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div></div>
                </div>
            <?php elseif ($tab === 'restaurants') : ?>
                <div class="chart-grid">
                    <div class="chart-card">
                        <div class="chart-title"><?php echo $edit_restaurant ? 'Edit Restaurant' : 'Add Restaurant'; ?></div>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('menuqr_save_restaurant_admin', 'menuqr_restaurant_nonce'); ?>
                            <input type="hidden" name="action" value="menuqr_save_restaurant_admin">
                            <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) ($edit_restaurant->id ?? 0)); ?>">
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Restaurant Name</label><input class="form-input" name="name" value="<?php echo esc_attr($edit_restaurant->name ?? ''); ?>" required></div>
                                <div class="form-group"><label class="form-label">Owner Name</label><input class="form-input" name="owner_name" value="<?php echo esc_attr($edit_restaurant->owner_name ?? ''); ?>" required></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" value="<?php echo esc_attr($edit_restaurant->email ?? ''); ?>" required></div>
                                <div class="form-group"><label class="form-label">Phone</label><input class="form-input" name="phone" value="<?php echo esc_attr($edit_restaurant->phone ?? ''); ?>"></div>
                            </div>
                            <div class="form-group"><label class="form-label">Address</label><textarea class="form-textarea" name="address"><?php echo esc_textarea($edit_restaurant->address ?? ''); ?></textarea></div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Approval</label><select class="form-select" name="approval_status"><?php foreach (['pending','approved','rejected','demo'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($edit_restaurant->approval_status ?? 'pending', $status); ?>><?php echo esc_html(ucfirst($status)); ?></option><?php endforeach; ?></select></div>
                                <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (['active','suspended','inactive'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($edit_restaurant->status ?? 'active', $status); ?>><?php echo esc_html(ucfirst($status)); ?></option><?php endforeach; ?></select></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Subscription Status</label><select class="form-select" name="subscription_status"><?php foreach (['active','pending','inactive','expired','trial'] as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($edit_restaurant->subscription_status ?? 'inactive', $status); ?>><?php echo esc_html(ucfirst($status)); ?></option><?php endforeach; ?></select></div>
                                <div class="form-group"><label class="form-label"><?php echo $edit_restaurant ? 'Reset Password (optional)' : 'Password'; ?></label><input class="form-input" type="text" name="password"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Plan</label><select class="form-select" name="plan_id"><option value="0">Auto</option><?php foreach ($plans as $plan) : ?><option value="<?php echo esc_attr((string) $plan->id); ?>" <?php selected((int) ($edit_subscription->plan_id ?? 0), (int) $plan->id); ?>><?php echo esc_html($plan->name . ' - ' . menuqr_money((float) $plan->price)); ?></option><?php endforeach; ?></select></div>
                                <div class="form-group"><label class="form-label">Expiry Date</label><input class="form-input" type="datetime-local" name="expires_at" value="<?php echo esc_attr(!empty($edit_subscription->expires_at) ? gmdate('Y-m-d\TH:i', strtotime((string) $edit_subscription->expires_at)) : ''); ?>"></div>
                            </div>
                            <button class="btn btn-primary" type="submit"><?php echo $edit_restaurant ? 'Update Restaurant' : 'Create Restaurant'; ?></button>
                            <?php if ($edit_restaurant) : ?><a class="btn btn-outline" href="<?php echo esc_url(menuqr_admin_tab_url('restaurants')); ?>">Cancel</a><?php endif; ?>
                        </form>
                    </div>
                    <div class="chart-card">
                        <div class="chart-title">Restaurant Stats</div>
                        <div class="sum-row"><span>Approved</span><span><?php echo esc_html((string) count(array_filter($restaurants, static fn($r) => $r->approval_status === 'approved' || $r->approval_status === 'demo'))); ?></span></div>
                        <div class="sum-row"><span>Pending</span><span><?php echo esc_html((string) count(array_filter($restaurants, static fn($r) => $r->approval_status === 'pending'))); ?></span></div>
                        <div class="sum-row"><span>Suspended</span><span><?php echo esc_html((string) count(array_filter($restaurants, static fn($r) => $r->status === 'suspended'))); ?></span></div>
                        <div class="sum-row"><span>Active Subscription</span><span><?php echo esc_html((string) count(array_filter($restaurants, static fn($r) => $r->subscription_status === 'active' || $r->subscription_status === 'trial'))); ?></span></div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title">Manage Restaurants</div>
                    <div class="table-wrap"><div class="table-scroll"><table class="data-table">
                        <thead><tr><th>ID</th><th>Name</th><th>Owner</th><th>Email</th><th>Approval</th><th>Status</th><th>Subscription</th><th>Plan / Expiry</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($restaurants as $restaurant) : ?>
                            <tr>
                                <td><?php echo esc_html((string) $restaurant->id); ?></td>
                                <td><?php echo esc_html($restaurant->name); ?></td>
                                <td><?php echo esc_html($restaurant->owner_name); ?></td>
                                <td><?php echo esc_html($restaurant->email); ?></td>
                                <td><?php echo esc_html($restaurant->approval_status); ?></td>
                                <td><?php echo esc_html($restaurant->status); ?></td>
                                <td><?php echo esc_html($restaurant->subscription_status); ?></td>
                                <td>
                                    <?php $row_subscription = $current_subscriptions[(int) $restaurant->id] ?? null; ?>
                                    <div class="fs-sm"><?php echo esc_html($row_subscription->plan_name ?? '-'); ?></div>
                                    <div class="text-muted fs-sm"><?php echo esc_html(!empty($row_subscription->expires_at) ? mysql2date('d M Y H:i', $row_subscription->expires_at) : '-'); ?></div>
                                </td>
                                <td>
                                    <div class="inline-actions" style="flex-wrap:wrap;">
                                        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(add_query_arg(['tab' => 'restaurants', 'edit_restaurant' => (int) $restaurant->id], menuqr_get_page_url_by_slug('super-admin-dashboard'))); ?>">Edit</a>
                                        <a class="btn btn-outline btn-sm" href="<?php echo esc_url(add_query_arg(['restaurant_id' => (int) $restaurant->id], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>">Open</a>
                                    </div>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="inline-actions fqx-v192-approval-actions" style="margin-top:8px;flex-wrap:wrap;">
                                        <?php wp_nonce_field('menuqr_admin_action', 'menuqr_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="menuqr_restaurant_approval">
                                        <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant->id); ?>">
                                        <select class="form-select" name="approval_status" title="Approval status">
                                            <?php foreach (['pending','approved','rejected','demo'] as $approval) : ?>
                                                <option value="<?php echo esc_attr($approval); ?>" <?php selected($restaurant->approval_status, $approval); ?>><?php echo esc_html(ucfirst($approval)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                        <button class="btn btn-success btn-sm" type="submit" name="approval_status" value="approved">Approve</button>
                                        <button class="btn btn-danger btn-sm" type="submit" name="approval_status" value="rejected" onclick="return confirm('Reject this restaurant access request?');">Reject</button>
                                    </form>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="inline-actions" style="margin-top:8px;flex-wrap:wrap;">
                                        <?php wp_nonce_field('menuqr_admin_action', 'menuqr_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="menuqr_update_restaurant_subscription">
                                        <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant->id); ?>">
                                        <select class="form-select" name="subscription_status">
                                            <?php foreach (['active','pending','inactive','expired','trial'] as $sub_status) : ?>
                                                <option value="<?php echo esc_attr($sub_status); ?>" <?php selected($restaurant->subscription_status, $sub_status); ?>><?php echo esc_html(ucfirst($sub_status)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select class="form-select" name="plan_id">
                                            <option value="0">Auto plan</option>
                                            <?php foreach ($plans as $plan) : ?>
                                                <option value="<?php echo esc_attr((string) $plan->id); ?>" <?php selected((int) ($row_subscription->plan_id ?? 0), (int) $plan->id); ?>><?php echo esc_html($plan->name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input class="form-input" type="datetime-local" name="expires_at" value="<?php echo esc_attr(!empty($row_subscription->expires_at) ? gmdate('Y-m-d\TH:i', strtotime((string) $row_subscription->expires_at)) : ''); ?>">
                                        <button class="btn btn-success btn-sm" type="submit">Subscription</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div></div>
                </div>
            <?php elseif ($tab === 'plans') : ?>
                <div class="chart-grid">
                    <div class="chart-card">
                        <div class="chart-title"><?php echo $edit_plan ? 'Edit Subscription Plan' : 'Add Subscription Plan'; ?></div>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('menuqr_save_plan', 'menuqr_plan_nonce'); ?>
                            <input type="hidden" name="action" value="menuqr_save_plan">
                            <input type="hidden" name="plan_id" value="<?php echo esc_attr((string) ($edit_plan->id ?? 0)); ?>">
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Name</label><input class="form-input" name="name" value="<?php echo esc_attr($edit_plan->name ?? ''); ?>" required></div>
                                <div class="form-group"><label class="form-label">Slug</label><input class="form-input" name="slug" value="<?php echo esc_attr($edit_plan->slug ?? ''); ?>" required></div>
                            </div>
                            <?php $edit_cfg = $edit_plan ? menuqr_get_plan_config_from_plan($edit_plan) : ['features'=>[], 'limits'=>[]]; ?>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Monthly Price</label><input class="form-input" type="number" step="0.01" name="monthly_price" value="<?php echo esc_attr((string) ($edit_plan->monthly_price ?? $edit_plan->price ?? '0')); ?>" required></div>
                                <div class="form-group"><label class="form-label">Yearly Price</label><input class="form-input" type="number" step="0.01" name="yearly_price" value="<?php echo esc_attr((string) ($edit_plan->yearly_price ?? '0')); ?>"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Duration Days</label><input class="form-input" type="number" name="duration_days" value="<?php echo esc_attr((string) ($edit_plan->duration_days ?? $edit_plan->billing_days ?? '30')); ?>" required></div>
                                <div class="form-group"><label class="form-label">Trial Days</label><input class="form-input" type="number" name="trial_days" value="<?php echo esc_attr((string) ($edit_plan->trial_days ?? '0')); ?>"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Setup Fee</label><input class="form-input" type="number" step="0.01" name="setup_fee" value="<?php echo esc_attr((string) ($edit_plan->setup_fee ?? '0')); ?>"></div>
                                <div class="form-group"><label class="form-label">Plan Type</label><select class="form-select" name="plan_type"><?php foreach (['trial','restaurant','hotel','enterprise'] as $pt) : ?><option value="<?php echo esc_attr($pt); ?>" <?php selected($edit_plan->plan_type ?? 'restaurant', $pt); ?>><?php echo esc_html(ucfirst($pt)); ?></option><?php endforeach; ?></select></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Sort Order</label><input class="form-input" type="number" name="sort_order" value="<?php echo esc_attr((string) ($edit_plan->sort_order ?? '0')); ?>"></div>
                                <div class="form-group"><label class="form-label">Plan Color</label><input class="form-input" type="color" name="color" value="<?php echo esc_attr($edit_plan->color ?? '#ff7a18'); ?>"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Button Text</label><input class="form-input" name="button_text" value="<?php echo esc_attr($edit_plan->button_text ?? 'Choose Plan'); ?>"></div>
                                <label class="form-check"><input type="checkbox" name="is_recommended" <?php checked((int) ($edit_plan->is_recommended ?? 0), 1); ?>> Recommended badge</label>
                            </div>
                            <div class="form-group"><label class="form-label">Description</label><textarea class="form-textarea" name="description"><?php echo esc_textarea($edit_plan->description ?? ''); ?></textarea></div>
                            <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?php selected($edit_plan->status ?? 'active', 'active'); ?>>Active</option><option value="hidden" <?php selected($edit_plan->status ?? 'active', 'hidden'); ?>>Hidden</option><option value="draft" <?php selected($edit_plan->status ?? 'active', 'draft'); ?>>Draft</option></select></div>
                            <div class="section-title" style="margin-top:12px;">Feature Control</div>
                            <input class="form-input" data-fqx-feature-search placeholder="Search feature toggle..." style="margin-bottom:10px;">
                            <div class="fqx-feature-toggle-grid">
                                <?php foreach (function_exists('fqx_all_feature_keys') ? fqx_all_feature_keys() : [] as $fk) : ?>
                                    <label class="form-check fqx-feature-check"><input type="checkbox" name="feature_<?php echo esc_attr($fk); ?>" <?php checked(!empty($edit_cfg['features'][$fk]) || !$edit_plan, true); ?>> <?php echo esc_html(ucwords(str_replace('_',' ', $fk))); ?></label>
                                <?php endforeach; ?>
                            </div>
                            <div class="section-title" style="margin-top:12px;">Usage Limits (-1 = Unlimited)</div>
                            <div class="form-row">
                                <?php foreach (function_exists('fqx_all_limit_keys') ? fqx_all_limit_keys() : [] as $lk) : ?>
                                    <div class="form-group"><label class="form-label"><?php echo esc_html(ucwords(str_replace('_',' ', $lk))); ?></label><input class="form-input" type="number" name="limit_<?php echo esc_attr($lk); ?>" value="<?php echo esc_attr((string) ($edit_cfg['limits'][$lk] ?? -1)); ?>"></div>
                                <?php endforeach; ?>
                            </div>
                            <button class="btn btn-primary" type="submit"><?php echo $edit_plan ? 'Update Plan' : 'Save Plan'; ?></button>
                            <?php if ($edit_plan) : ?><a class="btn btn-outline" href="<?php echo esc_url(menuqr_admin_tab_url('plans')); ?>">Cancel</a><?php endif; ?>
                        </form>
                    </div>
                    <div class="chart-card">
                        <div class="chart-title">Plans</div>
                        <div class="table-wrap"><div class="table-scroll"><table class="data-table">
                            <thead><tr><th>Name</th><th>Price</th><th>Days</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($plans as $plan) : ?>
                                <tr>
                                    <td><?php echo esc_html($plan->name); ?></td>
                                    <td><?php echo esc_html(menuqr_money((float) $plan->price)); ?></td>
                                    <td><?php echo esc_html((string) $plan->billing_days); ?></td>
                                    <td><?php echo esc_html($plan->status); ?></td>
                                    <td>
                                        <div class="fqx-actions-row">
                                            <a class="btn btn-outline btn-sm" href="<?php echo esc_url(add_query_arg(['tab' => 'plans', 'edit_plan' => (int) $plan->id], menuqr_get_page_url_by_slug('super-admin-dashboard'))); ?>">Edit</a>
                                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Delete this plan permanently? Plans linked with subscriptions will be blocked for safety.');">
                                                <?php wp_nonce_field('menuqr_delete_plan_nonce', 'menuqr_delete_plan'); ?>
                                                <input type="hidden" name="action" value="menuqr_delete_plan">
                                                <input type="hidden" name="plan_id" value="<?php echo esc_attr((string) $plan->id); ?>">
                                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div></div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title">Subscriptions</div>
                    <div class="table-wrap"><div class="table-scroll"><table class="data-table">
                        <thead><tr><th>Restaurant</th><th>Plan</th><th>Status</th><th>Payment</th><th>Starts</th><th>Expires</th></tr></thead>
                        <tbody>
                        <?php foreach ($subscriptions as $subscription) : ?>
                            <tr>
                                <td><?php echo esc_html($subscription->restaurant_name); ?></td>
                                <td><?php echo esc_html($subscription->plan_name); ?></td>
                                <td><?php echo esc_html($subscription->status); ?></td>
                                <td><?php echo esc_html($subscription->payment_status); ?></td>
                                <td><?php echo esc_html(mysql2date('d M Y', $subscription->starts_at)); ?></td>
                                <td><?php echo esc_html(mysql2date('d M Y', $subscription->expires_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div></div>
                </div>
            <?php elseif ($tab === 'payment-gateway') : ?>
                <?php
                    $pg = function_exists('fqx_get_platform_payment_settings') ? fqx_get_platform_payment_settings() : [];
                    $gateway_count = function_exists('fqx_v185_gateway_enabled_count') ? fqx_v185_gateway_enabled_count($pg) : 0;
                    $approved_subscription_revenue = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$payments_table} WHERE status IN ('approved','verified','paid')");
                    $pending_subscription_revenue = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$payments_table} WHERE status = 'pending'");
                    $failed_subscription_payments = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$payments_table} WHERE status IN ('failed','rejected')");
                    $pg_saved = !empty($_GET['gateway_saved']);
                ?>
                <div class="fqx-v185-page-head">
                    <div>
                        <span class="fqx-kicker">Platform Subscription Checkout</span>
                        <h2>Plan Payment Gateway Settings</h2>
                        <p>Restaurant owner jab plan buy, renew ya upgrade karega to payment yahi Super Admin gateway settings se chalega. Restaurant order payment settings alag rahenge.</p>
                    </div>
                    <a class="btn btn-outline" href="<?php echo esc_url(menuqr_admin_tab_url('payments')); ?>">View Payment Verification</a>
                </div>
                <?php if ($pg_saved) : ?><div class="alert alert-success">Platform subscription payment gateway settings saved successfully.</div><?php endif; ?>
                <div class="fqx-v185-kpi-grid">
                    <div class="fqx-v185-kpi"><span>Active Gateways</span><strong><?php echo esc_html((string) $gateway_count); ?>/4</strong><small>UPI, Razorpay, Stripe, Bank</small></div>
                    <div class="fqx-v185-kpi"><span>Approved Revenue</span><strong><?php echo esc_html(menuqr_money($approved_subscription_revenue)); ?></strong><small>Subscription payments</small></div>
                    <div class="fqx-v185-kpi"><span>Pending Verification</span><strong><?php echo esc_html((string) $pending_payments); ?></strong><small><?php echo esc_html(menuqr_money($pending_subscription_revenue)); ?> pending</small></div>
                    <div class="fqx-v185-kpi"><span>Failed / Rejected</span><strong><?php echo esc_html((string) $failed_subscription_payments); ?></strong><small>Needs review</small></div>
                </div>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fqx-v185-gateway-form">
                    <?php wp_nonce_field('fqx_v185_save_platform_gateway', 'fqx_v185_gateway_nonce'); ?>
                    <input type="hidden" name="action" value="fqx_v185_save_platform_gateway">
                    <div class="fqx-v185-gateway-layout">
                        <div class="fqx-v185-gateway-main">
                            <div class="fqx-v185-card gateway-card">
                                <div class="fqx-v185-card-head"><div><h3>UPI Manual Payment</h3><p>Restaurant owner ko platform UPI QR/ID show hoga. Proof upload ke baad Super Admin approve karega.</p></div><label class="fqx-v185-switch"><input type="checkbox" name="platform_upi_enabled" value="1" <?php checked((int) ($pg['platform_upi_enabled'] ?? 1), 1); ?>><span></span></label></div>
                                <div class="fqx-v185-form-grid">
                                    <label>Platform UPI ID<input class="form-input" name="platform_upi_id" value="<?php echo esc_attr($pg['platform_upi_id'] ?? ''); ?>" placeholder="fluuexqr@upi"></label>
                                    <label>Merchant Name<input class="form-input" name="upi_merchant_name" value="<?php echo esc_attr($pg['upi_merchant_name'] ?? 'FluuexQR'); ?>"></label>
                                    <label>UPI QR Image URL<input class="form-input" name="platform_upi_qr" value="<?php echo esc_attr($pg['platform_upi_qr'] ?? ''); ?>" placeholder="https://..."></label>
                                    <label>Upload UPI QR<input class="form-input" type="file" name="platform_upi_qr_file" accept="image/png,image/jpeg,image/webp"></label>
                                </div>
                                <div class="fqx-v185-upi-preview">
                                    <div class="qr-box"><?php if (!empty($pg['platform_upi_qr'])) : ?><img src="<?php echo esc_url($pg['platform_upi_qr']); ?>" alt="Platform UPI QR" loading="lazy"><?php else : ?><span>QR</span><?php endif; ?></div>
                                    <label>Checkout Instructions<textarea class="form-input" name="platform_payment_instructions" rows="4"><?php echo esc_textarea($pg['platform_payment_instructions'] ?? 'Pay to FluuexQR and upload UTR/screenshot for verification.'); ?></textarea></label>
                                </div>
                            </div>

                            <div class="fqx-v185-card gateway-card">
                                <div class="fqx-v185-card-head"><div><h3>Razorpay Subscription Gateway</h3><p>Online plan payment success hone par subscription auto active ho sakta hai.</p></div><label class="fqx-v185-switch"><input type="checkbox" name="razorpay_enabled" value="1" <?php checked((int) ($pg['razorpay_enabled'] ?? 0), 1); ?>><span></span></label></div>
                                <div class="fqx-v185-form-grid">
                                    <label>Key ID<input class="form-input" name="razorpay_key_id" value="<?php echo esc_attr($pg['razorpay_key_id'] ?? ''); ?>" placeholder="rzp_live_xxxxx"></label>
                                    <label>Key Secret<input class="form-input" type="password" name="razorpay_key_secret" placeholder="<?php echo esc_attr(function_exists('fqx_v185_mask_secret') ? fqx_v185_mask_secret($pg['razorpay_key_secret'] ?? '') : 'Leave blank to keep existing'); ?>"></label>
                                    <label>Webhook Secret<input class="form-input" type="password" name="razorpay_webhook_secret" placeholder="<?php echo esc_attr(function_exists('fqx_v185_mask_secret') ? fqx_v185_mask_secret($pg['razorpay_webhook_secret'] ?? '') : 'Leave blank to keep existing'); ?>"></label>
                                    <label>Mode<select class="form-select" name="razorpay_mode"><option value="test" <?php selected($pg['razorpay_mode'] ?? 'test', 'test'); ?>>Test Mode</option><option value="live" <?php selected($pg['razorpay_mode'] ?? 'test', 'live'); ?>>Live Mode</option></select></label>
                                </div>
                            </div>

                            <div class="fqx-v185-card gateway-card">
                                <div class="fqx-v185-card-head"><div><h3>Stripe Subscription Gateway</h3><p>Card/international subscription payment ke liye Stripe settings.</p></div><label class="fqx-v185-switch"><input type="checkbox" name="stripe_enabled" value="1" <?php checked((int) ($pg['stripe_enabled'] ?? 0), 1); ?>><span></span></label></div>
                                <div class="fqx-v185-form-grid">
                                    <label>Publishable Key<input class="form-input" name="stripe_publishable_key" value="<?php echo esc_attr($pg['stripe_publishable_key'] ?? ''); ?>" placeholder="pk_live_xxxxx"></label>
                                    <label>Secret Key<input class="form-input" type="password" name="stripe_secret_key" placeholder="<?php echo esc_attr(function_exists('fqx_v185_mask_secret') ? fqx_v185_mask_secret($pg['stripe_secret_key'] ?? '') : 'Leave blank to keep existing'); ?>"></label>
                                    <label>Webhook Secret<input class="form-input" type="password" name="stripe_webhook_secret" placeholder="<?php echo esc_attr(function_exists('fqx_v185_mask_secret') ? fqx_v185_mask_secret($pg['stripe_webhook_secret'] ?? '') : 'Leave blank to keep existing'); ?>"></label>
                                    <label>Mode<select class="form-select" name="stripe_mode"><option value="test" <?php selected($pg['stripe_mode'] ?? 'test', 'test'); ?>>Test Mode</option><option value="live" <?php selected($pg['stripe_mode'] ?? 'test', 'live'); ?>>Live Mode</option></select></label>
                                </div>
                            </div>

                            <div class="fqx-v185-card gateway-card">
                                <div class="fqx-v185-card-head"><div><h3>Bank Transfer</h3><p>High value plans/manual settlement ke liye platform bank details.</p></div><label class="fqx-v185-switch"><input type="checkbox" name="bank_transfer_enabled" value="1" <?php checked((int) ($pg['bank_transfer_enabled'] ?? 1), 1); ?>><span></span></label></div>
                                <div class="fqx-v185-form-grid">
                                    <label>Account Name<input class="form-input" name="bank_account_name" value="<?php echo esc_attr($pg['bank_account_name'] ?? ''); ?>"></label>
                                    <label>Account Number<input class="form-input" name="bank_account_number" value="<?php echo esc_attr($pg['bank_account_number'] ?? ''); ?>"></label>
                                    <label>IFSC Code<input class="form-input" name="bank_ifsc" value="<?php echo esc_attr($pg['bank_ifsc'] ?? ''); ?>"></label>
                                    <label>Bank Name<input class="form-input" name="bank_name" value="<?php echo esc_attr($pg['bank_name'] ?? ''); ?>"></label>
                                    <label>Branch<input class="form-input" name="bank_branch" value="<?php echo esc_attr($pg['bank_branch'] ?? ''); ?>"></label>
                                    <label>Account Type<select class="form-select" name="bank_account_type"><option value="current" <?php selected($pg['bank_account_type'] ?? 'current', 'current'); ?>>Current</option><option value="savings" <?php selected($pg['bank_account_type'] ?? 'current', 'savings'); ?>>Savings</option></select></label>
                                    <label>Beneficiary Name<input class="form-input" name="bank_beneficiary_name" value="<?php echo esc_attr($pg['bank_beneficiary_name'] ?? ''); ?>"></label>
                                    <label>Reminder Days<input class="form-input" name="renewal_reminder_days" value="<?php echo esc_attr($pg['renewal_reminder_days'] ?? '7,3,1,0'); ?>" placeholder="7,3,1,0"></label>
                                </div>
                                <div class="fqx-v185-check-row">
                                    <label><input type="checkbox" name="manual_verification_required" value="1" <?php checked((int) ($pg['manual_verification_required'] ?? 1), 1); ?>> Manual verification required</label>
                                    <label><input type="checkbox" name="upi_autopay_enabled" value="1" <?php checked((int) ($pg['upi_autopay_enabled'] ?? 0), 1); ?>> UPI AutoPay label</label>
                                    <label><input type="checkbox" name="one_click_renewal_enabled" value="1" <?php checked((int) ($pg['one_click_renewal_enabled'] ?? 1), 1); ?>> One-click renewal</label>
                                </div>
                            </div>
                        </div>

                        <aside class="fqx-v185-gateway-side">
                            <div class="fqx-v185-card">
                                <h3>How Subscription Payment Works</h3>
                                <ol class="fqx-v185-steps">
                                    <li><b>Plan Select</b><span>Restaurant owner Subscription page se plan choose karega.</span></li>
                                    <li><b>Checkout</b><span>UPI/Razorpay/Stripe/Bank options Super Admin settings se show honge.</span></li>
                                    <li><b>Payment</b><span>Online success par auto active, UPI/Bank par pending verification.</span></li>
                                    <li><b>Access</b><span>Approve hone ke baad plan, expiry aur features activate honge.</span></li>
                                </ol>
                            </div>
                            <div class="fqx-v185-card">
                                <h3>Gateway Status</h3>
                                <?php foreach (['upi'=>'UPI Manual','razorpay'=>'Razorpay','stripe'=>'Stripe','bank'=>'Bank Transfer'] as $gkey => $glabel) : $status=function_exists('fqx_v185_gateway_status') ? fqx_v185_gateway_status($pg, $gkey) : 'Setup Needed'; ?>
                                    <div class="fqx-v185-status-row"><span><?php echo esc_html($glabel); ?></span><b class="<?php echo $status === 'Active' ? 'is-ok' : 'is-warn'; ?>"><?php echo esc_html($status); ?></b></div>
                                <?php endforeach; ?>
                            </div>
                            <div class="fqx-v185-card">
                                <h3>Pending Payment Proof</h3>
                                <?php $pending_preview = array_slice(array_filter((array) $payments, static fn($p) => ($p->status ?? '') === 'pending'), 0, 4); ?>
                                <?php if ($pending_preview) : foreach ($pending_preview as $pp) : ?>
                                    <div class="fqx-v185-payment-mini"><strong><?php echo esc_html($pp->restaurant_name ?: 'Restaurant'); ?></strong><span><?php echo esc_html($pp->plan_name ?: 'Plan'); ?> · <?php echo esc_html(menuqr_money((float) $pp->amount)); ?></span></div>
                                <?php endforeach; else : ?><p class="text-muted">No pending proof right now.</p><?php endif; ?>
                                <a class="btn btn-primary btn-sm" href="<?php echo esc_url(menuqr_admin_tab_url('payments')); ?>">Open Verification Center</a>
                            </div>
                            <button class="btn btn-primary fqx-v185-save-btn" type="submit">Save Platform Payment Settings</button>
                        </aside>
                    </div>
                </form>

            <?php elseif ($tab === 'payments') : ?>
                <div class="fqx-payment-hero">
                    <div>
                        <span class="fqx-kicker">Manual Verification Center</span>
                        <h2>Restaurant owner UTR & payment proof yahin dikhega</h2>
                        <p>Restaurant Admin jab renew/member banne ke liye UPI ya Bank payment submit karega, uska UTR/reference number aur screenshot proof Super Admin ke isi page par show hoga.</p>
                    </div>
                    <div class="fqx-proof-steps">
                        <b>Flow</b>
                        <span>1. Owner selects plan</span>
                        <span>2. Pays to platform UPI/Bank</span>
                        <span>3. Uploads UTR/screenshot</span>
                        <span>4. Super Admin Approve/Reject</span>
                    </div>
                </div>
                <div class="section-card fqx-card-premium">
                    <div class="fqx-section-head">
                        <div><div class="section-title">Subscription Payments / Manual Verification</div><p>UPI, Bank, Razorpay, Stripe subscription payments with proof preview.</p></div>
                        <a class="btn btn-primary btn-sm" href="<?php echo esc_url(menuqr_admin_tab_url('settings')); ?>">Set Platform UPI / Bank</a>
                    </div>
                    <div class="table-wrap"><div class="table-scroll"><table class="data-table fqx-payments-table">
                        <thead><tr><th>ID</th><th>Restaurant / Plan</th><th>Amount</th><th>Method</th><th>UTR / Reference</th><th>Proof Screenshot</th><th>Status</th><th>Submitted</th><th>Approve / Reject</th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $payment) : $proof_url = function_exists('fqx_get_payment_proof_url') ? fqx_get_payment_proof_url($payment) : (string) ($payment->proof_file ?? $payment->screenshot_url ?? ''); $reference = function_exists('fqx_get_payment_reference') ? fqx_get_payment_reference($payment) : (string) ($payment->utr_number ?? $payment->transaction_reference ?? ''); ?>
                            <tr>
                                <td><b>#<?php echo esc_html((string) $payment->id); ?></b></td>
                                <td><strong><?php echo esc_html($payment->restaurant_name ?: 'Restaurant'); ?></strong><br><span class="text-muted fs-sm"><?php echo esc_html($payment->plan_name ?: 'Selected plan'); ?></span></td>
                                <td><b><?php echo esc_html(menuqr_money((float) $payment->amount)); ?></b></td>
                                <td><span class="fqx-pill"><?php echo esc_html(strtoupper((string) $payment->payment_method)); ?></span></td>
                                <td><?php echo $reference ? '<code>' . esc_html($reference) . '</code>' : '<span class="text-muted">No UTR submitted</span>'; ?></td>
                                <td>
                                    <?php if ($proof_url) : ?>
                                        <a class="fqx-proof-thumb" href="<?php echo esc_url($proof_url); ?>" target="_blank" rel="noopener">
                                            <img src="<?php echo esc_url($proof_url); ?>" alt="Payment proof" loading="lazy">
                                            <span>View Proof</span>
                                        </a>
                                    <?php else : ?>
                                        <span class="text-muted">No screenshot</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="fqx-status fqx-status-<?php echo esc_attr((string) $payment->status); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $payment->status))); ?></span></td>
                                <td><?php echo esc_html(mysql2date('d M Y H:i', $payment->created_at)); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="fqx-payment-action">
                                        <?php wp_nonce_field('menuqr_admin_action', 'menuqr_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="menuqr_verify_subscription_payment">
                                        <input type="hidden" name="payment_id" value="<?php echo esc_attr((string) $payment->id); ?>">
                                        <select class="form-select" name="status">
                                            <option value="pending" <?php selected($payment->status, 'pending'); ?>>Pending</option>
                                            <option value="verified" <?php selected($payment->status, 'verified'); ?>>Approve & Activate</option>
                                            <option value="rejected" <?php selected($payment->status, 'rejected'); ?>>Reject</option>
                                        </select>
                                        <textarea class="form-input" name="admin_note" placeholder="Internal note / rejection reason"><?php echo esc_textarea($payment->admin_note ?? ''); ?></textarea>
                                        <button class="btn btn-primary btn-sm" type="submit">Save Status</button>
                                        <button class="btn btn-success btn-sm" type="submit" name="status" value="verified">Approve & Activate</button>
                                        <button class="btn btn-danger btn-sm" type="submit" name="status" value="rejected" onclick="return confirm('Reject this subscription payment?');">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)) : ?>
                            <tr><td colspan="9"><div class="fqx-empty-state">No subscription payment proof yet. Jab restaurant owner UPI/Bank payment submit karega, proof yahin show hoga.</div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table></div></div>
                </div>
            <?php elseif ($tab === 'orders') : ?>
                <div class="section-card">
                    <div class="section-title">All Platform Orders</div>
                    <div class="table-wrap"><div class="table-scroll"><table class="data-table">
                        <thead><tr><th>Order</th><th>Restaurant</th><th>Table</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead>
                        <tbody>
                        <?php foreach ($orders as $order) : ?>
                            <tr>
                                <td><?php echo esc_html($order->unique_code); ?></td>
                                <td><?php echo esc_html($order->restaurant_name); ?></td>
                                <td><?php echo esc_html($order->table_number ?: '-'); ?></td>
                                <td><?php echo esc_html(menuqr_money((float) $order->final_total)); ?></td>
                                <td><?php echo esc_html($order->payment_method . ' / ' . $order->payment_status); ?></td>
                                <td><span class="badge badge-<?php echo esc_attr($order->order_status); ?>"><?php echo esc_html($order->order_status); ?></span></td>
                                <td><?php echo esc_html(mysql2date('d M Y H:i', $order->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div></div>
                </div>

            <?php elseif ($tab === 'renewals') : ?>
                <div class="section-card"><div class="section-title">Renewals & Reminders</div><div class="alert alert-info">Shows accounts expiring in 7/3/1 days and expired accounts. One-click renewal uses enabled Platform UPI/Razorpay/Stripe methods.</div>
                <div class="table-wrap"><div class="table-scroll"><table class="data-table"><thead><tr><th>Restaurant</th><th>Plan</th><th>Status</th><th>Expires</th><th>Days Left</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($subscriptions as $srow) : $days = !empty($srow->expires_at) ? max(0,(int)ceil((strtotime($srow->expires_at)-current_time('timestamp'))/DAY_IN_SECONDS)) : 0; if ($days > 7 && $srow->status !== 'expired') continue; ?>
                    <tr><td><?php echo esc_html($srow->restaurant_name); ?></td><td><?php echo esc_html($srow->plan_name); ?></td><td><?php echo esc_html($srow->status); ?></td><td><?php echo esc_html(mysql2date('d M Y', $srow->expires_at)); ?></td><td><?php echo esc_html((string)$days); ?></td><td><a class="btn btn-outline btn-sm" href="<?php echo esc_url(add_query_arg(['tab'=>'restaurants','edit_restaurant'=>(int)$srow->restaurant_id], menuqr_get_page_url_by_slug('super-admin-dashboard'))); ?>">Extend / Edit</a></td></tr>
                <?php endforeach; ?></tbody></table></div></div></div>
            <?php elseif ($tab === 'expired') : ?>
                <div class="section-card"><div class="section-title">Expired Accounts</div><div class="table-wrap"><div class="table-scroll"><table class="data-table"><thead><tr><th>Restaurant</th><th>Plan</th><th>Expired On</th><th>Status</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($subscriptions as $srow) : if (strtotime((string)$srow->expires_at) >= current_time('timestamp') && $srow->status !== 'expired') continue; ?>
                    <tr><td><?php echo esc_html($srow->restaurant_name); ?></td><td><?php echo esc_html($srow->plan_name); ?></td><td><?php echo esc_html(mysql2date('d M Y', $srow->expires_at)); ?></td><td><span class="badge badge-expired">Expired</span></td><td><a class="btn btn-primary btn-sm" href="<?php echo esc_url(add_query_arg(['tab'=>'restaurants','edit_restaurant'=>(int)$srow->restaurant_id], menuqr_get_page_url_by_slug('super-admin-dashboard'))); ?>">Reactivate</a></td></tr>
                <?php endforeach; ?></tbody></table></div></div></div>
            <?php elseif ($tab === 'analytics') : ?>
                <?php $paid_users = count(array_filter($subscriptions, static fn($s)=>$s->status==='active')); $trial_users = count(array_filter($subscriptions, static fn($s)=>$s->status==='trial')); $mrr = (float)$wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$payments_table} WHERE status IN ('approved','verified') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"); ?>
                <div class="stat-grid"><div class="card"><div class="card-title">MRR</div><div class="card-value"><?php echo esc_html(menuqr_money($mrr)); ?></div></div><div class="card"><div class="card-title">Paid Users</div><div class="card-value"><?php echo esc_html((string)$paid_users); ?></div></div><div class="card"><div class="card-title">Trial Users</div><div class="card-value"><?php echo esc_html((string)$trial_users); ?></div></div><div class="card"><div class="card-title">Pending Payments</div><div class="card-value"><?php echo esc_html((string)$pending_payments); ?></div></div></div>

            <?php elseif ($tab === 'settings') : ?>
                <div class="chart-grid">
                    <div class="chart-card">
                        <div class="chart-title">Platform Settings</div>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('menuqr_save_platform_settings', 'menuqr_settings_nonce'); ?>
                            <input type="hidden" name="action" value="menuqr_save_platform_settings">
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Platform Name</label><input class="form-input" name="platform_name" value="<?php echo esc_attr($platform['platform_name']); ?>"></div>
                                <div class="form-group"><label class="form-label">Currency Symbol</label><input class="form-input" name="currency_symbol" value="<?php echo esc_attr($platform['currency_symbol']); ?>"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Default Tax %</label><input class="form-input" name="default_tax_rate" value="<?php echo esc_attr($platform['default_tax_rate']); ?>"></div>
                                <div class="form-group"><label class="form-label">Default Service Charge %</label><input class="form-input" name="default_service_charge_rate" value="<?php echo esc_attr($platform['default_service_charge_rate']); ?>"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Support Email</label><input class="form-input" type="email" name="support_email" value="<?php echo esc_attr($platform['support_email']); ?>"></div>
                                <div class="form-group"><label class="form-label">Support Phone</label><input class="form-input" name="support_phone" value="<?php echo esc_attr($platform['support_phone']); ?>"></div>
                            </div>
                            <div class="form-row">
                                <label class="form-check"><input type="checkbox" name="allow_restaurant_signup" <?php checked((int) $platform['allow_restaurant_signup'], 1); ?>> Allow restaurant signup</label>
                                <label class="form-check"><input type="checkbox" name="razorpay_enabled" <?php checked((int) $platform['razorpay_enabled'], 1); ?>> Enable Razorpay fields</label>
                            </div>
                            <div class="form-row">
                                <label class="form-check"><input type="checkbox" name="stripe_enabled" <?php checked((int) $platform['stripe_enabled'], 1); ?>> Enable Stripe fields</label>
                            </div>
                            <div class="fqx-v185-settings-gateway-note">
                                <strong>Platform Subscription Payment Gateway</strong>
                                <p>Plan buy/renew/upgrade payment settings ab separate user-friendly page par manage hota hai, taaki platform settings aur payment gateway confuse na ho.</p>
                                <a class="btn btn-primary btn-sm" href="<?php echo esc_url(menuqr_admin_tab_url('payment-gateway')); ?>">Open Plan Payment Gateway</a>
                            </div>
                            <button class="btn btn-primary" type="submit">Save Settings</button>
                            <div class="form-row">
                                <div class="form-group"><label class="form-label">Homepage Meta Title</label><input class="form-input" name="meta_title_home" value="<?php echo esc_attr($platform['meta_title_home'] ?? 'FluuexQR - QR Menu System for Restaurants'); ?>"></div>
                                <div class="form-group"><label class="form-label">Homepage Meta Description</label><textarea class="form-input" name="meta_description_home"><?php echo esc_textarea($platform['meta_description_home'] ?? ''); ?></textarea></div>
                            </div>
                        </form>
                    </div>
                    <div class="chart-card">
                        <div class="chart-title">Cache / SEO / Blog Tools</div>
                        <div class="sum-row"><span>Asset Cache Busting</span><span>filemtime()</span></div>
                        <div class="sum-row"><span>Blog Archive</span><span><a href="<?php echo esc_url(get_post_type_archive_link('post') ?: home_url('/blog/')); ?>" target="_blank">Open Blog</a></span></div>
                        <div class="sum-row"><span>Sitemap Compatibility</span><span>SEO plugin ready</span></div>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:16px;">
                            <?php wp_nonce_field('menuqr_clear_cache', 'menuqr_clear_cache_nonce'); ?>
                            <input type="hidden" name="action" value="menuqr_clear_platform_cache">
                            <button class="btn btn-outline" type="submit">Clear Theme Cache</button>
                        </form>
                    </div>
                    <div class="chart-card">
                        <div class="chart-title">Current Settings</div>
                        <div class="sum-row"><span>Platform</span><span><?php echo esc_html($platform['platform_name']); ?></span></div>
                        <div class="sum-row"><span>Currency</span><span><?php echo esc_html($platform['currency_symbol']); ?></span></div>
                        <div class="sum-row"><span>Default Tax</span><span><?php echo esc_html($platform['default_tax_rate']); ?>%</span></div>
                        <div class="sum-row"><span>Service Charge</span><span><?php echo esc_html($platform['default_service_charge_rate']); ?>%</span></div>
                        <div class="sum-row"><span>Signup Enabled</span><span><?php echo !empty($platform['allow_restaurant_signup']) ? 'Yes' : 'No'; ?></span></div>
                        <div class="sum-row"><span>Razorpay Fields</span><span><?php echo !empty($platform['razorpay_enabled']) ? 'Enabled' : 'Disabled'; ?></span></div>
                        <div class="sum-row"><span>Stripe Fields</span><span><?php echo !empty($platform['stripe_enabled']) ? 'Enabled' : 'Disabled'; ?></span></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
