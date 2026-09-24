<?php
//The buttons on the job sheet (Public/customer-order.php, Assets/jquery/warehouse_order.js) -
//see docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md. POST only, from a
//signed-in user still allowed in the session's shop, with the page's CSRF token; the rules
//live in Model/warehouse_order_class.php. Every answer is JSON: {ok, message, ...}.
include "../Includes/includes.php";
require_once "../Includes/csrf.php";
require_once "../Includes/warehouse_fulfilment.php";

header('Content-Type: application/json; charset=utf-8');

function wo_respond($status, array $body)
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}//respond

//a done action: the page reloads and shows its message once (View/warehouse_order_helpers.php)
function wo_done($message, array $more = [])
{
    $_SESSION['wo_flash'] = ['ok' => true, 'text' => $message];
    wo_respond(200, ['ok' => true, 'message' => $message] + $more);
}//done

if(!isset($_SESSION['user_id'], $_SESSION['shop_id']) || !(new ShopAccess())->canAccessShop($_SESSION['user_id'], $_SESSION['shop_id']))
{
    wo_respond(403, ['ok' => false, 'message' => 'Please sign in to the shop again.']);
}//not signed in to this shop
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null))
{
    wo_respond(400, ['ok' => false, 'message' => 'Your session expired. Please reload the page and try again.']);
}//not a post from our page

$orders = new WarehouseOrder();
$dispatches = new OrderDispatch();
$user_id = (int)$_SESSION['user_id'];
$shop_id = (int)$_SESSION['shop_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$line_id = (isset($_POST['line_id']) && $_POST['line_id'] !== '') ? (int)$_POST['line_id'] : null;
$field = function($key) {
    return (isset($_POST[$key]) && is_string($_POST[$key])) ? $_POST[$key] : '';
};

try
{
    switch(isset($_POST['action']) ? $_POST['action'] : '')
    {
        case 'start_preparing':
            $result = $orders->startPreparing($id, $shop_id, $user_id);
            wo_done($result['message']);
            break;

        case 'mark_ready':
            $result = $orders->markReady($id, $shop_id, $user_id, $line_id);
            wo_done($result['message']);
            break;

        case 'cannot_supply':
            $result = $orders->cannotSupply($id, $shop_id, $user_id, (int)$line_id, $field('reason'));
            wo_done($result['message']);
            break;

        case 'cancel_order':
            $result = $orders->cancel($id, $shop_id, $user_id);
            wo_done($result['message']);
            break;

        //the dispatch actions name the trip in `id`, not the order
        case 'open_dispatch':
            $result = $dispatches->open($id, $shop_id, $user_id);
            wo_done($result['message'], ['dispatch_id' => $result['dispatch_id']]);
            break;

        case 'cancel_dispatch':
            $result = $dispatches->cancel($id, $shop_id, $user_id);
            wo_done($result['message']);
            break;

        case 'complete_dispatch':
            $result = $dispatches->complete($id, $shop_id, $user_id,
                ['confirm_balance' => !empty($_POST['confirm_balance'])]);
            wo_done($result['message']);
            break;

        case 'delivered':
            $result = $dispatches->delivered($id, $shop_id, $user_id, $field('note'));
            wo_done($result['message']);
            break;

        default:
            wo_respond(422, ['ok' => false, 'message' => 'That action is not something this page can do.']);
    }//each action
}
catch(CustomerOrderRefused $e)
{
    wo_respond($e->status, ['ok' => false, 'message' => $e->getMessage()]);
}
catch(Throwable $e)
{
    wo_respond(500, ['ok' => false, 'message' => 'Something went wrong: ' . $e->getMessage()]);
}
