# Per-Shop Permissions — Design

- **Date:** 2026-09-23
- **Branch:** `main`
- **Status:** Approved by the customer (both options confirmed before implementation)

## 1. Problem

The shop login and per-shop access work (`db/SHOP_ACCESS_MODULE.md`): staff are assigned to
each shop separately, a new user joins only the shop they were created in, and the shop screen
offers a person only the shops they hold.

What does **not** work is the second half. The ticks behind a role live in `userroleaccess` and
`usermoduleaccess` keyed by the role alone, so a role means the same thing everywhere. Ticking
*Goods Received → Edit* for Valentino Italy ticks it for the Warehouse as well, and a person
made a Cashier in both shops is the same Cashier in both. The customer's words: *"giving the
user access in the shop should not automatically give them the same access in the warehouse."*

## 2. Goals

- The same role can mean different rights in each shop.
- An admin signs in to a shop and manages that shop's rights there; the other shops are not
  touched.
- Someone given rights only in the showroom has none in the warehouse and never sees it.
- Users stay company-wide: a user created in one shop is visible in the other.

### Non-goals

- Per-user rights that bypass roles. The customer already makes one-person roles (there is a
  role named *Ramindu*), so a role remains the unit that carries rights.
- Copying a role's set-up from one shop to another. If they ask for it later it is one button
  on the editor, on top of this design.

## 3. Options considered

| Option | What it means | Verdict |
|---|---|---|
| **A. Ticks per role per shop** | `userroleaccess`/`usermoduleaccess` gain `shop_SHID`; role names stay company-wide | **Chosen.** Smallest change: no role ids move, no assignment is re-pointed, every lookup gains one condition |
| B. Roles per shop | `userroles` gains `shop_SHID`; every role is cloned per shop at upgrade and each assignment re-pointed at its clone | Cleaner on screen, but rewrites live assignment data for no extra ability |
| C. Rights per user per shop | roles become templates; rights stored per (user, shop) | Throws away the role screens and the architecture the app is built on |

The customer was shown A and B and chose A.

## 4. Design

### Storage

```
userroleaccess   (UserRolls_URID, shop_SHID, SysFeatures_SFID) unique  + the six is_* ticks
usermoduleaccess (UserRoles_URID, shop_SHID, SysModules_SMID)  unique
```

A role with no row for a shop has no rights there.

### Reading

Every lookup already knew the shop, or can: `authcheck.php` puts the shop the person signed
into in the session and refuses the page otherwise, and the sidebar resolves the role from
(user, shop). So:

- `ShopAccess::hasFeatureRight($user, $shop, $feature, $rights)` — adds `shop_SHID = $shop`.
- `User::userAcces()`, `getUserRoleFeatureAccess()`, `getRoleViewAccess()`,
  `getUserRoleModuleAccess()` — take the shop, defaulting to the session's. This keeps the
  ~100 call sites in `View/sidebar.php`, `View/right-sidebar.php` and the pages unchanged.
- `User::getUserFeatureAccess($user, $feature, $shop)` — joins the ticks on the same shop as
  the assignment.
- `bcUserRight()` — adds the shop it already reads from the session.

Because a role can now have no row at all for a shop, the two lookups whose callers read
`$rights[0]['is_view']` without checking answer with `User::NO_RIGHTS` (every right off)
instead of an empty array. That is also a small bug fix: those pages used to raise a PHP
warning whenever a role had no row for a feature.

### Writing

`Controller/userrolecontrol.php` clears and rewrites only `$_SESSION['shop_id']`'s rows, and
`UserRole::add_userrole()` / `add_role_module()` upsert on the new unique keys.

### Screens

*Settings → User Roles* is where a mistake would be made, so every screen names the shop:
the list heads its columns *Features in &lt;shop&gt;* / *Menus in &lt;shop&gt;* and says
*Nothing ticked here* for a role that is not set up; Add and Edit Role say the ticks apply to
this shop only. *Assign Users to Shops* starts a new assignment in the shop the admin is in,
and the Add User form says the new user joins that shop alone.

## 5. Upgrade

`db/shop_permissions_install.php`, additive and idempotent: add the columns, merge any
duplicate rows into the oldest (the one the pages read today), copy every role's ticks into
every shop, delete the shared rows, add the unique keys.

The customer chose **"keep today's ticks in both"**: nobody's rights change on the day of the
upgrade, and the two shops drift apart only as the ticks are edited. The alternative offered
was to blank the Warehouse, which would have left non-admin staff there with an empty menu
until it was set up again.

## 6. Testing

- `ShopPermissionsMigrationTest` — copying, duplicates, no shops yet, a second run, the
  unique key.
- `RoleEditorTest` — the editor writes and clears one shop only; saving twice keeps one row.
- `PermissionLookupTest`, `ShopAccessTest` — one person, one role, two shops, two answers.
- `tests/e2e/shop_permissions_e2e.php` — the admin retickets a role in the e2e Warehouse over
  HTTP; the e2e Showroom's ticks, menus and pages are unchanged for the person holding that
  same role there.
