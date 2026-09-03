
(function(){
  'use strict';

  function closest(el, sel){ while(el && el.nodeType === 1){ if(el.matches(sel)) return el; el = el.parentElement; } return null; }

  function addIconLabels(){
    var actions = document.querySelectorAll('.fq-bill-actions a,.fq-bill-actions button,.fq-action-btn,.icon-btn,.btn-icon,.fq-row-action,td.actions a,td.actions button');
    actions.forEach(function(btn){
      if(!btn.getAttribute('aria-label')){
        var txt = (btn.textContent || '').trim();
        var href = (btn.getAttribute('href') || '').toLowerCase();
        var cls = (btn.className || '').toString().toLowerCase();
        var label = txt || 'Action';
        if(href.indexOf('whatsapp') !== -1 || cls.indexOf('whatsapp') !== -1) label = 'Send on WhatsApp';
        else if(cls.indexOf('print') !== -1 || href.indexOf('print') !== -1) label = 'Print bill';
        else if(cls.indexOf('pdf') !== -1 || href.indexOf('pdf') !== -1) label = 'Download PDF';
        else if(cls.indexOf('paid') !== -1) label = 'Mark paid';
        else if(cls.indexOf('view') !== -1 || href.indexOf('view') !== -1) label = 'View details';
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
      }
    });
  }

  function preventDoubleSubmit(){
    document.addEventListener('submit', function(e){
      var form = e.target;
      if(!form || form.dataset.fqxSubmitted === '1') return;
      if(form.matches('.fq-bills-table form, form[action*="admin-post.php"], .fq-bill-actions form')){
        form.dataset.fqxSubmitted = '1';
        var btn = form.querySelector('button[type="submit"],input[type="submit"]');
        if(btn){
          btn.dataset.fqxLoading = '1';
          btn.classList.add('is-loading');
        }
        setTimeout(function(){ form.dataset.fqxSubmitted = '0'; if(btn){ btn.dataset.fqxLoading = '0'; btn.classList.remove('is-loading'); } }, 9000);
      }
    }, true);

    document.addEventListener('click', function(e){
      var btn = closest(e.target, 'a,button');
      if(!btn) return;
      if(btn.matches('.fq-bill-actions a,.fq-bill-actions button,.fq-row-action,.icon-btn,.btn-icon,.fq-action-btn')){
        if(btn.dataset.fqxClicked === '1'){
          e.preventDefault();
          return false;
        }
        btn.dataset.fqxClicked = '1';
        btn.dataset.fqxLoading = '1';
        btn.classList.add('is-loading');
        setTimeout(function(){ btn.dataset.fqxClicked='0'; btn.dataset.fqxLoading='0'; btn.classList.remove('is-loading'); }, 4500);
      }
    }, true);
  }

  function debounceFilters(){
    var timers = new WeakMap();
    document.querySelectorAll('.fq-bills-filter-bar input[type="search"],.fq-bills-filter-bar input[name="s"],.fq-bills-filter-bar input[name="search"]').forEach(function(input){
      input.addEventListener('input', function(){
        clearTimeout(timers.get(input));
        timers.set(input, setTimeout(function(){
          var form = closest(input, 'form');
          if(form && input.value.length >= 3) {
            if(form.requestSubmit) form.requestSubmit();
            else form.submit();
          }
        }, 550));
      });
    });
  }

  function lazyImages(){
    document.querySelectorAll('.fqx-v145-restaurant-admin img:not([loading])').forEach(function(img){
      if(!img.closest('.sidebar-logo,.topbar')) img.setAttribute('loading','lazy');
      img.setAttribute('decoding','async');
    });
  }



  function adminSidebarMobile(){
    document.addEventListener('click', function(e){
      var toggle = closest(e.target, '.mq-sidebar-toggle');
      if(toggle){
        e.preventDefault();
        document.body.classList.toggle('fqx-sidebar-open');
        var shell = closest(toggle, '.app-shell');
        if(shell){ shell.classList.toggle('fqx-sidebar-open'); shell.classList.toggle('sidebar-open'); }
        return;
      }
      if(closest(e.target, '.mq-sidebar-overlay') || (window.innerWidth <= 1180 && closest(e.target, '.dashboard-shell .sidebar .nav-item'))){
        document.body.classList.remove('fqx-sidebar-open','sidebar-open');
        document.querySelectorAll('.app-shell').forEach(function(shell){ shell.classList.remove('fqx-sidebar-open','sidebar-open'); });
      }
    }, true);
    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape'){
        document.body.classList.remove('fqx-sidebar-open','sidebar-open');
        document.querySelectorAll('.app-shell').forEach(function(shell){ shell.classList.remove('fqx-sidebar-open','sidebar-open'); });
      }
    });
  }

  function init(){
    adminSidebarMobile();
    addIconLabels();
    preventDoubleSubmit();
    debounceFilters();
    lazyImages();
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
