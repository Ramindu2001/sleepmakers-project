<?php
include '../Includes/includes.php';
include '../Includes/authcheck.php';
require_once '../Includes/csrf.php';
require_once '../Includes/customer_orders.php';
require_once '../View/customer_order_helpers.php';

//Customer Orders: the orders this shop placed, and the orders sent to it
//(docs/superpowers/specs/2026-09-22-customer-orders-design.md)
$orders = new CustomerOrders();
$me = (int)$_SESSION['user_id'];
if(!$orders->can($me, $shop_id, CustomerOrders::VIEW))
{
    header("Location: ../Public/home.php");
    exit;
}//no Customer Orders right in this shop

$ours = $orders->listFor($shop_id, $me, 'ours');
$incoming = $orders->listFor($shop_id, $me, 'incoming');
$canPlace = $orders->can($me, $shop_id, CustomerOrders::PLACE) && !empty($orders->supplierShops($shop_id));
$tab = (isset($_GET['tab']) && $_GET['tab'] === 'incoming') ? 'incoming' : 'ours';
if(!isset($_GET['tab']) && empty($ours) && !empty($incoming))
{
    $tab = 'incoming';
}//a shop that only supplies starts on what it was sent

//one tab's table
function co_orders_table(array $rows, $side)
{
    if(empty($rows))
    {
        echo '<p class="text-muted my-4 text-center">No orders yet.</p>';
        return;
    }
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle text-nowrap mb-0">
            <thead class="header-item">
                <tr>
                    <th>Order No</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th><?= $side === 'incoming' ? 'From' : 'Ordered from' ?></th>
                    <th>Needed by</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Placed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $row) { ?>
                <tr class="co-row" data-status="<?= co_h($row['status']) ?>">
                    <td><a href="customer-order.php?id=<?= (int)$row['COID'] ?>" class="fw-semibold"><?= co_h($row['OrderNo']) ?></a></td>
                    <td><?= co_h($row['CustName']) ?></td>
                    <td><?= co_h($row['CustPhone']) ?></td>
                    <td><?= co_h($side === 'incoming' ? $row['ShopName'] : $row['SupplierName']) ?></td>
                    <td><?= co_h(co_date($row['NeededBy'])) ?></td>
                    <td class="text-wrap" style="min-width:220px;"><?= co_h($row['summary']) ?></td>
                    <td><?= co_badge($row['status']) ?></td>
                    <td><?= co_h(co_date($row['CreatedAt'])) ?><br><small class="text-muted"><?= co_h($row['CreatedByName']) ?></small></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}//orders table
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
                    <?= co_flash() ?>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h5 class="card-title fw-semibold mb-0">Customer Orders</h5>
                        <?php if($canPlace) { ?>
                        <a href="customer-order.php?new=1" class="btn btn-primary ms-auto"><i class="ti ti-plus"></i> New Order</a>
                        <?php } ?>
                    </div>
                    <p class="mb-3">Items a customer bought that this shop does not have are ordered from the warehouse here.
                        Items already given from this shop's stock are listed on the order but never sent.</p>

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link<?= $tab === 'ours' ? ' active' : '' ?>" data-bs-toggle="tab" data-bs-target="#co_tab_ours" type="button" role="tab">
                                            Our orders <span class="badge text-bg-light"><?= count($ours) ?></span></button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link<?= $tab === 'incoming' ? ' active' : '' ?>" data-bs-toggle="tab" data-bs-target="#co_tab_incoming" type="button" role="tab">
                                            Incoming <span class="badge text-bg-light"><?= count($incoming) ?></span></button>
                                    </li>
                                </ul>
                                <div class="ms-auto">
                                    <select id="co_status_filter" class="form-select form-select-sm" aria-label="Filter by status">
                                        <option value="">All statuses</option>
                                        <?php foreach(['Requested', 'Accepted', 'In transit', 'Arrived', 'Handed over', 'Rejected', 'Cancelled'] as $status) { ?>
                                        <option value="<?= co_h($status) ?>"><?= co_h($status) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="tab-content">
                                <div class="tab-pane fade<?= $tab === 'ours' ? ' show active' : '' ?>" id="co_tab_ours" role="tabpanel"><?php co_orders_table($ours, 'ours'); ?></div>
                                <div class="tab-pane fade<?= $tab === 'incoming' ? ' show active' : '' ?>" id="co_tab_incoming" role="tabpanel"><?php co_orders_table($incoming, 'incoming'); ?></div>
                            </div>
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
    <script src="../Assets/jquery/customer_order.js?v=20260922"></script>
</body>

</html>
