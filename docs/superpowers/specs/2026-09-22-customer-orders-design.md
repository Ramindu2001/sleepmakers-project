# Customer Orders to the Warehouse — Design

- **Date:** 2026-09-22
- **Branch:** `development`
- **Status:** Approved in design review (sub-project 3 of the customer's four requirements)

## 1. Problem

A showroom (Valentino Italy) sells what it has in stock through POS. When a customer buys
several items and some are not in the showroom - e.g. a **bedsheet** that is on the shelf and a
**bed** that only the warehouse has - there is no way to ask the warehouse for the bed. And the
request must say that the bedsheet was **already given** to the customer, or the warehouse
sends it again.

The app has no request flow. A small "Sales Order" page exists but is unused, and POS cannot
invoice an item the shop has no stock of.

## 2. Goals

- A showroom records a **customer order**: the customer, the lines already given from its own
  stock, and the lines the warehouse must supply (catalog products with notes - size, colour,
  firmness - or free-text custom-made items).
- The warehouse sees incoming orders, accepts or rejects them, and fulfils them with a
  **transfer created from the order**; custom-made lines are marked sent by hand.
- Both sides see progress per line (*ordered / on transfer / arrived*) and the order's status,
  up to **Handed over**.
- Lines already given are shown to the warehouse as *already given - do not send* and never
  go on a transfer.

### Non-goals

- Billing: the customer is invoiced in POS when they take each item (the bedsheet today, the
  bed when it arrives). POS is unchanged; the order only notes the advance paid and the
  hand-over invoice number.
- Direct delivery from the warehouse to the customer (items travel through the showroom).
- Stock-refill requests without a customer, printed order slips.

## 3. Decisions made in review

| Question | Decision |
|---|---|
| Billing | Invoice on hand-over, in POS as today. |
| Delivery | Through the showroom, by a transfer created from the order. |
| Custom requirements | Catalog products with a note, plus free-text lines for custom-made items. |
| Request types | Customer orders only. |

## 4. Data

`customerorders`

| Column | |
|---|---|
| `COID` | PK |
| `OrderNo` | `CO_000001`, per shop; unique with `shop_SHID` |
| `shop_SHID` | the showroom that took the order |
| `SupplierShopID` | the shop asked to supply it (another active shop of the same company) |
| `CustName`, `CustPhone` | required |
| `CustAddress`, `Notes` | optional |
| `NeededBy` | optional date |
| `AdvancePaid` | decimal, default 0 (information only) |
| `OrderStat` | 1 Requested, 2 Accepted, 3 Rejected, 4 Cancelled, 5 Handed over |
| `RejectReason`, `HandoverInvoiceNo` | optional |
| `user_USID`, `CreatedAt` | who placed it |
| `DecidedBy`, `DecidedAt` | who accepted / rejected it |
| `ClosedBy`, `ClosedAt` | who cancelled it or handed it over |

`customerorderlines`

| Column | |
|---|---|
| `COLID` | PK |
| `customerorders_COID` | the order |
| `LineSource` | `GIVEN` (from the showroom's stock) or `WAREHOUSE` |
| `products_PDID` | GIVEN: the showroom's product (optional); WAREHOUSE: the supplier's product, NULL for a custom-made line |
| `Description` | the product name when saved, or the custom item |
| `Qty` | > 0 |
| `Notes` | the customer's requirements (size, colour...) |
| `InvoiceNo` | GIVEN: the POS invoice it was sold on (optional) |
| `CustomSent`, `CustomNote` | custom-made lines: quantity the warehouse marked sent, and its note |
| `SortOrder` | display order |

`transferheader.CustomerOrderID` (nullable, indexed): the order a transfer was created from.

The role right **Customer Orders** is a new `sysfeatures` row in module 2 (*Orders*), inserted
with an id of at least 101 (the sidebar already refers to a missing feature 74) and always
looked up **by name**.

Installed by `db/customer_orders_install.php` (`CustomerOrdersMigration`, idempotent; also
`db/customer_orders.sql`) **before** the code is uploaded.

## 5. Progress and status

For each WAREHOUSE catalog line, from the transfers created from the order that are not
cancelled (`TransferStat <> 3`), per product, filled into the order's lines in order:

- **On transfer** = transfer quantity on those transfers (hold, pending or verified);
- **Arrived** = received quantity on the **verified** ones.

A custom-made line counts as arrived when its `CustomSent` reaches its quantity.

Status shown:

| Stored | Shown |
|---|---|
| 1 | **Requested** |
| 2 | **Arrived** when every WAREHOUSE line has arrived; else **In transit** when anything is on a transfer or marked sent; else **Accepted** |
| 3 / 4 / 5 | **Rejected** / **Cancelled** / **Handed over** |

## 6. Actions and rules

Rights are checked with `ShopAccess::hasFeatureRight` on the **Customer Orders** feature, in the
shop the user is signed in to (super admin: all rights).

| Action | Who (shop, right) | Allowed when |
|---|---|---|
| **Create** | the showroom, create | at least one WAREHOUSE line; the supplier is another active shop of the company; each catalog line's product is the supplier's own, active, `ItemType = 'P'`; quantities > 0; name and phone given |
| **Edit** | the showroom, create or edit | Requested (the warehouse has not acted) - replaces the lines |
| **Cancel** | the showroom, create or edit | Requested or Accepted, nothing on a transfer and nothing marked sent |
| **Accept** | the supplier, verify | Requested |
| **Reject** (reason required) | the supplier, verify | Requested or Accepted, nothing on a transfer and nothing marked sent |
| **Create transfer** | the supplier, verify | Requested or Accepted (accepts it). For each catalog line, what is not yet on a transfer is taken from the supplier's batches oldest first (the scanner upload's allocation). What the stock cannot cover is left off and reported; nothing at all in stock is refused. Creates a transfer on hold from the supplier to the showroom (`GT_` number as the Transfer page does), linked to the order. |
| **Mark custom item sent** | the supplier, verify | Requested or Accepted; up to the line's quantity; accepts the order |
| **Handed over** (invoice no. optional) | the showroom, create or edit | shown status Arrived |

Every change is one transaction with the order row locked. A shop sees an order only if it is
the showroom (*Our orders*) or the supplier (*Incoming*). Refusals: 403 (rights), 404 (not this
shop's order), 409 (the order has moved on), 422 (input).

The transfer created from an order then follows the normal transfer flow (scanner upload,
edit, Verify) with the usual *Transfer Note* rights.

## 7. Screens

- **Sidebar**, *Sales & Orders*: **Customer Orders**, with a badge counting Requested orders
  for the shop the user is in (users with the right; super admin).
- `Public/customer-orders.php`: tabs *Our orders* and *Incoming* (a tab shows when the user
  has orders there or can create them), status filter, table: order no., customer, phone,
  needed by, status badge, lines summary, created; **New Order**.
- `Public/customer-order.php`:
  - **new / edit** (`?new=1`, `?id=` while Requested): supplier shop, customer name / phone /
    address, needed by, advance, notes; *Already given from our stock* rows (product from this
    shop or text, qty, invoice no.); *From the warehouse* rows (product search over the
    supplier's catalog showing its stock, qty, note) and *Add custom-made item* rows (text,
    qty, note).
  - **view** (`?id=`): status badge and history (placed / accepted / rejected / closed, by whom
    and when), both line tables - the given lines marked *Already given - do not send* - with
    Ordered / On transfer / Arrived, the linked transfers (number, status, link) and the
    buttons the user may use.
- `Controller/CustomerOrderController.php`: JSON `{ok, message, ...}`, CSRF, the actions above
  plus the product search.
- `Assets/jquery/customer_order.js`: the form (select2 product search, rows) and the buttons.

## 8. Testing

- PHPUnit (`sleepmakers_test`): create (validation, numbering), edit, cancel / reject rules,
  accept, create transfer (oldest batches, only WAREHOUSE catalog lines, remaining quantities,
  shortfall report, link, number), custom sent, progress and status from transfers (hold,
  verified, cancelled), handed over only when arrived, rights and other shops' orders,
  migration idempotent.
- E2E over HTTP across the e2e Warehouse and Showroom: order with a given bedsheet and a
  requested bed → warehouse accepts → creates transfer (bedsheet not on it) → verify →
  arrived → handed over; refusals.
- Headless Chrome: the new order form (product search, rows, save) and the order view.
- Regression crawl and database restore.

## 9. Delivery

One commit and push per step on `development`. Deploy: back up, run
`php db/customer_orders_install.php`, upload the code, grant *Customer Orders* in the role
editor to the roles that need it.
