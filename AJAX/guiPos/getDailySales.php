<?php
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        
        if (!isset($_SESSION['shop_id'])) {
            echo json_encode(['success' => false, 'message' => 'Session shop_id not found']);
            exit;
        }
        
        $shop_id = $_SESSION['shop_id'];
    
    $dbObj = new DBTransactions();
    
    // Get daily sales summary
    $dailySalesQuery = "
        SELECT 
            COUNT(*) as total_invoices,
            COALESCE(SUM(NetAmount), 0) as total_sales,
            COALESCE(SUM(DiscountAmount), 0) as total_discount,
            COALESCE(AVG(NetAmount), 0) as avg_sale
        FROM invoiceheader 
        WHERE DATE(EffectiveDate) = '$date' 
        AND shop_SHID = '$shop_id'
        AND InvStat = 1
    ";
    
    $dailySalesData = $dbObj->getData($dailySalesQuery);
    $dailySales = $dailySalesData[0] ?? [
        'total_invoices' => 0,
        'total_sales' => 0,
        'total_discount' => 0,
        'avg_sale' => 0
    ];
    
    // Get user-wise sales
    $userSalesQuery = "
        SELECT 
            u.UserName,
            COUNT(h.IHID) as invoice_count,
            COALESCE(SUM(h.NetAmount), 0) as total_amount,
            COALESCE(AVG(h.NetAmount), 0) as avg_amount,
            COALESCE(SUM(h.DiscountAmount), 0) as discount_given
        FROM invoiceheader h
        LEFT JOIN user u ON h.user_USID = u.USID
        WHERE DATE(h.EffectiveDate) = '$date' 
        AND h.shop_SHID = '$shop_id'
        AND h.InvStat = 1
        GROUP BY h.user_USID, u.UserName
        ORDER BY total_amount DESC
    ";
    
    $userSalesData = $dbObj->getData($userSalesQuery);
    
    // Format user data
    $userSales = [];
    foreach ($userSalesData as $user) {
        $userSales[] = [
            'user_name' => $user['UserName'],
            'invoices' => (int)$user['invoice_count'],
            'total_amount' => (float)$user['total_amount'],
            'avg_amount' => (float)$user['avg_amount'],
            'discount_given' => (float)$user['discount_given']
        ];
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'daily_summary' => [
            'total_sales' => (float)$dailySales['total_sales'],
            'total_invoices' => (int)$dailySales['total_invoices'],
            'total_discount' => (float)$dailySales['total_discount'],
            'avg_sale' => (float)$dailySales['avg_sale']
        ],
        'user_sales' => $userSales
    ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
