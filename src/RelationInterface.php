<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema;

use Cycle\Schema\Exception\RelationException;

/**
 * Carries information about particular relation and table declaration required to properly
 * map two or more entities.
 */
interface RelationInterface
{
    /**
     * Create relation version linked to specific entity context.
     *
     * @param string $source
     * @param string $target
     * @param array  $options
     * @return RelationInterface
     *
     * @throws RelationException
     */
    public function withContext(string $source, string $target, array $options): RelationInterface;
}