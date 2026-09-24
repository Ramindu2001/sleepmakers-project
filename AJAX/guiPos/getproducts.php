<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Includes/textile_helper.php";
require_once "../../Includes/warehouse_fulfilment.php";
$shop_id = $_SESSION['shop_id'];
$dbObj = new DBTransactions();
//shop data
$sql="SELECT * FROM shop WHERE SHID='$shop_id'";
$shopdata = $dbObj->getData($sql);

//company data
$company_id=$shopdata[0]["Company_CMID"];
$sql="SELECT * FROM company WHERE CMID='$company_id' ";
$companydata = $dbObj->getData($sql);
$is_multicategory = $companydata[0]["is_multicategory"];
$is_commonStock = $companydata[0]["is_commonStock"];
$is_minus=$shopdata[0]["is_minus"];
$add="";
$is_expire = $shopdata[0]["is_expire"] ?? false;
$date=date("Y-m-d");
$show_images = isset($_GET['show_images']) ? intval($_GET['show_images']) : 1;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit  = isset($_GET['limit'])  ? intval($_GET['limit'])  : 40;
if(isset($_GET["allproducts"]))
{
    if($is_multicategory==1 )
    {
        $sql = "SELECT p.*, sc.*, s.* FROM `products` p 
        INNER JOIN subcategories sc ON sc.SCID=p.Subcategories_SCID
        INNER JOIN shop s ON s.SHID=p.shop_SHID
        WHERE s.Company_CMID='$company_id' AND p.ProductStat=1  ORDER BY p.ItemName,p.ItemType ASC LIMIT $limit OFFSET $offset;";
        $shopie=1;
    }
    else
    {
        $sql = "SELECT p.*, sc.* FROM `products` p 
        INNER JOIN subcategories sc ON sc.SCID=p.Subcategories_SCID
        WHERE p.shop_SHID='$shop_id' AND p.ProductStat=1  ORDER BY p.ItemName,p.ItemType ASC LIMIT $limit OFFSET $offset;";
    }
    $productData = $dbObj->getData($sql); 
    

    // Count total for lazy load sentinel
    if($is_multicategory==1)
    {
        $sqlCount = "SELECT COUNT(*) AS total FROM `products` p INNER JOIN subcategories sc ON sc.SCID=p.Subcategories_SCID INNER JOIN shop s ON s.SHID=p.shop_SHID WHERE s.Company_CMID='$company_id' AND p.ProductStat=1;";
    }
    else
    {
        $sqlCount = "SELECT COUNT(*) AS total FROM `products` p INNER JOIN subcategories sc ON sc.SCID=p.Subcategories_SCID WHERE p.shop_SHID='$shop_id' AND p.ProductStat=1;";
    }
    $totalRow = $dbObj->getData($sqlCount);
    $totalProducts = intval($totalRow[0]['total']);

    $procount=0;
    foreach ($productData as $row)
    {
        $product_id=$row["PDID"];
        $row["ProdSellPrice"] = tileSellingPrice($row, $shop_id, $company_id, $is_commonStock, $shopdata[0]);
        if($row["ItemType"]=="P")
        {
            $sql="SELECT *, SUM(CurrentQty) AS CurrentQty  FROM `inventory` i WHERE i.`products_PDID` ='$product_id' AND i.shop_SHID='$shop_id' GROUP BY i.`products_PDID`";
            if($is_expire)
            {
                $sql="SELECT *, SUM(CurrentQty) AS CurrentQty  FROM `inventory` i 
                INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
                WHERE i.`products_PDID` ='$product_id' AND i.shop_SHID='$shop_id' 
                    AND (ph.ExpDate IS NULL OR ph.ExpDate = '0000-00-00' OR ph.ExpDate > '$date')                
                GROUP BY i.`products_PDID`";
            }
            if($is_commonStock==1)
            {
                $sql="SELECT *, SUM(CurrentQty) AS CurrentQty  FROM `inventory` i 
                INNER JOIN shop s ON s.SHID=i.shop_SHID
                WHERE i.`products_PDID` ='$product_id' AND s.Company_CMID='$company_id' GROUP BY i.`products_PDID`";
                if($is_expire)
                {
                    $sql="SELECT *, SUM(CurrentQty) AS CurrentQty  FROM `inventory` i 
                    INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
                    INNER JOIN shop s ON s.SHID=i.shop_SHID
                    WHERE i.`products_PDID` ='$product_id' AND s.Company_CMID='$company_id' 
                        AND (ph.ExpDate IS NULL OR ph.ExpDate = '0000-00-00' OR ph.ExpDate > '$date')
                    GROUP BY i.`products_PDID`";
                }
            }
            $inventoryData = $dbObj->getData($sql); 
            if(isset($inventoryData[0]["CurrentQty"]) && $inventoryData[0]["CurrentQty"] > 0)
            {
                $filepath="../Assets/Images/prod_images/".$row['ProdImage'];
                if(!empty($row[0]['ProdImage']))
                {
                    $filepath="../Assets/Images/prod_images/".$row['ProdImage'];
                }
                else
                {
                    $filepath="../Assets/no-image.jpg";
                }
                if(!file_exists("../".$filepath))
                {
                    $filepath="../Assets/no-image.jpg";
                }
                $procount+=1;
                $inventoryData['CurrentQty'] = $inventoryData[0]['CurrentQty'] * $row['UnitConversion'];
                ?>
                <a href="javascript:void(0)" class="col-md-3 product mb-2">
                    <div class="card product-card position-relative">
                        <div class="d-none">
                            <input type="hidden" name="" id="productid" value="<?=$row["PDID"]?>">
                        </div>
                        <span class="qty-badge"><?=$inventoryData[0]["CurrentQty"]?></span>
                        <?php if($show_images == 1): ?>
                        <div class="product-image">
                            <img src="<?=$filepath?>" alt="product-image" class="w-100 product-img">
                        </div>
                        <div class="product-detail p-2">
                            <p class="product-title"><?=getProductDisplayName($row)?></p>
                            <p class="product-price"><b>Rs. <?=$row["ProdSellPrice"]?></b></p>
                        </div>
                        <?php else: ?>
                        <div class="product-name-tile">
                            <span class="product-name-text"><?=getProductDisplayName($row)?></span>
                            <span class="product-name-price">Rs. <?=$row["ProdSellPrice"]?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php
            }
            else
            {
                if($is_minus==1)
                {
                    $sql="SELECT *, SUM(CurrentQty) AS CurrentQty  FROM `inventory` i WHERE i.`products_PDID` ='$product_id' AND i.shop_SHID='$shop_id' AND i.is_default=1 GROUP BY i.`products_PDID` LIMIT 1";
                    if($is_commonStock==1)
                    {
                        $sql="SELECT *, SUM(CurrentQty) AS CurrentQty  FROM `inventory` i 
                        INNER JOIN shop s ON s.SHID=i.shop_SHID
                        WHERE i.`products_PDID` ='$product_id' AND s.Company_CMID='$company_id' GROUP BY i.`products_PDID` LIMIT 1";
                    }
                    $inventoryData = $dbObj->getData($sql); 
                    if(isset($inventoryData[0]["CurrentQty"]))
                    {
                        $filepath="../Assets/Images/prod_images/$row[ProdImage]";
                        if(isset($row["ProdImage"]) && $row["ProdImage"]!="" && file_exists("../".$filepath))
                        {
                            $filepath="../Assets/Images/prod_images/$row[ProdImage]";
                        }
                        else
                        {
                            $filepath="../Assets/no-image.jpg";
                        }
                        $procount+=1;
                        $inventoryData['CurrentQty'] = $inventoryData[0]['CurrentQty'] * $row['UnitConversion'];
                        ?>
                        <a href="javascript:void(0)" class="col-md-3 product mb-2 subcat-<?=$row["SCID"]?>">
                            <div class="card product-card position-relative">
                                <div class="d-none">
                                    <input type="hidden" name="" id="productid" value="<?=$row["PDID"]?>">
                                </div>
                                <span class="qty-badge"><?=$inventoryData[0]["CurrentQty"]?></span>
                                <?php if($show_images == 1): ?>
                                <div class="product-image">
                                    <img src="<?=$filepath?>" alt="product-image" class="w-100 product-img">
                                </div>
                                <div class="product-detail p-2">
                                    <p class="product-title"><?=getProductDisplayName($row)?></p>
                                    <p class="product-price"><b>Rs. <?=$row["ProdSellPrice"]?></b></p>
                                </div>
                                <?php else: ?>
                                <div class="product-name-tile">
                                    <span class="product-name-text"><?=getProductDisplayName($row)?></span>
                                    <span class="product-name-price">Rs. <?=$row["ProdSellPrice"]?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php
                    }
                    else
                    {
        
                    }
                }
                else
                {
        
                }    
            }
        }
        else
        {
            $sql="SELECT *, SUM(CurrentQty) AS CurrentQty  FROM `inventory` i WHERE i.`products_PDID` ='$product_id' AND i.shop_SHID='$shop_id' AND i.is_default=1 GROUP BY i.`products_PDID` LIMIT 1";
            if($is_commonStock==1)
            {
                $sql="SELECT *, SUM(CurrentQty) AS CurrentQty  FROM `inventory` i 
                INNER JOIN shop s ON s.SHID=i.shop_SHID
                WHERE i.`products_PDID` ='$product_id' AND s.Company_CMID='$company_id' GROUP BY i.`products_PDID` LIMIT 1";
            }
            $inventoryData = $dbObj->getData($sql); 
            if(isset($inventoryData[0]["CurrentQty"]))
            {
                $filepath="../Assets/Images/prod_images/$row[ProdImage]";
                if(isset($row["ProdImage"]) && $row["ProdImage"]!="" && file_exists("../".$filepath))
                {
                    $filepath="../Assets/Images/prod_images/$row[ProdImage]";
                }
                else
                {
                    $filepath="../Assets/no-image.jpg";
                }
                $procount+=1;
                $inventoryData['CurrentQty'] = $inventoryData[0]['CurrentQty'] * $row['UnitConversion'];
                ?>
                <a href="javascript:void(0)" class="col-md-3 product mb-2 subcat-<?=$row["SCID"]?>">
                    <div class="card product-card position-relative">
                        <div class="d-none">
                            <input type="hidden" name="" id="productid" value="<?=$row["PDID"]?>">
                        </div>
                        <span class="qty-badge"><?=$inventoryData[0]["CurrentQty"]?></span>
                        <?php if($show_images == 1): ?>
                        <div class="product-image">
                            <img src="<?=$filepath?>" alt="product-image" class="w-100 product-img">
                        </div>
                        <div class="product-detail p-2">
                            <p class="product-title"><?=getProductDisplayName($row)?></p>
                            <p class="product-price"><b>Rs. <?=$row["ProdSellPrice"]?></b></p>
                        </div>
                        <?php else: ?>
                        <div class="product-name-tile">
                            <span class="product-name-text"><?=getProductDisplayName($row)?></span>
                            <span class="product-name-price">Rs. <?=$row["ProdSellPrice"]?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php
            }
            else
            {
        
            }
        }
        
        
    }
    ?>
    <?php if(($offset + $limit) < $totalProducts): ?>
    <div class="col-12 lazy-load-sentinel" data-offset="<?=$offset + $limit?>" data-limit="<?=$limit?>" data-mode="allproducts" style="height:1px;"></div>
    <?php endif; ?>
    <script>
        $("#procount").text("<?=$procount?>");
    </script>
    <?php
}
else if(!isset($_GET["product_id"]) && (isset($_GET["subcat"]) || isset($_GET["value"])))
{
    if(isset($_GET["subcat"]))
    {
        $subcat = $_GET["subcat"];
        $add.=" AND sc.SCID='$subcat'";
    }
    if(isset($_GET["value"]))
    {
        $txt_search=$_GET["value"];
        $add.=" AND (p.Barcode LIKE '%".$txt_search."%' OR p.ItemName LIKE '%".$txt_search."%')";
    }
    if($is_multicategory==1)
    {
        $sql = "SELECT p.*, sc.*, s.* FROM `products` p 
        INNER JOIN subcategories sc ON sc.SCID=p.Subcategories_SCID
        INNER JOIN shop s ON s.SHID=p.shop_SHID
        WHERE s.Company_CMID='$company_id' AND p.ProductStat=1 $add ORDER BY p.ItemName,p.ItemType ASC LIMIT $limit OFFSET $offset;";
        $shopie=1;
    }
    else
    {
        $sql = "SELECT p.*, sc.* FROM `products` p 
        INNER JOIN subcategories sc ON sc.SCID=p.Subcategories_SCID
        WHERE p.shop_SHID='$shop_id' AND p.ProductStat=1 $add ORDER BY p.ItemName,p.ItemType ASC LIMIT $limit OFFSET $offset;";
    }
    $productData = $dbObj->getData($sql);

    // Count total for lazy load sentinel
    if($is_multicategory==1)
    {
        $sqlCount2 = "SELECT COUNT(*) AS total FROM `products` p INNER JOIN subcategories sc ON sc.SCID=p.Subcategories_SCID INNER JOIN shop s ON s.SHID=p.shop_SHID WHERE s.Company_CMID='$company_id' AND p.ProductStat=1 $add;";
    }
    else
    {
        $sqlCount2 = "SELECT COUNT(*) AS total FROM `products` p INNER JOIN subcategories sc ON sc.SCID=p.Subcategories_SCID WHERE p.shop_SHID='$shop_id' AND p.ProductStat=1 $add;";
    }
    $totalRow2 = $dbObj->getData($sqlCount2);
    $totalProducts2 = intval($totalRow2[0]['total']);

    $procount = 0;
    foreach ($productData as $row)
    {
        $product_id = $row["PDID"];
        $row["ProdSellPrice"] = tileSellingPrice($row, $shop_id, $company_id, $is_commonStock, $shopdata[0]);
        if($row["ItemType"]=="P")
        {
            $sql2="SELECT *, SUM(CurrentQty) AS CurrentQty FROM `inventory` i WHERE i.`products_PDID`='$product_id' AND i.shop_SHID='$shop_id' GROUP BY i.`products_PDID`";
            if($is_expire)
            {
                $sql2="SELECT *, SUM(CurrentQty) AS CurrentQty FROM `inventory` i
                INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
                WHERE i.`products_PDID`='$product_id' AND i.shop_SHID='$shop_id'
                    AND (ph.ExpDate IS NULL OR ph.ExpDate='0000-00-00' OR ph.ExpDate>'$date')
                GROUP BY i.`products_PDID`";
            }
            if($is_commonStock==1)
            {
                $sql2="SELECT *, SUM(CurrentQty) AS CurrentQty FROM `inventory` i
                INNER JOIN shop s ON s.SHID=i.shop_SHID
                WHERE i.`products_PDID`='$product_id' AND s.Company_CMID='$company_id' GROUP BY i.`products_PDID`";
                if($is_expire)
                {
                    $sql2="SELECT *, SUM(CurrentQty) AS CurrentQty FROM `inventory` i
                    INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
                    INNER JOIN shop s ON s.SHID=i.shop_SHID
                    WHERE i.`products_PDID`='$product_id' AND s.Company_CMID='$company_id'
                        AND (ph.ExpDate IS NULL OR ph.ExpDate='0000-00-00' OR ph.ExpDate>'$date')
                    GROUP BY i.`products_PDID`";
                }
            }
            $invData = $dbObj->getData($sql2);
            $hasStock = isset($invData[0]["CurrentQty"]) && $invData[0]["CurrentQty"] > 0;
            if(!$hasStock && $is_minus==1)
            {
                $sql3="SELECT *, SUM(CurrentQty) AS CurrentQty FROM `inventory` i WHERE i.`products_PDID`='$product_id' AND i.shop_SHID='$shop_id' AND i.is_default=1 GROUP BY i.`products_PDID` LIMIT 1";
                if($is_commonStock==1)
                {
                    $sql3="SELECT *, SUM(CurrentQty) AS CurrentQty FROM `inventory` i
                    INNER JOIN shop s ON s.SHID=i.shop_SHID
                    WHERE i.`products_PDID`='$product_id' AND s.Company_CMID='$company_id' GROUP BY i.`products_PDID` LIMIT 1";
                }
                $invData = $dbObj->getData($sql3);
                $hasStock = isset($invData[0]["CurrentQty"]);
            }
        }
        else
        {
            $sql2="SELECT *, SUM(CurrentQty) AS CurrentQty FROM `inventory` i WHERE i.`products_PDID`='$product_id' AND i.shop_SHID='$shop_id' AND i.is_default=1 GROUP BY i.`products_PDID` LIMIT 1";
            if($is_commonStock==1)
            {
                $sql2="SELECT *, SUM(CurrentQty) AS CurrentQty FROM `inventory` i
                INNER JOIN shop s ON s.SHID=i.shop_SHID
                WHERE i.`products_PDID`='$product_id' AND s.Company_CMID='$company_id' GROUP BY i.`products_PDID` LIMIT 1";
            }
            $invData = $dbObj->getData($sql2);
            $hasStock = isset($invData[0]["CurrentQty"]);
        }

        if($hasStock)
        {
            $filepath = "../Assets/Images/prod_images/".$row['ProdImage'];
            if(empty($row['ProdImage']) || !file_exists("../".$filepath))
            {
                $filepath = "../Assets/no-image.jpg";
            }
            $procount++;
            $displayQty = isset($invData[0]['CurrentQty']) ? ($invData[0]['CurrentQty'] * $row['UnitConversion']) : 0;
            ?>
            <a href="javascript:void(0)" class="col-md-3 product mb-2 subcat-<?=$row["SCID"]?>">
                <div class="card product-card position-relative">
                    <div class="d-none">
                        <input type="hidden" name="" id="productid" value="<?=$row["PDID"]?>">
                    </div>
                    <span class="qty-badge"><?=$displayQty?></span>
                    <?php if($show_images == 1): ?>
                    <div class="product-image">
                        <img src="<?=$filepath?>" alt="product-image" class="w-100 product-img">
                    </div>
                    <div class="product-detail p-2">
                        <p class="product-title"><?=getProductDisplayName($row)?></p>
                        <p class="product-price"><b>Rs. <?=$row["ProdSellPrice"]?></b></p>
                    </div>
                    <?php else: ?>
                    <div class="product-name-tile">
                        <span class="product-name-text"><?=getProductDisplayName($row)?></span>
                        <span class="product-name-price">Rs. <?=$row["ProdSellPrice"]?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php
        }
    }
    ?>
    <?php
    //Nothing of ours matched, or the shop simply does not carry it: offer the warehouse's own
    //catalogue. These are billed now and delivered later, so an item with no stock still shows.
    if(isset($_GET["value"]) && trim($_GET["value"]) !== "" && $offset == 0)
    {
        $warehouseRows = (new WarehouseOrder())->searchSupplier($shop_id, trim($_GET["value"]));
        $alreadyShown = array();
        foreach($productData as $shown)
        {
            $alreadyShown[strtoupper(trim($shown["Barcode"]))] = true;
        }//what this shop already offered
        foreach($warehouseRows as $wh)
        {
            if(isset($alreadyShown[strtoupper(trim($wh["Barcode"]))]))
            {
                continue;
            }//the shop has its own, so it is already on the screen
            $procount++;
            ?>
            <a href="javascript:void(0)" class="col-md-3 product mb-2 warehouse-product" data-warehouse="1" data-supplier-product="<?=(int)$wh["PDID"]?>">
                <div class="card product-card position-relative border-warning">
                    <div class="d-none">
                        <input type="hidden" id="productid" value="<?=(int)$wh["PDID"]?>">
                    </div>
                    <span class="qty-badge bg-warning text-dark">Order</span>
                    <div class="product-name-tile">
                        <span class="product-name-text"><?=htmlspecialchars($wh["ItemName"])?></span>
                        <span class="product-name-price">Rs. <?=$wh["ProdSellPrice"]?></span>
                        <span class="badge bg-warning text-dark d-block mt-1">Delivered from <?=htmlspecialchars($wh["SupplierName"])?></span>
                    </div>
                </div>
            </a>
            <?php
        }//each warehouse item the shop could order
    }//the warehouse catalogue
    ?>
    <?php if(($offset + $limit) < $totalProducts2): ?>
    <div class="col-12 lazy-load-sentinel" data-offset="<?=$offset + $limit?>" data-limit="<?=$limit?>" data-subcat="<?=isset($_GET['subcat']) ? htmlspecialchars($_GET['subcat']) : ''?>" data-value="<?=isset($_GET['value']) ? htmlspecialchars($_GET['value']) : ''?>" style="height:1px;"></div>
    <?php endif; ?>
    <script>
        (function(){ var el=document.getElementById('procount'); if(el) el.textContent='<?=$procount?>'; })();
    </script>
    <?php
}
elseif (isset($_GET["product_id"])) {
    //A product the warehouse holds, not this shop. It is billed now and delivered later, so it
    //has no stock here and no batch price - the warehouse's own selling price is what the
    //customer pays, and the quantity is not capped by a shelf.
    if(isset($_GET["warehouse"]))
    {
        $wh = (new WarehouseOrder())->supplierProduct($shop_id, $_GET["product_id"]);
        if($wh === null)
        {
            returnError("00005", "That item is not available to order");
        }
        if((float)$wh["UnitConversion"] <= 0)
        {
            $wh["UnitConversion"] = 1.000;
        }
        echo json_encode([
            "product" => $wh,
            "discountType" => 1,
            "discount" => 0,
            "unitPrice" => $wh["ProdSellPrice"],
            "cost" => $wh["ProdPurchasePrice"],
            "warehouse" => 1,
            "supplier_product_id" => (int)$wh["PDID"],
            "supplier_name" => $wh["SupplierName"],
            "supplier_shop_id" => (int)$wh["SupplierShopID"],
            "inventory" => [[
                "INID" => 0,
                "BatchID" => "",
                "SellingPrice" => $wh["ProdSellPrice"],
                "PurchasePrice" => $wh["ProdPurchasePrice"],
                "TotalCurrentQty" => 99999,
            ]],
        ]);
        exit;
    }//a warehouse item


    $product_id = $_GET["product_id"];
    $shopie = 0;
    $sql = "SELECT * FROM `products` p ";
    if ($is_multicategory == 1 || $is_commonStock == 1) {
        $sql .= "INNER JOIN shop s ON s.SHID=p.shop_SHID WHERE s.Company_CMID='$company_id'";
        $shopie = 1;
    } else {
        $sql .= "WHERE p.shop_SHID='$shop_id'";
    }
    $sql .= " AND p.PDID='$product_id' AND p.ProductStat=1 ORDER BY p.ItemName, p.ItemType ASC";
    
    $productData = $dbObj->getData($sql);
    if (!$productData) returnError("00004", "Product not found");
    
    $row = $productData[0];

   
    
    if ($row["ItemType"] == "P") {
        $stockType = $shopdata[0]["StockTypes_STID"];
        $is_minus = $shopdata[0]["is_minus"] ?? false;
        $is_expire = $shopdata[0]["is_expire"] ?? false;
        
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
        // Return response
        echo json_encode([
            "product" => $row,
            "discountType" => $discountType,
            "discount" => $discount,
            "unitPrice" => $inventoryData[0]["SellingPrice"],
            "cost" => $inventoryData[0]["PurchasePrice"],
            "inventory" => $inventoryData
        ]);
    }
    else
    {
        $stockType = $shopdata[0]["StockTypes_STID"];
        $is_minus = $shopdata[0]["is_minus"] ?? false;
        
        $inventoryData = fetchInventoryData(product_id: $product_id, shop_id: $shop_id, company_id: $company_id, is_commonStock: $is_commonStock, stockType: $stockType, is_default: true,is_expire: false,is_minus:0);
        
        if (!$inventoryData) returnError("00003", "No Stock Available");
        
        // Calculate discount
        list($discountType, $discount, $subtotal) = calculateDiscount($inventoryData[0]["SellingPrice"], $row);
        if($row['UnitConversion']==0.000 || $row['UnitConversion']==0 || $row['UnitConversion']=="0.000")
        {
            $row['UnitConversion']=1.000;
        }
        $inventoryData[0]["TotalCurrentQty"]=$inventoryData[0]["TotalCurrentQty"]*1;
        // Return response
        echo json_encode([
            "product" => $row,
            "discountType" => $discountType,
            "discount" => $discount,
            "unitPrice" => $inventoryData[0]["SellingPrice"],
            "cost" => $inventoryData[0]["PurchasePrice"],
            "inventory" => $inventoryData
        ]);
    }
}

/**
 * The unit price the cart will charge for this product - the same batch rule as the product_id branch above
 * (first batch in line with stock, the default row only where the shop sells below zero) - so a tile can never
 * show a different price from the one charged. Falls back to the product record when there is no stock price.
 */
function tileSellingPrice($row, $shop_id, $company_id, $is_commonStock, $shop) {
    $stockType = $shop["StockTypes_STID"];
    if ($row["ItemType"] == "P") {
        $is_expire = $shop["is_expire"] ?? false;
        $inventoryData = fetchInventoryData($row["PDID"], $shop_id, $company_id, $is_commonStock, $stockType, false, $is_expire, false);
        if (!$inventoryData && !empty($shop["is_minus"])) {
            $inventoryData = fetchInventoryData($row["PDID"], $shop_id, $company_id, $is_commonStock, $stockType, true, $is_expire, true);
        }
    } else {
        $inventoryData = fetchInventoryData($row["PDID"], $shop_id, $company_id, $is_commonStock, $stockType, true, false, 0);
    }
    return $inventoryData ? $inventoryData[0]["SellingPrice"] : $row["ProdSellPrice"];
}

/**
 * Fetch inventory data based on stock type.
 */
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


/**
 * Calculate discount based on percentage or flat amount.
 */
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

/**
 * Return JSON error response and exit script.
 */
function returnError($code, $message) {
    echo json_encode(["error" => true, "code" => $code, "message" => $message]);
    exit;
}

?>