<?php

declare(strict_types=1);

namespace Cycle\Schema;

use Cycle\Schema\Exception\RelationException;
use Cycle\Schema\Relation\OptionSchema;
use Cycle\Database\Exception\DBALException;

/**
 * Carries information about particular relation and table declaration required to properly
 * map two or more entities.
 */
interface RelationInterface extends SchemaModifierInterface
{
    /**
     * Create relation version linked to specific entity context.
     *
     * @throws RelationException
     */
    public function withContext(
        string $name,
        string $source,
        string $target,
        OptionSchema $options
    ): self;

    /**
     * @return array
     */
    public function packSchema(): array;
}
