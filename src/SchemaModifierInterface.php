<?php

declare(strict_types=1);

namespace Cycle\Schema;

use Cycle\Database\Exception\DBALException;
use Cycle\Schema\Exception\SchemaModifierException;

/**
 * Carries information about any entity and table declarations.
 */
interface SchemaModifierInterface
{
    /**
     * @param non-empty-string $role
     */
    public function withRole(string $role): static;

    /**
     * Compute structure changes (table column names, types etc). Also ensures existence of fields in entities.
     *
     * @throws SchemaModifierException
     */
    public function compute(Registry $registry): void;

    /**
     * Render needed indexes and foreign keys into table.
     *
     * @param Registry $registry
     *
     * @throws SchemaModifierException
     * @throws DBALException
     */
    public function render(Registry $registry): void;

    /**
     * Modify Entity schema array
     *
     * @param array $schema Entity schema
     */
    public function modifySchema(array &$schema): void;
}
