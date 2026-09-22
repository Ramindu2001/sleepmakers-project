<?php

class Shop extends Dbh
{
    public function setShop($ShopNo, $ShopName, $is_wholesale, $is_retail, $is_inventory, $is_minus, $is_category, $is_expire, $is_variation, $is_suppliers, $is_service, $is_salesman, $is_expenses, $is_customers, $is_fixedprice, $is_carton, $is_warranty, $is_promotions, $is_secondlan, $is_labelprice, $is_quotation, $is_racks, $is_credit, $is_prescription, $ShopStat, $Company_CMID, $StockTypes_STID, $AddressLineOne, $AddressLineTwo, $City, $emailAddress, $PhoneNumber,$is_counter,$is_excessAmount,$is_BatchNo,$invoice_print,$chk_is_under_cost)
    {
        try {
            $sql = "INSERT INTO shop(
                ShopNo, 
                ShopName, 
                WholesaleShop,
                RetailShop,
                is_inventory, 
                is_minus, 
                is_category, 
                is_expire,  
                is_variation, 
                is_suppliers, 
                is_service, 
                is_salesman, 
                is_expenses, 
                is_customers, 
                is_fixedprice, 
                is_carton, 
                is_warranty, 
                is_promotions, 
                is_secondlan, 
                is_labelprice, 
                is_quotation,
                is_racks,
                is_credit,
                is_prescription,
                is_counter,
                is_excessAmount,
                is_BatchNo,
                invoice_print,
                is_under_cost,
                ShopStat,
                Company_CMID, 
                StockTypes_STID,
                AddressLineOne,
                AddressLineTwo,
                City,
                emailAddress,
                PhoneNumber) VALUES('$ShopNo', '$ShopName', '$is_wholesale', '$is_retail', '$is_inventory', '$is_minus', '$is_category', '$is_expire', '$is_variation', '$is_suppliers', '$is_service', '$is_salesman', '$is_expenses', '$is_customers', '$is_fixedprice', '$is_carton', '$is_warranty', '$is_promotions', '$is_secondlan', '$is_labelprice', '$is_quotation', '$is_racks', '$is_credit', '$is_prescription','$is_counter','$is_excessAmount','$is_BatchNo','$invoice_print','$chk_is_under_cost', '$ShopStat', '$Company_CMID', '$StockTypes_STID', '$AddressLineOne', '$AddressLineTwo', '$City', '$emailAddress', '$PhoneNumber')";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            // return $sql;
        } catch (PDOException $e) {
            die("Error: Unable to insert data to shop: " . $e->getMessage());
        }

    }//insert shop

    public function updateShop($ShopName, $is_wholesale, $is_retail, $is_inventory, $is_minus, $is_category, $is_expire, $is_variation, $is_suppliers, $is_service, $is_salesman, $is_expenses, $is_customers, $is_fixedprice, $is_carton, $is_warranty, $is_promotions, $is_secondlan, $is_labelprice, $is_quotation, $is_racks, $is_credit, $is_prescription, $shop_stat, $Company_CMID, $StockTypes_STID, $AddressLineOne, $AddressLineTwo, $City, $emailAddress, $PhoneNumber, $shop_id,$is_counter,$is_excessAmount,$is_BatchNo,$invoice_print,$chk_is_under_cost)
    {
        try {
            $sql = "UPDATE shop SET
            ShopName=?,
            WholesaleShop=?,
            RetailShop=?,
            is_inventory=?, 
            is_minus=?, 
            is_category=?, 
            is_expire=?, 
            is_variation=?, 
            is_suppliers=?, 
            is_service=?, 
            is_salesman=?, 
            is_expenses=?, 
            is_customers=?, 
            is_fixedprice=?, 
            is_carton=?, 
            is_warranty=?, 
            is_promotions=?, 
            is_secondlan=?, 
            is_labelprice=?, 
            is_quotation=?, 
            is_racks=?,
            is_credit=?,
            is_prescription=?,
            is_counter=?,
            is_excessAmount=?,
            is_BatchNo=?,
            ShopStat=?,
            Company_CMID=?,  
            StockTypes_STID=?,
            AddressLineOne=?,
            AddressLineTwo=?,
            City=?,
            emailAddress=?,
            PhoneNumber=?,
            invoice_print=?,
            is_under_cost=?
            WHERE shop.SHID = ?;";   
            $stmt = $this->connect()->prepare($sql);           
            $stmt->execute([$ShopName, $is_wholesale, $is_retail, $is_inventory,$is_minus,$is_category,$is_expire,$is_variation, $is_suppliers, $is_service,$is_salesman,$is_expenses,$is_customers,$is_fixedprice,$is_carton,$is_warranty,$is_promotions,$is_secondlan, $is_labelprice, $is_quotation, $is_racks, $is_credit, $is_prescription,$is_counter,$is_excessAmount,
            $is_BatchNo, $shop_stat, $Company_CMID, $StockTypes_STID, $AddressLineOne, $AddressLineTwo, $City, $emailAddress, $PhoneNumber, $invoice_print,$chk_is_under_cost, $shop_id]);
        } 
        catch (PDOException $e) {
            die("Error: Unable to update the shop" . $e->getMessage());
        }
    }//update shop

    public function updateshoplogo($logo,$shop_id)
    {
        $sql="UPDATE shop SET ShopLogo='$logo' WHERE SHID='$shop_id'";
        $stmt = $this->connect()->prepare($sql);           
        $stmt->execute();

    }
    public function updateshopreceiptlogo($logo,$shop_id)
    {
        $sql="UPDATE shop SET ReceiptLogo='$logo' WHERE SHID='$shop_id'";
        $stmt = $this->connect()->prepare($sql);           
        $stmt->execute();

    }

    public function getCompanyONE($companmyID)
    {
        try {
            $sql = "SELECT * FROM `company`
            WHERE CMID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$companmyID]);
            return $stmt->fetchAll();
        } 
        catch (PDOException $e) {   
            die("Error: Unable to fetch Shop data " . $e->getMessage());
        }
    }

    public function setShopFeature($shop_id,$featureid,$on)
    {
        try {
            $sql = "INSERT INTO `shoppermissions`(`ShopFeature_SPFID`, `Shop_SHID`, `is_active`) VALUES ('$featureid','$shop_id','$on')";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();

        } catch (PDOException $e) {
            die("Error: Unable to insert data to shop: " . $e->getMessage());
        }
    }

    public function getOneShop($shop_id)
    {
        try {
            $sql = "SELECT * FROM shop S
            INNER JOIN company C ON C.CMID = S.Company_CMID
            INNER JOIN stocktypes ST ON ST.STID = S.StockTypes_STID
            WHERE S.SHID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Error: Unable to fetch Shop data " . $e->getMessage());
        }
    }//get one shop

    public function getShops()
    {
        try {
            $sql = "SELECT * FROM shop
            INNER JOIN company ON company.CMID = shop.Company_CMID
            INNER JOIN stocktypes ON stocktypes.STID = shop.StockTypes_STID;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            $Shops = $stmt->fetchAll();
            return $Shops;
        } catch (PDOException $e) {
            die("Error: Unable to fetch Shops. " . $e->getMessage());
        }
    }//get all shops

    public function getShopCount()
    {
        try {
            $sql = "SELECT max(SHID) as ShopCount FROM shop;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Error: Unable to get the shop count: " . $e->getMessage());
        }

    }//get company type

    public function getCompanyfromShop($shop_SHID1)
    {
        try {
            $sql = "SELECT Company_CMID FROM shop WHERE SHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_SHID1]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//get company ID 

    public function getStockTypes()
    {
        try {
            $stmt = $this->connect()->prepare("SELECT STID, StockTypeName FROM stocktypes");
            $stmt->execute();
            $Types = $stmt->fetchAll();
            return $Types;
        } catch (PDOException $e) {
            die("Error: Unable to fetch Stock Type. " . $e->getMessage());
        }
    }

//=================================== Has Specific Options =======================================//
    public function hasWholesaleShop($shop_id)
    {
        try 
        {
            $sql = "SELECT WholesaleShop FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['WholesaleShop'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//wholesale shop

    public function hasRetailShop($shop_id)
    {
        try 
        {
            $sql = "SELECT RetailShop FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['RetailShop'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//Retail shop   

    public function hasInventory($shop_id)
    {
        try 
        {
            $sql = "SELECT is_inventory FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_inventory'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has Inventory    

    public function hasMinus($shop_id)
    {
        try 
        {
            $sql = "SELECT is_minus FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_minus'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has minus    
    
    public function hasPrescription($shop_id)
    {
        try {
            $sql = "SELECT is_prescription FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_prescription'];
        } catch (PDOException $e) {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has prescription
    
    public function hasinvoice_print($shop_id)
    {
        try {
            $sql = "SELECT invoice_print FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            if(isset($data[0]['invoice_print']))
            {
               return $data[0]['invoice_print']; 
            }
            else
            {
                return 1;
            }
            
        } catch (PDOException $e) {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has prescription
    
    public function hasexcess($shop_id)
    {
        try {
            $sql = "SELECT is_excessAmount FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_excessAmount'];
        } catch (PDOException $e) {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has excess
    public function hasbatchNo($shop_id)
    {
        try {
            $sql = "SELECT is_BatchNo FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_BatchNo'];
        } catch (PDOException $e) {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has excess
    public function hascounter($shop_id)
    {
        try {
            $sql = "SELECT is_counter FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_counter'];
        } catch (PDOException $e) {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has prescription    

    public function hasUnderCost($shop_id)
    {
        try {
            $sql = "SELECT is_under_cost FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_under_cost'];
        } catch (PDOException $e) {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has under cost feature

    public function hasCategories($shop_id)
    {
        try 
        {
            $sql = "SELECT is_category FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_category'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has categories    

    public function hasExpiry($shop_id)
    {
        try 
        {
            $sql = "SELECT is_expire FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_expire'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has expire date    

    public function hasVariation($shop_id)
    {
        try 
        {
            $sql = "SELECT is_variation FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_variation'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has variation

    public function hasSuppliers($shop_id)
    {
        try 
        {
            $sql = "SELECT is_suppliers FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_suppliers'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has suppliers 

    public function hasService($shop_id)
    {
        try 
        {
            $sql = "SELECT is_service FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_service'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has service 

    public function hasSalesman($shop_id)
    {
        try 
        {
            $sql = "SELECT is_salesman FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_salesman'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has salesman 

    public function hasExpenses($shop_id)
    {
        try 
        {
            $sql = "SELECT is_expenses FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_expenses'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has expenses 

    public function hasCustomers($shop_id)
    {
        try 
        {
            $sql = "SELECT is_customers FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_customers'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has customers 

    public function hasFixedPrice($shop_id)
    {
        try 
        {
            $sql = "SELECT is_fixedprice FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_fixedprice'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has fixed price 

    public function hasCartonQty($shop_id)
    {
        try 
        {
            $sql = "SELECT is_carton FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_carton'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has carton qty 

    public function hasWarranty($shop_id)
    {
        try 
        {
            $sql = "SELECT is_warranty FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_warranty'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has warranty 

    public function hasPromotion($shop_id)
    {
        try 
        {
            $sql = "SELECT is_promotions FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_promotions'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has promotion 

    public function hasSecondLanguage($shop_id)
    {
        try 
        {
            $sql = "SELECT is_secondlan FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_secondlan'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has second language 

    public function hasLabelPrice($shop_id)
    {
        try 
        {
            $sql = "SELECT is_labelprice FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_labelprice'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has label price 

    public function hasQuotation($shop_id)
    {
        try
        {
            $sql = "SELECT is_quotation FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_quotation'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has quotation 

    public function hasRacks($shop_id)
    {
        try
        {
            $sql = "SELECT is_racks FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_racks'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has racks
    
    public function hasCredit($shop_id)
    {
        try
        {
            $sql = "SELECT is_credit FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);

            $data = $stmt->fetchAll();
            return $data[0]['is_credit'];
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to get the company ID " . $e->getMessage());
        }
    }//has racks

}//class Shop