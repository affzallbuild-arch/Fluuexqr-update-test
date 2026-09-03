(function(){
  'use strict';
  var d=document,b=d.body;
  function q(s,c){return (c||d).querySelector(s)}
  function qa(s,c){return Array.prototype.slice.call((c||d).querySelectorAll(s))}
  function closest(el,sel){return el && el.closest ? el.closest(sel) : null}
  function isDash(){return !!q('.app-shell.dashboard-shell')}
  function openSidebar(v){
    qa('.app-shell.dashboard-shell').forEach(function(shell){shell.classList.toggle('fqx-sidebar-open',!!v);shell.classList.toggle('sidebar-open',!!v);});
    b.classList.toggle('fqx-sidebar-open',!!v); b.classList.toggle('sidebar-open',!!v);
    qa('.mq-sidebar-toggle').forEach(function(btn){btn.setAttribute('aria-expanded',v?'true':'false')});
  }
  function ensureMobileToggle(){
    qa('.app-shell.dashboard-shell').forEach(function(shell){
      var topbar=q('.topbar',shell), sidebar=q('.sidebar',shell); if(!topbar||!sidebar) return;
      if(!q('.mq-sidebar-toggle',topbar)){
        var btn=d.createElement('button'); btn.type='button'; btn.className='mq-sidebar-toggle'; btn.setAttribute('aria-label','Open admin menu'); btn.setAttribute('aria-expanded','false'); btn.textContent='☰';
        topbar.insertBefore(btn, topbar.firstChild);
      }
      if(!q('.mq-sidebar-overlay',shell)){
        var ov=d.createElement('div'); ov.className='mq-sidebar-overlay'; shell.insertBefore(ov, shell.firstChild);
      }
    });
  }
  function enhanceTables(){
    qa('.page-body table').forEach(function(table){
      if(!closest(table,'.table-scroll') && !closest(table,'.table-wrap') && !closest(table,'.fq95-table-wrap')){
        var wrap=d.createElement('div'); wrap.className='table-scroll fqx-v196-table-scroll'; table.parentNode.insertBefore(wrap,table); wrap.appendChild(table);
      }
      var heads=qa('thead th',table).map(function(th){return (th.textContent||'').replace(/\s+/g,' ').trim();});
      if(!heads.length) return;
      qa('tbody tr',table).forEach(function(tr){
        qa('td',tr).forEach(function(td,i){ if(!td.getAttribute('data-label')) td.setAttribute('data-label', heads[i] || 'Info'); });
      });
    });
  }
  function labelAndFixActions(){
    qa('a,button').forEach(function(el){
      var txt=(el.textContent||'').replace(/\s+/g,' ').trim();
      var href=String(el.getAttribute('href')||'');
      var iconish=el.querySelector('svg,.dashicons,.nav-icon,.fq-svg-icon,.fq-icon') || /^(👁|🖨|💬|✅|💳|🧾|✏️|🗑|📄|🔍|⬇|🔗|☰|📋|🏪|💎|📶|🛏|⭐|🏷|🎁|📈)/.test(txt);
      if(iconish && (!el.getAttribute('title') || !el.getAttribute('aria-label'))){
        var label=txt || 'Action';
        if(href.indexOf('whatsapp')!==-1) label='WhatsApp'; else if(href.indexOf('print')!==-1) label='Print'; else if(href.indexOf('bill')!==-1) label='View Bill'; else if(href.indexOf('edit')!==-1) label='Edit';
        if(!el.getAttribute('title')) el.setAttribute('title',label);
        if(!el.getAttribute('aria-label')) el.setAttribute('aria-label',label);
      }
      el.style.pointerEvents='';
      el.removeAttribute('disabled');
    });
  }
  function clearStuckLoading(){
    qa('.is-loading,.fqx-v194-saving,.fqx-v195-saving,.fq95-loading').forEach(function(el){
      el.classList.remove('is-loading','fqx-v194-saving','fqx-v195-saving','fq95-loading');
      el.removeAttribute('aria-busy'); el.style.pointerEvents='';
    });
  }
  function bind(){
    if(b.dataset.fqxV196Bound==='1') return; b.dataset.fqxV196Bound='1';
    d.addEventListener('click',function(e){
      var t=e.target;
      if(closest(t,'.mq-sidebar-toggle')){e.preventDefault();openSidebar(!b.classList.contains('fqx-sidebar-open') && !b.classList.contains('sidebar-open'));return;}
      if(closest(t,'.mq-sidebar-overlay')){openSidebar(false);return;}
      if(window.matchMedia('(max-width:1024px)').matches && closest(t,'.dashboard-shell .sidebar a')){setTimeout(function(){openSidebar(false)},90)}
      var link=closest(t,'a[href]'); if(link){link.style.pointerEvents=''; link.classList.remove('is-loading','fqx-v194-saving','fqx-v195-saving','fq95-loading'); link.removeAttribute('aria-busy');}
    },true);
    d.addEventListener('keydown',function(e){if(e.key==='Escape') openSidebar(false)});
    window.addEventListener('pageshow',clearStuckLoading);
    window.addEventListener('resize',function(){if(window.innerWidth>1024) openSidebar(false)},{passive:true});
  }
  function init(){if(!isDash())return;ensureMobileToggle();enhanceTables();labelAndFixActions();clearStuckLoading();bind();}
  if(d.readyState==='loading') d.addEventListener('DOMContentLoaded',init); else init();
  var mo=new MutationObserver(function(){if(!isDash())return;enhanceTables();labelAndFixActions();});
  mo.observe(d.documentElement,{childList:true,subtree:true});
})();
