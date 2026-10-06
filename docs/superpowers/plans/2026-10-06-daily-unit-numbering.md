# Daily Unit Numbering Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Number every unit with one running serial per company per day, find a product from a unit's sticker on the product page, print the item barcode above the bars and the date and serial below them, and print three stickers of each unit by default.

**Architecture:** `Model/product_unit_class.php` stays the single owner of numbering. Only the *series* a serial belongs to changes (the code's date part, per company, seeded from the rows already there), so the code format, the scan parser, `resolve()` and every stored unit are untouched. The label work is pure helpers in `Includes/barcode_helper.php` (copies, ceiling, bar height) used by `Public/print-barcode.php` and the dialog. The search is one parameterised query that also matches a unit code's item.

**Tech Stack:** PHP 8.2 on XAMPP/Apache, MariaDB 10.4 (PDO), jQuery + Bootstrap dialog, PHPUnit 11.5 (`C:/xampp/php/php.exe tools/phpunit.phar`, database `sleepmakers_test`), PHP end-to-end scripts over HTTP (`tests/e2e/`, run against the live dev database with their own `e2e` records), headless Chrome UI scripts on Node 24 (`tests/ui/`).

**Spec:** `docs/superpowers/specs/2026-10-06-daily-unit-numbering-design.md`

All commands run from `C:\xampp\htdocs\sleepmakers\sleepmakers` (in Git Bash: `cd /c/xampp/htdocs/sleepmakers/sleepmakers`). On this machine the application is served at `http://localhost/sleepmakers/sleepmakers`; the e2e and UI scripts take that as `[base-url]` / `BASE`.

## Global Constraints

- The unit code format, `ProductUnits::suffixLength()`, `ScanParser`, `ProductUnits::resolve()` and the UNIQUE key on `productunits.UnitBarcode` do not change.
- Default pattern `{ITEM}{YY}{MM}{DD}{SEQ}` with a 4-digit serial (was `{ITEM}{YY}{MM}{SEQ}`); a shop that saved a pattern keeps it.
- Series key `unit:c<company id>:<date part>`, e.g. `unit:c2:261006`, counter row in `barcodesequence` under shop id `0`; a new series starts at the highest serial already issued in it (any item, any shop of the company, any state) plus one.
- A serial that would not fit the configured width (9,999 at 4 digits) is refused, the job rolls back, and the message names the `Serial digits` setting.
- New label option `unit_copies`: default **3**, range 1 to 100, same option set as `copies`; the plain `copies` option is untouched.
- One job renders at most `bcMaxLabelsPerJob()` = **1,000** stickers; `sum(units) x unit_copies` is checked **before** numbering, and a reprint (`units in the job x copies`) is checked too.
- No schema change and no data migration. Units already printed, received or dispatched are never touched.
- PHP 8.2, MariaDB 10.4, PHPUnit 11.5 with `failOnWarning` and `failOnNotice`: new code must raise no notice or warning.
- House style: Allman braces in `Model/` classes, closing-brace comments such as `}//allocate`, `//` comments in prose; `Includes/barcode_helper.php` keeps its docblock style and its `if (!function_exists(...))` guard; `Assets/jquery/barcode-label.js` stays pure ASCII.
- Commits: conventional style (`feat(units): ...`, `fix(...)`, `test(...)`, `docs: ...`), staged **by file name** (never `git add -A`), each ending with the trailer `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`. A file that already has uncommitted changes from the user is never committed without their say-so (Task 0).

## Review Focus

The spec says what to build; these are inputs and conditions it implies but no obvious test exercises. Each one has a test in the task named after it.

1. **A product barcode that is long or ends in digits** (live: `MATCOO26092300001`, 17 characters, next to a 10-digit suffix). The prefix/suffix split and the search go by the stored item barcode, never by a fixed width. *Task 2: `test_a_code_splits_into_the_item_and_the_rest`.*
2. **Search text that is blank, has quotes, `%` or `\`, or is a unit code in lower case.** No SQL error; plain text finds what it found before; a printed code is found in any case. *Task 2 (`test_a_typed_unit_code_names_the_item_it_was_printed_for`), Task 4 (e2e).*
3. **A separator in the pattern, or `{ITEM}` not first.** The suffix loses the joining separator; a code that does not start with its item prints whole, on one line. *Task 2 (`test_the_separator_that_joined_the_parts_is_dropped`, `test_a_code_that_does_not_start_with_its_item_is_not_split`).*
4. **Label switches and tiny stickers: bars off, number off, 25 x 15 mm.** The two lines appear or not as the switches say, and nothing is clipped on any of the nine sizes. *Task 3 (`UnitLabelTest`), Task 6 (UI script).*
5. **A day's series filling up halfway through a job, and a back-dated day that already holds the old per-item serials.** All or nothing, no repeated code, the refused number goes back. *Task 1 (`test_a_job_that_runs_out_of_serials_numbers_nothing_at_all`, `test_a_day_that_already_has_per_item_serials_carries_on_after_the_highest`).*

---

### Task 0: Before the first change (decisions, no code)

**Files:** none changed.

The working tree holds uncommitted work from before this plan. Three files in it are also edited here: `View/modals/product_barcode.php` (a CSS fix for the Print button), `tests/GrnScanUnitsTest.php` (GRN price tests) and `tests/e2e/lib.php` (the `SLEEPMAKERSID` cookie name, tied to the `.htaccess` change). The spec promised to keep that work separate or include it only on request.

- [ ] **Step 1: Look at what is pending**

Run: `git status --short`
Expected: ` M` on `.htaccess`, `AJAX/guiPos/getproducts.php`, `Model/scan_grn_class.php`, `Model/warehouse_order_class.php`, `Public/customer-order.php`, `Public/gui-pos.php`, `View/modals/product_barcode.php`, `tests/DispatchScanTest.php`, `tests/GrnScanUnitsTest.php`, `tests/e2e/lib.php`, plus `??` on this plan and the spec if they are not committed yet.

- [ ] **Step 2: Ask the user three things and wait for the answers**

1. **Branch.** "Work on `feature/daily-unit-numbering` (the earlier modules each had a feature branch) or on `main`?" Default: the feature branch (`git switch -c feature/daily-unit-numbering`; the pending changes come along untouched).
2. **The three shared files.** "`View/modals/product_barcode.php`, `tests/GrnScanUnitsTest.php` and `tests/e2e/lib.php` already hold your uncommitted changes. Shall I (a) leave that to you and commit those three files only with your changes included, saying so in the commit message, or (b) will you commit your own changes first?" Default: (b) is cleaner; take (a) only if they say so.
3. **The two documents.** "Commit the spec and this plan first (`docs: design for daily unit numbering`, `docs: implementation plan for daily unit numbering`), as the earlier modules did?" Default: yes.

- [ ] **Step 3: Record the answers**

Write the three answers at the top of the task report. Every later commit step follows them. If the answer to 2 is (a), each commit step that stages one of the three files adds the line `Includes the pending changes already in <file>.` to the commit body.

- [ ] **Step 4: Confirm the baseline**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --colors=never --filter 'ProductUnitsTest|UnitBarcodesMigrationTest|GrnScanUnitsTest|TransferScanTest|DispatchScanTest|ScanParserTest'`
Expected: `OK` (these are green before any change; the 2 known failures are in `GrnScanTest` and are not part of this filter).

---

### Task 1: One running serial for the day, per company

**Files:**
- Modify: `Model/product_unit_class.php` (header comment, constants, `scopeKey`, new `datePart` and `seriesStart`, `allocate`, `product`)
- Modify: `db/unit_barcodes_migration.php:29`, `db/unit_barcodes.sql:36` (default pattern)
- Modify: `tests/ProductUnitsTest.php`, `tests/UnitBarcodesMigrationTest.php:30`, `tests/GrnScanUnitsTest.php:148-149`

**Interfaces:**
- Consumes: `BarcodeSettings::allocateSequence($shop_id, $scope_key, $seq_start, $seq_step): int` (unchanged, `Model/barcode_settings_class.php`).
- Produces: `ProductUnits::COUNTER_SHOP = 0`; `ProductUnits::scopeKey(array $settings, $company_id, $produced_date): string`; `allocate()` and `allocateBatch()` now draw one serial series per company and date part; a full series throws `UnitBarcodeRefused` with `->status === 422` and a message containing `Serial digits`; `ProductUnits::DEFAULTS['pattern'] === '{ITEM}{YY}{MM}{DD}{SEQ}'`.

- [ ] **Step 1: Rewrite the numbering tests (failing first)**

In `tests/ProductUnitsTest.php`, replace everything from line 1 down to and including the method `test_the_shop_can_change_the_pattern` (the divider comment `// ---- what is written ---...` and everything after it stay exactly as they are) with:

```php
<?php
//Model/product_unit_class.php: one row per printed unit, its code, and the rules that make
//the same unit impossible to register twice.
final class ProductUnitsTest extends DatabaseTestCase
{
    private ProductUnits $units;
    private int $company;
    private int $warehouse;
    private int $showroom;
    private int $bed;
    private int $sheet;
    private int $pillow;
    private int $alice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->units = new ProductUnits();
        $this->company = $this->createCompany();
        $this->warehouse = $this->createShop($this->company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($this->company, ['ShopName' => 'Valentino Italy']);
        $this->alice = $this->createUser('alice', 'x', $this->createRole('Store Keeper'));
        $this->bed = $this->createProduct($this->warehouse, 'COO00001', 'Cooler Mattress');
        $this->sheet = $this->createProduct($this->warehouse, 'LIN00001', 'Bedsheet');
        $this->pillow = $this->createProduct($this->warehouse, 'PIL00001', 'Pillow');
    }

    private function print($product_id, $date, $qty, $shop_id = null)
    {
        return $this->units->allocate($shop_id ?? $this->warehouse, $product_id, $date, $qty, $this->alice);
    }

    //a unit row as it was written before the day's series was shared: only its code and serial matter
    private function legacy($code, $item, $product_id, $date, $seq, $shop_id = null, $state = ProductUnits::PRINTED)
    {
        return $this->insert('productunits', ['UnitBarcode' => $code, 'ItemBarcode' => $item,
            'products_PDID' => $product_id, 'shop_SHID' => $shop_id ?? $this->warehouse, 'ProducedDate' => $date,
            'SeqNo' => $seq, 'UnitStat' => $state, 'PrintRef' => 'UP_OLD', 'PrintedAt' => date('Y-m-d H:i:s'),
            'PrintedBy' => $this->alice]);
    }

    //the warehouse numbers by the day with a serial of this many digits
    private function serialDigits($digits)
    {
        $this->insert('barcodesettings', ['shop_SHID' => $this->warehouse, 'UnitMode' => 1,
            'UnitPattern' => '{ITEM}{YY}{MM}{DD}{SEQ}', 'UnitSeqLength' => $digits, 'UnitSeparator' => '']);
    }

    private function rowCount($sql)
    {
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    // ---- building the code -------------------------------------------------------------

    public function test_a_unit_code_is_the_item_the_day_and_a_serial()
    {
        $batch = $this->print($this->bed, '2025-09-12', 3);

        $this->assertSame(['COO000012509120001', 'COO000012509120002', 'COO000012509120003'], $batch['codes']);
    }

    public function test_a_shop_with_no_saved_rules_numbers_by_the_day()
    {
        $this->assertSame('{ITEM}{YY}{MM}{DD}{SEQ}', $this->units->settings($this->warehouse)['pattern']);
    }

    public function test_the_serial_carries_on_where_the_last_print_left_off()
    {
        $this->print($this->bed, '2025-09-12', 2);
        $batch = $this->print($this->bed, '2025-09-12', 2);

        $this->assertSame(['COO000012509120003', 'COO000012509120004'], $batch['codes']);
    }

    public function test_every_product_made_on_a_day_shares_one_running_serial()
    {
        $codes = array_merge(
            $this->print($this->bed, '2025-09-12', 2)['codes'],       //A, A
            $this->print($this->sheet, '2025-09-12', 1)['codes'],     //B
            $this->print($this->pillow, '2025-09-12', 1)['codes'],    //C
            $this->print($this->sheet, '2025-09-12', 1)['codes']      //B again
        );

        $this->assertSame(['COO000012509120001', 'COO000012509120002', 'LIN000012509120003',
            'PIL000012509120004', 'LIN000012509120005'], $codes);
    }

    public function test_one_job_over_several_products_shares_the_serial_too()
    {
        $batch = $this->units->allocateBatch($this->warehouse, [$this->bed => 2, $this->sheet => 2], '2025-09-12', $this->alice);
        $rows = $this->pdo->query('SELECT UnitBarcode FROM productunits ORDER BY PUID')->fetchAll(PDO::FETCH_COLUMN);

        $this->assertSame(['COO000012509120001', 'COO000012509120002', 'LIN000012509120003', 'LIN000012509120004'], $rows);
        $this->assertSame([$this->bed => 2, $this->sheet => 2], $batch['counts']);
    }

    public function test_a_new_day_starts_again_at_one()
    {
        $this->print($this->bed, '2025-09-12', 2);

        $this->assertSame(['LIN000012509130001'], $this->print($this->sheet, '2025-09-13', 1)['codes']);
        $this->assertSame(['COO000012509120003'], $this->print($this->bed, '2025-09-12', 1)['codes'], 'the earlier day carries on');
    }

    public function test_two_shops_of_one_company_share_one_series()
    {
        $showroomPiece = $this->createProduct($this->showroom, 'SHW00001', 'Showroom piece');
        $this->print($this->bed, '2025-09-12', 1);

        $batch = $this->print($showroomPiece, '2025-09-12', 1, $this->showroom);

        $this->assertSame(['SHW000012509120002'], $batch['codes']);
    }

    public function test_two_companies_never_share_a_series()
    {
        $elsewhere = $this->createShop($this->createCompany(), ['ShopName' => 'Elsewhere']);
        $theirs = $this->createProduct($elsewhere, 'ELS00001', 'Their bed');
        $this->print($this->bed, '2025-09-12', 3);

        $batch = $this->print($theirs, '2025-09-12', 1, $elsewhere);

        $this->assertSame(['ELS000012509120001'], $batch['codes']);
    }

    public function test_the_number_series_is_named_after_the_company_and_the_date_part()
    {
        $rules = $this->units->settings($this->warehouse);
        $key = 'unit:c' . $this->company . ':';

        $this->assertSame($key . '250912', $this->units->scopeKey($rules, $this->company, '2025-09-12'));
        $this->assertSame($key . '251001', $this->units->scopeKey($rules, $this->company, '2025-10-01'));

        $monthly = ['mode' => true, 'pattern' => '{ITEM}{YY}{MM}{SEQ}', 'seq_length' => 4, 'separator' => ''];
        $this->assertSame($key . '2509', $this->units->scopeKey($monthly, $this->company, '2025-09-12'));
        $this->assertSame($key . '2509', $this->units->scopeKey($monthly, $this->company, '2025-09-30'));

        $dashed = ['mode' => true, 'pattern' => '{ITEM}-{YY}-{MM}-{SEQ}', 'seq_length' => 4, 'separator' => '-'];
        $this->assertSame($key . '25-09', $this->units->scopeKey($dashed, $this->company, '2025-09-12'));

        $joined = ['mode' => true, 'pattern' => '{ITEM}{YY}{MM}{DD}{SEQ}', 'seq_length' => 4, 'separator' => '-'];
        $this->assertSame($key . '25-09-12', $this->units->scopeKey($joined, $this->company, '2025-09-12'));

        $undated = ['mode' => true, 'pattern' => '{ITEM}{SEQ}', 'seq_length' => 4, 'separator' => ''];
        $this->assertSame($key, $this->units->scopeKey($undated, $this->company, '2025-09-12'));
    }

    public function test_the_shop_can_change_the_pattern()
    {
        $this->insert('barcodesettings', ['shop_SHID' => $this->warehouse, 'UnitMode' => 1,
            'UnitPattern' => '{ITEM}{YY}{MM}{DD}{SEQ}', 'UnitSeqLength' => 3, 'UnitSeparator' => '-']);

        $this->assertSame(['COO00001-25-09-12-001'], $this->print($this->bed, '2025-09-12', 1)['codes']);
    }

    // ---- a series that already holds serials -------------------------------------------

    public function test_a_day_that_already_has_per_item_serials_carries_on_after_the_highest()
    {
        //how the numbering used to run: every item counted from one
        foreach ([1, 2, 3] as $n) {
            $this->legacy(sprintf('COO00001250912%04d', $n), 'COO00001', $this->bed, '2025-09-12', $n);
        }
        foreach ([1, 2] as $n) {
            $this->legacy(sprintf('LIN00001250912%04d', $n), 'LIN00001', $this->sheet, '2025-09-12', $n);
        }

        $this->assertSame(['COO000012509120004'], $this->print($this->bed, '2025-09-12', 1)['codes']);
        $this->assertSame(['LIN000012509120005'], $this->print($this->sheet, '2025-09-12', 1)['codes']);
    }

    public function test_a_voided_unit_still_holds_its_serial()
    {
        $this->legacy('COO000012509120007', 'COO00001', $this->bed, '2025-09-12', 7, null, ProductUnits::VOIDED);

        $this->assertSame(['LIN000012509120008'], $this->print($this->sheet, '2025-09-12', 1)['codes']);
    }

    public function test_units_of_another_company_do_not_move_the_start()
    {
        $elsewhere = $this->createShop($this->createCompany(), ['ShopName' => 'Elsewhere']);
        $theirs = $this->createProduct($elsewhere, 'ELS00001', 'Their bed');
        $this->legacy('ELS000012509120009', 'ELS00001', $theirs, '2025-09-12', 9, $elsewhere);

        $this->assertSame(['COO000012509120001'], $this->print($this->bed, '2025-09-12', 1)['codes']);
    }

    public function test_a_monthly_pattern_starts_after_the_highest_serial_of_the_whole_month()
    {
        $this->insert('barcodesettings', ['shop_SHID' => $this->warehouse, 'UnitMode' => 1,
            'UnitPattern' => '{ITEM}{YY}{MM}{SEQ}', 'UnitSeqLength' => 4, 'UnitSeparator' => '']);
        $this->legacy('COO0000125090004', 'COO00001', $this->bed, '2025-09-05', 4);

        //another day of the same month: the code does not say which day, so the series is the month's
        $this->assertSame(['LIN0000125090005'], $this->print($this->sheet, '2025-09-12', 1)['codes']);
        $this->assertSame(['LIN0000125100001'], $this->print($this->sheet, '2025-10-01', 1)['codes']);
    }

    // ---- a series that is full ---------------------------------------------------------

    public function test_numbering_stops_when_the_serial_has_no_digits_left_and_gives_nothing_away()
    {
        $this->serialDigits(1);
        $this->print($this->bed, '2025-09-12', 9);        //1 to 9 fills one digit

        try {
            $this->print($this->sheet, '2025-09-12', 1);
            $this->fail('the tenth unit of the day should have been refused');
        } catch (UnitBarcodeRefused $e) {
            $this->assertSame(422, $e->status);
            $this->assertStringContainsString('Serial digits', $e->getMessage());
        }

        $this->assertSame(9, $this->rowCount('SELECT COUNT(*) FROM productunits'));
        $next = $this->pdo->prepare('SELECT NextValue FROM barcodesequence WHERE shop_SHID = 0 AND ScopeKey = ?');
        $next->execute(['unit:c' . $this->company . ':250912']);
        $this->assertSame(10, (int) $next->fetchColumn(), 'the refused number went back');
    }

    public function test_a_job_that_runs_out_of_serials_numbers_nothing_at_all()
    {
        $this->serialDigits(1);

        try {
            $this->units->allocateBatch($this->warehouse, [$this->bed => 5, $this->sheet => 6], '2025-09-12', $this->alice);
            $this->fail('eleven units do not fit one digit');
        } catch (UnitBarcodeRefused $e) {
            $this->assertSame(422, $e->status);
        }

        $this->assertSame(0, $this->rowCount('SELECT COUNT(*) FROM productunits'));
        $this->assertSame(0, $this->rowCount('SELECT COUNT(*) FROM barcodesequence WHERE shop_SHID = 0'), 'no counter was left behind');
    }

```

- [ ] **Step 2: Update the install-default assertion**

In `tests/UnitBarcodesMigrationTest.php` line 30, change:

```php
        $this->assertSame('{ITEM}{YY}{MM}{SEQ}', $settings['UnitPattern']);
```
to:
```php
        $this->assertSame('{ITEM}{YY}{MM}{DD}{SEQ}', $settings['UnitPattern']);
```

- [ ] **Step 3: Run the tests to see them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --colors=never --filter 'ProductUnitsTest|UnitBarcodesMigrationTest'`
Expected: FAIL. The numbering tests report codes built per item and per month (for example expected `COO000012509120001`, got `COO0000125090001`), `test_the_number_series_...` reports a wrong key, the capacity tests report no refusal, and `UnitBarcodesMigrationTest` reports the old default pattern. No fatal errors.

- [ ] **Step 4: Update the model: header, constants, defaults**

In `Model/product_unit_class.php`, replace the opening comment block (lines 1 to 12, up to and including the line `//a code that only carries the month still answers "how many did we make on the 12th".`):

```php
<?php
//Every unit Sleep Makers make carries its own barcode
//(docs/superpowers/specs/2026-09-23-unit-barcodes-design.md).
//
//A unit code is the product's own barcode, the month it was made and a serial:
//
//    COO00001 2509 0013      pattern {ITEM}{YY}{MM}{SEQ}, serial 4 digits
//
//The pattern, the serial width and the separator are the shop's (barcodesettings), and the
//serial comes from the same atomic counter the product barcodes use, so two people printing
//at the same moment can never be given the same number. The exact day is kept on the row, so
//a code that only carries the month still answers "how many did we make on the 12th".
```
with:
```php
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
```

Then replace:
```php
    const MAX_PER_PRINT = 5000;            //one print job; the label page caps what it renders
    const DEFAULTS = [
        'mode' => false,
        'pattern' => '{ITEM}{YY}{MM}{SEQ}',
```
with:
```php
    const MAX_PER_PRINT = 5000;            //one print job; the label page caps what it renders
    const COUNTER_SHOP = 0;                //the day's counters are the company's: no real shop has id 0
    const DEFAULTS = [
        'mode' => false,
        'pattern' => '{ITEM}{YY}{MM}{DD}{SEQ}',
```

- [ ] **Step 5: Update the model: the series**

Replace the whole `scopeKey` method (the comment starting `//Which number series a unit takes its serial from` through `}//scope key`) with:

```php
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
```

- [ ] **Step 6: Update the model: `allocate` and `product`**

In `allocate()`, replace:
```php
        $settings = $this->settings($shop_id);
        $pdo = $this->connect();
        $barcodes = new BarcodeSettings();
        $scope = $this->scopeKey($settings, $item, $date);
```
with:
```php
        $settings = $this->settings($shop_id);
        $pdo = $this->connect();
        $barcodes = new BarcodeSettings();
        $company = (int)$product['CompanyID'];
        $scope = $this->scopeKey($settings, $company, $date);
        $capacity = (int)(pow(10, $settings['seq_length']) - 1);   //the most the serial's digits can carry
```
and replace:
```php
            $ref = $print_ref === null ? $this->nextPrintRef($shop_id) : $print_ref;
            $codes = [];
            for($i = 0; $i < $qty; $i++)
            {
                $seq = $barcodes->allocateSequence($shop_id, $scope, 1, 1);
                if($seq < 1)
                {
                    throw new UnitBarcodeRefused(500, 'The unit numbering could not be read. Please try again.');
                }
                $code = $this->build($settings, $item, $date, $seq);
```
with:
```php
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
```
In `product()`, replace:
```php
        $stmt = $this->connect()->prepare("SELECT products.PDID, products.Barcode, products.ItemName, products.ItemType,
            products.ProductStat, products.shop_SHID FROM products
```
with:
```php
        $stmt = $this->connect()->prepare("SELECT products.PDID, products.Barcode, products.ItemName, products.ItemType,
            products.ProductStat, products.shop_SHID, me.Company_CMID AS CompanyID FROM products
```

- [ ] **Step 7: Update the install defaults**

`db/unit_barcodes_migration.php` line 29, change `DEFAULT '{ITEM}{YY}{MM}{SEQ}'` to `DEFAULT '{ITEM}{YY}{MM}{DD}{SEQ}'`:
```php
        'UnitPattern' => "ALTER TABLE barcodesettings ADD COLUMN UnitPattern VARCHAR(160) NOT NULL DEFAULT '{ITEM}{YY}{MM}{DD}{SEQ}'
```
`db/unit_barcodes.sql` line 36, change `DEFAULT '{ITEM}{YY}{MM}{SEQ}'` to `DEFAULT '{ITEM}{YY}{MM}{DD}{SEQ}'`:
```sql
  ADD COLUMN IF NOT EXISTS `UnitPattern` varchar(160) NOT NULL DEFAULT '{ITEM}{YY}{MM}{DD}{SEQ}'
```

- [ ] **Step 8: Run the numbering tests to see them pass**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --colors=never --filter 'ProductUnitsTest|UnitBarcodesMigrationTest'`
Expected: `OK`. If `createCompany()` rejects a second company in `test_two_companies_never_share_a_series`, pass a distinct name: `$this->createCompany(['ComName' => 'Other Company'])` in the three tests that call it twice.

- [ ] **Step 9: Fix the one test that hard-coded the old code width, then run everything that uses units**

The default is now a 10-character suffix, so a made-up never-printed code in `tests/GrnScanUnitsTest.php` must be that shape. In `test_a_unit_code_we_never_printed_is_refused` (around line 148) change both literals from `'COO0000125099999'` to `'COO000012509129999'`:
```php
        $preview = $this->scan->preview($this->grn, $this->shop, $this->alice, 'COO000012509129999');
        $line = $this->lines($preview)['COO000012509129999'];
```
Run: `C:/xampp/php/php.exe tools/phpunit.phar --colors=never --filter 'ProductUnitsTest|UnitBarcodesMigrationTest|GrnScanUnitsTest|TransferScanTest|DispatchScanTest|ScanParserTest|ScanBatchesTest|WarehouseOrderTest|WarehouseFulfilmentMigrationTest|StockAllocatorTest'`
Expected: `OK`. If `TransferScanTest::test_a_code_that_is_not_a_unit_we_printed_is_still_not_on_the_transfer` fails, change its literal `'COO0000125099999'` to `'COO000012509129999'` the same way (both occurrences) and re-run.

- [ ] **Step 10: Commit**

```bash
git add Model/product_unit_class.php db/unit_barcodes_migration.php db/unit_barcodes.sql tests/ProductUnitsTest.php tests/UnitBarcodesMigrationTest.php tests/GrnScanUnitsTest.php
git commit -m "feat(units): one running serial for the whole day, per company" -m "The serial of a unit code no longer restarts for every product. A series is the company and the date part of the code, so the day's units count 0001, 0002, ... whatever they are. A series that has no counter yet starts after the highest serial already issued in it, so days numbered item by item cannot repeat a code. A full serial is refused and gives its numbers back. New shops default to {ITEM}{YY}{MM}{DD}{SEQ}." -m "Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>"
```
(If Task 0 answered "include pending changes", add the line `Includes the pending changes already in tests/GrnScanUnitsTest.php.` as a paragraph before the trailer.)

---

### Task 2: Split a code in two, and name the item behind a typed code

**Files:**
- Modify: `Model/product_unit_class.php` (two methods)
- Test: `tests/ProductUnitsTest.php`

**Interfaces:**
- Consumes: `ProductUnits::resolve(array $codes, $shop_id): array` (rows keyed by the code as given, each with `ItemBarcode`).
- Produces: `static ProductUnits::splitCode($unit_code, $item_barcode): ?array` returning `[prefix, suffix]` or `null`; `ProductUnits::itemBarcodeFor($text, $shop_id): string` returning the item barcode or `''`.

- [ ] **Step 1: Write the failing tests**

In `tests/ProductUnitsTest.php`, add these methods at the end of the class, before its final closing brace:

```php
    // ---- the two halves of a code, and finding a product from a sticker ----------------

    public function test_a_code_splits_into_the_item_and_the_rest()
    {
        $this->assertSame(['MATCOO00005', '2610010006'], ProductUnits::splitCode('MATCOO000052610010006', 'MATCOO00005'));
        //an item barcode that is long and ends in digits splits by the item, never by a width
        $this->assertSame(['MATCOO26092300001', '2610060001'],
            ProductUnits::splitCode('MATCOO260923000012610060001', 'MATCOO26092300001'));
    }

    public function test_the_separator_that_joined_the_parts_is_dropped()
    {
        $this->assertSame(['COO00001', '25-09-12-001'], ProductUnits::splitCode('COO00001-25-09-12-001', 'COO00001'));
        $this->assertSame(['COO00001', '250912_0001'], ProductUnits::splitCode('COO00001_250912_0001', 'COO00001'));
    }

    public function test_a_code_that_does_not_start_with_its_item_is_not_split()
    {
        $this->assertNull(ProductUnits::splitCode('2509COO000010001', 'COO00001'));
        $this->assertNull(ProductUnits::splitCode('COO00001', 'COO00001'), 'nothing after the item');
        $this->assertNull(ProductUnits::splitCode('COO000012509120001', ''), 'no item to split on');
    }

    public function test_the_item_is_matched_without_regard_to_case_but_printed_as_the_code_has_it()
    {
        $this->assertSame(['coo00001', '2509120001'], ProductUnits::splitCode('coo000012509120001', 'COO00001'));
    }

    public function test_a_typed_unit_code_names_the_item_it_was_printed_for()
    {
        $code = $this->print($this->bed, '2025-09-12', 1)['codes'][0];

        $this->assertSame('COO00001', $this->units->itemBarcodeFor($code, $this->warehouse));
        $this->assertSame('COO00001', $this->units->itemBarcodeFor('  ' . strtolower($code) . ' ', $this->warehouse));
    }

    public function test_text_that_is_not_a_printed_unit_code_names_no_item()
    {
        $this->print($this->bed, '2025-09-12', 1);

        $this->assertSame('', $this->units->itemBarcodeFor('COO00001', $this->warehouse), 'a product barcode is not a unit code');
        $this->assertSame('', $this->units->itemBarcodeFor('COO000012509129999', $this->warehouse));
        $this->assertSame('', $this->units->itemBarcodeFor('   ', $this->warehouse));
        $this->assertSame('', $this->units->itemBarcodeFor("x' OR '1'='1", $this->warehouse));
    }
```

- [ ] **Step 2: Run to see them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --colors=never --filter ProductUnitsTest`
Expected: FAIL with `Call to undefined method ProductUnits::splitCode()` (and `itemBarcodeFor`).

- [ ] **Step 3: Implement**

In `Model/product_unit_class.php`, add these two methods directly before the `//------------------------------------------------------------------ helpers` divider:

```php
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
```

- [ ] **Step 4: Run to see them pass**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --colors=never --filter ProductUnitsTest`
Expected: `OK`.

- [ ] **Step 5: Commit**

```bash
git add Model/product_unit_class.php tests/ProductUnitsTest.php
git commit -m "feat(units): split a unit code in two, and find the item behind a typed code" -m "splitCode gives the item barcode and the date-and-serial the sticker prints on two lines; itemBarcodeFor lets the product page find a product from a whole unit code. Both go by the stored item, not by a fixed width." -m "Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>"
```

---

### Task 3: Label options and layout helpers

**Files:**
- Modify: `Includes/barcode_helper.php` (`bcOptionDefaults`, `bcResolveOptions`, four new functions before `}//function guard`)
- Create: `tests/UnitLabelTest.php`

**Interfaces:**
- Produces: option `unit_copies` (default 3, clamped 1 to 100) in `bcOptionDefaults()` and `bcResolveOptions()`; `bcUnitCopiesOf($options): int`; `bcUnitJobCeiling($units, $copies, $max): string` (empty string when the job fits, else the reason); `bcUnitContentHeight($options, $bars): float`; `bcUnitBarHeight($options, $size, $bars_are_automatic = true): float`.

- [ ] **Step 1: Write the failing tests**

Create `tests/UnitLabelTest.php`:

```php
<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Includes/barcode_helper.php';

//Includes/barcode_helper.php: what a unit sticker needs that a plain label does not - how many
//copies, whether a job fits one page, and the bar height once the code takes two lines.
final class UnitLabelTest extends TestCase
{
    private function options($size_key, array $input = [])
    {
        $size = bcResolveLabelSize($size_key);
        return [bcResolveOptions($input, $size), $size];
    }

    // ---- copies ------------------------------------------------------------------------

    public function test_a_unit_gets_three_stickers_unless_told_otherwise()
    {
        [$options] = $this->options('50x25');

        $this->assertSame(3, $options['unit_copies']);
        $this->assertSame(1, $options['copies'], 'a plain label is still one sticker');
    }

    public function test_the_copies_of_a_unit_are_kept_between_one_and_a_hundred()
    {
        foreach ([[0, 1], [-4, 1], [250, 100], ['7', 7], ['abc', 3], ['', 3], [null, 3]] as [$posted, $expected]) {
            [$options] = $this->options('50x25', ['unit_copies' => $posted]);
            $this->assertSame($expected, $options['unit_copies'], var_export($posted, true));
        }
    }

    public function test_the_copies_of_a_raw_option_set_are_read_the_way_the_options_are()
    {
        $this->assertSame(3, bcUnitCopiesOf([]));
        $this->assertSame(3, bcUnitCopiesOf(null));
        $this->assertSame(5, bcUnitCopiesOf(['unit_copies' => '5']));
        $this->assertSame(100, bcUnitCopiesOf(['unit_copies' => 999]));
        $this->assertSame(1, bcUnitCopiesOf(['unit_copies' => 0]));
    }

    // ---- the one-page ceiling ----------------------------------------------------------

    public function test_a_job_that_fits_is_allowed()
    {
        $this->assertSame('', bcUnitJobCeiling(333, 3, 1000));
        $this->assertSame('', bcUnitJobCeiling(1000, 1, 1000));
        $this->assertSame('', bcUnitJobCeiling(0, 3, 1000), 'nothing to print is not too many');
    }

    public function test_a_job_that_does_not_fit_says_how_many_units_would()
    {
        $message = bcUnitJobCeiling(334, 3, 1000);

        $this->assertStringContainsString('1,002 stickers (334 units x 3 copies)', $message);
        $this->assertStringContainsString('at most 1,000', $message);
        $this->assertStringContainsString('up to 333 units', $message);
    }

    public function test_the_message_reads_properly_for_one_copy()
    {
        $this->assertStringContainsString('(1,001 units x 1 copy)', bcUnitJobCeiling(1001, 1, 1000));
    }

    // ---- the bars once the code takes two lines ----------------------------------------

    public function test_the_bars_keep_their_height_where_the_extra_line_fits()
    {
        [$options, $size] = $this->options('50x25');

        $this->assertEqualsWithDelta(8.0, bcUnitBarHeight($options, $size, true), 0.0001);
    }

    public function test_the_bars_give_back_what_the_extra_line_needs_on_the_smallest_sticker()
    {
        [$options, $size] = $this->options('25x15');

        $this->assertEqualsWithDelta(4.0, bcUnitBarHeight($options, $size, true), 0.0001);   //the profile says 4.6
    }

    public function test_a_bar_height_the_operator_set_is_left_alone()
    {
        [$options, $size] = $this->options('25x15', ['bar_height' => 4.6]);

        $this->assertEqualsWithDelta(4.6, bcUnitBarHeight($options, $size, false), 0.0001);
    }

    public function test_nothing_changes_without_bars_or_without_the_number()
    {
        [$noBars, $size] = $this->options('25x15', ['show_bars' => 0]);
        [$noCode] = $this->options('25x15', ['show_code' => 0]);

        $this->assertEqualsWithDelta($noBars['bar_height'], bcUnitBarHeight($noBars, $size, true), 0.0001);
        $this->assertEqualsWithDelta($noCode['bar_height'], bcUnitBarHeight($noCode, $size, true), 0.0001);
    }

    public function test_the_bars_never_shrink_below_three_millimetres()
    {
        [$options, $size] = $this->options('25x15', ['show_shop' => 1, 'show_second' => 1, 'show_cat' => 1,
            'show_sku' => 1, 'show_batch' => 1, 'show_date' => 1, 'show_footer' => 1]);

        $this->assertEqualsWithDelta(3.0, bcUnitBarHeight($options, $size, true), 0.0001);
    }

    public function test_every_standard_size_holds_the_two_line_code_at_its_default_lines()
    {
        foreach (bcGetLabelSizes() as $key => $profile) {
            [$options, $size] = $this->options($key);
            $bars = bcUnitBarHeight($options, $size, true);
            $inner = (float) $size['height'] - 2 * (float) $options['padding'];

            $this->assertLessThanOrEqual($inner + 0.0001, bcUnitContentHeight($options, $bars), $key . ' clips');
            if ($key !== '25x15') {
                $this->assertEqualsWithDelta((float) $profile['bar_height'], $bars, 0.0001,
                    $key . ' lost bar height it did not need to');
            }
        }
    }
}
```

- [ ] **Step 2: Run to see them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --colors=never --filter UnitLabelTest`
Expected: FAIL. `Undefined array key "unit_copies"` (a PHPUnit error, since notices fail) for the first tests and `Call to undefined function bcUnitCopiesOf()` / `bcUnitJobCeiling()` / `bcUnitBarHeight()` for the rest.

- [ ] **Step 3: Add the option and its clamp**

In `Includes/barcode_helper.php`, in `bcOptionDefaults()` change:
```php
            'copies'      => 1,
            'auto_print'  => 1,
```
to:
```php
            'copies'      => 1,
            'unit_copies' => 3,      //stickers of each unit: on the product, the invoice, the warranty card
            'auto_print'  => 1,
```
In `bcResolveOptions()` change:
```php
        $out['copies']    = bcClampInt($out['copies'], 1, 100, 1);
```
to:
```php
        $out['copies']    = bcClampInt($out['copies'], 1, 100, 1);
        $out['unit_copies'] = bcClampInt($out['unit_copies'], 1, 100, 3);
```

- [ ] **Step 4: Add the four helpers**

In `Includes/barcode_helper.php`, replace the end of the file:
```php
    function bcMm($mm)
    {
        return rtrim(rtrim(number_format((float) $mm, 2, '.', ''), '0'), '.');
    }//bcMm

}//function guard
```
with:
```php
    function bcMm($mm)
    {
        return rtrim(rtrim(number_format((float) $mm, 2, '.', ''), '0'), '.');
    }//bcMm

    /**
     * The copies of each unit asked for in a raw option set (a posted job, a saved shop default),
     * read the way bcResolveOptions() will read it: 1 to 100, three when there is none.
     */
    function bcUnitCopiesOf($options)
    {
        return bcClampInt(is_array($options) && isset($options['unit_copies']) ? $options['unit_copies'] : 3, 1, 100, 3);
    }//bcUnitCopiesOf

    /**
     * Would a unit job fit on one page? Every unit is numbered before the page renders, and a page
     * renders at most $max stickers, so a job that is too big has to be refused BEFORE it numbers
     * anything. Returns '' when it fits, else the reason, in words the operator can act on.
     */
    function bcUnitJobCeiling($units, $copies, $max)
    {
        $units  = max(0, (int) $units);
        $copies = max(1, (int) $copies);
        $max    = max(1, (int) $max);
        $total  = $units * $copies;

        if ($total <= $max) {
            return '';
        }//fits

        $word = ($copies === 1) ? 'copy' : 'copies';

        return 'That would print ' . number_format($total) . ' stickers (' . number_format($units)
            . ' units x ' . $copies . ' ' . $word . ') and one job holds at most ' . number_format($max)
            . '. At ' . $copies . ' ' . $word . ' print up to ' . number_format((int) floor($max / $copies))
            . ' units at a time, or lower the copies.';
    }//bcUnitJobCeiling

    /**
     * The height, in millimetres, of the lines of a UNIT sticker with the bars at $bars mm: every
     * line that is switched on, the code as TWO lines (the item barcode above the bars, the date and
     * serial below them) and the gap between neighbours. Text is set at the line height 1.05 that
     * print-barcode.php uses.
     */
    function bcUnitContentHeight($options, $bars)
    {
        $line  = 1.05;
        $parts = array();

        if (!empty($options['show_shop'])) {
            $parts[] = $options['shop_font'] * $line;
        }//shop line
        if (!empty($options['show_name'])) {
            $parts[] = $options['name_font'] * $line;
        }//item name
        if (!empty($options['show_second'])) {
            $parts[] = $options['name_font'] * $line;
        }//second name
        foreach (array('show_cat', 'show_sku') as $flag) {
            if (!empty($options[$flag])) {
                $parts[] = $options['small_font'] * $line;
            }
        }//small lines above the code
        if (!empty($options['show_code'])) {
            $parts[] = $options['code_font'] * $line;
        }//the item barcode, above the bars
        if (!empty($options['show_bars'])) {
            $parts[] = (float) $bars;
        }//the bars
        if (!empty($options['show_code'])) {
            $parts[] = $options['code_font'] * $line;
        }//the date and serial, below the bars
        if (!empty($options['show_price'])) {
            $parts[] = $options['price_font'] * $line;
        }//price
        foreach (array('show_batch', 'show_date', 'show_footer') as $flag) {
            if (!empty($options[$flag])) {
                $parts[] = $options['small_font'] * $line;
            }
        }//small lines after it

        if (empty($parts)) {
            return 0.0;
        }//nothing on the sticker

        return array_sum($parts) + (count($parts) - 1) * (float) $options['line_gap'];
    }//bcUnitContentHeight

    /**
     * The bar height of a UNIT sticker. Its code takes two lines where a plain label has one, so
     * where a small sticker has no room for the second line the bars give back the difference
     * (never going below 3mm). A bar height the operator typed in is respected
     * ($bars_are_automatic false), and so is a sticker with no bars or no number.
     */
    function bcUnitBarHeight($options, $size, $bars_are_automatic = true)
    {
        $bars = (float) $options['bar_height'];

        if (!$bars_are_automatic || empty($options['show_bars']) || empty($options['show_code'])) {
            return $bars;
        }//nothing to give back

        $inner = (float) $size['height'] - 2 * (float) $options['padding'];
        $room  = $inner - bcUnitContentHeight($options, 0);
        $fits  = floor(round($room, 6) * 10) / 10;

        return ($fits >= $bars) ? $bars : max(3.0, $fits);
    }//bcUnitBarHeight

}//function guard
```

- [ ] **Step 5: Run to see them pass**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --colors=never --filter UnitLabelTest`
Expected: `OK` (11 tests).

- [ ] **Step 6: Commit**

```bash
git add Includes/barcode_helper.php tests/UnitLabelTest.php
git commit -m "feat(labels): copies of each unit, the one-page ceiling and the bars of a two-line code" -m "unit_copies (default 3) joins the label options. bcUnitJobCeiling says no to a unit job that would not all print before anything is numbered. bcUnitBarHeight trims the bars only where a small sticker has no room for the second code line." -m "Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>"
```

---

### Task 4: Find a product from a unit's whole barcode

**Files:**
- Modify: `AJAX/Products/getProductSearch.php`
- Modify: `tests/e2e/lib.php` (cleanup of the company-level counters), `tests/e2e/unit_barcodes_e2e.php`

**Interfaces:**
- Consumes: `ProductUnits::itemBarcodeFor($text, $shop_id): string` (Task 2).
- Produces: the product page search returns the product whose barcode is a printed unit's prefix, in addition to the text matches.

- [ ] **Step 1: Keep the e2e records clean (the daily counters live under shop 0)**

In `tests/e2e/lib.php`, in `E2EFixtures::down()`, replace:
```php
        $this->pdo->exec("DELETE FROM barcodesequence WHERE shop_SHID IN ($shops)");
```
with:
```php
        $this->pdo->exec("DELETE FROM barcodesequence WHERE shop_SHID IN ($shops)");
        //the daily unit counters belong to the company, not to a shop (db/UNIT_BARCODES_MODULE.md)
        foreach ($this->pdo->query("SELECT CMID FROM company WHERE ComName = 'e2e Company'")->fetchAll(PDO::FETCH_COLUMN) as $company) {
            $this->pdo->prepare("DELETE FROM barcodesequence WHERE shop_SHID = 0 AND ScopeKey LIKE ?")->execute(['unit:c' . (int) $company . ':%']);
        }//each e2e company
```

- [ ] **Step 2: Write the failing e2e checks, and move the e2e to the daily pattern**

In `tests/e2e/unit_barcodes_e2e.php`:

(a) Replace the settings insert:
```php
//this warehouse prints a unique barcode on every unit
$pdo->prepare("INSERT INTO barcodesettings (shop_SHID, UnitMode, UnitPattern, UnitSeqLength, UnitSeparator)
    VALUES (?, 1, '{ITEM}{YY}{MM}{SEQ}', 4, '') ON DUPLICATE KEY UPDATE UnitMode = 1")->execute([$W]);
```
with:
```php
//this warehouse prints a unique barcode on every unit, numbered by the day
$pdo->prepare("INSERT INTO barcodesettings (shop_SHID, UnitMode, UnitPattern, UnitSeqLength, UnitSeparator)
    VALUES (?, 1, '{ITEM}{YY}{MM}{DD}{SEQ}', 4, '') ON DUPLICATE KEY UPDATE UnitMode = 1")->execute([$W]);
```
(b) Replace the two code checks:
```php
    check('each one has its own code, built from the item, the month and a serial',
        $codes === ['E2EBED0125090001', 'E2EBED0125090002', 'E2EBED0125090003'], $b);
```
with:
```php
    check('each one has its own code, built from the item, the day and a serial',
        $codes === ['E2EBED012509120001', 'E2EBED012509120002', 'E2EBED012509120003'], $b);
```
and
```php
    check('the labels show those codes, one sticker each', $b->has('E2EBED0125090001')
        && $b->has('E2EBED0125090002') && $b->has('E2EBED0125090003'), $b);
```
with:
```php
    check('the labels show those codes, one sticker each', $b->has('E2EBED012509120001')
        && $b->has('E2EBED012509120002') && $b->has('E2EBED012509120003'), $b);
```
(c) Replace the never-printed code `'E2EBED0125099999'` (in the "A code we never printed" section) with `'E2EBED012509129999'`.

(d) Insert this section immediately before the line `    echo "Scanning them into a GRN\n";`:
```php
    echo "Finding the product from a unit's sticker\n";
    $b->get('AJAX/Products/getProductSearch.php?txt_input=' . urlencode($codes[0]));
    check('the product page search finds the product from the whole unit code',
        $b->has('e2e Bed') && $b->has('E2EBED01'), $b);
    $b->get('AJAX/Products/getProductSearch.php?txt_input=' . urlencode(strtolower($codes[1])));
    check('in any case', $b->has('e2e Bed'), $b);
    $b->get('AJAX/Products/getProductSearch.php?txt_input=' . urlencode('E2EBED01'));
    check('and from the product barcode alone, as before', $b->has('e2e Bed'), $b);
    $b->get('AJAX/Products/getProductSearch.php?txt_input=' . urlencode('Bed'));
    check('and from part of the name, as before', $b->has('e2e Bed'), $b);
    $b->get('AJAX/Products/getProductSearch.php?txt_input=' . urlencode($codes[0] . 'X'));
    check('but not from a code that was never printed', !$b->has('e2e Bed'), $b);
    $b->get('AJAX/Products/getProductSearch.php?txt_input=' . urlencode("O'Brien \" % \\ _"));
    check('and text with quotes, wildcards and a backslash is only text',
        $b->status === 200 && !$b->has('Error: Unable to read'), $b);
    checkClean('the product search', $b);

```

- [ ] **Step 3: Run the e2e to see the search checks fail**

Run: `C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php http://localhost/sleepmakers/sleepmakers`
Expected: the numbering and label checks PASS; `the product page search finds the product from the whole unit code` and `in any case` FAIL; `and text with quotes, wildcards and a backslash is only text` FAILs with the SQL error text in the body (the text is pasted into the query today); final line `N check(s) FAILED.`

- [ ] **Step 4: Parameterise the search and match a unit code's item**

In `AJAX/Products/getProductSearch.php`, replace the line:
```php
$txt_input = $_GET['txt_input'];
```
with:
```php
$txt_input = isset($_GET['txt_input']) ? (string)$_GET['txt_input'] : '';
```
and replace everything from the line `if($multi_category == 1)` through the line `$prodData = $dbObj->getData($sql_1);` with:
```php
//a unit's sticker carries the product's own barcode followed by the unit's date and serial: typed or
//scanned whole, it stands for that product (db/UNIT_BARCODES_MODULE.md)
require_once "../../Model/unit_barcode_refused_class.php";
require_once "../../Model/product_unit_class.php";
$unitItem = (new ProductUnits())->itemBarcodeFor($txt_input, $shop_id);

//the text and the unit's item go in as parameters, never into the SQL itself
$match = "(concat(products.Barcode, products.ItemName) LIKE ?" . ($unitItem === '' ? "" : " OR products.Barcode = ?") . ")";
$params = ["%".$txt_input."%"];
if($unitItem !== '')
{
    $params[] = $unitItem;
}//a unit code

if($multi_category == 1)
{
    $sql_1 = "SELECT *,categories.CTID AS cat_ID FROM products 
    INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    INNER JOIN shop ON shop.SHID = products.shop_SHID
    WHERE ".$match." AND shop.Company_CMID = ? LIMIT 50;";
    $params[] = (int)$company_id;
}//has multi category
else
{
    $sql_1 = "SELECT *,categories.CTID AS cat_ID FROM products 
    INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    WHERE ".$match." AND products.shop_SHID = ? LIMIT 50;";
    $params[] = (int)$shop_id;
}//mo multi category

$prodData = $dbObj->getMultipleData($sql_1, $params);
```

- [ ] **Step 5: Run the e2e to see everything pass**

Run: `C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php http://localhost/sleepmakers/sleepmakers`
Expected: every line `PASS`, ending `All checks passed.`

- [ ] **Step 6: Make sure the live database is clean again**

Run: `/c/xampp/mysql/bin/mysql.exe -u root sleepmakers -N -e "SELECT COUNT(*) FROM barcodesequence WHERE shop_SHID = 0; SELECT COUNT(*) FROM shop WHERE ShopName LIKE 'e2e %'; SELECT COUNT(*) FROM productunits WHERE ItemBarcode LIKE 'E2E%';"`
Expected: `0`, `0`, `0` on separate lines (the e2e removed its own company counters, shops and units).

- [ ] **Step 7: Commit**

```bash
git add AJAX/Products/getProductSearch.php tests/e2e/lib.php tests/e2e/unit_barcodes_e2e.php
git commit -m "feat(products): find a product from a unit's whole barcode" -m "The product page search also matches the product whose barcode is the prefix of a printed unit code, in any case. The query is parameterised, so the typed text is never part of the SQL. The e2e fixtures remove the company-level daily counters, and the unit e2e runs on the daily pattern." -m "Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>"
```
(If Task 0 answered "include pending changes", add the line `Includes the pending changes already in tests/e2e/lib.php.` before the trailer.)

---

### Task 5: The print page: two-line code, copies and the ceiling

**Files:**
- Modify: `Public/print-barcode.php`
- Modify: `tests/e2e/unit_barcodes_e2e.php`

**Interfaces:**
- Consumes: `ProductUnits::splitCode` (Task 2); `bcUnitCopiesOf`, `bcUnitJobCeiling`, `bcUnitBarHeight`, option `unit_copies` (Task 3).
- Produces: the label markup contract the e2e and UI scripts rely on: a unit sticker is `<div class="bc-label">` holding `<div class="bc-code bc-code-prefix">PREFIX</div>`, then `<div class="bc-bars">`, then `<div class="bc-code">SUFFIX</div>`; a plain sticker keeps one `<div class="bc-code">CODE</div>` after the bars. A refused job stores its reason in `$_SESSION['barcode_error']`.

- [ ] **Step 1: Write the failing e2e checks**

In `tests/e2e/unit_barcodes_e2e.php`:

(a) Replace the existing check
```php
    check('the labels show those codes, one sticker each', $b->has('E2EBED012509120001')
        && $b->has('E2EBED012509120002') && $b->has('E2EBED012509120003'), $b);
```
with:
```php
    check('the item barcode is printed above the bars, the date and serial below them',
        $b->has('<div class="bc-code bc-code-prefix">E2EBED01</div>')
        && $b->has('<div class="bc-code">2509120001</div>')
        && $b->has('<div class="bc-code">2509120003</div>')
        && strpos($b->body, 'class="bc-code bc-code-prefix"') < strpos($b->body, 'class="bc-bars"')
        && strpos($b->body, 'class="bc-bars"') < strpos($b->body, '<div class="bc-code">2509120001</div>'), $b);
    check('each unit prints three stickers by default: nine for three units',
        substr_count($b->body, '<div class="bc-label">') === 9, $b);
```
(b) Replace the reprint check
```php
    check('the same codes come out again', $b->has($codes[0]) && $b->has($codes[2]), $b);
```
with:
```php
    check('the same codes come out again',
        $b->has('<div class="bc-code">' . substr($codes[0], 8) . '</div>') && $b->has('<div class="bc-code">' . substr($codes[2], 8) . '</div>'), $b);
```
(c) Insert this section immediately before `    echo "Selling a unit at the till\n";`:
```php
    echo "A job that would not all print\n";
    $numbered = (int) $pdo->query("SELECT COUNT(*) FROM productunits WHERE shop_SHID = $W")->fetchColumn();
    $b->post('Public/print-barcode.php', ['btn_print_barcode' => '1', 'print_mode' => 'units',
        'produced_date' => '2025-09-12', 'item_id' => [$stock->products['bed']], 'item_qty' => [400],
        'item_price' => ['1500.00'], 'item_batch' => [''], 'unit_copies' => 3, 'bc_size' => '50x25']);
    check('400 units at 3 copies (1,200 stickers) is refused before anything is numbered',
        (int) $pdo->query("SELECT COUNT(*) FROM productunits WHERE shop_SHID = $W")->fetchColumn() === $numbered, $b);

    echo "Reprinting with a number of copies\n";
    $b->post('Public/print-barcode.php', ['btn_print_barcode' => '1', 'print_mode' => 'reprint', 'print_ref' => $ref,
        'unit_copies' => 2]);
    check('two copies of three units is six stickers', substr_count($b->body, '<div class="bc-label">') === 6, $b);

```

- [ ] **Step 2: Run the e2e to see the new checks fail**

Run: `C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php http://localhost/sleepmakers/sleepmakers`
Expected: FAIL on `the item barcode is printed above the bars...` (no prefix line yet), `each unit prints three stickers by default` (it prints one each), `the same codes come out again`, `400 units at 3 copies ... is refused before anything is numbered` (400 units were numbered) and `two copies of three units is six stickers`.

- [ ] **Step 3: Say no before numbering, and read the copies of a reprint**

In `Public/print-barcode.php`, in the reprint branch replace:
```php
                    $job['options'] = bcShopLabelDefaults(isset($stored['LabelDefaults']) ? $stored['LabelDefaults'] : '');
                }//the shop's own label settings

                $unitObj->reprint($shop_id, $ref);
                $job['print_ref'] = $ref;
```
with:
```php
                    $job['options'] = bcShopLabelDefaults(isset($stored['LabelDefaults']) ? $stored['LabelDefaults'] : '');
                }//the shop's own label settings

                if (isset($_POST['unit_copies'])) {
                    $job['options']['unit_copies'] = $_POST['unit_copies'];
                }//the copies asked for beside the Reprint button

                $too_many = bcUnitJobCeiling(count($again), bcUnitCopiesOf($job['options']), bcMaxLabelsPerJob());
                if ($too_many !== '') {
                    throw new UnitBarcodeRefused(422, $too_many);
                }//would not all print: refused before the reprint is counted

                $unitObj->reprint($shop_id, $ref);
                $job['print_ref'] = $ref;
```
and, in the new-numbers branch, replace:
```php
                }//how many of each

                $batch = $unitObj->allocateBatch($shop_id, $wanted, $produced, $user_id);
```
with:
```php
                }//how many of each

                /*
                 * Every unit is numbered before the page renders, and a page renders at most one
                 * job's worth of stickers. Say no BEFORE numbering anything, or units would be
                 * recorded as printed that never reach the paper.
                 */
                $too_many = bcUnitJobCeiling(array_sum($wanted), bcUnitCopiesOf($job['options']), bcMaxLabelsPerJob());
                if ($too_many !== '') {
                    throw new UnitBarcodeRefused(422, $too_many);
                }//would not all print

                $batch = $unitObj->allocateBatch($shop_id, $wanted, $produced, $user_id);
```

- [ ] **Step 4: Carry the split code and the bar height into the render**

Replace:
```php
    foreach ((new ProductUnits())->forPrintRef($shop_id, $unit_ref) as $unit) {
        $unit_codes[(int) $unit['products_PDID']][] = (string) $unit['UnitBarcode'];
    }//each unit of the job
}//unit job
```
with:
```php
    foreach ((new ProductUnits())->forPrintRef($shop_id, $unit_ref) as $unit) {
        $unit_codes[(int) $unit['products_PDID']][] = array(
            'code'  => (string) $unit['UnitBarcode'],
            //the item barcode above the bars and the rest below them; null prints the code whole
            'split' => ProductUnits::splitCode($unit['UnitBarcode'], $unit['ItemBarcode']),
        );
    }//each unit of the job

    //a unit's code takes two lines, one above the bars and one below: where a small sticker has no
    //room for the second line the bars give back the difference
    $options['bar_height'] = bcUnitBarHeight(
        $options,
        $size,
        empty($raw_options['bar_height']) || (float) $raw_options['bar_height'] <= 0
    );
}//unit job
```

- [ ] **Step 5: Build the stickers from entries, and repeat each by its own copies**

Replace:
```php
    $codes = isset($unit_codes[$item['id']])
        ? $unit_codes[$item['id']]
        : array_fill(0, max(1, (int) $item['qty']), $barcode);

    if ($barcode === '' && empty($unit_codes[$item['id']])) {
        $skipped[] = $product['ItemName'];
        continue;
    }//no barcode on the product
```
with:
```php
    $is_unit = !empty($unit_codes[$item['id']]);
    $codes   = $is_unit
        ? $unit_codes[$item['id']]
        : array_fill(0, max(1, (int) $item['qty']), array('code' => $barcode, 'split' => null));

    if ($barcode === '' && !$is_unit) {
        $skipped[] = $product['ItemName'];
        continue;
    }//no barcode on the product
```
Replace:
```php
    foreach ($codes as $code) {

        $svg = '';
```
with:
```php
    foreach ($codes as $entry) {

        $code = $entry['code'];
        $svg  = '';
```
Replace:
```php
            'barcode'  => $code,
            'batch'    => (string) $item['batch'],
```
with:
```php
            'barcode'  => $code,
            'prefix'   => $entry['split'] === null ? '' : $entry['split'][0],
            'suffix'   => $entry['split'] === null ? '' : $entry['split'][1],
            'batch'    => (string) $item['batch'],
```
Replace:
```php
        //copies repeats each sticker; a unit is one sticker unless copies says otherwise
        for ($i = 0; $i < $options['copies']; $i++) {
```
with:
```php
        //every sticker is repeated: a unit by its own copies (the product, the invoice, the
        //warranty card), a plain label by the copies option
        $copies = $is_unit ? $options['unit_copies'] : $options['copies'];
        for ($i = 0; $i < $copies; $i++) {
```

- [ ] **Step 6: Print the two lines**

Replace:
```php
                                <?php if ($options['show_bars']) { ?>
                                    <div class="bc-bars"><?php echo $sticker['svg']; ?></div>
                                <?php } ?>

                                <?php if ($options['show_code']) { ?>
                                    <div class="bc-code"><?php echo bcpE($sticker['barcode']); ?></div>
                                <?php } ?>
```
with:
```php
                                <?php if ($options['show_code'] && $sticker['prefix'] !== '') { ?>
                                    <div class="bc-code bc-code-prefix"><?php echo bcpE($sticker['prefix']); ?></div>
                                <?php } ?>

                                <?php if ($options['show_bars']) { ?>
                                    <div class="bc-bars"><?php echo $sticker['svg']; ?></div>
                                <?php } ?>

                                <?php if ($options['show_code']) { ?>
                                    <div class="bc-code"><?php echo bcpE($sticker['prefix'] !== '' ? $sticker['suffix'] : $sticker['barcode']); ?></div>
                                <?php } ?>
```

- [ ] **Step 7: Run the e2e to see everything pass**

Run: `C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php http://localhost/sleepmakers/sleepmakers`
Expected: every line `PASS`, ending `All checks passed.`; the live database is left clean (repeat the three counts of Task 4 step 6: `0`, `0`, `0`).

- [ ] **Step 8: Commit**

```bash
git add Public/print-barcode.php tests/e2e/unit_barcodes_e2e.php
git commit -m "feat(labels): the item barcode above the bars, the date and serial below, three stickers a unit" -m "A unit sticker prints its code on two lines either side of the bars; plain labels are unchanged. Each unit is repeated by unit_copies (default 3). A job that would exceed one page of stickers is refused before any unit is numbered, a reprint too. Small stickers give the second line its room from the bars." -m "Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>"
```

---

### Task 6: The Print Barcode dialog

**Files:**
- Modify: `View/modals/product_barcode.php`, `Assets/jquery/barcode-label.js`, `AJAX/Products/getBarcodeItems.php`
- Modify: `tests/e2e/fixtures.php` (a `unitmode` command)
- Create: `tests/ui/unit_label_ui.mjs`

**Interfaces:**
- Consumes: option `unit_copies`, `bcMaxLabelsPerJob()`, `ProductUnits::suffixLength($shop_id)`, the label markup contract of Task 5.
- Produces: input `#bc_unit_copies` (name `unit_copies`, default 3) in the unit panel, `#bc_ceiling_warning`, modal attribute `data-max-labels`, item field `unit_modules`, script version marker `window.BC_LABEL_JS = 5`.

`View/modals/product_barcode.php` already holds an uncommitted CSS fix (Task 0). Follow the answer recorded there before committing.

- [ ] **Step 1: A fixtures command that switches unit numbering on for an e2e shop**

In `tests/e2e/fixtures.php`, replace:
```php
$command = isset($argv[1]) ? $argv[1] : '';
$argv = [$argv[0]]; //lib.php reads an optional base url from the arguments
```
with:
```php
$command = isset($argv[1]) ? $argv[1] : '';
$arg = isset($argv[2]) ? (int) $argv[2] : 0;
$argv = [$argv[0]]; //lib.php reads an optional base url from the arguments
```
and replace:
```php
} else {
    fwrite(STDERR, "usage: php tests/e2e/fixtures.php up|down\n");
    exit(2);
}
```
with:
```php
} elseif ($command === 'unitmode' && $arg > 0) {
    //a unique barcode on every unit, numbered by the day, in one e2e shop (the id `up` printed)
    $pdo->prepare("INSERT INTO barcodesettings (shop_SHID, UnitMode, UnitPattern, UnitSeqLength, UnitSeparator)
        VALUES (?, 1, '{ITEM}{YY}{MM}{DD}{SEQ}', 4, '') ON DUPLICATE KEY UPDATE UnitMode = 1,
        UnitPattern = VALUES(UnitPattern), UnitSeqLength = VALUES(UnitSeqLength), UnitSeparator = VALUES(UnitSeparator)")->execute([$arg]);
    echo "unit barcodes on for shop $arg\n";
} else {
    fwrite(STDERR, "usage: php tests/e2e/fixtures.php up|down|unitmode <shop id>\n");
    exit(2);
}
```
Also add the line `//   C:/xampp/php/php.exe tests/e2e/fixtures.php unitmode <shop id>   numbers the units of an e2e shop by the day` under the two usage lines in the header comment.

- [ ] **Step 2: Write the failing UI script**

Create `tests/ui/unit_label_ui.mjs`:

```js
// Browser check of the Print Barcode dialog for units and of the stickers it prints: three copies
// of each unit by default, the live sticker total and its ceiling, the code on two lines (the item
// barcode above the bars, the date and serial below them), and nothing clipped on any sticker size.
//
//   BASE=http://localhost/sleepmakers/sleepmakers node tests/ui/unit_label_ui.mjs [screenshot-folder]
//
// Needs Node 24+, Chrome and the local site; see tests/ui/harness.mjs.
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { UiRun, BASE, sleep } from './harness.mjs';

const PHP = process.env.PHP || 'C:/xampp/php/php.exe';
const FIXTURES = fileURLToPath(new URL('../e2e/fixtures.php', import.meta.url));
const SIZES = ['100x50', '50x30', '50x25', '40x30', '38x25', '34x25', '34x20', '30x20', '25x15'];
const run = new UiRun(process.argv[2], 9337);

const summary = () => run.js(`document.getElementById('bc_total_summary').textContent`);
const shown = (id) => run.js(`document.getElementById('${id}').offsetParent !== null`);
const printDisabled = () => run.js(`document.getElementById('bc_btn_print').disabled`);

// one print job for the bed, posted the way the dialog posts it, and opened in this tab
const printUnit = (bedId, fields = {}) => run.submit(`(() => {
  const f = document.createElement('form');
  f.method = 'post';
  f.action = '../Public/print-barcode.php';
  const all = Object.assign({ btn_print_barcode: 1, print_mode: 'units', produced_date: '2025-09-12',
    'item_id[]': ${bedId}, 'item_qty[]': 1, 'item_price[]': '1500.00', 'item_batch[]': '', size: '50x25', across: 1,
    show_bars: 1, show_code: 1, show_name: 1, show_price: 1, auto_print: 0, unit_copies: 1 }, ${JSON.stringify(fields)});
  for (const [name, value] of Object.entries(all)) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    f.appendChild(input);
  }
  document.body.appendChild(f);
  f.submit();
})()`);

// the first sticker of the page: where its lines are, and whether anything pokes out of it
const firstSticker = async () => JSON.parse(await run.js(`(() => {
  const label = document.querySelector('.bc-label:not(.bc-blank)');
  const box = label.getBoundingClientRect();
  const clipped = [...label.children].some((k) => {
    const r = k.getBoundingClientRect();
    return r.top < box.top - 0.5 || r.bottom > box.bottom + 0.5;
  });
  const prefix = label.querySelector('.bc-code-prefix');
  const suffix = [...label.querySelectorAll('.bc-code')].find((e) => !e.classList.contains('bc-code-prefix'));
  const bars = label.querySelector('.bc-bars');
  const after = (a, b) => !!(a && b && (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING));
  return JSON.stringify({ clipped, prefix: prefix && prefix.textContent, suffix: suffix && suffix.textContent,
    ordered: after(prefix, bars) && after(bars, suffix) });
})()`));

let failure = null;
try {
  const fx = await run.start();
  execFileSync(PHP, [FIXTURES, 'unitmode', String(fx.shops.W)]);   // the e2e warehouse numbers its units
  const bed = fx.products.bed;

  console.log('Open the Print Barcode dialog for one product');
  await run.signIn('e2e_admin', fx.shops.W);
  await run.go(`${BASE}/Public/product.php`);
  await run.js(`$('#product_search').val('E2EBED01').trigger('keyup')`);
  for (let i = 0; i < 25 && !(await run.js(`!!document.querySelector('#tbl_products tr[data-id="${bed}"]')`)); i++) {
    await sleep(200);
  }
  await run.js(`document.querySelector('#tbl_products tr[data-id="${bed}"] .btn_open_barcode').click()`);
  for (let i = 0; i < 25 && (await run.js(`document.querySelectorAll('#bc_items_body tr').length`)) < 1; i++) {
    await sleep(200);
  }
  await sleep(400);

  run.check('the dialog offers a unique barcode on every unit, switched on',
    await run.js(`document.getElementById('bc_unit_mode').checked`));
  run.check('with three copies of each unit by default',
    (await run.js(`document.getElementById('bc_unit_copies').value`)) === '3');
  run.check('and the total says so: ' + (await summary()), (await summary()).includes('1 unit x 3 copies = 3 labels'));
  run.check('the long-code warning measures a unit code, which is longer than the product barcode',
    await run.js(`(() => { const r = $('#bc_items_body tr').first();
      return parseFloat(r.data('bc-unit-modules')) > parseFloat(r.data('bc-modules')); })()`));
  await run.shot('dialog-units.png');

  // 400 units at 3 copies is 1,200 stickers: more than one page holds
  await run.js(`$('#bc_items_body .bc-qty').val(400).trigger('input')`);
  run.check('400 units at 3 copies is called out', await shown('bc_ceiling_warning'));
  run.check('and cannot be printed', await printDisabled());
  await run.shot('dialog-too-many.png');

  await run.js(`$('#bc_unit_copies').val(2).trigger('input')`);
  run.check('400 units at 2 copies (800) fits again', !(await shown('bc_ceiling_warning')) && !(await printDisabled()));

  await run.js(`$('#bc_unit_mode').prop('checked', false).trigger('change')`);
  run.check('a plain label job counts the old way: ' + (await summary()), (await summary()).includes('400 labels'));
  run.check('and hides the unit controls', !(await shown('bc_unit_copies_row')) && !(await shown('bc_unit_date_row')));
  await run.js(`$('#bc_unit_mode').prop('checked', true).trigger('change')`);

  console.log('Print one unit sticker on every sticker size');
  for (const size of SIZES) {
    await printUnit(bed, { size });
    const s = await firstSticker();
    run.check(`${size}: prefix above the bars, suffix below, nothing clipped (${s.prefix} / ${s.suffix})`,
      !s.clipped && s.prefix === 'E2EBED01' && /^250912\d{4}$/.test(s.suffix) && s.ordered);
    await run.shot(`label-${size}.png`);
  }

  console.log('Switches and copies');
  await printUnit(bed, { show_bars: 0 });
  const noBars = JSON.parse(await run.js(`JSON.stringify({ bars: document.querySelectorAll('.bc-bars').length,
    lines: [...document.querySelectorAll('.bc-label .bc-code')].slice(0, 2).map((e) => e.textContent) })`));
  run.check('without the bars the two lines still read item barcode, then date and serial',
    noBars.bars === 0 && noBars.lines[0] === 'E2EBED01' && /^250912\d{4}$/.test(noBars.lines[1]));
  await printUnit(bed, { show_code: 0 });
  run.check('without the number neither line prints', (await run.js(`document.querySelectorAll('.bc-code').length`)) === 0);
  await printUnit(bed, { 'item_qty[]': 2, unit_copies: 3 });
  run.check('two units at three copies is six stickers',
    (await run.js(`document.querySelectorAll('.bc-label:not(.bc-blank)').length`)) === 6);

  run.checkScripts('no JavaScript errors from the label dialog', /barcode-label\.js/);
} catch (e) {
  failure = e;
}
await run.finish(failure);
```

- [ ] **Step 3: Run it to see it fail**

Run: `BASE=http://localhost/sleepmakers/sleepmakers node tests/ui/unit_label_ui.mjs`
Expected: `FAIL` with a stack trace ending in `Cannot read properties of null (reading 'value')` (there is no `#bc_unit_copies` yet), then `1 UI check(s) FAILED.`. The e2e records are removed at the end either way.

- [ ] **Step 4: The dialog markup**

In `View/modals/product_barcode.php`:

Replace
```php
<div class="modal fade" tabindex="-1" role="dialog" id="product_barcode_modal" aria-hidden="true">
```
with
```php
<div class="modal fade" tabindex="-1" role="dialog" id="product_barcode_modal" aria-hidden="true"
     data-max-labels="<?php echo (int) bcMaxLabelsPerJob(); ?>">
```
Replace
```php
                            <span class="text-muted fs-2">the production date the codes carry</span>
                        </div>
```
with
```php
                            <span class="text-muted fs-2">the production date the codes carry</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2" id="bc_unit_copies_row">
                            <label class="form-label mb-0 text-muted fs-2" for="bc_unit_copies">Copies of each unit</label>
                            <input type="number" class="form-control form-control-sm" style="max-width:90px;"
                                   id="bc_unit_copies" name="unit_copies" min="1" max="100"
                                   value="<?php echo (int) $bc_default['unit_copies']; ?>">
                            <span class="text-muted fs-2">one for the product, one for the invoice, one for the warranty card</span>
                        </div>
```
Replace
```php
                    <div class="alert alert-warning mt-3 mb-0 py-2 fs-2" id="bc_size_warning" style="display:none;"></div>
```
with
```php
                    <div class="alert alert-warning mt-3 mb-0 py-2 fs-2" id="bc_size_warning" style="display:none;"></div>
                    <div class="alert alert-danger mt-3 mb-0 py-2 fs-2" id="bc_ceiling_warning" style="display:none;"></div>
```
Replace `if (window.BC_LABEL_JS !== 4) {` with `if (window.BC_LABEL_JS !== 5) {`.

- [ ] **Step 5: The dialog script**

In `Assets/jquery/barcode-label.js`:

Replace `window.BC_LABEL_JS = 4;` with `window.BC_LABEL_JS = 5;`.

Replace
```js
        { name: "copies",      type: "value",  id: "#bc_copies" }
```
with
```js
        { name: "copies",      type: "value",  id: "#bc_copies" },
        { name: "unit_copies", type: "value",  id: "#bc_unit_copies" }
```
Replace the start of `refreshTotals()`, from `function refreshTotals() {` down to and including the line `$("#bc_btn_print").prop("disabled", products === 0);`, with:
```js
    function refreshTotals() {
        var products = 0;
        var units = 0;
        var labels = 0;
        var maxModules = 0;

        var unitOn = $("#bc_unit_mode").is(":checked");

        //a unit job repeats each unit's sticker by its own copies, a plain job by the style option
        var copies = parseInt($(unitOn ? "#bc_unit_copies" : "#bc_copies").val(), 10);
        if (!(copies > 0)) {
            copies = 1;
        }//being retyped - count as one, but leave the box alone

        $("#bc_items_body tr").each(function () {
            var qtyField = $(this).find(".bc-qty");
            if (qtyField.length === 0) {
                return;
            }

            products++;
            units += readQty(qtyField);
            labels += readQty(qtyField) * copies;

            //a unit's code is longer than the product's, so the sticker warning measures that one
            var modules = parseFloat($(this).data(unitOn && $(this).data("bc-unit-modules") ? "bc-unit-modules" : "bc-modules"));
            if (modules > maxModules) {
                maxModules = modules;
            }
        });

        //the bullet is written as an escape so this file stays pure ASCII,
        //whatever charset the server serves the script with
        $("#bc_total_summary").text(
            products + " product" + (products === 1 ? "" : "s") + " \u2022 " +
            (unitOn ? units + " unit" + (units === 1 ? "" : "s") + " x " + copies + " cop" + (copies === 1 ? "y" : "ies") + " = " : "") +
            labels + " label" + (labels === 1 ? "" : "s")
        );

        //one page holds a fixed number of stickers and a unit job over it is refused, so say so here
        var ceiling = parseInt($("#product_barcode_modal").attr("data-max-labels"), 10) || 0;
        var tooMany = unitOn && ceiling > 0 && labels > ceiling;

        $("#bc_ceiling_warning")
            .text("That is " + labels + " stickers and one job holds at most " + ceiling + ". At " + copies +
                " cop" + (copies === 1 ? "y" : "ies") + " print up to " + Math.floor(ceiling / copies) +
                " units at a time, or lower the copies.")
            .toggle(tooMany);

        $("#bc_btn_print").prop("disabled", products === 0 || tooMany);
```
Replace
```js
                for (var j = 0; j < res.items.length; j++) {
                    $("#bc_items_body tr[data-bc-id='" + res.items[j].id + "']")
                        .data("bc-modules", res.items[j].modules);
                }
```
with
```js
                for (var j = 0; j < res.items.length; j++) {
                    $("#bc_items_body tr[data-bc-id='" + res.items[j].id + "']")
                        .data("bc-modules", res.items[j].modules)
                        .data("bc-unit-modules", res.items[j].unit_modules || 0);
                }
```
Replace
```js
        $("#product_barcode_modal").on("input", ".bc-qty, #bc_copies", refreshTotals);
```
with
```js
        $("#product_barcode_modal").on("input", ".bc-qty, #bc_copies, #bc_unit_copies", refreshTotals);
```
Replace the `#bc_copies` blur handler
```js
        $("#product_barcode_modal").on("blur", "#bc_copies", function () {
            var copies = parseInt($(this).val(), 10);
            if (!(copies > 0)) {
                copies = 1;
            }
            if (copies > 100) {
                copies = 100;
            }
            $(this).val(copies);
            refreshTotals();
            savePrefs();
        });
```
with
```js
        $("#product_barcode_modal").on("blur", "#bc_copies, #bc_unit_copies", function () {
            var copies = parseInt($(this).val(), 10);
            if (!(copies > 0)) {
                copies = $(this).is("#bc_unit_copies") ? 3 : 1;
            }
            if (copies > 100) {
                copies = 100;
            }
            $(this).val(copies);
            refreshTotals();
            savePrefs();
        });
```
Replace
```js
        function unitMode() {
            var on = $("#bc_unit_mode").is(":checked");
            $("#bc_unit_date_row").toggle(on);
            $(".bc-qty-head").text(on ? "Units" : "Labels");
            return on;
        }
```
with
```js
        function unitMode() {
            var on = $("#bc_unit_mode").is(":checked");
            $("#bc_unit_date_row").toggle(on);
            $("#bc_unit_copies_row").toggle(on);
            $(".bc-qty-head").text(on ? "Units" : "Labels");
            refreshTotals();
            return on;
        }
```

- [ ] **Step 6: Tell the dialog how long a unit code is**

In `AJAX/Products/getBarcodeItems.php`, after `require_once __DIR__ . '/../../Model/barcode_settings_class.php';` add:
```php
require_once __DIR__ . '/../../Model/unit_barcode_refused_class.php';
require_once __DIR__ . '/../../Model/product_unit_class.php';
```
After `$stat = bcShopStat($dbObj, $shop_id);` add:
```php

    //where the shop numbers every unit, the sticker carries the item barcode plus this many more
    //characters, so the "long code" warning has to measure that and not the item barcode alone
    $unit_suffix = (new ProductUnits())->suffixLength($shop_id);
```
and replace
```php
            'modules'   => bcBarcodeModuleCount($barcode),
```
with
```php
            'modules'   => bcBarcodeModuleCount($barcode),
            'unit_modules' => ($unit_suffix > 0 && $barcode !== '') ? bcBarcodeModuleCount($barcode . str_repeat('9', $unit_suffix)) : 0,
```

- [ ] **Step 7: Run the UI script to see it pass**

Run: `BASE=http://localhost/sleepmakers/sleepmakers node tests/ui/unit_label_ui.mjs C:/Users/Admin/AppData/Local/Temp/claude/c--xampp-htdocs-sleepmakers-sleepmakers/440b3f9a-bc0a-4fc1-8b9e-7050e43002e2/scratchpad`
Expected: every line `PASS` (the dialog checks, nine size lines, the switches and copies checks, the script-errors check), ending `All UI checks passed.`
Then open `label-50x25.png` and `label-25x15.png` from that folder and look at them: the item barcode reads above the bars, the date and serial below, the whole sticker visible. Report anything that looks off even though the checks passed.

- [ ] **Step 8: Commit**

```bash
git add View/modals/product_barcode.php Assets/jquery/barcode-label.js AJAX/Products/getBarcodeItems.php tests/e2e/fixtures.php tests/ui/unit_label_ui.mjs
git commit -m "feat(labels): copies of each unit in the Print Barcode dialog, and a ceiling the dialog can see" -m "The unit panel gets Copies of each unit (default 3). The total reads units x copies = labels, over one page's worth the dialog warns and disables Print, and the long-code warning measures a unit code. The UI script checks the dialog and every sticker size for clipping." -m "Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>"
```
(If Task 0 answered "include pending changes", add the line `Includes the pending changes already in View/modals/product_barcode.php.` before the trailer.)

---

### Task 7: Say why a job was refused, the Reprint copies box, the settings text

**Files:**
- Modify: `Public/product.php`, `Public/unit-barcodes.php`, `Public/barcode-settings.php`
- Modify: `tests/e2e/unit_barcodes_e2e.php`

**Interfaces:**
- Consumes: `$_SESSION['barcode_error']` (written by `Public/print-barcode.php`), `bcUnitCopiesOf()`, `bcShopLabelDefaults()`, `BarcodeSettings::getSettings()`.
- Produces: the refusal reason shown on the product page and cleared; input `name="unit_copies"` beside each Reprint button.

- [ ] **Step 1: Write the failing e2e checks**

In `tests/e2e/unit_barcodes_e2e.php`:

(a) In the section `A job that would not all print`, after the check `400 units at 3 copies (1,200 stickers) is refused before anything is numbered`, add:
```php
    check('and the product page says why', $b->isOn('product.php') && $b->has('1,200 stickers (400 units x 3 copies)'), $b);
    $b->get('Public/product.php');
    check('the reason is shown once, not on the next visit', !$b->has('1,200 stickers'), $b);
```
(b) In the section `What was produced`, after `checkClean('the unit barcodes page', $b);`, add:
```php
    check('each print job offers its copies beside Reprint, three by default',
        $b->has('name="unit_copies" min="1" max="100" value="3"'), $b);
    $b->get('Public/barcode-settings.php');
    check('the unit rules say the serial is shared by every product made on the date',
        $b->has('shared by every product made on the same'), $b);
    checkClean('the barcode settings page', $b);
```
(c) Insert this section immediately before `    echo "Selling a unit at the till\n";`:
```php
    echo "The shop's own default for the copies\n";
    $b->post('AJAX/Barcode/saveLabelDefaults.php', ['options' => json_encode(['size' => '50x25', 'unit_copies' => 5])]);
    check('Save as shop default keeps the copies of a unit', $b->json('ok') === true, $b);
    $b->get('Public/unit-barcodes.php');
    check('and a reprint starts on them', $b->has('name="unit_copies" min="1" max="100" value="5"'), $b);
    $b->post('AJAX/Products/getBarcodeItems.php', ['product_ids' => [$stock->products['bed']]]);
    $dialog = json_decode($b->body, true);
    check('as does the dialog', isset($dialog['defaults']['unit_copies']) && (int) $dialog['defaults']['unit_copies'] === 5, $b);
    check('which also reports how long a unit code is, for the sticker warning',
        isset($dialog['items'][0]['unit_modules']) && $dialog['items'][0]['unit_modules'] > $dialog['items'][0]['modules'], $b);

```

- [ ] **Step 2: Run the e2e to see them fail**

Run: `C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php http://localhost/sleepmakers/sleepmakers`
Expected: FAIL on `and the product page says why`, `each print job offers its copies beside Reprint`, `the unit rules say the serial is shared...` and `and a reprint starts on them`; `the reason is shown once` passes trivially, and the shop-default and dialog checks already pass (Tasks 3 and 6).

- [ ] **Step 3: Show the refusal on the product page**

In `Public/product.php`, replace:
```php
                unset($_SESSION['product_update']);
            }//session set
            ?>
```
with:
```php
                unset($_SESSION['product_update']);
            }//session set

            //a refusal from the Print Barcode page, which sends the user back here with the reason
            if(isset($_SESSION['barcode_error']))
            {
                ?>
                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php echo htmlspecialchars((string)$_SESSION['barcode_error'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php
                unset($_SESSION['barcode_error']);
            }//a barcode refusal
            ?>
```

- [ ] **Step 4: The Reprint copies box**

In `Public/unit-barcodes.php`, replace:
```php
require_once '../Model/unit_barcode_refused_class.php';
require_once '../Model/product_unit_class.php';

$unitObj = new ProductUnits();
```
with:
```php
require_once '../Model/unit_barcode_refused_class.php';
require_once '../Model/product_unit_class.php';
require_once '../Includes/barcode_helper.php';

$unitObj = new ProductUnits();

//the copies of each unit a reprint starts on: the shop's saved label default, else three
$stored = (new BarcodeSettings())->getSettings($shop_id);
$reprintCopies = bcUnitCopiesOf(bcShopLabelDefaults(isset($stored['LabelDefaults']) ? $stored['LabelDefaults'] : ''));
```
and, in the Reprint form, add these three lines directly after the line `<input type="hidden" name="print_ref" value="<?= ubE($job['print_ref']) ?>">`, at the same indentation as that line:
```php
<input type="number" name="unit_copies" min="1" max="100" value="<?= (int) $reprintCopies ?>"
       class="form-control form-control-sm d-inline-block align-middle me-1"
       style="width:68px;" title="Copies of each unit">
```

- [ ] **Step 5: The settings text**

In `Public/barcode-settings.php`, inside the hint under *Unit code pattern*, add these three lines directly after the line `{SEQ} the serial. {ITEM} and {SEQ} are required.`, at the same indentation as that line:
```php
The serial is one running number shared by every product made on the same
date, across the whole company: {ITEM}{YY}{MM}{DD}{SEQ} counts 0001, 0002,
0003 ... through the day, whatever was made.
```

- [ ] **Step 6: Run the e2e to see everything pass**

Run: `C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php http://localhost/sleepmakers/sleepmakers`
Expected: every line `PASS`, ending `All checks passed.`; the live database is left clean (Task 4 step 6: `0`, `0`, `0`).

- [ ] **Step 7: Commit**

```bash
git add Public/product.php Public/unit-barcodes.php Public/barcode-settings.php tests/e2e/unit_barcodes_e2e.php
git commit -m "feat(labels): say why a print was refused, copies beside Reprint, the shared serial explained" -m "A refusal from the Print Barcode page was saved in the session and never shown, so the product page just reloaded. It now shows the reason once. Each print job on the Unit Barcodes page gets a copies box, and the unit rules say the serial is shared by every product made on the date." -m "Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>"
```

---

### Task 8: Docs, and the whole thing once more

**Files:**
- Modify: `db/UNIT_BARCODES_MODULE.md`, `docs/superpowers/specs/2026-09-23-unit-barcodes-design.md`, `README.md`

- [ ] **Step 1: The module guide**

In `db/UNIT_BARCODES_MODULE.md`:

Replace the table row
```
| `barcodesettings.UnitPattern` | how a unit code is built, default `{ITEM}{YY}{MM}{SEQ}` |
```
with
```
| `barcodesettings.UnitPattern` | how a unit code is built, default `{ITEM}{YY}{MM}{DD}{SEQ}` |
```
Replace the code diagram
```
COO00001 2509 0013      ->  COO0000125090013
   |      |    |
   |      |    +-- serial, restarts per item per month
   |      +------- year and month it was made
   +-------------- the product's own barcode
```
with
```
COO00001 250912 0013    ->  COO000012509120013
   |       |      |
   |       |      +-- serial: one running number for the day, whatever the product
   |       +--------- year, month and day it was made
   +----------------- the product's own barcode - the prefix (the rest is the suffix)
```
Replace the paragraph
```
The serial comes from the barcode module's atomic counter, under the key
`unit:<everything in the code except the serial>`. So it restarts whenever that fixed part
changes: with `{YY}{MM}` in the pattern, each item starts again at 1 every month.
```
with
```
The serial comes from the barcode module's atomic counter, under the key
`unit:c<company id>:<the date part of the code>` (for example `unit:c2:261006`), held under shop
id 0 because the series belongs to the company. The item is **not** part of the key: every product
made on the same day shares one running serial, so the first unit of the day is `0001` whatever it
is, the next is `0002`, and the next day starts at `0001` again. With `{YY}{MM}` only in the pattern
the series is the month's. A series that has no counter yet starts after the highest serial already
issued in it (any item, any shop of the company, any state), so a day that was numbered item by item
before this rule cannot repeat a code. When the serial's digits run out (9,999 at 4 digits) the print
is refused, with the number given back; raise *Serial digits*.
```
In section `## 3. Printing`, after the bullet `- Untick the box for a plain product label (a shelf label, say).` add:
```
- **Copies of each unit** (default 3, 1 to 100): one sticker for the product, one for the invoice,
  one for the warranty card. The total is units x copies.
- A sticker shows the item barcode **above** the bars and the date and serial **below** them; the
  bars encode the whole code. Plain product labels are unchanged.
```
and replace the bullet
```
- The 1000-label ceiling of the barcode module still applies.
```
with
```
- One job holds at most 1,000 stickers (units x copies). A bigger job is refused **before** any
  unit is numbered, with the number of units that would fit; the dialog shows the total live and
  disables *Print Labels* over it.
```
In section `## 5. Everywhere else`, insert this bullet directly before the bullet that starts `- Nothing is recorded against a unit in either place`:
```
- **The product page.** Type or scan a unit's whole barcode into the search box and the product it
  belongs to is found, in any case. Typing a product barcode or part of a name works as before.
```
In section `### Reprinting`, directly after the line `is how a damaged sticker is replaced without inventing a second unit.` add:
```

Each job has a copies box beside *Reprint* (the shop's saved default, three if none). A reprint
that would exceed one page of stickers is refused with the same message as a new job.
```
In section `## 8. Files`, inside the *Modified* block, update three existing lines in place:
- `Public/print-barcode.php          a job can carry one code per unit, and reprints` becomes `Public/print-barcode.php          a job can carry one code per unit, reprints, the two-line code, copies, the ceiling check`
- `View/modals/product_barcode.php   the unit mode and the production date` becomes `View/modals/product_barcode.php   the unit mode, the production date, the copies of each unit`
- `Assets/jquery/barcode-label.js    the mode toggle` becomes `Assets/jquery/barcode-label.js    the mode toggle, the live sticker total and its ceiling`

and directly after the line `Controller/barcodeSettingsController.php  saves the unit rules` add:
```
Includes/barcode_helper.php       the copies of a unit, the one-page ceiling, the bars of a two-line code
AJAX/Products/getProductSearch.php   finds a product from a unit's whole barcode
AJAX/Products/getBarcodeItems.php    the length of a unit code, for the dialog's size warning
Public/product.php                shows why a print was refused
Public/unit-barcodes.php          the copies box beside Reprint
```
In section `## 9. Tests`, directly after the two lines
```
prints them, scans them into a GRN, scans them again and is refused, reprints, and sells one
at the till.
```
add:
```

`UnitLabelTest` covers the copies of a unit, the one-page ceiling and the bars of a two-line code;
`ProductUnitsTest` also covers the shared daily serial, seeding, capacity and the prefix/suffix split.
The end-to-end script also searches the product page by a unit code, checks the two-line label, the
three copies and the refusal message. The browser check is
`BASE=<site> node tests/ui/unit_label_ui.mjs [screenshot-folder]`: the dialog, and every sticker size
for clipping.
```

- [ ] **Step 2: Point the old spec at the new rule**

In `docs/superpowers/specs/2026-09-23-unit-barcodes-design.md`, replace
```
- **Status:** Approved by the customer (three format/scope choices confirmed before implementation)
```
with
```
- **Status:** Approved by the customer (three format/scope choices confirmed before implementation)
- **Superseded in part:** the numbering rule in section 3 (the serial restarts per item per month) is
  replaced by `2026-10-06-daily-unit-numbering-design.md`: one running serial per company and day.
```

- [ ] **Step 3: The README test list**

In `README.md`, after the line
```
node tests/ui/customer_orders_ui.mjs [screenshot-folder]     # headless Chrome, the customer order pages
```
add:
```
node tests/ui/unit_label_ui.mjs [screenshot-folder]          # headless Chrome, the Print Barcode dialog and unit stickers
```

- [ ] **Step 4: Check no stale wording is left**

Run: `grep -rnEi "restarts? (per|for every|at 1|again)|per item per month|each item starts|one series per item" --include=*.php --include=*.md --include=*.js . | grep -v /vendor/ | grep -v "docs/superpowers/specs/2026-09-23" | grep -v "docs/superpowers/specs/2026-10-06" | grep -v "docs/superpowers/plans/2026-10-06"`
Expected: only `Includes/barcode_generator.php` (about *product* barcodes, not units). Anything else: fix it.

- [ ] **Step 5: Verify everything**

Run, each from the repo root, and read the output of every one:

1. `C:/xampp/php/php.exe tools/phpunit.phar --colors=never` (about 8 minutes). Expected: the only failures are the two existing ones, `GrnScanTest::test_edited_prices_are_used_and_invalid_ones_block` and `GrnScanTest::test_a_shop_that_tracks_expiry_labels_and_racks_gets_those_fields`; everything else passes, and the test count is above 262.
2. `C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php http://localhost/sleepmakers/sleepmakers` Expected: `All checks passed.`
3. `C:/xampp/php/php.exe tests/e2e/shop_login_e2e.php http://localhost/sleepmakers/sleepmakers` (it shares `tests/e2e/lib.php`, which changed). Expected: `All checks passed.`
4. `BASE=http://localhost/sleepmakers/sleepmakers node tests/ui/unit_label_ui.mjs` Expected: `All UI checks passed.`
5. `/c/xampp/mysql/bin/mysql.exe -u root sleepmakers -N -e "SELECT COUNT(*) FROM barcodesequence WHERE shop_SHID = 0; SELECT COUNT(*) FROM shop WHERE ShopName LIKE 'e2e %'; SELECT COUNT(*) FROM productunits WHERE ItemBarcode LIKE 'E2E%';"` Expected: `0`, `0`, `0`.
6. `git status --short` Expected: no file this plan touches is left modified; the files that were already pending (Task 0) are as they were; nothing untracked except what the user has not asked to commit.

If any of these differ, stop and report the output rather than fixing around it.

- [ ] **Step 6: Commit**

```bash
git add db/UNIT_BARCODES_MODULE.md docs/superpowers/specs/2026-09-23-unit-barcodes-design.md README.md
git commit -m "docs: the unit barcode guide for the daily series, copies, search and the two-line label" -m "Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>"
```
