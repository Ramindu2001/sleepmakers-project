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
require_once __DIR__ . '/../Model/shop_access_class.php';
require_once __DIR__ . '/../Model/add_users_to_shops_class.php';
require_once __DIR__ . '/../db/shop_access_migration.php';
require_once __DIR__ . '/../db/scan_upload_migration.php';
require_once __DIR__ . '/../db/customer_orders_migration.php';
require_once __DIR__ . '/../Includes/scan_upload.php';
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../Includes/shop_session.php';
require_once __DIR__ . '/DatabaseTestCase.php';
