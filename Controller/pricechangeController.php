<?php 

include "../Includes/includes.php";
include '../Includes/authcheck.php';
$Pricechange=new Pricechange();
$dbObj = new DBTransactions();
$user=$_SESSION["user_id"];
$shop_id = $_SESSION['shop_id'];

if(isset($_POST["btn_save_product"]))
{
    $product_id=$_POST["product_id"];
    if(isset($_POST["varriation_id"]))
    {
        $varriation_id=$_POST["varriation_id"];
    }
    else
    {
        $varriation_id="";
    }
    $price_history_id=$_POST["batch_id"];
    $batch=$_POST["batch"];
    $old_selling_price=$_POST["old_selling_price"];
    $new_selling_price=$_POST["new_selling_price"];
    if(isset($_POST["new_label_price"]))
    {
        $new_label_price=$_POST["new_label_price"];
    }
    else
    {
        $new_label_price=$_POST["old_label_price"];
    }
    $old_label_price=$_POST["old_label_price"];
    $user=$_SESSION["user_id"];
    $shop_id = $_SESSION['shop_id'];
    $insert=$Pricechange->insert_price_change($product_id,$varriation_id,$batch,$old_selling_price,$old_label_price,$new_selling_price,$new_label_price,$user,$price_history_id,$shop_id);
    echo "Data Inserted";

    $update=$Pricechange->update_price_history($new_selling_price,$new_label_price,$price_history_id);
    echo "Data Updated";

    //the product record shows the new price too (POS product tiles, product list), as the bulk price change does
    $prodObj = new Product();
    $prodObj->setCurrentPrices($product_id, 0, $new_selling_price);

    header("Location: ../Public/price-change.php");



}
elseif(isset($_POST["bulkPriceChange"]))
{
    if(isset($_POST["price"]) && count($_POST["prodItem"]) > 0)
    {
        for ($i=0; $i <count($_POST["prodItem"]) ; $i++) 
        { 
            $proID=$_POST["prodItem"][$i];
            $price=$_POST["price"];
            $sql="SELECT * FROM pricehistory WHERE ProductID='$proID'";
            $pricehistory=$dbObj->getData($sql);
            foreach($pricehistory AS $row)
            {
                $varriation_id=$row["VariationID"];
                $batch=$row["BatchID"];
                $old_selling_price=$row["SellingPrice"];
                $old_label_price=$row["labelPrice"];
                $new_label_price=$row["labelPrice"];
                $price_history_id=$row["PHID"];
                $insert=$Pricechange->insert_price_change($proID,$varriation_id,$batch,$old_selling_price,$old_label_price,$price,$new_label_price,$user,$price_history_id,$shop_id);
            }
            $sql="UPDATE pricehistory SET SellingPrice='$price' WHERE ProductID='$proID'";
            $update=$dbObj->executeTransaction($sql);
            $sql="UPDATE products SET ProdSellPrice='$price' WHERE PDID='$proID'";
            $update=$dbObj->executeTransaction($sql);
            $_SESSION["price_update"]=1;
            header("Location: ../Public/bulk-price-change.php");

        }
    }
    else
    {
        $_SESSION["price_update"]=0;
        header("Location: ../Public/bulk-price-change.php");
    }
}
else
{
    header("Location: ../Public/price-change.php");
}
?>