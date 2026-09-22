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
  if(!isset($_GET["id"]) || $_GET["id"]==0 || $_GET["id"]==null || $_GET["id"]=="" || $_GET["id"]==" ")
    {
        ?>
        <script>
            $(document).ready(function() {
                window.history.back();
            });
        </script>
        <?php
    }
    else
    {
        $id=$_GET["id"];
        $salesreturn= new Sales_return_class();
        $details=$salesreturn->return_view_details($id,$shop_id);
        if(count($details)>0)
        {

        }
        else
        {
            header("Location:../Public/invoice_return_report.php");
        }
    }
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
        ?>
        <!--  Sidebar End -->
        <!--  Main wrapper -->
        <div class="body-wrapper">
            <?php 
            require_once '../View/header.php';
            require_once "../View/modals/main-category.php";
            $shops= new Shop();
            $shop=$shops->getOneShop($shop_id);
            ?>
            <div class="container-fluid">
                <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
                <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['CompanyLocation']?>">
                <input type="hidden" name="" id="shop_address" value="<?=$shop[0]['CompanyLocation']?>"> 
                <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Return View Details</h5>
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card-header">
                                        <h5 class="card-title fw-semibold mb-3">Sales Return:-  <span style="color: #d71920;font-weight: 900;"><?=$details[0]["return_no"]?></span> </h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="sales-return-form" action="../Controller/SalesReturnControl.php" method="post">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="IDate" class="form-label">Return Date</label>
                                                    <input type="text" name="IDate" id="IDate" class="form-control" value="<?=$details[0]["EffectiveDate"]?>" readonly>
                                                </div>
                                                <div class="col-md-6 p-4">
                                                    <a href="sales-return-credit-note.php?invoice_id=<?=$id?>" class="btn btn-primary" id="print">
                                                        <i class="ti ti-printer"></i> Print Return Note
                                                    </a>
                                                </div>
                                                <div class="col-md-12 mt-5">
                                                    <div class="table-responsive">
                                                        <table class="table">
                                                            <thead>
                                                                <tr>
                                                                    <th>SN</th>
                                                                    <th>Item Information</th>
                                                                    <th>Return Quantity</th>
                                                                    <th>Rate</th>
                                                                    <th>Discount</th>
                                                                    <th>Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                $sl=1;
                                                                $invoiceDetails=$salesreturn->return_details($id);
                                                                foreach ($invoiceDetails as $row) 
                                                                {
                                                                    ?>
                                                                    <tr id="idid_">
                                                                        <td>
                                                                            <?=$sl?>
                                                                        </td>
                                                                        <td>
                                                                            <?=$invoiceDetails[0]["ItemName"]?>
                                                                        </td>
                                                                        <td style="padding: 10px;">
                                                                            <?=$invoiceDetails[0]["ReturnQty"]?>
                                                                        </td>
                                                                        <td>
                                                                            <?=$invoiceDetails[0]["return_unit_price"]?>
                                                                        </td>
                                                                        <td>
                                                                            <?=$invoiceDetails[0]["return_discount"]?>
                                                                        </td>
                                                                        <td>
                                                                            <?=$invoiceDetails[0]["ReturnAmount"]?>
                                                                        </td>
                                                                    </tr>
                                                                    <?php 
                                                                    $sl=$sl+1;
                                                                }
                                                                ?>
                                                                
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="4" rowspan="3">
                                                                        <div class="row">
                                                                            <div class="col-md-6"><p> Reason: -</p></div>
                                                                            <div class="col-md-6"><p><?=$details[0]["reason"]?></p></div>
                                                                            <div class="col-md-6"><p class="w-49"> Stock: -</p></div>
                                                                            <div class="col-md-6"><p class="w-49"><?php 
                                                                        if($details[0]["usability"]==1)
                                                                        {
                                                                            echo "Adjust Stock";
                                                                        }
                                                                        else
                                                                        {
                                                                            echo "Damage Stock";
                                                                        }
                                                                        ?></p></div>
                                                                            <div class="col-md-6"><p class="w-49"> Return Type: -</p></div>
                                                                            <div class="col-md-6"><p class="w-49"><?php 
                                                                        if($details[0]["return_type"]==1)
                                                                        {
                                                                            echo "Cash Refund";
                                                                        }
                                                                        else
                                                                        {
                                                                            echo "Exchange";
                                                                        }
                                                                        ?></p></div>
                                                                        </div>                                                                        
                                                                    </td>
                                                                    <td>Total Return Amount</td>
                                                                    <td>
                                                                        <?= number_format($details[0]["return_amount"], 2);?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Total Discount</td>
                                                                    <td>
                                                                        <?= number_format($details[0]["return_discount"], 2);?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Total Amount</td>
                                                                    <td>
                                                                        <?= number_format($details[0]["return_gross_amount"], 2);?>
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--  Body Wrapper End -->
<?php require_once '../View/footer.php';?> 

    <script src="../Assets/jquery/sale_summary.js"></script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
    
<script>
</script>
</body>
</html>
