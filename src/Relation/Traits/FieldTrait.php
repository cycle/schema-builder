<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Relation\Traits;

use Cycle\ORM\Relation;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Map\FieldMap;
use Cycle\Schema\Exception\FieldException;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Schema\Table\Column;

trait FieldTrait
{
    protected function getField(Entity $entity, int $field): Field
    {
        try {
            return $entity->getFields()->get($this->getOptions()->get($field));
        } catch (FieldException $e) {
            throw new RelationException(
                sprintf(
                    'Field `%s`.`%s` does not exists, referenced by `%s`',
                    $entity->getRole(),
                    $this->getOptions()->get($field),
                    $this->source
                ),
                $e->getCode(),
                $e
            );
        }
    }

    protected function getFields(Entity $entity, int $option): FieldMap
    {
        $fields = new FieldMap();
        $keys = (array)$this->getOptions()->get($option);

        foreach ($keys as $key) {
            try {
                $field = $entity->getFields()->getByColumnName($key);
                $name = $entity->getFields()->getKeyByColumnName($key);

                $fields->set($name, $field);
            } catch (FieldException $e) {
                throw new RelationException(
                    sprintf(
                        'Field `%s`.`%s` does not exists, referenced by `%s`.',
                        $entity->getRole(),
                        $key,
                        $this->source
                    ),
                    $e->getCode(),
                    $e
                );
            }
        }

        return $fields;
    }

    protected function ensureField(Entity $target, string $column, Field $outer, bool $nullable = false): void
    {
        // ensure that field will be indexed in memory for fast references
        $outer->setReferenced(true);

        if ($target->getFields()->hasColumn($column)) {
            // field already exists and defined by the user
            return;
        }

        $field = new Field();
        $field->setColumn($column);
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

        if ($nullable) {
            $field->getOptions()->set(Column::OPT_NULLABLE, true);
        }

        $target->getFields()->set($column, $field);
    }

    protected function createRelatedFields(Entity $source, int $sourceKey, Entity $target, int $targetKey): void
    {
        foreach ($this->getFields($source, $sourceKey) as $sourceField) {
            foreach ((array)$this->options->get($targetKey) as $targetField) {
                $this->ensureField(
                    $target,
                    $targetField,
                    $sourceField,
                    $this->options->get(Relation::NULLABLE)
                );
            }
        }
    }

    abstract protected function getOptions(): OptionSchema;
}
