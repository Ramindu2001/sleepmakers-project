<?php
/**
 * Barcode settings + sequence counters.
 * -----------------------------------------------------------------------------
 * Data access for the auto barcode generator. The rules themselves live in
 * Includes/barcode_generator.php - this class only reads and writes.
 *
 * The one piece of real logic here is allocateSequence(): it has to hand a
 * different number to every caller even when two people save a product in the
 * same millisecond, which is why it uses the LAST_INSERT_ID() counter idiom
 * rather than SELECT-then-UPDATE.
 */
class BarcodeSettings extends Dbh
{
    //=========================================================== settings ====

    /**
     * The saved rules for a shop, or an empty array when the shop has never
     * opened the settings page. Callers pass the result through
     * bcgNormalizeSettings() which fills in every default.
     */
    public function getSettings($shop_id)
    {
        try {
            $sql = "SELECT * FROM barcodesettings WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array((int) $shop_id));
            $rows = $stmt->fetchAll();

            return empty($rows) ? array() : $rows[0];
        }//try
        catch (PDOException $e) {
            //a shop that predates the migration must still be able to save a
            //product, so a missing table is not fatal here
            return array();
        }//catch
    }//getSettings

    /**
     * Insert or update the rules for a shop. Returns true on success.
     *
     * $data uses the same keys as bcgDefaultSettings(). Anything missing keeps
     * its current value because the caller merges before calling.
     *
     * Every column name is back quoted on purpose: `Separator` is a reserved
     * word in MariaDB (GROUP_CONCAT ... SEPARATOR) and the statement is a
     * syntax error without them.
     *
     * This returns false rather than die()-ing like the older models, because
     * one of its callers is a JSON endpoint - a die() there would send the
     * error message instead of a response the browser can read.
     */
    public function saveSettings($shop_id, $data, $user_id)
    {
        try {
            $sql = "INSERT INTO `barcodesettings`
                        (`shop_SHID`, `AutoGenerate`, `Pattern`, `FixedPrefix`, `Suffix`, `ShopCode`,
                         `Separator`, `CatCodeLength`, `SubCodeLength`, `SeqScope`, `SeqStart`,
                         `SeqStep`, `SeqLength`, `SeqPadChar`, `Casing`, `Symbology`, `MaxLength`,
                         `StripInvalid`, `LabelDefaults`, `UpdatedDate`, `UpdateUserID`)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?)
                    ON DUPLICATE KEY UPDATE
                        `AutoGenerate`  = VALUES(`AutoGenerate`),
                        `Pattern`       = VALUES(`Pattern`),
                        `FixedPrefix`   = VALUES(`FixedPrefix`),
                        `Suffix`        = VALUES(`Suffix`),
                        `ShopCode`      = VALUES(`ShopCode`),
                        `Separator`     = VALUES(`Separator`),
                        `CatCodeLength` = VALUES(`CatCodeLength`),
                        `SubCodeLength` = VALUES(`SubCodeLength`),
                        `SeqScope`      = VALUES(`SeqScope`),
                        `SeqStart`      = VALUES(`SeqStart`),
                        `SeqStep`       = VALUES(`SeqStep`),
                        `SeqLength`     = VALUES(`SeqLength`),
                        `SeqPadChar`    = VALUES(`SeqPadChar`),
                        `Casing`        = VALUES(`Casing`),
                        `Symbology`     = VALUES(`Symbology`),
                        `MaxLength`     = VALUES(`MaxLength`),
                        `StripInvalid`  = VALUES(`StripInvalid`),
                        `LabelDefaults` = VALUES(`LabelDefaults`),
                        `UpdatedDate`   = NOW(),
                        `UpdateUserID`  = VALUES(`UpdateUserID`);";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array(
                (int) $shop_id,
                (int) $data['AutoGenerate'],
                (string) $data['Pattern'],
                (string) $data['FixedPrefix'],
                (string) $data['Suffix'],
                (string) $data['ShopCode'],
                (string) $data['Separator'],
                (int) $data['CatCodeLength'],
                (int) $data['SubCodeLength'],
                (string) $data['SeqScope'],
                (int) $data['SeqStart'],
                (int) $data['SeqStep'],
                (int) $data['SeqLength'],
                (string) $data['SeqPadChar'],
                (string) $data['Casing'],
                (string) $data['Symbology'],
                (int) $data['MaxLength'],
                (int) $data['StripInvalid'],
                (string) $data['LabelDefaults'],
                (int) $user_id,
            ));

            return true;
        }//try
        catch (PDOException $e) {
            return false;
        }//catch
    }//saveSettings

    /**
     * The rules for a unique barcode on every unit (db/UNIT_BARCODES_MODULE.md).
     *
     * Saved on its own so the two forms never overwrite each other: the product
     * rules keep the unit rules, and this keeps the product rules. A row created
     * here starts on the table's own defaults for everything else.
     */
    public function saveUnitSettings($shop_id, array $data, $user_id)
    {
        try {
            $sql = "INSERT INTO `barcodesettings`
                        (`shop_SHID`, `UnitMode`, `UnitPattern`, `UnitSeqLength`, `UnitSeparator`,
                         `UpdatedDate`, `UpdateUserID`)
                    VALUES (?,?,?,?,?,NOW(),?)
                    ON DUPLICATE KEY UPDATE
                        `UnitMode`      = VALUES(`UnitMode`),
                        `UnitPattern`   = VALUES(`UnitPattern`),
                        `UnitSeqLength` = VALUES(`UnitSeqLength`),
                        `UnitSeparator` = VALUES(`UnitSeparator`),
                        `UpdatedDate`   = NOW(),
                        `UpdateUserID`  = VALUES(`UpdateUserID`);";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array(
                (int) $shop_id,
                empty($data['UnitMode']) ? 0 : 1,
                (string) $data['UnitPattern'],
                (int) $data['UnitSeqLength'],
                (string) $data['UnitSeparator'],
                (int) $user_id,
            ));

            return true;
        }//try
        catch (PDOException $e) {
            return false;
        }//catch
    }//saveUnitSettings

    //=========================================================== sequences ===

    /**
     * Consume the next number for a scope and return it.
     *
     * Concurrency safe. The UPDATE takes a row lock, LAST_INSERT_ID(NextValue)
     * both returns the value being consumed AND stores it on the session, so
     * the SELECT that follows can never pick up somebody else's number - the
     * session variable is private to this connection.
     *
     * Returns 0 when the counter table is missing, which tells the caller to
     * fall back to the product number.
     */
    public function allocateSequence($shop_id, $scope_key, $seq_start, $seq_step)
    {
        $shop_id = (int) $shop_id;
        $seq_start = (int) $seq_start;
        $seq_step = (int) $seq_step;

        if ($seq_step < 1) {
            $seq_step = 1;
        }//never stand still
        if ($seq_start < 0) {
            $seq_start = 0;
        }//never negative

        try {
            $pdo = $this->connect();

            /*
             * Make sure the counter row exists and is parked on SeqStart. The
             * no-op ON DUPLICATE branch is what keeps this from throwing on the
             * unique key when the row is already there.
             */
            $seed = $pdo->prepare(
                "INSERT INTO barcodesequence (shop_SHID, ScopeKey, NextValue, UpdatedDate)
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE BQID = BQID;"
            );
            $seed->execute(array($shop_id, $scope_key, $seq_start));

            //consume one number, atomically
            $bump = $pdo->prepare(
                "UPDATE barcodesequence
                    SET NextValue = LAST_INSERT_ID(NextValue) + ?,
                        UpdatedDate = NOW()
                  WHERE shop_SHID = ? AND ScopeKey = ?;"
            );
            $bump->execute(array($seq_step, $shop_id, $scope_key));

            //LAST_INSERT_ID() is per connection, so this is our own number
            $value = (int) $pdo->query('SELECT LAST_INSERT_ID();')->fetchColumn();

            return $value;
        }//try
        catch (PDOException $e) {
            return 0;
        }//catch
    }//allocateSequence

    /**
     * What allocateSequence() WOULD return, without consuming anything.
     *
     * Used by the previews. A preview that consumed a number would burn a code
     * every time somebody opened the add product dialog and looked away.
     */
    public function peekSequence($shop_id, $scope_key, $seq_start)
    {
        try {
            $sql = "SELECT NextValue FROM barcodesequence WHERE shop_SHID = ? AND ScopeKey = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array((int) $shop_id, $scope_key));
            $value = $stmt->fetchColumn();

            if ($value === false || $value === null) {
                return (int) $seq_start;
            }//never used

            return (int) $value;
        }//try
        catch (PDOException $e) {
            return (int) $seq_start;
        }//catch
    }//peekSequence

    /**
     * Move a counter to a specific number. Used by "Reset numbering" on the
     * settings page when a shop wants to continue from an existing series.
     */
    public function setSequence($shop_id, $scope_key, $next_value)
    {
        $next_value = (int) $next_value;
        if ($next_value < 0) {
            $next_value = 0;
        }//never negative

        try {
            $sql = "INSERT INTO barcodesequence (shop_SHID, ScopeKey, NextValue, UpdatedDate)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE NextValue = VALUES(NextValue), UpdatedDate = NOW();";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array((int) $shop_id, $scope_key, $next_value));

            return true;
        }//try
        catch (PDOException $e) {
            return false;
        }//catch
    }//setSequence

    /**
     * Every counter this shop is running, newest touched first. Shown on the
     * settings page so the operator can see and correct the numbering.
     */
    public function getSequences($shop_id)
    {
        try {
            $sql = "SELECT ScopeKey, NextValue, UpdatedDate
                    FROM barcodesequence
                    WHERE shop_SHID = ?
                    ORDER BY UpdatedDate DESC, ScopeKey ASC
                    LIMIT 200;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array((int) $shop_id));

            return $stmt->fetchAll();
        }//try
        catch (PDOException $e) {
            return array();
        }//catch
    }//getSequences

    /**
     * Delete one counter, so the next product starts again from SeqStart.
     */
    public function deleteSequence($shop_id, $scope_key)
    {
        try {
            $sql = "DELETE FROM barcodesequence WHERE shop_SHID = ? AND ScopeKey = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array((int) $shop_id, $scope_key));

            return true;
        }//try
        catch (PDOException $e) {
            return false;
        }//catch
    }//deleteSequence

    //============================================================= lookups ===

    /**
     * Is this barcode already on a product?
     *
     * Checked across the WHOLE products table, not just this shop, because
     * Product::getProductByBarcode() resolves a scanned code with no shop
     * filter. A code that is unique per shop but not globally would let the
     * POS ring up another shop's item.
     */
    public function barcodeExists($barcode, $ignore_product_id = 0)
    {
        $barcode = trim((string) $barcode);
        if ($barcode === '') {
            return false;
        }//nothing to check

        try {
            $sql = "SELECT COUNT(*) FROM products WHERE Barcode = ? AND PDID <> ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array($barcode, (int) $ignore_product_id));

            return ((int) $stmt->fetchColumn() > 0);
        }//try
        catch (PDOException $e) {
            //fail safe: claim it is taken so the generator tries another number
            return true;
        }//catch
    }//barcodeExists

    /**
     * Category + subcategory names and short codes for one subcategory.
     * Returns an empty array when the subcategory is unknown.
     */
    public function getCategoryContext($subcat_id)
    {
        $subcat_id = (int) $subcat_id;
        if ($subcat_id <= 0) {
            return array();
        }//no subcategory

        try {
            $sql = "SELECT subcategories.SCID, subcategories.SubCatName, subcategories.SubCatCode,
                           categories.CTID, categories.CategoryName, categories.CategoryCode
                    FROM subcategories
                    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
                    WHERE subcategories.SCID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array($subcat_id));
            $rows = $stmt->fetchAll();

            return empty($rows) ? array() : $rows[0];
        }//try
        catch (PDOException $e) {
            //SubCatCode / CategoryCode missing means the migration has not run
            return array();
        }//catch
    }//getCategoryContext

    /**
     * Shop name and number, for the {SHOP} token default.
     */
    public function getShopContext($shop_id)
    {
        try {
            $sql = "SELECT SHID, ShopNo, ShopName FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array((int) $shop_id));
            $rows = $stmt->fetchAll();

            return empty($rows) ? array() : $rows[0];
        }//try
        catch (PDOException $e) {
            return array();
        }//catch
    }//getShopContext
}//class BarcodeSettings
