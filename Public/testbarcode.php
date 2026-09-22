<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
$invoice_id = 0;
$shop_id = 0;
$user_id = $_SESSION['user_id'];

if(isset($_SESSION['shop_id']))
{
    $shop_id = $_SESSION['shop_id'];
}//assign shop id

if(isset($_GET['print_barcode']))
{
    $barcode = $_GET['print_barcode'];
}//assign invoice id

$dbObj = new DBTransactions();
?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <style>
        td 
        {
            padding: 1.5mm !important;
        }
        canvas
        {
            width: 90%;
        }
        td
        {
            width: 150px;
            height: 94px;
        }
        table
        {
            width: 300px;
        }
    </style>
</head>

<body>
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
            <?php 
            include '../View/sidebar.php';
            ?>
            <div class="body-wrapper">
                <?php 
                include '../View/header.php';
                ?>
                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Print Barcode : -<?=$barcode?></h5>
                    <div class="card">
                        <div class="card-body">
                            <form action="" method="get">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="hidden" name="print_barcode" id="print_barcode" value="<?=$barcode?>">
                                        <label for="" class="form-label">Row</label>
                                        <input type="number" name="qty" id="qty" class="form-control" min="1" value="<?php echo (isset($_GET["qty"])?$_GET["qty"]:"1");?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="" class="form-label">Column</label>
                                        <input type="number" name="cqty" id="cqty" class="form-control" min="1" value="<?php echo (isset($_GET["cqty"])?$_GET["cqty"]:"1");?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="" class="form-label">Select Print</label>
                                        <select name="printsize" id="" class="form-control">
                                            <option <?php if(isset($_GET["printsize"])){if($_GET["printsize"]==1){echo"selected";}}?> value="1">150mm x 100mm</option>
                                            <option <?php if(isset($_GET["printsize"])){if($_GET["printsize"]==2){echo"selected";}}?> value="2">100mm x 75mm</option>
                                            <option <?php if(isset($_GET["printsize"])){if($_GET["printsize"]==3){echo"selected";}}?> value="3">75mm x 50mm</option>
                                            <option <?php if(isset($_GET["printsize"])){if($_GET["printsize"]==4){echo"selected";}}?> value="4">50mm x 25mm</option>
                                            <option <?php if(isset($_GET["printsize"])){if($_GET["printsize"]==5){echo"selected";}}?> value="5">38mm x 25mm</option>
                                            <option <?php if(isset($_GET["printsize"])){if($_GET["printsize"]==6){echo"selected";}}?> value="6">34mm x 25mm</option>
                                            <option <?php if(isset($_GET["printsize"])){if($_GET["printsize"]==7){echo"selected";}}?> value="7">30mm x 20mm</option>
                                            <option <?php if(isset($_GET["printsize"])){if($_GET["printsize"]==8){echo"selected";}}?> value="8">30mm x 15mm</option>
                                            <option <?php if(isset($_GET["printsize"])){if($_GET["printsize"]==9){echo"selected";}}?> value="9">25mm x 15mm</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 p-4">
                                        <input type="submit" value="Submit" class="btn btn-primary">
                                    </div>
                                </div>
                                <hr>
                            </form>
                            <div class="col-md-6">
                                <button class="btn btn-primary" id="printButton">Print Barcode</button>
                                <div id="printing">
                                    <script>
                                        $(document).ready(function() {
                                            var value = $("#return").val();
                                            if (value) 
                                            {
                                                JsBarcode("#barcode", value, {
                                                    format: "CODE128",
                                                    lineColor: "#000",
                                                    width: 2,
                                                    height: 50,
                                                    displayValue: true
                                                });
                                            } else {
                                                alert("Please enter a value for the barcode.");
                                            };
                                            $('#printButton').click(function(){
                                                var printContents = $('#printing').html();
                                                var originalContents = $('body').html();
                                                var value = $("#return").val();
                                                if (value) 
                                                {
                                                    JsBarcode("#barcode", value, {
                                                        format: "CODE128",
                                                        lineColor: "#000",
                                                        width: 2,
                                                        height: 50,
                                                        displayValue: true
                                                    });
                                                } else {
                                                    alert("Please enter a value for the barcode.");
                                                };
                                                $('body').html(printContents);
                                                setInterval(function() {
                                                    window.print();
                                                    $('body').html(originalContents);
                                                    
                                                }, 1000);
                                            });
                                        });
                                    </script>
                                    <input type="hidden" name="" value="<?=$barcode?>" id="return">
                                   <table>
                                        <?php 
                                            $rows = isset($_GET["qty"])?$_GET["qty"]:"1";
                                            $cols = isset($_GET["cqty"])?$_GET["cqty"]:"1";
                                            for ($i = 0; $i < $rows; $i++) {
                                                echo '<tr>';
                                                for ($j = 0; $j < $cols; $j++) {
                                                    echo '<td><canvas id="barcode"></canvas></td>';
                                                }
                                                echo '</tr>';
                                            }
                                            ?>
                                            <script>
                                                $(document).ready(function(){
                                                    var value = $("#return").val();
                                                    if (value) 
                                                    {
                                                        JsBarcode("#barcode", value, {
                                                            format: "CODE128",
                                                            lineColor: "#000",
                                                            width: 2,
                                                            height: 50,
                                                            displayValue: true
                                                        });
                                                    } else {
                                                        alert("Please enter a value for the barcode.");
                                                    };
                                                })
                                            </script>
                                    </table> 
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // window.print();
        setInterval(function(){
            // window.location="../Public/sales-return.php";
        }, 1000);
    </script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>

</html>