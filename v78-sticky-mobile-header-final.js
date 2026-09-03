(function(){
  'use strict';
  function ready(fn){ if(document.readyState!=='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn,{once:true});} }
  ready(function(){
    var header=document.querySelector('.fq-public-header');
    if(!header) return;
    var toggle=header.querySelector('[data-fq-menu-toggle], .fq-mobile-toggle, .fq-three-dot-toggle');
    var panel=header.querySelector('#fqMobileNav, .fq-mobile-panel');
    var overlay=header.querySelector('[data-fq-menu-overlay], .fq-mobile-overlay');
    if(!toggle||!panel) return;

    function setOpen(open){
      document.body.classList.toggle('fq-final-menu-open', !!open);
      document.body.classList.toggle('fq-menu-open', !!open);
      document.body.classList.toggle('fq-mobile-nav-open', !!open);
      panel.classList.toggle('is-open', !!open);
      panel.setAttribute('aria-hidden', open ? 'false' : 'true');
      if(overlay){
        overlay.classList.toggle('is-open', !!open);
        overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
      }
      toggle.classList.toggle('is-active', false); // keep 3 dots, never X
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close mobile menu' : 'Open mobile menu');
      Array.prototype.forEach.call(toggle.querySelectorAll('span'), function(s){
        s.style.transform='none'; s.style.opacity='1';
      });
    }
    function isOpen(){ return document.body.classList.contains('fq-final-menu-open') || panel.classList.contains('is-open'); }

    // Capture phase locks this button so old theme scripts cannot reverse the state.
    document.addEventListener('click', function(e){
      var btn=e.target.closest && e.target.closest('[data-fq-menu-toggle], .fq-mobile-toggle, .fq-three-dot-toggle');
      if(btn && header.contains(btn)){
        e.preventDefault(); e.stopPropagation(); if(e.stopImmediatePropagation) e.stopImmediatePropagation();
        setOpen(!isOpen());
        return false;
      }
      var ov=e.target.closest && e.target.closest('[data-fq-menu-overlay], .fq-mobile-overlay');
      if(ov && header.contains(ov)){
        e.preventDefault(); e.stopPropagation(); if(e.stopImmediatePropagation) e.stopImmediatePropagation();
        setOpen(false); return false;
      }
      var link=e.target.closest && e.target.closest('#fqMobileNav a, .fq-mobile-panel a');
      if(link && header.contains(link)){ setOpen(false); }
    }, true);

    document.addEventListener('keydown', function(e){ if(e.key==='Escape') setOpen(false); });
    window.addEventListener('resize', function(){ if(window.innerWidth>1100) setOpen(false); });
    setOpen(false);
  });
})();
