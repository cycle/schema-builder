<?php

declare(strict_types=1);

namespace Cycle\Schema;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\RegistryException;
use Cycle\Schema\Exception\RelationException;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Exception\DBALException;
use Cycle\Database\Schema\AbstractTable;
use Traversable;

/**
 * @implements \IteratorAggregate<Entity>
 */
final class Registry implements \IteratorAggregate
{
    /** @var Entity[] */
    private array $entities = [];
    private DatabaseProviderInterface $dbal;
    private \SplObjectStorage $tables;
    private \SplObjectStorage $children;
    private \SplObjectStorage $relations;
    private Defaults $defaults;

    /**
     * @param DatabaseProviderInterface $dbal
     */
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

    public function getIterator(): Traversable
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

        $schema = null;
        foreach ($this->tables as $other) {
            $association = $this->tables[$other];

            if ($association === null) {
                continue;
            }

            // avoid schema duplication
            if ($association['database'] === $database && $association['table'] === $table) {
                $schema = $association['schema'];
                break;
            }
        }

        if (null === $schema) {
            $dbTable = $this->dbal->database($database)->table($table);
            if (!\method_exists($dbTable, 'getSchema')) {
                throw new RegistryException('Unable to retrieve table schema.');
            }
            $schema = $dbTable->getSchema();
        }

        $this->tables[$entity] = [
            'database' => $database,
            'table' => $table,
            'schema' => $schema,
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
        if (!$this->hasTable($entity)) {
            throw new RegistryException("Entity `{$entity->getRole()}` has no assigned table");
        }

        return $this->tables[$entity]['database'];
    }

    /**
     * @throws RegistryException
     *
     * @return non-empty-string
     */
    public function getTable(Entity $entity): string
    {
        if (!$this->hasTable($entity)) {
            throw new RegistryException("Entity `{$entity->getRole()}` has no assigned table");
        }

        return $this->tables[$entity]['table'];
    }

    /**
     * @throws RegistryException
     */
    public function getTableSchema(Entity $entity): AbstractTable
    {
        if (!$this->hasTable($entity)) {
            throw new RegistryException("Entity `{$entity->getRole()}` has no assigned table");
        }

        return $this->tables[$entity]['schema'];
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
}
