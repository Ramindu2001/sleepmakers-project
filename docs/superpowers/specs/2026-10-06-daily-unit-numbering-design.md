# Daily Unit Numbering, Unit-Code Search and Two-Line Labels — Design

- **Date:** 2026-10-06
- **Branch:** `main`
- **Status:** Design approved in conversation; this spec is awaiting review
- **Builds on:** `2026-09-23-unit-barcodes-design.md`, `db/UNIT_BARCODES_MODULE.md`

## 1. Problem

Every unit Sleep Makers make carries its own barcode: the product's barcode (the *prefix*)
followed by a generated *suffix* made of the production date and a serial. Today the serial
restarts for every product. On 2026-10-01 three products made 14 units and the highest serial
that day was 6, because each product counted from 1. Staff want the suffix to be **one running
number for the whole day, whatever the product**, so the suffix alone says which unit of the
day it was.

Three more things go with it:

1. the product page cannot find a product from a unit's barcode;
2. a sticker prints the whole code on one line, where the prefix and the suffix should read as
   two lines, one above the bars and one below;
3. each unit's sticker is wanted three times (on the product, on the invoice, on the warranty
   card), but copies today is a global style option that defaults to 1.

## 2. How it works today

| Area | Today | Where |
|---|---|---|
| The code | Pattern, serial width and separator are per shop (`barcodesettings`). The Warehouse already uses `{ITEM}{YY}{MM}{DD}{SEQ}` with 4 digits: `MATCOO00005` + `261001` + `0006`. | `Model/product_unit_class.php` |
| The serial | Atomic counter in `barcodesequence`, keyed `unit:<the code without its serial>`: one series per **item** per date part. | `scopeKey()`, `BarcodeSettings::allocateSequence()` |
| Storage | `productunits`: a row per unit, `UnitBarcode` UNIQUE, `ItemBarcode` (the prefix), `ProducedDate`, `SeqNo`, state. | `db/unit_barcodes.sql` |
| Scanning | A scanned code is looked up whole in `productunits`, which gives its item and then the product in the scanning shop (GRN, transfer, dispatch, till). The parser also uses the pattern's fixed width to split codes typed with no separator. | `Model/scan_*_class.php`, `AJAX/guiPos/getbarcodevalue.php` |
| Product search | `concat(Barcode, ItemName) LIKE '%text%'`, with the text pasted into the SQL. A unit code matches nothing. | `AJAX/Products/getProductSearch.php` |
| Printing | A sticker is the bars and the whole code on one line. `copies` is a global option, default 1. Units are numbered first and the page then renders at most 1,000 stickers. | `Public/print-barcode.php`, `View/modals/product_barcode.php`, `Includes/barcode_helper.php` |

Live data read on 2026-10-06: 214 units (150 printed, 62 received, 2 dispatched) in two suffix
widths (8 on the first day, 10 since); one shop with unit mode on; two companies, both shops in
company 2.

## 3. Goals and non-goals

### Goals

- One serial per day across all products, so the first unit of the day is `0001` whatever it is.
- Search the product page with a unit's full barcode and get its product.
- Unit stickers show the prefix above the bars and the suffix below them.
- A unit's sticker prints three times by default, and the operator can change that.
- Nothing already printed, received or dispatched changes or stops scanning.

### Non-goals

- Renumbering or relabelling any unit already printed.
- Searching by unit code in other screens (invoice item search, adjustments, returns).
- Fixing the code format. The pattern, serial width and separator stay configurable.
- Raising the 1,000-sticker ceiling or splitting a job into several pages automatically.

### Acceptance examples (2026-10-06, 4-digit serial, default pattern)

| Order | Product barcode | Code | Prefix line | Suffix line |
|---|---|---|---|---|
| 1st unit of the day | `PROD001` | `PROD0012610060001` | `PROD001` | `2610060001` |
| 2nd (another product) | `PROD002` | `PROD0022610060002` | `PROD002` | `2610060002` |
| 3rd (first product again) | `PROD001` | `PROD0012610060003` | `PROD001` | `2610060003` |
| 50th of the day | any | `<barcode>2610060050` | | `2610060050` |

Printing A, A, B, C, B gives serials 1, 2, 3, 4, 5. The next day starts again at 0001.
Searching `PROD0012610060003` on the product page finds `PROD001`.

## 4. Numbering

### 4.1 The series

A serial belongs to a **series**, named after everything in the code except the item and the
serial: the *date part*. The key is `unit:c<company id>:<date part>`, for example
`unit:c2:261006`.

- The date part is built by the same code that builds the unit code, from the production
  ("Made on") date. A pattern with `{DD}` therefore gives a daily series, `{YY}{MM}` a monthly
  one, and a pattern with no date a single running one. The series always matches what the code
  shows, which is what keeps codes unique.
- The counter row is kept in `barcodesequence` under **shop id 0**: company-level, and no real
  shop has id 0 (the table has no foreign keys). One series per company means two shops can
  never issue the same code for the same item on the same day, even though the unique key on
  `UnitBarcode` is global.
- The counter is read and written inside the print job's transaction (the application shares one
  database connection), so a refused or failed job gives its numbers back, and concurrent jobs
  for the same day queue on the counter row instead of racing.

### 4.2 Seeding a series

A series that has no counter row yet starts at **the highest serial already issued in that
series, plus one**. "Already issued" means every unit of the company, any item, any shop, any
state (voided units keep their code in the unique key) whose production date renders to the
same date part. After that the existing atomic counter takes over.

This is what makes the change safe for a day that already holds per-item serials, such as
2026-10-01 when "Made on" is back-dated, or the first day after this ships.

### 4.3 Capacity

A serial that would not fit the configured width (9,999 at 4 digits) is refused: the job rolls
back, nothing is numbered, and the message names the *Serial digits* setting. Today the 10,000th
unit would silently get a code one character longer, which breaks the parser's fixed-width
splitting.

### 4.4 Defaults

A shop with unit mode on but no saved pattern gets `{ITEM}{YY}{MM}{DD}{SEQ}` (was
`{ITEM}{YY}{MM}{SEQ}`), so the default is a daily series. The change is in
`ProductUnits::DEFAULTS` and in the install defaults (`db/unit_barcodes_migration.php`,
`db/unit_barcodes.sql`), which only matter to new installs. A shop that has saved a pattern
keeps it.

A saved pattern without `{DD}` still works: its series becomes company-wide for the month
instead of per item. That is the intended meaning of "not per product".

### 4.5 What does not change

The code format, `suffixLength()`, the scan parser, `resolve()`, the unique key, reprinting
(which allocates nothing), and the counters already in `barcodesequence`. The old per-item
counter rows stay where they are, unused.

## 5. Searching by unit code

`AJAX/Products/getProductSearch.php`:

- Besides the existing text match, if the typed text is exactly a unit code that was printed
  (`ProductUnits::resolve`, case-insensitive), the product whose barcode equals that unit's
  `ItemBarcode` (the prefix) is matched too. The rows are the union, with the existing limit of
  50 and the existing shop or company filter, so a code from another company finds nothing.
- Typing a prefix or part of a name works exactly as today.
- The query is parameterised. The text is no longer concatenated into the SQL, and an empty
  `ItemBarcode` never matches products that have no barcode.

## 6. Printing

### 6.1 Two-line label

On a unit sticker whose code starts with the item barcode:

- the **prefix** (the item barcode) prints above the bars and the **suffix** (the rest of the
  code, a leading separator dropped) prints below them. The bars still encode the full code;
- both lines use the existing code style and follow the existing *Barcode number* switch. With
  *Barcode bars* off the two lines simply stack; with *Barcode number* off neither prints;
- a code that does not start with the item barcode (a pattern with `{ITEM}` not first) prints
  as today, on one line below the bars;
- plain product labels (shelf labels) are unchanged.

**Fit.** The extra line costs about 2 to 3 mm. By arithmetic over the nine size profiles it fits
all of them at default settings except 25 x 15 mm, which would be about 0.6 mm over (the default
50 x 25 has about 0.7 mm to spare). For a unit sticker the bars give up whatever the lines do not
fit, never below 3 mm, and only while the bar height is on automatic (a hand-set bar height is
respected). Every profile is checked by rendering before this is called done.

### 6.2 Copies

- A new label option `unit_copies`, default **3**, range 1 to 100, in the same option set as
  `copies`, so it is remembered per browser, saved with "Save as shop default", and reset by
  "Reset options".
- It shows in the dialog's unit panel under *Made on*: **Copies of each unit**, hint "one for the
  product, one for the invoice, one for the warranty card".
- A unit job prints `units x unit_copies` stickers: each unit's sticker repeated that many
  times. The plain `copies` option is untouched and still applies to plain labels.
- **Reprint**: each job's Reprint button on the Unit Barcodes page gets the same box, prefilled
  from the shop default (3 if none). A reprint takes all its other label options from the shop
  defaults, as today, but uses the copies posted from that box instead of the default's.

### 6.3 The sticker ceiling

A page renders at most `bcMaxLabelsPerJob()` stickers (1,000). Today the units are numbered
first and anything beyond the ceiling is recorded as printed but never rendered. At 3 copies the
ceiling is exceeded from 334 units, so this becomes easy to hit. Therefore:

- the dialog shows the total live (**N stickers = U units x C copies**) and, above the ceiling,
  shows a warning and disables *Print Labels*;
- the server checks `sum(units) x unit_copies` (copies after clamping to 1 to 100) **before**
  numbering and refuses, numbering nothing, with a message that says how many units fit at that
  number of copies;
- a reprint is checked the same way (`units in the job x copies`) and refused with the same kind
  of message, instead of silently printing a truncated job.

### 6.4 Messages

A refusal in the unit print path is saved in `$_SESSION['barcode_error']` and the user is sent
back to the product page, but nothing ever reads it, so today the page just reloads. The product
page's message area will show it (as an alert, in the style of its other messages) and clear it
after showing. This covers the existing refusals as well as the two new ones.

### 6.5 Size warning

The dialog's "this code is long for the sticker" warning measures the product barcode, which is
shorter than the unit code actually printed. In unit mode the server will also return the module
count of a representative unit code (`item` + the suffix width) and the dialog uses that.

## 7. Compatibility and data

- **Existing units and stickers.** Untouched. Scanning resolves a code by exact lookup in
  `productunits`, independent of how it was built, so the 8- and 10-wide codes already in use
  keep resolving in the GRN, transfer, dispatch and till.
- **Mixed widths.** As today: the parser uses the current pattern's width for codes typed with
  no separator, and an older-width code scanned on its own line is still resolved by lookup.
  The format does not change here, so this work adds no new mixing.
- **No schema change and no data migration.** The rollout is uploading the changed files.
- **Barcode Settings.** The "running counters" list shows a shop's counters only. The company
  daily counters are under shop 0 and do not appear there; they roll over by themselves.
- **Permissions.** Unchanged: numbering needs `is_print` and (`is_create` or `is_edit`).

## 8. Testing

Tests first, in `ProductUnitsTest` unless noted:

- the series: A, A, B, C, B on one day gives serials 1 to 5; the next day restarts at 1;
- the series key for the live pattern, a monthly pattern, a pattern with a separator and a
  pattern with no date; two companies keep separate series; two shops of one company share one;
- seeding: per-item units already on a date make the next serial one past the highest, back-dated
  production continues that date's series, voided units count;
- capacity: at the width's limit the next print is refused and nothing is written or consumed;
- a failed job gives its numbers back;
- the prefix/suffix split, with and without a separator, and a code that does not start with the
  item;
- the bar-height trim and the ceiling arithmetic (pure helpers);
- the install defaults (`UnitBarcodesMigrationTest`), and the search lookup of a unit code to its
  item.

The tests that assume the old default (`ProductUnitsTest` expectations) are updated. The end-to-end
script (`tests/e2e/unit_barcodes_e2e.php`) is updated for the shared series, and gains steps for
searching the product page by a unit code, the two-line label, and the default of 3 copies.
Before finishing, every label size is rendered to an image and looked at, and the full suite is
run against its baseline (262 tests, 2 existing failures in `GrnScanTest`, unrelated).

## 9. Docs and rollout

- `db/UNIT_BARCODES_MODULE.md`: the code and series, printing and copies, searching, files.
- The Barcode Settings page: help text says the serial is shared by every product made on the same
  date, per company.
- A one-line pointer from `2026-09-23-unit-barcodes-design.md` to this spec, which supersedes its
  numbering rule ("serial restarts per item per month").
- Nothing to run on the database. The next print after deploy uses the new numbering.

## 10. Notes and risks

- The company's daily counter row is locked for the length of a print job, so two people printing
  at once wait for each other briefly. A job is a few hundred rows.
- Not touched: the two existing `GrnScanTest` failures, the print-reference race when two jobs
  start at the same instant, the 5,000-per-product numbering limit.
- The working tree holds earlier uncommitted work, including a CSS fix in
  `View/modals/product_barcode.php`, a file this change also edits. Commits will keep that
  separate or include it only on request.
