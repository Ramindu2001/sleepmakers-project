<?php
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

// Decode the JSON data sent from the client
$data = json_decode(file_get_contents('php://input'), true);

$dbObj = new DBTransactions();

// Extract form data
$invoice_no = $data['invoice_no'];
$customerCTID = $data['customerCTID'];
$effective_date = $data['effective_date'];
$shop_id = $data['shop_id'];
$products = $data['products'];


// Calculate the total amount for the sales order
$total_amount = 0;
foreach ($products as $product) {
    $total_amount += floatval($product['total']);
}

$status = 1; // 1 = 

$sql1 = "INSERT INTO sales_orders (SalesOrderNo, SalesOrderDate, customer_id, total_amount , status , shop_SHID) 
         VALUES (?, ?, ?, ? , ? , ?)";
// Pass data as an array
$data = [$invoice_no, $effective_date, $customerCTID, $total_amount , $status, $shop_id];
$lastInsertedId = $dbObj->executeTransactionAndReturnLastInsertID($sql1, $data);
$sales_order_id = $lastInsertedId;

// Insert into the `sales_order_details` table
foreach ($products as $product) {
    $product_id = $product['PDID'];
    $quantity = $product['qty'];
    $price = $product['selling_price'];
    $total = $product['total'];

    // Fetch additional product details if needed (e.g., product_name)
    $sql_fetch_product = "SELECT ItemName FROM products WHERE PDID = '$product_id'";
    $product_data = $dbObj->getData($sql_fetch_product);
    $product_name = $product_data[0]['ItemName'];

    // Insert into `sales_order_details`
    $sql2 = "INSERT INTO sales_order_details (sales_order_id, product_id, product_name, quantity, price, total) 
             VALUES ('$sales_order_id', '$product_id', '$product_name', '$quantity', '$price', '$total')";
    $dbObj->executeTransaction($sql2);
}

// Return a success response
echo json_encode(['status' => 'success', 'message' => 'Data saved successfully!']);
?>