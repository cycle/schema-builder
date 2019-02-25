<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Compiler;

use Cycle\Schema\Registry;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\CompilerInterface;

/**
 * Render indexes and foreign keys defined by entity relations.
 */
class RenderRelations implements CompilerInterface
{
    public function compute(Registry $builder, Entity $entity)
    {
        if (!$builder->hasTable($entity)) {
            return;
        }

        foreach ($builder->getRelations($entity) as $name => $relation) {
            dump($relation);
        }
    }
}