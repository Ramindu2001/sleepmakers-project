<?php

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

header("Content-Type: application/json");

if (isset($_GET['id'])) {
    $orderId = intval($_GET['id']); // Get Order ID

    $dbObj = new DBTransactions();

    // Fetch Sales Order Details
    $query = "SELECT * FROM sales_orders WHERE id = ".$orderId."";
    $orderheaderData = $dbObj->getData($query);

   
    if ($HeaderResult > 0) {

        $orderData = $orderheaderData[0];
        // Fetch Ordered Products
        $productsQuery = "SELECT sop.product_id AS PDID, p.ItemName AS PDName, sop.quantity, sop.price, 
                          (sop.quantity * sop.price) AS total 
                          FROM sales_order_details sop
                          JOIN products p ON sop.product_id = p.PDID
                          WHERE sop.sales_order_id = ".$orderId."";
        $productsResult = $dbObj->getData($productsQuery);
        
        $products = [];
        while ($row = $productsResult) {
            $products[] = $row;
        }

        // Return JSON response
        echo json_encode([
            "success" => true,
            "data" => [
                "invoice_no" => $orderData['invoice_no'],
                "customer_id" => $orderData['customer_id'],
                "effective_date" => $orderData['effective_date'],
                "shop_id" => $orderData['shop_id'],
                "products" => $products
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Sales order not found"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}

?>
