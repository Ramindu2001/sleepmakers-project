# POS to Warehouse Fulfilment

A customer wants a bedsheet, a bed and a headboard made to order. The bedsheet is on the shelf,
the bed is only in the warehouse, and the headboard does not exist yet. The cashier bills all
three on **one invoice**, takes the money, and hands over the bedsheet. The bed and the
headboard become the warehouse's job: it prepares them, scans them out, and delivers them to
the customer.

The system tracks **every line separately**, so an item carried out of the shop can never be
picked in the warehouse again.

Design: `docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md`.

---

## 1. Install

Run once on the server, **before** uploading the code:

```
php db/warehouse_fulfilment_install.php
```

It adds the invoice, customer and delivery columns to `customerorders`, the per-line state to
`customerorderlines`, the dispatch columns to `productunits`, and creates `orderdispatches` and
`orderdispatchlines`. Idempotent - a second run reports `[skip]`. `db/warehouse_fulfilment.sql`
is the same change for a manual install.

Then grant the right - **Settings → User Roles → edit the role → Orders → Customer Orders**,
with the *Orders* module ticked:

| Role | Rights |
|---|---|
| Shop cashiers | View (the till creates orders by itself; no extra right is needed to sell) |
| Warehouse staff who prepare and send | View, Edit, **Verify** |
| Anyone who only needs to look | View |

Super admins have every right.

## 2. At the counter

Search for an item the shop does not have and the **warehouse's own catalogue** answers,
badged *Delivered from …*. An item with no stock is still offered: the warehouse makes it and
the customer waits. For something nobody stocks, press **Custom-made item** and type what it is
and what it costs.

The bill then shows what is being handed over now and what the warehouse owes:

```
e2e Bedsheet    2   from the shelf
Cooler Bed      1   From Warehouse     [Specs]
Headboard       1   Custom-made        [Specs]
```

Press **Specs** beside a line to tell the warehouse what the customer asked for - size, colour,
firmness. Before payment the till asks who it is being delivered to: the customer's name and
phone, the address (or *the customer collects at the shop*), and when they want it.

Then take the payment exactly as usual - in full, part of it, or on credit. Saving the sale
creates the order.

Two things worth knowing:

- **A sale that cannot leave a proper order is refused outright.** The money is never taken for
  goods nobody has been told to send.
- **A bill with warehouse items cannot be held.** The order is part of the sale, so finish it,
  or take those items off the bill.

## 3. In the warehouse

*Sales & Orders → Warehouse Orders* - the queue, pending first, with a badge counting what
nobody has picked up yet. Open one and you get the job sheet: the customer, the invoice and
what they still owe, where it goes, and every line with its own state. Lines the shop already
handed over are greyed out and marked **Given at shop — do not send**.

| Button | What it does |
|---|---|
| **Start preparing** | tells the shop somebody is on it |
| **Ready** (per line) / **Everything is ready** | picked and packed |
| **Cannot supply** | takes a line off the order with a reason the shop sees |
| **Start a dispatch** | opens one trip for what is ready |
| **Scan items** | the same scanner dialog as GRNs and transfers |
| **Send it** | the goods leave: stock comes off, the sale is costed |
| **… reached the customer** | the order is finished |

Part of an order can go now and the rest later. Open the dispatch for what fits in the van; the
remainder stays on the order.

## 4. Scanning before dispatch

Nothing leaves unscanned. A **unit sticker** is best - it names one physical item, so it can be
refused by name if it has already gone somewhere else. A plain **product barcode** counts one
against its line. Custom-made items have no barcode and are ticked off by hand.

| What was scanned | What the screen says |
|---|---|
| a code nobody printed or sells | *Not a product we know* |
| something for a different customer | *Not on this order* |
| something the customer already took | *Given at the shop — not to be sent* |
| the same sticker twice | counted once, *1 code scanned twice* |
| a sticker that already went out | *Already dispatched on DS_000004* |
| a sticker in somebody else's open trip | *Being dispatched on DS_000007* |
| a voided sticker | *Voided sticker* |
| more than the line needs | *Only 1 more needed* |

**Send it** only works once every item on the trip has been scanned. If the customer still owes
money the screen says so and somebody has to agree out loud - delivering on balance payment is
normal, but nobody does it by accident. If the stock has gone since it was scanned, or a
sticker has just left on another dispatch, **nothing is sent** and the picker is told why.

## 5. Statuses

| Order | Meaning |
|---|---|
| Pending | waiting for the warehouse to pick it up |
| Preparing | somebody is on it |
| Ready | everything left to send is picked and packed |
| Dispatched | it is out on the road |
| Completed | the customer has everything |
| Cancelled | called off; refund through Sales Return |

| Line | Meaning |
|---|---|
| Given at shop | handed over the counter; never pickable |
| Pending / Ready | the warehouse still owes it |
| Dispatched / Delivered | on its way / arrived |
| Cannot supply | the warehouse could not fill it, with a reason |

**Paying never completes an order.** An order is finished when the customer has the goods.

## 6. Where the stock and the money go

The sale and the money belong to the **shop**. The goods come off the **warehouse's** stock at
the moment the dispatch is sent, oldest batch first, and the cost is recorded against the
shop's invoice. There is no transfer paperwork: one movement, and both stock figures stay true.

A cancelled line returns nothing by itself. The shop refunds it through **Sales Return**.

## 7. Deploying

1. Back up the database.
2. `php db/warehouse_fulfilment_install.php` on the server.
3. Upload the code (not `tests/`, `tools/` or `docs/`).
4. Grant **Customer Orders** to the roles that need it (section 1).
5. The order and scanner scripts are versioned (`?v=20260924`); clear the nginx cache if pages
   still show the old buttons.

## 8. Tests

```
C:/xampp/php/php.exe tools/phpunit.phar                                  # the model, the scanner, the migration
C:/xampp/php/php.exe tests/e2e/warehouse_fulfilment_e2e.php              # the whole story over HTTP, both shops
```

## 9. Found while building

- **No report reads `inventory_consumption`.** The only place outside the till that touches it
  is `Controller/cancelwholesaleinovice.php`, reachable only from the wholesale invoice view -
  and its query is already broken (`$sql2` assigned, `$sql_2` used), so its loop never runs.
  Carrying the warehouse's shop id on those rows therefore skews nothing. Left as found; it is
  older than this work.
- **`View/sidebar.php` sets `$view = "is_view"`.** Any page that keeps its own `$view` loses it
  the moment the sidebar is included. The job sheet uses `$orderView`.
- A dispatch has to remember what it was **meant** to carry, or a van that holds two of three
  beds could never be sent. That is `orderdispatchlines.PlannedQty`: one plan row per line when
  the dispatch opens, and the scans are the rows without it.
- The scanner dialog (`View/modals/scan-upload.php`, `Assets/jquery/scan_upload.js`) was already
  driven by `data-context` and `data-doc-id`, so dispatch scanning reuses it. Staff meet the
  dialog they already know from GRNs and transfers.
- The hand-typed Customer Orders module this replaces was removed with its controller, script,
  view helpers, model and tests. Its tables, numbering and role right carry on unchanged.
