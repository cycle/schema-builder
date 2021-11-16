<?php

declare(strict_types=1);

namespace Cycle\Schema;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\SchemaInterface as Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\Schema\Definition\Comparator\FieldComparator;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Database\Exception\CompilerException;
use Cycle\Schema\Definition\Inheritance\JoinedTable;
use Cycle\Schema\Definition\Inheritance\SingleTable;
use Cycle\Schema\Exception\SchemaModifierException;
use Cycle\Schema\Exception\TableInheritance\DiscriminatorColumnNotPresentException;
use Cycle\Schema\Exception\TableInheritance\WrongDiscriminatorColumnException;
use Cycle\Schema\Exception\TableInheritance\WrongParentKeyColumnException;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\Rules\English\InflectorFactory;
use Throwable;

final class Compiler
{
    /** @var array<non-empty-string, array<int, mixed>> */
    private array $result = [];

    /** @var array<int, mixed> */
    private array $defaults = [
        Schema::MAPPER => Mapper::class,
        Schema::REPOSITORY => Repository::class,
        Schema::SOURCE => Source::class,
        Schema::SCOPE => null,
        Schema::TYPECAST_HANDLER => null,
    ];

    private Inflector $inflector;

    public function __construct()
    {
        $this->inflector = (new InflectorFactory())->build();
    }

    /**
     * Compile the registry schema.
     *
     * @param GeneratorInterface[] $generators
     */
    public function compile(Registry $registry, array $generators = [], array $defaults = []): array
    {
        $this->defaults = $defaults + $this->defaults;

        foreach ($generators as $generator) {
            if (!$generator instanceof GeneratorInterface) {
                throw new CompilerException(
                    sprintf(
                        'Invalid generator `%s`.',
                        is_object($generator) ? get_class($generator) : gettype($generator)
                    )
                );
            }

            $registry = $generator->run($registry);
        }

        foreach ($registry->getIterator() as $entity) {
            if (!$entity->hasPrimaryKey()) {
                // incomplete entity, skip
                continue;
            }

            $this->compute($registry, $entity);
        }

        return $this->result;
    }

    /**
     * Get compiled schema result.
     */
    public function getSchema(): array
    {
        return $this->result;
    }

    /**
     * Compile entity and relation definitions into packed ORM schema.
     */
    private function compute(Registry $registry, Entity $entity): void
    {
        $schema = [
            Schema::ENTITY => $entity->getClass(),
            Schema::SOURCE => $entity->getSource() ?? $this->defaults[Schema::SOURCE],
            Schema::MAPPER => $entity->getMapper() ?? $this->defaults[Schema::MAPPER],
            Schema::REPOSITORY => $entity->getRepository() ?? $this->defaults[Schema::REPOSITORY],
            Schema::SCOPE => $entity->getScope() ?? $this->defaults[Schema::SCOPE],
            Schema::SCHEMA => $entity->getSchema(),
            Schema::TYPECAST_HANDLER => $entity->getTypecast() ?? $this->defaults[Schema::TYPECAST_HANDLER],
            Schema::PRIMARY_KEY => $entity->getPrimaryFields()->getNames(),
            Schema::COLUMNS => $this->renderColumns($entity),
            Schema::FIND_BY_KEYS => $this->renderReferences($entity),
            Schema::TYPECAST => $this->renderTypecast($entity),
            Schema::RELATIONS => [],
        ];

        // For table inheritance we need to fill specific schema segments
        $inheritance = $entity->getInheritance();
        if ($inheritance instanceof SingleTable) {
            // Check if discriminator column defined and is not null or empty
            if ($inheritance->getDiscriminator() === null || $inheritance->getDiscriminator() === '') {
                throw new DiscriminatorColumnNotPresentException($entity);
            }
            if (!$entity->getFields()->has($inheritance->getDiscriminator())) {
                throw new WrongDiscriminatorColumnException($entity, $inheritance->getDiscriminator());
            }

            $schema[Schema::CHILDREN] = $inheritance->getChildren();
            $schema[Schema::DISCRIMINATOR] = $inheritance->getDiscriminator();
        } elseif ($inheritance instanceof JoinedTable) {
            $schema[Schema::PARENT] = $inheritance->getParent()->getRole();

            $parent = $registry->getEntity($inheritance->getParent()->getRole());
            if ($inheritance->getOuterKey()) {
                if (!$parent->getFields()->has($inheritance->getOuterKey())) {
                    throw new WrongParentKeyColumnException($parent, $inheritance->getOuterKey());
                }
                $schema[Schema::PARENT_KEY] = $inheritance->getOuterKey();
            }
        }

        $this->renderRelations($registry, $entity, $schema);

        // Note: backward compatibility for ORM v1
//        foreach ($registry->getChildren($entity) as $child) {
//            $this->result[$child->getClass()] = [
//                Schema::ROLE => $entity->getRole(),
//            ];
//        }

        if ($registry->hasTable($entity)) {
            $schema[Schema::DATABASE] = $registry->getDatabase($entity);
            $schema[Schema::TABLE] = $registry->getTable($entity);
        }

        // Apply modifiers
        foreach ($entity->getSchemaModifiers() as $modifier) {
            try {
                $modifier->modifySchema($schema);
            } catch (Throwable $e) {
                throw new SchemaModifierException(
                    sprintf(
                        'Unable to apply schema modifier `%s` for the `%s` role. %s',
                        $modifier::class,
                        (string)$entity->getRole(),
                        $e->getMessage()
                    ),
                    (int)$e->getCode(),
                    $e
                );
            }
        }

        // For STI child we need only schema role as a key and entity segment
        if ($entity->isChildOfSingleTableInheritance()) {
            $schema = array_intersect_key($schema, [Schema::ENTITY, Schema::ROLE]);
        }

        ksort($schema);
        $this->result[(string)$entity->getRole()] = $schema;
    }

    private function renderColumns(Entity $entity): array
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
            } catch (Throwable $e) {
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

    private function renderTypecast(Entity $entity): array
    {
        $schema = [];
        foreach ($entity->getFields() as $name => $field) {
            if ($field->hasTypecast()) {
                $schema[$name] = $field->getTypecast();
            }
        }

        return $schema;
    }

    private function renderReferences(Entity $entity): array
    {
        $schema = $entity->getPrimaryFields()->getNames();

        foreach ($entity->getFields() as $name => $field) {
            if ($field->isReferenced()) {
                $schema[] = $name;
            }
        }

        return array_unique($schema);
    }

    private function renderRelations(Registry $registry, Entity $entity, array &$schema): void
    {
        foreach ($registry->getRelations($entity) as $relation) {
            $relation->modifySchema($schema);
        }
    }

    /**
     * Return the unique alias for the child entity.
     */
    private function childAlias(Entity $entity): string
    {
        $r = new \ReflectionClass($entity->getClass());

        return $this->inflector->classify($r->getShortName());
    }
}
