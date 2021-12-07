<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition;

use Cycle\Schema\Definition\Map\FieldMap;
use Cycle\Schema\Definition\Map\OptionMap;
use Cycle\Schema\Definition\Map\RelationMap;
use Cycle\Schema\Exception\EntityException;
use Cycle\Schema\SchemaModifierInterface;

/**
 * Contains information about specific entity definition.
 */
final class Entity
{
    private OptionMap $options;

    private ?string $role = null;

    private ?string $class = null;

    private ?string $database = null;

    private ?string $tableName = null;

    private ?string $mapper = null;

    private ?string $source = null;

    private ?string $scope = null;

    private ?string $repository = null;

    private array|string|null $typecast = null;

    private array $schema = [];

    private FieldMap $fields;

    private RelationMap $relations;
    private FieldMap $primaryFields;
    private array $schemaModifiers = [];
    private ?Inheritance $inheritance = null;
    /** @var class-string|null */
    private ?string $stiParent = null;

    public function __construct()
    {
        $this->options = new OptionMap();
        $this->fields = new FieldMap();
        $this->primaryFields = new FieldMap();
        $this->relations = new RelationMap();
    }

    /**
     * Full entity copy.
     */
    public function __clone()
    {
        $this->options = clone $this->options;
        $this->fields = clone $this->fields;
        $this->primaryFields = clone $this->primaryFields;
        $this->relations = clone $this->relations;
    }

    public function getOptions(): OptionMap
    {
        return $this->options;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setMapper(?string $mapper): self
    {
        $this->mapper = $mapper;

        return $this;
    }

    public function getMapper(): ?string
    {
        return $this->normalizeClass($this->mapper);
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->normalizeClass($this->source);
    }

    public function setScope(?string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->normalizeClass($this->scope);
    }

    public function setRepository(?string $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    public function getRepository(): ?string
    {
        return $this->normalizeClass($this->repository);
    }

    /**
     * @param class-string|class-string[]|non-empty-string|non-empty-string[]|null $typecast
     *
     * @return $this
     */
    public function setTypecast(array|string|null $typecast): self
    {
        $this->typecast = $typecast;

        return $this;
    }

    /**
     * @return class-string|class-string[]|non-empty-string|non-empty-string[]|null
     */
    public function getTypecast(): array|string|null
    {
        return $this->typecast;
    }

    public function getFields(): FieldMap
    {
        return $this->fields;
    }

    public function getRelations(): RelationMap
    {
        return $this->relations;
    }

    public function addSchemaModifier(SchemaModifierInterface $modifier): self
    {
        $this->schemaModifiers[] = $modifier;
        return $this;
    }

    /**
     * @return \Traversable<array-key, SchemaModifierInterface>
     */
    public function getSchemaModifiers(): \Traversable
    {
        // yield from $this->getRelations();
        yield from $this->schemaModifiers;
    }

    public function setSchema(array $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * Merge entity relations and fields.
     */
    public function merge(self $entity): void
    {
        foreach ($entity->getRelations() as $name => $relation) {
            if (!$this->relations->has($name)) {
                $this->relations->set($name, $relation);
            }
        }

        foreach ($entity->getFields() as $name => $field) {
            if (!$this->fields->has($name)) {
                $this->fields->set($name, $field);
            }
        }
    }

    /**
     * Check if entity has primary key
     */
    public function hasPrimaryKey(): bool
    {
        if ($this->primaryFields->count() > 0) {
            return true;
        }

        foreach ($this->getFields() as $field) {
            if ($field->isPrimary()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set primary key using column list
     *
     * @param string[] $columns
     */
    public function setPrimaryColumns(array $columns): void
    {
        $this->primaryFields = new FieldMap();

        foreach ($columns as $column) {
            $name = $this->fields->getKeyByColumnName($column);
            $this->primaryFields->set($name, $this->fields->get($name));
        }
    }

    /**
     * Get entity primary key property names
     */
    public function getPrimaryFields(): FieldMap
    {
        $map = new FieldMap();

        foreach ($this->getFields() as $name => $field) {
            if ($field->isPrimary()) {
                $map->set($name, $field);
            }
        }

        if ($this->primaryFields->count() === 0 xor $map->count() === 0) {
            return $map->count() === 0 ? $this->primaryFields : $map;
        }

        if (
            $this->primaryFields->count() !== $map->count()
            || array_diff($map->getColumnNames(), $this->primaryFields->getColumnNames()) !== []
        ) {
            // todo make friendly exception
            throw new EntityException("Ambiguous primary key definition for `{$this->getRole()}`.");
        }

        return $this->primaryFields;
    }

    private function normalizeClass(string $class = null): ?string
    {
        if ($class === null) {
            return null;
        }

        return ltrim($class, '\\');
    }

    public function setInheritance(Inheritance $inheritance): void
    {
        $this->inheritance = $inheritance;
    }

    public function getInheritance(): ?Inheritance
    {
        return $this->inheritance;
    }

    /**
     * Check if entity is a child of STI
     */
    public function isChildOfSingleTableInheritance(): bool
    {
        return $this->stiParent !== null;
    }

    /**
     * @param class-string|null $parentClass
     */
    public function markAsChildOfSingleTableInheritance(?string $parentClass): void
    {
        $this->stiParent = $parentClass;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function setDatabase(?string $database): void
    {
        $this->database = $database;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }
}
