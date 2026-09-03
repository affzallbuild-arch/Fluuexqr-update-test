
<?php
if (!defined('ABSPATH')) { exit; }

function fqx_v167_staff_role_alias(string $role): string {
    $role = sanitize_key($role ?: 'waiter');
    $map = [
        'chef' => 'kitchen',
        'kitchen_staff' => 'kitchen',
        'kitchen-staff' => 'kitchen',
        'restaurant_manager' => 'manager',
        'restaurant-manager' => 'manager',
        'room-service' => 'room_service',
        'room_service_staff' => 'room_service',
        'service' => 'waiter',
        'service_staff' => 'waiter',
        'steward' => 'steward',
        'front-office' => 'front_office',
        'frontoffice' => 'front_office',
        'house_keeping' => 'housekeeping',
        'support' => 'housekeeping',
    ];
    return $map[$role] ?? $role;
}

function fqx_v167_staff_member_for_user(int $user_id): ?object {
    global $wpdb;
    if ($user_id <= 0 || !function_exists('menuqr_table')) { return null; }
    $staff_table = menuqr_table('staff');
    $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$staff_table} WHERE wp_user_id = %d ORDER BY id DESC LIMIT 1", $user_id));
    if ($member) { return $member; }
    $user = get_userdata($user_id);
    if ($user && $user->user_email) {
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$staff_table} WHERE email = %s ORDER BY id DESC LIMIT 1", $user->user_email));
    }
    return null;
}

function fqx_v167_current_staff_member(): ?object {
    return is_user_logged_in() ? fqx_v167_staff_member_for_user(get_current_user_id()) : null;
}

function fqx_v167_staff_role_for_user(int $user_id = 0): string {
    $user_id = $user_id ?: get_current_user_id();
    $member = fqx_v167_staff_member_for_user($user_id);
    if ($member && !empty($member->role_name)) { return fqx_v167_staff_role_alias((string) $member->role_name); }
    $meta = (string) get_user_meta($user_id, 'menuqr_staff_role_name', true);
    return fqx_v167_staff_role_alias($meta ?: 'waiter');
}

function fqx_v167_role_department(string $role): string {
    $role = fqx_v167_staff_role_alias($role);
    $map = [
        'manager' => 'Management', 'cashier' => 'Front Office', 'front_office' => 'Front Office',
        'chef' => 'Kitchen', 'kitchen' => 'Kitchen', 'room_service' => 'Service', 'waiter' => 'Service', 'steward' => 'Housekeeping',
        'delivery' => 'Delivery', 'housekeeping' => 'Housekeeping'
    ];
    return $map[$role] ?? 'Service';
}

function fqx_v167_role_assigned_area(string $role): string {
    $role = fqx_v167_staff_role_alias($role);
    $map = ['manager'=>'Main Building','cashier'=>'Front Office','front_office'=>'Front Office','kitchen'=>'Kitchen','chef'=>'Kitchen','room_service'=>'2nd Floor','waiter'=>'Main Dining','steward'=>'Main Building','delivery'=>'Delivery Zone','housekeeping'=>'Guest Floors'];
    return $map[$role] ?? 'Main Building';
}

function fqx_v167_role_shift(string $role): string {
    $role = fqx_v167_staff_role_alias($role);
    $map = ['manager'=>'9:00 AM - 6:00 PM','cashier'=>'10:00 AM - 7:00 PM','front_office'=>'9:00 AM - 6:00 PM','kitchen'=>'8:00 AM - 4:00 PM','chef'=>'8:00 AM - 4:00 PM','room_service'=>'7:00 AM - 3:00 PM','waiter'=>'2:00 PM - 10:00 PM','steward'=>'9:00 AM - 5:00 PM','delivery'=>'12:00 PM - 9:00 PM','housekeeping'=>'8:00 AM - 4:00 PM'];
    return $map[$role] ?? '9:00 AM - 6:00 PM';
}

function fqx_v167_staff_department($member): string {
    $uid = (int) ($member->wp_user_id ?? 0);
    $v = $uid ? trim((string) get_user_meta($uid, 'fqx_staff_department', true)) : '';
    return $v ?: fqx_v167_role_department((string) ($member->role_name ?? 'waiter'));
}
function fqx_v167_staff_area($member): string {
    $uid = (int) ($member->wp_user_id ?? 0);
    $v = $uid ? trim((string) get_user_meta($uid, 'fqx_staff_assigned_area', true)) : '';
    return $v ?: fqx_v167_role_assigned_area((string) ($member->role_name ?? 'waiter'));
}
function fqx_v167_staff_shift($member): string {
    $uid = (int) ($member->wp_user_id ?? 0);
    $v = $uid ? trim((string) get_user_meta($uid, 'fqx_staff_shift_time', true)) : '';
    return $v ?: fqx_v167_role_shift((string) ($member->role_name ?? 'waiter'));
}

function fqx_v167_staff_allowed_tabs_for_role(string $role): array {
    $role = fqx_v167_staff_role_alias($role);
    $map = [
        'manager' => ['dashboard','orders','menu','tables','rooms','wifi','staff','payments','bills','reviews','settings','combos','coupons','reports','subscription'],
        'cashier' => ['bills','orders','payments'],
        'kitchen' => ['orders'],
        'chef' => ['orders'],
        'waiter' => ['orders','tables'],
        'steward' => ['orders','tables'],
        'room_service' => ['orders','rooms'],
        'delivery' => ['orders'],
        'front_office' => ['orders','rooms','bills'],
        'housekeeping' => ['orders','rooms'],
    ];
    return $map[$role] ?? ['orders'];
}

function fqx_v167_staff_default_url_for_user(?WP_User $user = null): string {
    $user = $user ?: wp_get_current_user();
    if (!$user || empty($user->ID)) { return home_url('/'); }
    $role = fqx_v167_staff_role_for_user((int) $user->ID);
    if (in_array($role, ['kitchen','chef'], true)) { return menuqr_get_page_url_by_slug('kitchen-dashboard'); }
    $tab = 'orders';
    if ('cashier' === $role) { $tab = 'bills'; }
    elseif ('manager' === $role) { $tab = 'dashboard'; }
    elseif ('front_office' === $role || 'room_service' === $role || 'housekeeping' === $role) { $tab = 'rooms'; }
    elseif ('delivery' === $role) { $tab = 'orders'; }
    return add_query_arg('tab', $tab, menuqr_get_page_url_by_slug('restaurant-dashboard'));
}

function fqx_v167_is_limited_staff_user(): bool {
    if (!is_user_logged_in()) { return false; }
    $u = wp_get_current_user();
    if (!$u) { return false; }
    if (current_user_can('manage_options') || in_array('restaurant_admin', (array) $u->roles, true) || in_array('super_admin', (array) $u->roles, true)) { return false; }
    return in_array('staff', (array) $u->roles, true) || (function_exists('menuqr_user_matches_staff_record') && menuqr_user_matches_staff_record($u));
}

function fqx_v167_staff_can_access_tab(string $tab): bool {
    if (!fqx_v167_is_limited_staff_user()) { return true; }
    $role = fqx_v167_staff_role_for_user();
    return in_array($tab, fqx_v167_staff_allowed_tabs_for_role($role), true);
}

function fqx_v167_filter_sidebar_items_for_current_staff(array $items): array {
    if (!fqx_v167_is_limited_staff_user()) { return $items; }
    $allowed = fqx_v167_staff_allowed_tabs_for_role(fqx_v167_staff_role_for_user());
    return array_values(array_filter($items, static function($item) use ($allowed) {
        $id = (string) ($item['id'] ?? '');
        if ('dashboard' === $id) { return in_array('dashboard', $allowed, true); }
        return in_array($id, $allowed, true);
    }));
}

function fqx_v167_role_display_name(string $role): string {
    $roles = function_exists('menuqr_staff_roles') ? menuqr_staff_roles() : [];
    $alias = fqx_v167_staff_role_alias($role);
    return $roles[$alias] ?? $roles[$role] ?? ucwords(str_replace('_',' ', $alias));
}
