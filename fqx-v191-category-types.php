<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v191 Category Types / Subcategories
 * Adds category-level menu types such as Starters > Soups without changing existing category/item workflow.
 */

if (!function_exists('fqx_v191_category_types_table')) {
    function fqx_v191_category_types_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'qrmenu_category_types';
    }
}

if (!function_exists('fqx_v191_column_exists')) {
    function fqx_v191_column_exists(string $table, string $column): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE %s', $column));
    }
}

if (!function_exists('fqx_v191_install_category_types_schema')) {
    function fqx_v191_install_category_types_schema(): void {
        global $wpdb;
        $installed = get_option('fqx_v191_category_types_schema');
        $types_table = fqx_v191_category_types_table();
        $items_table = function_exists('menuqr_table') ? menuqr_table('items') : $wpdb->prefix . 'qrmenu_items';
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE {$types_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            restaurant_id BIGINT UNSIGNED NOT NULL,
            category_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(191) NOT NULL,
            description TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY restaurant_id (restaurant_id),
            KEY category_id (category_id),
            KEY restaurant_category (restaurant_id, category_id),
            KEY is_active (is_active)
        ) {$charset};");

        if (!fqx_v191_column_exists($items_table, 'category_type_id')) {
            $wpdb->query("ALTER TABLE {$items_table} ADD COLUMN category_type_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER category_id");
        }
        if (!fqx_v191_column_exists($items_table, 'category_type_id')) {
            // If host blocks ALTER, avoid hard failure; UI will still manage types.
            update_option('fqx_v191_category_types_column_missing', 1, false);
        } else {
            delete_option('fqx_v191_category_types_column_missing');
            $index_exists = (bool) $wpdb->get_var("SHOW INDEX FROM {$items_table} WHERE Key_name = 'category_type_id'");
            if (!$index_exists) {
                $wpdb->query("ALTER TABLE {$items_table} ADD KEY category_type_id (category_type_id)");
            }
        }

        if ($installed !== '1') {
            update_option('fqx_v191_category_types_schema', '1', false);
        }
    }
    add_action('init', 'fqx_v191_install_category_types_schema', 5);
}

if (!function_exists('fqx_v191_get_category_types')) {
    function fqx_v191_get_category_types(int $restaurant_id, int $category_id = 0): array {
        global $wpdb;
        fqx_v191_install_category_types_schema();
        $table = fqx_v191_category_types_table();
        if ($category_id > 0) {
            return (array) $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d AND category_id = %d ORDER BY sort_order ASC, name ASC", $restaurant_id, $category_id));
        }
        return (array) $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d ORDER BY category_id ASC, sort_order ASC, name ASC", $restaurant_id));
    }
}

if (!function_exists('fqx_v191_get_category_types_grouped')) {
    function fqx_v191_get_category_types_grouped(int $restaurant_id): array {
        $grouped = [];
        foreach (fqx_v191_get_category_types($restaurant_id) as $type) {
            $cid = (int) ($type->category_id ?? 0);
            if ($cid <= 0) { continue; }
            $grouped[$cid][] = $type;
        }
        return $grouped;
    }
}

if (!function_exists('fqx_v191_get_category_type_map')) {
    function fqx_v191_get_category_type_map(int $restaurant_id): array {
        $map = [];
        foreach (fqx_v191_get_category_types($restaurant_id) as $type) {
            $map[(int) $type->id] = $type;
        }
        return $map;
    }
}

if (!function_exists('fqx_v191_handle_save_category_type')) {
    function fqx_v191_handle_save_category_type(): void {
        if (!function_exists('menuqr_require_role')) { wp_die('Unauthorized'); }
        menuqr_require_role(['restaurant_admin', 'super_admin']);
        check_admin_referer('fqx_save_category_type', 'fqx_category_type_nonce');
        global $wpdb;
        fqx_v191_install_category_types_schema();
        $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
        menuqr_validate_restaurant_access($restaurant_id);
        $id = absint($_POST['category_type_id'] ?? 0);
        $category_id = absint($_POST['category_id'] ?? 0);
        $name = sanitize_text_field(wp_unslash($_POST['type_name'] ?? ''));
        $description = sanitize_textarea_field(wp_unslash($_POST['type_description'] ?? ''));
        $sort_order = absint($_POST['type_sort_order'] ?? 0);
        $active = !empty($_POST['is_active']) ? 1 : 0;

        if ($restaurant_id <= 0 || $category_id <= 0 || $name === '') {
            wp_safe_redirect(add_query_arg(['tab' => 'menu', 'section' => 'categories', 'mq_notice' => 'category_type_error'], menuqr_get_page_url_by_slug('restaurant-dashboard')));
            exit;
        }

        $category_exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . menuqr_table('categories') . " WHERE id = %d AND restaurant_id = %d", $category_id, $restaurant_id));
        if ($category_exists <= 0) {
            wp_safe_redirect(add_query_arg(['tab' => 'menu', 'section' => 'categories', 'mq_notice' => 'category_type_error'], menuqr_get_page_url_by_slug('restaurant-dashboard')));
            exit;
        }

        $table = fqx_v191_category_types_table();
        $payload = [
            'restaurant_id' => $restaurant_id,
            'category_id' => $category_id,
            'name' => $name,
            'description' => $description,
            'sort_order' => $sort_order,
            'is_active' => $active,
            'updated_at' => current_time('mysql'),
        ];
        if ($id > 0) {
            $wpdb->update($table, $payload, ['id' => $id, 'restaurant_id' => $restaurant_id]);
        } else {
            $payload['created_at'] = current_time('mysql');
            $wpdb->insert($table, $payload);
        }
        wp_safe_redirect(add_query_arg(['tab' => 'menu', 'section' => 'categories', 'mq_notice' => 'category_type_saved'], menuqr_get_page_url_by_slug('restaurant-dashboard')) . '#fqCategoryTypeForm');
        exit;
    }
    add_action('admin_post_fqx_save_category_type', 'fqx_v191_handle_save_category_type');
}

if (!function_exists('fqx_v191_handle_delete_category_type')) {
    function fqx_v191_handle_delete_category_type(): void {
        if (!function_exists('menuqr_require_role')) { wp_die('Unauthorized'); }
        menuqr_require_role(['restaurant_admin', 'super_admin']);
        check_admin_referer('fqx_delete_category_type', 'fqx_category_type_delete_nonce');
        global $wpdb;
        fqx_v191_install_category_types_schema();
        $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
        $type_id = absint($_POST['category_type_id'] ?? 0);
        menuqr_validate_restaurant_access($restaurant_id);
        $items_table = menuqr_table('items');
        if (fqx_v191_column_exists($items_table, 'category_type_id')) {
            $items_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$items_table} WHERE restaurant_id = %d AND category_type_id = %d", $restaurant_id, $type_id));
            if ($items_count > 0) {
                wp_safe_redirect(add_query_arg(['tab' => 'menu', 'section' => 'categories', 'mq_notice' => 'category_type_has_items'], menuqr_get_page_url_by_slug('restaurant-dashboard')) . '#fqCategoryTypeForm');
                exit;
            }
        }
        $deleted = false !== $wpdb->delete(fqx_v191_category_types_table(), ['id' => $type_id, 'restaurant_id' => $restaurant_id]);
        wp_safe_redirect(add_query_arg(['tab' => 'menu', 'section' => 'categories', 'mq_notice' => $deleted ? 'category_type_deleted' : 'category_type_error'], menuqr_get_page_url_by_slug('restaurant-dashboard')) . '#fqCategoryTypeForm');
        exit;
    }
    add_action('admin_post_fqx_delete_category_type', 'fqx_v191_handle_delete_category_type');
}

if (!function_exists('fqx_v191_category_type_options_html')) {
    function fqx_v191_category_type_options_html(int $restaurant_id, int $selected_type_id = 0, int $selected_category_id = 0): string {
        $categories = function_exists('menuqr_get_categories') ? menuqr_get_categories($restaurant_id) : [];
        $grouped = fqx_v191_get_category_types_grouped($restaurant_id);
        ob_start();
        echo '<option value="0">No Type / Direct Category</option>';
        foreach ($categories as $cat) {
            $cid = (int) $cat->id;
            if (empty($grouped[$cid])) { continue; }
            echo '<optgroup label="' . esc_attr($cat->name) . '">';
            foreach ($grouped[$cid] as $type) {
                $label = $type->name;
                if ($selected_category_id > 0 && $selected_category_id !== $cid) {
                    $label .= ' — ' . $cat->name;
                }
                echo '<option data-category-id="' . esc_attr((string) $cid) . '" value="' . esc_attr((string) $type->id) . '" ' . selected($selected_type_id, (int) $type->id, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</optgroup>';
        }
        return (string) ob_get_clean();
    }
}
