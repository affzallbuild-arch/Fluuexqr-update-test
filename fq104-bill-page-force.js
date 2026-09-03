(function($){
  'use strict';

  function readParam(name){
    try { return String(new URLSearchParams(window.location.search || '').get(name) || '').trim(); } catch(e){ return ''; }
  }

  function numberFrom(value){
    var raw = String(value === undefined || value === null ? '' : value).trim();
    var num = Number(raw.replace(/\D+/g, ''));
    return num > 0 ? num : 0;
  }

  function randomToken(){
    if(window.crypto && window.crypto.getRandomValues){
      var bytes = new Uint8Array(24);
      window.crypto.getRandomValues(bytes);
      return Array.prototype.map.call(bytes, function(b){ return b.toString(16).padStart(2, '0'); }).join('');
    }
    return 'mqr_' + Date.now() + '_' + Math.random().toString(36).slice(2);
  }

  function serviceContext(){
    var root = $('#menuqr-customer-app');
    var declared = String(root.attr('data-order-source') || root.data('order-source') || readParam('source') || readParam('order_source') || '').toLowerCase();
    var restaurantId = numberFrom(root.data('restaurant-id')) || numberFrom(readParam('r')) || numberFrom(readParam('restaurant_id'));
    var roomId = numberFrom(root.data('room-id')) || numberFrom(readParam('room_id')) || numberFrom(readParam('room')) || numberFrom(readParam('room_no')) || numberFrom(readParam('room_number'));
    var tableId = numberFrom(root.data('table-id')) || numberFrom(readParam('table_id')) || numberFrom(readParam('t')) || numberFrom(readParam('table')) || numberFrom(readParam('table_no')) || numberFrom(readParam('table_number'));

    if(declared === 'room' || declared === 'room_qr' || declared === 'hotel_room'){
      tableId = 0;
    } else if(declared === 'table' || declared === 'table_qr'){
      roomId = 0;
    } else if(roomId > 0){
      tableId = 0;
    }

    var orderSource = roomId > 0 ? 'room_qr' : 'table_qr';
    var serviceId = roomId > 0 ? roomId : tableId;
    return { restaurantId: restaurantId, roomId: roomId, tableId: tableId, orderSource: orderSource, serviceId: serviceId };
  }

  function storageKey(service){
    service = service || serviceContext();
    var source = service.roomId > 0 ? 'room' : 'table';
    return 'menuqr_bill_session_' + service.restaurantId + '_' + source + '_' + service.serviceId;
  }

  function sessionToken(service){
    var key = storageKey(service);
    var stored = null;
    try { stored = JSON.parse(localStorage.getItem(key) || 'null'); } catch(e){ stored = null; }
    var now = Date.now();
    var maxAge = 4 * 60 * 60 * 1000;
    if(stored && stored.token && stored.createdAt && (now - Number(stored.createdAt)) < maxAge){
      return stored.token;
    }
    var token = randomToken();
    try { localStorage.setItem(key, JSON.stringify({token: token, createdAt: now})); } catch(e){}
    return token;
  }

  function makeSessionBillUrl(service, token){
    var base = (window.menuqr_ajax && window.menuqr_ajax.bill_url) ? String(window.menuqr_ajax.bill_url) : (window.location.origin + '/bill/');
    var url = new URL(base, window.location.origin);
    url.searchParams.set('r', String(service.restaurantId || 0));
    url.searchParams.set('session', token || sessionToken(service));
    url.searchParams.set('order_source', service.orderSource);
    if(service.roomId > 0){
      url.searchParams.set('room_id', String(service.roomId));
      url.searchParams.delete('t');
      url.searchParams.delete('table_id');
    } else {
      url.searchParams.set('t', String(service.tableId || 0));
      url.searchParams.delete('room_id');
    }
    return url.toString();
  }

  function latestBillKey(service){ return storageKey(service) + '_latest_bill'; }

  function saveLatestBill(url){
    if(!url){ return; }
    var service = serviceContext();
    try { localStorage.setItem(latestBillKey(service), JSON.stringify({url: String(url), savedAt: Date.now()})); } catch(e){}
  }

  function readLatestBill(service){
    var saved = null;
    try { saved = JSON.parse(localStorage.getItem(latestBillKey(service)) || 'null'); } catch(e){ saved = null; }
    if(saved && saved.url && saved.savedAt && (Date.now() - Number(saved.savedAt)) < (4 * 60 * 60 * 1000)){
      return String(saved.url);
    }
    return '';
  }

  function extractBillUrl(payload){
    if(!payload){ return ''; }
    var data = payload.data || payload;
    if(data && data.bill_direct_url){ return String(data.bill_direct_url); }
    if(data && data.bill_url){ return String(data.bill_url); }
    if(data && data.order && data.order.bill_url){ return String(data.order.bill_url); }
    return '';
  }

  $(document).ajaxSuccess(function(_event, _xhr, _settings, response){
    var url = extractBillUrl(response);
    if(url){ saveLatestBill(url); }
  });

  function openRealBill(event){
    if(event){
      event.preventDefault();
      event.stopPropagation();
      if(event.stopImmediatePropagation){ event.stopImmediatePropagation(); }
    }

    var service = serviceContext();
    var token = sessionToken(service);
    var savedUrl = readLatestBill(service);
    if(savedUrl){
      window.location.href = savedUrl;
      return false;
    }

    if(!(window.menuqr_ajax && window.menuqr_ajax.ajax_url && window.menuqr_ajax.nonce)){
      window.location.href = makeSessionBillUrl(service, token);
      return false;
    }

    var btn = event && event.currentTarget ? event.currentTarget : document.getElementById('menuqr-header-view-bill');
    if(btn){ btn.setAttribute('disabled', 'disabled'); btn.classList.add('is-loading'); }

    $.ajax({
      url: window.menuqr_ajax.ajax_url,
      type: 'POST',
      cache: false,
      data: {
        action: 'menuqr_get_customer_bill',
        nonce: window.menuqr_ajax.nonce,
        restaurant_id: service.restaurantId,
        table_id: service.tableId,
        room_id: service.roomId,
        order_source: service.orderSource,
        bill_session_token: token,
        _t: Date.now()
      }
    }).done(function(response){
      var url = extractBillUrl(response);
      if(url){
        saveLatestBill(url);
        window.location.href = url;
        return;
      }
      window.location.href = makeSessionBillUrl(service, token);
    }).fail(function(){
      window.location.href = makeSessionBillUrl(service, token);
    }).always(function(){
      if(btn){ btn.removeAttribute('disabled'); btn.classList.remove('is-loading'); }
    });

    return false;
  }

  document.addEventListener('click', function(e){
    var target = e.target && e.target.closest ? e.target.closest('#menuqr-header-view-bill, #menuqr-view-bill, .menuqr-header-icon-bill') : null;
    if(target && document.getElementById('menuqr-customer-app')){
      openRealBill(e);
    }
  }, true);

  $(function(){
    $(document).off('click', '#menuqr-view-bill, #menuqr-header-view-bill, .menuqr-header-icon-bill');
    $(document).on('click.fq107RealBill', '#menuqr-view-bill, #menuqr-header-view-bill, .menuqr-header-icon-bill', openRealBill);
    var billButton = document.getElementById('menuqr-header-view-bill');
    if(billButton){
      billButton.setAttribute('type', 'button');
      billButton.setAttribute('data-bill-page', '1');
      billButton.setAttribute('data-real-bill-page', '1');
    }
  });
})(jQuery);
