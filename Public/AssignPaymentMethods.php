<?php
include "../Includes/includes.php";
include '../Includes/authcheck.php';
if($userObj->checkusertype($_SESSION["user_id"])==1)
{

}
else
{
    ?>
    <script>
        window.location.href = "../Public/home.php";
    </script>
    <?php
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include '../View/head.php';
    // include '../View/loader.php';
    ?>
</head>

<body>
    <?php
    //load editor
    $Fe_Id = 0;
    if (isset($_GET['Fe_Id'])) {
        $Fe_Id = $_GET['Fe_Id'];
        echo "<script>";
        echo "$(document).ready(function(){";
        echo "$('#add-payment-methods').modal('toggle');";
        echo "});";
        echo "</script>";
    }
    include '../View/modals/add-payment-methods.php';
    include "../View/modals/add-paymethod.php";
    ?>

    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <?php include '../View/sidebar.php'; ?>
            <div class="body-wrapper">
                <?php include '../View/header.php'; ?>
                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">Assign Payment to Shops</h5>

                    <button type="button" class="btn btn-primary rounded-pill ml-1 mb-2"
                        id="btn_Add_Pay_modal" data-bs-dismiss="modal">Assign Payment Methods</button>
                    <br>

                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table search-table align-middle text-nowrap" id="tbl_active_store">
                                            <thead class="header-item">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Shop</th>
                                                    <th>Payment Methods</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $PayObj = new AddPaymentModels();
                                                $PaymentName = $PayObj->getPaymentMethods();
                                                foreach ($PaymentName as $Payment): ?>
                                                    <tr data-id="<?php echo $Payment['SPID'];?>">
                                                        <td><?php echo $Payment['SPID']; ?></td>
                                                        <td>
                                                            <img src="../Assets/Images/shop_images/<?php echo $Payment['ShopLogo'];?>" alt="shop logo" style="width:50px; height:auto;">
                                                            <?php echo $Payment['ShopName']; ?>
                                                        </td>
                                                        <td>
                                                            <img src="../Assets/Images/paymethod_images/<?php echo $Payment['image_path'];?>" alt="shop logo" style="width:50px; height:auto;">
                                                            <?php echo $Payment['PaymethodName'];?>
                                                        </td>
                                                        <td>
                                                            <form action="../Controller/AddPaymentController.php" method="POST">
                                                                <input type="hidden" name="paymethod_id" value="<?php echo $Payment['SPID'];?>">
                                                                <button name="btn_delete_paymethod" class="btn btn-danger">Delete</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach;?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- add payment methods -->
                    <div class="card">
                        <div class="card-header">
                            <button type="button" id="btn_open_paymethod" class="btn btn-primary border border-success rounded-pill ml-1">Add Paymethod</button>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow:scroll; overflow-x:hidden;">
                            <table class="table">
                                <tr>
                                    <th>No</th>
                                    <th>Image</th>
                                    <th>Paymethod</th>
                                    <th>Action</th>
                                </tr>
                                <?php 
                                    $sql = "SELECT * FROM paymethod;";
                                    $dbObj = new DBTransactions();

                                    $dbData = $dbObj->getData($sql);
                                    $row_count = 0;
                                    foreach($dbData as $row)
                                    {
                                        $row_count += 1;
                                        ?>
                                        <tr>
                                            <td><?php echo $row_count?></td>
                                            <td>
                                                <img src="../Assets/Images/paymethod_images/<?php echo $row['image_path'];?>" alt="pay method Image" style="width: 80px; height:auto;">
                                            </td>
                                            <td><?php echo $row['PaymethodName']?></td>
                                            <td>Action</td>
                                        </tr>
                                        <?php 
                                    }//foreach
                                ?>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <?php include '../View/footer.php'; ?>
    <script src="../Assets/jquery/AddPayMethods.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>

</html>