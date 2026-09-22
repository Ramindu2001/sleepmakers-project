<?php
include "../Includes/includes.php";

if (isset($_POST['btn_save_method'])) {
    $shop_SHID = $_POST['shop_SHID'];
    $paymethod_PMIDs = $_POST['paymethod_PMID']; // This will be an array of payment methods

    if (empty($shop_SHID) || empty($paymethod_PMIDs)) {
        error_log("Shop ID or Payment Method ID is missing.");
        die("Error: Shop ID or Payment Method ID is missing.");
    }

    $PaymentObj = new AddPaymentModels();
    $errors = [];
    foreach ($paymethod_PMIDs as $paymethod_PMID) {
        $result = $PaymentObj->setPaymentModels($shop_SHID, $paymethod_PMID);
        if ($result !== true) {
            $errors[] = "Couldn't assign payment method ID $paymethod_PMID to shop ID $shop_SHID.";
        }
    }

    if (empty($errors)) {
        echo "Payment Methods Added";
    } else {
        echo "Error: " . implode(', ', $errors);
    }
}

if (isset($_POST['check_pay_method'])) {
    $shop_SHID = $_POST['shop_SHID'];
    $paymethod_PMIDs = $_POST['paymethod_PMID'];

    if (empty($shop_SHID) || empty($paymethod_PMIDs)) {
        die("Error: Shop ID or Payment Method ID is missing.");
    }

    $shopObj = new AddPaymentModels();
    $errors = [];
    foreach ($paymethod_PMIDs as $paymethod_PMID) {
        $result = $shopObj->setPaymentModels($shop_SHID, $paymethod_PMID);
        if ($result === "Payment method already assigned to this shop") {
            $errors[] = "Payment method ID $paymethod_PMID already assigned to shop ID $shop_SHID.";
        }
    }

    if (empty($errors)) {
        echo "Payment method not assigned to this shop yet.";
    } else {
        echo implode(', ', $errors);
    }
}

if (isset($_POST['delete_payment'])) {
    $SPID = $_POST['SPID'];

    if (empty($SPID)) {
        die("Error: Payment method ID is missing.");
    }

    $PaymentObj = new AddPaymentModels();
    $result = $PaymentObj->deletePaymentMethod($SPID);
    if ($result) {
        echo "Payment Method Deleted";
    } else {
        echo "Error: Couldn't delete the Payment Method";
    }
    exit();
}

if(isset($_POST['btn_delete_paymethod']))
{
    $paymethod_id = $_POST['paymethod_id'];

    $PaymentObj = new AddPaymentModels();
    $PaymentObj->deletePaymentMethod($paymethod_id);

    header("Location: ../Public/AssignPaymentMethods.php");
}//delete assigned paymethod

//========================= Add Payment Method =============================//
if(isset($_POST['btn_save_paymethod']))
{
    $paymethod_name = $_POST['paymethod_name'];

    $paymethod_image_size = floatval($_FILES['paymethod_image']['size']);

    //file upload directry
    $target_dir = "../Assets/Images/paymethod_images/";

    if(!empty($paymethod_name))
    {
        if($paymethod_image_size < 500000 AND $paymethod_image_size > 0)
        {
            $sql = "SELECT count(PMID) as PaymethodCount FROM paymethod;";
            $dbObj = new DBTransactions();
            $dbData = $dbObj->getData($sql);

            $pay_count = floatval($dbData[0]['PaymethodCount']);
            $pay_count += 1;

            //naming
            $commonObj = new Common();
            $pay_image_no = $commonObj->createCount("PM", $pay_count);

            //file path
            $target_file_path = $target_dir . $pay_image_no . basename($_FILES['paymethod_image']['name']);
            $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);

            $pay_image_name = $pay_image_no . "." . $file_type;
            $target_file_path = $target_dir . $pay_image_name;

            // Allow certain file formats 
            $allow_types = array('jpg', 'png', 'PNG', 'jpeg');

            if(in_array($file_type, $allow_types))
            {
                if(move_uploaded_file($_FILES["paymethod_image"]["tmp_name"], $target_file_path))
                {
                    $payObj = new AddPaymentModels();

                    $payObj->setPaymethod($paymethod_name, $pay_image_name);

                    $_SESSION['paymethod_update'] = 3; //save success
                    header("Location: ../Public/AssignPaymentMethods.php");
                }//file moved
            }//valid file type
            else
            {
                $_SESSION['paymethod_update'] = 2; //invalid file type
                header("Location: ../Public/AssignPaymentMethods.php");
                die("Error: Image size greater than 500KB");
            }//invalid file type
        }//valid size
        else
        {
            $_SESSION['paymethod_update'] = 1;
            header("Location: ../Public/AssignPaymentMethods.php");
            die("Error: Image size greater than 500KB");
        }//invelid image size
    }//has name
    else
    {
        $_SESSION['paymethod_update'] = 0;
        header("Location: ../Public/AssignPaymentMethods.php");
        die("Error: No paymethod name");
    }//no name
}//add payment method