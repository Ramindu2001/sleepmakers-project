<?php
//Every unit Sleep Makers make carries its own barcode
//(docs/superpowers/specs/2026-09-23-unit-barcodes-design.md).
//
//A unit code is the product's own barcode (the prefix) and then the day it was made and a serial
//(the suffix):
//
//    COO00001 250912 0013    pattern {ITEM}{YY}{MM}{DD}{SEQ}, serial 4 digits
//
//The serial is one running number for the whole company and the day, whatever the product
//(docs/superpowers/specs/2026-10-06-daily-unit-numbering-design.md). The pattern, the serial
//width and the separator are the shop's (barcodesettings), and the serial comes from the same
//atomic counter the product barcodes use, so two people printing at the same moment can never
//be given the same number. The exact day is kept on the row, so a pattern that only carries
//the month still answers "how many did we make on the 12th".
//
//Nothing here changes stock: a unit is recorded when it is PRINTED and marked RECEIVED when
//it is scanned into a GRN. The unique key on UnitBarcode is what makes the same unit
//impossible to register twice.

//the serials come from the barcode module's counter, and not every page that prints labels
//loads the generator, so the model asks for what it needs itself
require_once __DIR__ . '/barcode_settings_class.php';

class ProductUnits extends Dbh
{
    const PRINTED = 1;
    const RECEIVED = 2;
    const VOIDED = 0;
    const DISPATCHED = 3;   //sent to the customer it was sold to

    const MAX_PER_PRINT = 5000;            //one print job; the label page caps what it renders
    const COUNTER_SHOP = 0;                //the day's counters are the company's: no real shop has id 0
    const DEFAULTS = [
        'mode' => false,
        'pattern' => '{ITEM}{YY}{MM}{DD}{SEQ}',
        'seq_length' => 4,
        'separator' => '',
    ];

    //the date parts a pattern may carry, longest token first so {YYYY} wins over {YY}
    const DATE_TOKENS = ['{YYYY}' => 'Y', '{YY}' => 'y', '{MM}' => 'm', '{DD}' => 'd'];

    private $settingsCache = [];

    //------------------------------------------------------------------ the shop's rules

    //['mode' => bool, 'pattern' => ..., 'seq_length' => int, 'separator' => ...]
    public function settings($shop_id)
    {
        $shop_id = (int)$shop_id;
        if(isset($this->settingsCache[$shop_id]))
        {
            return $this->settingsCache[$shop_id];
        }//asked before

        $settings = self::DEFAULTS;
        $stmt = $this->connect()->prepare("SELECT UnitMode, UnitPattern, UnitSeqLength, UnitSeparator
            FROM barcodesettings WHERE shop_SHID = ?;");
        $stmt->execute([$shop_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row !== false)
        {
            $settings = [
                'mode' => (int)$row['UnitMode'] === 1,
                'pattern' => trim((string)$row['UnitPattern']) === '' ? self::DEFAULTS['pattern'] : (string)$row['UnitPattern'],
                'seq_length' => max(1, min(9, (int)$row['UnitSeqLength'])),
                'separator' => (string)$row['UnitSeparator'],
            ];
        }//the shop set its own

        if(strpos($settings['pattern'], '{SEQ}') === false)
        {
            $settings['pattern'] .= '{SEQ}';
        }//a unit code without a serial would not be unique

        $this->settingsCache[$shop_id] = $settings;
        return $settings;
    }//settings

    //is this shop printing a unique barcode on every unit?
    public function modeOn($shop_id)
    {
        $settings = $this->settings($shop_id);
        return $settings['mode'];
    }//mode on

    //The part of a unit code that follows the item barcode, e.g. "25090013" - always the same
    //width, which is what lets codes typed with no separator between them be split apart.
    //0 where the shop does not number its units, so nothing about scanning changes there.
    public function suffixLength($shop_id)
    {
        $settings = $this->settings($shop_id);
        if(!$settings['mode'])
        {
            return 0;
        }//not printing unit barcodes
        return strlen($this->build($settings, 'X', date('Y-m-d'), 1)) - 1;
    }//suffix length

    //The part of a unit code that is neither the item nor the serial: the production date as the
    //pattern writes it ("250912" for {ITEM}{YY}{MM}{DD}{SEQ}), without a separator at either end.
    private function datePart(array $settings, $produced_date)
    {
        $part = $this->build($settings, '', $produced_date, null);
        if($settings['separator'] !== '')
        {
            $part = trim($part, $settings['separator']);
        }//never start or end on a separator
        return strtoupper($part);
    }//date part

    //Which number series a unit takes its serial from: the company, and everything in the code
    //except the item and the serial. {ITEM}{YY}{MM}{DD}{SEQ} gives one series a day for the whole
    //company, so the serial says which unit of the day it was whatever the product; a pattern with
    //only {YY}{MM} gives one a month. The series always matches what the code shows, which is what
    //keeps two codes from ever being the same.
    public function scopeKey(array $settings, $company_id, $produced_date)
    {
        return 'unit:c' . (int)$company_id . ':' . $this->datePart($settings, $produced_date);
    }//scope key

    //Where a series that has no counter yet starts: one past the highest serial already issued in it,
    //by any item, in any shop of the company, in any state (a voided unit keeps its code in the
    //unique key). Days numbered item by item before the series was shared hold serials that a count
    //from 1 would repeat. A series that holds nothing starts at 1.
    private function seriesStart(array $settings, $company_id, $produced_date)
    {
        $pdo = $this->connect();
        $part = $this->datePart($settings, $produced_date);

        $stmt = $pdo->prepare("SELECT DISTINCT productunits.ProducedDate FROM productunits
            INNER JOIN shop ON shop.SHID = productunits.shop_SHID
            WHERE shop.Company_CMID = ?;");
        $stmt->execute([(int)$company_id]);
        $days = [];
        foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $day)
        {
            if($this->datePart($settings, $day) === $part)
            {
                $days[] = $day;
            }
        }//the days whose code writes the same date part
        if(empty($days))
        {
            return 1;
        }

        $stmt = $pdo->prepare("SELECT COALESCE(MAX(productunits.SeqNo), 0) FROM productunits
            INNER JOIN shop ON shop.SHID = productunits.shop_SHID
            WHERE shop.Company_CMID = ? AND productunits.ProducedDate IN ("
            . implode(',', array_fill(0, count($days), '?')) . ");");
        $stmt->execute(array_merge([(int)$company_id], $days));
        return (int)$stmt->fetchColumn() + 1;
    }//series start

    //what a unit code looks like under this shop's rules, for the settings page
    public function sample($shop_id, $item_barcode = 'COO00001', $produced_date = null)
    {
        $date = self::validDate($produced_date);
        return $this->build($this->settings($shop_id), $item_barcode, $date === null ? date('Y-m-d') : $date, 1);
    }//sample

    //the code for one unit: the pattern with the item, the date and the serial filled in
    private function build(array $settings, $item_barcode, $produced_date, $seq)
    {
        $time = strtotime($produced_date . ' 12:00:00');
        $parts = ['{ITEM}' => $item_barcode];
        foreach(self::DATE_TOKENS as $token => $format)
        {
            $parts[$token] = date($format, $time);
        }//each date part
        //$seq null: the code without its serial, which is what names the number series
        $parts['{SEQ}'] = $seq === null ? '' : str_pad((string)(int)$seq, $settings['seq_length'], '0', STR_PAD_LEFT);

        $code = $settings['pattern'];
        if($settings['separator'] !== '')
        {
            //the separator goes between the parts that are there, never at either end
            $code = preg_replace('/\}\s*\{/', '}' . $settings['separator'] . '{', $code);
        }//separated parts
        return strtr($code, $parts);
    }//build

    //------------------------------------------------------------------ printing

    //Allocate $qty codes for one product made on one day, write their rows and return
    //['print_ref' => 'UP_000001', 'codes' => [...], 'product' => row]. All or nothing.
    public function allocate($shop_id, $product_id, $produced_date, $qty, $user_id, $print_ref = null)
    {
        $shop_id = (int)$shop_id;
        $qty = self::countOf($qty);
        $date = self::validDate($produced_date);
        if($date === null)
        {
            throw new UnitBarcodeRefused(422, 'Enter the production date as a real date.');
        }
        if($qty < 1 || $qty > self::MAX_PER_PRINT)
        {
            throw new UnitBarcodeRefused(422, 'Enter how many units to print, between 1 and ' . self::MAX_PER_PRINT . '.');
        }

        $product = $this->product($product_id, $shop_id);
        $item = trim((string)$product['Barcode']);
        if($item === '')
        {
            throw new UnitBarcodeRefused(422, $product['ItemName'] . ' has no barcode of its own yet, so its units cannot be numbered.');
        }

        $settings = $this->settings($shop_id);
        $pdo = $this->connect();
        $barcodes = new BarcodeSettings();
        $company = (int)$product['CompanyID'];
        $scope = $this->scopeKey($settings, $company, $date);
        $capacity = (int)(pow(10, $settings['seq_length']) - 1);   //the most the serial's digits can carry

        $insert = $pdo->prepare("INSERT INTO productunits (UnitBarcode, ItemBarcode, products_PDID, shop_SHID,
            ProducedDate, SeqNo, UnitStat, PrintRef, PrintedAt, PrintedBy, PrintCount)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1);");

        $own = !$pdo->inTransaction();
        if($own)
        {
            $pdo->beginTransaction();
        }//all the codes of a job, or none

        try
        {
            $ref = $print_ref === null ? $this->nextPrintRef($shop_id) : $print_ref;
            $start = $this->seriesStart($settings, $company, $date);
            $codes = [];
            for($i = 0; $i < $qty; $i++)
            {
                $seq = $barcodes->allocateSequence(self::COUNTER_SHOP, $scope, $start, 1);
                if($seq < 1)
                {
                    throw new UnitBarcodeRefused(500, 'The unit numbering could not be read. Please try again.');
                }
                if($seq > $capacity)
                {
                    $part = $this->datePart($settings, $date);
                    throw new UnitBarcodeRefused(422, 'The unit numbering' . ($part === '' ? '' : ' for ' . $part)
                        . ' is full: a ' . $settings['seq_length'] . '-digit serial carries at most ' . $capacity
                        . ' units. Raise "Serial digits" in Barcode Settings.');
                }//the serial would make this code longer than every other
                $code = $this->build($settings, $item, $date, $seq);
                $insert->execute([$code, $item, (int)$product['PDID'], $shop_id, $date, $seq, self::PRINTED, $ref, (int)$user_id]);
                $codes[] = $code;
            }//each unit

            if($own)
            {
                $pdo->commit();
            }
            return ['print_ref' => $ref, 'codes' => $codes, 'product' => $product];
        }
        catch(Throwable $e)
        {
            if($own && $pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
    }//allocate

    //One print job over several products: [product id => how many], all under one reference
    //and in one transaction, so a job either numbers everything or nothing.
    //Returns ['print_ref' => 'UP_000001', 'counts' => [product id => how many]].
    public function allocateBatch($shop_id, array $quantities, $produced_date, $user_id)
    {
        $pdo = $this->connect();
        $own = !$pdo->inTransaction();
        if($own)
        {
            $pdo->beginTransaction();
        }

        try
        {
            $ref = $this->nextPrintRef($shop_id);
            $counts = [];
            foreach($quantities as $product_id => $qty)
            {
                $batch = $this->allocate($shop_id, $product_id, $produced_date, $qty, $user_id, $ref);
                $counts[(int)$product_id] = count($batch['codes']);
            }//each product
            if(empty($counts))
            {
                throw new UnitBarcodeRefused(422, 'Choose at least one product to number.');
            }

            if($own)
            {
                $pdo->commit();
            }
            return ['print_ref' => $ref, 'counts' => $counts];
        }
        catch(Throwable $e)
        {
            if($own && $pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
    }//allocate batch

    //the next print job reference for this shop (UP_000001, UP_000002, ...)
    public function nextPrintRef($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT PrintRef FROM productunits WHERE shop_SHID = ?
            ORDER BY PUID DESC LIMIT 1;");
        $stmt->execute([(int)$shop_id]);
        $last = $stmt->fetchColumn();
        $number = ($last === false) ? 0 : (int)substr((string)$last, 3);
        return 'UP_' . str_pad((string)($number + 1), 6, '0', STR_PAD_LEFT);
    }//next print ref

    //the last print jobs of a shop, newest first: [['print_ref', 'printed_at', 'units', 'items']]
    public function printJobs($shop_id, $limit = 25)
    {
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->connect()->prepare("SELECT productunits.PrintRef, MIN(productunits.PrintedAt) AS PrintedAt,
            COUNT(*) AS Units, GROUP_CONCAT(DISTINCT COALESCE(products.ItemName, '') ORDER BY products.ItemName SEPARATOR ', ') AS Items
            FROM productunits
            LEFT JOIN products ON products.PDID = productunits.products_PDID
            WHERE productunits.shop_SHID = ?
            GROUP BY productunits.PrintRef
            ORDER BY MIN(productunits.PUID) DESC
            LIMIT " . $limit . ";");
        $stmt->execute([(int)$shop_id]);

        return array_map(function($row) {
            return [
                'print_ref' => $row['PrintRef'],
                'printed_at' => $row['PrintedAt'],
                'units' => (int)$row['Units'],
                'items' => (string)$row['Items'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }//print jobs

    //the units of one print job, oldest first
    public function forPrintRef($shop_id, $print_ref)
    {
        $stmt = $this->connect()->prepare("SELECT productunits.*, products.ItemName FROM productunits
            LEFT JOIN products ON products.PDID = productunits.products_PDID
            WHERE productunits.shop_SHID = ? AND productunits.PrintRef = ? ORDER BY productunits.PUID;");
        $stmt->execute([(int)$shop_id, (string)$print_ref]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }//for print ref

    //the same codes again, for a sticker that was damaged: nothing new is allocated
    public function reprint($shop_id, $print_ref)
    {
        $pdo = $this->connect();
        $pdo->prepare("UPDATE productunits SET PrintCount = PrintCount + 1, LastPrintedAt = NOW()
            WHERE shop_SHID = ? AND PrintRef = ?;")->execute([(int)$shop_id, (string)$print_ref]);
        return $this->forPrintRef($shop_id, $print_ref);
    }//reprint

    //------------------------------------------------------------------ scanning

    //What each of these codes is: [code as given => unit row + 'product_id' (the product with
    //that barcode IN THIS SHOP, null when this shop does not carry it)]. Codes that are not
    //ours are left out, so the caller can tell a unit from anything else.
    public function resolve(array $codes, $shop_id)
    {
        $wanted = [];
        foreach($codes as $code)
        {
            $code = trim((string)$code);
            if($code !== '')
            {
                $wanted[strtoupper($code)][] = $code;
            }
        }//as given, by upper case
        if(empty($wanted))
        {
            return [];
        }

        $keys = array_keys($wanted);
        $stmt = $this->connect()->prepare("SELECT productunits.*, here.PDID AS ShopProductID, here.ProductStat AS ShopProductStat,
            here.ItemType AS ShopItemType, COALESCE(here.ItemName, printed.ItemName) AS ItemName,
            grnheader.GRNHeaderNo
            FROM productunits
            LEFT JOIN products printed ON printed.PDID = productunits.products_PDID
            LEFT JOIN products here ON here.Barcode = productunits.ItemBarcode AND here.shop_SHID = ?
            LEFT JOIN grnheader ON grnheader.GHID = productunits.GRNHeader_GHID
            WHERE UPPER(productunits.UnitBarcode) IN (" . implode(',', array_fill(0, count($keys), '?')) . ");");
        $stmt->execute(array_merge([(int)$shop_id], $keys));

        $found = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $row['product_id'] = $row['ShopProductID'] === null ? null : (int)$row['ShopProductID'];
            foreach($wanted[strtoupper($row['UnitBarcode'])] as $asGiven)
            {
                $found[$asGiven] = $row;
            }//however it was typed
        }//each unit
        return $found;
    }//resolve

    //Mark these units received into a GRN line. Only units that are not already received are
    //taken, so a second attempt changes nothing; returns how many were taken.
    public function markReceived(array $codes, $grn_id, $grn_detail_id, $user_id)
    {
        $keys = [];
        foreach($codes as $code)
        {
            $code = trim((string)$code);
            if($code !== '')
            {
                $keys[] = strtoupper($code);
            }
        }//codes to take
        if(empty($keys))
        {
            return 0;
        }

        $stmt = $this->connect()->prepare("UPDATE productunits
            SET UnitStat = ?, GRNHeader_GHID = ?, grndetails_GDID = ?, ReceivedAt = NOW(), ReceivedBy = ?
            WHERE UPPER(UnitBarcode) IN (" . implode(',', array_fill(0, count($keys), '?')) . ")
            AND UnitStat = ?;");
        $stmt->execute(array_merge([self::RECEIVED, (int)$grn_id, (int)$grn_detail_id, (int)$user_id], $keys, [self::PRINTED]));
        return $stmt->rowCount();
    }//mark received

    //the units a GRN took in, so cancelling or re-opening it can give them back
    public function releaseGrn($grn_id)
    {
        $stmt = $this->connect()->prepare("UPDATE productunits
            SET UnitStat = ?, GRNHeader_GHID = NULL, grndetails_GDID = NULL, ReceivedAt = NULL, ReceivedBy = NULL
            WHERE GRNHeader_GHID = ? AND UnitStat = ?;");
        $stmt->execute([self::PRINTED, (int)$grn_id, self::RECEIVED]);
        return $stmt->rowCount();
    }//release grn

    //------------------------------------------------------------------ reporting

    //what was made, per product and day: [['date', 'product_id', 'name', 'printed', 'received']]
    public function produced($shop_id, $from, $to)
    {
        $from = self::validDate($from);
        $to = self::validDate($to);
        if($from === null || $to === null)
        {
            return [];
        }

        $stmt = $this->connect()->prepare("SELECT productunits.ProducedDate, productunits.products_PDID,
            COALESCE(products.ItemName, '') AS ItemName, COUNT(*) AS Printed,
            SUM(productunits.UnitStat = ?) AS Received
            FROM productunits
            LEFT JOIN products ON products.PDID = productunits.products_PDID
            WHERE productunits.shop_SHID = ? AND productunits.ProducedDate BETWEEN ? AND ?
            AND productunits.UnitStat <> ?
            GROUP BY productunits.ProducedDate, productunits.products_PDID, products.ItemName
            ORDER BY productunits.ProducedDate, products.ItemName, productunits.products_PDID;");
        $stmt->execute([self::RECEIVED, (int)$shop_id, $from, $to, self::VOIDED]);

        return array_map(function($row) {
            return [
                'date' => $row['ProducedDate'],
                'product_id' => (int)$row['products_PDID'],
                'name' => $row['ItemName'],
                'printed' => (int)$row['Printed'],
                'received' => (int)$row['Received'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }//produced

    //how many units this shop made on one day
    public function countProduced($shop_id, $date)
    {
        $date = self::validDate($date);
        if($date === null)
        {
            return 0;
        }
        $stmt = $this->connect()->prepare("SELECT COUNT(*) FROM productunits
            WHERE shop_SHID = ? AND ProducedDate = ? AND UnitStat <> ?;");
        $stmt->execute([(int)$shop_id, $date, self::VOIDED]);
        return (int)$stmt->fetchColumn();
    }//count produced

    //The two halves of a unit code as its sticker prints them: the item barcode (the prefix) and the
    //rest (the date and the serial, without the separator that joined them). Null when the code does
    //not start with its item, so the caller prints it whole. Goes by the item, never by a width: an
    //item barcode can be long and can end in digits.
    public static function splitCode($unit_code, $item_barcode)
    {
        $unit_code = (string)$unit_code;
        $item = trim((string)$item_barcode);
        if($item === '' || strlen($unit_code) <= strlen($item) || strncasecmp($unit_code, $item, strlen($item)) !== 0)
        {
            return null;
        }

        $rest = preg_replace('/^[^A-Za-z0-9]+/', '', substr($unit_code, strlen($item)));
        return $rest === '' ? null : [substr($unit_code, 0, strlen($item)), $rest];
    }//split code

    //The product barcode a typed or scanned unit code belongs to, or '' when the text is not a unit
    //code we printed. The product page's search uses it to find a product from its sticker.
    public function itemBarcodeFor($text, $shop_id)
    {
        $text = trim((string)$text);
        if($text === '')
        {
            return '';
        }

        $found = $this->resolve([$text], $shop_id);
        if(empty($found))
        {
            return '';
        }
        $unit = reset($found);
        return trim((string)$unit['ItemBarcode']);
    }//item barcode for

    //------------------------------------------------------------------ helpers

    //the product, when it belongs to this shop (or to its company, where the catalog is shared)
    private function product($product_id, $shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT products.PDID, products.Barcode, products.ItemName, products.ItemType,
            products.ProductStat, products.shop_SHID, me.Company_CMID AS CompanyID FROM products
            INNER JOIN shop me ON me.SHID = ?
            INNER JOIN shop owner ON owner.SHID = products.shop_SHID
            INNER JOIN company ON company.CMID = me.Company_CMID
            WHERE products.PDID = ?
            AND (products.shop_SHID = me.SHID OR (company.is_multicategory = 1 AND owner.Company_CMID = me.Company_CMID));");
        $stmt->execute([(int)$shop_id, (int)$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if($product === false)
        {
            throw new UnitBarcodeRefused(404, 'That product is not in this shop.');
        }
        if($product['ItemType'] !== 'P')
        {
            throw new UnitBarcodeRefused(422, $product['ItemName'] . ' is a service, so it has no units.');
        }
        return $product;
    }//product

    //a whole number of units, or 0 when it is not one
    private static function countOf($value)
    {
        if(is_string($value))
        {
            $value = trim($value);
        }
        return (is_int($value) || (is_string($value) && preg_match('/^\d{1,6}$/', $value))) ? (int)$value : 0;
    }//count of

    //a real Y-m-d date, or null
    public static function validDate($value)
    {
        if($value instanceof DateTimeInterface)
        {
            return $value->format('Y-m-d');
        }
        if(!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($value), $m))
        {
            return null;
        }
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? trim($value) : null;
    }//valid date
}//ProductUnits
