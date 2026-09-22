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
                }//new line
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
        $parsed = ScanParser::parse($raw, self::knownBarcodes($byCode));

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
        foreach($parsed['unknown'] as $token => $qty)
        {
            $lines[] = $this->errorLine($token, $qty, 'Not a product in this shop');
        }

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

    private function line(array $header, array $shop, array $options, array $product, $barcode, $qty, array $decisions)
    {
        $pdo = $this->connect();
        $line = [
            'key' => $barcode, 'barcode' => $barcode, 'product_id' => (int)$product['PDID'], 'name' => $product['ItemName'],
            'qty' => $qty, 'apply_qty' => $qty, 'status' => 'ok', 'message' => 'New line', 'left_out' => false,
            'can_leave_out' => true, 'editable' => false, 'existing_id' => null, 'existing_qty' => null, 'prices_locked' => false,
        ];

        $stmt = $pdo->prepare("SELECT GDID, InitQty, UnitPurchasePrice, UnitSellPrice, UnitLabelPrice, MnfDate, ExpDate FROM grndetails
            WHERE GRNHeader_GHID = ? AND products_PDID = ? ORDER BY GDID DESC LIMIT 1;");
        $stmt->execute([$header['GHID'], $product['PDID']]);
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

        $mnf = date('Y-m-d');
        $exp = date('Y-m-d');                   //manual entry stamps today when the shop has no expiry
        if($options['expiry'])
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
