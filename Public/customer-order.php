<?php
include '../Includes/includes.php';
include '../Includes/authcheck.php';
require_once '../Includes/csrf.php';
require_once '../Includes/customer_orders.php';
require_once '../View/customer_order_helpers.php';

//One customer order: placing or editing it (?new=1, ?id=N&edit=1) or following it (?id=N) -
//see docs/superpowers/specs/2026-09-22-customer-orders-design.md. The buttons shown come from
//the order's can_* flags; every action is checked again by Controller/CustomerOrderController.php.
$orders = new CustomerOrders();
$me = (int)$_SESSION['user_id'];
$mode = isset($_GET['new']) ? 'new' : 'view';
$order = null;
try
{
    if($mode === 'new')
    {
        if(!$orders->can($me, $shop_id, CustomerOrders::PLACE))
        {
            throw new CustomerOrderRefused(403, 'You do not have the right to place customer orders in this shop.');
        }
    }//a new order
    else
    {
        $order = $orders->get(isset($_GET['id']) ? (int)$_GET['id'] : 0, $shop_id, $me);
        if(isset($_GET['edit']))
        {
            if(!$order['can_edit'])
            {
                throw new CustomerOrderRefused(409, 'This order can no longer be edited.');
            }
            $mode = 'edit';
        }
    }//an existing order
}
catch(CustomerOrderRefused $e)
{
    $_SESSION['co_flash'] = ['ok' => false, 'text' => $e->getMessage()];
    header("Location: " . ($orders->can($me, $shop_id, CustomerOrders::VIEW) ? "customer-orders.php" : "home.php"));
    exit;
}//not for this user or this shop

$suppliers = $orders->supplierShops($shop_id);
if($mode === 'new' && empty($suppliers))
{
    $_SESSION['co_flash'] = ['ok' => false, 'text' => 'There is no other shop in this company to order from.'];
    header("Location: customer-orders.php");
    exit;
}//nowhere to order from

//the lines an edit starts from, for Assets/jquery/customer_order.js
$initial = [];
if($mode === 'edit')
{
    foreach($order['lines'] as $line)
    {
        $initial[] = [
            'source' => $line['LineSource'],
            'kind' => ($line['LineSource'] === 'WAREHOUSE' && $line['products_PDID'] === null) ? 'custom' : 'product',
            'product_id' => $line['products_PDID'],
            'text' => $line['Description'],
            'qty' => $line['qty'],
            'notes' => (string)$line['Notes'],
            'invoice_no' => (string)$line['InvoiceNo'],
        ];
    }
}
$form = $mode === 'edit' ? $order : ['SupplierShopID' => 0, 'CustName' => '', 'CustPhone' => '', 'CustAddress' => '', 'NeededBy' => '', 'AdvancePaid' => '', 'Notes' => ''];
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
                    <input type="hidden" id="co_csrf_token" value="<?= co_h(csrf_token()) ?>">
                    <p class="mb-2"><a href="customer-orders.php<?= ($order !== null && $order['side'] === 'incoming') ? '?tab=incoming' : '' ?>"><i class="ti ti-arrow-left"></i> Customer Orders</a></p>

<?php if($mode !== 'view') { ?>
                    <!------------------------------ new / edit ------------------------------>
                    <div id="co_form" data-mode="<?= co_h($mode) ?>" data-order-id="<?= $order !== null ? (int)$order['COID'] : 0 ?>">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-3"><?= $mode === 'edit' ? 'Edit ' . co_h($order['OrderNo']) : 'New customer order' ?></h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label" for="co_supplier">Order from</label>
                                        <select id="co_supplier" class="form-select">
                                            <?php foreach($suppliers as $supplier) { ?>
                                            <option value="<?= (int)$supplier['SHID'] ?>"<?= (int)$supplier['SHID'] === (int)$form['SupplierShopID'] ? ' selected' : '' ?>><?= co_h($supplier['ShopName']) ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="co_needed_by">Needed by</label>
                                        <input type="date" id="co_needed_by" class="form-control" value="<?= co_h($form['NeededBy']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="co_advance">Advance paid</label>
                                        <input type="number" step="0.01" min="0" id="co_advance" class="form-control" placeholder="0.00" value="<?= co_h($form['AdvancePaid'] === '0.00' ? '' : $form['AdvancePaid']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="co_cust_name">Customer name <span class="text-danger">*</span></label>
                                        <input type="text" id="co_cust_name" class="form-control" maxlength="120" value="<?= co_h($form['CustName']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="co_cust_phone">Phone <span class="text-danger">*</span></label>
                                        <input type="text" id="co_cust_phone" class="form-control" maxlength="25" value="<?= co_h($form['CustPhone']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="co_cust_address">Address</label>
                                        <input type="text" id="co_cust_address" class="form-control" maxlength="255" value="<?= co_h($form['CustAddress']) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="co_notes">Notes</label>
                                        <textarea id="co_notes" class="form-control" rows="2" maxlength="2000"><?= co_h($form['Notes']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-1">Already given from our stock</h5>
                                <p class="text-muted mb-2">Items the customer already took from this shop. The supplier sees them on the order and never sends them.</p>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-2" id="co_given">
                                        <thead><tr><th style="min-width:300px;">Product, or a description</th><th style="width:120px;">Qty</th><th style="width:200px;">Invoice No</th><th style="width:60px;"></th></tr></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="co_add_given"><i class="ti ti-plus"></i> Add item already given</button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-1">From <span id="co_supplier_name"></span></h5>
                                <p class="text-muted mb-2">What the supplier must send. Put the customer's requirements (size, colour, firmness...) in the note.</p>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-2" id="co_warehouse">
                                        <thead><tr><th style="min-width:300px;">Item</th><th style="width:120px;">Qty</th><th style="min-width:220px;">Note</th><th style="width:60px;"></th></tr></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="co_add_product"><i class="ti ti-plus"></i> Add product</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="co_add_custom"><i class="ti ti-plus"></i> Add custom-made item</button>
                            </div>
                        </div>

                        <div id="co_error" class="alert alert-danger" style="display:none;"></div>
                        <div class="d-flex gap-2 mb-4">
                            <button type="button" class="btn btn-primary" id="co_save"><?= $mode === 'edit' ? 'Save changes' : 'Send to supplier' ?></button>
                            <a href="<?= $mode === 'edit' ? 'customer-order.php?id=' . (int)$order['COID'] : 'customer-orders.php' ?>" class="btn bg-danger-subtle text-danger">Cancel</a>
                        </div>
                        <script type="application/json" id="co_initial"><?= json_encode($initial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                    </div>
<?php } else { ?>
                    <!------------------------------ view ------------------------------>
                    <?php
                    $given = array_values(array_filter($order['lines'], function($l) { return $l['LineSource'] === 'GIVEN'; }));
                    $supplied = array_values(array_filter($order['lines'], function($l) { return $l['LineSource'] === 'WAREHOUSE'; }));
                    ?>
                    <div id="co_order" data-order-id="<?= (int)$order['COID'] ?>" data-showroom="<?= co_h($order['ShopName']) ?>">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <h5 class="card-title fw-semibold mb-0">Customer order <?= co_h($order['OrderNo']) ?></h5>
                                    <?= co_badge($order['status']) ?>
                                    <div class="ms-auto d-flex flex-wrap gap-2">
                                        <?php if($order['can_edit']) { ?><a href="customer-order.php?id=<?= (int)$order['COID'] ?>&amp;edit=1" class="btn btn-outline-primary btn-sm"><i class="ti ti-edit"></i> Edit</a><?php } ?>
                                        <?php if($order['can_accept']) { ?><button type="button" class="btn btn-success btn-sm co-action" data-action="accept">Accept</button><?php } ?>
                                        <?php if($order['can_create_transfer']) { ?><button type="button" class="btn btn-primary btn-sm co-action" data-action="create_transfer"><i class="ti ti-truck"></i> Create transfer</button><?php } ?>
                                        <?php if($order['can_reject']) { ?><button type="button" class="btn btn-outline-danger btn-sm co-action" data-action="reject">Reject</button><?php } ?>
                                        <?php if($order['can_handover']) { ?><button type="button" class="btn btn-success btn-sm co-action" data-action="handover">Handed over</button><?php } ?>
                                        <?php if($order['can_cancel']) { ?><button type="button" class="btn btn-outline-danger btn-sm co-action" data-action="cancel">Cancel order</button><?php } ?>
                                    </div>
                                </div>
                                <p class="mb-3">From <strong><?= co_h($order['ShopName']) ?></strong> to <strong><?= co_h($order['SupplierName']) ?></strong></p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm mb-3">
                                            <tr><th style="width:140px;">Customer</th><td><?= co_h($order['CustName']) ?></td></tr>
                                            <tr><th>Phone</th><td><?= co_h($order['CustPhone']) ?></td></tr>
                                            <?php if($order['CustAddress'] !== null) { ?><tr><th>Address</th><td><?= co_h($order['CustAddress']) ?></td></tr><?php } ?>
                                            <?php if($order['NeededBy'] !== null) { ?><tr><th>Needed by</th><td><?= co_h(co_date($order['NeededBy'])) ?></td></tr><?php } ?>
                                            <tr><th>Advance paid</th><td><?= co_h($order['AdvancePaid']) ?></td></tr>
                                            <?php if($order['Notes'] !== null) { ?><tr><th>Notes</th><td style="white-space:pre-line;"><?= co_h($order['Notes']) ?></td></tr><?php } ?>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-1"><i class="ti ti-point"></i> Placed by <?= co_h($order['CreatedByName']) ?> on <?= co_h(co_date($order['CreatedAt'], true)) ?></li>
                                            <?php if($order['DecidedAt'] !== null) { ?>
                                            <li class="mb-1"><i class="ti ti-point"></i> <?= (int)$order['OrderStat'] === CustomerOrders::REJECTED ? 'Rejected' : 'Accepted' ?> by <?= co_h($order['DecidedByName']) ?> on <?= co_h(co_date($order['DecidedAt'], true)) ?><?php if($order['RejectReason'] !== null) { ?>: <em><?= co_h($order['RejectReason']) ?></em><?php } ?></li>
                                            <?php } ?>
                                            <?php if($order['ClosedAt'] !== null) { ?>
                                            <li class="mb-1"><i class="ti ti-point"></i> <?= (int)$order['OrderStat'] === CustomerOrders::CANCELLED ? 'Cancelled' : 'Handed over' ?> by <?= co_h($order['ClosedByName']) ?> on <?= co_h(co_date($order['ClosedAt'], true)) ?><?php if($order['HandoverInvoiceNo'] !== null) { ?> (invoice <?= co_h($order['HandoverInvoiceNo']) ?>)<?php } ?></li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if(!empty($given)) { ?>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <h5 class="card-title fw-semibold mb-0">Given from <?= co_h($order['ShopName']) ?>'s stock</h5>
                                    <span class="badge text-bg-secondary">Already given - do not send</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead><tr><th>Item</th><th class="text-end">Qty</th><th>Invoice No</th></tr></thead>
                                        <tbody>
                                            <?php foreach($given as $line) { ?>
                                            <tr><td><?= co_h($line['Description']) ?></td><td class="text-end"><?= co_h(CustomerOrders::qtyText($line['qty'])) ?></td><td><?= co_h($line['InvoiceNo']) ?></td></tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-2">To be supplied by <?= co_h($order['SupplierName']) ?></h5>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0" id="co_supplied">
                                        <thead><tr><th>Item</th><th>Note</th><th class="text-end">Ordered</th><th class="text-end">On transfer</th><th class="text-end">Arrived</th><th></th></tr></thead>
                                        <tbody>
                                            <?php foreach($supplied as $line) {
                                                $custom = $line['products_PDID'] === null;
                                                $done = $line['arrived'] >= $line['qty'];
                                            ?>
                                            <tr>
                                                <td><?php if($custom) { ?><span class="badge text-bg-light me-1">Custom-made</span> <?php } ?><?= co_h($line['Description']) ?></td>
                                                <td><?= co_h($line['Notes']) ?><?php if($custom && $line['CustomNote'] !== null) { ?> <br><small class="text-muted">Sent: <?= co_h($line['CustomNote']) ?></small><?php } ?></td>
                                                <td class="text-end"><?= co_h(CustomerOrders::qtyText($line['qty'])) ?></td>
                                                <td class="text-end"><?= co_h(CustomerOrders::qtyText($line['on_transfer'])) ?></td>
                                                <td class="text-end<?= $done ? ' text-success fw-semibold' : '' ?>"><?= co_h(CustomerOrders::qtyText($line['arrived'])) ?><?php if($done) { ?> <i class="ti ti-check"></i><?php } ?></td>
                                                <td class="text-end">
                                                    <?php if($custom && $order['can_mark_custom']) { ?>
                                                    <button type="button" class="btn btn-outline-primary btn-sm co-action" data-action="custom_sent" data-line-id="<?= (int)$line['COLID'] ?>"
                                                        data-qty="<?= co_h(CustomerOrders::qtyText($line['qty'])) ?>" data-sent="<?= co_h(CustomerOrders::qtyText($line['CustomSent'])) ?>"
                                                        data-note="<?= co_h($line['CustomNote']) ?>">Mark sent</button>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <?php if(!empty($order['transfers'])) { ?>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-2">Transfers</h5>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0" id="co_transfers">
                                        <thead><tr><th>Transfer No</th><th>Date</th><th>Status</th><th></th></tr></thead>
                                        <tbody>
                                            <?php foreach($order['transfers'] as $transfer) { ?>
                                            <tr>
                                                <td><?= co_h($transfer['TransferNo']) ?></td>
                                                <td><?= co_h(co_date($transfer['EffectiveDate'])) ?></td>
                                                <td><?= co_h($transfer['status']) ?></td>
                                                <td class="text-end"><a href="transfer-details.php?id=<?= (int)$transfer['THID'] ?>" class="btn btn-outline-primary btn-sm">Open</a></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <!-- one dialog for every action on the order (Assets/jquery/customer_order.js) -->
                    <div class="modal fade" id="co_action_modal" tabindex="-1" aria-labelledby="co_modal_title" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="co_modal_title"></h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p id="co_modal_text"></p>
                                    <div id="co_modal_fields"></div>
                                    <div id="co_modal_error" class="text-danger fw-bold" style="display:none;"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" id="co_modal_ok">OK</button>
                                    <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
<?php } ?>
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
