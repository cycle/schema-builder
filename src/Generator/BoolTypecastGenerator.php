<?php

declare(strict_types=1);

namespace Cycle\Schema\Generator;

use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;

final class BoolTypecastGenerator implements GeneratorInterface
{
    public function run(Registry $registry): Registry
    {
        foreach ($registry as $entity) {
            foreach ($entity->getFields() as $field) {
                if ('boolean' === $field->getType() && !$field->hasTypecast()) {
                    $field->setTypecast('bool');
                }
            }
        }

        return $registry;
    }
}
