<?php
include '../Includes/includes.php';
include '../Includes/authcheck.php';
require_once '../Includes/csrf.php';
require_once '../Includes/warehouse_fulfilment.php';
require_once '../View/warehouse_order_helpers.php';

//The warehouse's own queue: what has to be prepared and sent to a customer, pending first
//(docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md).
$orders = new WarehouseOrder();
$me = (int)$_SESSION['user_id'];
if(!$orders->can($me, $shop_id, WarehouseOrder::VIEW))
{
    header("Location: ../Public/home.php");
    exit;
}//no Customer Orders right in this shop

//Pending first, because an order nobody has picked up is the one that needs a person.
$status = isset($_GET['status']) ? ($_GET['status'] === '' ? null : (int)$_GET['status']) : WarehouseOrder::PENDING;
$rows = $orders->listFor($shop_id, $me, 'incoming', $status);
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
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h5 class="card-title fw-semibold mb-0">Warehouse Orders</h5>
                        <form method="get" class="ms-auto">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value=""<?= $status === null ? ' selected' : '' ?>>All statuses</option>
                                <?php foreach([WarehouseOrder::PENDING, WarehouseOrder::PREPARING, WarehouseOrder::READY,
                                    WarehouseOrder::DISPATCHED, WarehouseOrder::COMPLETED, WarehouseOrder::CANCELLED] as $option) { ?>
                                <option value="<?= (int)$option ?>"<?= $status === $option ? ' selected' : '' ?>><?= wo_h(wo_status_name($option)) ?></option>
                                <?php } ?>
                            </select>
                        </form>
                    </div>
                    <p class="mb-3">Customers have paid for these items. Prepare them, scan them out, and send them.
                        Anything already handed over at the shop is marked on the order and must never be sent again.</p>

                    <div class="card">
                        <div class="card-body">
                            <?php if(empty($rows)) { ?>
                            <p class="text-muted my-4 text-center">Nothing waiting.</p>
                            <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle text-nowrap mb-0">
                                    <thead class="header-item">
                                        <tr>
                                            <th>Order No</th>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th>Phone</th>
                                            <th>Wanted by</th>
                                            <th>To send</th>
                                            <th>Balance</th>
                                            <th>Goes to</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($rows as $row) { ?>
                                        <tr>
                                            <td><a href="customer-order.php?id=<?= (int)$row['COID'] ?>"><b><?= wo_h($row['OrderNo']) ?></b></a></td>
                                            <td><?= wo_h($row['InvoiceNo']) ?></td>
                                            <td><?= wo_h($row['CustName']) ?><br><small class="text-muted"><?= wo_h($row['ShopName']) ?></small></td>
                                            <td><?= wo_h($row['CustPhone']) ?></td>
                                            <td><?= wo_h(wo_date($row['NeededBy'])) ?></td>
                                            <td><?= wo_progress($row['progress']) ?></td>
                                            <td><?= wo_balance($row['money']) ?></td>
                                            <td><?= (int)$row['DeliverTo'] === WarehouseOrder::DELIVER_PICKUP
                                                ? 'Collects at the shop' : wo_h($row['DeliveryAddress']) ?></td>
                                            <td><?= wo_status_badge($row['OrderStat']) ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php } ?>
                        </div>
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
</body>

</html>
