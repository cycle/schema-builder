<?php

declare(strict_types=1);

namespace Cycle\Schema\Generator;

use Cycle\Database\Schema\AbstractColumn;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;

/**
 * Must be run after RenderTable.
 */
final class GenerateTypecast implements GeneratorInterface
{
    /**
     * @param Registry $registry
     *
     * @return Registry
     */
    public function run(Registry $registry): Registry
    {
        foreach ($registry as $entity) {
            $this->computeByClassPropertyType($entity);
            $this->computeByFieldType($entity);
            $this->computeByColumn($registry, $entity);
        }

        return $registry;
    }

    private function computeByClassPropertyType(Entity $entity): void
    {
        $refClass = new \ReflectionClass($entity->getClass());
        foreach ($entity->getFields() as $field) {
            if ($field->hasTypecast()) {
                continue;
            }
            if (!$refClass->hasProperty($field->getColumn())) {
                continue;
            }

            $refProp = $refClass->getProperty($field->getColumn());
            if (!$refProp->hasType() || !$refProp->getType()->isBuiltin()) {
                continue;
            }

            $field->setTypecast(
                match ($refProp->getType()->getName()) {
                    'bool' => 'bool',
                    'int' => 'int',
                    'string' => 'string',
                    default => null
                }
            );
        }
    }

    private function computeByFieldType(Entity $entity): void
    {
        foreach ($entity->getFields() as $field) {
            if ($field->hasTypecast()) {
                continue;
            }

            $field->setTypecast(
                match ($field->getType()) {
                    'bool', 'boolean' => 'bool',
                    'int', 'integer' => 'int',
                    'string' => 'string',
                    default => null
                }
            );
        }
    }

    /**
     * Automatically clarify column types based on table column types.
     *
     * @param Registry $registry
     * @param Entity   $entity
     */
    protected function computeByColumn(Registry $registry, Entity $entity): void
    {
        if (!$registry->hasTable($entity)) {
            return;
        }

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
     *
     * @return callable|string
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

        if (in_array($column->getAbstractType(), ['datetime', 'date', 'time', 'timestamp'])) {
            return 'datetime';
        }

        return null;
    }
}
