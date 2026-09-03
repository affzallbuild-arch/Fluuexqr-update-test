
(function(){
  'use strict';
  function ready(fn){ if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn);} else {fn();} }
  function setTitles(){
    document.querySelectorAll('a,button').forEach(function(el){
      var txt=(el.getAttribute('aria-label')||el.getAttribute('title')||el.textContent||'').trim().replace(/\s+/g,' ');
      if(!txt){
        var emoji=(el.innerText||'').trim();
        txt=emoji || 'Action';
      }
      if(!el.getAttribute('title')) el.setAttribute('title', txt);
      if(!el.getAttribute('aria-label')) el.setAttribute('aria-label', txt);
    });
  }
  function fixPdfButton(){
    var btn=document.getElementById('fqDownloadPdfBtn');
    if(btn && !btn.dataset.fqx197PdfBound){
      btn.dataset.fqx197PdfBound='1';
      btn.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        var oldTitle=document.title;
        var title=btn.getAttribute('data-title') || 'FluuexQR-Bill';
        document.title=title;
        btn.classList.add('is-loading');
        btn.disabled=true;
        setTimeout(function(){
          try{ window.print(); }catch(err){}
          setTimeout(function(){ btn.classList.remove('is-loading'); btn.disabled=false; document.title=oldTitle; }, 900);
        }, 120);
      });
    }
  }
  function fixMobileDrawer(){
    ['.fqx-v115-super','.fqx-v145-restaurant-admin'].forEach(function(scopeSel){
      document.querySelectorAll(scopeSel).forEach(function(scope){
        if(scope.querySelector('.fqx-v197-mobile-toggle')) return;
        var topbar=scope.querySelector('.topbar') || scope.querySelector('.main-content');
        if(!topbar) return;
        var b=document.createElement('button');
        b.type='button';
        b.className='fqx-v197-mobile-toggle';
        b.innerHTML='☰';
        b.setAttribute('aria-label','Open menu');
        b.setAttribute('title','Open menu');
        b.style.cssText='min-width:42px;min-height:42px;border-radius:12px;border:1px solid rgba(255,255,255,.18);background:#0f1b2d;color:#fff;font-weight:900;display:none;align-items:center;justify-content:center;';
        b.addEventListener('click',function(){
          scope.classList.toggle('fqx-sidebar-open');
          document.body.classList.toggle('fqx-sidebar-open');
        });
        topbar.insertBefore(b, topbar.firstChild);
      });
    });
  }
  function mobileButtonStyles(){
    if(!document.getElementById('fqx-v197-mobile-inline-style')){
      var st=document.createElement('style');
      st.id='fqx-v197-mobile-inline-style';
      st.textContent='@media(max-width:1024px){.fqx-v197-mobile-toggle{display:inline-flex!important}.fqx-v115-super .sidebar,.fqx-v145-restaurant-admin .sidebar{position:fixed!important;left:0!important;top:0!important;height:100vh!important;z-index:9999!important;transform:translateX(-110%)!important;transition:transform .24s ease!important}.fqx-v115-super.fqx-sidebar-open .sidebar,.fqx-v145-restaurant-admin.fqx-sidebar-open .sidebar{transform:translateX(0)!important}}';
      document.head.appendChild(st);
    }
  }
  function preventStuckLoading(){
    window.addEventListener('pageshow', function(){
      document.querySelectorAll('button[disabled].is-loading,.is-loading').forEach(function(el){
        el.classList.remove('is-loading');
        if(el.tagName==='BUTTON') el.disabled=false;
      });
    });
  }
  function improveClicks(){
    document.addEventListener('click', function(e){
      var icon=e.target.closest('svg,i,span');
      if(icon){
        var act=icon.closest('a,button');
        if(act && act !== e.target && !act.disabled){
          // allow native click to bubble; this fixes nested icon hit area without changing URLs.
        }
      }
    }, true);
  }
  ready(function(){
    setTitles();
    fixPdfButton();
    fixMobileDrawer();
    mobileButtonStyles();
    preventStuckLoading();
    improveClicks();
  });
})();
