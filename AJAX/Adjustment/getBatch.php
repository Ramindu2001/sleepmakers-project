<?php 

session_start();
include '../../Includes/config.php';
include '../../Model/pricechange_class.php';
include '../../Model/user_class.php';
include '../../Includes/authcheck.php';
include "../../Model/DB_Class.php";

$Pricechange= new Pricechange();


    $product_id=$_POST["product_id"];
    $varriation_id=$_POST["varriation_id"];
    $sql = "SELECT i.*,i.BatchID AS batch, ph.SellingPrice AS rate, ph.PHID AS pricehistoryID  FROM `inventory` i 
            INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
            WHERE i.products_PDID='$product_id' AND ph.VariationID='$varriation_id' AND i.CurrentQty > 0";
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);
    if(count($itemData)==0)
    {
        echo "";
    }
    else
    {
        ?>
        <option value="">Select Batch</option>
        <?php
        foreach ($itemData as $row) 
        {
            ?>
            <option value="<?=$row["pricehistoryID"]?>"><?=$row["batch"]?> - <?=$row["rate"]?></option>
            <?php
        }
    }

?>