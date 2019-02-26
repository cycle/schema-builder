<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema;

use Cycle\ORM\Schema;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\BuilderException;

final class Compiler implements GeneratorInterface
{
    /** @var array */
    private $result = [];

    /**
     * Get compiled schema result.
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Compile entity and relation definitions into packed ORM schema.
     *
     * @param Registry $registry
     * @param Entity   $entity
     */
    public function compute(Registry $registry, Entity $entity)
    {
        $schema = [
            Schema::ENTITY       => $entity->getClass(),
            Schema::SOURCE       => $entity->getSource(),
            Schema::MAPPER       => $entity->getMapper(),
            Schema::REPOSITORY   => $entity->getRepository(),
            Schema::CONSTRAIN    => $entity->getConstrain(),
            Schema::COLUMNS      => $this->renderColumns($entity),
            Schema::FIND_BY_KEYS => $this->renderReferences($entity),
            Schema::TYPECAST     => $this->renderTypecast($entity),
            Schema::RELATIONS    => $this->renderRelations($registry, $entity),
            Schema::SCHEMA       => $entity->getSchema()
        ];

        if ($registry->hasTable($entity)) {
            $primaryKeys = $registry->getTableSchema($entity)->getPrimaryKeys();
            if (count($primaryKeys) !== 1) {
                throw new BuilderException("Entity `{$entity->getRole()}` must define exactly one primary key");
            }

            $schema[Schema::DATABASE] = $registry->getDatabase($entity);
            $schema[Schema::TABLE] = $registry->getTable($entity);
            $schema[Schema::PRIMARY_KEY] = current($primaryKeys);
        }

        // table inheritance
        foreach ($registry->getChildren($entity) as $child) {
            $this->result[$child->getClass()] = [Schema::ROLE => $entity->getRole()];
            $schema[Schema::CHILDREN][] = $child->getClass();
        }

        ksort($schema);
        $this->result[$entity->getRole()] = $schema;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    protected function renderColumns(Entity $entity): array
    {
        $schema = [];
        foreach ($entity->getFields() as $name => $field) {
            $schema[$name] = $field->getColumn();
        }

        return $schema;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    protected function renderTypecast(Entity $entity): array
    {
        $schema = [];
        foreach ($entity->getFields() as $name => $field) {
            if ($field->hasTypecast()) {
                $schema[$name] = $field->getTypecast();
            }
        }

        return $schema;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    protected function renderReferences(Entity $entity): array
    {
        $schema = [];
        foreach ($entity->getFields() as $name => $field) {
            if ($field->isReferenced()) {
                $schema[] = $name;
            }
        }

        return $schema;
    }

    /**
     * @param Registry $registry
     * @param Entity   $entity
     * @return array
     */
    protected function renderRelations(Registry $registry, Entity $entity): array
    {
        return [];
    }
}