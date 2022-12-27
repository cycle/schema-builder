<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition;

use Cycle\Schema\Definition\Map\OptionMap;
use Cycle\Schema\Exception\FieldException;

/**
 * Field declaration, it's type and mapping to column.
 */
final class Field
{
    private OptionMap $options;
    private OptionMap $attributes;
    private ?string $column = null;
    private ?string $type = null;
    private bool $primary = false;
    private array|string|null $typecast = null;
    private bool $referenced = false;
    private ?string $entityClass = null;

    public function __construct()
    {
        $this->options = new OptionMap();
        $this->attributes = new OptionMap();
    }

    public function __clone()
    {
        $this->options = clone $this->options;
        $this->attributes = clone $this->attributes;
    }

    public function getOptions(): OptionMap
    {
        return $this->options;
    }

    public function getAttributes(): OptionMap
    {
        return $this->attributes;
    }

    public function getType(): string
    {
        if (empty($this->column)) {
            throw new FieldException('Field type must be set');
        }

        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setPrimary(bool $primary): self
    {
        $this->primary = $primary;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->primary || in_array($this->type, ['primary', 'bigPrimary']);
    }

    public function setColumn(string $column): self
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @throws FieldException
     */
    public function getColumn(): string
    {
        if (empty($this->column)) {
            throw new FieldException('Column mapping must be set');
        }

        return $this->column;
    }

    public function setTypecast(array|string|null $typecast): self
    {
        $this->typecast = $typecast;

        return $this;
    }

    public function hasTypecast(): bool
    {
        return $this->typecast !== null;
    }

    public function getTypecast(): array|string|null
    {
        return $this->typecast;
    }

    public function setReferenced(bool $indexed): self
    {
        $this->referenced = $indexed;

        return $this;
    }

    public function isReferenced(): bool
    {
        return $this->referenced;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(?string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }
}
