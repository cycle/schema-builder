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

interface CompilerInterface
{
    /**
     * Perform schema clarification/computation for given entity.
     *
     * @param Registry $builder
     * @param Entity   $entity
     */
    public function compute(Registry $builder, Entity $entity);
}