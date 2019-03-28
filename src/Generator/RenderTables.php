<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Generator;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Cycle\Schema\Table\Column;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Database\Schema\Reflector;

/**
 * Generate table columns based on entity definition.
 */
final class RenderTables implements GeneratorInterface
{
    // Do not render table when entity option is set
    public const READONLY = 'readonly';

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
     * @param Registry $registry
     * @return Registry
     */
    public function run(Registry $registry): Registry
    {
        foreach ($registry as $entity) {
            $this->compute($registry, $entity);
        }

        return $registry;
    }

    /**
     * Generate table schema based on given entity definition.
     *
     * @param Registry $registry
     * @param Entity   $entity
     */
    protected function compute(Registry $registry, Entity $entity)
    {
        if (!$registry->hasTable($entity) || $entity->getOptions()->has(self::READONLY)) {
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

        if (count($primaryKeys)) {
            $table->setPrimaryKeys($primaryKeys);
        }

        $this->reflector->addTable($table);
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

    /**
     * @return Reflector
     */
    public function getReflector(): Reflector
    {
        return $this->reflector;
    }
}