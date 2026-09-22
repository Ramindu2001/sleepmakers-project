-- Access related tables as they were before the shop access module.
-- tests/DatabaseTestCase.php loads this before every test.

CREATE TABLE `company` (
  `CMID` int(11) NOT NULL AUTO_INCREMENT,
  `CompanyNo` varchar(12) DEFAULT NULL,
  `ComName` varchar(60) DEFAULT NULL,
  `CompanyLocation` varchar(60) DEFAULT NULL,
  `LicenceNo` varchar(60) DEFAULT NULL,
  `VersionNo` varchar(60) DEFAULT NULL,
  `ComLogo` varchar(255) DEFAULT NULL,
  `ComStartDate` date DEFAULT NULL,
  `ComExpireDate` date DEFAULT NULL,
  `ComStat` tinyint(4) DEFAULT NULL,
  `is_multicategory` tinyint(4) DEFAULT NULL,
  `is_commonStock` int(11) NOT NULL DEFAULT 0,
  `CompanyType_CTID` int(11) NOT NULL,
  `last_updateDate` date NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`CMID`),
  KEY `fk_Company_CompanyType1_idx` (`CompanyType_CTID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `shop` (
  `SHID` int(11) NOT NULL AUTO_INCREMENT,
  `ShopNo` varchar(12) DEFAULT NULL,
  `ShopName` varchar(45) DEFAULT NULL,
  `ShopLogo` varchar(255) DEFAULT NULL,
  `ReceiptLogo` varchar(255) DEFAULT NULL,
  `WholesaleShop` tinyint(1) NOT NULL COMMENT 'Wholesale shop',
  `RetailShop` tinyint(1) NOT NULL COMMENT 'Retail shop',
  `is_inventory` tinyint(4) DEFAULT NULL,
  `is_minus` tinyint(4) DEFAULT NULL,
  `is_category` tinyint(4) DEFAULT NULL,
  `is_expire` tinyint(4) DEFAULT NULL,
  `is_variation` tinyint(4) DEFAULT NULL,
  `is_suppliers` tinyint(4) DEFAULT NULL,
  `is_service` tinyint(4) DEFAULT NULL,
  `is_salesman` tinyint(4) DEFAULT NULL,
  `is_expenses` tinyint(4) DEFAULT NULL,
  `is_customers` tinyint(4) DEFAULT NULL,
  `is_fixedprice` tinyint(4) DEFAULT NULL,
  `is_carton` tinyint(4) DEFAULT NULL,
  `is_warranty` tinyint(4) DEFAULT NULL,
  `is_promotions` tinyint(4) DEFAULT NULL,
  `is_secondlan` tinyint(4) DEFAULT NULL,
  `is_labelprice` tinyint(4) DEFAULT NULL,
  `is_quotation` tinyint(4) DEFAULT NULL,
  `is_racks` tinyint(4) DEFAULT NULL,
  `is_credit` tinyint(4) DEFAULT NULL,
  `invoice_print` tinyint(4) NOT NULL DEFAULT 1,
  `is_prescription` tinyint(1) NOT NULL,
  `is_counter` int(11) NOT NULL DEFAULT 1,
  `is_excessAmount` int(11) NOT NULL DEFAULT 0,
  `is_BatchNo` int(11) NOT NULL DEFAULT 0,
  `is_under_cost` int(11) NOT NULL DEFAULT 0,
  `is_a4invoice` tinyint(4) NOT NULL DEFAULT 0,
  `ShopStat` tinyint(4) DEFAULT NULL,
  `Company_CMID` int(11) NOT NULL,
  `StockTypes_STID` int(11) NOT NULL,
  `AddressLineOne` varchar(255) DEFAULT NULL,
  `AddressLineTwo` varchar(255) DEFAULT NULL,
  `City` varchar(120) DEFAULT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `PhoneNumber` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`SHID`),
  KEY `fk_shop_Company1_idx` (`Company_CMID`),
  KEY `fk_shop_StockTypes1_idx` (`StockTypes_STID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `user` (
  `USID` int(11) NOT NULL AUTO_INCREMENT,
  `UserProfile` varchar(255) DEFAULT NULL,
  `UserName` varchar(45) DEFAULT NULL,
  `UserEmail` varchar(150) DEFAULT NULL,
  `ContactNo` varchar(12) DEFAULT NULL,
  `UserPwd` varchar(255) DEFAULT NULL,
  `PwdChange` varchar(255) DEFAULT NULL,
  `UserStat` tinyint(4) DEFAULT 1,
  `UserRoles_URID` int(11) NOT NULL,
  `UserType` int(11) NOT NULL DEFAULT 0,
  `paylimit` float(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`USID`),
  KEY `fk_user_UserRoles1_idx` (`UserRoles_URID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `userroles` (
  `URID` int(11) NOT NULL AUTO_INCREMENT,
  `UserRoleName` varchar(60) DEFAULT NULL,
  `ur_status` tinyint(1) NOT NULL DEFAULT 1,
  `added_by` int(11) NOT NULL,
  `user_ip` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`URID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `shopusers` (
  `SUID` int(11) NOT NULL AUTO_INCREMENT,
  `shop_SHID` int(11) NOT NULL,
  `user_USID` int(11) NOT NULL,
  PRIMARY KEY (`SUID`),
  KEY `fk_ShopUsers_shop1_idx` (`shop_SHID`),
  KEY `fk_ShopUsers_user1_idx` (`user_USID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `userlog` (
  `ULID` int(11) NOT NULL AUTO_INCREMENT,
  `logStart` datetime DEFAULT NULL,
  `logEnd` datetime DEFAULT NULL,
  `logStat` tinyint(4) DEFAULT NULL,
  `user_USID` int(11) NOT NULL,
  PRIMARY KEY (`ULID`),
  KEY `fk_userlog_user1_idx` (`user_USID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `userroleaccess` (
  `RAID` int(11) NOT NULL AUTO_INCREMENT,
  `is_create` tinyint(4) DEFAULT NULL,
  `is_edit` tinyint(4) DEFAULT NULL,
  `is_view` tinyint(4) DEFAULT NULL,
  `is_delete` tinyint(4) DEFAULT NULL,
  `is_verify` tinyint(4) DEFAULT NULL,
  `is_print` tinyint(4) DEFAULT NULL,
  `UserRolls_URID` int(11) NOT NULL,
  `SysFeatures_SFID` int(11) NOT NULL,
  PRIMARY KEY (`RAID`),
  KEY `fk_UserRoleAccess_UserRolls1_idx` (`UserRolls_URID`),
  KEY `fk_UserRoleAccess_SysFeatures1_idx` (`SysFeatures_SFID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `usermoduleaccess` (
  `MAID` int(11) NOT NULL AUTO_INCREMENT,
  `SysModules_SMID` int(11) NOT NULL,
  `UserRoles_URID` int(11) DEFAULT NULL,
  PRIMARY KEY (`MAID`),
  KEY `fk_UserModuleAccess_SysModules1_idx` (`SysModules_SMID`),
  KEY `UserRoles_URID` (`UserRoles_URID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
