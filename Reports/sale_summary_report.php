<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

?>
<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  include '../View/datatables.php';
  include "../View/modals/view-invoice.php";
  $sql = "SELECT * FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id='$shop_id' AND RecieptType=2";
  $dbObj = new DBTransactions();
  $dbData = $dbObj->getData($sql);
  $invoices = empty($dbData) ? "wholesaleInvoice.php" : $dbData[0]['ReceiptPath'];

  //get current date time
  date_default_timezone_set("Asia/Colombo");

  $start_date = "";
  $end_date = "";
  if (isset($_GET['date'])) {
    $date = explode("_", $_GET['date']);
    $start_date = $date[0];
    $end_date = $date[1];
} 
if(isset($_GET["start_date"]) && isset($_GET["end_date"]))
{
    $start_date = $_GET["start_date"];
    $end_date = $_GET["end_date"];
}
else {
    $start_date = date("Y-m-d");
    $end_date = date("Y-m-d");
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
    include '../View/sidebar.php';
    $feature_id=39;
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
        include '../View/header.php';
        include "../View/modals/main-category.php";
        $shops= new Shop();
        $shop=$shops->getOneShop($shop_id);

        //get date
        date_default_timezone_set("Asia/Colombo");
        $effective_date = date("Y-m-d");
        ?>
         <div class="container-fluid">
            <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
            <input type="hidden" name="" id="invoices" value="<?=$invoices?>">
            <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?>">
            <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
            <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?>"> 
            <input type="hidden" name="" id="title" value="Sales Summary">
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Sales Summary</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <form action=" " class="form-inline" method="get" accept-charset="utf-8">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="" for="from_date">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" id="from_date" placeholder="Start Date" value="<?php echo $start_date;?>">
                                </div> 
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="" for="to_date">End Date</label>
                                    <input type="date" name="end_date"class="form-control" id="to_date" placeholder="End Date" value="<?php echo $end_date;?>">
                                </div>
                            </div>
                            <?php 
                                    if($userType==1)
                                    {
                                        ?>
                                        <div class="col-md-2">
                                            <label for="claim" class="form-label">Claim Bill With Invoices</label>
                                            <input type="checkbox" name="claim" id="claim" class="form-check" value="1"
                                            <?php 
                                            if(isset($_GET["claim"]))
                                            {
                                                echo "checked";
                                            }
                                            ?>
                                            >
                                        </div>
                                        <?php
                                    }
                                    ?>
                                    
                            <div class="col-md-2 mt-4">
                                <button type="submit" id="btn-filter" name="btn_sale_summary" class="btn btn-success">Find</button>
                            </div>
                        </div>
                    </form>
                    <table class="table table-hover" id="tbl_category">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Sales Date</th>
                            <th>Invoice Number</th>
                            <th>Sales Person</th>
                            <th>Cashier</th>
                            <th>Invoice Status</th>
                            <th>Gross Amount</th>
                            <th>Total Discount</th>
                            <th>Total Amount</th>           
                            <th class="text-center">View Invoice</th>  
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dbObj = new DBTransactions();
                        $add = "";
                                    if (isset($_GET["claim"])) {
                                        $add = " OR ih.InvStat=6";
                                    }
                        $sql = "SELECT ih.*, sm.SalesmansName as SalesPerson, u.UserName AS cashier FROM `invoiceheader` ih 
                                left join salesmans sm ON ih.Salesmans_SLID = sm.SLID
                                left join user u ON ih.user_USID=u.USID
                                WHERE ih.shop_SHID=".$shop_id." AND ih.InvStat = 1 $add AND EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' ";

                        $saleData = $dbObj->getData($sql);

                        // $saleObj = new Report();
                        // $saleData = $saleObj->sales($shop_id);
                        $i=1;
                        $total_discount = 0;
                        $total_amount = 0;
                        foreach ($saleData as $row) 
                        {
                             $total_discount += $row['DiscountAmount'];
                             $total_amount += $row['NetAmount'];
                            ?>
                            <tr>
                                <td> <?php echo $i;?> </td>
                                <td> <?php echo $row['EffectiveDate'] ?> </td>
                                <td> <?php echo $row['BillNo'] ?></td>
                                <td> <?php echo $row['SalesPerson'] ?></td>
                                <td> <?php echo $row['cashier'] ?></td>
                                <td> <?php echo $InvStat = $row['InvStat']; ?></td>
                                

                                                                

                                <td>
                                    <?php 
                                        if($InvStat==1)
                                        {
                                            ?>
                                            <span class="badge bg-success">Finalized</span>
                                            <?php
                                        }
                                        else if($InvStat==6)
                                        {
                                            ?>
                                            <span class="badge bg-danger" >Claim Bill</span>
                                            <?php
                                        }
                                        else
                                        {
                                            ?>
                                            <span class="badge bg-danger" >N/A</span>
                                            <?php
                                        }
                                        ?></td>
                                <td align="right"> <?php echo $row['GrossAmount'] ?></td>
                                <td align="right"> <?php echo $row['DiscountAmount']?></td>
                                <td align="right"> <?php echo $row['NetAmount']?></td>
                                <td class="text-center">
                                    <input type="hidden" name="" id="invoiceID" value="<?=$row["IHID"]?>">
                                    <?php 
                                    $sql="SELECT * FROM invoiceheader WHERE IHID='$row[IHID]'";
                                    $query=$dbObj->getData($sql);
                                    $count=count($query);
                                    if($count>0)
                                    {
                                        ?>
                                        <a href="javascript:void;" class="btn btn-primary viewInvoice">View Invoice</a>
                                        <?php
                                    }
                                    ?>
                                    
                                </td>
                            </tr>
                            <?php 
                            $i++;
                        }
                        ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="8"></td> 
                        <td class="text-end"><strong>Total Discount: <?php echo number_format($total_discount, 2); ?></strong><input type="hidden" name="total_discount" id="total_discount" value="<?=$total_discount?>"></td>
                        <td class="text-end"><strong>Total Amount: <?php echo number_format($total_amount, 2); ?></strong><input type="hidden" name="total_amount" id="total_amount" value="<?=$total_amount?>"></td>
                        <td></td>
                    </tr>
                </tfoot>
                </table>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
    $(document).ready(function () {
        $(".viewInvoice").click(function(){
            var invoices= $("#invoices").val();
            var invoiceID= $(this).parent().find("#invoiceID").val();
            var src="../Receipts/<?=$invoices?>?invoice="+invoiceID+"&Iframe=1";
            $("#iframe").attr("src",src);
            $("#viewInvoice_modal").modal("toggle");
        });
    })
</script>
<?php include '../View/footer.php';?> 

    <script src="../Assets/jquery/sale_summary.js"></script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>
</html>
