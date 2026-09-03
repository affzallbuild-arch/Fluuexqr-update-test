(function(){
  'use strict';
  function qsa(sel, root){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}
  function closest(el, sel){while(el && el.nodeType===1){if(el.matches(sel)) return el; el=el.parentElement;} return null;}
  function textOf(el){return (el && (el.getAttribute('aria-label') || el.getAttribute('title') || el.textContent || '') || '').replace(/\s+/g,' ').trim();}

  function labelIconButtons(){
    qsa('a,button').forEach(function(el){
      var isIconish = el.querySelector('svg,.dashicons,.fq-svg-icon,.fq-icon') || /^(👁|🖨|💬|✅|💳|🧾|✏️|🗑|📄|🔍|⬇|🔗)/.test((el.textContent||'').trim());
      if(!isIconish) return;
      var label = textOf(el);
      if(!label){
        var href = String(el.getAttribute('href')||'');
        if(href.indexOf('whatsapp')!==-1) label='WhatsApp Bill';
        else if(href.indexOf('print')!==-1) label='Print';
        else if(href.indexOf('bill')!==-1) label='View Bill';
        else label='Action';
      }
      if(!el.getAttribute('title')) el.setAttribute('title', label);
      if(!el.getAttribute('aria-label')) el.setAttribute('aria-label', label);
    });
  }

  function keepLinksClickable(){
    document.addEventListener('click', function(e){
      var link = closest(e.target, 'a');
      if(!link) return;
      var href = link.getAttribute('href') || '';
      if(!href || href === '#') return;
      // Prevent stale loading classes from blocking first tap/open.
      link.classList.remove('is-loading','fqx-v194-saving','fqx-saving');
      link.removeAttribute('aria-busy');
      link.style.pointerEvents = '';
    }, true);
  }

  function categoryTypeFilter(){
    qsa('form select[name="category_id"]').forEach(function(catSelect){
      if(catSelect.dataset.fqxV195CatFilter === '1') return;
      catSelect.dataset.fqxV195CatFilter = '1';
      var form = closest(catSelect, 'form');
      if(!form) return;
      var typeSelect = form.querySelector('select[name="category_type_id"]');
      if(!typeSelect) return;
      function sync(){
        var cat = String(catSelect.value || '');
        var current = typeSelect.options[typeSelect.selectedIndex];
        var selectedAllowed = !current || !current.dataset.categoryId || current.dataset.categoryId === cat;
        qsa('option', typeSelect).forEach(function(opt){
          var oid = String(opt.dataset.categoryId || '');
          var show = !oid || !cat || oid === cat;
          opt.hidden = !show;
          opt.disabled = !show;
        });
        qsa('optgroup', typeSelect).forEach(function(group){
          var visible = qsa('option', group).some(function(opt){return !opt.hidden;});
          group.hidden = !visible;
          group.disabled = !visible;
        });
        if(!selectedAllowed) typeSelect.value = '0';
      }
      catSelect.addEventListener('change', sync);
      sync();
    });
  }

  function safeSubmitStates(){
    qsa('form').forEach(function(form){
      if(form.dataset.fqxV195SubmitSafe === '1') return;
      form.dataset.fqxV195SubmitSafe = '1';
      form.addEventListener('submit', function(){
        var btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if(!btn) return;
        // Only show loading visually; do not disable the button before browser submits.
        btn.classList.add('fqx-v195-saving');
        btn.setAttribute('aria-busy','true');
      }, {capture:false});
    });
    window.addEventListener('pageshow', function(){
      qsa('.fqx-v195-saving,.fqx-v194-saving,.is-loading').forEach(function(el){
        el.classList.remove('fqx-v195-saving','fqx-v194-saving','is-loading');
        el.removeAttribute('aria-busy');
        el.style.pointerEvents='';
      });
    });
  }

  function init(){labelIconButtons(); keepLinksClickable(); categoryTypeFilter(); safeSubmitStates();}
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
