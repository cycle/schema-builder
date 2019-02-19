<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema\Definition;

interface TableInterface
{
    /**
     * Entity specific column declarations (if any).
     *
     * @return ColumnInterface[]
     */
    public function getColumns(): array;

    /**
     * Entity specific index declaration (if any).
     *
     * @return IndexInterface[]
     */
    public function getIndexes(): array;
}