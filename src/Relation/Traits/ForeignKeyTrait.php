<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Relation\Traits;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\Util\OptionSchema;

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
    ) {
        if ($registry->getDatabase($source) !== $registry->getDatabase($target)) {
            return;
        }

        $registry->getTableSchema($target)
            ->foreignKey($outerField->getColumn())
            ->references($registry->getTable($source), $innerField->getColumn())
            ->onUpdate($this->getOptions()->get(self::FK_ACTION))
            ->onDelete($this->getOptions()->get(self::FK_ACTION));
    }

    /**
     * @return OptionSchema
     */
    abstract protected function getOptions(): OptionSchema;
}