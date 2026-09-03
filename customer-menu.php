<?php
if (!defined('ABSPATH')) {
    exit;
}
$restaurant_id = absint($_GET['r'] ?? ($_GET['restaurant_id'] ?? 0));
$source_raw = sanitize_key(wp_unslash($_GET['source'] ?? $_GET['order_source'] ?? ''));
$table_id = absint($_GET['table_id'] ?? ($_GET['t'] ?? ($_GET['table'] ?? 0)));
$table_ref = sanitize_text_field(wp_unslash($_GET['table_no'] ?? ($_GET['table_number'] ?? ($_GET['t'] ?? ''))));
$room_id = absint($_GET['room_id'] ?? ($_GET['room'] ?? ($_GET['room_no'] ?? ($_GET['room_number'] ?? 0))));
$room_ref = sanitize_text_field(wp_unslash($_GET['room_no'] ?? ($_GET['room_number'] ?? ($_GET['room'] ?? ($_GET['room_id'] ?? '')))));

if (in_array($source_raw, ['room', 'room_qr', 'hotel_room'], true)) {
    $table_id = 0;
} elseif (in_array($source_raw, ['table', 'table_qr'], true)) {
    $room_id = 0;
}

$fqx_secure_context = function_exists('fqx_v200_resolve_request_service') ? fqx_v200_resolve_request_service([
    'restaurant_id' => $restaurant_id,
    'table_id' => $table_id,
    'room_id' => $room_id,
    'order_source' => $room_id > 0 ? 'room_qr' : 'table_qr',
]) : ['ok' => true];
if (empty($fqx_secure_context['ok'])) {
    echo '<section class="menuqr-customer-app"><div style="max-width:620px;margin:40px auto;padding:24px;border-radius:18px;background:#fff3cd;color:#7c2d12;text-align:center;font-weight:800;">' . esc_html($fqx_secure_context['message'] ?? 'Invalid QR. Please scan the correct QR again.') . '</div></section>';
    return;
}
if (empty($fqx_secure_context['legacy_mode'])) {
    $restaurant_id = (int) ($fqx_secure_context['restaurant_id'] ?? $restaurant_id);
    $table_id = (int) ($fqx_secure_context['table_id'] ?? 0);
    $room_id = (int) ($fqx_secure_context['room_id'] ?? 0);
    $source_raw = (string) ($fqx_secure_context['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr'));
    $table_ref = (string) ($fqx_secure_context['table_number'] ?? '');
    $room_ref = (string) ($fqx_secure_context['room_number'] ?? '');
}

$service_context = function_exists('menuqr_get_service_point_context') ? menuqr_get_service_point_context($restaurant_id, $table_id, $room_id, $table_ref, $room_ref) : [];
if (!empty($fqx_secure_context['qr_token'])) { $service_context['qr_token'] = (string) $fqx_secure_context['qr_token']; }
$order_source = (string) ($service_context['order_source'] ?? ($room_id > 0 ? 'room_qr' : 'table_qr'));
$table_id = (int) ($service_context['table_id'] ?? $table_id);
$room_id = (int) ($service_context['room_id'] ?? $room_id);
$table_label = (string) ($service_context['table_number'] ?? ($table_ref ?: $table_id));
$room_label = (string) ($service_context['room_number'] ?? ($room_ref ?: $room_id));
$service_label = (string) ($service_context['label'] ?? ($order_source === 'room_qr' ? ('Room ' . $room_label) : ('Table ' . $table_label)));
$fqx_room_session = null;
if ($order_source === 'room_qr' && function_exists('fqx_start_room_session')) {
    $fqx_room_session = $GLOBALS['fqx_v133_current_room_session'] ?? fqx_start_room_session($restaurant_id, $room_id, fqx_create_device_hash());
}
?>
<section id="menuqr-customer-app" class="menuqr-customer-app menuqr-foodwala-ui" data-restaurant-id="<?php echo esc_attr((string) $restaurant_id); ?>" data-table-id="<?php echo esc_attr((string) $table_id); ?>" data-room-id="<?php echo esc_attr((string) $room_id); ?>" data-order-source="<?php echo esc_attr($order_source); ?>" data-table-label="<?php echo esc_attr($table_label); ?>" data-room-label="<?php echo esc_attr($room_label); ?>" data-service-label="<?php echo esc_attr($service_label); ?>" data-qr-token="<?php echo esc_attr((string) ($service_context['qr_token'] ?? '')); ?>" data-room-session-token="<?php echo esc_attr((string) ($fqx_room_session->session_token ?? '')); ?>" data-room-session-expires="<?php echo esc_attr((string) ($fqx_room_session->expires_at ?? '')); ?>">
    <div class="view active" id="v-menu">
        <header class="menuqr-menu-topbar">
            <div class="menuqr-menu-topbar-shell">
                <div class="menuqr-topbar-brand">
                    <div class="menu-restaurant-logo" id="m-restaurant-logo" aria-hidden="true"><span>R</span></div>
                    <div class="menuqr-topbar-copy">
                        <div class="menu-rest-name" id="m-rest-name">Restaurant</div>
                        <div class="menu-rest-subtitle" id="m-rest-subtitle">Good Food, Good Mood</div>
                        <div class="menu-table" id="m-table-info" aria-live="polite"><?php echo esc_html($service_label); ?></div>
                        <?php if ($order_source === 'room_qr' && $fqx_room_session && function_exists('fqx_is_room_session_active') && fqx_is_room_session_active((string) $fqx_room_session->session_token)) : ?>
                            <div class="fqx-room-session-badge">Room Session Active — 24 Hours</div>
                        <?php elseif ($order_source === 'room_qr') : ?>
                            <div class="fqx-room-session-expired">Your room session has expired. Please scan the Room QR again.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="menuqr-topbar-header-actions" aria-label="Menu quick actions">
                    <button class="menuqr-header-icon menuqr-header-icon-cart" type="button" data-menuqr-go="cart" aria-label="Open cart" title="Cart">
                        <span class="menuqr-header-icon-glyph" aria-hidden="true">🛒</span>
                        
                        <b class="menuqr-header-badge" id="m-cart-count">0</b>
                    </button>
                    <button class="menuqr-header-icon menuqr-header-icon-bill" type="button" id="menuqr-header-view-bill" aria-label="View bill" title="Bill">
                        <span class="menuqr-header-icon-glyph" aria-hidden="true">🧾</span>
                       
                    </button>
                    <button class="menuqr-header-icon menuqr-header-icon-track" type="button" id="menuqr-header-track-order" aria-label="Track order" title="Track order">
                        <span class="menuqr-header-icon-glyph" aria-hidden="true">📍</span>
                        
                    </button>
                </div>
            </div>
        </header>

        <section class="menuqr-food-tools" aria-label="Menu filters">
            <div class="cat-strip" id="m-cat-strip"></div>
        </section>

        <main class="menuqr-menu-content">
            <div class="menu-grid" id="m-items-grid">
                <div class="section-card menu-loading-card">
                    <div class="menu-loading-icon">🍽️</div>
                    <strong>Loading restaurant menu…</strong>
                    <span>Please wait while we fetch the categories and items.</span>
                </div>
            </div>
            <div class="menuqr-bill-history" id="menuqr-bill-history" hidden></div>

            <footer class="menuqr-customer-footer">
                <strong id="m-footer-rest-name">Restaurant</strong>
                <span id="m-footer-rest-copy">Browse the menu, add items to cart, review your bill and place your order.</span>
                <div class="menuqr-restaurant-review-card" id="menuqr-restaurant-review-card" hidden></div>
            </footer>
        </main>
    </div>
</section>
