<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_generate_qr_token(): string {
    return wp_generate_password(20, false, false);
}

function menuqr_get_menu_url(int $restaurant_id, int $table_id = 0, int $room_id = 0): string {
    // v200: Newly generated/printed QR links use secure random qr_token.
    // Old numeric URLs continue to work unless Strict Secure QR Mode is enabled.
    if (function_exists('fqx_v200_secure_menu_url')) {
        return fqx_v200_secure_menu_url($restaurant_id, $table_id, $room_id);
    }

    $args = ['r' => $restaurant_id];

    if ($room_id > 0) {
        $room_number = function_exists('menuqr_get_room_display_name') ? menuqr_get_room_display_name($restaurant_id, $room_id, (string) $room_id) : (string) $room_id;
        $args['source'] = 'room';
        $args['room_id'] = $room_id;
        $args['room_number'] = $room_number;
    } else {
        $table_number = function_exists('menuqr_get_table_display_name') ? menuqr_get_table_display_name($restaurant_id, $table_id, (string) $table_id) : (string) $table_id;
        $args['source'] = 'table';
        $args['table_id'] = $table_id;
        $args['table_number'] = $table_number;
        $args['t'] = $table_id;
    }

    return add_query_arg($args, menuqr_get_page_url_by_slug('menu'));
}

function menuqr_get_room_menu_url(int $restaurant_id, int $room_id): string {
    return menuqr_get_menu_url($restaurant_id, 0, $room_id);
}

function menuqr_get_real_qr_image_url(string $url, int $size = 300, string $format = 'png'): string {
    $size = max(120, min(1000, $size));
    $format = strtolower($format);
    if (!in_array($format, ['png', 'svg', 'eps'], true)) {
        $format = 'png';
    }

    return add_query_arg([
        'data' => rawurlencode($url),
        'size' => $size . 'x' . $size,
        'format' => $format,
        'margin' => 10,
    ], 'https://api.qrserver.com/v1/create-qr-code/');
}

function menuqr_qr_svg(string $url, int $size = 180): string {
    $img = menuqr_get_real_qr_image_url($url, $size, 'svg');
    return '<img src="' . esc_url($img) . '" alt="' . esc_attr($url) . '" width="' . (int) $size . '" height="' . (int) $size . '" loading="lazy" decoding="async" />';
}

function menuqr_render_qr_placeholder(string $url, int $size = 180): string {
    return '<img src="' . esc_url(menuqr_get_real_qr_image_url($url, $size, 'png')) . '" alt="' . esc_attr($url) . '" width="' . (int) $size . '" height="' . (int) $size . '" loading="lazy" decoding="async" />';
}

function menuqr_qr_download_url(int $restaurant_id, int $table_id, string $format = 'png'): string {
    return add_query_arg([
        'menuqr_qr_download' => 1,
        'restaurant_id'      => $restaurant_id,
        'table_id'           => $table_id,
        'format'             => $format,
        '_menuqr'           => wp_create_nonce('menuqr_qr_' . $restaurant_id . '_' . $table_id . '_' . $format),
    ], home_url('/'));
}


function menuqr_qr_card_download_url(int $restaurant_id, int $table_id): string {
    return add_query_arg([
        'menuqr_qr_card_download' => 1,
        'restaurant_id'           => $restaurant_id,
        'table_id'                => $table_id,
        '_menuqr'                 => wp_create_nonce('menuqr_qr_card_' . $restaurant_id . '_' . $table_id),
    ], home_url('/'));
}

function menuqr_room_qr_download_url(int $restaurant_id, int $room_id, string $format = 'png'): string {
    return add_query_arg([
        'menuqr_room_qr_download' => 1,
        'restaurant_id'           => $restaurant_id,
        'room_id'                 => $room_id,
        'format'                  => $format,
        '_menuqr'                 => wp_create_nonce('menuqr_room_qr_' . $restaurant_id . '_' . $room_id . '_' . $format),
    ], home_url('/'));
}

function menuqr_room_qr_card_download_url(int $restaurant_id, int $room_id): string {
    return add_query_arg([
        'menuqr_room_qr_card_download' => 1,
        'restaurant_id'                => $restaurant_id,
        'room_id'                      => $room_id,
        '_menuqr'                      => wp_create_nonce('menuqr_room_qr_card_' . $restaurant_id . '_' . $room_id),
    ], home_url('/'));
}

function menuqr_qr_print_url(int $restaurant_id): string {
    return add_query_arg([
        'menuqr_print_qr'  => 1,
        'restaurant_id'    => $restaurant_id,
        'tab'              => 'tables',
    ], menuqr_get_page_url_by_slug('restaurant-dashboard'));
}



function menuqr_qr_templates(): array {
    return [
        'minimal_clean' => [
            'name' => '1. Minimal Clean',
            'badge' => 'Clean white card with red FluuexQR accents',
            'accent' => '#e94560',
            'accent2' => '#ffffff',
            'dark' => false,
            'tagline' => 'Scan to View Menu',
            'headline' => 'THE FOOD HOUSE',
            'subline' => '',
            'theme' => 'minimal',
            'emoji' => '🍽️',
        ],
        'dark_premium' => [
            'name' => '2. Dark Premium',
            'badge' => 'Black neon red scan and order layout',
            'accent' => '#ff1f45',
            'accent2' => '#050505',
            'dark' => true,
            'tagline' => 'SCAN & ORDER',
            'headline' => 'Delicious Food',
            'subline' => 'Great Experience',
            'theme' => 'dark',
            'emoji' => '🌶️',
        ],
        'restaurant_theme' => [
            'name' => '3. Restaurant Theme',
            'badge' => 'Chef plate with fork, spoon and food corners',
            'accent' => '#6b3f28',
            'accent2' => '#fff1dc',
            'dark' => false,
            'tagline' => 'SCAN TO VIEW MENU',
            'headline' => 'Chef Special',
            'subline' => '',
            'theme' => 'restaurant',
            'emoji' => '👨‍🍳',
        ],
        'luxury_gold' => [
            'name' => '4. Luxury Gold',
            'badge' => 'Black and gold premium dining card',
            'accent' => '#d4af37',
            'accent2' => '#050505',
            'dark' => true,
            'tagline' => 'SCAN TO VIEW MENU',
            'headline' => 'PREMIUM DINING',
            'subline' => '',
            'theme' => 'gold',
            'emoji' => '🏨',
        ],
        'modern_gradient' => [
            'name' => '5. Modern Gradient',
            'badge' => 'Purple to orange good-food-good-mood design',
            'accent' => '#ffffff',
            'accent2' => '#7c3aed',
            'dark' => true,
            'tagline' => 'SCAN & ORDER',
            'headline' => 'GOOD FOOD',
            'subline' => 'GOOD MOOD',
            'theme' => 'gradient',
            'emoji' => '✨',
        ],
        'tech_neon' => [
            'name' => '6. Tech Neon',
            'badge' => 'Future of dining neon blue display',
            'accent' => '#00d4ff',
            'accent2' => '#020617',
            'dark' => true,
            'tagline' => 'SCAN TO ORDER',
            'headline' => 'FUTURE OF DINING',
            'subline' => '',
            'theme' => 'tech',
            'emoji' => '⚡',
        ],
        'fast_food_style' => [
            'name' => '7. Fast Food Style',
            'badge' => 'Hungry scan and order fast food poster',
            'accent' => '#ef4444',
            'accent2' => '#facc15',
            'dark' => false,
            'tagline' => 'SCAN & ORDER',
            'headline' => 'HUNGRY?',
            'subline' => '',
            'theme' => 'fastfood',
            'emoji' => '🍔',
        ],
        'cafe_style' => [
            'name' => '8. Café Style',
            'badge' => 'Coffee clipboard and warm café look',
            'accent' => '#8b5e34',
            'accent2' => '#ead0ad',
            'dark' => false,
            'tagline' => 'SCAN TO VIEW MENU',
            'headline' => 'Life Happens',
            'subline' => 'Coffee Helps',
            'theme' => 'cafe',
            'emoji' => '☕',
        ],
        'premium_hotel_style' => [
            'name' => '9. Premium Hotel Style',
            'badge' => 'Elegant cream and gold grand hotel card',
            'accent' => '#c69c45',
            'accent2' => '#fffaf0',
            'dark' => false,
            'tagline' => 'SCAN TO VIEW MENU',
            'headline' => 'THE GRAND HOTEL',
            'subline' => '',
            'theme' => 'hotel',
            'emoji' => '🍽️',
        ],
        'animated_digital' => [
            'name' => '10. Animated Style / Digital',
            'badge' => 'Purple neon digital scan and order card',
            'accent' => '#a855f7',
            'accent2' => '#09011f',
            'dark' => true,
            'tagline' => 'FAST • EASY • CONTACTLESS',
            'headline' => 'SCAN & ORDER',
            'subline' => '',
            'theme' => 'digital',
            'emoji' => '💜',
        ],
    ];
}

function menuqr_qr_template_limit(int $restaurant_id): int {
    /*
     * Keep all 10 designs selectable in the dashboard so restaurants can actually
     * test and print the complete QR template set. Commercial plan-lock messaging
     * can still be shown elsewhere, but saving must not silently fail.
     */
    return count(menuqr_qr_templates());
}

function menuqr_qr_template_keys_for_restaurant(int $restaurant_id): array {
    return array_keys(menuqr_qr_templates());
}

function menuqr_get_restaurant_qr_template(int $restaurant_id): string {
    $template = sanitize_key((string) get_option('menuqr_restaurant_' . $restaurant_id . '_qr_template', 'minimal_clean'));
    $templates = menuqr_qr_templates();
    return isset($templates[$template]) ? $template : 'minimal_clean';
}

function menuqr_set_restaurant_qr_template(int $restaurant_id, string $template): bool {
    $template = sanitize_key($template);
    $templates = menuqr_qr_templates();
    if (!isset($templates[$template])) {
        return false;
    }

    update_option('menuqr_restaurant_' . $restaurant_id . '_qr_template', $template, false);

    // Older builds cached QR choice in browser/server caches; this keeps the
    // selected template immediately visible after save on shared hosting too.
    if (function_exists('wp_cache_delete')) {
        wp_cache_delete('menuqr_restaurant_' . $restaurant_id . '_qr_template', 'options');
        wp_cache_delete('alloptions', 'options');
    }

    return true;
}

function menuqr_get_qr_template_config(int $restaurant_id, string $template = ''): array {
    $templates = menuqr_qr_templates();
    $template = $template ? sanitize_key($template) : menuqr_get_restaurant_qr_template($restaurant_id);
    return $templates[$template] ?? $templates['minimal_clean'];
}

function menuqr_qr_safe_svg_text(string $text): string {
    $text = wp_strip_all_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    return esc_html($text);
}

function menuqr_qr_font_size(string $text, int $base = 42, int $min = 22): int {
    $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    if ($length <= 12) {
        return $base;
    }
    if ($length <= 18) {
        return max($min, $base - 6);
    }
    if ($length <= 26) {
        return max($min, $base - 12);
    }
    return $min;
}

function menuqr_qr_get_restaurant_label(int $restaurant_id): string {
    $restaurant = function_exists('menuqr_get_restaurant') ? menuqr_get_restaurant($restaurant_id) : null;
    $name = $restaurant && !empty($restaurant->name) ? (string) $restaurant->name : get_bloginfo('name');
    return trim($name) ?: 'Restaurant';
}

function menuqr_qr_template_svg_markup(int $restaurant_id, $table_number, string $url, string $template_key = '', int $width = 300, int $height = 480): string {
    $template_key = $template_key ? sanitize_key($template_key) : menuqr_get_restaurant_qr_template($restaurant_id);
    $template = menuqr_get_qr_template_config($restaurant_id, $template_key);
    $restaurant_name_raw = menuqr_qr_get_restaurant_label($restaurant_id);
    $restaurant_name = menuqr_qr_safe_svg_text($restaurant_name_raw);
    $restaurant_name_fs = menuqr_qr_font_size($restaurant_name_raw, 42, 22);
    $table = str_pad((string) $table_number, 2, '0', STR_PAD_LEFT);
    $qr = esc_url(menuqr_get_real_qr_image_url($url, 720, 'png'));
    $accent = esc_attr($template['accent'] ?? '#e94560');
    $accent2 = esc_attr($template['accent2'] ?? '#ffffff');
    $headline = menuqr_qr_safe_svg_text((string) ($template['headline'] ?? 'SCAN & ORDER'));
    $subline = menuqr_qr_safe_svg_text((string) ($template['subline'] ?? ''));
    $tagline = menuqr_qr_safe_svg_text((string) ($template['tagline'] ?? 'SCAN TO VIEW MENU'));
    $dark = !empty($template['dark']);
    $text = $dark ? '#ffffff' : '#1a1a2e';
    $muted = $dark ? '#e5e7eb' : '#4a4f63';

    $brand = <<<SVG
<g class="brand">
  <circle cx="124" cy="72" r="27" fill="none" stroke="{$accent}" stroke-width="5"/>
  <text x="124" y="82" text-anchor="middle" font-size="28" font-weight="900">🍽</text>
  <text x="304" y="78" text-anchor="middle" fill="{$text}" font-family="Arial, Helvetica, sans-serif" font-size="{$restaurant_name_fs}" font-weight="900">{$restaurant_name}</text>
  <text x="304" y="103" text-anchor="middle" fill="{$muted}" font-family="Arial, Helvetica, sans-serif" font-size="12" font-weight="700" letter-spacing="4">SCAN. ORDER. ENJOY.</text>
</g>
SVG;

    $footer = <<<SVG
<g class="footer" font-family="Arial, Helvetica, sans-serif" font-weight="800">
  <circle cx="150" cy="860" r="22" fill="none" stroke="{$accent}" stroke-width="3"/><text x="150" y="868" text-anchor="middle" font-size="22" fill="{$accent}">▣</text><text x="150" y="905" text-anchor="middle" fill="{$text}" font-size="18">SCAN</text>
  <circle cx="300" cy="860" r="22" fill="none" stroke="{$accent}" stroke-width="3"/><text x="300" y="868" text-anchor="middle" font-size="22" fill="{$accent}">⌂</text><text x="300" y="905" text-anchor="middle" fill="{$text}" font-size="18">ORDER</text>
  <circle cx="450" cy="860" r="22" fill="none" stroke="{$accent}" stroke-width="3"/><text x="450" y="868" text-anchor="middle" font-size="22" fill="{$accent}">☺</text><text x="450" y="905" text-anchor="middle" fill="{$text}" font-size="18">ENJOY</text>
</g>
SVG;

    $qr_block = <<<SVG
<rect x="145" y="325" width="310" height="310" rx="20" fill="#ffffff" stroke="{$accent}" stroke-width="8" filter="url(#shadow)"/>
<image href="{$qr}" x="170" y="350" width="260" height="260" preserveAspectRatio="xMidYMid meet"/>
SVG;

    $table_pill = <<<SVG
<rect x="195" y="660" width="210" height="62" rx="16" fill="{$accent}"/>
<text x="300" y="702" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="32" font-weight="900">TABLE {$table}</text>
SVG;

    switch ($template_key) {
        case 'dark_premium':
            $body = <<<SVG
<rect x="15" y="15" width="570" height="930" rx="38" fill="#050505" stroke="{$accent}" stroke-width="3"/>
<path d="M75 38h450l35 35v120M525 922H75l-35-35V760" fill="none" stroke="{$accent}" stroke-width="4" opacity=".78"/>
{$brand}
<text x="300" y="195" text-anchor="middle" fill="#ffffff" font-family="Georgia, serif" font-size="34" font-style="italic">{$headline}</text>
<text x="300" y="248" text-anchor="middle" fill="#ffffff" font-family="Georgia, serif" font-size="34" font-style="italic">{$subline}</text>
<rect x="130" y="308" width="340" height="340" rx="28" fill="none" stroke="{$accent}" stroke-width="5" filter="url(#glow-red)"/>
{$qr_block}
<text x="300" y="720" text-anchor="middle" fill="{$accent}" font-family="Arial, Helvetica, sans-serif" font-size="34" font-weight="900">{$tagline}</text>
<rect x="205" y="760" width="190" height="58" rx="16" fill="#0a0a0a" stroke="{$accent}" stroke-width="4"/>
<text x="300" y="798" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="900">TABLE {$table}</text>
{$footer}
SVG;
            break;

        case 'restaurant_theme':
            $body = <<<SVG
<rect x="15" y="15" width="570" height="930" rx="28" fill="#fff0d9" stroke="#d2a16a" stroke-width="3"/>
<circle cx="65" cy="60" r="32" fill="#d44b32" opacity=".25"/><circle cx="540" cy="60" r="32" fill="#3faa5f" opacity=".25"/>
<circle cx="58" cy="888" r="34" fill="#f97316" opacity=".25"/><circle cx="540" cy="895" r="34" fill="#84cc16" opacity=".22"/>
{$brand}
<path d="M222 205c0-38 34-62 70-48 28-39 92-14 82 35 35-8 59 23 45 53H181c-12-28 10-52 41-40Z" fill="none" stroke="#4b2c1f" stroke-width="8"/>
<path d="M105 255v238M495 255v238" stroke="#4b2c1f" stroke-width="13" stroke-linecap="round"/>
<circle cx="300" cy="480" r="184" fill="#fff9ef" stroke="#6b3f28" stroke-width="5"/>
<image href="{$qr}" x="188" y="368" width="224" height="224" preserveAspectRatio="xMidYMid meet"/>
<path d="M172 690h256l-32 74H204Z" fill="#5b2f1d"/>
<text x="300" y="738" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="36" font-weight="900">TABLE {$table}</text>
<text x="300" y="805" text-anchor="middle" fill="#4b2c1f" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="900">{$tagline}</text>
{$footer}
SVG;
            break;

        case 'luxury_gold':
            $body = <<<SVG
<rect x="15" y="15" width="570" height="930" rx="24" fill="#050505" stroke="{$accent}" stroke-width="4"/>
<path d="M55 92V55h37M508 55h37v37M55 868v37h37M508 905h37v-37" fill="none" stroke="{$accent}" stroke-width="8"/>
<path d="M215 110h170c0-72-170-72-170 0Z" fill="{$accent}"/><rect x="190" y="118" width="220" height="18" rx="9" fill="{$accent}"/>
{$brand}
<text x="300" y="235" text-anchor="middle" fill="{$accent}" font-family="Arial, Helvetica, sans-serif" font-size="38" font-weight="900">{$headline}</text>
<rect x="135" y="310" width="330" height="330" rx="28" fill="none" stroke="{$accent}" stroke-width="8" filter="url(#gold-glow)"/>
{$qr_block}
<text x="300" y="715" text-anchor="middle" fill="{$accent}" font-family="Arial, Helvetica, sans-serif" font-size="36" font-weight="900">TABLE {$table}</text>
<text x="300" y="766" text-anchor="middle" fill="{$accent}" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="800">{$tagline}</text>
{$footer}
SVG;
            break;

        case 'modern_gradient':
            $body = <<<SVG
<defs><linearGradient id="modern" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#4318b8"/><stop offset=".52" stop-color="#c026d3"/><stop offset="1" stop-color="#fb5b1d"/></linearGradient></defs>
<rect x="15" y="15" width="570" height="930" rx="30" fill="url(#modern)"/>
<circle cx="500" cy="100" r="120" fill="#ffffff" opacity=".08"/><circle cx="80" cy="840" r="120" fill="#ffffff" opacity=".08"/>
{$brand}
<text x="300" y="220" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="42" font-weight="900">{$headline}</text>
<text x="300" y="270" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="42" font-weight="900">{$subline}</text>
<rect x="130" y="326" width="340" height="340" rx="30" fill="none" stroke="#ffffff" stroke-width="7"/>
{$qr_block}
<text x="300" y="742" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="34" font-weight="900">TABLE {$table}</text>
<text x="300" y="790" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="800">{$tagline}</text>
{$footer}
SVG;
            break;

        case 'tech_neon':
            $body = <<<SVG
<rect x="15" y="15" width="570" height="930" rx="28" fill="#020617" stroke="{$accent}" stroke-width="3"/>
<path d="M95 290h410M95 650h410M110 305v330M490 305v330" fill="none" stroke="{$accent}" stroke-width="4" opacity=".65" filter="url(#cyan-glow)"/>
{$brand}
<text x="300" y="225" text-anchor="middle" fill="{$accent}" font-family="Arial, Helvetica, sans-serif" font-size="38" font-weight="900">{$headline}</text>
<rect x="128" y="320" width="344" height="344" rx="34" fill="none" stroke="{$accent}" stroke-width="7" filter="url(#cyan-glow)"/>
{$qr_block}
<rect x="204" y="710" width="192" height="60" rx="16" fill="#071827" stroke="{$accent}" stroke-width="4"/>
<text x="300" y="750" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="31" font-weight="900">TABLE {$table}</text>
<text x="300" y="815" text-anchor="middle" fill="{$accent}" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="900">{$tagline}</text>
{$footer}
SVG;
            break;

        case 'fast_food_style':
            $body = <<<SVG
<rect x="15" y="15" width="570" height="930" rx="28" fill="#fffef7" stroke="#ffd000" stroke-width="6"/>
<circle cx="70" cy="70" r="60" fill="#ffd000" opacity=".75"/><circle cx="540" cy="92" r="52" fill="#ffd000" opacity=".75"/><circle cx="62" cy="810" r="60" fill="#ffd000" opacity=".75"/>
{$brand}
<text x="300" y="220" text-anchor="middle" fill="#111827" stroke="#ffd000" stroke-width="10" paint-order="stroke fill" font-family="Arial Black, Arial, sans-serif" font-size="66" font-weight="900" transform="rotate(-5 300 220)">{$headline}</text>
<path d="M172 265h256l-24 76H196Z" fill="#111827"/>
<text x="300" y="314" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="32" font-weight="900">{$tagline}</text>
<rect x="138" y="370" width="324" height="324" rx="28" fill="#ffffff" stroke="#111827" stroke-width="5"/>
<image href="{$qr}" x="170" y="402" width="260" height="260" preserveAspectRatio="xMidYMid meet"/>
<rect x="205" y="725" width="190" height="58" rx="14" fill="#ef4444"/>
<text x="300" y="764" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="900">TABLE {$table}</text>
<text x="95" y="795" font-size="62">🍔</text><text x="430" y="797" font-size="62">🍟</text>
{$footer}
SVG;
            break;

        case 'cafe_style':
            $body = <<<SVG
<rect x="15" y="15" width="570" height="930" rx="28" fill="#efd9bd" stroke="#c69b72" stroke-width="4"/>
<circle cx="65" cy="855" r="18" fill="#6b4423" opacity=".55"/><circle cx="525" cy="840" r="16" fill="#6b4423" opacity=".5"/><circle cx="88" cy="120" r="13" fill="#6b4423" opacity=".4"/>
{$brand}
<text x="300" y="205" text-anchor="middle" fill="#5b3920" font-family="Georgia, serif" font-size="36" font-style="italic">{$headline}</text>
<text x="300" y="250" text-anchor="middle" fill="#5b3920" font-family="Georgia, serif" font-size="36" font-style="italic">{$subline}</text>
<rect x="174" y="295" width="252" height="440" rx="32" fill="#7b4b2b"/>
<rect x="197" y="330" width="206" height="300" rx="18" fill="#ffffff"/>
<image href="{$qr}" x="222" y="355" width="156" height="156" preserveAspectRatio="xMidYMid meet"/>
<text x="300" y="600" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="33" font-weight="900">TABLE {$table}</text>
<text x="300" y="636" text-anchor="middle" fill="#f5ddbf" font-family="Arial, Helvetica, sans-serif" font-size="16" font-weight="800">{$tagline}</text>
<circle cx="445" cy="735" r="64" fill="#f7ead8" stroke="#c69b72" stroke-width="4"/><text x="445" y="755" font-size="66" text-anchor="middle">☕</text>
{$footer}
SVG;
            break;

        case 'premium_hotel_style':
            $body = <<<SVG
<rect x="15" y="15" width="570" height="930" rx="22" fill="#fffaf0" stroke="{$accent}" stroke-width="4"/>
<path d="M55 95V55h40M505 55h40v40M55 865v40h40M505 905h40v-40" fill="none" stroke="{$accent}" stroke-width="6"/>
{$brand}
<text x="300" y="250" text-anchor="middle" fill="#a47722" font-family="Georgia, serif" font-size="31" font-weight="700">{$headline}</text>
<rect x="157" y="335" width="286" height="286" rx="10" fill="#ffffff" stroke="{$accent}" stroke-width="7"/>
<image href="{$qr}" x="190" y="368" width="220" height="220" preserveAspectRatio="xMidYMid meet"/>
<text x="300" y="700" text-anchor="middle" fill="#7c5b20" font-family="Georgia, serif" font-size="34" font-weight="900">TABLE {$table}</text>
<text x="300" y="762" text-anchor="middle" fill="#7c5b20" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="800">{$tagline}</text>
{$footer}
SVG;
            break;

        case 'animated_digital':
            $body = <<<SVG
<rect x="15" y="15" width="570" height="930" rx="28" fill="#09011f" stroke="#2e1065" stroke-width="5"/>
<circle cx="465" cy="215" r="120" fill="#a855f7" opacity=".12"/><circle cx="120" cy="790" r="130" fill="#ec4899" opacity=".08"/>
{$brand}
<text x="300" y="225" text-anchor="middle" fill="#d8b4fe" font-family="Arial, Helvetica, sans-serif" font-size="38" font-weight="900">{$headline}</text>
<text x="300" y="268" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="22" font-weight="800" letter-spacing="3">{$tagline}</text>
<rect x="126" y="325" width="348" height="348" rx="30" fill="none" stroke="#a855f7" stroke-width="7" filter="url(#purple-glow)"/>
<rect x="146" y="345" width="308" height="308" rx="24" fill="#ffffff"/>
<image href="{$qr}" x="174" y="373" width="252" height="252" preserveAspectRatio="xMidYMid meet"/>
<rect x="207" y="716" width="186" height="58" rx="16" fill="#2e1065" stroke="#a855f7" stroke-width="4" filter="url(#purple-glow)"/>
<text x="300" y="755" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="900">TABLE {$table}</text>
{$footer}
SVG;
            break;

        case 'minimal_clean':
        default:
            $body = <<<SVG
<rect x="15" y="15" width="570" height="930" rx="28" fill="#ffffff" stroke="{$accent}" stroke-width="4"/>
{$brand}
<text x="300" y="210" text-anchor="middle" fill="#1a1a2e" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="900">{$restaurant_name}</text>
<text x="300" y="260" text-anchor="middle" fill="#1a1a2e" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="800">TABLE</text>
<line x1="80" y1="300" x2="190" y2="300" stroke="{$accent}" stroke-width="5"/><line x1="410" y1="300" x2="520" y2="300" stroke="{$accent}" stroke-width="5"/>
<text x="300" y="325" text-anchor="middle" fill="#1a1a2e" font-family="Arial, Helvetica, sans-serif" font-size="84" font-weight="900">{$table}</text>
<rect x="135" y="390" width="330" height="330" rx="22" fill="#ffffff" stroke="{$accent}" stroke-width="5"/>
<image href="{$qr}" x="167" y="422" width="266" height="266" preserveAspectRatio="xMidYMid meet"/>
<text x="300" y="775" text-anchor="middle" fill="#1a1a2e" font-family="Arial, Helvetica, sans-serif" font-size="25" font-weight="700">Scan to <tspan fill="{$accent}" font-weight="900">View Menu</tspan></text>
{$footer}
SVG;
            break;
    }

    $svg = <<<SVG
<svg class="menuqr-same-template-svg menuqr-same-template-{$template_key}" xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 600 960" role="img" aria-label="{$restaurant_name} table {$table} QR menu">
  <defs>
    <filter id="shadow"><feDropShadow dx="0" dy="14" stdDeviation="18" flood-color="#000000" flood-opacity=".25"/></filter>
    <filter id="glow-red"><feDropShadow dx="0" dy="0" stdDeviation="10" flood-color="#ff1f45" flood-opacity=".85"/></filter>
    <filter id="gold-glow"><feDropShadow dx="0" dy="0" stdDeviation="8" flood-color="#d4af37" flood-opacity=".75"/></filter>
    <filter id="cyan-glow"><feDropShadow dx="0" dy="0" stdDeviation="8" flood-color="#00d4ff" flood-opacity=".85"/></filter>
    <filter id="purple-glow"><feDropShadow dx="0" dy="0" stdDeviation="10" flood-color="#a855f7" flood-opacity=".85"/></filter>
  </defs>
  {$body}
</svg>
SVG;

    return $svg;
}

function menuqr_render_qr_card_html(int $restaurant_id, $table_number, string $url, int $size = 180, string $template_key = ''): string {
    $template_key = $template_key ? sanitize_key($template_key) : menuqr_get_restaurant_qr_template($restaurant_id);
    return '<div class="menuqr-qr-template-card menuqr-qr-template-' . esc_attr($template_key) . '">' . menuqr_qr_template_svg_markup($restaurant_id, $table_number, $url, $template_key, 280, 448) . '</div>';
}


function menuqr_handle_qr_card_download(): void {
    if (empty($_GET['menuqr_qr_card_download'])) {
        return;
    }

    $restaurant_id = absint($_GET['restaurant_id'] ?? 0);
    $table_id = absint($_GET['table_id'] ?? 0);
    $nonce = sanitize_text_field(wp_unslash($_GET['_menuqr'] ?? ''));

    if (!$restaurant_id || !$table_id || !$nonce || !wp_verify_nonce($nonce, 'menuqr_qr_card_' . $restaurant_id . '_' . $table_id)) {
        wp_die(esc_html__('Invalid QR card request.', 'menuqr'));
    }

    if (!is_user_logged_in()) {
        auth_redirect();
    }

    if (!current_user_can('manage_options') && $restaurant_id !== menuqr_get_current_restaurant_id()) {
        wp_die(esc_html__('Restaurant access denied.', 'menuqr'));
    }

    global $wpdb;
    $tables_table = menuqr_table('tables');
    $table = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tables_table} WHERE id = %d AND restaurant_id = %d",
        $table_id,
        $restaurant_id
    ));

    if (!$table) {
        wp_die(esc_html__('Table not found.', 'menuqr'));
    }

    $menu_url = menuqr_get_menu_url($restaurant_id, (int) $table->id);
    $template_key = menuqr_get_restaurant_qr_template($restaurant_id);
    $svg = menuqr_qr_template_svg_markup($restaurant_id, (string) $table->table_number, $menu_url, $template_key, 600, 960);

    nocache_headers();
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="qr-card-table-' . sanitize_file_name((string) $table->table_number) . '-' . sanitize_file_name($template_key) . '.svg"');
    echo $svg;
    exit;
}
add_action('template_redirect', 'menuqr_handle_qr_card_download');

function menuqr_handle_qr_download(): void {
    if (empty($_GET['menuqr_qr_download'])) {
        return;
    }

    $restaurant_id = absint($_GET['restaurant_id'] ?? 0);
    $table_id = absint($_GET['table_id'] ?? 0);
    $format = sanitize_key(wp_unslash($_GET['format'] ?? 'png'));
    $nonce = sanitize_text_field(wp_unslash($_GET['_menuqr'] ?? ''));

    if (!$restaurant_id || !$table_id || !$nonce || !wp_verify_nonce($nonce, 'menuqr_qr_' . $restaurant_id . '_' . $table_id . '_' . $format)) {
        wp_die(esc_html__('Invalid QR request.', 'menuqr'));
    }

    if (!is_user_logged_in()) {
        auth_redirect();
    }

    if (!current_user_can('manage_options') && $restaurant_id !== menuqr_get_current_restaurant_id()) {
        wp_die(esc_html__('Restaurant access denied.', 'menuqr'));
    }

    global $wpdb;
    $tables_table = menuqr_table('tables');
    $table = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tables_table} WHERE id = %d AND restaurant_id = %d",
        $table_id,
        $restaurant_id
    ));

    if (!$table) {
        wp_die(esc_html__('Table not found.', 'menuqr'));
    }

    $menu_url = menuqr_get_menu_url($restaurant_id, (int) $table->id);
    $remote_qr = menuqr_get_real_qr_image_url($menu_url, 600, $format === 'svg' ? 'svg' : 'png');
    $response = wp_remote_get($remote_qr, ['timeout' => 20]);

    if (is_wp_error($response)) {
        wp_die(esc_html__('QR download failed. Please try again.', 'menuqr'));
    }

    $body = wp_remote_retrieve_body($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    if (!$body) {
        wp_die(esc_html__('QR image not available.', 'menuqr'));
    }

    $extension = 'svg' === $format ? 'svg' : 'png';
    if (!$content_type) {
        $content_type = 'svg' === $format ? 'image/svg+xml' : 'image/png';
    }

    nocache_headers();
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="menuqr-table-' . sanitize_file_name((string) $table->table_number) . '.' . $extension . '"');
    echo $body;
    exit;
}
add_action('template_redirect', 'menuqr_handle_qr_download');


function menuqr_get_qr_template_record(int $restaurant_id, int $table_id): ?object {
    global $wpdb;
    if ($restaurant_id <= 0 || $table_id <= 0) {
        return null;
    }

    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . menuqr_table('qr_templates') . " WHERE restaurant_id = %d AND table_id = %d LIMIT 1",
        $restaurant_id,
        $table_id
    ));
}

function menuqr_prepare_qr_template_payload(int $restaurant_id, int $table_id, string $template_key): array {
    global $wpdb;
    $tables_table = menuqr_table('tables');

    $table = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tables_table} WHERE id = %d AND restaurant_id = %d LIMIT 1",
        $table_id,
        $restaurant_id
    ));

    if (!$table) {
        return [];
    }

    $template_key = sanitize_key($template_key ?: menuqr_get_restaurant_qr_template($restaurant_id));
    $templates = menuqr_qr_templates();
    if (!isset($templates[$template_key])) {
        $template_key = 'minimal_clean';
    }

    $qr_url = menuqr_get_menu_url($restaurant_id, (int) $table->id);
    $qr_image = menuqr_get_real_qr_image_url($qr_url, 720, 'png');
    $design_settings = [
        'template_key' => $template_key,
        'template' => $templates[$template_key],
        'restaurant_name' => menuqr_qr_get_restaurant_label($restaurant_id),
        'table_number' => (string) $table->table_number,
        'cta_text' => (string) ($templates[$template_key]['tagline'] ?? 'Scan to View Menu'),
    ];

    return [
        'restaurant_id' => $restaurant_id,
        'table_id' => (int) $table->id,
        'template_key' => $template_key,
        'qr_url' => $qr_url,
        'qr_image' => $qr_image,
        'design_settings' => wp_json_encode($design_settings),
        'table_number' => (string) $table->table_number,
        'svg_markup' => menuqr_qr_template_svg_markup($restaurant_id, (string) $table->table_number, $qr_url, $template_key, 600, 960),
        'html_markup' => menuqr_render_qr_card_html($restaurant_id, (string) $table->table_number, $qr_url, 180, $template_key),
        'png_download_url' => menuqr_qr_download_url($restaurant_id, (int) $table->id),
        'svg_download_url' => menuqr_qr_card_download_url($restaurant_id, (int) $table->id),
        'print_url' => add_query_arg([
            'menuqr_print_qr_single' => 1,
            'restaurant_id' => $restaurant_id,
            'table_id' => (int) $table->id,
            'template_key' => $template_key,
        ], menuqr_get_page_url_by_slug('restaurant-dashboard')),
    ];
}

function menuqr_save_qr_template_record(int $restaurant_id, int $table_id, string $template_key): array {
    global $wpdb;
    $payload = menuqr_prepare_qr_template_payload($restaurant_id, $table_id, $template_key);
    if (empty($payload)) {
        return ['success' => false, 'message' => 'Table not found.'];
    }

    $now = current_time('mysql');
    $table_name = menuqr_table('qr_templates');
    $existing_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name} WHERE restaurant_id = %d AND table_id = %d LIMIT 1",
        $restaurant_id,
        $table_id
    ));

    $db_payload = [
        'restaurant_id' => $restaurant_id,
        'table_id' => $table_id,
        'template_key' => $payload['template_key'],
        'qr_url' => $payload['qr_url'],
        'qr_image' => $payload['qr_image'],
        'design_settings' => $payload['design_settings'],
        'updated_at' => $now,
    ];

    if ($existing_id > 0) {
        $result = $wpdb->update($table_name, $db_payload, ['id' => $existing_id, 'restaurant_id' => $restaurant_id]);
    } else {
        $db_payload['created_at'] = $now;
        $result = $wpdb->insert($table_name, $db_payload);
        $existing_id = (int) $wpdb->insert_id;
    }

    if ($result === false) {
        return ['success' => false, 'message' => 'QR template could not be saved.'];
    }

    menuqr_set_restaurant_qr_template($restaurant_id, $payload['template_key']);
    $payload['id'] = $existing_id;
    return ['success' => true, 'message' => 'QR template created successfully.', 'record' => $payload];
}

function menuqr_print_qr_single_template(): void {
    if (empty($_GET['menuqr_print_qr_single'])) {
        return;
    }

    $restaurant_id = absint($_GET['restaurant_id'] ?? 0);
    $table_id = absint($_GET['table_id'] ?? 0);
    $template_key = sanitize_key(wp_unslash($_GET['template_key'] ?? ''));

    if (!$restaurant_id || !$table_id) {
        return;
    }

    if (!is_user_logged_in()) {
        auth_redirect();
    }

    if (!current_user_can('manage_options') && $restaurant_id !== menuqr_get_current_restaurant_id()) {
        wp_die(esc_html__('Restaurant access denied.', 'menuqr'));
    }

    $payload = menuqr_prepare_qr_template_payload($restaurant_id, $table_id, $template_key);
    if (empty($payload)) {
        wp_die(esc_html__('QR template not found.', 'menuqr'));
    }

    ?><!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html(menuqr_qr_get_restaurant_label($restaurant_id)); ?> - QR Print</title>
        <?php wp_head(); ?>
        <style>
            body{margin:0;background:#f5f7fb;font-family:Arial,sans-serif}
            .menuqr-print-wrap{min-height:100vh;display:grid;place-items:center;padding:24px}
            .menuqr-print-card{max-width:420px;width:100%;background:#fff;border-radius:28px;padding:24px;box-shadow:0 20px 60px rgba(16,24,40,.16)}
            .menuqr-print-card .menuqr-qr-template-card{max-width:100%}
            @media print{
                body{background:#fff}
                .menuqr-print-wrap{padding:0}
                .menuqr-print-card{box-shadow:none;padding:0;border-radius:0}
            }
        </style>
    </head>
    <body>
        <div class="menuqr-print-wrap">
            <div class="menuqr-print-card"><?php echo $payload['html_markup']; ?></div>
        </div>
        <script>window.addEventListener('load',function(){window.print();});</script>
        <?php wp_footer(); ?>
    </body>
    </html><?php
    exit;
}
add_action('template_redirect', 'menuqr_print_qr_single_template');


function menuqr_handle_room_qr_card_download(): void {
    if (empty($_GET['menuqr_room_qr_card_download'])) {
        return;
    }

    $restaurant_id = absint($_GET['restaurant_id'] ?? 0);
    $room_id = absint($_GET['room_id'] ?? 0);
    $nonce = sanitize_text_field(wp_unslash($_GET['_menuqr'] ?? ''));

    if (!$restaurant_id || !$room_id || !$nonce || !wp_verify_nonce($nonce, 'menuqr_room_qr_card_' . $restaurant_id . '_' . $room_id)) {
        wp_die(esc_html__('Invalid room QR request.', 'menuqr'));
    }

    if (!is_user_logged_in()) {
        auth_redirect();
    }

    global $wpdb;
    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('rooms') . " WHERE id = %d AND restaurant_id = %d", $room_id, $restaurant_id));
    if (!$room) {
        wp_die(esc_html__('Room not found.', 'menuqr'));
    }

    $menu_url = menuqr_get_room_menu_url($restaurant_id, (int) $room->id);
    $svg = menuqr_render_qr_template_svg_markup($restaurant_id, (string) $room->room_number, $menu_url, 'luxury_gold', 600, 960);

    nocache_headers();
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="qr-card-room-' . sanitize_file_name((string) $room->room_number) . '.svg"');
    echo $svg;
    exit;
}
add_action('template_redirect', 'menuqr_handle_room_qr_card_download');

function menuqr_handle_room_qr_download(): void {
    if (empty($_GET['menuqr_room_qr_download'])) {
        return;
    }

    $restaurant_id = absint($_GET['restaurant_id'] ?? 0);
    $room_id = absint($_GET['room_id'] ?? 0);
    $format = sanitize_key(wp_unslash($_GET['format'] ?? 'png'));
    $nonce = sanitize_text_field(wp_unslash($_GET['_menuqr'] ?? ''));

    if (!$restaurant_id || !$room_id || !$nonce || !wp_verify_nonce($nonce, 'menuqr_room_qr_' . $restaurant_id . '_' . $room_id . '_' . $format)) {
        wp_die(esc_html__('Invalid room QR request.', 'menuqr'));
    }

    if (!is_user_logged_in()) {
        auth_redirect();
    }

    global $wpdb;
    $room = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . menuqr_table('rooms') . " WHERE id = %d AND restaurant_id = %d", $room_id, $restaurant_id));

    if (!$room) {
        wp_die(esc_html__('Room not found.', 'menuqr'));
    }

    $menu_url = menuqr_get_room_menu_url($restaurant_id, (int) $room->id);
    $remote_qr = menuqr_get_real_qr_image_url($menu_url, 600, $format === 'svg' ? 'svg' : 'png');
    $response = wp_remote_get($remote_qr, ['timeout' => 20]);

    if (is_wp_error($response)) {
        wp_die(esc_html__('QR download failed. Please try again.', 'menuqr'));
    }

    $body = wp_remote_retrieve_body($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    if (!$body) {
        wp_die(esc_html__('QR image not available.', 'menuqr'));
    }

    $extension = 'svg' === $format ? 'svg' : 'png';
    if (!$content_type) {
        $content_type = 'svg' === $format ? 'image/svg+xml' : 'image/png';
    }

    nocache_headers();
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="menuqr-room-' . sanitize_file_name((string) $room->room_number) . '.' . $extension . '"');
    echo $body;
    exit;
}
add_action('template_redirect', 'menuqr_handle_room_qr_download');
