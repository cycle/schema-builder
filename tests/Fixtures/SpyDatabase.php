<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\StatementInterface;
use Cycle\Database\TableInterface;

/**
 * Wraps a real database and records every {@see self::getSchemas()} call so tests can assert the
 * bulk-loading contract of the Registry: linking defers introspection, and the first schema request
 * loads all the pending tables through a single batched call.
 */
class SpyDatabase implements DatabaseInterface, DatabaseProviderInterface
{
    /** @var list<non-empty-string[]> The table-name list of each getSchemas() call, in order. */
    public array $getSchemasCalls = [];

    public function __construct(
        private DatabaseInterface $database,
    ) {}

    /**
     * @param non-empty-string[]|null $tables
     *
     * @return array<non-empty-string, AbstractTable>
     */
    public function getSchemas(?array $tables = null): array
    {
        $this->getSchemasCalls[] = $tables;

        return $this->database->getSchemas($tables);
    }

    public function database(?string $database = null): DatabaseInterface
    {
        return $this;
    }

    public function getName(): string
    {
        return $this->database->getName();
    }

    public function table(string $name): TableInterface
    {
        return $this->database->table($name);
    }

    public function getType(): string
    {
        return $this->database->getType();
    }

    public function getDriver(int $type = self::WRITE): DriverInterface
    {
        return $this->database->getDriver($type);
    }

    public function withPrefix(string $prefix, bool $add = true): DatabaseInterface
    {
        throw new \BadMethodCallException(__METHOD__ . ' is not expected to be called');
    }

    public function getPrefix(): string
    {
        return $this->database->getPrefix();
    }

    public function hasTable(string $name): bool
    {
        return $this->database->hasTable($name);
    }

    public function getTables(): array
    {
        return $this->database->getTables();
    }

    public function execute(string $query, array $parameters = []): int
    {
        return $this->database->execute($query, $parameters);
    }

    public function query(string $query, array $parameters = []): StatementInterface
    {
        return $this->database->query($query, $parameters);
    }

    public function insert(string $table = ''): InsertQuery
    {
        return $this->database->insert($table);
    }

    public function update(string $table = '', array $values = [], array $where = []): UpdateQuery
    {
        return $this->database->update($table, $values, $where);
    }

    public function delete(string $table = '', array $where = []): DeleteQuery
    {
        return $this->database->delete($table, $where);
    }

    public function select(mixed $columns = '*'): SelectQuery
    {
        return $this->database->select($columns);
    }

    public function transaction(callable $callback, ?string $isolationLevel = null): mixed
    {
        return $this->database->transaction($callback, $isolationLevel);
    }

    public function begin(?string $isolationLevel = null): bool
    {
        return $this->database->begin($isolationLevel);
    }

    public function commit(): bool
    {
        return $this->database->commit();
    }

    public function rollback(): bool
    {
        return $this->database->rollback();
    }
}
