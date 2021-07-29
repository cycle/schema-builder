<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
     * Create foreign key between two entities. Only when both entities are located
     * in a same database.
     *
     * @param Registry $registry
     * @param Entity   $source
     * @param Entity   $target
     * @param Field    $innerField
     * @param Field    $outerField
     */
    protected function createForeignKey(
        Registry $registry,
        Entity $source,
        Entity $target,
        Field $innerField,
        Field $outerField
    ): void {
        if ($registry->getDatabase($source) !== $registry->getDatabase($target)) {
            return;
        }

        $outerFields = (new FieldMap())->set($outerField->getColumn(), $outerField);
        $innerFields = (new FieldMap())->set($innerField->getColumn(), $innerField);

        $this->createForeignCompositeKey($registry, $source, $target, $outerFields, $innerFields);
    }


    /**
     * Create foreign key between two entities with composite fields. Only when both entities are located
     * in a same database.
     *
     * @param Registry $registry
     * @param Entity   $source
     * @param Entity   $target
     * @param FieldMap $innerFields
     * @param FieldMap $outerFields
     */
    protected function createForeignCompositeKey(
        Registry $registry,
        Entity $source,
        Entity $target,
        FieldMap $innerFields,
        FieldMap $outerFields
    ): void {
        if ($registry->getDatabase($source) !== $registry->getDatabase($target)) {
            return;
        }

        $registry->getTableSchema($target)
            ->foreignKey($outerFields->getKeys())
            ->references($registry->getTable($source), $innerFields->getKeys())
            ->onUpdate($this->getOptions()->get(RelationSchema::FK_ACTION))
            ->onDelete($this->getOptions()->get(RelationSchema::FK_ACTION));
    }

    /**
     * @return OptionSchema
     */
    abstract protected function getOptions(): OptionSchema;
}
