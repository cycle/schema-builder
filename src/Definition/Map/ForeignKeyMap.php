<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition\Map;

use Cycle\Schema\Definition\ForeignKey;

/**
 * Manage the set of foreign keys associated with the entity.
 *
 * @implements \IteratorAggregate<non-empty-string, ForeignKey>
 */
final class ForeignKeyMap implements \IteratorAggregate, \Countable
{
    /**
     * @var array<non-empty-string, ForeignKey>
     */
    private array $foreignKeys = [];

    /**
     * Cloning.
     */
    public function __clone()
    {
        foreach ($this->foreignKeys as $index => $foreignKey) {
            $this->foreignKeys[$index] = clone $foreignKey;
        }
    }

    public function has(ForeignKey $foreignKey): bool
    {
        return isset($this->foreignKeys[$this->generateIdentifier($foreignKey)]);
    }

    public function set(ForeignKey $foreignKey): self
    {
        $this->foreignKeys[$this->generateIdentifier($foreignKey)] = $foreignKey;

        return $this;
    }

    public function remove(ForeignKey $foreignKey): self
    {
        unset($this->foreignKeys[$this->generateIdentifier($foreignKey)]);

        return $this;
    }

    public function count(): int
    {
        return \count($this->foreignKeys);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->foreignKeys);
    }

    /**
     * @return non-empty-string
     */
    private function generateIdentifier(ForeignKey $foreignKey): string
    {
        return \sprintf(
            '%s:%s:%s',
            $foreignKey->getTarget(),
            \implode(',', $foreignKey->getInnerColumns()),
            \implode(',', $foreignKey->getOuterColumns())
        );
    }
}
