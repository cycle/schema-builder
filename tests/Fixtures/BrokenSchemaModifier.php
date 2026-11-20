<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Exception\SchemaModifierException;
use Cycle\Schema\Registry;
use Cycle\Schema\SchemaModifierInterface;

class BrokenSchemaModifier implements SchemaModifierInterface
{
    public function __construct(private string $brokenMethod) {}

    public function withRole(string $role): static
    {
        if ($this->brokenMethod === __METHOD__) {
            $this->throwException();
        }

        return $this;
    }

    public function compute(Registry $registry): void
    {
        if ($this->brokenMethod === __METHOD__) {
            $this->throwException();
        }
    }

    public function render(Registry $registry): void
    {
        if ($this->brokenMethod === __METHOD__) {
            $this->throwException();
        }
    }

    public function modifySchema(array &$schema): void
    {
        if ($this->brokenMethod === __METHOD__) {
            $this->throwException();
        }
    }

    private function throwException(): void
    {
        throw new SchemaModifierException('Something went wrong');
    }
}
