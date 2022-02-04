<?php

namespace Selective\TestTrait\Traits;

use DomainException;
use PDO;
use PDOStatement;
use UnexpectedValueException;

/**
 * Database test.
 */
trait DatabaseTestTrait
{
    /**
     * @var string Path to schema.sql
     */
    protected $schemaFile = '';

    /**
     * Create tables and insert fixtures.
     *
     * TestCases must call this method inside setUp().
     *
     * @param string|null $schemaFile The sql schema file
     *
     * @return void
     */
    protected function setUpDatabase(string $schemaFile = null): void
    {
        if (isset($schemaFile)) {
            $this->schemaFile = $schemaFile;
        }

        $this->getConnection();

        $this->createTables();
        $this->truncateTables();

        if (!empty($this->fixtures)) {
            $this->insertFixtures($this->fixtures);
        }
    }

    /**
     * Get database connection.
     *
     * @return PDO The PDO instance
     */
    protected function getConnection(): PDO
    {
        return $this->container->get(PDO::class);
    }

    /**
     * Create tables.
     *
     * @return void
     */
    protected function createTables(): void
    {
        if (defined('DB_TEST_TRAIT_INIT')) {
            return;
        }

        $this->dropTables();
        $this->importSchema();

        define('DB_TEST_TRAIT_INIT', 1);
    }

    /**
     * Get database variable.
     *
     * @param string $variable The variable
     *
     * @return string|null The value
     */
    protected function getDatabaseVariable(string $variable): ?string
    {
        $statement = $this->getConnection()->prepare('SHOW VARIABLES LIKE ?');
        if (!$statement || $statement->execute([$variable]) === false) {
            throw new UnexpectedValueException('Invalid SQL statement');
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            // Database variable not defined
            return null;
        }

        return (string)$row['Value'];
    }

    /**
     * Clean up database. Truncate tables.
     *
     * @return void
     */
    protected function dropTables(): void
    {
        $pdo = $this->getConnection();

        $pdo->exec('SET unique_checks=0; SET foreign_key_checks=0;');

        $statement = $this->createQueryStatement(
            'SELECT TABLE_NAME
                FROM information_schema.tables
                WHERE table_schema = database()'
        );

        $rows = (array)$statement->fetchAll(PDO::FETCH_ASSOC);

        $sql = [];
        foreach ($rows as $row) {
            $sql[] = sprintf('DROP TABLE `%s`;', $row['TABLE_NAME']);
        }

        if ($sql) {
            $pdo->exec(implode("\n", $sql));
        }

        $pdo->exec('SET unique_checks=1; SET foreign_key_checks=1;');
    }

    /**
     * Create PDO statement.
     *
     * @param string $sql The sql
     *
     * @throws UnexpectedValueException
     *
     * @return PDOStatement The statement
     */
    private function createQueryStatement(string $sql): PDOStatement
    {
        $statement = $this->getConnection()->query($sql, PDO::FETCH_ASSOC);

        if (!$statement instanceof PDOStatement) {
            throw new UnexpectedValueException('Invalid SQL statement');
        }

        return $statement;
    }

    /**
     * Import table schema.
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    protected function importSchema(): void
    {
        if (!$this->schemaFile) {
            throw new UnexpectedValueException('The path for schema.sql is not defined');
        }

        if (!file_exists($this->schemaFile)) {
            throw new UnexpectedValueException(sprintf('File not found: %s', $this->schemaFile));
        }

        $pdo = $this->getConnection();
        $pdo->exec('SET unique_checks=0; SET foreign_key_checks=0;');
        $pdo->exec((string)file_get_contents($this->schemaFile));
        $pdo->exec('SET unique_checks=1; SET foreign_key_checks=1;');
    }

    /**
     * Clean up database.
     *
     * @return void
     */
    protected function truncateTables(): void
    {
        $pdo = $this->getConnection();

        $pdo->exec('SET unique_checks=0; SET foreign_key_checks=0;');

        $expiry = $this->getDatabaseVariable('information_schema_stats_expiry');
        if ($expiry === null) {
            // MariaDB: Truncate only changed tables
            $statement = $this->createQueryStatement(
                'SELECT TABLE_NAME
                FROM information_schema.tables
                WHERE table_schema = database()
                AND (update_time IS NOT NULL OR auto_increment IS NOT NULL)'
            );
        } else {
            // MySQL: Truncate all tables
            // Workaround for MySQL 8: update_time not working.
            // Even SET information_schema_stats_expiry=0; has no affect anymore.
            // https://bugs.mysql.com/bug.php?id=95407
            $statement = $this->createQueryStatement(
                'SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = database()'
            );
        }

        $rows = (array)$statement->fetchAll(PDO::FETCH_ASSOC);

        $sql = [];
        foreach ($rows as $row) {
            $sql[] = sprintf('TRUNCATE TABLE `%s`;', $row['TABLE_NAME']);
        }

        if ($sql) {
            $pdo->exec(implode("\n", $sql));
        }

        $pdo->exec('SET unique_checks=1; SET foreign_key_checks=1;');
    }

    /**
     * Iterate over all fixtures and insert them into their tables.
     *
     * @param array $fixtures The fixtures
     *
     * @return void
     */
    protected function insertFixtures(array $fixtures): void
    {
        foreach ($fixtures as $fixture) {
            $object = new $fixture();

            foreach ($object->records as $row) {
                $this->insertFixture($object->table, $row);
            }
        }
    }

    /**
     * Insert row into table.
     *
     * @param string $table The table name
     * @param array $row The row data
     *
     * @return void
     */
    protected function insertFixture(string $table, array $row): void
    {
        $fields = array_keys($row);

        array_walk(
            $fields,
            function (&$value) {
                $value = sprintf('`%s`=:%s', $value, $value);
            }
        );

        $statement = $this->createPreparedStatement(sprintf('INSERT INTO `%s` SET %s', $table, implode(',', $fields)));
        $statement->execute($row);
    }

    /**
     * Create PDO statement.
     *
     * @param string $sql The sql
     *
     * @throws UnexpectedValueException
     *
     * @return PDOStatement The statement
     */
    private function createPreparedStatement(string $sql): PDOStatement
    {
        $statement = $this->getConnection()->prepare($sql);

        if (!$statement instanceof PDOStatement) {
            throw new UnexpectedValueException('Invalid SQL statement');
        }

        return $statement;
    }

    /**
     * Asserts that a given table is the same as the given row.
     *
     * @param array $expectedRow Row expected to find
     * @param string $table Table to look into
     * @param int $id The primary key
     * @param array|null $fields The columns
     * @param string $message Optional message
     *
     * @return void
     */
    protected function assertTableRow(
        array $expectedRow,
        string $table,
        int $id,
        array $fields = null,
        string $message = ''
    ): void {
        $this->assertSame(
            $expectedRow,
            $this->getTableRowById($table, $id, $fields ?: array_keys($expectedRow)),
            $message
        );
    }

    /**
     * Fetch row by ID.
     *
     * @param string $table Table name
     * @param int $id The primary key value
     * @param array|null $fields The array of fields
     *
     * @throws DomainException
     *
     * @return array Row
     */
    protected function getTableRowById(string $table, int $id, array $fields = null): array
    {
        $sql = sprintf('SELECT * FROM `%s` WHERE `id` = :id', $table);
        $statement = $this->createPreparedStatement($sql);
        $statement->execute(['id' => $id]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (empty($row)) {
            throw new DomainException(sprintf('Row not found: %s', $id));
        }

        if ($fields) {
            $row = array_intersect_key($row, array_flip($fields));
        }

        return $row;
    }

    /**
     * Asserts that a given table equals the given row.
     *
     * @param array $expectedRow Row expected to find
     * @param string $table Table to look into
     * @param int $id The primary key
     * @param array|null $fields The columns
     * @param string $message Optional message
     *
     * @return void
     */
    protected function assertTableRowEquals(
        array $expectedRow,
        string $table,
        int $id,
        array $fields = null,
        string $message = ''
    ): void {
        $this->assertEquals(
            $expectedRow,
            $this->getTableRowById($table, $id, $fields ?: array_keys($expectedRow)),
            $message
        );
    }

    /**
     * Asserts that a given table contains a given row value.
     *
     * @param mixed $expected The expected value
     * @param string $table Table to look into
     * @param int $id The primary key
     * @param string $field The column name
     * @param string $message Optional message
     *
     * @return void
     */
    protected function assertTableRowValue(
        $expected,
        string $table,
        int $id,
        string $field,
        string $message = ''
    ): void {
        $actual = $this->getTableRowById($table, $id, [$field])[$field];
        $this->assertSame($expected, $actual, $message);
    }

    /**
     * Asserts that a given table contains a given number of rows.
     *
     * @param int $expected The number of expected rows
     * @param string $table Table to look into
     * @param string $message Optional message
     *
     * @return void
     */
    protected function assertTableRowCount(int $expected, string $table, string $message = ''): void
    {
        $this->assertSame($expected, $this->getTableRowCount($table), $message);
    }

    /**
     * Get table row count.
     *
     * @param string $table The table name
     *
     * @return int The number of rows
     */
    protected function getTableRowCount(string $table): int
    {
        $sql = sprintf('SELECT COUNT(*) AS counter FROM `%s`;', $table);
        $statement = $this->createQueryStatement($sql);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return (int)($row['counter'] ?? 0);
    }

    /**
     * Asserts that a given table contains a given number of rows.
     *
     * @param string $table Table to look into
     * @param int $id The id
     * @param string $message Optional message
     *
     * @return void
     */
    protected function assertTableRowExists(string $table, int $id, string $message = ''): void
    {
        $this->assertTrue((bool)$this->findTableRowById($table, $id), $message);
    }

    /**
     * Fetch row by ID.
     *
     * @param string $table Table name
     * @param int $id The primary key value
     *
     * @return array Row
     */
    protected function findTableRowById(string $table, int $id): array
    {
        $sql = sprintf('SELECT * FROM `%s` WHERE `id` = :id', $table);
        $statement = $this->createPreparedStatement($sql);
        $statement->execute(['id' => $id]);

        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Asserts that a given table contains a given number of rows.
     *
     * @param string $table Table to look into
     * @param int $id The id
     * @param string $message Optional message
     *
     * @return void
     */
    protected function assertTableRowNotExists(string $table, int $id, string $message = ''): void
    {
        $this->assertFalse((bool)$this->findTableRowById($table, $id), $message);
    }
}
