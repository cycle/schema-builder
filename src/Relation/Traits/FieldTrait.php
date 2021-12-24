<?php

declare(strict_types=1);

namespace Cycle\Schema\Relation\Traits;

use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
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
                    $entity->getRole(),
                    $this->getOptions()->get($field),
                    $this->source
                ),
                $e->getCode(),
                $e
            );
        }
    }

    protected function getFields(Entity $entity, int $option, AbstractTable $table = null): FieldMap
    {
        $fields = new FieldMap();
        $keys = (array)$this->getOptions()->get($option);

        foreach ($keys as $key) {
            try {
                if ($entity->getFields()->has($key)) {
                    $field = $entity->getFields()->get($key);
                    $name = $key;
                } else {
                    $field = $entity->getFields()->getByColumnName($key);
                    $name = $entity->getFields()->getKeyByColumnName($key);
                }

                $fields->set($name, $field);
            } catch (FieldException $e) {
                if ($table === null || !$table->hasColumn($key)) {
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

                $fields->set($key, $this->createField($key, $entity, $table->column($key)));
            }
        }

        return $fields;
    }

    protected function ensureField(Entity $target, string $column, Field $outer, bool $nullable = false): void
    {
        // ensure that field will be indexed in memory for fast references
        $outer->setReferenced(true);

        if ($target->getFields()->has($column) || $target->getFields()->hasColumn($column)) {
            // field already exists and defined by the user
            return;
        }

        $field = new Field();
        $field->setEntityClass($target->getClass());
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

    protected function createRelatedFields(
        Entity $source,
        int $sourceKey,
        AbstractTable $sourceTable,
        Entity $target,
        int $targetKey
    ): void {
        foreach (['innerKey', 'outerKey'] as $key) {
            $options = $this->options->getOptions();

            if (! isset($options[$key])) {
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

        $sourceFields = $this->getFields($source, $sourceKey, $sourceTable);
        $targetColumns = (array)$this->options->get($targetKey);

        $sourceFieldNames = $sourceFields->getNames();

        if (count($targetColumns) !== count($sourceFieldNames)) {
            throw new RegistryException(
                sprintf(
                    'Inconsistent amount of related fields. '
                    . 'Source entity: `%s`; keys: `%s`. Target entity: `%s`; keys: `%s`.',
                    $source->getRole(),
                    implode('`, `', $this->getFields($source, $sourceKey)->getColumnNames()),
                    $target->getRole(),
                    implode('`, `', $targetColumns)
                )
            );
        }

        $fields = array_combine($targetColumns, $sourceFieldNames);

        foreach ($fields as $targetColumn => $sourceFieldName) {
            $sourceField = $sourceFields->get($sourceFieldName);

            if (!$source->getFields()->has($sourceFieldName)) {
                $source->getFields()->set($sourceFieldName, $sourceField);
            }

            $this->ensureField(
                $target,
                $targetColumn,
                $sourceField,
                $this->options->get(Relation::NULLABLE)
            );
        }
    }

    protected function createField(string $name, Entity $entity, AbstractColumn $column): Field
    {
        $field = new Field();
        $field->setEntityClass($entity->getClass());
        $field->setColumn($name);
        $field->setType($column->getType());

        if ($column->isNullable()) {
            $field->getOptions()->set(Column::OPT_NULLABLE, true);
        }

        return $field;
    }

    abstract protected function getOptions(): OptionSchema;
}
