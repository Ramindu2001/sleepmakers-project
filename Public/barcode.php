
<!doctype html>
<html lang="en">

<head>
    <?php include '../View/head.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <style>
        @media print{
            .barcode
            {
                width: 100%;
            }
            .form-control
            {
                display: none;
            }
        }
        .page-break {
                page-break-after: always;    
                margin: 0;
                padding: 0;
        }
    </style>
<body>
<form action="" method="get">
    <input type="hidden" name="print_barcode" id="return" value="<?=$_GET["print_barcode"]?>">
    <input type="text" name="amount" id="" class="form-control">
</form>
<?php 
if(isset($_GET["amount"]))
{
    $amount=$_GET["amount"];
}
else
{
    $amount=1;
}


for ($i=0; $i <$amount ; $i++) 
{ 
    ?>
    <canvas class="barcode"></canvas>
    <div class="page-break"></div>
    <?php
}
?>

    <script>
        $(document).ready(function() {
            var value = $("#return").val();
            if (value) {
                $(".barcode").each(function() {
                    JsBarcode(this, value, {
                        format: "CODE128",
                        lineColor: "#000",
                        width: 1,
                        height: 25,
                        displayValue: true
                    });
                });
            } else {
                alert("Please enter a value for the barcode.");
            }
            <?php 
            if(isset($_GET["amount"]))
            {
                ?>
                window.print();
                <?php
            }
            ?>
            
        });
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
