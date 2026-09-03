(function(){
  'use strict';
  var d=document;
  function ready(fn){ if(d.readyState==='loading') d.addEventListener('DOMContentLoaded',fn); else fn(); }
  function qa(sel,ctx){ return Array.prototype.slice.call((ctx||d).querySelectorAll(sel)); }
  function cleanupDuplicateMenus(){
    qa('.fqx-v197-mobile-toggle').forEach(function(btn){ btn.remove(); });
    qa('.app-shell.dashboard-shell').forEach(function(shell){
      var topbar=shell.querySelector('.topbar'); if(!topbar) return;
      var toggles=qa('.mq-sidebar-toggle', topbar);
      if(!toggles.length){
        var btn=d.createElement('button'); btn.type='button'; btn.className='mq-sidebar-toggle'; btn.textContent='☰'; btn.setAttribute('aria-label','Open admin menu'); btn.setAttribute('title','Open admin menu'); btn.setAttribute('aria-expanded','false'); topbar.insertBefore(btn, topbar.firstChild);
      } else if(toggles.length>1){
        toggles.slice(1).forEach(function(b){ b.remove(); });
      }
    });
  }
  function openSidebar(on){
    qa('.app-shell.dashboard-shell,.fqx-v145-restaurant-admin,.fqx-v115-super').forEach(function(el){ el.classList.toggle('fqx-sidebar-open', !!on); el.classList.toggle('sidebar-open', !!on); });
    d.body.classList.toggle('fqx-sidebar-open', !!on); d.body.classList.toggle('sidebar-open', !!on);
    qa('.mq-sidebar-toggle').forEach(function(b){ b.setAttribute('aria-expanded', on?'true':'false'); });
  }
  function labelsForTables(){
    qa('.page-body table').forEach(function(table){
      var heads=qa('thead th', table).map(function(th){ return (th.textContent||'').replace(/\s+/g,' ').trim(); });
      if(!heads.length) return;
      qa('tbody tr', table).forEach(function(tr){
        qa('td', tr).forEach(function(td,i){ if(!td.getAttribute('data-label')) td.setAttribute('data-label', heads[i] || 'Info'); });
      });
    });
  }
  function fixActionLabels(){
    qa('a,button').forEach(function(el){
      var text=(el.textContent||'').replace(/\s+/g,' ').trim();
      var href=String(el.getAttribute('href')||'');
      if(!text){
        if(href.indexOf('print')!==-1) text='Print';
        else if(href.indexOf('download')!==-1 || href.indexOf('qr_download')!==-1) text='Download';
        else if(href.indexOf('whatsapp')!==-1) text='WhatsApp';
        else if(href.indexOf('edit')!==-1) text='Edit';
        else text='Action';
      }
      if(!el.getAttribute('title')) el.setAttribute('title', text);
      if(!el.getAttribute('aria-label')) el.setAttribute('aria-label', text);
      el.style.pointerEvents='';
    });
  }
  function wifiCardSelection(){
    qa('.fqx-v197-wifi-template-form').forEach(function(form){
      form.addEventListener('change', function(e){
        if(e.target && e.target.name==='wifi_qr_template'){
          qa('.fqx-v198-room-wifi-card',form).forEach(function(card){
            var inp=card.querySelector('input[name="wifi_qr_template"]');
            var selected=inp && inp.checked;
            card.classList.toggle('is-selected', !!selected);
            var badge=card.querySelector('.fqx-v197-wifi-selected');
            if(badge) badge.textContent=selected?'Selected ✓':'Select';
          });
        }
      });
    });
  }
  function preventButtonStuck(){
    window.addEventListener('pageshow',function(){
      qa('.is-loading,[aria-busy="true"]').forEach(function(el){ el.classList.remove('is-loading','fqx-v194-saving','fqx-v195-saving'); el.removeAttribute('aria-busy'); if(el.tagName==='BUTTON') el.disabled=false; el.style.pointerEvents=''; });
    });
  }
  function bind(){
    if(d.body.dataset.fqxV198Bound==='1') return; d.body.dataset.fqxV198Bound='1';
    d.addEventListener('click',function(e){
      var toggle=e.target.closest && e.target.closest('.mq-sidebar-toggle');
      if(toggle){ e.preventDefault(); openSidebar(!d.body.classList.contains('fqx-sidebar-open')); return; }
      if(e.target.closest && e.target.closest('.mq-sidebar-overlay')){ openSidebar(false); return; }
      if(window.innerWidth<=1024 && e.target.closest && e.target.closest('.sidebar a')) setTimeout(function(){openSidebar(false);},80);
    },true);
    d.addEventListener('keydown',function(e){ if(e.key==='Escape') openSidebar(false); });
  }
  function init(){ cleanupDuplicateMenus(); labelsForTables(); fixActionLabels(); wifiCardSelection(); preventButtonStuck(); bind(); }
  ready(init);
  var t=null;
  new MutationObserver(function(){ clearTimeout(t); t=setTimeout(function(){ cleanupDuplicateMenus(); labelsForTables(); fixActionLabels(); },120); }).observe(d.documentElement,{childList:true,subtree:true});
})();

/* v199 Restaurant Admin Category action reliability + mobile labels */
(function(){
  'use strict';
  var d=document;
  function ready(fn){ if(d.readyState==='loading') d.addEventListener('DOMContentLoaded',fn); else fn(); }
  function qa(sel,ctx){ return Array.prototype.slice.call((ctx||d).querySelectorAll(sel)); }
  function isCategoryPage(){ return !!qa('.fq-cat-table,.fq-cat-actions,#fqCategoryForm,#fqCategoryTypeForm').length; }
  function fixCategoryActions(){
    if(!isCategoryPage()) return;
    d.body.classList.add('fqx-v199-category-responsive');
    qa('.fq-cat-table').forEach(function(table){
      var heads=qa('thead th',table).map(function(th){return (th.textContent||'').replace(/\s+/g,' ').trim();});
      qa('tbody tr',table).forEach(function(tr){
        qa('td',tr).forEach(function(td,i){ if(!td.getAttribute('data-label')) td.setAttribute('data-label', heads[i] || 'Info'); });
      });
    });
    qa('.fq-cat-actions a,.fq-cat-actions button,.fq-cat-add-btn,.fq-cat-save-btn,.fq-cat-empty a,.fq-cat-card-head a').forEach(function(el){
      var text=(el.textContent||'').replace(/\s+/g,' ').trim() || 'Action';
      if(!el.getAttribute('title')) el.setAttribute('title',text);
      if(!el.getAttribute('aria-label')) el.setAttribute('aria-label',text);
      el.style.pointerEvents='';
      el.style.maxWidth='100%';
    });
  }
  function preventBlankSubmitText(){
    qa('.fq-cat-save-btn,button[type="submit"],.btn,.btn-primary,.btn-success,.btn-danger').forEach(function(btn){
      var text=(btn.textContent||'').replace(/\s+/g,' ').trim();
      if(text){ btn.dataset.fqxOriginalText = btn.dataset.fqxOriginalText || text; }
    });
    window.addEventListener('pageshow',function(){
      qa('button,a').forEach(function(el){
        el.classList.remove('is-loading','fqx-v194-saving','fqx-v195-saving','fqx-saving');
        el.removeAttribute('aria-busy');
        if(el.tagName==='BUTTON') el.disabled=false;
        if(el.dataset && el.dataset.fqxOriginalText && !(el.textContent||'').replace(/\s+/g,' ').trim()) el.textContent=el.dataset.fqxOriginalText;
        el.style.pointerEvents='';
      });
    });
  }
  ready(function(){ fixCategoryActions(); preventBlankSubmitText(); });
  var timer=null;
  new MutationObserver(function(){ clearTimeout(timer); timer=setTimeout(fixCategoryActions,120); }).observe(d.documentElement,{childList:true,subtree:true});
})();
