<?php

declare(strict_types=1);

namespace Cycle\Schema\Relation\Traits;

use Cycle\ORM\Relation;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Map\FieldMap;
use Cycle\Schema\Exception\FieldException;
use Cycle\Schema\Exception\RegistryException;
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
                    $entity->getRole() ?? 'unknown',
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
                $fields->set($key, $entity->getFields()->get($key));
            } catch (FieldException $e) {
                throw new RelationException(
                    sprintf(
                        'Field `%s`.`%s` does not exists, referenced by `%s`.',
                        $entity->getRole() ?? 'unknown',
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

    protected function createRelatedFields(
        Entity $source,
        int $sourceKey,
        Entity $target,
        int $targetKey
    ): void {
        $sourceFields = $this->getFields($source, $sourceKey);
        $targetColumns = (array)$this->options->get($targetKey);

        $sourceFieldNames = $sourceFields->getNames();

        if (count($targetColumns) !== count($sourceFieldNames)) {
            throw new RegistryException(
                sprintf(
                    'Inconsistent amount of related fields. '
                    . 'Source entity: `%s`; keys: `%s`. Target entity: `%s`; keys: `%s`.',
                    $source->getRole() ?? 'unknown',
                    implode('`, `', $this->getFields($source, $sourceKey)->getColumnNames()),
                    $target->getRole() ?? 'unknown',
                    implode('`, `', $targetColumns)
                )
            );
        }

        $fields = array_combine($targetColumns, $sourceFieldNames);

        foreach ($fields as $targetColumn => $sourceFieldName) {
            $sourceField = $sourceFields->get($sourceFieldName);
            $this->ensureField(
                $target,
                $targetColumn,
                $sourceField,
                $this->options->get(Relation::NULLABLE)
            );
        }
    }

    /**
     * This method tries to replace column names with property names in relations
     */
    protected function normalizeContextFields(
        Entity $source,
        Entity $target,
        array $keys = ['innerKey', 'outerKey']
    ): void {
        foreach ($keys as $key) {
            $options = $this->options->getOptions();

            if (!isset($options[$key])) {
                continue;
            }

            $columns = (array)$options[$key];

            foreach ($columns as $i => $column) {
                $entity = $key === 'innerKey' ? $source : $target;

                if ($entity->getFields()->hasColumn($column)) {
                    $columns[$i] = $entity->getFields()->getKeyByColumnName($column);
                }
            }

            $this->options = $this->options->withOptions([
                $key => $columns,
            ]);
        }
    }

    /**
     * @param non-empty-string $fieldName
     */
    protected function ensureField(Entity $target, string $fieldName, Field $outerField, bool $nullable = false): void
    {
        // ensure that field will be indexed in memory for fast references
        $outerField->setReferenced(true);

        if ($target->getFields()->has($fieldName)) {
            // field already exists and defined by the user
            return;
        }

        $field = new Field();
        $field->setEntityClass($target->getClass());
        $field->setColumn($fieldName);
        $field->setTypecast($outerField->getTypecast());
        // Copy attributes from outer to target
        foreach ($outerField->getAttributes() as $k => $v) {
            $field->getAttributes()->set($k, $v);
        }

        switch ($outerField->getType()) {
            case 'primary':
                $field->setType('int');
                break;
            case 'bigPrimary':
                $field->setType('bigint');
                break;
            case 'smallPrimary':
                $field->setType('smallint');
                break;
            default:
                $field->setType($outerField->getType());
        }

        if ($nullable) {
            $field->getOptions()->set(Column::OPT_NULLABLE, true);
        }

        $target->getFields()->set($fieldName, $field);
    }

    abstract protected function getOptions(): OptionSchema;
}
