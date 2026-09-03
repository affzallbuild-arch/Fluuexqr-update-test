(function(){
  'use strict';
  function ready(fn){ if(document.readyState !== 'loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn, {once:true}); } }
  ready(function(){
    var header = document.querySelector('.fq-public-header');
    if(!header){ return; }
    var toggle = header.querySelector('[data-fq-menu-toggle]');
    var panel = header.querySelector('#fqMobileNav');
    var overlay = header.querySelector('[data-fq-menu-overlay], .fq-mobile-overlay');
    if(!toggle || !panel){ return; }
    panel.hidden = false;
    if(overlay){ overlay.hidden = false; }
    function setOpen(open){
      open = !!open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close mobile menu' : 'Open mobile menu');
      panel.setAttribute('aria-hidden', open ? 'false' : 'true');
      panel.classList.toggle('is-open', open);
      toggle.classList.toggle('is-active', open);
      if(overlay){ overlay.setAttribute('aria-hidden', open ? 'false' : 'true'); overlay.classList.toggle('is-open', open); }
      document.documentElement.classList.toggle('fq-menu-open', open);
      document.body.classList.toggle('fq-menu-open', open);
    }
    toggle.addEventListener('click', function(e){
      if(e){ e.preventDefault(); e.stopPropagation(); if(e.stopImmediatePropagation){ e.stopImmediatePropagation(); } }
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    }, true);
    toggle.addEventListener('keydown', function(e){
      if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); setOpen(toggle.getAttribute('aria-expanded') !== 'true'); }
    }, true);
    panel.addEventListener('click', function(e){ if(e.target && e.target.closest && e.target.closest('a')){ setOpen(false); } }, true);
    if(overlay){ overlay.addEventListener('click', function(e){ if(e){ e.preventDefault(); e.stopPropagation(); } setOpen(false); }, true); }
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ setOpen(false); } });
    window.addEventListener('resize', function(){ if(window.innerWidth > 1100){ setOpen(false); } }, {passive:true});
    setOpen(false);
  });
})();
