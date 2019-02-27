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
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;

class RelationReflector implements GeneratorInterface
{
    /**
     * @param Registry $registry
     * @param Entity   $entity
     */
    public function compute(Registry $registry, Entity $entity)
    {
        foreach ($registry->getRelations($entity) as $relation) {
            $relation->render($registry);
        }
    }
}