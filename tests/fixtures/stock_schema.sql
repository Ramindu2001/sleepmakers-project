-- Stock tables (products, GRN, inventory, price history, transfers) as they are before the
-- scanner upload. tests/DatabaseTestCase.php loads this after legacy_schema.sql. The foreign
-- keys are kept, except grnheader -> suppliers (no suppliers table here).

CREATE TABLE `products` (
  `PDID` int(11) NOT NULL AUTO_INCREMENT,
  `ProductNo` varchar(12) DEFAULT NULL,
  `ProdImage` varchar(255) DEFAULT NULL,
  `Barcode` varchar(45) DEFAULT NULL,
  `ItemName` varchar(120) DEFAULT NULL,
  `ProdDescription` mediumtext DEFAULT NULL,
  `SecondName` varchar(120) DEFAULT NULL,
  `ProdPurchasePrice` decimal(12,2) DEFAULT NULL,
  `ProdSellPrice` decimal(12,2) DEFAULT NULL,
  `CartonQty` int(11) DEFAULT 1,
  `ProductStat` tinyint(4) DEFAULT NULL,
  `AddedDate` date DEFAULT NULL,
  `UpdatedDate` date DEFAULT NULL,
  `ItemType` varchar(1) DEFAULT NULL,
  `user_USID` int(11) NOT NULL,
  `UpdateUserID` int(11) DEFAULT NULL,
  `Subcategories_SCID` int(11) NOT NULL,
  `shop_SHID` int(11) NOT NULL,
  `PurchaseUnit` int(11) DEFAULT NULL,
  `UnitConversion` decimal(12,3) DEFAULT NULL,
  `SellingUnit` int(11) DEFAULT NULL,
  `prodDiscount` decimal(18,2) DEFAULT 0.00,
  `prodFlatDiscount` float(10,2) NOT NULL,
  `is_fixedPrice` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`PDID`),
  KEY `fk_products_user1_idx` (`user_USID`),
  KEY `fk_products_Subcategories1_idx` (`Subcategories_SCID`),
  KEY `fk_products_shop1_idx` (`shop_SHID`),
  KEY `idx_products_barcode` (`Barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `units` (
  `UNID` int(11) NOT NULL AUTO_INCREMENT,
  `UnitName` varchar(60) DEFAULT NULL,
  `ShortName` varchar(10) DEFAULT NULL,
  `shop_SHID` int(11) NOT NULL,
  PRIMARY KEY (`UNID`),
  KEY `fk_units_shop1_idx` (`shop_SHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `variations` (
  `VRID` int(11) NOT NULL AUTO_INCREMENT,
  `VariationName` varchar(45) DEFAULT NULL,
  `products_PDID` int(11) NOT NULL,
  PRIMARY KEY (`VRID`),
  KEY `fk_variations_products1_idx` (`products_PDID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `grnheader` (
  `GHID` int(11) NOT NULL AUTO_INCREMENT,
  `GRNHeaderNo` varchar(12) DEFAULT NULL,
  `EffectiveDate` date DEFAULT NULL,
  `InvoiceNo` varchar(45) DEFAULT NULL,
  `ItemCount` int(11) DEFAULT NULL,
  `TotalPurchasePrice` decimal(12,2) DEFAULT NULL,
  `TotalSellPrice` decimal(12,2) DEFAULT NULL,
  `GRNStartTime` datetime DEFAULT NULL,
  `GRNEndTime` datetime DEFAULT NULL,
  `GRNStat` int(11) DEFAULT NULL,
  `user_USID` int(11) NOT NULL,
  `shop_SHID` int(11) NOT NULL,
  `Suppliers_SPID` int(11) NOT NULL,
  `SuppPayment` decimal(12,2) NOT NULL,
  `SuppBalance` decimal(12,2) NOT NULL,
  `excessAmount` decimal(12,2) NOT NULL,
  `refference` text NOT NULL,
  `PurchDiscType` int(11) DEFAULT 0,
  `PurchDisc` decimal(18,2) DEFAULT 0.00,
  `TotalDisc` decimal(18,2) NOT NULL DEFAULT 0.00,
  `TotalOriginalPurchase` decimal(18,2) DEFAULT 0.00,
  PRIMARY KEY (`GHID`),
  KEY `fk_GRNHeader_user1_idx` (`user_USID`),
  KEY `fk_GRNHeader_shop1_idx` (`shop_SHID`),
  KEY `fk_GRNHeader_Suppliers1_idx` (`Suppliers_SPID`),
  CONSTRAINT `fk_GRNHeader_shop1` FOREIGN KEY (`shop_SHID`) REFERENCES `shop` (`SHID`),
  CONSTRAINT `fk_GRNHeader_user1` FOREIGN KEY (`user_USID`) REFERENCES `user` (`USID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `grndetails` (
  `GDID` int(11) NOT NULL AUTO_INCREMENT,
  `InitQty` decimal(12,3) DEFAULT NULL,
  `CurrentQty` decimal(12,3) DEFAULT NULL,
  `UnitPurchasePrice` decimal(12,2) DEFAULT NULL,
  `UnitLabelPrice` decimal(12,2) DEFAULT NULL,
  `UnitSellPrice` decimal(12,2) DEFAULT NULL,
  `TotalPurchasePrice` decimal(12,2) DEFAULT NULL,
  `TotalSellPrice` decimal(12,2) DEFAULT NULL,
  `MnfDate` date DEFAULT NULL,
  `ExpDate` date DEFAULT NULL,
  `GRNStat` int(11) DEFAULT NULL,
  `VariationID` int(11) DEFAULT NULL,
  `products_PDID` int(11) NOT NULL,
  `GRNHeader_GHID` int(11) NOT NULL,
  `Rack_RKID` int(11) NOT NULL,
  PRIMARY KEY (`GDID`),
  KEY `fk_GRNDetails_products1_idx` (`products_PDID`),
  KEY `fk_GRNDetails_GRNHeader1_idx` (`GRNHeader_GHID`),
  KEY `fk_GRNDetails_Rack1_idx` (`Rack_RKID`),
  CONSTRAINT `fk_GRNDetails_GRNHeader1` FOREIGN KEY (`GRNHeader_GHID`) REFERENCES `grnheader` (`GHID`),
  CONSTRAINT `fk_GRNDetails_Rack1` FOREIGN KEY (`Rack_RKID`) REFERENCES `rack` (`RKID`),
  CONSTRAINT `fk_GRNDetails_products1` FOREIGN KEY (`products_PDID`) REFERENCES `products` (`PDID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `inventory` (
  `INID` int(11) NOT NULL AUTO_INCREMENT,
  `CurrentQty` decimal(12,3) DEFAULT NULL,
  `BillQty` decimal(12,3) DEFAULT NULL,
  `ReturnQty` decimal(12,3) DEFAULT NULL,
  `TransferInQty` decimal(12,3) DEFAULT NULL,
  `TransferOutQty` decimal(12,3) DEFAULT NULL,
  `Sup_Rtn` decimal(12,3) NOT NULL DEFAULT 0.000,
  `products_PDID` int(11) NOT NULL,
  `shop_SHID` int(11) NOT NULL,
  `RackID` int(11) DEFAULT NULL,
  `is_default` int(11) NOT NULL DEFAULT 0,
  `BatchID` varchar(11) DEFAULT NULL,
  PRIMARY KEY (`INID`),
  KEY `fk_Inventory_products1_idx` (`products_PDID`),
  KEY `fk_Inventory_shop1_idx` (`shop_SHID`),
  CONSTRAINT `fk_Inventory_products1` FOREIGN KEY (`products_PDID`) REFERENCES `products` (`PDID`),
  CONSTRAINT `fk_Inventory_shop1` FOREIGN KEY (`shop_SHID`) REFERENCES `shop` (`SHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `pricehistory` (
  `PHID` int(11) NOT NULL AUTO_INCREMENT,
  `ProductID` int(11) DEFAULT NULL COMMENT 'Without a foreign key we pass the product_id.',
  `VariationID` int(11) DEFAULT NULL COMMENT 'without a foreign key variation ID can be null, if there is  no variations',
  `EffectiveDate` date DEFAULT NULL,
  `PurchasePrice` decimal(12,2) DEFAULT NULL,
  `SellingPrice` decimal(12,2) DEFAULT NULL,
  `labelPrice` decimal(10,2) DEFAULT NULL,
  `MnfDate` date DEFAULT NULL,
  `ExpDate` date DEFAULT NULL,
  `BatchID` varchar(45) DEFAULT NULL,
  `Inventory_INID` int(11) NOT NULL,
  `GrnDetailID` int(11) DEFAULT NULL,
  PRIMARY KEY (`PHID`),
  KEY `fk_PriceHistory_Inventory1_idx` (`Inventory_INID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `transferheader` (
  `THID` int(11) NOT NULL AUTO_INCREMENT,
  `TransferNo` varchar(12) DEFAULT NULL,
  `EffectiveDate` date DEFAULT NULL,
  `TransferFrom` int(11) DEFAULT NULL COMMENT 'Transfer from shop id',
  `TransferTo` int(11) DEFAULT NULL COMMENT 'Transfer to shop id',
  `TransferTotalCount` int(11) DEFAULT NULL,
  `TransferTotalAmount` decimal(12,2) DEFAULT NULL,
  `TransferStat` int(11) DEFAULT NULL,
  `shop_SHID` int(11) NOT NULL,
  `user_USID` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`THID`),
  KEY `fk_TransferHeader_shop1_idx` (`shop_SHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `transferdetails` (
  `TDID` int(11) NOT NULL AUTO_INCREMENT,
  `TransferQty` decimal(12,3) DEFAULT NULL,
  `ReceivedQty` decimal(12,3) DEFAULT NULL,
  `UnitPurchasePrice` decimal(12,2) DEFAULT NULL,
  `UnitSellingPrice` decimal(12,2) DEFAULT NULL,
  `MnfDate` date DEFAULT NULL,
  `ExpDate` date DEFAULT NULL,
  `TransferTotalAmount` decimal(12,2) DEFAULT NULL,
  `InventoryID` int(11) DEFAULT NULL,
  `products_PDID` int(11) NOT NULL,
  `VariationID` int(11) DEFAULT NULL,
  `RackID` int(11) DEFAULT NULL,
  `TransferStat` int(11) DEFAULT NULL,
  `TransferHeader_THID` int(11) NOT NULL,
  `Batch_ID` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`TDID`),
  KEY `fk_TransferDetails_products1_idx` (`products_PDID`),
  KEY `fk_TransferDetails_TransferHeader1_idx` (`TransferHeader_THID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `rack` (
  `RKID` int(11) NOT NULL AUTO_INCREMENT,
  `RackNo` varchar(10) DEFAULT NULL,
  `RackName` varchar(45) DEFAULT NULL,
  `Sections_SEID` int(11) NOT NULL,
  PRIMARY KEY (`RKID`),
  KEY `fk_Rack_Sections1_idx` (`Sections_SEID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE `sections` (
  `SEID` int(11) NOT NULL AUTO_INCREMENT,
  `SectionNo` varchar(10) DEFAULT NULL,
  `SectionName` varchar(45) DEFAULT NULL,
  `shop_SHID` int(11) NOT NULL,
  PRIMARY KEY (`SEID`),
  KEY `fk_Sections_shop1_idx` (`shop_SHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- rack 1: the default rack every shop without racks uses (manual GRN entry writes Rack_RKID 1)
INSERT INTO `sections` (`SEID`, `SectionNo`, `SectionName`, `shop_SHID`) VALUES (1, 'SE_000001', 'Default Section', 0);
INSERT INTO `rack` (`RKID`, `RackNo`, `RackName`, `Sections_SEID`) VALUES (1, 'RK_000001', 'Default Rack', 1);

-- barcode module: the shop's rules and the running counters (db/BARCODE_MODULE.md)
CREATE TABLE `barcodesettings` (
  `BSID` int(11) NOT NULL AUTO_INCREMENT,
  `shop_SHID` int(11) NOT NULL,
  `AutoGenerate` tinyint(4) NOT NULL DEFAULT 1,
  `Pattern` varchar(160) NOT NULL DEFAULT '{PREFIX}{CAT}{SUB}{SEQ}',
  `FixedPrefix` varchar(24) NOT NULL DEFAULT '',
  `Suffix` varchar(24) NOT NULL DEFAULT '',
  `ShopCode` varchar(12) NOT NULL DEFAULT '',
  `Separator` varchar(4) NOT NULL DEFAULT '',
  `CatCodeLength` tinyint(4) NOT NULL DEFAULT 3,
  `SubCodeLength` tinyint(4) NOT NULL DEFAULT 3,
  `SeqScope` varchar(16) NOT NULL DEFAULT 'pattern',
  `SeqStart` bigint(20) NOT NULL DEFAULT 1,
  `SeqStep` int(11) NOT NULL DEFAULT 1,
  `SeqLength` tinyint(4) NOT NULL DEFAULT 5,
  `SeqPadChar` varchar(1) NOT NULL DEFAULT '0',
  `Casing` varchar(8) NOT NULL DEFAULT 'upper',
  `Symbology` varchar(12) NOT NULL DEFAULT 'CODE128',
  `MaxLength` tinyint(4) NOT NULL DEFAULT 45,
  `StripInvalid` tinyint(4) NOT NULL DEFAULT 1,
  `LabelDefaults` text DEFAULT NULL,
  `UpdatedDate` datetime DEFAULT NULL,
  `UpdateUserID` int(11) DEFAULT NULL,
  PRIMARY KEY (`BSID`),
  UNIQUE KEY `uq_barcodesettings_shop` (`shop_SHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `barcodesequence` (
  `BQID` bigint(20) NOT NULL AUTO_INCREMENT,
  `shop_SHID` int(11) NOT NULL,
  `ScopeKey` varchar(160) NOT NULL,
  `NextValue` bigint(20) NOT NULL DEFAULT 1,
  `UpdatedDate` datetime DEFAULT NULL,
  PRIMARY KEY (`BQID`),
  UNIQUE KEY `uq_barcodesequence` (`shop_SHID`,`ScopeKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Selling: the POS invoice, its lines, what each sale took out of stock, and the customer.
-- Foreign keys are left off, as elsewhere in these fixtures, so a test can insert one row
-- without building the whole company around it.
CREATE TABLE `customers` (
  `CTID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerNo` varchar(12) DEFAULT NULL,
  `CustName` varchar(120) DEFAULT NULL,
  `CustGender` int(11) NOT NULL DEFAULT 1,
  `CustDOB` date DEFAULT NULL,
  `CustAddress` varchar(255) DEFAULT NULL,
  `CustContact` varchar(12) DEFAULT NULL,
  `MaxCreditAmount` decimal(12,2) DEFAULT 100000.00,
  `PaymentTerm` int(11) DEFAULT NULL,
  `CustStat` int(11) DEFAULT NULL,
  `shop_SHID` int(11) NOT NULL,
  PRIMARY KEY (`CTID`),
  KEY `fk_Customers_shop1_idx` (`shop_SHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `invoiceheader` (
  `IHID` int(11) NOT NULL AUTO_INCREMENT,
  `InvoiceNo` varchar(12) DEFAULT NULL,
  `Inv_Type` int(11) NOT NULL DEFAULT 1,
  `EffectiveDate` date DEFAULT NULL,
  `BillNo` varchar(12) DEFAULT NULL,
  `InvStartTime` datetime DEFAULT NULL,
  `InvEndTime` datetime DEFAULT NULL,
  `InvItemCount` int(11) DEFAULT NULL,
  `GrossAmount` decimal(12,2) DEFAULT NULL,
  `lineDiscount` float(10,2) DEFAULT 0.00,
  `PercentDiscount` decimal(12,2) DEFAULT 0.00,
  `FixedDiscount` decimal(12,2) DEFAULT 0.00,
  `DiscountAmount` decimal(12,2) DEFAULT NULL,
  `discountType` int(11) NOT NULL DEFAULT 1,
  `deliveryCharge` float(10,2) NOT NULL DEFAULT 0.00,
  `otherCharge` float(10,2) NOT NULL DEFAULT 0.00,
  `excessAmount` float(10,2) DEFAULT 0.00,
  `returnAmount` float(10,2) DEFAULT 0.00,
  `NetAmount` decimal(12,2) DEFAULT NULL,
  `CustPayment` decimal(12,2) DEFAULT NULL,
  `CustBalance` decimal(12,2) DEFAULT NULL,
  `InvStat` tinyint(4) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `user_USID` int(11) NOT NULL,
  `customers_CTID` int(11) DEFAULT NULL,
  `Salesmans_SLID` int(11) NOT NULL DEFAULT 0,
  `ReturnHeader_RHID` int(11) DEFAULT NULL,
  `shop_SHID` int(11) NOT NULL,
  `CashCounter_CCID` int(11) NOT NULL DEFAULT 0,
  `print_count` int(11) NOT NULL DEFAULT 1,
  `is_delivery` int(11) NOT NULL DEFAULT 0,
  `deliveryPartner` varchar(120) NOT NULL DEFAULT '',
  `sales_source` int(11) DEFAULT NULL,
  `HIID` int(11) DEFAULT NULL,
  PRIMARY KEY (`IHID`),
  KEY `fk_InvoiceHeader_shop1` (`shop_SHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `invoicedetails` (
  `IDID` int(11) NOT NULL AUTO_INCREMENT,
  `Item_Name` text NOT NULL,
  `SellQty` decimal(12,3) DEFAULT NULL,
  `UnitPrice` decimal(12,2) DEFAULT NULL,
  `SellAmount` decimal(12,2) DEFAULT NULL,
  `PercentDiscount` decimal(12,2) DEFAULT NULL,
  `DirectDiscount` decimal(12,2) DEFAULT NULL,
  `SellDiscount` decimal(12,2) DEFAULT NULL,
  `disc_type` int(11) DEFAULT 0,
  `ItemType` int(11) NOT NULL DEFAULT 1,
  `SoldAmount` decimal(12,2) DEFAULT NULL,
  `WarrantyStart` date DEFAULT NULL,
  `WarrantyEnd` date DEFAULT NULL,
  `ReferenceNo` varchar(45) DEFAULT NULL,
  `InvoiceHeader_IHID` int(11) NOT NULL,
  `products_PDID` int(11) NOT NULL,
  `item_des` varchar(250) NOT NULL DEFAULT '',
  `batch_no` varchar(12) DEFAULT NULL,
  `Inventory_INID` int(11) NOT NULL DEFAULT 0,
  `shop_id` int(11) NOT NULL,
  PRIMARY KEY (`IDID`),
  KEY `fk_InvoiceDetails_InvoiceHeader1_idx` (`InvoiceHeader_IHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `inventory_consumption` (
  `ICID` int(11) NOT NULL DEFAULT 0,
  `invoice_headerID` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `inventory_INID` int(11) NOT NULL,
  `price` float(10,2) NOT NULL,
  `sold_price` float(10,2) NOT NULL,
  `batch No` int(11) NOT NULL,
  `product_PDID` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `quantity` float(10,2) NOT NULL,
  `shop_SHID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
