<?php

declare(strict_types=1);

namespace Cycle\Schema\Generator;

use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;

final class ForeignKeys implements GeneratorInterface
{
    public function run(Registry $registry): Registry
    {
        foreach ($registry as $entity) {
            foreach ($entity->getForeignKeys() as $fk) {
                $registry->getTableSchema($entity)
                    ->foreignKey($fk->getInnerColumns(), $fk->isCreateIndex())
                    ->references($fk->getTable(), $fk->getOuterColumns())
                    ->onUpdate($fk->getAction())
                    ->onDelete($fk->getAction());
            }
        }

        return $registry;
    }
}
