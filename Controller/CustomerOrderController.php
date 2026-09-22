<?php
//Customer orders endpoint (Public/customer-order.php, Assets/jquery/customer_order.js) - see
//docs/superpowers/specs/2026-09-22-customer-orders-design.md. POST only, from a signed-in user
//still allowed in the session's shop, with the page's CSRF token; the rules live in
//Model/customer_order_class.php. Every answer is JSON: {ok, message, ...}.
include "../Includes/includes.php";
require_once "../Includes/csrf.php";
require_once "../Includes/customer_orders.php";

header('Content-Type: application/json; charset=utf-8');

function order_respond($status, array $body)
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}//respond

if(!isset($_SESSION['user_id'], $_SESSION['shop_id']) || !(new ShopAccess())->canAccessShop($_SESSION['user_id'], $_SESSION['shop_id']))
{
    order_respond(403, ['ok' => false, 'message' => 'Please sign in to the shop again.']);
}//not signed in to this shop
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null))
{
    order_respond(400, ['ok' => false, 'message' => 'Your session expired. Please reload the page and try again.']);
}//not a post from our page

$orders = new CustomerOrders();
$user_id = (int)$_SESSION['user_id'];
$shop_id = (int)$_SESSION['shop_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$field = function($key) {
    return (isset($_POST[$key]) && is_string($_POST[$key])) ? $_POST[$key] : '';
};
$order = function() use ($field) {
    $data = json_decode($field('order'), true);
    return is_array($data) ? $data : [];
};

try
{
    switch(isset($_POST['action']) ? $_POST['action'] : '')
    {
        case 'create':
            $placed = $orders->create($shop_id, $user_id, $order());
            order_respond(200, ['ok' => true, 'message' => 'Order ' . $placed['order_no'] . ' sent.', 'id' => $placed['id'], 'order_no' => $placed['order_no']]);
        case 'update':
            $orders->update($id, $shop_id, $user_id, $order());
            order_respond(200, ['ok' => true, 'message' => 'Order saved.', 'id' => $id]);
        case 'cancel':
            $orders->cancel($id, $shop_id, $user_id);
            order_respond(200, ['ok' => true, 'message' => 'Order cancelled.']);
        case 'accept':
            $orders->accept($id, $shop_id, $user_id);
            order_respond(200, ['ok' => true, 'message' => 'Order accepted.']);
        case 'reject':
            $orders->reject($id, $shop_id, $user_id, $field('reason'));
            order_respond(200, ['ok' => true, 'message' => 'Order rejected.']);
        case 'create_transfer':
            $made = $orders->createTransfer($id, $shop_id, $user_id);
            order_respond(200, ['ok' => true, 'message' => $made['message'], 'transfer_id' => $made['transfer_id'], 'transfer_no' => $made['transfer_no']]);
        case 'custom_sent':
            $orders->markCustomSent($id, $shop_id, $user_id, (int)$field('line_id'), $field('qty'), $field('note'));
            order_respond(200, ['ok' => true, 'message' => 'Marked sent.']);
        case 'handover':
            $orders->handover($id, $shop_id, $user_id, $field('invoice_no'));
            order_respond(200, ['ok' => true, 'message' => 'Order handed over.']);
        case 'products':
            if(!$orders->can($user_id, $shop_id, CustomerOrders::CHANGE))
            {
                order_respond(403, ['ok' => false, 'message' => 'You do not have the right to place customer orders in this shop.']);
            }
            $from = $shop_id;
            if($field('scope') === 'supplier')
            {
                $from = (int)$field('supplier_id');
                if(!in_array($from, array_column($orders->supplierShops($shop_id), 'SHID'), true))
                {
                    order_respond(422, ['ok' => false, 'message' => 'Choose the shop to order from.']);
                }
            }//the supplier's catalog, else this shop's own products
            order_respond(200, ['ok' => true, 'products' => $orders->searchProducts($from, $field('term'))]);
        default:
            order_respond(400, ['ok' => false, 'message' => 'Unknown action.']);
    }
}
catch(CustomerOrderRefused $e)
{
    order_respond($e->status, ['ok' => false, 'message' => $e->getMessage()]);
}
catch(Throwable $e)
{
    error_log('CustomerOrderController: ' . $e->getMessage());
    order_respond(500, ['ok' => false, 'message' => 'Something went wrong. Nothing was saved.']);
}//catch
