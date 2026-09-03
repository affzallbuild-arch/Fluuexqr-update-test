(function(){'use strict';
  function ready(fn){document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn):fn();}
  ready(function(){
    document.body.classList.add('fqx-home-v112-restored');
    document.querySelectorAll('.sr').forEach(function(el){el.classList.add('up');});
    // If the original fq91 renderer failed to run, make sure the homepage is not empty.
    var pricing=document.getElementById('pricingGrid');
    if(pricing && !pricing.children.length){
      var plans=[
        ['Free Trial','₹0','/10 Days','Full access trial for new restaurants',['10-day free trial','All features ON during trial','No payment required','Trial only once','Upgrade required after expiry'],'Start 10-Day Trial','ghost',''],
        ['Starter 5 Table','₹999','/month','For small restaurants starting digital ordering',['5 tables','5 categories','20 menu items','2 staff users','No Room QR'],'Choose Starter','ghost',''],
        ['Restaurant All Access','₹1,999','/month','For restaurants, cafes, dhabas and cloud kitchens',['Table QR ordering','QR menu, cart and checkout','Kitchen display and live orders','Running/PDF/Thermal bill','WhatsApp bill + UPI/Razorpay','Reviews, coupons, combos, reports'],'Choose Restaurant Plan','fire','⭐ Most Popular'],
        ['Hotel + Restaurant Full Access','₹2,499','/month','For hotels, resorts and restaurants with rooms',['Everything in Restaurant plan','Hotel Room QR ordering','Room-wise bill','Room number tracking in kitchen','Table + Room QR combined','Priority support badge'],'Choose Hotel Plan','ghost','']
      ];
      pricing.innerHTML=plans.map(function(p){return '<div class="price-card '+(p[7]?'pop':'')+' sr up">'+(p[7]?'<div class="pop-badge">'+p[7]+'</div>':'')+'<div class="price-name">'+p[0]+'</div><div class="price-amt"><sup></sup>'+p[1]+'<sub>'+p[2]+'</sub></div><div class="price-note">'+p[3]+'</div><hr class="price-hr"><ul class="price-feats">'+p[4].map(function(f){return '<li><span class="pf-check">✓</span>'+f+'</li>';}).join('')+'</ul><a href="/signup/" class="btn btn-'+p[6]+'" style="width:100%;border-radius:12px">'+p[5]+'</a></div>';}).join('');
    }
    var m=document.getElementById('mtrack');
    if(m && !m.children.length){['QR Menu Ordering','Kitchen Display','Smart Billing','UPI Payments','WhatsApp Bill','Hotel Room QR','Table Ordering','Paid/Unpaid Billing'].forEach(function(t){var d=document.createElement('div');d.className='marquee-item';d.innerHTML='<b>'+t+'</b><span class="mdot"></span>';m.appendChild(d);});}
  });
})();
