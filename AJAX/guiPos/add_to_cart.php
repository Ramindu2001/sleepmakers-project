<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/gui_pos_class.php";
include "../../Model/shop_class.php";

$noRight = 1;
$dbObj = new DBTransactions();
$shop_id = $_SESSION['shop_id'];
$guiObj = new guiPOS;
$user_id = $_SESSION['user_id'];
$shopObj = new Shop();
$shopData=$shopObj->getOneShop($shop_id);
$is_expire=$shopData[0]["is_expire"];
$is_minus=$shopData[0]["is_minus"];
$company_id=$shopData[0]["CMID"];
$is_commonStock=$shopData[0]["is_commonStock"];
$date=date("Y-m-d");
$alert = [];

if (!empty($_POST["HoldID"]) || !empty($_GET["HoldID"])) {
    if(!empty($_POST["HoldID"]))
    {
        $HoldID = $_POST["HoldID"];    
    }
    if(!empty($_GET["HoldID"]))
    {
        $HoldID = $_GET["HoldID"];    
    }
    

    // Query to get HeaderData
    $sql = "SELECT * FROM hold_invoice hi 
            LEFT JOIN customers c ON c.CTID = hi.customers_CTID
            LEFT JOIN salesmans s ON s.SLID = hi.Salesmans_SLID
            WHERE hi.HIID = '$HoldID'";    
    $headerData = $dbObj->getData($sql);

    $sql="SELECT * FROM selldetail sd
    INNER JOIN products p ON p.PDID = sd.products_PDID
    WHERE sd.sellHeader_SHID='$HoldID'";
    $detailsData=$dbObj->getData($sql);
    $detailData=[];
    foreach ($detailsData as $row) 
    {
        $product_id=$row["products_PDID"];
        $itemData = []; 
        if ($row["ItemType"] == "P") {
            $stockType = $shopData[0]["StockTypes_STID"];
            $is_minus = $shopData[0]["is_minus"] ?? false;
            $is_expire = $shopData[0]["is_expire"] ?? false;
            
            $inventoryData = fetchInventoryData(product_id: $product_id, shop_id: $shop_id, company_id: $company_id, is_commonStock: $is_commonStock, stockType: $stockType, is_default: false,is_expire: $is_expire, is_minus : false);
            
            if (!$inventoryData && $is_minus) {
                $inventoryData = fetchInventoryData(product_id: $product_id, shop_id: $shop_id, company_id: $company_id, is_commonStock: $is_commonStock, stockType: $stockType, is_default: true,is_expire: $is_expire,is_minus : true);
            }
            
            if (!$inventoryData) returnError("00003", "No Stock Available");
            
            // Calculate discount
            list($discountType, $discount, $subtotal) = calculateDiscount($inventoryData[0]["SellingPrice"], $row);
            if($row['UnitConversion']==0.000 || $row['UnitConversion']==0 || $row['UnitConversion']=="0.000")
            {
                $row['UnitConversion']=1.000;
            }
            $inventoryData[0]["TotalCurrentQty"]=$inventoryData[0]["TotalCurrentQty"]*$row["UnitConversion"];
            
        }
        else
        {
            $stockType = $shopData[0]["StockTypes_STID"];
            $is_minus = $shopData[0]["is_minus"] ?? false;
            
            $inventoryData = fetchInventoryData(product_id: $product_id, shop_id: $shop_id, company_id: $company_id, is_commonStock: $is_commonStock, stockType: $stockType, is_default: true,is_expire: false,is_minus:0);
            
            if (!$inventoryData) returnError("00003", "No Stock Available");
            
            // Calculate discount
            list($discountType, $discount, $subtotal) = calculateDiscount($inventoryData[0]["SellingPrice"], $row);
            if($row['UnitConversion']==0.000 || $row['UnitConversion']==0 || $row['UnitConversion']=="0.000")
            {
                $row['UnitConversion']=1.000;
            }
            $inventoryData[0]["TotalCurrentQty"]=$inventoryData[0]["TotalCurrentQty"]*1;
            
        }
        $itemData["PDID"]=$product_id;
        $itemData["SellingPrice"]=$inventoryData[0]["SellingPrice"];
        $itemData["Item_Name"]=$row["Item_Name"];
        $itemData["ItemType"]=$row["ItemType"];
        $itemData["SellQty"]=$row["SellQty"];
        $itemData["totalQty"]=$inventoryData[0]["TotalCurrentQty"];
        $itemData["UnitPrice"]=$row["UnitPrice"];
        $itemData["origi_UnitPrice"]=$row["origi_UnitPrice"];
        $itemData["PurchasePrice"]=$inventoryData[0]["SellingPrice"];
        $itemData["SellAmount"]=$row["SellAmount"];
        $itemData["disc_type"]=$row["disc_type"];
        $itemData["PercentDiscount"]=$row["PercentDiscount"];
        $itemData["DirectDiscount"]=$row["DirectDiscount"];
        $itemData["SellAmount"]=$row["SellAmount"];
        $itemData["SoldAmount"]=$row["SoldAmount"];
        $itemData["is_fixedPrice"]=$row["is_fixedPrice"];
        $detailData[] = $itemData;
    }
    

    // Return combined JSON response
    if (!empty($headerData) || !empty($detailData)) {
        echo json_encode([
            "success" => true,
            "HeaderData" => $headerData,
            "DetailData" => $detailData
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "No data found"]);
    }
} 
else if(!empty($_POST["HoldIDs"]))
{
    $HoldIDs=$_POST["HoldIDs"];
    $sql="UPDATE hold_invoice SET InvStat=0 WHERE HIID='$HoldIDs'";
    $dbObj->executeTransaction($sql);
    echo json_encode(["success" => true, "message" => "No data found"]);
}
else {
    $alert["Error"][] = "Oops, something went wrong. Error Code: #temp-0001";
    echo json_encode(["success" => false, "errors" => $alert]);
}

function fetchInventoryData($product_id, $shop_id, $company_id, $is_commonStock, $stockType, $is_default, $is_expire, $is_minus) {
    global $dbObj;
    $condition = " ";
    $date = date("Y-m-d");

    $condition .= $is_default ? " AND i.is_default=1" : " AND i.CurrentQty > 0";

    if ($is_expire) {
        $condition .= " AND (ph.ExpDate IS NULL OR ph.ExpDate = '0000-00-00' OR ph.ExpDate > '$date')";
    }

    $order = ($stockType == 3) ? "DESC" : "ASC"; // LIFO vs FIFO
    
    $sql = "SELECT i.INID, SUM(i.CurrentQty) AS TotalCurrentQty, ph.SellingPrice AS SellingPrice, (CASE WHEN ph.PurchasePrice = 0 THEN SellingPrice ELSE ph.PurchasePrice END) AS PurchasePrice 
            FROM `inventory` i
            INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID ";

    if ($is_commonStock == 1) {
        $sql .= "INNER JOIN shop s ON s.SHID = i.shop_SHID 
                 WHERE i.products_PDID = '$product_id' 
                 AND s.Company_CMID = '$company_id' $condition ";
    } else {
        $sql .= "WHERE i.products_PDID = '$product_id' 
                 AND i.shop_SHID = '$shop_id' $condition ";
    }

    $sql .= "GROUP BY ph.SellingPrice ORDER BY i.INID $order ";
    
    return $dbObj->getData($sql); // Return all records
      
}
function calculateDiscount($unitPrice, $row) {
    $discount = 0;
    $discountType = 0;
    
    if (!empty($row["prodDiscount"]) && $row["prodDiscount"] != "0.00") {
        //$discount = ($unitPrice * $row["prodDiscount"]) / 100;
        $discount = $row["prodDiscount"];
        $discountType = 1;
    } elseif (!empty($row["prodFlatDiscount"]) && $row["prodFlatDiscount"] != "0.00") {
        $discount = $row["prodFlatDiscount"];
        $discountType = 2;
    }
    
    return [$discountType, $discount, $unitPrice - $discount];
}
function returnError($code, $message) {
    echo json_encode(["error" => true, "code" => $code, "message" => $message]);
    exit;
}

?>