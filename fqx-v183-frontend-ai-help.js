(function(){
  function ready(fn){document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn,{once:true}):fn();}
  ready(function(){
    var bot=document.getElementById('fq91Chatbot');
    if(!bot) return;
    var toggle=document.getElementById('fq91ChatToggle');
    var close=document.getElementById('fq91ChatClose');
    var form=document.getElementById('fq91ChatForm');
    var input=document.getElementById('fq91ChatInput');
    var body=document.getElementById('fq91ChatBody');
    if(!toggle || !form || !input || !body) return;

    var lang='hi';
    var intents=[
      ['setup',['setup','start','shuru','install','onboard','account','restaurant setup','complete setup','configure','configuration','kaise start','सुरू','सेटअप']],
      ['menu',['menu','item','category','dish','food','price','photo','out of stock','available','add item','मेनू','आइटम','कैटेगरी']],
      ['table',['table','table qr','dine','dining','qr table','टेबल','टेबल क्यूआर']],
      ['room',['room','room qr','hotel','guest','room service','wifi qr','room template','room no','रूम','होटल']],
      ['kitchen',['kitchen','kds','chef','preparing','ready','served','order not showing','status','किचन','ऑर्डर नहीं']],
      ['bill',['bill','billing','invoice','print','pdf','thermal','paid','unpaid','mark paid','payment status','बिल','इनवॉइस','प्रिंट']],
      ['payment',['payment','upi','razorpay','stripe','cash','bank','gateway','phonepe','gpay','paytm','पेमेंट','यूपीआई']],
      ['staff',['staff','role','manager','waiter','cashier','delivery staff','kitchen staff','login','permission','स्टाफ','लॉगिन']],
      ['reports',['report','analytics','revenue','sales','export csv','export pdf','top item','रिपोर्ट','सेल']],
      ['subscription',['subscription','plan','renew','upgrade','expiry','trial','pricing','प्लान','सब्सक्रिप्शन']],
      ['delivery',['delivery','rider','delivery boy','assign','tracking','डिलीवरी']],
      ['troubleshoot',['not working','blank','error','issue','problem','wrong','not showing','save nahi','click nahi','काम नहीं','समस्या','नहीं हो रहा']],
      ['contact',['contact','support','demo','call','whatsapp','help','सपोर्ट','कॉल']]
    ];
    var replies={
      hi:{
        setup:"Complete setup guide:\n\n1. Restaurant Admin login kijiye.\n2. Restaurant Profile me name, logo, address, phone, GST/FSSAI add kijiye.\n3. Menu me categories aur items add kijiye.\n4. Tables & QR me table QR generate kijiye.\n5. Rooms & QR me room QR aur room templates set kijiye.\n6. Kitchen staff, cashier, waiter, delivery roles Staff page me banaiye.\n7. Payment Settings me UPI/gateway/cash enable kijiye.\n8. Bill Branding me logo, GST, footer note aur print size set kijiye.\n9. Ek fresh test order place karke Kitchen, Tracking aur Bill verify kijiye.\n\nAap specific issue likho, main exact step bata dunga.",
        menu:"Menu help:\n\n1. Restaurant Admin → Menu open kijiye.\n2. Add Item par click karke name, category, price, image, description add kijiye.\n3. Availability ON rakhiye.\n4. Save ke baad Customer QR Menu refresh kijiye.\n\nAgar item show nahi ho raha: category active hai, item available hai, restaurant_id correct hai, aur cache clear hai — ye check kijiye.",
        table:"Table QR help:\n\n1. Tables & QR page open kijiye.\n2. Table number add kijiye.\n3. Generate/Download QR kijiye.\n4. QR scan karke menu open hona chahiye.\n5. Order place ke baad Kitchen me Table No show hona chahiye.\n\nImportant: Table order me Room No nahi aana chahiye.",
        room:"Room QR help:\n\n1. Rooms & QR page open kijiye.\n2. Room number add kijiye.\n3. QR Templates me 10 templates me se select karke save kijiye.\n4. Print QR page me selected template preview check kijiye.\n5. WiFi QR enabled hai to room printable card me Menu QR + WiFi QR dono show honge.\n6. Order place ke baad Kitchen me Room No show hona chahiye.\n\nAgar template save nahi ho raha: cache clear karke selected badge aur template key check kijiye.",
        kitchen:"Kitchen/KDS help:\n\n1. Kitchen Dashboard open kijiye.\n2. Fresh order place kijiye.\n3. Status buttons test kijiye: Accept → Preparing → Ready → Served.\n4. Table order me Table No aur room order me Room No verify kijiye.\n\nAgar order show nahi hota: staff restaurant_id, order status, cache aur JS console check kijiye.",
        bill:"Billing help:\n\n1. Order ke baad Bill page open kijiye.\n2. Print/PDF/WhatsApp buttons test kijiye.\n3. Mark Paid karne par customer bill/tracker me PAID dikhna chahiye.\n4. Bill Branding me logo, GST, tax breakdown, thank-you note set kijiye.\n\nAgar paid status customer ko nahi dikh raha: bill_id/order_id sync aur cache clear check kijiye.",
        payment:"Payment help:\n\n1. Payment Settings me UPI ID add kijiye.\n2. Cash/UPI/Gateway toggles enable kijiye.\n3. Razorpay/Stripe keys masked rakhiye aur test connection kijiye.\n4. Customer checkout me enabled payment methods show hone chahiye.\n\nSecurity: payment secrets frontend par expose nahi hone chahiye.",
        staff:"Staff help:\n\n1. Staff page me user create kijiye.\n2. Role select kijiye: Kitchen Staff, Cashier, Waiter, Room Service, Delivery, Manager.\n3. Login ke baad role ke according limited pages show hone chahiye.\n4. Kitchen Staff → KDS, Cashier → Bills, Waiter → Orders, Room Service → Room Orders.",
        reports:"Reports help:\n\n1. Reports page open kijiye.\n2. Date range, source/branch, report type select kijiye.\n3. Revenue, orders, payment breakdown, top items aur busiest hours verify kijiye.\n4. Export CSV/PDF use kijiye.",
        subscription:"Subscription help:\n\n1. Subscription page me Current Plan, Days Remaining aur Billing Status check kijiye.\n2. Renew Now ya Upgrade Plan use kijiye.\n3. Annual billing switch se yearly plan select kijiye.\n4. Plan expiry hone par locked features expected behavior ke according disable honge.",
        delivery:"Delivery help:\n\n1. Delivery staff create kijiye.\n2. Order ko delivery staff assign kijiye.\n3. Track status: Assigned → Picked → On the way → Delivered.\n4. Customer tracker me status update show hona chahiye.",
        troubleshoot:"Troubleshooting checklist:\n\n1. Page ka naam aur screenshot note kijiye.\n2. Cache clear kijiye: LiteSpeed/browser.\n3. Console error check kijiye.\n4. Fresh test order/QR scan kijiye.\n5. Restaurant ID, table ID, room ID verify kijiye.\n6. Save button ke nonce/action aur response check kijiye.\n\nExact problem likho: jaise 'Room template save nahi ho raha' ya 'Add Item click nahi ho raha'.",
        contact:"Support:\n\nIssue fast solve karne ke liye ye bhejiye:\n1. Restaurant name\n2. Page name\n3. Screenshot\n4. QR URL/order ID\n5. Problem kya click karne par aa raha hai\n\nEmail: support@fluuexqr.com / hello@fluuexqr.com"
      },
      en:{
        setup:"Complete setup guide:\n\n1. Log in as Restaurant Admin.\n2. Add restaurant name, logo, address, phone, GST/FSSAI.\n3. Add categories and menu items.\n4. Generate table QR codes.\n5. Generate room QR codes and select room templates.\n6. Create staff roles: kitchen, cashier, waiter, delivery and manager.\n7. Configure UPI/gateway/cash in Payment Settings.\n8. Configure Bill Branding and print settings.\n9. Place a fresh test order and verify Kitchen, Tracking and Bill.\n\nTell me the exact issue and I will guide step-by-step.",
        menu:"Menu help:\n\n1. Open Restaurant Admin → Menu.\n2. Click Add Item.\n3. Add name, category, price, image and description.\n4. Keep availability ON.\n5. Save and refresh the Customer QR Menu.\n\nIf item is not visible: check active category, available item status, restaurant ID and cache.",
        table:"Table QR help:\n\n1. Open Tables & QR.\n2. Add table number.\n3. Generate/download QR.\n4. Scan QR and place a test order.\n5. Kitchen should show the correct Table No.\n\nTable orders should not show Room No.",
        room:"Room QR help:\n\n1. Open Rooms & QR.\n2. Add room number.\n3. Select one of the 10 QR templates and save.\n4. Check Print QR preview.\n5. If WiFi QR is enabled, the room printable card should show Menu QR + WiFi QR.\n6. Kitchen should show the correct Room No.",
        kitchen:"Kitchen/KDS help:\n\n1. Open Kitchen Dashboard.\n2. Place a fresh order.\n3. Test status buttons: Accept → Preparing → Ready → Served.\n4. Verify Table No for table orders and Room No for room orders.",
        bill:"Billing help:\n\n1. Open Bill after order.\n2. Test Print/PDF/WhatsApp buttons.\n3. Mark Paid should show PAID on customer bill/tracker.\n4. Bill Branding controls logo, GST, tax breakdown and footer note.",
        payment:"Payment help:\n\n1. Add UPI ID in Payment Settings.\n2. Enable Cash/UPI/Gateway toggles.\n3. Add Razorpay/Stripe keys and test connection.\n4. Checkout should show enabled payment methods.\n\nPayment secrets must stay masked/secure.",
        staff:"Staff help:\n\n1. Create staff user from Staff page.\n2. Select role: Kitchen Staff, Cashier, Waiter, Room Service, Delivery or Manager.\n3. After login, staff should see only allowed pages.\n4. Kitchen → KDS, Cashier → Bills, Waiter → Orders, Delivery → Delivery Orders.",
        reports:"Reports help:\n\n1. Open Reports.\n2. Select date range/source/report type.\n3. Verify revenue, orders, payment breakdown, top items and busiest hours.\n4. Export CSV/PDF if needed.",
        subscription:"Subscription help:\n\n1. Check Current Plan, Days Remaining and Billing Status.\n2. Use Renew Now or Upgrade Plan.\n3. Switch to annual plan if needed.\n4. Feature access should follow current plan limits.",
        delivery:"Delivery help:\n\n1. Create delivery staff.\n2. Assign order to delivery staff.\n3. Update status: Assigned → Picked → On the way → Delivered.\n4. Customer tracker should show updates.",
        troubleshoot:"Troubleshooting checklist:\n\n1. Note page name and screenshot.\n2. Clear LiteSpeed/browser cache.\n3. Check console error.\n4. Place fresh test order/QR scan.\n5. Verify restaurant ID, table ID and room ID.\n6. Check save button nonce/action response.\n\nWrite the exact issue, like 'Room template not saving' or 'Add Item button not opening'.",
        contact:"Support:\n\nSend these details for faster help:\n1. Restaurant name\n2. Page name\n3. Screenshot\n4. QR URL/order ID\n5. What happens after clicking\n\nEmail: support@fluuexqr.com / hello@fluuexqr.com"
      }
    };
    function detectLang(q){
      var s=(q||'').toLowerCase();
      if(/[\u0900-\u097F]/.test(q) || s.indexOf('hindi')>-1 || s.indexOf('hinglish')>-1) return 'hi';
      if(s.indexOf('english')>-1 || s.indexOf('en ')>-1) return 'en';
      var en=['how','what','why','setup','guide','issue','problem','not working','show','create','download','print','payment'];
      return en.some(function(w){return s.indexOf(w)>-1;}) ? 'en' : lang;
    }
    function findIntent(q){
      var s=(q||'').toLowerCase();
      var best='troubleshoot', score=0;
      intents.forEach(function(row){
        var sc=0;
        row[1].forEach(function(k){ if(s.indexOf(k.toLowerCase())>-1){ sc += Math.max(1, k.split(' ').length); } });
        if(sc>score){score=sc;best=row[0];}
      });
      return score ? best : 'troubleshoot';
    }
    function add(text,type){
      var m=document.createElement('div');
      m.className='fq91-msg '+(type||'bot');
      m.textContent=text;
      body.appendChild(m);
      body.scrollTop=body.scrollHeight;
    }
    function answer(q){
      lang=detectLang(q);
      var intent=findIntent(q);
      var pack=replies[lang]||replies.hi;
      return pack[intent]||pack.troubleshoot;
    }
    function openBot(){ bot.classList.add('open'); toggle.setAttribute('aria-expanded','true'); setTimeout(function(){input.focus();},120); }
    function closeBot(){ bot.classList.remove('open'); toggle.setAttribute('aria-expanded','false'); }
    toggle.addEventListener('click',function(e){ e.preventDefault(); bot.classList.contains('open') ? closeBot() : openBot(); });
    if(close){ close.addEventListener('click',function(e){ e.preventDefault(); closeBot(); }); }
    form.addEventListener('submit',function(e){
      e.preventDefault();
      var q=(input.value||'').trim();
      if(!q) return;
      add(q,'user');
      input.value='';
      setTimeout(function(){ add(answer(q),'bot'); },180);
    });
    bot.querySelectorAll('[data-fq91-question]').forEach(function(btn){
      btn.addEventListener('click',function(e){
        e.preventDefault();
        var q=btn.getAttribute('data-fq91-question')||btn.textContent||'';
        add(q,'user');
        setTimeout(function(){ add(answer(q),'bot'); },160);
      });
    });
  });
})();