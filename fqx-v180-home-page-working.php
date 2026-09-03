<?php
/** FluuexQR v180 — Homepage same-to-same working marquee/stats/dynamic sections. */
if (!defined('ABSPATH')) { exit; }

function fqx_v180_is_homepage_context(): bool {
    if (is_admin()) { return false; }
    if (function_exists('is_front_page') && is_front_page()) { return true; }
    if (function_exists('is_home') && is_home()) { return true; }
    if (function_exists('is_page') && is_page(['home','features'])) { return true; }
    return false;
}

function fqx_v180_enqueue_home_page_working_assets(): void {
    if (!fqx_v180_is_homepage_context()) { return; }
    if (function_exists('wp_script_is') && wp_script_is('fluuexqr-v91-v6-mobile-fixed-ui', 'registered') && !wp_script_is('fluuexqr-v91-v6-mobile-fixed-ui', 'enqueued')) {
        // Keep old handle registered if cache plugins reference it, but this v180 script is the source of truth.
    }
    wp_enqueue_script(
        'fqx-v180-home-page-working',
        MENUQR_THEME_URI . '/assets/js/fqx-v180-home-page-working.js',
        [],
        function_exists('menuqr_asset_version') ? menuqr_asset_version('assets/js/fqx-v180-home-page-working.js') : MENUQR_THEME_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'fqx_v180_enqueue_home_page_working_assets', 10050);

function fqx_v180_home_inline_fallback(): void {
    if (!fqx_v180_is_homepage_context()) { return; }
    ?>
    <script id="fqx-v180-home-fallback">
    document.documentElement.classList.add('fqx-v180-home-loaded');
    </script>
    <?php
}
add_action('wp_footer', 'fqx_v180_home_inline_fallback', 10060);
