(function($){
  'use strict';
  var FOUR_HOURS = 4 * 60 * 60 * 1000;

  function app(){ return $('#menuqr-customer-app'); }
  function service(){
    var root = app();
    var roomId = Number(root.data('room-id') || 0);
    var tableId = Number(root.data('table-id') || 0);
    return {
      restaurantId: Number(root.data('restaurant-id') || 0),
      tableId: tableId,
      roomId: roomId,
      orderSource: String(root.data('order-source') || (roomId > 0 ? 'room_qr' : 'table_qr')),
      serviceId: roomId > 0 ? roomId : tableId
    };
  }
  function storageKey(){
    var s = service();
    return 'menuqr_bill_session_' + s.restaurantId + '_' + (s.roomId > 0 ? 'room' : 'table') + '_' + s.serviceId;
  }
  function trackerKey(){ return storageKey() + '_v129_tracker'; }
  function ordersKey(){ return storageKey() + '_v129_order_ids'; }
  function latestOrderKey(){ return storageKey() + '_v129_last_order_id'; }
  function readJSON(key){ try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch(e){ return null; } }
  function writeJSON(key, value){ try { localStorage.setItem(key, JSON.stringify(value)); } catch(e){} }
  function cleanExpired(key){
    var data = readJSON(key);
    if(data && data.createdAt && (Date.now() - Number(data.createdAt)) > FOUR_HOURS){
      try { localStorage.removeItem(key); } catch(e){}
      return null;
    }
    return data;
  }
  function getToken(){
    var stored = cleanExpired(storageKey());
    return stored && stored.token ? String(stored.token) : '';
  }
  function getLastOrderId(){
    var data = cleanExpired(latestOrderKey());
    if(data && data.orderId){ return Number(data.orderId); }
    var ids = cleanExpired(ordersKey());
    if(ids && $.isArray(ids.ids) && ids.ids.length){ return Number(ids.ids[ids.ids.length - 1]); }
    return 0;
  }
  function saveOrderId(orderId){
    orderId = Number(orderId || 0);
    if(!orderId){ return; }
    var data = cleanExpired(ordersKey()) || {ids: [], createdAt: Date.now()};
    if(!$.isArray(data.ids)){ data.ids = []; }
    if(data.ids.indexOf(orderId) === -1){ data.ids.push(orderId); }
    data.ids = data.ids.slice(-20);
    data.createdAt = data.createdAt || Date.now();
    writeJSON(ordersKey(), data);
    writeJSON(latestOrderKey(), {orderId: orderId, createdAt: Date.now()});
    writeJSON(trackerKey(), {active: true, createdAt: Date.now()});
  }
  function esc(value){
    return $('<div>').text(value == null ? '' : String(value)).html();
  }
  function money(value){
    var n = Number(value || 0);
    return '₹' + n.toLocaleString('en-IN', {maximumFractionDigits: 2});
  }
  function label(status){
    status = String(status || 'pending');
    return status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ');
  }
  function itemRows(order){
    var items = order.items || [];
    return items.map(function(item){
      return '<div class="sum-row"><span>' + esc(item.emoji || '🍽️') + ' ' + esc(item.name || 'Item') + ' × ' + esc(item.qty || 1) + '</span><span>' + money(Number(item.price || 0) * Number(item.qty || 1)) + '</span></div>';
    }).join('') || '<p class="text-muted">No items found.</p>';
  }
  function stepsHtml(status){
    var steps = ['pending','accepted','preparing','ready','served'];
    var active = Math.max(0, steps.indexOf(String(status || 'pending')));
    return '<div class="status-steps fqx-v129-steps">' + steps.map(function(step, index){
      var cls = index < active ? 'done' : (index === active ? 'active' : '');
      var connector = index < steps.length - 1 ? '<div class="step-connector ' + (index < active ? 'done' : '') + '"></div>' : '';
      return '<div class="step-block"><div class="step-circle ' + cls + '">' + (index + 1) + '</div><div class="step-label">' + esc(step) + '</div>' + connector + '</div>';
    }).join('') + '</div>';
  }
  function showStatusView(){
    $('#v-menu,#menuqr-cart-wrap,#menuqr-checkout-wrap').attr('hidden','hidden').removeClass('active is-active').css('display','none').attr('aria-hidden','true');
    $('#menuqr-order-status-wrap').removeAttr('hidden').addClass('active is-active').css('display','block').attr('aria-hidden','false');
    $('html,body').stop(true).animate({scrollTop: 0}, 180);
  }
  function statusClass(status){
    status = String(status || 'unpaid').toLowerCase();
    if(status === 'paid') return 'badge-paid';
    if(status.indexOf('pending') !== -1) return 'badge-pending';
    if(status === 'failed') return 'badge-cancelled';
    return 'badge-unpaid';
  }
  function renderTracker(data){
    var bill = data && data.bill ? data.bill : null;
    var orders = data && $.isArray(data.orders) ? data.orders : [];
    var billStatus = bill ? String(bill.payment_status || 'unpaid') : 'unpaid';
    var billUrl = (data && (data.bill_direct_url || data.bill_url)) ? String(data.bill_direct_url || data.bill_url) : '';
    var billTotal = bill ? Number(bill.grand_total || 0) : 0;

    $('#st-current').text(orders.length ? (orders.length + ' order running') : 'Live tracking');

    if(!orders.length){
      $('#st-items').html('<div class="empty-state"><span class="empty-icon">📍</span><h4>No active order found</h4><p>Place an order and tracking will stay here for 4 hours.</p></div>');
      $('#st-details').html('');
      $('#st-steps').html('');
      return;
    }

    var cards = orders.map(function(order, index){
      var orderNo = Number(order.order_index || (index + 1));
      var title = 'Order ' + orderNo;
      var code = order.unique_code || ('MQR-' + order.id);
      var payStatus = String(order.bill_payment_status || billStatus || order.payment_status || 'unpaid');
      return '<article class="fqx-v129-order-card ' + (String(order.order_status) === 'served' ? 'is-served' : '') + '">' +
        '<div class="fqx-v129-order-head"><div><strong>' + esc(title) + '</strong><span>' + esc(code) + '</span></div><span class="badge badge-' + esc(order.order_status || 'pending') + '">' + esc(label(order.order_status)) + '</span></div>' +
        stepsHtml(order.order_status) +
        '<div class="fqx-v129-order-items">' + itemRows(order) + '</div>' +
        '<div class="fqx-v129-order-meta"><div><span>Order Total</span><b>' + money(order.final_total || 0) + '</b></div><div><span>Bill Status</span><b class="badge ' + statusClass(payStatus) + '">' + esc(label(payStatus)) + '</b></div></div>' +
      '</article>';
    }).join('');

    $('#st-items').html(cards);
    $('#st-steps').html('');
    $('#st-details').html(
      '<div class="fqx-v129-bill-status-card"><div><span>4-Hour Running Bill</span><strong>' + money(billTotal) + '</strong></div>' +
      '<div><span>Payment Status</span><strong class="badge ' + statusClass(billStatus) + '">' + esc(label(billStatus)) + '</strong></div>' +
      (billUrl ? '<a class="btn btn-primary btn-full" target="_blank" rel="noopener" href="' + esc(billUrl) + '">🧾 Open Real Bill</a>' : '') +
      '</div>'
    );
  }
  function fetchTracker(openView){
    var s = service();
    var token = getToken();
    var lastOrder = getLastOrderId();
    if(openView){ showStatusView(); }
    if(!s.restaurantId || (!s.tableId && !s.roomId) || (!token && !lastOrder)){
      if(openView){
        renderTracker({orders: []});
      }
      return;
    }
    $('#st-items').html('<div class="section-card">Loading 4-hour order tracking…</div>');
    $.ajax({
      url: window.menuqr_ajax.ajax_url,
      method: 'GET',
      cache: false,
      data: {
        action: 'menuqr_get_running_order_tracker',
        nonce: window.menuqr_ajax.nonce,
        restaurant_id: s.restaurantId,
        table_id: s.tableId,
        room_id: s.roomId,
        order_source: s.orderSource,
        bill_session_token: token,
        order_id: lastOrder,
        _t: Date.now()
      }
    }).done(function(resp){
      if(resp && resp.success){ renderTracker(resp.data || {}); }
      else { renderTracker({orders: []}); }
    }).fail(function(){
      $('#st-items').html('<div class="section-card">Tracking refresh failed. Please try again.</div>');
    });
  }
  function refreshBillIfOpen(){
    if(!$('#menuqr-bill-history').length || $('#menuqr-bill-history').is('[hidden]') || !$('#menuqr-bill-history').is(':visible')){ return; }
    $('#menuqr-header-view-bill').trigger('click');
  }

  $(document).ajaxSuccess(function(_event, _xhr, settings, response){
    try {
      var dataString = String(settings && settings.data ? settings.data : '');
      var data = response && response.data ? response.data : null;
      if(data && data.order_id){ saveOrderId(data.order_id); }
      if(data && data.order && data.order.id){ saveOrderId(data.order.id); }
      if(dataString.indexOf('menuqr_update_order_status') !== -1 || dataString.indexOf('menuqr_get_customer_bill') !== -1){
        if($('#menuqr-order-status-wrap').is(':visible')){ setTimeout(function(){ fetchTracker(false); }, 400); }
      }
    } catch(e){}
  });

  $(document).off('click.fqxV129Track', '#menuqr-header-track-order').on('click.fqxV129Track', '#menuqr-header-track-order', function(e){
    e.preventDefault();
    e.stopImmediatePropagation();
    writeJSON(trackerKey(), {active: true, createdAt: Date.now()});
    fetchTracker(true);
  });

  $(document).off('click.fqxV129StatusRefresh', '#menuqr-status-refresh').on('click.fqxV129StatusRefresh', '#menuqr-status-refresh', function(e){
    e.preventDefault();
    fetchTracker(false);
  });

  $(function(){
    cleanExpired(trackerKey());
    cleanExpired(ordersKey());
    cleanExpired(latestOrderKey());
    var tracker = cleanExpired(trackerKey());
    if($('#menuqr-order-status-wrap').length && tracker && tracker.active){
      // Keep tracking available for 4 hours, but do not auto-switch user away from menu on fresh page load.
      fetchTracker(false);
    }
    window.setInterval(function(){
      if($('#menuqr-order-status-wrap').is(':visible')){ fetchTracker(false); }
      refreshBillIfOpen();
    }, 6000);
  });
})(jQuery);
