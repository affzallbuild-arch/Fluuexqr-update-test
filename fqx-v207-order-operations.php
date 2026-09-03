<?php
if (!defined('ABSPATH')) { exit; }

function fqx_v207_cancel_customer_order(): void {
    menuqr_verify_ajax();
    nocache_headers();
    $id = absint($_POST['order_id'] ?? 0);
    $reason = sanitize_textarea_field(wp_unslash($_POST['reason'] ?? ''));
    if (!$id || $reason === '') menuqr_json_response(false, ['message'=>'A cancellation reason is required.'], 400);
    global $wpdb; $orders = menuqr_table('orders');
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders} WHERE id=%d LIMIT 1", $id));
    if (!$order) menuqr_json_response(false, ['message'=>'Order not found.'], 404);
    $token = function_exists('fqx_v200_get_request_token') ? fqx_v200_get_request_token() : '';
    if ($token === '' || !function_exists('fqx_v200_find_service_by_token')) menuqr_json_response(false, ['message'=>'This order can only be cancelled from its original QR session.'], 403);
    $ctx = fqx_v200_find_service_by_token($token);
    if (!$ctx || (int)$ctx['restaurant_id'] !== (int)$order->restaurant_id || (int)($ctx['table_id'] ?? 0) !== (int)($order->table_id ?? 0) || (int)($ctx['room_id'] ?? 0) !== (int)($order->room_id ?? 0))
        menuqr_json_response(false, ['message'=>'This order does not belong to this QR session.'], 403);
    $status = sanitize_key((string)($order->order_status ?? 'pending'));
    if (in_array($status, ['preparing','ready','served','completed','delivered'], true)) menuqr_json_response(false, ['message'=>'This order cannot be cancelled because preparation has already started.'], 409);
    if ($status === 'cancelled') menuqr_json_response(false, ['message'=>'This order is already cancelled.'], 409);
    $old = trim((string)($order->customer_note ?? ''));
    $note = 'Cancellation reason: ' . $reason;
    if ($old !== '') $note = $old . "\n" . $note;
    $ok = $wpdb->update($orders, ['order_status'=>'cancelled','customer_note'=>$note,'updated_at'=>current_time('mysql')], ['id'=>$id], ['%s','%s','%s'], ['%d']);
    if ($ok === false) menuqr_json_response(false, ['message'=>'Order could not be cancelled.'], 500);
    menuqr_json_response(true, ['message'=>'Order cancelled successfully.','order_id'=>$id,'order_status'=>'cancelled']);
}
add_action('wp_ajax_fqx_v207_cancel_customer_order', 'fqx_v207_cancel_customer_order');
add_action('wp_ajax_nopriv_fqx_v207_cancel_customer_order', 'fqx_v207_cancel_customer_order');

function fqx_v207_handle_manual_order(): void {
    menuqr_require_role(['staff','restaurant_admin','super_admin']);
    menuqr_require_post_nonce('fqx_manual_order_nonce','fqx_v207_manual_order');
    $rid = menuqr_get_current_restaurant_id();
    $back = menuqr_restaurant_tab_url('orders');
    if (!$rid) menuqr_redirect_back_with_status(['mq_notice'=>'manual_order_error','mq_error'=>'Restaurant context not found.'],$back);
    $type = sanitize_key(wp_unslash($_POST['target_type'] ?? ''));
    $target_id = absint($_POST['target_id'] ?? 0);
    $source = sanitize_key(wp_unslash($_POST['order_source'] ?? 'reception'));
    $name = sanitize_text_field(wp_unslash($_POST['customer_name'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['customer_phone'] ?? ''));
    $raw = json_decode((string)wp_unslash($_POST['items_json'] ?? '[]'), true);
    if (!in_array($type,['table','room'],true) || !$target_id || !is_array($raw) || !$raw) menuqr_redirect_back_with_status(['mq_notice'=>'manual_order_invalid','mq_error'=>'Select a table/room and at least one menu item.'],$back);
    if ($type === 'table') {
        $target = menuqr_find_table_by_id($rid,$target_id);
        $point = 'Table ' . (string)($target->table_number ?? $target->number ?? $target_id);
    } else {
        $target = null; foreach ((array)menuqr_get_rooms($rid) as $r) { if ((int)($r->id ?? 0) === $target_id) { $target=$r; break; } }
        $point = 'Room ' . (string)($target->room_number ?? $target->number ?? $target->name ?? $target_id);
    }
    if (!$target) menuqr_redirect_back_with_status(['mq_notice'=>'manual_order_invalid','mq_error'=>'Selected service point was not found.'],$back);
    $map=[]; foreach ((array)menuqr_get_items($rid) as $mi) $map[(int)($mi->id ?? 0)]=$mi;
    $items=[];
    foreach($raw as $row){
        $iid=absint($row['item_id']??$row['id']??0); $qty=max(1,min(99,absint($row['qty']??1)));
        if(!$iid || empty($map[$iid])) continue; $mi=$map[$iid];
        $items[]=['item_id'=>$iid,'name'=>sanitize_text_field((string)($mi->name??'Item')),'price'=>(float)($mi->price??0),'qty'=>$qty,'emoji'=>sanitize_text_field((string)($mi->emoji??'🍽️')),'image'=>esc_url_raw((string)($mi->image??''))];
    }
    if(!$items) menuqr_redirect_back_with_status(['mq_notice'=>'manual_order_invalid','mq_error'=>'No valid menu items were selected.'],$back);
    $source_labels=['reception'=>'Reception','phone'=>'Phone Call','online'=>'Online'];
    $source_label=$source_labels[$source]??'Reception';
    $note='Manual order • Source: '.$source_label."\nService point: ".$point;
    $created=menuqr_create_customer_order_record($rid,$type==='table'?$target_id:0,$items,'cash','unpaid',$note,'','','','','', $name?:'Guest Customer',$phone,'',$type==='room'?$target_id:0,$type==='room'?'room_qr':'table_qr');
    if(empty($created['success'])) menuqr_redirect_back_with_status(['mq_notice'=>'manual_order_error','mq_error'=>(string)($created['message']??'Order could not be created.')],$back);
    menuqr_redirect_back_with_status(['mq_notice'=>'manual_order_created','selected_order'=>absint($created['order_id']??0)],$back);
}
add_action('admin_post_fqx_v207_manual_order','fqx_v207_handle_manual_order');
