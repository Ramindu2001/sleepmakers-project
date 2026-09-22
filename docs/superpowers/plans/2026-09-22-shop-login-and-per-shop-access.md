# Shop Login and Per-Shop Access Control Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entering a shop requires a username and password, and each user–shop assignment carries its own role and an active flag, so rights are managed per shop.

**Architecture:** `shopusers` gains `UserRoles_URID` (role in that shop) and `is_active`, applied by an idempotent migration. One class, `ShopAccess` (`Model/shop_access_class.php`), holds every access rule; the sidebars, barcode helper, product search, page guard (`authcheck.php`), session poll (`newauthcheck.php`), main login and the new shop login all ask it. The shop screen opens a login modal per shop that posts to `shopController.php`.

**Tech Stack:** PHP 8.2 (XAMPP locally, ea-php82 on the server), MariaDB 10.4 local / 11.4 server, PDO, Bootstrap 5, jQuery, PHPUnit 11 (phar).

**Spec:** `docs/superpowers/specs/2026-09-22-shop-login-and-per-shop-access-design.md`

## Global Constraints

- Additive schema only: nothing existing is dropped, renamed or re-typed; the migration is idempotent.
- Existing users keep exactly their current rights after the migration (each assignment gets the user's current role).
- Super admin = `user.UserType = 1`: enters every shop, not limited by roles, still types the password at the shop login.
- A user may enter a shop when: user `UserStat = 1`, assignment `is_active = 1`, `shop.ShopStat = 1`, role `ur_status = 1`. Company expired / `ComStat = 0` only blocks non-admins, at the point of choosing the shop.
- Shop login looks users up exactly like the main login (`UserName = ?`); the field is labelled **Username**.
- Error texts, verbatim: `Invalid username or password` · `Your account is inactive. Please contact your system admin.` · `You do not have access to this shop.` · `Your role in this shop is inactive. Please contact your system admin.` · `Company Expired. Please Contact Synnex IT Solutions.` · `Company Inactive. Please Contact Synnex IT Solutions.` · `Your access to this shop has been removed.` · `Your session expired. Please try again.`
- Code style: match the surrounding legacy PHP — `snake_case`/`camelCase` mix as found, `//comment` after closing braces (`}//if ...`), `header("Location: ...")`, no namespaces, no Composer autoloading for app classes.
- Every page render must stay free of new PHP warnings (pages run with `display_errors = 1`).
- Tests never touch the `sleepmakers` database or the server; unit tests use `sleepmakers_test`; the E2E script creates and removes its own `e2e…` records in the local dev database.
- After each task: `git add` the task's files, commit with the given message plus the trailer `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`, and `git push origin development`.
- Commands below run from the repo root `C:\xampp\htdocs\sleepmakers` in Git Bash. PHP is `C:/xampp/php/php.exe` (aliased `PHP` below), MySQL client is `/c/xampp/mysql/bin/mysql -uroot`.

## Spec additions found while planning

1. `Includes/newauthcheck.php` is polled every 10 s by every page (`View/header.php`, `View/gui-header.php`) and sends the browser to `switchshop.php` on `-1`. It gets the same `canAccessShop` check, so revocation also reaches idle screens (POS) within ~10 s.
2. **Switch Shop** is shown only to super admins today (`View/header.php:84`, `View/gui-header.php:104`). Under this design every user needs it (moving between their shops, handing a counter over), so it is shown to everyone.

## File Structure

| File | Responsibility |
|---|---|
| `Includes/config.php` (modify) | CLI-only `CLOUDPOS_DB_CREDENTIALS` override so tests reach their own database |
| `tools/get-phpunit.sh` (create) | download PHPUnit 11 phar (git-ignored) |
| `phpunit.xml` (create) | PHPUnit config |
| `tests/bootstrap.php`, `tests/db_credentials.test.php`, `tests/DatabaseTestCase.php`, `tests/fixtures/legacy_schema.sql` (create) | test harness: throwaway DB, pre-migration schema, fixture helpers |
| `db/shop_access_migration.php` (create) | `ShopAccessMigration` — the schema change as a testable class |
| `db/shop_access_install.php` (create) | CLI/browser wrapper that runs the migration and prints the report |
| `db/shop_access_module.sql` (create) | same change as plain SQL |
| `db/SHOP_ACCESS_MODULE.md` (create) | install + behaviour + admin guide |
| `Model/shop_access_class.php` (create) | `ShopAccess` — every access rule, shop login authentication, messages |
| `Includes/csrf.php` (create) | per-session CSRF token |
| `Includes/shop_session.php` (create) | `shop_session_enter()` — session changes after a shop login |
| `Controller/shopController.php` (modify) | `btn_shop_login` replaces the unchecked `btn_continue` |
| `Public/dashboard.php` (rewrite) | shop cards + shop login modal + access-removed alert |
| `Includes/authcheck.php`, `Includes/newauthcheck.php` (modify) | re-check access on every page and every poll |
| `View/header.php`, `View/gui-header.php` (modify) | Switch Shop for everyone |
| `View/sidebar.php`, `View/right-sidebar.php`, `Includes/barcode_helper.php`, `Model/user_class.php`, `AJAX/Products/getProductSearch.php`, `Includes/remember_me.php`, `Includes/includes.php` (modify) | per-shop role lookups |
| `Controller/userController.php`, `Public/login.php` (modify) | main login uses `ShopAccess`; new-user assignment with role |
| `Model/add_users_to_shops_class.php`, `Controller/AddUsersToShopsController.php`, `Public/AssignUsersToShops.php`, `View/modals/add-user-features.php`, `Assets/jquery/AddShops.js` (modify) | admin screen: role, revoke/restore, hardened JSON endpoint |
| `Public/users.php`, `View/modals/add-user.php`, `View/modals/edit-user.php` (modify) | "Default Role" label |
| `tests/e2e/shop_login_e2e.php` (create) | end-to-end check over HTTP against local XAMPP |

---

### Task 1: Test harness

**Files:**
- Modify: `Includes/config.php:16-19`
- Modify: `.gitignore`
- Create: `tools/get-phpunit.sh`, `phpunit.xml`, `tests/bootstrap.php`, `tests/db_credentials.test.php`, `tests/DatabaseTestCase.php`, `tests/fixtures/legacy_schema.sql`
- Test: `tests/DatabaseHarnessTest.php`

**Interfaces:**
- Produces: `abstract class DatabaseTestCase extends PHPUnit\Framework\TestCase` with `protected PDO $pdo`, `protected function insert(string $table, array $row): int`, `createCompany(array $overrides = []): int`, `createShop(int $company_id, array $overrides = []): int`, `createRole(string $name, bool $active = true): int`, `createUser(string $name, string $password, int $role_id, array $overrides = []): int`, `grant(int $role_id, int $feature_id, array $rights): int`, `allowModule(int $role_id, int $module_id): int`. `class TestDbh extends Dbh { public function pdo(): PDO }`.

- [ ] **Step 1: Let the CLI point the app at another credentials file** — in `Includes/config.php` replace

```php
            //a server keeps its own credentials in db_credentials.php (never committed);
            //without that file the local XAMPP defaults above are used
            $credentialsFile = __DIR__ . '/db_credentials.php';
```
with
```php
            //a server keeps its own credentials in db_credentials.php (never committed);
            //without that file the local XAMPP defaults above are used. From the command line
            //CLOUDPOS_DB_CREDENTIALS may name another credentials file - the test suite uses it
            //to run against its own database. Web requests never read it.
            $credentialsFile = __DIR__ . '/db_credentials.php';
            if (PHP_SAPI === 'cli' && getenv('CLOUDPOS_DB_CREDENTIALS')) {
                $credentialsFile = getenv('CLOUDPOS_DB_CREDENTIALS');
            } //test or tool override
```
(The file starts with a UTF-8 BOM — keep it; edit with the Edit tool, not by rewriting the file.)

- [ ] **Step 2: PHPUnit download script** — `tools/get-phpunit.sh`:

```sh
#!/usr/bin/env sh
# Downloads PHPUnit 11 (needs PHP 8.2+) into tools/phpunit.phar. The phar is git-ignored: it is
# a development tool and never ships to the server. Run the tests with
#   C:/xampp/php/php.exe tools/phpunit.phar
set -e
cd "$(dirname "$0")"
curl -fsSL -o phpunit.phar https://phar.phpunit.de/phpunit-11.phar
echo "downloaded tools/phpunit.phar"
```

- [ ] **Step 3: `.gitignore`** — append:

```
# PHPUnit (tools/get-phpunit.sh) and its cache
tools/phpunit.phar
.phpunit.cache/
```

- [ ] **Step 4: `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- Run: C:/xampp/php/php.exe tools/phpunit.phar  (download it with tools/get-phpunit.sh) -->
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         failOnWarning="true"
         failOnNotice="true">
    <testsuites>
        <testsuite name="Cloud POS">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 5: `tests/db_credentials.test.php`**

```php
<?php
//The database the test suite runs against (tests/bootstrap.php). The tests drop and recreate
//its tables before every test: never point this at a real database.
return [
    'host'   => getenv('CLOUDPOS_TEST_DB_HOST') ?: 'localhost',
    'user'   => getenv('CLOUDPOS_TEST_DB_USER') ?: 'root',
    'pwd'    => getenv('CLOUDPOS_TEST_DB_PWD') ?: '',
    'dbName' => 'sleepmakers_test',
];
```

- [ ] **Step 6: `tests/fixtures/legacy_schema.sql`** — the access-related tables exactly as they are before this module (copied from the local database with `SHOW CREATE TABLE`; the `company → companytype` foreign key is left out because `companytype` is not part of the fixture). No semicolons inside comments — the loader splits on `;`.

```sql
-- Access related tables as they were before the shop access module.
-- tests/DatabaseTestCase.php loads this before every test.

CREATE TABLE `company` (
  `CMID` int(11) NOT NULL AUTO_INCREMENT,
  `CompanyNo` varchar(12) DEFAULT NULL,
  `ComName` varchar(60) DEFAULT NULL,
  `CompanyLocation` varchar(60) DEFAULT NULL,
  `LicenceNo` varchar(60) DEFAULT NULL,
  `VersionNo` varchar(60) DEFAULT NULL,
  `ComLogo` varchar(255) DEFAULT NULL,
  `ComStartDate` date DEFAULT NULL,
  `ComExpireDate` date DEFAULT NULL,
  `ComStat` tinyint(4) DEFAULT NULL,
  `is_multicategory` tinyint(4) DEFAULT NULL,
  `is_commonStock` int(11) NOT NULL DEFAULT 0,
  `CompanyType_CTID` int(11) NOT NULL,
  `last_updateDate` date NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`CMID`),
  KEY `fk_Company_CompanyType1_idx` (`CompanyType_CTID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `shop` (
  `SHID` int(11) NOT NULL AUTO_INCREMENT,
  `ShopNo` varchar(12) DEFAULT NULL,
  `ShopName` varchar(45) DEFAULT NULL,
  `ShopLogo` varchar(255) DEFAULT NULL,
  `ReceiptLogo` varchar(255) DEFAULT NULL,
  `WholesaleShop` tinyint(1) NOT NULL COMMENT 'Wholesale shop',
  `RetailShop` tinyint(1) NOT NULL COMMENT 'Retail shop',
  `is_inventory` tinyint(4) DEFAULT NULL,
  `is_minus` tinyint(4) DEFAULT NULL,
  `is_category` tinyint(4) DEFAULT NULL,
  `is_expire` tinyint(4) DEFAULT NULL,
  `is_variation` tinyint(4) DEFAULT NULL,
  `is_suppliers` tinyint(4) DEFAULT NULL,
  `is_service` tinyint(4) DEFAULT NULL,
  `is_salesman` tinyint(4) DEFAULT NULL,
  `is_expenses` tinyint(4) DEFAULT NULL,
  `is_customers` tinyint(4) DEFAULT NULL,
  `is_fixedprice` tinyint(4) DEFAULT NULL,
  `is_carton` tinyint(4) DEFAULT NULL,
  `is_warranty` tinyint(4) DEFAULT NULL,
  `is_promotions` tinyint(4) DEFAULT NULL,
  `is_secondlan` tinyint(4) DEFAULT NULL,
  `is_labelprice` tinyint(4) DEFAULT NULL,
  `is_quotation` tinyint(4) DEFAULT NULL,
  `is_racks` tinyint(4) DEFAULT NULL,
  `is_credit` tinyint(4) DEFAULT NULL,
  `invoice_print` tinyint(4) NOT NULL DEFAULT 1,
  `is_prescription` tinyint(1) NOT NULL,
  `is_counter` int(11) NOT NULL DEFAULT 1,
  `is_excessAmount` int(11) NOT NULL DEFAULT 0,
  `is_BatchNo` int(11) NOT NULL DEFAULT 0,
  `is_under_cost` int(11) NOT NULL DEFAULT 0,
  `is_a4invoice` tinyint(4) NOT NULL DEFAULT 0,
  `ShopStat` tinyint(4) DEFAULT NULL,
  `Company_CMID` int(11) NOT NULL,
  `StockTypes_STID` int(11) NOT NULL,
  `AddressLineOne` varchar(255) DEFAULT NULL,
  `AddressLineTwo` varchar(255) DEFAULT NULL,
  `City` varchar(120) DEFAULT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `PhoneNumber` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`SHID`),
  KEY `fk_shop_Company1_idx` (`Company_CMID`),
  KEY `fk_shop_StockTypes1_idx` (`StockTypes_STID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `user` (
  `USID` int(11) NOT NULL AUTO_INCREMENT,
  `UserProfile` varchar(255) DEFAULT NULL,
  `UserName` varchar(45) DEFAULT NULL,
  `UserEmail` varchar(150) DEFAULT NULL,
  `ContactNo` varchar(12) DEFAULT NULL,
  `UserPwd` varchar(255) DEFAULT NULL,
  `PwdChange` varchar(255) DEFAULT NULL,
  `UserStat` tinyint(4) DEFAULT 1,
  `UserRoles_URID` int(11) NOT NULL,
  `UserType` int(11) NOT NULL DEFAULT 0,
  `paylimit` float(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`USID`),
  KEY `fk_user_UserRoles1_idx` (`UserRoles_URID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `userroles` (
  `URID` int(11) NOT NULL AUTO_INCREMENT,
  `UserRoleName` varchar(60) DEFAULT NULL,
  `ur_status` tinyint(1) NOT NULL DEFAULT 1,
  `added_by` int(11) NOT NULL,
  `user_ip` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`URID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `shopusers` (
  `SUID` int(11) NOT NULL AUTO_INCREMENT,
  `shop_SHID` int(11) NOT NULL,
  `user_USID` int(11) NOT NULL,
  PRIMARY KEY (`SUID`),
  KEY `fk_ShopUsers_shop1_idx` (`shop_SHID`),
  KEY `fk_ShopUsers_user1_idx` (`user_USID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `userlog` (
  `ULID` int(11) NOT NULL AUTO_INCREMENT,
  `logStart` datetime DEFAULT NULL,
  `logEnd` datetime DEFAULT NULL,
  `logStat` tinyint(4) DEFAULT NULL,
  `user_USID` int(11) NOT NULL,
  PRIMARY KEY (`ULID`),
  KEY `fk_userlog_user1_idx` (`user_USID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `userroleaccess` (
  `RAID` int(11) NOT NULL AUTO_INCREMENT,
  `is_create` tinyint(4) DEFAULT NULL,
  `is_edit` tinyint(4) DEFAULT NULL,
  `is_view` tinyint(4) DEFAULT NULL,
  `is_delete` tinyint(4) DEFAULT NULL,
  `is_verify` tinyint(4) DEFAULT NULL,
  `is_print` tinyint(4) DEFAULT NULL,
  `UserRolls_URID` int(11) NOT NULL,
  `SysFeatures_SFID` int(11) NOT NULL,
  PRIMARY KEY (`RAID`),
  KEY `fk_UserRoleAccess_UserRolls1_idx` (`UserRolls_URID`),
  KEY `fk_UserRoleAccess_SysFeatures1_idx` (`SysFeatures_SFID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `usermoduleaccess` (
  `MAID` int(11) NOT NULL AUTO_INCREMENT,
  `SysModules_SMID` int(11) NOT NULL,
  `UserRoles_URID` int(11) DEFAULT NULL,
  PRIMARY KEY (`MAID`),
  KEY `fk_UserModuleAccess_SysModules1_idx` (`SysModules_SMID`),
  KEY `UserRoles_URID` (`UserRoles_URID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

- [ ] **Step 7: `tests/bootstrap.php`**

```php
<?php
//PHPUnit bootstrap: points the application's database layer (Includes/config.php) at the
//throwaway test database and creates it when missing. Tests that need the database extend
//DatabaseTestCase, which rebuilds its tables before every test.
date_default_timezone_set('Asia/Colombo');

$credentialsFile = __DIR__ . '/db_credentials.test.php';
$credentials = require $credentialsFile;
if (substr($credentials['dbName'], -5) !== '_test') {
    fwrite(STDERR, "Refusing to run: the test database name must end in _test.\n");
    exit(1);
}//safety net - the tests drop tables

putenv('CLOUDPOS_DB_CREDENTIALS=' . $credentialsFile);

$server = new PDO('mysql:host=' . $credentials['host'] . ';charset=utf8mb4', $credentials['user'], $credentials['pwd']);
$server->exec('CREATE DATABASE IF NOT EXISTS `' . $credentials['dbName'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
$server = null;

ob_start(); //config.php starts with a byte order mark: keep it out of the test output
require_once __DIR__ . '/../Includes/config.php';
ob_end_clean();
require_once __DIR__ . '/../Model/DB_Class.php';
require_once __DIR__ . '/../Model/user_class.php';
require_once __DIR__ . '/DatabaseTestCase.php';
```

- [ ] **Step 8: `tests/DatabaseTestCase.php`**

```php
<?php
use PHPUnit\Framework\TestCase;

//Reaches the application's shared PDO handle (Dbh::connect() is protected), so the tests use
//exactly the connection the code under test uses.
class TestDbh extends Dbh
{
    public function pdo()
    {
        return $this->connect();
    }//pdo
}//TestDbh

//Base class for tests that need the database. Before every test all tables of the test
//database are dropped and tests/fixtures/legacy_schema.sql is loaded, so each test starts
//from the same known state.
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = (new TestDbh())->pdo();
        $this->resetSchema();
    }//setUp

    private function resetSchema()
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec('DROP TABLE `' . $table . '`');
        }//each table
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/fixtures/legacy_schema.sql'));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $this->pdo->exec($statement);
        }//each statement
    }//resetSchema

    //insert one row and return its auto increment id
    protected function insert($table, array $row)
    {
        $columns = array_keys($row);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) VALUES ('
            . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $this->pdo->prepare($sql)->execute(array_values($row));
        return (int) $this->pdo->lastInsertId();
    }//insert

    protected function createCompany(array $overrides = [])
    {
        return $this->insert('company', $overrides + [
            'ComName' => 'Test Company',
            'ComStat' => 1,
            'ComExpireDate' => date('Y-m-d', strtotime('+1 year')),
            'CompanyType_CTID' => 1,
        ]);
    }//createCompany

    protected function createShop($company_id, array $overrides = [])
    {
        return $this->insert('shop', $overrides + [
            'ShopName' => 'Test Shop',
            'WholesaleShop' => 0,
            'RetailShop' => 1,
            'is_prescription' => 0,
            'ShopStat' => 1,
            'Company_CMID' => $company_id,
            'StockTypes_STID' => 1,
            'emailAddress' => 'shop@example.com',
        ]);
    }//createShop

    protected function createRole($name, $active = true)
    {
        return $this->insert('userroles', ['UserRoleName' => $name, 'ur_status' => $active ? 1 : 0, 'added_by' => 1]);
    }//createRole

    protected function createUser($name, $password, $role_id, array $overrides = [])
    {
        return $this->insert('user', $overrides + [
            'UserName' => $name,
            'UserEmail' => strtolower($name) . '@example.com',
            'UserPwd' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]),
            'UserStat' => 1,
            'UserRoles_URID' => $role_id,
            'UserType' => 0,
        ]);
    }//createUser

    //give a role rights on a feature, e.g. grant($role, 1, ['is_view'])
    protected function grant($role_id, $feature_id, array $rights)
    {
        $flags = ['is_create' => 0, 'is_edit' => 0, 'is_view' => 0, 'is_delete' => 0, 'is_verify' => 0, 'is_print' => 0];
        foreach ($rights as $right) {
            $flags[$right] = 1;
        }//each right
        return $this->insert('userroleaccess', $flags + ['UserRolls_URID' => $role_id, 'SysFeatures_SFID' => $feature_id]);
    }//grant

    protected function allowModule($role_id, $module_id)
    {
        return $this->insert('usermoduleaccess', ['SysModules_SMID' => $module_id, 'UserRoles_URID' => $role_id]);
    }//allowModule
}//DatabaseTestCase
```

- [ ] **Step 9: Write the harness test** — `tests/DatabaseHarnessTest.php`:

```php
<?php
final class DatabaseHarnessTest extends DatabaseTestCase
{
    public function test_runs_against_the_test_database()
    {
        $this->assertSame('sleepmakers_test', $this->pdo->query('SELECT DATABASE()')->fetchColumn());
    }

    public function test_fixture_helpers_create_rows()
    {
        $role = $this->createRole('Cashier');
        $user = $this->createUser('alice', 'secret', $role);

        $row = $this->pdo->query('SELECT UserName, UserRoles_URID FROM user WHERE USID = ' . $user)->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['UserName' => 'alice', 'UserRoles_URID' => (string) $role], $row);
    }

    public function test_every_test_starts_from_empty_tables()
    {
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM user')->fetchColumn());
    }
}
```

- [ ] **Step 10: Download PHPUnit and run** — `sh tools/get-phpunit.sh && PHP tools/phpunit.phar`
Expected: `OK (3 tests, 3 assertions)`.

- [ ] **Step 11: Commit and push**

```bash
git add Includes/config.php .gitignore tools/get-phpunit.sh phpunit.xml tests/
git commit -m "test: PHPUnit harness against a throwaway sleepmakers_test database"
git push origin development
```

---

### Task 2: Schema migration

**Files:**
- Create: `db/shop_access_migration.php`, `db/shop_access_install.php`, `db/shop_access_module.sql`, `db/SHOP_ACCESS_MODULE.md`
- Modify: `.gitignore` (re-include the new db files), `tests/bootstrap.php`, `tests/DatabaseTestCase.php`, `Model/add_users_to_shops_class.php:5-31` (`setUserModels` stores the role)
- Test: `tests/ShopAccessMigrationTest.php`

**Interfaces:**
- Consumes: `DatabaseTestCase` (Task 1).
- Produces: `class ShopAccessMigration { __construct(PDO $pdo); run(): string[] }`; `DatabaseTestCase::$migrate` (bool, default `true`) and `DatabaseTestCase::assign(int $user_id, int $shop_id, int $role_id, bool $active = true): int`. After this task `shopusers` = `SUID, shop_SHID, user_USID, UserRoles_URID INT NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1`, unique `(shop_SHID, user_USID)`.

- [ ] **Step 1: Write the failing tests** — `tests/ShopAccessMigrationTest.php`:

```php
<?php
final class ShopAccessMigrationTest extends DatabaseTestCase
{
    protected $migrate = false; //these tests run the migration themselves

    private function column($name)
    {
        $stmt = $this->pdo->prepare("SELECT IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shopusers' AND COLUMN_NAME = ?");
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function assignments()
    {
        return $this->pdo->query('SELECT SUID, shop_SHID, user_USID, UserRoles_URID, is_active FROM shopusers ORDER BY SUID')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function test_every_assignment_gets_its_users_current_role_and_stays_active()
    {
        $company = $this->createCompany();
        $shop = $this->createShop($company);
        $cashier = $this->createRole('Cashier');
        $manager = $this->createRole('Manager');
        $alice = $this->createUser('alice', 'x', $cashier);
        $bob = $this->createUser('bob', 'x', $manager);
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $alice]);
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $bob]);

        (new ShopAccessMigration($this->pdo))->run();

        $rows = $this->assignments();
        $this->assertSame((string) $cashier, $rows[0]['UserRoles_URID']);
        $this->assertSame((string) $manager, $rows[1]['UserRoles_URID']);
        $this->assertSame(['1', '1'], array_column($rows, 'is_active'));
    }

    public function test_assignments_of_deleted_users_are_revoked_with_no_role()
    {
        $shop = $this->createShop($this->createCompany());
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => 999]);

        $report = (new ShopAccessMigration($this->pdo))->run();

        $this->assertSame([['SUID' => '1', 'shop_SHID' => (string) $shop, 'user_USID' => '999', 'UserRoles_URID' => '0', 'is_active' => '0']], $this->assignments());
        $this->assertStringContainsString('user 999, who no longer exists', implode("\n", $report));
    }

    public function test_duplicate_assignments_are_merged_into_the_oldest()
    {
        $shop = $this->createShop($this->createCompany());
        $role = $this->createRole('Cashier');
        $alice = $this->createUser('alice', 'x', $role);
        $first = $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $alice]);
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $alice]);

        (new ShopAccessMigration($this->pdo))->run();

        $this->assertSame([(string) $first], array_column($this->assignments(), 'SUID'));
    }

    public function test_role_becomes_required_and_one_assignment_per_user_per_shop()
    {
        (new ShopAccessMigration($this->pdo))->run();

        $this->assertSame('NO', $this->column('UserRoles_URID')['IS_NULLABLE']);
        $this->assertSame('1', $this->column('is_active')['COLUMN_DEFAULT']);

        $shop = $this->createShop($this->createCompany());
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => 1, 'UserRoles_URID' => 1]);
        $this->expectException(PDOException::class);
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => 1, 'UserRoles_URID' => 2]);
    }

    public function test_running_twice_changes_nothing()
    {
        $shop = $this->createShop($this->createCompany());
        $role = $this->createRole('Cashier');
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $this->createUser('alice', 'x', $role)]);
        (new ShopAccessMigration($this->pdo))->run();
        $before = $this->assignments();

        $report = (new ShopAccessMigration($this->pdo))->run();

        $this->assertSame($before, $this->assignments());
        foreach ($report as $line) {
            $this->assertStringStartsWith('[skip]', $line);
        }
    }
}
```

- [ ] **Step 2: Add the `$migrate` switch and `assign()` helper to `tests/DatabaseTestCase.php`** — add the property below `protected PDO $pdo;`:

```php
    //run the shop access migration after loading the legacy schema (Task 2 tests turn it off)
    protected $migrate = true;
```
change `setUp()` to:
```php
    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = (new TestDbh())->pdo();
        $this->resetSchema();
        if ($this->migrate) {
            (new ShopAccessMigration($this->pdo))->run();
        }//migrated schema
    }//setUp
```
and add after `allowModule()`:
```php
    //assign a user to a shop with a role (needs the migrated schema)
    protected function assign($user_id, $shop_id, $role_id, $active = true)
    {
        return $this->insert('shopusers', [
            'shop_SHID' => $shop_id,
            'user_USID' => $user_id,
            'UserRoles_URID' => $role_id,
            'is_active' => $active ? 1 : 0,
        ]);
    }//assign
```
and in `tests/bootstrap.php` add before the `DatabaseTestCase.php` line:
```php
require_once __DIR__ . '/../db/shop_access_migration.php';
```

- [ ] **Step 3: Run to verify failure** — `PHP tools/phpunit.phar`
Expected: fatal/errors `Failed opening required '.../db/shop_access_migration.php'`.

- [ ] **Step 4: Implement `db/shop_access_migration.php`**

```php
<?php
/**
 * Shop access module - the schema change.
 * -----------------------------------------------------------------------------
 * Turns shopusers from plain membership into per-shop access:
 *
 *   shopusers.UserRoles_URID   the user's role IN THAT SHOP (was: one role everywhere)
 *   shopusers.is_active        access on/off - revoking keeps the row and the history
 *   uq_shopusers_shop_user     one assignment per user per shop
 *
 * Every existing assignment gets the role its user holds today, so nobody's rights change.
 *
 * ADDITIVE ONLY and idempotent: every step is skipped when it is already in place.
 * Run through db/shop_access_install.php (the tests call it directly).
 */
class ShopAccessMigration
{
    private $pdo;
    private $report = array();

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }//construct

    //apply every step; returns one report line per step
    public function run()
    {
        $this->report = array();

        $this->step('shopusers.UserRoles_URID column',
            !$this->hasColumn('shopusers', 'UserRoles_URID'),
            "ALTER TABLE shopusers ADD COLUMN UserRoles_URID INT(11) NULL COMMENT 'role in this shop' AFTER user_USID;");

        $this->step('shopusers.is_active column',
            !$this->hasColumn('shopusers', 'is_active'),
            "ALTER TABLE shopusers ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = may enter the shop' AFTER UserRoles_URID;");

        if ($this->isNullable('shopusers', 'UserRoles_URID')) {
            $copied = $this->pdo->exec("UPDATE shopusers
                INNER JOIN user ON user.USID = shopusers.user_USID
                SET shopusers.UserRoles_URID = user.UserRoles_URID
                WHERE shopusers.UserRoles_URID IS NULL;");
            $this->say('ok', 'shop roles copied from each user\'s role: ' . $copied . ' assignment(s)');

            $orphans = $this->pdo->query("SELECT SUID, shop_SHID, user_USID FROM shopusers
                WHERE UserRoles_URID IS NULL ORDER BY SUID;")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($orphans as $row) {
                $this->say('warn', 'assignment ' . $row['SUID'] . ' (shop ' . $row['shop_SHID'] . ') belongs to user '
                    . $row['user_USID'] . ', who no longer exists: revoked');
            }//each orphan
            $this->pdo->exec("UPDATE shopusers SET UserRoles_URID = 0, is_active = 0 WHERE UserRoles_URID IS NULL;");
        }//backfill

        $this->step('shopusers.UserRoles_URID required',
            $this->isNullable('shopusers', 'UserRoles_URID'),
            "ALTER TABLE shopusers MODIFY UserRoles_URID INT(11) NOT NULL COMMENT 'role in this shop';");

        $this->mergeDuplicates();

        $this->step('unique key uq_shopusers_shop_user',
            !$this->hasIndex('shopusers', 'uq_shopusers_shop_user'),
            "ALTER TABLE shopusers ADD UNIQUE KEY uq_shopusers_shop_user (shop_SHID, user_USID);");

        $this->step('index idx_shopusers_role',
            !$this->hasIndex('shopusers', 'idx_shopusers_role'),
            "ALTER TABLE shopusers ADD KEY idx_shopusers_role (UserRoles_URID);");

        return $this->report;
    }//run

    //one assignment per user per shop: keep the oldest row, active if any copy was
    private function mergeDuplicates()
    {
        $groups = $this->pdo->query("SELECT shop_SHID, user_USID, MIN(SUID) AS keep_suid,
            MAX(is_active) AS any_active, COUNT(*) AS copies
            FROM shopusers GROUP BY shop_SHID, user_USID HAVING COUNT(*) > 1;")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($groups)) {
            $this->say('skip', 'duplicate assignments - none');
            return;
        }//nothing to merge

        $keep = $this->pdo->prepare("UPDATE shopusers SET is_active = ? WHERE SUID = ?;");
        $drop = $this->pdo->prepare("DELETE FROM shopusers WHERE shop_SHID = ? AND user_USID = ? AND SUID <> ?;");
        foreach ($groups as $group) {
            $keep->execute(array($group['any_active'], $group['keep_suid']));
            $drop->execute(array($group['shop_SHID'], $group['user_USID'], $group['keep_suid']));
            $this->say('ok', 'user ' . $group['user_USID'] . ' in shop ' . $group['shop_SHID'] . ': '
                . ($group['copies'] - 1) . ' duplicate assignment(s) merged into ' . $group['keep_suid']);
        }//each duplicate group
    }//mergeDuplicates

    private function step($label, $needed, $sql)
    {
        if (!$needed) {
            $this->say('skip', $label . ' - already in place');
            return;
        }//nothing to do

        $this->pdo->exec($sql);
        $this->say('ok', $label);
    }//step

    private function say($status, $text)
    {
        $this->report[] = '[' . $status . '] ' . $text;
    }//say

    private function hasColumn($table, $column)
    {
        return $this->columnInfo($table, $column) !== false;
    }//hasColumn

    private function isNullable($table, $column)
    {
        $info = $this->columnInfo($table, $column);
        return $info !== false && $info['IS_NULLABLE'] === 'YES';
    }//isNullable

    private function columnInfo($table, $column)
    {
        $stmt = $this->pdo->prepare("SELECT IS_NULLABLE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?;");
        $stmt->execute(array($table, $column));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }//columnInfo

    private function hasIndex($table, $index)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?;");
        $stmt->execute(array($table, $index));
        return (int) $stmt->fetchColumn() > 0;
    }//hasIndex
}//ShopAccessMigration
```

Note on `test_running_twice_changes_nothing`: on the second run the backfill block is skipped (column no longer nullable) and every `step()`/`mergeDuplicates()` reports `[skip]`, so every line starts with `[skip]`.

- [ ] **Step 5: Run the tests** — `PHP tools/phpunit.phar`
Expected: `OK (8 tests, …)`.

- [ ] **Step 6: Installer `db/shop_access_install.php`**

```php
<?php
/**
 * Shop access module installer - see db/SHOP_ACCESS_MODULE.md.
 * -----------------------------------------------------------------------------
 * Run it once, BEFORE uploading the module's code:
 *      cli     : php db/shop_access_install.php
 *      browser : /db/shop_access_install.php   (signed-in administrator only)
 *
 * Idempotent and additive only - running it twice is harmless.
 */

$sai_browser = (php_sapi_name() !== 'cli');

if ($sai_browser) {
    session_start();
    header('Content-Type: text/plain; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo "Please log in to Cloud POS first, then reload this page.";
        exit;
    }//not logged in
}//browser

require_once __DIR__ . '/../Includes/config.php';
require_once __DIR__ . '/../Model/DB_Class.php';
require_once __DIR__ . '/shop_access_migration.php';

//changing the schema is administrator business - checked with a direct query
if ($sai_browser) {
    $sai_check = (new DBTransactions())->getMultipleData("SELECT UserType FROM user WHERE USID = ?;", array((int) $_SESSION['user_id']));

    if (empty($sai_check) || (int) $sai_check[0]['UserType'] !== 1) {
        echo "Only an administrator can run the shop access installer.";
        exit;
    }//not an administrator
}//browser

//reaches the shared PDO handle; Dbh::connect() is protected by design
class ShopAccessInstaller extends Dbh
{
    public function pdo()
    {
        return $this->connect();
    }//pdo
}//ShopAccessInstaller

$pdo = (new ShopAccessInstaller())->pdo();

echo "Shop access module installer\n";
echo "database: " . $pdo->query('SELECT DATABASE();')->fetchColumn() . "\n";
echo str_repeat('-', 60) . "\n";

try {
    foreach ((new ShopAccessMigration($pdo))->run() as $line) {
        echo $line . "\n";
    }//each step
    echo str_repeat('-', 60) . "\n";
    echo "Done.\n";
} catch (PDOException $e) {
    echo "[FAILED] " . $e->getMessage() . "\n";
    echo "The steps above were applied; nothing after the failing step was. Fix the cause and run the installer again.\n";
    exit(1);
}//catch
```

- [ ] **Step 7: Plain SQL `db/shop_access_module.sql`**

```sql
-- Shop access module: the same schema change as db/shop_access_install.php, as plain SQL
-- (MariaDB 10.4+ / MySQL 8). Prefer the installer - it is idempotent and reports what it did.
-- Run once, BEFORE uploading the module's code. See db/SHOP_ACCESS_MODULE.md.

ALTER TABLE shopusers
  ADD COLUMN UserRoles_URID INT(11) NULL COMMENT 'role in this shop' AFTER user_USID,
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = may enter the shop' AFTER UserRoles_URID;

-- every existing assignment keeps the rights its user has today
UPDATE shopusers
INNER JOIN user ON user.USID = shopusers.user_USID
SET shopusers.UserRoles_URID = user.UserRoles_URID
WHERE shopusers.UserRoles_URID IS NULL;

-- assignments of users that no longer exist: no role, no access
UPDATE shopusers SET UserRoles_URID = 0, is_active = 0 WHERE UserRoles_URID IS NULL;

ALTER TABLE shopusers MODIFY UserRoles_URID INT(11) NOT NULL COMMENT 'role in this shop';

-- one assignment per user per shop: keep the oldest
DELETE copy FROM shopusers copy
INNER JOIN shopusers original
  ON original.shop_SHID = copy.shop_SHID
 AND original.user_USID = copy.user_USID
 AND original.SUID < copy.SUID;

ALTER TABLE shopusers
  ADD UNIQUE KEY uq_shopusers_shop_user (shop_SHID, user_USID),
  ADD KEY idx_shopusers_role (UserRoles_URID);
```

- [ ] **Step 8: Keep new-user assignment working on the migrated table** — in `Model/add_users_to_shops_class.php` `setUserModels()` replace

```php
            $sql = "INSERT INTO shopusers (shop_SHID, user_USID) VALUES (?, ?)";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_SHID, $user_USID]);
```
with
```php
            //the user's default role becomes their role in this shop
            $sql = "INSERT INTO shopusers (shop_SHID, user_USID, UserRoles_URID)
            SELECT ?, USID, UserRoles_URID FROM user WHERE USID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_SHID, $user_USID]);
```

- [ ] **Step 9: Docs `db/SHOP_ACCESS_MODULE.md`**

````markdown
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
- A user with a single shop goes straight into it after the main sign in.
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
````

- [ ] **Step 10: `.gitignore`** — below the `!db/BARCODE_MODULE.md` line add:

```
!db/shop_access_migration.php
!db/shop_access_install.php
!db/shop_access_module.sql
!db/SHOP_ACCESS_MODULE.md
```

- [ ] **Step 11: Migrate the local development database** — `PHP db/shop_access_install.php`
Expected: `[ok]` for both columns, `shop roles copied … 7 assignment(s)`, `[ok] shopusers.UserRoles_URID required`, `[skip] duplicate assignments - none`, `[ok]` both keys. Then run it again: every line `[skip]`. Check: `/c/xampp/mysql/bin/mysql -uroot sleepmakers -e "SELECT * FROM shopusers"` shows `UserRoles_URID` equal to each user's role and `is_active = 1`.

- [ ] **Step 12: Commit and push**

```bash
git add db/shop_access_migration.php db/shop_access_install.php db/shop_access_module.sql db/SHOP_ACCESS_MODULE.md .gitignore tests/ Model/add_users_to_shops_class.php
git commit -m "feat(db): per-shop role and active flag on shopusers, with installer"
git push origin development
```

---

### Task 3: `ShopAccess` — the access rules

**Files:**
- Create: `Model/shop_access_class.php`
- Modify: `tests/bootstrap.php`
- Test: `tests/ShopAccessTest.php`

**Interfaces:**
- Consumes: migrated `shopusers` (Task 2), `DatabaseTestCase` helpers.
- Produces (`class ShopAccess extends Dbh`):
  - `const ERR_BAD_CREDENTIALS = 'bad_credentials'`, `ERR_USER_INACTIVE = 'user_inactive'`, `ERR_NO_ACCESS = 'no_access'`, `ERR_ROLE_INACTIVE = 'role_inactive'`, `ERR_COMPANY_EXPIRED = 'company_expired'`, `ERR_COMPANY_INACTIVE = 'company_inactive'`
  - `static errorMessage(string $error): string`
  - `static unavailableReason(array $shop /* ComStat, ComExpireDate */, $userType): ?string`
  - `canAccessShop($user_id, $shop_id): bool`
  - `getShopRoleId($user_id, $shop_id): ?int` (null for super admin or no access)
  - `getSelectableShops($user_id): array` of rows `SHID, ShopName, CMID, ComName, ComStat, ComExpireDate`
  - `hasInactiveRoleAssignment($user_id): bool`
  - `authenticate($username, $password, $shop_id): array{ok: bool, user: ?array, error: ?string}`

- [ ] **Step 1: Write the failing tests** — `tests/ShopAccessTest.php`:

```php
<?php
final class ShopAccessTest extends DatabaseTestCase
{
    private ShopAccess $access;
    private int $company;
    private int $warehouse;
    private int $showroom;
    private int $storeKeeper;
    private int $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->access = new ShopAccess();
        $this->company = $this->createCompany(['ComName' => 'Sleep Makers']);
        $this->warehouse = $this->createShop($this->company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($this->company, ['ShopName' => 'Valentino Italy']);
        $this->storeKeeper = $this->createRole('Store Keeper');
        $this->cashier = $this->createRole('Cashier');
    }

    private function admin()
    {
        return $this->createUser('admin', 'admin-pass', $this->cashier, ['UserType' => 1]);
    }

    // ---- canAccessShop -------------------------------------------------------------------

    public function test_admin_enters_every_shop_without_an_assignment()
    {
        $admin = $this->admin();
        $this->assertTrue($this->access->canAccessShop($admin, $this->warehouse));
        $this->assertTrue($this->access->canAccessShop($admin, $this->showroom));
    }

    public function test_user_enters_only_shops_assigned_to_them()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier);

        $this->assertTrue($this->access->canAccessShop($alice, $this->showroom));
        $this->assertFalse($this->access->canAccessShop($alice, $this->warehouse));
    }

    public function test_revoked_assignment_keeps_the_user_out()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier, false);

        $this->assertFalse($this->access->canAccessShop($alice, $this->showroom));
    }

    public function test_inactive_shop_keeps_users_out_but_not_admins()
    {
        $closed = $this->createShop($this->company, ['ShopStat' => 0]);
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $closed, $this->cashier);

        $this->assertFalse($this->access->canAccessShop($alice, $closed));
        $this->assertTrue($this->access->canAccessShop($this->admin(), $closed));
    }

    public function test_inactive_role_keeps_the_user_out()
    {
        $retired = $this->createRole('Retired', false);
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $retired);

        $this->assertFalse($this->access->canAccessShop($alice, $this->showroom));
    }

    public function test_inactive_user_is_kept_out_even_as_admin()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier, ['UserStat' => 0]);
        $this->assign($alice, $this->showroom, $this->cashier);
        $boss = $this->createUser('boss', 'x', $this->cashier, ['UserType' => 1, 'UserStat' => 0]);

        $this->assertFalse($this->access->canAccessShop($alice, $this->showroom));
        $this->assertFalse($this->access->canAccessShop($boss, $this->showroom));
    }

    public function test_rejects_ids_that_are_not_positive_integers()
    {
        $this->assertFalse($this->access->canAccessShop('1 OR 1=1', $this->showroom));
        $this->assertFalse($this->access->canAccessShop($this->admin(), 0));
        $this->assertFalse($this->access->canAccessShop($this->admin(), 999));
    }

    // ---- getShopRoleId -------------------------------------------------------------------

    public function test_the_same_user_holds_a_different_role_in_each_shop()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->warehouse, $this->storeKeeper);
        $this->assign($alice, $this->showroom, $this->cashier);

        $this->assertSame($this->storeKeeper, $this->access->getShopRoleId($alice, $this->warehouse));
        $this->assertSame($this->cashier, $this->access->getShopRoleId($alice, $this->showroom));
    }

    public function test_no_role_for_admins_or_without_access()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier, false);

        $this->assertNull($this->access->getShopRoleId($this->admin(), $this->showroom));
        $this->assertNull($this->access->getShopRoleId($alice, $this->showroom));
        $this->assertNull($this->access->getShopRoleId($alice, $this->warehouse));
    }

    // ---- getSelectableShops / hasInactiveRoleAssignment ------------------------------------

    public function test_user_is_offered_only_the_shops_they_may_enter()
    {
        $retired = $this->createRole('Retired', false);
        $outlet = $this->createShop($this->company, ['ShopName' => 'Outlet']);
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->warehouse, $this->storeKeeper);
        $this->assign($alice, $this->showroom, $this->cashier, false);
        $this->assign($alice, $outlet, $retired);

        $shops = $this->access->getSelectableShops($alice);

        $this->assertSame(['Warehouse'], array_column($shops, 'ShopName'));
        $this->assertSame('Sleep Makers', $shops[0]['ComName']);
        $this->assertArrayHasKey('ComExpireDate', $shops[0]);
        $this->assertArrayHasKey('ComStat', $shops[0]);
    }

    public function test_admin_is_offered_every_shop()
    {
        $this->createShop($this->company, ['ShopName' => 'Closed', 'ShopStat' => 0]);
        $shops = $this->access->getSelectableShops($this->admin());
        $this->assertSame(['Warehouse', 'Valentino Italy', 'Closed'], array_column($shops, 'ShopName'));
    }

    public function test_inactive_user_is_offered_nothing()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier, ['UserStat' => 0]);
        $this->assign($alice, $this->showroom, $this->cashier);
        $this->assertSame([], $this->access->getSelectableShops($alice));
    }

    public function test_tells_an_inactive_role_apart_from_no_assignment()
    {
        $retired = $this->createRole('Retired', false);
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $bob = $this->createUser('bob', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $retired);

        $this->assertTrue($this->access->hasInactiveRoleAssignment($alice));
        $this->assertFalse($this->access->hasInactiveRoleAssignment($bob));
    }

    // ---- authenticate --------------------------------------------------------------------

    public function test_valid_credentials_with_access_sign_in()
    {
        $alice = $this->createUser('alice', 'correct horse', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier);

        $result = $this->access->authenticate('alice', 'correct horse', $this->showroom);

        $this->assertTrue($result['ok']);
        $this->assertNull($result['error']);
        $this->assertSame((string) $alice, (string) $result['user']['USID']);
    }

    public function test_wrong_password_and_unknown_user_get_the_same_answer()
    {
        $alice = $this->createUser('alice', 'correct horse', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier);

        $this->assertSame(ShopAccess::ERR_BAD_CREDENTIALS, $this->access->authenticate('alice', 'wrong', $this->showroom)['error']);
        $this->assertSame(ShopAccess::ERR_BAD_CREDENTIALS, $this->access->authenticate('nobody', 'x', $this->showroom)['error']);
        $this->assertSame(ShopAccess::ERR_BAD_CREDENTIALS, $this->access->authenticate('alice', '', $this->showroom)['error']);
        $this->assertSame(ShopAccess::ERR_BAD_CREDENTIALS, $this->access->authenticate(null, null, $this->showroom)['error']);
    }

    public function test_inactive_account_is_refused()
    {
        $alice = $this->createUser('alice', 'pw', $this->cashier, ['UserStat' => 0]);
        $this->assign($alice, $this->showroom, $this->cashier);
        $this->assertSame(ShopAccess::ERR_USER_INACTIVE, $this->access->authenticate('alice', 'pw', $this->showroom)['error']);
    }

    public function test_shop_without_access_is_refused()
    {
        $alice = $this->createUser('alice', 'pw', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier, false);

        $this->assertSame(ShopAccess::ERR_NO_ACCESS, $this->access->authenticate('alice', 'pw', $this->warehouse)['error']);
        $this->assertSame(ShopAccess::ERR_NO_ACCESS, $this->access->authenticate('alice', 'pw', $this->showroom)['error']);
        $this->assertSame(ShopAccess::ERR_NO_ACCESS, $this->access->authenticate('alice', 'pw', 999)['error']);
        $this->assertSame(ShopAccess::ERR_NO_ACCESS, $this->access->authenticate('alice', 'pw', 'abc')['error']);
    }

    public function test_inactive_role_is_refused()
    {
        $retired = $this->createRole('Retired', false);
        $alice = $this->createUser('alice', 'pw', $this->cashier);
        $this->assign($alice, $this->showroom, $retired);
        $this->assertSame(ShopAccess::ERR_ROLE_INACTIVE, $this->access->authenticate('alice', 'pw', $this->showroom)['error']);
    }

    public function test_closed_company_refuses_users_but_not_admins()
    {
        $expired = $this->createCompany(['ComExpireDate' => date('Y-m-d', strtotime('-1 day'))]);
        $inactive = $this->createCompany(['ComStat' => 0]);
        $oldShop = $this->createShop($expired);
        $offShop = $this->createShop($inactive);
        $alice = $this->createUser('alice', 'pw', $this->cashier);
        $this->assign($alice, $oldShop, $this->cashier);
        $this->assign($alice, $offShop, $this->cashier);
        $this->createUser('boss', 'pw', $this->cashier, ['UserType' => 1]);

        $this->assertSame(ShopAccess::ERR_COMPANY_EXPIRED, $this->access->authenticate('alice', 'pw', $oldShop)['error']);
        $this->assertSame(ShopAccess::ERR_COMPANY_INACTIVE, $this->access->authenticate('alice', 'pw', $offShop)['error']);
        $this->assertTrue($this->access->authenticate('boss', 'pw', $oldShop)['ok']);
    }

    public function test_admin_signs_into_any_shop()
    {
        $this->createUser('boss', 'pw', $this->cashier, ['UserType' => 1]);
        $this->assertTrue($this->access->authenticate('boss', 'pw', $this->warehouse)['ok']);
    }

    // ---- messages and company state -------------------------------------------------------

    public function test_every_error_has_its_message()
    {
        $this->assertSame('Invalid username or password', ShopAccess::errorMessage(ShopAccess::ERR_BAD_CREDENTIALS));
        $this->assertSame('Your account is inactive. Please contact your system admin.', ShopAccess::errorMessage(ShopAccess::ERR_USER_INACTIVE));
        $this->assertSame('You do not have access to this shop.', ShopAccess::errorMessage(ShopAccess::ERR_NO_ACCESS));
        $this->assertSame('Your role in this shop is inactive. Please contact your system admin.', ShopAccess::errorMessage(ShopAccess::ERR_ROLE_INACTIVE));
        $this->assertSame('Company Expired. Please Contact Synnex IT Solutions.', ShopAccess::errorMessage(ShopAccess::ERR_COMPANY_EXPIRED));
        $this->assertSame('Company Inactive. Please Contact Synnex IT Solutions.', ShopAccess::errorMessage(ShopAccess::ERR_COMPANY_INACTIVE));
        $this->assertSame('Oops! Something went wrong', ShopAccess::errorMessage('unknown'));
    }

    public function test_unavailable_reason_follows_company_state()
    {
        $open = ['ComStat' => 1, 'ComExpireDate' => date('Y-m-d')];
        $expired = ['ComStat' => 1, 'ComExpireDate' => date('Y-m-d', strtotime('-1 day'))];
        $inactive = ['ComStat' => 0, 'ComExpireDate' => date('Y-m-d', strtotime('+1 year'))];

        $this->assertNull(ShopAccess::unavailableReason($open, 0));
        $this->assertSame(ShopAccess::ERR_COMPANY_EXPIRED, ShopAccess::unavailableReason($expired, 0));
        $this->assertSame(ShopAccess::ERR_COMPANY_INACTIVE, ShopAccess::unavailableReason($inactive, 0));
        $this->assertNull(ShopAccess::unavailableReason($expired, 1));
    }
}
```

- [ ] **Step 2: Load the class in tests** — `tests/bootstrap.php`, after the `user_class.php` line:

```php
require_once __DIR__ . '/../Model/shop_access_class.php';
```

- [ ] **Step 3: Run to verify failure** — `PHP tools/phpunit.phar --filter ShopAccessTest`
Expected: fails opening `Model/shop_access_class.php`.

- [ ] **Step 4: Implement `Model/shop_access_class.php`**

```php
<?php
//Who may enter which shop, and with which role.
//
//Each shopusers row assigns one user to one shop with the role they hold THERE (UserRoles_URID)
//and whether that access is active (is_active). Permission checks ask for the role in the
//current shop, so one person can have different rights in each shop - or no access at all.
//user.UserRoles_URID is only the default offered when the user is added to a shop.
//
//A super admin (user.UserType = 1) may enter every shop and is not limited by roles.
//See db/SHOP_ACCESS_MODULE.md.
class ShopAccess extends Dbh
{
    const ERR_BAD_CREDENTIALS  = 'bad_credentials';
    const ERR_USER_INACTIVE    = 'user_inactive';
    const ERR_NO_ACCESS        = 'no_access';
    const ERR_ROLE_INACTIVE    = 'role_inactive';
    const ERR_COMPANY_EXPIRED  = 'company_expired';
    const ERR_COMPANY_INACTIVE = 'company_inactive';

    //what the user is told for each refusal
    const MESSAGES = [
        self::ERR_BAD_CREDENTIALS  => 'Invalid username or password',
        self::ERR_USER_INACTIVE    => 'Your account is inactive. Please contact your system admin.',
        self::ERR_NO_ACCESS        => 'You do not have access to this shop.',
        self::ERR_ROLE_INACTIVE    => 'Your role in this shop is inactive. Please contact your system admin.',
        self::ERR_COMPANY_EXPIRED  => 'Company Expired. Please Contact Synnex IT Solutions.',
        self::ERR_COMPANY_INACTIVE => 'Company Inactive. Please Contact Synnex IT Solutions.',
    ];

    //checked against when the username is unknown, so a wrong username takes as long as a wrong
    //password and the response time does not reveal which usernames exist
    const TIMING_HASH = '$2y$10$Bzs983rK8QxvLYrRfcNsTOuwam2aT.bZncnWPuk/YwOupQLEaf3am';

    public static function errorMessage($error)
    {
        return isset(self::MESSAGES[$error]) ? self::MESSAGES[$error] : 'Oops! Something went wrong';
    }//error message

    //is this shop closed to the user because of its company? $shop needs ComStat and
    //ComExpireDate. Super admins are never kept out. Returns an ERR_COMPANY_* code or null.
    public static function unavailableReason(array $shop, $userType)
    {
        if($userType == 1)
        {
            return null;
        }//super admin

        if($shop['ComStat'] == 0)
        {
            return self::ERR_COMPANY_INACTIVE;
        }//company switched off

        if(date('Y-m-d') > $shop['ComExpireDate'])
        {
            return self::ERR_COMPANY_EXPIRED;
        }//licence over

        return null;
    }//unavailable reason

    //may this user enter this shop?
    public function canAccessShop($user_id, $shop_id)
    {
        return $this->findAccess($user_id, $shop_id) !== null;
    }//can access shop

    //the role this user holds in this shop; null for a super admin (not limited by roles) and
    //for a user who may not enter the shop
    public function getShopRoleId($user_id, $shop_id)
    {
        $access = $this->findAccess($user_id, $shop_id);
        if($access === null || $access['UserType'] == 1)
        {
            return null;
        }//no role applies

        return (int)$access['UserRoles_URID'];
    }//get shop role id

    //the shops offered on the shop screen, with their company's name and state
    public function getSelectableShops($user_id)
    {
        $user = $this->findActiveUser($user_id);
        if($user === null)
        {
            return [];
        }//no such active user

        $columns = "shop.SHID, shop.ShopName, company.CMID, company.ComName, company.ComStat, company.ComExpireDate";
        if($user['UserType'] == 1)
        {
            $sql = "SELECT " . $columns . " FROM shop
            INNER JOIN company ON company.CMID = shop.Company_CMID
            ORDER BY shop.SHID;";
            $params = [];
        }//every shop
        else
        {
            $sql = "SELECT " . $columns . " FROM shopusers
            INNER JOIN shop ON shop.SHID = shopusers.shop_SHID
            INNER JOIN company ON company.CMID = shop.Company_CMID
            INNER JOIN userroles ON userroles.URID = shopusers.UserRoles_URID
            WHERE shopusers.user_USID = ? AND shopusers.is_active = 1
            AND shop.ShopStat = 1 AND userroles.ur_status = 1
            ORDER BY shop.SHID;";
            $params = [$user['USID']];
        }//assigned shops

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }//get selectable shops

    //is this user kept out of an assigned, active shop only because the role they hold there is
    //inactive? Tells "your role is inactive" apart from "no shops" at sign in.
    public function hasInactiveRoleAssignment($user_id)
    {
        $sql = "SELECT 1 FROM shopusers
        INNER JOIN shop ON shop.SHID = shopusers.shop_SHID
        LEFT JOIN userroles ON userroles.URID = shopusers.UserRoles_URID
        WHERE shopusers.user_USID = ? AND shopusers.is_active = 1 AND shop.ShopStat = 1
        AND (userroles.URID IS NULL OR userroles.ur_status <> 1) LIMIT 1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }//has inactive role assignment

    //check a username and password for entering a shop. The user is looked up exactly as the
    //main login does (Controller/userController.php), so the same credentials work everywhere.
    //Returns ['ok' => true, 'user' => row, 'error' => null] or ['ok' => false, 'user' => null,
    //'error' => one of the ERR_* codes].
    public function authenticate($username, $password, $shop_id)
    {
        $username = is_string($username) ? $username : '';
        $password = is_string($password) ? $password : '';

        $user = null;
        if($username !== '')
        {
            $stmt = $this->connect()->prepare("SELECT * FROM user WHERE UserName = ? ORDER BY USID LIMIT 1;");
            $stmt->execute([$username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user = $row === false ? null : $row;
        }//look the user up

        //verified even for an unknown user, so both failures take the same time
        $valid = password_verify($password, $user !== null ? (string)$user['UserPwd'] : self::TIMING_HASH);
        if($user === null || !$valid)
        {
            return self::refuse(self::ERR_BAD_CREDENTIALS);
        }//unknown user or wrong password

        if($user['UserStat'] != 1)
        {
            return self::refuse(self::ERR_USER_INACTIVE);
        }//account switched off

        $shop_id = self::toId($shop_id);
        if($shop_id === null)
        {
            return self::refuse(self::ERR_NO_ACCESS);
        }//not a shop id

        $sql = "SELECT shop.ShopStat, company.ComStat, company.ComExpireDate,
        shopusers.is_active, userroles.ur_status
        FROM shop
        INNER JOIN company ON company.CMID = shop.Company_CMID
        LEFT JOIN shopusers ON shopusers.shop_SHID = shop.SHID AND shopusers.user_USID = ?
        LEFT JOIN userroles ON userroles.URID = shopusers.UserRoles_URID
        WHERE shop.SHID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user['USID'], $shop_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        if($shop === false)
        {
            return self::refuse(self::ERR_NO_ACCESS);
        }//no such shop

        if($user['UserType'] != 1)
        {
            if($shop['is_active'] != 1 || $shop['ShopStat'] != 1)
            {
                return self::refuse(self::ERR_NO_ACCESS);
            }//not assigned, revoked, or shop closed

            if($shop['ur_status'] != 1)
            {
                return self::refuse(self::ERR_ROLE_INACTIVE);
            }//role switched off or deleted

            $reason = self::unavailableReason($shop, $user['UserType']);
            if($reason !== null)
            {
                return self::refuse($reason);
            }//company closed
        }//not a super admin

        return ['ok' => true, 'user' => $user, 'error' => null];
    }//authenticate

    //------------------------------------------------------------------------------------------

    private static function refuse($error)
    {
        return ['ok' => false, 'user' => null, 'error' => $error];
    }//refuse

    //a positive integer id, or null
    private static function toId($value)
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }//to id

    private function findActiveUser($user_id)
    {
        $user_id = self::toId($user_id);
        if($user_id === null)
        {
            return null;
        }//not an id

        $stmt = $this->connect()->prepare("SELECT USID, UserType FROM user WHERE USID = ? AND UserStat = 1;");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user === false ? null : $user;
    }//find active user

    //the user's access to the shop (UserType and UserRoles_URID), or null when they may not
    //enter it - the one rule every check goes through
    private function findAccess($user_id, $shop_id)
    {
        $user_id = self::toId($user_id);
        $shop_id = self::toId($shop_id);
        if($user_id === null || $shop_id === null)
        {
            return null;
        }//not ids

        $sql = "SELECT user.UserType, shopusers.UserRoles_URID
        FROM user
        INNER JOIN shop ON shop.SHID = ?
        INNER JOIN company ON company.CMID = shop.Company_CMID
        LEFT JOIN shopusers ON shopusers.user_USID = user.USID AND shopusers.shop_SHID = shop.SHID
        LEFT JOIN userroles ON userroles.URID = shopusers.UserRoles_URID
        WHERE user.USID = ? AND user.UserStat = 1
        AND (user.UserType = 1
             OR (shopusers.is_active = 1 AND shop.ShopStat = 1 AND userroles.ur_status = 1))
        LIMIT 1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$shop_id, $user_id]);
        $access = $stmt->fetch(PDO::FETCH_ASSOC);
        return $access === false ? null : $access;
    }//find access

}//class shop access
```

- [ ] **Step 5: Run the tests** — `PHP tools/phpunit.phar`
Expected: all pass (≈30 tests).

- [ ] **Step 6: Commit and push**

```bash
git add Model/shop_access_class.php tests/
git commit -m "feat(access): ShopAccess holds every shop access rule and the shop login check"
git push origin development
```

---

### Task 4: Permission checks use the role held in the current shop

**Files:**
- Modify: `Includes/includes.php:10` (load `ShopAccess`), `Includes/remember_me.php:132-156` (`canAccessShop` delegates), `View/sidebar.php:28-29`, `View/right-sidebar.php:7`, `Includes/barcode_helper.php:958-969` (`bcUserRight`), `Model/user_class.php:222-233` (`getUserFeatureAccess`), `AJAX/Products/getProductSearch.php:25`
- Test: `tests/PermissionLookupTest.php`

**Interfaces:**
- Consumes: `ShopAccess::getShopRoleId`, `ShopAccess::canAccessShop` (Task 3).
- Produces: `User::getUserFeatureAccess($user_id, $feature_id, $shop_id)` (new third parameter); `bcUserRight()` resolves the role for `$_SESSION['shop_id']`.

- [ ] **Step 1: Write the failing tests** — `tests/PermissionLookupTest.php`:

```php
<?php
require_once __DIR__ . '/../Includes/barcode_helper.php';

final class PermissionLookupTest extends DatabaseTestCase
{
    private int $warehouse;
    private int $showroom;
    private int $alice;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $storeKeeper = $this->createRole('Store Keeper');
        $cashier = $this->createRole('Cashier');
        $this->grant($storeKeeper, 16, ['is_view', 'is_print']);   //Products feature
        $this->alice = $this->createUser('alice', 'x', $cashier);
        $this->assign($this->alice, $this->warehouse, $storeKeeper);
        $this->assign($this->alice, $this->showroom, $cashier);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_feature_rights_come_from_the_role_in_the_given_shop()
    {
        $user = new User();
        $inWarehouse = $user->getUserFeatureAccess($this->alice, 16, $this->warehouse);
        $inShowroom = $user->getUserFeatureAccess($this->alice, 16, $this->showroom);

        $this->assertSame('1', (string) $inWarehouse[0]['is_print']);
        $this->assertSame([], $inShowroom);
    }

    public function test_revoked_assignment_has_no_feature_rights()
    {
        $this->pdo->exec('UPDATE shopusers SET is_active = 0 WHERE shop_SHID = ' . $this->warehouse);
        $this->assertSame([], (new User())->getUserFeatureAccess($this->alice, 16, $this->warehouse));
    }

    public function test_barcode_rights_follow_the_current_shop()
    {
        $db = new DBTransactions();

        $_SESSION['shop_id'] = $this->warehouse;
        $this->assertTrue(bcUserCanPrint($db, $this->alice));

        $_SESSION['shop_id'] = $this->showroom;
        $this->assertFalse(bcUserCanPrint($db, $this->alice));

        unset($_SESSION['shop_id']);
        $this->assertFalse(bcUserCanPrint($db, $this->alice));
    }

    public function test_remember_me_uses_the_same_access_rule()
    {
        require_once __DIR__ . '/../Includes/remember_me.php';
        $this->pdo->exec('UPDATE shopusers SET is_active = 0 WHERE shop_SHID = ' . $this->showroom);

        $this->assertTrue((new RememberMe())->canAccessShop($this->alice, $this->warehouse));
        $this->assertFalse((new RememberMe())->canAccessShop($this->alice, $this->showroom));
    }
}
```

- [ ] **Step 2: Run to verify failure** — `PHP tools/phpunit.phar --filter PermissionLookupTest`
Expected: failures — `getUserFeatureAccess` still joins `user.UserRoles_URID` (showroom returns rows), `bcUserCanPrint` true in the showroom, `canAccessShop` true for the revoked showroom.

- [ ] **Step 3: `Model/user_class.php`** — replace `getUserFeatureAccess`:

```php
    //the user's rights on a feature in a shop - through the role they hold in that shop
    public function getUserFeatureAccess($user_id,$feature_id,$shop_id)
    {
        $sql = "SELECT RAID, is_create, is_edit, is_view, is_delete, is_verify, is_print, UserRolls_URID, SysFeatures_SFID FROM userroleaccess
        INNER JOIN userroles ON userroles.URID = userroleaccess.UserRolls_URID
        INNER JOIN shopusers ON shopusers.UserRoles_URID = userroleaccess.UserRolls_URID
        WHERE shopusers.user_USID = ? AND shopusers.shop_SHID = ? AND shopusers.is_active = 1
        AND userroles.ur_status = 1 AND SysFeatures_SFID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id,$shop_id,$feature_id]);
        $data = $stmt->fetchAll(); 
        
        return $data;
    }
```
and in `AJAX/Products/getProductSearch.php` change
`$userData = $userObj->getUserFeatureAccess($user_id,$feature_id);` to
`$userData = $userObj->getUserFeatureAccess($user_id,$feature_id,$shop_id); //role held in this shop`.

- [ ] **Step 4: `Includes/barcode_helper.php` `bcUserRight`** — replace

```php
        $role_id = (int) $rows[0]['UserRoles_URID'];
```
with
```php
        //rights come from the role held in the current shop (Model/shop_access_class.php)
        require_once __DIR__ . '/../Model/shop_access_class.php';
        $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 0;
        $role_id = (int) (new ShopAccess())->getShopRoleId($user_id, $shop_id);
        if ($role_id === 0) {
            return false;
        }//no access to this shop
```
(`$rows` still supplies `UserType` for the administrator short-circuit above it.)

- [ ] **Step 5: `Includes/remember_me.php`** — at the top, after the opening comment block and before `class RememberMe`, add

```php
require_once __DIR__ . '/../Model/shop_access_class.php';
```
and replace the whole `canAccessShop()` method (comment included) with:
```php
    //may this user open this shop? The one rule lives in ShopAccess (Model/shop_access_class.php):
    //UserType 1 opens every shop, everyone else only the active shops they hold an active role in.
    public function canAccessShop($user_id, $shop_id)
    {
        return (new ShopAccess())->canAccessShop($user_id, $shop_id);
    }//can access shop
```

- [ ] **Step 6: `Includes/includes.php`** — after `include "../Model/user_class.php";` add

```php
include_once "../Model/shop_access_class.php";
```

- [ ] **Step 7: `View/sidebar.php`** — replace

```php
if ($userType != 1) {
  $userRole_id = $user[0]['UserRoles_URID'];
```
with
```php
if ($userType != 1) {
  //the role this user holds in the current shop (Model/shop_access_class.php); 0 matches no rights
  $userRole_id = (int) (new ShopAccess())->getShopRoleId($user_id, $shop_id);
```

- [ ] **Step 8: `View/right-sidebar.php`** — replace `$userRole_id=$user[0]['UserRoles_URID'];` with

```php
//a super admin is not limited by roles and keeps their own; everyone else gets the role they
//hold in the current shop (Model/shop_access_class.php) - 0 matches no rights
$userRole_id = $userType == 1 ? $user[0]['UserRoles_URID'] : (int) (new ShopAccess())->getShopRoleId($user_id, $shop_id);
```

- [ ] **Step 9: Run all tests** — `PHP tools/phpunit.phar` → all pass.

- [ ] **Step 10: Smoke check in the browser session** — `curl -s -o /dev/null -w "%{http_code}\n" http://localhost/sleepmakers/Public/login.php` → `200`; `PHP -l` each modified file → `No syntax errors detected`.

- [ ] **Step 11: Commit and push**

```bash
git add Includes/includes.php Includes/remember_me.php View/sidebar.php View/right-sidebar.php Includes/barcode_helper.php Model/user_class.php AJAX/Products/getProductSearch.php tests/PermissionLookupTest.php
git commit -m "feat(access): permission checks use the role held in the current shop"
git push origin development
```

---

### Task 5: Shop login

**Files:**
- Create: `Includes/csrf.php`, `Includes/shop_session.php`, `tests/e2e/shop_login_e2e.php`
- Modify: `Controller/shopController.php:1-22` (`btn_continue` → `btn_shop_login`), `tests/bootstrap.php`
- Rewrite: `Public/dashboard.php`
- Test: `tests/CsrfTest.php`, `tests/ShopSessionTest.php`, E2E scenarios in `tests/e2e/shop_login_e2e.php`

**Interfaces:**
- Consumes: `ShopAccess::authenticate`, `getSelectableShops`, `unavailableReason`, `errorMessage` (Task 3); `RememberMe::rememberShop/forget` (existing).
- Produces: `csrf_token(): string`, `csrf_validate($token): bool` (uses `$_SESSION['csrf_token']`); `shop_session_enter(array &$session, array $user, $shop_id): bool` (true = a different user took over); session flash `$_SESSION['shop_login_error'] = ['shop_id' => int, 'username' => string, 'message' => string]`; POST contract `shopController.php`: `btn_shop_login`, `shop_id`, `user_name`, `user_pwd`, `csrf_token`.

- [ ] **Step 1: Write failing unit tests** — `tests/CsrfTest.php`:

```php
<?php
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void { $_SESSION = []; }
    protected function tearDown(): void { $_SESSION = []; }

    public function test_token_is_stable_within_a_session()
    {
        $token = csrf_token();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertSame($token, csrf_token());
    }

    public function test_only_the_sessions_token_validates()
    {
        $token = csrf_token();
        $this->assertTrue(csrf_validate($token));
        $this->assertFalse(csrf_validate(str_repeat('0', 64)));
        $this->assertFalse(csrf_validate(''));
        $this->assertFalse(csrf_validate(null));
        $this->assertFalse(csrf_validate(['x']));
    }

    public function test_nothing_validates_before_a_token_was_issued()
    {
        $this->assertFalse(csrf_validate(''));
        $this->assertFalse(csrf_validate(str_repeat('0', 64)));
    }
}
```
`tests/ShopSessionTest.php`:
```php
<?php
use PHPUnit\Framework\TestCase;

final class ShopSessionTest extends TestCase
{
    private function user($id)
    {
        return ['USID' => $id, 'UserName' => 'user' . $id, 'UserPwd' => 'hash', 'PwdChange' => 'token', 'UserType' => 0];
    }

    public function test_same_user_enters_the_shop_and_keeps_the_session()
    {
        $session = ['user_id' => 5, 'user' => [['USID' => 5]], 'remember_me' => 1, 'csrf_token' => 'abc'];

        $switched = shop_session_enter($session, $this->user(5), 2);

        $this->assertFalse($switched);
        $this->assertSame(2, $session['shop_id']);
        $this->assertSame(1, $session['remember_me']);
        $this->assertSame(1, $session['loading']);
        $this->assertSame(1, $session['toast']);
    }

    public function test_another_user_takes_over_with_a_clean_session()
    {
        $session = ['user_id' => 5, 'user' => [['USID' => 5]], 'remember_me' => 1, 'csrf_token' => 'abc', 'status' => 1];

        $switched = shop_session_enter($session, $this->user(7), '3');

        $this->assertTrue($switched);
        $this->assertSame(7, $session['user_id']);
        $this->assertSame('user7', $session['user'][0]['UserName']);
        $this->assertArrayNotHasKey('UserPwd', $session['user'][0]);
        $this->assertArrayNotHasKey('PwdChange', $session['user'][0]);
        $this->assertArrayNotHasKey('remember_me', $session);
        $this->assertArrayNotHasKey('status', $session);
        $this->assertSame('abc', $session['csrf_token']);
        $this->assertSame(3, $session['shop_id']);
    }

    public function test_works_on_the_real_session_array()
    {
        $_SESSION = ['user_id' => 5];
        shop_session_enter($_SESSION, $this->user(7), 4);
        $this->assertSame(7, $_SESSION['user_id']);
        $this->assertSame(4, $_SESSION['shop_id']);
        $_SESSION = [];
    }
}
```
Add to `tests/bootstrap.php` (before `DatabaseTestCase.php`):
```php
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../Includes/shop_session.php';
```

- [ ] **Step 2: Run to verify failure** — `PHP tools/phpunit.phar` → fails opening `Includes/csrf.php`.

- [ ] **Step 3: `Includes/csrf.php`**

```php
<?php
//Cross-site request forgery protection: one random token per session, sent with every form
//and AJAX call that changes something and compared in constant time, so another site cannot
//submit those forms on behalf of a signed-in user.
function csrf_token()
{
    if(empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']))
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }//first use in this session

    return $_SESSION['csrf_token'];
}//csrf token

function csrf_validate($token)
{
    return is_string($token)
        && isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) && $_SESSION['csrf_token'] !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}//csrf validate
```

- [ ] **Step 4: `Includes/shop_session.php`**

```php
<?php
//Enter a shop after a successful shop login (Controller/shopController.php).
//
//$session is $_SESSION, passed in so this can be tested. When a different person than the one
//signed in logs into the shop, nothing of the previous user's session carries over - it is as
//if they had signed out and in. Returns true in that case, so the caller can also clear the
//previous user's remember-me cookies.
function shop_session_enter(array &$session, array $user, $shop_id)
{
    $switched = !isset($session['user_id']) || (int)$session['user_id'] !== (int)$user['USID'];
    if($switched)
    {
        $csrf_token = isset($session['csrf_token']) ? $session['csrf_token'] : null;
        $session = array();
        if($csrf_token !== null)
        {
            $session['csrf_token'] = $csrf_token;
        }//the open forms stay valid

        unset($user['UserPwd'], $user['PwdChange']);
        $session['user_id'] = (int)$user['USID'];
        $session['user'] = array($user);
    }//another user takes over

    $session['shop_id'] = (int)$shop_id;
    $session['loading'] = 1;   //home.php shows the loading screen once
    $session['toast'] = 1;     //and its "Hi. Shop: ..." greeting
    return $switched;
}//shop session enter
```

- [ ] **Step 5: Run unit tests** — `PHP tools/phpunit.phar` → all pass.

- [ ] **Step 6: Write the E2E harness and the failing shop-login scenarios** — `tests/e2e/shop_login_e2e.php`:

```php
<?php
//End-to-end check of the shop login and per-shop access against a running copy of the
//application - by default the local XAMPP site:
//
//   C:/xampp/php/php.exe tests/e2e/shop_login_e2e.php [base-url]
//
//It creates its own company, shops, roles and users (all named "e2e..."), drives the site over
//HTTP like a browser (cookies, redirects, CSRF tokens) and removes everything it created at the
//end, even when a check fails. It uses the application's own database settings
//(Includes/config.php): run it only against a development database.
if (PHP_SAPI !== 'cli') {
    exit("Command line only.\n");
}//never from a browser

date_default_timezone_set('Asia/Colombo');
$base = rtrim(isset($argv[1]) ? $argv[1] : 'http://localhost/sleepmakers', '/');

ob_start(); //config.php starts with a byte order mark
require_once __DIR__ . '/../../Includes/config.php';
ob_end_clean();

class E2EDb extends Dbh
{
    public function pdo() { return $this->connect(); }
}//E2EDb

//a browser: one cookie jar, follows redirects, remembers where it ended up
class E2EBrowser
{
    private $base;
    private $jar;
    public $url = '';
    public $status = 0;
    public $body = '';

    public function __construct($base)
    {
        $this->base = $base;
        $this->jar = tempnam(sys_get_temp_dir(), 'e2e');
    }

    public function get($path) { return $this->request($path, null); }
    public function post($path, array $fields) { return $this->request($path, $fields); }

    private function request($path, $fields)
    {
        $curl = curl_init($this->base . '/' . ltrim($path, '/'));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($fields !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
        }
        //every page starts with the byte order mark of Includes/config.php - a browser drops it
        $this->body = preg_replace('/^\xEF\xBB\xBF/', '', (string) curl_exec($curl));
        $this->status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $this->url = (string) curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        curl_close($curl);
        return $this;
    }

    public function isOn($page) { return strpos(parse_url($this->url, PHP_URL_PATH), '/' . $page) !== false; }
    public function has($text) { return strpos($this->body, $text) !== false; }

    public function csrf()
    {
        return preg_match('/id="(?:shop_login|assign)_csrf_token"[^>]*value="([0-9a-f]{64})"/', $this->body, $m) ? $m[1] : '';
    }

    public function __destruct() { @unlink($this->jar); }
}//E2EBrowser

//the records this run creates, found again by name so a crashed run is cleaned up next time
class E2EFixtures
{
    private $pdo;
    public $password;
    public $company;
    public $shops = [];
    public $roles = [];
    public $users = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->password = 'E2e-' . bin2hex(random_bytes(6));
    }

    public function up()
    {
        $this->down();
        $this->pdo->prepare("INSERT INTO company (CompanyNo, ComName, ComExpireDate, ComStat, CompanyType_CTID)
            SELECT 'E2E', 'e2e Company', ?, 1, MIN(CTID) FROM companytype")
            ->execute([date('Y-m-d', strtotime('+1 year'))]);
        $this->company = (int) $this->pdo->lastInsertId();

        foreach (['W' => 'e2e Warehouse', 'S' => 'e2e Showroom'] as $key => $name) {
            $this->pdo->prepare("INSERT INTO shop (ShopNo, ShopName, WholesaleShop, RetailShop, is_prescription, ShopStat,
                Company_CMID, StockTypes_STID, emailAddress) VALUES (?, ?, 0, 1, 0, 1, ?, 1, 'e2e@example.com')")
                ->execute(['E2E' . $key, $name, $this->company]);
            $this->shops[$key] = (int) $this->pdo->lastInsertId();
        }

        //Store Keeper sees Store (module 1, feature 1); Cashier sees Invoice List (module 2, feature 56)
        foreach (['keeper' => ['e2e Store Keeper', 1, 1, 1], 'cashier' => ['e2e Cashier', 1, 2, 56], 'retired' => ['e2e Retired', 0, 2, 56]] as $key => $role) {
            $this->pdo->prepare("INSERT INTO userroles (UserRoleName, ur_status, added_by) VALUES (?, ?, 1)")->execute([$role[0], $role[1]]);
            $this->roles[$key] = (int) $this->pdo->lastInsertId();
            $this->pdo->prepare("INSERT INTO usermoduleaccess (SysModules_SMID, UserRoles_URID) VALUES (?, ?)")->execute([$role[2], $this->roles[$key]]);
            $this->pdo->prepare("INSERT INTO userroleaccess (is_create, is_edit, is_view, is_delete, is_verify, is_print, UserRolls_URID, SysFeatures_SFID)
                VALUES (0, 0, 1, 0, 0, 0, ?, ?)")->execute([$this->roles[$key], $role[3]]);
        }

        $hash = password_hash($this->password, PASSWORD_DEFAULT);
        foreach (['alice' => 0, 'bob' => 0, 'carol' => 0, 'admin' => 1] as $name => $type) {
            $this->pdo->prepare("INSERT INTO user (UserProfile, UserName, UserEmail, ContactNo, UserPwd, UserStat, UserRoles_URID, UserType)
                VALUES ('avator.svg', ?, ?, '0000000000', ?, 1, ?, ?)")
                ->execute(['e2e_' . $name, 'e2e_' . $name . '@example.com', $hash, $this->roles['cashier'], $type]);
            $this->users[$name] = (int) $this->pdo->lastInsertId();
        }

        //alice: Store Keeper in the warehouse, Cashier in the showroom. bob: only the showroom,
        //as Store Keeper. carol: only the warehouse, with a retired role.
        $this->assign('alice', 'W', 'keeper');
        $this->assign('alice', 'S', 'cashier');
        $this->assign('bob', 'S', 'keeper');
        $this->assign('carol', 'W', 'retired');
    }

    public function assign($user, $shop, $role, $active = 1)
    {
        $this->pdo->prepare("INSERT INTO shopusers (shop_SHID, user_USID, UserRoles_URID, is_active) VALUES (?, ?, ?, ?)")
            ->execute([$this->shops[$shop], $this->users[$user], $this->roles[$role], $active]);
    }

    public function setActive($user, $shop, $active)
    {
        $this->pdo->prepare("UPDATE shopusers SET is_active = ? WHERE user_USID = ? AND shop_SHID = ?")
            ->execute([$active, $this->users[$user], $this->shops[$shop]]);
    }

    public function suid($user, $shop)
    {
        $stmt = $this->pdo->prepare("SELECT SUID FROM shopusers WHERE user_USID = ? AND shop_SHID = ?");
        $stmt->execute([$this->users[$user], $this->shops[$shop]]);
        return (int) $stmt->fetchColumn();
    }

    public function lastLogin($user)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM userlog WHERE user_USID = ?");
        $stmt->execute([$this->users[$user]]);
        return (int) $stmt->fetchColumn();
    }

    public function down()
    {
        $users = "SELECT USID FROM user WHERE UserName LIKE 'e2e\\_%'";
        $roles = "SELECT URID FROM userroles WHERE UserRoleName LIKE 'e2e %'";
        $shops = "SELECT SHID FROM shop WHERE ShopName LIKE 'e2e %'";
        $this->pdo->exec("DELETE FROM shopusers WHERE user_USID IN ($users) OR shop_SHID IN ($shops)");
        $this->pdo->exec("DELETE FROM userlog WHERE user_USID IN ($users)");
        $this->pdo->exec("DELETE FROM userroleaccess WHERE UserRolls_URID IN ($roles)");
        $this->pdo->exec("DELETE FROM usermoduleaccess WHERE UserRoles_URID IN ($roles)");
        $this->pdo->exec("DELETE FROM user WHERE UserName LIKE 'e2e\\_%'");
        $this->pdo->exec("DELETE FROM userroles WHERE UserRoleName LIKE 'e2e %'");
        $this->pdo->exec("DELETE FROM shop WHERE ShopName LIKE 'e2e %'");
        $this->pdo->exec("DELETE FROM company WHERE ComName = 'e2e Company'");
    }
}//E2EFixtures

$failures = 0;
function check($label, $ok, E2EBrowser $browser = null)
{
    global $failures;
    echo ($ok ? "  PASS  " : "  FAIL  ") . $label . "\n";
    if (!$ok) {
        $failures++;
        if ($browser !== null) {
            echo "        at " . $browser->url . " (HTTP " . $browser->status . ")\n";
        }
    }
}//check

function signIn(E2EBrowser $browser, $user, $password)
{
    $browser->get('Public/logout.php');
    return $browser->post('Controller/userController.php', ['user_name' => $user, 'user_pwd' => $password, 'btn_log_in' => 'Sign In']);
}//signIn

function shopLogin(E2EBrowser $browser, $shop_id, $user, $password, $csrf = null)
{
    $browser->get('Public/dashboard.php');
    return $browser->post('Controller/shopController.php', [
        'btn_shop_login' => 'Sign In',
        'shop_id' => $shop_id,
        'user_name' => $user,
        'user_pwd' => $password,
        'csrf_token' => $csrf === null ? $browser->csrf() : $csrf,
    ]);
}//shopLogin

//a sidebar link only a role with that feature sees
function sees(E2EBrowser $browser, $page) { return $browser->has('href="../Public/' . $page); }

$fx = new E2EFixtures((new E2EDb())->pdo());
$fx->up();
$pw = $fx->password;
$W = $fx->shops['W'];
$S = $fx->shops['S'];

try {
    $b = new E2EBrowser($base);

    echo "Shop login\n";
    signIn($b, 'e2e_alice', $pw);
    check('alice with two shops lands on the shop screen', $b->isOn('Public/dashboard.php'), $b);
    check('both of her shops are offered', $b->has('e2e Warehouse') && $b->has('e2e Showroom'), $b);
    check('the shop login dialog is on the page', $b->has('id="shop_login_modal"') && $b->csrf() !== '', $b);

    shopLogin($b, $W, 'e2e_alice', 'wrong-password');
    check('wrong password is refused', $b->isOn('Public/dashboard.php') && $b->has('Invalid username or password'), $b);

    shopLogin($b, $W, 'e2e_bob', $pw);
    check('bob cannot log into a shop he is not assigned to', $b->has('You do not have access to this shop.'), $b);

    shopLogin($b, $W, 'e2e_alice', $pw, str_repeat('0', 64));
    check('a forged form (bad CSRF token) is refused', $b->has('Your session expired. Please try again.'), $b);

    $b->post('Controller/shopController.php', ['btn_continue' => '1', 'cmb_shops' => $W]);
    $b->get('Public/home.php');
    check('the old shop picker post no longer opens a shop', $b->isOn('Public/dashboard.php'), $b);

    shopLogin($b, $W, 'e2e_alice', $pw);
    check('alice logs into the warehouse', $b->isOn('Public/home.php') && $b->status === 200, $b);
    check('as Store Keeper there she sees Store', sees($b, 'store.php'), $b);

    $b->get('Public/switchshop.php');
    check('Switch Shop returns to the shop screen', $b->isOn('Public/dashboard.php'), $b);
    shopLogin($b, $S, 'e2e_alice', $pw);
    check('alice logs into the showroom', $b->isOn('Public/home.php'), $b);
    check('as Cashier there she does not see Store', !sees($b, 'store.php'), $b);

    $b->get('Public/switchshop.php');
    $before = $fx->lastLogin('bob');
    shopLogin($b, $S, 'e2e_bob', $pw);
    check('bob takes over the counter in the showroom', $b->isOn('Public/home.php'), $b);
    check('the session is now bob\'s (Store Keeper in the showroom sees Store)', sees($b, 'store.php'), $b);
    check('bob\'s login is logged', $fx->lastLogin('bob') === $before + 1);

    //scenarios of later tasks are added above this line
} finally {
    $fx->down();
}

echo $failures === 0 ? "\nAll checks passed.\n" : "\n" . $failures . " check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
```

- [ ] **Step 7: Run to verify failure** — `PHP tests/e2e/shop_login_e2e.php`
Expected: FAIL on the dialog, wrong password, CSRF, old-post and switch checks (the dashboard has no `shop_login_modal` and `btn_continue` still opens any shop).

- [ ] **Step 8: `Controller/shopController.php`** — replace the head of the file up to and including the `btn_continue` block (lines 1–22) with:

```php
<?php
include "../Includes/includes.php";
require_once "../Includes/remember_me.php";
require_once "../Includes/csrf.php";
require_once "../Includes/shop_session.php";
$shopObj = new Shop();


if (isset($_POST['btn_shop_login']))
{
    //entering a shop always takes a username and password - see db/SHOP_ACCESS_MODULE.md.
    //Whoever signs in here with access to the shop becomes the session's user.
    $shop_id = filter_var(isset($_POST['shop_id']) ? $_POST['shop_id'] : null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $username = isset($_POST['user_name']) && is_string($_POST['user_name']) ? $_POST['user_name'] : '';

    if(!isset($_SESSION['user_id']))
    {
        header("Location: ../Public/login.php");
        exit;
    }//signed out meanwhile

    if(!csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null) || $shop_id === false)
    {
        $_SESSION['shop_login_error'] = ['shop_id' => (int)$shop_id, 'username' => $username, 'message' => 'Your session expired. Please try again.'];
        header("Location: ../Public/dashboard.php");
        exit;
    }//stale or forged form

    $shopAccess = new ShopAccess();
    $result = $shopAccess->authenticate($username, isset($_POST['user_pwd']) ? $_POST['user_pwd'] : '', $shop_id);
    if(!$result['ok'])
    {
        $_SESSION['shop_login_error'] = ['shop_id' => $shop_id, 'username' => $username, 'message' => ShopAccess::errorMessage($result['error'])];
        header("Location: ../Public/dashboard.php");
        exit;
    }//refused: back to the shop screen, which reopens this shop's dialog

    $remember = isset($_SESSION["remember_me"]) || isset($_COOKIE["remember_meS"]);
    session_regenerate_id(true); //new privileges, new session id
    $switched = shop_session_enter($_SESSION, $result['user'], $shop_id);
    if($switched)
    {
        //a shared counter never stays remembered as the previous person
        RememberMe::forget();
        setcookie("remember_meS", "", time() - 3600, "/");
        $login_date_time = date("Y-m-d H:i:s");
        (new User())->setUserLog($login_date_time, $login_date_time, 1, $result['user']['USID']);
    }//another user took over
    elseif($remember)
    {
        //signed token, only for a shop this user may open - see Includes/remember_me.php
        (new RememberMe())->rememberShop($_SESSION['user_id'], $shop_id);
    }//same user, remember-me on

    header("Location: ../Public/home.php");
    exit;
}//log into a shop
```

- [ ] **Step 9: Rewrite `Public/dashboard.php`** (full file):

```php
<?php 
include '../Includes/includes.php';
include '../Includes/authcheck-dashboard.php';
require_once '../Includes/csrf.php';

//======================= Shop screen ====================//
/*
 * Lists the shops this user may enter. Entering one always takes a username and password:
 * the card opens the shop login dialog, which posts to Controller/shopController.php
 * (btn_shop_login). See db/SHOP_ACCESS_MODULE.md.
 */
if(isset($_SESSION['shop_id']))
{
  header("Location:../Public/home.php");
  exit;
}//already in a shop
else if(($remembered_shop_id = (new RememberMe())->shopFromCookie($_SESSION['user_id'])) !== null)
{
  //signed remember-me shop cookie, see Includes/remember_me.php
  $_SESSION['shop_id']=$remembered_shop_id;
  header("Location:../Public/home.php");
  exit;
}//remembered shop

$shopAccess = new ShopAccess();
$shops = $shopAccess->getSelectableShops($user_id);
if(empty($shops))
{
  $_SESSION['user_error'] = 9;
  unset($_SESSION['user_id'], $_SESSION['user']);
  header("Location: login.php");
  exit;
}//no shop to enter

$userType = $user[0]['UserType'];

//a refused shop login comes back here to reopen its dialog with the reason
$shop_login_error = isset($_SESSION['shop_login_error']) ? $_SESSION['shop_login_error'] : null;
unset($_SESSION['shop_login_error']);

//a value for inline JavaScript, safe inside a <script> block
function dashboard_js($value)
{
  return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}//dashboard js
?>
<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  ?>
  <style>
    @media (min-width: 1200px)
    {
      #main-wrapper[data-layout=vertical][data-header-position=fixed] .app-header {
          width: calc(100%);
      }
      #main-wrapper[data-layout=vertical][data-sidebartype=full] .body-wrapper {
      margin-left: 0;
      }
    }
    
    .synnex-shop:not(:disabled):hover 
    {
      background: #5d87ff ;
    }
    
    .synnex-shop:not(:disabled):hover .card-title
    {
      color: white !important;
    }
    .synnex-shop:disabled
    {
      background: grey !important;
      cursor: not-allowed;
    }
    #main-wrapper[data-layout=vertical][data-header-position=fixed] .body-wrapper>.container-fluid{
    padding-top: calc(182px + 15px);
    }
    .card {
    margin-bottom: 30px;
    background: #ffffffeb;
    }
    .app-header
    {
      background: none;
    }
    body
    {
      
    height: 100vh;
    background: url('../Assets/Images/icons/background 3.jpg');
    background-position: center;
    background-size: cover;
    }
    /* the shop login dialog looks like the sign in card (Public/login.php) */
    #shop_login_modal .modal-content
    {
      background: #ffffffe8;
    }
    .btn-sign-in
    {
      background-color: #0072bc !important;
    }
  </style>
</head>

<body>
  <div class="toast toast-onload align-items-center text-bg-primary border-0 fade" role="alert" aria-live="assertive" aria-atomic="true" id="toast">
    <div class="toast-body hstack align-items-start gap-6">
      <i class="ti ti-alert-circle fs-6"></i>
      <div>
        <h5 class="text-white fs-3 mb-1">Welcome to Synnex Cloud POS</h5>
        <h6 class="text-white fs-2 mb-0">Happiness is the key to Success.</h6>
      </div>
      <button type="button" class="btn-close btn-close-white fs-2 m-0 ms-auto shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!--  Main wrapper -->
    <div class="body-wrapper">
      <!--  Header Start -->
      <?php 
      include '../View/header-dashbord.php';
      ?>
      <!--  Header End -->
      <div class="container-fluid">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title fw-semibold mb-4"><?=htmlspecialchars($shops[0]['ComName'])?></h5>
            <p class="mb-0">Please Select Your Shop</p>

            <div class="m-3">
              <div class="container-fluid row">
                <?php 
                foreach ($shops as $shop) 
                {
                  $reason = ShopAccess::unavailableReason($shop, $userType);
                  ?>
                  <div class="col-md-3 p-1">
                    <button type="button" class="card synnex-shop" data-shop-id="<?=(int)$shop['SHID']?>" data-shop-name="<?=htmlspecialchars($shop['ShopName'])?>" <?=$reason !== null ? 'disabled' : ''?>>
                      <div class="w-100 p-2">
                        <div class="row">
                          <div class="col-md-12 " style="display:flex; justify-content:center;">
                            <img src="../Assets/Images/icons/shop.png" class="w-50" alt="">
                          </div>
                          <div class="col-md-12 m-2">
                            <h6 class="card-title mb-4 text-center" style="font-size:14px; color:<?=$reason !== null ? '#ff0000' : '#5d87ff'?>;">
                              <?=htmlspecialchars($shop['ShopName'])?>
                            </h6>
                          </div>
                          <?php 
                          if($reason !== null)
                          {
                            ?>
                            <div class="col-md-12 m-2">
                              <h6 class="card-title mb-4 text-center" style="font-size:14px; color:#ff0000;">
                                <b><?=ShopAccess::errorMessage($reason)?></b>
                              </h6>
                            </div>
                            <?php
                          }//company closed to this user
                          ?>
                        </div>
                      </div>
                    </button>
                  </div>
                  <?php
                }//foreach shop
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Shop login: same look as the sign in card on Public/login.php -->
  <div class="modal fade" id="shop_login_modal" tabindex="-1" aria-labelledby="shop_login_title" aria-hidden="true" style="background: #00000075;">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body p-4">
          <button type="button" class="btn-close float-end" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center py-3">
            <img src="../Assets/Images/synnex_logo.png" width="200" alt="">
          </div>
          <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
            <img src="../Assets/Images/icons/shop.png" width="32" alt="">
            <h5 class="mb-0 fw-semibold" id="shop_login_title">Sign in to <span id="shop_login_name"></span></h5>
          </div>
          <p class="text-center" id="shop_login_error" style="color:#ff0000; font-weight:bold; display:none;"></p>
          <form action="../Controller/shopController.php" method="POST">
            <input type="hidden" name="csrf_token" id="shop_login_csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="shop_id" id="shop_login_shop_id" value="">
            <div class="mb-3">
              <label for="shop_user_name" class="form-label">Username</label>
              <input type="text" name="user_name" class="form-control" id="shop_user_name" maxlength="50" autocomplete="username" required>
            </div>
            <div class="mb-4">
              <label for="shop_user_pwd" class="form-label">Password</label>
              <input type="password" class="form-control" id="shop_user_pwd" name="user_pwd" autocomplete="current-password" required>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div class="form-check">
                <input class="form-check-input primary" type="checkbox" id="shop_show_password">
                <label class="form-check-label text-dark" for="shop_show_password">
                  Show Password
                </label>
              </div>
            </div>
            <input type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-2 rounded-2 btn-sign-in" value="Sign In" name="btn_shop_login">
            <button type="button" class="btn bg-danger-subtle text-danger w-100 rounded-2" data-bs-dismiss="modal">Cancel</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../Assets/js/sidebarmenu.js"></script>
  <script src="../Assets/js/app.min.js"></script>
  <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="../Assets/jquery/toast.js"></script>
  <script>
    $(document).ready(function(){
      var modalEl = document.getElementById('shop_login_modal');
      var modal = new bootstrap.Modal(modalEl);
      var signedInUser = <?=dashboard_js($user_name)?>;

      function openShopLogin(shopId, shopName, username, error)
      {
        $('#shop_login_shop_id').val(shopId);
        $('#shop_login_name').text(shopName);
        $('#shop_user_name').val(username);
        $('#shop_user_pwd').val('').prop('type', 'password');
        $('#shop_show_password').prop('checked', false);
        if (error)
        {
          $('#shop_login_error').text(error).show();
        }
        else
        {
          $('#shop_login_error').hide().text('');
        }
        modal.show();
      }//open shop login

      $('.synnex-shop').on('click', function(){
        openShopLogin($(this).data('shop-id'), $(this).data('shop-name'), signedInUser, '');
      });

      modalEl.addEventListener('shown.bs.modal', function(){
        $($('#shop_user_name').val() ? '#shop_user_pwd' : '#shop_user_name').trigger('focus');
      });

      $('#shop_show_password').on('change', function(){
        $('#shop_user_pwd').prop('type', this.checked ? 'text' : 'password');
      });

      <?php 
      if($shop_login_error !== null)
      {
        ?>
        //the last shop login was refused: reopen it with the reason
        var refused = <?=dashboard_js($shop_login_error)?>;
        var card = $('.synnex-shop[data-shop-id="' + refused.shop_id + '"]');
        openShopLogin(refused.shop_id, card.length ? card.data('shop-name') : '', refused.username, refused.message);
        <?php
      }//refused shop login
      ?>
    });
  </script>
</body>

</html>
```

- [ ] **Step 10: Run the E2E and unit tests** — `PHP tests/e2e/shop_login_e2e.php` → `All checks passed.`; `PHP tools/phpunit.phar` → all pass.

- [ ] **Step 11: Look at it** — open `http://localhost/sleepmakers/Public/login.php`, sign in as an admin, click a shop card: the dialog shows the logo, *Sign in to <shop>*, Username (pre-filled), Password, Show Password, blue Sign In, Cancel; a wrong password reopens it with the red message.

- [ ] **Step 12: Commit and push**

```bash
git add Includes/csrf.php Includes/shop_session.php Controller/shopController.php Public/dashboard.php tests/
git commit -m "feat(shop-login): entering a shop takes a username and password"
git push origin development
```

---

### Task 6: Access is re-checked on every page and every poll; Switch Shop for everyone

**Files:**
- Modify: `Includes/authcheck.php:43-56`, `Includes/newauthcheck.php`, `Public/dashboard.php` (alert), `View/header.php:84-93`, `View/gui-header.php:103-112`, `tests/e2e/shop_login_e2e.php`

**Interfaces:**
- Consumes: `ShopAccess::canAccessShop` (Task 3), `RememberMe::forgetShop` (existing).
- Produces: session flash `$_SESSION['shop_access_error']` (string), shown once by `dashboard.php`.

- [ ] **Step 1: Add the failing E2E scenarios** — in `tests/e2e/shop_login_e2e.php` replace the line `//scenarios of later tasks are added above this line` with:

```php
    echo "Access re-checked\n";
    signIn($b, 'e2e_alice', $pw);
    shopLogin($b, $W, 'e2e_alice', $pw);
    $fx->setActive('alice', 'W', 0);
    $b->get('Public/home.php');
    check('revoked access sends alice back to the shop screen on her next click', $b->isOn('Public/dashboard.php'), $b);
    check('and tells her why', $b->has('Your access to this shop has been removed.'), $b);
    check('the revoked shop is no longer offered', !$b->has('e2e Warehouse') && $b->has('e2e Showroom'), $b);
    $fx->setActive('alice', 'W', 1);

    shopLogin($b, $W, 'e2e_alice', $pw);
    $fx->setActive('alice', 'W', 0);
    $b->post('Includes/newauthcheck.php', []);
    check('an idle screen\'s poll also reports the lost access (-1)', trim($b->body) === '-1', $b);
    $fx->setActive('alice', 'W', 1);

    signIn($b, 'e2e_alice', $pw);
    shopLogin($b, $W, 'e2e_alice', $pw);
    //the menu label, not the URL: the session poll's JavaScript names switchshop.php on every page
    check('Switch Shop is offered to a normal user', $b->has('>Switch Shop</p>'), $b);

    //scenarios of later tasks are added above this line
```

- [ ] **Step 2: Run to verify failure** — `PHP tests/e2e/shop_login_e2e.php` → the three *Access re-checked* checks and *Switch Shop* FAIL.

- [ ] **Step 3: `Includes/authcheck.php`** — after the `if(isset($_SESSION['shop_id'])) … else { … }//else goto dashboard` block, before `?>`, add:

```php

if(!(new ShopAccess())->canAccessShop($_SESSION['user_id'], $shop_id))
{
    //since the shop was entered its access was revoked, or the role, the shop or the user was
    //switched off: back to the shop screen - see db/SHOP_ACCESS_MODULE.md
    RememberMe::forgetShop();
    unset($_SESSION['shop_id']);
    $_SESSION['shop_access_error'] = "Your access to this shop has been removed.";
    header("Location: ../Public/dashboard.php");
    exit;
}//no longer allowed in this shop
```

- [ ] **Step 4: `Includes/newauthcheck.php`** — replace the final `echo $login;` with:

```php
if($login==1 && isset($_SESSION['shop_id']) && !$shopObjAccess->canAccessShop($_SESSION['user_id'], $_SESSION['shop_id']))
{
    //access to this shop revoked since it was entered: the page goes to switchshop.php
    $_SESSION['shop_access_error'] = "Your access to this shop has been removed.";
    $login=-1;
}//still allowed in this shop
echo $login;
```
and add near the top, after `include "../Includes/includes.php";`:
```php
$shopObjAccess = new ShopAccess();
```

- [ ] **Step 5: Show the message on the shop screen** — in `Public/dashboard.php`, right after `<div class="card-body">`, add:

```php
            <?php 
            if(isset($_SESSION['shop_access_error']))
            {
              ?>
              <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                <?=htmlspecialchars($_SESSION['shop_access_error'])?>
              </div>
              <?php
              unset($_SESSION['shop_access_error']);
            }//access removed while in a shop
            ?>
```

- [ ] **Step 6: Switch Shop for everyone** — in both `View/header.php` and `View/gui-header.php`, remove the `if($userType==1) { ?> … <?php }` wrapper around the Switch Shop link so the `<a href="../Public/switchshop.php" …>…Switch Shop</p></a>` block is always rendered. Replace, in each file,

```php
              <?php 
              if($userType==1)
              {
                ?>
                <a href="../Public/switchshop.php" class="d-flex align-items-center gap-2 dropdown-item" style="padding:5px 16px !important;">
                  <i class="ti ti-arrows-exchange-2" style="font-size:12px;"></i>
                  <p class="mb-0" style="font-size:12px;">Switch Shop</p>
                </a>
                <?php
              }
              ?>
```
with
```php
              <!-- every user: move to another of their shops, or hand the counter over (db/SHOP_ACCESS_MODULE.md) -->
              <a href="../Public/switchshop.php" class="d-flex align-items-center gap-2 dropdown-item" style="padding:5px 16px !important;">
                <i class="ti ti-arrows-exchange-2" style="font-size:12px;"></i>
                <p class="mb-0" style="font-size:12px;">Switch Shop</p>
              </a>
```

- [ ] **Step 7: Run** — `PHP tests/e2e/shop_login_e2e.php` → all pass; `PHP tools/phpunit.phar` → all pass.

- [ ] **Step 8: Commit and push**

```bash
git add Includes/authcheck.php Includes/newauthcheck.php Public/dashboard.php View/header.php View/gui-header.php tests/e2e/shop_login_e2e.php
git commit -m "feat(access): re-check shop access on every page and poll; Switch Shop for every user"
git push origin development
```

---

### Task 7: Main login uses the per-shop rules

**Files:**
- Modify: `Controller/userController.php` (the `elseif(isset($_POST['btn_log_in']))` block), `Public/login.php` (error 10), `tests/e2e/shop_login_e2e.php`

**Interfaces:**
- Consumes: `ShopAccess::getSelectableShops`, `hasInactiveRoleAssignment`, `unavailableReason`, `ERR_COMPANY_EXPIRED` (Task 3); `RememberMe::rememberUser/rememberShop`.
- Produces: `$_SESSION['user_error'] = 10` → *Company Inactive…* on the login page.

- [ ] **Step 1: Add the failing E2E scenarios** — above `//scenarios of later tasks are added above this line`:

```php
    echo "Main login\n";
    signIn($b, 'e2e_bob', $pw);
    check('bob, with one shop, goes straight into it', $b->isOn('Public/home.php'), $b);
    signIn($b, 'e2e_carol', $pw);
    check('carol, whose only role is inactive, is told so', $b->isOn('Public/login.php') && $b->has('Inactive userrole'), $b);
    $fx->setActive('bob', 'S', 0);
    signIn($b, 'e2e_bob', $pw);
    check('bob, whose only shop was revoked, has no shops', $b->isOn('Public/login.php') && $b->has('No shops assigned'), $b);
    $fx->setActive('bob', 'S', 1);
```

- [ ] **Step 2: Run to verify failure** — `PHP tests/e2e/shop_login_e2e.php` → carol FAILS: the old login checks only her default role (active) and `getCompanyByUser` ignores roles, so she is let in and Task 6's page check bounces her to *No shops assigned* instead of *Inactive userrole*. (Revoked bob already ends on *No shops assigned* through that same bounce; his check guards the direct path.)

- [ ] **Step 3: `Controller/userController.php`** — replace the whole `elseif(isset($_POST['btn_log_in'])) { … }//log into system` block with:

```php
elseif(isset($_POST['btn_log_in']))
{
    //fetch data
    $username = $_POST['user_name'];
    $userpwd = $_POST['user_pwd'];

    $userObj = new User();
    $data = $userObj->getUserByName($username);

    if(empty($data))
    {
        $_SESSION['user_error']=2;
        header("Location: ../Public/login.php");
    }//user not exist
    elseif($data[0]['UserStat']!=1)
    {
        $_SESSION['user_error'] = 8;
        header("Location: ../Public/login.php");
    }//user inactive
    elseif(!password_verify($userpwd, $data[0]['UserPwd']))
    {
        $_SESSION['user_error'] = 3;
        header("Location: ../Public/login.php");
    }//wrong password
    else
    {
        $logObj = new User();
        //update user log
        $logObj->editUserLogStat($data[0]['USID']);

        //the shops this user may enter, each through the role held there (Model/shop_access_class.php)
        $shopAccess = new ShopAccess();
        $shops = $shopAccess->getSelectableShops($data[0]['USID']);
        if(empty($shops))
        {
            //7: assigned, but the role they hold is inactive - 9: nothing to enter
            $_SESSION['user_error'] = $shopAccess->hasInactiveRoleAssignment($data[0]['USID']) ? 7 : 9;
            header("Location: ../Public/login.php");
        }//no shop to enter
        elseif(count($shops)==1 && ($reason = ShopAccess::unavailableReason($shops[0], $data[0]['UserType'])) !== null)
        {
            if($reason == ShopAccess::ERR_COMPANY_EXPIRED)
            {
                $_SESSION["expired"]=1;
            }
            else
            {
                $_SESSION['user_error'] = 10;
            }
            header("Location: ../Public/login.php");
        }//the only shop's company is closed
        else
        {
            session_regenerate_id(true); //a fresh session id for the signed in user
            $_SESSION['user_id'] = $data[0]['USID'];
            $_SESSION['user'] = $data;
            $login_date_time = date("Y-m-d H:i:s");
            $logObj->setUserLog($login_date_time, $login_date_time, 1, $data[0]['USID']);

            if(isset($_POST["remember_me"]))
            {
                $_SESSION["remember_me"]=1;
                setcookie('remember_meS', '1', time() + (30 * 24 * 60 * 60), "/"); 
                (new RememberMe())->rememberUser($data[0]['USID']); //signed token, see Includes/remember_me.php
            }//remember me

            if(count($shops)==1)
            {
                //one shop: the password just checked opens it - the shop screen would only ask again
                $_SESSION['shop_id'] = $shops[0]['SHID'];
                if(isset($_POST["remember_me"]))
                {
                    (new RememberMe())->rememberShop($data[0]['USID'], $shops[0]['SHID']);
                }//remembered with its shop
                header("Location: ../Public/home.php");
            }//single shop
            else
            {
                $_SESSION["toast"]=1;
                header("Location: ../Public/dashboard.php");
            }//choose a shop
        }//signed in
    }//user exists, active, right password
}//log into system
```

- [ ] **Step 4: `Public/login.php`** — after the `user_error==9` branch add:

```php
                  elseif ($_SESSION['user_error']==10) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000; font-weight:bold;">Company Inactive. Please Contact Synnex IT Solutions.</p>
                    <?php
                  }
```

- [ ] **Step 5: Run** — `PHP tests/e2e/shop_login_e2e.php` → all pass; `PHP tools/phpunit.phar` → all pass; `PHP -l Controller/userController.php Public/login.php` → no syntax errors.

- [ ] **Step 6: Commit and push**

```bash
git add Controller/userController.php Public/login.php tests/e2e/shop_login_e2e.php
git commit -m "feat(login): sign in offers only the shops the user may enter"
git push origin development
```

---

### Task 8: Admin screen — role per assignment, revoke/restore, hardened endpoint

**Files:**
- Modify: `Model/add_users_to_shops_class.php`, `Public/AssignUsersToShops.php`, `View/modals/add-user-features.php`, `tests/bootstrap.php`, `tests/e2e/shop_login_e2e.php`
- Rewrite: `Controller/AddUsersToShopsController.php`, `Assets/jquery/AddShops.js`
- Test: `tests/AddUsersModelsTest.php`

**Interfaces:**
- Consumes: `csrf_token/csrf_validate` (Task 5), migrated `shopusers`.
- Produces (`AddUsersModels`): `assignUser($shop_SHID, $user_USID, $role_id): string` (`'assigned'`|`'exists'`), `updateRole($SUID, $role_id): void`, `setActive($SUID, $active): void`, `isActiveRole($role_id): bool`, `shopExists($shop_id): bool`, `userExists($user_id): bool`, `getActiveRoles(): array`, `getAssignedUsers()` now returns `SUID, shop_SHID, user_USID, UserRoles_URID, is_active, ShopName, UserName, UserRoleName`, `getUsers()` also returns `UserRoles_URID`. Endpoint contract: POST `action` ∈ {`save`, `update_role`, `set_active`, `delete`} + `csrf_token` → JSON `{ok, message}`.

- [ ] **Step 1: Write the failing model tests** — `tests/AddUsersModelsTest.php`:

```php
<?php
final class AddUsersModelsTest extends DatabaseTestCase
{
    private AddUsersModels $model;
    private int $shop;
    private int $cashier;
    private int $alice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new AddUsersModels();
        $this->shop = $this->createShop($this->createCompany(), ['ShopName' => 'Warehouse']);
        $this->cashier = $this->createRole('Cashier');
        $this->alice = $this->createUser('alice', 'x', $this->cashier);
    }

    private function row($suid)
    {
        return $this->pdo->query('SELECT UserRoles_URID, is_active FROM shopusers WHERE SUID = ' . (int) $suid)->fetch(PDO::FETCH_ASSOC);
    }

    public function test_assigns_a_user_once_with_the_chosen_role()
    {
        $keeper = $this->createRole('Store Keeper');

        $this->assertSame('assigned', $this->model->assignUser($this->shop, $this->alice, $keeper));
        $this->assertSame('exists', $this->model->assignUser($this->shop, $this->alice, $this->cashier));

        $rows = $this->pdo->query('SELECT UserRoles_URID, is_active FROM shopusers')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertSame([['UserRoles_URID' => (string) $keeper, 'is_active' => '1']], $rows);
    }

    public function test_changes_the_role_and_toggles_access()
    {
        $keeper = $this->createRole('Store Keeper');
        $suid = $this->assign($this->alice, $this->shop, $this->cashier);

        $this->model->updateRole($suid, $keeper);
        $this->model->setActive($suid, 0);
        $this->assertSame(['UserRoles_URID' => (string) $keeper, 'is_active' => '0'], $this->row($suid));

        $this->model->setActive($suid, 1);
        $this->assertSame('1', $this->row($suid)['is_active']);
    }

    public function test_only_active_roles_are_offered_and_accepted()
    {
        $retired = $this->createRole('Retired', false);

        $this->assertTrue($this->model->isActiveRole($this->cashier));
        $this->assertFalse($this->model->isActiveRole($retired));
        $this->assertFalse($this->model->isActiveRole(999));
        $this->assertSame(['Cashier'], array_column($this->model->getActiveRoles(), 'UserRoleName'));
    }

    public function test_knows_which_shops_and_users_exist()
    {
        $this->assertTrue($this->model->shopExists($this->shop));
        $this->assertFalse($this->model->shopExists(999));
        $this->assertTrue($this->model->userExists($this->alice));
        $this->assertFalse($this->model->userExists(999));
    }

    public function test_lists_assignments_with_role_and_access()
    {
        $this->assign($this->alice, $this->shop, $this->cashier, false);

        $rows = $this->model->getAssignedUsers();

        $this->assertSame('Warehouse', $rows[0]['ShopName']);
        $this->assertSame('alice', $rows[0]['UserName']);
        $this->assertSame('Cashier', $rows[0]['UserRoleName']);
        $this->assertSame('0', (string) $rows[0]['is_active']);
    }

    public function test_users_come_with_their_default_role()
    {
        $users = $this->model->getUsers();
        $this->assertSame((string) $this->cashier, (string) $users[0]['UserRoles_URID']);
    }
}
```
Add to `tests/bootstrap.php` (before `DatabaseTestCase.php`):
```php
require_once __DIR__ . '/../Model/add_users_to_shops_class.php';
```

- [ ] **Step 2: Run to verify failure** — `PHP tools/phpunit.phar --filter AddUsersModelsTest` → `Call to undefined method AddUsersModels::assignUser()`.

- [ ] **Step 3: `Model/add_users_to_shops_class.php`** — replace `getUsers()` and `getAssignedUsers()` with the versions below, and add the new methods after `setUserModels()` (the write methods throw `PDOException`; `Controller/AddUsersToShopsController.php` turns that into a JSON answer):

```php
    //assign a user to a shop with the role they will hold there. Returns 'assigned', or
    //'exists' when they are already assigned to that shop (change the role with updateRole)
    public function assignUser($shop_SHID, $user_USID, $role_id)
    {
        $pdo = $this->connect();
        $check = $pdo->prepare("SELECT COUNT(*) FROM shopusers WHERE shop_SHID = ? AND user_USID = ?");
        $check->execute([$shop_SHID, $user_USID]);
        if ($check->fetchColumn() > 0) {
            return 'exists';
        }//already assigned

        try {
            $stmt = $pdo->prepare("INSERT INTO shopusers (shop_SHID, user_USID, UserRoles_URID, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$shop_SHID, $user_USID, $role_id]);
        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                return 'exists';
            }//assigned by someone else a moment ago (uq_shopusers_shop_user)
            throw $e;
        }//catch
        return 'assigned';
    }//assignUser

    //the role the user holds in the shop of this assignment
    public function updateRole($SUID, $role_id)
    {
        $stmt = $this->connect()->prepare("UPDATE shopusers SET UserRoles_URID = ? WHERE SUID = ?");
        $stmt->execute([$role_id, $SUID]);
    }//updateRole

    //revoke (0) or restore (1) access; the assignment and its history stay
    public function setActive($SUID, $active)
    {
        $stmt = $this->connect()->prepare("UPDATE shopusers SET is_active = ? WHERE SUID = ?");
        $stmt->execute([$active ? 1 : 0, $SUID]);
    }//setActive

    public function isActiveRole($role_id)
    {
        $stmt = $this->connect()->prepare("SELECT 1 FROM userroles WHERE URID = ? AND ur_status = 1");
        $stmt->execute([$role_id]);
        return $stmt->fetch() !== false;
    }//isActiveRole

    public function shopExists($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT 1 FROM shop WHERE SHID = ?");
        $stmt->execute([$shop_id]);
        return $stmt->fetch() !== false;
    }//shopExists

    public function userExists($user_id)
    {
        $stmt = $this->connect()->prepare("SELECT 1 FROM user WHERE USID = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch() !== false;
    }//userExists

    //the roles an assignment may be given
    public function getActiveRoles()
    {
        $stmt = $this->connect()->prepare("SELECT URID, UserRoleName FROM userroles WHERE ur_status = 1 ORDER BY UserRoleName");
        $stmt->execute();
        return $stmt->fetchAll();
    }//getActiveRoles
```
```php
    public function getUsers()
    {
        try {
            //UserRoles_URID: the user's default role, offered when they are added to a shop
            $stmt = $this->connect()->prepare("SELECT USID, UserName, UserRoles_URID FROM user");
            $stmt->execute();
            $Users = $stmt->fetchAll();
            return $Users;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Users. " . $e->getMessage());
        }
    }

    public function getAssignedUsers()
    {
        try {
            $stmt = $this->connect()->prepare("SELECT su.SUID, su.shop_SHID, su.user_USID, su.UserRoles_URID, su.is_active,
                s.ShopName, u.UserName, ur.UserRoleName
                FROM shopusers su
                INNER JOIN shop s ON s.SHID = su.shop_SHID
                INNER JOIN user u ON u.USID = su.user_USID
                LEFT JOIN userroles ur ON ur.URID = su.UserRoles_URID
                ORDER BY s.ShopName, u.UserName");
            $stmt->execute();
            $Users = $stmt->fetchAll();
            return $Users;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Users. " . $e->getMessage());
        }
    }
```

- [ ] **Step 4: Run model tests** — `PHP tools/phpunit.phar` → all pass.

- [ ] **Step 5: Add the failing endpoint E2E scenarios** — above `//scenarios of later tasks are added above this line`:

```php
    echo "Assign Users to Shops\n";
    $endpoint = 'Controller/AddUsersToShopsController.php';
    signIn($b, 'e2e_alice', $pw);
    shopLogin($b, $W, 'e2e_alice', $pw);
    $b->post($endpoint, ['action' => 'set_active', 'suid' => $fx->suid('alice', 'W'), 'active' => 0]);
    check('a normal user cannot change shop access (403)', $b->status === 403, $b);

    signIn($b, 'e2e_admin', $pw);
    shopLogin($b, $W, 'e2e_admin', $pw);
    $b->get('Public/AssignUsersToShops.php');
    $token = $b->csrf();
    check('the admin screen shows role and access columns', $b->has('<th>Role</th>') && $b->has('<th>Access</th>') && $token !== '', $b);

    $b->post($endpoint, ['action' => 'save', 'shop_id' => $W, 'user_id' => $fx->users['bob'], 'role_id' => $fx->roles['cashier']]);
    check('a request without the CSRF token is refused (400)', $b->status === 400, $b);

    $b->post($endpoint, ['action' => 'save', 'shop_id' => $W, 'user_id' => $fx->users['bob'], 'role_id' => $fx->roles['cashier'], 'csrf_token' => $token]);
    check('admin assigns bob to the warehouse as Cashier', $b->status === 200 && json_decode($b->body, true)['ok'] === true, $b);
    $b->post($endpoint, ['action' => 'save', 'shop_id' => $W, 'user_id' => $fx->users['bob'], 'role_id' => $fx->roles['keeper'], 'csrf_token' => $token]);
    check('assigning him twice is refused', $b->status === 409, $b);
    $b->post($endpoint, ['action' => 'save', 'shop_id' => $S, 'user_id' => $fx->users['carol'], 'role_id' => $fx->roles['retired'], 'csrf_token' => $token]);
    check('an inactive role cannot be given', $b->status === 422, $b);

    $bobW = $fx->suid('bob', 'W');
    $b->post($endpoint, ['action' => 'update_role', 'suid' => $bobW, 'role_id' => $fx->roles['keeper'], 'csrf_token' => $token]);
    check('admin changes bob\'s warehouse role', json_decode($b->body, true)['ok'] === true, $b);
    $b->post($endpoint, ['action' => 'set_active', 'suid' => $bobW, 'active' => 0, 'csrf_token' => $token]);
    check('admin revokes bob\'s warehouse access', json_decode($b->body, true)['message'] === 'Access revoked.', $b);

    $bob = new E2EBrowser($base);
    signIn($bob, 'e2e_bob', $pw);
    check('bob is back to one shop and goes straight in', $bob->isOn('Public/home.php'), $bob);

    $b->post($endpoint, ['action' => 'delete', 'suid' => $bobW, 'csrf_token' => $token]);
    check('an assignment without history can be deleted', json_decode($b->body, true)['ok'] === true && $fx->suid('bob', 'W') === 0, $b);
```

- [ ] **Step 6: Run to verify failure** — `PHP tests/e2e/shop_login_e2e.php` → the *Assign Users to Shops* checks FAIL (no auth, no JSON, no role column).

- [ ] **Step 7: Rewrite `Controller/AddUsersToShopsController.php`**

```php
<?php
//Settings -> Assign Users to Shops (Public/AssignUsersToShops.php, Assets/jquery/AddShops.js).
//Decides who may enter which shop and with which role - see db/SHOP_ACCESS_MODULE.md.
//Only a signed-in super admin may use it, every request carries the page's CSRF token, and
//every answer is JSON: {"ok": true|false, "message": "..."}.
include "../Includes/includes.php";
require_once "../Includes/csrf.php";

header('Content-Type: application/json; charset=utf-8');

function assign_respond($ok, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}//respond

//a posted positive integer id, or null
function assign_post_id($key)
{
    $id = filter_var(isset($_POST[$key]) ? $_POST[$key] : null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}//posted id

$signedIn = isset($_SESSION['user_id']) ? (new User())->getOneUser($_SESSION['user_id']) : [];
if(empty($signedIn) || $signedIn[0]['UserType'] != 1 || $signedIn[0]['UserStat'] != 1)
{
    assign_respond(false, 'Only a system admin can change shop access.', 403);
}//not a super admin

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null))
{
    assign_respond(false, 'Your session expired. Please reload the page and try again.', 400);
}//not a form post from our page

$assignObj = new AddUsersModels();
$action = isset($_POST['action']) ? $_POST['action'] : '';

try
{
    if($action === 'save')
    {
        $shop_id = assign_post_id('shop_id');
        $user_id = assign_post_id('user_id');
        $role_id = assign_post_id('role_id');
        if($shop_id === null || $user_id === null || $role_id === null)
        {
            assign_respond(false, 'Please select a shop, a user and a role.', 422);
        }//missing field
        if(!$assignObj->isActiveRole($role_id))
        {
            assign_respond(false, 'Please select an active role.', 422);
        }//inactive or unknown role
        if(!$assignObj->shopExists($shop_id) || !$assignObj->userExists($user_id))
        {
            assign_respond(false, 'That shop or user no longer exists.', 422);
        }//stale page
        if($assignObj->assignUser($shop_id, $user_id, $role_id) === 'exists')
        {
            assign_respond(false, 'User already assigned to this shop. Use Edit to change the role.', 409);
        }//duplicate
        assign_respond(true, 'User assigned.');
    }//add an assignment

    $suid = assign_post_id('suid');
    if($suid === null || $assignObj->getUserShopData($suid) === false)
    {
        assign_respond(false, 'Assignment not found. Please reload the page.', 404);
    }//every other action works on an existing assignment

    if($action === 'update_role')
    {
        $role_id = assign_post_id('role_id');
        if($role_id === null || !$assignObj->isActiveRole($role_id))
        {
            assign_respond(false, 'Please select an active role.', 422);
        }//inactive or unknown role
        $assignObj->updateRole($suid, $role_id);
        assign_respond(true, 'Role updated.');
    }//change the role in that shop
    elseif($action === 'set_active')
    {
        $active = isset($_POST['active']) ? (string)$_POST['active'] : '';
        if($active !== '0' && $active !== '1')
        {
            assign_respond(false, 'Unknown access state.', 422);
        }//not 0/1
        $assignObj->setActive($suid, $active === '1');
        assign_respond(true, $active === '1' ? 'Access restored.' : 'Access revoked.');
    }//revoke or restore
    elseif($action === 'delete')
    {
        if($assignObj->checkUserDelete($suid) !== "User can be deleted.")
        {
            assign_respond(false, 'This user has transactions in this shop, so the assignment cannot be deleted. Use Revoke to remove access.', 409);
        }//history in that shop
        $assignObj->deleteUserShop($suid);
        assign_respond(true, 'Assignment deleted.');
    }//delete an unused assignment

    assign_respond(false, 'Unknown action.', 400);
}
catch(PDOException $e)
{
    error_log("AddUsersToShopsController: " . $e->getMessage());
    assign_respond(false, 'Could not save the change. Please try again.', 500);
}//database error
```

- [ ] **Step 8: `View/modals/add-user-features.php`** (full file):

```php
<!-- Settings -> Assign Users to Shops: add an assignment, or edit the role of one (Assets/jquery/AddShops.js) -->
<div id="SysFeature_modal" class="modal fade show" tabindex="-1" aria-labelledby="assign_modal_title" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="assign_modal_title">Add Users</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userShopForm">
                    <input type="hidden" id="assign_suid" value="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="m-2">
                                <label class="form-label" for="ShopName">Select a Shop</label>
                                <select id="ShopName" class="form-control mb-2" required>
                                    <option value="">Select a Shop</option>
                                    <?php
                                    $ShopObj = new AddUsersModels();
                                    foreach ($ShopObj->getShops() as $Shop): ?>
                                        <option value="<?php echo $Shop['SHID']; ?>"><?php echo htmlspecialchars($Shop['ShopName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="m-2">
                                <label class="form-label" for="UserName">User</label>
                                <select id="UserName" class="form-control mb-2" required>
                                    <option value="">Select a User</option>
                                    <?php
                                    foreach ($ShopObj->getUsers() as $User): ?>
                                        <option value="<?php echo $User['USID']; ?>" data-default-role="<?php echo $User['UserRoles_URID']; ?>"><?php echo htmlspecialchars($User['UserName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="m-2">
                                <label class="form-label" for="RoleName">Role in this Shop</label>
                                <select id="RoleName" class="form-control mb-2" required>
                                    <option value="">Select a Role</option>
                                    <?php
                                    foreach ($ShopObj->getActiveRoles() as $Role): ?>
                                        <option value="<?php echo $Role['URID']; ?>"><?php echo htmlspecialchars($Role['UserRoleName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <p class="text-danger fw-bold m-2" id="assign_error" style="display:none;"></p>
                    <div class="modal-footer">
                        <button type="submit" class="btn bg-primary-subtle text-primary waves-effect" id="btn_submit_shops">Save</button>
                        <button type="button" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 9: `Public/AssignUsersToShops.php`** — add `require_once "../Includes/csrf.php";` after the `authcheck.php` include, and replace the `<h5 …>Assign Users to Shops</h5>` … closing `</table>` part with:

```php
                    <h5 class="card-title fw-semibold mb-4">Assign Users to Shops</h5>
                    <p class="mb-3">Each row lets one user into one shop, with the role they hold in that shop. Revoke blocks access but keeps the row and its history.</p>

                    <button type="button" class="btn btn-primary rounded-pill ml-1 mb-2"
                        id="btn_Add_SysFeature_modal">Add New Users</button>
                    <input type="hidden" id="assign_csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                    <br>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table search-table align-middle text-nowrap"
                                            id="tbl_active_store">
                                            <thead class="header-item">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Shop</th>
                                                    <th>User</th>
                                                    <th>Role</th>
                                                    <th>Access</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $ShopObj = new AddUsersModels();
                                                foreach ($ShopObj->getAssignedUsers() as $Shop): ?>
                                                    <tr data-suid="<?php echo $Shop['SUID']; ?>" data-shop-id="<?php echo $Shop['shop_SHID']; ?>"
                                                        data-user-id="<?php echo $Shop['user_USID']; ?>" data-role-id="<?php echo $Shop['UserRoles_URID']; ?>">
                                                        <td><?php echo $Shop['SUID']; ?></td>
                                                        <td><?php echo htmlspecialchars($Shop['ShopName']); ?></td>
                                                        <td><?php echo htmlspecialchars($Shop['UserName']); ?></td>
                                                        <td><?php echo $Shop['UserRoleName'] !== null ? htmlspecialchars($Shop['UserRoleName']) : '<span class="text-danger">No role</span>'; ?></td>
                                                        <td>
                                                            <?php if ($Shop['is_active'] == 1) { ?>
                                                                <span class="mb-1 badge text-bg-success">Active</span>
                                                            <?php } else { ?>
                                                                <span class="mb-1 badge bg-danger">Revoked</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <a href="javascript:void(0)" class="btn-edit-assignment me-2" title="Edit Role"><i class="ti ti-edit"></i></a>
                                                            <?php if ($Shop['is_active'] == 1) { ?>
                                                                <a href="javascript:void(0)" class="btn-set-active text-warning me-2" data-active="0" title="Revoke Access"><i class="ti ti-user-off"></i></a>
                                                            <?php } else { ?>
                                                                <a href="javascript:void(0)" class="btn-set-active text-success me-2" data-active="1" title="Restore Access"><i class="ti ti-user-check"></i></a>
                                                            <?php } ?>
                                                            <a href="javascript:void(0)" class="btn-delete text-danger" title="Delete"><i class="ti ti-trash"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
```

- [ ] **Step 10: Rewrite `Assets/jquery/AddShops.js`**

```js
//Settings -> Assign Users to Shops. Every change goes to Controller/AddUsersToShopsController.php,
//which answers {ok, message} JSON - see db/SHOP_ACCESS_MODULE.md.
$(document).ready(function () {
    var modal = $("#SysFeature_modal");

    function send(data, done) {
        data.csrf_token = $("#assign_csrf_token").val();
        $.ajax({
            type: 'POST',
            url: '../Controller/AddUsersToShopsController.php',
            data: data,
            dataType: 'json'
        }).done(function (response) {
            done(response);
        }).fail(function (xhr) {
            done(xhr.responseJSON || { ok: false, message: 'Could not reach the server. Please try again.' });
        });
    }//send

    function showError(message) {
        $("#assign_error").text(message).toggle(!!message);
    }//showError

    $('#btn_Add_SysFeature_modal').click(function () {
        $("#assign_modal_title").text("Add Users");
        $("#assign_suid").val('');
        $("#ShopName, #UserName").prop('disabled', false).val('');
        $("#RoleName").val('');
        showError('');
        modal.modal('show');
    });

    //a new assignment starts from the user's default role
    $("#UserName").on('change', function () {
        var role = String($(this).find(':selected').data('default-role') || '');
        if (role && $("#RoleName option[value='" + role + "']").length) {
            $("#RoleName").val(role);
        }
    });

    $(".btn-edit-assignment").click(function () {
        var row = $(this).closest('tr');
        $("#assign_modal_title").text("Edit Shop Role");
        $("#assign_suid").val(row.data('suid'));
        $("#ShopName").val(String(row.data('shop-id'))).prop('disabled', true);
        $("#UserName").val(String(row.data('user-id'))).prop('disabled', true);
        $("#RoleName").val(String(row.data('role-id')));
        showError('');
        modal.modal('show');
    });

    $("#userShopForm").on('submit', function (e) {
        e.preventDefault();
        var suid = $("#assign_suid").val();
        var data = suid
            ? { action: 'update_role', suid: suid, role_id: $("#RoleName").val() }
            : { action: 'save', shop_id: $("#ShopName").val(), user_id: $("#UserName").val(), role_id: $("#RoleName").val() };
        send(data, function (response) {
            if (response.ok) {
                location.reload();
            } else {
                showError(response.message);
            }
        });
    });

    $(".btn-set-active").click(function () {
        var row = $(this).closest('tr');
        var active = String($(this).data('active'));
        var question = active === '1'
            ? "Restore this user's access to the shop?"
            : "Revoke this user's access to the shop? They leave it on their next click.";
        if (!confirm(question)) {
            return;
        }
        send({ action: 'set_active', suid: row.data('suid'), active: active }, function (response) {
            alert(response.message);
            if (response.ok) {
                location.reload();
            }
        });
    });

    $(".btn-delete").click(function (e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        if (!confirm("Delete this assignment?")) {
            return;
        }
        send({ action: 'delete', suid: row.data('suid') }, function (response) {
            alert(response.message);
            if (response.ok) {
                location.reload();
            }
        });
    });
});
```

- [ ] **Step 11: Run** — `PHP tests/e2e/shop_login_e2e.php` → all pass; `PHP tools/phpunit.phar` → all pass.

- [ ] **Step 12: Look at it** — as admin open *Settings → Assign Users to Shops*: Role and Access columns, Edit opens the dialog with shop/user locked, Revoke/Restore/Delete answer with a message.

- [ ] **Step 13: Commit and push**

```bash
git add Model/add_users_to_shops_class.php Controller/AddUsersToShopsController.php Public/AssignUsersToShops.php View/modals/add-user-features.php Assets/jquery/AddShops.js tests/
git commit -m "feat(admin): assign a role per shop, revoke and restore access; secure the endpoint"
git push origin development
```

---

### Task 9: Users page — Default Role

**Files:**
- Modify: `Public/users.php:148`, `View/modals/add-user.php:56`, `View/modals/edit-user.php:44`, `Controller/userController.php` (`add-user` branch), `Model/add_users_to_shops_class.php` (remove `setUserModels`)

**Interfaces:**
- Consumes: `AddUsersModels::assignUser` (Task 8).

- [ ] **Step 1: Labels** — `Public/users.php`: `<th>User Role</th>` → `<th>Default Role</th>`. In `View/modals/add-user.php` and `View/modals/edit-user.php` replace the label text `Select User Role` (keep the `<span class="text-danger">*</span>`) with `Default Role`, and add directly after each role `</select>`:

```php
                                <small class="text-muted">Rights in each shop are set under Settings &rarr; Assign Users to Shops.</small>
```

- [ ] **Step 2: New user joins the current shop with their default role** — in `Controller/userController.php` `add-user` branch replace

```php
                                $shopUserObj->setUserModels($_SESSION['shop_id'], $add_users);
```
with
```php
                                //joins the shop they were created from, with their default role
                                $shopUserObj->assignUser($_SESSION['shop_id'], $add_users, $userRole);
```
and delete the now unused `setUserModels()` method from `Model/add_users_to_shops_class.php`. Confirm no caller remains: `grep -rn "setUserModels" --include=*.php . | grep -v vendor` → no output.

- [ ] **Step 3: Verify** — `PHP -l` on the four PHP files → no syntax errors; `PHP tools/phpunit.phar` and `PHP tests/e2e/shop_login_e2e.php` → all pass. In the browser (admin): *Users* shows *Default Role*; add a user → they appear under *Assign Users to Shops* for the current shop with the chosen role.

- [ ] **Step 4: Commit and push**

```bash
git add Public/users.php View/modals/add-user.php View/modals/edit-user.php Controller/userController.php Model/add_users_to_shops_class.php
git commit -m "feat(users): user role becomes Default Role; new users join the shop with it"
git push origin development
```

---

### Task 10: Final verification and docs

**Files:**
- Modify: `README.md` (Usage + Administration), `docs/superpowers/specs/2026-09-22-shop-login-and-per-shop-access-design.md` (spec additions), memory `server-deployment.md`

- [ ] **Step 1: Full test run** — `PHP tools/phpunit.phar` and `PHP tests/e2e/shop_login_e2e.php`; both green. Record the counts.

- [ ] **Step 2: Every menu page still renders** — sign in as the local admin through the browser (or curl with the e2e admin created by a temporary run of the fixtures) and request each `Public/*.php` linked from `View/sidebar.php`; none may show `Fatal error`, `Warning: Undefined` introduced by this work, or an SQL error. (Pre-existing notices in edit-form modals are known and out of scope.)

- [ ] **Step 3: Screenshots** — shop screen, the shop login dialog, the refused-login state, and the Assign Users to Shops screen, via headless Chrome; attach to the hand-off message.

- [ ] **Step 4: Docs** — `README.md`: in *Usage* step 2 → "Select the shop and sign in to it with your username and password."; under *Administration and Security* add "- Per-shop access: each user's role is set per shop (see `db/SHOP_ACCESS_MODULE.md`)". In the spec add a *Spec additions found while planning* section with the two items at the top of this plan.

- [ ] **Step 5: Commit and push**

```bash
git add README.md docs/superpowers/specs/2026-09-22-shop-login-and-per-shop-access-design.md
git commit -m "docs: shop login and per-shop access in README and spec"
git push origin development
```

- [ ] **Step 6: Memory** — add to the server-deployment memory: the next redeploy must run `php db/shop_access_install.php` before uploading the code.
