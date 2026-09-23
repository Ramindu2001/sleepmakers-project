<?php
//What every scanner upload shares (docs/superpowers/specs/2026-09-22-scanner-upload-design.md):
//finding products by barcode, the preview's bookkeeping (left-out lines, what blocks, the
//duplicate batch guard) and the all-or-nothing transaction. GrnScan and TransferScan build
//on it.
abstract class ScanDocument extends Dbh
{
    protected $access;
    protected $batches;
    protected $units;

    public function __construct()
    {
        $this->access = new ShopAccess();
        $this->batches = new ScanBatches();
        $this->units = new ProductUnits();
    }//construct

    //Where a shop prints a unique barcode on every unit (db/UNIT_BARCODES_MODULE.md), fold the
    //unit codes that were read into the item each belongs to: every unit counts one for its
    //product, however often its code was read. Codes that are not units stay unknown.
    protected function foldUnits(array $parsed, $shop_id)
    {
        $codes = array_merge(array_keys($parsed['units']), array_keys($parsed['unknown']));
        if(!empty($codes))
        {
            foreach($this->units->resolve($codes, $shop_id) as $code => $unit)
            {
                $item = trim((string)$unit['ItemBarcode']);
                $parsed['items'][$item] = (isset($parsed['items'][$item]) ? $parsed['items'][$item] : 0) + 1;
                unset($parsed['units'][$code], $parsed['unknown'][(string)$code]);
            }//each unit we printed
        }

        foreach($parsed['units'] as $code => $qty)
        {
            $parsed['unknown'][$code] = $qty;
        }//shaped like a unit, but never printed
        $parsed['units'] = [];
        return $parsed;
    }//fold units

    //a preview line for a code that cannot be used as it is
    protected function errorLine($key, $qty, $message, ?array $product = null)
    {
        return [
            'key' => (string)$key,
            'barcode' => $product === null ? null : trim($product['Barcode']),
            'product_id' => $product === null ? null : (int)$product['PDID'],
            'name' => $product === null ? '' : $product['ItemName'],
            'qty' => $qty,
            'apply_qty' => 0,
            'status' => 'error',
            'message' => $message,
            'left_out' => false,
            'can_leave_out' => true,
            'editable' => false,
        ];
    }//error line

    //marks the left-out lines, counts what blocks, totals what apply() would write and looks
    //for the same batch already applied to this document ($doc_type null: no such guard)
    protected function finish(array $preview, array $decisions, $doc_type)
    {
        $leaveOut = [];
        foreach((isset($decisions['leave_out']) && is_array($decisions['leave_out'])) ? $decisions['leave_out'] : [] as $key)
        {
            if(is_scalar($key))
            {
                $leaveOut[(string)$key] = true;
            }
        }//keys the user left out

        $blocking = 0;
        $applied = [];
        foreach($preview['lines'] as &$line)
        {
            $line['left_out'] = $line['can_leave_out'] && isset($leaveOut[$line['key']]);
            if($line['left_out'])
            {
                continue;
            }
            if($line['status'] === 'error')
            {
                $blocking++;
                continue;
            }
            //'fingerprint' is for lines that share a barcode - one per production date, say
            $signature = isset($line['fingerprint']) ? $line['fingerprint'] : $line['barcode'];
            if($signature !== null && $line['apply_qty'] > 0)
            {
                $applied[$signature] = $line['apply_qty'];
            }
        }
        unset($line);

        $preview['blocking'] = $blocking;
        $preview['applied'] = $applied;
        $preview['duplicate'] = null;
        if($doc_type !== null && !empty($applied))
        {
            $earlier = $this->batches->findDuplicate($doc_type, $preview['doc_id'], ScanBatches::fingerprint($applied));
            if($earlier !== null)
            {
                $preview['duplicate'] = ['user' => (string)$earlier['UserName'], 'at' => $earlier['CreatedAt']];
            }
        }//same batch before?
        $preview['can_apply'] = $blocking === 0 && !empty($applied);
        return $preview;
    }//finish

    //apply() goes ahead only with nothing blocking and a duplicate batch confirmed
    protected function assertCanApply(array $preview, array $decisions)
    {
        if($preview['blocking'] > 0)
        {
            throw new ScanRefused(422, 'Please fix or leave out the lines marked in red.', $preview);
        }
        if(empty($preview['applied']))
        {
            throw new ScanRefused(422, 'There is nothing to add.', $preview);
        }
        if($preview['duplicate'] !== null && empty($decisions['confirm_duplicate']))
        {
            throw new ScanRefused(409, 'This scan batch was already added by ' . $preview['duplicate']['user'] . ' on '
                . $preview['duplicate']['at'] . '. Add it again?', $preview, 'duplicate');
        }
    }//assert can apply

    //runs $work in one database transaction: everything is written, or nothing
    protected function transaction(callable $work)
    {
        $pdo = $this->connect();
        $pdo->beginTransaction();
        try
        {
            $result = $work();
            $pdo->commit();
            return $result;
        }
        catch(Throwable $e)
        {
            if($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
    }//transaction

    //the products of these shops by upper-case barcode (a code two products share lists both)
    protected function productsByBarcode(array $shop_ids)
    {
        $shop_ids = array_values(array_map('intval', $shop_ids));
        if(empty($shop_ids))
        {
            return [];
        }
        $stmt = $this->connect()->prepare("SELECT PDID, Barcode, ItemName, ItemType, ProductStat, shop_SHID, ProdPurchasePrice, ProdSellPrice
            FROM products WHERE shop_SHID IN (" . implode(',', array_fill(0, count($shop_ids), '?')) . ")
            AND Barcode IS NOT NULL AND TRIM(Barcode) <> '' ORDER BY PDID;");
        $stmt->execute($shop_ids);
        $byCode = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $byCode[strtoupper(trim($row['Barcode']))][] = $row;
        }
        return $byCode;
    }//products by barcode

    protected static function knownBarcodes(array $byCode)
    {
        $codes = [];
        foreach($byCode as $rows)
        {
            $codes[] = trim($rows[0]['Barcode']);
        }
        return $codes;
    }//known barcodes

    //[product, null] or [null, reason]: the shop's own product wins over another shop's
    protected function pickProduct(array $candidates, $shop_id)
    {
        $own = array_values(array_filter($candidates, function($row) use ($shop_id) {
            return (int)$row['shop_SHID'] === (int)$shop_id;
        }));
        $pool = count($own) > 0 ? $own : $candidates;
        if(count($pool) === 1)
        {
            return [$pool[0], null];
        }
        return [null, count($pool) === 0 ? 'Not a product in this shop' : 'Two products share this barcode'];
    }//pick product

    //a quantity for people: 5, 2.5 - never 5.000
    public static function qty($number)
    {
        return rtrim(rtrim(number_format((float)$number, 3, '.', ''), '0'), '.');
    }//qty

    //a quantity for the preview: whole numbers as int (5), others as float (2.5)
    public static function number($number)
    {
        $number = (float)$number;
        return $number == (int)$number ? (int)$number : $number;
    }//number

    //a price as the database stores it ("1450.50"), or null when it is not a number >= 0
    public static function money($value)
    {
        if(is_int($value) || is_float($value))
        {
            $value = (string)$value;
        }
        if(!is_string($value) || !preg_match('/^\d+(\.\d+)?$/', trim($value)))
        {
            return null;
        }
        return number_format(round((float)trim($value), 2), 2, '.', '');
    }//money

    //a real Y-m-d date, or null
    public static function validDate($value)
    {
        if(!is_string($value))
        {
            return null;
        }
        $value = trim($value);
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }//valid date
}//ScanDocument
