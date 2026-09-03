(function($){
  'use strict';

  function param(name){ try { return String(new URLSearchParams(window.location.search || '').get(name) || '').trim(); } catch(e){ return ''; } }
  function num(v){ var n = Number(String(v || '').replace(/\D+/g,'')); return n > 0 ? n : 0; }
  function app(){ return $('#menuqr-customer-app'); }
  function service(){
    var root = app();
    var declared = String(root.attr('data-order-source') || root.data('order-source') || param('source') || param('order_source') || '').toLowerCase();
    var restaurantId = num(root.data('restaurant-id')) || num(param('r')) || num(param('restaurant_id'));
    var roomId = num(root.data('room-id')) || num(param('room_id')) || num(param('room')) || num(param('room_no')) || num(param('room_number'));
    var tableId = num(root.data('table-id')) || num(param('table_id')) || num(param('t')) || num(param('table')) || num(param('table_no')) || num(param('table_number'));
    if(declared === 'room' || declared === 'room_qr' || declared === 'hotel_room'){ tableId = 0; }
    else if(declared === 'table' || declared === 'table_qr'){ roomId = 0; }
    else if(roomId > 0){ tableId = 0; }
    return { restaurantId: restaurantId, tableId: tableId, roomId: roomId, orderSource: roomId > 0 ? 'room_qr' : 'table_qr', serviceId: roomId > 0 ? roomId : tableId };
  }
  function storageKey(s){ s = s || service(); return 'menuqr_bill_session_' + s.restaurantId + '_' + (s.roomId > 0 ? 'room' : 'table') + '_' + s.serviceId; }
  function randomToken(){
    if(window.crypto && window.crypto.getRandomValues){ var b = new Uint8Array(24); window.crypto.getRandomValues(b); return Array.prototype.map.call(b, function(x){ return x.toString(16).padStart(2,'0'); }).join(''); }
    return 'mqr_' + Date.now() + '_' + Math.random().toString(36).slice(2);
  }
  function token(s){
    s = s || service();
    var key = storageKey(s), saved = null, now = Date.now();
    try { saved = JSON.parse(localStorage.getItem(key) || 'null'); } catch(e){}
    if(saved && saved.token && saved.createdAt && (now - Number(saved.createdAt)) < (4*60*60*1000)){ return String(saved.token); }
    var t = randomToken();
    try { localStorage.setItem(key, JSON.stringify({token:t, createdAt:now})); } catch(e){}
    return t;
  }
  function latestKey(s){ return storageKey(s) + '_v123_latest_bill'; }
  function latestOrderKey(s){ return storageKey(s) + '_v123_last_order'; }
  function getData(payload){ return payload && payload.data ? payload.data : payload; }
  function billUrl(payload){
    var d = getData(payload) || {};
    if(d.bill_direct_url){ return String(d.bill_direct_url); }
    if(d.bill_url){ return String(d.bill_url); }
    if(d.bill_session_url){ return String(d.bill_session_url); }
    if(d.order && d.order.bill_url){ return String(d.order.bill_url); }
    return '';
  }
  function saveBillUrl(url){ if(!url){ return; } try { localStorage.setItem(latestKey(service()), JSON.stringify({url:String(url), savedAt:Date.now()})); } catch(e){} }
  function saveOrderId(orderId){ orderId = num(orderId); if(!orderId){ return; } try { localStorage.setItem(latestOrderKey(service()), JSON.stringify({orderId:orderId, savedAt:Date.now()})); } catch(e){} }
  function readOrderId(){ var saved = null; try { saved = JSON.parse(localStorage.getItem(latestOrderKey(service())) || 'null'); } catch(e){} return saved && saved.orderId && (Date.now() - Number(saved.savedAt || 0)) < (4*60*60*1000) ? Number(saved.orderId) : 0; }
  function readBillUrl(){ var saved = null; try { saved = JSON.parse(localStorage.getItem(latestKey(service())) || 'null'); } catch(e){} return saved && saved.url && (Date.now() - Number(saved.savedAt || 0)) < (4*60*60*1000) ? String(saved.url) : ''; }
  function sessionBillUrl(s, t){
    var base = (window.menuqr_ajax && (window.menuqr_ajax.bill_url || window.menuqr_ajax.bill_page_url)) ? String(window.menuqr_ajax.bill_url || window.menuqr_ajax.bill_page_url) : (window.location.origin + '/bill/');
    var u = new URL(base, window.location.origin);
    u.searchParams.set('r', String(s.restaurantId || 0));
    u.searchParams.set('session', t || token(s));
    u.searchParams.set('order_source', s.orderSource);
    var oid = readOrderId(); if(oid){ u.searchParams.set('order_id', String(oid)); }
    if(s.roomId > 0){ u.searchParams.set('room_id', String(s.roomId)); u.searchParams.delete('t'); u.searchParams.delete('table_id'); }
    else { u.searchParams.set('t', String(s.tableId || 0)); u.searchParams.delete('room_id'); }
    return u.toString();
  }
  function showBillToast(url){
    if(!app().length || !url){ return; }
    $('#fq-v123-view-bill-toast').remove();
    var html = '<div id="fq-v123-view-bill-toast" style="position:fixed;left:12px;right:12px;bottom:14px;z-index:99999;background:linear-gradient(135deg,#ff7a18,#ff3d00);color:#fff;padding:13px 14px;border-radius:18px;box-shadow:0 18px 45px rgba(255,95,0,.35);display:flex;align-items:center;justify-content:space-between;gap:10px;font-family:Inter,system-ui,sans-serif"><b>Real bill generated</b><a href="'+url.replace(/"/g,'&quot;')+'" style="background:#fff;color:#9a3412;border-radius:999px;padding:9px 13px;text-decoration:none;font-weight:900">Open Bill</a></div>';
    $('body').append(html);
    setTimeout(function(){ $('#fq-v123-view-bill-toast').fadeOut(250, function(){ $(this).remove(); }); }, 9000);
  }

  $(document).ajaxSuccess(function(_event, _xhr, settings, response){
    var d = getData(response) || {};
    if(d.bill_session_token){ try { localStorage.setItem(storageKey(service()), JSON.stringify({token:String(d.bill_session_token), createdAt:Date.now()})); } catch(e){} }
    if(d.order_id){ saveOrderId(d.order_id); }
    if(d.order && d.order.id){ saveOrderId(d.order.id); }
    var url = billUrl(response);
    if(url){ saveBillUrl(url); }
    if(settings && settings.data && String(settings.data).indexOf('action=menuqr_place_order') !== -1 && url){ showBillToast(url); }
  });

  function openBill(e){
    if(e){ e.preventDefault(); e.stopPropagation(); if(e.stopImmediatePropagation){ e.stopImmediatePropagation(); } }
    var s = service(), t = token(s), saved = readBillUrl();
    // v128: Do not blindly open a stale saved bill URL. First ask server for the latest real bill
    // for this table/room. This recovers from browser token mismatch and old localStorage.
    if(!(window.menuqr_ajax && window.menuqr_ajax.ajax_url && window.menuqr_ajax.nonce)){ window.location.href = saved || sessionBillUrl(s,t); return false; }
    var btn = e && e.currentTarget ? e.currentTarget : document.getElementById('menuqr-header-view-bill');
    if(btn){ btn.setAttribute('disabled','disabled'); btn.classList.add('is-loading'); }
    $.ajax({
      url: window.menuqr_ajax.ajax_url,
      type: 'POST', cache: false,
      data: { action:'menuqr_get_customer_bill', nonce:window.menuqr_ajax.nonce, restaurant_id:s.restaurantId, table_id:s.tableId, room_id:s.roomId, order_source:s.orderSource, bill_session_token:t, order_id:readOrderId(), _t:Date.now() }
    }).done(function(resp){
      var url = billUrl(resp);
      if(url){ saveBillUrl(url); window.location.href = url; return; }
      if(saved){ window.location.href = saved; return; }
      window.location.href = sessionBillUrl(s,t);
    }).fail(function(){ window.location.href = sessionBillUrl(s,t); })
      .always(function(){ if(btn){ btn.removeAttribute('disabled'); btn.classList.remove('is-loading'); } });
    return false;
  }

  document.addEventListener('click', function(e){
    var target = e.target && e.target.closest ? e.target.closest('#menuqr-header-view-bill, #menuqr-view-bill, .menuqr-header-icon-bill, [data-menuqr-bill]') : null;
    if(target && app().length){ openBill(e); }
  }, true);
  $(document).on('click.fq123RealBill', '#menuqr-header-view-bill, #menuqr-view-bill, .menuqr-header-icon-bill, [data-menuqr-bill]', openBill);
})(jQuery);
