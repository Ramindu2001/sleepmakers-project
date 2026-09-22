# Shop Login and Per-Shop Access Control — Design

- **Date:** 2026-09-22
- **Branch:** `development`
- **Status:** Approved in design review, awaiting spec review

## 1. Problem

1. After signing in, a user picks a shop on `Public/dashboard.php` and is let straight in.
   `Controller/shopController.php` (`btn_continue`) copies whatever `cmb_shops` value is posted into
   `$_SESSION['shop_id']` without asking for credentials and without checking that the user is
   assigned to that shop, so any signed-in user can open any shop by posting its id.
2. Permissions are global. A user has one role (`user.UserRoles_URID`), and that role's feature
   rights (`userroleaccess`) and module rights (`usermoduleaccess`) apply in every shop. `shopusers`
   only records membership, so a user cannot be given stock access in Shop A while being kept out of
   Shop B, or given different rights in each.
3. `Controller/AddUsersToShopsController.php` has no authentication check, its "is this user
   already assigned?" call inserts the assignment, and an assignment cannot be removed once the user
   has any GRN, invoice, adjustment or transfer in that shop — so access can never be revoked.

## 2. Goals

- Entering a shop requires a username and password.
- Any user who has access to a shop can log into it with their normal username and password; the
  same credentials work for every shop or warehouse they are allowed into.
- Access and rights are managed per shop: each user–shop assignment carries its own role, and can be
  revoked and restored.
- The new screens match the existing UI (login card, shop cards, Bootstrap modals, table pages).
- Existing users keep exactly the rights they have today when the change is deployed.

### Non-goals

- Changing the main login page's behaviour (it keeps its current messages and username-only lookup).
- Login throttling / lockout. It would have to cover the main login too to be meaningful; noted as a
  follow-up.
- Per-user feature overrides (rejected approach B/C, see §9).

## 3. Decisions made in review

| Question | Decision |
|---|---|
| Who may log into a shop? | **Any permitted user.** Whoever enters valid credentials and has access to the shop becomes the session user for that shop. |
| When is the shop login asked? | **Every shop entry** from the shop screen, including after Switch Shop. A single-shop user goes straight in after the main sign-in (their password was just checked against that shop). Remember-me restores only a shop that was entered with a password, and only while access remains. |
| Access model | **A: role per shop assignment.** |

## 4. Data model

Additive only — nothing existing is dropped, renamed or re-typed.

### `shopusers`

| Change | Purpose |
|---|---|
| `UserRoles_URID INT NOT NULL` | The user's role **in this shop**. Backfilled from `user.UserRoles_URID`, so every existing user keeps their current rights. |
| `is_active TINYINT(1) NOT NULL DEFAULT 1` | Access on/off. Revoking keeps the row, so history and the role choice are preserved. |
| `UNIQUE KEY uq_shopusers_shop_user (shop_SHID, user_USID)` | One assignment per user per shop. Duplicates are merged first (lowest `SUID` kept). |
| `KEY idx_shopusers_role (UserRoles_URID)` | Role lookups. |

### `user.UserRoles_URID`

Unchanged in the database. Its meaning becomes **Default Role**: the role pre-selected when the user
is added to a shop, and the role given to a new user in the shop they are created from.

### Installer

- `db/shop_access_module.sql` — the schema as plain SQL (MariaDB 10.4+ / MySQL 8).
- `db/shop_access_install.php` — idempotent installer, same conventions as
  `db/barcode_module_install.php`: CLI (`php db/shop_access_install.php`) or browser for a signed-in
  super admin only; reports each step; safe to run twice.
- Steps: add `UserRoles_URID` as NULL → backfill from `user` → add `is_active` → rows whose user no
  longer exists get `UserRoles_URID = 0` (matches no role) and `is_active = 0`, and are listed in the
  report → make `UserRoles_URID` NOT NULL → merge duplicate assignments → add the unique key and
  index.
- `db/SHOP_ACCESS_MODULE.md` documents install and behaviour. `.gitignore` re-includes these three
  files, as it does for the barcode module.

**Deploy order:** run the installer, then upload the code (old code keeps working on the migrated
schema except that it cannot insert assignments without a role — a window of seconds).

## 5. Access rules — one place

New `Model/shop_access_class.php`, `class ShopAccess extends Dbh`, loaded by `Includes/includes.php`.

A user **may enter** a shop when:

- **Super admin** (`user.UserType = 1`), active: any shop that exists (as today).
- **Everyone else:** `user.UserStat = 1`, an assignment row exists with `is_active = 1`,
  `shop.ShopStat = 1`, and the assignment's role has `userroles.ur_status = 1`.

Company expiry / `ComStat = 0` is **not** part of `canAccessShop` (unchanged behaviour: it is enforced
where the shop is chosen — greyed-out card for non-admins, refused at shop login — and admins may
still enter an expired company).

| Method | Returns |
|---|---|
| `canAccessShop($user_id, $shop_id)` | `bool` per the rule above |
| `getShopRoleId($user_id, $shop_id)` | role id in that shop; `null` for super admin or no access |
| `getSelectableShops($user_id)` | the rows for the shop screen: shop + company columns (`SHID`, `ShopName`, `ComName`, `ComStat`, `ComExpireDate`, …) for every shop the user may enter |
| `authenticate($username, $password, $shop_id)` | `ShopLoginResult`: `ok`, `user` row, or `error` code (see §6) |

### Where the per-shop role is used

| Place | Change |
|---|---|
| `View/sidebar.php` | `$userRole_id = ShopAccess::getShopRoleId($user_id, $shop_id)`; every page reads `$userRole_id` from here, so all page guards (`viewPermission.php`, `editPermission.php`, inline `userAcces` checks) become per-shop with no page edits. |
| `View/right-sidebar.php` | same |
| `Includes/barcode_helper.php` `bcUserRight` | resolves the role for the session shop |
| `User::getUserFeatureAccess` (product search AJAX) | joins the assignment for the session shop instead of `user.UserRoles_URID` |
| `Includes/remember_me.php` `canAccessShop` | delegates to `ShopAccess` |

A non-admin with no role in the current shop gets `$userRole_id = 0`, which matches no
`userroleaccess` rows, so every check denies (fail closed). In practice `authcheck.php` sends them to
the shop screen before a page renders.

### Enforcement on every page

`Includes/authcheck.php`, after resolving `shop_id` (session or remember-me), calls
`canAccessShop($user_id, $shop_id)`. If it fails: unset `shop_id`, clear the remember-me shop cookie,
set a flash message *"Your access to this shop has been removed."*, redirect to `dashboard.php`.
Revoking access or changing a role therefore takes effect on the user's next page load.

### Where a shop session may be set

After this change `$_SESSION['shop_id']` is written only by:

1. the shop login (`shopController.php`, `btn_shop_login`);
2. the main login for a single-shop user, right after the password was verified
   (`userController.php`);
3. a valid signed remember-me shop token (`authcheck.php`, `dashboard.php`), which is re-checked by
   `canAccessShop`.

Removed: the `btn_continue` handler, and the dashboard's own auto-entry for single-shop users
(the dashboard now always asks; see §6).

## 6. Shop login flow

### Shop screen (`Public/dashboard.php`)

- Cards look as today (shop icon, name, company-expired / inactive state for non-admins).
- Cards are no longer submit buttons of one shared form. Clicking an enabled card opens the modal.
- Shops come from `ShopAccess::getSelectableShops($session_user_id)`.
- No shops → as today: sign out with *"No shops assigned to your account…"*.
- One shop → one card, which also asks for credentials (reached only via Switch Shop / remember-me).

### Modal — "Sign in to *Shop Name*"

Bootstrap modal styled like the login card (`Public/login.php`):

- Cloud POS logo, shop icon, title *Sign in to Valentino Italy*.
- **Username** (pre-filled with the signed-in user's name, editable) and **Password** (always
  empty) — same inputs and **Show Password** checkbox as the login page. The user is looked up
  exactly as the main login does (`User::getUserByName`), so the same credentials work; the label
  says *Username* because an email address is not accepted (see §11).
- Full-width blue **Sign In** (`btn btn-primary w-100 py-8 fs-4 rounded-2`) and a **Cancel** button.
- Hidden `shop_id` and CSRF token.
- Error line in the login page's style (`text-center`, red, bold).

### Submit — `POST Controller/shopController.php`, `btn_shop_login`

1. Require a signed-in session user and a valid CSRF token; otherwise redirect to login / dashboard.
2. `ShopAccess::authenticate($username, $password, $shop_id)`:

   | Error code | Message |
   |---|---|
   | `bad_credentials` (unknown user or wrong password) | Invalid username or password |
   | `user_inactive` | Your account is inactive. Please contact your system admin. |
   | `no_access` | You do not have access to this shop. |
   | `role_inactive` | Your role in this shop is inactive. Please contact your system admin. |
   | `company_expired` (non-admin) | Company Expired. Please Contact Synnex IT Solutions. |
   | `company_inactive` (non-admin) | Company Inactive. Please Contact Synnex IT Solutions. |

3. **Failure:** store `{shop_id, username, message}` in a flash session key and redirect to
   `dashboard.php`, which reopens the modal for that shop with the message and the typed username.
4. **Success:**
   - `session_regenerate_id(true)`.
   - If the authenticated user differs from the session user: set `$_SESSION['user_id']` and
     `$_SESSION['user']`, write a `userlog` entry, clear the remember-me cookies and
     `$_SESSION['remember_me']` (a shared counter never stays remembered as the previous person).
   - If it is the same user and remember-me is on: issue the signed shop token (as today).
   - Set `$_SESSION['shop_id']`, `$_SESSION['loading']`, `$_SESSION['toast']`; redirect to `home.php`.

### Main login (`Controller/userController.php`)

- The shop list comes from `getSelectableShops`.
- The old check on the *default* role's status (`CheckUserRoleStatus(user.UserRoles_URID)`) is
  replaced by the per-shop rule: active assignments exist but none is enterable because every
  assigned role is inactive → existing error 7 (*Inactive userrole*). Nothing enterable otherwise →
  existing error 9 (*No shops assigned*).
- Exactly one enterable shop → straight in (company-expiry rule unchanged); when *Remember me* is
  ticked the signed shop token is issued too, so a remembered single-shop user is not asked again
  after the PHP session expires.
- Several shops → shop screen.

### Switch Shop

Unchanged (`switchshop.php` clears the shop and remembered shop) — the next shop needs credentials.

### CSRF

New `Includes/csrf.php`: `csrf_token()` (per-session random token) and
`csrf_validate($token)` (`hash_equals`). Used by the shop-login form and the assignment endpoints.

## 7. Administration — Settings → Assign Users to Shops

`Public/AssignUsersToShops.php` (super admin only, as today):

| Column | Content |
|---|---|
| ID, Shop, User | as today |
| **Role** | role in that shop |
| **Access** | badge: green *Active* / red *Revoked* |
| Actions | **Edit** · **Revoke** / **Restore** · **Delete** |

- **Add / Edit** use the existing modal with a third field, **Role** (active roles only; picking a
  user pre-selects their default role). Adding an existing pair shows *"User already assigned to this
  shop — use Edit."*; Edit changes only the role.
- **Revoke / Restore** toggles `is_active`.
- **Delete** keeps today's rule (only when the user has no GRN/adjustment/invoice/transfer in that
  shop); when refused the message says *"…use Revoke to remove access."*
- `Controller/AddUsersToShopsController.php` rewritten: requires a signed-in super admin and a CSRF
  token, validates ids and role, one action per request (`save`, `update_role`, `set_active`,
  `delete`), JSON responses `{ok, message}`. The inserting "check" call is removed.
- `Assets/jquery/AddShops.js` updated for the new actions.

### Users page

- `Public/users.php` and its modals: label **User Role** → **Default Role**, with the hint
  *"Rights in each shop are set under Assign Users to Shops."*
- `userController.php` `add-user`: the new user is assigned to the current shop with their default
  role (as today, now with the role stored).

## 8. Testing

- **PHPUnit 11** as `tools/phpunit.phar` (git-ignored; `tools/get-phpunit.sh` downloads it). Composer
  is not required and nothing test-related ships to the server.
- **Test database:** `sleepmakers_test`, rebuilt from the local `sleepmakers` schema by
  `tests/bootstrap.php`; tests never touch `sleepmakers` or live.
- **`Includes/config.php`:** if the environment variable `CLOUDPOS_DB_CREDENTIALS` names a file, it
  is used instead of `Includes/db_credentials.php`. Without it, behaviour is unchanged.
- **Unit / integration tests:**
  - `ShopAccess::canAccessShop` — admin, assigned, revoked, inactive shop, inactive role, inactive
    user, unassigned.
  - `ShopAccess::getShopRoleId` — the same user resolves to different roles in two shops.
  - `ShopAccess::authenticate` — success and each error code.
  - `getSelectableShops` — admin sees all, user sees only enterable shops.
  - Installer — backfill, duplicate merge, orphan handling, idempotent second run.
  - Shop-login controller — posting a shop the user is not assigned to is refused; identity switch.
- **End-to-end** against local XAMPP over HTTP with a cookie jar: sign in → shop screen → wrong
  password refused → another user's valid login switches identity → revoke → next click lands on the
  shop screen → menus follow the per-shop role. Plus a browser check of the modal.

## 9. Alternatives considered

- **B. Per-user-per-shop feature matrix** — most granular, but ~70 features × 6 flags per person per
  shop to maintain, duplicates roles, large new UI. Rejected.
- **C. Global role + per-shop overrides** — flexible, but precedence rules are hard to reason about
  and audit. Rejected.

## 10. Delivery

- Each step is its own commit on `development`, pushed to `origin/development` immediately.
- Order: spec → plan → test harness → migration → `ShopAccess` → permission wiring → page
  enforcement → shop login → admin screen → users page → end-to-end verification.
- Not merged to `main`; not deployed. On deploy: run `db/shop_access_install.php` first (see §4).

## 11. Follow-ups (out of scope)

- Login throttling for both the main login and the shop login.
- The main login page accepts only the username although its label says *Username/email*.
