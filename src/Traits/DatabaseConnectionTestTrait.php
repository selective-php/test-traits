<?php

namespace Selective\TestTrait\Traits;

use PDO;
use PDOStatement;
use UnexpectedValueException;

trait DatabaseConnectionTestTrait
{
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
}
