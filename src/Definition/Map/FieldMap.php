<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition\Map;

use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\FieldException;
use Traversable;

/**
 * Manage the set of fields associated with the entity.
 *
 * @implements \IteratorAggregate<string, Field>
 */
final class FieldMap implements \IteratorAggregate, \Countable
{
    /** @var Field[] */
    private $fields = [];

    /**
     * Cloning.
     */
    public function __clone()
    {
        foreach ($this->fields as $name => $field) {
            $this->fields[$name] = clone $field;
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->fields);
    }

    /**
     * Get field column names
     */
    public function getColumnNames(): array
    {
        return array_values(array_map(static function (Field $field) {
            return $field->getColumn();
        }, $this->fields));
    }

    /**
     * Get property names
     */
    public function getNames(): array
    {
        return array_keys($this->fields);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Check if field with given column name exist
     */
    public function hasColumn(string $name): bool
    {
        foreach ($this->fields as $field) {
            if ($field->getColumn() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get field by property name
     */
    public function get(string $name): Field
    {
        if (!$this->has($name)) {
            throw new FieldException("Undefined field `{$name}`.");
        }

        return $this->fields[$name];
    }

    /**
     * Get property name by column name
     */
    public function getKeyByColumnName(string $name): string
    {
        foreach ($this->fields as $key => $field) {
            if ($field->getColumn() === $name) {
                return $key;
            }
        }

        throw new FieldException("Undefined field with column name `{$name}`.");
    }

    /**
     * Get field by column name
     */
    public function getByColumnName(string $name): Field
    {
        foreach ($this->fields as $field) {
            if ($field->getColumn() === $name) {
                return $field;
            }
        }

        throw new FieldException("Undefined field with column name `{$name}`.");
    }

    /**
     * @param string $name
     * @param Field  $field
     *
     * @return FieldMap
     */
    public function set(string $name, Field $field): self
    {
        if ($this->has($name)) {
            throw new FieldException("Field `{$name}` already exists.");
        }

        $this->fields[$name] = $field;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return FieldMap
     */
    public function remove(string $name): self
    {
        unset($this->fields[$name]);
        return $this;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->fields);
    }
}
