<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;

class Dummy
{
    public static function makeEntity(): Entity
    {
        $entity = new Entity();
        $entity->setRole('dummy');

        return $entity;
    }
}