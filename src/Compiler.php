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

final class Compiler implements ProcessorInterface
{
    /** @var array */
    private $result = [];

    /**
     * Get compiled schema result.
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Compile entity and relation definitions into packed ORM schema.
     *
     * @param Registry $builder
     * @param Entity   $entity
     */
    public function compute(Registry $builder, Entity $entity)
    {

    }
}