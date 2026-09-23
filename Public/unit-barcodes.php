<?php
/**
 * Items > Unit Barcodes.
 * -----------------------------------------------------------------------------
 * What this shop has made: how many units were printed on each day and how many
 * of them have been received, the print jobs themselves (so a damaged sticker
 * can be printed again with the SAME code), and a box to look a single unit up.
 *
 * Read only - nothing here numbers a new unit. See db/UNIT_BARCODES_MODULE.md.
 */
include '../Includes/includes.php';
include '../Includes/authcheck.php';
require_once '../Model/unit_barcode_refused_class.php';
require_once '../Model/product_unit_class.php';

$unitObj = new ProductUnits();

//the dates being looked at: this month unless the form says otherwise
$from = ProductUnits::validDate(isset($_GET['from']) ? $_GET['from'] : '');
$to   = ProductUnits::validDate(isset($_GET['to']) ? $_GET['to'] : '');
$from = $from === null ? date('Y-m-01') : $from;
$to   = $to === null ? date('Y-m-t') : $to;

$find = trim((string) (isset($_GET['find']) ? $_GET['find'] : ''));
$found = $find === '' ? array() : $unitObj->resolve(array($find), $shop_id);
$foundUnit = empty($found) ? null : reset($found);

function ubE($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}//ubE
?>
<!doctype html>
<html lang="en">

<head>
    <?php include '../View/head.php'; ?>
</head>

<body>
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <?php
            include '../View/sidebar.php';
            $feature_id = 16;                       //Products, like the rest of the barcode module
            include '../Includes/viewPermission.php';
            ?>
            <div class="body-wrapper">
                <?php include '../View/header.php'; ?>
                <div class="container-fluid">

                    <h5 class="card-title fw-semibold mb-1">Unit Barcodes</h5>
                    <p class="mb-4 text-muted">
                        Every unit made in <b><?= ubE($shop_name) ?></b> carries its own barcode. Nothing can be
                        received twice, and what was produced on a day is counted here.
                        <?php if (!$unitObj->modeOn($shop_id)) { ?>
                            <span class="badge text-bg-warning ms-1">Switched off</span>
                            <a href="barcode-settings.php" class="ms-1">Turn unit barcodes on</a>
                        <?php } ?>
                    </p>

                    <!-- ------------------------------------------------ find one unit -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-2 align-items-end">
                                <div class="col-12 col-md-5">
                                    <label class="form-label fs-2 text-muted mb-1" for="find">Look a unit up</label>
                                    <input type="text" class="form-control" id="find" name="find" value="<?= ubE($find) ?>"
                                           placeholder="scan or type a unit barcode">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">Find</button>
                                </div>
                            </form>

                            <?php if ($find !== '') { ?>
                                <div class="mt-3">
                                    <?php if ($foundUnit === null) { ?>
                                        <div class="alert alert-warning mb-0 py-2">
                                            <b><?= ubE($find) ?></b> is not a unit barcode we printed.
                                        </div>
                                    <?php } else { ?>
                                        <table class="table table-sm align-middle mb-0">
                                            <tr><th style="width:180px;">Unit barcode</th>
                                                <td class="font-monospace"><?= ubE($foundUnit['UnitBarcode']) ?></td></tr>
                                            <tr><th>Item</th>
                                                <td><?= ubE($foundUnit['ItemName']) ?>
                                                    <span class="text-muted font-monospace ms-1"><?= ubE($foundUnit['ItemBarcode']) ?></span></td></tr>
                                            <tr><th>Made on</th><td><?= ubE($foundUnit['ProducedDate']) ?></td></tr>
                                            <tr><th>Printed</th>
                                                <td><?= ubE($foundUnit['PrintedAt']) ?> &middot; job <?= ubE($foundUnit['PrintRef']) ?>
                                                    <?php if ((int) $foundUnit['PrintCount'] > 1) { ?>
                                                        <span class="badge text-bg-light text-muted ms-1">printed <?= (int) $foundUnit['PrintCount'] ?> times</span>
                                                    <?php } ?></td></tr>
                                            <tr><th>State</th>
                                                <td>
                                                    <?php if ((int) $foundUnit['UnitStat'] === ProductUnits::RECEIVED) { ?>
                                                        <span class="badge text-bg-success">Received</span>
                                                        on <?= ubE($foundUnit['GRNHeaderNo']) ?> at <?= ubE($foundUnit['ReceivedAt']) ?>
                                                    <?php } else { ?>
                                                        <span class="badge text-bg-secondary">Printed, not received yet</span>
                                                    <?php } ?>
                                                </td></tr>
                                        </table>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="row">
                        <!-- --------------------------------------- what was produced -->
                        <div class="col-12 col-lg-7">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title fw-semibold mb-0" style="margin-top:0;">Produced</h5>
                                    <span class="text-muted fs-2">units printed per day, and how many are in stock</span>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="row g-2 align-items-end mb-3">
                                        <div class="col-6 col-md-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="from">From</label>
                                            <input type="date" class="form-control" id="from" name="from" value="<?= ubE($from) ?>">
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="to">To</label>
                                            <input type="date" class="form-control" id="to" name="to" value="<?= ubE($to) ?>">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-light border">Show</button>
                                        </div>
                                    </form>

                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Made on</th>
                                                    <th>Item</th>
                                                    <th class="text-end">Units</th>
                                                    <th class="text-end">Received</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $summary = $unitObj->produced($shop_id, $from, $to);
                                                $total = 0;
                                                foreach ($summary as $row) {
                                                    $total += $row['printed'];
                                                    ?>
                                                    <tr>
                                                        <td><?= ubE($row['date']) ?></td>
                                                        <td><?= ubE($row['name']) ?></td>
                                                        <td class="text-end"><?= (int) $row['printed'] ?></td>
                                                        <td class="text-end">
                                                            <?php if ($row['received'] === $row['printed']) { ?>
                                                                <span class="badge text-bg-success"><?= (int) $row['received'] ?></span>
                                                            <?php } else { ?>
                                                                <?= (int) $row['received'] ?>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }//each day and item
                                                if (empty($summary)) {
                                                    ?>
                                                    <tr><td colspan="4" class="text-muted">Nothing was printed between these dates.</td></tr>
                                                    <?php
                                                }//nothing
                                                ?>
                                            </tbody>
                                            <?php if (!empty($summary)) { ?>
                                                <tfoot>
                                                    <tr class="fw-semibold">
                                                        <td colspan="2">Total</td>
                                                        <td class="text-end"><?= (int) $total ?></td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            <?php } ?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ------------------------------------------- print jobs -->
                        <div class="col-12 col-lg-5">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title fw-semibold mb-0" style="margin-top:0;">Print Jobs</h5>
                                    <span class="text-muted fs-2">
                                        Reprint puts the same codes on paper again - it never numbers new units
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Job</th>
                                                    <th>Printed</th>
                                                    <th class="text-end">Units</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $jobs = $unitObj->printJobs($shop_id, 25);
                                                foreach ($jobs as $job) {
                                                    ?>
                                                    <tr>
                                                        <td class="font-monospace"><?= ubE($job['print_ref']) ?></td>
                                                        <td>
                                                            <?= ubE(substr((string) $job['printed_at'], 0, 16)) ?>
                                                            <div class="text-muted fs-2"><?= ubE($job['items']) ?></div>
                                                        </td>
                                                        <td class="text-end"><?= (int) $job['units'] ?></td>
                                                        <td class="text-end">
                                                            <?php if ($userType == 1 || (isset($print) && $print == 1)) { ?>
                                                                <form action="print-barcode.php" method="POST" target="_blank" class="d-inline">
                                                                    <input type="hidden" name="print_mode" value="reprint">
                                                                    <input type="hidden" name="print_ref" value="<?= ubE($job['print_ref']) ?>">
                                                                    <button type="submit" name="btn_print_barcode" value="1"
                                                                            class="btn btn-sm btn-light border" title="Print these same codes again">
                                                                        <i class="ti ti-printer"></i> Reprint
                                                                    </button>
                                                                </form>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }//each job
                                                if (empty($jobs)) {
                                                    ?>
                                                    <tr><td colspan="4" class="text-muted">
                                                        No units have been printed yet. Select products on the
                                                        <a href="product.php">Products</a> page and use Print Barcode.
                                                    </td></tr>
                                                    <?php
                                                }//nothing yet
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <?php include '../View/footer.php'; ?>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>

</html>
