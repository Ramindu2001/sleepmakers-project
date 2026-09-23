<?php
//Scanner upload for a GRN (docs/superpowers/specs/2026-09-22-scanner-upload-design.md, §6).
//preview() says what an upload would do; apply() does it in one transaction. The lines it
//writes are the ones manual entry (AJAX/GRN/addGRNDetails.php) would write.
class GrnScan extends ScanDocument
{
    const FEATURE = 2;                          //Goods Received
    const RIGHTS = ['is_create', 'is_edit'];

    public function preview($grn_id, $shop_id, $user_id, $raw, array $decisions = [])
    {
        return $this->build($this->header($grn_id, $shop_id, $user_id, false), $raw, $decisions);
    }//preview

    public function apply($grn_id, $shop_id, $user_id, $raw, array $decisions)
    {
        return $this->transaction(function() use ($grn_id, $shop_id, $user_id, $raw, $decisions) {
            $header = $this->header($grn_id, $shop_id, $user_id, true);
            $preview = $this->build($header, $raw, $decisions);
            $this->assertCanApply($preview, $decisions);

            $pdo = $this->connect();
            $lines = 0;
            $qty = 0;
            foreach($preview['lines'] as $line)
            {
                if($line['left_out'] || $line['status'] === 'error' || $line['apply_qty'] <= 0)
                {
                    continue;
                }
                if($line['existing_id'] !== null)
                {
                    $stmt = $pdo->prepare("SELECT InitQty, UnitPurchasePrice, UnitSellPrice FROM grndetails WHERE GDID = ? FOR UPDATE;");
                    $stmt->execute([$line['existing_id']]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $newQty = (float)$row['InitQty'] + $line['apply_qty'];
                    $pdo->prepare("UPDATE grndetails SET InitQty = ?, CurrentQty = ?, TotalPurchasePrice = ?, TotalSellPrice = ? WHERE GDID = ?;")
                        ->execute([$newQty, $newQty, round($newQty * (float)$row['UnitPurchasePrice'], 2),
                            round($newQty * (float)$row['UnitSellPrice'], 2), $line['existing_id']]);
                    $detail_id = (int)$line['existing_id'];
                }//adds to the line already there
                else
                {
                    $pdo->prepare("INSERT INTO grndetails (InitQty, CurrentQty, UnitPurchasePrice, UnitLabelPrice, UnitSellPrice,
                        TotalPurchasePrice, TotalSellPrice, MnfDate, ExpDate, GRNStat, VariationID, products_PDID, GRNHeader_GHID, Rack_RKID)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?);")
                        ->execute([$line['apply_qty'], $line['apply_qty'], $line['purchase_price'], $line['label_price'],
                            $line['selling_price'], round($line['apply_qty'] * (float)$line['purchase_price'], 2),
                            round($line['apply_qty'] * (float)$line['selling_price'], 2), $line['mnf_date'], $line['exp_date'],
                            $preview['variation_id'], $line['product_id'], $header['GHID'], $preview['rack_id']]);
                    $detail_id = (int)$pdo->lastInsertId();
                }//new line

                if(!empty($line['unit_codes']))
                {
                    //the units join this line, and can never join another one
                    $taken = $this->units->markReceived($line['unit_codes'], $header['GHID'], $detail_id, $user_id);
                    if($taken !== count($line['unit_codes']))
                    {
                        throw new ScanRefused(409, 'Someone took some of those units into another GRN a moment ago. Check the list again.');
                    }//another upload won the race - nothing of this one is written
                }//units

                $lines++;
                $qty += $line['apply_qty'];
            }//each line

            $this->batches->record(ScanBatches::GRN, $header['GHID'], $shop_id, $user_id, $preview['applied'], $preview['scans']);
            return [
                'message' => 'Added ' . $lines . ' product(s), ' . self::qty($qty) . ' item(s) to ' . $header['GRNHeaderNo'] . '.',
                'result' => ['doc_id' => (int)$header['GHID'], 'lines' => $lines, 'qty' => $qty],
            ];
        });
    }//apply

    //the GRN, when this user may add to it now (locked for update when $lock)
    private function header($grn_id, $shop_id, $user_id, $lock)
    {
        $stmt = $this->connect()->prepare("SELECT GHID, GRNHeaderNo, GRNStat, shop_SHID FROM grnheader WHERE GHID = ?" . ($lock ? " FOR UPDATE" : "") . ";");
        $stmt->execute([(int)$grn_id]);
        $header = $stmt->fetch(PDO::FETCH_ASSOC);
        if($header === false || (int)$header['shop_SHID'] !== (int)$shop_id)
        {
            throw new ScanRefused(404, 'This GRN is not in this shop.');
        }
        if(!$this->access->hasFeatureRight($user_id, $shop_id, self::FEATURE, self::RIGHTS))
        {
            throw new ScanRefused(403, 'You do not have the right to change GRNs in this shop.');
        }
        if(!in_array((int)$header['GRNStat'], [0, 1], true))
        {
            throw new ScanRefused(409, 'This GRN is already verified or cancelled.');
        }
        return $header;
    }//header

    private function build(array $header, $raw, array $decisions)
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT shop.SHID, shop.is_variation, shop.is_labelprice, shop.is_expire, shop.is_racks,
            shop.Company_CMID, company.is_multicategory FROM shop INNER JOIN company ON company.CMID = shop.Company_CMID WHERE shop.SHID = ?;");
        $stmt->execute([$header['shop_SHID']]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);

        $shop_ids = [(int)$shop['SHID']];
        if((int)$shop['is_multicategory'] === 1)
        {
            $stmt = $pdo->prepare("SELECT SHID FROM shop WHERE Company_CMID = ?;");
            $stmt->execute([$shop['Company_CMID']]);
            $shop_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }//the company's products, as the GRN product search offers
        $byCode = $this->productsByBarcode($shop_ids);
        //where the shop numbers every unit, a unit code is an item code plus a fixed number of
        //characters; the parser sets those tokens aside and the database decides what they are
        $parsed = ScanParser::parse($raw, self::knownBarcodes($byCode), $this->units->suffixLength($shop['SHID']));

        $options = [
            'label_price' => (int)$shop['is_labelprice'] === 1,
            'expiry' => (int)$shop['is_expire'] === 1,
            'racks' => (int)$shop['is_racks'] === 1,
        ];
        $rack_id = 1;
        if($options['racks'])
        {
            $options['racks_list'] = $this->racks($shop['SHID']);
            $ids = array_column($options['racks_list'], 'id');
            $wanted = isset($decisions['rack_id']) ? (int)$decisions['rack_id'] : 0;
            $rack_id = in_array($wanted, $ids, true) ? $wanted : (empty($ids) ? 1 : $ids[0]);
        }//one rack for the upload

        $withVariations = [];
        if((int)$shop['is_variation'] === 1)
        {
            $withVariations = array_flip(array_map('intval', $pdo->query("SELECT DISTINCT products_PDID FROM variations;")->fetchAll(PDO::FETCH_COLUMN)));
        }//products that need a variation picked

        $lines = [];
        foreach($parsed['items'] as $barcode => $qty)
        {
            $barcode = (string)$barcode;
            [$product, $reason] = $this->pickProduct($byCode[strtoupper($barcode)], $shop['SHID']);
            if($product === null)
            {
                $lines[] = $this->errorLine($barcode, $qty, $reason);
            }
            elseif($product['ItemType'] !== 'P')
            {
                $lines[] = $this->errorLine($barcode, $qty, 'Service item - no stock', $product);
            }
            elseif((int)$product['ProductStat'] !== 1)
            {
                $lines[] = $this->errorLine($barcode, $qty, 'Inactive product', $product);
            }
            elseif(isset($withVariations[(int)$product['PDID']]))
            {
                $lines[] = $this->errorLine($barcode, $qty, 'Has variations - use Add Products', $product);
            }
            else
            {
                $lines[] = $this->line($header, $shop, $options, $product, $barcode, $qty, $decisions);
            }
        }//each code

        //the unit codes, and anything unknown that might still be one (an older code shape)
        $resolved = $this->units->resolve(array_merge(array_keys($parsed['units']), array_keys($parsed['unknown'])), $shop['SHID']);
        $groups = [];
        foreach($parsed['units'] + $parsed['unknown'] as $code => $scanned)
        {
            $code = (string)$code;
            if(!isset($resolved[$code]))
            {
                $lines[] = $this->errorLine($code, $scanned, isset($parsed['units'][$code])
                    ? 'Not a unit we printed' : 'Not a product in this shop');
                continue;
            }//nothing of ours

            $unit = $resolved[$code];
            if((int)$unit['UnitStat'] === ProductUnits::RECEIVED)
            {
                $lines[] = $this->errorLine($code, $scanned, 'Already received on '
                    . ($unit['GRNHeaderNo'] === null ? 'another GRN' : $unit['GRNHeaderNo']));
                continue;
            }//taken in before - never a second time

            //the unit's item, as this shop carries it: the same rules a product code gets
            $item = strtoupper($unit['ItemBarcode']);
            [$product, $reason] = isset($byCode[$item]) ? $this->pickProduct($byCode[$item], $shop['SHID'])
                : [null, 'Not a product in this shop'];
            if($product === null)
            {
                $lines[] = $this->errorLine($code, $scanned, $reason);
                continue;
            }
            if($product['ItemType'] !== 'P')
            {
                $lines[] = $this->errorLine($code, $scanned, 'Service item - no stock', $product);
                continue;
            }
            if((int)$product['ProductStat'] !== 1)
            {
                $lines[] = $this->errorLine($code, $scanned, 'Inactive product', $product);
                continue;
            }
            if(isset($withVariations[(int)$product['PDID']]))
            {
                $lines[] = $this->errorLine($code, $scanned, 'Has variations - use Add Products', $product);
                continue;
            }

            $key = 'unit:' . $unit['ItemBarcode'] . ':' . $unit['ProducedDate'];
            if(!isset($groups[$key]))
            {
                $groups[$key] = ['product' => $product, 'date' => $unit['ProducedDate'], 'codes' => [], 'twice' => 0];
            }
            $groups[$key]['codes'][] = $unit['UnitBarcode'];
            $groups[$key]['twice'] += $scanned - 1;     //a code read more than once is still one unit
        }//each unit code

        foreach($groups as $key => $group)
        {
            $lines[] = $this->unitLine($header, $shop, $options, $group, $key, $decisions);
        }//each product and production date

        return $this->finish([
            'context' => 'grn',
            'doc_id' => (int)$header['GHID'],
            'scans' => $parsed['scans'],
            'truncated' => $parsed['truncated'],
            'lines' => $lines,
            'options' => $options,
            'rack_id' => $rack_id,
            'variation_id' => (int)$shop['is_variation'] === 1 ? 0 : 1,   //manual entry's "no variation"
        ], $decisions, ScanBatches::GRN);
    }//build

    //One product's line. $produced_date is set for units: the line then belongs to that
    //production date, so units made on two days never land in one line.
    private function line(array $header, array $shop, array $options, array $product, $barcode, $qty, array $decisions, $produced_date = null)
    {
        $pdo = $this->connect();
        $line = [
            'key' => $barcode, 'barcode' => $barcode, 'product_id' => (int)$product['PDID'], 'name' => $product['ItemName'],
            'qty' => $qty, 'apply_qty' => $qty, 'status' => 'ok', 'message' => 'New line', 'left_out' => false,
            'can_leave_out' => true, 'editable' => false, 'existing_id' => null, 'existing_qty' => null, 'prices_locked' => false,
        ];

        $sql = "SELECT GDID, InitQty, UnitPurchasePrice, UnitSellPrice, UnitLabelPrice, MnfDate, ExpDate FROM grndetails
            WHERE GRNHeader_GHID = ? AND products_PDID = ?" . ($produced_date === null ? '' : " AND MnfDate <=> ?")
            . " ORDER BY GDID DESC LIMIT 1;";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($produced_date === null ? [$header['GHID'], $product['PDID']]
            : [$header['GHID'], $product['PDID'], $produced_date]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if($existing !== false)
        {
            return array_merge($line, [
                'message' => 'Adds to the existing line: ' . self::qty($existing['InitQty']) . ' → ' . self::qty((float)$existing['InitQty'] + $qty),
                'existing_id' => (int)$existing['GDID'],
                'existing_qty' => self::qty($existing['InitQty']),
                'prices_locked' => true,
                'purchase_price' => self::money($existing['UnitPurchasePrice']),
                'selling_price' => self::money($existing['UnitSellPrice']),
                'label_price' => self::money($existing['UnitLabelPrice'] === null ? '0' : $existing['UnitLabelPrice']),
                'mnf_date' => $existing['MnfDate'],
                'exp_date' => $existing['ExpDate'],
            ]);
        }//adds to the product's line

        //new line: prices of the product's latest batch here, else the product's own prices
        $stmt = $pdo->prepare("SELECT pricehistory.PurchasePrice, pricehistory.SellingPrice, pricehistory.labelPrice FROM pricehistory
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
            WHERE inventory.products_PDID = ? AND inventory.shop_SHID = ? ORDER BY pricehistory.PHID DESC LIMIT 1;");
        $stmt->execute([$product['PDID'], $shop['SHID']]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        $selling = self::money($batch ? $batch['SellingPrice'] : $product['ProdSellPrice']);
        $prices = [
            'purchase' => self::money($batch ? $batch['PurchasePrice'] : $product['ProdPurchasePrice']),
            'selling' => $selling,
            'label' => $options['label_price'] ? self::money($batch && $batch['labelPrice'] !== null ? $batch['labelPrice'] : $selling) : '0.00',
        ];

        $typed = (isset($decisions['prices'][$product['PDID']]) && is_array($decisions['prices'][$product['PDID']])) ? $decisions['prices'][$product['PDID']] : [];
        $problem = null;
        foreach(['purchase', 'selling', 'label'] as $field)
        {
            if($field === 'label' && !$options['label_price'])
            {
                continue;
            }//label prices not used here
            if(array_key_exists($field, $typed))
            {
                $value = self::money($typed[$field]);
                if($value === null)
                {
                    $problem = $problem ?: 'Enter a valid ' . $field . ' price';
                    $value = is_scalar($typed[$field]) ? (string)$typed[$field] : '';
                }
                $prices[$field] = $value;
            }//typed prices win
            elseif($prices[$field] === null)
            {
                $problem = $problem ?: 'Enter a valid ' . $field . ' price';
            }//no price anywhere
        }//each price

        $mnf = $produced_date === null ? date('Y-m-d') : $produced_date;
        $exp = date('Y-m-d');                   //manual entry stamps today when the shop has no expiry
        if($options['expiry'] && $produced_date !== null)
        {
            //the unit says when it was made; only the expiry is still the operator's to enter
            $dates = (isset($decisions['dates'][$product['PDID']]) && is_array($decisions['dates'][$product['PDID']])) ? $decisions['dates'][$product['PDID']] : [];
            $typedExp = (isset($dates['exp']) && is_string($dates['exp'])) ? $dates['exp'] : '';
            $exp = self::validDate($typedExp);
            if($exp === null || !($exp > $mnf))
            {
                $problem = $problem ?: 'Enter an Exp date after ' . $mnf;
                $exp = $typedExp;
            }
        }//units, with expiry tracked
        elseif($options['expiry'])
        {
            $dates = (isset($decisions['dates'][$product['PDID']]) && is_array($decisions['dates'][$product['PDID']])) ? $decisions['dates'][$product['PDID']] : [];
            $typedMnf = (isset($dates['mnf']) && is_string($dates['mnf'])) ? $dates['mnf'] : '';
            $typedExp = (isset($dates['exp']) && is_string($dates['exp'])) ? $dates['exp'] : '';
            $mnf = self::validDate($typedMnf);
            $exp = self::validDate($typedExp);
            $today = date('Y-m-d');
            if($mnf === null || $exp === null)
            {
                $problem = $problem ?: 'Enter the Mnf and Exp dates';
            }
            elseif(!($mnf < $today && $exp > $today && $exp > $mnf))
            {
                $problem = $problem ?: 'Mnf must be before today; Exp after today and after Mnf';
            }
            $mnf = $mnf === null ? $typedMnf : $mnf;
            $exp = $exp === null ? $typedExp : $exp;
        }//expiry tracked, as on manual entry

        $line['editable'] = true;
        $line['purchase_price'] = $prices['purchase'];
        $line['selling_price'] = $prices['selling'];
        $line['label_price'] = $prices['label'];
        $line['mnf_date'] = $mnf;
        $line['exp_date'] = $exp;
        if($problem !== null)
        {
            $line['status'] = 'error';
            $line['message'] = $problem;
        }
        return $line;
    }//line

    //One line for the units of one product made on one day. Its quantity is how many DIFFERENT
    //unit codes were read: a code read twice is still one unit (db/UNIT_BARCODES_MODULE.md).
    private function unitLine(array $header, array $shop, array $options, array $group, $key, array $decisions)
    {
        $count = count($group['codes']);
        $line = $this->line($header, $shop, $options, $group['product'], trim($group['product']['Barcode']),
            $count, $decisions, $group['date']);

        $message = $count . ' unit(s) made ' . $group['date'];
        if($group['twice'] > 0)
        {
            $message .= ', ' . $group['twice'] . ' scanned twice';
        }//read more than once, counted once
        if($line['existing_id'] !== null)
        {
            $message .= ' - adds to the line already on this GRN: '
                . self::qty($line['existing_qty']) . ' → ' . self::qty((float)$line['existing_qty'] + $count);
        }//joins what is there

        $line['key'] = $key;
        $line['fingerprint'] = $key;            //two dates of one product are two batches
        $line['unit_codes'] = $group['codes'];
        $line['message'] = $message;
        if($group['twice'] > 0 && $line['status'] === 'ok')
        {
            $line['status'] = 'warn';
        }//worth a look, but nothing to fix
        return $line;
    }//unit line

    private function racks($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT rack.RKID, rack.RackName, sections.SectionName FROM rack
            INNER JOIN sections ON sections.SEID = rack.Sections_SEID WHERE sections.shop_SHID = ? ORDER BY sections.SEID, rack.RKID;");
        $stmt->execute([(int)$shop_id]);
        return array_map(function($row) {
            return ['id' => (int)$row['RKID'], 'name' => $row['SectionName'] . ' - ' . $row['RackName']];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }//racks
}//GrnScan
