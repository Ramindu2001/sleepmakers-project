-- =============================================================================
--  Barcode module - schema
-- -----------------------------------------------------------------------------
--  Adds everything the auto barcode generator and the label printer need.
--
--  ADDITIVE ONLY. Nothing existing is dropped, renamed or re-typed, so the
--  script is safe to run against a live shop and safe to run twice.
--
--  Run against the Cloud POS database only, e.g.
--      mysql -u root synnex_cloud_pos < barcode_module.sql
--
--  MariaDB 10.4+ / MySQL 8 both accept the IF NOT EXISTS forms below. If your
--  server rejects them, run db/barcode_module_install.php instead - it does the
--  same work through information_schema checks.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Short codes on the category tree.
--
--    CategoryNo / SubCatNo cannot be used as a barcode prefix: they are 9
--    characters long ("MC_000001") and are only unique per shop. These columns
--    hold the 2-4 character code the operator actually wants to see on a label
--    ("BEV", "GRO"). Left NULL, the generator derives one from the name.
-- -----------------------------------------------------------------------------
ALTER TABLE `categories`
    ADD COLUMN IF NOT EXISTS `CategoryCode` VARCHAR(12) NULL DEFAULT NULL AFTER `CategoryName`;

ALTER TABLE `subcategories`
    ADD COLUMN IF NOT EXISTS `SubCatCode` VARCHAR(12) NULL DEFAULT NULL AFTER `SubCatName`;

-- -----------------------------------------------------------------------------
-- 2. Barcode is looked up on every scan and probed for uniqueness on every
--    generated code. It had no index at all.
--
--    NOT unique on purpose: duplicate barcodes already exist in live data, and
--    a unique index would make the next product save fail outright.
-- -----------------------------------------------------------------------------
ALTER TABLE `products`
    ADD INDEX IF NOT EXISTS `idx_products_barcode` (`Barcode`);

-- -----------------------------------------------------------------------------
-- 3. Per shop barcode rules.
--
--    One row per shop. A shop with no row uses the built in defaults, so the
--    module works before anybody opens the settings page.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `barcodesettings` (
    `BSID`          INT(11)      NOT NULL AUTO_INCREMENT,
    `shop_SHID`     INT(11)      NOT NULL,

    -- generate a barcode when the operator leaves the field empty
    `AutoGenerate`  TINYINT(4)   NOT NULL DEFAULT 1,

    -- token pattern, e.g. {PREFIX}{SUB}{SEQ}
    `Pattern`       VARCHAR(160) NOT NULL DEFAULT '{PREFIX}{SUB}{SEQ}',
    `FixedPrefix`   VARCHAR(24)  NOT NULL DEFAULT '',
    `Suffix`        VARCHAR(24)  NOT NULL DEFAULT '',
    `ShopCode`      VARCHAR(12)  NOT NULL DEFAULT '',
    `Separator`     VARCHAR(4)   NOT NULL DEFAULT '',

    -- how many characters of the category / subcategory code to use
    `CatCodeLength` TINYINT(4)   NOT NULL DEFAULT 3,
    `SubCodeLength` TINYINT(4)   NOT NULL DEFAULT 3,

    -- sequence number behaviour
    `SeqScope`      VARCHAR(16)  NOT NULL DEFAULT 'pattern',
    `SeqStart`      BIGINT(20)   NOT NULL DEFAULT 1,
    `SeqStep`       INT(11)      NOT NULL DEFAULT 1,
    `SeqLength`     TINYINT(4)   NOT NULL DEFAULT 5,
    `SeqPadChar`    VARCHAR(1)   NOT NULL DEFAULT '0',

    -- output shaping
    `Casing`        VARCHAR(8)   NOT NULL DEFAULT 'upper',
    `Symbology`     VARCHAR(12)  NOT NULL DEFAULT 'CODE128',
    `MaxLength`     TINYINT(4)   NOT NULL DEFAULT 45,
    `StripInvalid`  TINYINT(4)   NOT NULL DEFAULT 1,

    -- default label print options for this shop, stored as JSON
    `LabelDefaults` TEXT         NULL DEFAULT NULL,

    `UpdatedDate`   DATETIME     NULL DEFAULT NULL,
    `UpdateUserID`  INT(11)      NULL DEFAULT NULL,

    PRIMARY KEY (`BSID`),
    UNIQUE KEY `uq_barcodesettings_shop` (`shop_SHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------------------------
-- 4. Sequence counters.
--
--    One row per (shop, scope). ScopeKey is what "Restart numbering per ..."
--    resolves to - the category id, the subcategory id, or the static part of
--    the pattern.
--
--    The UNIQUE key is what makes the counter atomic: the allocator does
--        UPDATE ... SET NextValue = LAST_INSERT_ID(NextValue) + step
--    which locks the row, hands back the value it consumed, and can never give
--    the same number to two people saving a product at the same moment.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `barcodesequence` (
    `BQID`        BIGINT(20)   NOT NULL AUTO_INCREMENT,
    `shop_SHID`   INT(11)      NOT NULL,
    `ScopeKey`    VARCHAR(160) NOT NULL,
    `NextValue`   BIGINT(20)   NOT NULL DEFAULT 1,
    `UpdatedDate` DATETIME     NULL DEFAULT NULL,

    PRIMARY KEY (`BQID`),
    UNIQUE KEY `uq_barcodesequence` (`shop_SHID`, `ScopeKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
