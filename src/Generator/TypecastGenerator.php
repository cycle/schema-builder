<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Generator;

use Cycle\Schema\Registry;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\GeneratorInterface;
use Spiral\Database\Schema\AbstractColumn;

class TypecastGenerator implements GeneratorInterface
{
    /**
     * Automatically clarify column types based on table column types.
     *
     * @param Registry $registry
     * @param Entity   $entity
     */
    public function compute(Registry $registry, Entity $entity)
    {
        $table = $registry->getTableSchema($entity);

        foreach ($entity->getFields() as $field) {
            if ($field->hasTypecast() || !$table->hasColumn($field->getColumn())) {
                continue;
            }

            $column = $table->column($field->getColumn());

            $field->setTypecast($this->typecast($column));
        }
    }

    /**
     * @param AbstractColumn $column
     * @return string|callable
     */
    private function typecast(AbstractColumn $column)
    {
        switch ($column->getType()) {
            case AbstractColumn::BOOL:
                return 'bool';
            case AbstractColumn::INT:
                return 'int';
            case AbstractColumn::FLOAT:
                return 'float';
        }

        if (in_array($column->getAbstractType(), ['datetime', 'date', 'time'])) {
            return 'datetime';
        }

        return null;
    }
}