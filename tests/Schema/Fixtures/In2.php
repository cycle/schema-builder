<?php

/**
 * Cycle ORM Schema Builder.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Schema\Tests\Fixtures;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;

class In2 implements ParentInterface
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('in2');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'uuid',
            (new Field())->setType('primary')->setColumn('id')->setPrimary(true)
        );

        return $entity;
    }
}
