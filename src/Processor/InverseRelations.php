<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Processor;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\ProcessorInterface;
use Cycle\Schema\Registry;

class InverseRelations implements ProcessorInterface
{
    /**
     * Inverse entity relations.
     *
     * @param Registry $registry
     * @param Entity   $entity
     */
    public function compute(Registry $registry, Entity $entity)
    {

    }
}