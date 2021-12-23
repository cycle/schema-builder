<?php

declare(strict_types=1);

namespace Cycle\Schema\Generator;

use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\Reflector;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Cycle\Schema\Table\Column;

/**
 * Generate table columns based on entity definition.
 */
final class RenderTables implements GeneratorInterface
{
    private Reflector $reflector;

    /**
     * TableGenerator constructor.
     */
    public function __construct()
    {
        $this->reflector = new Reflector();
    }

    public function run(Registry $registry): Registry
    {
        foreach ($registry as $entity) {
            $this->compute($registry, $entity);
        }

        return $registry;
    }

    /**
     * List of all involved tables sorted in order of their dependency.
     *
     * @return AbstractTable[]
     */
    public function getTables(): array
    {
        return $this->reflector->sortedTables();
    }

    public function getReflector(): Reflector
    {
        return $this->reflector;
    }

    /**
     * Generate table schema based on given entity definition.
     */
    private function compute(Registry $registry, Entity $entity): void
    {
        if (!$registry->hasTable($entity)) {
            // do not render entities without associated table
            return;
        }

        $table = $registry->getTableSchema($entity);

        $primaryKeys = [];
        foreach ($entity->getFields() as $field) {
            $column = Column::parse($field);

            if ($column->isPrimary()) {
                $primaryKeys[] = $field->getColumn();
            }

            $column->render($table->column($field->getColumn()));
        }

        // todo fix discriminator column name
        // if ($registry->getChildren($entity) !== []) {
        //     if (!$table->hasColumn(Mapper::ENTITY_TYPE)) {
        //         $table->string(Mapper::ENTITY_TYPE, 32);
        //     }
        // }

        if (count($primaryKeys)) {
            $table->setPrimaryKeys($primaryKeys);
        }

        $this->reflector->addTable($table);
    }
}
