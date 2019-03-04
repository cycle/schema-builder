<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Generator;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Generator\Traits\GeneratorTrait;
use Cycle\Schema\Registry;

/**
 * Renders all required relations columns, indexes and foreign keys.
 */
final class RenderRelations
{
    use GeneratorTrait;

    /**
     * @param Registry $registry
     * @param Entity   $entity
     */
    protected function compute(Registry $registry, Entity $entity)
    {
        foreach ($registry->getRelations($entity) as $relation) {
            $relation->render($registry);
        }
    }
}