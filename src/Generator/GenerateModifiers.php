<?php

declare(strict_types=1);

namespace Cycle\Schema\Generator;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\SchemaException;
use Cycle\Schema\Exception\SchemaModifierException;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Cycle\Schema\SchemaModifierInterface;

/**
 * Generate modifiers based on their schematic definitions.
 */
final class GenerateModifiers implements GeneratorInterface
{
    public function run(Registry $registry): Registry
    {
        foreach ($registry as $entity) {
            $this->register($registry, $entity);
        }

        return $registry;
    }

    protected function register(Registry $registry, Entity $entity): void
    {
        $role = $entity->getRole();
        assert($role !== null);
        foreach ($entity->getSchemaModifiers() as $modifier) {
            \assert($modifier instanceof SchemaModifierInterface);
            try {
                $modifier->compute($registry);
            } catch (SchemaModifierException $e) {
                throw new SchemaException(
                    sprintf('Unable to compute modifier `%s` for the `%s` role.', $modifier::class, $role),
                    $e->getCode(),
                    $e
                );
            }
        }
    }
}
