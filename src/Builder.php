<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Exception\BuilderException;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Exception\DBALException;

class Builder
{
    /** @var DatabaseManager */
    private $dbal;

    /** @var Entity[] */
    private $entities = [];

    /** @var \SplObjectStorage */
    private $tables;

    /** @var \SplObjectStorage */
    private $children;


    private $relations;

    /**
     * @param DatabaseManager $dbal
     */
    public function __construct(DatabaseManager $dbal)
    {
        $this->dbal = $dbal;
        $this->tables = new \SplObjectStorage();
        $this->children = new \SplObjectStorage();
    }

    /**
     * @param Entity $entity
     */
    public function register(Entity $entity)
    {
        $this->entities[] = $entity;
    }

    /**
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        foreach ($this->entities as $entity) {
            if ($entity->getRole() == $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function hasEntity(Entity $entity): bool
    {
        return array_search($entity, $this->entities, true) !== false;
    }

    /**
     * Get entity by it's role.
     *
     * @param string $role
     * @return Entity
     *
     * @throws BuilderException
     */
    public function getEntity(string $role): Entity
    {
        foreach ($this->entities as $entity) {
            if ($entity->getRole() == $role || $entity->getClass() === $role) {
                return $entity;
            }
        }

        throw new BuilderException("Undefined entity `{$role}`");
    }

    /**
     * Assign child entity to parent entity.
     *
     * @param Entity $parent
     * @param Entity $child
     *
     * @throws BuilderException
     */
    public function registerChild(Entity $parent, Entity $child)
    {
        if (!$this->hasEntity($parent)) {
            throw new BuilderException("Undefined entity `{$parent->getRole()}`");
        }

        if (!$this->children->contains($parent)) {
            $this->children[$parent] = [];
        }

        $this->children[$parent][] = $child;

        // merge parent and child schema
        $parent->merge($child);
    }

    /**
     * Get all assigned children entities.
     *
     * @param Entity $entity
     * @return array
     */
    public function getChildren(Entity $entity): array
    {
        if (!$this->hasEntity($entity)) {
            throw new BuilderException("Undefined entity `{$entity->getRole()}`");
        }

        if (!$this->children->contains($entity)) {
            return [];
        }

        return $this->children[$entity];
    }

    /**
     * Associate entity with table.
     *
     * @param Entity      $entity
     * @param string|null $database
     * @param string      $table
     *
     * @throws BuilderException
     * @throws DBALException
     */
    public function linkTable(Entity $entity, ?string $database, string $table)
    {
        if (!$this->hasEntity($entity)) {
            throw new BuilderException("Undefined entity `{$entity->getRole()}`");
        }

        $this->tables[$entity] = $this->dbal->database($database)->table($table)->getSchema();
    }

    public function getEntities()
    {

    }
}