<?php

declare(strict_types=1);

namespace Cycle\Schema\Relation;

use Cycle\Database\Schema\AbstractTable;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Registry;
use Cycle\Schema\RelationInterface;

/**
 * Defines relation options, renders needed columns and other options.
 */
abstract class RelationSchema implements RelationInterface
{
    // relation rendering options
    public const INDEX_CREATE = 1001;
    public const FK_CREATE = 1002;
    public const FK_ACTION = 1003;
    public const FK_ON_DELETE = 1004;
    public const INVERSE = 1005;
    public const MORPH_KEY_LENGTH = 1009;
    public const EMBEDDED_PREFIX = 1010;

    // options to be excluded from generated schema (helpers)
    protected const EXCLUDE = [
        self::FK_CREATE,
        self::FK_ACTION,
        self::FK_ON_DELETE,
        self::INDEX_CREATE,
        self::EMBEDDED_PREFIX,
    ];

    // exported relation type
    protected const RELATION_TYPE = null;

    // name of all required relation options
    protected const RELATION_SCHEMA = [];

    /**
     * Relation container name in the entity
     */
    protected string $name;

    /**
     * @var non-empty-string
     */
    protected string $source;

    /**
     * @var non-empty-string
     */
    protected string $target;

    protected OptionSchema $options;

    /**
     * @param non-empty-string $role
     */
    public function withRole(string $role): static
    {
        $relation = clone $this;
        $relation->source = $role;
        return $relation;
    }

    public function modifySchema(array &$schema): void
    {
        $schema[SchemaInterface::RELATIONS][$this->name] = $this->packSchema();
    }

    /**
     * @param non-empty-string $source
     * @param non-empty-string $target
     */
    public function withContext(string $name, string $source, string $target, OptionSchema $options): RelationInterface
    {
        $relation = clone $this;
        $relation->source = $source;
        $relation->target = $target;
        $relation->name = $name;

        $relation->options = $options->withTemplate(static::RELATION_SCHEMA)->withContext([
            'relation' => $name,
            'source:role' => $source,
            'target:role' => $target,
        ]);

        return $relation;
    }

    public function compute(Registry $registry): void
    {
        $this->options = $this->options->withContext([
            'source:primaryKey' => $this->getPrimaryColumns($registry->getEntity($this->source)),
        ]);

        if ($registry->hasEntity($this->target)) {
            $this->options = $this->options->withContext([
                'target:primaryKey' => $this->getPrimaryColumns($registry->getEntity($this->target)),
            ]);
        }
    }

    protected function getLoadMethod(): ?int
    {
        if (!$this->options->has(Relation::LOAD)) {
            return null;
        }

        switch ($this->options->get(Relation::LOAD)) {
            case 'eager':
            case Relation::LOAD_EAGER:
                return Relation::LOAD_EAGER;
            default:
                return Relation::LOAD_PROMISE;
        }
    }

    protected function getOptions(): OptionSchema
    {
        return $this->options;
    }

    /**
     * @throws RegistryException
     */
    protected function getPrimaryColumns(Entity $entity): array
    {
        $columns = $entity->getPrimaryFields()->getNames();

        if ($columns === []) {
            throw new RegistryException("Entity `{$entity->getRole()}` must have defined primary key");
        }

        return $columns;
    }

    /**
     * @param array<string> $columns
     * @param bool $strictOrder True means that fields order in the {@see $columns} argument is matter
     * @param bool $withSorting True means that fields will be compared taking into account the column values sorting
     * @param bool|null $unique Unique index or not. Null means both
     */
    protected function hasIndex(
        AbstractTable $table,
        array $columns,
        bool $strictOrder = true,
        bool $withSorting = true,
        bool $unique = null
    ): bool {
        if ($strictOrder && $withSorting && $unique === null) {
            return $table->hasIndex($columns);
        }
        $indexes = $table->getIndexes();

        foreach ($indexes as $index) {
            if ($unique !== null && $index->isUnique() !== $unique) {
                continue;
            }
            $tableColumns = $withSorting ? $index->getColumnsWithSort() : $index->getColumns();

            if (count($columns) !== count($tableColumns)) {
                continue;
            }

            if ($strictOrder ? $columns === $tableColumns : array_diff($columns, $tableColumns) === []) {
                return true;
            }
        }
        return false;
    }

    private function packSchema(): array
    {
        $schema = [];

        foreach (static::RELATION_SCHEMA as $option => $template) {
            if (in_array($option, static::EXCLUDE, true)) {
                continue;
            }

            $schema[$option] = $this->options->get($option);
        }

        // load option is not required in schema
        unset($schema[Relation::LOAD]);

        return [
            Relation::TYPE => static::RELATION_TYPE,
            Relation::TARGET => $this->target,
            Relation::LOAD => $this->getLoadMethod(),
            Relation::SCHEMA => $schema,
        ];
    }
}
