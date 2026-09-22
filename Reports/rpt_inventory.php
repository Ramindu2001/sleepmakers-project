<?php 
// require_once '../Includes/includes.php';

include "../Includes/includes.php";
require_once '../Includes/authcheck.php';

$dbObj = new DBTransactions();
$shopObj = new Shop();
$shop_id = $_SESSION['shop_id'];

$category_id = 0;
$subcategory_id = 0;

if(isset($_GET['cat']))
 {
   $cat = explode("_", $_GET['cat']);
   $category_id = $cat[0];
   $subcategory_id = $cat[1];
 }//date set
 else
 {
   $category_id = 0;
   $subcategory_id = 0;
 }//date not set

?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  require_once '../View/head.php';
  require_once '../View/loader.php';
  ?>

  <link rel="stylesheet" href="../Assets/css/datatables.min.css">
  <script src="../Assets/js/datatables.min.js"></script>

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
                <input type="hidden" name="" id="title" value="Inventory Report">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Inventory Report</h5>
                        <?php 
                        //  echo "cat - " .$category_id. "<br>";
                        //  echo "subcat - " .$subcategory_id. "<br>";
                        ?>
                    </div>
                    <div class="card-body">
                        <div class="container">
                            <form action="../Controller/ReportController.php" method="POST" class="form-inline">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="" class="form-label">Category</label>
                                        <select name="cmb_category" id="cmb_category" class="form-select">
                                        <option value="0">=== Select Category ===</option>
                                        <?php
                                        $sql = "SELECT * FROM `categories` WHERE shop_SHID = ".$shop_id.";";
                                        $catData = $dbObj->getData($sql);
                                        foreach($catData as $row)
                                        {
                                            $is_select = $category_id == $row['CTID'] ? "selected" : "";
                                            ?>
                                            <option value="<?php echo $row['CTID'];?>" <?php echo $is_select;?>><?php echo $row['CategoryName'];?></option>
                                            <?php 
                                        }//foreach

                                        ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="" class="form-label">Subcategory</label>
                                        <select name="cmb_subcategory" id="cmb_subcategory" class="form-select">
                                            <option value="0">=== Select Subcategory ===</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-success mt-4" name="btn_category_search" id="btn_category_search">Find</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <table id="tbl_inventory_summary">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Batch</th>
                                    <th>Item</th>
                                    <th>Current Qty</th>
                                    <th>Unit</th>
                                    <th>Purchase Price</th>
                                    <th>Selling Price</th>
                                    <th>Stock Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 

                                if($shopObj->hasMinus($shop_id))
                                {
                                    if($category_id != 0 AND $subcategory_id == 0)
                                    {
                                        $sql = "SELECT products.Barcode, products.ItemName, inventory.CurrentQty, units.ShortName, PurchasePrice, SellingPrice, pricehistory.BatchID, 
                                        (inventory.CurrentQty * PurchasePrice) AS StockValue FROM `pricehistory`
                                        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                        INNER JOIN products ON products.PDID = pricehistory.ProductID
                                        INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
                                        INNER JOIN units ON units.UNID = products.PurchaseUnit
                                        WHERE inventory.shop_SHID = ".$shop_id." AND products.ItemType='P' AND subcategories.categories_CTID = ".$category_id." ORDER BY ProductID;";
                                    }//category only
                                    elseif($category_id != 0 AND $subcategory_id != 0)
                                    {
                                        $sql = "SELECT products.Barcode, products.ItemName, inventory.CurrentQty, units.ShortName, PurchasePrice, SellingPrice, pricehistory.BatchID, 
                                        (inventory.CurrentQty * PurchasePrice) AS StockValue FROM `pricehistory`
                                        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                        INNER JOIN products ON products.PDID = pricehistory.ProductID
                                        INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
                                        INNER JOIN units ON units.UNID = products.PurchaseUnit
                                        WHERE inventory.shop_SHID = ".$shop_id." AND products.ItemType='P' AND subcategories.SCID = ".$subcategory_id." ORDER BY ProductID;";
                                    }//category & subcategory
                                    else
                                    {
                                        $sql = "SELECT 
                                        products.Barcode, 
                                        products.ItemName, 
                                        inventory.CurrentQty, 
                                        units.ShortName, 
                                        PurchasePrice, 
                                        SellingPrice,
                                        pricehistory.BatchID, 
                                        (inventory.CurrentQty * PurchasePrice) AS StockValue FROM `pricehistory`
                                        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                        INNER JOIN products ON products.PDID = pricehistory.ProductID
                                        INNER JOIN units ON units.UNID = products.PurchaseUnit
                                        WHERE inventory.shop_SHID = ".$shop_id." AND products.ItemType='P' ORDER BY ProductID;";
                                    }//no categories
                                }//has minus
                                else
                                {
                                    if($category_id != 0 AND $subcategory_id == 0)
                                    {
                                        $sql = "SELECT products.Barcode, products.ItemName, inventory.CurrentQty, units.ShortName, PurchasePrice, SellingPrice, pricehistory.BatchID, 
                                        (inventory.CurrentQty * PurchasePrice) AS StockValue FROM `pricehistory`
                                        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                        INNER JOIN products ON products.PDID = pricehistory.ProductID
                                        INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
                                        INNER JOIN units ON units.UNID = products.PurchaseUnit
                                        WHERE inventory.shop_SHID = ".$shop_id." AND products.ItemType='P' AND subcategories.categories_CTID = ".$category_id." AND inventory.CurrentQty>0 ORDER BY ProductID;";
                                    }//category only
                                    elseif($category_id != 0 AND $subcategory_id != 0)
                                    {
                                        $sql = "SELECT products.Barcode, products.ItemName, inventory.CurrentQty, units.ShortName, PurchasePrice, SellingPrice, pricehistory.BatchID, 
                                        (inventory.CurrentQty * PurchasePrice) AS StockValue FROM `pricehistory`
                                        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                        INNER JOIN products ON products.PDID = pricehistory.ProductID
                                        INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
                                        INNER JOIN units ON units.UNID = products.PurchaseUnit
                                        WHERE inventory.shop_SHID = ".$shop_id." AND products.ItemType='P' AND subcategories.categories_CTID = ".$category_id." AND subcategories.SCID = ".$subcategory_id." AND inventory.CurrentQty>0 ORDER BY ProductID;";
                                    }//category & subcategory
                                    else
                                    {
                                        $sql = "SELECT 
                                        products.Barcode, 
                                        products.ItemName, 
                                        inventory.CurrentQty, 
                                        units.ShortName, 
                                        PurchasePrice, 
                                        SellingPrice, 
                                        pricehistory.BatchID,
                                        (inventory.CurrentQty * PurchasePrice) AS StockValue FROM `pricehistory`
                                        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                        INNER JOIN products ON products.PDID = pricehistory.ProductID
                                        INNER JOIN units ON units.UNID = products.PurchaseUnit
                                        WHERE inventory.shop_SHID = ".$shop_id." AND products.ItemType='P' AND inventory.CurrentQty>0 ORDER BY ProductID;";
                                    }//no category or subcategory but qty > 0
                                    
                                }//else

                                // echo "sql = " . $sql . "<br>";

                                $row_count = 0;
                                $invData = $dbObj->getData($sql);
                                foreach($invData as $row)
                                {
                                    $row_count += 1;
                                    $batch_no = $row['BatchID'];
                                    $barcode = $row['Barcode'];
                                    $item_name = $row['ItemName'];
                                    $current_qty = $row['CurrentQty'];
                                    $unit_name = $row['ShortName'];
                                    $purchase_price = $row['PurchasePrice'];
                                    $selling_price = $row['SellingPrice'];
                                    $stock_value = number_format((float)$row['StockValue'], 2, '.', '');

                                    ?>
                                    <tr>
                                        <td><?php echo $row_count;?></td>
                                        <td><?php echo $batch_no;?></td>
                                        <td><b><?php echo $barcode ."<br>". $item_name;?></b></td>
                                        <td style="text-align: right;"><?php echo $current_qty;?></td>
                                        <td style="text-align: right;"><?php echo $unit_name;?></td>
                                        <td style="text-align: right;"><?php echo $purchase_price;?></td>
                                        <td style="text-align: right;"><?php echo $selling_price;?></td>
                                        <td style="text-align: right;"><?php echo $stock_value;?></td>
                                    </tr>
                                    <?php 
                                }//foreach 1
                                ?>
                            </tbody>

                            <!-- footer -->
                             <tfoot>
                                <tr>
                                    <th colspan="7" style="text-align:right">Total:</th>
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
            var reportname=$("#title").val();

            $("#tbl_inventory_summary").DataTable({
               
                paging: true,
                lengthChange: true,
                searching: true,
                //pageLength: 10,

                layout:{
                    topStart:{
                        buttons:[
                            //====================== PDF
                            {
                                extend: 'pdf',
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
                            //===================== print
                            {
                                extend: 'print',
                                title: '',
                                customize: function(win){
                                    var formattedCity = shop_city.charAt(0).toUpperCase() + shop_city.slice(1).toLowerCase();
                    
                                    $(win.document.body).css('font-size', '10pt').prepend(
                                    '<div style="text-align: center; margin-bottom: 20px;">' +
                                    '<p style="margin: 5px; font-size: 24px; font-weight: bold;">' + shop_name + '</p>' +
                                    '<p style="margin-bottom: 5px;">' + shop_address_one + ', ' + shop_address_two + '</p>' +
                                    '<p style="margin-bottom: 5px;">' + formattedCity + '</p>' +  // Reduced margin
                                    '<p style="margin-bottom: 5px;">' + shop_number + '</p>' +  // Reduced margin
                                    '<p style="margin: 5px; font-size: 18px; text-align:center;">'+ reportname +'</p>' + //report name
                                    '</div>'
                                    );
                                }
                            }, 
                            //===================== excel
                            {
                                extend: 'excel',
                                title: reportname,
                            },
                            //====================== csv
                            {
                                extend: 'csv',
                                title: reportname,
                            }
                        ]
                    } 
                },//layouts
                
                //get total
                footerCallback: function (row, data, start, end, display) {
                    let api = this.api();
            
                    // Remove the formatting to get integer data for summation
                    let intVal = function (i) {
                        return typeof i === 'string'
                            ? i.replace(/[\$,]/g, '') * 1
                            : typeof i === 'number'
                            ? i
                            : 0;
                    };
            
                    // Total over all pages
                    total = api
                        .column(7)
                        .data()
                        .reduce((a, b) => intVal(a) + intVal(b), 0);

                    total = total.toFixed(2);
            
                    // Total over this page
                    pageTotal = api
                        .column(7, { page: 'current' })
                        .data()
                        .reduce((a, b) => intVal(a) + intVal(b), 0);
            
                    // Update footer
                    api.column(7).footer().innerHTML = pageTotal + '(' + total + ')';
                }
            }); 

            //=========== Search by Category =============//
            $("#cmb_category").change(function(){
                var category_id = $(this).val();
                $.get("../AJAX/AjaxCategory/getSubcategory.php", {
                    category_id: category_id
                }, function(data){
                    // alert(data);
                    $("#cmb_subcategory").html(data);
                    $("#cmb_subcategory").focus();
                });//get subcategory
            });//cmb changed
        });//jquery
    </script>
</body>
</html>
