<?php
include "../Includes/includes.php";
include '../Includes/authcheck.php';

// Debugging: Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$shop_id = $_SESSION['shop_id'];
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
        echo "$('#add-expenses').modal('toggle');";
        echo "});";
        echo "</script>";
    }
    include '../View/modals/add-expenses.php';
    include '../View/modals/Edit-expenses.php';

    ?>
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <?php include '../View/sidebar.php'; 
            $feature_id=13;
            include '../Includes/viewPermission.php';?>
            <div class="body-wrapper">
                <?php include '../View/header.php';?>
                <div class="container-fluid">
                    <?php 
                    if(isset($_SESSION["exp_edit"]) && $_SESSION["exp_edit"]==1)
                    {
                        ?>
                        <div class="alert alert-success">
                            Expense Edited Successfully
                        </div>
                        <?php
                        unset($_SESSION["exp_edit"]);
                    }
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-4">
                                Add Expenses
                                <?php 
                                if($create==1 || $userType==1)
                                {
                                    ?>
                                <button type="button" class="btn btn-primary rounded-pill float-end"
                                    id="btn_Add_Expense_modal">
                                    <small>Add Expenses</small>
                                </button>
                                <?php
                                }
                                ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table search-table align-middle text-nowrap" id="tbl_expenses">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Date</th>
                                                    <th>Expense Amount</th>
                                                    <th>Expense Category</th>
                                                    <th>Remarks</th>
                                                    <th>User</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM expenses 
                                                INNER JOIN expensecategory ON expensecategory.ECID = expenses.expensecategory_id
                                                INNER JOIN user ON user.USID = expenses.user_USID
                                                WHERE shop_SHID = ".$shop_id." ORDER BY EffectiveDate DESC LIMIT 50;";


                                                $dbObj = new DBTransactions();
                                                $expenData = $dbObj->getData($sql);

                                                $i=1;
                                                if(count($expenData)>0)
                                                {
                                                foreach ($expenData as $row): ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $row['EffectiveDate']; ?></td>
                                                    <td><?php echo $row['ExpenseAmount']; ?></td>
                                                    <td><?php echo $row['expense_ctg']; ?></td>
                                                    <td><?php echo $row['ExpenseReason']; ?></td>
                                                    <td><?php echo $row['UserName']; ?></td>
                                                    <td>
                                                        <?php 
                                                        if($userType==1 || $edit==1) {
                                                        ?>
                                                    <a href="javascript:void(0);" class="btn_edit btn btn-primary me-2" id="btn_edit"
                                                    data-epid="<?php echo $row['EPID']; ?>">Edit</a>
                
                                                        <?php } ?>

                                                        <?php if($userType==1 || $delete==1) { ?>
                                                        <a href="javascript:void(0);" class="btn_delete btn btn-danger"
                                                            data-epid="<?php echo $row['EPID']; ?>">Delete</a>
                                                        <?php } ?>
                                                    </td>

                                                </tr>
                                                <?php 
                                                $i++;
                                                endforeach; 
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

    <script src="../Assets/jquery/AddExpenses.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
    $(document).ready(function() {
        $("#tbl_expenses").DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            // pageLength: 50,
        }); //data table
    });
    </script>

</body>

</html>

