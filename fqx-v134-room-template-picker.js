(function(){
  'use strict';
  function ready(fn){ if(document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  function updatePicker(picker){
    var radios = picker.querySelectorAll('input[name="room_qr_template"]');
    var selected = '';
    radios.forEach(function(r){
      var opt = r.closest('.fqx-v141-template-option,.fqx-v140-template-option');
      if(!opt) return;
      var badge = opt.querySelector('.fqx-v141-template-top b');
      var cta = opt.querySelector('.fqx-v141-template-cta');
      opt.classList.toggle('is-selected', r.checked);
      if(r.checked){ selected = r.value; if(badge) badge.textContent = 'Selected'; if(cta) cta.textContent = 'Selected ✓'; }
      else { if(badge) badge.textContent = 'Select'; if(cta) cta.textContent = 'Tap to Select'; }
    });
    if(selected) picker.setAttribute('data-selected-template', selected);
  }
  ready(function(){
    document.querySelectorAll('.fqx-v134-template-picker,.fqx-v141-template-picker').forEach(function(picker){
      updatePicker(picker);
      picker.addEventListener('change', function(e){ if(e.target && e.target.name === 'room_qr_template') updatePicker(picker); });
      picker.addEventListener('click', function(e){
        var opt = e.target.closest && e.target.closest('.fqx-v141-template-option,.fqx-v140-template-option');
        if(!opt || !picker.contains(opt)) return;
        var radio = opt.querySelector('input[name="room_qr_template"]');
        if(radio && !radio.checked){ radio.checked = true; radio.dispatchEvent(new Event('change',{bubbles:true})); }
      });
    });
    document.querySelectorAll('.fqx-v142-template-select').forEach(function(sel){
      sel.addEventListener('change', function(){
        var studio = sel.closest('.fqx-v142-room-template-studio');
        var name = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';
        if(studio){
          var label = studio.querySelector('.fqx-v142-current-name');
          if(label) label.textContent = name + ' (not saved yet)';
          studio.classList.add('is-dirty');
        }
      });
    });
  });
})();
