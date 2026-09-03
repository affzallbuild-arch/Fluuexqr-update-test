
(function(){
  'use strict';
  if (window.__fluuexQrV60PerfLoaded) { return; }
  window.__fluuexQrV60PerfLoaded = true;

  function ready(fn){
    if(document.readyState !== 'loading'){ fn(); return; }
    document.addEventListener('DOMContentLoaded', fn, {once:true});
  }

  ready(function(){
    var toggle = document.querySelector('.fq-mobile-toggle');
    var panel = document.getElementById('fqMobileNav');
    var overlay = document.querySelector('.fq-mobile-overlay');
    if(toggle && panel){
      var setOpen = function(open){
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        panel.hidden = !open;
        panel.classList.toggle('is-open', open);
        toggle.classList.toggle('is-active', open);
        if(overlay){
          overlay.hidden = !open;
          overlay.classList.toggle('is-open', open);
        }
        document.documentElement.classList.toggle('fq-menu-open', open);
        document.body.classList.toggle('fq-menu-open', open);
      };
      setOpen(false);
      toggle.addEventListener('click', function(e){
        if(e && e.preventDefault){ e.preventDefault(); }
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
      });
      panel.addEventListener('click', function(e){ if(e.target && e.target.closest && e.target.closest('a')){ setOpen(false); } });
      if(overlay){ overlay.addEventListener('click', function(){ setOpen(false); }); }
      document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ setOpen(false); } });
      window.addEventListener('resize', function(){ if(window.innerWidth > 1100){ setOpen(false); } }, {passive:true});
    }

    document.querySelectorAll('img:not([loading])').forEach(function(img){ img.loading = 'lazy'; });
    document.querySelectorAll('img:not([decoding])').forEach(function(img){ img.decoding = 'async'; });

    document.querySelectorAll('table').forEach(function(table){
      if(table.parentElement && /table-wrap|orders-table-wrap|admin-table-wrap|mq-table-wrap/.test(table.parentElement.className)){ return; }
      var wrap = document.createElement('div');
      wrap.className = 'mq-table-wrap';
      table.parentNode.insertBefore(wrap, table);
      wrap.appendChild(table);
    });
  });
})();
