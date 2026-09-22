# Customer Orders - showroom to warehouse

A customer buys a **bedsheet** that is on the showroom's shelf and a **bed** that only the
warehouse has. The showroom gives the bedsheet (and invoices it in POS as usual) and places a
**customer order**: the bedsheet is recorded as *already given* - so the warehouse never sends
it - and the bed is requested from the warehouse. The warehouse fulfils the order with a
transfer created from it; when the bed arrives the showroom invoices it in POS and marks the
order **Handed over**.

Design: `docs/superpowers/specs/2026-09-22-customer-orders-design.md`.

---

## 1. Install

Run once on the server, **before** uploading the code:

```
php db/customer_orders_install.php
```

It creates `customerorders` and `customerorderlines`, adds `transferheader.CustomerOrderID`
and the role right **Customer Orders** (module *Orders*, id 101 or above). Idempotent - a second
run reports `[skip]`. `db/customer_orders.sql` is the same change for a manual install.

Then grant the right - **Settings → User Roles → edit the role → Orders → Customer Orders**,
with the *Orders* module ticked:

| Role | Rights |
|---|---|
| Showroom staff who take orders | View, Create, Edit |
| Warehouse staff who fulfil them | View, Verify |
| Anyone who only needs to look | View |

Super admins have every right.

## 2. The showroom

*Sales & Orders → Customer Orders → New Order*:

1. **Order from** - the shop that will supply it (the other active shops of the company).
2. The customer's name and phone (required), address, *needed by*, advance paid, notes.
3. **Already given from our stock** - what the customer already took (a product of this shop or
   a description, the quantity, the POS invoice number). The supplier sees these lines marked
   *Already given - do not send*; they never go on a transfer.
4. **From the warehouse** - products from the supplier's catalog (the search shows its stock),
   with a note for the customer's requirements (size, colour, firmness...), and **custom-made
   items** described in words.
5. **Send to supplier**. The order gets its number (`CO_000001`) and is *Requested*; it can be
   edited until the supplier acts, and cancelled until anything is on its way.

When everything has arrived the order shows **Arrived**: give the items to the customer,
invoice them in POS, and press **Handed over** (with the invoice number).

## 3. The warehouse

*Customer Orders → Incoming* (the menu badge counts the orders waiting for an answer):

- **Accept**, or **Reject** with a reason the showroom sees.
- **Create transfer** - a transfer on hold to the showroom, with what the order still needs of
  its catalog lines, taken from the oldest stock (not counting stock already on other open
  transfers). What the stock cannot cover is reported and can go on a later transfer. Then
  check it on the Transfer page (the scanner upload works there) and **Verify** it as usual.
- **Mark sent** - for custom-made items, with the quantity and a note (vehicle, date...).

## 4. Statuses

| Status | Meaning |
|---|---|
| Requested | placed, waiting for the supplier |
| Accepted | the supplier is preparing it |
| In transit | something is on a transfer (on hold, pending or verified) or marked sent |
| Arrived | every requested line has arrived (verified transfers, custom items marked sent) |
| Handed over | the showroom gave everything to the customer |
| Rejected / Cancelled | closed by the supplier / the showroom |

A cancelled transfer no longer counts. Billing always happens in POS; the order only notes the
advance paid and the hand-over invoice number.

## 5. Deploying

1. Back up the database.
2. `php db/customer_orders_install.php` on the server.
3. Upload the code (not `tests/`, `tools/` or `docs/`).
4. Grant **Customer Orders** to the roles that need it (section 1).

## 6. Tests

```
C:/xampp/php/php.exe tools/phpunit.phar                         # CustomerOrdersTest, migration, allocator
C:/xampp/php/php.exe tests/e2e/customer_orders_e2e.php          # the whole flow over HTTP, with the real transfer Verify
node tests/ui/customer_orders_ui.mjs [screenshot-folder]         # headless Chrome: the form and the warehouse actions
```
