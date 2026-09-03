(function($){
  'use strict';

  var FOUR_HOURS = 4 * 60 * 60 * 1000;
  var fetchingTracker = false;
  var lastTrackerSignature = '';
  var lastTrackerPayload = null;

  function app(){ return $('#menuqr-customer-app'); }
  function ctx(){
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
  function keyBase(){ var s = ctx(); return 'menuqr_bill_session_' + s.restaurantId + '_' + (s.roomId > 0 ? 'room' : 'table') + '_' + s.serviceId; }
  function trackerKey(){ return keyBase() + '_v131_tracker'; }
  function ordersKey(){ return keyBase() + '_v129_order_ids'; }
  function latestOrderKey(){ return keyBase() + '_v129_last_order_id'; }
  function payloadKey(){ return keyBase() + '_v131_payload'; }
  function readJSON(key){ try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch(e){ return null; } }
  function writeJSON(key, value){ try { localStorage.setItem(key, JSON.stringify(value)); } catch(e){} }
  function cleanExpired(key){
    var data = readJSON(key);
    if(data && data.createdAt && (Date.now() - Number(data.createdAt)) > FOUR_HOURS){ try { localStorage.removeItem(key); } catch(e){} return null; }
    return data;
  }
  function token(){ var data = cleanExpired(keyBase()); return data && data.token ? String(data.token) : ''; }
  function lastOrderId(){
    var data = cleanExpired(latestOrderKey());
    if(data && data.orderId){ return Number(data.orderId); }
    var ids = cleanExpired(ordersKey());
    return ids && $.isArray(ids.ids) && ids.ids.length ? Number(ids.ids[ids.ids.length - 1]) : 0;
  }
  function saveOrderId(id){
    id = Number(id || 0); if(!id){ return; }
    var data = cleanExpired(ordersKey()) || {ids: [], createdAt: Date.now()};
    if(!$.isArray(data.ids)){ data.ids = []; }
    if(data.ids.indexOf(id) === -1){ data.ids.push(id); }
    data.ids = data.ids.slice(-20);
    data.createdAt = data.createdAt || Date.now();
    writeJSON(ordersKey(), data);
    writeJSON(latestOrderKey(), {orderId: id, createdAt: Date.now()});
    writeJSON(trackerKey(), {active: true, createdAt: Date.now()});
  }
  function esc(v){ return $('<div>').text(v == null ? '' : String(v)).html(); }
  function money(v){ return '₹' + (Number(v || 0)).toLocaleString('en-IN', {maximumFractionDigits: 2}); }
  function normalizeStatus(status){ return String(status || 'unpaid').toLowerCase(); }
  function label(status){ status = String(status || 'pending').replace(/_/g, ' '); return status.charAt(0).toUpperCase() + status.slice(1); }
  function isDoneStatus(status){ status = normalizeStatus(status); return status === 'served' || status === 'cancelled' || status === 'refunded'; }
  function visibleOrderLabel(status){ return normalizeStatus(status) === 'served' ? 'Served' : 'Running Order'; }
  function orderBadgeClass(status){ return normalizeStatus(status) === 'served' ? 'badge-paid' : 'badge-running'; }
  function badgeClass(status){
    status = normalizeStatus(status);
    if(status === 'paid' || status === 'success' || status === 'captured' || status === 'completed'){ return 'badge-paid'; }
    if(status.indexOf('pending') !== -1){ return 'badge-pending'; }
    if(status === 'failed' || status === 'cancelled' || status === 'refunded'){ return 'badge-cancelled'; }
    return 'badge-unpaid';
  }
  function requestData(){
    var s = ctx();
    return {
      action: 'menuqr_get_customer_bill',
      nonce: window.menuqr_ajax && window.menuqr_ajax.nonce,
      restaurant_id: s.restaurantId,
      table_id: s.tableId,
      room_id: s.roomId,
      order_source: s.orderSource,
      bill_session_token: token(),
      order_id: lastOrderId(),
      _t: Date.now()
    };
  }
  function fetchBill(){ return $.ajax({ url: window.menuqr_ajax.ajax_url, method: 'POST', cache: false, data: requestData() }); }
  function hideMenuForPanel(){
    $('#v-menu').removeClass('active is-active').attr({'hidden':'hidden','aria-hidden':'true'}).css('display','none');
    $('#menuqr-bill-history').attr('hidden','hidden').empty().css('display','none');
    $('#m-items-grid,#m-cat-strip').removeAttr('hidden').css('display','');
  }
  function restoreMenu(){
    $('#menuqr-order-status-wrap,#menuqr-bill-history').attr('hidden','hidden').removeClass('active is-active fqx-v131-status-open').css('display','none').attr('aria-hidden','true');
    $('body').removeClass('fqx-v131-tracker-open fqx-v130-tracker-open');
    $('#v-menu').removeAttr('hidden').addClass('active is-active').css('display','block').attr('aria-hidden','false');
    $('#m-items-grid,#m-cat-strip').removeAttr('hidden').css('display','');
    window.requestAnimationFrame(function(){ window.scrollTo({top:0, behavior:'auto'}); });
  }
  function scrollToTrackerTop(){
    var wrap = document.getElementById('menuqr-order-status-wrap');
    if(!wrap){ return; }
    var top = Math.max(0, wrap.getBoundingClientRect().top + window.pageYOffset - 2);
    window.scrollTo({top: top, behavior:'auto'});
  }
  function showTrackerShell(){
    hideMenuForPanel();
    var wrap = $('#menuqr-order-status-wrap');
    wrap.removeAttr('hidden').addClass('active is-active fqx-v130-status-open fqx-v131-status-open').css('display','block').attr('aria-hidden','false');
    $('body').addClass('fqx-v131-tracker-open fqx-v130-tracker-open');
    $('#st-current').text('Running Order');
    $('#st-steps').empty();
    window.requestAnimationFrame(scrollToTrackerTop);
  }
  function stepsHtml(status){
    var steps = ['pending','accepted','preparing','ready','served'];
    var current = Math.max(0, steps.indexOf(String(status || 'pending')));
    return '<div class="status-steps fqx-v130-steps fqx-v131-steps">' + steps.map(function(step, i){
      var cls = i < current ? 'done' : (i === current ? 'active' : '');
      var connector = i < steps.length - 1 ? '<div class="step-connector ' + (i < current ? 'done' : '') + '"></div>' : '';
      return '<div class="step-block"><div class="step-circle ' + cls + '">' + (i + 1) + '</div><div class="step-label">' + esc(step) + '</div>' + connector + '</div>';
    }).join('') + '</div>';
  }
  function itemRows(order){
    var items = order.items || [];
    return items.map(function(item){ return '<div class="sum-row"><span>' + esc(item.emoji || '🍽️') + ' ' + esc(item.name || 'Item') + ' × ' + esc(item.qty || 1) + '</span><span>' + money(Number(item.total || 0) || (Number(item.price || 0) * Number(item.qty || 1))) + '</span></div>'; }).join('') || '<p class="text-muted">No items found.</p>';
  }
  function signature(data){
    try {
      var simple = {
        bill: data && data.bill ? {id:data.bill.id,status:data.bill.payment_status,total:data.bill.grand_total} : null,
        orders: (data && $.isArray(data.orders) ? data.orders : []).map(function(o){ return {id:o.id,status:o.order_status,pay:o.payment_status,bpay:o.bill_payment_status,total:o.final_total}; })
      };
      return JSON.stringify(simple);
    } catch(e){ return String(Date.now()); }
  }
  function renderTracker(data, opts){
    opts = opts || {};
    var bill = data && data.bill ? data.bill : null;
    var orders = data && $.isArray(data.orders) ? data.orders : [];
    var billStatus = bill ? normalizeStatus(bill.payment_status || 'unpaid') : 'unpaid';
    var billUrl = data && (data.bill_direct_url || data.bill_url) ? String(data.bill_direct_url || data.bill_url) : '';

    $('#st-current').text('Running Order');
    $('#st-steps').empty();

    if(!orders.length){
      $('#st-items').html('<div class="fqx-v130-empty fqx-v131-empty"><span>📍</span><h4>No running order found</h4><p>Your order tracker stays available for 4 hours after placing an order.</p></div>');
      $('#st-details').html('');
      return;
    }

    $('#st-items').html(orders.map(function(order, index){
      var orderNo = Number(order.order_index || (index + 1));
      var orderStatus = normalizeStatus(order.order_status || 'pending');
      var pay = normalizeStatus(order.bill_payment_status || billStatus || order.payment_status || 'unpaid');
      var shownStatus = visibleOrderLabel(orderStatus);
      return '<article class="fqx-v130-order-card fqx-v131-order-card ' + (isDoneStatus(orderStatus) ? 'is-served' : 'is-running') + '">' +
        '<div class="fqx-v130-order-head fqx-v131-order-head"><div><strong>Order ' + orderNo + '</strong><span>' + esc(order.unique_code || ('MQR-' + order.id)) + '</span></div><b class="badge ' + orderBadgeClass(orderStatus) + '">' + esc(shownStatus) + '</b></div>' +
        stepsHtml(orderStatus) +
        '<div class="fqx-v130-order-items">' + itemRows(order) + '</div>' +
        '<div class="fqx-v130-order-meta"><div><span>Order Total</span><strong>' + money(order.final_total || 0) + '</strong></div><div><span>Bill Status</span><strong class="badge ' + badgeClass(pay) + '">' + esc(label(pay)) + '</strong></div></div>' +
      '</article>';
    }).join(''));

    $('#st-details').html('<div class="fqx-v130-bill-card fqx-v131-bill-card"><div><span>Running Bill Total</span><strong>' + money(bill ? bill.grand_total : 0) + '</strong></div><div><span>Payment Status</span><strong class="badge ' + badgeClass(billStatus) + '">' + esc(label(billStatus)) + '</strong></div>' + (billUrl ? '<a class="btn btn-primary btn-full" target="_blank" rel="noopener" href="' + esc(billUrl) + '">🧾 Open Real Bill</a>' : '') + '</div>');
  }
  function applyPayload(data, opts){
    opts = opts || {};
    lastTrackerPayload = data || {};
    try { writeJSON(payloadKey(), {createdAt:Date.now(), data:lastTrackerPayload}); } catch(e){}
    var sig = signature(lastTrackerPayload);
    if(opts.silent && sig === lastTrackerSignature){ return; }
    lastTrackerSignature = sig;
    var y = window.pageYOffset;
    renderTracker(lastTrackerPayload, opts);
    if(opts.silent){ window.scrollTo({top:y, behavior:'auto'}); }
  }
  function openTracker(){
    showTrackerShell();
    var cached = cleanExpired(payloadKey());
    if(cached && cached.data){ applyPayload(cached.data, {silent:false}); }
    else { $('#st-items').html('<div class="section-card fqx-v130-loading fqx-v131-loading">Loading running order…</div>'); $('#st-details').html(''); }
    if(fetchingTracker){ return; }
    fetchingTracker = true;
    fetchBill().done(function(resp){ applyPayload(resp && resp.success ? (resp.data || {}) : {}, {silent:false}); }).fail(function(){ if(!cached){ $('#st-items').html('<div class="section-card">Tracking refresh failed. Please try again.</div>'); } }).always(function(){ fetchingTracker = false; });
  }
  function refreshTrackerSilent(){
    if(!$('#menuqr-order-status-wrap').is(':visible') || fetchingTracker){ return; }
    fetchingTracker = true;
    fetchBill().done(function(resp){ if(resp && resp.success){ applyPayload(resp.data || {}, {silent:true}); } }).always(function(){ fetchingTracker = false; });
  }

  function renderBill(data){
    var box = $('#menuqr-bill-history');
    if(!box.length){ return; }
    if(!data || !data.bill){
      box.removeAttr('hidden').css('display','block').html('<div class="section-card"><div class="section-title">Your Running Bill</div><div class="fqx-v130-empty"><span>🧾</span><h4>No running bill yet</h4><p>Place an order and your bill will appear here.</p></div><button class="btn btn-outline btn-full" type="button" id="menuqr-close-bill-history">Back to Menu</button></div>');
      return;
    }
    var bill = data.bill;
    var session = data.session || {};
    var orders = data.orders || [];
    var status = normalizeStatus(bill.payment_status || 'unpaid');
    var due = (status === 'paid' || status === 'refunded' || status === 'cancelled') ? 0 : Number(bill.grand_total || 0);
    var items = [];
    try { items = bill.items_snapshot ? (typeof bill.items_snapshot === 'string' ? JSON.parse(bill.items_snapshot || '[]') : bill.items_snapshot) : []; } catch(e){ items = []; }
    var itemRowsHtml = (items || []).map(function(item){ return '<div class="bill-mini-item"><span>' + esc(item.emoji || '🍽️') + ' ' + esc(item.name || 'Item') + ' ×' + Number(item.qty || 1) + '</span><strong>' + money(item.total || 0) + '</strong></div>'; }).join('');
    box.removeAttr('hidden').css('display','block').html('<div class="section-card running-bill-card fqx-v130-bill-history-card">' +
      '<div class="section-title"><span>🧾 Your 4-Hour Running Bill</span><span class="badge ' + badgeClass(status) + '">' + esc(label(status)) + '</span></div>' +
      '<div class="bill-mini-meta"><div><strong>Bill:</strong> ' + esc(bill.bill_number || '-') + '</div><div><strong>Orders:</strong> ' + orders.length + '</div><div><strong>Fresh:</strong> ' + esc(data.fresh_at || '') + '</div></div>' +
      '<div class="bill-mini-items">' + (itemRowsHtml || '<p class="text-muted">No items yet.</p>') + '</div>' +
      '<div class="bill-mini-total"><span>Total</span><strong>' + money(bill.grand_total || 0) + '</strong></div>' +
      '<div class="bill-mini-total fqx-v130-due-line"><span>Due</span><strong>' + money(due) + '</strong></div>' +
      '<div class="bill-mini-total fqx-v130-pay-line"><span>Payment Status</span><strong class="badge ' + badgeClass(status) + '">' + esc(label(status)) + '</strong></div>' +
      '<div class="form-row" style="margin-top:12px;"><input class="form-input" id="bill-customer-name" placeholder="Name" value="' + esc(session.customer_name || '') + '"><input class="form-input" id="bill-customer-whatsapp" placeholder="WhatsApp number" value="' + esc(session.customer_whatsapp || '') + '"></div>' +
      '<div class="mq-actions-center" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;"><button class="btn btn-primary" id="menuqr-save-bill-customer" type="button">Save WhatsApp</button>' +
      (data.bill_url ? '<a class="btn btn-outline" target="_blank" rel="noopener" href="' + esc(data.bill_url) + '">Open Bill</a>' : '') +
      (data.print_url ? '<a class="btn btn-teal" target="_blank" rel="noopener" href="' + esc(data.print_url) + '">Print Bill</a>' : '') +
      '<button class="btn btn-ghost" type="button" id="menuqr-close-bill-history">Back to Menu</button></div></div>');
  }
  function openBill(){
    $('#menuqr-order-status-wrap').attr('hidden','hidden').removeClass('active is-active fqx-v131-status-open').css('display','none');
    $('#v-menu').removeAttr('hidden').addClass('active is-active').css('display','block');
    $('#m-items-grid,#m-cat-strip').attr('hidden','hidden').css('display','none');
    $('#menuqr-bill-history').removeAttr('hidden').css('display','block').html('<div class="section-card">Loading latest bill status…</div>');
    fetchBill().done(function(resp){ renderBill(resp && resp.success ? resp.data : null); }).fail(function(){ renderBill(null); });
    window.requestAnimationFrame(function(){ var el = document.getElementById('menuqr-bill-history'); if(el){ window.scrollTo({top: Math.max(0, el.getBoundingClientRect().top + window.pageYOffset - 2), behavior:'auto'}); } });
  }

  document.addEventListener('click', function(e){
    var track = e.target && e.target.closest ? e.target.closest('#menuqr-header-track-order') : null;
    if(track){ e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); writeJSON(trackerKey(), {active:true, createdAt:Date.now()}); openTracker(); return false; }
    var bill = e.target && e.target.closest ? e.target.closest('#menuqr-header-view-bill') : null;
    if(bill){ e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); openBill(); return false; }
  }, true);

  $(document).off('click.fqxV131BackMenu', '[data-menuqr-back="menu"], #menuqr-close-bill-history').on('click.fqxV131BackMenu', '[data-menuqr-back="menu"], #menuqr-close-bill-history', function(e){ e.preventDefault(); e.stopImmediatePropagation(); restoreMenu(); });
  $(document).off('click.fqxV131Refresh', '#menuqr-status-refresh').on('click.fqxV131Refresh', '#menuqr-status-refresh', function(e){ e.preventDefault(); refreshTrackerSilent(); });

  $(document).ajaxSuccess(function(_event, _xhr, settings, response){
    try {
      var data = response && response.data ? response.data : null;
      if(data && data.order_id){ saveOrderId(data.order_id); }
      if(data && data.order && data.order.id){ saveOrderId(data.order.id); }
    } catch(e){}
  });

  $(function(){
    cleanExpired(trackerKey()); cleanExpired(ordersKey()); cleanExpired(latestOrderKey()); cleanExpired(payloadKey());
    window.setInterval(function(){ refreshTrackerSilent(); if($('#menuqr-bill-history').is(':visible')){ fetchBill().done(function(resp){ if(resp && resp.success){ renderBill(resp.data || null); } }); } }, 12000);
  });
})(jQuery);
