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
 * Declare table dropped (initiate diff calculation).
 */
final class CleanTables implements GeneratorInterface
{
    /**
     * @param Registry $registry
     * @return Registry
     */
    public function run(Registry $registry): Registry
    {
        foreach ($registry as $entity) {
            if (!$registry->hasTable($entity)) {
                continue;
            }

            if (!$entity->getOptions()->has(RenderTables::READONLY)) {
                $registry->getTableSchema($entity)->declareDropped();
            }
        }

        return $registry;
    }
}