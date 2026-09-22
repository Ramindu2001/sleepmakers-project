<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$unitObj = new Unit();

if(isset($_POST['btn_save_unit']))
{
    $unit_name = $_POST['unit_name'];
    $short_name = $_POST['short_name'];

    if(!empty($unit_name))
    {
        if(!empty($short_name))
        {
            //check duplicates
            $unitDuplicate = $unitObj->getUnitDuplicate($unit_name, $short_name, $shop_id);
            if(empty($unitDuplicate))
            {
                //save unit
                $unitObj->setUnit($unit_name, $short_name, $shop_id);

                $_SESSION['unit_update'] = 3; //saved successfully
                header("Location: ../Public/units.php");
            }//no duplicate
            else
            {
                $_SESSION['unit_update'] = 2; //duplicate
                header("Location: ../Public/units.php");
                die("Error: unit already exist.");
            }//has duplicate
        }//has short name
        else
        {
            $_SESSION['unit_update'] = 1;
            header("Location: ../Public/units.php");
            die("Error: No short name.");
        }//no short name
    }//has unit name
    else
    {
        $_SESSION['unit_update'] = 0;
        header("Location: ../Public/units.php");
        die("Error: No unit name.");
    }//no unit name
}//save units

if(isset($_POST['btn_update_unit']))
{
    $unit_id = $_POST['hide_unit_id'];
    $unit_name = $_POST['unit_name'];
    $short_name = $_POST['short_name'];

    if(!empty($unit_name))
    {
        if(!empty($short_name))
        {
            //check duplicates
            $unitDuplicate = $unitObj->getUnitDuplicate($unit_name, $short_name, $shop_id);
            if(empty($unitDuplicate))
            {
                //update unit
                $unitObj->editUnit($unit_name, $short_name, $unit_id);

                $_SESSION['unit_update'] = 3; //saved successfully
                header("Location: ../Public/units.php");
            }//no duplicate
            else
            {
                $_SESSION['unit_update'] = 2; //duplicate
                header("Location: ../Public/units.php");
                die("Error: unit already exist.");
            }//has duplicate
        }//has short name
        else
        {
            $_SESSION['unit_update'] = 1;
            header("Location: ../Public/units.php");
            die("Error: No short name.");
        }//no short name
    }//has unit name
    else
    {
        $_SESSION['unit_update'] = 0;
        header("Location: ../Public/units.php");
        die("Error: No unit name.");
    }//no unit name
}//update units

if(isset($_POST['btn_delete_units']))
{
    $unit_id = $_POST['hide_unit_id'];

    $dbObj = new DBTransactions();
    //check product unit id

    $sql = "SELECT count(PDID) as product_count FROM `products` WHERE PurchaseUnit = ".$unit_id." OR SellingUnit = ".$unit_id.";";
    $prodData = $dbObj->getData($sql);

    $product_count = floatval($prodData[0]['product_count']);

    if($product_count > 0)
    {
        $_SESSION['unit_update'] = 5;
        header("Location: ../Public/units.php");
    }//has products
    else
    {   
        $unitObj = new Unit();
        $unitObj->deleteUnit($unit_id);

        $_SESSION['unit_update'] = 6;
        header("Location: ../Public/units.php");
    }//no products

}//delete units