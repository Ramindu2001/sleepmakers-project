<?php
//Sales & Orders -> Customer Orders (what this shop's till sold and still owes a customer) and
//Warehouse Orders (what this shop has to prepare and send), with a badge counting the orders
//nobody has picked up yet. Included by View/sidebar.php in both of its Sales & Orders menus.
//docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md
require_once __DIR__ . '/../Includes/warehouse_fulfilment.php';
$menuOrders = new WarehouseOrder();
if($menuOrders->featureId() > 0 && isset($_SESSION['user_id']) && $menuOrders->can($_SESSION['user_id'], $shop_id, WarehouseOrder::VIEW))
{
    $menuWaiting = $menuOrders->pendingCount($shop_id);
    ?>
    <li class="sidebar-item">
      <a href="../Public/customer-orders.php" class="sidebar-link sidebar-link2">
        <div class="round-16 d-flex align-items-center justify-content-center">
          <i class="ti ti-circle"></i>
        </div>
        <span class="hide-menu">Customer Orders</span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="../Public/warehouse-orders.php" class="sidebar-link sidebar-link2">
        <div class="round-16 d-flex align-items-center justify-content-center">
          <i class="ti ti-circle"></i>
        </div>
        <span class="hide-menu">Warehouse Orders<?php if($menuWaiting > 0) { ?> <span class="badge bg-danger rounded-pill ms-1"><?= (int)$menuWaiting ?></span><?php } ?></span>
      </a>
    </li>
    <?php
}//may see customer orders
