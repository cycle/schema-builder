<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Generator;

use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;

/**
 * Create inversed copy for specific relations.
 */
class InverseRelations implements GeneratorInterface
{
    /**
     * @param Registry $registry
     * @return Registry
     */
    public function run(Registry $registry): Registry
    {

    }
}