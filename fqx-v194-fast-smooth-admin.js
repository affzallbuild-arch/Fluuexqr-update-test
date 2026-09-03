(function(){
  'use strict';
  function closest(el, sel){ while(el && el.nodeType === 1){ if(el.matches(sel)) return el; el = el.parentElement; } return null; }
  function qsa(sel, root){ return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function makeBillLinksOpenOnFirstTap(){
    qsa('.fq-bill-actions a, .fq-bills-table a, a[href*="bill"], a[href*="print"], a[href*="whatsapp"]').forEach(function(a){
      a.classList.remove('is-loading');
      a.removeAttribute('data-fqx-clicked');
      a.removeAttribute('data-fqx-loading');
      if(!a.getAttribute('title')){
        var txt = (a.textContent || '').trim() || 'Open bill';
        a.setAttribute('title', txt);
      }
    });
    document.addEventListener('click', function(e){
      var a = closest(e.target, 'a');
      if(!a) return;
      var href = a.getAttribute('href') || '';
      if(!href || href === '#' || href.indexOf('javascript:') === 0) return;
      if(a.matches('.fq-bill-actions a, .fq-bills-table a, a[href*="bill"], a[href*="print"], a[href*="whatsapp"]')){
        a.classList.remove('is-loading');
        a.removeAttribute('data-fqx-clicked');
        a.removeAttribute('data-fqx-loading');
      }
    }, true);
  }

  function smoothForms(){
    var selectors = [
      'form[action*="admin-post.php"] input[name="action"][value="menuqr_save_category"]',
      'form[action*="admin-post.php"] input[name="action"][value="fqx_save_category_type"]',
      'form[action*="admin-post.php"] input[name="action"][value="menuqr_mark_bill_payment"]',
      'form[action*="admin-post.php"] input[name="action"][value="menuqr_close_bill_session"]'
    ].join(',');
    qsa(selectors).forEach(function(input){
      var form = closest(input, 'form');
      if(!form || form.dataset.fqxV194Bound === '1') return;
      form.dataset.fqxV194Bound = '1';
      form.addEventListener('submit', function(){
        var btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if(btn){
          btn.classList.add('fqx-v194-saving');
          btn.setAttribute('aria-busy','true');
          if(btn.tagName === 'BUTTON'){
            btn.dataset.originalText = btn.dataset.originalText || btn.textContent;
            if((input.value || '').indexOf('category') !== -1) btn.textContent = 'Saving...';
            else btn.textContent = btn.dataset.originalText;
          }
        }
      }, {passive:true});
    });
  }

  function categoryTypeFilterSmooth(){
    var categorySelects = qsa('select[name="category_id"]');
    categorySelects.forEach(function(sel){
      if(sel.dataset.fqxV194CatBound === '1') return;
      sel.dataset.fqxV194CatBound = '1';
      sel.addEventListener('change', function(){
        var form = closest(sel, 'form');
        if(!form) return;
        var typeSelect = form.querySelector('select[name="category_type_id"]');
        if(typeSelect){
          typeSelect.classList.add('fqx-v194-highlight');
          setTimeout(function(){ typeSelect.classList.remove('fqx-v194-highlight'); }, 1200);
        }
      }, {passive:true});
    });
  }

  function fastMobileTables(){
    qsa('.fqx-v145-restaurant-admin .table-wrap, .fqx-v145-restaurant-admin .table-scroll').forEach(function(el){
      el.style.webkitOverflowScrolling = 'touch';
    });
  }

  function clearStaleUiAfterBackForwardCache(){
    window.addEventListener('pageshow', function(event){
      if(event.persisted){
        qsa('.is-loading,.fqx-v194-saving').forEach(function(el){ el.classList.remove('is-loading','fqx-v194-saving'); el.removeAttribute('aria-busy'); });
      }
    });
  }

  function init(){
    makeBillLinksOpenOnFirstTap();
    smoothForms();
    categoryTypeFilterSmooth();
    fastMobileTables();
    clearStaleUiAfterBackForwardCache();
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
