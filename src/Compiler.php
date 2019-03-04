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
use Spiral\Database\Exception\CompilerException;

final class Compiler implements GeneratorInterface
{
    /** @var array */
    private $result = [];

    /**
     * Get compiled schema result.
     *
     * @return array
     */
    public function getSchema(): array
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
            Schema::SCHEMA       => $entity->getSchema(),
            Schema::PRIMARY_KEY  => $this->getPrimary($entity),
            Schema::COLUMNS      => $this->renderColumns($entity),
            Schema::FIND_BY_KEYS => $this->renderReferences($entity),
            Schema::TYPECAST     => $this->renderTypecast($entity),
            Schema::RELATIONS    => $this->renderRelations($registry, $entity)
        ];

        if ($registry->hasTable($entity)) {
            $schema[Schema::DATABASE] = $registry->getDatabase($entity);
            $schema[Schema::TABLE] = $registry->getTable($entity);
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
        $schema = [$this->getPrimary($entity)];

        foreach ($entity->getFields() as $name => $field) {
            if ($field->isReferenced()) {
                $schema[] = $name;
            }
        }

        return array_unique($schema);
    }

    /**
     * @param Registry $registry
     * @param Entity   $entity
     * @return array
     */
    protected function renderRelations(Registry $registry, Entity $entity): array
    {
        $result = [];
        foreach ($registry->getRelations($entity) as $name => $relation) {
            $result[$name] = $relation->packSchema();
        }

        return $result;
    }

    /**
     * @param Entity $entity
     * @return string
     *
     * @throws CompilerException
     */
    protected function getPrimary(Entity $entity): string
    {
        foreach ($entity->getFields() as $name => $field) {
            if ($field->isPrimary()) {
                return $name;
            }
        }

        throw new CompilerException("Entity `{$entity->getRole()}` must have defined primary key");
    }
}