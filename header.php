<?php if (!defined('ABSPATH')) { exit; } ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$menuqr_is_dashboard = function_exists('menuqr_is_dashboard_context') ? menuqr_is_dashboard_context() : false;
$menuqr_is_customer_menu_page = function_exists('menuqr_is_customer_menu_context') ? menuqr_is_customer_menu_context() : false;
$menuqr_is_public_shell = function_exists('menuqr_is_public_shell_context') ? menuqr_is_public_shell_context() : !$menuqr_is_dashboard;
$dashboard_url = function_exists('menuqr_get_dashboard_url') ? menuqr_get_dashboard_url() : home_url('/');
$login_url = function_exists('menuqr_get_page_url_by_slug') ? menuqr_get_page_url_by_slug('login') : wp_login_url();
$signup_url = function_exists('menuqr_get_page_url_by_slug') ? menuqr_get_page_url_by_slug('signup') : wp_registration_url();
$logo_url = function_exists('menuqr_get_brand_logo_url') ? menuqr_get_brand_logo_url() : '';
?>
<?php if ($menuqr_is_public_shell) : ?>
<nav class="nav" id="nav">
  <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-logo" aria-label="FluuexQR Home">
    <?php if (function_exists('fqx_brand_logo_img')) { echo fqx_brand_logo_img('main', 'fqx-header-logo', 'FluuexQR Hotel & Restaurant Automation', 'eager'); } elseif ($logo_url) { ?><img src="<?php echo esc_url($logo_url); ?>" width="1446" height="544" alt="FluuexQR" loading="eager" decoding="async" fetchpriority="high"><?php } else { ?><div class="nav-logo-mark">🍽️</div><?php } ?>
  </a>
  <div class="nav-links">
    <a href="<?php echo esc_url(home_url('/#features')); ?>">Features</a>
    <a href="<?php echo esc_url(home_url('/pricing/')); ?>">Pricing</a>
    <a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a>
    <a href="<?php echo esc_url(home_url('/#how')); ?>">How It Works</a>
    <a href="<?php echo esc_url(home_url('/#locations')); ?>">Locations</a>
    <a href="<?php echo esc_url(home_url('/#faq')); ?>">FAQ</a>
    <a href="<?php echo esc_url(home_url('/#testimonials')); ?>">Reviews</a>
    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>">Contact</a>
  </div>
  <div class="nav-actions">
    <a href="<?php echo esc_url(is_user_logged_in() ? $dashboard_url : $login_url); ?>" class="nav-login"><?php echo is_user_logged_in() ? 'Dashboard' : 'Sign In'; ?></a>
    <a href="<?php echo esc_url(is_user_logged_in() ? $dashboard_url : $signup_url); ?>" class="btn btn-fire btn-pill">Get Started Free</a>
  </div>
  <button type="button" class="hamburger" id="ham" aria-label="Menu" aria-expanded="false" aria-controls="mobileNav">
    <span></span><span></span><span></span>
  </button>
</nav>

<style id="fq98-original-header-restore">
/* FluuexQR v98: Restore v1-v94 public header behavior only */
.nav{
  position:fixed !important;
  top:16px !important;
  left:50% !important;
  right:auto !important;
  transform:translateX(-50%) !important;
  width:calc(100% - 48px) !important;
  max-width:1100px !important;
  z-index:1000 !important;
  background:rgba(10,10,26,.75) !important;
  -webkit-backdrop-filter:blur(24px) !important;
  backdrop-filter:blur(24px) !important;
  border:1px solid rgba(255,255,255,.14) !important;
  border-radius:20px !important;
  padding:0 20px !important;
  min-height:0 !important;
  height:64px !important;
  display:flex !important;
  align-items:center !important;
  justify-content:space-between !important;
  gap:16px !important;
  overflow:visible !important;
}
.nav.scrolled{background:rgba(5,5,15,.9) !important;box-shadow:0 8px 40px rgba(0,0,0,.5) !important;}
.nav-logo{display:flex !important;align-items:center !important;gap:10px !important;min-width:0 !important;max-width:none !important;flex:0 0 auto !important;color:#fff !important;text-decoration:none !important;overflow:visible !important;}
.nav-logo img{display:block !important;width:auto !important;max-width:none !important;max-height:46px !important;height:auto !important;object-fit:contain !important;border-radius:0 !important;}
.nav-links{display:flex !important;align-items:center !important;justify-content:center !important;gap:2px !important;flex:0 1 auto !important;min-width:0 !important;visibility:visible !important;pointer-events:auto !important;}
.nav-links a{display:inline-flex !important;align-items:center !important;color:rgba(255,255,255,.72) !important;text-decoration:none !important;padding:8px 14px !important;border-radius:10px !important;font-size:.88rem !important;font-weight:500 !important;white-space:nowrap !important;background:transparent !important;}
.nav-links a:hover{color:#fff !important;background:rgba(255,255,255,.08) !important;}
.nav-actions{display:flex !important;align-items:center !important;gap:10px !important;visibility:visible !important;pointer-events:auto !important;}
.nav-login{display:inline-flex !important;align-items:center !important;justify-content:center !important;padding:9px 18px !important;border-radius:10px !important;font-size:.87rem !important;font-weight:600 !important;color:rgba(255,255,255,.72) !important;border:1px solid rgba(255,255,255,.12) !important;background:transparent !important;text-decoration:none !important;visibility:visible !important;pointer-events:auto !important;}
.nav-actions .btn{display:inline-flex !important;visibility:visible !important;pointer-events:auto !important;}
.hamburger{display:none !important;align-items:center !important;justify-content:center !important;flex-direction:column !important;gap:5px !important;width:auto !important;height:auto !important;min-width:0 !important;min-height:0 !important;padding:9px !important;margin:0 !important;background:rgba(255,255,255,.06) !important;border-radius:10px !important;border:1px solid rgba(255,255,255,.12) !important;position:relative !important;z-index:1002 !important;}
.hamburger span{display:block !important;width:20px !important;height:2px !important;background:#fff !important;border-radius:2px !important;opacity:1 !important;}
.hamburger.open span:nth-child(1){transform:translateY(7px) rotate(45deg) !important;}
.hamburger.open span:nth-child(2){opacity:0 !important;}
.hamburger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg) !important;}
.mobile-nav{display:none !important;position:fixed !important;top:96px !important;left:24px !important;right:24px !important;background:rgba(10,10,26,.97) !important;-webkit-backdrop-filter:blur(24px) !important;backdrop-filter:blur(24px) !important;border:1px solid rgba(255,255,255,.16) !important;border-radius:20px !important;padding:20px !important;z-index:999 !important;flex-direction:column !important;gap:6px !important;}
.mobile-nav.show{display:flex !important;}
.mobile-nav a{display:block !important;padding:13px 16px !important;border-radius:12px !important;font-weight:600 !important;color:#fff !important;font-size:.95rem !important;text-decoration:none !important;background:transparent !important;}
.mobile-nav a:hover{background:rgba(255,255,255,.08) !important;color:#fff !important;}
.mobile-nav .btn{margin-top:6px !important;width:100% !important;justify-content:center !important;}
@media(max-width:900px){
  .nav{top:10px !important;left:50% !important;right:auto !important;width:calc(100% - 20px) !important;max-width:calc(100% - 20px) !important;height:58px !important;min-height:58px !important;border-radius:16px !important;padding:0 12px !important;gap:10px !important;overflow:visible !important;}
  .nav .nav-links,
  .nav .nav-actions,
  .nav .nav-login,
  .nav .nav-actions .btn{display:none !important;visibility:hidden !important;pointer-events:none !important;}
  .nav .hamburger{display:flex !important;flex:0 0 auto !important;}
  .nav .nav-logo{flex:1 1 auto !important;max-width:calc(100% - 54px) !important;overflow:hidden !important;}
  .nav .nav-logo img{max-height:40px !important;max-width:min(190px,100%) !important;}
  .mobile-nav{top:76px !important;left:10px !important;right:10px !important;border-radius:16px !important;padding:12px !important;max-height:calc(100vh - 90px) !important;overflow:auto !important;}
  .mobile-nav.show{display:flex !important;}
}
@media(max-width:420px){
  .nav{height:56px !important;min-height:56px !important;padding:0 12px !important;}
  .nav .nav-logo img{max-height:38px !important;max-width:min(170px,100%) !important;}
  .hamburger{padding:8px !important;}
}
</style>

<div class="mobile-nav" id="mobileNav">
  <a href="<?php echo esc_url(home_url('/#features')); ?>">⚡ Features</a>
  <a href="<?php echo esc_url(home_url('/pricing/')); ?>">💰 Pricing</a>
  <a href="<?php echo esc_url(home_url('/blog/')); ?>">📝 Blog</a>
  <a href="<?php echo esc_url(home_url('/#how')); ?>">🔧 How It Works</a>
  <a href="<?php echo esc_url(home_url('/#locations')); ?>">📍 Locations</a>
  <a href="<?php echo esc_url(home_url('/#faq')); ?>">❓ FAQ</a>
  <a href="<?php echo esc_url(home_url('/#testimonials')); ?>">⭐ Reviews</a>
  <a href="<?php echo esc_url(home_url('/contact-us/')); ?>">📞 Contact</a>
  <a href="<?php echo esc_url(is_user_logged_in() ? $dashboard_url : $login_url); ?>" class="btn btn-ghost"><?php echo is_user_logged_in() ? 'Dashboard' : 'Sign In'; ?></a>
  <a href="<?php echo esc_url(is_user_logged_in() ? $dashboard_url : $signup_url); ?>" class="btn btn-fire" style="margin-top:4px">🚀 Get Started Free</a>
</div>
<script id="fq100-header-menu-fix">
(function(){
  'use strict';
  function ready(fn){ if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn,{once:true});}else{fn();} }
  ready(function(){
    var ham=document.getElementById('ham');
    var menu=document.getElementById('mobileNav');
    var nav=document.getElementById('nav');
    if(!ham || !menu){return;}
    function setOpen(open){
      menu.classList.toggle('show', !!open);
      ham.classList.toggle('open', !!open);
      ham.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('fq-public-menu-open', !!open);
    }
    if(!ham.dataset.fq100Bound){
      ham.dataset.fq100Bound='1';
      ham.addEventListener('click',function(e){
        e.preventDefault();
        e.stopPropagation();
        setOpen(!menu.classList.contains('show'));
      }, true);
    }
    document.addEventListener('click',function(e){
      if(menu.classList.contains('show') && !menu.contains(e.target) && !ham.contains(e.target)){setOpen(false);}
    });
    document.addEventListener('keydown',function(e){ if(e.key==='Escape'){setOpen(false);} });
    menu.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(){setOpen(false);});});
    if(nav){
      var onScroll=function(){nav.classList.toggle('scrolled', window.scrollY>50);};
      onScroll();
      window.addEventListener('scroll',onScroll,{passive:true});
    }
  });
})();
</script>

<?php endif; ?>
<main class="<?php echo $menuqr_is_dashboard ? 'fq-main fq-main-dashboard' : ($menuqr_is_customer_menu_page ? 'fq-main fq-main-app' : 'fq-main fq-main-public fq90-public-main'); ?>">
