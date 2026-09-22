<?php
ini_set('display_errors', 0);
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

header('Content-Type: application/json');

if (!isset($_SESSION['shop_id'])) {
    echo json_encode([]);
    exit;
}

$dbObj     = new DBTransactions();
$shopObj   = new Shop();
$shop_id   = $_SESSION['shop_id'];
$shopData  = $shopObj->getOneShop($shop_id);

$company_id     = $shopData[0]["Company_CMID"];
$is_commonStock = intval($shopData[0]["is_commonStock"]);
$is_expire      = !empty($shopData[0]["is_expire"]) ? true : false;
$date           = date("Y-m-d");

$raw_ids = isset($_GET['product_ids']) ? $_GET['product_ids'] : [];
if (empty($raw_ids) || !is_array($raw_ids)) {
    echo json_encode([]);
    exit;
}

$product_ids = array_values(array_unique(array_map('intval', $raw_ids)));
$result = [];

foreach ($product_ids as $product_id) {
    if ($is_commonStock == 1) {
        if ($is_expire) {
            $sql = "SELECT SUM(i.CurrentQty) AS CurrentQty FROM inventory i
                    INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
                    INNER JOIN shop s ON s.SHID = i.shop_SHID
                    WHERE i.products_PDID = $product_id AND s.Company_CMID = '$company_id'
                    AND (ph.ExpDate IS NULL OR ph.ExpDate = '0000-00-00' OR ph.ExpDate > '$date')";
        } else {
            $sql = "SELECT SUM(i.CurrentQty) AS CurrentQty FROM inventory i
                    INNER JOIN shop s ON s.SHID = i.shop_SHID
                    WHERE i.products_PDID = $product_id AND s.Company_CMID = '$company_id'";
        }
    } else {
        if ($is_expire) {
            $sql = "SELECT SUM(i.CurrentQty) AS CurrentQty FROM inventory i
                    INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
                    WHERE i.products_PDID = $product_id AND i.shop_SHID = '$shop_id'
                    AND (ph.ExpDate IS NULL OR ph.ExpDate = '0000-00-00' OR ph.ExpDate > '$date')";
        } else {
            $sql = "SELECT SUM(i.CurrentQty) AS CurrentQty FROM inventory i
                    WHERE i.products_PDID = $product_id AND i.shop_SHID = '$shop_id'";
        }
    }
    $data = $dbObj->getData($sql);
    $result[$product_id] = isset($data[0]['CurrentQty']) ? floatval($data[0]['CurrentQty']) : 0;
}

echo json_encode($result);
