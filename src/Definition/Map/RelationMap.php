<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Definition\Map;

use Cycle\Schema\Definition\Relation;
use Cycle\Schema\Exception\RelationException;

class RelationMap implements \IteratorAggregate
{
    /** @var Relation[] */
    private $fields = [];

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * @param string $name
     * @return Relation
     */
    public function get(string $name): Relation
    {
        if (!$this->has($name)) {
            throw new RelationException("Undefined relation `{$name}`");
        }

        return $this->fields[$name];
    }

    /**
     * @param string   $name
     * @param Relation $relation
     * @return RelationMap
     */
    public function set(string $name, Relation $relation): self
    {
        if ($this->has($name)) {
            throw new RelationException("Relation `{$name}` already exists");
        }

        $this->fields[$name] = $relation;

        return $this;
    }

    /**
     * @return Relation[]|\Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }
}