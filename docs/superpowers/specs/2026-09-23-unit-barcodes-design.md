# Unique Barcode Per Unit — Design

- **Date:** 2026-09-23
- **Branch:** `main`
- **Status:** Approved by the customer (three format/scope choices confirmed before implementation)

## 1. Problem

Today one product has one barcode. A hundred Cooler Mattresses share `COO00001`, and the
scanner upload built for GRNs deliberately reads a code twice as *quantity two*.

Sleep Makers make the goods they sell, so every mattress that leaves the factory floor is a
distinct unit with its own sticker. They need:

1. a unique barcode on every unit;
2. duplicate scanning to be impossible;
3. the product to be identifiable from the code;
4. the production date to be identifiable from the code;
5. a count of what was produced on a given date;
6. GRN scanning where many different codes of the same product add up to that product's
   quantity.

One product on the live system already carries `109D25090013` — an item code, `2509`, and a
serial `0013`. That is the sticker they already print, and the format below reproduces it.

## 2. Goals

- Print *n* stickers for a product and a production date, each with its own code, recorded.
- Scan those codes into a GRN: each adds one to that product's quantity.
- Never take the same unit in twice — not in one upload, not across GRNs, not ever.
- Answer "how many did we make on the 12th" and "where is unit X" from the system.
- Unit codes keep working wherever a barcode is scanned today, above all at the till.

### Non-goals

- Marking a unit sold or transferred. The customer chose *printed and received*; POS and
  transfer scans resolve a unit code to its product but record nothing against the unit.
  Adding that later needs no change to what is built here, only new writes.
- Replacing the product barcode. Shelf labels and the POS product search are unchanged.

## 3. The code

Chosen format (`{ITEM}{YYMM}{SEQ}`, no separator, 4-digit serial):

```
COO00001 2509 0013      ->   COO0000125090013
   |      |    |
   |      |    +-- serial, restarts per item per month
   |      +------- year and month produced
   +-------------- the product's own barcode
```

- **{ITEM}** is `products.Barcode` — every product already has one, so all 275 work at once.
- The pattern lives in `barcodesettings` per shop, so the customer can change it: the date
  token may be `{YYMM}`, `{YYMMDD}` or absent, a separator may be added, and the serial
  width is configurable. The **exact production day is always stored** on the unit row, so a
  month-level code still answers "how many on the 12th".
- The serial is allocated with the existing atomic counter (`BarcodeSettings::allocateSequence`)
  under the scope key `unit:<item>:<date part>` — the same mechanism product barcodes use, so
  two people printing at once can never get the same number.
- CODE 128, like the rest of the catalog. 16 characters is ~3 mm wider than today's 8.

## 4. Storage

```
productunits
  PUID           bigint      auto increment
  UnitBarcode    varchar(64) UNIQUE   <- the same code can never be registered twice
  ItemBarcode    varchar(45)          <- the product barcode it resolves to, in any shop
  products_PDID  int                  <- the product it was printed for
  shop_SHID      int                  <- the shop that printed it
  ProducedDate   date                 <- the exact production day
  SeqNo          int
  UnitStat       tinyint              <- 1 printed, 2 received, 0 voided
  PrintRef       varchar(20)          <- the print job (UP_000001), so a reprint finds it
  PrintedAt / PrintedBy / PrintCount / LastPrintedAt
  GRNHeader_GHID / grndetails_GDID / ReceivedAt / ReceivedBy   <- filled when received
```

`ItemBarcode` rather than only `products_PDID`, because a transfer copies a product into the
receiving shop **with the same barcode**: a unit printed in the Warehouse must resolve in
Valentino Italy, where the product is a different row.

## 5. Printing

*Products → Print Barcode* gains a **unique barcode per unit** mode (a per-shop setting,
default on where the unit pattern is configured):

- per selected product: quantity and a production date (today by default);
- the server allocates that many serials in one transaction, writes the `productunits` rows
  and prints one sticker per code - never the same code twice;
- a job is capped by the existing 1000-label ceiling;
- **Re-printing** is a separate path: *Unit Barcodes* lists what was printed, by print job,
  product and date, and reprints exactly those codes, bumping `PrintCount`. A reprint never
  allocates a new number, so a damaged sticker is replaced, not duplicated.

Plain product labels stay available for shelf labels.

## 6. GRN scanning

A scanned token is resolved in this order:

1. an exact product barcode (what happens today) - counts as quantity, unchanged;
2. the unit shape: `<known item barcode><date part><serial>` - looked up in `productunits`;
3. otherwise unknown.

Then, per unit:

| Case | What the preview says |
|---|---|
| a fresh unit | adds 1 to that product, on that production date |
| the same code twice in one upload | counted **once**, line marked *scanned twice* |
| already taken into a GRN | red line: *already received on GRN_000004*, can be left out |
| well-formed but never printed | red line: *not a unit we printed* |
| the unit's product is not in this shop | red line, as for any foreign barcode |
| a quantity typed after a unit code (`CODE,10`) | red line: a unit is one item |

Units of the same product with **different production dates** become separate GRN lines, each
carrying its own `MnfDate`, so the GRN shows what was made when.

`apply()` marks every applied unit received, with the GRN and the line it went into, inside
the same transaction that writes the GRN lines. The unique key on `UnitBarcode` plus that
state is what makes double registration impossible even if two people apply at the same moment.

## 7. Everywhere else a barcode is scanned

- **POS** (`AJAX/guiPos/getbarcodevalue.php`): if the scanned value is not a product barcode,
  resolve it as a unit and return its product. Without this the sticker on the box cannot be
  sold at the till.
- **Transfer scanning** (send and receive): the same resolution, so units can be picked and
  checked by scanning. Nothing is recorded against the unit (see non-goals).

## 8. Reporting

*Unit Barcodes* page: units per product and production date, with how many are printed, how
many received, and into which GRN. This answers "how many units were produced on a date" and
finds any single unit by its code.

## 9. Testing

- The allocator: serials are unique under concurrency, restart per item per month, and the
  unique key rejects a second row for the same code.
- The resolver: product code, unit code, unknown, foreign shop, wrong shape.
- GRN scanning: 100 codes of one product make quantity 100; a repeat in the batch counts once;
  a code already received is refused and names its GRN; two production dates make two lines.
- Reprint reuses codes and allocates nothing.
- End to end over HTTP: print a batch, scan it into a GRN, scan it again, verify the refusal.

## 10. Upgrade

`db/unit_barcodes_install.php`, additive and idempotent: the new table and the new
`barcodesettings` columns. Nothing existing changes, and with the unit mode off the
application behaves exactly as it does today.
