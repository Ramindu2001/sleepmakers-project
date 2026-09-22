<?php
//database connection
class Dbh
{
    private $host = "localhost";
    private $user = "root";
    private $pwd = "";
    private $dbName = "sleepmakers";

    private static $sharedPdo = null;

    protected function connect()
    {
        if (self::$sharedPdo === null) {
            //a server keeps its own credentials in db_credentials.php (never committed);
            //without that file the local XAMPP defaults above are used
            $credentialsFile = __DIR__ . '/db_credentials.php';
            if (is_file($credentialsFile)) {
                $credentials = require $credentialsFile;
                $this->host = $credentials['host'];
                $this->user = $credentials['user'];
                $this->pwd = $credentials['pwd'];
                $this->dbName = $credentials['dbName'];
            } //if server credentials
            try {
                //charset set explicitly: servers whose MariaDB default is latin1 would garble non-ASCII text
                $dsn = "mysql:host=" . $this->host . "; dbname=" . $this->dbName . ";charset=utf8mb4";
                $pdo = new PDO($dsn, $this->user, $this->pwd);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                //NOW()/CURDATE() in queries must agree with PHP's Asia/Colombo (includes.php),
                //whatever time zone the database server runs in; Sri Lanka has no DST
                $pdo->exec("SET time_zone = '+05:30'");
                self::$sharedPdo = $pdo;
            } //try
            catch (PDOException $e) {
                echo "Connection failed..." . $e->getMessage();
            } //catch
        } //if
        return self::$sharedPdo;
    } //connect method
}//class Dbh
