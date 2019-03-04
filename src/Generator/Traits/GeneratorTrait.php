<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Generator\Traits;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Registry;

trait GeneratorTrait
{
    /**
     * Iterate over registry.
     *
     * @param Registry $registry
     * @return Registry
     */
    public function run(Registry $registry): Registry
    {
        foreach ($registry->getIterator() as $entity) {
            $this->compute($registry, $entity);
        }

        return $registry;
    }

    /**
     * @param Registry $registry
     * @param Entity   $entity
     */
    abstract protected function compute(Registry $registry, Entity $entity);
}