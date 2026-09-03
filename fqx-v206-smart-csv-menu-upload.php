<?php
if (!defined('ABSPATH')) { exit; }

/**
 * FluuexQR v206 Smart CSV Menu Upload
 * Adds a safe CSV menu importer without changing existing manual menu workflow.
 */

if (!function_exists('fqx_v206_csv_bool')) {
    function fqx_v206_csv_bool($value, int $default = 0): int {
        $v = strtolower(trim((string) $value));
        if ($v === '') { return $default; }
        if (in_array($v, ['1','yes','true','y','on','available','active'], true)) { return 1; }
        if (in_array($v, ['0','no','false','n','off','unavailable','inactive','out','out of stock'], true)) { return 0; }
        return $default;
    }
}

if (!function_exists('fqx_v206_normalize_csv_header')) {
    function fqx_v206_normalize_csv_header(string $header): string {
        $h = strtolower(trim($header));
        $h = preg_replace('/[^a-z0-9]+/', '_', $h);
        $h = trim((string) $h, '_');
        $aliases = [
            'item' => 'item_name', 'itemname' => 'item_name', 'item_name' => 'item_name', 'item_names' => 'item_name', 'name' => 'item_name', 'food_name' => 'item_name', 'dish_name' => 'item_name', 'product_name' => 'item_name',
            'rate' => 'price', 'amount' => 'price', 'mrp' => 'price', 'selling_price' => 'price', 'item_price' => 'price',
            'cat' => 'category', 'category_name' => 'category',
            'sub_category' => 'subcategory', 'subcat' => 'subcategory', 'sub_category_name' => 'subcategory', 'type' => 'subcategory', 'item_type' => 'subcategory', 'category_type' => 'subcategory',
            'veg_non_veg' => 'veg_nonveg', 'veg_nonveg' => 'veg_nonveg', 'veg_non_veg_type' => 'veg_nonveg', 'food_type' => 'veg_nonveg', 'veg_type' => 'veg_nonveg',
            'desc' => 'description', 'details' => 'description', 'short_description' => 'description',
            'ingredient' => 'ingredients', 'item_ingredients' => 'ingredients',
            'image' => 'image_url', 'image_url' => 'image_url', 'image_link' => 'image_url', 'photo' => 'image_url', 'filename' => 'image_url', 'image_filename' => 'image_url',
            'offer_price' => 'discount_price', 'discount' => 'discount_price', 'sale_price' => 'discount_price',
            'featured' => 'is_featured', 'is_featured' => 'is_featured',
            'available' => 'is_available', 'availability' => 'is_available', 'is_available' => 'is_available', 'status' => 'is_available',
        ];
        return $aliases[$h] ?? $h;
    }
}

if (!function_exists('fqx_v206_csv_get')) {
    function fqx_v206_csv_get(array $row, string $key, string $default = ''): string {
        return isset($row[$key]) ? trim((string) $row[$key]) : $default;
    }
}

if (!function_exists('fqx_v206_guess_category_type')) {
    function fqx_v206_guess_category_type(string $item_name): array {
        $n = strtolower($item_name);
        $rules = [
            ['keywords' => ['paneer butter','paneer tikka','paneer','kadai paneer','shahi paneer'], 'category' => 'Main Course Veg', 'subcategory' => 'Paneer'],
            ['keywords' => ['chicken biryani','mutton biryani','veg biryani','biryani'], 'category' => 'Biryani', 'subcategory' => 'Biryani'],
            ['keywords' => ['veg noodles','chicken noodles','hakka noodles','noodles','fried rice','manchurian','chilli chicken','chilli paneer'], 'category' => 'Chinese', 'subcategory' => 'Noodles'],
            ['keywords' => ['soup','tomato soup','hot sour','sweet corn'], 'category' => 'Soup', 'subcategory' => 'Soup'],
            ['keywords' => ['cold coffee','coffee','tea','chai','juice','shake','lassi','mocktail','beverage'], 'category' => 'Beverages', 'subcategory' => 'Coffee'],
            ['keywords' => ['gulab jamun','rasgulla','sweet','dessert','ice cream','kheer'], 'category' => 'Sweets', 'subcategory' => 'Dessert'],
            ['keywords' => ['tandoori chicken','chicken tikka','tangdi','kebab','seekh'], 'category' => 'Tandoori', 'subcategory' => 'Chicken Tikka'],
            ['keywords' => ['dosa','idli','uttapam','vada','sambar'], 'category' => 'South Indian', 'subcategory' => 'Dosa'],
            ['keywords' => ['roti','naan','kulcha','paratha','lachha'], 'category' => 'Tandoori Roti', 'subcategory' => 'Bread'],
            ['keywords' => ['dal fry','dal tadka','dal makhani','dal'], 'category' => 'Main Course Veg', 'subcategory' => 'Dal'],
            ['keywords' => ['mutton curry','mutton','fish curry','prawn','chicken curry','butter chicken','murgh'], 'category' => 'Main Course Non Veg', 'subcategory' => 'Mutton'],
            ['keywords' => ['pizza'], 'category' => 'Pizza', 'subcategory' => 'Pizza'],
            ['keywords' => ['burger','sandwich'], 'category' => 'Fast Food', 'subcategory' => 'Burger'],
            ['keywords' => ['starter','fry','crispy'], 'category' => 'Starters', 'subcategory' => 'Starter'],
        ];
        foreach ($rules as $rule) {
            foreach ($rule['keywords'] as $kw) {
                if (strpos($n, $kw) !== false) {
                    if (strpos($n, 'chicken biryani') !== false) { return ['Biryani', 'Chicken Biryani']; }
                    if (strpos($n, 'mutton biryani') !== false) { return ['Biryani', 'Mutton Biryani']; }
                    if (strpos($n, 'veg biryani') !== false) { return ['Biryani', 'Veg Biryani']; }
                    return [$rule['category'], $rule['subcategory']];
                }
            }
        }
        return ['Menu', 'General'];
    }
}

if (!function_exists('fqx_v206_detect_food_type')) {
    function fqx_v206_detect_food_type(string $item_name, string $given = ''): string {
        $g = strtolower(trim($given));
        $g = str_replace([' ', '-'], '_', $g);
        if ($g !== '') {
            if (in_array($g, ['veg','vegetarian'], true)) { return 'veg'; }
            if (in_array($g, ['nonveg','non_veg','nonvegetarian','non_vegetarian'], true)) { return 'nonveg'; }
            if ($g === 'egg') { return 'egg'; }
            if (in_array($g, ['both','veg_nonveg','veg_non_veg','veg_nonveg'], true)) { return 'both'; }
            if (strpos($g, 'non') !== false) { return 'nonveg'; }
        }
        $n = strtolower($item_name);
        if (preg_match('/veg\s*\/\s*non\s*veg|veg\s*non\s*veg|veg\/nonveg/i', $item_name)) { return 'both'; }
        foreach (['chicken','mutton','fish','prawn','murgh','tangdi','non veg','nonveg'] as $kw) { if (strpos($n, $kw) !== false) { return 'nonveg'; } }
        if (strpos($n, 'egg') !== false) { return 'egg'; }
        foreach (['paneer','veg','dal','aloo','mushroom','dosa','idli','roti','naan','coffee','tea','sweet','rasgulla','gulab jamun','kulcha','lassi'] as $kw) { if (strpos($n, $kw) !== false) { return 'veg'; } }
        return 'veg';
    }
}

if (!function_exists('fqx_v206_generate_description')) {
    function fqx_v206_generate_description(string $item_name, string $category, string $description = '', string $ingredients = ''): string {
        if (trim($description) !== '') { return sanitize_textarea_field($description); }
        if (trim($ingredients) !== '') { return sanitize_textarea_field('Made with ' . trim($ingredients) . '.'); }
        $name = trim($item_name) ?: 'Menu item';
        $cat = trim($category);
        if ($cat !== '' && strtolower($cat) !== 'menu') {
            return sanitize_textarea_field('Freshly prepared ' . $name . ' from our ' . $cat . ' menu.');
        }
        return sanitize_textarea_field('Freshly prepared ' . $name . ' served hot.');
    }
}

if (!function_exists('fqx_v206_find_image_url')) {
    function fqx_v206_find_image_url(string $image_value, string $item_name, string $category = ''): string {
        $image_value = trim($image_value);
        if ($image_value !== '') {
            if (preg_match('#^https?://#i', $image_value)) {
                $safe = esc_url_raw($image_value);
                return preg_match('/\.(jpe?g|png|webp|gif)(\?.*)?$/i', $safe) ? $safe : '';
            }
            $filename = sanitize_file_name(basename($image_value));
            if ($filename !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $filename)) {
                $uploads = wp_upload_dir();
                return trailingslashit($uploads['baseurl']) . 'fluuexqr-menu-images/' . rawurlencode($filename);
            }
        }
        $uploads = wp_upload_dir();
        $base_dir = trailingslashit($uploads['basedir']) . 'fluuexqr-menu-images/';
        $base_url = trailingslashit($uploads['baseurl']) . 'fluuexqr-menu-images/';
        $candidates = [];
        $slug = sanitize_title($item_name);
        if ($slug !== '') { $candidates[] = $slug; }
        $cat_slug = sanitize_title($category);
        if ($cat_slug !== '') { $candidates[] = $cat_slug; }
        foreach (array_unique($candidates) as $candidate) {
            foreach (['webp','jpg','jpeg','png'] as $ext) {
                $file = $base_dir . $candidate . '.' . $ext;
                if (file_exists($file)) { return $base_url . rawurlencode($candidate . '.' . $ext); }
            }
        }
        return '';
    }
}

if (!function_exists('fqx_v206_get_or_create_category')) {
    function fqx_v206_get_or_create_category(int $restaurant_id, string $name, bool $dry_run = false, array &$stats = []): int {
        global $wpdb;
        $name = sanitize_text_field($name ?: 'Menu');
        $table = menuqr_table('categories');
        $existing = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE restaurant_id = %d AND LOWER(name) = LOWER(%s) LIMIT 1", $restaurant_id, $name));
        if ($existing > 0) { return $existing; }
        if ($dry_run) { return 0; }
        $now = current_time('mysql');
        $inserted = $wpdb->insert($table, [
            'restaurant_id' => $restaurant_id,
            'name' => $name,
            'description' => '',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        if ($inserted) { $stats['categories_created'] = ($stats['categories_created'] ?? 0) + 1; return (int) $wpdb->insert_id; }
        return 0;
    }
}

if (!function_exists('fqx_v206_get_or_create_type')) {
    function fqx_v206_get_or_create_type(int $restaurant_id, int $category_id, string $name, bool $dry_run = false, array &$stats = []): int {
        global $wpdb;
        if (!function_exists('fqx_v191_category_types_table') || !function_exists('fqx_v191_install_category_types_schema')) { return 0; }
        fqx_v191_install_category_types_schema();
        $name = sanitize_text_field($name ?: 'General');
        $table = fqx_v191_category_types_table();
        $existing = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE restaurant_id = %d AND category_id = %d AND LOWER(name) = LOWER(%s) LIMIT 1", $restaurant_id, $category_id, $name));
        if ($existing > 0) { return $existing; }
        if ($dry_run || $category_id <= 0) { return 0; }
        $now = current_time('mysql');
        $inserted = $wpdb->insert($table, [
            'restaurant_id' => $restaurant_id,
            'category_id' => $category_id,
            'name' => $name,
            'description' => '',
            'sort_order' => 0,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        if ($inserted) { $stats['types_created'] = ($stats['types_created'] ?? 0) + 1; return (int) $wpdb->insert_id; }
        return 0;
    }
}

if (!function_exists('fqx_v206_parse_csv_file')) {
    function fqx_v206_parse_csv_file(string $file_path): array {
        $rows = [];
        $errors = [];
        $handle = fopen($file_path, 'r');
        if (!$handle) { return [[], ['CSV file could not be opened.']]; }
        $headers = fgetcsv($handle, 0, ',');
        if (!$headers || !is_array($headers)) { fclose($handle); return [[], ['CSV header row missing.']]; }
        $map = [];
        foreach ($headers as $i => $h) {
            $key = fqx_v206_normalize_csv_header((string) $h);
            if ($key !== '') { $map[$i] = $key; }
        }
        $has_name = in_array('item_name', $map, true);
        $has_price = in_array('price', $map, true);
        if (!$has_name || !$has_price) {
            fclose($handle);
            return [[], ['CSV must have at least item_name and price columns. Accepted aliases: name/food name and price/rate/amount.']];
        }
        $line = 1;
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $line++;
            if (!array_filter($data, static fn($v) => trim((string) $v) !== '')) { continue; }
            $row = ['_row_number' => $line];
            foreach ($map as $i => $key) {
                $row[$key] = isset($data[$i]) ? sanitize_text_field(wp_unslash((string) $data[$i])) : '';
            }
            $rows[] = $row;
        }
        fclose($handle);
        return [$rows, $errors];
    }
}

if (!function_exists('fqx_v206_build_csv_item')) {
    function fqx_v206_build_csv_item(array $row): array {
        $item_name = sanitize_text_field(fqx_v206_csv_get($row, 'item_name'));
        [$guess_cat, $guess_type] = fqx_v206_guess_category_type($item_name);
        $category = sanitize_text_field(fqx_v206_csv_get($row, 'category', $guess_cat));
        $subcategory = sanitize_text_field(fqx_v206_csv_get($row, 'subcategory', $guess_type));
        if ($category === '') { $category = $guess_cat; }
        if ($subcategory === '') { $subcategory = $guess_type; }
        $price_raw = str_replace([',','₹','Rs.','Rs','INR',' '], '', fqx_v206_csv_get($row, 'price'));
        $discount_raw = str_replace([',','₹','Rs.','Rs','INR',' '], '', fqx_v206_csv_get($row, 'discount_price'));
        $price = is_numeric($price_raw) ? (float) $price_raw : null;
        $discount = ($discount_raw !== '' && is_numeric($discount_raw)) ? (float) $discount_raw : 0.0;
        $food_type = fqx_v206_detect_food_type($item_name, fqx_v206_csv_get($row, 'veg_nonveg'));
        $description = fqx_v206_generate_description($item_name, $category, fqx_v206_csv_get($row, 'description'), fqx_v206_csv_get($row, 'ingredients'));
        $image = fqx_v206_find_image_url(fqx_v206_csv_get($row, 'image_url'), $item_name, $category);
        return [
            'row_number' => (int) ($row['_row_number'] ?? 0),
            'category' => $category,
            'subcategory' => $subcategory,
            'item_name' => $item_name,
            'price' => $price,
            'food_type' => $food_type,
            'description' => $description,
            'image' => $image,
            'discount_price' => $discount,
            'is_featured' => fqx_v206_csv_bool(fqx_v206_csv_get($row, 'is_featured'), 0),
            'is_available' => fqx_v206_csv_bool(fqx_v206_csv_get($row, 'is_available'), 1),
        ];
    }
}

if (!function_exists('fqx_v206_process_rows')) {
    function fqx_v206_process_rows(int $restaurant_id, array $rows, bool $preview_only): array {
        global $wpdb;
        $items_table = menuqr_table('items');
        $has_type_col = function_exists('fqx_v191_column_exists') && fqx_v191_column_exists($items_table, 'category_type_id');
        $stats = [
            'total_rows' => count($rows), 'valid_rows' => 0, 'skipped_rows' => 0,
            'categories_created' => 0, 'types_created' => 0, 'items_created' => 0, 'items_updated' => 0,
            'errors' => [], 'preview' => [], 'mode' => $preview_only ? 'preview' : 'import',
        ];
        foreach ($rows as $raw) {
            $item = fqx_v206_build_csv_item($raw);
            $row_no = $item['row_number'];
            if ($item['item_name'] === '') { $stats['skipped_rows']++; $stats['errors'][] = "Row {$row_no}: item_name is empty."; continue; }
            if ($item['price'] === null || $item['price'] < 0) { $stats['skipped_rows']++; $stats['errors'][] = "Row {$row_no}: price must be numeric."; continue; }
            $stats['valid_rows']++;
            $action = 'Create';
            $existing_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$items_table} WHERE restaurant_id = %d AND LOWER(name) = LOWER(%s) LIMIT 1", $restaurant_id, $item['item_name']));
            if ($existing_id > 0) { $action = 'Update'; }
            if ($preview_only) {
                $stats['preview'][] = array_merge($item, ['action' => $action]);
                continue;
            }
            $category_id = fqx_v206_get_or_create_category($restaurant_id, $item['category'], false, $stats);
            if ($category_id <= 0) { $stats['skipped_rows']++; $stats['errors'][] = "Row {$row_no}: category could not be created."; continue; }
            $type_id = $has_type_col ? fqx_v206_get_or_create_type($restaurant_id, $category_id, $item['subcategory'], false, $stats) : 0;
            $now = current_time('mysql');
            $payload = [
                'restaurant_id' => $restaurant_id,
                'category_id' => $category_id,
                'name' => $item['item_name'],
                'description' => $item['description'],
                'price' => $item['price'],
                'discount_price' => $item['discount_price'],
                'food_type' => $item['food_type'],
                'tax_rate' => 5.0,
                'service_charge_rate' => 0.0,
                'emoji' => '🍽️',
                'variants' => wp_json_encode([]),
                'addons' => wp_json_encode([]),
                'is_available' => $item['is_available'],
                'is_featured' => $item['is_featured'],
                'updated_at' => $now,
            ];
            if ($has_type_col) { $payload['category_type_id'] = $type_id; }
            if ($item['image'] !== '') { $payload['image'] = $item['image']; }
            if ($existing_id > 0) {
                $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$items_table} WHERE id = %d AND restaurant_id = %d", $existing_id, $restaurant_id));
                if ($existing && empty($payload['image'])) { unset($payload['image']); }
                // Preserve existing tax/service/emoji/variants/addons when updating unless CSV explicitly controls normal fields.
                foreach (['tax_rate','service_charge_rate','emoji','variants','addons'] as $preserve) { unset($payload[$preserve]); }
                $ok = false !== $wpdb->update($items_table, $payload, ['id' => $existing_id, 'restaurant_id' => $restaurant_id]);
                if ($ok) { $stats['items_updated']++; } else { $stats['skipped_rows']++; $stats['errors'][] = "Row {$row_no}: item update failed. " . $wpdb->last_error; }
            } else {
                $payload['image'] = $payload['image'] ?? '';
                $payload['created_at'] = $now;
                $ok = false !== $wpdb->insert($items_table, $payload);
                if ($ok) { $stats['items_created']++; } else { $stats['skipped_rows']++; $stats['errors'][] = "Row {$row_no}: item create failed. " . $wpdb->last_error; }
            }
        }
        return $stats;
    }
}

if (!function_exists('fqx_v206_handle_smart_csv_upload')) {
    function fqx_v206_handle_smart_csv_upload(): void {
        menuqr_require_role(['restaurant_admin', 'super_admin', 'staff']);
        check_admin_referer('fqx_v206_smart_csv_upload', 'fqx_csv_nonce');
        $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
        menuqr_validate_restaurant_access($restaurant_id);
        if (function_exists('fqx_v167_is_limited_staff_user') && fqx_v167_is_limited_staff_user() && function_exists('fqx_v167_staff_can_access_tab') && !fqx_v167_staff_can_access_tab('menu')) {
            wp_die(esc_html__('Menu import access denied.', 'menuqr'));
        }
        $redirect = add_query_arg(['tab' => 'menu'], menuqr_get_page_url_by_slug('restaurant-dashboard')) . '#fqSmartCsvMenuUpload';
        if (empty($_FILES['csv_file']['name']) || !isset($_FILES['csv_file']['tmp_name'])) {
            wp_safe_redirect(add_query_arg(['mq_notice' => 'fqx_csv_error', 'mq_error' => rawurlencode('Please choose a CSV file.')], $redirect)); exit;
        }
        $file = $_FILES['csv_file'];
        if (!empty($file['error'])) {
            wp_safe_redirect(add_query_arg(['mq_notice' => 'fqx_csv_error', 'mq_error' => rawurlencode('CSV upload failed.')], $redirect)); exit;
        }
        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            wp_safe_redirect(add_query_arg(['mq_notice' => 'fqx_csv_error', 'mq_error' => rawurlencode('CSV file is too large. Maximum 2MB allowed.')], $redirect)); exit;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $mime = function_exists('mime_content_type') ? (string) mime_content_type((string) $file['tmp_name']) : '';
        if ($ext !== 'csv' || ($mime && !in_array($mime, ['text/plain','text/csv','application/csv','application/vnd.ms-excel','text/x-csv'], true))) {
            wp_safe_redirect(add_query_arg(['mq_notice' => 'fqx_csv_error', 'mq_error' => rawurlencode('Only CSV files are allowed.')], $redirect)); exit;
        }
        [$rows, $parse_errors] = fqx_v206_parse_csv_file((string) $file['tmp_name']);
        $preview_only = !empty($_POST['preview_only']);
        $result = $parse_errors ? ['total_rows'=>0,'valid_rows'=>0,'skipped_rows'=>0,'categories_created'=>0,'types_created'=>0,'items_created'=>0,'items_updated'=>0,'errors'=>$parse_errors,'preview'=>[],'mode'=>$preview_only?'preview':'import'] : fqx_v206_process_rows($restaurant_id, $rows, $preview_only);
        $key = 'fqx_csv_' . wp_generate_password(16, false, false);
        set_transient($key, $result, 15 * MINUTE_IN_SECONDS);
        $notice = $preview_only ? 'fqx_csv_preview' : 'fqx_csv_imported';
        wp_safe_redirect(add_query_arg(['mq_notice' => $notice, 'fqx_csv_result' => rawurlencode($key)], $redirect));
        exit;
    }
    add_action('admin_post_fqx_v206_smart_csv_upload', 'fqx_v206_handle_smart_csv_upload');
}

if (!function_exists('fqx_v206_sample_csv_url')) {
    function fqx_v206_sample_csv_url(): string {
        return get_template_directory_uri() . '/assets/samples/fluuexqr-smart-menu-sample-v206.csv';
    }
}

if (!function_exists('fqx_v206_render_smart_csv_box')) {
    function fqx_v206_render_smart_csv_box(int $restaurant_id): void {
        $result = null;
        $result_key = sanitize_text_field(wp_unslash($_GET['fqx_csv_result'] ?? ''));
        if ($result_key !== '') { $result = get_transient($result_key); }
        ?>
        <section id="fqSmartCsvMenuUpload" class="fq-menu-table-card fqx-smart-csv-card" style="margin:18px 0;">
            <div class="fq-cat-card-head" style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;">
                <div><h3 style="margin:0 0 6px;">Smart CSV Menu Upload</h3><p style="margin:0;color:#64748b;">Upload menu CSV. Only <strong>item_name</strong> and <strong>price</strong> are required. Blank category, type, veg/nonveg, description and image are auto handled.</p></div>
                <a class="btn btn-outline btn-sm" href="<?php echo esc_url(fqx_v206_sample_csv_url()); ?>" download>Download sample CSV</a>
            </div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="margin-top:14px;display:grid;gap:12px;">
                <?php wp_nonce_field('fqx_v206_smart_csv_upload', 'fqx_csv_nonce'); ?>
                <input type="hidden" name="action" value="fqx_v206_smart_csv_upload">
                <input type="hidden" name="restaurant_id" value="<?php echo esc_attr((string) $restaurant_id); ?>">
                <input type="hidden" name="_menuqr_redirect" value="<?php echo esc_url(add_query_arg(['tab'=>'menu'], menuqr_get_page_url_by_slug('restaurant-dashboard'))); ?>#fqSmartCsvMenuUpload">
                <div class="form-row" style="display:grid;grid-template-columns:minmax(220px,1fr) auto auto;gap:12px;align-items:end;">
                    <label class="form-group"><span class="form-label">CSV file upload</span><input class="form-input" type="file" name="csv_file" accept=".csv,text/csv" required></label>
                    <label class="form-check" style="padding:12px 14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;"><input type="checkbox" name="preview_only" value="1"> Preview only</label>
                    <button class="btn btn-primary" type="submit">Import CSV Menu</button>
                </div>
                <div class="text-muted fs-sm" style="font-size:13px;color:#64748b;">Supported columns: category, subcategory, item_name, price, veg_nonveg, description, ingredients, image_url, discount_price, is_featured, is_available. Image is optional; if missing, item will still upload.</div>
            </form>
            <?php if (is_array($result)) : ?>
                <div class="fqx-csv-result" style="margin-top:16px;border:1px solid #e5e7eb;border-radius:14px;padding:14px;background:#f8fafc;">
                    <h4 style="margin:0 0 10px;"><?php echo esc_html(($result['mode'] ?? '') === 'preview' ? 'Preview Result' : 'Import Result'); ?></h4>
                    <div class="fq-menu-stats" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr));margin:0 0 12px;">
                        <div class="fq-menu-stat-card"><div><small>Total Rows</small><strong><?php echo esc_html((string) ($result['total_rows'] ?? 0)); ?></strong></div></div>
                        <div class="fq-menu-stat-card green"><div><small>Valid Rows</small><strong><?php echo esc_html((string) ($result['valid_rows'] ?? 0)); ?></strong></div></div>
                        <div class="fq-menu-stat-card red"><div><small>Skipped</small><strong><?php echo esc_html((string) ($result['skipped_rows'] ?? 0)); ?></strong></div></div>
                        <div class="fq-menu-stat-card purple"><div><small>Categories</small><strong><?php echo esc_html((string) ($result['categories_created'] ?? 0)); ?></strong></div></div>
                        <div class="fq-menu-stat-card blue"><div><small>Types</small><strong><?php echo esc_html((string) ($result['types_created'] ?? 0)); ?></strong></div></div>
                        <div class="fq-menu-stat-card"><div><small>Created</small><strong><?php echo esc_html((string) ($result['items_created'] ?? 0)); ?></strong></div></div>
                        <div class="fq-menu-stat-card green"><div><small>Updated</small><strong><?php echo esc_html((string) ($result['items_updated'] ?? 0)); ?></strong></div></div>
                    </div>
                    <?php if (!empty($result['preview'])) : ?>
                        <div class="table-wrap"><table class="data-table fq-menu-table"><thead><tr><th>Category</th><th>Subcategory</th><th>Item Name</th><th>Price</th><th>Veg/NonVeg</th><th>Description</th><th>Image</th><th>Action</th></tr></thead><tbody>
                        <?php foreach (array_slice((array) $result['preview'], 0, 200) as $p) : ?>
                            <tr><td><?php echo esc_html($p['category'] ?? ''); ?></td><td><?php echo esc_html($p['subcategory'] ?? ''); ?></td><td><?php echo esc_html($p['item_name'] ?? ''); ?></td><td><?php echo esc_html((string) ($p['price'] ?? '')); ?></td><td><?php echo esc_html($p['food_type'] ?? ''); ?></td><td><?php echo esc_html($p['description'] ?? ''); ?></td><td><?php echo !empty($p['image']) ? '<span title="' . esc_attr($p['image']) . '">Image found</span>' : '<span>Default/blank</span>'; ?></td><td><?php echo esc_html($p['action'] ?? ''); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                    <?php if (!empty($result['errors'])) : ?><div class="alert alert-warning" style="margin-top:12px;"><strong>Errors:</strong><br><?php foreach ((array) $result['errors'] as $err) : ?><?php echo esc_html((string) $err); ?><br><?php endforeach; ?></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
        <style>@media(max-width:760px){.fqx-smart-csv-card .form-row{grid-template-columns:1fr!important}.fqx-smart-csv-card .btn{width:100%;justify-content:center}}</style>
        <?php
    }
}
