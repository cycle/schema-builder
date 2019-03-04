<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Generator;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Generator\Traits\GeneratorTrait;
use Cycle\Schema\Registry;
use Cycle\Schema\Table\ColumnSchema;
use Spiral\Database\Schema\Reflector;

/**
 * Generate table columns based on entity definition.
 */
final class RenderTable
{
    use GeneratorTrait;

    /** @var Reflector */
    private $reflector;

    /**
     * TableGenerator constructor.
     */
    public function __construct()
    {
        $this->reflector = new Reflector();
    }

    /**
     * Generate table schema based on given entity definition.
     *
     * @param Registry $registry
     * @param Entity   $entity
     */
    protected function compute(Registry $registry, Entity $entity)
    {
        if (!$registry->hasTable($entity)) {
            // do not render entities without associated table
            return;
        }

        $table = $registry->getTableSchema($entity);

        $primaryKeys = [];
        foreach ($entity->getFields() as $field) {
            $column = ColumnSchema::parse($field);

            if ($column->isPrimary()) {
                $primaryKeys[] = $field->getColumn();
            }

            $column->render($table->column($field->getColumn()));
        }

        if (count($primaryKeys)) {
            $table->setPrimaryKeys($primaryKeys);
        }

        $this->reflector->addTable($table);
    }

    /**
     * @return Reflector
     */
    public function getReflector(): Reflector
    {
        return $this->reflector;
    }
}