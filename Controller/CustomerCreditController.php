<?php
include "../Includes/includes.php";
include '../Includes/authcheck.php';

if(isset($_POST['submit_payment'])) {
    // Set default status for cuscredittransactions
    $cuscreditTransactionStat = 1; // Active status
    $cus_id = $_POST['cus_id'];
    $shop_id = $_POST['shop_id'];
    
    $dbObj = new DBTransactions();
    $credit = new credit_customer();
    $success = false;
    
    // Check if it's a single payment or multiple payments
    if(isset($_POST['invoice_id_payment']) && !empty($_POST['invoice_id_payment'])) {
        // Single payment
        $invoice_id = $_POST['invoice_id_payment'];
        $payment_amount = $_POST['payment_amount'];
        $payment_type = $_POST['payment_type'];
        
        // Handle cheque details if payment type is cheque
        $cheque_number = isset($_POST['cheque_number']) ? $_POST['cheque_number'] : '';
        $cheque_date = isset($_POST['cheque_date']) ? $_POST['cheque_date'] : '';
        $bank_name = isset($_POST['bank_name']) ? $_POST['bank_name'] : '';
        
        // Insert payment record
        $sql = "INSERT INTO cuscredittransactions 
                (cuscreditTransactionAmount, cuscreditTransactionStat, invoice_id, paymethod_id, createDate, CreditCustomer_CCID) 
                VALUES 
                ('$payment_amount', '$cuscreditTransactionStat', '$invoice_id', '$payment_type', NOW(), '$cus_id')";
        
        $result = $dbObj->executeTransaction($sql);
        
        if($result) {
            // Update credit record
            $sql = "UPDATE creditcustomer 
                    SET DebitAmount = DebitAmount + $payment_amount, 
                        Balance = CreditAmount - (DebitAmount + $payment_amount) 
                    WHERE Customers_CTID = '$cus_id' AND invoice_header_id = '$invoice_id'";
            
            $update_result = $dbObj->executeTransaction($sql);
            
            if($update_result) {
                $success = true;
            }
        }
    } else if(isset($_POST['invoice_id']) && is_array($_POST['invoice_id'])) {
        // Multiple payments
        $invoice_ids = $_POST['invoice_id'];
        $amounts = $_POST['amount'];
        $payment_type = $_POST['multi_payment_type'];
        
        // Handle cheque details if payment type is cheque
        $cheque_number = isset($_POST['multi_cheque_number']) ? $_POST['multi_cheque_number'] : '';
        $cheque_date = isset($_POST['multi_cheque_date']) ? $_POST['multi_cheque_date'] : '';
        $bank_name = isset($_POST['multi_bank_name']) ? $_POST['multi_bank_name'] : '';
        
        $success = true; // Assume success until proven otherwise
        
        // Start transaction
        $dbObj->beginTransaction();
        
        try {
            for($i = 0; $i < count($invoice_ids); $i++) {
                $invoice_id = $invoice_ids[$i];
                $payment_amount = $amounts[$i];
                
                if($payment_amount > 0) {
                    // Insert payment record
                    $sql = "INSERT INTO cuscredittransactions 
                            (cuscreditTransactionAmount, cuscreditTransactionStat, invoice_id, paymethod_id, createDate, CreditCustomer_CCID) 
                            VALUES 
                            ('$payment_amount', '$cuscreditTransactionStat', '$invoice_id', '$payment_type', NOW(), '$cus_id')";
                    
                    $result = $dbObj->executeTransaction($sql);
                    
                    if($result) {
                        // Update credit record
                        $sql = "UPDATE creditcustomer 
                                SET DebitAmount = DebitAmount + $payment_amount, 
                                    Balance = CreditAmount - (DebitAmount + $payment_amount) 
                                WHERE Customers_CTID = '$cus_id' AND invoice_header_id = '$invoice_id'";
                        
                        $update_result = $dbObj->executeTransaction($sql);
                        
                        if(!$update_result) {
                            $success = false;
                            break;
                        }
                    } else {
                        $success = false;
                        break;
                    }
                }
            }
            
            if($success) {
                $dbObj->commitTransaction();
            } else {
                $dbObj->rollbackTransaction();
            }
        } catch(Exception $e) {
            $dbObj->rollbackTransaction();
            $success = false;
        }
    }
    
    // Set session message and redirect
    if($success) {
        $_SESSION["credit_customer"] = 1; // Success
    } else {
        $_SESSION["credit_customer"] = 0; // Error
    }
    
    // Redirect back to the credit-pay.php page
    header("Location: ../Public/credit-pay.php?cus_id=$cus_id");
    exit();
}
?>
