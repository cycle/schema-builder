<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Visitor;

use Cycle\Schema\Builder;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\VisitorInterface;
use Spiral\Database\Schema\AbstractColumn;

/**
 * Generate table columns based on entity definition.
 */
class RenderTable implements VisitorInterface
{
    /**
     * Generate table schema based on given entity definition.
     *
     * @param Builder $builder
     * @param Entity  $entity
     */
    public function compute(Builder $builder, Entity $entity)
    {
        // todo: readonly
        $table = $builder->getTable($entity);

        foreach ($entity->getFields() as $field) {

        }

        // todo: primary columns
        // todo: rendering (!), see tablerenderer
    }

    /**
     * Cast default value based on column type. Required to prevent conflicts when not nullable
     * column added to existed table with data in.
     *
     * @param AbstractColumn $column
     * @return mixed
     */
    protected function castDefault(AbstractColumn $column)
    {
        if (in_array($column->getAbstractType(), ['timestamp', 'datetime', 'time', 'date'])) {
            return 0;
        }

        if ($column->getAbstractType() == 'enum') {
            // we can use first enum value as default
            return $column->getEnumValues()[0];
        }

        switch ($column->getType()) {
            case AbstractColumn::INT:
                return 0;
            case AbstractColumn::FLOAT:
                return 0.0;
            case AbstractColumn::BOOL:
                return false;
        }

        return '';
    }
}