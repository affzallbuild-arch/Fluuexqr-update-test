(function(){
  'use strict';
  function ready(fn){
    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, {once:true});
    else fn();
  }
  ready(function(){
    var header = document.querySelector('.fq-public-header');
    if(!header) return;
    var toggle = header.querySelector('[data-fq-menu-toggle], .fq-mobile-toggle, .fq-three-dot-toggle');
    var panel = header.querySelector('#fqMobileNav, .fq-mobile-panel');
    var overlay = header.querySelector('[data-fq-menu-overlay], .fq-mobile-overlay');
    if(!toggle || !panel) return;

    // Remove attributes/classes that can block click/open from old cached scripts.
    panel.hidden = false;
    if(overlay) overlay.hidden = false;

    function isOpen(){ return toggle.getAttribute('aria-expanded') === 'true'; }
    function setOpen(open){
      open = !!open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Menu open' : 'Open menu');
      panel.setAttribute('aria-hidden', open ? 'false' : 'true');
      panel.classList.toggle('is-open', open);
      toggle.classList.toggle('is-active', open);
      if(overlay){
        overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
        overlay.classList.toggle('is-open', open);
      }
      document.documentElement.classList.toggle('fq-menu-open', open);
      document.body.classList.toggle('fq-menu-open', open);
    }

    // Replace old listeners by capturing click at document level. This wins even if old JS is broken.
    document.addEventListener('click', function(e){
      var clickedToggle = e.target.closest && e.target.closest('[data-fq-menu-toggle], .fq-mobile-toggle, .fq-three-dot-toggle');
      if(clickedToggle && header.contains(clickedToggle)){
        e.preventDefault();
        e.stopPropagation();
        setOpen(!isOpen());
        return;
      }
      if(overlay && e.target === overlay){
        e.preventDefault();
        setOpen(false);
        return;
      }
      if(panel.contains(e.target) && e.target.closest && e.target.closest('a')){
        setOpen(false);
      }
    }, true);

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') setOpen(false);
    });
    window.addEventListener('resize', function(){
      if(window.innerWidth > 1100) setOpen(false);
    }, {passive:true});

    setOpen(false);
  });
})();
