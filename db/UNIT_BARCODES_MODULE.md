# Unique Barcode Per Unit

A shop that makes what it sells puts a different barcode on every unit. Builds on the barcode
module (`db/BARCODE_MODULE.md`), which numbers **products**; this one numbers the **units** of
a product. Design: `docs/superpowers/specs/2026-09-23-unit-barcodes-design.md`.

---

## 1. Install

Run **once, before** uploading the module's code:

```
php db/unit_barcodes_install.php
```

Command line only - on the live server `db/.htaccess` keeps `db/` off the web.

**Additive only** and **idempotent**. Until a shop switches the feature on, nothing behaves
differently anywhere. `db/unit_barcodes.sql` is the same schema as plain SQL.

### What it adds

| Object | Purpose |
|---|---|
| `productunits` | one row per printed unit; `UnitBarcode` is **unique**, which is what makes registering a unit twice impossible |
| `barcodesettings.UnitMode` | `1` = this shop prints a unique barcode on every unit |
| `barcodesettings.UnitPattern` | how a unit code is built, default `{ITEM}{YY}{MM}{DD}{SEQ}` |
| `barcodesettings.UnitSeqLength` | digits in the serial, default `4` |
| `barcodesettings.UnitSeparator` | between the parts, default none |

---

## 2. The code

```
COO00001 250912 0013    ->  COO000012509120013
   |       |      |
   |       |      +-- serial: one running number for the day, whatever the product
   |       +--------- year, month and day it was made
   +----------------- the product's own barcode - the prefix (the rest is the suffix)
```

| Token | Value |
|---|---|
| `{ITEM}` | the product's barcode (`products.Barcode`) - **required** |
| `{YYYY} {YY} {MM} {DD}` | parts of the production date |
| `{SEQ}` | the serial - **required**, padded to *Serial digits* |

The serial comes from the barcode module's atomic counter, under the key
`unit:c<company id>:<the date part of the code>` (for example `unit:c2:261006`), held under shop
id 0 because the series belongs to the company. The item is **not** part of the key: every product
made on the same day shares one running serial, so the first unit of the day is `0001` whatever it
is, the next is `0002`, and the next day starts at `0001` again. With `{YY}{MM}` only in the pattern
the series is the month's. A series that has no counter yet starts after the highest serial already
issued in it (any item, any shop of the company, any state), so a day that was numbered item by item
before this rule cannot repeat a code. When the serial's digits run out (9,999 at 4 digits) the print
is refused, with the number given back; raise *Serial digits*.

**The exact production day is always stored on the row**, whatever the code carries. A code
with only the month still answers "how many did we make on the 12th".

> Keep the serial wide enough. With 4 digits an item can have 9,999 units in one month; the
> 10,000th makes the code one character longer, which is legal but breaks the fixed width that
> lets codes typed with no separator be split apart.

### Changing the pattern

*Settings → Barcode Settings → A Unique Barcode On Every Unit*, with a live example. Codes
already printed never change - only new ones follow the new rule.

---

## 3. Printing

*Products* → select → **Print Barcode** → tick **A unique barcode on every unit**, set
**Made on**, and the *Labels* column becomes *Units*.

- The server numbers that many units **in one transaction** and prints one sticker per code.
- Numbering happens once per job; reloading the label page re-renders the same codes.
- Untick the box for a plain product label (a shelf label, say).
- **Copies of each unit** (default 3, 1 to 100): one sticker for the product, one for the invoice,
  one for the warranty card. The total is units x copies.
- A sticker shows the item barcode **above** the bars and the date and serial **below** them; the
  bars encode the whole code. Plain product labels are unchanged.
- One job holds at most 1,000 stickers (units x copies). A bigger job is refused **before** any
  unit is numbered, with the number of units that would fit; the dialog shows the total live and
  disables *Print Labels* over it.

### Reprinting

*Items → Unit Barcodes → Reprint* puts the codes of an earlier job on paper again. It
**allocates nothing**: the same codes come out, and `PrintCount` records that it happened. That
is how a damaged sticker is replaced without inventing a second unit.

Each job has a copies box beside *Reprint* (the shop's saved default, three if none). A reprint
that would exceed one page of stickers is refused with the same message as a new job.

---

## 4. Receiving - the GRN

Scan the stickers into a GRN with the usual **Scan / Upload** button.

| What is scanned | What happens |
|---|---|
| many different unit codes of one product | they add up: 100 codes → quantity 100 |
| the same code twice in one upload | counted **once**, the line says *n scanned twice* |
| a code already taken into a GRN | refused: *Already received on GRN_000004* |
| a code shaped like a unit that we never printed | refused: *Not a unit we printed* |
| a unit whose item this shop does not carry | refused, like any foreign barcode |
| a plain product barcode | unchanged: it still counts as quantity |

Units of the same product made on **different days** become **separate GRN lines**, each
carrying its own `MnfDate`, so the GRN shows what was made when.

Applying marks every unit received, with the GRN and the line it went into, inside the same
transaction that writes the lines. If another upload took one of them a moment earlier, the
whole upload is refused rather than half applied.

Refused lines can be left out like any other, so one bad sticker never blocks a delivery.

---

## 5. Everywhere else

- **The till.** A unit code that is not a product code is looked up and the till sells the
  product it belongs to, so the sticker on the box scans at the counter.
- **Transfers.** Picking and checking by scanning unit stickers works on both sides; each unit
  counts one for its product.
- **The product page.** Type or scan a unit's whole barcode into the search box and the product it
  belongs to is found, in any case. Typing a product barcode or part of a name works as before.
- Nothing is recorded against a unit in either place - a unit is tracked as **printed** and
  **received**, which is what was asked for. Marking sold or transferred would be new writes
  on top of what is here, not a change to it.

---

## 6. Reporting - Items → Unit Barcodes

- **Produced**: units per day and item between two dates, with how many have been received.
- **Print jobs**: the last 25, with a Reprint button.
- **Look a unit up**: paste or scan a code to see its item, the day it was made, when it was
  printed and whether it has been received, and on which GRN.

---

## 7. Permissions

Like the rest of the barcode module, this rides on the **Products** feature (SFID 16) - no new
`sysfeatures` row, so nothing to grant on a live system.

| Action | Right |
|---|---|
| Print labels, reprint a job | `is_print` |
| Number new units | `is_print` **and** `is_create` or `is_edit` |
| Change the unit rules | `is_edit` (the Barcode Settings page's own right) |
| Scan units into a GRN | the GRN feature (SFID 2), as before |

---

## 8. Files

**New**

```
db/unit_barcodes_migration.php     the schema change
db/unit_barcodes_install.php       the installer
db/unit_barcodes.sql               the same as plain SQL
db/UNIT_BARCODES_MODULE.md         this file

Model/product_unit_class.php          numbering, resolving, receiving, reporting
Model/unit_barcode_refused_class.php  a refusal with an HTTP status
Public/unit-barcodes.php              Items > Unit Barcodes
```

**Modified**

```
Model/scan_parser_class.php       reads unit-shaped tokens, splits runs of them
Model/scan_document_class.php     folds unit codes into their item (transfers)
Model/scan_grn_class.php          unit lines, duplicates, per-production-date lines
Model/scan_transfer_class.php     unit codes on both sides of a transfer
Model/barcode_settings_class.php  saveUnitSettings()
Public/print-barcode.php          a job can carry one code per unit, reprints, the two-line code, copies, the ceiling check
Public/barcode-settings.php       the unit rules
View/modals/product_barcode.php   the unit mode, the production date, the copies of each unit
View/sidebar.php                  Items > Unit Barcodes
Assets/jquery/barcode-label.js    the mode toggle, the live sticker total and its ceiling
AJAX/guiPos/getbarcodevalue.php   the till resolves a unit code
Controller/barcodeSettingsController.php  saves the unit rules
Includes/barcode_helper.php       the copies of a unit, the one-page ceiling, the bars of a two-line code
AJAX/Products/getProductSearch.php   finds a product from a unit's whole barcode
AJAX/Products/getBarcodeItems.php    the length of a unit code, for the dialog's size warning
Public/product.php                shows why a print was refused
Public/unit-barcodes.php          the copies box beside Reprint
```

---

## 9. Tests

```
C:/xampp/php/php.exe tools/phpunit.phar                             # unit + integration
C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php [base-url]     # against a running site
```

`ProductUnitsTest` covers numbering, the pattern, resolving, receiving, reprinting and the
production count; `UnitBarcodesMigrationTest` the install; `ScanParserTest` the unit-shaped
tokens; `GrnScanUnitsTest` the GRN rules above. The end-to-end script numbers three units,
prints them, scans them into a GRN, scans them again and is refused, reprints, and sells one
at the till.

`UnitLabelTest` covers the copies of a unit, the one-page ceiling and the bars of a two-line code;
`ProductUnitsTest` also covers the shared daily serial, seeding, capacity and the prefix/suffix split.
The end-to-end script also searches the product page by a unit code, checks the two-line label, the
three copies and the refusal message. The browser check is
`BASE=<site> node tests/ui/unit_label_ui.mjs [screenshot-folder]`: the dialog, and every sticker size
for clipping.
