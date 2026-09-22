<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];

if(isset($_POST['btn_save_Salesman']))
{          
    $SalesmanNo = "";
    $SalesmansName =  $_POST['SalsName'];                       
    $Contact =  $_POST['Contact'];
    $commision_rate =  $_POST['commision_rate'];

    if(!empty($SalesmansName))
    {
        if(!empty($Contact))
        {   
            // Validate checkbox
            $SalesmanStat = isset($_POST['SalesmanStat']) ? 1 : 0;

            // Validate and sanitize input data (optional)
            $SalesmansName = htmlspecialchars($SalesmansName);
            $Contact = htmlspecialchars($Contact);

            //get the Saleman no 
            $salObj = new Salesman();
                                
            $Salesman = $salObj->getSalesmanCount();
            $Sal_count = floatval($Salesman[0]['Salcount']);
            $Sal_count += 1;

            $commonObj = new Common();

            $SalesmanNo = $commonObj->createCount("SM", $Sal_count);

            // Error handling 
            try 
            {
                $salObj->setSalesman($SalesmanNo, $SalesmansName, $Contact, $commision_rate, $SalesmanStat, $shop_id);
                $_SESSION['salesman_update'] = 2;
                header("Location: ../Public/Salesmanlist.php");
                exit();
            }
            catch(Exception $e) {
                // Handle any errors from the function
                echo "An error occurred while adding the Salesman: " . $e->getMessage();
                exit();
            }
        }//has contact
        else
        {
            $_SESSION['salesman_update'] = 1;
            header("Location: ../Public/Salesmanlist.php");
            die("Error: no salesman contact no.");
        }//no contact
    }//has salesman name
    else
    {
        $_SESSION['salesman_update'] = 0;
        header("Location: ../Public/Salesmanlist.php");
        die("Error: no salesman name.");
    }//no salesman name
}//save new Salesman


if(isset($_POST['btn_Update_Salesman']))
{  
    $salesman_id = $_POST['hide_salesman_id'];
    $SalesmanNo = $_POST['SalNo'];
    $SalesmansName =  $_POST['SalsName'];                       
    $Contact =  $_POST['Contact'];
    $commision_rate =  $_POST['commision_rate'];

    if(!empty($SalesmansName))
    {
        if(!empty($Contact))
        {   
            // Validate checkbox
            $SalesmanStat = 0;
            if(isset($_POST['SalesmanStat'])) 
            {
                $SalesmanStat = 1;
            }

            // Validate and sanitize input data (optional)
            $SalesmansName = htmlspecialchars($SalesmansName);
            $Contact = htmlspecialchars($Contact);

            //get the Saleman no 
            $salObj = new Salesman();                         
            $Salesman = $salObj->getSalesmanCount();
            $Sal_count = floatval($Salesman[0]['Salcount']);
            $Sal_count += 1;

            $commonObj = new Common();                                
            $SalesmanNo = $commonObj->createCount("SM", $Sal_count);   

            // Error handling 
            try 
            {
                $salObj->updateSalesman($SalesmansName, $Contact, $commision_rate, $SalesmanStat, $salesman_id);
                $_SESSION['salesman_update'] = 3;
                header("Location: ../Public/Salesmanlist.php");
                exit();
            } 
            catch(Exception $e) {
                // Handle any errors from the function
                echo "An error occurred while adding the Salesman: " . $e->getMessage();
                exit();
            }
        }//has contact
        else
        {
            $_SESSION['salesman_update'] = 1;
            header("Location: ../Public/Salesmanlist.php");
            die("Error: no salesman contact no.");
        }//no contact
    }//has salesman name
    else
    {
        $_SESSION['salesman_update'] = 0;
        header("Location: ../Public/Salesmanlist.php");
        die("Error: no salesman name.");
    }//no salesman name  
       
}//update Salesman

if(isset($_POST['btn_delete_salesman']))
{
    $salesman_id = $_POST['hide_salesman_id'];

    //check invoices
    $dbObj = new DBTransactions();

    $sql = "SELECT count(IHID) as invoice_count FROM `invoiceheader` WHERE Salesmans_SLID = ".$salesman_id.";";
    $invData = $dbObj->getData($sql);

    $invoice_count = floatval($invData[0]['invoice_count']);

    $has_invoice = $invoice_count > 0 ? 1 : 0;

    //echo "count - " . $has_invoice . "<br>";

    if($has_invoice)
    {
        //cannot delete salesman
        $_SESSION['salesman_update'] = 4;
        header("Location: ../Public/Salesmanlist.php");
    }//has invoice
    else
    {
        $saleObj = new Salesman();
        $saleObj->deleteSalesman($salesman_id);

        $_SESSION['salesman_update'] = 5;
        header("Location: ../Public/Salesmanlist.php");
    }//delete salesman
}//delete salesman
