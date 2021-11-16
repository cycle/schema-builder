<?php

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Registry;
use Cycle\Schema\SchemaModifierInterface;

class BrokenSchemaModifier implements SchemaModifierInterface
{

    public function withRole(string $role): static
    {
        // TODO: Implement withRole() method.
    }

    public function compute(Registry $registry): void
    {
        // TODO: Implement compute() method.
    }

    public function render(Registry $registry): void
    {
        // TODO: Implement render() method.
    }

    public function modifySchema(array &$schema): void
    {
        throw new \Exception('Something went wrong');
    }
}
