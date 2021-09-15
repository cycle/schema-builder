<?php

declare(strict_types=1);

namespace Cycle\Schema\Generator;

use Cycle\Schema\Exception\EntityException;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;

final class ValidateEntities implements GeneratorInterface
{
    /**
     * @param Registry $registry
     *
     * @throws EntityException
     *
     * @return Registry
     */
    public function run(Registry $registry): Registry
    {
        foreach ($registry->getIterator() as $entity) {
            if (count($entity->getFields()) === 0) {
                throw new EntityException(
                    "Entity `{$entity->getRole()}` is empty"
                );
            }
        }

        return $registry;
    }
}
