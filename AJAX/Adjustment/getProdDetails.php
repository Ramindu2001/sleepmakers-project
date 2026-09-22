<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/inventory_class.php";

$shop_id = $_SESSION['shop_id'];
$dbObj = new DBTransactions();
if(isset($_POST["product_id"]))
{
    $product_id = $_POST['product_id'];
    $sql = "SELECT v.*,p.ItemName AS Product_name FROM `variations` v 
    INNER JOIN products p ON p.PDID=v.products_PDID
    WHERE v.products_PDID='$product_id';";
    
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
                WHERE i.products_PDID='$product_id'";
        
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

if(isset($_POST["PHID"]))
{
    $PHID=$_POST["PHID"];
    $sql = "SELECT * FROM pricehistory ph 
    INNER JOIN inventory i ON i.INID=ph.Inventory_INID
    WHERE ph.PHID='$PHID';";
    
    $itemData = $dbObj->getData($sql);
    
    $itemResult = array();
    if(!empty($itemData))
        {
            foreach($itemData as $row)
            {
                $data['id'] = $row['PHID'];
                $data['CurrentQty'] = $row['CurrentQty'];
                $data['PurchasePrice'] = $row['PurchasePrice'];
                $data['SellingPrice'] = $row['SellingPrice'];
                $data['MnfDate'] = $row['MnfDate'];
                $data['ExpDate'] = $row['ExpDate'];

                array_push($itemResult, $data);
            }//foreach
        }//has items

        echo json_encode($itemResult);
}