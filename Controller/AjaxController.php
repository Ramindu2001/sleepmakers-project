<?php
include "../Includes/includes.php";
include '../Includes/authcheck.php';

// Handle AJAX requests
if(isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch($action) {
        case 'get_customer_invoices':
            getCustomerInvoices();
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

/**
 * Get customer invoices with pending amounts
 */
function getCustomerInvoices() {
    if(!isset($_POST['cus_id']) || !isset($_POST['shop_id'])) {
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }
    
    $cus_id = $_POST['cus_id'];
    $shop_id = $_POST['shop_id'];
    
    $credit = new credit_customer();
    $details = $credit->credit_customer_details($cus_id, $shop_id);
    
    $result = [];
    foreach ($details as $row) {
        $pending = $row['total_credit'] - $row['total_debit'];
        if($pending > 0) {
            $result[] = [
                'invoice_header_id' => $row['invoice_header_id'],
                'invoice' => $row['invoice'],
                'pending' => number_format((float)$pending, 2, '.', '')
            ];
        }
    }
    
    echo json_encode($result);
}
?>
