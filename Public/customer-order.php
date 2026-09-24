<?php
include '../Includes/includes.php';
include '../Includes/authcheck.php';
require_once '../Includes/csrf.php';
require_once '../Includes/warehouse_fulfilment.php';
require_once '../View/warehouse_order_helpers.php';

//The job sheet, shared by both sides: the shop sees how its customer's order is getting on,
//the warehouse sees what to prepare and what was already handed over at the counter
//(docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md).
$orders = new WarehouseOrder();
$me = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try
{
    $view = $orders->get($id, $shop_id, $me);
}
catch(CustomerOrderRefused $e)
{
    $_SESSION['wo_flash'] = ['ok' => false, 'text' => $e->getMessage()];
    header("Location: ../Public/customer-orders.php");
    exit;
}//not this shop's order, or no right

$order = $view['order'];
$lines = $view['lines'];
$money = $view['money'];
$isSupplier = (int)$order['SupplierShopID'] === (int)$shop_id;
$isOurs = (int)$order['shop_SHID'] === (int)$shop_id;
$canProcess = $isSupplier && $orders->can($me, $shop_id, WarehouseOrder::PROCESS);
$canChange = $orders->can($me, $shop_id, WarehouseOrder::CHANGE);
$open = !in_array((int)$order['OrderStat'], [WarehouseOrder::COMPLETED, WarehouseOrder::CANCELLED], true);
$anyDispatched = false;
foreach($lines as $line)
{
    if((float)$line['DispatchedQty'] > 0)
    {
        $anyDispatched = true;
    }
}//anything already gone
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../View/head.php'; ?>
</head>

<body>
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <?php include '../View/sidebar.php'; ?>
            <div class="body-wrapper">
                <?php include '../View/header.php'; ?>
                <div class="container-fluid">
                    <?= wo_flash() ?>
                    <input type="hidden" id="wo_csrf_token" value="<?= wo_h(csrf_token()) ?>">
                    <input type="hidden" id="wo_order_id" value="<?= (int)$order['COID'] ?>">

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <h5 class="card-title fw-semibold mb-0">Order <?= wo_h($order['OrderNo']) ?></h5>
                        <?= wo_status_badge($order['OrderStat']) ?>
                        <a href="<?= $isSupplier ? 'warehouse-orders.php' : 'customer-orders.php' ?>" class="btn btn-light btn-sm ms-auto">Back</a>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card h-100"><div class="card-body">
                                <h6 class="fw-semibold">The customer</h6>
                                <p class="mb-1"><b><?= wo_h($order['CustName']) ?></b></p>
                                <p class="mb-1"><?= wo_h($order['CustPhone']) ?></p>
                                <p class="mb-0 text-muted"><?= wo_h($order['CustAddress']) ?></p>
                            </div></div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100"><div class="card-body">
                                <h6 class="fw-semibold">The money</h6>
                                <p class="mb-1">Invoice <b><?= wo_h($order['InvoiceNo']) ?></b>
                                    <?php if((int)$order['InvStat'] !== 1 && $order['InvoiceNo'] !== null) { ?>
                                    <span class="badge bg-danger">cancelled</span>
                                    <?php } ?>
                                </p>
                                <p class="mb-1">Billed Rs. <?= wo_money($money['net']) ?>, paid Rs. <?= wo_money($money['paid']) ?></p>
                                <p class="mb-0"><?= wo_balance($money) ?></p>
                            </div></div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100"><div class="card-body">
                                <h6 class="fw-semibold">Where it goes</h6>
                                <?php if((int)$order['DeliverTo'] === WarehouseOrder::DELIVER_PICKUP) { ?>
                                <p class="mb-1"><b>The customer collects at <?= wo_h($order['ShopName']) ?></b></p>
                                <?php } else { ?>
                                <p class="mb-1"><?= wo_h($order['DeliveryAddress']) ?></p>
                                <p class="mb-1"><?= wo_h($order['DeliveryPhone']) ?></p>
                                <?php } ?>
                                <p class="mb-0 text-muted">
                                    <?php if(!empty($order['NeededBy'])) { ?>Wanted by <?= wo_h(wo_date($order['NeededBy'])) ?>. <?php } ?>
                                    <?= wo_h($order['DeliveryNote']) ?>
                                </p>
                            </div></div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">The items</h6>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="header-item">
                                        <tr>
                                            <th>Item</th>
                                            <th>What the customer asked for</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Sent</th>
                                            <th>State</th>
                                            <?php if($canProcess || $canChange) { ?><th class="text-end">Action</th><?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($lines as $line) {
                                            $given = (int)$line['LineStat'] === WarehouseOrder::LINE_GIVEN;
                                            $pickable = !$given && (int)$line['LineStat'] !== WarehouseOrder::LINE_CANCELLED
                                                && (float)$line['Qty'] - (float)$line['DispatchedQty'] > 0;
                                        ?>
                                        <tr class="<?= $given ? 'table-light text-muted' : '' ?>">
                                            <td>
                                                <b><?= wo_h($line['Description']) ?></b>
                                                <?php if($given) { ?>
                                                <br><span class="badge text-bg-success">Given at shop &mdash; do not send</span>
                                                <?php } elseif($line['SupplierProductID'] === null) { ?>
                                                <br><span class="badge bg-warning text-dark">Custom-made</span>
                                                <?php } ?>
                                            </td>
                                            <td><?= wo_h($line['Notes']) ?>
                                                <?php if(!empty($line['CancelReason'])) { ?>
                                                <br><small class="text-danger">Cannot supply: <?= wo_h($line['CancelReason']) ?></small>
                                                <?php } ?>
                                            </td>
                                            <td class="text-end"><?= wo_h(wo_qty($line['Qty'])) ?></td>
                                            <td class="text-end">
                                                <?php if($given) { ?>&mdash;<?php } else { ?>
                                                <?= wo_h(wo_qty($line['DispatchedQty'])) ?> of <?= wo_h(wo_qty($line['Qty'])) ?>
                                                <?php } ?>
                                            </td>
                                            <td><?= wo_line_badge($line['LineStat']) ?></td>
                                            <?php if($canProcess || $canChange) { ?>
                                            <td class="text-end">
                                                <?php if($pickable && $canProcess && (int)$line['LineStat'] !== WarehouseOrder::LINE_READY) { ?>
                                                <button class="btn btn-sm btn-outline-info wo-action" data-action="mark_ready"
                                                    data-line="<?= (int)$line['COLID'] ?>">Ready</button>
                                                <?php } ?>
                                                <?php if($pickable && $canChange && $isSupplier) { ?>
                                                <button class="btn btn-sm btn-outline-danger wo-action" data-action="cannot_supply"
                                                    data-line="<?= (int)$line['COLID'] ?>">Cannot supply</button>
                                                <?php } ?>
                                            </td>
                                            <?php } ?>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if(!empty($view['dispatches'])) { ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Dispatches</h6>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="header-item">
                                        <tr><th>No</th><th>Items scanned</th><th>State</th><th>Sent</th><th>Delivered</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($view['dispatches'] as $dispatch) { ?>
                                        <tr>
                                            <td><b><?= wo_h($dispatch['DispatchNo']) ?></b></td>
                                            <td><?= (int)$dispatch['ScannedCount'] ?></td>
                                            <td><?= wo_h(wo_dispatch_name($dispatch['DispatchStat'])) ?></td>
                                            <td><?= wo_h(wo_date($dispatch['SentAt'], true)) ?></td>
                                            <td><?= wo_h(wo_date($dispatch['DeliveredAt'], true)) ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php if($canProcess && $open && (int)$order['OrderStat'] === WarehouseOrder::PENDING) { ?>
                        <button class="btn btn-warning wo-action" data-action="start_preparing">Start preparing</button>
                        <?php } ?>
                        <?php if($canProcess && $open) { ?>
                        <button class="btn btn-info wo-action" data-action="mark_ready">Everything is ready</button>
                        <?php } ?>
                        <?php if($isOurs && $canChange && $open && !$anyDispatched) { ?>
                        <button class="btn btn-outline-danger wo-action" data-action="cancel_order"
                            data-confirm="Cancel this whole order? The customer will need a refund through Sales Return.">Cancel order</button>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../View/footer.php'; ?>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/jquery/warehouse_order.js?v=20260924"></script>
</body>

</html>
