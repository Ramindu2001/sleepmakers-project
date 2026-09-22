# Scanner Upload - GRN and Transfer

Staff scan a delivery with the company's 2D scanner in **inventory (batch) mode**, put it in
its USB cradle and press **Upload**. Cloud POS turns the codes into one checked line per
product, with the number of scans as the quantity: into a **GRN**, into a **transfer being
sent**, or as the **received quantities** of a transfer that arrived.

Design: `docs/superpowers/specs/2026-09-22-scanner-upload-design.md`.

---

## 1. Install

Run once on the server, **before** uploading the code:

```
php db/scan_upload_install.php
```

It creates the `scanbatches` table (every applied upload: who, where, when, what). It is
idempotent - a second run reports `[skip]`. `db/scan_upload.sql` is the same change for a
manual install.

## 2. Using it

| Screen | Button | Who sees it |
|---|---|---|
| GRN details, on hold or pending | **Scan / Upload** | super admin, or a role with *Goods Received* create or edit right in that shop |
| Transfer details, sending shop, on hold or pending | **Scan / Upload** | super admin, or *Transfer Note* edit right |
| Transfer details, receiving shop, on hold or pending | **Scan received items** | super admin, or *Transfer Note* edit or verify right |

1. Open the document. Press **Upload** on the cradle - no need to click anything first: typing
   at scanner speed opens the dialog by itself (or click the button first).
2. The dialog shows one line per product. Several uploads add up; **Clear** starts again.
3. Lines in red must be fixed or **left out** before anything can be added:
   - *Not a product in this shop* / *Not on this transfer* - create the product (Add
     Products) and **Check again**, or leave the code out;
   - *Two products share this barcode* - fix the products' barcodes;
   - *Service item*, *Inactive product*, *Has variations - use Add Products*;
   - *Only N in stock* (sending) - scan fewer or leave it out;
   - a missing or invalid price or date.
4. **Add to GRN** / **Add to Transfer** / **Apply received quantities** writes everything in one
   go, or nothing.

**GRN.** Prices come from the product's latest batch (else the product's prices) and can be
edited. A product already on the GRN gets the quantity added to its line, keeping that line's
prices. Shops that use label prices, expiry dates or racks get those fields.

**Sending.** Stock is taken from the product's batches oldest first, split across batches
when needed, with each batch's prices and dates - like picking the batch by hand.

**Receiving.** The preview compares *Sent* with *Scanned*: **Match**, **Short** (the rest stays
in the sending shop when the transfer is verified) or **Extra** (never more than was sent is
received). A product not scanned is received as 0. A shortage is confirmed before it is applied.

**The same batch twice.** A scanner whose memory was not cleared uploads the same batch again.
Adding the same batch to the same GRN or transfer twice is refused with who added it and when;
it can be added again only after a second confirmation. (Receiving sets quantities rather than
adding them, so repeating it changes nothing.)

## 3. Scanner settings

- **Suffix: Enter** (the usual default) is best. **Tab** also works.
- **No suffix** also works: the run-together codes are split on the shop's own barcodes when
  there is exactly one way to do it; anything else is shown as unknown, never guessed.
- A scanner that uploads `CODE,10` (code and count) is read as quantity 10.
- **Show raw** in the dialog shows exactly what the scanner typed (`⏎` Enter, `⇥` Tab).

## 4. Deploying

1. Back up the database.
2. `php db/scan_upload_install.php` on the server.
3. Upload the code (not `tests/` or `tools/`).
4. The GRN / transfer scripts are versioned (`?v=20260922`); clear the nginx cache if pages
   still show the old buttons.

## 5. Tests

```
C:/xampp/php/php.exe tools/phpunit.phar                     # parser, GRN, transfer, batches, migration
C:/xampp/php/php.exe tests/e2e/scan_upload_e2e.php          # end to end over HTTP
node tests/ui/scan_upload_ui.mjs [screenshot-folder]         # headless Chrome typing like the scanner
```
