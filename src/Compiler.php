<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\Schema\Definition\Comparator\FieldComparator;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Spiral\Database\Exception\CompilerException;

final class Compiler
{
    /** @var array */
    private $result = [];

    /** @var array */
    private $defaults = [
        Schema::MAPPER => Mapper::class,
        Schema::REPOSITORY => Repository::class,
        Schema::SOURCE => Source::class,
        Schema::CONSTRAIN => null,
    ];

    /** @var \Doctrine\Inflector\Inflector */
    private $inflector;

    public function __construct()
    {
        $this->inflector = (new \Doctrine\Inflector\Rules\English\InflectorFactory())->build();
    }

    /**
     * Compile the registry schema.
     *
     * @param Registry $registry
     * @param GeneratorInterface[] $generators
     * @param array $defaults
     * @return array
     *
     */
    public function compile(Registry $registry, array $generators = [], array $defaults = []): array
    {
        $this->defaults = $defaults + $this->defaults;

        foreach ($generators as $generator) {
            if (!$generator instanceof GeneratorInterface) {
                throw new CompilerException(sprintf(
                    'Invalid generator `%s`',
                    is_object($generator) ? get_class($generator) : gettype($generator)
                ));
            }

            $registry = $generator->run($registry);
        }

        foreach ($registry->getIterator() as $entity) {
            if ($this->getPrimary($entity) === null) {
                // incomplete entity, skip
                continue;
            }

            $this->compute($registry, $entity);
        }

        return $this->result;
    }

    /**
     * Get compiled schema result.
     *
     * @return array
     */
    public function getSchema(): array
    {
        return $this->result;
    }

    /**
     * Compile entity and relation definitions into packed ORM schema.
     *
     * @param Registry $registry
     * @param Entity   $entity
     */
    protected function compute(Registry $registry, Entity $entity): void
    {
        $schema = [
            Schema::ENTITY       => $entity->getClass(),
            Schema::SOURCE       => $entity->getSource() ?? $this->defaults[Schema::SOURCE],
            Schema::MAPPER       => $entity->getMapper() ?? $this->defaults[Schema::MAPPER],
            Schema::REPOSITORY   => $entity->getRepository() ?? $this->defaults[Schema::REPOSITORY],
            Schema::CONSTRAIN    => $entity->getConstrain() ?? $this->defaults[Schema::CONSTRAIN],
            Schema::SCHEMA       => $entity->getSchema(),
            Schema::PRIMARY_KEY  => $this->getPrimary($entity),
            Schema::COLUMNS      => $this->renderColumns($entity),
            Schema::FIND_BY_KEYS => $this->renderReferences($entity),
            Schema::TYPECAST     => $this->renderTypecast($entity),
            Schema::RELATIONS    => $this->renderRelations($registry, $entity)
        ];

        if ($registry->hasTable($entity)) {
            $schema[Schema::DATABASE] = $registry->getDatabase($entity);
            $schema[Schema::TABLE] = $registry->getTable($entity);
        }

        // table inheritance
        foreach ($registry->getChildren($entity) as $child) {
            $this->result[$child->getClass()] = [Schema::ROLE => $entity->getRole()];
            $schema[Schema::CHILDREN][$this->childAlias($child)] = $child->getClass();
        }

        ksort($schema);
        $this->result[$entity->getRole()] = $schema;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    protected function renderColumns(Entity $entity): array
    {
        // Check field duplicates
        /** @var Field[][] $fieldGroups */
        $fieldGroups = [];
        // Collect and group fields by column name
        foreach ($entity->getFields() as $name => $field) {
            $fieldGroups[$field->getColumn()][$name] = $field;
        }
        foreach ($fieldGroups as $fieldName => $fields) {
            // We need duplicates only
            if (count($fields) === 1) {
                continue;
            }
            // Compare
            $comparator = new FieldComparator();
            foreach ($fields as $name => $field) {
                $comparator->addField($name, $field);
            }
            try {
                $comparator->compare();
            } catch (\Throwable $e) {
                throw new Exception\CompilerException(
                    sprintf("Error compiling the `%s` role.\n\n%s", $entity->getRole(), $e->getMessage()),
                    $e->getCode()
                );
            }
        }

        $schema = [];
        foreach ($entity->getFields() as $name => $field) {
            $schema[$name] = $field->getColumn();
        }

        return $schema;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    protected function renderTypecast(Entity $entity): array
    {
        $schema = [];
        foreach ($entity->getFields() as $name => $field) {
            if ($field->hasTypecast()) {
                $schema[$name] = $field->getTypecast();
            }
        }

        return $schema;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    protected function renderReferences(Entity $entity): array
    {
        $schema = [$this->getPrimary($entity)];

        foreach ($entity->getFields() as $name => $field) {
            if ($field->isReferenced()) {
                $schema[] = $name;
            }
        }

        return array_unique($schema);
    }

    /**
     * @param Registry $registry
     * @param Entity   $entity
     * @return array
     */
    protected function renderRelations(Registry $registry, Entity $entity): array
    {
        $result = [];
        foreach ($registry->getRelations($entity) as $name => $relation) {
            $result[$name] = $relation->packSchema();
        }

        return $result;
    }

    /**
     * @param Entity $entity
     * @return string|null
     */
    protected function getPrimary(Entity $entity): ?string
    {
        foreach ($entity->getFields() as $name => $field) {
            if ($field->isPrimary()) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Return the unique alias for the child entity.
     *
     * @param Entity $entity
     * @return string
     */
    protected function childAlias(Entity $entity): string
    {
        $r = new \ReflectionClass($entity->getClass());

        return $this->inflector->classify($r->getShortName());
    }
}
