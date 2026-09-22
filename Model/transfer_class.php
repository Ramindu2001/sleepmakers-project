<?php

class Transfer extends Dbh
{
    public function setTransfer($TransferNo, $EffectiveDate, $TransferFrom, $TransferTo, $TransferTotalCount, $TransferTotalAmount, $TransferStat, $shop_SHID, $user_USID)
    {
        try 
        {
            $sql="INSERT INTO transferheader(TransferNo, EffectiveDate, TransferFrom, TransferTo, TransferTotalCount, TransferTotalAmount, TransferStat, shop_SHID, user_USID) VALUES(?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$TransferNo, $EffectiveDate, $TransferFrom, $TransferTo, $TransferTotalCount, $TransferTotalAmount, $TransferStat, $shop_SHID, $user_USID]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set transfer header

    public function checktransferID($INID,$transferID)
    {
        try{
            $sql = "SELECT count(*) AS transferdetailsCount FROM transferdetails WHERE InventoryID = '$INID' AND TransferHeader_THID='$transferID';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            $row=$stmt->fetchAll();
            if($row[0]["transferdetailsCount"]>0)
            {
                return false;
            }
            return true;
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }

    public function editTransferHeaderStat($transfer_header_stat, $transfer_header_id)
    {
        try 
        {
            $sql="UPDATE transferheader SET TransferStat = ? WHERE THID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$transfer_header_stat, $transfer_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit transfer header

    //move the header to a new status only while it is still in one of $allowed_stats;
    //returns 0 when the transfer had already moved on (verified / cancelled / double submit)
    public function editTransferHeaderStatIfIn($transfer_header_stat, $transfer_header_id, $allowed_stats)
    {
        try
        {
            $placeholders = implode(',', array_fill(0, count($allowed_stats), '?'));
            $sql="UPDATE transferheader SET TransferStat = ? WHERE THID = ? AND TransferStat IN (".$placeholders.");";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array_merge([$transfer_header_stat, $transfer_header_id], $allowed_stats));
            return $stmt->rowCount();
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to update data: " . $e->getMessage());
        }//catch
    }//edit transfer header stat if still allowed

    //lines of a transfer may only change while it is on hold (0) or pending (1),
    //and only from one of the two shops taking part in it; returns the header row or null
    public function getEditableTransferForShop($transfer_header_id, $shop_id)
    {
        try{
            $sql = "SELECT * FROM transferheader WHERE THID = ? AND TransferStat IN (0,1) AND (TransferFrom = ? OR TransferTo = ?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$transfer_header_id, $shop_id, $shop_id]);
            $rows = $stmt->fetchAll();
            return empty($rows) ? null : $rows[0];
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get editable transfer for shop

    //Mnf / Exp date of a transfer line: a real date (Y-m-d) or null = no date. An empty date is never
    //replaced with today's date: a shop that doesn't track expiry has no date fields on the transfer
    //page, and a receiving shop that does track expiry treats "expires today" as already expired
    public function cleanDate($value)
    {
        $value = trim((string)$value);
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }//clean date

    //transaction helpers on the shared connection (the DBTransactions versions echo text,
    //which would break the redirects that follow)
    public function beginDbTransaction()
    {
        $this->connect()->beginTransaction();
    }

    public function commitDbTransaction()
    {
        $this->connect()->commit();
    }

    public function rollbackDbTransaction()
    {
        if($this->connect()->inTransaction())
        {
            $this->connect()->rollBack();
        }
    }

    public function editTransferTotals($transfer_date, $row_count, $transfer_amount, $transfer_header_id)
    {
        try 
        {
            $sql="UPDATE transferheader SET EffectiveDate=?, TransferTotalCount =?, TransferTotalAmount=? WHERE THID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$transfer_date, $row_count, $transfer_amount, $transfer_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit transfer header totals

    public function getTransferMax($shop_id)
    {
        try{
            $sql = "SELECT max(THID) as maxTransfer FROM transferheader WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get company type

    public function getAllTransfer($shop_id)
    {
        try{
            $sql = "SELECT * FROM transferheader 
            INNER JOIN user ON user.USID = transferheader.user_USID
            WHERE shop_SHID = ? ORDER BY THID DESC;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get company type

    public function getOneTransferHeader($transfer_header_id)
    {
        try{
            $sql = "SELECT * FROM transferheader
            INNER JOIN user ON user.USID = transferheader.user_USID
            WHERE THID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$transfer_header_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get one transfer header

//===============================================================================================//
//================================ Product in the receiving shop ================================//
//===============================================================================================//
    //Every shop has its own product list (products.shop_SHID), unless the company shares one list
    //between its shops (company.is_multicategory = 1). Stock that arrives in a shop has to be booked
    //against that shop's own product, otherwise the shop's POS, product list and barcode search never
    //see it. Returns the product id to use in the receiving shop: the same product when the list is
    //shared or the shop already owns it, else the shop's product with the same barcode and item name
    //(the rule the stock upload uses), else a copy of the product created in that shop.
    //Must run inside the verify transaction, so a failed transfer leaves no product behind.
    public function getDestinationProductID($source_product_id, $dest_shop_id, $user_id)
    {
        try
        {
            $pdo = $this->connect();

            $stmt = $pdo->prepare("SELECT company.is_multicategory FROM shop INNER JOIN company ON company.CMID = shop.Company_CMID WHERE shop.SHID = ?;");
            $stmt->execute([$dest_shop_id]);
            $shopRows = $stmt->fetchAll();
            if(!empty($shopRows) && $shopRows[0]['is_multicategory'] == 1)
            {
                return $source_product_id;
            }//one product list for the whole company

            $stmt = $pdo->prepare("SELECT * FROM products WHERE PDID = ?;");
            $stmt->execute([$source_product_id]);
            $prodRows = $stmt->fetchAll();
            if(empty($prodRows))
            {
                return 0;
            }//unknown product
            $source = $prodRows[0];

            if($source['shop_SHID'] == $dest_shop_id)
            {
                return $source_product_id;
            }//the receiving shop already owns it

            //one transfer at a time creates products in a shop, so two verified together can't both add it
            $stmt = $pdo->prepare("SELECT SHID FROM shop WHERE SHID = ? FOR UPDATE;");
            $stmt->execute([$dest_shop_id]);

            //locking read: sees products committed after this transaction's first read
            $stmt = $pdo->prepare("SELECT PDID FROM products WHERE shop_SHID = ? AND Barcode <=> ? AND ItemName <=> ? AND ItemType <=> ? ORDER BY ProductStat DESC, PDID ASC LIMIT 1 LOCK IN SHARE MODE;");
            $stmt->execute([$dest_shop_id, $source['Barcode'], $source['ItemName'], $source['ItemType']]);
            $matchRows = $stmt->fetchAll();
            if(!empty($matchRows))
            {
                return $matchRows[0]['PDID'];
            }//the shop already has this product

            $subcat_id = $this->getDestinationSubcategoryID($source['Subcategories_SCID'], $dest_shop_id);
            $this_date = date("Y-m-d");

            $sql = "INSERT INTO products(ProductNo, ProdImage, Barcode, ItemName, ProdDescription, SecondName, ProdPurchasePrice, ProdSellPrice, CartonQty, ProductStat, AddedDate, UpdatedDate, ItemType, user_USID, UpdateUserID, Subcategories_SCID, shop_SHID, PurchaseUnit, UnitConversion, SellingUnit, prodDiscount, prodFlatDiscount, is_fixedPrice) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['', null, $source['Barcode'], $source['ItemName'], $source['ProdDescription'], $source['SecondName'], $source['ProdPurchasePrice'], $source['ProdSellPrice'], $source['CartonQty'], 1, $this_date, $this_date, $source['ItemType'], $user_id, $user_id, $subcat_id, $dest_shop_id, $source['PurchaseUnit'], $source['UnitConversion'], $source['SellingUnit'], $source['prodDiscount'], $source['prodFlatDiscount'], $source['is_fixedPrice']]);
            $new_product_id = $pdo->lastInsertId();

            //product number from the real id, and an image file of its own (editing a product's image
            //deletes the old file, so the two shops must not share one)
            $commObj = new Common();
            $product_no = $commObj->createCount("PD", $new_product_id);
            $image_name = $this->copyProductImage($source['ProdImage'], $commObj->createCount("PI", $new_product_id));
            $stmt = $pdo->prepare("UPDATE products SET ProductNo = ?, ProdImage = ? WHERE PDID = ?;");
            $stmt->execute([$product_no, $image_name, $new_product_id]);

            //default stock row with price history, the same way a product created in the shop starts
            $batch_id = "B" . sprintf("%'.09d", 1);
            $stmt = $pdo->prepare("INSERT INTO inventory(CurrentQty, BillQty, ReturnQty, TransferInQty, TransferOutQty, products_PDID, shop_SHID, RackID, BatchID, is_default) VALUES(?,?,?,?,?,?,?,?,?,?);");
            $stmt->execute([0, 0, 0, 0, 0, $new_product_id, $dest_shop_id, 1, $batch_id, 1]);
            $default_inventory_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO pricehistory(ProductID, VariationID, EffectiveDate, PurchasePrice, SellingPrice, labelPrice, MnfDate, ExpDate, BatchID, Inventory_INID, GrnDetailID) VALUES(?,?,?,?,?,?,?,?,?,?,?);");
            $stmt->execute([$new_product_id, 0, $this_date, $source['ProdPurchasePrice'], $source['ProdSellPrice'], $source['ProdSellPrice'], null, null, $batch_id, $default_inventory_id, 0]);

            return $new_product_id;
        }//try
        catch(PDOException $e)
        {
            die("Error: Unable to add the product to the receiving shop: " . $e->getMessage());
        }//catch
    }//get destination product id

    //the receiving shop's subcategory with the same category and subcategory names (created when missing)
    private function getDestinationSubcategoryID($source_subcat_id, $dest_shop_id)
    {
        $pdo = $this->connect();
        $commObj = new Common();

        $stmt = $pdo->prepare("SELECT subcategories.SubCatName, categories.CategoryName FROM subcategories INNER JOIN categories ON categories.CTID = subcategories.categories_CTID WHERE subcategories.SCID = ?;");
        $stmt->execute([$source_subcat_id]);
        $names = $stmt->fetchAll();
        $category_name = empty($names) ? "Transferred" : $names[0]['CategoryName'];
        $subcat_name = empty($names) ? "Transferred" : $names[0]['SubCatName'];

        $stmt = $pdo->prepare("SELECT CTID FROM categories WHERE shop_SHID = ? AND CategoryName = ? ORDER BY CTID ASC LIMIT 1 LOCK IN SHARE MODE;");
        $stmt->execute([$dest_shop_id, $category_name]);
        $catRows = $stmt->fetchAll();
        if(!empty($catRows))
        {
            $category_id = $catRows[0]['CTID'];
        }//has category
        else
        {
            //numbered like the category form does
            $stmt = $pdo->prepare("SELECT max(CTID) AS CategoryCount FROM categories WHERE shop_SHID = ?;");
            $stmt->execute([$dest_shop_id]);
            $countRows = $stmt->fetchAll();
            $category_no = $commObj->createCount("MC", intval($countRows[0]['CategoryCount']) + 1);

            $stmt = $pdo->prepare("INSERT INTO categories(CategoryNo, CategoryName, shop_SHID) VALUES(?,?,?);");
            $stmt->execute([$category_no, $category_name, $dest_shop_id]);
            $category_id = $pdo->lastInsertId();
        }//new category

        $stmt = $pdo->prepare("SELECT SCID FROM subcategories WHERE categories_CTID = ? AND SubCatName = ? ORDER BY SCID ASC LIMIT 1 LOCK IN SHARE MODE;");
        $stmt->execute([$category_id, $subcat_name]);
        $subRows = $stmt->fetchAll();
        if(!empty($subRows))
        {
            return $subRows[0]['SCID'];
        }//has subcategory

        //numbered like the subcategory form does
        $stmt = $pdo->prepare("SELECT max(SCID) AS SubcatCount FROM subcategories INNER JOIN categories ON categories.CTID = subcategories.categories_CTID WHERE shop_SHID = ?;");
        $stmt->execute([$dest_shop_id]);
        $countRows = $stmt->fetchAll();
        $subcat_no = $commObj->createCount("SC", intval($countRows[0]['SubcatCount']) + 1);

        $stmt = $pdo->prepare("INSERT INTO subcategories(SubCatNo, SubCatName, categories_CTID) VALUES(?,?,?);");
        $stmt->execute([$subcat_no, $subcat_name, $category_id]);
        return $pdo->lastInsertId();
    }//get destination subcategory id

    //copy of the product image under the new product's own name; null when there is no image file
    private function copyProductImage($image_name, $new_base_name)
    {
        $image_dir = __DIR__ . "/../Assets/Images/prod_images/";
        if(empty($image_name) || !is_file($image_dir . basename($image_name)))
        {
            return null;
        }//no image

        $extension = pathinfo($image_name, PATHINFO_EXTENSION);
        $new_name = $new_base_name . "." . $extension;
        if(file_exists($image_dir . $new_name))
        {
            $new_name = $new_base_name . "_" . uniqid() . "." . $extension;
        }//never overwrite another product's image

        return copy($image_dir . basename($image_name), $image_dir . $new_name) ? $new_name : null;
    }//copy product image

    //the variation of the receiving shop's product with the same name (created when missing)
    public function getDestinationVariationID($variation_id, $source_product_id, $dest_product_id)
    {
        if(intval($variation_id) <= 0 || $dest_product_id == $source_product_id)
        {
            return $variation_id;
        }//no variation, or the same product

        try
        {
            $pdo = $this->connect();
            $stmt = $pdo->prepare("SELECT VariationName FROM variations WHERE VRID = ?;");
            $stmt->execute([$variation_id]);
            $varRows = $stmt->fetchAll();
            if(empty($varRows))
            {
                return 0;
            }//unknown variation

            $stmt = $pdo->prepare("SELECT VRID FROM variations WHERE products_PDID = ? AND VariationName = ? ORDER BY VRID ASC LIMIT 1 LOCK IN SHARE MODE;");
            $stmt->execute([$dest_product_id, $varRows[0]['VariationName']]);
            $destRows = $stmt->fetchAll();
            if(!empty($destRows))
            {
                return $destRows[0]['VRID'];
            }//has variation

            $stmt = $pdo->prepare("INSERT INTO variations(VariationName, products_PDID) VALUES(?,?);");
            $stmt->execute([$varRows[0]['VariationName'], $dest_product_id]);
            return $pdo->lastInsertId();
        }//try
        catch(PDOException $e)
        {
            die("Error: Unable to add the variation to the receiving shop: " . $e->getMessage());
        }//catch
    }//get destination variation id

//===============================================================================================//
//======================================= Transfer Detail =======================================//
//===============================================================================================//

    public function setTransferDetail($TransferQty, $ReceivedQty, $UnitPurchasePrice, $UnitSellingPrice, $MnfDate, $ExpDate, $TransferTotalAmount, $InventoryID, $products_PDID, $VariationID, $RackID, $TransferStat, $TransferHeader_THID, $Batch_ID)
    {
        try 
        {
            $sql="INSERT INTO transferdetails(TransferQty, ReceivedQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate, TransferTotalAmount, InventoryID, products_PDID, VariationID, RackID, TransferStat, TransferHeader_THID, Batch_ID) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$TransferQty, $ReceivedQty, $UnitPurchasePrice, $UnitSellingPrice, $MnfDate, $ExpDate, $TransferTotalAmount, $InventoryID, $products_PDID, $VariationID, $RackID, $TransferStat, $TransferHeader_THID, $Batch_ID]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set category

    public function editTransferDetail($TransferQty, $ReceivedQty, $UnitPurchasePrice, $UnitSellingPrice, $MnfDate, $ExpDate, $TransferTotalAmount, $InventoryID, $products_PDID, $VariationID, $RackID, $Batch_ID, $transfer_detail_id)
    {
        try 
        {
            $sql="UPDATE transferdetails SET TransferQty=?, ReceivedQty=?, UnitPurchasePrice=?, UnitSellingPrice=?, MnfDate=?, ExpDate=?, TransferTotalAmount=?, InventoryID=?, products_PDID=?, VariationID=?, RackID=?, Batch_ID=? WHERE TDID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$TransferQty, $ReceivedQty, $UnitPurchasePrice, $UnitSellingPrice, $MnfDate, $ExpDate, $TransferTotalAmount, $InventoryID, $products_PDID, $VariationID, $RackID, $Batch_ID, $transfer_detail_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit transfer detail

    public function editTransferDetailStat($transfer_detail_stat, $transfer_detail_id)
    {
        try 
        {
            $sql="UPDATE transferdetails SET TransferStat=? WHERE TransferHeader_THID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$transfer_detail_stat, $transfer_detail_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit transfer header

    public function deleteTransferDetail($transfer_detail_id)
    {
        try 
        {
            $sql="DELETE FROM transferdetails WHERE TDID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$transfer_detail_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit transfer detail

    public function getTransferDetailByHeader($transfer_header_id)
    {
        try
        {
            $sql = "SELECT * FROM transferdetails WHERE TransferHeader_THID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$transfer_header_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get transfer detail by header

//===============================================================================================//
//==================================== Transfer Transaction =====================================//
//===============================================================================================//
    public function setTransferTransaction($TrnTransactionAmount, $TrnTransactionStat, $transfer_header_id, $paymethod_id)
    {
        try 
        {
            $sql="INSERT INTO transfertransactions(TrnTransactionAmount, TrnTransactionStat, transfer_header_id, paymethod_id) VALUES (?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$TrnTransactionAmount, $TrnTransactionStat, $transfer_header_id, $paymethod_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set category

}//class transfer