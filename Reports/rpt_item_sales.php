<?php 
// require_once '../Includes/includes.php';
include "../Includes/includes.php";
require_once '../Includes/authcheck.php';

$shopObj = new Shop();
$shop_id = $_SESSION['shop_id'];

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

<!doctype html>
<html lang="en">

<head>
    <?php 
    require_once '../View/head.php';
    require_once '../View/loader.php';
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
    $feature_id=38;
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
        ?>
        <div class="container-fluid">
           
            <div class="container-fluid">
                <?php 
                $shop = $shopObj->getOneShop($shop_id);
                ?>

                <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
                <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?> ">
                <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
                <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
                <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?> ">
                <input type="hidden" name="" id="title" value="Item Sales Report">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Item Sales Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="container">
                            <form action="" method="get" class="form-inline">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="" class="form-label">Start Date</label>
                                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="" class="form-label">End Date</label>
                                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>" class="form-control">
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
                                    
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-success mt-4" name="btn_item_sales" id="btn_item_sales">Find</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <P class="text-danger" style="margin: 10px 0;">Item sales report does not include invoice discount.</P>

                        <table id="tbl_inventory_summary">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Invoice No</th>
                                    <th>Invoice Date</th>
                                    <th>Barcode</th>
                                    <th>Item Name</th>
                                    <th>Sold Qty</th>
                                    <th>Discount Type</th>
                                    <th>Item Discount</th>
                                    <th>Total Item Amount</th>
                                    <th>Invoice Status</th>
                                    <th>Cashier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $dbObj = new DBTransactions();
                                $add = "";
                                    if (isset($_GET["claim"])) {
                                        $add = " OR invoiceheader.InvStat=6";
                                    }

                                $sql = "SELECT *, invoicedetails.PercentDiscount AS ItemDisc FROM invoicedetails
                                INNER JOIN invoiceheader ON invoiceheader.IHID = invoicedetails.InvoiceHeader_IHID
                                INNER JOIN user ON user.USID = invoiceheader.user_USID
                                INNER JOIN products ON products.PDID = invoicedetails.products_PDID
                                WHERE invoiceheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND invoiceheader.shop_SHID = ".$shop_id."  AND invoiceheader.InvStat=1 $add ORDER BY IHID DESC;";

                                $row_count = 0;
                                $sold_qty_total = 0;
                                $item_discount_total = 0;
                                $total_amount = 0;

                                $invData = $dbObj->getData($sql);
                                foreach($invData as $row)
                                {
                                    $row_count += 1;
                                    $invoice_id = $row['IHID'];
                                    $InvStat = $row['InvStat'];
                                    $invoice_date = $row['InvEndTime'];
                                    $invoice_no = $row['BillNo'];
                                    $item_count = $row['InvItemCount'];
                                    $gross_amount = $row['GrossAmount'];
                                    $invoice_discount = $row['DiscountAmount'];
                                    $net_amount = $row['NetAmount'];
                                    $user_name = $row['UserName'];
                                    $barcode = $row['Barcode'];
                                    $item_name = $row['ItemName'];
                                    $sold_qty = $row['SellQty'];  
                                    $disc_type = $row['disc_type'];
                                    $PercentDiscount = $row['ItemDisc'];
                                    $SellDiscount = $row['SellDiscount'];
                                    $sell_amount = $row['SoldAmount'];  
                                    $UnitPrice = $row['UnitPrice'];  

                                    
                                    if($disc_type==1)
                                    {
                                        $tot=$UnitPrice*$sold_qty;
                                        $item_discount=($tot * $PercentDiscount)/100;
                                    }
                                    else
                                    {
                                        $item_discount=$SellDiscount*$sold_qty ;
                                    }
                                    // Accumulate totals
                                    $sold_qty_total += $sold_qty;
                                    $item_discount_total += $item_discount;
                                    $total_amount += $sell_amount;

                                // Get transactions
                                    $sql_1 = "SELECT * FROM `transactions` 
                                    INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
                                    WHERE InvoiceHeader_IHID = ".$invoice_id.";";

                                    $transData = $dbObj->getData($sql_1);

                                    ?>
                                    <tr>
                                        <td><?php echo $row_count;?></td>
                                       
                                        <td><?php echo $invoice_no;?></td>
                                        <td><?php echo $invoice_date;?></td>
                                        <td><?php echo $barcode;?></td>
                                        <td><?php echo $item_name;?></td>
                                        <td><?php echo $sold_qty;?></td>
                                        <td><?php 
                                        if($disc_type==1)
                                        {
                                            echo "Percentage";
                                            $item_discount=$PercentDiscount;
                                        }
                                        else
                                        {
                                            echo "Flat Rate";
                                            $item_discount=$SellDiscount."<br> (Per Unit)";
                                        }
                                        ?></td>
                                        <td><?php echo $item_discount;?></td>
                                        <td><?php echo $sell_amount;?></td>
                                        <td><?php 
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
                                        <td><?php echo $user_name;?></td>
                                    </tr>
                                    <?php
                                    
                                }//foreach 1
                                ?>
                            </tbody>

                            <!-- footer -->
                           <!-- footer -->
                            <tfoot>
                            <tr>
                            <th style="text-align:right">Total:</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th><?php echo number_format($sold_qty_total, 2); ?></th>
                            <th></th>
                            <th><?php echo number_format($item_discount_total, 2); ?></th>
                            <th><?php echo number_format($total_amount, 2); ?></th>
                            <th></th>
                            <th></th>
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
<?php require_once '../View/footer.php';?>

    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>

    <script>
      $(document).ready(function(){
    var shop_name = $("#shop_name").val();
    var shop_address_one = $('#shop_address_one').val();
    var shop_address_two = $('#shop_address_two').val();
    var shop_city = $('#shop_city').val();
    var shop_number = $('#shop_number').val();
    var reportname = $("#title").val();
    var $table = $("#tbl_inventory_summary");

    var headerCount = $table.find('thead th').length;
    var footerCount = $table.find('tfoot th').length;
    if (footerCount > 0 && headerCount !== footerCount) {
        console.error('DataTable init aborted: header/footer column mismatch', {
            headerCount: headerCount,
            footerCount: footerCount
        });
        return;
    }

    if ($.fn.dataTable.isDataTable($table)) {
        $table.DataTable().destroy();
    }

    $table.DataTable({
        paging: true,                // Enables pagination
        lengthChange: true,          // Allows the user to change the number of records per page
        searching: true,             // Enables search functionality
        pageLength: 10,              // Sets default number of rows per page (you can adjust this)
        dom: 'Bfrtip',               // Specifies the position of buttons
        buttons: [
            {
                extend: 'excelHtml5',
                title: 'Item Wise Sales Report',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                title: 'Item Wise Sales Report',
                exportOptions: {
                    columns: ':visible'
                },
                customize: function (doc){
                    doc.content.splice(0,1,{
                        text: [
                            {text: shop_name + "\n", bold: true, fontSize: 16},
                            {text: shop_address_one + ",\n", bold: true, fontSize: 12},
                            {text: shop_address_two + ",\n", bold: true, fontSize: 12},
                            {text: shop_number + ",\n", bold: true, fontSize: 12},
                            {text: reportname + ",\n", bold: true, fontSize: 14},
                        ]
                    }) 
                },
                download: 'open'
            },
            {
                extend: 'print',
                title: 'Item Wise Sales Report',
                exportOptions: {
                    columns: ':visible'
                },
                customize: function(win){
                    var formattedCity = shop_city.charAt(0).toUpperCase() + shop_city.slice(1).toLowerCase(); 
                    $(win.document.body).prepend(
                        '<div style="text-align:center;">' +
                            '<h4>' + shop_name + '</h4>' +
                            '<h5>' + shop_address_one + ' ' + shop_address_two + ', ' + formattedCity + '</h5>' +
                            '<h6>' + shop_number + '</h6>' +
                            '<h3>' + reportname + '</h3>' +
                        '</div>'
                    );
                    $(win.document.body).find('table').css('font-size', '12px');
                    $(win.document.body).find('table').css('border-collapse', 'collapse');
                    $(win.document.body).find('th').css('text-align', 'center');
                    $(win.document.body).find('th, td').css('padding', '5px');
                },
                download: 'open'
            }
        ],
        "order": [[ 0, 'desc' ]],
    });
});
    </script>
</body>
</html>
