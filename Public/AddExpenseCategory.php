<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../Includes/includes.php";
include '../Includes/authcheck.php';
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
        echo "$('#add-expense-category').modal('toggle');";
        echo "});";
        echo "</script>";
    }
    include '../View/modals/add-expense-category.php';
    ?>

    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <?php include '../View/sidebar.php'; 
            $feature_id=11;
            include '../Includes/viewPermission.php';
            
            ?>
            <div class="body-wrapper">
                <?php include '../View/header.php'; ?>
                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">Add Expense Category</h5>
                    <?php 
                    if($userType==1 || $create==1)
                    {
                        ?>
                        <button type="button" class="btn btn-primary border border-primary rounded-pill ml-1 mb-2"
                        id="btn_Add_Category_modal" data-bs-dismiss="modal">Add Category</button>
                        <?php
                    }
                    ?>
                    <br>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table search-table align-middle text-nowrap"
                                            id="tbl_active_store">
                                            <thead class="header-item">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Expence Category</th>
                                                    <th>Expence Type</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i=1;
                                                $CategoryObj = new AddExpenseCtgModels();
                                                $CategoryName = $CategoryObj->getCategories();
                                                if(count($CategoryName)>0)
                                                {
                                                    foreach ($CategoryName as $Category): ?>
                                                    <tr>
                                                        <td><?=$i?></td>
                                                        <td><?php echo $Category['expense_ctg']; ?></td>
                                                        <td><?php echo $Category['expense_type']; ?></td>
                                                        <td>
                                                            <?php 
                                                            if($userType==1 || $delete==1 || $edit==1)
                                                            {
                                                                ?>
                                                                <a class="btn_delete" id="btn_Add_Category_modal"
                                                                data-ecid="<?php echo $Category['ECID']; ?>">Delete</a>
                                                                <?php
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <?php 
                                                    $i++;
                                                    endforeach; 
                                                }
                                                else 
                                                {
                                                    ?>
                                                    <td class="text-danger text-center" colspan="4">No Items Found</td>
                                                    <?php
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
        </div>
    </div>
    <?php include '../View/footer.php'; ?>
    <script src="../Assets/jquery/AddExpenseCtg.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>

</html>