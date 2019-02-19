<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\Schema\Map\FieldMap;
use Cycle\Schema\Map\RelMap;

/**
 * Contains information about specific entity definition.
 */
final class Entity
{
    /** @var string */
    private $role;

    /** @var string|null */
    private $class;

    /** @var string|null */
    private $mapper;

    /** @var string|null */
    private $source;

    /** @var string|null */
    private $constrain;

    /** @var string|null */
    private $repository;

    /** @var FieldMap */
    private $fields;

    /** @var RelMap */
    private $relMap;

    /**
     * Entity constructor.
     */
    public function __construct()
    {
        $this->fields = new FieldMap();
        $this->relMap = new RelMap();
    }

    /**
     * @param string $role
     * @return Entity
     */
    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /***
     * @param string $class
     * @return Entity
     */
    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @param string|null $mapper
     * @return Entity
     */
    public function setMapper(?string $mapper): self
    {
        $this->mapper = $mapper;

        return $this;
    }

    /**
     * @return string
     */
    public function getMapper(): string
    {
        return $this->mapper ?? Mapper::class;
    }

    /**
     * @param string|null $source
     * @return Entity
     */
    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source ?? Source::class;
    }

    /**
     * @param string|null $constrain
     * @return Entity
     */
    public function setConstrain(?string $constrain): self
    {
        $this->constrain = $constrain;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getConstrain(): ?string
    {
        return $this->constrain;
    }

    /**
     * @param string|null $repository
     * @return Entity
     */
    public function setRepository(?string $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @return string
     */
    public function getRepository(): string
    {
        return $this->repository ?? Repository::class;
    }

    /**
     * @return FieldMap
     */
    public function getFields(): FieldMap
    {
        return $this->fields;
    }

    /**
     * @return RelMap
     */
    public function getRelations(): RelMap
    {
        return $this->relMap;
    }

    /**
     * Pack entity schema into internal representation.
     *
     * @return array
     */
    public function packSchema(): array
    {
        $schema = [
            Schema::MAPPER     => $this->getMapper(),
            Schema::SOURCE     => $this->getSource(),
            Schema::REPOSITORY => $this->getRepository(),
            Schema::CONSTRAIN  => $this->getConstrain(),
            Schema::COLUMNS    => $this->fields->packColumns(),
            Schema::TYPECAST   => $this->fields->packTypecast()
        ];

        // todo: additional schema
        // todo: pack the relation map
        // todo: where to glue table and database information

        if (isset($this->class)) {
            $schema[Schema::ENTITY] = $this->getClass();
        }

        return $schema;
    }
}