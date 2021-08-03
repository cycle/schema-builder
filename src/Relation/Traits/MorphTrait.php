<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Relation\Traits;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Registry;
use Cycle\Schema\Table\Column;

trait MorphTrait
{
    /**
     * @param Registry $registry
     * @param string   $interface
     * @return \Generator
     */
    protected function findTargets(Registry $registry, string $interface): \Generator
    {
        foreach ($registry as $entity) {
            $class = $entity->getClass();
            if ($class === null || !in_array($interface, class_implements($class))) {
                continue;
            }

            yield $entity;
        }
    }

    /**
     * @param Registry $registry
     * @param string   $interface
     * @return array Tuple [name, Field]
     *
     * @throws RelationException
     */
    protected function findOuterKey(Registry $registry, string $interface): array
    {
        /** @var Field|null $field */
        $keys = null;
        $fields = null;
        $prevEntity = null;

        foreach ($this->findTargets($registry, $interface) as $entity) {
            $primaryFields = $entity->getPrimaryFields();
            $primaryKeys = $primaryFields->getColumnNames();

            if (is_null($keys)) {
                $keys = $primaryKeys;
                $fields = $primaryFields;
                $prevEntity = $entity;
            } else {
                if ($keys !== $primaryKeys) {
                    throw new RelationException(sprintf(
                        "Inconsistent primary key reference (%s). PKs: (%s). Required PKs [%s]: (%s)",
                        $entity->getRole(),
                        implode(',', $primaryKeys),
                        $prevEntity->getRole(),
                        implode(',', $keys)
                    ));
                }
            }
        }

        if (is_null($fields)) {
            throw new RelationException('Unable to find morphed parent');
        }

        return [$keys, $fields];
    }

    /**
     * @param Entity $target
     * @param string $name
     * @param int    $length
     * @param bool   $nullable
     */
    protected function ensureMorphField(Entity $target, string $name, int $length, bool $nullable = false): void
    {
        if ($target->getFields()->has($name)) {
            // field already exists and defined by the user
            return;
        }

        $field = new Field();
        $field->setColumn($name);
        $field->setType(sprintf('string(%s)', $length));

        if ($nullable) {
            $field->getOptions()->set(Column::OPT_NULLABLE, true);
        }

        $target->getFields()->set($name, $field);
    }
}
