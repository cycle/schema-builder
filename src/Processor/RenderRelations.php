<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Processor;

use Cycle\Schema\Registry;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\ProcessorInterface;

/**
 * Render indexes and foreign keys defined by entity relations.
 */
class RenderRelations implements ProcessorInterface
{
    public function compute(Registry $registry, Entity $entity)
    {
        if (!$registry->hasTable($entity)) {
            return;
        }

        foreach ($registry->getRelations($entity) as $name => $relation) {
            dump($relation);
        }
    }
}