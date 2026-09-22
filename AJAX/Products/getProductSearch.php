<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";
include "../../Model/user_class.php";

$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];
$txt_input = $_GET['txt_input'];

$dbObj = new DBTransactions();
$shopObj = new Shop();
$userObj = new User();

$sql = "SELECT * FROM user WHERE USID = ".$user_id.";";

//get user type
$userData = $dbObj->getData($sql);
$userType = $userData[0]['UserType'];

$feature_id = 16; //product feature
//get user role access
$userData = $userObj->getUserFeatureAccess($user_id,$feature_id,$shop_id); //role held in this shop
if(empty($userData))
{
    $create = 0;
    $print = 0;
}
else
{
    $create = floatval($userData[0]['is_create']);
    //printing labels is its own right - the tick boxes follow it, not is_create
    $print = isset($userData[0]['is_print']) ? floatval($userData[0]['is_print']) : 0;
}


//get company stat
$sql = "SELECT * FROM shop
INNER JOIN company ON company.CMID = shop.Company_CMID
WHERE SHID = ".$shop_id.";";

$shopData = $dbObj->getData($sql);
$multi_category = floatval($shopData[0]['is_multicategory']);
$company_id = floatval($shopData[0]['CMID']);

if($multi_category == 1)
{
    $sql_1 = "SELECT *,categories.CTID AS cat_ID FROM products 
    INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    INNER JOIN shop ON shop.SHID = products.shop_SHID
    WHERE concat(Barcode, ItemName) LIKE '%".$txt_input."%' AND shop.Company_CMID = ".$company_id." LIMIT 50;";
}//has multi category
else
{
    $sql_1 = "SELECT *,categories.CTID AS cat_ID FROM products 
    INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    WHERE concat(Barcode, ItemName) LIKE '%".$txt_input."%' AND products.shop_SHID = ".$shop_id." LIMIT 50;";
}//mo multi category

$prodData = $dbObj->getData($sql_1);

echo "<tr>";
echo "<th style='width:34px;'>";
if($userType==1 || $print==1)
{
    echo "<input type='checkbox' class='form-check-input' id='bc_select_all' title='Select all results'>";
}//may print
echo "</th>";
echo "<th>Product No</th>";
echo "<th>Category</th>";
echo "<th>Subcategory</th>";
echo "<th>Item</th>";
echo "<th>Image</th>";
//no inventory
if($shopObj->hasInventory($shop_id) == 0)
{
    echo "<th>Purchase Price</th>";
    echo "<th>Selling Price</th>";
}//no inventory
echo "<th>Type</th>";
echo "<th>Action</th>";
echo "<th>Barcode</th>";
echo "</tr>";

foreach($prodData as $row)
{
    $barcode = $row['Barcode'];
    echo "<tr data-id='".$row['PDID']."'>";
    echo "<td>";
    if($userType==1 || $print==1)
    {
        echo "<input type='checkbox' class='form-check-input bc-select' title='Select for barcode printing'>";
    }//may print
    echo "</td>";
    echo "<td>".$row['ProductNo']."</td>";
    echo "<td>".$row['CategoryName']."</td>";
    echo "<td>".$row['SubCatName']."</td>";

    echo "<td><b>";
    echo  $row['Barcode']." <br> ".$row['ItemName'];
    if($shopObj->hasSecondLanguage($shop_id))
    {
        echo "<br>" . $row['SecondName'];
    }//has second language
    echo "</b></td>";

    echo "<td>";
    //check image
    if(isset($row['ProdImage']))
    {
        echo "<img src='../Assets/Images/prod_images/".$row['ProdImage']."' style='width:auto; height:50px;'>";
    }//has image
    else
    {   
        echo "<img src='../Assets/Images/icons/product.png' style='width:auto; height:50px;'>"; 
    }//default image
    echo "</td>";

    //no inventory
    if($shopObj->hasInventory($shop_id) == 0)
    {
        echo "<td>".$row['ProdPurchasePrice']."</td>";
        echo "<td>".$row['ProdSellPrice']."</td>";
    }//no inventory

    echo "<td>";
    //check product or service
    if($row['ItemType'] == 'P')
    {
        echo "<p class='text-success' ><i class='ti ti-box'></i><b> Product</b></p>";
    }//product
    else
    {
        echo "<i class='ti ti-man'></i> Service";
    }//service
    echo "</td>";

    echo "<td>";
    if($userType==1 || $create==1)
    {
        if($shopObj->hasVariation($shop_id))
        {
            echo "<button type='button' class='btn border border-primary btn_open_variation'><i class='ti ti-list'></i></button>";
        }//has variation
        echo "<button type='button' class='btn_edit_product btn border border-primary'><i class='ti ti-edit'></i></button>";
    }//admin or has access
    echo "</td>";

    echo "<td>";
    if($userType==1 || $print==1)
    {
        echo "<button type='button' class='btn border-primary btn_open_barcode'><small>Print Barcode</small></button>";
    }//admin or has access
    echo "</td>";

    echo "</tr>";
}//foreach