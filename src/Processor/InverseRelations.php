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

class InverseRelations implements ProcessorInterface
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