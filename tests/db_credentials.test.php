<?php
//The database the test suite runs against (tests/bootstrap.php). The tests drop and recreate
//its tables before every test: never point this at a real database.
return [
    'host'   => getenv('CLOUDPOS_TEST_DB_HOST') ?: 'localhost',
    'user'   => getenv('CLOUDPOS_TEST_DB_USER') ?: 'root',
    'pwd'    => getenv('CLOUDPOS_TEST_DB_PWD') ?: '',
    'dbName' => 'sleepmakers_test',
];
