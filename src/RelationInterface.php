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
use Cycle\Schema\Relation\Util\OptionRouter;

/**
 * Carries information about particular relation and table declaration required to properly
 * map two or more entities.
 */
interface RelationInterface
{
    /**
     * Create relation version linked to specific entity context.
     *
     * @param string       $name
     * @param string       $source
     * @param string       $target
     * @param OptionRouter $options
     * @return RelationInterface
     *
     * @throws RelationException
     */
    public function withContext(string $name, string $source, string $target, OptionRouter $options): RelationInterface;

    /**
     * Compute relation references (column names and etc).
     *
     * @param Registry $registry
     *
     * @throws RelationException
     */
    public function compute(Registry $registry);

    /**
     * @return array
     */
    public function packSchema(): array;

    // todo: packSchema
    // todo: renderTable
    // todo: constrain options
    // todo: inverse relation
}