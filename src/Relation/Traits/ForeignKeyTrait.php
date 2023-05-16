<?php

declare(strict_types=1);

namespace Cycle\Schema\Relation\Traits;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Map\FieldMap;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Relation\RelationSchema;

trait ForeignKeyTrait
{
    /**
     * Create foreign key between two entities. Only when both entities are located in a same database.
     */
    final protected function createForeignKey(
        Registry $registry,
        Entity $source,
        Entity $target,
        Field $innerField,
        Field $outerField,
        bool $indexCreate = true
    ): void {
        if ($registry->getDatabase($source) !== $registry->getDatabase($target)) {
            return;
        }

        $outerFields = (new FieldMap())->set($outerField->getColumn(), $outerField);
        $innerFields = (new FieldMap())->set($innerField->getColumn(), $innerField);

        $this->createForeignCompositeKey($registry, $source, $target, $outerFields, $innerFields, $indexCreate);
    }

    /**
     * Create foreign key between two entities with composite fields. Only when both entities are located
     * in a same database.
     */
    final protected function createForeignCompositeKey(
        Registry $registry,
        Entity $source,
        Entity $target,
        FieldMap $innerFields,
        FieldMap $outerFields,
        bool $indexCreate = true
    ): void {
        if ($registry->getDatabase($source) !== $registry->getDatabase($target)) {
            return;
        }

        $fkAction = $this->getOptions()->get(RelationSchema::FK_ACTION);
        $registry->getTableSchema($target)
            ->foreignKey($outerFields->getColumnNames(), $indexCreate)
            ->references($registry->getTable($source), $innerFields->getColumnNames())
            ->onUpdate($fkAction)
            ->onDelete($this->getOptions()->get(RelationSchema::FK_ON_DELETE) ?? $fkAction);
    }

    abstract protected function getOptions(): OptionSchema;
}
