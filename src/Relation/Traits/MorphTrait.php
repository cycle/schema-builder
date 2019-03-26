<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Relation\Traits;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Registry;
use Cycle\Schema\Table\ColumnSchema;

trait MorphTrait
{
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
        $key = null;
        $field = null;

        foreach ($registry as $entity) {
            $class = $entity->getClass();
            if ($class === null || !in_array($interface, class_implements($class))) {
                continue;
            }

            $primaryKey = $this->getPrimary($entity);
            $primaryField = $entity->getFields()->get($primaryKey);

            if (is_null($field)) {
                $key = $primaryKey;
                $field = $primaryField;
            } else {
                if ($key != $primaryKey) {
                    throw new RelationException("Inconsistent primary key reference (name)");
                }

                if ($field->getType() != $primaryField->getType()) {
                    throw new RelationException("Inconsistent primary key reference (type)");
                }
            }
        }

        if (is_null($field)) {
            throw new RelationException("Unable to find morphed parent");
        }

        return [$key, $field];
    }

    /**
     * @param Entity $target
     * @param string $name
     * @param int    $lenght
     * @param bool   $nullable
     */
    protected function ensureMorphField(Entity $target, string $name, int $lenght, bool $nullable = false)
    {
        if ($target->getFields()->has($name)) {
            // field already exists and defined by the user
            return;
        }

        $field = new Field();
        $field->setColumn($name);
        $field->setType(sprintf("string(%s)", $lenght));

        if ($nullable) {
            $field->getOptions()->set(ColumnSchema::OPT_NULLABLE, true);
        }

        $target->getFields()->set($name, $field);
    }
}