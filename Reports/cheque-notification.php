<?php 
require_once '../Includes/includes.php';
require_once '../Includes/authcheck.php';
?>

<!doctype html>
<html lang="en">
<head>
  <?php 
  require_once '../View/head.php';
  require_once '../View/loader.php';
  require_once '../View/datatables.php';
  ?>
</head>
<body>
<div class="h-100vh">
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" 
         data-sidebar-position="fixed" data-header-position="fixed">

        <!-- Sidebar -->
        <?php 
        require_once '../View/sidebar.php';
        $feature_id = 55;
        include '../Includes/viewPermission.php';

        if (!($userType == 1 || $print == 1)) {
            echo "<script>
                    setInterval(function() {
                        $('.dt-buttons').addClass('d-none');  
                    }, 100);
                  </script>";
        }
        ?>

        <div class="body-wrapper">
            <?php 
            require_once '../View/header.php';
            require_once "../View/modals/main-category.php";
            $shops = new Shop();
            $shop = $shops->getOneShop($shop_id);
            ?>

            <div class="container-fluid">
                <input type="hidden" id="shop_name" value="<?=$shop[0]['ShopName']?>">
                <input type="hidden" id="shop_number" value="<?=$shop[0]['CompanyLocation']?>">
                <input type="hidden" id="shop_address" value="<?=$shop[0]['CompanyLocation']?>">

                <!-- Customer Cheque Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-0"><b>Customer Cheque Payments Alert</b></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="customerChequeTable">
                                <thead>
                                    <tr class="text-end">
                                        <th>Sl</th>
                                         <th>Cheque No</th>
                                        <th>Cheque Date</th>
                                        <th>Customer Name</th>
                                        <th>Customer Cheque Type</th>
                                        <th>Bank</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $shop_id = $_SESSION['shop_id'];
                                    $status = 1;

                                    $sql = "SELECT *, ccd.chqDate AS RealizeDate FROM `custcheq` cc
                                            INNER JOIN custchqdetail ccd ON cc.CCQID = ccd.CCQID
                                            INNER JOIN customers c ON c.CTID = cc.cust_CTID
                                            INNER JOIN invoiceheader i ON i.IHID = cc.invoiceID
                                            WHERE cc.shop_SHID = '$shop_id' AND cc.chq_stat = '$status'
                                            AND ccd.chqDate != '0000-00-00' 
                                            AND (
                                                ccd.chqDate <= CURDATE() 
                                                OR ccd.chqDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)
                                            )
                                            ORDER BY cc.chq_no DESC";

                                    $dbObj = new DBTransactions();
                                    $invData = $dbObj->getData($sql);
                                    $i = 1;

                                    if (count($invData) > 0) {
                                        foreach ($invData as $row) {
                                            $isExpired = strtotime($row["chqDate"]) <= time();
                                            $class = $isExpired ? 'text-danger' : '';
                                    ?>
                                            <tr class="text-end <?=$class?>">
                                                <td><?=$i++?></td>
                                                <td><?=$row["chq_no"]?></td>
                                                <td><?=$row["chqDate"]?></td>
                                                <td><?=$row["CustName"]?></td>
                                                <td>
                                                    <?= $row["type"] == 1
                                                        ? '<span class="text-danger fw-bold"><i class="ti ti-outbound" style="transform:rotate(311deg);"></i> Issued Cheque</span>'
                                                        : '<span class="text-success fw-bold"><i class="ti ti-outbound" style="transform:rotate(133deg);"></i> Received Cheque</span>'; ?>
                                                </td>
                                                <td><?=$row["bank"]?></td>
                                                <td><?=number_format($row["chqAmount"], 2)?></td>
                                                <td>
                                                    <a href="../Public/edit-cheque.php?type=2&chq=<?=$row["CCQID"]?>" class="btn btn-primary"><i class="ti ti-edit"></i></a>
                                                </td>
                                                
                                            </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center text-danger">No Results Found</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Supplier Cheque Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-0"><b>Supplier Cheque Payments Alert</b></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="supplierChequeTable">
                                <thead>
                                    <tr class="text-end">
                                        <th>Sl</th>
                                        <th>Cheque No</th>
                                        <th>Cheque Date</th>
                                        <th>Supplier Name</th>
                                        <th>Supplier Cheque Type</th>
                                        <th>Bank</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $sql = "SELECT *, scd.chqDate AS RealizeDate FROM supcheq sc
                                            INNER JOIN supchqdetail scd ON sc.SCQID = scd.SCQID
                                            INNER JOIN suppliers s ON s.SPID = sc.sup_SPID
                                            INNER JOIN grnheader g ON g.GHID = sc.GRNHeader_GHID
                                            WHERE sc.shop_SHID = '$shop_id' AND sc.chq_stat = '$status'
                                            AND scd.chqDate != '0000-00-00' 
                                            AND (
                                                scd.chqDate <= CURDATE() 
                                                OR scd.chqDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)
                                            )
                                            ORDER BY sc.chq_no DESC";

                                    $invData = $dbObj->getData($sql);
                                    $i = 1;

                                    if (count($invData) > 0) {
                                        foreach ($invData as $row) {
                                            $isExpired = strtotime($row["chqDate"]) <= time();
                                            $class = $isExpired ? 'text-danger' : '';
                                    ?>
                                            <tr class="text-end <?=$class?>">
                                                <td><?=$i++?></td>
                                                <td><?=$row["chq_no"]?></td>
                                                <td><?=$row["chqDate"]?></td>
                                                <td><?=$row["SupplierName"]?></td>
                                                <td>
                                                    <?= $row["type"] == 1
                                                        ? '<span class="text-danger fw-bold"><i class="ti ti-outbound" style="transform:rotate(311deg);"></i> Issued Cheque</span>'
                                                        : '<span class="text-success fw-bold"><i class="ti ti-outbound" style="transform:rotate(133deg);"></i> Received Cheque</span>'; ?>
                                                </td>
                                                <td><?=$row["bank"]?></td>
                                                <td><?=number_format($row["chqAmount"], 2)?></td>
                                                <td>
                                                    <a href="../Public/edit-cheque.php?type=1&chq=<?=$row["SCQID"]?>" class="btn btn-primary"><i class="ti ti-edit"></i></a>
                                                </td>
                                            </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center text-danger">No Results Found</td></tr>';
                                    }
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

<?php require_once '../View/footer.php'; ?>
<script src="../Assets/jquery/sale_summary.js"></script>
<script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Assets/js/sidebarmenu.js"></script>
<script src="../Assets/js/app.min.js"></script>
<script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
<script src="../Assets/js/dashboard.js"></script>
</body>
</html>
