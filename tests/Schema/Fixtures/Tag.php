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

class Tag implements ParentInterface
{
    public static function define(): Entity
    {
        $entity = new Entity();
        $entity->setRole('tag');
        $entity->setClass(self::class);

        $entity->getFields()->set(
            'id',
            (new Field())->setType('bigPrimary')->setColumn('id')->setPrimary(true)
        );

        $entity->getFields()->set(
            'name',
            (new Field())->setType('string(32)')->setColumn('name')
        );

        return $entity;
    }

    public static function defineCompositePK(): Entity
    {
        $entity = self::define();

        $entity->getFields()->set(
            'slug',
            (new Field())->setType('primary')->setColumn('slug')->setPrimary(true)
        );

        return $entity;
    }
}
