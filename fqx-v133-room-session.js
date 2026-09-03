(function(){
  'use strict';
  function ready(fn){ if(document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  ready(function(){
    var app = document.getElementById('menuqr-customer-app');
    if(!app || (app.dataset.orderSource !== 'room_qr' && app.dataset.orderSource !== 'room')) return;
    var token = app.dataset.roomSessionToken || '';
    var exp = app.dataset.roomSessionExpires || '';
    var rid = app.dataset.restaurantId || '0';
    var room = app.dataset.roomId || '0';
    if(token){
      try{ localStorage.setItem('fqx_room_session_' + rid + '_' + room, JSON.stringify({token:token, expiresAt:exp, savedAt:Date.now()})); }catch(e){}
      try{ localStorage.setItem('menuqr_bill_session_' + rid + '_room_' + room, JSON.stringify({token:token, createdAt:Date.now(), expiresAt:exp})); }catch(e){}
    }
    var subtitle = document.getElementById('m-table-info');
    if(subtitle && !document.querySelector('.fqx-room-session-badge')){
      var badge = document.createElement('div');
      badge.className = 'fqx-room-session-badge';
      badge.textContent = exp ? 'Room Session Active — 24 Hours' : 'Room Session Active';
      subtitle.insertAdjacentElement('afterend', badge);
    }
  });
})();
