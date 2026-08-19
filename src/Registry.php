<?php

declare(strict_types=1);

namespace Cycle\Schema;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Exception\RelationException;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Exception\DBALException;
use Cycle\Database\Schema\AbstractTable;

/**
 * @implements \IteratorAggregate<Entity>
 */
final class Registry implements \IteratorAggregate
{
    /** @var Entity[] */
    private array $entities = [];

    private DatabaseProviderInterface $dbal;

    /**
     * @var \SplObjectStorage<
     *     Entity,
     *     array{database: string, table: non-empty-string, schema: AbstractTable|null}|null
     * >
     */
    private \SplObjectStorage $tables;

    private \SplObjectStorage $children;
    private \SplObjectStorage $relations;
    private Defaults $defaults;

    public function __construct(DatabaseProviderInterface $dbal, ?Defaults $defaults = null)
    {
        $this->dbal = $dbal;
        $this->tables = new \SplObjectStorage();
        $this->children = new \SplObjectStorage();
        $this->relations = new \SplObjectStorage();
        $this->defaults = $defaults ?? new Defaults();
    }

    public function register(Entity $entity): self
    {
        foreach ($this->entities as $e) {
            if ($e->getRole() == $entity->getRole()) {
                throw new RegistryException("Duplicate entity `{$e->getRole()}`");
            }
        }

        $this->entities[] = $entity;
        $this->tables[$entity] = null;
        $this->children[$entity] = [];
        $this->relations[$entity] = [];

        return $this;
    }

    /**
     * @param string $role Entity role of class.
     */
    public function hasEntity(string $role): bool
    {
        foreach ($this->entities as $entity) {
            if ($entity->getRole() === $role || $entity->getClass() === $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get entity by it's role.
     *
     * @param string $role Entity role or class name.
     *
     * @throws RegistryException
     */
    public function getEntity(string $role): Entity
    {
        foreach ($this->entities as $entity) {
            if ($entity->getRole() == $role || $entity->getClass() === $role) {
                return $entity;
            }
        }

        throw new RegistryException("Undefined entity `{$role}`");
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->entities);
    }

    /**
     * Assign child entity to parent entity.
     * Be careful! This method merges the parent and child entity schemas.
     * If you don't need to merge schemas {@see Registry::registerChildWithoutMerge()}.
     *
     * @throws RegistryException
     */
    public function registerChild(Entity $parent, Entity $child): void
    {
        $this->registerChildWithoutMerge($parent, $child);

        // merge parent and child schema
        $parent->merge($child);
    }

    public function registerChildWithoutMerge(Entity $parent, Entity $child): void
    {
        if (!$this->hasInstance($parent)) {
            throw new RegistryException("Undefined entity `{$parent->getRole()}`");
        }

        $children = $this->children[$parent];
        $children[] = $child;
        $this->children[$parent] = $children;
    }

    /**
     * Get all assigned children entities.
     *
     * @return Entity[]
     */
    public function getChildren(Entity $entity): array
    {
        if (!$this->hasInstance($entity)) {
            throw new RegistryException("Undefined entity `{$entity->getRole()}`");
        }

        return $this->children[$entity];
    }

    /**
     * Associate entity with table.
     *
     * @param non-empty-string $table
     *
     * @throws RegistryException
     * @throws DBALException
     */
    public function linkTable(Entity $entity, ?string $database, string $table): self
    {
        if (!$this->hasInstance($entity)) {
            throw new RegistryException("Undefined entity `{$entity->getRole()}`");
        }

        $database = $this->dbal->database($database)->getName();

        // Table schemas are loaded lazily and in bulk: by the time the first schema is requested
        // all the linked tables are known, so the whole set costs a constant number of queries.
        $this->tables[$entity] = [
            'database' => $database,
            'table' => $table,
            'schema' => null,
        ];

        return $this;
    }

    /**
     * @throws RegistryException
     */
    public function hasTable(Entity $entity): bool
    {
        if (!$this->hasInstance($entity)) {
            throw new RegistryException("Undefined entity `{$entity->getRole()}`");
        }

        return $this->tables[$entity] !== null;
    }

    /**
     * @throws RegistryException
     */
    public function getDatabase(Entity $entity): string
    {
        return $this->getTableAssociation($entity)['database'];
    }

    /**
     * @return non-empty-string
     * @throws RegistryException
     *
     */
    public function getTable(Entity $entity): string
    {
        return $this->getTableAssociation($entity)['table'];
    }

    /**
     * @throws RegistryException
     */
    public function getTableSchema(Entity $entity): AbstractTable
    {
        $schema = $this->getTableAssociation($entity)['schema'];

        if ($schema === null) {
            $this->loadTableSchemas();
            $schema = $this->getTableAssociation($entity)['schema'];
            \assert($schema !== null);
        }

        return $schema;
    }

    /**
     * Create entity relation.
     *
     * @throws RegistryException
     * @throws RelationException
     */
    public function registerRelation(Entity $entity, string $name, RelationInterface $relation): void
    {
        if (!$this->hasInstance($entity)) {
            throw new RegistryException("Undefined entity `{$entity->getRole()}`");
        }

        $relations = $this->relations[$entity];
        $relations[$name] = $relation;
        $this->relations[$entity] = $relations;
    }

    /**
     * @throws RegistryException
     */
    public function hasRelation(Entity $entity, string $name): bool
    {
        if (!$this->hasInstance($entity)) {
            throw new RegistryException("Undefined entity `{$entity->getRole()}`");
        }

        return isset($this->relations[$entity][$name]);
    }

    public function getRelation(Entity $entity, string $name): RelationInterface
    {
        if (!$this->hasRelation($entity, $name)) {
            throw new RegistryException("Undefined relation `{$entity->getRole()}`.`{$name}`");
        }

        return $this->relations[$entity][$name];
    }

    /**
     * Get all relations assigned with given entity.
     *
     * @return RelationInterface[]
     */
    public function getRelations(Entity $entity): array
    {
        if (!$this->hasInstance($entity)) {
            throw new RegistryException("Undefined entity `{$entity->getRole()}`");
        }

        return $this->relations[$entity];
    }

    public function getDefaults(): Defaults
    {
        return $this->defaults;
    }

    protected function hasInstance(Entity $entity): bool
    {
        return array_search($entity, $this->entities, true) !== false;
    }

    /**
     * @return array{database: string, table: non-empty-string, schema: AbstractTable|null}
     *
     * @throws RegistryException
     */
    private function getTableAssociation(Entity $entity): array
    {
        if (!$this->hasInstance($entity)) {
            throw new RegistryException("Undefined entity `{$entity->getRole()}`");
        }

        $association = $this->tables[$entity];

        if ($association === null) {
            throw new RegistryException("Entity `{$entity->getRole()}` has no assigned table");
        }

        return $association;
    }

    /**
     * Load schemas for all the linked tables that don't have one yet. Entities sharing the same
     * database and table receive the same {@see AbstractTable} instance.
     *
     * @throws RegistryException
     * @throws DBALException
     */
    private function loadTableSchemas(): void
    {
        /** @var array<string, array<non-empty-string, AbstractTable>> $loaded */
        $loaded = [];
        /** @var array<string, array<non-empty-string, true>> $pending */
        $pending = [];
        foreach ($this->tables as $entity) {
            $association = $this->tables[$entity];
            if ($association === null) {
                continue;
            }

            if ($association['schema'] !== null) {
                $loaded[$association['database']][$association['table']] = $association['schema'];
            } else {
                $pending[$association['database']][$association['table']] = true;
            }
        }

        foreach ($pending as $database => $tables) {
            // avoid schema duplication
            $names = \array_keys(\array_diff_key($tables, $loaded[$database] ?? []));
            if ($names === []) {
                continue;
            }

            $db = $this->dbal->database($database);
            if (\method_exists($db, 'getSchemas')) {
                /** @var array<non-empty-string, AbstractTable> $schemas */
                $schemas = $db->getSchemas($names);
                $loaded[$database] = ($loaded[$database] ?? []) + $schemas;
                continue;
            }

            foreach ($names as $name) {
                $dbTable = $db->table($name);
                if (!\method_exists($dbTable, 'getSchema')) {
                    throw new RegistryException('Unable to retrieve table schema.');
                }
                /** @var AbstractTable $schema */
                $schema = $dbTable->getSchema();
                $loaded[$database][$name] = $schema;
            }
        }

        foreach ($this->tables as $entity) {
            $association = $this->tables[$entity];
            if ($association === null || $association['schema'] !== null) {
                continue;
            }

            $association['schema'] = $loaded[$association['database']][$association['table']];
            $this->tables[$entity] = $association;
        }
    }
}
