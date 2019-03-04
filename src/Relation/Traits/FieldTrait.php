<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Relation\Traits;

use Cycle\ORM\Relation;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Table\ColumnSchema;

trait FieldTrait
{
    /**
     * @param Entity $entity
     * @param int    $field
     * @return bool
     */
    protected function hasField(Entity $entity, int $field): bool
    {
        return $entity->getFields()->has($this->getOptions()->get($field));
    }

    /**
     * @param Entity $entity
     * @param int    $field
     * @return Field
     */
    protected function getField(Entity $entity, int $field): Field
    {
        return $entity->getFields()->get($this->getOptions()->get($field));
    }

    /**
     * @param Entity $target
     * @param string $name
     * @param Field  $outer
     */
    protected function ensureField(Entity $target, string $name, Field $outer)
    {
        if ($target->getFields()->has($name)) {
            // field already exists and defined by the user
            return;
        }

        $field = new Field();
        $field->setColumn($name);
        $field->setTypecast($outer->getTypecast());

        switch ($outer->getType()) {
            case 'primary':
                $field->setType('int');
                break;
            case 'bigPrimary':
                $field->setType('bigint');
                break;
            default:
                $field->setType($outer->getType());
        }

        if ($this->getOptions()->get(Relation::NULLABLE)) {
            $field->getOptions()->set(ColumnSchema::OPT_NULLABLE, true);
        }

        $target->getFields()->set($name, $field);
    }

    /**
     * @return OptionSchema
     */
    abstract protected function getOptions(): OptionSchema;
}