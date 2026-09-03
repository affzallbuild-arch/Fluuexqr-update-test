<?php
if (!defined('ABSPATH')) { exit; }
menuqr_require_role(['staff', 'restaurant_admin', 'super_admin']);
menuqr_sync_user_role_context(get_current_user_id());
$restaurant_id = menuqr_get_current_restaurant_id();
$accessible_restaurant_ids = menuqr_get_user_accessible_restaurant_ids(get_current_user_id());
if (!$restaurant_id && !empty($accessible_restaurant_ids)) {
    $restaurant_id = (int) $accessible_restaurant_ids[0];
    update_user_meta(get_current_user_id(), 'menuqr_restaurant_id', $restaurant_id);
}
if (!$restaurant_id && is_user_logged_in()) {
    $restaurant_id = menuqr_get_staff_restaurant_id_by_user(get_current_user_id());
    if ($restaurant_id) {
        update_user_meta(get_current_user_id(), 'menuqr_restaurant_id', $restaurant_id);
        if (!in_array($restaurant_id, $accessible_restaurant_ids, true)) {
            $accessible_restaurant_ids[] = $restaurant_id;
        }
    }
}
$accessible_restaurant_ids = array_values(array_unique(array_filter(array_map('absint', (array) $accessible_restaurant_ids))));
if (!$restaurant_id && !empty($accessible_restaurant_ids)) {
    $restaurant_id = (int) $accessible_restaurant_ids[0];
}
$restaurant = $restaurant_id ? menuqr_get_restaurant($restaurant_id) : null;
$initial_orders = !empty($accessible_restaurant_ids)
    ? menuqr_get_active_kitchen_orders_by_restaurants($accessible_restaurant_ids)
    : ($restaurant_id ? menuqr_get_active_kitchen_orders($restaurant_id) : []);
foreach ($initial_orders as $order) {
    $order->items = json_decode((string) $order->items_json, true) ?: [];
}
if (!$restaurant_id && !current_user_can('manage_options')) {
    echo '<div class="mq-container narrow"><div class="alert alert-warning">Kitchen restaurant mapping not found. Please create staff under the same restaurant or login again.</div></div>';
    return;
}
?>
<section class="view active kitchen-app" id="v-kitchen-app"
    data-restaurant-id="<?php echo esc_attr((string) $restaurant_id); ?>"
    data-restaurant-ids="<?php echo esc_attr(implode(',', $accessible_restaurant_ids)); ?>">
    <div class="kitchen-topbar">
        <div>
            <h2><?php echo esc_html($restaurant ? $restaurant->name : 'Kitchen Display'); ?></h2>
            <div class="kitchen-meta">Live kitchen display • auto refresh every 5 seconds • sound on new order • payment status visible</div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="kitchen-live"><span class="live-dot"></span> Live</div>
            <a class="btn btn-outline btn-sm" href="<?php echo esc_url(wp_logout_url(menuqr_get_page_url_by_slug('login'))); ?>">Logout</a>
        </div>
    </div>
    <div class="kitchen-summary-bar">
        <div class="kitchen-summary-chip"><strong id="k-live-count"><?php echo esc_html((string) count($initial_orders)); ?></strong><span>Live Orders</span></div>
        <div class="kitchen-summary-chip"><strong>5s</strong><span>Auto Refresh</span></div>
        <div class="kitchen-summary-chip"><strong id="k-poll-status">Connecting</strong><span>Polling</span></div>
        <div class="kitchen-summary-chip"><strong id="k-last-updated">—</strong><span>Last Update</span></div>
    </div>
    <div class="kitchen-grid" id="k-grid">
        <?php if (!empty($initial_orders)) : ?>
            <?php foreach ($initial_orders as $order) : ?>
                <?php
                    $status = strtolower((string) $order->order_status);
                    $minutes = isset($order->age_minutes) ? max(0, (int) $order->age_minutes) : max(0, (int) floor((current_time('timestamp') - strtotime((string) $order->created_at)) / 60));
                    $card_classes = ['kc'];
                    if ($minutes >= 20) { $card_classes[] = 'urgent'; }
                    $first_item = !empty($order->items[0]) ? $order->items[0] : [];
                    $first_image = !empty($first_item['image']) ? esc_url($first_item['image']) : '';
                    $first_emoji = !empty($first_item['emoji']) ? (string) $first_item['emoji'] : '🍽️';
                    $payment_method = strtoupper((string) ($order->payment_method ?: 'cash'));
                    $payment_status = strtolower((string) ($order->payment_status ?: 'pending'));
                ?>
                <div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
                    <div class="kc-head">
                        <div class="kc-head-main">
                            <?php if ($first_image) : ?>
                                <img class="kc-thumb" src="<?php echo $first_image; ?>" alt="<?php echo esc_attr($first_item['name'] ?? 'Order item'); ?>">
                            <?php else : ?>
                                <div class="kc-thumb kc-thumb-emoji"><?php echo esc_html($first_emoji); ?></div>
                            <?php endif; ?>
                            <div>
                                <?php
                                if (function_exists('menuqr_normalize_order_service_point')) { $order = menuqr_normalize_order_service_point($order); }
                                $is_room_order = (($order->order_source ?? '') === 'room_qr');
                                $point_value = $is_room_order ? ($order->room_number ?: $order->room_id) : ($order->table_number ?: $order->table_id);
                                $point_label = $is_room_order ? 'Room No' : 'Table No';
                                ?>
                                <div class="kc-table"><?php echo esc_html($point_label . ': ' . ($point_value ?: '—')); ?></div>
                                <div class="kc-source-badge <?php echo esc_attr($is_room_order ? 'is-room' : 'is-table'); ?>"><?php echo esc_html($is_room_order ? 'Room Order' : 'Table Order'); ?></div>
                                <div class="kc-meta"><?php echo esc_html($order->unique_code ?: ('MQR-' . $order->id)); ?></div>
                            </div>
                        </div>
                        <div class="kc-head-side">
                            <div class="kc-timer <?php echo esc_attr($minutes >= 20 ? 'urgent' : ($minutes >= 10 ? 'warn' : 'ok')); ?>">
                                <?php echo esc_html($minutes . ' min'); ?>
                            </div>
                            <span class="badge badge-<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span>
                        </div>
                    </div>
                    <div class="kc-pay-row">
                        <span class="tag tag-blue"><?php echo esc_html($payment_method); ?></span>
                        <span class="tag <?php echo esc_attr($payment_status === 'paid' ? 'tag-green' : 'tag-accent'); ?>"><?php echo esc_html($payment_status); ?></span>
                        <span class="kc-total"><?php echo esc_html(menuqr_money((float) $order->final_total)); ?></span>
                    </div>
                    <div class="kc-items">
                        <?php foreach ((array) $order->items as $item) : ?>
                            <div class="kc-item">
                                <span><?php echo esc_html($item['name'] ?? 'Item'); ?></span>
                                <span class="kc-item-qty">×<?php echo esc_html((string) ($item['qty'] ?? 1)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="kc-actions">
                        <button class="btn btn-sm btn-kitchen" data-kitchen-order="<?php echo esc_attr((string) $order->id); ?>" data-status="accepted">Accept</button>
                        <button class="btn btn-sm btn-kitchen" data-kitchen-order="<?php echo esc_attr((string) $order->id); ?>" data-status="preparing">Preparing</button>
                        <button class="btn btn-sm btn-kitchen" data-kitchen-order="<?php echo esc_attr((string) $order->id); ?>" data-status="ready">Ready</button>
                        <button class="btn btn-sm btn-kitchen btn-kitchen-success" data-kitchen-order="<?php echo esc_attr((string) $order->id); ?>" data-status="served">Served</button>
                        <button class="btn btn-sm btn-kitchen btn-kitchen-cancel" data-kitchen-order="<?php echo esc_attr((string) $order->id); ?>" data-status="cancelled">Cancel</button>
                    </div>
                    <?php if (!empty($order->customer_note)) : ?>
                        <div class="kc-note">Note: <?php echo esc_html($order->customer_note); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="section-card kitchen-empty-card">No active kitchen orders yet. Keep this screen open. New orders will appear automatically in 5 seconds.</div>
        <?php endif; ?>
    </div>
</section>


<?php
$menuqr_kitchen_config = [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('menuqr_nonce'),
    'restaurantId' => (int) $restaurant_id,
    'restaurantIds' => implode(',', $accessible_restaurant_ids),
    'refreshMs' => 5000,
];
?>
<script>
window.MenuQRKitchenInlineActive = true;
window.MenuQRKitchenConfig = <?php echo wp_json_encode($menuqr_kitchen_config); ?>;
(function(){
    'use strict';

    var config = window.MenuQRKitchenConfig || {};
    var grid = document.getElementById('k-grid');
    var liveCount = document.getElementById('k-live-count');
    var lastUpdated = document.getElementById('k-last-updated');
    var pollStatus = document.getElementById('k-poll-status');
    var knownIds = [];
    var polling = false;
    var timer = null;

    if(!grid || !config.ajaxUrl){ return; }

    function escapeHtml(value){
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function money(value){
        return '₹' + Number(value || 0).toFixed(2);
    }

    function setPollStatus(text, className){
        if(!pollStatus){ return; }
        pollStatus.textContent = text;
        pollStatus.className = className || '';
    }

    function setLastUpdated(){
        if(!lastUpdated){ return; }
        var now = new Date();
        lastUpdated.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'});
    }

    function orderAgeMinutes(order){
        if(order && typeof order.age_minutes !== 'undefined'){
            var serverMinutes = Number(order.age_minutes || 0);
            if(!isNaN(serverMinutes) && serverMinutes >= 0){ return Math.floor(serverMinutes); }
        }
        if(!order || !order.created_at){ return 0; }
        var parsed = new Date(String(order.created_at).replace(' ', 'T'));
        if(isNaN(parsed.getTime())){ return 0; }
        return Math.max(0, Math.floor((Date.now() - parsed.getTime()) / 60000));
    }

    function cleanPointValue(value){
        value = String(value === undefined || value === null ? '' : value).trim();
        if(!value || value === '0' || value === '—' || value.toLowerCase() === 'null'){ return ''; }
        return value;
    }

    function sourceMeta(order){
        var source = String((order && order.order_source) || '').toLowerCase();
        var roomValue = cleanPointValue(order && (order.room_number || order.room_id));
        var tableValue = cleanPointValue(order && (order.table_number || order.table_id));
        var isRoom = false;
        if(roomValue){
            isRoom = true;
        } else if((source === 'room_qr' || source === 'room') && !tableValue){
            isRoom = true;
        }
        return {
            isRoom: isRoom,
            label: isRoom ? 'Room No:' : 'Table No:',
            value: isRoom ? (roomValue || '—') : (tableValue || '—')
        };
    }

    function statusLabel(status){
        var map = {
            pending: 'Pending',
            accepted: 'Accepted',
            preparing: 'Preparing',
            ready: 'Ready',
            served: 'Served'
        };
        return map[status] || status || 'Pending';
    }

    function timerClass(minutes){
        if(minutes >= 20){ return 'urgent'; }
        if(minutes >= 10){ return 'warn'; }
        return 'ok';
    }

    function playPing(){
        try {
            var AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if(!AudioContextClass){ return; }
            var ctx = new AudioContextClass();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.001, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.14, ctx.currentTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.36);
        } catch(e) {}
    }

    function itemMedia(item){
        if(item && item.image){
            return '<img src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.name || 'Item') + '" class="kc-item-img">';
        }
        return '<span class="kc-item-emoji">' + escapeHtml((item && item.emoji) || '🍽️') + '</span>';
    }

    function button(order, status, label){
        var current = String(order.order_status || 'pending');
        var active = current === status ? ' is-current' : '';
        var served = status === 'served' ? ' btn-kitchen-success' : '';
        var cancelled = status === 'cancelled' ? ' btn-kitchen-cancel' : '';
        return '<button type="button" class="btn-kitchen-action' + active + served + cancelled + '" data-kitchen-order="' + escapeHtml(order.id) + '" data-status="' + escapeHtml(status) + '">' + escapeHtml(label) + '</button>';
    }

    function orderCard(order){
        var items = Array.isArray(order.items) ? order.items : [];
        var firstItem = items[0] || {};
        var minutes = orderAgeMinutes(order);
        var id = Number(order.id);
        var isNew = knownIds.length > 0 && knownIds.indexOf(id) === -1;
        var payMethod = String(order.payment_method || 'cash').toUpperCase();
        var payStatus = String(order.payment_status || 'pending').toUpperCase();
        var status = String(order.order_status || 'pending');
        var point = sourceMeta(order);
        var hero = firstItem.image
            ? '<img src="' + escapeHtml(firstItem.image) + '" alt="' + escapeHtml(firstItem.name || 'Order item') + '" class="kc-thumb">'
            : '<div class="kc-thumb kc-thumb-emoji">' + escapeHtml(firstItem.emoji || '🍽️') + '</div>';

        return '<article class="kc kitchen-order-card ' + (isNew ? 'new-order ' : '') + (minutes >= 20 ? 'urgent' : '') + '" data-order-id="' + escapeHtml(id) + '">' +
            '<div class="kc-head">' +
                '<div class="kc-head-main">' +
                    hero +
                    '<div class="kc-order-main">' +
                        '<div class="kc-table">' + escapeHtml(point.label) + ' ' + escapeHtml(point.value) + '</div>' +
                        '<div class="kc-source-badge ' + (point.isRoom ? 'is-room' : 'is-table') + '">' + (point.isRoom ? 'Room Order' : 'Table Order') + '</div>' +
                        '<div class="kc-meta">' + escapeHtml(order.unique_code || ('MQR-' + id)) + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="kc-head-side">' +
                    '<div class="kc-timer ' + timerClass(minutes) + '">' + minutes + ' min</div>' +
                    '<span class="kc-status-badge status-' + escapeHtml(status) + '">' + escapeHtml(statusLabel(status)) + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="kc-pay-row">' +
                '<span class="kc-pill payment-method">' + escapeHtml(payMethod) + '</span>' +
                '<span class="kc-pill payment-status">' + escapeHtml(payStatus) + '</span>' +
                '<span class="kc-total">' + money(order.final_total || 0) + '</span>' +
            '</div>' +
            '<div class="kc-items">' +
                items.map(function(item){
                    return '<div class="kc-item kitchen-item-row">' +
                        '<div class="kc-item-left">' + itemMedia(item) + '<span class="kc-item-name">' + escapeHtml(item.name || 'Item') + '</span></div>' +
                        '<span class="kc-item-qty">×' + escapeHtml(item.qty || 1) + '</span>' +
                    '</div>';
                }).join('') +
            '</div>' +
            '<div class="kc-actions">' +
                button(order, 'accepted', 'Accept') +
                button(order, 'preparing', 'Preparing') +
                button(order, 'ready', 'Ready') +
                button(order, 'served', 'Served') +
                button(order, 'cancelled', 'Cancel') +
            '</div>' +
            (order.customer_note ? '<div class="kc-note">Note: ' + escapeHtml(order.customer_note) + '</div>' : '') +
        '</article>';
    }

    function renderOrders(orders){
        var ids = orders.map(function(order){ return Number(order.id); });
        var hasNew = ids.some(function(id){ return knownIds.indexOf(id) === -1; }) && knownIds.length > 0;

        if(!orders.length){
            grid.innerHTML = '<div class="section-card kitchen-empty-card"><strong>No active kitchen orders yet.</strong><br>Keep this screen open. New orders will appear automatically within 5 seconds.</div>';
        } else {
            grid.innerHTML = orders.map(orderCard).join('');
        }

        if(liveCount){ liveCount.textContent = String(orders.length); }
        if(hasNew){ playPing(); }
        knownIds = ids;
    }

    function fetchOrders(){
        if(polling){ return; }
        polling = true;
        setPollStatus('Checking', 'is-checking');

        var form = new FormData();
        form.append('action', 'menuqr_get_kitchen_orders');
        form.append('nonce', config.nonce || '');
        form.append('restaurant_id', config.restaurantId || 0);
        form.append('restaurant_ids', config.restaurantIds || '');
        form.append('_menuqr_cache_bust', String(Date.now()));

        fetch(config.ajaxUrl + '?_menuqr_kitchen=' + Date.now(), {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, max-age=0',
                'Pragma': 'no-cache'
            },
            body: form
        }).then(function(response){
            return response.json();
        }).then(function(json){
            if(!json || !json.success){
                setPollStatus('Error', 'is-error');
                return;
            }
            renderOrders((json.data && json.data.orders) || []);
            setLastUpdated();
            setPollStatus('Live', 'is-live');
        }).catch(function(){
            setPollStatus('Retrying', 'is-error');
        }).finally(function(){
            polling = false;
        });
    }

    function updateStatus(buttonEl){
        if(!buttonEl || buttonEl.classList.contains('is-updating')){ return; }
        buttonEl.classList.add('is-updating');
        buttonEl.disabled = true;

        var form = new FormData();
        form.append('action', 'menuqr_update_order_status');
        form.append('nonce', config.nonce || '');
        form.append('order_id', buttonEl.getAttribute('data-kitchen-order') || '');
        form.append('status', buttonEl.getAttribute('data-status') || '');
        if (String(buttonEl.getAttribute('data-status') || '') === 'cancelled') {
            var reason = window.prompt('Cancellation reason / remarks:');
            if (reason === null) { buttonEl.classList.remove('is-updating'); buttonEl.disabled = false; return; }
            reason = String(reason).trim();
            if (!reason) { window.alert('Cancellation reason is required.'); buttonEl.classList.remove('is-updating'); buttonEl.disabled = false; return; }
            form.append('remarks', reason);
        }

        fetch(config.ajaxUrl + '?_menuqr_status=' + Date.now(), {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            body: form
        }).then(function(response){
            return response.json();
        }).then(function(json){
            if(!json || !json.success){
                buttonEl.classList.add('is-error');
                setTimeout(function(){ buttonEl.classList.remove('is-error'); }, 1200);
                return;
            }
            buttonEl.classList.add('is-done');
            fetchOrders();
        }).catch(function(){
            buttonEl.classList.add('is-error');
            setTimeout(function(){ buttonEl.classList.remove('is-error'); }, 1200);
        }).finally(function(){
            setTimeout(function(){
                buttonEl.disabled = false;
                buttonEl.classList.remove('is-updating', 'is-done');
            }, 700);
        });
    }

    grid.addEventListener('click', function(event){
        var buttonEl = event.target.closest('[data-kitchen-order][data-status]');
        if(buttonEl){ updateStatus(buttonEl); }
    });

    fetchOrders();
    timer = window.setInterval(fetchOrders, Number(config.refreshMs || 5000));
    window.addEventListener('focus', fetchOrders);
    document.addEventListener('visibilitychange', function(){
        if(document.visibilityState === 'visible'){ fetchOrders(); }
    });
})();
</script>
