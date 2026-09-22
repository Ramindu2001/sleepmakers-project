# Barcode Module

Automatic barcode generation and barcode label printing for Cloud POS.

---

## 1. Install

Run **once** after deploying the files:

```
http://<your-host>/Cloud_POS/db/barcode_module_install.php
```

(log in to Cloud POS first — the installer refuses an anonymous request), or from a shell:

```
php db/barcode_module_install.php
```

Only an **administrator** (`UserType = 1`) can run it from a browser.

It is **additive only** and **idempotent**: nothing existing is dropped, renamed or
re-typed, and running it twice is harmless. It reports what it did and lists any
duplicate barcodes already in `products` so they can be corrected by hand.

`db/barcode_module.sql` is the same schema as plain SQL, for MariaDB 10.4+ / MySQL 8.

### What it adds

| Object | Purpose |
|---|---|
| `categories.CategoryCode` | short prefix code, e.g. `BEV` (nullable) |
| `subcategories.SubCatCode` | short prefix code, e.g. `JUI` (nullable) |
| `products` index `idx_products_barcode` | the barcode is scanned and probed constantly and had no index. **Not unique** — duplicates already exist in live data and a unique index would make the next save fail |
| `barcodesettings` | one row per shop: the generation rules + the shop's default label options |
| `barcodesequence` | the running-number counters |

---

## 2. Automatic barcodes

**Settings → Barcode Settings** (also reachable from the button on the Products page).

A barcode is built from a **token pattern** plus a **sequence configuration**:

```
{PREFIX}{CAT}{SUB}{SEQ}   with prefix "SM" and separator "-"   ->   SM-BEV-JUI-00042
```

### Tokens

| Token | Value |
|---|---|
| `{PREFIX}` | the fixed prefix from the settings page |
| `{CAT}` | main category code (or the first letters of its name) |
| `{SUB}` | sub category code (or the first letters of its name) |
| `{SHOP}` | shop code (or the first letters of the shop name) |
| `{SEQ}` | the running number — **always present**, one is appended if left out |
| `{YYYY} {YY} {MM} {DD}` | date parts |
| `{ITEM:n}` | first *n* letters of the item name |
| `{RAND:n}` | *n* random digits |

Anything that is not a token is used literally.

### The separator

The separator is inserted **only between parts that actually produced a value**, so a
product with no category comes out as `SM-00042`, never `SM--00042`.

### Sequence numbering

`Start at`, `Step by`, `Digits` and `Pad with` are configurable, plus where the
numbering **restarts**:

| Scope | Counter |
|---|---|
| Per prefix *(default)* | a separate number series for every distinct fixed part of the pattern |
| Per sub category | one series per sub category |
| Per main category | one series per main category |
| Per shop | one series for the whole shop |
| Company wide | one series across every shop |

> A pattern containing `{YY}` or `{MM}` under "per prefix" restarts its numbering every
> year / month, because the fixed part of the pattern changes. That is deliberate.

Running counters are listed at the bottom of the settings page and can be set to any
number (to continue an existing series) or reset.

### Barcode types

CODE 128 *(default — encodes anything)*, CODE 39, CODE 93, EAN-13, EAN-8, UPC-A, ITF.

For **EAN-13 / EAN-8 / UPC-A** the module strips non-digits, fits the fixed part and the
sequence into the exact digit count the symbology allows, and appends the **check digit**
— so what is stored is what a scanner reads back. In these modes the sequence
automatically fills the remaining digits and the "Digits" setting does not apply; the
live preview says so.

The settings page validates the combination as you type and warns in plain words
(letters in a numeric symbology, an underscore in CODE 39, a prefix too long for
EAN-13, a code that is already taken, …).

### Where it happens

| Path | Behaviour |
|---|---|
| Add Product, barcode left empty | generated at **save** time |
| Add Product, **Generate** button | allocated immediately and **reserved** |
| Edit Product, barcode cleared | generated (previously this failed with "barcode or username empty") |
| CSV upload (`productController.php`, `bulk_upload`) | generated for rows with an empty barcode column |
| Barcode typed by the operator | **always** used as typed |
| Automatic barcodes switched off | falls back to the product number, exactly as before |

**Previews never consume a number.** The hint under the barcode field and the settings
page preview both *peek* at the counter. Only saving a product, or pressing **Generate**,
takes one — so opening the dialog and closing it again wastes nothing, and two people
never see the same "next" number as their own.

> Importing thousands of rows: the generator re-reads the shop settings, the shop row
> and the category row for every generated barcode (about 6 extra queries per row on top
> of what the importer already does). Fine for hundreds of rows; for a very large import,
> supply the barcodes in the file instead.

**Uniqueness is checked across the whole `products` table**, not per shop, because
`Product::getProductByBarcode()` resolves a scanned code with no shop filter. A code
that collides is skipped and the next number is tried (up to 30 attempts).

### Category codes

Leave a category's code empty and the generator derives one from its name
("Beverage" → `BEV`). **Settings → Barcode Settings → Fill in the missing category
codes** writes the derived codes down in one click, which makes them visible and
editable on the Main Categories / Sub Categories pages and stops a later rename quietly
changing every new barcode.

---

## 3. Label printing

**Products → Print Barcode** (one product) or tick several rows and use the
**Print Barcode (n)** button. The selection survives paging and searching.

### One printed page is one ROW of labels

Not one label. On a 2-across roll, printing one label per page leaves the whole right
hand column blank — half the roll in the bin — and, because the page is then narrower
than the media, the print driver is free to centre it, which drops the content over the
die cut gap. The dialog and the print page both display, as a headline, the exact media
size to set the printer to.

In the printer dialog set **Margins = None**, **Scale = 100%** (never "Fit to page")
and **Headers and footers off**.

### Options

**Size & layout** — 9 preset sticker sizes plus custom W×H · 1–8 labels across ·
column gap · row gap · inner margin · skip *n* already-used stickers on the first row ·
hairline cut guides · automatic printer dialog.

**Content** — shop name (or custom text) · item name (shortened / CAPITALS) ·
second name · category/sub category · product number · barcode bars · barcode number ·
price (custom prefix, 0 or 2 decimals) · batch · printed date (6 formats) · footer text.

**Barcode** — symbology (automatic or forced) · bar height · bar width % · bar colour.

A single print job is capped at **1000 labels** (every sticker carries its own inline
SVG, so 2000 is a 5MB page and ~200,000 DOM nodes for the browser to lay out). The print
page says so and asks for the rest as a second batch.

**Style** — 5 font stacks · alignment · bold name / bold price · per-line text sizes ·
line gap · code tracking · copies of each label.

Every size setting left at **0** means "use the tuning that belongs to this sticker
size", so switching from a 50×25 to a 30×20 re-tunes the typography instead of clipping.

### Where settings are remembered

* **Per browser** — automatically, on every change. The roll loaded in a given printer
  does not change between jobs.
* **Per shop** — **Save as shop default** in the dialog. Every Print Barcode dialog in
  the shop then opens on those settings.

A newly saved shop default wins over older browser choices once (it is timestamped),
then the operator's own adjustments take over again.

---

## 4. Permissions

The whole module rides on the existing **Products** feature (SFID 16) — no new
`sysfeatures` row, so no `userroleaccess` backfill is needed on a live system.

| Action | Right |
|---|---|
| Print labels, tick boxes, print dialog | `is_print` |
| Generate / preview a barcode | `is_create` or `is_edit` |
| Change the barcode rules, save shop defaults | `is_edit` |

Administrators (`UserType = 1`) always pass. Every check is enforced **server side** in
the endpoint, not only by hiding the button.

---

## 5. Files

**New**

```
db/barcode_module.sql                     schema
db/barcode_module_install.php             idempotent installer
db/BARCODE_MODULE.md                      this file

Includes/barcode_generator.php            generation engine (tokens, sequences, symbology)
Includes/barcode_helper.php               label geometry, options, rendering, permissions
Includes/barcode_ajax.php                 shared JSON bootstrap for the endpoints

Model/barcode_settings_class.php          settings + atomic sequence allocator
Controller/barcodeSettingsController.php  settings form handler
Public/barcode-settings.php               Settings > Barcode Settings
Public/print-barcode.php                  label renderer

AJAX/Products/generateBarcode.php         preview (peek) / generate (allocate)
AJAX/Products/getBarcodeItems.php         data for the print dialog
AJAX/Barcode/previewPattern.php           settings live preview
AJAX/Barcode/saveLabelDefaults.php        save the shop's label defaults

Assets/jquery/barcode-label.js            print dialog + product multi select
Assets/jquery/barcode-settings.js         settings live preview
```

**Modified** (a `.bak_barcode_<timestamp>` copy of each was taken alongside it)

```
Includes/includes.php                     registers the new model
Model/category_class.php                  category / sub category codes; duplicate check fix
Controller/categoryController.php         saves the codes; duplicate check fix
Controller/productController.php          generates on save (add, edit, CSV upload)
Public/product.php                        tick box column, bulk print button, scripts
Public/category.php, Public/subcategory.php   Barcode Code column
View/modals/product_barcode.php           REPLACED with the new print dialog
View/modals/add-products.php              Generate button + live hint
View/modals/main-category.php, sub-category.php   Barcode Code field
View/sidebar.php                          Items > Barcode Settings
Assets/jquery/product.js                  old label handler removed, auto barcode hint added
Assets/jquery/category.js                 carries the code field
AJAX/Products/getProductSearch.php        tick boxes in search results
```

### One pre-existing bug fixed along the way

`categoryController.php` rejected **any** category or sub category update that kept the
same name, because the duplicate-name check counted the row being edited. That made the
new Barcode Code field impossible to set (setting a code is exactly the case where the
name does not change). Both checks now skip the row being edited; duplicate protection
against *other* rows is unchanged.

---

## 6. Deliberately not changed

`Controller/UploadController.php` and `Controller/bulkUploadController.php` take the
barcode from a **mapped spreadsheet column** and use it as the key to match or create
products (`$products_cache[$barcode]`, `checkItem($barcode, …)`). Generating a barcode
there would break that matching, so those two importers are untouched: they still expect
the barcode to be in the file.
