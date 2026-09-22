<?php
//Sales & Orders -> Customer Orders, with a badge counting the orders waiting for this shop's
//answer (docs/superpowers/specs/2026-09-22-customer-orders-design.md). Included by
//View/sidebar.php in both of its Sales & Orders menus.
require_once __DIR__ . '/../Includes/customer_orders.php';
$menuOrders = new CustomerOrders();
if($menuOrders->featureId() > 0 && isset($_SESSION['user_id']) && $menuOrders->can($_SESSION['user_id'], $shop_id, CustomerOrders::VIEW))
{
    $menuWaiting = $menuOrders->incomingCount($shop_id);
    ?>
    <li class="sidebar-item">
      <a href="../Public/customer-orders.php" class="sidebar-link sidebar-link2">
        <div class="round-16 d-flex align-items-center justify-content-center">
          <i class="ti ti-circle"></i>
        </div>
        <span class="hide-menu">Customer Orders<?php if($menuWaiting > 0) { ?> <span class="badge bg-danger rounded-pill ms-1"><?= (int)$menuWaiting ?></span><?php } ?></span>
      </a>
    </li>
    <?php
}//may see customer orders
