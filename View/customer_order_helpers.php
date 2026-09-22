<?php
//Small helpers shared by the customer order pages (Public/customer-orders.php,
//Public/customer-order.php).

//text for HTML
function co_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}//co h

//an order status as a badge, coloured as the rest of the app colours states
function co_badge($status)
{
    $classes = [
        'Requested' => 'bg-primary', 'Accepted' => 'text-bg-info', 'In transit' => 'text-bg-warning', 'Arrived' => 'text-bg-success',
        'Handed over' => 'text-bg-secondary', 'Rejected' => 'bg-danger', 'Cancelled' => 'bg-danger',
    ];
    return '<span class="badge ' . (isset($classes[$status]) ? $classes[$status] : 'bg-secondary') . '">' . co_h($status) . '</span>';
}//co badge

//22 Sep 2026 (and the time when asked); empty stays empty
function co_date($value, $withTime = false)
{
    if(empty($value))
    {
        return '';
    }
    return date($withTime ? 'd M Y, H:i' : 'd M Y', strtotime($value));
}//co date

//the flash message an action left for the next page load
function co_flash()
{
    $message = isset($_SESSION['co_flash']) ? $_SESSION['co_flash'] : null;
    unset($_SESSION['co_flash']);
    if($message === null)
    {
        return '';
    }
    return '<div class="alert alert-' . ($message['ok'] ? 'success' : 'danger') . ' alert-dismissible fade show" role="alert">'
        . co_h($message['text']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}//co flash
