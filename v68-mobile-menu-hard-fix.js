(function(){
  'use strict';
  function ready(fn){ if(document.readyState !== 'loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn, {once:true}); } }
  ready(function(){
    document.querySelectorAll('.fq-public-header').forEach(function(header){
      if(header.__fqV68Bound){ return; }
      header.__fqV68Bound = true;
      var check = header.querySelector('#fqMobileMenuCheck');
      var toggle = header.querySelector('[data-fq-menu-toggle], .fq-mobile-toggle');
      var panel = header.querySelector('#fqMobileNav, .fq-mobile-panel');
      var overlay = header.querySelector('.fq-mobile-overlay');
      if(!check || !toggle || !panel){ return; }
      function setOpen(open){
        check.checked = !!open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        panel.hidden = false;
        panel.classList.toggle('is-open', !!open);
        toggle.classList.toggle('is-active', !!open);
        if(overlay){ overlay.hidden = false; overlay.classList.toggle('is-open', !!open); }
        document.documentElement.classList.toggle('fq-menu-open', !!open);
        document.body.classList.toggle('fq-menu-open', !!open);
      }
      toggle.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
      }, true);
      check.addEventListener('change', function(){ setOpen(check.checked); });
      if(overlay){ overlay.addEventListener('click', function(e){ e.preventDefault(); setOpen(false); }, true); }
      panel.addEventListener('click', function(e){ if(e.target && e.target.closest && e.target.closest('a')){ setOpen(false); } });
      document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ setOpen(false); } });
      window.addEventListener('resize', function(){ if(window.innerWidth > 1100){ setOpen(false); } }, {passive:true});
      setOpen(false);
    });
  });
})();
