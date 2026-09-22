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
        //PHP 8.1+ returns integer columns as ints
        $this->assertSame(['UserName' => 'alice', 'UserRoles_URID' => $role], $row);
    }

    public function test_every_test_starts_from_empty_tables()
    {
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM user')->fetchColumn());
    }
}
