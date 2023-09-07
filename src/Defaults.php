<?php

declare(strict_types=1);

namespace Cycle\Schema;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;

/**
 * @implements \ArrayAccess<int, mixed>
 */
final class Defaults implements \ArrayAccess
{
    /**
     * @param array<int, mixed> $defaults
     */
    public function __construct(
        private array $defaults = [
            SchemaInterface::MAPPER => Mapper::class,
            SchemaInterface::REPOSITORY => Repository::class,
            SchemaInterface::SOURCE => Source::class,
            SchemaInterface::SCOPE => null,
            SchemaInterface::TYPECAST_HANDLER => null,
        ]
    ) {
    }

    /**
     * @param array<int, mixed> $defaults
     */
    public function merge(array $defaults): self
    {
        $this->defaults = $defaults + $this->defaults;

        return $this;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->defaults[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->defaults[$offset];
    }

    /**
     * @param int $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->defaults[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->defaults[$offset]);
    }
}
