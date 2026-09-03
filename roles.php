<?php
if (!defined('ABSPATH')) {
    exit;
}

function menuqr_register_roles(): void {
    add_role('super_admin', 'FluuexQR Super Admin', [
        'read' => true,
        'upload_files' => true,
        'manage_options' => true,
    ]);

    add_role('restaurant_admin', 'Restaurant Admin', [
        'read' => true,
        'upload_files' => true,
    ]);

    add_role('staff', 'Restaurant Staff', [
        'read' => true,
    ]);

    $administrator = get_role('administrator');
    if ($administrator instanceof WP_Role) {
        $administrator->add_cap('manage_options');
    }
}
