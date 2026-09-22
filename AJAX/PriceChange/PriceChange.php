<?php 

session_start();
include '../../Includes/config.php';
include '../../Model/pricechange_class.php';
include '../../Model/user_class.php';
include '../../Includes/authcheck.php';
include "../../Model/DB_Class.php";

$Pricechange= new Pricechange();

if(isset($_POST["items"]))
{
    $item=$_POST["items"];

    $sql = "SELECT v.*,p.ItemName AS Product_name FROM `variations` v 
    INNER JOIN products p ON p.PDID=v.products_PDID
    WHERE v.products_PDID='$item';";
    
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);
    $itemResult = array();

    if(!empty($itemData))
    {
        $type="varriant";
        $data['id'] = "";
        $data['type'] = "varriant";
        $data['text'] = "Select Varriant";
        array_push($itemResult, $data);
        foreach($itemData as $row)
        {
            $data['id'] = $row['VRID'];
            $data['type'] = "varriant";
            $data['text'] = $row['VariationName'] ." - ". $row['Product_name'];

            array_push($itemResult, $data);
        }//foreach

    }//has items
    else
    {
        $type="Batch";

        $sql = "SELECT i.*,i.BatchID AS batch, ph.SellingPrice AS rate, ph.PHID  FROM `inventory` i 
                INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
                WHERE i.products_PDID='$item' AND i.CurrentQty > 0";
        
        $dbObj = new DBTransactions();
        $itemData = $dbObj->getData($sql);
        $data['id'] = "";
        $data['type'] = "batch";
        $data['text'] = "Select Batch";
        array_push($itemResult, $data);
        foreach($itemData as $row)
        {
            $data['id'] = $row['PHID'];
            $data['type'] = "batch";
            $data['text'] = $row['batch'] ." - ". $row['rate'];

            array_push($itemResult, $data);
        }//foreach

        
    }
    echo json_encode($itemResult);
}
elseif (isset($_POST["product_id"])) 
{
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
}
elseif(isset($_POST["PHID"]))
{
    $id=$_POST["PHID"];
    $sql = "SELECT ph.*,i.BatchID AS batch FROM `pricehistory`ph 
    INNER JOIN inventory i ON i.INID=ph.Inventory_INID
    WHERE ph.PHID='$id';";
    
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);
    $itemResult = array();

    if(!empty($itemData))
    {
        foreach($itemData as $row)
        {
            $data['labelPrice'] = $row['labelPrice'];
            $data['SellingPrice'] = $row['SellingPrice'];
            $data['batch'] = $row['batch'];

            array_push($itemResult, $data);
        }//foreach

    }
    echo json_encode($itemResult);
}
?>