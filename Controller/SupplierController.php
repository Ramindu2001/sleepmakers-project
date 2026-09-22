<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$suppObj = new Supplier();    

if(isset($_POST['btn_save_Supplier']))
{          
    $SupplierName = $_POST['SuppName'];
    $Contact = $_POST['Contact'];
    $distributer = $_POST['distributer'];      

    //supplier Active
    $SupplierStat= 0;
    if(isset($_POST['SuppStatus'])){$SupplierStat = 1;}      
    
    if(!empty($distributer))
    {
        if(!empty($SupplierName))
        {
            if(!empty($Contact))
            {
                //get the Supplier no                         
                $supplier = $suppObj->getSupplierCount();
                $Supp_count = floatval($supplier[0]['SuppCount']);
                $Supp_count += 1;

                $commonObj = new Common();                               
                $SupplierNo = $commonObj->createCount("SP", $Supp_count);      

                //SupplierNo, Distributer, SupplierName, Contact, SupplierStat, shop_SHID
                $suppObj->setSupplier($SupplierNo, $distributer, $SupplierName, $Contact, $SupplierStat, $shop_id);

                $_SESSION['supplier_update'] = 3;
                header("Location: ../Public/Supplierlist.php");
                exit(); // Always add exit() after a header redirect
            }//has contact no
            else
            {
                $_SESSION['supplier_update'] = 2;
                header("Location: ../Public/Supplierlist.php");
                die("Error: supplier contact not entered.");
            }//no contact
        }//has supplier name
        else
        {
            $_SESSION['supplier_update'] = 1;
            header("Location: ../Public/Supplierlist.php");
            die("Error: no supplier name entered.");
        }//no supplier name
    }//has distributer
    else
    {
        $_SESSION['supplier_update'] = 0;
        header("Location: ../Public/Supplierlist.php");
        die("Error: no distributer entered.");
    }//no distributer
}//save new Supplier

if(isset($_POST['btn_Update_Supplier']))
{     
    $supplier_id = $_POST['hide_supplier_id'];
    $SupplierName = $_POST['SuppName'];
    $Contact = $_POST['Contact'];
    $distributer = $_POST['distributer'];      

    //supplier Active
    $SupplierStat= 0;
    if(isset($_POST['SuppStatus'])){$SupplierStat = 1;}      
    
    if(!empty($distributer))
    {
        if(!empty($SupplierName))
        {
            if(!empty($Contact))
            {
                //SupplierNo, Distributer, SupplierName, Contact, SupplierStat, shop_SHID
                $suppObj->editSupplier($distributer, $SupplierName, $Contact, $SupplierStat, $supplier_id);

                $_SESSION['supplier_update'] = 4;
                header("Location: ../Public/Supplierlist.php");
                exit(); // Always add exit() after a header redirect
            }//has contact no
            else
            {
                $_SESSION['supplier_update'] = 2;
                header("Location: ../Public/Supplierlist.php");
                die("Error: supplier contact not entered.");
            }//no contact
        }//has supplier name
        else
        {
            $_SESSION['supplier_update'] = 1;
            header("Location: ../Public/Supplierlist.php");
            die("Error: no supplier name entered.");
        }//no supplier name
    }//has distributer
    else
    {
        $_SESSION['supplier_update'] = 0;
        header("Location: ../Public/Supplierlist.php");
        die("Error: no distributer entered.");
    }//no distributer       
}//update Supplier

if(isset($_POST['btn_delete_supplier']))
{
    $supplier_id = $_POST['hide_supplier_id'];
    if(isset($supplier_id))
    {
        //check constrains
        $sql = "SELECT * FROM grnheader WHERE Suppliers_SPID = ".$supplier_id.";";
        $dbObj = new DBTransactions();
        $dbData = $dbObj->getData($sql);
        if(empty($dbData))
        {
            $suppObj->deleteSupplier($supplier_id);

            $_SESSION['supplier_update'] = 5;
            header("Location: ../Public/Supplierlist.php");
        }//no entry
        else
        {
            $_SESSION['supplier_update'] = 6;
            header("Location: ../Public/Supplierlist.php");
            die("Error: supplier Constrain violation.");
        }//has entry
        
    }//has supplier
    else
    {
        header("Location: ../Public/Supplierlist.php");
        die("Error: no supplier selected");
    }//no supplier
}//delete supplier

