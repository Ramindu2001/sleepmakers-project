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
            //without that file the local XAMPP defaults above are used. From the command line
            //CLOUDPOS_DB_CREDENTIALS may name another credentials file - the test suite uses it
            //to run against its own database. Web requests never read it.
            $credentialsFile = __DIR__ . '/db_credentials.php';
            if (PHP_SAPI === 'cli' && getenv('CLOUDPOS_DB_CREDENTIALS')) {
                $credentialsFile = getenv('CLOUDPOS_DB_CREDENTIALS');
            } //test or tool override
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
