<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition\Map;

use Cycle\Schema\Definition\Relation;
use Cycle\Schema\Exception\RelationException;
use Traversable;

/**
 * @implements \IteratorAggregate<string, Relation>
 */
final class RelationMap implements \IteratorAggregate
{
    /** @var array<string, Relation> */
    private array $relations = [];

    public function __clone()
    {
        foreach ($this->relations as $name => $relation) {
            $this->relations[$name] = clone $relation;
        }
    }

    public function has(string $name): bool
    {
        return isset($this->relations[$name]);
    }

    public function get(string $name): Relation
    {
        if (!$this->has($name)) {
            throw new RelationException("Undefined relation `{$name}`");
        }

        return $this->relations[$name];
    }

    public function set(string $name, Relation $relation): self
    {
        if ($this->has($name)) {
            throw new RelationException("Relation `{$name}` already exists");
        }

        $this->relations[$name] = $relation;

        return $this;
    }

    public function remove(string $name): self
    {
        unset($this->relations[$name]);
        return $this;
    }

    /**
     * @return Traversable<string, Relation>
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->relations);
    }
}
