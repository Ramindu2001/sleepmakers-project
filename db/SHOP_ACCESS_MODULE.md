# Shop Access Module

Shop login and per-shop access control for Cloud POS.
Design: `docs/superpowers/specs/2026-09-22-shop-login-and-per-shop-access-design.md`.

---

## 1. Install

Run **once, before** uploading the module's code:

```
php db/shop_access_install.php
```

or, signed in as an administrator (`UserType = 1`), open `/db/shop_access_install.php`
(only where `db/` is reachable from the web - on the live server `db/.htaccess` blocks it,
so use the shell there).

It is **additive only** and **idempotent**: nothing existing is dropped, renamed or re-typed,
and running it twice is harmless. `db/shop_access_module.sql` is the same change as plain SQL.

### What it changes

| Object | Purpose |
|---|---|
| `shopusers.UserRoles_URID` | the user's role **in that shop**. Filled from `user.UserRoles_URID`, so nobody's rights change on install |
| `shopusers.is_active` | `1` = may enter the shop, `0` = access revoked (the row and the history stay) |
| unique key `uq_shopusers_shop_user` | one assignment per user per shop (duplicates are merged into the oldest first) |
| index `idx_shopusers_role` | role lookups |

Assignments whose user no longer exists get role `0` and are revoked; the installer lists them.

---

## 2. How access works

- **Entering a shop always takes a username and password.** The shop screen opens a
  *Sign in to <shop>* dialog. Anyone with access to that shop can sign in there with their
  normal username and password - the session then belongs to them (a shared counter can be
  handed over through *Switch Shop*).
- There are always two sign ins: the main sign in, then the shop's. Every user lands on the
  shop screen after the main sign in, even with a single shop, and new shops appear there as
  soon as they are assigned.
- *Remember me* keeps only the main sign in. A remembered browser opens on the shop screen and
  the shop still asks for the password; a shop is never remembered.
- **Who may enter a shop:** a super admin (`UserType = 1`) - every shop. Everyone else - an
  active assignment, to an active shop, with an active role, while their account is active.
  Expired or inactive companies stay closed to non-admins, as before.
- **What they may do there:** the feature and module rights of the role they hold **in that
  shop**. The role screens (*Settings → User Roles*) are unchanged.
- Revoking access, deactivating the role or changing it takes effect on the user's next click,
  and on idle screens within about 10 seconds.
- `user.UserRoles_URID` is shown as **Default Role**: the role offered when the user is added
  to a shop.

## 3. Managing access - Settings → Assign Users to Shops

| Action | Effect |
|---|---|
| **Add New Users** | Shop + User + Role. The role starts as the user's default role. |
| **Edit** | change the user's role in that shop |
| **Revoke / Restore** | block or re-allow entering the shop; keeps the row and history |
| **Delete** | only when the user has no GRN, adjustment, invoice or transfer in that shop - otherwise use Revoke |

Example: to let a store keeper check stock in the Warehouse but not in the showroom, give them a
role with *Store → View* in the Warehouse row and revoke (or never add) the showroom row.

## 4. Deploying

1. Back up the database.
2. `php db/shop_access_install.php` on the server.
3. Upload the code.

Old code keeps working on the migrated table, except that it cannot add assignments without a
role - a window of seconds between steps 2 and 3.

## 5. Tests

```
sh tools/get-phpunit.sh                      # once
C:/xampp/php/php.exe tools/phpunit.phar      # unit + integration (database sleepmakers_test)
C:/xampp/php/php.exe tests/e2e/shop_login_e2e.php [base-url]   # against a running local site
```
