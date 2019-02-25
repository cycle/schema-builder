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

class InverseRelations implements CompilerInterface
{
    /**
     * Inverse entity relations.
     *
     * @param Registry $builder
     * @param Entity   $entity
     */
    public function compute(Registry $builder, Entity $entity)
    {

    }
}