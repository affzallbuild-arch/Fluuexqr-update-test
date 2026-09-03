(function(){
  'use strict';
  function ready(fn){ if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn,{once:true});}else{fn();} }
  ready(function(){
    const FEATURES = [
      {ico:'📱',title:'QR Menu Ordering',desc:'Customers scan the table QR, browse your full menu with photos, and order from their phone. Zero app download needed.',badge:''},
      {ico:'🏨',title:'Hotel Room QR',desc:'In-room dining QR for hotels. Guests scan and order directly — room number auto-tagged on every kitchen order.',badge:''},
      {ico:'🍽️',title:'Table QR Ordering',desc:'Unique QR per table. Orders auto-tagged by table number. No mix-ups, maximum kitchen efficiency.',badge:''},
      {ico:'👨‍🍳',title:'Kitchen Display System',desc:'Live order screen for kitchen. Real-time status: Pending → Preparing → Ready. No paper tickets.',badge:''},
      {ico:'🧾',title:'Running Bills',desc:'Customers see their live order total at any time. Perfect for group dining and large family tables.',badge:''},
      {ico:'🖨️',title:'Thermal Invoice',desc:'Print professional GST-ready invoices in seconds. Works with any thermal printer, fully branded.',badge:''},
      {ico:'💳',title:'UPI Payments',desc:'Accept Google Pay, PhonePe, Paytm at table. No extra hardware — works from customer phone.',badge:'New'},
      {ico:'💬',title:'WhatsApp Billing',desc:'Send invoice to customer WhatsApp instantly. Paperless, eco-friendly, customers love it.',badge:''},
      {ico:'🔔',title:'Waiter Calling',desc:'Customers call waiter digitally from QR menu. Staff notified on device — no shouting needed.',badge:''},
      {ico:'🚴',title:'Delivery Tracking',desc:'Assign delivery agents, track live status, send WhatsApp updates. All from one dashboard.',badge:''},
      {ico:'🏢',title:'Multi-Branch Management',desc:'Manage multiple restaurants or locations with separate menus, orders, reports from one panel.',badge:''},
      {ico:'📊',title:'Analytics Dashboard',desc:'Revenue, top dishes, peak hours, table performance — visual charts, accessible from any device.',badge:''},
      {ico:'🧮',title:'Restaurant Billing POS',desc:'Complete POS system — cash, card, UPI, split bills, discounts, GST — all handled automatically.',badge:''}
    ];
    const COMPARE = [
      ['Ordering Speed','⚡ Instant from customer phone','❌ Slow — waiter takes manually'],
      ['Billing Errors','✓ Near-zero — auto-calculated','❌ Frequent — manual mistakes'],
      ['Menu Update Cost','✓ Free — update anytime online','❌ Reprinting cost each time'],
      ['Kitchen Communication','✓ Live digital display — instant','❌ Paper tickets — error-prone'],
      ['UPI / Digital Payment','✓ Built-in — all UPI apps','❌ Separate POS device needed'],
      ['WhatsApp Invoice','✓ One-click WhatsApp bill','❌ Not possible'],
      ['Revenue Analytics','✓ Full real-time dashboard','❌ Manual counting only'],
      ['Multi-Branch Control','✓ All locations in one panel','❌ Managed separately each'],
      ['Contactless Hygiene','✓ 100% phone-based — clean','❌ Shared paper — hygiene risk'],
      ['Customer Feedback','✓ Auto post-meal rating','❌ No feedback system'],
      ['Monthly Cost','✓ ₹999/month flat','❌ Printing + errors cost ₹5,000+']
    ];
    const TESTIMONIALS = [
      {stars:5,text:'FluuexQR completely changed how my restaurant in Purnia operates. Earlier we had billing errors daily. Now auto-generated bills, customers pay via PhonePe themselves. Revenue up 30% in the first month.',name:'Ramesh Kumar',biz:'Sher-e-Punjab, Purnia',init:'RK'},
      {stars:5,text:'Our hotel near Katihar Junction now has QR ordering in every room. Guests are impressed — they mention it in Booking.com reviews. Setup was done in 25 minutes with Hindi support on WhatsApp.',name:'Anjali Singh',biz:'Hotel Anand Palace, Katihar',init:'AS'},
      {stars:5,text:'I manage 3 restaurants in Bihar and Delhi from one FluuexQR dashboard. The multi-branch feature is exactly what I needed. Separate analytics per location, one subscription. Highly recommend.',name:'Mohammed Hassan',biz:'Taj Kitchen — 3 Locations',init:'MH'},
      {stars:5,text:'The kitchen display system is the best part. My kitchen staff was confused with paper tickets. Now they see everything on screen — Pending, Preparing, Ready. Service is faster and errors are near zero.',name:'Priya Tiwari',biz:'Spice Garden, Saharsa',init:'PT'},
      {stars:5,text:'We are a cloud kitchen with 2 brands. FluuexQR handles both separately with different menus. Delivery tracking and WhatsApp billing make customers very happy. Professional platform at low price.',name:'Suraj Mandal',biz:'Foodie Hub Cloud Kitchen',init:'SM'},
      {stars:5,text:'Previously I spent ₹3,000/month just on printing new menus when prices changed. With FluuexQR I update the menu from my phone in 2 minutes for free. Best investment I made for my restaurant.',name:'Kavita Devi',biz:'Devi Bhojnalaya, Patna',init:'KD'}
    ];
    const PRICING = [
      {name:'Free Trial',price:'0',unit:'/mo',desc:'Test FluuexQR risk-free',feats:['Up to 50 orders/month','Basic QR menu','1 restaurant','Email support','Basic analytics'],cta:'Start Free Trial',style:'ghost',pop:false},
      {name:'Basic',price:'999',unit:'/mo',desc:'For growing restaurants',feats:['500 orders/month','Kitchen Display System','WhatsApp invoices','UPI payment integration','3 restaurant locations','Waiter call feature','Priority support'],cta:'Get Started',style:'fire',pop:true},
      {name:'Premium',price:'1999',unit:'/mo',desc:'For serious restaurant businesses',feats:['Unlimited orders','All Basic features','Full analytics dashboard','Multi-branch management','Hotel room QR ordering','Delivery tracking','Dedicated account manager','Custom branding & API'],cta:'Choose Premium',style:'ghost',pop:false}
    ];
    const MARQUEE_ITEMS = ['📱 QR Menu Ordering','👨‍🍳 Kitchen Display','🧾 Smart Billing','💳 UPI Payments','💬 WhatsApp Invoice','🏨 Hotel Room QR','🍽️ Table Ordering','🔔 Waiter Calling','📊 Analytics','🚴 Delivery Tracking','🏢 Multi-Branch','🖨️ Thermal Printing'];

    const esc = function(s){return String(s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});};
    const mtrack = document.getElementById('mtrack');
    if(mtrack){
      const dupItems = MARQUEE_ITEMS.concat(MARQUEE_ITEMS);
      mtrack.innerHTML = dupItems.map(function(i){return '<div class="marquee-item"><b>'+esc(i.split(' ').slice(1).join(' '))+'</b><span class="mdot"></span></div>';}).join('');
      mtrack.classList.add('fq-home-ready');
    }
    const featGrid = document.getElementById('featGrid');
    if(featGrid){featGrid.innerHTML = FEATURES.map(function(f,i){return '<div class="feat-card sr d'+((i%4)+1)+'">'+(f.badge?'<div class="feat-new">'+esc(f.badge)+'</div>':'')+'<div class="feat-ico">'+f.ico+'</div><h3>'+esc(f.title)+'</h3><p>'+esc(f.desc)+'</p></div>';}).join('');}
    const cmpBody = document.getElementById('cmpBody');
    if(cmpBody){cmpBody.innerHTML = COMPARE.map(function(r){return '<tr><td>'+esc(r[0])+'</td><td class="hi">'+esc(r[1])+'</td><td class="lo">'+esc(r[2])+'</td></tr>';}).join('');}
    const testiGrid = document.getElementById('testiGrid');
    if(testiGrid){testiGrid.innerHTML = TESTIMONIALS.map(function(t,i){return '<div class="testi-card sr d'+((i%3)+1)+'"><div class="t-stars">'+'★'.repeat(t.stars)+'</div><p class="t-text">&quot;'+esc(t.text)+'&quot;</p><div class="t-author"><div class="t-av">'+esc(t.init)+'</div><div class="t-meta"><b>'+esc(t.name)+'</b><span>'+esc(t.biz)+'</span></div></div></div>';}).join('');}
    const pricingGrid = document.getElementById('pricingGrid');
    if(pricingGrid){pricingGrid.innerHTML = PRICING.map(function(p){return '<div class="price-card'+(p.pop?' pop':'')+' sr">'+(p.pop?'<div class="pop-badge">⭐ Most Popular</div>':'')+'<div class="price-name">'+esc(p.name)+'</div><div class="price-amt"><sup>₹</sup>'+esc(p.price)+'<sub>'+esc(p.unit)+'</sub></div><div class="price-note">'+esc(p.desc)+'</div><hr class="price-hr"><ul class="price-feats">'+p.feats.map(function(f){return '<li><span class="pf-check">✓</span>'+esc(f)+'</li>';}).join('')+'</ul><a href="/signup/" class="btn btn-'+esc(p.style)+'" style="width:100%;border-radius:12px">'+esc(p.cta)+'</a></div>';}).join('');}

    const nav = document.getElementById('nav');
    if(nav && !nav.dataset.fq180Scroll){nav.dataset.fq180Scroll='1'; window.addEventListener('scroll',function(){nav.classList.toggle('scrolled',window.scrollY>60);},{passive:true});}
    const ham = document.getElementById('ham');
    const mNav = document.getElementById('mobileNav');
    if(ham && mNav && !ham.dataset.fq180Bound){
      ham.dataset.fq180Bound='1';
      ham.addEventListener('click',function(){const o=mNav.classList.toggle('show');ham.classList.toggle('open',o);ham.setAttribute('aria-expanded',o?'true':'false');});
      document.addEventListener('click',function(e){if(mNav.classList.contains('show')&&!mNav.contains(e.target)&&!ham.contains(e.target)){mNav.classList.remove('show');ham.classList.remove('open');ham.setAttribute('aria-expanded','false');}});
    }
    document.querySelectorAll('.tab').forEach(function(t){if(t.dataset.fq180Tab)return;t.dataset.fq180Tab='1';t.addEventListener('click',function(){document.querySelectorAll('.tab').forEach(function(x){x.classList.remove('on');x.setAttribute('aria-selected','false');});document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.remove('on');});t.classList.add('on');t.setAttribute('aria-selected','true');var panel=document.getElementById('panel-'+t.dataset.tab);if(panel)panel.classList.add('on');});});

    if('IntersectionObserver' in window){
      const io = new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('up');io.unobserve(e.target);}});},{threshold:.1,rootMargin:'0px 0px -30px 0px'});
      document.querySelectorAll('.sr').forEach(function(el){io.observe(el);});
      const co = new IntersectionObserver(function(entries){entries.forEach(function(e){if(!e.isIntersecting)return; const el=e.target,target=+el.dataset.to||0,dur=1800,step=target/(dur/16);let cur=0; const timer=setInterval(function(){cur=Math.min(cur+step,target);el.textContent=Math.floor(cur);if(cur>=target)clearInterval(timer);},16); co.unobserve(el);});},{threshold:.35});
      document.querySelectorAll('.cnt').forEach(function(el){co.observe(el);});
    } else {
      document.querySelectorAll('.sr').forEach(function(el){el.classList.add('up');});
      document.querySelectorAll('.cnt').forEach(function(el){el.textContent=el.dataset.to||'0';});
    }
    document.querySelectorAll('.ps-cat').forEach(function(c){if(c.dataset.fq180Cat)return;c.dataset.fq180Cat='1';c.addEventListener('click',function(){document.querySelectorAll('.ps-cat').forEach(function(x){x.classList.remove('active');});c.classList.add('active');});});
    document.querySelectorAll('.ps-add').forEach(function(btn){if(btn.dataset.fq180Add)return;btn.dataset.fq180Add='1';btn.addEventListener('click',function(){this.textContent='✓';this.style.background='var(--green)';setTimeout(()=>{this.textContent='+';this.style.background='var(--f)';},1200);});});
    document.querySelectorAll('a[href^="#"]').forEach(function(a){if(a.dataset.fq180Anchor)return;a.dataset.fq180Anchor='1';a.addEventListener('click',function(e){const href=a.getAttribute('href')||'';const id=href.slice(1);if(!id)return;const el=document.getElementById(id);if(el){e.preventDefault();el.scrollIntoView({behavior:'smooth',block:'start'});if(mNav&&ham){mNav.classList.remove('show');ham.classList.remove('open');}}});});
  });
})();
