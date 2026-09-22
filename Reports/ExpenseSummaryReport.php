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
    <!--  Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php
            require_once '../View/sidebar.php';
            $feature_id=53;
            include '../Includes/viewPermission.php';
            if($userType==1 || $print==1)
            {
                
            }
            else
            {
                ?>
                <script>
                    setInterval(function(){
                        $(".dt-buttons").addClass("d-none");  
                    }, 100);
                    
                </script>
                <?php
            }
            ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
                <?php
                require_once '../View/header.php';
                require_once "../View/modals/main-category.php";
                $shops = new Shop();
                $shop = $shops->getOneShop($shop_id);
                $from_date = $_POST['from_date'] ?? '';
                $to_date = $_POST['to_date'] ?? '';
                ?>
                <div class="container-fluid">
                    <input type="hidden" name="" id="shop_name" value="<?= $shop[0]['ShopName'] ?>">
                    <input type="hidden" name="" id="shop_address_one" value="<?= $shop[0]['AddressLineOne'] ?>">
                    <input type="hidden" name="" id="shop_address_two" value="<?= $shop[0]['AddressLineTwo'] ?>">
                    <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
                    <input type="hidden" name="" id="shop_number" value="<?= $shop[0]['PhoneNumber'] ?>">
                    <input type="hidden" name="" id="title" value="Expense Summary">
                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Expense Summary</h5>
                    <div class="container-fluid">
                        <div class="card">
                            <div class="card-body">
                                <form action="" class="form-inline" method="post" accept-charset="utf-8">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="" for="from_date">Start Date</label>
                                                <input type="date" name="from_date" class="form-control datepicker"
                                                    id="from_date" placeholder="Start Date"
                                                    value="<?php echo $from_date; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="" for="to_date">End Date</label>
                                                <input type="date" name="to_date" class="form-control datepicker"
                                                    id="to_date" placeholder="End Date" value="<?php echo $to_date; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mt-4">
                                            <button type="submit" id="btn-filter" class="btn btn-success">Find</button>
                                        </div>
                                    </div>
                                </form>
                                <table class="table table-hover" id="tbl_expense">
                                    <thead>
                                        <tr>
                                            <th>Expense ID</th>
                                            <th>Date</th>
                                            <th>Category</th>
                                            <th>Reason</th>
                                            <th>User</th>
                                            <th>Shop</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $expenseObj = new Report();
                                        $expenseData = $expenseObj->expenseByDate($shop_id, $from_date, $to_date);
                                        $i = 1;
                                        $totalAmount = 0;
                                        foreach ($expenseData as $row) {
                                            $totalAmount += $row['ExpenseAmount'];
                                            ?>
                                            <tr>
                                                <td><?php echo $i;?></td>
                                                <td><?php echo $row['EffectiveDate']?></td>
                                                <td><?php echo $row['expense_ctg']?></td>
                                                <td><?php echo $row['ExpenseReason'] ?></td>
                                                <td><?php echo $row['UserName'] ?></td>
                                                <td><?php echo $row['ShopName'] ?></td>
                                                <td><?php echo $row['ExpenseAmount'] ?></td>
                                            </tr>
                                            <?php
                                            $i++;
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                    <td colspan="6"></td> 
                                    <td class="text-end"><strong>Total Amount: <?php echo number_format($totalAmount, 2); ?></strong><input type="hidden" name="totalAmount" id="totalAmount" value="<?=$totalAmount?>"></td>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--  Body Wrapper End -->
        <?php require_once '../View/footer.php'; ?>

        <script src="../Assets/jquery/expense_summary.js"></script>
        <script src="../Assets/js/sidebarmenu.js"></script>
</body>
</html>