<?php

declare(strict_types=1);

namespace Cycle\Schema;

use Cycle\ORM\SchemaInterface as Schema;
use Cycle\Schema\Definition\Comparator\FieldComparator;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Inheritance\JoinedTable;
use Cycle\Schema\Definition\Inheritance\SingleTable;
use Cycle\Schema\Exception\CompilerException;
use Cycle\Schema\Exception\SchemaModifierException;
use Cycle\Schema\Exception\TableInheritance\DiscriminatorColumnNotPresentException;
use Cycle\Schema\Exception\TableInheritance\WrongDiscriminatorColumnException;
use Cycle\Schema\Exception\TableInheritance\WrongParentKeyColumnException;
use Throwable;

final class Compiler
{
    /** @var array<non-empty-string, array<int, mixed>> */
    private array $result = [];

    /**
     * Compile the registry schema.
     *
     * @param GeneratorInterface[] $generators
     */
    public function compile(Registry $registry, array $generators = [], array $defaults = []): array
    {
        $registry->getDefaults()->merge($defaults);

        foreach ($generators as $generator) {
            if (!$generator instanceof GeneratorInterface) {
                throw new CompilerException(
                    sprintf(
                        'Invalid generator `%s`. It should implement `%s` interface.',
                        \is_object($generator) ? $generator::class : \var_export($generator, true),
                        GeneratorInterface::class
                    )
                );
            }

            $registry = $generator->run($registry);
        }

        foreach ($registry->getIterator() as $entity) {
            if ($entity->hasPrimaryKey() || $entity->isChildOfSingleTableInheritance()) {
                $this->compute($registry, $entity);
            }
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
        $defaults = $registry->getDefaults();
        $role = $entity->getRole();
        \assert($role !== null);

        $schema = [
            Schema::ENTITY => $entity->getClass(),
            Schema::SOURCE => $entity->getSource() ?? $defaults[Schema::SOURCE],
            Schema::MAPPER => $entity->getMapper() ?? $defaults[Schema::MAPPER],
            Schema::REPOSITORY => $entity->getRepository() ?? $defaults[Schema::REPOSITORY],
            Schema::SCOPE => $entity->getScope() ?? $defaults[Schema::SCOPE],
            Schema::SCHEMA => $entity->getSchema(),
            Schema::TYPECAST_HANDLER => $this->renderTypecastHandler($registry->getDefaults(), $entity),
            Schema::PRIMARY_KEY => $entity->getPrimaryFields()->getNames(),
            Schema::COLUMNS => $this->renderColumns($entity),
            Schema::FIND_BY_KEYS => $this->renderReferences($entity),
            Schema::TYPECAST => $this->renderTypecast($entity),
            Schema::RELATIONS => [],
            Schema::GENERATED_FIELDS => $this->renderGeneratedFields($entity),
        ];

        // For table inheritance we need to fill specific schema segments
        $inheritance = $entity->getInheritance();
        if ($inheritance instanceof SingleTable) {
            // Check if discriminator column defined and is not null or empty
            $discriminator = $inheritance->getDiscriminator();
            if ($discriminator === null || $discriminator === '') {
                throw new DiscriminatorColumnNotPresentException($entity);
            }
            if (!$entity->getFields()->has($discriminator)) {
                throw new WrongDiscriminatorColumnException($entity, $discriminator);
            }

            $schema[Schema::CHILDREN] = $inheritance->getChildren();
            $schema[Schema::DISCRIMINATOR] = $discriminator;
        } elseif ($inheritance instanceof JoinedTable) {
            $schema[Schema::PARENT] = $inheritance->getParent()->getRole();
            assert($schema[Schema::PARENT] !== null);

            $parent = $registry->getEntity($schema[Schema::PARENT]);
            if ($inheritance->getOuterKey()) {
                if (!$parent->getFields()->has($inheritance->getOuterKey())) {
                    throw new WrongParentKeyColumnException($parent, $inheritance->getOuterKey());
                }
                $schema[Schema::PARENT_KEY] = $inheritance->getOuterKey();
            }
        }

        $this->renderRelations($registry, $entity, $schema);

        if ($registry->hasTable($entity)) {
            $schema[Schema::DATABASE] = $registry->getDatabase($entity);
            $schema[Schema::TABLE] = $registry->getTable($entity);
        }

        // Apply modifiers
        foreach ($entity->getSchemaModifiers() as $modifier) {
            \assert($modifier instanceof SchemaModifierInterface);
            try {
                $modifier->modifySchema($schema);
            } catch (Throwable $e) {
                throw new SchemaModifierException(
                    sprintf(
                        'Unable to apply schema modifier `%s` for the `%s` role. %s',
                        $modifier::class,
                        $role,
                        $e->getMessage()
                    ),
                    (int)$e->getCode(),
                    $e
                );
            }
        }

        // For STI child we need only schema role as a key and entity segment
        if ($entity->isChildOfSingleTableInheritance()) {
            $schema = \array_intersect_key($schema, [Schema::ENTITY, Schema::ROLE]);
        }

        /** @var array<int, mixed> $schema */
        ksort($schema);

        $this->result[$role] = $schema;
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
        foreach ($fieldGroups as $fields) {
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
                throw new Exception\CompilerException(sprintf(
                    "Error compiling the `%s` role.\n\n%s",
                    $entity->getRole() ?? 'unknown',
                    $e->getMessage()
                ), (int) $e->getCode());
            }
        }

        $schema = [];
        foreach ($entity->getFields() as $name => $field) {
            $schema[$name] = $field->getColumn();
        }

        return $schema;
    }

    private function renderGeneratedFields(Entity $entity): array
    {
        $schema = [];
        foreach ($entity->getFields() as $name => $field) {
            if ($field->getGenerated() !== null) {
                $schema[$name] = $field->getGenerated();
            }
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

    private function renderTypecastHandler(Defaults $defaults, Entity $entity): array|null|string
    {
        $defaults = $defaults[Schema::TYPECAST_HANDLER] ?? [];
        if (!\is_array($defaults)) {
            $defaults = [$defaults];
        }

        if ($defaults === []) {
            return $entity->getTypecast();
        }

        $typecast = $entity->getTypecast() ?? [];

        return \array_values(\array_unique(\array_merge(\is_array($typecast) ? $typecast : [$typecast], $defaults)));
    }
}
