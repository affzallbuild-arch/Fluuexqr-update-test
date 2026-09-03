<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v127
 * - Adds Starter 5 Table plan on existing v125 installs.
 * - Fixes table number lookup so QR/menu/bill/kitchen always shows visible table_number, not random DB id.
 * - Repairs blank/duplicate table numbers once, without touching valid custom numbers.
 */

function fqx_v127_force_plan_sync(): void {
    if ((int) get_option('fqx_v127_starter_plan_synced', 0) >= 1) { return; }
    update_option('fqx_default_plans_version', 0, false);
    if (function_exists('fqx_create_default_plans')) { fqx_create_default_plans(); }
    update_option('fqx_v127_starter_plan_synced', 1, false);
    if (function_exists('menuqr_purge_all_caches')) { menuqr_purge_all_caches('v127_starter_plan_added'); }
}
add_action('init', 'fqx_v127_force_plan_sync', 12);
add_action('after_switch_theme', 'fqx_v127_force_plan_sync', 40);

function fqx_v127_repair_table_numbers(): void {
    if ((int) get_option('fqx_v127_table_numbers_repaired', 0) >= 1) { return; }
    global $wpdb;
    $tables_table = menuqr_table('tables');
    $restaurant_ids = (array) $wpdb->get_col("SELECT DISTINCT restaurant_id FROM {$tables_table}");
    foreach ($restaurant_ids as $restaurant_id) {
        $restaurant_id = (int) $restaurant_id;
        if ($restaurant_id <= 0) { continue; }
        $rows = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT id, table_number FROM {$tables_table} WHERE restaurant_id=%d ORDER BY id ASC",
            $restaurant_id
        ));
        $used = [];
        $next = 1;
        foreach ($rows as $row) {
            $id = (int) $row->id;
            $current = trim((string) $row->table_number);
            $needs_fix = ($current === '' || $current === '0' || isset($used[strtolower($current)]));
            if (!$needs_fix) {
                $used[strtolower($current)] = true;
                if (ctype_digit($current)) { $next = max($next, (int) $current + 1); }
                continue;
            }
            while (isset($used[(string) $next])) { $next++; }
            $new_number = (string) $next;
            $wpdb->update($tables_table, ['table_number' => $new_number, 'updated_at' => current_time('mysql')], ['id' => $id, 'restaurant_id' => $restaurant_id]);
            $used[$new_number] = true;
            $next++;
        }
    }
    update_option('fqx_v127_table_numbers_repaired', 1, false);
}
add_action('init', 'fqx_v127_repair_table_numbers', 30);
