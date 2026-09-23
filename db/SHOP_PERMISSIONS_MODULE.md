# Per-Shop Permissions Module

Each role carries its own ticks in each shop. Builds on the shop access module
(`db/SHOP_ACCESS_MODULE.md`), which decided **who may enter a shop**; this one decides
**what they may do once they are in it**.

---

## 1. Install

Run **once, before** uploading the module's code:

```
php db/shop_permissions_install.php
```

Command line only - on the live server `db/.htaccess` keeps `db/` off the web.

It is **additive only** and **idempotent**: nothing existing is dropped, renamed or re-typed,
and running it twice is harmless. `db/shop_permissions.sql` adds the same columns as plain
SQL, but only the installer can also move the existing ticks, so prefer the installer.

### What it changes

| Object | Purpose |
|---|---|
| `userroleaccess.shop_SHID` | the shop these feature ticks apply in |
| `usermoduleaccess.shop_SHID` | the shop this menu module is shown in |
| unique key `uq_roleaccess_role_shop_feature` | one row per role, shop and feature (older duplicates are merged into the oldest, which is the row the pages read today) |
| unique key `uq_moduleaccess_role_shop_module` | one row per role, shop and menu module |

**Nobody's rights change on install.** Every row a role had is copied into every shop, and the
shared rows are then removed. From that moment each shop is ticked on its own.

---

## 2. How it works

- A role name (*Cashier*, *Manager*, …) is company-wide. What it **may do** belongs to that
  role **in one shop**.
- Each person is given a role per shop already (*Settings → Assign Users to Shops*). Their
  rights are the ticks of that role **in the shop they signed into**.
- So one person, one role, two shops can mean two different menus - and giving someone rights
  in the showroom gives them nothing in the warehouse.
- A role with nothing ticked in a shop still lets its users **in**; they just see no menu.
  To keep someone out of a shop entirely, do not assign them there (or revoke it).
- A super admin (`UserType = 1`) is not limited by roles and enters every shop.

### Where it is decided

| Place | What it answers |
|---|---|
| `ShopAccess::hasFeatureRight($user, $shop, $feature, $rights)` | the server-side gate used by the newer modules (scanner upload, customer orders) |
| `User::userAcces()`, `getUserRoleFeatureAccess()`, `getRoleViewAccess()`, `getUserRoleModuleAccess()` | what the pages and the sidebar show. They take the shop, and default to the one open in the session |
| `User::getUserFeatureAccess($user, $feature, $shop)` | rights of a user in one named shop, through their role there |
| `bcUserRight()` (`Includes/barcode_helper.php`) | the barcode and label endpoints |

A role that has never been ticked in this shop has no row at all. The lookups then answer with
**every right off** (`User::NO_RIGHTS`) rather than nothing, because the pages read
`$rights[0]['is_view']` without looking first.

---

## 3. Managing it - Settings → User Roles

The page always works in the shop you are signed into, and says so:

| Screen | What it does |
|---|---|
| **User Roles** | lists every role with the features and menus it has **here**; a role that is not set up here says *Nothing ticked here* |
| **Add New User Role** | creates the role (the name is company-wide) and ticks it **for this shop** |
| **Edit** | changes this shop's ticks. The role name and the Active/Inactive switch are company-wide, so those do change everywhere |

To set the same role up in another shop, use *Switch Shop*, sign in there and open the page
again. Nothing is copied between shops after the install - that is the point.

Example, the way Sleep Makers uses it: *Test* is created in Valentino Italy, so he appears in
the Users list everywhere but is only assigned to Valentino Italy. Tick his role there and he
can sell; he never sees the Warehouse. To let him receive stock later, sign in to the
Warehouse, add him under *Assign Users to Shops* and tick what he needs **there**.

---

## 4. Deploying

1. Back up the database.
2. `php db/shop_permissions_install.php` on the server.
3. Upload the code.

Between steps 2 and 3 old code would write ticks with `shop_SHID = 0`, which nothing reads -
a window of seconds. Reading keeps working throughout.

---

## 5. Tests

```
sh tools/get-phpunit.sh                      # once
C:/xampp/php/php.exe tools/phpunit.phar      # unit + integration (database sleepmakers_test)
C:/xampp/php/php.exe tests/e2e/shop_permissions_e2e.php [base-url]   # against a running site
```

`ShopPermissionsMigrationTest` covers the install (copying, duplicates, no shops yet, a second
run), `RoleEditorTest` the editor writing one shop only, `PermissionLookupTest` and
`ShopAccessTest` the lookups. The end-to-end script retickets a role in one shop and proves the
other shop's menus, pages and ticks are untouched.
