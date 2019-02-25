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

final class Compiler implements ProcessorInterface
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
            Schema::ENTITY     => $entity->getClass(),
            Schema::SOURCE     => $entity->getSource(),
            Schema::MAPPER     => $entity->getMapper(),
            Schema::REPOSITORY => $entity->getRepository(),
            Schema::CONSTRAIN  => $entity->getConstrain(),
            Schema::RELATIONS  => []
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

        $schema += $this->computeFields($entity);

        // todo: relations

        foreach ($registry->getChildren($entity) as $child) {
            // alias
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
    protected function computeFields(Entity $entity): array
    {
        $schema = [
            Schema::COLUMNS      => [],
            Schema::TYPECAST     => [],
            Schema::FIND_BY_KEYS => []
        ];

        foreach ($entity->getFields() as $name => $field) {
            $schema[Schema::COLUMNS][$name] = $field->getColumn();

            if ($field->hasTypecast()) {
                $schema[Schema::TYPECAST][$name] = $field->getTypecast();
            }

            if ($field->isReferenced()) {
                $schema[Schema::FIND_BY_KEYS][] = $name;
            }
        }

        return $schema;
    }
}