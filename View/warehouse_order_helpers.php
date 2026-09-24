<?php
//Small helpers shared by the fulfilment pages (Public/customer-orders.php,
//Public/warehouse-orders.php, Public/customer-order.php).

//text for HTML
function wo_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}//wo h

//what an order is doing, in a word
function wo_status_name($status)
{
    $names = [
        WarehouseOrder::PENDING => 'Pending',
        WarehouseOrder::PREPARING => 'Preparing',
        WarehouseOrder::READY => 'Ready',
        WarehouseOrder::DISPATCHED => 'Dispatched',
        WarehouseOrder::COMPLETED => 'Completed',
        WarehouseOrder::CANCELLED => 'Cancelled',
    ];
    return isset($names[(int)$status]) ? $names[(int)$status] : 'Unknown';
}//wo status name

function wo_status_badge($status)
{
    $classes = [
        WarehouseOrder::PENDING => 'bg-danger',
        WarehouseOrder::PREPARING => 'text-bg-warning',
        WarehouseOrder::READY => 'text-bg-info',
        WarehouseOrder::DISPATCHED => 'bg-primary',
        WarehouseOrder::COMPLETED => 'text-bg-success',
        WarehouseOrder::CANCELLED => 'bg-secondary',
    ];
    $class = isset($classes[(int)$status]) ? $classes[(int)$status] : 'bg-secondary';
    return '<span class="badge ' . $class . '">' . wo_h(wo_status_name($status)) . '</span>';
}//wo status badge

//what one line is doing. A line given over the counter says so plainly, because that is the
//thing the warehouse must never pick again.
function wo_line_name($status)
{
    $names = [
        WarehouseOrder::LINE_GIVEN => 'Given at shop',
        WarehouseOrder::LINE_PENDING => 'Pending',
        WarehouseOrder::LINE_READY => 'Ready',
        WarehouseOrder::LINE_DISPATCHED => 'Dispatched',
        WarehouseOrder::LINE_DELIVERED => 'Delivered',
        WarehouseOrder::LINE_CANCELLED => 'Cannot supply',
    ];
    return isset($names[(int)$status]) ? $names[(int)$status] : 'Unknown';
}//wo line name

function wo_line_badge($status)
{
    $classes = [
        WarehouseOrder::LINE_GIVEN => 'text-bg-success',
        WarehouseOrder::LINE_PENDING => 'bg-danger',
        WarehouseOrder::LINE_READY => 'text-bg-info',
        WarehouseOrder::LINE_DISPATCHED => 'bg-primary',
        WarehouseOrder::LINE_DELIVERED => 'text-bg-success',
        WarehouseOrder::LINE_CANCELLED => 'bg-secondary',
    ];
    $class = isset($classes[(int)$status]) ? $classes[(int)$status] : 'bg-secondary';
    return '<span class="badge ' . $class . '">' . wo_h(wo_line_name($status)) . '</span>';
}//wo line badge

function wo_dispatch_name($status)
{
    $names = [1 => 'Being scanned', 2 => 'Sent', 3 => 'Cancelled'];
    return isset($names[(int)$status]) ? $names[(int)$status] : 'Unknown';
}//wo dispatch name

//24 Sep 2026 (and the time when asked); empty stays empty
function wo_date($value, $withTime = false)
{
    if(empty($value) || $value === '0000-00-00')
    {
        return '';
    }
    return date($withTime ? 'd M Y, H:i' : 'd M Y', strtotime($value));
}//wo date

//1,250.00
function wo_money($value)
{
    return number_format((float)$value, 2);
}//wo money

//5, 2.5 - never 5.000
function wo_qty($value)
{
    return rtrim(rtrim(number_format((float)$value, 3, '.', ''), '0'), '.');
}//wo qty

//"2 of 3 pending" for the queue
function wo_progress(array $progress)
{
    if($progress['total'] === 0)
    {
        return '<span class="text-muted">nothing to send</span>';
    }
    if($progress['waiting'] === 0)
    {
        return '<span class="text-success">all ' . (int)$progress['total'] . ' dealt with</span>';
    }
    return '<b>' . (int)$progress['waiting'] . '</b> of ' . (int)$progress['total'] . ' pending';
}//wo progress

//the balance, shouted in red while the customer still owes something
function wo_balance(array $money)
{
    if($money['balance'] <= 0)
    {
        return '<span class="text-success">Paid in full</span>';
    }
    return '<span class="text-danger fw-bold">Rs. ' . wo_money($money['balance']) . ' due</span>';
}//wo balance

//the flash message an action left for the next page load
function wo_flash()
{
    $message = isset($_SESSION['wo_flash']) ? $_SESSION['wo_flash'] : null;
    unset($_SESSION['wo_flash']);
    if($message === null)
    {
        return '';
    }
    return '<div class="alert alert-' . ($message['ok'] ? 'success' : 'danger') . ' alert-dismissible fade show" role="alert">'
        . wo_h($message['text']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}//wo flash
