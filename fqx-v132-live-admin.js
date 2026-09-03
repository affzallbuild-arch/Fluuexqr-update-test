(function($){
  'use strict';
  function tab(){ try{return new URL(window.location.href).searchParams.get('tab') || 'overview';}catch(e){return 'overview';} }
  function findOrdersBody(){
    var t = tab();
    if(t === 'orders') return $('.section-card table.data-table tbody').first();
    if(t === 'overview' || t === 'dashboard') return $('.section-card:contains("Recent Orders") table.data-table tbody').first();
    return $();
  }
  function findBillsBody(){
    if(tab() !== 'bills') return $();
    return $('.section-card:contains("Running Bills") table.data-table tbody').first();
  }
  function sameHtml($el, html){ return $.trim($el.html()) === $.trim(html); }
  function updateStat(title, value){
    $('.card').each(function(){ var $c=$(this); if($.trim($c.find('.card-title').first().text())===title){ $c.find('.card-value').first().text(value); }});
  }
  function refresh(){
    if(!window.menuqr_ajax || !menuqr_ajax.ajax_url || !menuqr_ajax.nonce) return;
    var $shell = $('.dashboard-shell,.app-shell').first();
    if(!$shell.length) return;
    $.ajax({
      url: menuqr_ajax.ajax_url,
      method: 'POST',
      dataType: 'json',
      data: {action:'fqx_v132_restaurant_live_snapshot', nonce:menuqr_ajax.nonce},
      cache: false
    }).done(function(res){
      if(!res || !res.success || !res.data) return;
      var $orders = findOrdersBody();
      if($orders.length && res.data.orders_html && !sameHtml($orders, res.data.orders_html)){
        $orders.html(res.data.orders_html).addClass('fqx-live-updated');
        setTimeout(function(){ $orders.removeClass('fqx-live-updated'); }, 900);
      }
      var $bills = findBillsBody();
      if($bills.length && res.data.bills_html && !sameHtml($bills, res.data.bills_html)){
        $bills.html(res.data.bills_html).addClass('fqx-live-updated');
        setTimeout(function(){ $bills.removeClass('fqx-live-updated'); }, 900);
      }
      if(res.data.counts){
        updateStat("Today's Orders", String(res.data.counts.today_orders));
        updateStat('Pending Orders', String(res.data.counts.pending_orders));
        updateStat('Revenue', String(res.data.counts.revenue));
        updateStat("Today's Revenue", String(res.data.counts.today_revenue));
      }
      $('.fqx-live-dot').remove();
      $('.topbar-title').append('<small class="fqx-live-dot"> live</small>');
    });
  }
  $(function(){
    if(!$('.dashboard-shell,.app-shell').length) return;
    refresh();
    window.setInterval(refresh, 4000);
    $(document).on('submit', '.fqx-live-order-form', function(){ setTimeout(refresh, 900); });
  });
})(jQuery);
