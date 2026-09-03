(function(){
  function boot(){
    var nav=document.getElementById('fq90Nav');
    var ham=document.getElementById('fq90Ham');
    var mob=document.getElementById('fq90MobileNav');
    if(nav){
      var onScroll=function(){nav.classList.toggle('scrolled', window.scrollY>50);};
      onScroll(); window.addEventListener('scroll',onScroll,{passive:true});
    }
    if(ham&&mob){
      function setOpen(open){mob.classList.toggle('show',open);mob.setAttribute('aria-hidden',open?'false':'true');ham.setAttribute('aria-expanded',open?'true':'false');}
      ham.addEventListener('click',function(e){e.preventDefault();setOpen(!mob.classList.contains('show'));});
      document.addEventListener('click',function(e){if(mob.classList.contains('show')&&!mob.contains(e.target)&&!ham.contains(e.target))setOpen(false);});
      mob.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(){setOpen(false);});});
      document.addEventListener('keydown',function(e){if(e.key==='Escape')setOpen(false);});
    }
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();
