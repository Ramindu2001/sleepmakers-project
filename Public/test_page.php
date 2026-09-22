<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php 
    include "../View/head.php";
    ?>
    <title>Document</title>

    <link rel="stylesheet" href="../Assets/css/datatables.min.css">
    <script src="../Assets/js/datatables.min.js"></script>

</head>
<body>
    <div class="card">
        <div class="card-header">
            <h5>Test Page </h5>
        </div>
        
        <div class="card-body">
            
            <table id="tbl_product">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Barcode</th>
                    <th>Item name</th>
                    <th>cost</th>
                    <th>price</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php 
                $dbObj = new DBTransactions();

                $sql = "SELECT * FROM `products` WHERE shop_SHID = ".$shop_id.";";

                $prodData = $dbObj->getData($sql);

                foreach($prodData as $row)
                {
                    $product_id = $row['PDID'];
                    $barcode = $row['Barcode'];
                    $prod_name = $row['ItemName'];
                    $purchase_price = $row['ProdPurchasePrice'];
                    $selling_price = $row['ProdSellPrice'];

                    ?>
                    
                        <tr data-id="<?php echo $product_id;?>">
                            <td><?php echo $product_id;?></td>
                            <td><?php echo $barcode;?></td>
                            <td><?php echo $prod_name;?></td>
                            <td><?php echo $purchase_price;?></td>
                            <td><?php echo $selling_price;?></td>
                            <td>
                                <button class="btn_click">Run</button>
                            </td>
                        </tr>
                    
                    <?php 

                }//foreach  
                ?>
                </tbody>
            </table>
           

        </div>
    </div>


<script>
$(document).ready(function(){

    $("#tbl_product").DataTable({
        paging: true,
        lengthChange: true,
        searching: true,

        // columnDefs: [
        //     {
        //         data: null,
        //         defaultContent: '<button>Click!</button>',
        //         targets: -1
        //     }
        // ]
    });

    $("#tbl_product").on('click', '.btn_click', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');
        alert("Clicked... " + id);
    });
});//jQuery
</script>
</body>
</html>