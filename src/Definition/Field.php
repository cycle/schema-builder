<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition;

use Cycle\ORM\Schema\GeneratedField;
use Cycle\Schema\Definition\Map\OptionMap;
use Cycle\Schema\Exception\FieldException;

/**
 * Field declaration, it's type and mapping to column.
 */
final class Field
{
    private OptionMap $options;
    private OptionMap $attributes;

    /**
     * @var non-empty-string|null
     */
    private ?string $column = null;

    /**
     * @var non-empty-string|null
     */
    private ?string $type = null;

    private bool $primary = false;

    /**
     * @var callable-array|string|null
     */
    private array|string|null $typecast = null;

    private ?int $generated = null;

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

    /**
     * @return non-empty-string
     */
    public function getType(): string
    {
        if (empty($this->type)) {
            throw new FieldException('Field type must be set');
        }

        return $this->type;
    }

    /**
     * @param non-empty-string $type
     */
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
        return $this->primary || in_array($this->type, ['primary', 'bigPrimary', 'smallPrimary']);
    }

    /**
     * @param non-empty-string $column
     */
    public function setColumn(string $column): self
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @throws FieldException
     *
     * @return non-empty-string
     */
    public function getColumn(): string
    {
        if (empty($this->column)) {
            throw new FieldException('Column mapping must be set');
        }

        return $this->column;
    }

    /**
     * @param callable-array|string|null $typecast
     */
    public function setTypecast(array|string|null $typecast): self
    {
        $this->typecast = $typecast;

        return $this;
    }

    public function hasTypecast(): bool
    {
        return $this->typecast !== null;
    }

    /**
     * @return callable-array|string|null
     */
    public function getTypecast(): array|string|null
    {
        return $this->typecast;
    }

    /**
     * @param int|null $type Generating type {@see GeneratedField*} constants.
     */
    public function setGenerated(int|null $type): self
    {
        $this->generated = $type;

        return $this;
    }

    public function getGenerated(): ?int
    {
        return $this->generated;
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
