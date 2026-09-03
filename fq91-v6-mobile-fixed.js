
/* ═══════════════════════════════
   DATA
═══════════════════════════════ */
const FEATURES = [
  {ico:'📱',title:'QR Menu Ordering',desc:'Customers scan the table QR, browse your full menu with photos, and order from their phone. Zero app download needed.',badge:''},
  {ico:'🏨',title:'Hotel Room QR',desc:'In-room dining QR for hotels. Guests scan and order directly — room number auto-tagged on every kitchen order.',badge:''},
  {ico:'🍽️',title:'Table QR Ordering',desc:'Unique QR per table. Orders auto-tagged by table number. No mix-ups, maximum kitchen efficiency.',badge:''},
  {ico:'👨‍🍳',title:'Kitchen Display System',desc:'Live order screen for kitchen. Real-time status: Pending → Preparing → Ready. No paper tickets.',badge:''},
  {ico:'🧾',title:'Running Bills',desc:'Customers see their live order total at any time. Perfect for group dining and large family tables.',badge:''},
  {ico:'🖨️',title:'Thermal Invoice',desc:'Print professional GST-ready invoices in seconds. Works with any thermal printer, fully branded.',badge:''},
  {ico:'💳',title:'UPI Payments',desc:'Accept Google Pay, PhonePe, Paytm at table. No extra hardware — works from customer\'s phone.',badge:'New'},
  {ico:'💬',title:'WhatsApp Billing',desc:'Send invoice to customer\'s WhatsApp instantly. Paperless, eco-friendly, customers love it.',badge:''},
  {ico:'🔔',title:'Waiter Calling',desc:'Customers call waiter digitally from QR menu. Staff notified on device — no shouting needed.',badge:''},
  {ico:'🚴',title:'Delivery Tracking',desc:'Assign delivery agents, track live status, send WhatsApp updates. All from one dashboard.',badge:''},
  {ico:'🏢',title:'Multi-Branch Management',desc:'Manage multiple restaurants or locations with separate menus, orders, reports from one panel.',badge:''},
  {ico:'📊',title:'Analytics Dashboard',desc:'Revenue, top dishes, peak hours, table performance — visual charts, accessible from any device.',badge:''},
  {ico:'🧮',title:'Restaurant Billing POS',desc:'Complete POS system — cash, card, UPI, split bills, discounts, GST — all handled automatically.',badge:''},
];

const COMPARE = [
  ['Ordering Speed','⚡ Instant from customer\'s phone','❌ Slow — waiter takes manually'],
  ['Billing Errors','✓ Near-zero — auto-calculated','❌ Frequent — manual mistakes'],
  ['Menu Update Cost','✓ Free — update anytime online','❌ Reprinting cost each time'],
  ['Kitchen Communication','✓ Live digital display — instant','❌ Paper tickets — error-prone'],
  ['UPI / Digital Payment','✓ Built-in — all UPI apps','❌ Separate POS device needed'],
  ['WhatsApp Invoice','✓ One-click WhatsApp bill','❌ Not possible'],
  ['Revenue Analytics','✓ Full real-time dashboard','❌ Manual counting only'],
  ['Multi-Branch Control','✓ All locations in one panel','❌ Managed separately each'],
  ['Contactless Hygiene','✓ 100% phone-based — clean','❌ Shared paper — hygiene risk'],
  ['Customer Feedback','✓ Auto post-meal rating','❌ No feedback system'],
  ['Monthly Cost','✓ Free trial, then ₹1,999/month','❌ Printing + errors cost ₹5,000+'],
];

const TESTIMONIALS = [
  {stars:5,text:'FluuexQR completely changed how my restaurant in Purnia operates. Earlier we had billing errors daily. Now auto-generated bills, customers pay via PhonePe themselves. Revenue up 30% in the first month.',name:'Ramesh Kumar',biz:'Sher-e-Punjab, Purnia',init:'RK'},
  {stars:5,text:'Our hotel near Katihar Junction now has QR ordering in every room. Guests are impressed — they mention it in Booking.com reviews. Setup was done in 25 minutes with Hindi support on WhatsApp.',name:'Anjali Singh',biz:'Hotel Anand Palace, Katihar',init:'AS'},
  {stars:5,text:'I manage 3 restaurants in Bihar and Delhi from one FluuexQR dashboard. The multi-branch feature is exactly what I needed. Separate analytics per location, one subscription. Highly recommend.',name:'Mohammed Hassan',biz:'Taj Kitchen — 3 Locations',init:'MH'},
  {stars:5,text:'The kitchen display system is the best part. My kitchen staff was confused with paper tickets. Now they see everything on screen — Pending, Preparing, Ready. Service is faster and errors are near zero.',name:'Priya Tiwari',biz:'Spice Garden, Saharsa',init:'PT'},
  {stars:5,text:'We are a cloud kitchen with 2 brands. FluuexQR handles both separately with different menus. Delivery tracking and WhatsApp billing make customers very happy. Professional platform at low price.',name:'Suraj Mandal',biz:'Foodie Hub Cloud Kitchen',init:'SM'},
  {stars:5,text:'Previously I spent ₹3,000/month just on printing new menus when prices changed. With FluuexQR I update the menu from my phone in 2 minutes for free. Best investment I\'ve made for my restaurant.',name:'Kavita Devi',biz:'Devi Bhojnalaya, Patna',init:'KD'},
];

const PRICING = [
  {name:'Free Trial',price:'0',unit:'/10 days',desc:'Full access trial for new restaurants',feats:['10-day free trial','All features ON during trial','No payment required','Trial only once per restaurant','Upgrade required after expiry'],cta:'Start 10-Day Trial',style:'ghost',pop:false},
  {name:'Starter 5 Table',price:'999',unit:'/month',desc:'For small restaurants, cafés and dhabas',feats:['5 table QR ordering','5 categories','20 menu items','2 staff users','Kitchen display + billing','WhatsApp bill + UPI/Razorpay/Cash','Room QR not included'],cta:'Choose Starter Plan',style:'ghost',pop:false},
  {name:'Restaurant All Access',price:'1,999',unit:'/month',desc:'For restaurants, cafes, dhabas and cloud kitchens',feats:['Unlimited table QR ordering','Unlimited menu items/categories','Kitchen display and live orders','Running/PDF/Thermal bill','WhatsApp bill and UPI/Razorpay payment','Reviews, coupons, combos, reports','Staff and table management'],cta:'Choose Restaurant Plan',style:'fire',pop:true},
  {name:'Hotel + Restaurant Full Access',price:'2,499',unit:'/month',desc:'For hotels, resorts and restaurants with rooms',feats:['Everything in Restaurant plan','Hotel Room QR ordering','Room-wise bill and room service orders','Room number tracking in kitchen','Table + Room QR combined','Hotel guest tracking','Priority support badge'],cta:'Choose Hotel Plan',style:'ghost',pop:false},
];

const MARQUEE_ITEMS = [
  '📱 QR Menu Ordering','👨‍🍳 Kitchen Display','🧾 Smart Billing','💳 UPI Payments',
  '💬 WhatsApp Invoice','🏨 Hotel Room QR','🍽️ Table Ordering','🔔 Waiter Calling',
  '📊 Analytics','🚴 Delivery Tracking','🏢 Multi-Branch','🖨️ Thermal Printing',
];

/* ═══════════════════════════════
   RENDER
═══════════════════════════════ */
// Marquee
const mtrack = document.getElementById('mtrack');
const dupItems = [...MARQUEE_ITEMS,...MARQUEE_ITEMS];
if(mtrack){mtrack.innerHTML = dupItems.map(i=>`<div class="marquee-item"><b>${i.split(' ').slice(1).join(' ')}</b><span class="mdot"></span></div>`).join('');}

// Features
const featGrid=document.getElementById('featGrid');
if(featGrid){featGrid.innerHTML = FEATURES.map((f,i)=>`
  <div class="feat-card sr d${(i%4)+1}">
    ${f.badge?`<div class="feat-new">${f.badge}</div>`:''}
    <div class="feat-ico">${f.ico}</div>
    <h3>${f.title}</h3>
    <p>${f.desc}</p>
  </div>`).join('');}

// Compare table
const cmpBody=document.getElementById('cmpBody');
if(cmpBody){cmpBody.innerHTML = COMPARE.map(r=>`
  <tr>
    <td>${r[0]}</td>
    <td class="hi">${r[1]}</td>
    <td class="lo">${r[2]}</td>
  </tr>`).join('');}

// Testimonials
const testiGrid=document.getElementById('testiGrid');
if(testiGrid){testiGrid.innerHTML = TESTIMONIALS.map((t,i)=>`
  <div class="testi-card sr d${(i%3)+1}">
    <div class="t-stars">${'★'.repeat(t.stars)}</div>
    <p class="t-text">"${t.text}"</p>
    <div class="t-author">
      <div class="t-av">${t.init}</div>
      <div class="t-meta"><b>${t.name}</b><span>${t.biz}</span></div>
    </div>
  </div>`).join('');}

// Pricing
const pricingGrid=document.getElementById('pricingGrid');
if(pricingGrid){pricingGrid.innerHTML = PRICING.map(p=>`
  <div class="price-card${p.pop?' pop':''} sr">
    ${p.pop?'<div class="pop-badge">⭐ Most Popular</div>':''}
    <div class="price-name">${p.name}</div>
    <div class="price-amt"><sup>₹</sup>${p.price}<sub>${p.unit}</sub></div>
    <div class="price-note">${p.desc}</div>
    <hr class="price-hr">
    <ul class="price-feats">
      ${p.feats.map(f=>`<li><span class="pf-check">✓</span>${f}</li>`).join('')}
    </ul>
    <a href="/signup/" class="btn btn-${p.style}" style="width:100%;border-radius:12px">${p.cta}</a>
  </div>`).join('');}

/* ═══════════════════════════════
   INTERACTIONS
═══════════════════════════════ */
// Nav scroll
const nav = document.getElementById('nav');
if(nav){window.addEventListener('scroll',()=>nav.classList.toggle('scrolled',scrollY>60),{passive:true});}

// Hamburger
const ham = document.getElementById('ham');
const mNav = document.getElementById('mobileNav');
if(ham && mNav && !ham.dataset.fq91Bound){
  ham.dataset.fq91Bound='1';
  ham.addEventListener('click',()=>{
    const o = mNav.classList.toggle('show');
    ham.classList.toggle('open',o);
    ham.setAttribute('aria-expanded',o);
  });
  document.addEventListener('click',e=>{
    if(mNav.classList.contains('show')&&!mNav.contains(e.target)&&!ham.contains(e.target)){
      mNav.classList.remove('show');ham.classList.remove('open');
    }
  });
}

// Tabs
document.querySelectorAll('.tab').forEach(t=>{
  t.addEventListener('click',()=>{
    document.querySelectorAll('.tab').forEach(x=>{x.classList.remove('on');x.setAttribute('aria-selected','false')});
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('on'));
    t.classList.add('on');t.setAttribute('aria-selected','true');
    document.getElementById('panel-'+t.dataset.tab)?.classList.add('on');
  });
});

// Scroll reveal
const io = new IntersectionObserver(entries=>{
  entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('up');io.unobserve(e.target)}});
},{threshold:.1,rootMargin:'0px 0px -30px 0px'});
document.querySelectorAll('.sr').forEach(el=>io.observe(el));

// Counter animation
const co = new IntersectionObserver(entries=>{
  entries.forEach(e=>{
    if(!e.isIntersecting)return;
    const el=e.target,target=+el.dataset.to,dur=1800,step=target/(dur/16);
    let cur=0;
    const t=setInterval(()=>{
      cur=Math.min(cur+step,target);
      el.textContent=Math.floor(cur);
      if(cur>=target)clearInterval(t);
    },16);
    co.unobserve(el);
  });
},{threshold:.5});
document.querySelectorAll('.cnt').forEach(el=>co.observe(el));

// Phone screen QR cat interaction
document.querySelectorAll('.ps-cat').forEach(c=>{
  c.addEventListener('click',()=>{
    document.querySelectorAll('.ps-cat').forEach(x=>x.classList.remove('active'));
    c.classList.add('active');
  });
});

// Phone screen add button micro-interaction
document.querySelectorAll('.ps-add').forEach(btn=>{
  btn.addEventListener('click',function(){
    this.textContent='✓';
    this.style.background='var(--green)';
    setTimeout(()=>{this.textContent='+';this.style.background='var(--f)';},1200);
  });
});

// Smooth anchor scrolling
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const id=a.getAttribute('href').slice(1);
    const el=document.getElementById(id);
    if(el){e.preventDefault();el.scrollIntoView({behavior:'smooth',block:'start'});mNav.classList.remove('show');ham.classList.remove('open');}
  });
});


// FluuexQR AI Support Chatbot - v94 business support intent engine
(function(){
  const bot=document.getElementById('fq91Chatbot');
  if(!bot) return;
  const toggle=document.getElementById('fq91ChatToggle');
  const close=document.getElementById('fq91ChatClose');
  const form=document.getElementById('fq91ChatForm');
  const input=document.getElementById('fq91ChatInput');
  const body=document.getElementById('fq91ChatBody');
  let chatLang='hi';

  const supportMap = [
    ['Complete setup / full onboarding', ['complete','full setup','setup','onboard','start','shuru','install','activation','configure','configuration','website setup','system setup'], 'setup'],
    ['Menu & item management', ['menu','item','category','price','photo','image','dish','food','available','out of stock','stock'], 'menu'],
    ['Table QR generation', ['table','table qr','dine','dining','scan table'], 'table'],
    ['Hotel room QR ordering', ['room','hotel','guest','room service','room qr','suite'], 'room'],
    ['Kitchen Display System / KDS', ['kitchen','kds','display','chef','preparing','ready','served','order not showing','new order','refresh'], 'kitchen'],
    ['Thermal bill / invoice / PDF', ['bill','billing','invoice','thermal','print','pdf','receipt','running bill','gst','cgst','sgst'], 'bill'],
    ['Payments / UPI / gateway', ['payment','upi','razorpay','stripe','cash','paid','gateway','phonepe','google pay','gpay','paytm'], 'payment'],
    ['Staff / role login', ['staff','role','login','user','kitchen user','delivery user','permission','password','admin login'], 'staff'],
    ['Delivery tracking', ['delivery','rider','delivery boy','maps','google map','tracking','assign'], 'delivery'],
    ['Subscription / plans', ['plan','pricing','subscription','renew','expiry','trial','upgrade','restaurant all access','hotel restaurant','hotel plan','restaurant plan'], 'plan'],
    ['Reports / analytics', ['report','analytics','sales','revenue','popular item','dashboard','today order','earning'], 'report'],
    ['QR scan/menu fetch problem', ['not showing','not fetch','fetch','missing','blank','wrong table','wrong room','room no 0','table no 0','qr problem','scan problem'], 'troubleshoot'],
    ['Demo / contact / support', ['demo','contact','support','email','gmail','call','whatsapp','book'], 'contact']
  ];

  const replies = {
    hi: {
      setup:`Complete FluuexQR setup guide:\n\n1. Admin login kijiye.\n2. Restaurant Profile me name, logo, address, phone, GST/FSSAI add kijiye.\n3. Menu section me categories aur food items add kijiye.\n4. Tables & QR me table numbers add karke QR generate kijiye.\n5. Rooms section me hotel room numbers add karke Room QR generate kijiye.\n6. Staff section me Kitchen/Delivery users create kijiye.\n7. Payments me UPI ID ya gateway details set kijiye.\n8. Billing settings me GST, invoice prefix, footer message set kijiye.\n9. Kitchen Dashboard open karke fresh test order place kijiye.\n10. Table QR aur Room QR dono scan karke verify kijiye.\n\nClient support tip: client ko pehle demo menu, table QR, room QR, KDS, bill print aur admin dashboard flow dikhaaiye.`,
      menu:`Menu add/update guide:\n\n1. Restaurant Dashboard → Menu open kijiye.\n2. Pehle category add kijiye: Starter, Pizza, Drinks, Main Course.\n3. Add Item par click karke name, price, image, description add kijiye.\n4. Item ko correct category me assign kijiye.\n5. Availability ON rakhiye.\n6. Save karne ke baad customer QR menu refresh kijiye.\n\nAgar menu show nahi ho raha: cache clear karein, restaurant_id check karein, aur item active hai ya nahi verify karein.`,
      table:`Table QR setup guide:\n\n1. Dashboard → Tables & QR open kijiye.\n2. Add Table: 01, 02, 03 jaise numbers add kijiye.\n3. Generate QR par click kijiye.\n4. QR print karke same table par place kijiye.\n5. Scan test: URL me source=table ya table id hona chahiye.\n6. Order place karke Kitchen Dashboard me Table No verify kijiye.\n\nRule: Table order me sirf Table No dikhna chahiye, Room No nahi.`,
      room:`Hotel Room QR setup guide:\n\n1. Dashboard → Rooms section open kijiye.\n2. Add Room: 101, 203, 305 jaise room numbers add kijiye.\n3. Generate Room QR par click kijiye.\n4. QR ko room me place kijiye.\n5. Guest scan karega to menu open hoga aur order Room No ke saath kitchen me jayega.\n6. Kitchen Dashboard me Room No verify kijiye.\n\nRule: Room order me sirf Room No dikhna chahiye, Table No nahi.`,
      kitchen:`Kitchen Dashboard issue guide:\n\n1. Kitchen Dashboard page open kijiye.\n2. Kitchen staff user login check kijiye.\n3. Customer side se fresh order place kijiye.\n4. Cache clear kijiye aur JS Combine temporarily OFF karke test kijiye.\n5. Status buttons test kijiye: Accept → Preparing → Ready → Served.\n6. Table order me Table No aur Room order me Room No verify kijiye.\n\nAgar old order me Room No 0 aa raha hai, fresh Room QR se new order test karein.`,
      bill:`Bill / thermal invoice / PDF guide:\n\n1. Customer order place hone ke baad Bill page open kijiye.\n2. Admin Orders se bhi invoice open kar sakte hain.\n3. Print Invoice par click kijiye.\n4. Thermal printer ke liye 80mm paper select kijiye.\n5. PDF ke liye browser print dialog me Save as PDF choose kijiye.\n6. Same table/room se 4 hours ke andar repeat order aata hai to running bill me add hoga.\n\nCheck: bill me Table No ya Room No correct source ke according hi show hona chahiye.`,
      payment:`Payment / UPI setup guide:\n\n1. Dashboard → Payments settings open kijiye.\n2. Restaurant UPI ID add kijiye.\n3. Razorpay/Stripe use karna ho to keys add kijiye.\n4. Cash/UPI payment option enable rakhiye.\n5. Test order place karke checkout screen verify kijiye.\n6. Payment success ke baad order status aur bill amount check kijiye.`,
      staff:`Staff login setup guide:\n\n1. Dashboard → Staff section open kijiye.\n2. Kitchen staff, delivery staff ya manager user create kijiye.\n3. Role choose kijiye: Kitchen / Delivery / Manager.\n4. Login details staff ko dijiye.\n5. Kitchen user ko sirf orders/status access milna chahiye.\n6. Delivery user ko assigned order aur map access milna chahiye.`,
      delivery:`Delivery setup guide:\n\n1. Delivery option enable kijiye.\n2. Delivery staff create kijiye.\n3. Order aane ke baad admin order ko delivery boy assign kare.\n4. Google Maps navigation link se delivery start kare.\n5. Status update kare: Assigned → Picked → On the way → Delivered.`,
      plan:`Plan/subscription guide:\n\n1. Starter/Trial demo testing ke liye hai.\n2. Basic single restaurant ke liye best: QR menu, table QR, KDS, billing.\n3. Premium hotel room QR, multi-branch, delivery aur advanced reports ke liye hai.\n4. Renew/Upgrade issue ke liye Super Admin → Subscription section check kijiye.`,
      report:`Reports & analytics guide:\n\n1. Dashboard → Reports open kijiye.\n2. Date range select kijiye.\n3. Total orders, revenue, popular items aur payment mode check kijiye.\n4. Daily sales aur top items client ko explain kijiye.\n5. Export/PDF option available ho to accountant ko report bhej sakte hain.`,
      troubleshoot:`QR/menu troubleshooting guide:\n\n1. QR URL me restaurant id check kijiye.\n2. Table QR me table id/source table hona chahiye.\n3. Room QR me room id/source room hona chahiye.\n4. Restaurant ke menu items active hain ya nahi check kijiye.\n5. Cache clear kijiye.\n6. Naya QR regenerate karke scan test kijiye.\n7. Fresh order place karke Kitchen Dashboard me Table No/Room No verify kijiye.`,
      contact:`Support/contact guide:\n\n1. Demo ke liye Contact page open kijiye.\n2. Restaurant/hotel name, mobile, city aur requirement share kijiye.\n3. Support email: hello@fluuexqr.com / support@fluuexqr.com\n4. Aap issue ka screenshot aur QR URL bhejenge to support fast milega.`,
      fallback:`Mujhe exact issue samajhne ke liye ye detail bhejiye:\n\n1. Problem kis page par hai?\n2. Table QR hai ya Room QR?\n3. Error kya dikh raha hai?\n4. Fresh order test kiya ya old order hai?\n5. Screenshot/QR URL available hai?\n\nMain menu, table QR, room QR, kitchen, billing, payment, staff, delivery, subscription aur reports setup me help kar sakta hoon.`
    },
    en: {
      setup:`Complete FluuexQR setup guide:\n\n1. Log in as Restaurant Admin.\n2. Add restaurant name, logo, address, phone, GST and FSSAI details.\n3. Create menu categories and add food items with price, image and description.\n4. Add tables and generate table QR codes.\n5. Add hotel rooms and generate room QR codes.\n6. Create staff users for kitchen, delivery and manager roles.\n7. Set UPI ID or payment gateway keys.\n8. Configure billing: GST, invoice prefix, footer message and print settings.\n9. Open Kitchen Dashboard and place a fresh test order.\n10. Test both table QR and room QR flows.\n\nClient support tip: show clients the full flow: customer scan, order, kitchen display, bill print and admin dashboard.`,
      menu:`Menu setup guide:\n\n1. Open Restaurant Dashboard → Menu.\n2. Create categories such as Starters, Pizza, Drinks and Main Course.\n3. Add item name, price, image, description and category.\n4. Keep item availability ON.\n5. Save and refresh the customer QR menu.\n\nIf menu is not showing: clear cache, check restaurant ID and confirm items are active.`,
      table:`Table QR setup guide:\n\n1. Open Dashboard → Tables & QR.\n2. Add table numbers such as 01, 02 or 07.\n3. Click Generate/Download QR.\n4. Print the QR and place it on the same table.\n5. Scan and test: URL should contain table source/table id.\n6. Place an order and verify Table No in Kitchen Dashboard.\n\nRule: table orders must show only Table No, not Room No.`,
      room:`Hotel Room QR setup guide:\n\n1. Open Dashboard → Rooms.\n2. Add room numbers such as 101, 203 or 305.\n3. Generate and download Room QR.\n4. Place the QR inside the hotel room.\n5. Guest scans it, menu opens, and order reaches kitchen with Room No.\n6. Verify Room No on Kitchen Dashboard.\n\nRule: room orders must show only Room No, not Table No.`,
      kitchen:`Kitchen Dashboard troubleshooting:\n\n1. Open Kitchen Dashboard.\n2. Confirm kitchen staff login is correct.\n3. Place a fresh test order from customer menu.\n4. Clear cache and temporarily turn off JS Combine if needed.\n5. Test status buttons: Accept → Preparing → Ready → Served.\n6. Verify Table No for table orders and Room No for room orders.`,
      bill:`Bill / thermal invoice / PDF guide:\n\n1. Open invoice after a customer order.\n2. Admin can also open invoice from Orders.\n3. Click Print Invoice.\n4. Select 80mm paper for thermal printer.\n5. Use browser Print → Save as PDF for PDF download.\n6. Repeat orders from the same table/room within 4 hours should be added to the running bill.`,
      payment:`Payment setup guide:\n\n1. Open Dashboard → Payments.\n2. Add restaurant UPI ID.\n3. Add Razorpay/Stripe keys if online gateway is needed.\n4. Enable Cash/UPI payment options.\n5. Place a test order and verify checkout and bill amount.`,
      staff:`Staff setup guide:\n\n1. Open Dashboard → Staff.\n2. Create Kitchen, Delivery or Manager user.\n3. Select correct role and permissions.\n4. Share login details with staff.\n5. Kitchen users should manage order status only.\n6. Delivery users should see assigned orders and map navigation.`,
      delivery:`Delivery setup guide:\n\n1. Enable delivery option.\n2. Create delivery staff users.\n3. Assign order to a delivery boy from admin orders.\n4. Open Google Maps navigation.\n5. Update status: Assigned → Picked → On the way → Delivered.`,
      plan:`Plan/subscription guide:\n\n1. Starter/Trial is for demo testing.\n2. Basic is best for a single restaurant: QR menu, table QR, KDS and billing.\n3. Premium is for hotel room QR, multi-branch, delivery and advanced reports.\n4. For renewal/upgrade issues, check Super Admin → Subscription.`,
      report:`Reports & analytics guide:\n\n1. Open Dashboard → Reports.\n2. Select date range.\n3. Check total orders, revenue, popular items and payment modes.\n4. Explain daily sales and top items to the client.\n5. Export/share report if available.`,
      troubleshoot:`QR/menu troubleshooting guide:\n\n1. Check restaurant ID in QR URL.\n2. Table QR should include table id/source table.\n3. Room QR should include room id/source room.\n4. Confirm restaurant menu items are active.\n5. Clear cache.\n6. Regenerate QR and scan again.\n7. Place a fresh order and verify Table No/Room No in Kitchen Dashboard.`,
      contact:`Support/contact guide:\n\n1. Open Contact page for demo/support.\n2. Share restaurant/hotel name, mobile, city and requirement.\n3. Email: hello@fluuexqr.com / support@fluuexqr.com\n4. Send screenshot and QR URL for faster support.`,
      fallback:`Please share these details so I can guide you correctly:\n\n1. Which page has the issue?\n2. Is it Table QR or Room QR?\n3. What error is showing?\n4. Is it a fresh order or old order?\n5. Do you have screenshot or QR URL?\n\nI can help with menu, table QR, room QR, kitchen, billing, payments, staff, delivery, subscriptions and reports.`
    }
  };

  function detectLanguage(q){
    const s=(q||'').toLowerCase();
    if(/[\u0900-\u097F]/.test(q) || s.includes('hindi') || s.includes('हिंदी')) return 'hi';
    if(s.includes('english') || s.includes('angrezi') || s.includes('अंग्रेज')) return 'en';
    const enWords=['how','what','why','setup','guide','issue','problem','not','working','showing','create','generate','download','print','payment','support'];
    if(enWords.some(w=>s.includes(w))) return 'en';
    return chatLang;
  }
  function normalize(q){return (q||'').toLowerCase().replace(/[^a-z0-9\u0900-\u097F₹ ]+/g,' ')}
  function findIntent(q){
    const s=normalize(q);
    let best={intent:'fallback',score:0};
    supportMap.forEach(row=>{
      let score=0;
      row[1].forEach(k=>{ if(s.includes(k.toLowerCase())) score += Math.max(1, k.split(' ').length); });
      if(score>best.score) best={intent:row[2],score};
    });
    if(s.includes('english') && (s.includes('explain') || s.includes('samjha') || s.includes('guide'))) {
      const prev = body ? (body.innerText || '') : '';
      return best.score ? best.intent : 'setup';
    }
    return best.score ? best.intent : 'fallback';
  }
  function addMsg(text,type){
    const m=document.createElement('div');
    m.className='fq91-msg '+type;
    m.textContent=text;
    body.appendChild(m);
    body.scrollTop=body.scrollHeight;
  }
  function buildReply(q){
    chatLang = detectLanguage(q);
    const langPack = replies[chatLang] || replies.hi;
    const intent = findIntent(q);
    return langPack[intent] || langPack.fallback;
  }
  function open(){bot.classList.add('open');setTimeout(()=>input&&input.focus(),120)}
  function closeBot(){bot.classList.remove('open')}
  function reply(q){setTimeout(()=>addMsg(buildReply(q),'bot'),220)}

  toggle&&toggle.addEventListener('click',()=>bot.classList.contains('open')?closeBot():open());
  close&&close.addEventListener('click',closeBot);
  form&&form.addEventListener('submit',e=>{
    e.preventDefault();
    const v=(input.value||'').trim();
    if(!v)return;
    addMsg(v,'user');
    input.value='';
    reply(v);
  });
  bot.querySelectorAll('[data-fq91-question]').forEach(b=>b.addEventListener('click',()=>{
    const q=b.getAttribute('data-fq91-question')||b.textContent||'';
    addMsg(q,'user');
    reply(q);
  }));
})();
