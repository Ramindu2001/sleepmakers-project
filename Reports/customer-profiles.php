<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
$dbObj = new DBTransactions();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
 include '../View/head.php';
 // include '../View/loader.php';

  ?>
  <style>
    .table>:not(caption)>*>* 
    {
        padding: 10px;
    }
    input[readonly]
    {
        background-color: rgb(235, 235, 235);
        border-color: rgb(235, 235, 235);
    }
        @media print {
    .no-print, .action-data {

        display: none !important; /* Hide the action column */
    }
}
</style>
</head>

<body>
<!--  Body Wrapper -->
<div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
             include '../View/sidebar.php';
            ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
                <!--  Header Start -->
                <?php 
                    include '../View/header.php';
                    
                ?>
                <!--  Header End -->
    
                <div class="container-fluid">
                <h5 class="card-title fw-semibold mb-4">Customers List</h5>

                <?php 
                if (isset($_SESSION["credit_customer"])) {
                // Display session messages
                }
                ?>

                <!-- Search Bar -->
                <div class="mb-3">
                <form method="GET" action="">
                <div class="input-group">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Search customers by name, number, or contact..." 
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '' ?>"
                />
                <button type="submit" class="btn btn-primary">Search</button>
                </div>
                </form>
                </div>
                <button type="button" class="btn btn-success" onclick="printTable(false)">Print</button>

                <!-- Customer Table -->
                <div class="card">
                <div class="card-body">
                <?php 
                $searchQuery = '';
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8');
                $searchQuery = "AND (CustomerNo LIKE '%$search%' OR CustName LIKE '%$search%' OR CustContact LIKE '%$search%')";
                }
                
                $sql = "SELECT * FROM shop
                INNER JOIN company ON company.CMID = shop.Company_CMID
                WHERE SHID = ".$shop_id.";";
                $shopData = $dbObj->getData($sql);
                $multi_category = $shopData[0]['is_multicategory'];
                if($multi_category==1)
                {
                    $com_id=$shopData[0]['CMID'];
                    $sql = "SELECT * FROM customers
                    INNER JOIN shop ON shop.SHID = customers.shop_SHID
                    WHERE shop.Company_CMID='$com_id' $searchQuery";
                }
                else
                {
                    $sql = "SELECT * FROM customers WHERE shop_SHID='$shop_id' $searchQuery";
                }
                
                $itemData = $dbObj->getData($sql);

                if (empty($itemData)) {
                echo '<p class="text-center text-muted">No customers found matching your search.</p>';
                } else {
                ?>
                <div class="card print-area" id="printArea">
    <div class="card-body">
        <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Customer No</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Contact No</th>
                            <th>Status</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        foreach ($itemData as $row) { ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><?= $row["CustomerNo"]?></td>
                                <td><?= $row["CustName"]?></td>
                                <td><?= $row["CustAddress"]?></td>
                                <td><?= $row["CustContact"]?></td>
                                <td><?= $row["CustStat"] == 1 ? "Active" : "Inactive" ?></td>
                                <td class="action-data">
                                    <a href="../Public/customerProfile.php?cus_id=<?= $row["CTID"] ?>">View Profile</a>
                                </td>
                            </tr>
                            <?php $i++;
                        } ?>
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
    <div id="hidden"></div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <!-- <script src="../Assets/jquery/credit-pay.js"></script> -->
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>  
    <script>
function printTable(hideAction) {
   
document.querySelectorAll(".no-print, .action-data").forEach(el => el.classList.remove("hide-on-print"));
    
    let printContents = document.getElementById("printArea").innerHTML;
    let originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload(); // Reload to restore functionality
}
</script>
                                         
</body>

</html>


