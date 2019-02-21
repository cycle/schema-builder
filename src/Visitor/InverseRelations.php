<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Visitor;

use Cycle\Schema\Builder;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\VisitorInterface;

class InverseRelations implements VisitorInterface
{
    /**
     * Inverse entity relations.
     *
     * @param Builder $builder
     * @param Entity  $entity
     */
    public function compute(Builder $builder, Entity $entity)
    {

    }
}