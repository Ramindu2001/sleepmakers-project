# Scanner Upload for GRN and Transfer — Design

- **Date:** 2026-09-22
- **Branch:** `development`
- **Status:** Approved in design review (sub-project 2 of the customer's four requirements)

## 1. Problem

The company sells its own 2D barcode scanner. In *inventory (batch) mode* the scanner stores
every scan; placed in its USB cradle, the **Upload** button types the stored codes into the PC
like a keyboard, each followed by the scanner's suffix (Enter by default).

Today the GRN and Transfer screens take one product at a time (product picker + typed quantity).
Staff who scan a delivery have to click an input first and then upload, and the codes arrive as
one run-together "sentence" (`COO00001COO00001COO00002`): a single-line input drops the Enter
keys. Nothing counts the scans, finds duplicates or checks the codes.

Every unit of a product carries the product's own barcode (the app's barcode module prints one
code per product), so **scanning a code N times means quantity N**.

## 2. Goals

- On the GRN screen, and on both sides of a transfer (sending and receiving), staff open
  **Scan / Upload**, press Upload on the cradle and get one line per product with its quantity.
- No input has to be clicked first; the codes never run together.
- Every code is validated; problems are shown, never silently dropped or guessed.
- The same scan batch cannot be added twice to a document by accident.
- Results match what manual entry would have produced (prices, batches, dates, racks).
- Manual entry is unchanged.

### Non-goals

- Per-unit serial numbers (the customer confirmed one barcode per product).
- Products with variations in shops that use variations (they stay on manual entry; the
  Sleep Makers shops have variations off).
- Scanning on other screens (POS, adjustments, supplier returns).

## 3. Decisions made in review

| Question | Decision |
|---|---|
| Barcodes | Same code on every unit of a product; count = quantity. |
| Transfer | Both sides scan: the sending shop builds the transfer, the receiving shop confirms what arrived. |
| Nothing dropped silently | *Add* stays disabled until every problem line is fixed or explicitly left out. |
| Duplicate batch | Blocked with who/when; adding it again needs a second confirmation. |

## 4. Capturing the upload (browser)

`Assets/jquery/scan_upload.js` + `View/modals/scan-upload.php`, one component for all three uses.

- **Scan / Upload** button opens the *Upload from scanner* dialog. The dialog has no fade, so
  it is visible synchronously and its capture box (`#scan_capture`, a textarea) takes the focus
  at once: the first keystroke of an upload already lands there.
- The textarea keeps Enter as a new line. **Tab** is caught and turned into a new line (a
  scanner set to a Tab suffix would otherwise move the focus away).
- **Auto-open:** while the dialog is closed and no input, select, textarea or editable element
  has the focus, keystrokes are buffered. A burst of at least 4 characters typed at scanner
  speed (≤ 35 ms between keys on average, no gap over 100 ms) and ended by Enter or Tab opens
  the dialog with the buffered text; the rest of the upload goes straight into the box. Slower
  typing is discarded, as today.
- Each upload is added to the box (several uploads can be combined); **Clear** empties it.
- 400 ms after the last keystroke the box is sent to the server for a **check**; the preview
  appears. **Check again** repeats it by hand.
- **Show raw** shows exactly what arrived, with `⏎` and `⇥` markers, to diagnose scanner
  settings.

## 5. Reading the codes (server) — `ScanParser`

Pure PHP, no database: `ScanParser::parse(string $raw, array $knownBarcodes): array`.

1. Up to 200 000 characters and 5 000 codes; control characters other than Tab/CR/LF removed.
2. Lines are split on CR, LF or CRLF; empty lines ignored.
3. A line of exactly two tokens whose second token is 1–5 digits and is not itself a known
   barcode is **code + quantity** (`COO00001,10`, `COO00001 10`, `COO00001<Tab>10`).
4. Otherwise a line is split on spaces, Tabs, commas and semicolons; each token counts 1.
5. A token is matched to a known barcode case-insensitively, trying the value as scanned, with
   one leading `0` added, and with leading zeros removed (as the POS scan does).
6. A token that is not a known barcode is **split against the known barcodes** (dynamic
   programming over the string). Exactly one way to split the whole token → those codes.
   None, or more than one → the token is **unknown** (never guessed).
7. Result: `items` (canonical barcode → count), `unknown` (token → count), `scans` (total).

The known barcodes come from the caller (the shop's products for GRN and sending, the
transfer's own products for receiving).

## 6. GRN — `GrnScan`

Available while the GRN is **on hold or pending**, in the GRN's own shop, to a super admin or a
role with *Goods Received* create or edit rights in that shop.

**Product for a code:** products of the GRN's shop (or, when the company has
`is_multicategory = 1`, of the company, preferring the shop's own product), `ItemType = 'P'`.

| Case | Status |
|---|---|
| no product with that code | error *Not a product in this shop* |
| two products share the code | error *Two products share this barcode* |
| service item / inactive product | error *Service item* / *Inactive product* |
| product has variations and the shop uses variations | error *Has variations - use Add Products* |
| product already has a line in this GRN | ok, *adds to the existing line: 5 → 15*; that line's prices are kept (read-only) |
| otherwise | ok, new line |

**Prices of a new line** are pre-filled from the product's latest batch in this shop
(`pricehistory`), else the product's purchase / selling price, and are editable. Label price
appears when the shop uses label prices. Shops that track expiry get Mnf / Exp date fields per
line (same rules as manual entry: Mnf before today, Exp after today and after Mnf). Shops that
use racks get one Section / Rack choice for the upload. Variation id and rack follow the manual
defaults (no-variation value; rack 1 when the shop has no racks).

**Apply** (one database transaction): lock the GRN row (`FOR UPDATE`) and re-check its status,
shop and rights; parse the posted raw text again (the client's counts are never trusted);
apply the user's decisions (left-out codes, prices, dates, rack); refuse while any error line is
not left out, or any price is missing or negative; refuse a duplicate batch unless confirmed;
insert new lines / add to existing lines (quantities and totals recomputed); record the batch.

## 7. Transfer, sending side — `TransferScan::send…`

Available while the transfer is on hold or pending, in the **sending** shop, to a super admin or
a role with *Transfer Note* edit rights there.

- Products are found as for the GRN (sending shop's products).
- **Batches:** the shop's inventory rows of the product with stock, oldest first (`INID`), each
  reduced by what this transfer already takes from it. The scanned quantity is split across
  them; each part becomes a line with that batch's `BatchID`, prices, dates and variation (as
  manual entry takes them from the batch).
- More scanned than available → error *Only N in stock (M already on this transfer)*.
- A part for a batch already on the transfer adds to that line (transfer and received qty).
- Duplicate batch guard as for the GRN.

## 8. Transfer, receiving side — `TransferScan::receive…`

Available while the transfer is on hold or pending, in the **receiving** shop, to a super admin
or a role with *Transfer Note* edit or verify rights there.

- Codes are matched against the products **on this transfer** (the receiving shop may not have
  the product yet: it is created when the transfer is verified).
- Preview per product on the transfer: Sent, Scanned, and *Match*, *Short −N* or *Extra +N*.
  A scanned product not on the transfer is an error line (*Not in this transfer*).
- **Apply** sets each line's received quantity: the scans of a product fill its lines in order,
  each up to its sent quantity; a product not scanned is received as 0. Totals are recomputed.
  When anything is short the user confirms first (*N items short - they stay in the sending
  shop*). Verify then moves only the received quantities (existing behaviour).
- Receiving sets quantities rather than adding, so uploading the same batch again changes
  nothing; the duplicate guard does not apply. The batch is still recorded.

## 9. Data — `scanbatches`

| Column | |
|---|---|
| `SBID` | PK |
| `DocType` | `GRN`, `TRF_OUT`, `TRF_IN` |
| `DocID` | GRN header or transfer header id |
| `shop_SHID`, `user_USID` | where and who |
| `Fingerprint` | SHA-256 of the applied lines, sorted `barcode<Tab>count` |
| `ScanCount`, `LineCount` | codes read, products applied |
| `Summary` | JSON of the applied lines (audit) |
| `CreatedAt` | datetime |

Index `(DocType, DocID, Fingerprint)`. Installed by `db/scan_upload_install.php`
(`ScanUploadMigration`, idempotent; also `db/scan_upload.sql`) **before** the code is uploaded.

## 10. Endpoint — `Controller/ScanUploadController.php`

POST, JSON `{ok, message, preview?}`, like `AddUsersToShopsController`:

- signed in with a shop session and still allowed in that shop, else 403;
- CSRF token, else 400;
- `context` = `grn` | `transfer_send` | `transfer_receive`, `doc_id`, `action` = `check` | `apply`,
  `raw`, and for apply the decisions (`leave_out[]`, `prices[product][purchase|selling|label]`,
  `dates[product][mnf|exp]`, `rack_id`, `confirm_duplicate`, `confirm_short`);
- rights and document state are checked by the model; refusals come back as 403 / 409 / 422
  with a message and, where useful, the preview to show.

Rights use a new `ShopAccess::hasFeatureRight($user_id, $shop_id, $feature_id, $rights)`
(super admin, or the role held in that shop grants one of the rights).

## 11. Screens

- **GRN details** (`Public/grn-details.php`), on hold / pending: **Scan / Upload** next to
  *Add Products*.
- **Transfer details**, on hold / pending: **Scan / Upload** in the sending shop, **Scan received
  items** in the receiving shop.
- The dialog uses the app's Bootstrap modal, table and badge styles. After *Add* / *Apply* it
  closes, shows the summary, and the page reloads its table and totals through a
  `scanupload:applied` event handled in `grn_detail.js` / `transfer_details.js`.

## 12. Testing

- PHPUnit (`sleepmakers_test`), with the GRN, product, inventory, price history and transfer
  tables added to the test schema:
  - `ScanParser`: Enter / Tab / space / comma separated, run-together, code + quantity, unknown
    and ambiguous tokens, leading zeros, limits.
  - `ScanBatches`: fingerprint, duplicate lookup, record.
  - `GrnScan`: new line, merge, prices, errors, locked GRN, other shop's GRN, rights, duplicate
    batch, all-or-nothing.
  - `TransferScan`: oldest-first split, stock limits, merge, receive match / short / extra /
    not on transfer.
  - `ScanUploadMigration`: creates, idempotent.
- E2E over HTTP (`tests/e2e/scan_upload_e2e.php`), with its own e2e shop, products and
  documents: check, apply, refusals (rights, CSRF, status, duplicate).
- Headless Chrome: types like a scanner (with Enter, with Tab, run together) on the GRN page
  without clicking anything; the dialog opens, the preview counts are right, *Add* updates the
  table.
- Regression crawl and database restore as for sub-project 1.

## 13. Delivery

One commit and push per step on `development`:
parser + table → GRN → transfer send → transfer receive → docs.
Deploy: back up, run `php db/scan_upload_install.php`, upload the code.
