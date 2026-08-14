<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Database\TableInterface;

/**
 * A table implementation without the `getSchema()` method — the Registry must refuse it with a
 * clear exception instead of failing on an undefined method call.
 */
class LegacyTable implements TableInterface
{
    public function __construct(
        private TableInterface $table,
    ) {}

    public function exists(): bool
    {
        return $this->table->exists();
    }

    public function getName(): string
    {
        return $this->table->getName();
    }

    public function getFullName(): string
    {
        return $this->table->getFullName();
    }

    public function getPrimaryKeys(): array
    {
        return $this->table->getPrimaryKeys();
    }

    public function hasColumn(string $name): bool
    {
        return $this->table->hasColumn($name);
    }

    public function getColumns(): array
    {
        return $this->table->getColumns();
    }

    public function hasIndex(array $columns = []): bool
    {
        return $this->table->hasIndex($columns);
    }

    public function getIndexes(): array
    {
        return $this->table->getIndexes();
    }

    public function hasForeignKey(array $columns): bool
    {
        return $this->table->hasForeignKey($columns);
    }

    public function getForeignKeys(): array
    {
        return $this->table->getForeignKeys();
    }

    public function getDependencies(): array
    {
        return $this->table->getDependencies();
    }
}
