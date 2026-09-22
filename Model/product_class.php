<?php 

class Product extends Dbh
{
    public function setProduct($ProductNo, $ProdImage, $Barcode, $ItemName, $ProdDescription, $SecondName, $ProdPurchasePrice, $ProdSellPrice, $CartonQty, $ProductStat, $AddedDate, $UpdatedDate, $ItemType, $user_USID, $UpdateUserID, $Subcategories_SCID, $shop_SHID, $PurchaseUnit, $UnitConversion, $SellingUnit, $prod_Discount = 0 , $Flat_discount = 0,$chk_fp=1)
    {
        try 
        {
            $sql="INSERT INTO products(ProductNo, ProdImage, Barcode, ItemName, ProdDescription, SecondName, ProdPurchasePrice, ProdSellPrice, CartonQty, ProductStat, AddedDate, UpdatedDate, ItemType, user_USID, UpdateUserID, Subcategories_SCID, shop_SHID, PurchaseUnit, UnitConversion, SellingUnit, prodDiscount, prodFlatDiscount, is_fixedPrice) VALUES('$ProductNo', '$ProdImage', '$Barcode', '$ItemName', '$ProdDescription', '$SecondName', '$ProdPurchasePrice', '$ProdSellPrice', '$CartonQty', '$ProductStat', '$AddedDate', '$UpdatedDate', '$ItemType', '$user_USID', '$UpdateUserID', '$Subcategories_SCID', '$shop_SHID', '$PurchaseUnit', '$UnitConversion', '$SellingUnit', '$prod_Discount' , '$Flat_discount', '$chk_fp');";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $sql;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set product

    public function getSubCat($name)
    {
        try
        {
            $shop_SHID = $_SESSION["shop_id"];
            $sql = "SELECT * FROM subcategories sc INNER JOIN categories c ON c.CTID=sc.categories_CTID WHERE sc.SubCatName='$name' AND c.shop_SHID='$shop_SHID';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table getSubCat" . $e->getMessage());
        }
    }
    //the product record carries the price the item currently sells at - the POS product tiles, the
    //product list and label printing read it. Called when stock arrives (GRN / transfer verified);
    //a zero price on the incoming stock never overwrites a real one.
    public function setCurrentPrices($product_id, $purchase_price, $selling_price)
    {
        try
        {
            $sql = "UPDATE products SET ProdPurchasePrice = IF(? > 0, ?, ProdPurchasePrice), ProdSellPrice = IF(? > 0, ?, ProdSellPrice) WHERE PDID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$purchase_price, $purchase_price, $selling_price, $selling_price, $product_id]);
        }
        catch (PDOException $e)
        {
            die("Error: Unable to update product prices: " . $e->getMessage());
        }
    }//set current prices
    public function checkbarcode($barcode)
    {
        try
        {
            $shop_SHID = $_SESSION["shop_id"];
            $sql = "SELECT * FROM `products` WHERE Barcode=? AND shop_SHID=?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$barcode,$shop_SHID]);
            $code=$stmt->fetchAll();
            if(count($code)==0)
            {
                return true;
            }
            else 
            {
                return false;
            }
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }
    public function checkproduct($name)
    {
        try
        {
            $shop_SHID = $_SESSION["shop_id"];
            $sql = "SELECT * FROM `products` WHERE ItemName=? AND shop_SHID=?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$name,$shop_SHID]);
            $code=$stmt->fetchAll();
            if(count($code)==0) { return true; }
            else { return false; }
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }

    public function editProduct($ProdImage, $Barcode, $ItemName, $ProdDescription, $SecondName, $ProdPurchasePrice, $ProdSellPrice, $CartonQty, $UpdatedDate, $ItemType, $UpdateUserID, $Subcategories_SCID, $shop_SHID, $PurchaseUnit, $UnitConversion, $SellingUnit, $PDID, $prodDiscount,$prodStatus,$prodFlatDiscount,$chk_fp)
    {
        try 
        {
            $sql="UPDATE products SET ProdImage='$ProdImage', Barcode='$Barcode', ItemName='$ItemName', ProdDescription='$ProdDescription', SecondName='$SecondName', ProdPurchasePrice='$ProdPurchasePrice', ProdSellPrice='$ProdSellPrice', CartonQty='$CartonQty', UpdatedDate='$UpdatedDate', ItemType='$ItemType', UpdateUserID='$UpdateUserID', Subcategories_SCID='$Subcategories_SCID', shop_SHID='$shop_SHID', PurchaseUnit='$PurchaseUnit', UnitConversion='$UnitConversion', SellingUnit='$SellingUnit', prodDiscount='$prodDiscount',ProductStat='$prodStatus', prodFlatDiscount='$prodFlatDiscount', is_fixedPrice='$chk_fp' WHERE PDID='$PDID';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $sql;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set product

    public function getProductCount()
    {
        try
        {
            $sql = "SELECT max(PDID) AS ProductCount FROM products;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    public function getProductImageCount()                                                    
    {
        try
        {
            $sql = "SELECT COUNT(PDID) AS ProducImagetCount FROM products;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    public function getOneProduct($product_id)
    {
        try
        {
            $sql = "SELECT * FROM products 
            INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
            INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
            WHERE PDID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$product_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    public function getAllProduct()
    {
        try
        {
            $sql = "SELECT * FROM products 
            INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
            INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
            WHERE ProductStat = 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    public function getProductByBarcode($barcode)
    {
        try
        {
            $sql = "SELECT *,categories.CTID AS cat_ID FROM products 
            INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
            INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
            WHERE products.Barcode = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$barcode]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }

    public function getProductByShop($shop_id)
    {
        try
        {
            $sql = "SELECT *,categories.CTID AS cat_ID FROM products 
            INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
            INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
            WHERE products.shop_SHID = ? LIMIT 50;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

//================================ variations =================================//
public function setVariation($VariationName, $products_PDID)
{
    try 
    {
        $sql="INSERT INTO variations(VariationName, products_PDID) VALUES(?,?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$VariationName, $products_PDID]);
    }//try 
    catch (PDOException $e) 
    {
        die("Error: Unable to insert data: " . $e->getMessage());
    }//catch
}//set product

public function editVariation($VariationName, $variation_id)
{
    try 
    {
        $sql="UPDATE variations SET VariationName = ? WHERE VRID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$VariationName, $variation_id]);
    }//try 
    catch (PDOException $e) 
    {
        die("Error: Unable to insert data: " . $e->getMessage());
    }//catch
}//set product

public function getqtycount($shop_id,$product_id) 
{
    try
        {
            $sql = "SELECT *, COUNT(CurrentQty) AS count_qty FROM `inventory`
            INNER JOIN products ON inventory.products_PDID=products.PDID
                        WHERE (inventory.products_PDID=? OR products.Barcode=?) AND inventory.shop_SHID=? AND inventory.CurrentQty>0";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$product_id,$product_id,$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    
}

public function getproductwithinventorypricehistory($product_id,$shop_id)
{
    try
    {
        $sql = "SELECT p.*,i.*, ph.*, ph.PHID AS price_id FROM `products` p 
        INNER JOIN pricehistory ph ON ph.ProductID=p.PDID
        INNER JOIN inventory i ON i.INID=ph.Inventory_INID
        WHERE i.shop_SHID=? && (p.PDID=? OR p.Barcode=?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$shop_id,$product_id,$product_id]);
        return $stmt->fetchAll();
    }
    catch(PDOException $e)
    {
        die("Error: Unable to read from table" . $e->getMessage());
    }
}

public function get_products_with_barcode($barcode,$shop_id)
{
    try
    {
        $sql = "SELECT ph.*,p.*,i.* FROM `pricehistory` ph
        INNER JOIN inventory i ON ph.Inventory_INID=i.INID
        INNER JOIN products p ON p.PDID = i.products_PDID
        WHERE p.Barcode=?
        AND i.shop_SHID=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$barcode,$shop_id]);
        return $stmt->fetchAll();
    }
    catch(PDOException $e)
    {
        die("Error: Unable to read from table" . $e->getMessage());
    }

}
public function getproductwithinventorypricehistory2($product_id,$shop_id,$price_id)
{
    try
    {
        $sql = "SELECT p.*,i.*, ph.*, ph.PHID AS price_id FROM `products` p 
        INNER JOIN pricehistory ph ON ph.ProductID=p.PDID
        INNER JOIN inventory i ON i.INID=ph.Inventory_INID
        WHERE i.shop_SHID=? && (p.PDID=? OR p.Barcode=?)&& ph.PHID=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$shop_id,$product_id,$product_id,$price_id]);
        return $stmt->fetchAll();
    }
    catch(PDOException $e)
    {
        die("Error: Unable to read from table" . $e->getMessage());
    }
}

public function getpricehistorywithinventory($product_id,$shop_id)
{
    try
        {
            $sql = "SELECT * FROM `pricehistory` ph
            INNER JOIN inventory i ON ph.Inventory_INID=i.INID
            INNER JOIN products p ON p.PDID = i.products_PDID
            WHERE (p.Barcode=? OR p.PDID=?) AND i.shop_SHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$product_id,$product_id,$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table" . $e->getMessage());
        }
}

public function getproductswithinventory($shop_id)
{
    try
        {
            $sql = "WITH MaxInventory AS (
                SELECT 
                    products_PDID,
                    SUM(CurrentQty) AS MaxQty
                FROM 
                    inventory
                WHERE 
                    shop_SHID = ?
                GROUP BY 
                    products_PDID
            )
            SELECT 
                p.*,
                MAX(ph.SellingPrice) AS MAXPRICE,
                i.CurrentQty AS qty,
                c.CTID AS cat_ID,
                mi.MaxQty AS sum_qty
            FROM 
                products p
                INNER JOIN subcategories sc ON sc.SCID = p.Subcategories_SCID
                INNER JOIN categories c ON c.CTID = sc.categories_CTID
                INNER JOIN inventory i ON i.products_PDID = p.PDID
                INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
                INNER JOIN MaxInventory mi ON mi.products_PDID = i.products_PDID 
            WHERE 
                i.shop_SHID = ? GROUP BY p.PDID;"; 

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id,$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
}

public function getproductswithinventory_paged($shop_id, $offset=0, $limit=40, $value=null, $subcat=null)
{
    try
    {
        $params = [$shop_id, $shop_id];
        $where_extra = "";

        if (!empty($value)) {
            $where_extra .= " AND (p.ItemName LIKE ? OR p.Barcode LIKE ?)";
            $params[] = "%$value%";
            $params[] = "%$value%";
        }
        if (!empty($subcat)) {
            $where_extra .= " AND sc.SCID = ?";
            $params[] = $subcat;
        }

        $params[] = $limit;
        $params[] = $offset;

        $sql = "WITH MaxInventory AS (
            SELECT products_PDID, SUM(CurrentQty) AS MaxQty
            FROM inventory WHERE shop_SHID = ? GROUP BY products_PDID
        )
        SELECT p.*, MAX(ph.SellingPrice) AS MAXPRICE, i.CurrentQty AS qty,
            c.CTID AS cat_ID, mi.MaxQty AS sum_qty
        FROM products p
            INNER JOIN subcategories sc ON sc.SCID = p.Subcategories_SCID
            INNER JOIN categories c ON c.CTID = sc.categories_CTID
            INNER JOIN inventory i ON i.products_PDID = p.PDID
            INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
            INNER JOIN MaxInventory mi ON mi.products_PDID = i.products_PDID
        WHERE i.shop_SHID = ? $where_extra
        GROUP BY p.PDID
        LIMIT ? OFFSET ?;";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    catch(PDOException $e)
    {
        die("Error: Unable to read from table " . $e->getMessage());
    }
}

public function getproductswithinventory_count($shop_id, $value=null, $subcat=null)
{
    try
    {
        $params = [$shop_id, $shop_id];
        $where_extra = "";

        if (!empty($value)) {
            $where_extra .= " AND (p.ItemName LIKE ? OR p.Barcode LIKE ?)";
            $params[] = "%$value%";
            $params[] = "%$value%";
        }
        if (!empty($subcat)) {
            $where_extra .= " AND sc.SCID = ?";
            $params[] = $subcat;
        }

        $sql = "WITH MaxInventory AS (
            SELECT products_PDID, SUM(CurrentQty) AS MaxQty
            FROM inventory WHERE shop_SHID = ? GROUP BY products_PDID
        )
        SELECT COUNT(DISTINCT p.PDID) AS total
        FROM products p
            INNER JOIN subcategories sc ON sc.SCID = p.Subcategories_SCID
            INNER JOIN categories c ON c.CTID = sc.categories_CTID
            INNER JOIN inventory i ON i.products_PDID = p.PDID
            INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
            INNER JOIN MaxInventory mi ON mi.products_PDID = i.products_PDID
        WHERE i.shop_SHID = ? $where_extra;";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return intval($row['total']);
    }
    catch(PDOException $e)
    {
        die("Error: Unable to read from table " . $e->getMessage());
    }
}

public function getVariationByProduct($product_id)  
{
    try
    {
        $sql = "SELECT * FROM variations WHERE products_PDID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$product_id]);
        return $stmt->fetchAll();
    }
    catch(PDOException $e)
    {
        die("Error: Unable to read from table " . $e->getMessage());
    }
}//get category count

public function getBatchesByProduct($product_id)  
{
    try
    {
        $sql = "SELECT *,IV.BatchID AS BatchID FROM inventory IV 
        INNER JOIN pricehistory PH ON IV.INID = PH.Inventory_INID AND IV.BatchID = PH.BatchID 
        WHERE IV.products_PDID = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$product_id]);
        return $stmt->fetchAll();
    }
    catch(PDOException $e)
    {
        die("Error: Unable to read from table " . $e->getMessage());
    }
}//get Batch

public function getBatchesByProductVariation($product_id,$variation_id)  
{
    try
    {
        $sql = "SELECT * FROM inventory IV INNER JOIN pricehistory PH ON IV.INID = PH.Inventory_INID AND IV.BatchID = PH.BatchID WHERE products_PDID = ? AND PH.VariationID = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$product_id,$variation_id]);
        return $stmt->fetchAll();
    }
    catch(PDOException $e)
    {
        die("Error: Unable to read from table " . $e->getMessage());
    }
}//get Batch by Variation

public function getSpecificByProductAndBatch($product_id,$batch_id)  
{
    try
    {
        $sql = "SELECT PH.PurchasePrice AS PurchasePrice,PH.MnfDate,PH.ExpDate,IV.INID,IV.CurrentQty FROM inventory IV INNER JOIN pricehistory PH ON IV.INID = PH.Inventory_INID AND IV.BatchID = PH.BatchID WHERE IV.products_PDID = ? AND IV.BatchID = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$product_id,$batch_id]);
        return $stmt->fetchAll();
    }
    catch(PDOException $e)
    {
        die("Error: Unable to read from table " . $e->getMessage());
    }
}//get Batch

}//class Product