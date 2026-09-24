<?php
//Takes a quantity of a product from a shop's stock batches, oldest first (inventory rows with
//stock, by INID), the way the transfer page takes it from a batch: each part carries its
//batch's prices, dates and variation. Used by the scanner upload (TransferScan) and by
//warehouse orders (WarehouseOrder).
class StockAllocator extends Dbh
{
    //$taken: inventory id => ['tdid' => the line already taking from it on the transfer being
    //filled (or null), 'qty' => how much is already taken]. Returns
    //['parts' => [...], 'free' => stock left after $taken, 'taken' => already taken from these
    //batches, 'short' => what could not be allocated]
    public function allocate($product_id, $shop_id, $qty, array $taken)
    {
        $stmt = $this->connect()->prepare("SELECT inventory.INID, inventory.CurrentQty, pricehistory.BatchID, pricehistory.PurchasePrice,
            pricehistory.SellingPrice, pricehistory.MnfDate, pricehistory.ExpDate, pricehistory.VariationID
            FROM inventory
            INNER JOIN pricehistory ON pricehistory.PHID = (SELECT MAX(ph.PHID) FROM pricehistory ph WHERE ph.Inventory_INID = inventory.INID)
            WHERE inventory.products_PDID = ? AND inventory.shop_SHID = ? AND inventory.CurrentQty > 0
            ORDER BY inventory.INID ASC;");
        $stmt->execute([(int)$product_id, (int)$shop_id]);

        $free = 0;
        $already = 0;
        $remaining = (float)$qty;
        $parts = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $id = (int)$row['INID'];
            $takenHere = isset($taken[$id]) ? (float)$taken[$id]['qty'] : 0;
            $already += $takenHere;
            $left = (float)$row['CurrentQty'] - $takenHere;
            if($left <= 0)
            {
                continue;
            }//this batch is already all taken
            $free += $left;
            $use = min($left, $remaining);
            if($use > 0)
            {
                $parts[] = [
                    'inventory_id' => $id,
                    'batch_id' => (string)$row['BatchID'],
                    'qty' => self::number($use),
                    'tdid' => isset($taken[$id]['tdid']) ? $taken[$id]['tdid'] : null,
                    'purchase' => $row['PurchasePrice'],
                    'selling' => $row['SellingPrice'],
                    'mnf' => self::date($row['MnfDate']),
                    'exp' => self::date($row['ExpDate']),
                    'variation_id' => empty($row['VariationID']) ? 0 : (int)$row['VariationID'],
                ];
                $remaining -= $use;
            }
        }//each batch, oldest first

        return ['parts' => $parts, 'free' => self::number($free), 'taken' => self::number($already), 'short' => self::number(max(0, $remaining))];
    }//allocate

    private static function number($number)
    {
        $number = round((float)$number, 3);
        return $number == (int)$number ? (int)$number : $number;
    }//number

    private static function date($value)
    {
        $value = trim((string)$value);
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }//date
}//StockAllocator
