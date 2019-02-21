<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema;

use Cycle\Schema\Definition\Entity;

interface VisitorInterface
{
    /**
     * Perform schema clarification/computation for given entity.
     *
     * @param Builder $builder
     * @param Entity  $entity
     */
    public function compute(Builder $builder, Entity $entity);
}