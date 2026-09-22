<?php 
include "../Includes/includes.php";


if(isset($_POST['btn_save_customer']))
{          
    $CustomerNo = "";
    $CustName = $_POST['CusName'];
    $CustContact = $_POST['Contact'];
    $Custgender = $_POST['gender'];
    $shop_id = $_SESSION['shop_id'];

    $CustAddress = isset($_POST['Address']) ? $_POST['Address'] : "";
    $CustDOB = isset($_POST['DOB']) ? $_POST['DOB'] : "";
    $PaymentTerm = isset($_POST['payment_term']) ? $_POST['payment_term'] : 0;
    //add default for payment term
    $PaymentTerm = empty($_POST['payment_term']) ? 60 : $_POST['payment_term'];
    // Validate checkbox
    $CustStat = 0;
    if(isset($_POST['CustomerStat'])) 
    {
        $CustStat = 1;
    }//Active customer 

    //default credit amount, should change later
    $MaxCreditAmount = empty($_POST['CreditAmount']) ? 75000 : $_POST['CreditAmount']; 

    // Validate and sanitize input data (optional)
    $CustName = htmlspecialchars($CustName);
    $CustContact = htmlspecialchars($CustContact);
    $MaxCreditAmount = htmlspecialchars($MaxCreditAmount);
    $CustAddress = htmlspecialchars($CustAddress);

    //get the Customer no                         
    $cusObj = new Customer();                            
    $customer = $cusObj->getCustomerCount();
    if(!empty($customer))
    {
        $Cus_count = floatval($customer[0]['CusCount']);
        $Cus_count += 1;
    }
    else
    {
        $Cus_count = 1;
    }//not max count

    $commonObj = new Common();                                
    $CustomerNo = $commonObj->createCount("CU", $Cus_count);  

    // Validate inputs
    if(!empty($CustName) && !empty($CustContact)) 
    {
        if(is_numeric($MaxCreditAmount))
        {
            // Error handling for setCustomer function
            try 
            {
                $cusObj->setCustomer($CustomerNo,$CustName,$CustAddress,$CustContact,$MaxCreditAmount, $PaymentTerm, $CustStat,$shop_id,$CustDOB,$Custgender);
                $_SESSION['customer_update'] = 2;
                header("Location: ../Public/Customerlist.php");
            }//try
            catch(Exception $e)
            {
                // Handle any errors from setCustomer function
                echo "An error occurred while adding the customer: " . $e->getMessage();
                exit();
            }//catch
        }//velid number
        else
        {
            // Handle empty fields
            $_SESSION['customer_update'] = 1;
            header("Location: ../Public/Customerlist.php");
            die("Error: not valid number.");
        }//not valid number

    }//has values
    else
    {
        // Handle empty fields
        $_SESSION['customer_update'] = 0;
        header("Location: ../Public/Customerlist.php");
        die("Error: insufficint data.");
    }//empty input values

}//save new customer
elseif(isset($_POST['WS_btn_save_customer']))
{          
    $CustomerNo = "";
    $CustName = $_POST['CusName'];
    $CustContact = $_POST['Contact'];
    $Custgender = $_POST['gender'];
    $shop_id = $_SESSION['shop_id'];

    $CustAddress = isset($_POST['Address']) ? $_POST['Address'] : "";
    $CustDOB = isset($_POST['DOB']) ? $_POST['DOB'] : "";
    $PaymentTerm = isset($_POST['payment_term']) ? $_POST['payment_term'] : 0;
    //add default for payment term
    $PaymentTerm = empty($_POST['payment_term']) ? 60 : $_POST['payment_term'];
    // Validate checkbox
    $CustStat = 0;
    if(isset($_POST['CustomerStat'])) 
    {
        $CustStat = 1;
    }//Active customer 

    //default credit amount, should change later
    $MaxCreditAmount = empty($_POST['CreditAmount']) ? 75000 : $_POST['CreditAmount']; 

    // Validate and sanitize input data (optional)
    $CustName = htmlspecialchars($CustName);
    $CustContact = htmlspecialchars($CustContact);
    $MaxCreditAmount = htmlspecialchars($MaxCreditAmount);
    $CustAddress = htmlspecialchars($CustAddress);

    //get the Customer no                         
    $cusObj = new Customer();                            
    $customer = $cusObj->getCustomerCount();
    if(!empty($customer))
    {
        $Cus_count = floatval($customer[0]['CusCount']);
        $Cus_count += 1;
    }
    else
    {
        $Cus_count = 1;
    }//not max count

    $commonObj = new Common();                                
    $CustomerNo = $commonObj->createCount("CU", $Cus_count);  

    // Validate inputs
    if(!empty($CustName) && !empty($CustContact)) 
    {
        if(is_numeric($MaxCreditAmount))
        {
            // Error handling for setCustomer function
            try 
            {
                $cusObj->setCustomer($CustomerNo,$CustName,$CustAddress,$CustContact,$MaxCreditAmount, $PaymentTerm, $CustStat,$shop_id,$CustDOB,$Custgender);
                $_SESSION['customer_update'] = 2;
                $custID=$cusObj->getCustomerCount();
                $string=$CustName." - ".$CustContact;
                ?>
                <script>
                if (typeof setPredefinedSupplier === "function") {
                    setPredefinedSupplier("<?=$custID[0]["CusCount"]?>","<?=$string?>");
                }
                if (typeof setPredefinedCustomer === "function") {
                    setPredefinedCustomer("<?=$custID[0]["CusCount"]?>","<?=$string?>");
                }
                </script>
                <?php

            }//try
            catch(Exception $e)
            {
                // Handle any errors from setCustomer function
                echo "An error occurred while adding the customer: " . $e->getMessage();
                exit();
            }//catch
        }//velid number
        else
        {
            // Handle empty fields
            $_SESSION['customer_update'] = 1;
            die("Error: not valid number.");
        }//not valid number

    }//has values
    else
    {
        // Handle empty fields
        $_SESSION['customer_update'] = 0;
        die("Error: insufficint data.");
    }//empty input values

}//save new customer

if(isset($_POST['btn_update_customer']))
{    
    $customer_id = $_POST['hide_customer_id'];
    $CustomerNo = "";
    $CustName = $_POST['CusName'];
    $CustContact = $_POST['Contact'];  
    $shop_id = $_SESSION['shop_id'];

    $Custgender = isset($_POST['gender']) ? $_POST['gender'] : "1";
    $CustDOB = isset($_POST['DOB']) ? $_POST['DOB'] : "";
    $CustAddress = isset($_POST['Address']) ? $_POST['Address'] : "";
    //add default for payment term
    $PaymentTerm = empty($_POST['payment_term']) ? 60 : $_POST['payment_term'];

    // Customer stat
    $CustStat = isset($_POST['CustomerStat']) ? 1 : 0;

    //default credit amount, should change later
    $MaxCreditAmount = empty($_POST['CreditAmount']) ? 75000 : $_POST['CreditAmount']; 

    // Validate and sanitize input data (optional)
    $CustName = htmlspecialchars($CustName);
    $CustContact = htmlspecialchars($CustContact);
    $MaxCreditAmount = htmlspecialchars($MaxCreditAmount);
    $CustAddress = htmlspecialchars($CustAddress);

    //customer object                        
    $cusObj = new Customer();  

    // Validate inputs
    if(!empty($CustName) && !empty($CustContact)) 
    {
        if(is_numeric($MaxCreditAmount))
        {
            // Error handling for setCustomer function
            try 
            {
                //$cusObj->setCustomer($CustomerNo,$CustName,$CustAddress,$CustContact,$MaxCreditAmount,$CustStat,$shop_id);
                $cusObj->updateCustomer($CustName, $Custgender, $CustDOB, $CustAddress, $CustContact, $MaxCreditAmount, $PaymentTerm, $CustStat, $customer_id);
                $_SESSION['customer_update'] = 3;
                header("Location: ../Public/Customerlist.php");
            }//try update
            catch(Exception $e)
            {
                // Handle any errors from setCustomer function
                echo "An error occurred while adding the customer: " . $e->getMessage();
                exit();
            }//catch
        }//velid number
        else
        {
            // Handle empty fields
            $_SESSION['customer_update'] = 1;
            header("Location: ../Public/Customerlist.php");
            die("Error: not valid number.");
        }//not valid number

    }//has values
    else
    {
        // Handle empty fields
        $_SESSION['customer_update'] = 0;
        header("Location: ../Public/Customerlist.php");
        die("Error: insufficint data.");
    }//empty input values
}//update Supplier

if(isset($_POST['btn_delete_customer']))
{
    $customer_id = $_POST['hide_customer_id'];

    $dbObj = new DBTransactions();
    //check invoice
    $sql = "SELECT count(IHID) as invoice_count FROM `invoiceheader` WHERE customers_CTID = ".$customer_id.";";
    $invData = $dbObj->getData($sql);
    $invoice_count = floatval($invData[0]['invoice_count']);

    $has_invoice = $invoice_count > 0 ? 1 : 0;

    //check credit
    $sql_1 = "SELECT count(PRHID) as prescription_count FROM `prescriptionheader` WHERE customer_CTID = ".$customer_id.";";
    $presData = $dbObj->getData($sql_1);
    $prescription_count = floatval($presData[0]['prescription_count']);

    $has_prescription = $prescription_count > 0 ? 1 : 0;

    // echo "has invoice - " . $has_invoice . "<br>";
    // echo "has pres - " . $has_prescription . "<br>";

    if($has_invoice || $has_prescription)
    {
        //cannot delete customer
        $_SESSION['customer_update'] = 4;
        header("Location: ../Public/Customerlist.php");
        die("Error: constraint violation.");
    }  
    else
    {
        //can delete customer
        $custObj = new Customer();
        $custObj->deleteCustomer($customer_id);

        $_SESSION['customer_update'] = 5;
        header("Location: ../Public/Customerlist.php");
    }//can delet customer

}//delete customer