<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition\Map;

use Cycle\Schema\Exception\OptionException;

/**
 * @implements \IteratorAggregate<string, mixed>
 */
final class OptionMap implements \IteratorAggregate
{
    private array $options = [];

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @throws OptionException
     */
    public function get(string $name): mixed
    {
        if (!$this->has($name)) {
            throw new OptionException("Undefined option `{$name}`");
        }

        return $this->options[$name];
    }

    public function set(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function remove(string $name): self
    {
        unset($this->options[$name]);

        return $this;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->options);
    }
}
