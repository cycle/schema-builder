<?php

declare(strict_types=1);

namespace Cycle\Schema\Relation\Traits;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Map\FieldMap;
use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Registry;
use Cycle\Schema\Table\Column;
use Generator;

trait MorphTrait
{
    /**
     * @psalm-param non-empty-string $interface
     *
     * @psalm-assert class-string $interface
     *
     * @return Entity[]|Generator
     *
     * @psalm-return Generator<int, Entity>
     */
    protected function findTargets(Registry $registry, string $interface): Generator
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
     * @param non-empty-string $interface
     *
     * @throws RelationException
     *
     * @return array Tuple [name, Field]
     */
    protected function findOuterKey(Registry $registry, string $interface): array
    {
        $keys = null;
        $fields = null;
        $prevEntity = null;

        foreach ($this->findTargets($registry, $interface) as $entity) {
            $primaryFields = $entity->getPrimaryFields();
            $primaryKeys = $this->getPrimaryColumns($entity);

            if (null === $keys) {
                $keys = $primaryKeys;
                $fields = $primaryFields;
                $prevEntity = $entity;
            } elseif ($keys !== $primaryKeys && $prevEntity !== null) {
                throw new RelationException(sprintf(
                    'Inconsistent primary key reference (%s). PKs: (%s). Required PKs [%s]: (%s).',
                    $entity->getRole() ?? 'unknown',
                    implode(',', $primaryKeys),
                    $prevEntity->getRole() ?? 'unknown',
                    implode(',', $keys)
                ));
            }
        }

        if (null === $fields) {
            throw new RelationException('Unable to find morphed parent.');
        }

        return [$keys, $fields];
    }

    /**
     * @param non-empty-string $column
     */
    protected function ensureMorphField(Entity $target, string $column, int $length, bool $nullable = false): void
    {
        if ($target->getFields()->has($column)) {
            // field already exists and defined by the user
            return;
        }

        $field = new Field();
        $field->setEntityClass($target->getClass());
        $field->setColumn($column);
        $field->setType(sprintf('string(%s)', $length));

        if ($nullable) {
            $field->getOptions()->set(Column::OPT_NULLABLE, true);
        }

        $target->getFields()->set($column, $field);
    }

    protected function mergeIndex(Registry $registry, Entity $source, FieldMap ...$mergeMaps): void
    {
        $table = $registry->getTableSchema($source);

        if ($this->options->get(self::INDEX_CREATE)) {
            /** @psalm-suppress NamedArgumentNotAllowed */
            $index = array_merge(...array_map(
                static function (FieldMap $map): array {
                    return $map->getColumnNames();
                },
                $mergeMaps
            ));

            if (count($index) > 0) {
                $table->index($index);
            }
        }
    }
}
