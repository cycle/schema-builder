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
                $target = $registry->getEntity($fk->getTarget());
                $targetSchema = $registry->getTableSchema($target);

                $pkExists = \array_diff($fk->getOuterColumns(), $targetSchema->getPrimaryKeys()) === [];
                if (!$pkExists && !$targetSchema->hasIndex($fk->getOuterColumns())) {
                    $targetSchema->index($fk->getOuterColumns())->unique();
                }

                $registry->getTableSchema($entity)
                    ->foreignKey($fk->getInnerColumns(), $fk->isCreateIndex())
                    ->references($registry->getTable($target), $fk->getOuterColumns())
                    ->onUpdate($fk->getAction())
                    ->onDelete($fk->getAction());
            }
        }

        return $registry;
    }
}
