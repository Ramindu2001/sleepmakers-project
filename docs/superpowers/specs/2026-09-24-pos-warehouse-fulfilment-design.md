# POS to Warehouse Fulfilment — Design

- **Date:** 2026-09-24
- **Branch:** `main`
- **Status:** Approved in design review

## 1. Problem

A customer walks into the showroom and asks for a bedsheet, a bed and a custom-made headboard.
The bedsheet is on the shelf. The bed is only in the warehouse. The headboard does not exist
yet. Today the cashier cannot put that order through POS at all: POS refuses any line the shop
has no stock of (`Controller/guiPosController.php`, lines 47-95 and 707 onward), and a product
the shop has never held has no product row in the shop, so POS cannot even show it.

The *Customer Orders* module built on 2026-09-22 asks the warehouse for the missing items, but
it is a separate form typed by hand after the sale, it fulfils orders with a **transfer to the
showroom** rather than a delivery to the customer, and it has no dispatch check. It has never
been used on the live site — `customerorders` is empty — so it can be reshaped freely.

What is missing is a single path: bill and take the money once, in POS, and let the system
decide line by line what leaves the shop today and what the warehouse owes the customer.

## 2. Goals

- **POS is the only front door.** One invoice, one payment, whether the goods are on the shelf,
  in the warehouse, or not made yet.
- The system **works out by itself** which lines the shop can give and which the warehouse must
  supply, including part of a line (1 of 3 beds here, 2 from the warehouse).
- Every line carries **its own status**, so an item handed over the counter can never be picked
  again in the warehouse.
- The warehouse gets a **queue of its own**: customer, invoice, money, delivery, what to
  prepare, what was already given, what is still pending.
- **Nothing is dispatched unscanned.** Each item is scanned against that order; wrong items,
  duplicates and over-picking are refused.
- An order is **Completed only when the goods reach the customer**, never because it was paid.

### Non-goals

- Refunding a line the warehouse cannot supply — the line is cancelled with a reason and the
  money is returned through the existing Sales Return module.
- Vehicle scheduling, route planning, driver apps, customer notifications.
- Changing how POS prices, discounts, payments or receipts work.
- The wholesale invoice and the old unlinked `gui_pos.php`; both are left alone.

## 3. Decisions made in review

| Question | Decision |
|---|---|
| Where the goods go | The customer's address by default; an order may be marked *collect at the shop*. |
| Whose stock | The warehouse's, consumed at dispatch. The shop's invoice is the sale; no transfer paperwork. |
| The existing Customer Orders module | Folded in. Orders are created by POS; the hand-typed request form is retired; its tables, numbering and role right are reused. |
| Who completes an order | Whoever handed it over, after dispatch. Dispatched and Completed stay separate states. |
| Customer details | A real customer record (picked or added in POS, as today). |
| Part-paid orders | Dispatch is allowed; the balance is shown in red and must be confirmed. |
| Part of an order ready | Dispatch what is ready; the rest stays pending on the same order. |
| What is scanned | A unit sticker where there is one, otherwise the product barcode. Custom items are ticked by hand. |

## 4. Data

All changes are additive. Installed by `db/warehouse_fulfilment_install.php`
(`WarehouseFulfilmentMigration`, idempotent; also `db/warehouse_fulfilment.sql`) **before** the
code is uploaded.

### `customerorders` — new columns

| Column | |
|---|---|
| `InvoiceHeader_IHID` | the POS invoice that created the order, indexed |
| `DeliverTo` | 1 the customer's address, 2 collect at the shop |
| `DeliveryAddress`, `DeliveryPhone`, `DeliveryNote` | where it goes; prefilled from the customer |
| `OrderStat` | **redefined** (below) |

`CustName`, `CustPhone`, `CustAddress`, `NeededBy`, `Notes`, `AdvancePaid`, `RejectReason`,
`user_USID`, `CreatedAt`, `DecidedBy/At`, `ClosedBy/At` keep their meaning; `RejectReason`
becomes the cancel reason and `customers_CTID` is added so the order points at the saved
customer. Money is **never copied**: net, paid and balance are read by joining `invoiceheader`
(`NetAmount`, `CustPayment`, `CustBalance`), so a later payment shows up by itself.

`OrderStat` is renumbered. Live holds no rows; the migration maps anything found in a
development database: 1→1, 2→2, 3→6, 4→6, 5→5.

| | |
|---|---|
| 1 | Pending |
| 2 | Preparing |
| 3 | Ready |
| 4 | Dispatched |
| 5 | Completed |
| 6 | Cancelled |

### `customerorderlines` — new columns

| Column | |
|---|---|
| `LineStat` | 1 Given at shop, 2 Pending, 3 Ready, 4 Dispatched, 5 Delivered, 6 Cancelled |
| `DispatchedQty`, `DeliveredQty` | decimal, default 0 |
| `SupplierProductID` | the **warehouse's** product, NULL for a custom-made line |
| `UnitPrice`, `LineTotal` | what the customer was billed, for the job sheet |
| `CancelReason` | why the warehouse could not supply it |

`products_PDID` **changes meaning**: it is now always the **shop's own** product — the one the
invoice line carries — and NULL only for a custom-made line. The warehouse's product moves to
`SupplierProductID`. The migration states this; no live row is affected.

`LineSource` keeps `GIVEN` / `WAREHOUSE`. A `GIVEN` line is created with `LineStat` 1 and is
never pickable.

A line of three beds may go out in two deliveries, so quantity and state are kept apart:
`DispatchedQty` and `DeliveredQty` count what has gone and what has arrived, while `LineStat`
describes **the remainder**. A line reads *Dispatched 2 of 3* with `LineStat` still Pending or
Ready for the last one; it becomes Dispatched when `DispatchedQty` reaches `Qty`, and Delivered
when `DeliveredQty` does. `AdvancePaid`, which the old module used, is left unused — the money
now comes from the invoice.

### `orderdispatches` — new table

`DSID`; `DispatchNo` (`DS_000001`, per shop, unique with `shop_SHID`); `customerorders_COID`;
`shop_SHID` (the warehouse); `DispatchStat` (1 open, 2 sent, 3 cancelled); `DeliverTo`;
`DeliveryNote` (vehicle, driver); `CreatedBy/At`; `SentBy/At`; `DeliveredBy/At`.

### `orderdispatchlines` — new table

`DDID`; `orderdispatches_DSID`; `customerorderlines_COLID`; `products_PDID` (the warehouse
product actually issued); `Qty`; `UnitBarcode` (NULL when a product barcode was scanned);
`productunits_PUID` (NULL likewise); `InventoryID`, `Batch_ID` (filled when the dispatch is
sent); `ScannedAt`, `ScannedBy`. Unique key on (`orderdispatches_DSID`, `UnitBarcode`) — MySQL
allows many NULLs, so product-barcode rows are unaffected while the same sticker cannot be
scanned twice into one dispatch.

### `productunits` — new state and columns

`UnitStat` gains **3 = dispatched to a customer** (0 voided, 1 printed, 2 received). New
columns `orderdispatches_DSID`, `DispatchedAt`, `DispatchedBy`. A unit is accepted for dispatch
in state 1 or 2 — the warehouse may sticker goods at dispatch time as well as at GRN — and
refused in state 0 or 3.

## 5. How a sale becomes an order

The tile grid keeps showing the shop's own stock and nothing else. The **search box and the
barcode scanner** additionally find the warehouse's items and badge them *Warehouse —
delivered*. Adding one, or asking for more than the shop holds, splits the cart line:

```
Bedsheet       2   from shop
Bed            1   from warehouse
Headboard      1   custom-made        [specs…]
```

When the cart holds any warehouse or custom line, POS asks — before payment — for the customer
(pick or add, as today), the destination (the customer's address, prefilled, or *collect at the
shop*), a needed-by date and per-line specs. Payment then runs exactly as it does now: full,
part payment or credit customer.

Saving writes, in one transaction: the invoice as today, **no stock movement for warehouse or
custom lines**, and the order with its lines. Two supporting rules:

- A warehouse line bills against the **shop's own copy** of the product, created at checkout by
  `Transfer::getDestinationProductID` — the helper transfers already use — so the invoice, the
  shop's catalogue and any later transfer all line up. The order line keeps both ids.
- A **custom-made** line bills against one service-type product per shop, *Custom-made item*
  (`ItemType = 'S'`), created on first use. Service items already skip stock in POS, so no new
  billing path is invented. The typed name, price and specs live on the invoice line and on the
  order line.

Three code paths in `Controller/guiPosController.php` carry the change: the cash sale
(`?cash=1`, line 22), the full invoice (`?btn_submit_invoice=1`, line 707) and hold
(`?invoiceHold=1`, line 550), which must keep the flags so a held bill still becomes an order
when it is recalled. In the first two, lines flagged warehouse skip **both** the stock check
and the inventory consumption loop.

## 6. Status

Per line: **Given at shop · Pending · Ready · Dispatched · Delivered · Cancelled**.

The order's status is stored so the queue can filter and count on it, and is recomputed from
its lines by `refreshStatus()` at the end of every action, inside that action's transaction:

| Order shows | When |
|---|---|
| **Pending** | warehouse lines are waiting and the warehouse has not started |
| **Preparing** | the warehouse pressed *Start preparing* and not every remaining line is ready |
| **Ready** | every line with an open remainder is Ready |
| **Dispatched** | nothing is left to pick and at least one line is out |
| **Completed** | nothing is left to pick or deliver, and the customer got something |
| **Cancelled** | the order was cancelled outright, or every warehouse line was cancelled and nothing was given at the shop |

The last two rows overlap on purpose and are read in that order: an order whose bed was
cancelled but whose bedsheet was handed over is **Completed**, not Cancelled — the customer was
served, and the refund for the bed is a Sales Return. Payment never moves the status.

## 7. Screens and actions

| Screen | |
|---|---|
| `Public/customer-orders.php` | the shop's own orders: order no, invoice no, customer, needed by, *2 of 3 pending*, status |
| `Public/warehouse-orders.php` | the warehouse queue, Pending first, with a status filter and a sidebar badge counting Pending + Preparing |
| `Public/customer-order.php` | the job sheet, shared by both sides, showing the buttons for the side the user is signed in to |
| `View/modals/dispatch-scan.php` | the dispatch screen |

The job sheet shows the customer, the invoice and the money (balance in red), the delivery
block, and the lines — the shop-given ones greyed out and labelled **Given at shop — do not
send**, custom lines showing their specs, each line with its own status and dispatched count —
then the linked dispatches.

| Action | Who | Allowed when |
|---|---|---|
| **Create** | POS, any user who may bill | automatic, when a sale has a warehouse or custom line |
| **Start preparing** | warehouse, verify | Pending |
| **Mark ready** (per line or all) | warehouse, verify | Pending or Preparing |
| **Cannot supply** (reason required) | warehouse, edit | the line is not dispatched |
| **New dispatch** | warehouse, verify | at least one line is Ready. It opens holding every Ready remainder; the dispatcher may drop a line or lower its quantity before scanning |
| **Scan** | warehouse, verify | the dispatch is open |
| **Complete dispatch** | warehouse, verify | every line in this dispatch is fully scanned; an outstanding balance is confirmed |
| **Cancel dispatch** | warehouse, verify | it is open (nothing has left, no stock has moved) |
| **Delivered** | warehouse, verify, or the shop for a collection | the dispatch is sent |
| **Cancel order** | shop, edit | nothing dispatched |

Rights are checked with `ShopAccess::hasFeatureRight` on the existing **Customer Orders**
feature, in the shop the user is signed in to; super admins have every right. A shop sees an
order only as its owner or its supplier. Refusals keep the module's codes: 403 rights, 404 not
this shop's order, 409 the order moved on, 422 input.

## 8. Dispatch scanning

A third document type beside GRN and transfer, built on the existing `ScanDocument` framework
and added to `Controller/ScanUploadController.php` as context `dispatch`. The screen lists
every line in the dispatch with a live scanned tally and refuses, in the scanner's existing
red-line style:

| What was scanned | |
|---|---|
| a code that matches nothing | *Not a product we know* |
| a product that is not on this order | *Not on this order* |
| a product only on a given-at-shop line | *Given at the shop — not to be sent* |
| the same code again in this dispatch | *Already scanned* |
| a sticker already dispatched | *Already dispatched on DS_000004* |
| a sticker in another open dispatch | *Being dispatched on DS_000007* |
| a voided sticker | *Voided sticker* |
| more than the line needs | *Only 1 more needed* |

A unit sticker records which exact unit went to which customer. A product barcode counts one
item. Custom-made lines have no barcode and are ticked by hand with a note.

**Complete dispatch** unlocks only when every line in the dispatch is fully scanned, and does
all of this in one transaction: warehouse batches are consumed oldest first through the
existing `StockAllocator` (skipping stock already held by open transfers), `inventory` is
decremented and `BillQty` raised, `inventory_consumption` rows are written against the shop's
invoice, scanned units move to state 3, the lines become Dispatched and the order's status is
recomputed. If the stock has gone since the items were scanned, the whole dispatch is refused
with what is short — nothing partial is written.

## 9. Money and stock

The shop's invoice is the sale and is unchanged. The goods leave the **warehouse's** stock at
dispatch, so `inventory_consumption` rows carry the warehouse's `shop_SHID` with the shop's
`invoice_headerID`. Every report that reads `inventory_consumption` is checked against this
before the work ships, and the finding recorded here.

A cancelled line returns nothing by itself; the shop refunds through Sales Return.

## 10. Testing

PHPUnit against the throwaway `sleepmakers_test`:

- `tests/WarehouseOrderTest.php` — creating an order from a POS payload, line splitting
  (shop-only, warehouse-only, part of a line, custom), the shop's product copy, the service
  product, status recomputation for every case, cannot-supply, cancel rules, rights and other
  shops' orders.
- `tests/DispatchScanTest.php` — every refusal in section 8, partial dispatch, over-scan,
  complete dispatch consuming the right batches, units moving to state 3, a second dispatch of
  the rest, cancelling an open dispatch.
- `tests/WarehouseFulfilmentMigrationTest.php` — idempotent, the status remap, the new columns
  and keys.

`tests/e2e/warehouse_fulfilment_e2e.php` drives the whole story over HTTP across the e2e
Warehouse and Showroom: sell a bedsheet from stock plus a bed and a custom headboard, pay part
of it, watch the order appear as Pending, prepare it, scan the bed (with a unit sticker) and
tick the headboard, confirm the balance, dispatch, mark delivered, see the order Completed and
the warehouse's stock down by one bed — plus the refusals.

Before deploying: the five existing e2e suites, and the admin and owner page crawls compared
against the current code on a server copy of the live database.

## 11. Delivery

Five phases, each committed and pushed to `main` on its own:

1. Storage and model — migration, installer, `.sql`, `Model/warehouse_order_class.php`, tests.
2. POS — warehouse items in search and scan, the cart split, the order step, the three checkout
   paths, tests.
3. The queue and the job sheet — `warehouse-orders.php`, the shared order page, prepare / ready
   / cannot supply / cancel, tests.
4. Dispatch — `Model/scan_dispatch_class.php`, the scan screen, stock consumption, unit
   marking, delivered, tests.
5. The module guide, the e2e suite and the regression run.

Then deploy with the recipe this server has proved: back up the database, run
`php db/warehouse_fulfilment_install.php`, rehearse on a copy of the live database with a copy
of the code, run the suites and the crawl there, ship only the changed files, and check the
live site signed in afterwards.

`.gitignore` holds `db/*` with one `!` line per shipped file, so the four new `db/` files are
silently left out of the commit until their `!` lines are added. This has caught out both
previous modules.

## 12. Risks to settle while building

- `inventory_consumption.shop_SHID` will hold the warehouse while the invoice belongs to the
  shop. `Model/Report_class.php` is read before phase 4 ships and anything that assumes the two
  match is fixed or reported.
- Three checkout paths, one of them hold-and-recall: the flags must survive a held bill.
- Creating the shop's product copy inside the sale transaction also creates a category and
  subcategory copy when they are missing. Acceptable, but it happens at checkout only — never
  while the cashier is browsing.
- Live product PDID 1 carries a whole factory unit code in its `Barcode` field
  (`109D25090013`). Unrelated to this work, still open with the customer, and it makes that
  product's unit stickers 20 characters long.
